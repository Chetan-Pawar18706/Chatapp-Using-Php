<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: User Logout
 * ChatApp - POST /api/logout.php
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

// Accept POST and GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (!session_validate_csrf($csrf_token) && !is_ajax_request()) {
        send_error('Invalid security token', 403);
    }
}

// Perform logout
$result = logout_user();

if ($result['success']) {
    // If direct browser request (not AJAX), redirect
    if (!is_ajax_request()) {
        header('Location: ../login.php');
        exit;
    }
    send_success($result['message'], [
        'redirect' => get_base_url() . '/login.php'
    ]);
} else {
    if (!is_ajax_request()) {
        header('Location: ../login.php');
        exit;
    }
    send_error($result['message']);
}
