<?php
/**
 * =====================================================
 * Media Configuration
 * ChatApp - File Upload Settings
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Upload Settings
 */
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20 MB

/**
 * Allowed File Extensions by Category
 */
define('ALLOWED_EXTENSIONS', [
    'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    'videos' => ['mp4', 'webm', 'ogg', 'mov'],
    'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
    'archives' => ['zip', 'rar', '7z']
]);

/**
 * MIME Types by Category
 */
define('ALLOWED_MIMES', [
    'images' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    ],
    'videos' => [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime'
    ],
    'documents' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain'
    ],
    'archives' => [
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed'
    ]
]);

/**
 * Storage Paths
 */
define('STORAGE_PATH', dirname(__DIR__) . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
define('IMAGE_PATH', UPLOAD_PATH . '/images');
define('VIDEO_PATH', UPLOAD_PATH . '/videos');
define('DOCUMENT_PATH', UPLOAD_PATH . '/documents');
define('THUMBNAIL_PATH', STORAGE_PATH . '/thumbnails');
define('TEMP_PATH', STORAGE_PATH . '/temp');

/**
 * File Size Limits by Category (in bytes)
 */
define('SIZE_LIMITS', [
    'images' => 10 * 1024 * 1024,    // 10 MB
    'videos' => 20 * 1024 * 1024,    // 20 MB
    'documents' => 20 * 1024 * 1024, // 20 MB
    'archives' => 20 * 1024 * 1024   // 20 MB
]);

/**
 * Thumbnail Settings
 */
define('THUMBNAIL_WIDTH', 200);
define('THUMBNAIL_HEIGHT', 200);

/**
 * Get File Category from Extension
 * 
 * @param string $extension File extension
 * @return string|null Category name or null
 */
function get_file_category($extension) {
    $extension = strtolower($extension);
    
    foreach (ALLOWED_EXTENSIONS as $category => $extensions) {
        if (in_array($extension, $extensions)) {
            return $category;
        }
    }
    
    return null;
}

/**
 * Check if Extension is Allowed
 * 
 * @param string $extension File extension
 * @return bool True if allowed
 */
function is_extension_allowed($extension) {
    return get_file_category($extension) !== null;
}

/**
 * Check if MIME Type is Allowed
 * 
 * @param string $mimeType MIME type
 * @param string $category File category
 * @return bool True if allowed
 */
function is_mime_allowed($mimeType, $category) {
    if (!isset(ALLOWED_MIMES[$category])) {
        return false;
    }
    return in_array($mimeType, ALLOWED_MIMES[$category]);
}

/**
 * Get Storage Path for Category
 * 
 * @param string $category File category
 * @return string Storage path
 */
function get_storage_path($category) {
    $paths = [
        'images' => IMAGE_PATH,
        'videos' => VIDEO_PATH,
        'documents' => DOCUMENT_PATH,
        'archives' => DOCUMENT_PATH
    ];
    
    return $paths[$category] ?? DOCUMENT_PATH;
}

/**
 * Generate Unique File Name
 * 
 * @param string $originalName Original file name
 * @return string Unique file name
 */
function generate_unique_filename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $uniqueName = bin2hex(random_bytes(16)) . '.' . $extension;
    return $uniqueName;
}

/**
 * Format File Size
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted size
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get File Icon Based on Type
 * 
 * @param string $category File category
 * @param string $extension File extension
 * @return string Font Awesome icon class
 */
function get_file_icon($category, $extension = '') {
    $icons = [
        'images' => 'fa-file-image',
        'videos' => 'fa-file-video',
        'documents' => [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'ppt' => 'fa-file-powerpoint',
            'pptx' => 'fa-file-powerpoint',
            'txt' => 'fa-file-alt',
            'default' => 'fa-file'
        ],
        'archives' => 'fa-file-archive'
    ];
    
    if ($category === 'documents' && isset($icons['documents'][$extension])) {
        return $icons['documents'][$extension];
    }
    
    return $icons[$category] ?? 'fa-file';
}

/**
 * Check if File is Image
 * 
 * @param string $category File category
 * @return bool True if image
 */
function is_image($category) {
    return $category === 'images';
}

/**
 * Check if File is Video
 * 
 * @param string $category File category
 * @return bool True if video
 */
function is_video($category) {
    return $category === 'videos';
}

/**
 * Create Thumbnail for Image
 * 
 * @param string $sourcePath Source image path
 * @param string $destPath Destination thumbnail path
 * @return bool True on success
 */
function create_thumbnail($sourcePath, $destPath) {
    // Check if GD library is available
    if (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng')) {
        return false;
    }
    
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo['mime'];
    
    // Create source image based on type
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Calculate thumbnail dimensions
    $thumbWidth = THUMBNAIL_WIDTH;
    $thumbHeight = THUMBNAIL_HEIGHT;
    
    $aspectRatio = $width / $height;
    
    if ($aspectRatio > 1) {
        $thumbHeight = $thumbWidth / $aspectRatio;
    } else {
        $thumbWidth = $thumbHeight * $aspectRatio;
    }
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
    
    // Preserve transparency for PNG
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
    }
    
    imagecopyresampled(
        $thumbnail, $source,
        0, 0, 0, 0,
        $thumbWidth, $thumbHeight,
        $width, $height
    );
    
    // Save thumbnail
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($thumbnail, $destPath, 85);
            break;
        case 'image/png':
            $result = imagepng($thumbnail, $destPath, 6);
            break;
        case 'image/gif':
            $result = imagegif($thumbnail, $destPath);
            break;
        case 'image/webp':
            $result = imagewebp($thumbnail, $destPath, 85);
            break;
    }
    
    // Clean up
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $result;
}
