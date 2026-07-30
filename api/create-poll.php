<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Create Poll
 * ChatApp - POST /api/create-poll.php
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
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();
$question = trim($input['question'] ?? '');
$options = $input['options'] ?? [];
$is_multiple = (int)($input['is_multiple'] ?? 0);
$is_anonymous = (int)($input['is_anonymous'] ?? 0);
$receiver_id = !empty($input['receiver_id']) ? (int)$input['receiver_id'] : null;
$group_id = !empty($input['group_id']) ? (int)$input['group_id'] : null;
$expires_hours = (int)($input['expires_hours'] ?? 0);

if (empty($question)) {
    send_error('Question is required');
}

if (count($options) < 2) {
    send_error('At least 2 options are required');
}

if (count($options) > 10) {
    send_error('Maximum 10 options allowed');
}

$expires_at = null;
if ($expires_hours > 0) {
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_hours} hours"));
}

// Insert poll
$sql = "INSERT INTO polls (user_id, question, is_multiple, is_anonymous, receiver_id, group_id, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$result = db_execute($sql, [$user_id, $question, $is_multiple, $is_anonymous, $receiver_id, $group_id, $expires_at], 'isiiiss');

if ($result) {
    $poll_id = mysqli_insert_id($conn);
    
    // Insert options
    $opt_sql = "INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)";
    foreach ($options as $option) {
        $opt_text = trim($option);
        if (!empty($opt_text)) {
            db_execute($opt_sql, [$poll_id, $opt_text], 'is');
        }
    }
    
    send_success('Poll created', ['poll_id' => (int)$poll_id]);
} else {
    send_error('Failed to create poll');
}
