<?php
include_once 'connectdb.php';
session_start();
if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    header('location:../index.php');
    exit;
}
include_once "header.php";

$today = date('Y-m-d');
$salesTotal = 0;
$costTotal = 0;
$profitTotal = 0;
try {
    $stmt = $pdo->prepare("SELECT SUM(total - COALESCE(deposit,0)) FROM tbl_invoice WHERE DATE(order_date)=? AND status='Complete'");
    $stmt->execute([$today]);
    $salesTotal = floatval($stmt->fetchColumn() ?? 0);
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(d.qty * p.purchaseprice),0)
                FROM tbl_invoice_details d
                JOIN tbl_invoice inv ON d.invoice_id = inv.invoice_id
                JOIN tbl_product p ON d.product_id = p.pid
                WHERE DATE(inv.order_date)=? AND inv.status='Complete'");
    $stmt->execute([$today]);
    $costTotal = floatval($stmt->fetchColumn() ?? 0);
    
    $profitTotal = $salesTotal - $costTotal;
} catch (Throwable $e) {
    // ignore
}
?>

<div class="content-wrapper">
  <div class="row mb-2">
    <div class="col-sm-6"><h1 class="m-0">Net Profit Report</h1></div>
    <div class="col-sm-6 text-right">
      <a href="dashboard.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i> Back</a>
      <button class="btn btn-primary btn-sm" id="btnPrint"><i class="fas fa-print"></i> Print Profit Report</button>
    </div>
  </div>
  <div class="content"><div class="container-fluid">
    <div id="reportContent">
      <p><strong>Date:</strong> <?php echo date('Y-m-d H:i'); ?></p>
      <p><strong>System:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_NAME']); ?></p>
      <p><strong>Prepared by:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
      <hr>
      <p><strong>Total Sales:</strong> ₱<?php echo number_format($salesTotal,2); ?></p>
      <p><strong>Total Product Cost:</strong> ₱<?php echo number_format($costTotal,2); ?></p>
      <p><strong>Net Profit:</strong> ₱<?php echo number_format($profitTotal,2); ?></p>
    </div>
  </div></div>
</div>

<script>
$(function(){
    $('#btnPrint').click(function(){
        const html = $('#reportContent').html();
        const w = window.open('','_blank','width=800,height=600');
        w.document.write('<html><head><title>Profit Report</title><style>body{font-family:sans-serif;margin:20px;}@media print{body{margin:0;}}</style></head><body>'+html+'</body></html>');
        w.document.close();
        setTimeout(()=>{w.print(); w.close();},250);
    });
});
</script>

<?php include_once "footer.php"; ?>