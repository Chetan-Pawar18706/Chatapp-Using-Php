<?php
/**
 * =====================================================
 * Admin API: Notifications
 * ChatApp - Admin Notifications
 * =====================================================
 */

define('APP_RUNNING', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/admin/config.php';
require_once dirname(__DIR__) . '/admin/auth.php';
require_once dirname(__DIR__) . '/admin/helpers.php';

admin_session_init();

if (!admin_verify_session()) {
    admin_send_error('Unauthorized', 401);
}

// Get pending notifications count
$count = 0;

// Pending reports
$report_query = "SELECT COUNT(*) as count FROM user_reports WHERE status = 'pending'";
$count += mysqli_fetch_assoc(mysqli_query($conn, $report_query))['count'];

// Pending messages (could add more notification types)

admin_send_success('Notifications retrieved', [
    'count' => $count,
    'pending_reports' => mysqli_fetch_assoc(mysqli_query($conn, $report_query))['count']
]);
