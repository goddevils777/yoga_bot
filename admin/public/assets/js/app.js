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

    async initializeApp() {
        this.setupNavigation();
        this.setupEventHandlers();

        // Сначала загружаем данные
        await this.loadInitialData();

        // Потом восстанавливаем секцию
        const savedSection = localStorage.getItem('currentSection') || 'bots';
        await this.switchSection(savedSection);
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

        const navBtn = document.querySelector(`[data-section="${section}"]`);
        if (navBtn) {
            navBtn.classList.add('active');
        }

        // Показываем нужную секцию
        document.querySelectorAll('.section').forEach(sec => {
            sec.classList.remove('active');
            sec.classList.add('hidden');
        });

        const sectionElement = document.getElementById(`${section}-section`);
        if (sectionElement) {
            sectionElement.classList.remove('hidden');
            sectionElement.classList.add('active');
        } else {
            console.error('Section not found:', section);
            return;
        }

        this.currentSection = section;
        localStorage.setItem('currentSection', section);
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
        await this.loadBots();
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
            'Меню': ['start_message', 'primary_menu'],
            'Основные разделы': ['developing_programs', 'online_yoga', 'tours_and_retreats', 'detox_programs'],
            'Развивающие программы': ['aroma_diagnostics', 'successful_year', 'longevity_foundation', 'inner_support', 'vipassana_online', 'kids_yoga', 'dharma_code', 'light_detox'],
            'Йога онлайн': ['live_yoga', 'our_learning_platform', 'free_classes'],
            'Туры': ['tours_calendar', 'thailand_retreat', 'bali_retreat', 'nepal_tour', 'japan_zen_tour', 'kailas_tour'],
            'Детокс программы': ['detox_3days', 'detox_7days']
        };
        let html = '<div class="content-editor-wrapper"><div class="content-categories">';

        for (const [categoryName, keys] of Object.entries(categories)) {
            const categoryItems = this.content.filter(item => keys.includes(item.content_key));
            if (categoryItems.length === 0) continue;

            // Проверяем сохраненное состояние категории
            const isCollapsed = this.getCategoryState(categoryName);
            const collapsedClass = isCollapsed ? 'collapsed' : '';

            html += `
                <div class="content-category">
                    <div class="category-header ${collapsedClass}" onclick="app.toggleCategory(this, '${categoryName}')">
                    <h4 class="category-title">${categoryName} (${categoryItems.length})</h4>
                    <span class="category-toggle">▼</span>
                </div>
                <div class="category-body ${collapsedClass}">
                    ${categoryItems.map((item, index) => `
                            <div class="content-item" data-id="${item.id}">
                                <div class="content-item-main">
                                <div class="content-item-header">
                                    <h5>${this.escapeHtml(item.title)}</h5>
                                    <span class="content-status ${item.status}">${item.status === 'active' ? 'Активен' : 'Неактивен'}</span>
                                </div>
                                <div class="content-item-body">
                                    <p class="content-preview">${this.escapeHtml(item.text?.substring(0, 100) || '')}...</p>
                                </div>
                                <div class="content-item-footer">
                                    <button class="btn btn-sm" onclick="app.editContent(${item.id})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        Редактировать
                                    </button>
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
        console.log('=== EDIT CONTENT DEBUG ===');
        console.log('Received contentId:', contentId, typeof contentId);
        console.log('Current content array:', this.content);

        const item = this.content.find(c => {
            console.log('Comparing:', c.id, typeof c.id, 'with', contentId, typeof contentId);
            return c.id === parseInt(contentId);
        });

        console.log('Found item:', item);

        if (!item) {
            notifications.error('Контент не найден');
            console.error('Content not found for ID:', contentId);
            return;
        }

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
            if (item.buttons) {
                let buttonsData = item.buttons;

                console.log('Raw buttons from server:', buttonsData);

                // Если это строка - парсим
                if (typeof buttonsData === 'string') {
                    // Убираем внешние кавычки если есть
                    buttonsData = buttonsData.replace(/^"(.*)"$/, '$1');
                    // Заменяем экранированные слеши
                    buttonsData = buttonsData.replace(/\\"/g, '"');

                    console.log('After cleanup:', buttonsData);

                    buttons = JSON.parse(buttonsData);
                } else if (Array.isArray(buttonsData)) {
                    buttons = buttonsData;
                }
            }

            // Проверяем что результат - массив
            if (!Array.isArray(buttons)) {
                console.warn('Buttons is not an array after parsing:', buttons);
                buttons = [];
            }
        } catch (e) {
            console.error('Error parsing buttons:', e, item.buttons);
            buttons = [];
        }

        console.log('Final parsed buttons:', buttons);

        // Создаем временный элемент для декодирования HTML-сущностей
        const decodeHtml = (html) => {
            const txt = document.createElement('textarea');
            txt.innerHTML = html;
            return txt.value;
        };

        const modal = this.createModal('Редактировать контент', `
        <form id="editContentForm">
            <input type="hidden" name="id" value="${item.id}">
            <input type="hidden" name="content_key" value="${item.content_key}">
            
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="title" class="form-control" value="${this.escapeHtml(decodeHtml(item.title))}" required>
            </div>
            
            <div class="form-group">
                <label>Ключ контента</label>
                <input type="text" class="form-control" value="${item.content_key}" readonly>
            </div>
            
            <div class="form-group">
                <label>Текст сообщения</label>
                <textarea name="text" class="form-control" rows="6" required>${decodeHtml(item.text || '')}</textarea>
            </div>
        
            <div class="form-group">
                <label>Кнопки</label>
                <div id="buttonsContainer">
                    ${buttons.map((btn, i) => this.renderButtonEditor(btn, i)).join('')}
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="app.addButton()">+ Добавить кнопку</button>
            </div>

            <div class="form-group">
                <label>Изображение</label>
                
                <div id="imagePreviewContainer" style="margin: 10px 0; display: ${item.media_id ? 'block' : 'none'}; position: relative;">
                    <img id="imagePreview" src="" style="max-width: 100%; border-radius: 8px; border: 2px dashed #cbd5e1; padding: 4px;" />
                    <button type="button" class="remove-image-btn" onclick="app.removeImagePreview(${item.id})" title="Удалить изображение">×</button>
                </div>
                
                <label for="mediaInput" class="file-upload-label">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    Выбрать изображение
                </label>
                <input type="file" id="mediaInput" name="media" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="app.previewImage(event)">
                <small class="form-text">Макс. размер: 10 МБ. Форматы: JPG, PNG, GIF, WebP</small>
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

        // Если есть media_id - показываем текущее изображение
        if (item.media_id) {
            setTimeout(() => {
                const preview = document.getElementById('imagePreview');
                const container = document.getElementById('imagePreviewContainer');

                if (preview && container) {
                    preview.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="400" height="200" fill="%23f3f4f6"/><text x="50%" y="50%" text-anchor="middle" fill="%236b7280" font-size="16">Изображение загружено</text></svg>';
                    container.style.display = 'block';
                }
            }, 100);
        }

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

    async removeImage(contentId) {
        if (!confirm('Удалить изображение из этого контента?')) return;

        try {
            const content = this.content.find(c => c.id === contentId);
            if (!content) {
                notifications.error('Контент не найден');
                return;
            }

            await api.saveContent(this.currentBot.id, content.content_key, {
                id: contentId,
                media_id: null,
                media_type: null
            });

            notifications.success('Изображение удалено');
            await this.loadContent();
            this.closeModal();
        } catch (error) {
            notifications.error('Ошибка удаления изображения');
        }
    }

    previewImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        const container = document.getElementById('imagePreviewContainer');

        if (file) {
            // Проверка размера
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                notifications.error('Файл слишком большой. Максимум 10 МБ');
                event.target.value = '';
                container.style.display = 'none';
                return;
            }

            // Проверка типа
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                notifications.error('Недопустимый формат. Используйте JPG, PNG, GIF или WebP');
                event.target.value = '';
                container.style.display = 'none';
                return;
            }

            // Показываем превью
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                container.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            container.style.display = 'none';
        }
    }

    async removeImagePreview(contentId) {
        if (!confirm('Удалить изображение из этого контента?')) return;

        try {
            const content = this.content.find(c => c.id === contentId);
            if (!content) {
                notifications.error('Контент не найден');
                return;
            }

            await api.saveContent(this.currentBot.id, content.content_key, {
                id: contentId,
                title: content.title,
                text: content.text,
                status: content.status,
                buttons: content.buttons,
                media_id: null,
                media_type: null
            });

            notifications.success('Изображение удалено');

            // Скрываем превью
            const container = document.getElementById('imagePreviewContainer');
            if (container) {
                container.style.display = 'none';
            }

            await this.loadContent();
        } catch (error) {
            notifications.error('Ошибка удаления изображения');
        }
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
            // Проверяем, загружено ли новое изображение
            const mediaFile = formData.get('media');
            let mediaData = {};

            if (mediaFile && mediaFile.size > 0) {
                // Валидация файла
                const maxSize = 10 * 1024 * 1024; // 10 MB
                if (mediaFile.size > maxSize) {
                    notifications.error('Файл слишком большой. Максимум 10 МБ');
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(mediaFile.type)) {
                    notifications.error('Недопустимый формат файла. Используйте JPG, PNG, GIF или WebP');
                    return;
                }

                // Загружаем изображение
                notifications.info('Загрузка изображения...');
                const uploadResult = await api.uploadMedia(mediaFile, this.currentBot.id);
                mediaData.media_id = uploadResult.file_id;
                mediaData.media_type = 'photo';
            }

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
                id: formData.get('id'),
                title: formData.get('title'),
                text: formData.get('text'),
                status: formData.get('status'),
                buttons: JSON.stringify(buttons),
                ...mediaData
            };

            console.log('Saving content with data:', data);

            await api.saveContent(this.currentBot.id, formData.get('content_key'), data);
            notifications.success('Контент сохранён');
            await this.loadContent();
            this.closeModal();
        } catch (error) {
            notifications.error(`Ошибка сохранения: ${error.message}`);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Сохранить';
        }
    }

    toggleCategory(element, categoryName) {
        element.classList.toggle('collapsed');
        element.nextElementSibling.classList.toggle('collapsed');

        // Сохраняем состояние
        const isCollapsed = element.classList.contains('collapsed');
        this.saveCategoryState(categoryName, isCollapsed);
    }

    getCategoryState(categoryName) {
        const states = JSON.parse(localStorage.getItem('categoryStates') || '{}');
        // По умолчанию все свернуты, кроме "Меню"
        return states[categoryName] !== undefined ? states[categoryName] : (categoryName !== 'Меню');
    }

    saveCategoryState(categoryName, isCollapsed) {
        const states = JSON.parse(localStorage.getItem('categoryStates') || '{}');
        states[categoryName] = isCollapsed;
        localStorage.setItem('categoryStates', JSON.stringify(states));
    }
}

// Инициализация приложения
window.app = new App();