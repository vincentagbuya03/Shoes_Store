/**
 * Rider Notification System
 * Handles real-time notifications for delivery riders
 */

class RiderNotifications {
    constructor() {
        this.container = document.getElementById('notificationContainer');
        this.btn = document.getElementById('notificationBtn');
        this.dropdown = document.getElementById('notificationDropdown');
        this.badge = document.getElementById('notificationBadge');
        this.countBadge = document.getElementById('notificationCountBadge');
        this.list = document.getElementById('notificationList');
        this.markAllBtn = document.getElementById('markAllRead');
        this.clearAllBtn = document.getElementById('clearAllNotifications');
        
        this.isOpen = false;
        this.pollInterval = null;
        this.pollDelay = 30000; // 30 seconds
        
        this.init();
    }
    
    init() {
        if (!this.btn || !this.dropdown) return;
        
        // Toggle dropdown
        this.btn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.container.contains(e.target)) {
                this.close();
            }
        });
        
        // Mark all as read
        if (this.markAllBtn) {
            this.markAllBtn.addEventListener('click', () => this.markAllRead());
        }
        
        // Clear all
        if (this.clearAllBtn) {
            this.clearAllBtn.addEventListener('click', () => this.clearAll());
        }
        
        // Initial load
        this.fetchCount();
        
        // Start polling
        this.startPolling();
        
        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    toggle() {
        this.isOpen ? this.close() : this.open();
    }
    
    open() {
        this.isOpen = true;
        this.dropdown.classList.add('show');
        this.btn.setAttribute('aria-expanded', 'true');
        this.fetchNotifications();
    }
    
    close() {
        this.isOpen = false;
        this.dropdown.classList.remove('show');
        this.btn.setAttribute('aria-expanded', 'false');
    }
    
    async fetchCount() {
        try {
            // Get the base path for API calls
            const basePath = this.getBasePath();
            const response = await fetch(basePath + 'api/notifications_api.php?action=count');
            const data = await response.json();
            
            if (data.success) {
                this.updateBadge(data.unread);
            }
        } catch (error) {
            console.error('Failed to fetch notification count:', error);
            // Hide badge on error
            this.updateBadge(0);
        }
    }
    
    getBasePath() {
        // Determine base path based on current location
        const path = window.location.pathname;
        if (path.includes('/delivery_rider/')) {
            const idx = path.indexOf('/delivery_rider/');
            return path.substring(0, idx + '/delivery_rider/'.length);
        }
        return './';
    }
    
    async fetchNotifications() {
        this.list.innerHTML = `
            <div class="notification-loading">
                <div class="notification-loader"></div>
                <span>Loading notifications...</span>
            </div>
        `;
        
        try {
            const basePath = this.getBasePath();
            const response = await fetch(basePath + 'api/notifications_api.php?action=list&limit=20');
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.notifications);
            } else {
                this.list.innerHTML = `
                    <div class="notification-empty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <p>No notifications yet</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
            this.list.innerHTML = `
                <div class="notification-empty">
                    <p>Failed to load notifications</p>
                </div>
            `;
        }
    }
    
    renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            this.list.innerHTML = `
                <div class="notification-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <p>No notifications yet</p>
                </div>
            `;
            return;
        }
        
        this.list.innerHTML = notifications.map(n => this.renderNotificationItem(n)).join('');
        
        // Add click handlers
        this.list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id;
                const url = item.dataset.url;
                this.markAsRead(id, url);
            });
        });
    }
    
    renderNotificationItem(notification) {
        const iconClass = this.getIconClass(notification.type);
        const icon = this.getIcon(notification.type);
        const unreadClass = notification.is_read ? '' : 'unread';
        
        return `
            <div class="notification-item ${unreadClass}" data-id="${notification.id}" data-url="${notification.url || ''}">
                <div class="notification-icon ${iconClass}">
                    ${icon}
                </div>
                <div class="notification-content">
                    <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                    ${notification.body ? `<div class="notification-body">${this.escapeHtml(notification.body)}</div>` : ''}
                    <div class="notification-time">${notification.time_ago}</div>
                </div>
            </div>
        `;
    }
    
    getIconClass(type) {
        const classes = {
            'new_order': '',
            'order_update': '',
            'order_cancelled': 'danger',
            'payment_received': 'success',
            'earnings_credited': 'success',
            'reminder': 'warning',
            'system': ''
        };
        return classes[type] || '';
    }
    
    getIcon(type) {
        const icons = {
            'new_order': `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                <path d="m3.3 7 8.7 5 8.7-5"/>
                <path d="M12 22V12"/>
            </svg>`,
            'order_update': `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 2v6h-6"/>
                <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                <path d="M3 22v-6h6"/>
                <path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
            </svg>`,
            'order_cancelled': `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>`,
            'payment_received': `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>`,
            'earnings_credited': `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v20"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>`,
            'reminder': `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>`,
            'system': `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>`
        };
        return icons[type] || icons['system'];
    }
    
    async markAsRead(id, url) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('id', id);
            
            const basePath = this.getBasePath();
            await fetch(basePath + 'api/notifications_api.php', {
                method: 'POST',
                body: formData
            });
            
            // Update UI
            const item = this.list.querySelector(`[data-id="${id}"]`);
            if (item) {
                item.classList.remove('unread');
            }
            
            // Update count
            this.fetchCount();
            
            // Navigate if URL provided
            if (url) {
                window.location.href = url;
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }
    
    async markAllRead() {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            
            const basePath = this.getBasePath();
            await fetch(basePath + 'api/notifications_api.php', {
                method: 'POST',
                body: formData
            });
            
            // Update UI
            this.list.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            
            this.updateBadge(0);
            this.showToast('All notifications marked as read', 'success');
        } catch (error) {
            console.error('Failed to mark all as read:', error);
            this.showToast('Failed to mark notifications as read', 'error');
        }
    }
    
    async clearAll() {
        if (!confirm('Are you sure you want to clear all notifications?')) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'clear_all');
            
            const basePath = this.getBasePath();
            await fetch(basePath + 'api/notifications_api.php', {
                method: 'POST',
                body: formData
            });
            
            this.list.innerHTML = `
                <div class="notification-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <p>No notifications yet</p>
                </div>
            `;
            
            this.updateBadge(0);
            this.showToast('All notifications cleared', 'success');
        } catch (error) {
            console.error('Failed to clear notifications:', error);
            this.showToast('Failed to clear notifications', 'error');
        }
    }
    
    updateBadge(count) {
        if (this.badge) {
            this.badge.textContent = count > 99 ? '99+' : count;
            this.badge.style.display = count > 0 ? 'flex' : 'none';
        }
        if (this.countBadge) {
            this.countBadge.textContent = count;
        }
    }
    
    startPolling() {
        this.pollInterval = setInterval(() => {
            this.fetchCount();
        }, this.pollDelay);
    }
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
            error: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
            warning: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
            info: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`
        };
        
        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        `;
        
        container.appendChild(toast);
        
        // Close button handler
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        });
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.riderNotifications = new RiderNotifications();
});

// Global toast function for use in other scripts
function showToast(message, type = 'info') {
    if (window.riderNotifications) {
        window.riderNotifications.showToast(message, type);
    }
}
