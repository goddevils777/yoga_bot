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

    reinitialize() {
        this.currentSection = 'bots';
        this.currentBot = null;
        this.bots = [];
        this.content = {};

        this.setupNavigation();
        this.setupEventHandlers();
        this.loadInitialData();
    }

    setupNavigation() {
        const navButtons = document.querySelectorAll('.nav-btn');
        navButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const section = e.target.dataset.section;
                await this.switchSection(section);
            });
        });
    }

    async switchSection(section) {
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
        await this.loadSectionData(section);
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

            // Автоматически выбираем первого (единственного) бота
            if (this.bots.length > 0) {
                this.currentBot = this.bots[0];
                this.renderBotSettings();
            } else {
                notifications.warning('Бот не найден в системе');
            }
        } catch (error) {
            notifications.error('Ошибка загрузки бота');
        }
    }

    renderBotSettings() {
        const container = document.getElementById('botSettings');
        if (!container || !this.currentBot) return;

        container.innerHTML = `
        <div class="bot-info-card">
            <div class="bot-info-header">
                <h4>${this.escapeHtml(this.currentBot.name)}</h4>
                <span class="bot-status ${this.currentBot.status}">${this.currentBot.status === 'active' ? 'Активен' : 'Неактивен'}</span>
            </div>
            <div class="bot-info-body">
                <div class="info-row">
                    <span class="info-label">Тема:</span>
                    <span class="info-value">${this.escapeHtml(this.currentBot.theme)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Webhook:</span>
                    <span class="info-value">${this.escapeHtml(this.currentBot.webhook_url)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Создан:</span>
                    <span class="info-value">${new Date(this.currentBot.created_at).toLocaleString('ru-RU')}</span>
                </div>
            </div>
        </div>
    `;
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
            document.getElementById('contentEditor').innerHTML = '<p>Бот не найден</p>';
            return;
        }

        try {
            const content = await api.getBotContent(this.currentBot.id);
            this.content = content;  // ✅ Сохраняем
            this.renderContentList();  // ✅ Правильный метод
        } catch (error) {
            notifications.error('Ошибка загрузки контента');
        }
    }

    renderContentList() {
        const container = document.getElementById('contentEditor');
        if (!container) return;

        if (!this.content || this.content.length === 0) {
            container.innerHTML = `<div class="empty-state"><p>📝 Контент не найден</p></div>`;
            return;
        }

        const categories = {
            'Меню': ['primary_menu', 'start_message'],
            'Основные разделы': ['developing_programs', 'online_yoga', 'tours_and_retreats', 'detox_programs'],
            'Развивающие программы': ['aroma_diagnostics', 'successful_year', 'longevity_foundation', 'inner_support', 'vipassana_online', 'kids_yoga', 'dharma_code'],
            'Детокс программы': ['light_detox', 'detox_3days', 'detox_7days'],
            'Йога онлайн': ['live_yoga', 'our_learning_platform', 'free_classes'],
            'Туры': ['tours_calendar']
        };

        let html = '<div class="content-editor-wrapper"><div class="content-categories">';

        for (const [categoryName, keys] of Object.entries(categories)) {
            const categoryItems = this.content.filter(item => keys.includes(item.content_key));
            if (categoryItems.length === 0) continue;

            html += `
            <div class="content-category">
                <div class="category-header" onclick="this.classList.toggle('collapsed'); this.nextElementSibling.classList.toggle('collapsed')">
                    <h4 class="category-title">${categoryName} (${categoryItems.length})</h4>
                    <span class="category-toggle">▼</span>
                </div>
                <div class="category-body">
                    ${categoryItems.map((item, index) => `
                        <div class="content-item" data-id="${item.id}">
                            <div class="sort-buttons">
                                ${index > 0 ? `<button class="btn-sort" onclick="app.moveContent(${item.id}, 'up')">▲</button>` : '<span style="height:24px"></span>'}
                                ${index < categoryItems.length - 1 ? `<button class="btn-sort" onclick="app.moveContent(${item.id}, 'down')">▼</button>` : ''}
                            </div>
                            <div class="content-item-main">
                                <div class="content-item-header">
                                    <h5>${this.escapeHtml(item.title)}</h5>
                                    <span class="content-status ${item.status}">${item.status === 'active' ? 'Активен' : 'Неактивен'}</span>
                                </div>
                                <div class="content-item-body">
                                    <p class="content-preview">${this.escapeHtml(item.text?.substring(0, 100) || '')}...</p>
                                </div>
                                <div class="content-item-footer">
                                    <button class="btn btn-sm" onclick="app.editContent(${item.id})">✏️ Редактировать</button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        }

        html += '</div></div>';
        container.innerHTML = html;
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

    editContent(contentId) {
        const item = this.content.find(c => c.id === contentId);
        if (!item) return;

        this.showEditContentModal(item);
    }

    async deleteContent(contentId) {
        if (!confirm('Удалить этот контент?')) return;

        try {
            await api.deleteContent(this.currentBot.id, contentId);
            notifications.success('Контент удалён');
            await this.loadContent();
        } catch (error) {
            notifications.error('Ошибка удаления');
        }
    }

    showAddContentModal() {
        notifications.info('Функция добавления контента (в разработке)');
    }

    showEditContentModal(item) {
        // Парсим кнопки из JSON
        let buttons = [];
        try {
            buttons = item.buttons ? JSON.parse(item.buttons) : [];
        } catch (e) {
            buttons = [];
        }

        const modal = this.createModal('Редактировать контент', `
        <form id="editContentForm">
            <input type="hidden" name="id" value="${item.id}">
            
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="title" class="form-control" value="${this.escapeHtml(item.title)}" required>
            </div>
            
            <div class="form-group">
                <label>Ключ контента</label>
                <input type="text" name="content_key" class="form-control" value="${item.content_key}" readonly>
            </div>
            
            <div class="form-group">
                <label>Текст сообщения</label>
                <textarea name="text" class="form-control" rows="6" required>${this.escapeHtml(item.text || '')}</textarea>
            </div>
            
            <div class="form-group">
                <label>Кнопки</label>
                <div id="buttonsContainer">
                    ${buttons.map((btn, i) => this.renderButtonEditor(btn, i)).join('')}
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="app.addButton()">+ Добавить кнопку</button>
            </div>
            
            <div class="form-group">
                <label>Статус</label>
                <select name="status" class="form-control">
                    <option value="active" ${item.status === 'active' ? 'selected' : ''}>Активен</option>
                    <option value="inactive" ${item.status === 'inactive' ? 'selected' : ''}>Неактивен</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="app.deleteContentFromModal(${item.id})">🗑 Удалить блок</button>
                <div style="flex: 1"></div>
                <button type="button" class="btn btn-secondary" onclick="app.closeModal()">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    `);

        modal.querySelector('#editContentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleSaveContent(new FormData(e.target));
        });
    }


    async deleteContentFromModal(contentId) {
        if (!confirm('Удалить этот блок контента? Это действие нельзя отменить.')) return;

        this.closeModal();
        await this.deleteContent(contentId);
    }

    renderButtonEditor(btn, index) {
        return `
        <div class="button-editor" data-index="${index}">
            <div class="button-row">
                <input type="text" class="form-control" placeholder="Текст кнопки" 
                       value="${this.escapeHtml(btn.text || '')}" data-field="text">
                <select class="form-control" data-field="type">
                    <option value="callback" ${!btn.url ? 'selected' : ''}>Команда</option>
                    <option value="url" ${btn.url ? 'selected' : ''}>Ссылка</option>
                </select>
                <input type="text" class="form-control" placeholder="${btn.url ? 'URL' : 'Команда (например: /menu)'}" 
                       value="${this.escapeHtml(btn.callback_data || btn.url || '')}" data-field="value">
                <button type="button" class="btn btn-sm btn-danger" onclick="app.removeButton(${index})">×</button>
            </div>
        </div>
    `;
    }

    addButton() {
        const container = document.getElementById('buttonsContainer');
        const index = container.children.length;
        const newBtn = this.renderButtonEditor({ text: '', callback_data: '' }, index);
        container.insertAdjacentHTML('beforeend', newBtn);
    }

    removeButton(index) {
        document.querySelector(`.button-editor[data-index="${index}"]`)?.remove();
    }

async handleSaveContent(formData) {
    const submitBtn = document.querySelector('#editContentForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Сохранение...';

    try {
        // Собираем кнопки из формы
        const buttonEditors = document.querySelectorAll('.button-editor');
        const buttons = [];
        
        buttonEditors.forEach(editor => {
            const text = editor.querySelector('[data-field="text"]').value.trim();
            const type = editor.querySelector('[data-field="type"]').value;
            const value = editor.querySelector('[data-field="value"]').value.trim();
            
            if (text && value) {
                if (type === 'url') {
                    buttons.push({ text, url: value });
                } else {
                    buttons.push({ text, callback_data: value });
                }
            }
        });

        const data = {
            title: formData.get('title'),
            text: formData.get('text'),
            status: formData.get('status'),
            buttons: JSON.stringify(buttons)
        };

        await api.saveContent(this.currentBot.id, formData.get('content_key'), data);
        notifications.success('Контент сохранён');
        this.closeModal();
        await this.loadContent();
    } catch (error) {
        notifications.error(`Ошибка сохранения: ${error.message}`);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Сохранить';
    }
}

    moveContent(contentId, direction) {
        notifications.info(`Сортировка (сохранение порядка - в разработке)`);
    }
}

// Инициализация приложения
window.app = new App();