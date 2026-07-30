<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Delete Story
 * ChatApp - POST /api/delete-story.php
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

// Check if story belongs to current user
$story = db_fetch_single(
    "SELECT id, media_path FROM stories WHERE id = ? AND user_id = ?",
    [$story_id, $user_id],
    'ii'
);

if (!$story) {
    send_error('Story not found or unauthorized');
}

// Delete media file if exists
if ($story['media_path']) {
    $file_path = dirname(__DIR__) . '/' . $story['media_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Delete story (views will cascade delete)
$sql = "DELETE FROM stories WHERE id = ?";
db_execute($sql, [$story_id], 'i');

send_success('Story deleted');
