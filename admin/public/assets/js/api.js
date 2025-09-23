class API {
    constructor() {
        this.baseURL = '../modules/api/endpoints.php';
        this.token = localStorage.getItem('admin_token');
    }

    async request(endpoint, options = {}) {
        const config = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        if (this.token) {
            config.headers['Authorization'] = `Bearer ${this.token}`;
        }

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(`${this.baseURL}/${endpoint}`, config);

            if (response.status === 401) {
                this.logout();
                throw new Error('Unauthorized');
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Авторизация
    // Авторизация
    async login(telegramId, authCode) {
        try {
            // Используем абсолютный путь от корня сервера
            const loginUrl = '/modules/auth/login.php';
            console.log('Sending login request to:', loginUrl);
            console.log('Data:', { telegram_id: telegramId, auth_code: authCode });

            const response = await fetch(loginUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    telegram_id: telegramId,
                    auth_code: authCode
                })
            });

            console.log('Response status:', response.status);
            console.log('Response URL:', response.url);

            const responseText = await response.text();
            console.log('Raw response:', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was:', responseText);
                throw new Error('Server returned invalid JSON. Response: ' + responseText.substring(0, 200));
            }

            if (!response.ok) {
                throw new Error(data.error || 'Login failed');
            }

            this.token = data.token;
            localStorage.setItem('admin_token', this.token);
            localStorage.setItem('user_role', data.role);

            return data;
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    }

    logout() {
        this.token = null;
        localStorage.removeItem('admin_token');
        localStorage.removeItem('user_role');
        window.location.reload();
    }

    // Боты
    async getBots() {
        return this.request('bots');
    }

    async addBot(botData) {
        return this.request('bots', {
            method: 'POST',
            body: botData
        });
    }

    async deleteBot(botId) {
        return this.request(`bots/${botId}`, {
            method: 'DELETE'
        });
    }

    // Контент
    async getBotContent(botId, contentKey = '') {
        const endpoint = contentKey ? `content/${botId}/${contentKey}` : `content/${botId}`;
        return this.request(endpoint);
    }

    async saveContent(botId, contentKey, contentData) {
        return this.request(`content/${botId}/${contentKey}`, {
            method: 'POST',
            body: contentData
        });
    }

    async deleteContent(botId, contentKey) {
        return this.request(`content/${botId}/${contentKey}`, {
            method: 'DELETE'
        });
    }

    // Рассылки
    async getBroadcasts(botId) {
        return this.request(`broadcasts/${botId}`);
    }

    async createBroadcast(botId, broadcastData) {
        return this.request(`broadcast`, {
            method: 'POST',
            body: { bot_id: botId, ...broadcastData }
        });
    }

    async startBroadcast(broadcastId) {
        return this.request(`broadcast/${broadcastId}/start`, {
            method: 'POST'
        });
    }

    // Пользователи
    async getUsers(botId, filters = {}) {
        const params = new URLSearchParams({
            bot_id: botId,
            ...filters
        });
        return this.request(`users?${params}`);
    }

    async blockUser(botId, userId, reason = '') {
        return this.request(`users/block`, {
            method: 'POST',
            body: { bot_id: botId, user_id: userId, reason }
        });
    }

    async unblockUser(botId, userId) {
        return this.request(`users/unblock`, {
            method: 'POST',
            body: { bot_id: botId, user_id: userId }
        });
    }

    // Статистика
    async getStats(botId, period = '30d') {
        return this.request(`stats/${botId}?period=${period}`);
    }

    // Загрузка медиа
    async uploadMedia(file, botId) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('bot_id', botId);

        try {
            const response = await fetch('../modules/api/upload.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Upload failed');
            }

            return data;
        } catch (error) {
            console.error('Upload error:', error);
            throw error;
        }
    }

    // OpenAI управление
    async getOpenAISettings(botId) {
        return this.request(`openai/${botId}/settings`);
    }

    async saveOpenAISettings(botId, settings) {
        return this.request(`openai/${botId}/settings`, {
            method: 'POST',
            body: settings
        });
    }

    async getTokenUsage(botId, period = '7d') {
        return this.request(`openai/${botId}/usage?period=${period}`);
    }

    // Проверка состояния токена
    isAuthenticated() {
        return !!this.token;
    }

    getUserRole() {
        return localStorage.getItem('user_role');
    }
}

// Создаем глобальный экземпляр API
window.api = new API();