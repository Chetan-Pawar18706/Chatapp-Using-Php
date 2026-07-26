<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Get Typing Status
 * ChatApp - GET /api/get-typing.php
 * Get typing status for a specific user
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$user_id = session_get_user_id();
$other_user_id = (int)($_GET['user_id'] ?? 0);

if (!$other_user_id) {
    send_error('User ID is required');
}

// Get other user's typing status to current user
$sql = "SELECT is_typing, last_typing_at 
        FROM typing_status 
        WHERE user_id = ? AND chat_with_user_id = ?
        LIMIT 1";
$typing = db_fetch_single($sql, [$other_user_id, $user_id], 'ii');

$is_typing = false;
if ($typing && $typing['is_typing']) {
    // Check if typing status is recent (within 10 seconds)
    $typing_time = strtotime($typing['last_typing_at']);
    if (time() - $typing_time < 10) {
        $is_typing = true;
    }
}

send_success('Typing status retrieved', [
    'user_id' => $other_user_id,
    'is_typing' => $is_typing
]);
