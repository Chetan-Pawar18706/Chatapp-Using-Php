<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Dashboard Data
 * ChatApp - GET /api/dashboard.php
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

session_initialize();

// Verify session
if (!session_verify_security()) {
    send_error('Session expired', 401);
}

if (!session_is_logged_in()) {
    send_error('Please login', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$user_id = session_get_user_id();

// Get user profile data
$user_sql = "SELECT id, username, email, friend_code, avatar, bio, is_online, last_seen, created_at 
             FROM users WHERE id = ? AND status = 'active' LIMIT 1";
$user = db_fetch_single($user_sql, [$user_id], 'i');

if (!$user) {
    send_error('User not found', 404);
}

// Get friend count
$friend_count_sql = "SELECT COUNT(*) as count FROM friendships 
                     WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'";
$friend_count = db_fetch_single($friend_count_sql, [$user_id, $user_id], 'ii');

// Get pending friend requests count
$request_count_sql = "SELECT COUNT(*) as count FROM friendships 
                      WHERE friend_id = ? AND status = 'pending'";
$request_count = db_fetch_single($request_count_sql, [$user_id], 'i');

// Get unread messages count
$unread_sql = "SELECT COUNT(*) as count FROM messages 
               WHERE receiver_id = ? AND is_read = 0";
$unread_count = db_fetch_single($unread_sql, [$user_id], 'i');

// Get groups count
$group_count_sql = "SELECT COUNT(*) as count FROM group_members 
                    WHERE user_id = ?";
$group_count = db_fetch_single($group_count_sql, [$user_id], 'i');

$dashboard_data = [
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'friend_code' => $user['friend_code'],
        'avatar' => $user['avatar'],
        'bio' => $user['bio'],
        'is_online' => (bool)$user['is_online'],
        'last_seen' => $user['last_seen'] ? time_ago($user['last_seen']) : 'Now',
        'member_since' => format_date($user['created_at'], 'M Y')
    ],
    'stats' => [
        'friends' => (int)($friend_count['count'] ?? 0),
        'requests' => (int)($request_count['count'] ?? 0),
        'unread_messages' => (int)($unread_count['count'] ?? 0),
        'groups' => (int)($group_count['count'] ?? 0)
    ]
];

send_success('Dashboard data loaded', $dashboard_data);
