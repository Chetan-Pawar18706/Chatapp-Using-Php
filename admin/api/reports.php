<?php
/**
 * =====================================================
 * Admin API: Reports
 * ChatApp - Reports Management API
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

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!admin_verify_csrf($csrf_token)) {
    admin_send_error('Invalid CSRF token', 403);
}

$action = $input['action'] ?? '';
$admin_id = admin_get_id();

switch ($action) {
    case 'update_status':
        $report_id = (int)($input['report_id'] ?? 0);
        $status = $input['status'] ?? '';
        $notes = $input['notes'] ?? '';
        
        if (!$report_id || !in_array($status, ['reviewed', 'resolved', 'dismissed'])) {
            admin_send_error('Invalid parameters');
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE user_reports SET status = ?, reviewed_by = ?, reviewed_at = NOW(), resolution_notes = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'siis', $status, $admin_id, $notes, $report_id);
        mysqli_stmt_execute($stmt);
        
        admin_log_activity($admin_id, 'update_report', 'report', $report_id, ['status' => $status]);
        admin_send_success('Report updated');
        break;
        
    default:
        admin_send_error('Invalid action');
}
