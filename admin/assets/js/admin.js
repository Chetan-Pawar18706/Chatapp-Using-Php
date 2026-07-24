/**
 * =====================================================
 * Admin Panel JavaScript
 * ChatApp - Admin Interactions
 * =====================================================
 */

// Global variables
const ADMIN = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
    baseUrl: '/admin/api/'
};

// ==================== Sidebar ====================
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            toggleOverlay();
        });
    }
    
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
            toggleOverlay();
        });
    }
});

function toggleOverlay() {
    let overlay = document.querySelector('.sidebar-overlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
        
        overlay.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    overlay.classList.toggle('active');
}

// ==================== AJAX Helper ====================
async function adminAjax(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'X-CSRF-Token': ADMIN.csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data && method !== 'GET') {
        if (data instanceof FormData) {
            data.append('csrf_token', ADMIN.csrfToken);
            options.body = data;
        } else {
            options.headers['Content-Type'] = 'application/json';
            data.csrf_token = ADMIN.csrfToken;
            options.body = JSON.stringify(data);
        }
    }
    
    try {
        const response = await fetch(ADMIN.baseUrl + endpoint, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('AJAX Error:', error);
        showToast('An error occurred', 'error');
        return { success: false, message: 'Network error' };
    }
}

// ==================== Toast Notifications ====================
function showToast(message, type = 'success', duration = 3000) {
    let container = document.querySelector('.toast-container');
    
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="${icons[type] || icons.success}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ==================== Confirmation Dialog ====================
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ==================== Search ====================
const globalSearch = document.getElementById('globalSearch');
if (globalSearch) {
    let searchTimeout;
    
    globalSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                // Redirect to search page or show results
                window.location.href = `users.php?search=${encodeURIComponent(query)}`;
            }, 500);
        }
    });
}

// ==================== Table Functions ====================
function selectAll(source) {
    const checkboxes = document.querySelectorAll('.select-item');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

function getSelectedIds() {
    const checkboxes = document.querySelectorAll('.select-item:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

// ==================== User Actions ====================
async function toggleUserStatus(userId, action) {
    const result = await adminAjax('users.php', 'POST', {
        action: action,
        user_id: userId
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(result.message, 'error');
    }
}

async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }
    
    const result = await adminAjax('users.php', 'POST', {
        action: 'delete',
        user_id: userId
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => window.location.href = 'users.php', 1000);
    } else {
        showToast(result.message, 'error');
    }
}

// ==================== Group Actions ====================
async function deleteGroup(groupId) {
    if (!confirm('Are you sure you want to delete this group? This action cannot be undone.')) {
        return;
    }
    
    const result = await adminAjax('groups.php', 'POST', {
        action: 'delete',
        group_id: groupId
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => window.location.href = 'groups.php', 1000);
    } else {
        showToast(result.message, 'error');
    }
}

// ==================== Report Actions ====================
async function updateReportStatus(reportId, status, notes = '') {
    const result = await adminAjax('reports.php', 'POST', {
        action: 'update_status',
        report_id: reportId,
        status: status,
        notes: notes
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(result.message, 'error');
    }
}

// ==================== Blocked Users ====================
async function unblockUser(blockId) {
    if (!confirm('Are you sure you want to unblock this user?')) {
        return;
    }
    
    const result = await adminAjax('blocked.php', 'POST', {
        action: 'unblock',
        block_id: blockId
    });
    
    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(result.message, 'error');
    }
}

// ==================== Charts ====================
function createChart(canvasId, type, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#94a3b8',
                    font: { family: 'Inter' }
                }
            }
        },
        scales: type !== 'pie' && type !== 'doughnut' ? {
            x: {
                grid: { color: 'rgba(51, 65, 85, 0.5)' },
                ticks: { color: '#94a3b8' }
            },
            y: {
                grid: { color: 'rgba(51, 65, 85, 0.5)' },
                ticks: { color: '#94a3b8' }
            }
        } : undefined
    };
    
    return new Chart(ctx, {
        type: type,
        data: data,
        options: { ...defaultOptions, ...options }
    });
}

// ==================== Date Formatting ====================
function formatDate(dateString, format = 'short') {
    const date = new Date(dateString);
    const options = {
        short: { month: 'short', day: 'numeric', year: 'numeric' },
        long: { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' },
        time: { hour: '2-digit', minute: '2-digit' },
        datetime: { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }
    };
    
    return date.toLocaleDateString('en-US', options[format] || options.short);
}

// ==================== Loading State ====================
function showLoading(element) {
    const originalContent = element.innerHTML;
    element.innerHTML = '<span class="spinner"></span>';
    element.disabled = true;
    
    return function hideLoading() {
        element.innerHTML = originalContent;
        element.disabled = false;
    };
}

// ==================== Form Validation ====================
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let valid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            valid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return valid;
}

// ==================== Export Functions ====================
function exportCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = [];
    const headers = [];
    
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    rows.push(headers.join(','));
    
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            let value = td.textContent.trim().replace(/"/g, '""');
            row.push(`"${value}"`);
        });
        rows.push(row.join(','));
    });
    
    const csv = rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    
    window.URL.revokeObjectURL(url);
}

// ==================== Notifications ====================
async function checkNotifications() {
    try {
        const response = await fetch(ADMIN.baseUrl + 'notifications.php');
        const data = await response.json();
        
        if (data.success && data.data.count > 0) {
            document.getElementById('notifBadge').textContent = data.data.count;
            document.getElementById('notifBadge').style.display = 'block';
        }
    } catch (error) {
        console.error('Failed to check notifications:', error);
    }
}

// Check notifications periodically
setInterval(checkNotifications, 60000);
checkNotifications();

// ==================== Keyboard Shortcuts ====================
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const search = document.getElementById('globalSearch');
        if (search) search.focus();
    }
});
