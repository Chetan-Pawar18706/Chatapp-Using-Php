<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Media Upload API
 * ChatApp - Secure File Upload Handler
 * =====================================================
 */

// Set APP_RUNNING before any includes
define('APP_RUNNING', true);

// Set content type header
header('Content-Type: application/json');

// Include required files
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/compat.php';
require_once dirname(__DIR__) . '/config/media.php';

// Start session
init_session();

// Check if user is logged in
if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, ['success' => false, 'message' => 'Method not allowed']);
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
}

// Check rate limit
$user_id = get_user_id();
if (!check_rate_limit('media_upload_' . $user_id, 30, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many uploads. Please wait.']);
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
    ];
    
    $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $message = $errorMessages[$errorCode] ?? 'Upload error occurred';
    
    send_json_response(400, ['success' => false, 'message' => $message]);
}

$file = $_FILES['file'];

// Get receiver ID or group ID (optional for gallery uploads)
$receiver_id = !empty($_POST['receiver_id']) ? intval($_POST['receiver_id']) : null;
$group_id = !empty($_POST['group_id']) ? intval($_POST['group_id']) : null;

// Get file extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Check if extension is allowed
if (!is_extension_allowed($extension)) {
    send_json_response(400, [
        'success' => false,
        'message' => 'File type not allowed. Allowed: JPG, PNG, GIF, WEBP, MP4, PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP'
    ]);
}

// Get file category
$category = get_file_category($extension);
if (!$category) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid file category']);
}

error_log("Upload Debug: extension=$extension, category=$category, category_type=" . gettype($category));
error_log("Upload Debug: category_hex=" . bin2hex($category) . ", receiver_id=" . var_export($receiver_id, true) . ", group_id=" . var_export($group_id, true) . ", file_size=" . $file['size']);

// Check file size
$maxSize = SIZE_LIMITS[$category] ?? MAX_FILE_SIZE;
if ($file['size'] > $maxSize) {
    send_json_response(400, [
        'success' => false,
        'message' => 'File too large. Maximum size: ' . format_file_size($maxSize)
    ]);
}

// Validate MIME type
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

error_log("upload-media: ext=$extension, mime=$mimeType, allowed=" . (is_mime_allowed($mimeType, $category) ? 'yes' : 'no'));

if (!is_mime_allowed($mimeType, $category)) {
    send_json_response(400, [
        'success' => false,
        'message' => 'Invalid file type detected'
    ]);
}

// Additional security check - verify file content matches extension
if ($category === 'images') {
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        send_json_response(400, ['success' => false, 'message' => 'Invalid image file']);
    }
}

// Generate unique filename
$uniqueFilename = generate_unique_filename($file['name']);

// Get storage path
$storagePath = get_storage_path($category);

// Create directory if it doesn't exist
if (!is_dir($storagePath)) {
    mkdir($storagePath, 0755, true);
}

// Ensure thumbnail directory exists
if (!is_dir(THUMBNAIL_PATH)) {
    mkdir(THUMBNAIL_PATH, 0755, true);
}

$filePath = $storagePath . '/' . $uniqueFilename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    send_json_response(500, ['success' => false, 'message' => 'Failed to save file']);
}

// Set file permissions
chmod($filePath, 0644);

// Create thumbnail for images
$thumbnailPath = null;
if ($category === 'images') {
    $thumbnailPath = THUMBNAIL_PATH . '/thumb_' . $uniqueFilename;
    create_thumbnail($filePath, $thumbnailPath);
}

// Store file info in database
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
    
    log_activity($user_id, 'media_upload', "Uploaded file: " . $file['name']);
    
    send_json_response(200, [
        'success' => true,
        'message' => 'File uploaded successfully',
        'data' => [
            'id' => $media_id,
            'file_name' => $uniqueFilename,
            'original_name' => $file['name'],
            'file_path' => $relativePath,
            'thumbnail_path' => $thumbnailRelativePath,
            'file_size' => $file['size'],
            'file_size_formatted' => format_file_size($file['size']),
            'file_type' => $mimeType,
            'file_extension' => $extension,
            'category' => $category,
            'icon' => get_file_icon($category, $extension),
            'is_image' => $category === 'images',
            'is_video' => $category === 'videos'
        ]
    ]);
} else {
    $dbError = mysqli_error(db_connect());
    error_log("Upload DB Error: " . $dbError);
    if (file_exists($filePath)) unlink($filePath);
    if ($thumbnailPath && file_exists($thumbnailPath)) {
        unlink($thumbnailPath);
    }
    
    send_json_response(500, ['success' => false, 'message' => 'Failed to save file record']);
}
