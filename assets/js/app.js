/**
 * =====================================================
 * ChatApp - Core JavaScript
 * AJAX, Validation, and UI Functions
 * =====================================================
 */

// =====================================================
// Configuration
// =====================================================
const APP_CONFIG = {
    baseUrl: window.location.origin + '/chatapp',
    apiPath: '/api',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
    csrfHeader: 'X-CSRF-TOKEN'
};

// =====================================================
// API Helper Functions
// =====================================================

/**
 * Make API Request using Fetch API
 * 
 * @param {string} endpoint API endpoint (e.g., '/register.php')
 * @param {string} method HTTP method
 * @param {object} data Request body
 * @returns {Promise<object>} Response data
 */
async function apiRequest(endpoint, method = 'GET', data = null, isFormData = false) {
    const url = APP_CONFIG.baseUrl + APP_CONFIG.apiPath + endpoint;
    
    const options = {
        method: method.toUpperCase(),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    };

    if (!isFormData) {
        options.headers['Content-Type'] = 'application/json';
    }
    
    // Add CSRF token
    if (APP_CONFIG.csrfToken && !isFormData) {
        options.headers[APP_CONFIG.csrfHeader] = APP_CONFIG.csrfToken;
    }
    
    // Add body for POST/PUT requests
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = isFormData ? data : JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        
        if (response.status === 401) {
            window.location.href = APP_CONFIG.baseUrl + '/login.php';
            return { success: false, message: 'Session expired' };
        }
        
        const result = await response.json();
        
        if (result.data && result.data.csrf_token) {
            APP_CONFIG.csrfToken = result.data.csrf_token;
        }
        
        return result;
    } catch (error) {
        console.error('API Request Error:', error);
        return {
            success: false,
            message: 'Network error. Please check your connection.',
            error: error.message
        };
    }
}

// =====================================================
// Form Validation Functions
// =====================================================

/**
 * Validate Email Format
 * 
 * @param {string} email Email to validate
 * @returns {boolean} True if valid
 */
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validate Username Format
 * Alphanumeric, 3-50 characters, can contain underscores
 * 
 * @param {string} username Username to validate
 * @returns {boolean} True if valid
 */
function validateUsername(username) {
    const regex = /^[a-zA-Z0-9_]{3,50}$/;
    return regex.test(username);
}

/**
 * Validate Password Strength
 * At least 8 chars, 1 uppercase, 1 lowercase, 1 number
 * 
 * @param {string} password Password to validate
 * @returns {object} Validation result with strength level
 */
function validatePasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) {
        strength++;
    } else {
        feedback.push('At least 8 characters');
    }
    
    if (/[a-z]/.test(password)) {
        strength++;
    } else {
        feedback.push('Add a lowercase letter');
    }
    
    if (/[A-Z]/.test(password)) {
        strength++;
    } else {
        feedback.push('Add an uppercase letter');
    }
    
    if (/\d/.test(password)) {
        strength++;
    } else {
        feedback.push('Add a number');
    }
    
    let level = 'weak';
    if (strength >= 4) {
        level = 'strong';
    } else if (strength >= 3) {
        level = 'medium';
    }
    
    return {
        valid: strength >= 4,
        strength: strength,
        level: level,
        feedback: feedback
    };
}

// =====================================================
// UI Helper Functions
// =====================================================

/**
 * Show Alert Message
 * 
 * @param {string} elementId Alert element ID
 * @param {string} message Alert message
 * @param {string} type Alert type (success, error, warning, info)
 */
function showAlert(elementId, message, type = 'error') {
    const alert = document.getElementById(elementId);
    if (!alert) return;
    
    // Set alert type
    alert.className = `alert alert-${type}`;
    
    // Set icon
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    // Set content
    alert.innerHTML = `
        <i class="fas fa-${icon} me-2"></i>
        <span>${escapeHtml(message)}</span>
    `;
    
    // Show alert
    alert.classList.add('show');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.classList.remove('show');
    }, 5000);
}

/**
 * Hide Alert Message
 * 
 * @param {string} elementId Alert element ID
 */
function hideAlert(elementId) {
    const alert = document.getElementById(elementId);
    if (alert) {
        alert.classList.remove('show');
    }
}

/**
 * Show Toast Notification
 * 
 * @param {string} message Toast message
 * @param {string} type Toast type (success, error, warning)
 */
function showToast(message, type = 'success') {
    // Get or create toast container
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Set icon
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span class="toast-message">${escapeHtml(message)}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * Set Button Loading State
 * 
 * @param {string} buttonId Button element ID
 * @param {boolean} loading Loading state
 * @param {string} originalText Original button text
 */
function setButtonLoading(buttonId, loading, originalText = '') {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner"></span> Processing...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || originalText;
    }
}

