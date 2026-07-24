<?php
/**
 * =====================================================
 * API: Get Recent Chats
 * ChatApp - GET /api/recent-chats.php
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

// Get recent conversations
$sql = "SELECT 
            u.id as user_id,
            u.username,
            u.avatar,
            u.is_online,
            m.content as last_message,
            m.created_at as last_message_time,
            m.sender_id,
            (SELECT COUNT(*) FROM messages 
             WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
        FROM users u
        INNER JOIN messages m ON (
            (m.sender_id = u.id AND m.receiver_id = ?) OR 
            (m.sender_id = ? AND m.receiver_id = u.id)
        )
        AND m.id = (
            SELECT MAX(m2.id) FROM messages m2 
            WHERE (m2.sender_id = u.id AND m2.receiver_id = ?) OR 
                  (m2.sender_id = ? AND m2.receiver_id = u.id)
        )
        WHERE u.id != ? AND u.status = 'active'
        ORDER BY m.created_at DESC
        LIMIT 10";

$recent_chats = db_fetch_all($sql, [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id], 'iiiiii');

$chats = [];
foreach ($recent_chats as $chat) {
    $chats[] = [
        'user_id' => (int)$chat['user_id'],
        'username' => $chat['username'],
        'avatar' => $chat['avatar'],
        'is_online' => (bool)$chat['is_online'],
        'last_message' => $chat['last_message'],
        'last_message_time' => time_ago($chat['last_message_time']),
        'is_sender' => (int)$chat['sender_id'] === $user_id,
        'unread_count' => (int)$chat['unread_count']
    ];
}

send_success('Recent chats loaded', ['chats' => $chats]);
