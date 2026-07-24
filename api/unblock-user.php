<?php
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
    log_activity($user_id, 'user_unblocked', ['unblocked_id' => $target_user_id]);
    send_success('User unblocked successfully');
} else {
    send_error('Failed to unblock user');
}
