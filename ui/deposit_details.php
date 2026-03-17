<?php
include_once 'connectdb.php';
session_start();
if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    header('location:../index.php');
    exit;
}

include_once "header.php";

// fetch deposit transactions
$transactions = [];
try {
    $stmt = $pdo->query("SELECT inv.invoice_id, inv.customer_name, inv.order_date, inv.deposit, inv.status
                        FROM tbl_invoice inv
                        WHERE COALESCE(deposit,0) <> 0
                        ORDER BY inv.order_date DESC");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $transactions = [];
}

// compute summaries
$collected = 0;
$refunded = 0;
foreach ($transactions as $row) {
    $amt = floatval($row['deposit']);
    if ($amt > 0) {
        $collected += $amt;
    } else {
        $refunded += $amt; // record negative value
    }
}
$refundedAbs = abs($refunded);
$balance = $collected - $refundedAbs; // remaining after refund
?>

<div class="content-wrapper">
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Tank Deposit Transactions</h1>
    </div>
    <div class="col-sm-6 text-right">
      <a href="dashboard.php" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i> Back</a>
      <button class="btn btn-primary btn-sm" id="btnPrint"><i class="fas fa-print"></i> Print Report</button>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div id="reportContent">
        <div class="mb-3">
          <strong>Date:</strong> <?php echo date('Y-m-d H:i'); ?><br>
          <strong>System:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_NAME']); ?><br>
          <strong>Prepared by:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
        </div>
        <div class="mb-3">
          <strong>Total Deposits Collected:</strong> ₱<?php echo number_format($collected,2); ?><br>
          <strong>Total Deposits Refunded:</strong> ₱<?php echo number_format($refundedAbs ?? 0,2); ?><br>
          <strong>Remaining Deposit Balance:</strong> ₱<?php echo number_format($balance,2); ?>
        </div>

        <table class="table table-sm table-bordered">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($transactions)): ?>
              <tr><td colspan="5" class="text-center text-muted">No deposit transactions found.</td></tr>
            <?php else: ?>
              <?php foreach ($transactions as $t): ?>
                <tr>
                  <td><?php echo htmlspecialchars($t['invoice_id']); ?></td>
                  <td><?php echo htmlspecialchars($t['customer_name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($t['order_date']); ?></td>
                  <td>₱<?php echo number_format(abs($t['deposit']),2); ?></td>
                  <td><?php echo ($t['deposit'] > 0) ? 'Collected' : 'Refunded'; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div> <!-- end reportContent -->
    </div>
  </div>
</div>

<script>
$(function(){
    $('#btnPrint').click(function(){
        const html = $('#reportContent').html();
        const w = window.open('','_blank','width=800,height=600');
        w.document.write('<html><head><title>Deposit Report</title>');
        w.document.write('<style>body{font-family:sans-serif;margin:20px;}@media print{body{margin:0;}}</style>');
        w.document.write('</head><body>'+html+'</body></html>');
        w.document.close();
        setTimeout(()=>{w.print(); w.close();},250);
    });
});
</script>

<?php include_once "footer.php"; ?>
