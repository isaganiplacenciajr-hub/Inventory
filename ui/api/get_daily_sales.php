<?php
include_once __DIR__ . '/../connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Support single date or date range via start_date and end_date
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;

// legacy 'date' param
if (isset($_GET['date']) && !$start_date && !$end_date) {
    $start_date = $_GET['date'];
    $end_date = $_GET['date'];
}

$validDate = function($d) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

if (!$start_date || !$validDate($start_date)) {
    $start_date = date('Y-m-d');
}
if (!$end_date || !$validDate($end_date)) {
    $end_date = $start_date;
}

try {
    if ($start_date === $end_date) {
        // hourly breakdown for single date
        $query = "
        SELECT 
            DATE_FORMAT(order_date, '%H:00') AS label,
            COALESCE(SUM(total - COALESCE(deposit,0)), 0) AS sales,
            COALESCE(SUM(CASE WHEN deposit > 0 THEN deposit ELSE 0 END),0) AS deposits,
            COUNT(DISTINCT invoice_id) AS transactions
        FROM tbl_invoice
        WHERE status = 'Complete' AND DATE(order_date) = :date
        GROUP BY HOUR(order_date)
        ORDER BY HOUR(order_date) ASC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':date', $start_date);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hourStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
            $hours[$hourStr] = ['sales' => 0.0, 'deposits' => 0.0, 'transactions' => 0];
        }
        $totalDeposits = 0.0;
        foreach ($results as $row) {
            $hours[$row['label']] = ['sales' => (float)$row['sales'], 'deposits' => (float)$row['deposits'], 'transactions' => (int)$row['transactions']];
        }

        $data = [];
        $totalSales = 0.0;
        $totalTransactions = 0;
        foreach ($hours as $label => $v) {
            $data[] = ['label' => $label, 'sales' => $v['sales'], 'transactions' => $v['transactions']];
            $totalSales += $v['sales'];
            $totalTransactions += $v['transactions'];
            $totalDeposits += $v['deposits'];
        }

        echo json_encode(['success' => true, 'data' => $data, 'total_sales' => $totalSales, 'total_transactions' => $totalTransactions, 'total_deposits' => $totalDeposits, 'start_date' => $start_date, 'end_date' => $end_date]);
        exit;
    }

    // date range: daily totals
    $query = "
    SELECT 
        DATE(order_date) AS label,
        COALESCE(SUM(total - COALESCE(deposit,0)), 0) AS sales,
        COALESCE(SUM(CASE WHEN deposit > 0 THEN deposit ELSE 0 END),0) AS deposits,
        COUNT(DISTINCT invoice_id) AS transactions
    FROM tbl_invoice
    WHERE status = 'Complete' AND DATE(order_date) BETWEEN :start_date AND :end_date
    GROUP BY DATE(order_date)
    ORDER BY DATE(order_date) ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':start_date', $start_date);
    $stmt->bindValue(':end_date', $end_date);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // build map for all dates in range, always include deposits
    $period = new DatePeriod(new DateTime($start_date), new DateInterval('P1D'), (new DateTime($end_date))->modify('+1 day'));
    $map = [];
    foreach ($period as $dt) {
        $lbl = $dt->format('Y-m-d');
        $map[$lbl] = ['sales' => 0.0, 'transactions' => 0, 'deposits' => 0.0];
    }
    foreach ($results as $row) {
        $map[$row['label']] = [
            'sales' => (float)$row['sales'],
            'transactions' => (int)$row['transactions'],
            'deposits' => isset($row['deposits']) ? (float)$row['deposits'] : 0.0
        ];
    }

    $data = [];
    $totalSales = 0.0;
    $totalTransactions = 0;
    $totalDeposits = 0.0;
    foreach ($map as $label => $v) {
        $data[] = ['label' => $label, 'sales' => $v['sales'], 'transactions' => $v['transactions']];
        $totalSales += $v['sales'];
        $totalTransactions += $v['transactions'];
        $totalDeposits += $v['deposits'];
    }

    echo json_encode(['success' => true, 'data' => $data, 'total_sales' => $totalSales, 'total_transactions' => $totalTransactions, 'total_deposits' => $totalDeposits, 'start_date' => $start_date, 'end_date' => $end_date]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
