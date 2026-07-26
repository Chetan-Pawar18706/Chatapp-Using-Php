<?php
/**
 * =====================================================
 * Helper Functions
 * ChatApp - Common Utility Functions
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

// Load compatibility layer (defines functions only if not already defined)
require_once __DIR__ . '/compat.php';

/**
 * Render avatar HTML - shows image if available, else letter initial
 */
if (!function_exists('render_avatar_html')) {
    function render_avatar_html($avatar, $username, $class = 'user-avatar') {
        if (!empty($avatar)) {
            return '<img src="' . htmlspecialchars($avatar) . '" alt="' . htmlspecialchars($username ?? '') . '" class="' . htmlspecialchars($class) . '-img">';
        }
        $initial = htmlspecialchars(substr($username ?? 'U', 0, 1));
        return '<div class="' . htmlspecialchars($class) . '">' . $initial . '</div>';
    }
}

if (!function_exists('get_user_theme')) {
    function get_user_theme() {
        if (isset($_SESSION['user_data']['theme'])) {
            return $_SESSION['user_data']['theme'];
        }
        if (isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/../config/database.php';
            $user_id = $_SESSION['user_id'];
            $result = db_fetch_single("SELECT theme FROM users WHERE id = ?", [$user_id], 'i');
            if ($result) {
                $_SESSION['user_data']['theme'] = $result['theme'];
                return $result['theme'];
            }
        }
        return 'dark';
    }
}

if (!function_exists('get_avatar_url')) {
    function get_avatar_url($avatar, $username = 'User') {
        if (!empty($avatar)) {
            return $avatar;
        }
        return 'storage/uploads/avatars/default-avatar.svg';
    }
}

/**
 * =====================================================
 * Activity Logging
 * =====================================================
 */
if (!function_exists('log_activity')) {
    function log_activity($user_id, $action, $details = []) {
        require_once __DIR__ . '/../config/database.php';
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $details_json = is_array($details) ? json_encode($details) : (string)$details;
        
        $sql = "INSERT INTO activity_log (user_id, action, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?)";
        $stmt = db_prepare($sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'issss', $user_id, $action, $ip, $user_agent, $details_json);
            mysqli_stmt_execute($stmt);
            $stmt->close();
        }
    }
}

/**
 * =====================================================
 * Response Functions
 * =====================================================
 */
if (!function_exists('send_json_response')) {
    function send_json_response($first, $second = []) {
        if (is_int($first)) {
            $status_code = $first;
            $data = $second;
        } else {
            $data = $first;
            $status_code = $second ?: 200;
        }
        
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('send_success')) {
    function send_success($message = 'Success', $data = []) {
        send_json_response(['success' => true, 'message' => $message, 'data' => $data], 200);
    }
}

if (!function_exists('send_error')) {
    function send_error($message = 'Error occurred', $status_code = 400, $errors = []) {
        send_json_response(['success' => false, 'message' => $message, 'errors' => $errors], $status_code);
    }
}

/**
 * =====================================================
 * User Functions
 * =====================================================
 */
if (!function_exists('request_password_reset')) {
    function request_password_reset($email) {
        require_once __DIR__ . '/../config/database.php';
        
        $email = trim($email);
        if (empty($email)) return ['success' => false, 'message' => 'Email is required'];
        if (!validate_email($email)) return ['success' => false, 'message' => 'Invalid email format'];
        
        $sql = "SELECT id, username, email FROM users WHERE email = ? AND status = 'active' LIMIT 1";
        $user = db_fetch_single($sql, [$email], 's');
        
        if (!$user) {
            return ['success' => true, 'message' => 'If an account exists with that email, a reset link has been sent'];
        }
        
        $token = generate_token(32);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?";
        $result = db_execute($sql, [$token, $expires, $user['id']], 'ssi');
        
        if ($result) {
            log_activity($user['id'], 'password_reset_requested');
            return [
                'success' => true,
                'message' => 'If an account exists with that email, a reset link has been sent',
                'reset_token' => $token
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to process request'];
    }
}

if (!function_exists('reset_password')) {
    function reset_password($token, $password, $confirm_password) {
        require_once __DIR__ . '/../config/database.php';
        
        if (empty($token) || empty($password) || empty($confirm_password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        if ($password !== $confirm_password) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }
        if (!validate_password_strength($password)) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and number'];
        }
        
        $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW() AND status = 'active' LIMIT 1";
        $user = db_fetch_single($sql, [$token], 's');
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        $hashed_password = hash_password($password);
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
        $result = db_execute($sql, [$hashed_password, $user['id']], 'si');
        
        if ($result) {
            log_activity($user['id'], 'password_reset_completed');
            return ['success' => true, 'message' => 'Password reset successful'];
        }
        
        return ['success' => false, 'message' => 'Failed to reset password'];
    }
}

if (!function_exists('logout_user')) {
    function logout_user() {
        if (!session_is_logged_in()) {
            return ['success' => true, 'message' => 'Already logged out'];
        }
        
        require_once __DIR__ . '/../config/database.php';
        
        $user_id = session_get_user_id();
        $sql = "UPDATE users SET is_online = 0, last_seen = NOW() WHERE id = ?";
        db_execute($sql, [$user_id], 'i');
        
        log_activity($user_id, 'logout');
        app_session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
}
