<?php
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

// Get receiver ID or group ID
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$group_id = intval($_POST['group_id'] ?? 0);

if ($receiver_id <= 0 && $group_id <= 0) {
    send_json_response(400, ['success' => false, 'message' => 'Invalid receiver or group']);
}

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

// Check file size
$maxSize = SIZE_LIMITS[$category] ?? MAX_FILE_SIZE;
if ($file['size'] > $maxSize) {
    send_json_response(400, [
        'success' => false,
        'message' => 'File too large. Maximum size: ' . format_file_size($maxSize)
    ]);
}

// Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

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

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'issssssiiis',
    $user_id,
    $uniqueFilename,
    $file['name'],
    $relativePath,
    $thumbnailRelativePath,
    $file['size'],
    $mimeType,
    $extension,
    $category,
    $receiver_id,
    $group_id
);

if (mysqli_stmt_execute($stmt)) {
    $media_id = mysqli_insert_id($conn);
    
    // Log activity
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
    // Delete file if database insert fails
    unlink($filePath);
    if ($thumbnailPath && file_exists($thumbnailPath)) {
        unlink($thumbnailPath);
    }
    
    send_json_response(500, ['success' => false, 'message' => 'Failed to save file record']);
}
