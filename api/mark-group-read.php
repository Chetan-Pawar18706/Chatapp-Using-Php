<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Mark Group Messages Read
 * ChatApp - POST /api/mark-group-read.php
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

session_initialize();

if (!session_verify_security() || !session_is_logged_in()) {
    send_error('Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$group_id = (int)($input['group_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$group_id) {
    send_error('Group ID is required');
}

// Check membership
$member_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$membership = db_fetch_single($member_sql, [$group_id, $user_id], 'ii');

if (!$membership) {
    send_error('You are not a member of this group');
}

// Mark all messages as read
$mark_sql = "INSERT IGNORE INTO group_messages_read (group_id, message_id, user_id, read_at)
             SELECT ?, m.id, ?, NOW()
             FROM messages m
             WHERE m.group_id = ? AND m.sender_id != ?";
$result = db_execute($mark_sql, [$group_id, $user_id, $group_id, $user_id], 'iiii');

// Mark notifications as read
$notif_sql = "UPDATE group_notifications SET is_read = 1 
              WHERE group_id = ? AND user_id = ? AND is_read = 0";
db_execute($notif_sql, [$group_id, $user_id], 'ii');

send_success('Messages marked as read');
