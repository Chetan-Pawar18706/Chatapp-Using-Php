<?php
/**
 * =====================================================
 * Admin API: Groups
 * ChatApp - Group Management API
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
    case 'delete':
        $group_id = (int)($input['group_id'] ?? 0);
        if (!$group_id) admin_send_error('Group ID required');
        
        $stmt = mysqli_prepare($conn, "DELETE FROM groups WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $group_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'delete_group', 'group', $group_id);
        admin_send_success('Group deleted');
        break;
        
    case 'update_status':
        $group_id = (int)($input['group_id'] ?? 0);
        $status = $input['status'] ?? '';
        
        if (!$group_id || !in_array($status, ['active', 'archived', 'deleted'])) {
            admin_send_error('Invalid parameters');
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE groups SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $status, $group_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'update_group_status', 'group', $group_id, ['status' => $status]);
        admin_send_success('Group status updated');
        break;
        
    default:
        admin_send_error('Invalid action');
}
