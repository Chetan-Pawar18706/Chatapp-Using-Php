/**
 * =====================================================
 * Complete Settings JavaScript
 * ChatApp - Settings Management
 * =====================================================
 */

const SettingsModule = {
    csrfToken: null,
    
    /**
     * Initialize Settings Module
     */
    init: function() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.initTabs();
        this.initTheme();
        this.initLanguage();
        this.initChatStyle();
        this.initForms();
        this.initPasswordStrength();
        this.initCharacterCounts();
        this.initBlockedUsers();
        this.initExport();
        this.initModals();
    },
    
    /**
     * Initialize Tabs
     */
    initTabs: function() {
        const navItems = document.querySelectorAll('.settings-nav .nav-item');
        const tabs = document.querySelectorAll('.settings-tab');
        
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                
                const tabId = item.dataset.tab;
                
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
                
                tabs.forEach(tab => tab.classList.remove('active'));
                document.getElementById(tabId)?.classList.add('active');
                
                window.location.hash = tabId;
            });
        });
        
        const hash = window.location.hash.slice(1);
        if (hash) {
            const navItem = document.querySelector(`[data-tab="${hash}"]`);
            if (navItem) navItem.click();
        }
    },
    
    /**
     * Initialize Theme
     */
    initTheme: function() {
        document.querySelectorAll('.theme-card input').forEach(input => {
            input.addEventListener('change', () => {
                document.querySelectorAll('.theme-card').forEach(card => card.classList.remove('active'));
                input.closest('.theme-card')?.classList.add('active');
                
                const theme = input.value;
                this.saveTheme(theme);
                document.documentElement.dataset.theme = theme;
            });
        });
    },
    
    /**
     * Save Theme
     */
    saveTheme: async function(theme) {
        document.documentElement.dataset.theme = theme;
        
        try {
            const response = await fetch('../api/update-profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `csrf_token=${this.csrfToken}&theme=${theme}`
            });
            
            const data = await response.json();
            if (data.success) {
                this.showToast('Theme updated', 'success');
            }
        } catch (error) {
            console.error('Failed to save theme:', error);
        }
    },
    
    /**
     * Initialize Language
     */
    initLanguage: function() {
        const languageSelect = document.getElementById('language');
        if (languageSelect) {
            languageSelect.addEventListener('change', () => {
                this.saveLanguage(languageSelect.value);
            });
        }
    },
    
    /**
     * Save Language
     */
    saveLanguage: async function(language) {
        try {
            const response = await fetch('../api/update-profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `csrf_token=${this.csrfToken}&language=${language}`
            });
            
            const data = await response.json();
            if (data.success) {
                this.showToast('Language updated', 'success');
            }
        } catch (error) {
            console.error('Failed to save language:', error);
        }
    },
    
    /**
     * Initialize Chat Style
     */
    initChatStyle: function() {
        document.querySelectorAll('input[name="chat_style"]').forEach(input => {
            input.addEventListener('change', () => {
                this.saveChatStyle(input.value);
            });
        });
    },
    
    /**
     * Save Chat Style
     */
    saveChatStyle: async function(chatStyle) {
        try {
            const response = await fetch('../api/update-profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `csrf_token=${this.csrfToken}&chat_style=${chatStyle}`
            });
            
            const data = await response.json();
            if (data.success) {
                this.showToast('Chat style updated', 'success');
            }
        } catch (error) {
            console.error('Failed to save chat style:', error);
        }
    },
    
    /**
     * Initialize Forms
     */
    initForms: function() {
        // Profile form
        document.getElementById('profileForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveProfile();
        });
        
        // Account form (email only)
        document.getElementById('accountForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveAccount();
        });
        
        // Password form
        document.getElementById('passwordForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.changePassword();
        });
        
        // Privacy form
        document.getElementById('privacyForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.savePrivacy();
        });
        
        // Notifications form
        document.getElementById('notificationsForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveNotifications();
        });
        
        // Avatar upload
        document.getElementById('avatarInput')?.addEventListener('change', (e) => {
            this.uploadAvatar(e.target.files[0]);
        });
        
        // Remove avatar
        document.getElementById('removeAvatarBtn')?.addEventListener('click', () => {
            this.removeAvatar();
        });
        
        // Deactivate confirm
        document.getElementById('deactivateConfirm')?.addEventListener('input', (e) => {
            const username = document.getElementById('username')?.value || '';
            document.getElementById('deactivateBtn').disabled = e.target.value !== username;
        });
        
        // Delete confirm
        document.getElementById('deleteConfirm')?.addEventListener('input', (e) => {
            document.getElementById('deleteBtn').disabled = e.target.value !== 'DELETE';
        });
    },
    
    /**
     * Save Account
     */
    saveAccount: async function() {
        const form = document.getElementById('accountForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('../api/update-profile.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Email updated successfully', 'success');
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to update email', 'error');
        }
    },
    
    /**
     * Save Profile
     */
    saveProfile: async function() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('../api/update-profile.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Profile updated successfully', 'success');
                const usernameEl = document.querySelector('.user-name');
                if (usernameEl) usernameEl.textContent = formData.get('username');
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to update profile', 'error');
        }
    },
    
    /**
     * Change Password
     */
    changePassword: async function() {
        const form = document.getElementById('passwordForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('../api/change-password.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Password changed successfully', 'success');
                form.reset();
                document.getElementById('passwordStrength').className = 'password-strength';
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to change password', 'error');
        }
    },
    
    /**
     * Save Privacy
     */
    savePrivacy: async function() {
        const form = document.getElementById('privacyForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('../api/update-settings.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Privacy settings saved', 'success');
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to save privacy settings', 'error');
        }
    },
    
    /**
     * Save Notifications
     */
    saveNotifications: async function() {
        const form = document.getElementById('notificationsForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('../api/update-settings.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Notification settings saved', 'success');
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to save notification settings', 'error');
        }
    },
    
    /**
     * Upload Avatar
     */
    uploadAvatar: async function(file) {
        if (!file) return;
        
        const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowed.includes(file.type)) {
            this.showToast('Only JPG, PNG, GIF, WEBP images are allowed', 'error');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            this.showToast('File too large. Max size: 5 MB', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', 'avatar');
        formData.append('csrf_token', this.csrfToken);
        
        try {
            const response = await fetch('../api/upload-photo.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Avatar updated', 'success');
                const avatarImg = document.querySelector('.current-avatar img');
                if (avatarImg) {
                    avatarImg.src = data.data.url + '?' + Date.now();
                } else {
                    const initials = document.querySelector('.current-avatar .avatar-initials');
                    if (initials) {
                        const img = document.createElement('img');
                        img.src = data.data.url;
                        img.alt = 'Avatar';
                        initials.replaceWith(img);
                    }
                }
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to upload avatar', 'error');
        }
    },
    
    /**
     * Remove Avatar
     */
    removeAvatar: async function() {
        if (!confirm('Remove your profile photo?')) return;
        
        try {
            const response = await fetch('../api/upload-photo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `csrf_token=${this.csrfToken}&type=avatar&remove=1`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Avatar removed', 'success');
                const currentAvatar = document.querySelector('.current-avatar');
                if (currentAvatar) {
                    const username = document.getElementById('username')?.value || 'U';
                    currentAvatar.innerHTML = `<div class="avatar-initials large">${username.substring(0, 2).toUpperCase()}</div>`;
                }
            }
        } catch (error) {
            this.showToast('Failed to remove avatar', 'error');
        }
    },
    
    /**
     * Initialize Password Strength
     */
    initPasswordStrength: function() {
        const passwordInput = document.getElementById('newPassword');
        const strengthDiv = document.getElementById('passwordStrength');
        
        passwordInput?.addEventListener('input', () => {
            const password = passwordInput.value;
            let strength = 'weak';
            
            if (password.length >= 8) {
                if (/[A-Z]/.test(password) && /[a-z]/.test(password) && /[0-9]/.test(password)) {
                    strength = 'strong';
                } else if (/[A-Z]/.test(password) || /[a-z]/.test(password)) {
                    strength = 'good';
                } else {
                    strength = 'fair';
                }
            } else if (password.length >= 6) {
                strength = 'fair';
            }
            
            strengthDiv.className = 'password-strength ' + strength;
            strengthDiv.querySelector('.strength-text').textContent = 
                strength.charAt(0).toUpperCase() + strength.slice(1);
        });
    },
    
    /**
     * Initialize Character Counts
     */
    initCharacterCounts: function() {
        const bioField = document.getElementById('bio');
        const bioCount = document.getElementById('bioCount');
        
        bioField?.addEventListener('input', () => {
            if (bioCount) bioCount.textContent = bioField.value.length;
        });
    },
    
    /**
     * Initialize Blocked Users
     */
    initBlockedUsers: function() {
        document.querySelectorAll('.unblock-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const userId = btn.dataset.userId;
                await this.unblockUser(userId);
            });
        });
    },
    
    /**
     * Unblock User
     */
    unblockUser: async function(userId) {
        try {
            const response = await fetch('../api/unblock-user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `csrf_token=${this.csrfToken}&user_id=${userId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('User unblocked', 'success');
                const item = document.querySelector(`.blocked-user-item[data-user-id="${userId}"]`);
                if (item) {
                    item.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        item.remove();
                        const list = document.getElementById('blockedList');
                        if (list && list.children.length === 0) {
                            list.innerHTML = `
                                <div class="empty-state">
                                    <i class="fas fa-ban"></i>
                                    <h4>No Blocked Users</h4>
                                    <p>When you block someone, they won't be able to message you or see your profile.</p>
                                </div>
                            `;
                        }
                    }, 300);
                }
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to unblock user', 'error');
        }
    },
    
    /**
     * Initialize Export
     */
    initExport: function() {
        document.getElementById('exportAllBtn')?.addEventListener('click', () => {
            this.exportData('all');
        });
        
        document.getElementById('exportMessagesBtn')?.addEventListener('click', () => {
            this.exportData('messages');
        });
        
        document.getElementById('exportMediaBtn')?.addEventListener('click', () => {
            this.exportData('media');
        });
    },
    
    /**
     * Export Data
     */
    exportData: async function(type) {
        const statusEl = document.getElementById('exportStatus');
        if (statusEl) {
            statusEl.style.display = 'flex';
        }
        
        try {
            const response = await fetch(`../api/export-data.php?type=${type}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `chatapp-${type}-export.zip`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
                
                this.showToast('Export downloaded successfully', 'success');
            } else {
                this.showToast('Export failed', 'error');
            }
        } catch (error) {
            this.showToast('Failed to export data', 'error');
        } finally {
            if (statusEl) {
                statusEl.style.display = 'none';
            }
        }
    },
    
    /**
     * Initialize Modals
     */
    initModals: function() {
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', () => {
                overlay.closest('.modal').classList.remove('active');
            });
        });
    },
    
    /**
     * Show Toast
     */
    showToast: function(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'exclamation-triangle'}"></i>
            <span>${message}</span>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'toastSlide 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// Global functions for modals
function showDeactivateModal() {
    document.getElementById('deactivateModal').classList.add('active');
}

function showDeleteModal() {
    document.getElementById('deleteModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function deactivateAccount() {
    const username = document.getElementById('deactivateConfirm').value;
    
    fetch('../api/deactivate-account.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: `csrf_token=${SettingsModule.csrfToken}&username=${encodeURIComponent(username)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '../index.php?deactivated=1';
        } else {
            SettingsModule.showToast(data.message, 'error');
        }
    })
    .catch(() => SettingsModule.showToast('Failed to deactivate account', 'error'));
}

function deleteAccount() {
    const confirmText = document.getElementById('deleteConfirm').value;
    
    fetch('../api/delete-account.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: `csrf_token=${SettingsModule.csrfToken}&confirm_text=${encodeURIComponent(confirmText)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '../index.php?deleted=1';
        } else {
            SettingsModule.showToast(data.message, 'error');
        }
    })
    .catch(() => SettingsModule.showToast('Failed to delete account', 'error'));
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

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

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    SettingsModule.init();
});
