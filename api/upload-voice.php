<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Upload Voice Message
 * ChatApp - POST /api/upload-voice.php
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

$user_id = session_get_user_id();

if (empty($_FILES['audio']['name'])) {
    send_error('Audio file is required');
}

$file = $_FILES['audio'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
$duration = (float)($_POST['duration'] ?? 0);
$csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!session_validate_csrf($csrf_token)) {
    send_error('Invalid security token', 403);
}

if (!$receiver_id && !$group_id) {
    send_error('Receiver or group is required');
}

// Validate file type
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['webm', 'ogg', 'mp3', 'wav', 'm4a'];
if (!in_array($ext, $allowed)) {
    send_error('Invalid audio format. Allowed: webm, ogg, mp3, wav, m4a');
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    send_error('Audio file must be less than 5MB');
}

// Generate unique filename
$filename = 'voice_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$upload_dir = dirname(__DIR__) . '/storage/uploads/voice/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$filepath = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    send_error('Failed to upload audio file');
}

$relative_path = 'storage/uploads/voice/' . $filename;

// Insert message first
$content = '🎤 Voice message';
$sql = "INSERT INTO messages (sender_id, receiver_id, group_id, content, message_type, auto_delete, created_at)
        VALUES (?, ?, ?, ?, 'voice', '12hours', NOW())";
$result = db_execute($sql, [$user_id, $receiver_id ?: null, $group_id, $content], 'iisss');

if ($result) {
    $message_id = mysqli_insert_id($conn);
    
    // Insert voice message details
    $voice_sql = "INSERT INTO voice_messages (message_id, user_id, file_path, duration, file_size)
                  VALUES (?, ?, ?, ?, ?)";
    db_execute($voice_sql, [$message_id, $user_id, $relative_path, $duration, $file['size']], 'iisii');
    
    send_success('Voice message sent', [
        'message_id' => (int)$message_id,
        'file_path' => $relative_path,
        'duration' => $duration
    ]);
} else {
    send_error('Failed to send voice message');
}
