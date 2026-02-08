<?php
session_start();
include_once 'connectdb.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// detect print mode
$isPrint = (isset($_GET['print']) && $_GET['print'] == 1);
include_once 'header.php';

// build print URL preserving query params
$printUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['print' => 1]));

// Determine week range: accept start and end via GET, otherwise compute current week (Mon-Sun)
if (isset($_GET['start']) && isset($_GET['end'])) {
  $start = $_GET['start'];
  $end = $_GET['end'];
} else {
  $monday = date('Y-m-d', strtotime('monday this week'));
  $sunday = date('Y-m-d', strtotime('sunday this week'));
  $start = $monday;
  $end = $sunday;
}

$sql = "SELECT i.invoice_id, i.order_date, d.product_name, d.qty, d.rate, d.saleprice AS line_total, d.addfee AS additional_fee, i.payment_type 
        FROM tbl_invoice i 
        JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id 
        WHERE DATE(i.order_date) BETWEEN :start AND :end 
        ORDER BY i.order_date DESC, i.invoice_id DESC";
$q = $pdo->prepare($sql);
$q->bindParam(':start', $start);
$q->bindParam(':end', $end);
$q->execute();
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

$aggOrders = $pdo->prepare("SELECT COUNT(DISTINCT invoice_id) FROM tbl_invoice WHERE DATE(order_date) BETWEEN :start AND :end");
$aggOrders->bindParam(':start', $start);
$aggOrders->bindParam(':end', $end);
$aggOrders->execute();
$totalOrders = (int)$aggOrders->fetchColumn();

$totalLpgSold = 0;
$totalAdditionalFees = 0.0;
foreach ($rows as $r) { 
  $totalLpgSold += (int)$r['qty']; 
  $totalAdditionalFees += (float)$r['additional_fee'];
}

$totalSalesStmt = $pdo->prepare("SELECT COALESCE(SUM(d.saleprice),0) 
                                 FROM tbl_invoice i 
                                 JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id 
                                 WHERE DATE(i.order_date) BETWEEN :start AND :end");
$totalSalesStmt->bindParam(':start', $start);
$totalSalesStmt->bindParam(':end', $end);
$totalSalesStmt->execute();
$totalSales = (float)$totalSalesStmt->fetchColumn();

$grandTotal = $totalSales;
?>

<style>
.badge-status {
  padding: 5px 10px;
  border-radius: 8px;
  font-weight: 600;
  color: white;
}
.status-paid { background-color: #28a745; }     /* green */
.status-unpaid { background-color: #dc3545; }   /* red */
.status-partial { background-color: #ffc107; color: #000; } /* yellow */
.payment-cash { background-color: #007bff; }    /* blue */
.payment-check { background-color: #6c757d; }   /* gray */
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Weekly Sales Report</h1>
          <small>Period: <?php echo htmlspecialchars($start); ?> to <?php echo htmlspecialchars($end); ?></small>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-primary">
        <div class="card-header">
          <form class="form-inline" method="get">
            <label class="mr-2">Start</label>
            <input type="date" name="start" class="form-control mr-2" value="<?php echo htmlspecialchars($start); ?>">
            <label class="mr-2">End</label>
            <input type="date" name="end" class="form-control mr-2" value="<?php echo htmlspecialchars($end); ?>">
            <button class="btn btn-primary" type="submit">Show</button>
            <a class="btn btn-secondary ml-2" href="sales_report_weekly.php">This Week</a>
            <a class="btn btn-success ml-2" href="<?php echo htmlspecialchars($printUrl); ?>" target="_blank">Print</a>
          </form>
        </div>
        <div class="card-body">
          <?php if (!empty($isPrint)): ?>
          <div class="receipt" style="text-align:center;margin-bottom:12px;">
            <h3>SPM LPG TRADING</h3>
            <div class="meta">Weekly Sales Report</div>
            <div class="meta">Period: <?php echo htmlspecialchars($start); ?> to <?php echo htmlspecialchars($end); ?></div>
          </div>
          <?php endif; ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped text-center">
              <thead class="thead-dark">
                <tr>
                  <th>Order ID</th>
                  <th>Date</th>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Unit Price</th>
                  <th>Add. Fee</th>
                  <th>Total</th>
                  <th>Payment</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r['invoice_id']); ?></td>
                  <td><?php echo htmlspecialchars($r['order_date']); ?></td>
                  <td><?php echo htmlspecialchars($r['product_name']); ?></td>
                  <td><?php echo htmlspecialchars($r['qty']); ?></td>
                  <td><?php echo number_format($r['rate'],2); ?></td>
                  <td><?php echo number_format($r['additional_fee'],2); ?></td>
                  <td><?php echo number_format($r['line_total'],2); ?></td>

                  <!-- Payment Color -->
                  <td>
                    <?php 
                      $paymentClass = '';
                      if (strtolower($r['payment_type']) == 'cash') $paymentClass = 'payment-cash';
                      elseif (strtolower($r['payment_type']) == 'check') $paymentClass = 'payment-check';
                    ?>
                    <span class="badge-status <?php echo $paymentClass; ?>">
                      <?php echo htmlspecialchars($r['payment_type']); ?>
                    </span>
                  </td>

                  <!-- Status Color -->
                  <td>
                    <?php
                      $st = 'N/A';
                      $statusClass = '';
                      $inv = $pdo->prepare('SELECT paid, total FROM tbl_invoice WHERE invoice_id = :id LIMIT 1');
                      $inv->bindValue(':id', $r['invoice_id'], PDO::PARAM_INT);
                      $inv->execute();
                      $invRow = $inv->fetch(PDO::FETCH_ASSOC);
                      if ($invRow) {
                        $p = (float)$invRow['paid'];
                        $t = (float)$invRow['total'];
                        if ($p >= $t - 0.01) {
                          $st = 'Paid';
                          $statusClass = 'status-paid';
                        } elseif ($p > 0) {
                          $st = 'Partial';
                          $statusClass = 'status-partial';
                        } else {
                          $st = 'Unpaid';
                          $statusClass = 'status-unpaid';
                        }
                      }
                    ?>
                    <span class="badge-status <?php echo $statusClass; ?>"><?php echo $st; ?></span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <hr>
          <div>
            <strong>Total Orders:</strong> <?php echo $totalOrders; ?> <br>
            <strong>Total LPG Sold (qty):</strong> <?php echo $totalLpgSold; ?> <br>
            <strong>Total Additional Fees:</strong> <?php echo number_format($totalAdditionalFees,2); ?> <br>
            <strong>Total Sales (items):</strong> <?php echo number_format($totalSales,2); ?> <br>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once 'footer.php'; ?>

<?php if ($isPrint): ?>
  <script>
    window.addEventListener('load', function(){ try { window.print(); } catch(e) {} });
  </script>
  <style>
    .main-header, .main-sidebar, .main-footer, .breadcrumb, .card-header form { display: none !important; }
    .content-wrapper { margin: 0; padding: 10px; }
    .table th, .table td { font-size: 12px; }
  </style>
<?php endif; ?>