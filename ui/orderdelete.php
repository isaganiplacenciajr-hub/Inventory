<?php

include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

// Verify user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: User not logged in']);
    exit;
}

$id = $_POST['pidd'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid invoice ID']);
    exit;
}

try {
    // Initialize archive manager with current user
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    
    // Archive the invoice (moves to archive table instead of deleting)
    $result = $archiveManager->archiveInvoice($id, 'Deleted from order list');
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Order archived successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}







?>