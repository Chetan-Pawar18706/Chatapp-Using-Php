<?php
/**
 * =====================================================
 * Admin API: Blocked
 * ChatApp - Blocked Users API
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
    case 'unblock':
        $block_id = (int)($input['block_id'] ?? 0);
        if (!$block_id) admin_send_error('Block ID required');
        
        $stmt = mysqli_prepare($conn, "DELETE FROM block_list WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $block_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'unblock_user', 'block', $block_id);
        admin_send_success('User unblocked');
        break;
        
    default:
        admin_send_error('Invalid action');
}
