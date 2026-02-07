<?php
include_once 'connectdb.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// detect print mode
$isPrint = (isset($_GET['print']) && $_GET['print'] == 1);
include_once 'header.php';

// build print URL preserving query params
$printUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['print' => 1]));

// Determine date range: default = today or from GET(date)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$start = $date;
$end = $date;

$sql = "SELECT i.invoice_id, i.order_date, d.product_name, d.qty, d.rate, d.saleprice AS line_total, 
        d.addfee AS additional_fee, i.payment_type 
        FROM tbl_invoice i 
        JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id 
        WHERE DATE(i.order_date) BETWEEN :start AND :end 
        ORDER BY i.order_date DESC, i.invoice_id DESC";
$q = $pdo->prepare($sql);
$q->bindParam(':start', $start);
$q->bindParam(':end', $end);
$q->execute();
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

// aggregates
$aggOrders = $pdo->prepare("SELECT COUNT(DISTINCT invoice_id) FROM tbl_invoice WHERE DATE(order_date) BETWEEN :start AND :end");
$aggOrders->bindParam(':start', $start);
$aggOrders->bindParam(':end', $end);
$aggOrders->execute();
$totalOrders = (int)$aggOrders->fetchColumn();

// totals
$totalLpgSold = 0;
$totalAdditionalFees = 0.0;
foreach ($rows as $r) { 
  $totalLpgSold += (int)$r['qty']; 
  $totalAdditionalFees += (float)$r['additional_fee'];
}

// Compute Grand Total (sum of line totals which already include fees)
$totalSalesStmt = $pdo->prepare("
    SELECT COALESCE(SUM(d.saleprice), 0)
    FROM tbl_invoice i
    JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id
    WHERE DATE(i.order_date) BETWEEN :start AND :end
");
$totalSalesStmt->bindParam(':start', $start);
$totalSalesStmt->bindParam(':end', $end);
$totalSalesStmt->execute();
$grandTotal = (float)$totalSalesStmt->fetchColumn();
?>

<style>
.table td, .table th {
  text-align: center;
  vertical-align: middle !important;
}

/* Receipt header */
.receipt {
  text-align: center;
  margin-bottom: 12px;
}
.receipt h3 { margin: 0; font-size: 18px; letter-spacing: 0.5px; }
.receipt .meta { font-size: 13px; color: #333; }

.badge-status {
  padding: 6px 12px;
  border-radius: 12px;
  color: #fff;
  font-weight: 600;
  display: inline-block;
  text-align: center;
  min-width: 80px;
}

/* ✅ Bright, professional color tones */
.badge-paid { background-color: #28a745; }     /* bright green */
.badge-unpaid { background-color: #dc3545; }   /* bright red */
.badge-partial { background-color: #ffc107; color: #000; } /* yellow */
.badge-cash { background-color: #007bff; }     /* bright blue */
.badge-gcash { background-color: #6610f2; }    /* violet-blue */
.badge-others { background-color: #6c757d; }   /* gray */

.card-header {
  background-color: #f8f9fa;
  font-weight: 600;
}
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Daily Sales Report</h1>
          <small>Period: <?php echo htmlspecialchars($start); ?></small>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-primary">
        <div class="card-header">
          <form class="form-inline" method="get">
            <label class="mr-2">Date</label>
            <input type="date" name="date" class="form-control mr-2" value="<?php echo htmlspecialchars($date); ?>">
            <button class="btn btn-primary" type="submit">Show</button>
            <a class="btn btn-secondary ml-2" href="sales_report_daily.php">Today</a>
            <a class="btn btn-success ml-2" href="<?php echo htmlspecialchars($printUrl); ?>" target="_blank">Print</a>
          </form>
        </div>

        <div class="card-body">
                  <?php if (!empty($isPrint)): ?>
                  <div class="receipt">
                    <h3>SPM LPG TRADING</h3>
                    <div class="meta">Daily Sales Report</div>
                    <div class="meta">Date: <?php echo htmlspecialchars($start); ?></div>
                  </div>
                  <?php endif; ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
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
                  <td>₱<?php echo number_format($r['rate'],2); ?></td>
                  <td>₱<?php echo number_format($r['additional_fee'],2); ?></td>
                  <td>₱<?php echo number_format($r['line_total'],2); ?></td>

                  <td>
                    <?php 
                      $paymentType = strtolower($r['payment_type']);
                      $badgeClass = 'badge-others';
                      if ($paymentType == 'cash') $badgeClass = 'badge-cash';
                      elseif ($paymentType == 'gcash') $badgeClass = 'badge-gcash';
                      echo '<span class="badge-status ' . $badgeClass . '">' . htmlspecialchars($r['payment_type']) . '</span>';
                    ?>
                  </td>
                  <td>
                    <?php
                      $st = 'N/A';
                      $inv = $pdo->prepare('SELECT paid, due FROM tbl_invoice WHERE invoice_id = :id LIMIT 1');
                      $inv->bindValue(':id', $r['invoice_id'], PDO::PARAM_INT);
                      $inv->execute();
                      $invRow = $inv->fetch(PDO::FETCH_ASSOC);
                      if ($invRow) {
                        if ((float)$invRow['due'] <= 0) {
                          $st = '<span class="badge-status badge-paid">Paid</span>';
                        } elseif ((float)$invRow['paid'] > 0) {
                          $st = '<span class="badge-status badge-partial">Partial</span>';
                        } else {
                          $st = '<span class="badge-status badge-unpaid">Unpaid</span>';
                        }
                      }
                      echo $st;
                    ?>
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
            <strong>Total Additional Fees:</strong> ₱<?php echo number_format($totalAdditionalFees,2); ?> <br>
            <strong>Grand Total (Sales):</strong> ₱<?php echo number_format($grandTotal,2); ?> <br>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once 'footer.php'; ?>

<?php if ($isPrint): ?>
  <script>
    // auto print when opened in print mode
    window.addEventListener('load', function(){
      try { window.print(); } catch(e) {}
    });
  </script>
  <style>
    /* Hide UI chrome for printing when opened in print mode */
    .main-header, .main-sidebar, .main-footer, .breadcrumb, .card-header form { display: none !important; }
    .content-wrapper { margin: 0; padding: 10px; }
    .table th, .table td { font-size: 12px; }
  </style>
<?php endif; ?>