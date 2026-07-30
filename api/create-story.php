<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * API: Create Story
 * ChatApp - POST /api/create-story.php
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/media.php';

session_initialize();

if (!session_verify_security() || !session_is_logged_in()) {
    send_error('Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

$user_id = session_get_user_id();

// Handle multipart form data (image/video upload)
$content = null;
$media_type = 'text';
$media_path = null;
$bg_color = '#6366f1';
$text_color = '#ffffff';
$font_size = 18;

if (!empty($_FILES['media']['name'])) {
    // Media upload
    $file = $_FILES['media'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $allowed_image = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_video = ['mp4', 'webm', 'ogg', 'mov'];
    
    if (in_array($ext, $allowed_image)) {
        $media_type = 'image';
    } elseif (in_array($ext, $allowed_video)) {
        $media_type = 'video';
    } else {
        send_error('Invalid file type. Allowed: jpg, png, gif, webp, mp4, webm');
    }
    
    // Validate file size (max 10MB for stories)
    if ($file['size'] > 10 * 1024 * 1024) {
        send_error('File size must be less than 10MB');
    }
    
    // Generate unique filename
    $filename = 'story_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $upload_dir = dirname(__DIR__) . '/storage/uploads/stories/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $media_path = 'storage/uploads/stories/' . $filename;
    } else {
        send_error('Failed to upload file');
    }
} else {
    // Text story
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $content = trim($input['content'] ?? '');
    $bg_color = $input['bg_color'] ?? '#6366f1';
    $text_color = $input['text_color'] ?? '#ffffff';
    $font_size = (int)($input['font_size'] ?? 18);
    
    if (empty($content)) {
        send_error('Story content is required');
    }
}

// Stories expire after 24 hours
$expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Insert story
$sql = "INSERT INTO stories (user_id, content, media_type, media_path, bg_color, text_color, font_size, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$result = db_execute($sql, [$user_id, $content, $media_type, $media_path, $bg_color, $text_color, $font_size, $expires_at], 'issssssi');

if ($result) {
    $story_id = mysqli_insert_id($conn);
    send_success('Story created', [
        'story_id' => (int)$story_id,
        'media_type' => $media_type,
        'media_path' => $media_path
    ]);
} else {
    send_error('Failed to create story');
}
