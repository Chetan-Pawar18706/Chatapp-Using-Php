<?php
/**
 * =====================================================
 * API: Get Mutual Friends
 * ChatApp - GET /api/get-mutual-friends.php
 * Get mutual friends between current user and another user
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

// Get mutual friends
$sql = "SELECT DISTINCT
            u.id,
            u.username,
            u.avatar,
            u.is_online
        FROM users u
        INNER JOIN friendships f1 ON (
            (f1.user_id = ? AND f1.friend_id = u.id) OR 
            (f1.user_id = u.id AND f1.friend_id = ?)
        )
        AND f1.status = 'accepted'
        INNER JOIN friendships f2 ON (
            (f2.user_id = ? AND f2.friend_id = u.id) OR 
            (f2.user_id = u.id AND f2.friend_id = ?)
        )
        AND f2.status = 'accepted'
        WHERE u.id != ? AND u.id != ? AND u.status = 'active'
        ORDER BY u.username ASC
        LIMIT 20";

$mutual_friends = db_fetch_all($sql, [
    $user_id, $user_id, 
    $other_user_id, $other_user_id,
    $user_id, $other_user_id
], 'iiiiii');

$formatted_friends = [];
foreach ($mutual_friends as $friend) {
    $formatted_friends[] = [
        'id' => (int)$friend['id'],
        'username' => $friend['username'],
        'avatar' => $friend['avatar'],
        'is_online' => (bool)$friend['is_online']
    ];
}

send_success('Mutual friends loaded', [
    'mutual_friends' => $formatted_friends,
    'count' => count($formatted_friends)
]);
