<?php
include_once 'connectdb.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$invoice = $_POST['invoice'] ?? '';
if (!$invoice) {
    echo json_encode(['error' => 'Missing invoice ID']);
    exit;
}

try {
    // fetch current deposit
    $stmt = $pdo->prepare("SELECT deposit FROM tbl_invoice WHERE invoice_id = ?");
    $stmt->execute([$invoice]);
    $dep = $stmt->fetchColumn();
    if ($dep === false) {
        echo json_encode(['error' => 'Invoice not found']);
        exit;
    }

    $amt = floatval($dep);
    if ($amt <= 0) {
        echo json_encode(['error' => 'No refundable deposit']);
        exit;
    }

    // perform refund by negating deposit amount
    $newDep = -$amt;
    $stmt = $pdo->prepare("UPDATE tbl_invoice SET deposit = ? WHERE invoice_id = ?");
    $stmt->execute([$newDep, $invoice]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Database error']);
}
