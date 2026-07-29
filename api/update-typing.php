<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Update Typing Status
 * ChatApp - POST /api/update-typing.php
 * Update user's typing status for real-time indicators
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

$chat_with_user_id = (int)($input['user_id'] ?? 0);
$is_typing = !empty($input['is_typing']) ? 1 : 0;
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

// Check if typing_status table exists
$table_exists = false;
try {
    $check = db_fetch_single("SHOW TABLES LIKE 'typing_status'", [], '');
    $table_exists = (bool)$check;
} catch (Exception $e) {
    $table_exists = false;
}

if (!$table_exists) {
    send_success('Typing status updated', [
        'is_typing' => $is_typing,
        'chat_with_user_id' => $chat_with_user_id
    ]);
}

// Update or insert typing status
$sql = "INSERT INTO typing_status (user_id, chat_with_user_id, is_typing, last_typing_at) 
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        is_typing = VALUES(is_typing),
        last_typing_at = NOW()";
$result = db_execute($sql, [$user_id, $chat_with_user_id, $is_typing], 'iii');

if ($result) {
    send_success('Typing status updated', [
        'is_typing' => $is_typing,
        'chat_with_user_id' => $chat_with_user_id
    ]);
} else {
    send_error('Failed to update typing status');
}
