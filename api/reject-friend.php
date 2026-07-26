<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Reject Friend Request
 * ChatApp - POST /api/reject-friend.php
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

// Delete the request
$delete_sql = "DELETE FROM friendships WHERE id = ?";
$result = db_execute($delete_sql, [$friendship_id], 'i');

if ($result) {
    log_activity($user_id, 'friend_rejected', [
        'friendship_id' => $friendship_id,
        'friend_id' => $friendship['user_id']
    ]);
    
    send_success('Friend request rejected');
} else {
    send_error('Failed to reject friend request');
}
