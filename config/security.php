<?php
/**
 * =====================================================
 * Security Configuration
 * ChatApp - Security Settings & Constants
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * ==================== CSRF PROTECTION ====================
 */
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour

/**
 * ==================== RATE LIMITING ====================
 */
define('RATE_LIMIT_WINDOW', 900); // 15 minutes in seconds
define('RATE_LIMIT_MAX_ATTEMPTS', [
    'login' => 10,
    'register' => 5,
    'password_reset' => 5,
    'friend_request' => 30,
    'message' => 120,
    'upload' => 30,
    'search' => 50,
    'api_general' => 200
]);

/**
 * ==================== LOGIN ATTEMPTS ====================
 */
define('MAX_LOGIN_ATTEMPTS', 10);
define('LOGIN_LOCKOUT_DURATION', 300); // 5 minutes
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes

/**
 * ==================== PASSWORD SECURITY ====================
 */
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 128);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('PASSWORD_BCRYPT_COST', 12);
define('PASSWORD_HISTORY_COUNT', 5); // Remember last 5 passwords

/**
 * ==================== SESSION SECURITY ====================
 */
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_REGENERATE_INTERVAL', 300); // Regenerate ID every 5 minutes
define('SESSION_MAX_CONCURRENT', 3); // Max concurrent sessions
define('SESSION_FINGERPRINT_ENABLED', false);

/**
 * ==================== FILE UPLOAD SECURITY ====================
 */
define('UPLOAD_MAX_SIZE', 20 * 1024 * 1024); // 20MB
define('UPLOAD_ALLOWED_TYPES', [
    'images' => [
        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'max_size' => 10 * 1024 * 1024 // 10MB
    ],
    'videos' => [
        'extensions' => ['mp4', 'webm', 'ogg', 'mov'],
        'mimes' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
        'max_size' => 50 * 1024 * 1024 // 50MB
    ],
    'documents' => [
        'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        'mimes' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain'
        ],
        'max_size' => 20 * 1024 * 1024 // 20MB
    ],
    'archives' => [
        'extensions' => ['zip', 'rar', '7z'],
        'mimes' => ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'],
        'max_size' => 20 * 1024 * 1024 // 20MB
    ]
]);

/**
 * ==================== XSS PROTECTION ====================
 */
define('XSS_SANITIZE_INPUT', true);
define('XSS_ESCAPE_OUTPUT', true);
define('XSS_CSP_ENABLED', true);

/**
 * ==================== SQL INJECTION ====================
 */
define('SQL_PREPARED_STATEMENTS', true);
define('SQL_ESCAPE_INPUT', true);

/**
 * ==================== SECURITY HEADERS ====================
 */
define('HEADERS_CONTENT_TYPE_OPTIONS', 'nosniff');
define('HEADERS_X_FRAME_OPTIONS', 'SAMEORIGIN');
define('HEADERS_X_XSS_PROTECTION', '1; mode=block');
define('HEADERS_STRICT_TRANSPORT_SECURITY', 'max-age=31536000; includeSubDomains');
define('HEADERS_REFERRER_POLICY', 'strict-origin-when-cross-origin');
define('HEADERS_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()');

/**
 * ==================== INPUT VALIDATION ====================
 */
define('VALID_USERNAME_PATTERN', '/^[a-zA-Z0-9_]{3,30}$/');
define('VALID_EMAIL_PATTERN', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/');
define('VALID_FRIEND_CODE_PATTERN', '/^[A-Z]{3}-[A-Z0-9]{6}$/');
define('MAX_INPUT_LENGTH', [
    'username' => 30,
    'email' => 100,
    'password' => 128,
    'message' => 5000,
    'bio' => 150,
    'about' => 1000,
    'group_name' => 100,
    'group_description' => 500
]);

/**
 * ==================== BLOCKED CONTENT ====================
 */
define('BLOCKED_WORDS', [
    // Add blocked words as needed
]);

define('SUSPICIOUS_PATTERNS', [
    '/<script\b[^>]*>(.*?)<\/script>/is', // Script tags
    '/javascript:/i', // JavaScript URLs
    '/on\w+\s*=/i', // Event handlers
    '/data:text\/html/i', // Data URLs
    '/vbscript:/i', // VBScript
    '/expression\(/i', // CSS expressions
    '/union\s+select/i', // SQL injection
    '/or\s+1\s*=\s*1/i', // SQL injection
    '/;\s*drop\s+table/i', // SQL injection
    '/\.\.\//', // Path traversal
]);
