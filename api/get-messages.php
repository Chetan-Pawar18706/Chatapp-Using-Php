<?php
/**
 * =====================================================
 * API: Get Messages
 * ChatApp - GET /api/get-messages.php
 * Returns messages between two users with pagination
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
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

if (!$other_user_id) {
    send_error('User ID is required');
}

// Verify friendship
$friendship_sql = "SELECT id FROM friendships 
                   WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
                   AND status = 'accepted' LIMIT 1";
$friendship = db_fetch_single($friendship_sql, [$user_id, $other_user_id, $other_user_id, $user_id], 'iiii');

if (!$friendship) {
    send_error('You are not friends with this user');
}

// Get messages
$sql = "SELECT 
            m.id,
            m.sender_id,
            m.receiver_id,
            m.content,
            m.message_type,
            m.reply_to_id,
            m.is_read,
            m.is_deleted,
            m.deleted_for_sender,
            m.deleted_for_receiver,
            m.delivered_at,
            m.seen_at,
            m.created_at,
            -- Reply message details
            rm.content as reply_content,
            rm.sender_id as reply_sender_id,
            ru.username as reply_sender_name,
            -- Sender details
            su.username as sender_name,
            su.avatar as sender_avatar
        FROM messages m
        LEFT JOIN messages rm ON m.reply_to_id = rm.id
        LEFT JOIN users ru ON rm.sender_id = ru.id
        LEFT JOIN users su ON m.sender_id = su.id
        WHERE (
            (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_for_sender = 0) OR 
            (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_for_receiver = 0)
        )
        AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?";

$messages = db_fetch_all($sql, [$user_id, $other_user_id, $other_user_id, $user_id, $limit, $offset], 'iiiiii');

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM messages 
              WHERE (
                  (sender_id = ? AND receiver_id = ? AND deleted_for_sender = 0) OR 
                  (sender_id = ? AND receiver_id = ? AND deleted_for_receiver = 0)
              )
              AND is_deleted = 0";
$count_result = db_fetch_single($count_sql, [$user_id, $other_user_id, $other_user_id, $user_id], 'iiii');
$total_messages = (int)($count_result['total'] ?? 0);

// Mark messages as read
$mark_read_sql = "UPDATE messages SET is_read = 1, seen_at = NOW() 
                  WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
db_execute($mark_read_sql, [$other_user_id, $user_id], 'ii');

// Format messages
$formatted_messages = [];
foreach ($messages as $msg) {
    $is_sender = (int)$msg['sender_id'] === $user_id;
    
    // Determine message status
    $status = 'sent';
    if ($msg['delivered_at']) {
        $status = 'delivered';
    }
    if ($msg['seen_at']) {
        $status = 'seen';
    }
    
    // Handle deleted messages
    $is_deleted_for_me = false;
    if ($is_sender && $msg['deleted_for_sender']) {
        $is_deleted_for_me = true;
    }
    if (!$is_sender && $msg['deleted_for_receiver']) {
        $is_deleted_for_me = true;
    }
    
    // Build reply data
    $reply_data = null;
    if ($msg['reply_to_id'] && $msg['reply_content'] !== null) {
        $reply_data = [
            'id' => (int)$msg['reply_to_id'],
            'content' => $msg['reply_content'],
            'sender_name' => $msg['reply_sender_name']
        ];
    }
    
    $formatted_messages[] = [
        'id' => (int)$msg['id'],
        'sender_id' => (int)$msg['sender_id'],
        'content' => $is_deleted_for_me ? 'This message was deleted' : $msg['content'],
        'message_type' => $msg['message_type'],
        'is_sender' => $is_sender,
        'is_deleted' => (bool)$msg['is_deleted'],
        'is_deleted_for_me' => $is_deleted_for_me,
        'status' => $is_sender ? $status : null,
        'reply_to' => $reply_data,
        'timestamp' => format_date($msg['created_at'], 'h:i A'),
        'date' => format_date($msg['created_at'], 'M d, Y')
    ];
}

// Reverse to show oldest first
$formatted_messages = array_reverse($formatted_messages);

send_success('Messages loaded', [
    'messages' => $formatted_messages,
    'total' => $total_messages,
    'page' => $page,
    'has_more' => ($offset + $limit) < $total_messages
]);
