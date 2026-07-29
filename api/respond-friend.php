<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Accept/Reject Friend Request
 * ChatApp - POST /api/respond-friend.php
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
$action = $input['action'] ?? '';
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

if (!$friendship_id || !in_array($action, ['accept', 'reject'])) {
    send_error('Invalid parameters');
}

$user_id = session_get_user_id();

// Verify the friendship request exists and belongs to current user
$check_sql = "SELECT id, user_id, friend_id, status FROM friendships 
              WHERE id = ? AND friend_id = ? AND status = 'pending' LIMIT 1";
$friendship = db_fetch_single($check_sql, [$friendship_id, $user_id], 'ii');

if (!$friendship) {
    send_error('Friend request not found');
}

if ($action === 'accept') {
    // Update status to accepted
    $sql = "UPDATE friendships SET status = 'accepted', updated_at = NOW() WHERE id = ?";
    $result = db_execute($sql, [$friendship_id], 'i');
    
    if ($result) {
        log_activity($user_id, 'friend_accepted', ['friend_id' => $friendship['user_id']]);
        send_success('Friend request accepted');
    } else {
        send_error('Failed to accept request');
    }
} else {
    // Delete the request
    $sql = "DELETE FROM friendships WHERE id = ?";
    $result = db_execute($sql, [$friendship_id], 'i');
    
    if ($result) {
        log_activity($user_id, 'friend_rejected', ['friend_id' => $friendship['user_id']]);
        send_success('Friend request rejected');
    } else {
        send_error('Failed to reject request');
    }
}
