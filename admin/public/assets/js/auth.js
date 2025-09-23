class Auth {
    constructor() {
        this.loginForm = document.getElementById('loginForm');
        this.mainApp = document.getElementById('mainApp');
        this.authForm = document.getElementById('authForm');
        this.logoutBtn = document.getElementById('logoutBtn');
        this.userRoleSpan = document.getElementById('userRole');
        
        this.init();
    }

    init() {
        if (api.isAuthenticated()) {
            this.showMainApp();
        } else {
            this.showLoginForm();
        }

        this.authForm.addEventListener('submit', (e) => this.handleLogin(e));
        this.logoutBtn.addEventListener('click', () => this.handleLogout());
    }

    // Остальные методы остаются теми же...
    showLoginForm() {
        this.loginForm.classList.remove('hidden');
        this.mainApp.classList.add('hidden');
    }

    showMainApp() {
        this.loginForm.classList.add('hidden');
        this.mainApp.classList.remove('hidden');
        this.updateUserInfo();
    }

    updateUserInfo() {
        const role = api.getUserRole();
        const roleNames = {
            'owner': 'Владелец',
            'admin': 'Администратор', 
            'manager': 'Менеджер'
        };
        this.userRoleSpan.textContent = roleNames[role] || role;
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const telegramId = document.getElementById('telegramId').value.trim();
        const authCode = document.getElementById('authCode').value.trim();
        const submitBtn = this.authForm.querySelector('button[type="submit"]');

        if (!telegramId || !authCode) {
            notifications.error('Заполните все поля');
            return;
        }

        if (!this.validateTelegramId(telegramId)) {
            notifications.error('Некорректный Telegram ID');
            return;
        }

        if (!this.validateAuthCode(authCode)) {
            notifications.error('Код доступа должен быть от 6 до 50 символов');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Вход...';

        const loadingNotif = notifications.loading('Проверка данных...');

        try {
            await api.login(telegramId, authCode);
            notifications.remove(loadingNotif);
            notifications.success('Успешная авторизация');
            this.showMainApp();
            this.authForm.reset();
        } catch (error) {
            notifications.remove(loadingNotif);
            notifications.error(error.message || 'Ошибка авторизации');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Войти';
        }
    }

    handleLogout() {
        if (confirm('Вы уверены, что хотите выйти?')) {
            api.logout();
            this.showLoginForm();
            notifications.success('Вы вышли из системы');
        }
    }

    hasPermission(action, botId = null) {
        const role = api.getUserRole();
        
        switch (role) {
            case 'owner':
                return true;
                
            case 'admin':
                return action !== 'manage_admins';
                
            case 'manager':
                return ['view_users', 'block_users', 'create_broadcast', 'view_content'].includes(action);
                
            default:
                return false;
        }
    }

    showIfPermitted(element, action, botId = null) {
        if (this.hasPermission(action, botId)) {
            element.classList.remove('hidden');
        } else {
            element.classList.add('hidden');
        }
    }

    validateTelegramId(id) {
        const telegramIdRegex = /^\d{8,12}$/;
        return telegramIdRegex.test(id);
    }

    validateAuthCode(code) {
        return code.length >= 6 && code.length <= 50;
    }
}

// Инициализируем без DOMContentLoaded
window.auth = new Auth();