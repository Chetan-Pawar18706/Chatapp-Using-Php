<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Send Group Message
 * ChatApp - POST /api/send-group-message.php
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

$group_id = (int)($input['group_id'] ?? 0);
$content = trim($input['content'] ?? '');
$reply_to_id = !empty($input['reply_to_id']) ? (int)$input['reply_to_id'] : null;
$auto_delete = $input['auto_delete'] ?? '12hours';
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

// Validate auto_delete value
$valid_auto_delete = ['view_once', '12hours'];
if (!in_array($auto_delete, $valid_auto_delete)) {
    $auto_delete = '12hours';
}

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$group_id) {
    send_error('Group ID is required');
}

if (empty($content)) {
    send_error('Message content is required');
}

if (strlen($content) > 5000) {
    send_error('Message too long (max 5000 characters)');
}

// Check membership
$member_sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
$membership = db_fetch_single($member_sql, [$group_id, $user_id], 'ii');

if (!$membership) {
    send_error('You are not a member of this group');
}

// Check if group exists
$group_sql = "SELECT name FROM groups WHERE id = ? AND status = 'active' LIMIT 1";
$group = db_fetch_single($group_sql, [$group_id], 'i');

if (!$group) {
    send_error('Group not found');
}

// Validate reply_to if provided
if ($reply_to_id) {
    $reply_check_sql = "SELECT id FROM messages WHERE id = ? AND group_id = ?";
    $reply_check = db_fetch_single($reply_check_sql, [$reply_to_id, $group_id], 'ii');
    if (!$reply_check) {
        $reply_to_id = null;
    }
}

// Check if auto_delete column exists
$has_auto_delete = false;
try {
    $check_col = db_fetch_single("SHOW COLUMNS FROM messages LIKE 'auto_delete'", [], '');
    $has_auto_delete = (bool)$check_col;
} catch (Exception $e) {
    $has_auto_delete = false;
}

// Encrypt message content
$encrypted_content = encrypt_message($content);

// Insert message
if ($has_auto_delete) {
    $sql = "INSERT INTO messages (sender_id, group_id, content, message_type, reply_to_id, auto_delete, created_at) 
            VALUES (?, ?, ?, 'text', ?, ?, NOW())";
    $result = db_execute($sql, [$user_id, $group_id, $encrypted_content, $reply_to_id, $auto_delete], 'iisss');
} else {
    $sql = "INSERT INTO messages (sender_id, group_id, content, message_type, reply_to_id, created_at) 
            VALUES (?, ?, ?, 'text', ?, NOW())";
    $result = db_execute($sql, [$user_id, $group_id, $encrypted_content, $reply_to_id], 'iiss');
}

if (!$result) {
    send_error('Failed to send message');
}

$message_id = db_insert_id();

// Get sender info
$sender_sql = "SELECT username, avatar FROM users WHERE id = ? LIMIT 1";
$sender = db_fetch_single($sender_sql, [$user_id], 'i');

// Build reply data
$reply_data = null;
if ($reply_to_id) {
    $reply_sql = "SELECT rm.content, ru.username as sender_name 
                 FROM messages rm 
                 LEFT JOIN users ru ON rm.sender_id = ru.id
                 WHERE rm.id = ? LIMIT 1";
    $reply_msg = db_fetch_single($reply_sql, [$reply_to_id], 'i');
    if ($reply_msg) {
        $reply_data = [
            'id' => $reply_to_id,
            'content' => decrypt_message($reply_msg['content']),
            'sender_name' => $reply_msg['sender_name']
        ];
    }
}

// Check for @mentions and create notifications
preg_match_all('/@(\w+)/', $content, $mentions);
if (!empty($mentions[1])) {
    foreach ($mentions[1] as $mentioned_username) {
        $mention_sql = "SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1";
        $mentioned_user = db_fetch_single($mention_sql, [$mentioned_username, $user_id], 'si');
        
        if ($mentioned_user) {
            // Check if mentioned user is in the group
            $check_member_sql = "SELECT user_id FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1";
            $is_member = db_fetch_single($check_member_sql, [$group_id, $mentioned_user['id']], 'ii');
            
            if ($is_member) {
                // Create notification in main notifications table
                require_once __DIR__ . '/../includes/notification_helpers.php';
                create_notification(
                    $mentioned_user['id'],
                    $user_id,
                    'mention',
                    'You were mentioned',
                    '@' . $mentioned_username . ' mentioned you in ' . $group['name'],
                    ['group_id' => $group_id, 'message_id' => $message_id]
                );
                
                // Also keep existing group_notifications
                $notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                             VALUES (?, ?, 'new_message', ?, ?, NOW())";
                $msg = "@" . $mentioned_username . " mentioned you in " . $group['name'];
                db_execute($notif_sql, [$group_id, $mentioned_user['id'], $msg, $user_id], 'iisi');
            }
        }
    }
}

// Create notifications for all group members (except sender)
$members_sql = "SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?";
$members = db_fetch_all($members_sql, [$group_id, $user_id], 'ii');

foreach ($members as $member) {
    $notif_sql = "INSERT INTO group_notifications (group_id, user_id, notification_type, message, created_by, created_at)
                 VALUES (?, ?, 'new_message', ?, ?, NOW())";
    $msg = ($sender['username'] ?? 'Someone') . ': ' . substr($content, 0, 50);
    db_execute($notif_sql, [$group_id, $member['user_id'], $msg, $user_id], 'iisi');
}

$formatted_message = [
    'id' => (int)$message_id,
    'sender_id' => (int)$user_id,
    'sender_name' => $sender['username'] ?? 'Unknown',
    'sender_avatar' => $sender['avatar'] ?? null,
    'content' => $content,
    'message_type' => 'text',
    'is_sender' => true,
    'is_deleted' => false,
    'reply_to' => $reply_data,
    'timestamp' => format_date(date('Y-m-d H:i:s'), 'h:i A'),
    'date' => format_date(date('Y-m-d H:i:s'), 'M d, Y')
];

send_success('Message sent', ['message' => $formatted_message]);
