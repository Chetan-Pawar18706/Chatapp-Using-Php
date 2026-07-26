<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Search Messages
 * ChatApp - GET /api/search-messages.php
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$user_id = session_get_user_id();
$other_user_id = (int)($_GET['user_id'] ?? 0);
$query = trim($_GET['q'] ?? '');

if (!$other_user_id || empty($query)) {
    send_error('User ID and search query are required');
}

if (strlen($query) < 2) {
    send_error('Search query must be at least 2 characters');
}

// Search messages
$sql = "SELECT 
            m.id,
            m.sender_id,
            m.content,
            m.created_at,
            su.username as sender_name
        FROM messages m
        LEFT JOIN users su ON m.sender_id = su.id
        WHERE (
            (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_for_sender = 0) OR 
            (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_for_receiver = 0)
        )
        AND m.is_deleted = 0
        AND m.content LIKE ?
        ORDER BY m.created_at DESC
        LIMIT 50";

$messages = db_fetch_all($sql, [
    $user_id, $other_user_id, $other_user_id, $user_id, "%{$query}%"
], 'iiiis');

$formatted_messages = [];
foreach ($messages as $msg) {
    $formatted_messages[] = [
        'id' => (int)$msg['id'],
        'sender_id' => (int)$msg['sender_id'],
        'sender_name' => $msg['sender_name'],
        'content' => $msg['content'],
        'timestamp' => format_date($msg['created_at'], 'M d, Y h:i A'),
        'is_sender' => (int)$msg['sender_id'] === $user_id
    ];
}

send_success('Search results', [
    'messages' => $formatted_messages,
    'total' => count($formatted_messages)
]);
