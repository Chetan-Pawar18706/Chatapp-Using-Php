<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Secret Folder API
 * ChatApp - Password Protected Sensitive Documents
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
$action = $_POST['action'] ?? '';

$user = db_fetch_single("SELECT secret_folder_password FROM users WHERE id = ?", [$user_id]);
$has_password = !empty($user['secret_folder_password']);

switch ($action) {
    case 'set_password':
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 4) {
            send_json_response(400, ['success' => false, 'message' => 'Password must be at least 4 characters']);
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        db_execute("UPDATE users SET secret_folder_password = ? WHERE id = ?", [$hashed, $user_id]);
        send_json_response(200, ['success' => true, 'message' => 'Password set successfully']);
        break;

    case 'change_password':
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        if (!$has_password) {
            send_json_response(400, ['success' => false, 'message' => 'No password set yet']);
        }
        if (!password_verify($old_password, $user['secret_folder_password'])) {
            send_json_response(403, ['success' => false, 'message' => 'Current password is incorrect']);
        }
        if (strlen($new_password) < 4) {
            send_json_response(400, ['success' => false, 'message' => 'Password must be at least 4 characters']);
        }
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        db_execute("UPDATE users SET secret_folder_password = ? WHERE id = ?", [$hashed, $user_id]);
        send_json_response(200, ['success' => true, 'message' => 'Password changed successfully']);
        break;

    case 'verify':
        $password = $_POST['password'] ?? '';
        if (!$has_password) {
            send_json_response(200, ['success' => true, 'message' => 'No password set', 'verified' => true]);
        }
        if (password_verify($password, $user['secret_folder_password'])) {
            send_json_response(200, ['success' => true, 'message' => 'Password verified', 'verified' => true]);
        } else {
            send_json_response(403, ['success' => false, 'message' => 'Incorrect password', 'verified' => false]);
        }
        break;

    case 'get_files':
        $password = $_POST['password'] ?? '';
        if ($has_password && !password_verify($password, $user['secret_folder_password'])) {
            send_json_response(403, ['success' => false, 'message' => 'Incorrect password']);
        }
        $files = db_fetch_all(
            "SELECT id, file_name, original_name, file_path, file_size, file_type, file_extension, category, is_secret, created_at FROM media WHERE user_id = ? AND is_secret = 1 ORDER BY created_at DESC",
            [$user_id]
        );
        if ($files) {
            foreach ($files as &$file) {
                $file['file_size_formatted'] = format_file_size($file['file_size']);
                $file['is_image'] = in_array($file['category'], ['images']);
                $file['is_video'] = in_array($file['category'], ['videos']);
            }
        }
        send_json_response(200, ['success' => true, 'files' => $files ?? []]);
        break;

    case 'has_password':
        send_json_response(200, ['success' => true, 'has_password' => $has_password]);
        break;

    case 'remove_password':
        if ($has_password && !password_verify($_POST['password'] ?? '', $user['secret_folder_password'])) {
            send_json_response(403, ['success' => false, 'message' => 'Incorrect password']);
        }
        db_execute("UPDATE users SET secret_folder_password = NULL WHERE id = ?", [$user_id]);
        send_json_response(200, ['success' => true, 'message' => 'Password removed']);
        break;

    default:
        send_json_response(400, ['success' => false, 'message' => 'Invalid action']);
}
