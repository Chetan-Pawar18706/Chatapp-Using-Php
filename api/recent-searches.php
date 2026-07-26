<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Recent Searches API
 * ChatApp - Manage Search History
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/search_helpers.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

$user_id = get_user_id();

// Handle GET - fetch recent searches
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? null;
    $limit = min(intval($_GET['limit'] ?? 10), 30);
    
    $searches = get_recent_searches($user_id, $type, $limit);
    
    send_json_response(200, [
        'success' => true,
        'data' => [
            'searches' => $searches
        ]
    ]);
}

// Handle POST - save recent search
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
    }
    
    $search_type = $_POST['search_type'] ?? 'user';
    $search_query = trim($_POST['search_query'] ?? '');
    $result_id = intval($_POST['result_id'] ?? 0) ?: null;
    $result_name = trim($_POST['result_name'] ?? '') ?: null;
    $result_data = $_POST['result_data'] ?? null;
    
    if (empty($search_query)) {
        send_json_response(400, ['success' => false, 'message' => 'Search query is required']);
    }
    
    $search_id = save_recent_search(
        $user_id,
        $search_type,
        $search_query,
        $result_id,
        $result_name,
        is_string($result_data) ? json_decode($result_data, true) : $result_data
    );
    
    if ($search_id) {
        send_json_response(200, [
            'success' => true,
            'message' => 'Search saved',
            'data' => ['id' => $search_id]
        ]);
    } else {
        send_json_response(200, [
            'success' => true,
            'message' => 'Search already exists'
        ]);
    }
}

// Handle DELETE - delete recent searches
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Verify CSRF token
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
    }
    
    $action = $_GET['action'] ?? 'delete';
    
    if ($action === 'clear') {
        $type = $_GET['type'] ?? null;
        
        if (clear_recent_searches($user_id, $type)) {
            send_json_response(200, [
                'success' => true,
                'message' => 'Recent searches cleared'
            ]);
        } else {
            send_json_response(500, ['success' => false, 'message' => 'Failed to clear searches']);
        }
    } else {
        $search_id = intval($_GET['id'] ?? 0);
        
        if ($search_id <= 0) {
            send_json_response(400, ['success' => false, 'message' => 'Invalid search ID']);
        }
        
        if (delete_recent_search($search_id, $user_id)) {
            send_json_response(200, [
                'success' => true,
                'message' => 'Search deleted'
            ]);
        } else {
            send_json_response(500, ['success' => false, 'message' => 'Failed to delete search']);
        }
    }
}
