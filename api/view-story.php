<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: View Story
 * ChatApp - POST /api/view-story.php
 * Mark a story as viewed
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
$story_id = (int)($input['story_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

$user_id = session_get_user_id();

if (!$story_id) {
    send_error('Story ID is required');
}

// Check if story exists and is not expired
$story = db_fetch_single(
    "SELECT id, user_id FROM stories WHERE id = ? AND expires_at > NOW()",
    [$story_id],
    'i'
);

if (!$story) {
    send_error('Story not found or expired');
}

// Don't mark own story as viewed
if ((int)$story['user_id'] === $user_id) {
    send_success('Own story - no view recorded');
}

// Insert view (ignore if already viewed)
$sql = "INSERT IGNORE INTO story_views (story_id, user_id) VALUES (?, ?)";
db_execute($sql, [$story_id, $user_id], 'ii');

send_success('Story viewed');
