<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Block User
 * ChatApp - POST /api/block-user.php
 * Block a user
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

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

if (!$target_user_id) {
    send_error('User ID is required');
}

$user_id = session_get_user_id();

// Cannot block yourself
if ($target_user_id === $user_id) {
    send_error('Invalid operation');
}

// Check if already blocked
$block_check_sql = "SELECT id FROM block_list WHERE user_id = ? AND blocked_user_id = ? LIMIT 1";
$existing = db_fetch_single($block_check_sql, [$user_id, $target_user_id], 'ii');

if ($existing) {
    send_error('User already blocked');
}

// Block the user
$sql = "INSERT INTO block_list (user_id, blocked_user_id, created_at) VALUES (?, ?, NOW())";
$result = db_execute($sql, [$user_id, $target_user_id], 'ii');

if ($result) {
    // Remove any existing friendship
    $remove_sql = "DELETE FROM friendships 
                   WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))";
    db_execute($remove_sql, [$user_id, $target_user_id, $target_user_id, $user_id], 'iiii');
    
    log_activity($user_id, 'user_blocked', ['blocked_id' => $target_user_id]);
    
    send_success('User blocked successfully');
} else {
    send_error('Failed to block user');
}
