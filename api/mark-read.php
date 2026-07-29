<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Mark Messages Read
 * ChatApp - POST /api/mark-read.php
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

$sender_id = (int)($input['sender_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$sender_id) {
    send_error('Sender ID is required');
}

// Mark all messages from this sender as read
$sql = "UPDATE messages SET is_read = 1, seen_at = NOW() 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
$result = db_execute($sql, [$sender_id, $user_id], 'ii');

$affected = db_affected_rows();

send_success('Messages marked as read', [
    'marked_count' => $affected
]);
