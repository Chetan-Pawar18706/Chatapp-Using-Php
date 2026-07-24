<?php
/**
 * =====================================================
 * API: Accept Friend Request
 * ChatApp - POST /api/accept-friend.php
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

$friendship_id = (int)($input['friendship_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

if (!$friendship_id) {
    send_error('Friendship ID is required');
}

$user_id = session_get_user_id();

// Verify the friendship request exists and belongs to current user
$sql = "SELECT id, user_id, friend_id FROM friendships 
        WHERE id = ? AND friend_id = ? AND status = 'pending' LIMIT 1";
$friendship = db_fetch_single($sql, [$friendship_id, $user_id], 'ii');

if (!$friendship) {
    send_error('Friend request not found');
}

// Update status to accepted
$update_sql = "UPDATE friendships SET status = 'accepted', updated_at = NOW() WHERE id = ?";
$result = db_execute($update_sql, [$friendship_id], 'i');

if ($result) {
    // Get requester info for response
    $user_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
    $user = db_fetch_single($user_sql, [$friendship['user_id']], 'i');
    
    // Create notification for the original requester
    require_once __DIR__ . '/../includes/notification_helpers.php';
    create_notification(
        $friendship['user_id'],
        $user_id,
        'friend_accept',
        'Friend Request Accepted',
        null,
        ['friend_id' => $user_id]
    );
    
    log_activity($user_id, 'friend_accepted', [
        'friendship_id' => $friendship_id,
        'friend_id' => $friendship['user_id']
    ]);
    
    send_success('You are now friends with ' . ($user['username'] ?? 'User'), [
        'friendship_id' => $friendship_id,
        'friend_id' => (int)$friendship['user_id'],
        'friend_username' => $user['username'] ?? 'User'
    ]);
} else {
    send_error('Failed to accept friend request');
}
