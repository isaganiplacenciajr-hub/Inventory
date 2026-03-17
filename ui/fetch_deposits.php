<?php
include_once 'connectdb.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$filter = $_GET['filter'] ?? 'Today';
$start = null; $end = null;
$today = date('Y-m-d');

switch ($filter) {
    case 'Yesterday':
        $start = $end = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'This Week':
        // monday through sunday
        $start = date('Y-m-d', strtotime('monday this week'));
        $end = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'This Month':
        $start = date('Y-m-01');
        $end = date('Y-m-t');
        break;
    case 'All Records':
        $start = $end = null;
        break;
    case 'Custom Date Range':
        $start = $_GET['start'] ?? $today;
        $end = $_GET['end'] ?? $today;
        break;
    default:
        $start = $end = $today;
}

$params = [];
$cond = '';
if ($start && $end) {
    $cond = ' AND DATE(order_date) BETWEEN ? AND ?';
    $params[] = $start;
    $params[] = $end;
}

try {
    $stmt = $pdo->prepare("SELECT order_date, invoice_id, customer_name, deposit FROM tbl_invoice WHERE COALESCE(deposit,0) <> 0 " . $cond . " ORDER BY order_date");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$collected = 0;
$refunded = 0;
$balance = 0;
$dataRows = [];
foreach ($rows as $r) {
    $amt = floatval($r['deposit']);
    $col = $amt > 0 ? $amt : 0;
    $ref = $amt < 0 ? abs($amt) : 0;
    $balance += $amt; // running
    $dataRows[] = [
        'date' => $r['order_date'],
        'invoice' => $r['invoice_id'],
        'customer' => $r['customer_name'],
        'deposit_amount' => $amt,
        'tank_size' => '11kg', // deposit only applies to 11kg tanks
        'collected' => $col,
        'refunded' => $ref,
        'balance' => $balance,
        'status' => $amt > 0 ? 'Collected' : 'Refunded'
    ];
    $collected += $col;
    $refunded += $ref;
}

$summary = [
    'collected' => $collected,
    'refunded' => $refunded,
    'balance' => $balance,
    'start' => $start,
    'end' => $end
];

echo json_encode(['rows' => $dataRows, 'summary' => $summary]);
