class UsersManager {
    constructor() {
        this.users = [];
        this.stats = {};
        this.currentFilters = {
            search: '',
            date_from: '',
            date_to: '',
            status: '',
            sort: 'date_register',
            order: 'DESC'
        };
    }

    async init(botId) {
        this.currentBotId = botId;

        // Ждём пока секция отобразится
        await new Promise(resolve => setTimeout(resolve, 100));

        this.renderFilters();
        this.attachEventListeners();
        await this.loadUsers();
    }

    renderFilters() {
        const container = document.getElementById('usersFilters');
        if (!container) {
            console.error('usersFilters container not found');
            return;
        }

        container.innerHTML = `
            <div class="filters-row">
                <div class="filter-group">
                    <label>Поиск</label>
                    <input type="text" id="userSearch" class="filter-input" placeholder="Ник, ID, имя...">
                </div>
                
                <div class="filter-group">
                    <label>Дата от</label>
                    <input type="date" id="dateFrom" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <label>Дата до</label>
                    <input type="date" id="dateTo" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <label>Статус</label>
                    <select id="statusFilter" class="filter-input">
                        <option value="">Все</option>
                        <option value="active">Активные</option>
                        <option value="blocked">Заблокированные</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-group">
                <label>Быстрый выбор периода</label>
                <div class="quick-filters">
                    <button class="quick-filter-btn" data-period="today">Сегодня</button>
                    <button class="quick-filter-btn" data-period="3days">3 дня</button>
                    <button class="quick-filter-btn" data-period="7days">7 дней</button>
                    <button class="quick-filter-btn" data-period="30days">30 дней</button>
                    <button class="quick-filter-btn" data-period="all">Всё время</button>
                </div>
            </div>
        `;
    }

