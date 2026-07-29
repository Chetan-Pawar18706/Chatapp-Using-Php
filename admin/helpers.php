<?php
/**
 * =====================================================
 * Admin Helper Functions
 * ChatApp - Common Admin Utilities
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Send JSON Response
 */
function admin_send_json($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send Success Response
 */
function admin_send_success($message = 'Success', $data = []) {
    admin_send_json([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Send Error Response
 */
function admin_send_error($message = 'Error occurred', $status_code = 400) {
    admin_send_json([
        'success' => false,
        'message' => $message
    ], $status_code);
}

/**
 * Sanitize Input
 */
function admin_sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get Client IP
 */
function admin_get_ip() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
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

/**
 * Format Date
 */
function admin_format_date($date, $format = 'M d, Y h:i A') {
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Time Ago
 */
function admin_time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    
    return admin_format_date($datetime, 'M d, Y');
}

/**
 * Format File Size
 */
function admin_format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Generate Pagination
 */
function admin_pagination($total, $per_page, $current_page, $base_url = '?') {
    $total_pages = ceil($total / $per_page);
    
    if ($total_pages <= 1) return '';
    
    $current_page = max(1, min($current_page, $total_pages));
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    $sep = (strpos($base_url, '?') !== false) ? '&' : '?';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . $sep . 'page=' . ($current_page - 1) . '">&laquo;</a></li>';
    }
    
    // Pages
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $current_page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $base_url . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . $sep . 'page=' . ($current_page + 1) . '">&raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Get Status Badge
 */
function admin_status_badge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'banned' => '<span class="badge bg-danger">Banned</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'reviewed' => '<span class="badge bg-info">Reviewed</span>',
        'resolved' => '<span class="badge bg-success">Resolved</span>',
        'dismissed' => '<span class="badge bg-secondary">Dismissed</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get Role Badge
 */
function admin_role_badge($role) {
    $badges = [
        'super_admin' => '<span class="badge bg-danger">Super Admin</span>',
        'admin' => '<span class="badge bg-primary">Admin</span>',
        'moderator' => '<span class="badge bg-info">Moderator</span>',
        'member' => '<span class="badge bg-secondary">Member</span>'
    ];
    
    return $badges[$role] ?? '<span class="badge bg-secondary">' . ucfirst(str_replace('_', ' ', $role)) . '</span>';
}

/**
 * Get User Avatar HTML
 */
function admin_user_avatar($user, $size = 40) {
    if (!empty($user['avatar'])) {
        $src = '../storage/uploads/avatars/' . htmlspecialchars($user['avatar']);
    } else {
        $name = strtoupper(substr($user['username'] ?? 'U', 0, 2));
        $src = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '"><rect width="' . $size . '" height="' . $size . '" fill="#6366f1"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="white" font-size="' . ($size * 0.4) . '" font-family="Arial">' . $name . '</text></svg>');
    }
    
    return '<img src="' . $src . '" alt="' . htmlspecialchars($user['username'] ?? '') . '" class="rounded-circle" width="' . $size . '" height="' . $size . '" style="object-fit: cover;">';
}

/**
 * Get Report Reason Text
 */
function admin_report_reason($reason) {
    $reasons = [
        'spam' => 'Spam',
        'harassment' => 'Harassment',
        'inappropriate_content' => 'Inappropriate Content',
        'fake_account' => 'Fake Account',
        'other' => 'Other'
    ];
    
    return $reasons[$reason] ?? ucfirst(str_replace('_', ' ', $reason));
}

/**
 * Truncate Text
 */
function admin_truncate($text, $length = 50, $suffix = '...') {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Generate CSRF Token
 */
function admin_csrf_token() {
    if (!isset($_SESSION['admin_csrf_token']) || !isset($_SESSION['admin_csrf_time'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['admin_csrf_time'] = time();
    } elseif (time() - $_SESSION['admin_csrf_time'] > 3600) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['admin_csrf_time'] = time();
    }
    
    return $_SESSION['admin_csrf_token'];
}

/**
 * Verify CSRF Token
 */
function admin_verify_csrf($token) {
    if (empty($token) || empty($_SESSION['admin_csrf_token'])) {
        return false;
    }
    
    if (time() - ($_SESSION['admin_csrf_time'] ?? 0) > 3600) {
        return false;
    }
    
    return hash_equals($_SESSION['admin_csrf_token'], $token);
}

/**
 * CSRF Hidden Field
 */
function admin_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . admin_csrf_token() . '">';
}
