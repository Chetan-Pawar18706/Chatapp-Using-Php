<?php
/**
 * =====================================================
 * Admin API: Users
 * ChatApp - User Management API
 * =====================================================
 */

define('APP_RUNNING', true);

require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/helpers.php';

admin_session_init();

if (!admin_verify_session()) {
    admin_send_error('Unauthorized', 401);
}

$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admin_send_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$admin_id = admin_get_id();

switch ($action) {
    case 'ban':
        $user_id = (int)($input['user_id'] ?? 0);
        if (!$user_id) admin_send_error('User ID required');
        
        $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'banned' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'ban_user', 'user', $user_id);
        admin_send_success('User banned successfully');
        break;
        
    case 'unban':
        $user_id = (int)($input['user_id'] ?? 0);
        if (!$user_id) admin_send_error('User ID required');
        
        $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'active' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'unban_user', 'user', $user_id);
        admin_send_success('User unbanned successfully');
        break;
        
    case 'activate':
        $user_id = (int)($input['user_id'] ?? 0);
        if (!$user_id) admin_send_error('User ID required');
        
        $stmt = mysqli_prepare($conn, "UPDATE users SET is_active = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'activate_user', 'user', $user_id);
        admin_send_success('User activated');
        break;
        
    case 'deactivate':
        $user_id = (int)($input['user_id'] ?? 0);
        if (!$user_id) admin_send_error('User ID required');
        
        $stmt = mysqli_prepare($conn, "UPDATE users SET is_active = 0 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'deactivate_user', 'user', $user_id);
        admin_send_success('User deactivated');
        break;
        
    case 'delete':
        $user_id = (int)($input['user_id'] ?? 0);
        if (!$user_id) admin_send_error('User ID required');
        
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'delete_user', 'user', $user_id);
        admin_send_success('User deleted');
        break;
        
    default:
        admin_send_error('Invalid action');
}
