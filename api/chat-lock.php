<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Chat Lock API
 * ChatApp - Password Protect Individual Chats
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

$user_id = session_get_user_id();
$action = $_POST['action'] ?? '';
$chat_type = $_POST['chat_type'] ?? 'chat';
$target_id = intval($_POST['target_id'] ?? 0);

if ($target_id <= 0) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid target ID']);
}

switch ($action) {
    case 'set':
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 4) {
            send_json_response(400, ['success' => false, 'message' => 'Password must be at least 4 characters']);
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        db_execute(
            "INSERT INTO chat_locks (user_id, chat_type, target_id, password_hash) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)",
            [$user_id, $chat_type, $target_id, $hashed]
        );
        send_json_response(200, ['success' => true, 'message' => 'Chat locked']);
        break;

    case 'verify':
        $password = $_POST['password'] ?? '';
        $lock = db_fetch_single(
            "SELECT password_hash FROM chat_locks WHERE user_id = ? AND chat_type = ? AND target_id = ?",
            [$user_id, $chat_type, $target_id]
        );
        if (!$lock) {
            send_json_response(200, ['success' => true, 'verified' => true, 'message' => 'No lock set']);
        }
        if (password_verify($password, $lock['password_hash'])) {
            send_json_response(200, ['success' => true, 'verified' => true, 'message' => 'Password verified']);
        } else {
            send_json_response(403, ['success' => false, 'verified' => false, 'message' => 'Incorrect password']);
        }
        break;

    case 'remove':
        $password = $_POST['password'] ?? '';
        $lock = db_fetch_single(
            "SELECT password_hash FROM chat_locks WHERE user_id = ? AND chat_type = ? AND target_id = ?",
            [$user_id, $chat_type, $target_id]
        );
        if ($lock && !password_verify($password, $lock['password_hash'])) {
            send_json_response(403, ['success' => false, 'message' => 'Incorrect password']);
        }
        db_execute(
            "DELETE FROM chat_locks WHERE user_id = ? AND chat_type = ? AND target_id = ?",
            [$user_id, $chat_type, $target_id]
        );
        send_json_response(200, ['success' => true, 'message' => 'Chat unlocked']);
        break;

    case 'check':
        $lock = db_fetch_single(
            "SELECT id FROM chat_locks WHERE user_id = ? AND chat_type = ? AND target_id = ?",
            [$user_id, $chat_type, $target_id]
        );
        send_json_response(200, ['success' => true, 'locked' => !empty($lock)]);
        break;

    case 'get_all':
        $locks = db_fetch_all(
            "SELECT chat_type, target_id FROM chat_locks WHERE user_id = ?",
            [$user_id]
        );
        $locked = [];
        if ($locks) {
            foreach ($locks as $l) {
                $key = $l['chat_type'] . '_' . $l['target_id'];
                $locked[$key] = true;
            }
        }
        send_json_response(200, ['success' => true, 'locked' => $locked]);
        break;

    default:
        send_json_response(400, ['success' => false, 'message' => 'Invalid action']);
}
