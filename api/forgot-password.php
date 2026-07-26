<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Forgot Password
 * ChatApp - POST /api/forgot-password.php
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
if (!check_rate_limit($client_ip, 'forgot_password', 3, 3600)) {
    send_error('Too many password reset attempts. Please try again later.', 429);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to POST data if not JSON
if (!$input) {
    $input = $_POST;
}

// Extract input
$email = trim($input['email'] ?? '');
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

// Validate CSRF token
if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
    send_error('Invalid security token', 403);
}

// Validate email
if (empty($email)) {
    send_error('Email is required');
}

if (!validate_email($email)) {
    send_error('Invalid email format');
}

// Process password reset request
$result = request_password_reset($email);

if ($result['success']) {
    // In production, don't send the token in response
    // It would be sent via email
    $data = [
        'message' => $result['message']
    ];
    
    // Dev mode only - include token for testing
    if (isset($result['reset_token'])) {
        $data['reset_token'] = $result['reset_token'];
        $data['reset_url'] = get_base_url() . '/pages/reset-password.php?token=' . $result['reset_token'];
    }
    
    send_success($result['message'], $data);
} else {
    send_error($result['message']);
}
