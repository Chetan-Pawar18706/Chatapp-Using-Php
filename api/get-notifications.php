<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Get Notifications API
 * ChatApp - Retrieve User Notifications
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/compat.php';
require_once dirname(__DIR__) . '/includes/notification_helpers.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

$user_id = get_user_id();

// Get parameters
$limit = min(intval($_GET['limit'] ?? 20), 50);
$offset = max(intval($_GET['offset'] ?? 0), 0);
$unread_only = isset($_GET['unread']) && $_GET['unread'] === '1';

// Get notifications
$notifications = get_user_notifications($user_id, $limit, $offset, $unread_only);

// Get unread count
$unread_count = get_unread_notification_count($user_id);

send_json_response(200, [
    'success' => true,
    'data' => [
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'has_more' => count($notifications) === $limit
    ]
]);
