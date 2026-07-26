<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Profile Update API
 * ChatApp - Update User Profile
 * =====================================================
 */

define('APP_RUNNING', true);

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';

init_session();

if (!is_logged_in()) {
    send_json_response(401, ['success' => false, 'message' => 'Unauthorized']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, ['success' => false, 'message' => 'Method not allowed']);
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    send_json_response(403, ['success' => false, 'message' => 'Invalid CSRF token']);
}

$user_id = get_user_id();

if (!check_rate_limit('profile_update_' . $user_id, 10, 60)) {
    send_json_response(429, ['success' => false, 'message' => 'Too many requests. Please wait.']);
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$about = trim($_POST['about'] ?? '');
$theme = trim($_POST['theme'] ?? '');
$language = trim($_POST['language'] ?? '');
$chat_style = trim($_POST['chat_style'] ?? '');

$existing_cols = [];
$col_result = mysqli_query($conn, "SHOW COLUMNS FROM users");
while ($col = mysqli_fetch_assoc($col_result)) {
    $existing_cols[] = $col['Field'];
}

$updates = [];
$types = '';
$params = [];

// Theme
if (!empty($theme) && in_array('theme', $existing_cols)) {
    $valid_themes = ['dark', 'light', 'midnight', 'ocean'];
    if (in_array($theme, $valid_themes)) {
        $updates[] = 'theme = ?';
        $types .= 's';
        $params[] = $theme;
    }
}

// Language
if (!empty($language) && in_array('language', $existing_cols)) {
    $updates[] = 'language = ?';
    $types .= 's';
    $params[] = $language;
}

// Chat style
if (!empty($chat_style) && in_array('chat_style', $existing_cols)) {
    $updates[] = 'chat_style = ?';
    $types .= 's';
    $params[] = $chat_style;
}

// Username
if (!empty($username)) {
    if (strlen($username) < 3 || strlen($username) > 30) {
        send_json_response(400, ['success' => false, 'message' => 'Username must be 3-30 characters']);
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        send_json_response(400, ['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
    }
    $check = db_fetch_single("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $user_id], 'si');
    if ($check) {
        send_json_response(400, ['success' => false, 'message' => 'Username is already taken']);
    }
    if (in_array('username', $existing_cols)) {
        $updates[] = 'username = ?';
        $types .= 's';
        $params[] = $username;
    }
}

// Email
if (!empty($email)) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_json_response(400, ['success' => false, 'message' => 'Invalid email format']);
    }
    $check = db_fetch_single("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id], 'si');
    if ($check) {
        send_json_response(400, ['success' => false, 'message' => 'Email is already registered']);
    }
    if (in_array('email', $existing_cols)) {
        $updates[] = 'email = ?';
        $types .= 's';
        $params[] = $email;
    }
}

// Bio
if (isset($_POST['bio']) && strlen($bio) <= 150) {
    if (in_array('bio', $existing_cols)) {
        $updates[] = 'bio = ?';
        $types .= 's';
        $params[] = $bio;
    }
}

// Save chat_style to settings JSON as well
$extra_settings = [];
if (!empty($chat_style)) $extra_settings['chat_style'] = $chat_style;

if (!empty($extra_settings) && in_array('settings', $existing_cols)) {
    $current_settings = db_fetch_single("SELECT settings FROM users WHERE id = ?", [$user_id], 'i');
    $decoded = json_decode($current_settings['settings'] ?? '{}', true) ?: [];
    $decoded = array_merge($decoded, $extra_settings);
    $updates[] = 'settings = ?';
    $types .= 's';
    $params[] = json_encode($decoded);
}

if (empty($updates)) {
    send_json_response(200, ['success' => true, 'message' => 'No changes to save']);
}

$updates[] = 'updated_at = NOW()';
$types .= 'i';
$params[] = $user_id;

$sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);

if (mysqli_stmt_execute($stmt)) {
    log_activity($user_id, 'profile_update', 'Updated profile settings');

    if (!empty($username)) {
        $_SESSION['username'] = $username;
        if (isset($_SESSION['user_data'])) {
            $_SESSION['user_data']['username'] = $username;
        }
    }

    if (!empty($theme)) {
        if (isset($_SESSION['user_data'])) {
            $_SESSION['user_data']['theme'] = $theme;
        }
        $_SESSION['theme'] = $theme;
    }

    send_json_response(200, [
        'success' => true,
        'message' => 'Settings updated successfully',
        'data' => array_merge(
            !empty($username) ? ['username' => $username] : [],
            !empty($email) ? ['email' => $email] : [],
            !empty($bio) ? ['bio' => $bio] : [],
            !empty($theme) ? ['theme' => $theme] : [],
            !empty($chat_style) ? ['chat_style' => $chat_style] : []
        )
    ]);
} else {
    send_json_response(500, ['success' => false, 'message' => 'Failed to update settings']);
}