/**
 * Escape HTML to Prevent XSS
 * 
 * @param {string} text Raw text
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Render avatar - shows image if available, else letter initial
 * @param {string} avatar Avatar URL/path
 * @param {string} username Username for fallback initial
 * @param {string} size CSS class for size (e.g., 'small', 'large')
 * @returns {string} HTML string
 */
function renderAvatar(avatar, username, size) {
    var sizeClass = size ? ' ' + size : '';
    if (avatar) {
        return '<img src="' + escapeHtml(avatar) + '" alt="' + escapeHtml(username || '') + '" class="user-avatar-img' + sizeClass + '">';
    }
    var initial = (username || 'U').charAt(0).toUpperCase();
    return '<div class="user-avatar' + sizeClass + '">' + initial + '</div>';
}

function getAvatarUrl(avatar) {
    return avatar || 'storage/uploads/avatars/default-avatar.svg';
}

/**
 * Update Password Strength Indicator
 * 
 * @param {string} password Password value
 * @param {string} strengthBarId Strength bar element ID
 * @param {string} strengthTextId Strength text element ID
 */
function updatePasswordStrength(password, strengthBarId, strengthTextId) {
    const strengthBar = document.getElementById(strengthBarId);
    const strengthText = document.getElementById(strengthTextId);
    
    if (!strengthBar || !strengthText) return;
    
    if (!password) {
        strengthBar.className = 'strength-bar-fill';
        strengthText.textContent = '';
        return;
    }
    
    const result = validatePasswordStrength(password);
    
    // Update bar
    strengthBar.className = `strength-bar-fill ${result.level}`;
    
    // Update text
    const messages = {
        'weak': 'Weak password',
        'medium': 'Medium strength',
        'strong': 'Strong password'
    };
    
    strengthText.textContent = messages[result.level];
    strengthText.style.color = {
        'weak': '#ef4444',
        'medium': '#f59e0b',
        'strong': '#22c55e'
    }[result.level];
}

/**
 * Toggle Password Visibility
 * 
 * @param {string} inputId Password input element ID
 * @param {string} iconId Toggle icon element ID
 */
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (!input || !icon) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

/**
 * Close Modal
 * 
 * @param {string} modalId Modal element ID
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

/**
 * Open Modal
 * 
 * @param {string} modalId Modal element ID
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

// =====================================================
// Form Submission Handlers
// =====================================================

/**
 * Handle Registration Form Submission
 * 
 * @param {Event} formEvent Form submit event
 */
