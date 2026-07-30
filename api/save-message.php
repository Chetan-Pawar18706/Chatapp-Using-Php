<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Save/Unsave Message
 * ChatApp - POST /api/save-message.php
 * Toggle save status for a message (Snapchat-style)
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
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$message_id) {
    send_error('Message ID is required');
}

// Check if message exists and user is part of the conversation
$msg_check = db_fetch_single(
    "SELECT id, sender_id, receiver_id FROM messages WHERE id = ? AND is_deleted = 0",
    [$message_id],
    'i'
);

if (!$msg_check) {
    send_error('Message not found');
}

// Only sender or receiver can save the message
if ((int)$msg_check['sender_id'] !== $user_id && (int)$msg_check['receiver_id'] !== $user_id) {
    send_error('Unauthorized to save this message');
}

// Toggle save status
$existing = db_fetch_single(
    "SELECT id FROM saved_messages WHERE message_id = ? AND user_id = ?",
    [$message_id, $user_id],
    'ii'
);

if ($existing) {
    // Unsave
    $sql = "DELETE FROM saved_messages WHERE message_id = ? AND user_id = ?";
    db_execute($sql, [$message_id, $user_id], 'ii');
    send_success('Message unsaved', ['saved' => false]);
} else {
    // Save
    $sql = "INSERT INTO saved_messages (message_id, user_id) VALUES (?, ?)";
    db_execute($sql, [$message_id, $user_id], 'ii');
    send_success('Message saved', ['saved' => true]);
}
