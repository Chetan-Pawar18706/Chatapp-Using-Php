<?php
/**
 * =====================================================
 * Admin Configuration
 * ChatApp - Admin Panel Settings
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

// Admin Session Configuration
define('ADMIN_SESSION_NAME', 'CHATAPP_ADMIN_SESSION');
define('ADMIN_SESSION_LIFETIME', 28800); // 8 hours
define('ADMIN_REMEMBER_LIFETIME', 604800); // 7 days

// Admin Roles
define('ADMIN_ROLES', [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'moderator' => 'Moderator'
]);

// Role Permissions
define('ADMIN_PERMISSIONS', [
    'super_admin' => [
        'manage_admins', 'manage_users', 'manage_groups', 'manage_messages',
        'manage_reports', 'manage_settings', 'view_logs', 'view_statistics',
        'ban_users', 'delete_groups', 'export_data'
    ],
    'admin' => [
        'manage_users', 'manage_groups', 'manage_messages',
        'manage_reports', 'view_logs', 'view_statistics',
        'ban_users', 'delete_groups'
    ],
    'moderator' => [
        'manage_messages', 'manage_reports', 'view_logs',
        'ban_users'
    ]
]);

// Pagination
define('ADMIN_PER_PAGE', 20);

// Upload Settings
define('ADMIN_AVATAR_PATH', dirname(__DIR__) . '/storage/uploads/avatars');
define('ADMIN_MAX_AVATAR_SIZE', 2 * 1024 * 1024); // 2MB

/**
 * Check if current admin has permission
 */
function admin_has_permission($permission) {
    $role = $_SESSION['admin_role'] ?? '';
    $permissions = ADMIN_PERMISSIONS[$role] ?? [];
    return in_array($permission, $permissions);
}

/**
 * Require specific permission
 */
function admin_require_permission($permission) {
    if (!admin_has_permission($permission)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit;
    }
}
