<?php
require_once __DIR__ . '/_init.php';
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
            m.media_id,
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

// Fetch media data for messages with attachments
$media_ids = array_filter(array_column($messages, 'media_id'));
$media_map = [];
if (!empty($media_ids)) {
    $placeholders = implode(',', array_fill(0, count($media_ids), '?'));
    $media_sql = "SELECT id, file_name, original_name, file_path, thumbnail_path, file_size, file_type, file_extension, category
                  FROM media WHERE id IN ($placeholders)";
    $media_types = str_repeat('i', count($media_ids));
    $media_rows = db_fetch_all($media_sql, $media_ids, $media_types);
    foreach ($media_rows as $m) {
        $m['file_size_formatted'] = format_file_size($m['file_size']);
        $m['is_image'] = $m['category'] === 'images';
        $m['is_video'] = $m['category'] === 'videos';
        $media_map[(int)$m['id']] = $m;
    }
}

// Fetch reactions for all messages (graceful if table missing)
$msg_ids = array_column($messages, 'id');
$reactions_map = [];
if (!empty($msg_ids)) {
    $placeholders = implode(',', array_fill(0, count($msg_ids), '?'));
    $react_types = str_repeat('i', count($msg_ids));
    try {
        $react_sql = "SELECT message_id, emoji, COUNT(*) as count, GROUP_CONCAT(user_id) as user_ids
                      FROM message_reactions WHERE message_id IN ($placeholders)
                      GROUP BY message_id, emoji";
        $react_rows = db_fetch_all($react_sql, $msg_ids, $react_types);
        foreach ($react_rows as $r) {
            $mid = (int)$r['message_id'];
            if (!isset($reactions_map[$mid])) $reactions_map[$mid] = [];
            $reactions_map[$mid][] = [
                'emoji' => $r['emoji'],
                'count' => (int)$r['count'],
                'user_ids' => array_map('intval', explode(',', $r['user_ids'])),
                'reacted' => in_array($user_id, array_map('intval', explode(',', $r['user_ids'])))
            ];
        }
    } catch (Exception $e) {
        // table doesn't exist, skip
    }
}

// Fetch saved status for all messages
$saved_map = [];
if (!empty($msg_ids)) {
    $placeholders = implode(',', array_fill(0, count($msg_ids), '?'));
    $saved_types = str_repeat('i', count($msg_ids));
    try {
        $saved_sql = "SELECT message_id FROM saved_messages WHERE message_id IN ($placeholders) AND user_id = ?";
        $saved_params = array_merge($msg_ids, [$user_id]);
        $saved_types .= 'i';
        $saved_rows = db_fetch_all($saved_sql, $saved_params, $saved_types);
        foreach ($saved_rows as $s) {
            $saved_map[(int)$s['message_id']] = true;
        }
    } catch (Exception $e) {
        // table doesn't exist, skip
    }
}
    } catch (Exception $e) {
        $reactions_map = [];
    }
}

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

// Instantly delete view_once messages after marking as read (skip saved)
$delete_view_once = "UPDATE messages SET is_deleted = 1 
                     WHERE sender_id = ? AND receiver_id = ? 
                     AND auto_delete = 'view_once' 
                     AND is_deleted = 0
                     AND id NOT IN (SELECT message_id FROM saved_messages)";
db_execute($delete_view_once, [$other_user_id, $user_id], 'ii');

// Format messages
$formatted_messages = [];
foreach ($messages as $msg) {
    // Decrypt content
    $msg['content'] = decrypt_message($msg['content']);
    if ($msg['reply_content'] !== null) {
        $msg['reply_content'] = decrypt_message($msg['reply_content']);
    }
    
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
        'media_id' => $msg['media_id'] ? (int)$msg['media_id'] : null,
        'media' => $msg['media_id'] ? ($media_map[(int)$msg['media_id']] ?? null) : null,
        'is_sender' => $is_sender,
        'is_deleted' => (bool)$msg['is_deleted'],
        'is_deleted_for_me' => $is_deleted_for_me,
        'is_saved' => isset($saved_map[(int)$msg['id']]),
        'status' => $is_sender ? $status : null,
        'reactions' => $reactions_map[(int)$msg['id']] ?? [],
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
