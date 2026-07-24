<?php
/**
 * =====================================================
 * Admin API: Logout
 * ChatApp - Admin Logout
 * =====================================================
 */

define('APP_RUNNING', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/admin/config.php';
require_once dirname(__DIR__) . '/admin/auth.php';
require_once dirname(__DIR__) . '/admin/helpers.php';

admin_session_init();
admin_logout();

header('Location: ../index.php');
exit;
