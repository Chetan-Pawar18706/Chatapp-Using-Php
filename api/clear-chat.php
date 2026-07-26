<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Clear Chat
 * ChatApp - POST /api/clear-chat.php
 * Soft-delete all messages for the current user
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

$user_id = session_get_user_id();
$target_user_id = intval($input['user_id'] ?? 0);

if (!$target_user_id) {
    send_error('User ID is required');
}

// Mark all sent messages as deleted for sender
$sent_sql = "UPDATE messages SET deleted_for_sender = 1 
             WHERE sender_id = ? AND receiver_id = ? AND deleted_for_sender = 0";
db_execute($sent_sql, [$user_id, $target_user_id], 'ii');

// Mark all received messages as deleted for receiver
$recv_sql = "UPDATE messages SET deleted_for_receiver = 1 
             WHERE sender_id = ? AND receiver_id = ? AND deleted_for_receiver = 0";
db_execute($recv_sql, [$target_user_id, $user_id], 'ii');

send_success('Chat cleared');
