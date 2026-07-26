<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Unblock User
 * ChatApp - POST /api/unblock-user.php
 * Unblock a user
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

if (!$target_user_id) {
    send_error('User ID is required');
}

$user_id = session_get_user_id();

// Unblock the user
$sql = "DELETE FROM block_list WHERE user_id = ? AND blocked_user_id = ?";
$result = db_execute($sql, [$user_id, $target_user_id], 'ii');

if ($result) {
    // Check if either user still has the other blocked
    $reverse_block = db_fetch_single(
        "SELECT id FROM block_list WHERE user_id = ? AND blocked_user_id = ? LIMIT 1",
        [$target_user_id, $user_id], 'ii'
    );

    // Restore friendship only if neither side has a block
    if (!$reverse_block) {
        // Check if friendship already exists
        $existing = db_fetch_single(
            "SELECT id, status FROM friendships 
             WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?) LIMIT 1",
            [$user_id, $target_user_id, $target_user_id, $user_id], 'iiii'
        );

        if (!$existing) {
            // Restore friendship as accepted
            db_execute(
                "INSERT INTO friendships (user_id, friend_id, status, created_at, updated_at) 
                 VALUES (?, ?, 'accepted', NOW(), NOW())",
                [$user_id, $target_user_id], 'ii'
            );
        } elseif ($existing['status'] !== 'accepted') {
            // If friendship exists but is pending, auto-accept it
            db_execute(
                "UPDATE friendships SET status = 'accepted', updated_at = NOW() 
                 WHERE id = ?",
                [$existing['id']], 'i'
            );
        }
    }

    log_activity($user_id, 'user_unblocked', ['unblocked_id' => $target_user_id]);
    send_success('User unblocked successfully');
} else {
    send_error('Failed to unblock user');
}
