<?php
/**
 * =====================================================
 * Admin API: Notifications
 * ChatApp - Admin Notifications
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

if (!$conn) {
    admin_send_error('Database connection failed', 500);
}

$count = 0;
$pending_reports = 0;

$report_query = "SELECT COUNT(*) as count FROM user_reports WHERE status = 'pending'";
$report_result = mysqli_query($conn, $report_query);
if ($report_result) {
    $report_row = mysqli_fetch_assoc($report_result);
    $pending_reports = $report_row['count'] ?? 0;
    $count += $pending_reports;
}

admin_send_success('Notifications retrieved', [
    'count' => $count,
    'pending_reports' => $pending_reports
]);
