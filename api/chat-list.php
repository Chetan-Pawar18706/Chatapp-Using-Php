<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Chat List
 * ChatApp - GET /api/chat-list.php
 * Returns friends with last message for messenger sidebar
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

// Get friends with last message
$sql = "SELECT 
            u.id,
            u.username,
            u.avatar,
            u.is_online,
            u.last_seen,
            m.content as last_message,
            m.created_at as last_message_time,
            m.sender_id as last_message_sender,
            m.is_read as last_message_read,
            (SELECT COUNT(*) FROM messages 
             WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0 
             AND is_deleted = 0 AND deleted_for_receiver = 0) as unread_count,
            (SELECT id FROM messages 
             WHERE ((sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?))
             ORDER BY created_at DESC LIMIT 1) as last_message_id
        FROM users u
        INNER JOIN friendships f ON (
            (f.user_id = ? AND f.friend_id = u.id) OR 
            (f.user_id = u.id AND f.friend_id = ?)
        )
        AND f.status = 'accepted'
        LEFT JOIN messages m ON (
            (m.sender_id = u.id AND m.receiver_id = ? AND m.deleted_for_receiver = 0) OR 
            (m.sender_id = ? AND m.receiver_id = u.id AND m.deleted_for_sender = 0)
        )
        AND m.id = (
            SELECT MAX(m2.id) FROM messages m2 
            WHERE ((m2.sender_id = u.id AND m2.receiver_id = ? AND m2.deleted_for_receiver = 0) 
                   OR (m2.sender_id = ? AND m2.receiver_id = u.id AND m2.deleted_for_sender = 0))
        )
        WHERE u.id != ? AND u.status = 'active'
        ORDER BY COALESCE(m.created_at, '1970-01-01') DESC, u.username ASC";

$chat_list = db_fetch_all($sql, [
    $user_id, $user_id, $user_id, $user_id, $user_id, 
    $user_id, $user_id, $user_id, $user_id, $user_id
], 'iiiiiiiiii');

$formatted_list = [];
foreach ($chat_list as $chat) {
    $last_msg = $chat['last_message'] ?? null;
    $last_time = $chat['last_message_time'] ?? null;
    
    $lock = db_fetch_single(
        "SELECT id FROM chat_locks WHERE user_id = ? AND chat_type = 'chat' AND target_id = ?",
        [$user_id, $chat['id']]
    );
    
    // Skip locked chats — they should NOT appear in normal list
    if (!empty($lock)) {
        continue;
    }
    
    $formatted_list[] = [
        'user_id' => (int)$chat['id'],
        'username' => $chat['username'],
        'avatar' => $chat['avatar'],
        'is_online' => (bool)$chat['is_online'],
        'last_seen' => $chat['last_seen'] ? time_ago($chat['last_seen']) : 'Never',
        'last_message' => $last_msg ? truncate_text($last_msg, 40) : null,
        'last_message_time' => $last_time ? time_ago($last_time) : null,
        'last_message_from_me' => $last_msg ? ((int)$chat['last_message_sender'] === $user_id) : null,
        'unread_count' => (int)$chat['unread_count'],
        'is_locked' => false
    ];
}

send_success('Chat list loaded', ['chats' => $formatted_list]);

/**
 * Truncate text helper
 */
function truncate_text($text, $length = 40) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}
