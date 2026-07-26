<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Add Friend by Code
 * ChatApp - POST /api/add-friend.php
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

$friend_code = trim($input['friend_code'] ?? '');
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

if (empty($friend_code)) {
    send_error('Friend code is required');
}

// Validate friend code format
if (!preg_match('/^[A-Z]{3}-[A-Z0-9]{6}$/', $friend_code)) {
    send_error('Invalid friend code format');
}

$user_id = session_get_user_id();

// Find user by friend code
$user_sql = "SELECT id, username FROM users WHERE friend_code = ? AND status = 'active' LIMIT 1";
$target_user = db_fetch_single($user_sql, [$friend_code], 's');

if (!$target_user) {
    send_error('User not found with this friend code');
}

if ($target_user['id'] == $user_id) {
    send_error('You cannot add yourself as a friend');
}

// Check if friendship already exists
$check_sql = "SELECT id, status FROM friendships 
              WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?) 
              LIMIT 1";
$existing = db_fetch_single($check_sql, [$user_id, $target_user['id'], $target_user['id'], $user_id], 'iiii');

if ($existing) {
    if ($existing['status'] === 'accepted') {
        send_error('You are already friends with this user');
    } elseif ($existing['status'] === 'pending') {
        send_error('Friend request already pending');
    }
}

// Create friend request
$sql = "INSERT INTO friendships (user_id, friend_id, status, created_at) VALUES (?, ?, 'pending', NOW())";
$result = db_execute($sql, [$user_id, $target_user['id']], 'ii');

if ($result) {
    // Create notification for target user
    $sender_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
    $sender = db_fetch_single($sender_sql, [$user_id], 'i');
    
    $notif_sql = "INSERT INTO notifications (user_id, type, title, message, from_user_id, is_read, created_at)
                  VALUES (?, 'friend_request', 'Friend Request', ?, ?, 0, NOW())";
    $notif_msg = ($sender['username'] ?? 'Someone') . ' sent you a friend request';
    db_execute($notif_sql, [$target_user['id'], $notif_msg, $user_id], 'issi');
    
    log_activity($user_id, 'friend_request_sent', ['friend_id' => $target_user['id']]);
    send_success('Friend request sent to ' . $target_user['username']);
} else {
    send_error('Failed to send friend request');
}
