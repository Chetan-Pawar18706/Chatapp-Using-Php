<?php
/**
 * =====================================================
 * API: Delete Message
 * ChatApp - POST /api/delete-message.php
 * Delete for me or delete for everyone
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
$delete_type = $input['delete_type'] ?? 'for_me'; // for_me or for_everyone
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$message_id) {
    send_error('Message ID is required');
}

// Get the message
$message_sql = "SELECT id, sender_id, receiver_id, content FROM messages WHERE id = ? LIMIT 1";
$message = db_fetch_single($message_sql, [$message_id], 'i');

if (!$message) {
    send_error('Message not found');
}

$is_sender = (int)$message['sender_id'] === $user_id;
$is_receiver = (int)$message['receiver_id'] === $user_id;

if (!$is_sender && !$is_receiver) {
    send_error('Unauthorized to delete this message');
}

if ($delete_type === 'for_everyone') {
    // Only sender can delete for everyone, and within 7 days
    if (!$is_sender) {
        send_error('Only the sender can delete for everyone');
    }
    
    // Check if within 7 days
    $msg_time = strtotime($message['created_at'] ?? '');
    if (time() - $msg_time > 7 * 24 * 60 * 60) {
        send_error('Cannot delete messages older than 7 days for everyone');
    }
    
    // Delete for everyone
    $sql = "UPDATE messages SET is_deleted = 1, content = 'This message was deleted' WHERE id = ?";
    $result = db_execute($sql, [$message_id], 'i');
    
    if ($result) {
        log_activity($user_id, 'message_deleted_everyone', ['message_id' => $message_id]);
        send_success('Message deleted for everyone', [
            'message_id' => $message_id,
            'delete_type' => 'for_everyone'
        ]);
    } else {
        send_error('Failed to delete message');
    }
} else {
    // Delete for me only
    $field = $is_sender ? 'deleted_for_sender' : 'deleted_for_receiver';
    $sql = "UPDATE messages SET {$field} = 1 WHERE id = ?";
    $result = db_execute($sql, [$message_id], 'i');
    
    if ($result) {
        log_activity($user_id, 'message_deleted_for_me', ['message_id' => $message_id]);
        send_success('Message deleted', [
            'message_id' => $message_id,
            'delete_type' => 'for_me'
        ]);
    } else {
        send_error('Failed to delete message');
    }
}
