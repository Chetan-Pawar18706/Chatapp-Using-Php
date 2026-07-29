<?php
define('APP_RUNNING', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/security.php';

session_initialize();

if (!session_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

// Check inactivity timeout (5 minutes)
$lastActivity = $_SESSION['last_activity'] ?? 0;
if (time() - $lastActivity > SESSION_LIFETIME) {
    // Session timed out due to inactivity
    app_session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired due to inactivity']);
    exit;
}

// Update last activity on each AJAX call
$_SESSION['last_activity'] = time();

$user_id = session_get_user_id();
db_execute("UPDATE users SET last_seen = NOW() WHERE id = ?", [$user_id], 'i');

echo json_encode(['success' => true, 'message' => 'Session active']);
