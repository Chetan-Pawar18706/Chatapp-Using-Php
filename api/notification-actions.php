<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Notification Actions API
 * ChatApp - Mark Read, Delete, Clear
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/notification_helpers.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, ['success' => false, 'message' => 'Method not allowed']);
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
}

$user_id = get_user_id();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'mark_read':
        $notification_id = intval($_POST['notification_id'] ?? 0);
        if ($notification_id <= 0) {
            send_json_response(400, ['success' => false, 'message' => 'Invalid notification ID']);
        }
        
        if (mark_notification_read($notification_id, $user_id)) {
            send_json_response(200, ['success' => true, 'message' => 'Notification marked as read']);
        } else {
            send_json_response(500, ['success' => false, 'message' => 'Failed to mark notification']);
        }
        break;
        
    case 'mark_all_read':
        if (mark_all_notifications_read($user_id)) {
            send_json_response(200, ['success' => true, 'message' => 'All notifications marked as read']);
        } else {
            send_json_response(500, ['success' => false, 'message' => 'Failed to mark notifications']);
        }
        break;
        
    case 'delete':
        $notification_id = intval($_POST['notification_id'] ?? 0);
        if ($notification_id <= 0) {
            send_json_response(400, ['success' => false, 'message' => 'Invalid notification ID']);
        }
        
        if (delete_notification($notification_id, $user_id)) {
            send_json_response(200, ['success' => true, 'message' => 'Notification deleted']);
        } else {
            send_json_response(500, ['success' => false, 'message' => 'Failed to delete notification']);
        }
        break;
        
    case 'clear_all':
        if (clear_all_notifications($user_id)) {
            send_json_response(200, ['success' => true, 'message' => 'All notifications cleared']);
        } else {
            send_json_response(500, ['success' => false, 'message' => 'Failed to clear notifications']);
        }
        break;
        
    default:
        send_json_response(400, ['success' => false, 'message' => 'Invalid action']);
}
