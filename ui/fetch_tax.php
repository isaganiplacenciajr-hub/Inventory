<?php
include_once 'connectdb.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    echo json_encode(['error'=>'Not authenticated']); exit;
}

$filter = $_GET['filter'] ?? 'Today';
$start=null; $end=null;
$today=date('Y-m-d');
switch($filter){
    case 'Yesterday':
        $start=$end=date('Y-m-d',strtotime('-1 day')); break;
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
    $cond=" AND DATE(order_date) BETWEEN ? AND ?";
    $params[]=$start;
    $params[]=$end;
}

try{
    $stmt = $pdo->prepare("SELECT order_date, invoice_id, (total - COALESCE(deposit,0)) AS sales
        FROM tbl_invoice WHERE status='Complete' " . $cond . " ORDER BY order_date");
    $stmt->execute($params);
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){
    echo json_encode(['error'=>'Query failed']); exit;
}

$data=[];
$totalSales=0; $totalVAT=0; $grand=0;
foreach($rows as $r){
    $sales=floatval($r['sales']);
    $vat=$sales*0.12;
    $totalSales += $sales;
    $totalVAT += $vat;
    $grand += ($sales + $vat);
    $data[]=[
        'date'=>$r['order_date'],
        'invoice'=>$r['invoice_id'],
        'sales'=>$sales,
        'vat'=>$vat,
        'total'=>$sales+$vat
    ];
}
$summary=['sales'=>$totalSales,'vat'=>$totalVAT,'grand'=>$grand,'start'=>$start,'end'=>$end];
echo json_encode(['rows'=>$data,'summary'=>$summary]);
