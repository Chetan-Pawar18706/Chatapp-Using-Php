/**
 * =====================================================
 * Notification System JavaScript
 * ChatApp - Real-time Notifications
 * =====================================================
 */

const NotificationModule = {
    csrfToken: null,
    dropdown: null,
    bell: null,
    list: null,
    badge: null,
    isOpen: false,
    isLoading: false,
    currentFilter: 'all',
    pollingInterval: null,
    
    /**
     * Initialize Notification Module
     */
    init: function() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.dropdown = document.getElementById('notificationDropdown');
        this.bell = document.getElementById('notificationBell');
        this.list = document.getElementById('notificationList');
        this.badge = document.getElementById('notificationBadge');
        
        if (this.bell) {
            this.initTabs();
            this.initCloseOnOutsideClick();
            this.loadNotifications();
            this.startPolling();
        }
    },
    
    /**
     * Initialize Tabs
     */
    initTabs: function() {
        const tabs = this.bell.querySelectorAll('.tab-btn');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this.currentFilter = tab.dataset.filter;
                this.loadNotifications();
            });
        });
    },
    
    /**
     * Initialize Close on Outside Click
     */
    initCloseOnOutsideClick: function() {
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.bell.contains(e.target)) {
                this.closeDropdown();
            }
        });
    },
    
    /**
     * Toggle Dropdown
     */
    toggleDropdown: function() {
        if (this.isOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    },
    
    /**
     * Open Dropdown
     */
    openDropdown: function() {
        this.isOpen = true;
        this.dropdown?.classList.add('active');
        this.bell?.querySelector('.bell-button')?.classList.add('active');
        this.loadNotifications();
    },
    
    /**
     * Close Dropdown
     */
    closeDropdown: function() {
        this.isOpen = false;
        this.dropdown?.classList.remove('active');
        this.bell?.querySelector('.bell-button')?.classList.remove('active');
    },
    
    /**
     * Load Notifications
     */
    loadNotifications: async function() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const unreadOnly = this.currentFilter === 'unread' ? '1' : '0';
            const response = await fetch(`../api/get-notifications.php?unread=${unreadOnly}&limit=20`);
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.data.notifications);
                this.updateBadge(data.data.unread_count);
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
            this.showError();
        } finally {
            this.isLoading = false;
        }
    },
    
    /**
     * Show Loading State
     */
    showLoading: function() {
        if (this.list) {
            this.list.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            `;
        }
    },
    
    /**
     * Show Error State
     */
    showError: function() {
        if (this.list) {
            this.list.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <h4>Failed to load notifications</h4>
                    <p>Please try again later</p>
                </div>
            `;
        }
    },
    
    /**
     * Render Notifications
     */
    renderNotifications: function(notifications) {
        if (!this.list) return;
        
        if (notifications.length === 0) {
            this.list.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h4>No notifications</h4>
                    <p>You're all caught up!</p>
                </div>
            `;
            return;
        }
        
        this.list.innerHTML = notifications.map(n => this.renderItem(n)).join('');
    },
    
    /**
     * Render Single Notification Item
     */
    renderItem: function(notification) {
        const { id, type, is_read, sender_username, sender_avatar, message, time_ago, data } = notification;
        
        const icon = this.getIcon(type);
        const color = this.getColor(type);
        const initials = (sender_username || 'S').substring(0, 2).toUpperCase();
        
        let avatarHtml;
        if (sender_avatar && sender_avatar.trim() !== '') {
            avatarHtml = `
                <img src="${sender_avatar}" alt="${sender_username}" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="avatar-initials" style="display:none;">${initials}</div>
            `;
        } else {
            avatarHtml = `<div class="avatar-initials">${initials}</div>`;
        }
        
        const actionUrl = this.getActionUrl(type, data);
        
        return `
            <div class="notification-item ${is_read ? '' : 'unread'}" 
                 data-id="${id}" 
                 data-type="${type}"
                 onclick="NotificationModule.handleClick(${id}, '${actionUrl}')">
                
                <div class="notification-avatar ${color}">
                    ${avatarHtml}
                    <span class="type-icon">
                        <i class="fas ${icon}"></i>
                    </span>
                </div>
                
                <div class="notification-content">
                    <p class="notification-message">${this.formatMessage(type, sender_username, message)}</p>
                    <span class="notification-time">${time_ago}</span>
                </div>
                
                <div class="notification-actions">
                    ${!is_read ? `<button class="action-btn" onclick="event.stopPropagation(); NotificationModule.markRead(${id})" title="Mark as read"><i class="fas fa-circle"></i></button>` : ''}
                    <button class="action-btn" onclick="event.stopPropagation(); NotificationModule.delete(${id})" title="Delete"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
    },
    
    /**
     * Format Message Based on Type
     */
    formatMessage: function(type, sender, message) {
        const name = sender || 'System';
        const messages = {
            'friend_request': `${name} sent you a friend request`,
            'friend_accept': `${name} accepted your friend request`,
            'message': `${name} sent you a message`,
            'mention': `${name} mentioned you`,
            'group_invite': `${name} invited you to a group`,
            'group_join': `${name} joined the group`,
            'group_leave': `${name} left the group`,
            'group_remove': 'You were removed from the group',
            'group_role_change': 'Your role was changed',
            'system': message || 'New notification'
        };
        return messages[type] || message || 'New notification';
    },
    
    /**
     * Get Icon for Type
     */
    getIcon: function(type) {
        const icons = {
            'friend_request': 'fa-user-plus',
            'friend_accept': 'fa-user-check',
            'message': 'fa-comment',
            'mention': 'fa-at',
            'group_invite': 'fa-users',
            'group_join': 'fa-sign-in-alt',
            'group_leave': 'fa-sign-out-alt',
            'group_remove': 'fa-user-minus',
            'group_role_change': 'fa-crown',
            'system': 'fa-bell'
        };
        return icons[type] || 'fa-bell';
    },
    
    /**
     * Get Color for Type
     */
    getColor: function(type) {
        const colors = {
            'friend_request': 'primary',
            'friend_accept': 'success',
            'message': 'info',
            'mention': 'warning',
            'group_invite': 'purple',
            'group_join': 'success',
            'group_leave': 'secondary',
            'group_remove': 'danger',
            'group_role_change': 'warning',
            'system': 'primary'
        };
        return colors[type] || 'primary';
    },
    
    /**
     * Get Action URL Based on Type
     */
    getActionUrl: function(type, data) {
        switch (type) {
            case 'friend_request':
                return 'dashboard.php#friends';
            case 'message':
                return `chat.php?user_id=${data?.sender_id || ''}`;
            case 'mention':
            case 'group_invite':
                return `group-chat.php?id=${data?.group_id || ''}`;
            default:
                return '#';
        }
    },
    
    /**
     * Handle Notification Click
     */
    handleClick: async function(id, url) {
        await this.markRead(id);
        if (url && url !== '#') {
            window.location.href = url;
        }
    },
    
    /**
     * Mark Notification as Read
     */
    markRead: async function(id) {
        try {
            const response = await fetch('../api/notification-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${this.csrfToken}&action=mark_read&notification_id=${id}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                const item = this.list?.querySelector(`[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                    const markBtn = item.querySelector('.action-btn .fa-circle')?.parentElement;
                    if (markBtn) markBtn.remove();
                }
                this.updateBadgeCount(-1);
            }
        } catch (error) {
            console.error('Failed to mark notification:', error);
        }
    },
    
    /**
     * Mark All as Read
     */
    markAllRead: async function() {
        try {
            const response = await fetch('../api/notification-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${this.csrfToken}&action=mark_all_read`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.list?.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                this.updateBadge(0);
            }
        } catch (error) {
            console.error('Failed to mark all as read:', error);
        }
    },
    
    /**
     * Delete Notification
     */
    delete: async function(id) {
        try {
            const response = await fetch('../api/notification-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${this.csrfToken}&action=delete&notification_id=${id}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                const item = this.list?.querySelector(`[data-id="${id}"]`);
                if (item) {
                    item.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => item.remove(), 300);
                }
                
                if (item?.classList.contains('unread')) {
                    this.updateBadgeCount(-1);
                }
            }
        } catch (error) {
            console.error('Failed to delete notification:', error);
        }
    },
    
    /**
     * Clear All Notifications
     */
    clearAll: async function() {
        if (!confirm('Clear all notifications?')) return;
        
        try {
            const response = await fetch('../api/notification-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${this.csrfToken}&action=clear_all`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.loadNotifications();
                this.updateBadge(0);
            }
        } catch (error) {
            console.error('Failed to clear notifications:', error);
        }
    },
    
    /**
     * Update Badge
     */
    updateBadge: function(count) {
        if (!this.badge) return;
        
        if (count > 0) {
            const displayCount = count > 99 ? '99+' : count;
            this.badge.textContent = displayCount;
            this.badge.style.display = 'flex';
        } else {
            this.badge.style.display = 'none';
        }
    },
    
    /**
     * Update Badge Count (relative)
     */
    updateBadgeCount: function(delta) {
        if (!this.badge) return;
        
        let current = parseInt(this.badge.textContent) || 0;
        if (this.badge.textContent === '99+') current = 100;
        
        current = Math.max(0, current + delta);
        this.updateBadge(current);
    },
    
    /**
     * Start Polling for New Notifications
     */
    startPolling: function() {
        this.pollingInterval = setInterval(async () => {
            try {
                const response = await fetch('../api/get-notifications.php?unread=1&limit=1');
                const data = await response.json();
                
                if (data.success) {
                    this.updateBadge(data.data.unread_count);
                }
            } catch (error) {
                // Silently fail polling
            }
        }, 30000); // Poll every 30 seconds
    },
    
    /**
     * Stop Polling
     */
    stopPolling: function() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
};

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    NotificationModule.init();
});

// Add animation keyframe
(function() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(-100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
})();