async function handleRegistration(formEvent) {
    formEvent.preventDefault();
    
    const form = formEvent.target;
    const submitBtn = form.querySelector('[type="submit"]');
    const alertEl = document.getElementById('registerAlert');
    
    // Get form data
    const formData = new FormData(form);
    const data = {
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        confirm_password: formData.get('confirm_password'),
        csrf_token: APP_CONFIG.csrfToken
    };
    
    // Client-side validation
    if (!data.username || !validateUsername(data.username)) {
        showAlert('registerAlert', 'Username must be 3-50 characters (letters, numbers, underscores)', 'error');
        return;
    }
    
    if (!data.email || !validateEmail(data.email)) {
        showAlert('registerAlert', 'Please enter a valid email address', 'error');
        return;
    }
    
    if (!data.password) {
        showAlert('registerAlert', 'Please enter a password', 'error');
        return;
    }
    
    const passwordCheck = validatePasswordStrength(data.password);
    if (!passwordCheck.valid) {
        showAlert('registerAlert', 'Password must be at least 8 characters with uppercase, lowercase, and number', 'error');
        return;
    }
    
    if (data.password !== data.confirm_password) {
        showAlert('registerAlert', 'Passwords do not match', 'error');
        return;
    }
    
    // Disable submit button
    setButtonLoading(submitBtn.id, true);
    hideAlert('registerAlert');
    
    // Make API request
    const result = await apiRequest('/register.php', 'POST', data);
    
    setButtonLoading(submitBtn.id, false);
    
    if (result.success) {
        showAlert('registerAlert', result.message, 'success');
        
        // Show friend code and redirect
        if (result.data && result.data.friend_code) {
            // Display success with friend code
            form.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h3 class="mt-3">Registration Successful!</h3>
                    <p class="text-muted">Your friend code is:</p>
                    <div class="friend-code-display">
                        <div class="code">${escapeHtml(result.data.friend_code)}</div>
                        <div class="label">Share this code with friends to connect</div>
                    </div>
                    <a href="${APP_CONFIG.baseUrl}/index.php" class="btn btn-primary-custom btn-auth mt-3">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            `;
        }
    } else {
        showAlert('registerAlert', result.message, 'error');
    }
}

/**
 * Handle Login Form Submission
 * 
 * @param {Event} formEvent Form submit event
 */
async function handleLogin(formEvent) {
    formEvent.preventDefault();
    
    const form = formEvent.target;
    const submitBtn = form.querySelector('[type="submit"]');
    
    // Get form data
    const formData = new FormData(form);
    const data = {
        username: formData.get('username'),
        password: formData.get('password'),
        remember_me: formData.get('remember_me') === 'on',
        csrf_token: APP_CONFIG.csrfToken
    };
    
    // Client-side validation
    if (!data.username) {
        showAlert('loginAlert', 'Please enter username or email', 'error');
        return;
    }
    
    if (!data.password) {
        showAlert('loginAlert', 'Please enter your password', 'error');
        return;
    }
    
    // Disable submit button
    setButtonLoading(submitBtn.id, true);
    hideAlert('loginAlert');
    
    // Make API request
    const result = await apiRequest('/login.php', 'POST', data);
    
    setButtonLoading(submitBtn.id, false);
    
    if (result.success) {
        showToast('Login successful! Redirecting...', 'success');
        
        // Redirect to dashboard
        setTimeout(() => {
            window.location.href = result.data.redirect || APP_CONFIG.baseUrl + '/pages/dashboard.php';
        }, 1000);
    } else {
        showAlert('loginAlert', result.message, 'error');
    }
}

/**
 * Handle Forgot Password Form Submission
 * 
 * @param {Event} formEvent Form submit event
 */
async function handleForgotPassword(formEvent) {
    formEvent.preventDefault();
    
    const form = formEvent.target;
    const submitBtn = form.querySelector('[type="submit"]');
    
    // Get form data
    const formData = new FormData(form);
    const data = {
        email: formData.get('email'),
        csrf_token: APP_CONFIG.csrfToken
    };
    
    // Client-side validation
    if (!data.email || !validateEmail(data.email)) {
        showAlert('forgotAlert', 'Please enter a valid email address', 'error');
        return;
    }
    
    // Disable submit button
    setButtonLoading(submitBtn.id, true);
    hideAlert('forgotAlert');
    
    // Make API request
    const result = await apiRequest('/forgot-password.php', 'POST', data);
    
    setButtonLoading(submitBtn.id, false);
    
    if (result.success) {
        showAlert('forgotAlert', result.message, 'success');
        
        // Show reset link in dev mode
        if (result.data && result.data.reset_url) {
            showAlert('forgotAlert', 
                `${result.message}<br><br><strong>Dev Mode:</strong> <a href="${result.data.reset_url}" class="text-primary-custom">${result.data.reset_url}</a>`, 
                'info'
            );
        }
    } else {
        showAlert('forgotAlert', result.message, 'error');
    }
}

/**
 * Handle Reset Password Form Submission
 * 
 * @param {Event} formEvent Form submit event
 */
async function handleResetPassword(formEvent) {
    formEvent.preventDefault();
    
    const form = formEvent.target;
    const submitBtn = form.querySelector('[type="submit"]');
    
    // Get URL params
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    // Get form data
    const formData = new FormData(form);
    const data = {
        token: token,
        password: formData.get('password'),
        confirm_password: formData.get('confirm_password'),
        csrf_token: APP_CONFIG.csrfToken
    };
    
    // Client-side validation
    if (!token) {
        showAlert('resetAlert', 'Invalid reset link', 'error');
        return;
    }
    
    if (!data.password) {
        showAlert('resetAlert', 'Please enter a new password', 'error');
        return;
    }
    
    const passwordCheck = validatePasswordStrength(data.password);
    if (!passwordCheck.valid) {
        showAlert('resetAlert', 'Password must be at least 8 characters with uppercase, lowercase, and number', 'error');
        return;
    }
    
    if (data.password !== data.confirm_password) {
        showAlert('resetAlert', 'Passwords do not match', 'error');
        return;
    }
    
    // Disable submit button
    setButtonLoading(submitBtn.id, true);
    hideAlert('resetAlert');
    
    // Make API request
    const result = await apiRequest('/reset-password.php', 'POST', data);
    
    setButtonLoading(submitBtn.id, false);
    
    if (result.success) {
        showAlert('resetAlert', result.message + ' Redirecting to login...', 'success');
        
        // Redirect to login
        setTimeout(() => {
            window.location.href = result.data.redirect || APP_CONFIG.baseUrl + '/index.php';
        }, 2000);
    } else {
        showAlert('resetAlert', result.message, 'error');
    }
}

/**
 * Handle Logout
 * 
 * @returns {Promise<void>}
 */
async function handleLogout() {
    const result = await apiRequest('/logout.php', 'POST', {
        csrf_token: APP_CONFIG.csrfToken
    });
    
    if (result.success) {
        window.location.href = result.data.redirect || APP_CONFIG.baseUrl + '/index.php';
    } else {
        // Force redirect even if API fails
        window.location.href = APP_CONFIG.baseUrl + '/index.php';
    }
}

// =====================================================
// Dashboard Functions
// =====================================================

/**
 * Load User Profile
 * 
 * @returns {Promise<void>}
 */
async function loadUserProfile() {
    const result = await apiRequest('/profile.php', 'GET');
    
    if (result.success && result.data) {
        // Update profile display
        const usernameEls = document.querySelectorAll('.profile-username');
        const emailEls = document.querySelectorAll('.profile-email');
        const avatarEls = document.querySelectorAll('.profile-avatar-text');
        const friendCodeEls = document.querySelectorAll('.profile-friend-code');
        const memberSinceEls = document.querySelectorAll('.profile-member-since');
        
        usernameEls.forEach(el => el.textContent = result.data.username);
        emailEls.forEach(el => el.textContent = result.data.email);
        avatarEls.forEach(el => el.textContent = result.data.username.charAt(0).toUpperCase());
        friendCodeEls.forEach(el => el.textContent = result.data.friend_code);
        memberSinceEls.forEach(el => el.textContent = result.data.member_since);
    }
}

/**
 * Check Authentication Status
 * 
 * @returns {Promise<boolean>} True if authenticated
 */
async function checkAuthStatus() {
    const result = await apiRequest('/check-auth.php', 'GET');
    
    if (result.success && result.data) {
        APP_CONFIG.csrfToken = result.data.csrf_token;
        
        if (!result.data.logged_in) {
            window.location.href = APP_CONFIG.baseUrl + '/index.php';
            return false;
        }
        return true;
    }
    
    return false;
}

// =====================================================
// Event Listeners
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from meta tag or cookie
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        APP_CONFIG.csrfToken = csrfMeta.content;
    }
    
    // Initialize forms
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegistration);
    }
    
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Password toggle (eye button)
    const passwordToggle = document.getElementById('passwordToggle');
    if (passwordToggle) {
        passwordToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            togglePasswordVisibility('password', 'toggleIcon');
        });
    }
    
    const forgotForm = document.getElementById('forgotForm');
    if (forgotForm) {
        forgotForm.addEventListener('submit', handleForgotPassword);
    }
    
    const resetForm = document.getElementById('resetForm');
    if (resetForm) {
        resetForm.addEventListener('submit', handleResetPassword);
    }
    
    // Password strength indicators
    const passwordInputs = document.querySelectorAll('[data-strength]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            updatePasswordStrength(
                this.value, 
                this.dataset.strength, 
                this.dataset.strengthText
            );
        });
    });
    
    // User dropdown toggle
    const userAvatar = document.querySelector('.user-avatar');
    const dropdownMenu = document.querySelector('.user-dropdown-menu');
    
    if (userAvatar && dropdownMenu) {
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            dropdownMenu.classList.remove('show');
        });
    }
    
    // Logout buttons
    const logoutBtns = document.querySelectorAll('[data-action="logout"]');
    logoutBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                handleLogout();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('show')) {
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }
    });
});

// =====================================================
// Export functions for global access
// =====================================================
window.ChatApp = {
    apiRequest,
    validateEmail,
    validateUsername,
    validatePasswordStrength,
    showAlert,
    hideAlert,
    showToast,
    togglePasswordVisibility,
    handleLogout,
    loadUserProfile,
    checkAuthStatus
};

// =====================================================
// Session Expiry Check - Periodic + Fetch Interceptor
// =====================================================
(function() {
    const SESSION_CHECK_URL = APP_CONFIG.baseUrl + '/api/session-check.php';
    const REDIRECT_URL = APP_CONFIG.baseUrl + '/login.php';
    let sessionExpired = false;

    function redirectToLogin() {
        if (sessionExpired) return;
        sessionExpired = true;
        window.location.href = REDIRECT_URL;
    }

    setInterval(async () => {
        if (sessionExpired) return;
        try {
            const resp = await fetch(SESSION_CHECK_URL, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (resp.status === 401) redirectToLogin();
        } catch(e) {}
    }, 60000);

    const origFetch = window.fetch;
    window.fetch = function() {
        return origFetch.apply(this, arguments).then(response => {
            if (response.status === 401 && !arguments[0]?.toString().includes('login.php')) {
                redirectToLogin();
            }
            return response;
        });
    };
})();
