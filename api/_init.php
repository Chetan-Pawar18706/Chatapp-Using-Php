<?php
// Prevent PHP errors from corrupting JSON responses
ini_set('display_errors', 0);
ini_set('html_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/temp/php_errors.log');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
