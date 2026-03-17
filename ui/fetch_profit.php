<?php
include_once 'connectdb.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    echo json_encode(['error'=>'Not authenticated']); exit;
}

$filter = $_GET['filter'] ?? 'Today';
$start = null; $end = null;
$today = date('Y-m-d');
switch($filter){
    case 'Yesterday':
        $start=$end=date('Y-m-d',strtotime('-1 day'));
        break;
    case 'This Week':
        $start=date('Y-m-d',strtotime('monday this week'));
        $end=date('Y-m-d',strtotime('sunday this week'));
        break;
    case 'This Month':
        $start=date('Y-m-01');
        $end=date('Y-m-t');
        break;
    case 'All Records':
        $start=$end=null; break;
    case 'Custom Date Range':
        $start=$_GET['start']??$today;
        $end=$_GET['end']??$today;
        break;
    default:
        $start=$end=$today;
}
$params=[];
$cond='';
if($start && $end){
    $cond=" AND DATE(inv.order_date) BETWEEN ? AND ?";
    $params[]=$start;
    $params[]=$end;
}

try{
    // Only show profit for invoices created by the logged-in user
    $userEmail = $_SESSION['useremail'] ?? null;
    $role = $_SESSION['role'] ?? '';
    if ($role === 'Admin') {
        // Admin sees all data
        $stmt = $pdo->prepare("SELECT inv.order_date, inv.invoice_id, d.product_name, d.qty, d.saleprice, d.product_id
            FROM tbl_invoice_details d
            JOIN tbl_invoice inv ON d.invoice_id = inv.invoice_id
            WHERE inv.status='Complete' " . $cond . " ORDER BY inv.order_date");
    } else if ($userEmail) {
        $userCond = " AND u.useremail = ?";
        $stmt = $pdo->prepare("SELECT inv.order_date, inv.invoice_id, d.product_name, d.qty, d.saleprice, d.product_id
            FROM tbl_invoice_details d
            JOIN tbl_invoice inv ON d.invoice_id = inv.invoice_id
            JOIN tbl_user u ON inv.created_by = u.userid
            WHERE inv.status='Complete' " . $cond . $userCond . " ORDER BY inv.order_date");
        $params[] = $userEmail;
    } else {
        // Fallback: show all data
        $stmt = $pdo->prepare("SELECT inv.order_date, inv.invoice_id, d.product_name, d.qty, d.saleprice, d.product_id
            FROM tbl_invoice_details d
            JOIN tbl_invoice inv ON d.invoice_id = inv.invoice_id
            WHERE inv.status='Completed' " . $cond . " ORDER BY inv.order_date");
    }
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // DEBUG: Output row count and first row
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo json_encode([
            'row_count' => count($rows),
            'first_row' => $rows[0] ?? null,
            'all_rows' => $rows
        ]);
        exit;
    }
}catch(Throwable $e){
    echo json_encode(['error'=>'Query failed']); exit;
}

// ...existing code...
$data=[];
$totalSales=0; $totalCost=0; $totalProfit=0; $totalVat=0;
foreach($rows as $r){
    $sales = floatval($r['saleprice']) * intval($r['qty']);
    // Fetch purchase price for product
    $cost = 0;
    $profit = 0;
    $vat = $sales * 0.12;
    $pid = $r['product_id'];
    $stmtCost = $pdo->prepare("SELECT purchaseprice FROM tbl_product WHERE pid = ? LIMIT 1");
    $stmtCost->execute([$pid]);
    $rowCost = $stmtCost->fetch(PDO::FETCH_ASSOC);
    if ($rowCost && isset($rowCost['purchaseprice'])) {
        $cost = floatval($rowCost['purchaseprice']) * intval($r['qty']);
    }
    $profit = $sales - $cost - $vat;
    $totalSales += $sales;
    $totalCost += $cost;
    $totalProfit += $profit;
    $totalVat += $vat;
    $data[] = [
        'date'=>$r['order_date'],
        'invoice'=>$r['invoice_id'],
        'product'=>$r['product_name'],
        'qty'=>$r['qty'],
        'sales'=>$sales,
        'cost'=>$cost,
        'vat'=>$vat,
        'profit'=>$profit,
        'product_id'=>$r['product_id']
    ];
}
$summary=['sales'=>$totalSales,'cost'=>$totalCost,'vat'=>$totalVat,'profit'=>$totalProfit,'start'=>$start,'end'=>$end];
echo json_encode(['rows'=>$data,'summary'=>$summary]);
