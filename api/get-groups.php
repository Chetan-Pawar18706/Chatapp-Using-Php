<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Groups List
 * ChatApp - GET /api/get-groups.php
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

// Get user's groups
$sql = "SELECT 
            g.id,
            g.name,
            g.description,
            g.avatar,
            g.created_by,
            g.created_at,
            gm.role as my_role,
            gm.joined_at,
            (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
            (SELECT username FROM users WHERE id = g.created_by) as creator_name,
            (SELECT COUNT(*) FROM messages 
             WHERE group_id = g.id 
             AND created_at > COALESCE(
                 (SELECT MAX(gmr.read_at) FROM group_messages_read gmr 
                  WHERE gmr.group_id = g.id AND gmr.user_id = ?), 
                 gm.joined_at
             )
             AND sender_id != ?) as unread_count,
            (SELECT content FROM messages 
             WHERE group_id = g.id AND is_deleted = 0
             ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages 
             WHERE group_id = g.id AND is_deleted = 0
             ORDER BY created_at DESC LIMIT 1) as last_message_time,
            (SELECT sender_id FROM messages 
             WHERE group_id = g.id AND is_deleted = 0
             ORDER BY created_at DESC LIMIT 1) as last_message_sender
        FROM groups g
        INNER JOIN group_members gm ON g.id = gm.group_id
        WHERE gm.user_id = ? AND g.status = 'active'
        ORDER BY last_message_time DESC, g.name ASC";

$groups = db_fetch_all($sql, [$user_id, $user_id, $user_id], 'iii');

$formatted_groups = [];
foreach ($groups as $group) {
    $last_msg = $group['last_message'];
    $from_me = $group['last_message_sender'] ? ((int)$group['last_message_sender'] === $user_id) : false;
    
    $lock = db_fetch_single(
        "SELECT id FROM chat_locks WHERE user_id = ? AND chat_type = 'group' AND target_id = ?",
        [$user_id, $group['id']]
    );
    
    // Skip locked groups — they should NOT appear in normal list
    if (!empty($lock)) {
        continue;
    }
    
    $formatted_groups[] = [
        'id' => (int)$group['id'],
        'name' => $group['name'],
        'description' => $group['description'],
        'avatar' => $group['avatar'],
        'creator_name' => $group['creator_name'],
        'my_role' => $group['my_role'],
        'member_count' => (int)$group['member_count'],
        'unread_count' => (int)$group['unread_count'],
        'last_message' => $last_msg ? truncate_text($last_msg, 40) : null,
        'last_message_time' => $group['last_message_time'] ? time_ago($group['last_message_time']) : null,
        'last_message_from_me' => $from_me,
        'is_locked' => false
    ];
}

send_success('Groups loaded', ['groups' => $formatted_groups]);
