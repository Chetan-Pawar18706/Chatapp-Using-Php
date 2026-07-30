<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Search Suggestions (Live Dropdown)
 * ChatApp - GET /api/search-suggestions.php
 * Instagram-style live search suggestions
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
$query = trim($_GET['q'] ?? '');

if (empty($query) || strlen($query) < 1) {
    send_success('Suggestions', ['users' => [], 'friends' => []]);
}

$search_term = '%' . $query . '%';
$starts_with = $query . '%';

// Get blocked user IDs
$blocked_sql = "SELECT blocked_user_id FROM block_list WHERE user_id = ?
                UNION
                SELECT user_id FROM block_list WHERE blocked_user_id = ?";
$blocked = db_fetch_all($blocked_sql, [$user_id, $user_id], 'ii');
$blocked_ids = array_column($blocked, 'blocked_user_id');
$blocked_ids[] = $user_id;

// 1. Get friends first (friends always show, even if search_visibility = 'friends')
$friend_sql = "SELECT u.id, u.username, u.avatar, u.is_online, u.user_status,
               'friend' as result_type,
               CASE 
                   WHEN u.username LIKE ? THEN 1
                   WHEN u.username LIKE ? THEN 2
                   ELSE 3
               END as relevance
               FROM users u
               INNER JOIN friendships f ON (
                   (f.user_id = ? AND f.friend_id = u.id) OR 
                   (f.user_id = u.id AND f.friend_id = ?)
               )
               WHERE f.status = 'accepted'
               AND (u.username LIKE ?)
               AND u.id NOT IN (" . str_repeat('?,', count($blocked_ids) - 1) . "?)
               ORDER BY relevance, u.username
               LIMIT 5";

$friend_params = array_merge([$starts_with, $search_term, $user_id, $user_id, $search_term], $blocked_ids);
$friend_types = 'ssii' . str_repeat('i', count($blocked_ids)) . 's';
$friends = db_fetch_all($friend_sql, $friend_params, $friend_types);

// 2. Get non-friend users (respecting search_visibility)
$user_sql = "SELECT id, username, avatar, is_online, user_status,
             'user' as result_type,
             CASE 
                 WHEN username LIKE ? THEN 1
                 WHEN username LIKE ? THEN 2
                 ELSE 3
             END as relevance
             FROM users 
             WHERE username LIKE ?
             AND id NOT IN (" . str_repeat('?,', count($blocked_ids) - 1) . "?)
             AND status = 'active'
             AND id != ?
             AND (
                 search_visibility = 'everyone'
                 OR (search_visibility = 'friends' AND id IN (
                     SELECT CASE WHEN user_id = ? THEN friend_id ELSE user_id END
                     FROM friendships
                     WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'
                 ))
             )
             ORDER BY relevance, username
             LIMIT 5";

$user_params = array_merge([$starts_with, $search_term, $search_term], $blocked_ids, [$user_id, $user_id, $user_id, $user_id]);
$user_types = 'ss' . str_repeat('i', count($blocked_ids)) . 'iii';
$users = db_fetch_all($user_sql, $user_params, $user_types);

// Format results
$formatted_friends = [];
foreach ($friends as $f) {
    $formatted_friends[] = [
        'id' => (int)$f['id'],
        'username' => $f['username'],
        'avatar' => $f['avatar'],
        'is_online' => (bool)$f['is_online'],
        'user_status' => $f['user_status'],
        'initials' => strtoupper(substr($f['username'], 0, 2)),
        'status_color' => get_status_color($f['user_status'] ?? 'online'),
        'is_friend' => true
    ];
}

$formatted_users = [];
foreach ($users as $u) {
    // Check if already in friends list
    $is_friend = false;
    foreach ($friends as $f) {
        if ((int)$f['id'] === (int)$u['id']) {
            $is_friend = true;
            break;
        }
    }
    if (!$is_friend) {
        $formatted_users[] = [
            'id' => (int)$u['id'],
            'username' => $u['username'],
            'avatar' => $u['avatar'],
            'is_online' => (bool)$u['is_online'],
            'user_status' => $u['user_status'],
            'initials' => strtoupper(substr($u['username'], 0, 2)),
            'status_color' => get_status_color($u['user_status'] ?? 'online'),
            'is_friend' => false
        ];
    }
}

send_success('Suggestions', [
    'friends' => $formatted_friends,
    'users' => $formatted_users
]);
