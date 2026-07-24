<?php
/**
 * =====================================================
 * API: Send Friend Request
 * ChatApp - POST /api/send-friend-request.php
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

$target_user_id = (int)($input['user_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

// Check rate limit
$client_ip = get_client_ip();
if (!check_rate_limit($client_ip, 'friend_request', 10, 3600)) {
    send_error('Too many friend requests. Please try again later.', 429);
}

if (!$target_user_id) {
    send_error('User ID is required');
}

$user_id = session_get_user_id();

// Cannot add yourself
if ($target_user_id === $user_id) {
    send_error('You cannot add yourself as a friend');
}

// Check if target user exists
$target_sql = "SELECT id, username, status FROM users WHERE id = ? AND status = 'active' LIMIT 1";
$target = db_fetch_single($target_sql, [$target_user_id], 'i');

if (!$target) {
    send_error('User not found');
}

// Check if blocked
$block_sql = "SELECT id FROM block_list 
              WHERE (user_id = ? AND blocked_user_id = ?) 
              OR (user_id = ? AND blocked_user_id = ?) 
              LIMIT 1";
$blocked = db_fetch_single($block_sql, [$user_id, $target_user_id, $target_user_id, $user_id], 'iiii');

if ($blocked) {
    send_error('Unable to send friend request');
}

// Check existing friendship
$existing_sql = "SELECT id, status, user_id FROM friendships 
                 WHERE (user_id = ? AND friend_id = ?) 
                 OR (user_id = ? AND friend_id = ?)
                 LIMIT 1";
$existing = db_fetch_single($existing_sql, [$user_id, $target_user_id, $target_user_id, $user_id], 'iiii');

if ($existing) {
    if ($existing['status'] === 'accepted') {
        send_error('You are already friends');
    } elseif ($existing['status'] === 'pending') {
        if ((int)$existing['user_id'] === $user_id) {
            send_error('Friend request already sent');
        } else {
            send_error('This user already sent you a request. Check your requests.');
        }
    } elseif ($existing['status'] === 'blocked') {
        send_error('Unable to send friend request');
    }
}

// Create friend request
$sql = "INSERT INTO friendships (user_id, friend_id, status, created_at) VALUES (?, ?, 'pending', NOW())";
$result = db_execute($sql, [$user_id, $target_user_id], 'ii');

if ($result) {
    $friendship_id = db_insert_id();
    
    // Create notification for target user
    require_once __DIR__ . '/../includes/notification_helpers.php';
    create_notification(
        $target_user_id,
        $user_id,
        'friend_request',
        'New Friend Request',
        null,
        ['friendship_id' => $friendship_id]
    );
    
    log_activity($user_id, 'friend_request_sent', ['target_id' => $target_user_id]);
    send_success('Friend request sent to ' . $target['username'], [
        'friendship_id' => $friendship_id,
        'target_username' => $target['username']
    ]);
} else {
    send_error('Failed to send friend request');
}
