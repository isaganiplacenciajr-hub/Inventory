<?php
include_once 'connectdb.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;

if (!$start || !$end) {
    echo "<p>Missing start or end date.</p>";
    exit;
}

try {
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $d2->setTime(23,59,59);
    $interval = $d1->diff($d2);
    $days = (int)$interval->format('%a') + 1;

    if ($days === 1) {
        $selectLabel = "DATE_FORMAT(i.order_date, '%Y-%m-%d %H:00')";
        $groupBy = "DATE_FORMAT(i.order_date, '%Y-%m-%d %H')";
        $title = 'Hourly Sales Report';
    } elseif ($days <= 31) {
        $selectLabel = "DATE(i.order_date)";
        $groupBy = "DATE(i.order_date)";
        $title = 'Daily Sales Report';
    } elseif ($days <= 365) {
        $selectLabel = "DATE_FORMAT(i.order_date, '%Y-%m')";
        $groupBy = "DATE_FORMAT(i.order_date, '%Y-%m')";
        $title = 'Monthly Sales Report';
    } else {
        $selectLabel = "YEAR(i.order_date)";
        $groupBy = "YEAR(i.order_date)";
        $title = 'Yearly Sales Report';
    }

    $sql = "SELECT {$selectLabel} AS period_label, COALESCE(SUM(d.saleprice),0) AS total_sales, COUNT(DISTINCT i.invoice_id) AS orders_count
            FROM tbl_invoice i
            JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id
            WHERE i.status = 'Complete' AND i.order_date BETWEEN :start AND :end
            GROUP BY {$groupBy}
            ORDER BY MIN(i.order_date) ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start', $d1->format('Y-m-d 00:00:00'));
    $stmt->bindValue(':end', $d2->format('Y-m-d H:i:s'));
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grand = 0.0;
    $totalOrders = 0;
    foreach ($rows as $r) { $grand += (float)$r['total_sales']; $totalOrders += (int)$r['orders_count']; }

} catch (Exception $e) {
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars($title); ?></title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 14px; color: #111; }
    .header { text-align: center; margin-bottom: 12px; }
    .meta { text-align: center; margin-bottom: 8px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background:#f4f4f4; }
    .totals { margin-top: 12px; font-weight: 700; }
    @media print {
      nav, .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="header">
    <h2>SPM LPG TRADING</h2>
    <div class="meta"><?php echo htmlspecialchars($title); ?></div>
    <div class="meta">Period: <?php echo htmlspecialchars($d1->format('Y-m-d')) . ' to ' . htmlspecialchars($d2->format('Y-m-d')); ?></div>
  </div>

  <?php if (empty($rows)): ?>
    <p>No sales data for selected date range</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Period</th><th>Orders</th><th>Total Sales (₱)</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['period_label']); ?></td>
          <td style="text-align:center"><?php echo (int)$r['orders_count']; ?></td>
          <td style="text-align:right"><?php echo number_format($r['total_sales'],2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals">
      Total Orders: <?php echo $totalOrders; ?> &nbsp;&nbsp; Grand Total: ₱<?php echo number_format($grand,2); ?>
    </div>
  <?php endif; ?>

  <script>
    window.addEventListener('load', function(){
      try { window.print(); } catch(e) {}
    });
  </script>
</body>
</html>
