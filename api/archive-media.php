<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Media Archive API
 * ChatApp - Move files to/from Archive
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/compat.php';
require_once dirname(__DIR__) . '/config/media.php';

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

$media_id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'archive';

if ($media_id <= 0) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid media ID']);
}

$query = "SELECT * FROM media WHERE id = ?";
$media = db_fetch_single($query, [$media_id]);

if (!$media) {
    send_json_response(404, ['success' => false, 'message' => 'File not found']);
}

$user_id = session_get_user_id();

if ($media['user_id'] != $user_id) {
    send_json_response(403, ['success' => false, 'message' => 'Permission denied']);
}

if ($action === 'restore') {
    $extension = $media['file_extension'];
    $original_category = get_file_category($extension);
    if (!$original_category || $original_category === 'archives') {
        $original_category = 'documents';
    }
    $new_category = $original_category;
} else {
    $new_category = 'archives';
}

$update = db_execute(
    "UPDATE media SET category = ? WHERE id = ?",
    [$new_category, $media_id],
    'si'
);

if ($update) {
    send_json_response(200, [
        'success' => true,
        'message' => $action === 'restore' ? 'File restored' : 'File moved to archives',
        'category' => $new_category
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to update file']);
}
