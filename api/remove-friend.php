<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Remove Friend
 * ChatApp - POST /api/remove-friend.php
 * Remove an existing friend
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

$friend_id = (int)($input['friend_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

if (!$friend_id) {
    send_error('Friend ID is required');
}

$user_id = session_get_user_id();

// Cannot remove yourself
if ($friend_id === $user_id) {
    send_error('Invalid operation');
}

// Verify friendship exists
$sql = "SELECT id FROM friendships 
        WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
        AND status = 'accepted' LIMIT 1";
$friendship = db_fetch_single($sql, [$user_id, $friend_id, $friend_id, $user_id], 'iiii');

if (!$friendship) {
    send_error('Friendship not found');
}

// Delete the friendship
$delete_sql = "DELETE FROM friendships 
               WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
               AND status = 'accepted'";
$result = db_execute($delete_sql, [$user_id, $friend_id, $friend_id, $user_id], 'iiii');

if ($result) {
    // Get friend username for response
    $user_sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
    $user = db_fetch_single($user_sql, [$friend_id], 'i');
    
    log_activity($user_id, 'friend_removed', ['friend_id' => $friend_id]);
    
    send_success('Removed ' . ($user['username'] ?? 'User') . ' from friends', [
        'friend_id' => $friend_id
    ]);
} else {
    send_error('Failed to remove friend');
}
