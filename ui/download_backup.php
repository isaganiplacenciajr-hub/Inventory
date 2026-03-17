<?php
// download_backup.php: Secure ZIP download for admin only
ob_clean();
ob_start();
session_start();
if (!isset($_SESSION['userid'])) {
    http_response_code(403);
    exit('Forbidden');
}
// Log for debug
error_log("Download requested by user {$_SESSION['userid']} role " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'unknown') . " file " . ($_GET['file'] ?? ''));
$backupDir = realpath(__DIR__ . '/../backups');
$filename = basename($_GET['file'] ?? '');
if (!$filename || !preg_match('/^system_backup_\d{4}_\d{2}_\d{2}_\d{2}_\d{2}\.zip$/', $filename)) {
    http_response_code(400);
    exit('Invalid file.');
}
$filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;
if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found.');
}
// Clean all output buffers before sending file
while (ob_get_level()) { ob_end_clean(); }
$size = filesize($filepath);
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $size);
header('Pragma: public');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');
header('Accept-Ranges: bytes');

// Stream file in chunks to avoid timeouts and high memory usage
$chunkSize = 1024 * 1024; // 1 MB
$handle = fopen($filepath, 'rb');
if ($handle === false) {
    http_response_code(500);
    exit('Unable to open file');
}

while (!feof($handle) && connection_status() === CONNECTION_NORMAL) {
    echo fread($handle, $chunkSize);
    flush();
}
fclose($handle);
exit;
