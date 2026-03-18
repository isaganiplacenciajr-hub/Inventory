<?php
// backup_system.php: Admin-only endpoint to create a ZIP backup of the project
session_start();
if (!isset($_SESSION['userid'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Admin only: insufficient permissions']);
    exit;
}
// Debug log helper
function debug_log($msg) {
    error_log('[BACKUP DEBUG] ' . $msg);
}
// Role logged for debug
error_log("Backup requested by user {$_SESSION['userid']} role " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'unknown') . "");

$root = realpath(__DIR__ . '/..');
$backupDir = $root . '/backups';
if (!is_dir($backupDir)) {
    debug_log('Creating backups directory: ' . $backupDir);
    mkdir($backupDir, 0755, true);
}
if (!is_writable($backupDir)) {
    debug_log('Backups directory not writable: ' . $backupDir);
    echo json_encode(['success'=>false,'message'=>'Backups directory is not writable.']);
    exit;
}

$now = new DateTime('Asia/Manila');
$filename = 'system_backup_' . $now->format('Y_m_d_H_i') . '.zip';
$zipPath = $backupDir . '/' . $filename;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    debug_log('Could not create ZIP file: ' . $zipPath);
    echo json_encode(['success'=>false,'message'=>'Could not create ZIP file.']);
    exit;
}

// Exclude these paths from backup (relative to root)
$excludePatterns = '/^(?:backups|\\.git|node_modules|logs|temp|vendor|cache|storage|\.env\.local|\.env\.bak)/i';$excludeFileExts = ['log','mp4','mp3','mkv','tmp','swp','bak'];
function shouldExcludePath($localPath, $excludePatterns, $excludeFileExts) {
    if ($localPath === '' || preg_match($excludePatterns, $localPath)) {
        return true;
    }
    $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
    return $ext && in_array($ext, $excludeFileExts, true);
}

function addAllToZip($folder, $zip, $root, $excludePatterns, $excludeFileExts, &$stats) {
    debug_log('Scanning folder: ' . $folder);
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;

        $stats['total']++;
        $pathName = $file->getPathname();
        $localPath = ltrim(str_replace($root, '', $pathName), '/\\');

        if (shouldExcludePath($localPath, $excludePatterns, $excludeFileExts)) {
            $stats['skipped']++;
            debug_log('SKIP: ' . $localPath);
            continue;
        }
        if (!$file->isReadable()) {
            $stats['unreadable']++;
            debug_log('NOT READABLE: ' . $localPath);
            continue;
        }

        $result = $zip->addFile($pathName, $localPath);
        if ($result) {
            $stats['added']++;
            debug_log('ADD: ' . $localPath . ' (OK)');
        } else {
            $stats['failed']++;
            debug_log('ADD: ' . $localPath . ' (FAIL)');
        }
    }
}

// Add all project files, excluding backup and open-source cache dirs
$stats = ['total'=>0,'added'=>0,'skipped'=>0,'unreadable'=>0,'failed'=>0];
debug_log('Scanning project tree: ' . $root);
addAllToZip($root, $zip, $root, $excludePatterns, $excludeFileExts, $stats);
$status = $zip->close();
if ($status !== TRUE) {
    debug_log('Failed to finalize ZIP: ' . $zip->getStatusString());
    unlink($zipPath); // Remove failed ZIP
    echo json_encode(['success'=>false,'message'=>'Failed to finalize ZIP: ' . $zip->getStatusString()]);
    exit;
}

$fileCount = $stats['added'];
if ($fileCount === 0) {
    debug_log('No files added to backup. Check permissions. Stats: ' . json_encode($stats));
    unlink($zipPath);
    echo json_encode(['success'=>false,'message'=>'No files added to backup. Check permissions.', 'stats' => $stats]);
    exit;
}

// Success
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'fileCount' => $fileCount,
    'size' => filesize($zipPath),
    'stats' => $stats
]);
