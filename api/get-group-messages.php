<?php
/**
 * =====================================================
 * API: Get Group Messages
 * ChatApp - GET /api/get-group-messages.php
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
$group_id = (int)($_GET['group_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

if (!$group_id) {
    send_error('Group ID is required');
}

// Check membership
$member_sql = "SELECT role, joined_at FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$membership = db_fetch_single($member_sql, [$group_id, $user_id], 'ii');

if (!$membership) {
    send_error('You are not a member of this group');
}

// Get messages
$sql = "SELECT 
            m.id,
            m.sender_id,
            m.content,
            m.message_type,
            m.reply_to_id,
            m.is_deleted,
            m.created_at,
            su.username as sender_name,
            su.avatar as sender_avatar,
            rm.content as reply_content,
            rm.sender_id as reply_sender_id,
            ru.username as reply_sender_name,
            (SELECT COUNT(*) FROM group_messages_read WHERE message_id = m.id) as read_count
        FROM messages m
        LEFT JOIN users su ON m.sender_id = su.id
        LEFT JOIN messages rm ON m.reply_to_id = rm.id
        LEFT JOIN users ru ON rm.sender_id = ru.id
        WHERE m.group_id = ? 
        AND m.is_deleted = 0
        AND m.created_at >= ?
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?";

$messages = db_fetch_all($sql, [$group_id, $membership['joined_at'], $limit, $offset], 'iisii');

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM messages 
              WHERE group_id = ? AND is_deleted = 0 AND created_at >= ?";
$count_result = db_fetch_single($count_sql, [$group_id, $membership['joined_at']], 'is');
$total_messages = (int)($count_result['total'] ?? 0);

// Mark as read
$mark_sql = "INSERT IGNORE INTO group_messages_read (group_id, message_id, user_id, read_at)
             SELECT ?, m.id, ?, NOW()
             FROM messages m
             WHERE m.group_id = ? AND m.sender_id != ?
             AND m.created_at > COALESCE(
                 (SELECT MAX(read_at) FROM group_messages_read WHERE group_id = ? AND user_id = ?),
                 ?
             )";
db_execute($mark_sql, [$group_id, $user_id, $group_id, $user_id, $group_id, $user_id, $membership['joined_at']], 'iiiiiss');

// Get group members for mention suggestions
$members_sql = "SELECT user_id, username FROM group_members gm 
                INNER JOIN users u ON gm.user_id = u.id
                WHERE gm.group_id = ?";
$group_members = db_fetch_all($members_sql, [$group_id], 'i');

// Format messages
$formatted_messages = [];
foreach ($messages as $msg) {
    $is_sender = (int)$msg['sender_id'] === $user_id;
    
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
        'sender_name' => $msg['sender_name'],
        'sender_avatar' => $msg['sender_avatar'],
        'content' => $msg['is_deleted'] ? 'This message was deleted' : $msg['content'],
        'message_type' => $msg['message_type'],
        'is_sender' => $is_sender,
        'is_deleted' => (bool)$msg['is_deleted'],
        'reply_to' => $reply_data,
        'read_count' => (int)$msg['read_count'],
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
    'has_more' => ($offset + $limit) < $total_messages,
    'members' => array_map(function($m) {
        return ['user_id' => (int)$m['user_id'], 'username' => $m['username']];
    }, $group_members)
]);
