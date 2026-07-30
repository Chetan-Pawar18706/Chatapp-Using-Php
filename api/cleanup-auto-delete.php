<?php
require_once __DIR__ . '/_init.php';
/**
 * =====================================================
 * Auto-Delete Cleanup
 * ChatApp - Deletes messages based on auto_delete setting
 * Run via cron: php api/cleanup-auto-delete.php
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Only allow CLI access - no web access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Direct access not permitted');
}

$deleted = 0;

// Delete view_once messages immediately after seen (skip saved messages)
$sql_view_once = "UPDATE messages SET is_deleted = 1 
                  WHERE auto_delete = 'view_once' 
                  AND seen_at IS NOT NULL 
                  AND is_deleted = 0
                  AND id NOT IN (SELECT message_id FROM saved_messages)";
db_execute($sql_view_once, [], '');
$deleted += mysqli_affected_rows($conn);

// Delete 12-hour messages after receiver sees them (skip saved messages)
$sql_12h = "UPDATE messages SET is_deleted = 1 
            WHERE auto_delete = '12hours' 
            AND seen_at IS NOT NULL 
            AND seen_at < DATE_SUB(NOW(), INTERVAL 12 HOUR) 
            AND is_deleted = 0
            AND id NOT IN (SELECT message_id FROM saved_messages)";
db_execute($sql_12h, [], '');
$deleted += mysqli_affected_rows($conn);

if (php_sapi_name() === 'cli') {
    echo "Auto-delete cleanup complete. $deleted messages marked as deleted.\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'deleted' => $deleted]);
}
