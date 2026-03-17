<?php
include_once __DIR__ . '/../connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$branch = isset($_GET['branch']) ? trim($_GET['branch']) : '';
$allowed = ['Matain', 'Sawmil', 'San Isidro', 'Main'];
if (!$branch || !in_array($branch, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch']);
    exit;
}

$branchConditions = [];
if ($branch === 'Matain') {
    $branchConditions = [ 'Matain', 'Matain Branch' ];
} elseif ($branch === 'Sawmil') {
    $branchConditions = [ 'Sawmill', 'Sawmill Branch' ];
} elseif ($branch === 'San Isidro') {
    $branchConditions = [ 'San Isidro', 'San Isidro Main Branch' ];
}

try {
    $placeholders = implode(',', array_fill(0, count($branchConditions), '?'));
    $sql = "SELECT i.invoice_id, d.product_name, d.qty, COALESCE(d.saleprice * d.qty, 0) AS total, i.order_date, i.branch
            FROM tbl_invoice i
            LEFT JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id
            WHERE DATE(i.order_date) = CURDATE() AND i.branch IN ($placeholders)
            ORDER BY i.order_date DESC, i.invoice_id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($branchConditions);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $r) {
        $result[] = [
            'invoice_id' => $r['invoice_id'],
            'product' => $r['product_name'],
            'qty' => (int)$r['qty'],
            'total' => number_format((float)$r['total'], 2, '.', ''),
            'order_date' => $r['order_date'],
            'branch' => $r['branch'],
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
