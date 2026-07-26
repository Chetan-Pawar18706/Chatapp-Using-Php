<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Global Search API
 * ChatApp - Search Users, Friends, Groups, Messages
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/security.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/search_helpers.php';

session_initialize();

if (!session_is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

// Get parameters
$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$limit = min(intval($_GET['limit'] ?? 20), 50);

// Validate query
if (strlen($query) < 2) {
    send_json_response(400, ['success' => false, 'message' => 'Search query must be at least 2 characters']);
}

$user_id = session_get_user_id();

// Build filters
$filters = [
    'limit' => $limit,
    'types' => $type === 'all' ? ['users', 'friends', 'groups', 'messages'] : [$type]
];

// Perform search
$results = global_search($query, $user_id, $filters);

// Get total results count
$total_count = count($results['users']) + 
               count($results['friends']) + 
               count($results['groups']) + 
               count($results['messages']);

send_json_response(200, [
    'success' => true,
    'data' => [
        'query' => $query,
        'type' => $type,
        'results' => $results,
        'total_count' => $total_count,
        'counts' => [
            'users' => count($results['users']),
            'friends' => count($results['friends']),
            'groups' => count($results['groups']),
            'messages' => count($results['messages'])
        ]
    ]
]);
