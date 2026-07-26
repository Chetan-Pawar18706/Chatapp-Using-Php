<?php
/**
 * =====================================================
 * Admin Authentication
 * ChatApp - Admin Login/Logout Functions
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Initialize Admin Session
 */
function admin_session_init() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', ADMIN_SESSION_LIFETIME);
    
    session_name(ADMIN_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => ADMIN_SESSION_LIFETIME,
        'path' => '/admin',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

/**
 * Check if Admin is Logged In
 */
function admin_is_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Get Current Admin ID
 */
function admin_get_id() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get Current Admin Data
 */
function admin_get_data() {
    return $_SESSION['admin_data'] ?? null;
}

/**
 * Admin Login
 */
function admin_login($username, $password, $remember = false) {
    $conn = db_connect();
    
    $username = trim($username);
    
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password are required'];
    }
    
    // Check if account is locked
    $lock_query = "SELECT locked_until FROM admin_users WHERE username = ? AND locked_until > NOW()";
    $lock_stmt = mysqli_prepare($conn, $lock_query);
    mysqli_stmt_bind_param($lock_stmt, 's', $username);
    mysqli_stmt_execute($lock_stmt);
    $lock_result = mysqli_stmt_get_result($lock_stmt);
    
    if (mysqli_num_rows($lock_result) > 0) {
        $lock_data = mysqli_fetch_assoc($lock_result);
        $remaining = strtotime($lock_data['locked_until']) - time();
        $minutes = ceil($remaining / 60);
        return ['success' => false, 'message' => "Account locked. Try again in {$minutes} minutes."];
    }
    
    // Find admin user
    $query = "SELECT id, username, email, password, full_name, role, avatar, is_active 
              FROM admin_users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);
    
    if (!$admin) {
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    if (!$admin['is_active']) {
        return ['success' => false, 'message' => 'Account is deactivated'];
    }
    
    if (!password_verify($password, $admin['password'])) {
        // Increment login attempts
        $attempts_query = "UPDATE admin_users SET login_attempts = login_attempts + 1 WHERE id = ?";
        $attempts_stmt = mysqli_prepare($conn, $attempts_query);
        mysqli_stmt_bind_param($attempts_stmt, 'i', $admin['id']);
        mysqli_stmt_execute($attempts_stmt);
        
        // Check if should lock account (5 attempts)
        if ($admin['login_attempts'] + 1 >= 5) {
            $lock_query = "UPDATE admin_users SET locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?";
            $lock_stmt = mysqli_prepare($conn, $lock_query);
            mysqli_stmt_bind_param($lock_stmt, 'i', $admin['id']);
            mysqli_stmt_execute($lock_stmt);
            
            return ['success' => false, 'message' => 'Account locked due to too many failed attempts. Try again in 30 minutes.'];
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    // Reset login attempts and update last login
    $update_query = "UPDATE admin_users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'i', $admin['id']);
    mysqli_stmt_execute($update_stmt);
    
    // Set session data
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_data'] = [
        'id' => $admin['id'],
        'username' => $admin['username'],
        'email' => $admin['email'],
        'full_name' => $admin['full_name'],
        'role' => $admin['role'],
        'avatar' => $admin['avatar']
    ];
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Remember me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + ADMIN_REMEMBER_LIFETIME;
        
        $token_query = "UPDATE admin_users SET remember_token = ? WHERE id = ?";
        $token_stmt = mysqli_prepare($conn, $token_query);
        mysqli_stmt_bind_param($token_stmt, 'si', $token, $admin['id']);
        mysqli_stmt_execute($token_stmt);
        
        setcookie('admin_remember', $token, $expires, '/admin/', '', false, true);
    }
    
    // Log activity
    admin_log_activity($admin['id'], 'login', null, null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
    
    // Regenerate session ID
    session_regenerate_id(true);
    
    return ['success' => true, 'message' => 'Login successful'];
}

/**
 * Admin Logout
 */
function admin_logout() {
    $conn = db_connect();
    
    if (admin_is_logged_in()) {
        $admin_id = admin_get_id();
        
        // Log activity
        admin_log_activity($admin_id, 'logout');
        
        // Clear remember token
        if (isset($_COOKIE['admin_remember'])) {
            $token_query = "UPDATE admin_users SET remember_token = NULL WHERE id = ?";
            $token_stmt = mysqli_prepare($conn, $token_query);
            mysqli_stmt_bind_param($token_stmt, 'i', $admin_id);
            mysqli_stmt_execute($token_stmt);
            
            setcookie('admin_remember', '', time() - 3600, '/admin/', '', false, true);
        }
    }
    
    // Destroy session
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Validate Remember Me Token
 */
function admin_validate_remember() {
    $conn = db_connect();
    
    if (!isset($_COOKIE['admin_remember'])) {
        return false;
    }
    
    $token = $_COOKIE['admin_remember'];
    
    $query = "SELECT id, username, email, full_name, role, avatar 
              FROM admin_users WHERE remember_token = ? AND is_active = 1 LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);
    
    if ($admin) {
        // Refresh token
        $new_token = bin2hex(random_bytes(32));
        $update_query = "UPDATE admin_users SET remember_token = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'si', $new_token, $admin['id']);
        mysqli_stmt_execute($update_stmt);
        
        setcookie('admin_remember', $new_token, time() + ADMIN_REMEMBER_LIFETIME, '/admin/', '', false, true);
        
        return $admin;
    }
    
    return false;
}

/**
 * Verify Admin Session Security
 */
function admin_verify_session() {
    if (!admin_is_logged_in()) {
        return false;
    }
    
    // Check IP
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SESSION['admin_ip']) && $_SESSION['admin_ip'] !== $current_ip) {
        admin_logout();
        return false;
    }
    
    // Check User Agent
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (isset($_SESSION['admin_user_agent']) && $_SESSION['admin_user_agent'] !== $current_ua) {
        admin_logout();
        return false;
    }
    
    // Check session timeout
    if (time() - ($_SESSION['admin_login_time'] ?? 0) > ADMIN_SESSION_LIFETIME) {
        admin_logout();
        return false;
    }
    
    // Update last activity
    $_SESSION['admin_login_time'] = time();
    
    return true;
}

/**
 * Log Admin Activity
 */
function admin_log_activity($admin_id, $action, $target_type = null, $target_id = null, $details = []) {
    $conn = db_connect();
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $details_json = !empty($details) ? json_encode($details) : null;
    
    $query = "INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ississs', $admin_id, $action, $target_type, $target_id, $details_json, $ip, $ua);
    mysqli_stmt_execute($stmt);
}

/**
 * Get Admin Avatar
 */
function admin_get_avatar($admin_data) {
    if (!empty($admin_data['avatar'])) {
        return '../storage/uploads/avatars/' . $admin_data['avatar'];
    }
    
    // Generate default avatar
    $name = strtoupper(substr($admin_data['full_name'] ?? $admin_data['username'], 0, 2));
    return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#6366f1"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="white" font-size="40" font-family="Arial">' . $name . '</text></svg>');
}
