<?php
/**
 * API Endpoint: Get archived invoices
 * Admin only
 * 
 * Parameters:
 *   - status: 'archived', 'restored', 'permanently_deleted', or 'all' (default: 'archived')
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
    $status = $_GET['status'] ?? 'archived';
    
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    $archives = $archiveManager->getArchivedInvoices($status);
    
    echo json_encode([
        'success' => true,
        'data' => $archives,
        'count' => count($archives)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching archives: ' . $e->getMessage()
    ]);
}
?>
