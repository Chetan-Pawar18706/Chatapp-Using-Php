/**
 * =====================================================
 * Settings Page JavaScript
 * ChatApp - Profile Settings Management
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
        this.initForms();
        this.initPhotoUpload();
        this.initCharacterCounts();
        this.initPasswordStrength();
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
                
                // Update nav
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
                
                // Update tabs
                tabs.forEach(tab => tab.classList.remove('active'));
                document.getElementById(tabId)?.classList.add('active');
                
                // Update URL hash
                window.location.hash = tabId;
            });
        });
        
        // Load tab from URL hash
        const hash = window.location.hash.slice(1);
        if (hash) {
            const navItem = document.querySelector(`[data-tab="${hash}"]`);
            if (navItem) navItem.click();
        }
    },
    
    /**
     * Initialize Forms
     */
    initForms: function() {
        // Profile Form
        document.getElementById('profileForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveProfile();
        });
        
        // Appearance Form
        document.getElementById('appearanceForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveAppearance();
        });
        
        // Password Form
        document.getElementById('passwordForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.changePassword();
        });
        
        // Notifications Form
        document.getElementById('notificationsForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveNotifications();
        });
        
        // Privacy Form
        document.getElementById('privacyForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.savePrivacy();
        });
        
        // Theme selection
        document.querySelectorAll('.theme-option input').forEach(input => {
            input.addEventListener('change', () => {
                document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('active'));
                input.closest('.theme-option')?.classList.add('active');
            });
        });
    },
    
    /**
     * Initialize Photo Upload
     */
    initPhotoUpload: function() {
        // Avatar upload
        document.getElementById('avatarInput')?.addEventListener('change', (e) => {
            this.uploadPhoto(e.target.files[0], 'avatar');
        });
        
        // Cover upload
        document.getElementById('coverInput')?.addEventListener('change', (e) => {
            this.uploadPhoto(e.target.files[0], 'cover');
        });
    },
    
    /**
     * Initialize Character Counts
     */
    initCharacterCounts: function() {
        const bioField = document.getElementById('bio');
        const aboutField = document.getElementById('about');
        const bioCount = document.getElementById('bioCount');
        const aboutCount = document.getElementById('aboutCount');
        
        bioField?.addEventListener('input', () => {
            if (bioCount) bioCount.textContent = bioField.value.length;
        });
        
        aboutField?.addEventListener('input', () => {
            if (aboutCount) aboutCount.textContent = aboutField.value.length;
        });
    },
    
    /**
     * Initialize Password Strength
     */
    initPasswordStrength: function() {
        const passwordInput = document.getElementById('new_password');
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
     * Initialize Modals
     */
    initModals: function() {
        // Deactivate confirmation
        document.getElementById('deactivateConfirm')?.addEventListener('input', (e) => {
            const username = e.target.dataset.username || '';
            document.getElementById('deactivateBtn').disabled = e.target.value !== username;
        });
        
        // Delete confirmation
        document.getElementById('deleteConfirm')?.addEventListener('input', (e) => {
            document.getElementById('deleteBtn').disabled = e.target.value !== 'DELETE';
        });
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
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Profile updated successfully', 'success');
                
                // Update username in navbar
                const usernameEl = document.querySelector('.user-name');
                if (usernameEl) usernameEl.textContent = data.data.username;
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to update profile', 'error');
        }
    },
    
    /**
     * Save Appearance
     */
    saveAppearance: async function() {
        const form = document.getElementById('appearanceForm');
        const formData = new FormData(form);
        formData.append('csrf_token', this.csrfToken);
        
        try {
            const response = await fetch('../api/update-settings.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Appearance settings saved', 'success');
                
                // Apply theme immediately
                const theme = formData.get('theme');
                document.documentElement.dataset.theme = theme;
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to save settings', 'error');
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
     * Save Notifications
     */
    saveNotifications: async function() {
        const form = document.getElementById('notificationsForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('../api/update-settings.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Notification preferences saved', 'success');
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to save preferences', 'error');
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
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Privacy settings saved', 'success');
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to save settings', 'error');
        }
    },
    
    /**
     * Upload Photo
     */
    uploadPhoto: async function(file, type) {
        if (!file) return;
        
        // Validate file
        const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowed.includes(file.type)) {
            this.showToast('Only JPG, PNG, GIF, and WEBP images are allowed', 'error');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            this.showToast('File too large. Maximum size: 5 MB', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type);
        formData.append('csrf_token', this.csrfToken);
        
        try {
            const response = await fetch('../api/upload-photo.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast(`${type === 'avatar' ? 'Profile' : 'Cover'} photo updated`, 'success');
                
                const newUrl = data.data.url + '?' + Date.now();
                
                // Update image
                if (type === 'avatar') {
                    const avatarImg = document.querySelector('.profile-photo img');
                    if (avatarImg) {
                        avatarImg.src = newUrl;
                    } else {
                        const initials = document.querySelector('.profile-photo .avatar-initials');
                        if (initials) {
                            const img = document.createElement('img');
                            img.src = data.data.url;
                            img.alt = 'Avatar';
                            initials.replaceWith(img);
                        }
                    }
                    // Update sidebar and navbar avatars
                    document.querySelectorAll('.user-avatar img, .user-avatar-img, #navbarAvatar img, #sidebarAvatar img, .sidebar-user .user-avatar img').forEach(el => {
                        el.src = newUrl;
                    });
                } else {
                    const coverImg = document.querySelector('.cover-photo');
                    if (coverImg) {
                        coverImg.src = data.data.url + '?' + Date.now();
                    } else {
                        const placeholder = document.querySelector('.cover-photo-placeholder');
                        if (placeholder) {
                            const img = document.createElement('img');
                            img.src = data.data.url;
                            img.alt = 'Cover';
                            img.className = 'cover-photo';
                            placeholder.replaceWith(img);
                        }
                    }
                }
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            this.showToast('Failed to upload photo', 'error');
        }
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
    const modal = document.getElementById('deactivateModal');
    const confirmInput = document.getElementById('deactivateConfirm');
    
    // Set username hint
    const username = document.getElementById('username')?.value || '';
    confirmInput.placeholder = username;
    confirmInput.dataset.username = username;
    
    modal.classList.add('active');
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
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
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
    .catch(() => {
        SettingsModule.showToast('Failed to deactivate account', 'error');
    });
}

function deleteAccount() {
    const confirmText = document.getElementById('deleteConfirm').value;
    
    fetch('../api/delete-account.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
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
    .catch(() => {
        SettingsModule.showToast('Failed to delete account', 'error');
    });
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

function resetForm() {
    document.querySelectorAll('.settings-form').forEach(form => {
        form.reset();
    });
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    SettingsModule.init();
});
