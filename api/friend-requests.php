<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Friend Requests
 * ChatApp - GET /api/friend-requests.php
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

// Get pending friend requests
$sql = "SELECT 
            f.id as friendship_id,
            f.user_id as requester_id,
            u.username,
            u.email,
            u.avatar,
            u.friend_code,
            f.created_at as request_date
        FROM friendships f
        INNER JOIN users u ON f.user_id = u.id
        WHERE f.friend_id = ? AND f.status = 'pending'
        ORDER BY f.created_at DESC
        LIMIT 20";

$requests = db_fetch_all($sql, [$user_id], 'i');

$formatted_requests = [];
foreach ($requests as $request) {
    $formatted_requests[] = [
        'friendship_id' => (int)$request['friendship_id'],
        'user_id' => (int)$request['requester_id'],
        'username' => $request['username'],
        'email' => $request['email'],
        'avatar' => $request['avatar'],
        'friend_code' => $request['friend_code'],
        'request_date' => time_ago($request['request_date'])
    ];
}

send_success('Friend requests loaded', ['requests' => $formatted_requests]);
