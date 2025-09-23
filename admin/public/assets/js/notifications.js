class Notifications {
    constructor() {
        this.container = this.createContainer();
        this.notifications = [];
    }

    createContainer() {
        const container = document.createElement('div');
        container.id = 'notifications-container';
        container.className = 'notifications-container';
        document.body.appendChild(container);
        return container;
    }

    show(message, type = 'info', duration = 5000, options = {}) {
        const notification = this.createNotification(message, type, options);
        this.notifications.push(notification);
        this.container.appendChild(notification.element);

        // Анимация появления
        setTimeout(() => {
            notification.element.classList.add('show');
        }, 10);

        // Автоудаление
        if (duration > 0) {
            setTimeout(() => {
                this.remove(notification.id);
            }, duration);
        }

        return notification.id;
    }

    createNotification(message, type, options) {
        const id = 'notif_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        const element = document.createElement('div');
        element.className = `notification notification-${type}`;
        element.dataset.id = id;

        const icons = {
            success: '✅',
            error: '⚠️',
            warning: '⚡',
            info: 'ℹ️'
        };

        element.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${icons[type] || icons.info}</span>
                <span class="notification-message">${this.sanitize(message)}</span>
                <button class="notification-close" onclick="notifications.remove('${id}')">&times;</button>
            </div>
            ${options.progress ? '<div class="notification-progress"><div class="notification-progress-bar"></div></div>' : ''}
        `;

        return { id, element, type };
    }

    remove(id) {
        const notification = this.container.querySelector(`[data-id="${id}"]`);
        if (notification) {
            notification.classList.add('hide');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                this.notifications = this.notifications.filter(n => n.id !== id);
            }, 300);
        }
    }

    clear() {
        this.notifications.forEach(notification => {
            this.remove(notification.id);
        });
    }

    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 4000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 4000) {
        return this.show(message, 'info', duration);
    }

    loading(message) {
        return this.show(message, 'info', 0, { progress: true });
    }

    sanitize(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }
}

// Глобальный экземпляр
window.notifications = new Notifications();