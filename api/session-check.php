<?php
define('APP_RUNNING', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

session_initialize();

if (!session_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$user_id = session_get_user_id();
db_execute("UPDATE users SET last_seen = NOW() WHERE id = ?", [$user_id], 'i');

echo json_encode(['success' => true, 'message' => 'Session active']);
