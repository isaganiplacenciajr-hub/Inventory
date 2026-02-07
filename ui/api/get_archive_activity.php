<?php
/**
 * API Endpoint: Get archive activity log
 * Admin only
 * 
 * Parameters:
 *   - invoice_id: Optional - filter by specific invoice
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

try {
    $invoiceId = $_GET['invoice_id'] ?? null;
    
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    $logs = $archiveManager->getActivityLog($invoiceId);
    
    echo json_encode([
        'success' => true,
        'data' => $logs
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching activity log: ' . $e->getMessage()
    ]);
}
?>
