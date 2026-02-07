<?php
/**
 * API Endpoint: Get archive statistics
 * Admin only
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
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    $stats = $archiveManager->getArchiveStatistics();
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching statistics: ' . $e->getMessage()
    ]);
}
?>