    attachEventListeners() {
        // Поиск
        const searchInput = document.getElementById('userSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.currentFilters.search = e.target.value;
                this.debounceLoadUsers();
            });
        }

        // Дата от
        const dateFrom = document.getElementById('dateFrom');
        if (dateFrom) {
            dateFrom.addEventListener('change', (e) => {
                this.currentFilters.date_from = e.target.value;
                this.loadUsers();
            });
        }

        // Дата до
        const dateTo = document.getElementById('dateTo');
        if (dateTo) {
            dateTo.addEventListener('change', (e) => {
                this.currentFilters.date_to = e.target.value;
                this.loadUsers();
            });
        }

        // Статус
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.currentFilters.status = e.target.value;
                this.loadUsers();
            });
        }

        // Быстрые фильтры
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.quick-filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.applyQuickFilter(e.target.dataset.period);
            });
        });
    }

    applyQuickFilter(period) {
        const today = new Date();
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');

        switch (period) {
            case 'today':
                dateFrom.value = this.formatDate(today);
                dateTo.value = this.formatDate(today);
                break;
            case '3days':
                dateFrom.value = this.formatDate(new Date(today.setDate(today.getDate() - 3)));
                dateTo.value = this.formatDate(new Date());
                break;
            case '7days':
                dateFrom.value = this.formatDate(new Date(today.setDate(today.getDate() - 7)));
                dateTo.value = this.formatDate(new Date());
                break;
            case '30days':
                dateFrom.value = this.formatDate(new Date(today.setDate(today.getDate() - 30)));
                dateTo.value = this.formatDate(new Date());
                break;
            case 'all':
                dateFrom.value = '';
                dateTo.value = '';
                break;
        }

        this.currentFilters.date_from = dateFrom.value;
        this.currentFilters.date_to = dateTo.value;
        this.loadUsers();
    }

    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    debounceLoadUsers() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.loadUsers(), 500);
    }

    async loadUsers() {
        try {
            const response = await api.getUsers(this.currentBotId, this.currentFilters);
            this.users = response.users;
            this.stats = response.stats;
            this.renderStats();
            this.renderUsers();
        } catch (error) {
            notifications.error('Ошибка загрузки пользователей');
        }
    }

    renderStats() {
        const container = document.getElementById('usersStats');
        if (!container) return;

        container.innerHTML = `
            <div class="stat-card total">
                <div class="stat-value">${this.stats.total || 0}</div>
                <div class="stat-label">Всего пользователей</div>
            </div>
            <div class="stat-card active">
                <div class="stat-value">${this.stats.active || 0}</div>
                <div class="stat-label">Активные</div>
            </div>
            <div class="stat-card blocked">
                <div class="stat-value">${this.stats.blocked || 0}</div>
                <div class="stat-label">Заблокированные</div>
            </div>
        `;
    }

    renderUsers() {
        const container = document.getElementById('usersTable');
        if (!container) return;

        if (this.users.length === 0) {
            container.innerHTML = `
            <div class="users-empty">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p>Пользователи не найдены</p>
            </div>
        `;
            return;
        }

        const rows = this.users.map(user => `
        <tr>
            <td>
                <div class="telegram-id">
                    ${user.telegram_id}
                    <button class="copy-btn" onclick="usersManager.copyToClipboard('${user.telegram_id}')" title="Копировать ID">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </td>
            <td>
                <div class="telegram-id">
                    @${user.username || 'без ника'}
                    ${user.username ? `<button class="copy-btn" onclick="usersManager.copyToClipboard('@${user.username}')" title="Копировать ник">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>` : ''}
                </div>
            </td>
            <td>
                <div class="telegram-id">
                    ${user.first_name || ''} ${user.last_name || ''}
                    ${user.first_name ? `<button class="copy-btn" onclick="usersManager.copyToClipboard('${user.first_name} ${user.last_name || ''}')" title="Копировать имя">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>` : ''}
                </div>
            </td>
            <td><span class="user-role ${user.role}">${this.getRoleLabel(user.role)}</span></td>
            <td><span class="user-status ${user.active_bot ? 'active' : 'blocked'}">${user.active_bot ? 'Активен' : 'Заблокирован'}</span></td>
            <td>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span style="font-size: 13px;">${this.formatDate(user.date_register)}</span>
                    <span style="font-size: 11px; color: #9ca3af;">${this.formatTime(user.date_register)}</span>
                </div>
            </td>
            <td>
                <div class="user-actions">
                    ${user.active_bot ?
                `<button class="btn btn-sm btn-warning" onclick="usersManager.blockUser(${user.telegram_id})" title="Заблокировать">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </button>` :
                `<button class="btn btn-sm btn-success" onclick="usersManager.unblockUser(${user.telegram_id})" title="Разблокировать">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>`
            }
                    <button class="btn btn-sm btn-danger" onclick="usersManager.deleteUser(${user.telegram_id})" title="Удалить">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

        container.innerHTML = `
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th class="sortable" onclick="usersManager.sort('telegram_id')">
                            ID 
                            <button class="copy-btn" onclick="usersManager.copyAllColumn('telegram_id')" title="Копировать все ID">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </th>
                        <th class="sortable" onclick="usersManager.sort('username')">
                            Username 
                            <button class="copy-btn" onclick="usersManager.copyAllColumn('username')" title="Копировать все ники">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </th>
                        <th class="sortable" onclick="usersManager.sort('first_name')">
                            Имя 
                            <button class="copy-btn" onclick="usersManager.copyAllColumn('first_name')" title="Копировать все имена">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th class="sortable" onclick="usersManager.sort('date_register')">Регистрация</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                </tbody>
            </table>
        </div>
    `;
    }

    getRoleLabel(role) {
        const labels = {
            'admin': 'Админ',
            'guest': 'Гость',
            'user': 'Пользователь'
        };
        return labels[role] || role;
    }

    formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('ru-RU', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    sort(field) {
        if (this.currentFilters.sort === field) {
            this.currentFilters.order = this.currentFilters.order === 'ASC' ? 'DESC' : 'ASC';
        } else {
            this.currentFilters.sort = field;
            this.currentFilters.order = 'DESC';
        }
        this.loadUsers();
    }

    async blockUser(telegramId) {
        const reason = prompt('Причина блокировки (необязательно):');
        if (reason === null) return;

        try {
            await api.blockUser(this.currentBotId, telegramId, reason);
            notifications.success('Пользователь заблокирован');
            await this.loadUsers();
        } catch (error) {
            notifications.error('Ошибка блокировки пользователя');
        }
    }

    async unblockUser(telegramId) {
        try {
            await api.unblockUser(this.currentBotId, telegramId);
            notifications.success('Пользователь разблокирован');
            await this.loadUsers();
        } catch (error) {
            notifications.error('Ошибка разблокировки пользователя');
        }
    }

    async deleteUser(telegramId) {
        if (!confirm('Удалить пользователя? Это действие необратимо!')) return;

        try {
            await api.deleteUser(telegramId);
            notifications.success('Пользователь удалён');
            await this.loadUsers();
        } catch (error) {
            notifications.error('Ошибка удаления пользователя');
        }
    }

    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            notifications.success('ID скопирован');
        }).catch(() => {
            notifications.error('Ошибка копирования');
        });
    }

    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    formatTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleTimeString('ru-RU', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    copyAllColumn(column) {
        let values = [];

        switch (column) {
            case 'telegram_id':
                values = this.users.map(u => u.telegram_id);
                break;
            case 'username':
                values = this.users.filter(u => u.username).map(u => '@' + u.username);
                break;
            case 'first_name':
                values = this.users.filter(u => u.first_name).map(u => `${u.first_name} ${u.last_name || ''}`.trim());
                break;
        }

        if (values.length === 0) {
            notifications.error('Нет данных для копирования');
            return;
        }

        const text = values.join('\n');
        navigator.clipboard.writeText(text).then(() => {
            notifications.success(`Скопировано ${values.length} записей`);
        }).catch(() => {
            notifications.error('Ошибка копирования');
        });
    }
}

window.usersManager = new UsersManager();