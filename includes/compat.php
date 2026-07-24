<?php
/**
 * =====================================================
 * Compatibility Layer
 * ChatApp - Legacy Function Shims
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Compatibility shim for init_session()
 * Maps to session_initialize()
 */
if (!function_exists('init_session')) {
    function init_session() {
        session_initialize();
    }
}

/**
 * Compatibility shim for is_logged_in()
 * Maps to session_is_logged_in()
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return session_is_logged_in();
    }
}

/**
 * Compatibility shim for get_user_id()
 * Maps to session_get_user_id()
 */
if (!function_exists('get_user_id')) {
    function get_user_id() {
        return session_get_user_id();
    }
}

/**
 * Compatibility shim for verify_csrf_token()
 * Maps to session_validate_csrf()
 */
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token = null) {
        $token = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return session_validate_csrf($token);
    }
}

/**
 * Compatibility shim for send_json_response()
 * Note: Original had args swapped (status, data) vs (data, status)
 * This handles both calling conventions
 */
if (!function_exists('send_json_response_compat')) {
    function send_json_response($first, $second = []) {
        // Detect which convention is used
        if (is_int($first)) {
            // Called as send_json_response($status_code, $data)
            $status_code = $first;
            $data = $second;
        } else {
            // Called as send_json_response($data, $status_code)
            $data = $first;
            $status_code = $second ?: 200;
        }
        
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Fix check_rate_limit() to accept both 3 and 4 arguments
 */
if (function_exists('check_rate_limit')) {
    // Wrap existing function to handle wrong arg count
    $original_check_rate_limit = 'check_rate_limit';
}

// Override check_rate_limit to be flexible
function check_rate_limit($ip_or_action, $action_or_max = null, $max_or_timeframe = null, $timeframe = null) {
    // Detect calling convention
    if ($timeframe !== null) {
        // 4 args: check_rate_limit($ip, $action, $max, $timeframe)
        $ip = $ip_or_action;
        $action = $action_or_max;
        $max_attempts = $max_or_timeframe;
    } elseif ($max_or_timeframe !== null) {
        // 3 args: check_rate_limit($action_id, $max, $timeframe) - legacy API style
        $ip = get_client_ip();
        $action = $ip_or_action;
        $max_attempts = $action_or_max;
        $timeframe = $max_or_timeframe;
    } else {
        // 2 args: check_rate_limit($ip, $action)
        $ip = $ip_or_action;
        $action = $action_or_max;
        $max_attempts = 5;
        $timeframe = 900;
    }
    
    require_once __DIR__ . '/../config/database.php';
    
    $conn = db_connect();
    if (!$conn) return false;
    
    // Clean old entries
    $sql = "DELETE FROM rate_limits WHERE action_type = ? AND first_attempt_at < DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $action, $timeframe);
    mysqli_stmt_execute($stmt);
    
    // Check current attempts
    $sql = "SELECT attempts, first_attempt_at FROM rate_limits WHERE ip_address = ? AND action_type = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $record = mysqli_fetch_assoc($result);
    
    if (!$record) {
        $sql = "INSERT INTO rate_limits (ip_address, action_type, attempts) VALUES (?, ?, 1)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
        mysqli_stmt_execute($stmt);
        return true;
    }
    
    $first_attempt = strtotime($record['first_attempt_at']);
    if (time() - $first_attempt > $timeframe) {
        $sql = "UPDATE rate_limits SET attempts = 1, first_attempt_at = NOW() WHERE ip_address = ? AND action_type = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
        mysqli_stmt_execute($stmt);
        return true;
    }
    
    if ($record['attempts'] >= $max_attempts) {
        return false;
    }
    
    $sql = "UPDATE rate_limits SET attempts = attempts + 1 WHERE ip_address = ? AND action_type = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
    mysqli_stmt_execute($stmt);
    
    return true;
}

/**
 * Unified get_client_ip()
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Unified time_ago()
 */
if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        return time_elapsed_string($datetime);
    }
}

if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        
        return date('M d, Y', $time);
    }
}

/**
 * Unified format_date()
 */
