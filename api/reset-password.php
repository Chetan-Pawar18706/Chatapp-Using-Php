<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Reset Password
 * ChatApp - POST /api/reset-password.php
 * =====================================================
 */

// Define app is running
define('APP_RUNNING', true);

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize session
session_initialize();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

// Check rate limit
$client_ip = get_client_ip();
if (!check_rate_limit($client_ip, 'reset_password', 3, 3600)) {
    send_error('Too many password reset attempts. Please try again later.', 429);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to POST data if not JSON
if (!$input) {
    $input = $_POST;
}

// Extract input
$token = trim($input['token'] ?? '');
$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

// Validate CSRF token
if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

// Validate inputs
if (empty($token)) {
    send_error('Reset token is required');
}

if (empty($password) || empty($confirm_password)) {
    send_error('All fields are required');
}

// Process password reset
$result = reset_password($token, $password, $confirm_password);

if ($result['success']) {
    send_success($result['message'], [
        'redirect' => get_base_url() . '/login.php'
    ]);
} else {
    send_error($result['message']);
}
