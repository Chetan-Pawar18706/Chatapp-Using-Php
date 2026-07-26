<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Chat User Info
 * ChatApp - GET /api/chat-user-info.php
 * Get user info for chat header
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

if (!$other_user_id) {
    send_error('User ID is required');
}

// Get user info
$sql = "SELECT id, username, avatar, is_online, last_seen, bio 
        FROM users WHERE id = ? AND status = 'active' LIMIT 1";
$user = db_fetch_single($sql, [$other_user_id], 'i');

if (!$user) {
    send_error('User not found');
}

// Get unread count
$unread_sql = "SELECT COUNT(*) as count FROM messages 
               WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
$unread = db_fetch_single($unread_sql, [$other_user_id, $user_id], 'ii');

// Get typing status
$typing_sql = "SELECT is_typing, last_typing_at FROM typing_status 
               WHERE user_id = ? AND chat_with_user_id = ? LIMIT 1";
$typing = db_fetch_single($typing_sql, [$other_user_id, $user_id], 'ii');

$is_typing = false;
if ($typing && $typing['is_typing']) {
    $typing_time = strtotime($typing['last_typing_at']);
    if (time() - $typing_time < 10) {
        $is_typing = true;
    }
}

send_success('User info loaded', [
    'user_id' => (int)$user['id'],
    'username' => $user['username'],
    'avatar' => $user['avatar'],
    'is_online' => (bool)$user['is_online'],
    'last_seen' => $user['last_seen'] ? time_ago($user['last_seen']) : 'Online',
    'bio' => $user['bio'],
    'unread_count' => (int)($unread['count'] ?? 0),
    'is_typing' => $is_typing
]);
