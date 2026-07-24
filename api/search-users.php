<?php
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

// Search users by username, email, or friend code
$sql = "SELECT id, username, email, friend_code, avatar, is_online
        FROM users 
        WHERE (username LIKE ? OR email LIKE ? OR friend_code LIKE ?)
        AND id != ? 
        AND status = 'active'
        LIMIT 10";

$search_term = "%{$query}%";
$users = db_fetch_all($sql, [$search_term, $search_term, $search_term, $user_id], 'sssi');

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
