<?php
include_once 'connectdb.php';
session_start();
include_once 'header.php';

// detect print mode
$isPrint = (isset($_GET['print']) && $_GET['print'] == 1);

// Determine month range
if (isset($_GET['month']) && isset($_GET['year'])) {
  $month = str_pad((int)$_GET['month'], 2, '0', STR_PAD_LEFT);
  $year = (int)$_GET['year'];
  $start = "$year-$month-01";
  $end = date('Y-m-t', strtotime($start));
} else {
  $start = date('Y-m-01');
  $end = date('Y-m-t');
}

// build print URL preserving query params
$printUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['print' => 1]));

// Fetch data
$sql = "SELECT i.invoice_id, i.order_date, d.product_name, d.qty, d.rate, d.saleprice AS line_total, 
        COALESCE(p.additionalfee,0) AS additional_fee, i.payment_type 
        FROM tbl_invoice i 
        JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id 
        LEFT JOIN tbl_product p ON d.product_id = p.pid 
        WHERE DATE(i.order_date) BETWEEN :start AND :end 
        ORDER BY i.order_date, i.invoice_id";
$q = $pdo->prepare($sql);
$q->bindParam(':start', $start);
$q->bindParam(':end', $end);
$q->execute();
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

// Totals
$totalOrders = $pdo->query("SELECT COUNT(DISTINCT invoice_id) FROM tbl_invoice 
  WHERE DATE(order_date) BETWEEN '$start' AND '$end'")->fetchColumn();

$totalLpgSold = 0;
$totalAdditionalFees = 0.0;
foreach ($rows as $r) { 
  $totalLpgSold += (int)$r['qty']; 
  $totalAdditionalFees += (float)$r['additional_fee'] * (int)$r['qty'];
}

$totalSales = $pdo->query("SELECT COALESCE(SUM(d.saleprice),0) 
  FROM tbl_invoice i 
  JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id 
  WHERE DATE(i.order_date) BETWEEN '$start' AND '$end'")->fetchColumn();

$grandTotal = $totalSales + $totalAdditionalFees;
?>

<style>
.badge {
  padding: 5px 10px;
  border-radius: 8px;
  color: white;
  font-size: 13px;
}
.badge-paid { background-color: #28a745; }     /* Green */
.badge-partial { background-color: #ffc107; }  /* Yellow */
.badge-unpaid { background-color: #dc3545; }   /* Red */
.badge-cash { background-color: #007bff; }     /* Blue */
.badge-unknown { background-color: #6c757d; }  /* Gray */
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Monthly Sales Report</h1>
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
            <label class="mr-2">Month</label>
            <select name="month" class="form-control mr-2">
              <?php for($m=1;$m<=12;$m++): $mm = str_pad($m,2,'0',STR_PAD_LEFT); ?>
                <option value="<?php echo $m; ?>" <?php if(date('m',strtotime($start))===$mm) echo 'selected'; ?>>
                  <?php echo date('F', mktime(0,0,0,$m,1)); ?>
                </option>
              <?php endfor; ?>
            </select>
            <label class="mr-2">Year</label>
            <input type="number" name="year" class="form-control mr-2" value="<?php echo date('Y', strtotime($start)); ?>">
            <button class="btn btn-primary" type="submit">Show</button>
            <a class="btn btn-secondary ml-2" href="sales_report_monthly.php">This Month</a>
            <a class="btn btn-success ml-2" href="<?php echo htmlspecialchars($printUrl); ?>" target="_blank">Print</a>
          </form>
        </div>

        <div class="card-body">
          <?php if (!empty($isPrint)): ?>
          <div class="receipt" style="text-align:center;margin-bottom:12px;">
            <h3>SPM LPG TRADING</h3>
            <div class="meta">Monthly Sales Report</div>
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
                <?php foreach ($rows as $r): 
                  $unitPrice = ((float)$r['qty']>0) ? ((float)$r['line_total']/(float)$r['qty']) : (float)$r['rate'];
                  $inv = $pdo->prepare('SELECT paid, due FROM tbl_invoice WHERE invoice_id = :id LIMIT 1');
                  $inv->bindValue(':id', $r['invoice_id'], PDO::PARAM_INT);
                  $inv->execute();
                  $invRow = $inv->fetch(PDO::FETCH_ASSOC);
                  if ($invRow) {
                    if ((float)$invRow['due'] <= 0) $status = 'Paid';
                    elseif ((float)$invRow['paid'] > 0) $status = 'Partial';
                    else $status = 'Unpaid';
                  } else $status = 'Unknown';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($r['invoice_id']); ?></td>
                  <td><?php echo htmlspecialchars($r['order_date']); ?></td>
                  <td><?php echo htmlspecialchars($r['product_name']); ?></td>
                  <td><?php echo htmlspecialchars($r['qty']); ?></td>
                  <td>₱ <?php echo number_format($unitPrice,2); ?></td>
                  <td>₱ <?php echo number_format($r['additional_fee'] * $r['qty'],2); ?></td>
                  <td>₱ <?php echo number_format($r['line_total'],2); ?></td>
                  <td>
                    <span class="badge <?php echo strtolower($r['payment_type'])=='cash' ? 'badge-cash' : 'badge-unknown'; ?>">
                      <?php echo htmlspecialchars($r['payment_type']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge 
                      <?php echo ($status=='Paid')?'badge-paid':(($status=='Partial')?'badge-partial':(($status=='Unpaid')?'badge-unpaid':'badge-unknown')); ?>">
                      <?php echo $status; ?>
                    </span>
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
            <strong>Total Additional Fees:</strong> ₱ <?php echo number_format($totalAdditionalFees,2); ?> <br>
            <strong>Total Sales (items):</strong> ₱ <?php echo number_format($totalSales,2); ?> <br>
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
