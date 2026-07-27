<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Change Password API
 * ChatApp - Secure Password Update
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/compat.php';

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
if (!check_rate_limit('password_change_' . $user_id, 3, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many attempts. Please wait 1 minute.']);
}

// Get input
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
$errors = [];

if (empty($current_password)) {
    $errors[] = 'Current password is required';
}

if (empty($new_password)) {
    $errors[] = 'New password is required';
} elseif (strlen($new_password) < 8) {
    $errors[] = 'New password must be at least 8 characters';
} elseif (!preg_match('/[A-Z]/', $new_password)) {
    $errors[] = 'New password must contain at least one uppercase letter';
} elseif (!preg_match('/[a-z]/', $new_password)) {
    $errors[] = 'New password must contain at least one lowercase letter';
} elseif (!preg_match('/[0-9]/', $new_password)) {
    $errors[] = 'New password must contain at least one number';
}

if ($new_password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

if ($current_password === $new_password) {
    $errors[] = 'New password must be different from current password';
}

// Return errors if any
if (!empty($errors)) {
    send_json_response(400, ['success' => false, 'message' => implode(', ', $errors)]);
}

// Get current password hash
$query = "SELECT password FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    send_json_response(400, ['success' => false, 'message' => 'Current password is incorrect']);
}

// Hash new password
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$update_query = "UPDATE users SET password = ?, last_password_change = NOW(), updated_at = NOW() WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, 'si', $new_password_hash, $user_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Invalidate all other sessions (optional security measure)
    $session_query = "DELETE FROM user_sessions WHERE user_id = ? AND session_token != ?";
    $session_stmt = mysqli_prepare($conn, $session_query);
    $session_token = $_SESSION['session_token'] ?? '';
    mysqli_stmt_bind_param($session_stmt, 'ss', $user_id, $session_token);
    mysqli_stmt_execute($session_stmt);
    
    // Log activity
    log_activity($user_id, 'password_change', 'Password changed successfully');
    
    send_json_response(200, [
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to update password']);
}
