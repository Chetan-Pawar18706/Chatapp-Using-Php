<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Deactivate Account API
 * ChatApp - Account Deactivation
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, ['success' => false, 'message' => 'Method not allowed']);
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
}

$user_id = get_user_id();

// Check rate limit
if (!check_rate_limit('deactivate_' . $user_id, 1, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many attempts. Please wait.']);
}

// Get confirmation username
$confirm_username = trim($_POST['username'] ?? '');

// Get current username
$query = "SELECT username FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($confirm_username !== $user['username']) {
    send_json_response(400, ['success' => false, 'message' => 'Username does not match']);
}

// Deactivate account
$update_query = "UPDATE users SET 
    is_active = 0, 
    deactivated_at = NOW(),
    status = 'inactive',
    updated_at = NOW() 
    WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, 'i', $user_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Delete all sessions
    $session_query = "DELETE FROM user_sessions WHERE user_id = ?";
    $session_stmt = mysqli_prepare($conn, $session_query);
    mysqli_stmt_bind_param($session_stmt, 'i', $user_id);
    mysqli_stmt_execute($session_stmt);
    
    // Log activity
    log_activity($user_id, 'account_deactivated', 'Account deactivated by user');
    
    // Destroy session
    app_session_destroy();
    
    send_json_response(200, [
        'success' => true,
        'message' => 'Account deactivated successfully. You can reactivate within 30 days.'
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to deactivate account']);
}
