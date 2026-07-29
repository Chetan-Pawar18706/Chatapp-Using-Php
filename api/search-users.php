<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Search Users
 * ChatApp - GET /api/search-users.php
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

if (empty($query) || strlen($query) < 2) {
    send_error('Search query must be at least 2 characters');
}

// Get blocked user IDs to exclude them
$blocked_sql = "SELECT blocked_user_id FROM block_list WHERE user_id = ?
                UNION
                SELECT user_id FROM block_list WHERE blocked_user_id = ?";
$blocked = db_fetch_all($blocked_sql, [$user_id, $user_id], 'ii');
$blocked_ids = array_column($blocked, 'blocked_user_id');
$blocked_ids[] = $user_id;
$placeholders = str_repeat('?,', count($blocked_ids) - 1) . '?';

// Search users by username, email, or friend code
$sql = "SELECT id, username, friend_code, avatar, is_online
        FROM users
        WHERE (username LIKE ? OR email LIKE ? OR friend_code LIKE ?)
        AND id NOT IN ($placeholders)
        AND status = 'active'
        LIMIT 10";

$search_term = "%{$query}%";
$params = array_merge([$search_term, $search_term, $search_term], $blocked_ids);
$types = str_repeat('s', count($blocked_ids) + 3);
$users = db_fetch_all($sql, $params, $types);

$results = [];
foreach ($users as $user) {
    $results[] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'friend_code' => $user['friend_code'],
        'avatar' => $user['avatar'],
        'is_online' => (bool)$user['is_online']
    ];
}

send_success('Search results', ['users' => $results]);
