<?php
/**
 * API Endpoint: Get archived invoice details
 * Admin only
 * 
 * Parameters:
 *   - invoice_id: The invoice ID to get details for
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
    
    if (!$invoiceId) {
        echo json_encode(['success' => false, 'message' => 'Invoice ID required']);
        exit;
    }
    
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    $details = $archiveManager->getArchivedInvoiceDetails($invoiceId);
    
    echo json_encode([
        'success' => true,
        'data' => $details
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching details: ' . $e->getMessage()
    ]);
}
?>
