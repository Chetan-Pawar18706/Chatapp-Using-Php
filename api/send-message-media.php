<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Send Message with Media API
 * ChatApp - Send Message with File Attachment
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/media.php';
require_once dirname(__DIR__) . '/includes/media_helpers.php';

init_session();

if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, ['success' => false, 'message' => 'Method not allowed']);
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
}

$user_id = get_user_id();
$receiver_id = intval($_POST['receiver_id'] ?? 0) ?: null;
$group_id = intval($_POST['group_id'] ?? 0) ?: null;
$content = trim($_POST['message'] ?? '');
$media_id = intval($_POST['media_id'] ?? 0);
$reply_to_id = intval($_POST['reply_to'] ?? 0);

error_log("send-media: uid=$user_id, receiver=$receiver_id, group=$group_id, content=" . strlen($content) . ", media_id=$media_id, files=" . (isset($_FILES['file']) ? 'yes' : 'no'));

if (!$receiver_id && !$group_id) {
    error_log("send-media: FAIL - Invalid receiver or group");
    send_json_response(400, ['success' => false, 'message' => 'Invalid receiver or group']);
}

if (!check_rate_limit('send_message_' . $user_id, 60, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many messages. Please wait.']);
}

if (isset($_FILES['file'])) {
    error_log("send-media-file-info: error={$_FILES['file']['error']}, name={$_FILES['file']['name']}, size={$_FILES['file']['size']}, tmp={$_FILES['file']['tmp_name']}");
}

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $category = get_file_category($extension);
    $maxSize = SIZE_LIMITS[$category] ?? MAX_FILE_SIZE;

    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? @finfo_file($finfo, $file['tmp_name']) : 'application/octet-stream';
    if ($finfo) finfo_close($finfo);

    if ($mimeType === 'application/octet-stream' || $mimeType === 'application/x-empty') {
        $fallbackMimes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheet.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
        ];
        $mimeType = $fallbackMimes[$extension] ?? $mimeType;
    }

    error_log("send-media-file: ext=$extension, cat=$category, mime=$mimeType, size={$file['size']}, maxSize=$maxSize, ext_ok=" . (is_extension_allowed($extension) ? 'yes' : 'no') . ", mime_ok=" . (is_mime_allowed($mimeType, $category) ? 'yes' : 'no'));

    if (!is_extension_allowed($extension)) {
        send_json_response(400, ['success' => false, 'message' => 'File type not allowed: ' . $extension]);
    }

    if ($category && $file['size'] > $maxSize) {
        send_json_response(400, ['success' => false, 'message' => 'File too large. Maximum size: ' . format_file_size($maxSize)]);
    }

    if (!is_mime_allowed($mimeType, $category)) {
        send_json_response(400, ['success' => false, 'message' => 'Invalid file type: ' . $mimeType]);
    }

    $uniqueFilename = generate_unique_filename($file['name']);
    $storagePath = get_storage_path($category);

    if (!is_dir($storagePath)) {
        mkdir($storagePath, 0755, true);
    }

    $filePath = $storagePath . '/' . $uniqueFilename;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        send_json_response(500, ['success' => false, 'message' => 'Failed to save file']);
    }

    chmod($filePath, 0644);

    $thumbnailPath = null;
    if ($category === 'images') {
        $thumbnailPath = THUMBNAIL_PATH . '/thumb_' . $uniqueFilename;
        create_thumbnail($filePath, $thumbnailPath);
    }

    $relativePath = 'storage/uploads/' . $category . '/' . $uniqueFilename;
    $thumbnailRelativePath = $thumbnailPath ? 'storage/thumbnails/thumb_' . $uniqueFilename : null;

    $query = "INSERT INTO media (user_id, file_name, original_name, file_path, thumbnail_path, file_size, file_type, file_extension, category, receiver_id, group_id, created_at)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $insertResult = db_execute($query, [
        $user_id, $uniqueFilename, $file['name'], $relativePath,
        $thumbnailRelativePath, (int)$file['size'], $mimeType, $extension,
        $category, $receiver_id, $group_id
    ], 'isssssssssi');

    if ($insertResult) {
        $media_id = mysqli_insert_id(db_connect());
    } else {
        if (file_exists($filePath)) unlink($filePath);
        if ($thumbnailPath && file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        send_json_response(500, ['success' => false, 'message' => 'Failed to save file record']);
    }
}

if (empty($content) && $media_id <= 0) {
    error_log("send-media: FAIL - Message or media required");
    send_json_response(400, ['success' => false, 'message' => 'Message or media required']);
}

if ($reply_to_id > 0) {
    $reply_query = "SELECT id FROM messages WHERE id = ? AND group_id IS NOT NULL";
    $reply_params = [$reply_to_id];
    $reply_types = 'i';

    if ($group_id <= 0 && $receiver_id > 0) {
        $reply_query = "SELECT id FROM messages WHERE id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))";
        $reply_params = [$reply_to_id, $user_id, $receiver_id, $receiver_id, $user_id];
        $reply_types = 'iiiii';
    }

    $reply_result = db_fetch_single($reply_query, $reply_params, $reply_types);
    if (!$reply_result) {
        send_json_response(400, ['success' => false, 'message' => 'Invalid reply target']);
    }
}

if ($group_id > 0) {
    $member = db_fetch_single("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?", [$group_id, $user_id], 'ii');
    if (!$member) {
        send_json_response(403, ['success' => false, 'message' => 'Not a member of this group']);
    }

    $query = "INSERT INTO messages (sender_id, group_id, content, media_id, reply_to_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $result = db_execute($query, [$user_id, $group_id, $content, $media_id ?: null, $reply_to_id ?: null], 'iisii');
} else {
    $friend = db_fetch_single(
        "SELECT id FROM friendships WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 'accepted'",
        [$user_id, $receiver_id, $receiver_id, $user_id], 'iiii'
    );
    $conv = db_fetch_single(
        "SELECT id FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) LIMIT 1",
        [$user_id, $receiver_id, $receiver_id, $user_id], 'iiii'
    );

    if (!$friend && !$conv) {
        send_json_response(403, ['success' => false, 'message' => 'Cannot send message to this user']);
    }

    $query = "INSERT INTO messages (sender_id, receiver_id, content, media_id, reply_to_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $result = db_execute($query, [$user_id, $receiver_id, $content, $media_id ?: null, $reply_to_id ?: null], 'iisii');
}

if ($result) {
    $message_id = db_fetch_single("SELECT LAST_INSERT_ID() as id", [], '');
    $msg_id = $message_id['id'] ?? mysqli_insert_id($conn);

    $media_data = null;
    if ($media_id > 0) {
        $media_data = db_fetch_single("SELECT * FROM media WHERE id = ?", [$media_id], 'i');
        if ($media_data) {
            $media_data['file_size_formatted'] = format_file_size($media_data['file_size']);
            $media_data['icon'] = get_file_icon($media_data['category'], $media_data['file_extension']);
            $media_data['is_image'] = $media_data['category'] === 'images';
            $media_data['is_video'] = $media_data['category'] === 'videos';
        }
    }

    $sender = db_fetch_single("SELECT username, avatar FROM users WHERE id = ?", [$user_id], 'i');

    send_json_response(200, [
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => [
            'id' => (int)$msg_id,
            'sender_id' => $user_id,
            'sender_name' => $sender['username'] ?? '',
            'sender_avatar' => $sender['avatar'] ?? null,
            'receiver_id' => $receiver_id,
            'group_id' => $group_id,
            'content' => $content,
            'media_id' => $media_id,
            'media' => $media_data,
            'reply_to_id' => $reply_to_id,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to send message']);
}