if (!function_exists('format_date')) {
    function format_date($date, $format = 'M d, Y h:i A') {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
}

/**
 * Unified sanitize_input()
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Unified escape()
 */
if (!function_exists('escape')) {
    function escape($input) {
        return htmlspecialchars($input ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Unified format_file_size()
 */
if (!function_exists('format_file_size')) {
    function format_file_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

/**
 * Unified truncate_text()
 */
if (!function_exists('truncate_text')) {
    function truncate_text($text, $length = 50, $suffix = '...') {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . $suffix;
    }
}

/**
 * Fix log_activity() to accept string or array as 3rd arg
 */
if (!function_exists('log_activity')) {
    function log_activity($user_id, $action, $details = []) {
        require_once __DIR__ . '/../config/database.php';
        
        $conn = db_connect();
        if (!$conn) return;
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $details_json = is_array($details) ? json_encode($details) : (string)$details;
        
        $sql = "INSERT INTO activity_log (user_id, action, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'issss', $user_id, $action, $ip, $user_agent, $details_json);
        mysqli_stmt_execute($stmt);
    }
}

/**
 * Unified validate_email()
 */
if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Unified validate_username()
 */
if (!function_exists('validate_username')) {
    function validate_username($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username) === 1;
    }
}

/**
 * Unified validate_password_strength()
 */
if (!function_exists('validate_password_strength')) {
    function validate_password_strength($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password) === 1;
    }
}

/**
 * Unified hash_password()
 */
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

/**
 * Unified verify_password()
 */
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Unified generate_token()
 */
if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

/**
 * Unified get_base_url()
 */
if (!function_exists('get_base_url')) {
    function get_base_url() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        $path = preg_replace('#/(api|pages|includes|config|assets|admin)(/.*)?$#', '', $path);
        return $protocol . '://' . $host . $path;
    }
}

/**
 * Unified is_ajax_request()
 */
if (!function_exists('is_ajax_request')) {
    function is_ajax_request() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

/**
 * Unified redirect()
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

/**
 * Unified username_exists()
 */
if (!function_exists('username_exists')) {
    function username_exists($username, $exclude_id = 0) {
        require_once __DIR__ . '/../config/database.php';
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        if ($exclude_id > 0) {
            $sql .= " AND id != ?";
            $result = db_fetch_single($sql, [$username, $exclude_id], 'si');
        } else {
            $result = db_fetch_single($sql, [$username], 's');
        }
        return $result && $result['count'] > 0;
    }
}

/**
 * Unified email_exists()
 */
if (!function_exists('email_exists')) {
    function email_exists($email, $exclude_id = 0) {
        require_once __DIR__ . '/../config/database.php';
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        if ($exclude_id > 0) {
            $sql .= " AND id != ?";
            $result = db_fetch_single($sql, [$email, $exclude_id], 'si');
        } else {
            $result = db_fetch_single($sql, [$email], 's');
        }
        return $result && $result['count'] > 0;
    }
}

/**
 * Unified friend_code_exists()
 */
if (!function_exists('friend_code_exists')) {
    function friend_code_exists($code) {
        require_once __DIR__ . '/../config/database.php';
        $sql = "SELECT COUNT(*) as count FROM users WHERE friend_code = ?";
        $result = db_fetch_single($sql, [$code], 's');
        return $result && $result['count'] > 0;
    }
}

/**
 * Unified generate_friend_code()
 */
if (!function_exists('generate_friend_code')) {
    function generate_friend_code($length = 6) {
        $prefixes = ['CHT', 'MSG', 'USR', 'NET', 'PLY', 'FRN', 'BDY'];
        $prefix = $prefixes[array_rand($prefixes)];
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $prefix . '-' . $random;
    }
}

/**
 * Unified generate_unique_friend_code()
 */
if (!function_exists('generate_unique_friend_code')) {
    function generate_unique_friend_code() {
        do {
            $code = generate_friend_code();
        } while (friend_code_exists($code));
        return $code;
    }
}

/**
 * Unified register_user()
 */
if (!function_exists('register_user')) {
    function register_user($data) {
        require_once __DIR__ . '/../config/database.php';
        
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';
        
        if (empty($username)) return ['success' => false, 'message' => 'Username is required'];
        if (!validate_username($username)) return ['success' => false, 'message' => 'Username must be 3-50 characters (letters, numbers, underscores)'];
        if (username_exists($username)) return ['success' => false, 'message' => 'Username already taken'];
        
        if (empty($email)) return ['success' => false, 'message' => 'Email is required'];
        if (!validate_email($email)) return ['success' => false, 'message' => 'Invalid email format'];
        if (email_exists($email)) return ['success' => false, 'message' => 'Email already registered'];
        
        if (empty($password)) return ['success' => false, 'message' => 'Password is required'];
        if (!validate_password_strength($password)) return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and number'];
        if ($password !== $confirm_password) return ['success' => false, 'message' => 'Passwords do not match'];
        
        $friend_code = generate_unique_friend_code();
        $hashed_password = hash_password($password);
        
        $sql = "INSERT INTO users (username, email, password, friend_code, created_at) VALUES (?, ?, ?, ?, NOW())";
        $result = db_execute($sql, [$username, $email, $hashed_password, $friend_code], 'ssss');
        
        if ($result) {
            $user_id = db_insert_id();
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $user_id,
                'username' => $username,
                'email' => $email,
                'friend_code' => $friend_code
            ];
        }
        
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Unified login_user()
 */
if (!function_exists('login_user')) {
    function login_user($identifier, $password, $remember_me = false) {
        require_once __DIR__ . '/../config/database.php';
        
        $sql = "SELECT id, username, email, password, friend_code, avatar, bio, status 
                FROM users WHERE username = ? OR email = ? LIMIT 1";
        $user = db_fetch_single($sql, [$identifier, $identifier], 'ss');
        
        if (!$user) return ['success' => false, 'message' => 'Invalid credentials'];
        if ($user['status'] !== 'active') return ['success' => false, 'message' => 'Account is ' . $user['status']];
        if (!verify_password($password, $user['password'])) return ['success' => false, 'message' => 'Invalid credentials'];
        
        $sql = "UPDATE users SET last_seen = NOW(), is_online = 1 WHERE id = ?";
        db_execute($sql, [$user['id']], 'i');
        
        log_activity($user['id'], 'login', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'remember_me' => $remember_me
        ];
    }
}

/**
 * Unified get_rate_limit_remaining()
 */
if (!function_exists('get_rate_limit_remaining')) {
    function get_rate_limit_remaining($ip, $action, $max_attempts = 5) {
        require_once __DIR__ . '/../config/database.php';
        $sql = "SELECT attempts FROM rate_limits WHERE ip_address = ? AND action_type = ?";
        $record = db_fetch_single($sql, [$ip, $action], 'ss');
        if (!$record) return $max_attempts;
        return max(0, $max_attempts - $record['attempts']);
    }
}
