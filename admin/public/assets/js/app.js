class App {
    constructor() {
        this.currentSection = 'bots';
        this.currentBot = null;
        this.bots = [];
        this.content = {};
        
        this.init();
    }

    init() {
        // Ждем загрузки DOM и авторизации
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (api.isAuthenticated()) {
                    this.initializeApp();
                }
            }, 100);
        });
    }

    initializeApp() {
        this.setupNavigation();
        this.setupEventHandlers();
        this.loadInitialData();
    }

    setupNavigation() {
        const navButtons = document.querySelectorAll('.nav-btn');
        navButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const section = e.target.dataset.section;
                this.switchSection(section);
            });
        });
    }

    switchSection(section) {
        // Обновляем навигацию
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-section="${section}"]`).classList.add('active');

        // Показываем нужную секцию
        document.querySelectorAll('.section').forEach(sec => {
            sec.classList.remove('active');
            sec.classList.add('hidden');
        });
        document.getElementById(`${section}-section`).classList.remove('hidden');
        document.getElementById(`${section}-section`).classList.add('active');

        this.currentSection = section;
        this.loadSectionData(section);
    }

    async loadSectionData(section) {
        try {
            switch (section) {
                case 'bots':
                    await this.loadBots();
                    break;
                case 'content':
                    await this.loadContent();
                    break;
                case 'broadcast':
                    await this.loadBroadcasts();
                    break;
                case 'users':
                    await this.loadUsers();
                    break;
            }
        } catch (error) {
            notifications.error(`Ошибка загрузки данных: ${error.message}`);
        }
    }

    setupEventHandlers() {
        // Добавление бота
        document.getElementById('addBotBtn')?.addEventListener('click', () => {
            this.showAddBotModal();
        });

        // Создание рассылки
        document.getElementById('createBroadcastBtn')?.addEventListener('click', () => {
            if (!this.currentBot) {
                notifications.warning('Сначала выберите бота');
                return;
            }
            this.showCreateBroadcastModal();
        });
    }

    async loadInitialData() {
        const loadingId = notifications.loading('Загрузка данных...');
        try {
            await this.loadBots();
            notifications.remove(loadingId);
        } catch (error) {
            notifications.remove(loadingId);
            notifications.error('Ошибка загрузки данных');
        }
    }

    async loadBots() {
        try {
            this.bots = await api.getBots();
            this.renderBots();
            
            // Выбираем первого бота по умолчанию
            if (this.bots.length > 0 && !this.currentBot) {
                this.currentBot = this.bots[0];
            }
        } catch (error) {
            notifications.error('Ошибка загрузки ботов');
        }
    }

    renderBots() {
        const container = document.getElementById('botsList');
        if (!container) return;

        if (this.bots.length === 0) {
            container.innerHTML = '<p>Нет добавленных ботов. Добавьте первого бота для начала работы.</p>';
            return;
        }

        container.innerHTML = `
            <div class="bots-grid">
                ${this.bots.map(bot => `
                    <div class="bot-card ${this.currentBot?.id === bot.id ? 'active' : ''}" data-bot-id="${bot.id}">
                        <div class="bot-card-header">
                            <h4>${this.escapeHtml(bot.name)}</h4>
                            <span class="bot-status ${bot.status}">${bot.status === 'active' ? 'Активен' : 'Неактивен'}</span>
                        </div>
                        <div class="bot-card-body">
                            <p><strong>Тема:</strong> ${this.escapeHtml(bot.theme)}</p>
                            <p><strong>Создан:</strong> ${new Date(bot.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="bot-card-footer">
                            <button class="btn btn-sm" onclick="app.selectBot(${bot.id})">Выбрать</button>
                            <button class="btn btn-sm btn-warning" onclick="app.editBot(${bot.id})">Настроить</button>
                            ${auth.hasPermission('delete_bot') ? `<button class="btn btn-sm btn-danger" onclick="app.deleteBot(${bot.id})">Удалить</button>` : ''}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        // Добавляем обработчики клика
        container.querySelectorAll('.bot-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    const botId = parseInt(card.dataset.botId);
                    this.selectBot(botId);
                }
            });
        });
    }

    selectBot(botId) {
        this.currentBot = this.bots.find(bot => bot.id === botId);
        this.renderBots(); // Перерендерим для обновления активного состояния
        notifications.info(`Выбран бот: ${this.currentBot.name}`);
    }

    showAddBotModal() {
        const modal = this.createModal('Добавить бота', `
            <form id="addBotForm">
                <div class="form-group">
                    <label>Название бота</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Токен бота</label>
                    <input type="text" name="token" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Webhook URL</label>
                    <input type="url" name="webhook_url" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Тематика</label>
                    <select name="theme" class="form-control">
                        <option value="general">Общая</option>
                        <option value="yoga">Йога</option>
                        <option value="business">Бизнес</option>
                        <option value="education">Образование</option>
                        <option value="health">Здоровье</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="app.closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        `);

        modal.querySelector('#addBotForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleAddBot(new FormData(e.target));
        });
    }

    async handleAddBot(formData) {
        const submitBtn = document.querySelector('#addBotForm button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Добавление...';

        try {
            const botData = {
                name: formData.get('name'),
                token: formData.get('token'),
                webhook_url: formData.get('webhook_url'),
                theme: formData.get('theme')
            };

            await api.addBot(botData);
            notifications.success('Бот успешно добавлен');
            this.closeModal();
            await this.loadBots();
        } catch (error) {
            notifications.error(`Ошибка добавления бота: ${error.message}`);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Добавить';
        }
    }

    async deleteBot(botId) {
        const bot = this.bots.find(b => b.id === botId);
        if (!confirm(`Вы уверены, что хотите удалить бота "${bot.name}"? Это действие нельзя отменить.`)) {
            return;
        }

        try {
            await api.deleteBot(botId);
            notifications.success('Бот удален');
            await this.loadBots();
            
            if (this.currentBot?.id === botId) {
                this.currentBot = this.bots[0] || null;
            }
        } catch (error) {
            notifications.error(`Ошибка удаления бота: ${error.message}`);
        }
    }

    async loadContent() {
        if (!this.currentBot) {
            document.getElementById('contentEditor').innerHTML = '<p>Выберите бота для управления контентом</p>';
            return;
        }

        try {
            const content = await api.getBotContent(this.currentBot.id);
            this.renderContentEditor(content);
        } catch (error) {
            notifications.error('Ошибка загрузки контента');
        }
    }

    renderContentEditor(content) {
        const container = document.getElementById('contentEditor');
        // Реализация редактора контента будет в следующем шаге
        container.innerHTML = '<div class="content-editor">Редактор контента (в разработке)</div>';
    }

    createModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3>${title}</h3>
                    <button class="modal-close" onclick="app.closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    }

    closeModal() {
        const modal = document.querySelector('.modal-overlay');
        if (modal) {
            modal.remove();
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async loadBroadcasts() {
        // Заглушка для рассылок
        document.getElementById('broadcastList').innerHTML = '<p>Функционал рассылок (в разработке)</p>';
    }

    async loadUsers() {
        // Заглушка для пользователей  
        document.getElementById('usersList').innerHTML = '<p>Управление пользователями (в разработке)</p>';
    }
}

// Инициализация приложения
window.app = new App();