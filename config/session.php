<?php
/**
 * =====================================================
 * Session Configuration & Management
 * ChatApp - Secure Session Handling
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Session Configuration Constants
 */
define('REMEMBER_ME_LIFETIME', 2592000);  // 30 days

/**
 * Initialize Secure Session
 * Uses Security class for enhanced session handling
 * 
 * @return void
 */
function session_initialize() {
    // Don't restart if already active
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    // Use Security class if available, otherwise fall back to basic session
    if (class_exists('Security')) {
        $security = Security::getInstance();
        $security->initSecureSession();
        $security->setSessionFingerprint();
    } else {
        // Basic session configuration (fallback)
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', 86400);
        
        session_name('CHATAPP_SESSION');
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        session_start();
    }
    
    // Regenerate ID periodically for security
    if (!isset($_SESSION['_last_regenerate'])) {
        $_SESSION['_last_regenerate'] = time();
    } elseif (time() - $_SESSION['_last_regenerate'] > 300) {
        session_regenerate_id(true);
        $_SESSION['_last_regenerate'] = time();
    }
}

/**
 * Check if User is Logged In
 * 
 * @return bool True if logged in
 */
function session_is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get Current User ID
 * 
 * @return int|null User ID or null
 */
function session_get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get Current User Data
 * 
 * @return array|null User data or null
 */
function session_get_user_data() {
    return $_SESSION['user_data'] ?? null;
}

/**
 * Set User Session Data
 * 
 * @param array $user_data User data from database
 * @param bool $remember_me Whether to set remember me cookie
 * @return void
 */
function session_set_user($user_data, $remember_me = false) {
    // Set session variables
    $_SESSION['user_id'] = (int)$user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['friend_code'] = $user_data['friend_code'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['last_activity'] = time();
    
    // Store user data for quick access
    $_SESSION['user_data'] = [
        'id' => (int)$user_data['id'],
        'username' => $user_data['username'],
        'email' => $user_data['email'],
        'friend_code' => $user_data['friend_code'],
        'avatar' => $user_data['avatar'] ?? null,
        'bio' => $user_data['bio'] ?? null,
        'theme' => $user_data['theme'] ?? 'dark'
    ];
    
    // Set session fingerprint using Security class
    if (class_exists('Security')) {
        $security = Security::getInstance();
        $security->setSessionFingerprint();
    }
    
    // Handle Remember Me
    if ($remember_me) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + REMEMBER_ME_LIFETIME;
        
        // Store token in database
        require_once __DIR__ . '/../config/database.php';
        $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
        db_execute($sql, [$token, $user_data['id']], 'si');
        
        // Set cookie
        setcookie(
            'remember_me',
            $token,
            $expires,
            '/',
            '',
            false, // secure
            true   // httponly
        );
        
        $_SESSION['remember_token'] = $token;
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Validate Remember Me Token
 * 
 * @return array|false User data if valid, false otherwise
 */
function session_validate_remember_me() {
    if (!isset($_COOKIE['remember_me'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_me'];
    
    require_once __DIR__ . '/../config/database.php';
    
    // Find user with this token
    $sql = "SELECT id, username, email, friend_code, avatar, bio 
            FROM users 
            WHERE remember_token = ? 
            AND status = 'active' 
            LIMIT 1";
    $user = db_fetch_single($sql, [$token], 's');
    
    if ($user) {
        // Refresh the token
        $new_token = bin2hex(random_bytes(32));
        $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
        db_execute($sql, [$new_token, $user['id']], 'si');
        
        // Set new cookie
        $expires = time() + REMEMBER_ME_LIFETIME;
        setcookie('remember_me', $new_token, $expires, '/', '', false, true);
        
        return $user;
    }
    
    return false;
}

/**
 * Generate CSRF Token
 * Uses Security class if available
 * 
 * @return string CSRF token
 */
function session_generate_csrf() {
    if (!class_exists('Security')) {
        $cfgFile = dirname(__DIR__) . '/config/security.php';
        $secFile = dirname(__DIR__) . '/includes/security.php';
        if (file_exists($cfgFile)) {
            require_once $cfgFile;
        }
        if (file_exists($secFile)) {
            require_once $secFile;
        }
    }
    
    if (class_exists('Security')) {
        $security = Security::getInstance();
        return $security->getCSRFToken();
    }
    
    // Fallback to basic CSRF generation
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['_csrf_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['_csrf_time'] = time();
    } elseif (time() - $_SESSION['_csrf_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['_csrf_time'] = time();
    }
    
    $token = $_SESSION['csrf_token'];
    if (is_array($token)) {
        $token = $token['token'] ?? '';
    }
    return $token;
}

/**
 * Validate CSRF Token
 * Uses Security class if available
 * 
 * @param string $token Token to validate
 * @return bool True if valid
 */
function session_validate_csrf($token) {
    if (!class_exists('Security')) {
        $cfgFile = dirname(__DIR__) . '/config/security.php';
        $secFile = dirname(__DIR__) . '/includes/security.php';
        if (file_exists($cfgFile)) {
            require_once $cfgFile;
        }
        if (file_exists($secFile)) {
            require_once $secFile;
        }
    }
    
    if (class_exists('Security')) {
        $security = Security::getInstance();
        return $security->validateCSRFToken($token);
    }
    
    // Fallback to basic validation
    $stored = $_SESSION['csrf_token'] ?? null;
    if (empty($token) || empty($stored)) {
        return false;
    }
    
    if (is_array($stored)) {
        $stored = $stored['token'] ?? '';
    }
    
    if (time() - ($_SESSION['_csrf_time'] ?? 0) > 3600) {
        return false;
    }
    
    return hash_equals($stored, $token);
}

/**
 * Destroy User Session (Logout)
 * Uses Security class if available
 * 
 * @return void
 */
function app_session_destroy() {
    // Remove remember me token from database
    if (isset($_SESSION['user_id']) && isset($_SESSION['remember_token'])) {
        require_once __DIR__ . '/../config/database.php';
        $sql = "UPDATE users SET remember_token = NULL WHERE id = ?";
        db_execute($sql, [$_SESSION['user_id']], 'i');
    }
    
    // Use Security class if available for clean session destruction
    if (class_exists('Security')) {
        $security = Security::getInstance();
        $security->destroySession();
        return;
    }
    
    // Fallback to basic session destruction
    $_SESSION = [];
    
    // Delete remember me cookie
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
    }
    
    // Destroy session using PHP's built-in function
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Verify Session Security
 * Checks IP, User Agent, and session fingerprint
 * 
 * @return bool True if session is secure
 */
function session_verify_security() {
    if (!session_is_logged_in()) {
        return true; // Not logged in, no session to verify
    }
    
    // Use Security class if available for enhanced validation
    if (class_exists('Security')) {
        $security = Security::getInstance();
        
        // Validate session fingerprint
        if (!$security->validateSessionFingerprint()) {
            session_destroy();
            return false;
        }
        
        // Update last activity for timeout check
        $_SESSION['last_activity'] = time();
        
        // Regenerate session if needed
        $security->regenerateSession();
        
        return true;
    }
    
    // Fallback to basic security check
    // Check IP address
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $current_ip) {
        session_destroy();
        return false;
    }
    
    // Check User Agent
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $current_ua) {
        session_destroy();
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > 86400) {
            session_destroy();
            return false;
        }
    }
    
    return true;
}

/**
 * Get Session Flash Messages
 * 
 * @param string $key Message key
 * @return string|null Flash message or null
 */
function session_get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

/**
 * Set Session Flash Message
 * 
 * @param string $key Message key
 * @param string $message Message content
 * @return void
 */
function session_set_flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}
