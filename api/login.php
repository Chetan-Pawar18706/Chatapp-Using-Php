<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: User Login
 * ChatApp - POST /api/login.php
 * =====================================================
 */

// Define app is running
define('APP_RUNNING', true);

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize session
session_initialize();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

// Get client IP for rate limiting
$client_ip = get_client_ip();

// Check rate limit using Security class
if (class_exists('Security')) {
    $security = Security::getInstance();
    if (!$security->checkRateLimit($client_ip, 'login', 5)) {
        send_error('Too many login attempts. Please try again later.', 429);
    }
} else {
    // Fallback to basic rate limiting
    if (!check_rate_limit($client_ip, 'login', 5, 900)) {
        send_error('Too many login attempts. Please try again in 15 minutes.', 429);
    }
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to POST data if not JSON
if (!$input) {
    $input = $_POST;
}

// Extract input
$identifier = trim($input['username'] ?? ''); // Can be username or email
$password = $input['password'] ?? '';
$remember_me = !empty($input['remember_me']);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

// Check if login is locked (after getting identifier)
if (class_exists('Security') && !empty($identifier)) {
    $security = Security::getInstance();
    if ($security->isLoginLocked($identifier)) {
        $remaining = $security->getLockoutRemaining($identifier);
        send_error('Account temporarily locked. Try again in ' . ceil($remaining / 60) . ' minutes.', 429);
    }
}

// Validate CSRF token
if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

// Basic validation
if (empty($identifier) || empty($password)) {
    send_error('Username/Email and password are required');
}

// Attempt login
$result = login_user($identifier, $password, $remember_me);

if ($result['success']) {
    // Clear rate limit counter on successful login
    if (class_exists('Security')) {
        $security = Security::getInstance();
        $security->clearRateLimit($client_ip, 'login');
        $security->recordLoginAttempt($identifier, true);
    }
    
    // Set session data
    session_set_user($result['user'], $remember_me);
    
    // Log success
    log_activity($result['user']['id'], 'login_success', [
        'ip' => $client_ip
    ]);
    
    send_success($result['message'], [
        'user_id' => $result['user']['id'],
        'username' => $result['user']['username'],
        'email' => $result['user']['email'],
        'friend_code' => $result['user']['friend_code'],
        'avatar' => $result['user']['avatar'],
        'redirect' => get_base_url() . '/pages/dashboard.php'
    ]);
} else {
    // Record failed attempt using Security class
    if (class_exists('Security')) {
        $security = Security::getInstance();
        $security->recordLoginAttempt($identifier, false);
    }
    
    // Log failed attempt
    log_activity(null, 'login_failed', [
        'identifier' => $identifier,
        'ip' => $client_ip,
        'reason' => $result['message']
    ]);
    
    send_error($result['message']);
}
