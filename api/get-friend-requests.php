<?php
/**
 * =====================================================
 * API: Get Friend Requests
 * ChatApp - GET /api/get-friend-requests.php
 * Get sent and received friend requests
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
$type = $_GET['type'] ?? 'received'; // received, sent

if ($type === 'received') {
    // Get requests received by current user
    $sql = "SELECT 
                f.id as friendship_id,
                f.user_id,
                f.created_at as request_date,
                u.username,
                u.email,
                u.friend_code,
                u.avatar,
                u.is_online,
                u.last_seen
            FROM friendships f
            INNER JOIN users u ON f.user_id = u.id
            WHERE f.friend_id = ? AND f.status = 'pending'
            AND u.status = 'active'
            ORDER BY f.created_at DESC";
    $requests = db_fetch_all($sql, [$user_id], 'i');
} else {
    // Get requests sent by current user
    $sql = "SELECT 
                f.id as friendship_id,
                f.friend_id as user_id,
                f.created_at as request_date,
                u.username,
                u.email,
                u.friend_code,
                u.avatar,
                u.is_online,
                u.last_seen
            FROM friendships f
            INNER JOIN users u ON f.friend_id = u.id
            WHERE f.user_id = ? AND f.status = 'pending'
            AND u.status = 'active'
            ORDER BY f.created_at DESC";
    $requests = db_fetch_all($sql, [$user_id], 'i');
}

$formatted_requests = [];
foreach ($requests as $request) {
    // Get mutual friends count
    $mutual_sql = "SELECT COUNT(DISTINCT CASE 
                    WHEN f1.user_id = ? THEN f1.friend_id 
                    ELSE f1.user_id END) as count
                   FROM friendships f1
                   INNER JOIN friendships f2 ON (
                       (f2.user_id = ? AND f2.friend_id = f1.friend_id AND f2.status = 'accepted')
                       OR (f2.user_id = f1.friend_id AND f2.friend_id = ? AND f2.status = 'accepted')
                   )
                   WHERE (f1.user_id = ? OR f1.friend_id = ?) 
                   AND f1.status = 'accepted'
                   AND f1.friend_id != ?
                   AND f1.user_id != ?";
    $mutual = db_fetch_single($mutual_sql, [
        $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id
    ], 'iiiiiii');
    
    $formatted_requests[] = [
        'friendship_id' => (int)$request['friendship_id'],
        'user_id' => (int)$request['user_id'],
        'username' => $request['username'],
        'email' => $request['email'],
        'friend_code' => $request['friend_code'],
        'avatar' => $request['avatar'],
        'is_online' => (bool)$request['is_online'],
        'last_seen' => $request['last_seen'] ? time_ago($request['last_seen']) : 'Never',
        'request_date' => time_ago($request['request_date']),
        'mutual_friends' => (int)($mutual['count'] ?? 0)
    ];
}

send_success('Friend requests loaded', [
    'requests' => $formatted_requests,
    'total' => count($formatted_requests)
]);
