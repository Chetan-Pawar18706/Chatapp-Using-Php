<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Toggle Secret Folder API
 * ChatApp - Move files in/out of Secret Folder
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/compat.php';

session_initialize();

if (!session_verify_security() || !session_is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, ['success' => false, 'message' => 'Method not allowed']);
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
}

$user_id = session_get_user_id();
$media_id = intval($_POST['id'] ?? 0);
$password = $_POST['password'] ?? '';

if ($media_id <= 0) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid media ID']);
}

$user = db_fetch_single("SELECT secret_folder_password FROM users WHERE id = ?", [$user_id]);

if (!empty($user['secret_folder_password']) && !password_verify($password, $user['secret_folder_password'])) {
    send_json_response(403, ['success' => false, 'message' => 'Incorrect secret folder password']);
}

$media = db_fetch_single("SELECT * FROM media WHERE id = ? AND user_id = ?", [$media_id, $user_id]);

if (!$media) {
    send_json_response(404, ['success' => false, 'message' => 'File not found']);
}

$new_state = $media['is_secret'] ? 0 : 1;

$update = db_execute("UPDATE media SET is_secret = ? WHERE id = ?", [$new_state, $media_id], 'ii');

if ($update) {
    send_json_response(200, [
        'success' => true,
        'message' => $new_state ? 'File moved to secret folder' : 'File restored from secret folder',
        'is_secret' => $new_state
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to update file']);
}
