<?php
include_once __DIR__ . '/../connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$invoiceId = isset($_GET['invoice_id']) ? trim($_GET['invoice_id']) : '';
if ($invoiceId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing invoice_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT product_name, qty, saleprice FROM tbl_invoice_details WHERE invoice_id = ?");
    $stmt->execute([$invoiceId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(function($r){
        return [
            'product' => $r['product_name'],
            'qty' => (int)$r['qty'],
            'unit_price' => number_format((float)$r['saleprice'], 2, '.', ''),
            'line_total' => number_format((float)$r['qty'] * (float)$r['saleprice'], 2, '.', ''),
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
