<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Search Friends
 * ChatApp - GET /api/search-friends.php
 * Search by username or friend code
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
$search_type = $_GET['type'] ?? 'all'; // all, username, code

if (empty($query) || strlen($query) < 2) {
    send_error('Search query must be at least 2 characters');
}

// Get blocked user IDs to exclude them
$blocked_sql = "SELECT blocked_user_id FROM block_list WHERE user_id = ? 
                UNION 
                SELECT user_id FROM block_list WHERE blocked_user_id = ?";
$blocked = db_fetch_all($blocked_sql, [$user_id, $user_id], 'ii');
$blocked_ids = array_column($blocked, 'blocked_user_id');
$blocked_ids[] = $user_id; // Exclude self

$placeholders = str_repeat('?,', count($blocked_ids) - 1) . '?';

// Build search query based on type
switch ($search_type) {
    case 'code':
        $sql = "SELECT id, username, email, friend_code, avatar, is_online, last_seen
                FROM users 
                WHERE friend_code LIKE ? 
                AND id NOT IN ($placeholders)
                AND status = 'active'
                LIMIT 10";
        $params = array_merge(["%{$query}%"], $blocked_ids);
        $types = str_repeat('s', count($blocked_ids) + 1);
        break;
    
    case 'username':
        $sql = "SELECT id, username, email, friend_code, avatar, is_online, last_seen
                FROM users 
                WHERE username LIKE ? 
                AND id NOT IN ($placeholders)
                AND status = 'active'
                LIMIT 10";
        $params = array_merge(["%{$query}%"], $blocked_ids);
        $types = str_repeat('s', count($blocked_ids) + 1);
        break;
    
    default: // all
        $sql = "SELECT id, username, email, friend_code, avatar, is_online, last_seen
                FROM users 
                WHERE (username LIKE ? OR friend_code LIKE ? OR email LIKE ?)
                AND id NOT IN ($placeholders)
                AND status = 'active'
                LIMIT 10";
        $params = array_merge(["%{$query}%", "%{$query}%", "%{$query}%"], $blocked_ids);
        $types = str_repeat('s', count($blocked_ids) + 3);
        break;
}

$users = db_fetch_all($sql, $params, $types);

// Get friendship status for each user
$results = [];
foreach ($users as $user) {
    // Check friendship status
    $friendship_sql = "SELECT id, status, user_id 
                       FROM friendships 
                       WHERE (user_id = ? AND friend_id = ?) 
                       OR (user_id = ? AND friend_id = ?)
                       LIMIT 1";
    $friendship = db_fetch_single($friendship_sql, [$user_id, $user['id'], $user['id'], $user_id], 'iiii');
    
    $friendship_status = 'none';
    $friendship_id = null;
    $can_send_request = true;
    
    if ($friendship) {
        $friendship_id = (int)$friendship['id'];
        
        if ($friendship['status'] === 'accepted') {
            $friendship_status = 'friends';
            $can_send_request = false;
        } elseif ($friendship['status'] === 'pending') {
            if ((int)$friendship['user_id'] === $user_id) {
                $friendship_status = 'request_sent';
            } else {
                $friendship_status = 'request_received';
            }
            $can_send_request = false;
        } elseif ($friendship['status'] === 'blocked') {
            $friendship_status = 'blocked';
            $can_send_request = false;
        }
    }
    
    // Get mutual friends count
    $mutual_sql = "SELECT COUNT(DISTINCT mutual_id) as count FROM (
        SELECT f2.friend_id as mutual_id FROM friendships f1
        INNER JOIN friendships f2 ON f1.friend_id = f2.user_id
        WHERE f1.user_id = ? AND f1.status = 'accepted' AND f2.friend_id != ?
        AND f2.status = 'accepted'
        AND f2.friend_id IN (SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'
                             UNION SELECT user_id FROM friendships WHERE friend_id = ? AND status = 'accepted')
    ) as mutuals";
    $mutual = db_fetch_single($mutual_sql, [$user_id, $user_id, $user['id'], $user['id']], 'iiii');
    
    $results[] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'friend_code' => $user['friend_code'],
        'avatar' => $user['avatar'],
        'is_online' => (bool)$user['is_online'],
        'last_seen' => $user['last_seen'] ? time_ago($user['last_seen']) : 'Never',
        'friendship_status' => $friendship_status,
        'friendship_id' => $friendship_id,
        'can_send_request' => $can_send_request,
        'mutual_friends' => (int)($mutual['count'] ?? 0)
    ];
}

send_success('Search results', ['users' => $results]);
