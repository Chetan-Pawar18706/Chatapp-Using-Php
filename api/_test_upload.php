<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('APP_RUNNING', true);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'test';

$tmpFile = tempnam(sys_get_temp_dir(), 'test');
file_put_contents($tmpFile, 'hello world');
$_FILES['file'] = [
    'name' => 'test.txt',
    'type' => 'text/plain',
    'tmp_name' => $tmpFile,
    'error' => UPLOAD_ERR_OK,
    'size' => 11
];
$_POST['csrf_token'] = 'test';

try {
    require __DIR__ . '/upload-media.php';
} catch (Throwable $e) {
    echo "CAUGHT: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
