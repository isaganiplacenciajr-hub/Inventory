<?php
/**
 * API Endpoint: Restore archived invoice
 * Admin only
 * 
 * POST Parameters:
 *   - archive_id: The archive record ID to restore
 *   - notes: Optional notes about restoration
 */

include_once '../connectdb.php';
include_once '../ArchiveManager.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['userid']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied: Admin only']);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $archiveId = $_POST['archive_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    if (!$archiveId) {
        echo json_encode(['success' => false, 'message' => 'Archive ID required']);
        exit;
    }
    
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    $result = $archiveManager->restoreInvoice($archiveId, $notes);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error restoring invoice: ' . $e->getMessage()
    ]);
}
?>
