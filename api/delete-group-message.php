<?php
/**
 * =====================================================
 * API: Delete Group Message
 * ChatApp - POST /api/delete-group-message.php
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

$message_id = (int)($input['message_id'] ?? 0);
$delete_type = $input['delete_type'] ?? 'for_me';
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$message_id) {
    send_error('Message ID is required');
}

// Get message
$msg_sql = "SELECT id, sender_id, group_id FROM messages WHERE id = ? AND group_id IS NOT NULL LIMIT 1";
$message = db_fetch_single($msg_sql, [$message_id], 'i');

if (!$message) {
    send_error('Message not found');
}

$is_sender = (int)$message['sender_id'] === $user_id;

// Check membership and role
$member_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$membership = db_fetch_single($member_sql, [$message['group_id'], $user_id], 'ii');

if (!$membership) {
    send_error('You are not a member of this group');
}

// Check permissions for delete for everyone
if ($delete_type === 'for_everyone') {
    if (!$is_sender && !in_array($membership['role'], ['admin', 'moderator'])) {
        send_error('Only sender, admins, or moderators can delete for everyone');
    }
}

// Perform delete
if ($delete_type === 'for_everyone') {
    $sql = "UPDATE messages SET is_deleted = 1, content = 'This message was deleted' WHERE id = ?";
    $result = db_execute($sql, [$message_id], 'i');
} else {
    // For group messages, we mark as deleted for the user
    // Since group messages don't have sender/receiver fields, we use a different approach
    $sql = "UPDATE messages SET is_deleted = 1, content = 'This message was deleted' WHERE id = ? AND sender_id = ?";
    $result = db_execute($sql, [$message_id, $user_id], 'ii');
}

if ($result) {
    log_activity($user_id, 'group_message_deleted', [
        'message_id' => $message_id, 
        'group_id' => $message['group_id'],
        'delete_type' => $delete_type
    ]);
    
    send_success('Message deleted', [
        'message_id' => $message_id,
        'delete_type' => $delete_type
    ]);
} else {
    send_error('Failed to delete message');
}
