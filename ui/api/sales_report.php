<?php
include_once __DIR__ . '/../connectdb.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;
$compareStart = isset($_GET['compare_start']) ? $_GET['compare_start'] : null;
$compareEnd = isset($_GET['compare_end']) ? $_GET['compare_end'] : null;
// view can be 'period' (default) or 'items'
$view = isset($_GET['view']) ? $_GET['view'] : 'period';

if (!$start || !$end) {
    echo json_encode(['success' => false, 'message' => 'Missing date range']);
    exit;
}

try {
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $d2->setTime(23,59,59);
    $interval = $d1->diff($d2);
    $days = (int)$interval->format('%a') + 1; // inclusive

    // determine grouping and label generator (period view)
    if ($days === 1) {
        $groupType = 'hour';
        $labelFormat = 'Y-m-d H:00';
    } elseif ($days <= 31) {
        $groupType = 'day';
        $labelFormat = 'Y-m-d';
    } elseif ($days <= 365) {
        $groupType = 'month';
        $labelFormat = 'Y-m';
    } else {
        $groupType = 'year';
        $labelFormat = 'Y';
    }

    // If view is items, return aggregation by product instead
    if ($view === 'items') {
        $sql = "SELECT d.product_id, d.product_name, COALESCE(SUM(d.qty),0) AS qty_sold, COALESCE(SUM(d.saleprice),0) AS total_sales, COUNT(DISTINCT i.invoice_id) AS orders_count
                FROM tbl_invoice i
                JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id
                WHERE i.status = 'Complete' AND i.order_date BETWEEN :start AND :end
                GROUP BY d.product_id, d.product_name
                ORDER BY total_sales DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start', $d1->format('Y-m-d 00:00:00'));
        $stmt->bindValue(':end', $d2->format('Y-m-d H:i:s'));
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // totals
        $dataSum = 0.0;
        $qtySum = 0;
        foreach ($items as $it) { $dataSum += (float)$it['total_sales']; $qtySum += (int)$it['qty_sold']; }

        echo json_encode([
            'success' => true,
            'view' => 'items',
            'rows' => $items,
            'grand_total' => $dataSum,
            'total_qty' => $qtySum,
            'total_orders' => count($items) ? array_sum(array_column($items,'orders_count')) : 0,
        ]);
        exit;
    }

    // If view is transactions, return full list of invoices in range
    if ($view === 'transactions') {
        // Return only completed invoices to match totals shown in the report
        $tstmt = $pdo->prepare("SELECT invoice_id, customer_name, total, DATE_FORMAT(order_date, '%Y-%m-%d %H:%i') AS order_date, status FROM tbl_invoice WHERE status = 'Complete' AND order_date BETWEEN :start AND :end ORDER BY order_date ASC");
        $tstmt->bindValue(':start', $d1->format('Y-m-d 00:00:00'));
        $tstmt->bindValue(':end', $d2->format('Y-m-d H:i:s'));
        $tstmt->execute();
        $transactions = $tstmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'transactions' => $transactions]);
        exit;
    }

    // helper: build full series labels between two dates based on groupType
    $labels = [];
    $periodStart = clone $d1;
    if ($groupType === 'hour') {
        for ($h = 0; $h < 24; $h++) {
            $dt = (clone $d1)->setTime($h, 0, 0);
            $labels[] = $dt->format($labelFormat);
        }
    } elseif ($groupType === 'day') {
        $cur = clone $d1;
        while ($cur <= $d2) {
            $labels[] = $cur->format($labelFormat);
            $cur->modify('+1 day');
        }
    } elseif ($groupType === 'month') {
        $cur = (clone $d1)->modify('first day of this month')->setTime(0,0,0);
        $endMonth = (clone $d2)->modify('first day of this month')->setTime(0,0,0);
        while ($cur <= $endMonth) {
            $labels[] = $cur->format($labelFormat);
            $cur->modify('+1 month');
        }
    } else { // year
        $cur = (clone $d1)->setDate((int)$d1->format('Y'),1,1)->setTime(0,0,0);
        $endYear = (clone $d2)->setDate((int)$d2->format('Y'),1,1)->setTime(0,0,0);
        while ($cur <= $endYear) {
            $labels[] = $cur->format($labelFormat);
            $cur->modify('+1 year');
        }
    }

    // build SQL grouping expression matching labelFormat
    switch ($groupType) {
        case 'hour':
            $selectLabel = "DATE_FORMAT(i.order_date, '%Y-%m-%d %H:00')";
            $groupExpr = "DATE_FORMAT(i.order_date, '%Y-%m-%d %H:00')";
            break;
        case 'day':
            $selectLabel = "DATE(i.order_date)";
            $groupExpr = "DATE(i.order_date)";
            break;
        case 'month':
            $selectLabel = "DATE_FORMAT(i.order_date, '%Y-%m')";
            $groupExpr = "DATE_FORMAT(i.order_date, '%Y-%m')";
            break;
        default:
            $selectLabel = "YEAR(i.order_date)";
            $groupExpr = "YEAR(i.order_date)";
            break;
    }

    $sql = "SELECT {$selectLabel} AS period_label, COALESCE(SUM(d.saleprice),0) AS total_sales, COUNT(DISTINCT i.invoice_id) AS orders_count
            FROM tbl_invoice i
            JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id
            WHERE i.status = 'Complete' AND i.order_date BETWEEN :start AND :end
            GROUP BY {$groupExpr}
            ORDER BY MIN(i.order_date) ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start', $d1->format('Y-m-d 00:00:00'));
    $stmt->bindValue(':end', $d2->format('Y-m-d H:i:s'));
    $stmt->execute();
    $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // map raw rows to labels and fill zeros
    $map = [];
    foreach ($rawRows as $r) {
        $map[$r['period_label']] = [
            'total_sales' => (float)$r['total_sales'],
            'orders_count' => (int)$r['orders_count']
        ];
    }

    $rows = [];
    $dataSum = 0.0;
    $ordersSum = 0;
    foreach ($labels as $lbl) {
        $val = isset($map[$lbl]) ? $map[$lbl]['total_sales'] : 0.0;
        $oc = isset($map[$lbl]) ? $map[$lbl]['orders_count'] : 0;
        $rows[] = ['period_label' => $lbl, 'total_sales' => $val, 'orders_count' => $oc];
        $dataSum += $val;
        $ordersSum += $oc;
    }

    // total orders (distinct) for range
    $ordStmt = $pdo->prepare("SELECT COUNT(DISTINCT invoice_id) FROM tbl_invoice WHERE status = 'Complete' AND order_date BETWEEN :start AND :end");
    $ordStmt->bindValue(':start', $d1->format('Y-m-d 00:00:00'));
    $ordStmt->bindValue(':end', $d2->format('Y-m-d H:i:s'));
    $ordStmt->execute();
    $totalOrders = (int)$ordStmt->fetchColumn();

    // sample up to 7 individual orders for clarity
    $sampleOrders = [];
    try {
        $sstmt = $pdo->prepare("SELECT invoice_id, customer_name, total, DATE_FORMAT(order_date, '%Y-%m-%d') AS order_date
                     FROM tbl_invoice
                     WHERE status = 'Complete' AND order_date BETWEEN :start AND :end
                     ORDER BY order_date ASC");
        $sstmt->bindValue(':start', $d1->format('Y-m-d 00:00:00'));
        $sstmt->bindValue(':end', $d2->format('Y-m-d H:i:s'));
        $sstmt->execute();
        $sampleOrders = $sstmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // ignore, we'll return empty sample
        $sampleOrders = [];
    }

    // if compare range provided, compute separate series (no alignment)
    $compareRows = [];
    if ($compareStart && $compareEnd) {
        try {
            $cd1 = new DateTime($compareStart);
            $cd2 = new DateTime($compareEnd);
            $cd2->setTime(23,59,59);
            $cstmt = $pdo->prepare("SELECT {$selectLabel} AS period_label, COALESCE(SUM(d.saleprice),0) AS total_sales FROM tbl_invoice i JOIN tbl_invoice_details d ON i.invoice_id = d.invoice_id WHERE i.status = 'Complete' AND i.order_date BETWEEN :cstart AND :cend GROUP BY {$groupExpr} ORDER BY MIN(i.order_date) ASC");
            $cstmt->bindValue(':cstart', $cd1->format('Y-m-d 00:00:00'));
            $cstmt->bindValue(':cend', $cd2->format('Y-m-d H:i:s'));
            $cstmt->execute();
            $compareRows = $cstmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // ignore compare errors
            $compareRows = [];
        }
    }

    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'labels' => $labels,
        'data_sum' => $dataSum,
        'grand_total' => $dataSum,
        'total_orders' => $totalOrders,
        'grouping' => $groupType,
        'compare_rows' => $compareRows,
        'sample_orders' => $sampleOrders,
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

?>
