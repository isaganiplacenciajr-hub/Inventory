<?php
include_once 'connectdb.php';
session_start();

if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == '' || $_SESSION['role'] == 'Admin') {
    header('location:../index.php');
    exit;
}

include_once 'headeruser.php';

$today = date('Y-m-d');
$userId = $_SESSION['userid'];

$pendingCount = 0;
$completedCount = 0;
$rejectedCount = 0;
$todayOrders = 0;
$todaySales = 0.00;
$totalProducts = 0;
$tankDeposits = 0.00;
$branchOrdersCount = ['Matain' => 0, 'Sawmill' => 0, 'San Isidro' => 0];
$recentTransactions = [];
$lowStock = [];
$stockNotif = [];

try {
    $stmt = $pdo->prepare("SELECT 
      SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
      SUM(CASE WHEN status = 'Complete' THEN 1 ELSE 0 END) AS complete,
      SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
      FROM tbl_invoice
      WHERE (created_by = :uid OR created_by_id = :uid) AND DATE(order_date) = :today");
    $stmt->execute([':uid' => $userId, ':today' => $today]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    $pendingCount = (int)($totals['pending'] ?? 0);
    $completedCount = (int)($totals['complete'] ?? 0);
    $rejectedCount = (int)($totals['rejected'] ?? 0);

    $stmt = $pdo->prepare("SELECT SUM(total - COALESCE(deposit,0)) as sales, COUNT(DISTINCT invoice_id) as orders FROM tbl_invoice WHERE (created_by = :uid OR created_by_id = :uid) AND DATE(order_date) = :today AND status = 'Complete'");
    $stmt->execute([':uid' => $userId, ':today' => $today]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $todaySales = (float)($stats['sales'] ?? 0);
    $todayOrders = (int)($stats['orders'] ?? 0);

    $stmt = $pdo->query('SELECT COUNT(*) FROM tbl_product');
    $totalProducts = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(NULLIF(branch,''),'Unspecified') AS branch, COUNT(*) AS cnt FROM tbl_invoice WHERE (created_by = :uid OR created_by_id = :uid) AND DATE(order_date) = :today AND status = 'Complete' GROUP BY branch");
    $stmt->execute([':uid' => $userId, ':today' => $today]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $branchName = trim($row['branch']);
        if (stripos($branchName, 'Matain') !== false) {
            $branchOrdersCount['Matain'] = (int)$row['cnt'];
        } elseif (stripos($branchName, 'Sawmill') !== false) {
            $branchOrdersCount['Sawmill'] = (int)$row['cnt'];
        } elseif (stripos($branchName, 'San Isidro') !== false) {
            $branchOrdersCount['San Isidro'] = (int)$row['cnt'];
        }
    }

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(deposit),0) as deposit_val FROM tbl_invoice WHERE (created_by = :uid OR created_by_id = :uid) AND DATE(order_date) = :today AND status = 'Complete'");
    $stmt->execute([':uid' => $userId, ':today' => $today]);
    $tankDeposits = (float)($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT invoice_id, total, status, branch, order_date FROM tbl_invoice WHERE (created_by = :uid OR created_by_id = :uid) AND DATE(order_date) = :today ORDER BY order_date DESC LIMIT 12");
    $stmt->execute([':uid' => $userId, ':today' => $today]);
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // user-specific 7-day sales graph data
    $graphStart = date('Y-m-d', strtotime('-6 days', strtotime($today)));
    $graphEnd = $today;
    $graphData = [];
    $stmt = $pdo->prepare("SELECT DATE(order_date) AS label, COALESCE(SUM(total - COALESCE(deposit,0)),0) AS sales FROM tbl_invoice WHERE (created_by = :uid OR created_by_id = :uid) AND status = 'Complete' AND DATE(order_date) BETWEEN :start AND :end GROUP BY DATE(order_date) ORDER BY DATE(order_date) ASC");
    $stmt->execute([':uid' => $userId, ':start' => $graphStart, ':end' => $graphEnd]);
    $graphRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $graphMap = [];
    $period = new DatePeriod(new DateTime($graphStart), new DateInterval('P1D'), (new DateTime($graphEnd))->modify('+1 day'));
    foreach ($period as $dt) {
        $graphMap[$dt->format('Y-m-d')] = 0;
    }
    foreach ($graphRows as $row) {
        $label = $row['label'];
        if (isset($graphMap[$label])) {
            $graphMap[$label] = (float)$row['sales'];
        }
    }
    foreach ($graphMap as $day => $amount) {
        $graphData[] = ['label' => $day, 'sales' => $amount];
    }

    $stmt = $pdo->query("SELECT category, stock FROM tbl_product WHERE stock IS NOT NULL AND stock <= 30 ORDER BY stock ASC");
    $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT category, SUM(COALESCE(stock,0)) AS total_stock FROM tbl_product GROUP BY category ORDER BY category ASC");
    $stockNotif = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    // keep defaults
}

function stockStatus($qty) {
    if ($qty == 0) {
        return ['red', 'OUT OF STOCK (RE-STOCK)'];
    } elseif ($qty <= 15) {
        return ['orange', 'LOW (RE-STOCK)'];
    } elseif ($qty <= 30) {
        return ['green', 'GOOD STOCK'];
    }
    return ['blue', 'HIGH STOCK'];
}

?>

<div class="content-wrapper">
    <div class="card bg-primary rounded-0">
        <div class="card-body text-center py-2">
            <h5 class="m-0 text-white">Welcome back, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b>!</h5>
        </div>
    </div>

    <style>
    .card-custom { border-radius: 14px; box-shadow: 0 10px 20px rgba(0,0,0,.08); transition: transform .25s ease, box-shadow .25s ease; }
    .card-custom:hover { transform: translateY(-3px); box-shadow: 0 16px 30px rgba(0,0,0,.15); }
    .summary-icon { font-size: 1.55rem; opacity: .85; }
    .h-100 { min-height: 220px; }
    .bg-soft-blue { background: rgba(0,123,255,.1); }
    .bg-soft-green { background: rgba(40,167,69,.1); }
    .bg-soft-yellow { background: rgba(255,193,7,.1); }
    .bg-soft-red { background: rgba(220,53,69,.1); }
    .table-hover tbody tr:hover { background-color: #f4f6f9; cursor: pointer; }
    .history-card { overflow: hidden; }
    .custom-badge { border-radius: 999px; padding: .33rem .65rem; font-size: .78rem; }
    @media (max-width: 991px) { .h-100 { min-height: auto; } }
    </style>

    <div class="content mt-3">
        <div class="container-fluid">

            <div class="row mb-3">
                <div class="col-lg-3 col-md-6 col-12 mb-3">
                    <div class="card card-custom h-100 border-0" onclick="window.location.href='userorderlist.php?date=<?php echo $today; ?>'" style="cursor:pointer;">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 text-primary bg-soft-blue p-3 rounded-circle"><i class="fas fa-chart-line summary-icon text-primary"></i></div>
                            <div>
                                <div class="text-uppercase text-muted" style="font-size:.8rem;">Today's Sales</div>
                                <div class="h2 mb-0">₱<?php echo number_format($todaySales,2); ?></div>
                                <small class="text-muted">Completed orders amount today</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12 mb-3">
                    <div class="card card-custom h-100 border-0" onclick="window.location.href='userorderlist.php?status=Pending'" style="cursor:pointer;">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 text-warning bg-soft-yellow p-3 rounded-circle"><i class="fas fa-clock summary-icon text-warning"></i></div>
                            <div>
                                <div class="text-uppercase text-muted" style="font-size:.8rem;">Pending Orders</div>
                                <div class="h2 mb-0"><?php echo $pendingCount; ?></div>
                                <small class="text-muted">Orders waiting confirmation</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12 mb-3">
                    <div class="card card-custom h-100 border-0" onclick="window.location.href='userorderlist.php?status=Complete'" style="cursor:pointer;">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 text-success bg-soft-green p-3 rounded-circle"><i class="fas fa-check-circle summary-icon text-success"></i></div>
                            <div>
                                <div class="text-uppercase text-muted" style="font-size:.8rem;">Completed Orders</div>
                                <div class="h2 mb-0"><?php echo $completedCount; ?></div>
                                <small class="text-muted">Orders fulfilled today</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12 mb-3">
                    <div class="card card-custom h-100 border-0" onclick="window.location.href='userorderlist.php?status=Rejected'" style="cursor:pointer;">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 text-danger bg-soft-red p-3 rounded-circle"><i class="fas fa-times-circle summary-icon text-danger"></i></div>
                            <div>
                                <div class="text-uppercase text-muted" style="font-size:.8rem;">Rejected Orders</div>
                                <div class="h2 mb-0"><?php echo $rejectedCount; ?></div>
                                <small class="text-muted">Orders rejected or canceled</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-custom border-0">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Today's Orders by Branch</h5>
                        </div>
                        <div class="card-body py-3">
                            <div class="row text-center">
                                <?php foreach (['Matain' => 'bg-light', 'Sawmill' => 'bg-light', 'San Isidro' => 'bg-light'] as $branch => $bg): ?>
                                    <div class="col-lg-4 col-md-4 col-12 mb-2">
                                        <div class="card card-custom h-100 border-0" onclick="window.location.href='userorderlist.php?status=Complete&date=<?php echo $today; ?>&branch=<?php echo urlencode($branch); ?>';" style="cursor:pointer;">
                                            <div class="card-body p-3">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($branch); ?></h6>
                                                <div class="h2 mb-1"><?php echo number_format($branchOrdersCount[$branch] ?? 0); ?></div>
                                                <small class="text-muted">Today's orders</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 col-12 mb-3">
                    <div class="card card-custom h-100 border-0" onclick="window.location.href='productlist.php';" style="cursor:pointer;">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 text-info bg-soft-blue p-3 rounded-circle"><i class="fas fa-box summary-icon text-info"></i></div>
                            <div>
                                <div class="text-uppercase text-muted" style="font-size:.8rem;">Total Products</div>
                                <div class="h2 mb-0"><?php echo number_format($totalProducts); ?></div>
                                <small class="text-muted">All product items</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12 mb-3">
                    <div class="card card-custom h-100 border-0" onclick="window.location.href='userorderlist.php?date=<?php echo $today; ?>';" style="cursor:pointer;">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 text-primary bg-soft-blue p-3 rounded-circle"><i class="fas fa-shopping-cart summary-icon text-primary"></i></div>
                            <div>
                                <div class="text-uppercase text-muted" style="font-size:.8rem;">Today's Orders</div>
                                <div class="h2 mb-0"><?php echo number_format($todayOrders); ?></div>
                                <small class="text-muted">Unique orders today</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12 mb-3">
                    <div class="card card-custom h-100 border-0" onclick="window.location.href='userorderlist.php?status=Complete&date=<?php echo $today; ?>';" style="cursor:pointer;">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 text-success bg-soft-green p-3 rounded-circle"><i class="fas fa-dollar-sign summary-icon text-success"></i></div>
                            <div>
                                <div class="text-uppercase text-muted" style="font-size:.8rem;">Today's Sales</div>
                                <div class="h2 mb-0">₱<?php echo number_format($todaySales,2); ?></div>
                                <small class="time">Total sales amount</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-12 mb-3">
                    <div class="card card-custom h-100 border-0" onclick="window.location.href='userorderlist.php?status=Complete&deposit_min=1&date=<?php echo $today; ?>';" style="cursor:pointer;">
                        <div class="card-body d-flex align-items-center">
                            <div class="mr-3 text-warning bg-soft-yellow p-3 rounded-circle"><i class="fas fa-gas-pump summary-icon text-warning"></i></div>
                            <div>
                                <div class="text-uppercase text-muted" style="font-size:.8rem;">Tank Deposits</div>
                                <div class="h2 mb-0">₱<?php echo number_format($tankDeposits,2); ?></div>
                                <small class="text-muted">11kg deposits today</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-custom border-0 history-card">
                        <div class="card-header bg-info text-white"><h5 class="m-0">Today's Recent Transactions</h5></div>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Branch</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentTransactions)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No transactions today</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentTransactions as $t): ?>
                                            <tr class="recent-transaction-row" onclick="window.location.href='view_invoice.php?invoice_id=<?php echo urlencode($t['invoice_id']); ?>';">
                                                <td><?php echo htmlspecialchars($t['invoice_id']); ?></td>
                                                <td><?php echo htmlspecialchars($t['branch'] ?? '-'); ?></td>
                                                <td>₱<?php echo number_format((float)$t['total'],2); ?></td>
                                                <td><?php echo htmlspecialchars($t['status']); ?></td>
                                                <td><?php echo htmlspecialchars(date('g:i A', strtotime($t['order_date']))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-6 col-12 mb-3">
                    <div class="card card-custom border-0">
                        <div class="card-header bg-danger text-white"><h5 class="m-0">Low Stock Alert</h5></div>
                        <div class="card-body table-responsive p-0" style="max-height: 250px; overflow-y:auto;">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Category</th><th>Remaining</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php if (empty($lowStock)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No low stock items</td></tr>
                                <?php else: ?>
                                    <?php foreach ($lowStock as $p): $stockInfo = stockStatus((int)$p['stock']); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($p['category']); ?></td>
                                            <td><?php echo (int)$p['stock']; ?></td>
                                            <td><span class="badge badge-pill" style="background-color:<?php echo $stockInfo[0]; ?>; color:#fff;"><?php echo htmlspecialchars($stockInfo[1]); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-12 mb-3">
                    <div class="card card-custom border-0">
                        <div class="card-header bg-success text-white"><h5 class="m-0">Top Selling Products Today</h5></div>
                        <div class="card-body">
                            <?php
                                $stmt = $pdo->prepare("SELECT p.category, SUM(d.qty) AS sold_qty FROM tbl_invoice_details d JOIN tbl_invoice inv ON d.invoice_id = inv.invoice_id JOIN tbl_product p ON d.product_id = p.pid WHERE (inv.created_by = :uid OR inv.created_by_id = :uid) AND DATE(inv.order_date) = :today AND inv.status = 'Complete' GROUP BY p.category ORDER BY sold_qty DESC LIMIT 5");
                                $stmt->execute([':uid' => $userId, ':today' => $today]);
                                $bestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (empty($bestProducts)) {
                                    echo '<p class="text-muted">No sales yet</p>';
                                } else {
                                    echo '<ol class="pl-3 mb-0">';
                                    foreach ($bestProducts as $row) {
                                        echo '<li>' . htmlspecialchars($row['category']) . ' - ' . (int)$row['sold_qty'] . ' sold</li>';
                                    }
                                    echo '</ol>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
              <div class="col-12">
                <div class="card card-custom border-0">
                  <div class="card-header bg-secondary text-white d-flex align-items-center justify-content-between">
                    <h5 class="m-0" style="color:#ffc107;"><i class="fas fa-chart-area mr-2"></i>Sales Overview</h5>
                    <div>
                      <div class="btn-group btn-group-sm mr-2" role="group" aria-label="Quick Filters">
                        <button class="btn btn-outline-primary" id="filterToday">Today</button>
                        <button class="btn btn-outline-primary" id="filterWeek">This Week</button>
                        <button class="btn btn-outline-primary" id="filterMonth">This Month</button>
                        <button class="btn btn-outline-primary" id="filterYear">This Year</button>
                      </div>
                      <div class="d-inline-block">
                        <input type="date" id="startDate" class="form-control form-control-sm d-inline-block" style="width:150px;" />
                        <span class="mx-1">to</span>
                        <input type="date" id="endDate" class="form-control form-control-sm d-inline-block" style="width:150px;" />
                        <button class="btn btn-sm btn-primary ml-2" id="applyDateRange">Apply</button>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="row mb-3">
                      <div class="col-md-3 col-6">
                        <div class="small-box bg-white p-3 border">
                          <div class="inner">
                            <h4 id="totalSalesAmount">₱0.00</h4>
                            <p>Total Sales Amount</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3 col-6">
                        <div class="small-box bg-white p-3 border">
                          <div class="inner">
                            <h4 id="totalTransactions">0</h4>
                            <p>Total Transactions</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 col-12 text-right align-self-center">
                        <small class="text-muted">Graph updates automatically when filters or date range change</small>
                      </div>
                    </div>
                    <div style="position:relative;">
                      <canvas id="salesGraphCanvas" height="120"></canvas>
                      <div id="salesGraphMessage" class="text-center text-muted mt-3"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-custom border-0">
                        <div class="card-header bg-secondary text-white"><h5 class="m-0">Stock Notification</h5></div>
                        <div class="card-body table-responsive p-0" style="max-height: 240px; overflow-y:auto;">
                            <table class="table table-bordered mb-0">
                                <thead class="thead-light"><tr><th>Category</th><th>Total Stock</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php if (empty($stockNotif)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No stock data available</td></tr>
                                <?php else: ?>
                                    <?php foreach ($stockNotif as $row): $statusInfo = stockStatus((int)$row['total_stock']); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['category']); ?> KG</td>
                                            <td><?php echo number_format($row['total_stock']); ?></td>
                                            <td><span class="badge badge-pill" style="background:<?php echo $statusInfo[0]; ?>; color:#fff;"><?php echo htmlspecialchars($statusInfo[1]); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
