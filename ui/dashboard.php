<?php
include_once 'connectdb.php';
session_start();

if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "") {
    header('location:../index.php');
    exit;
}

include_once "header.php";

/* ===========================
   STOCK NOTIFICATION LOGIC
   =========================== */
$stockNotif = [];
try {
    $stmt = $pdo->query("
        SELECT 
            category,
            SUM(COALESCE(stock,0)) AS total_stock
        FROM tbl_product
        GROUP BY category
        ORDER BY category ASC
    ");
    $stockNotif = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $stockNotif = [];
}

/* ===== FIXED STOCK STATUS FUNCTION ===== */
function stockStatus($qty) {
    if ($qty == 0) {
        return ['red', 'OUT OF STOCK (RE-STOCK)'];
    } elseif ($qty <= 15) {
        return ['orange', 'LOW (RE-STOCK)'];
    } elseif ($qty <= 30) {
        return ['green', 'GOOD STOCK'];
    } else { // 31+
        return ['blue', 'HIGH STOCK'];
    }
}

// additional dashboard datasets
$today = date('Y-m-d');
$recentTrans = [];
$lowStock = [];
$branchOrdersCount = [
    'Matain' => 0,
    'Sawmil' => 0,
    'San Isidro' => 0
];
try {
    $stmt = $pdo->prepare("SELECT branch, COUNT(*) AS cnt FROM tbl_invoice WHERE DATE(order_date) = ? GROUP BY branch");
    $stmt->execute([$today]);
    $branchRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($branchRows as $row) {
        $branch = trim((string)($row['branch'] ?? ''));
        $cnt = (int)($row['cnt'] ?? 0);
        if ($branch === '') {
            continue;
        }
        if (stripos($branch, 'Matain') !== false) {
            $branchOrdersCount['Matain'] += $cnt;
        } elseif (stripos($branch, 'Sawmill') !== false) {
            $branchOrdersCount['Sawmil'] += $cnt;
        } elseif (stripos($branch, 'San Isidro') !== false) {
            $branchOrdersCount['San Isidro'] += $cnt;
        } elseif (stripos($branch, 'Main') !== false) {
            $branchOrdersCount['Main'] += $cnt;
        }
    }

    // recent transactions (all users) for today
    $stmt = $pdo->prepare("SELECT inv.invoice_id, inv.total, inv.order_date, inv.status, inv.branch,
                           COALESCE(NULLIF(inv.created_by_name, ''), NULLIF(u.username, ''), CONCAT('User #', inv.created_by), 'Unknown') AS created_by_name,
                           COALESCE(NULLIF(inv.created_by_role, ''), NULLIF(u.role, ''), 'User') AS created_by_role
                           FROM tbl_invoice inv
                           LEFT JOIN tbl_user u ON inv.created_by = u.userid
                           WHERE DATE(inv.order_date) = ?
                           ORDER BY inv.order_date DESC
                           LIMIT 10");
    $stmt->execute([$today]);
    $recentTrans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // low stock items (<=30)
    $stmt = $pdo->query("SELECT category, stock FROM tbl_product WHERE stock IS NOT NULL AND stock <= 30 ORDER BY stock ASC");
    $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // ignore errors
}
?>

<div class="content-wrapper">

  <!-- HEADER -->
  <div class="card bg-primary rounded-0">
    <div class="card-body text-center py-2">
      <h5 class="m-0 text-white">
        Welcome — Hello Admin <b><?php echo $_SESSION['username']; ?></b>, welcome back!
      </h5>
    </div>
  </div>

  <style>
    .order-status-card {
      border: none;
      border-top: 5px solid;
      position: relative;
      overflow: hidden;
    }

    .order-status-card:hover {
      transform: translateY(-5px) !important;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .order-status-card[data-status="Complete"] {
      border-top-color: #28a745;
    }

    .order-status-card[data-status="Pending"] {
      border-top-color: #ffc107;
    }

    .order-status-card .card-body {
      padding: 1.5rem;
    }

    .order-status-card i {
      opacity: 0.8;
    }
  </style>

  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Dashboard</h1>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <div class="container-fluid">

      <!-- SUMMARY CARDS -->
      

      </div> <!-- end summary cards -->
      <!-- SALES OVERVIEW moved to bottom -->

      <!-- ORDER STATUS CARDS -->
      <div class="row">

        <!-- COMPLETE ORDERS -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-success shadow border-0 order-status-card" data-status="Complete" onclick="window.location.href='orderlist.php?status=Complete&date=<?php echo date('Y-m-d'); ?>';" style="cursor: pointer; transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-check-circle fa-2x text-success"></i></div>
              <div style="width: 100%;">
                <div style="font-weight: 600; color: #28a745;">Today's Complete Orders</div>
                <h3 class="mb-0" style="color: #28a745;">
                  <?php
                    $today = date('Y-m-d');
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_invoice WHERE status = 'Complete' AND DATE(order_date) = ?");
                    $stmt->execute([$today]);
                    $completeCount = $stmt->fetchColumn() ?? 0;
                    echo $completeCount;
                  ?>
                </h3>
                <small style="color: #666; margin-top: 5px; display: block;">Click to view today's sales</small>
              </div>
            </div>
          </div>
        </div>

        <!-- ADMIN PENDING ORDERS -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-warning shadow border-0 order-status-card" data-status="PendingAdmin" onclick="window.location.href='orderlist.php?status=Pending&role=Admin';" style="cursor: pointer; transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-user-clock fa-2x text-warning"></i></div>
              <div style="width: 100%;">
                  <div style="font-weight: 600; color: #ff0707;">Admin-Initiated Pending Orders</div>
                <h3 class="mb-0" style="color: #8f8420;">
                  <?php
                      $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_invoice inv LEFT JOIN tbl_user u ON inv.created_by = u.userid WHERE inv.status = 'Pending' AND COALESCE(u.role,'User') = 'Admin'");
                      $stmt->execute();
                    $pendingCount = $stmt->fetchColumn() ?? 0;
                    echo $pendingCount;
                  ?>
                </h3>
                <small style="color: #666; margin-top: 5px; display: block;">Click to view incomplete transactions created by admin</small>
              </div>
            </div>
          </div>
        </div>

        <!-- USER PENDING ORDERS -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-warning shadow border-0 order-status-card" data-status="PendingUser" onclick="window.location.href='admin_pending_orders.php';" style="cursor: pointer; transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-user-clock fa-2x text-warning"></i></div>
              <div style="width: 100%;">
                <div style="font-weight: 600; color: #ffc107;">Pending Orders for Admin Approval</div>
                <h3 class="mb-0" style="color: #ffc107;">
                  <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_invoice inv LEFT JOIN tbl_user u ON inv.created_by = u.userid WHERE inv.status = 'Pending' AND COALESCE(u.role,'User') != 'Admin'");
                    $stmt->execute();
                    $userPending = $stmt->fetchColumn() ?? 0;
                    echo $userPending;
                  ?>
                </h3>
                <small style="color: #666; margin-top: 5px; display: block;">Click to view and approve user-submitted orders</small>
              </div>
            </div>
          </div>
        </div>

      </div> <!-- end order status cards -->

      <!-- TODAY'S ORDERS BY BRANCH -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
              <h5 class="m-0" style="color:#ffc107;"><i class="fas fa-store mr-2"></i>Today's Orders by Branch</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <?php foreach (['Matain','Sawmil','San Isidro'] as $branchTitle): ?>
                <div class="col-lg-4 col-md-6 col-12 mb-3">
                  <div class="card border-0 shadow-sm branch-order-card" data-branch="<?php echo htmlspecialchars($branchTitle); ?>" style="cursor:pointer;">
                    <div class="card-body text-center py-4">
                      <h6><?php echo htmlspecialchars($branchTitle); ?></h6>
                      <p class="h3 mb-0"><?php echo number_format($branchOrdersCount[$branchTitle] ?? 0); ?></p>
                      <small>Today's orders</small>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SUMMARY CARDS -->
      <div class="row">

        <!-- TOTAL PRODUCT CATEGORIES -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-info shadow border-0" onclick="window.location.href='productlist.php';" style="cursor: pointer; transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-box fa-2x"></i></div>
              <div>
                <div style="color:#17a2b8;">Total Product Categories</div>
                <h3 class="mb-0">
                  <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM tbl_product");
                    echo (int)$stmt->fetchColumn();
                  ?>
                </h3>
                <small style="color: #666; margin-top: 5px; display: block;">Click to view products</small>
              </div>
            </div>
          </div>
        </div>

        <!-- TODAY'S ORDERS -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-info shadow border-0" onclick="window.location.href='sales_report.php';" style="cursor: pointer; transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-calendar-day fa-2x"></i></div>
              <div>
                <div style="color:#28a745;">Today's Orders</div>
                <h3 class="mb-0">
                  <?php
                    $today = date('Y-m-d');
                    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT invoice_id) FROM tbl_invoice WHERE DATE(order_date) = ?");
                    $stmt->execute([$today]);
                    echo (int)$stmt->fetchColumn();
                  ?>
                </h3>
                <small style="color: #666; margin-top: 5px; display: block;">Click to view today's report</small>
              </div>
            </div>
          </div>
        </div>

        <!-- TODAY'S SALES -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-info shadow border-0" onclick="window.location.href='sales_report.php';" style="cursor: pointer; transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-chart-line fa-2x"></i></div>
              <div>
                <div style="color:#dc3545;">Today's Sales</div>
                <h3 class="mb-0">
                  ₱<?php
                    $today = date('Y-m-d');
                    // Exclude deposit from sales
                    $stmt = $pdo->prepare("SELECT SUM(total - COALESCE(deposit,0)) FROM tbl_invoice WHERE DATE(order_date) = ? AND status = 'Complete'");
                    $stmt->execute([$today]);
                    echo number_format($stmt->fetchColumn() ?? 0, 2);
                  ?>
                </h3>
                <small style="color: #666; margin-top: 5px; display: block;">Click to view daily sales</small>
              </div>
            </div>
          </div>
        </div>

      </div> <!-- end summary cards -->

      <!-- TANK DEPOSIT / PROFIT / TAX CARDS -->
      <?php
        // calculate today's deposit, profit, tax
        $depositTotal = 0;
        $profitTotal = 0;
        $taxTotal = 0;
        $depositCollected = 0;
        $depositRefunded = 0;
        $balance = 0;
        try {
            // deposits collected positive and negative for refunds
            $stmt = $pdo->prepare("SELECT
                    COALESCE(SUM(CASE WHEN deposit > 0 THEN deposit ELSE 0 END),0) AS collected,
                    COALESCE(SUM(CASE WHEN deposit < 0 THEN deposit ELSE 0 END),0) AS refunded
                FROM tbl_invoice
                WHERE DATE(order_date) = ? AND status = 'Complete'");
            $stmt->execute([$today]);
            $depRow = $stmt->fetch(PDO::FETCH_ASSOC);

            $depositCollected = $depRow['collected'] ?? 0;
            $depositRefunded = $depRow['refunded'] ?? 0;
            // refunded value is negative; adding gives net balance
            $depositTotal = $depositCollected + $depositRefunded;
            $balance = $depositTotal;
            // Ensure balance is never negative or blank
            if ($balance < 0 || !$balance) {
              $balance = 0.00;
            }

            // total sales excluding deposit
            $stmt = $pdo->prepare("SELECT SUM(total - COALESCE(deposit,0)) FROM tbl_invoice WHERE DATE(order_date) = ? AND status = 'Complete'");
            $stmt->execute([$today]);
            $salesTotal = floatval($stmt->fetchColumn() ?? 0);

            // total cost of goods sold
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(d.qty * p.purchaseprice),0)
                FROM tbl_invoice_details d
                JOIN tbl_invoice inv ON d.invoice_id = inv.invoice_id
                JOIN tbl_product p ON d.product_id = p.pid
                WHERE DATE(inv.order_date) = ? AND inv.status = 'Complete'");
            $stmt->execute([$today]);
            $costTotal = floatval($stmt->fetchColumn() ?? 0);

            $profitTotal = $salesTotal - $costTotal;

            // tax 12% of sales
            $taxTotal = $salesTotal * 0.12;
            // Net profit after VAT
            $profitTotal = $profitTotal - $taxTotal;
        } catch (Throwable $e) {
            // ignore errors, defaults are zero
        }
      ?>

      <div class="row">
        <!-- TOTAL TANK DEPOSITS -->
        <div class="col-lg-4 col-12 mb-4">
          <div id="cardDeposits" class="card card-warning shadow border-0" style="cursor: pointer; transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-gas-pump fa-2x text-warning"></i></div>
              <div>
                <div style="color:#ffc107;">Total Tank Deposits (11kg)</div>
                <h3 class="mb-0">
                  ₱<?php echo number_format($balance, 2); ?>
                </h3>
                <small style="color: #666; margin-top: 5px; display: block;">Net balance (collected − refunded) – click for details</small>
              </div>
            </div>
          </div>
        </div>

        <!-- TOTAL TAX -->
        <div class="col-lg-4 col-12 mb-4">
          <div id="cardTax" class="card card-info shadow border-0" style="cursor: pointer; transition: all 0.3s ease;">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-file-invoice-dollar fa-2x text-info"></i></div>
              <div>
                <div style="color:#17a2b8;">Total Tax (BIR - 12% VAT)</div>
                <h3 class="mb-0">
                  ₱<?php echo number_format($taxTotal, 2); ?>
                </h3>
                <small style="color: #666; margin-top: 5px; display: block;">12% of today's sales – click for report</small>
              </div>
            </div>
          </div>
        </div>

          <!-- NET PROFIT CARD -->
          <div class="col-lg-4 col-12 mb-4">
            <div id="cardProfit" class="card card-success shadow border-0" style="cursor: pointer; transition: all 0.3s ease;">
              <div class="card-body d-flex align-items-center">
                <div class="mr-3"><i class="fas fa-coins fa-2x text-success"></i></div>
                <div>
                  <div style="color:#28a745;">Net Profit (after VAT)</div>
                  <h3 class="mb-0">
                    ₱<?php echo number_format($profitTotal, 2); ?>
                  </h3>
                  <small style="color: #666; margin-top: 5px; display: block;">Net profit for today – click to verify VAT (12%) deduction</small>
                </div>
              </div>
            </div>
          </div>
      </div> <!-- end deposit/profit/tax row -->

      <!-- RECENT TRANSACTIONS -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-info text-white"><h5 class="m-0">Today's Recent Transactions</h5></div>
            <div class="card-body p-2" style="max-height:250px; overflow-y:auto;">
              <style> .recent-transaction-row { cursor: pointer; } .recent-transaction-row:hover { background:#f8f9fa; } </style>
              <table class="table table-sm table-striped mb-0">
                <thead class="bg-info text-white">
                  <tr>
                    <th>Invoice</th>
                    <th>Branch</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentTrans)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No transactions today</td></tr>
                  <?php else: ?>
                    <?php foreach ($recentTrans as $t): ?>
                      <tr class="recent-transaction-row" data-invoice="<?php echo htmlspecialchars($t['invoice_id']); ?>">
                        <td><?php echo htmlspecialchars($t['invoice_id']); ?></td>
                        <td><?php echo htmlspecialchars($t['branch'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($t['created_by_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($t['created_by_role'] ?? 'User'); ?></td>
                        <td>₱<?php echo number_format($t['total'],2); ?></td>
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

      <!-- LOW STOCK & TOP SELLING -->
      <div class="row mb-4">
        <div class="col-lg-6 col-12 mb-4">
          <div class="card shadow-sm">
            <div class="card-header bg-danger text-white"><h5 class="m-0">Low Stock Alert</h5></div>
            <div class="card-body table-responsive p-0" style="max-height:250px;">
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th>Category</th>
                    <th>Remaining Stock</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($lowStock)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No low stock items</td></tr>
                  <?php else: ?>
                    <?php foreach ($lowStock as $p): ?>
                      <?php
                        $st = '';
                        if ($p['stock'] <= 10) {
                          $st = '<span class="badge badge-danger">Low Stock</span>';
                        } elseif ($p['stock'] <= 30) {
                          $st = '<span class="badge badge-warning">Medium Stock</span>';
                        } else {
                          $st = '<span class="badge badge-success">Good Stock</span>';
                        }
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars($p['category']); ?></td>
                        <td><?php echo (int)$p['stock']; ?></td>
                        <td><?php echo $st; ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-6 col-12 mb-4">
          <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><h5 class="m-0">Top Selling Products Today</h5></div>
            <div class="card-body">
              <?php
                $stmt = $pdo->prepare("SELECT p.category, SUM(d.qty) AS total_qty
                                       FROM tbl_invoice_details d
                                       JOIN tbl_invoice inv ON d.invoice_id = inv.invoice_id
                                       JOIN tbl_product p ON d.product_id = p.pid
                                       WHERE DATE(inv.order_date) = ? AND inv.status = 'Complete'
                                       GROUP BY p.category
                                       ORDER BY total_qty DESC
                                       LIMIT 5");
                $stmt->execute([$today]);
                $todaysTop = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!$todaysTop) {
                  echo '<p>No sales data today.</p>';
                } else {
                  $top = $todaysTop[0];
                  echo "<div style=\"font-size:1.25rem;font-weight:700;\">" . htmlspecialchars($top['category']) . "</div>";
                  echo "<div style=\"font-size:1rem;color:#666;margin-bottom:8px;\">Sold: " . (int)$top['total_qty'] . "</div>";
                  echo '<hr><div style="font-weight:600;">Top 3</div><ul class="pl-3">';
                  $i = 0;
                  foreach ($todaysTop as $c) {
                    if ($i++ >= 3) break;
                    echo '<li>' . htmlspecialchars($c['category']) . ' — ' . (int)$c['total_qty'] . ' pcs</li>';
                  }
                  echo '</ul>';
                }
              ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-12">
          <div class="card card-warning card-outline shadow-sm">

            <div class="card-header bg-secondary text-white">
              <h5 class="m-0">
                <i class="fas fa-bell mr-2"></i>Stock Notification
              </h5>
            </div>

            <div class="card-body table-responsive p-0">
              <table class="table table-bordered text-center">
                <thead class="thead-light">
                  <tr>
                    <th>Category</th>
                    <th>Total Stock</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>

                <?php if (empty($stockNotif)): ?>
                  <tr>
                    <td colspan="3">No stock data available</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($stockNotif as $row): ?>
                    <?php
                      [$color, $status] = stockStatus((int)$row['total_stock']);
                    ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($row['category']); ?> KG</strong></td>
                      <td><?php echo number_format($row['total_stock']); ?></td>
                      <td>
                        <span style="
                          background-color:<?php echo $color; ?>;
                          color:white;
                          padding:6px 12px;
                          border-radius:6px;
                          font-weight:600;
                        ">
                          <?php echo $status; ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>

                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div> <!-- end stock notification -->

      <!-- SALES OVERVIEW -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card shadow-sm">
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
                  <div id="boxSales" class="small-box bg-white p-3 border" style="cursor:pointer;">
                    <div class="inner">
                      <h4 id="totalSalesAmount">₱0.00</h4>
                      <p>Total Sales Amount</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-6">
                  <div id="boxTransactions" class="small-box bg-white p-3 border" style="cursor:pointer;">
                    <div class="inner">
                      <h4 id="totalTransactions">0</h4>
                      <p>Total Transactions</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-6">
                  <div id="boxDeposits" class="small-box bg-white p-3 border" style="cursor:pointer;">
                    <div class="inner">
                      <h4 id="totalTankDeposits">₱0.00</h4>
                      <p>Total Tank Deposits</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-12 text-right align-self-center">
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



    </div> <!-- end container-fluid -->
  </div> <!-- end content -->

</div> <!-- end content-wrapper -->

<!-- Branch Orders Modal -->
<div class="modal fade" id="branchOrdersModal" tabindex="-1" role="dialog" aria-labelledby="branchOrdersModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="branchOrdersModalLabel">Today's Orders by Branch</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-bordered" id="branchOrdersTable">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Date/Time</th>
                <th>Branch</th>
              </tr>
            </thead>
            <tbody id="branchOrdersTableBody">
              <tr><td colspan="6" class="text-center">No data loaded.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Recent Transaction Detail Modal -->
<div class="modal fade" id="recentTransactionModal" tabindex="-1" role="dialog" aria-labelledby="recentTransactionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="recentTransactionModalLabel">Transaction Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-bordered" id="recentTransactionDetailTable">
            <thead>
              <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Line Total</th>
              </tr>
            </thead>
            <tbody id="recentTransactionDetailBody">
              <tr><td colspan="4" class="text-center text-muted">No detail loaded.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Deposit Modal -->
<div class="modal fade" id="depositModal" tabindex="-1" role="dialog" aria-labelledby="depositModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="depositModalLabel">11kg Tank Deposit Transactions</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-inline mb-2">
          <label>Filter: </label>
          <select id="depositFilter" class="form-control ml-2">
            <option>Today</option>
            <option>Yesterday</option>
            <option>This Week</option>
            <option>This Month</option>
            <option>All Records</option>
            <option>Custom Date Range</option>
          </select>
        </div>
        <div id="depositCustomRange" class="form-inline mb-3" style="display:none;">
          <label>Start Date:</label>
          <input type="date" id="depositStart" class="form-control ml-2" />
          <label class="ml-3">End Date:</label>
          <input type="date" id="depositEnd" class="form-control ml-2" />
          <button class="btn btn-primary btn-sm ml-3" id="depositGenerate">Generate</button>
        </div>
        <p class="text-muted"><small>Click the "Refund" button in the action column to record a deposit refund. This will convert the selected transaction to a refunded entry.</small></p>
        <div class="table-responsive" style="max-height:400px; overflow:auto;">
          <table class="table table-sm table-bordered" id="depositTable">
            <thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Deposit Amount</th><th>Tank Size</th><th>Status</th><th>Action</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <div id="depositSummary" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="depositPrint">Print Deposit Report</button>
      </div>
    </div>
  </div>
</div>

<!-- Profit Modal -->
<div class="modal fade" id="profitModal" tabindex="-1" role="dialog" aria-labelledby="profitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="profitModalLabel">Net Profit Report</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-inline mb-2">
          <label>Filter: </label>
          <select id="profitFilter" class="form-control ml-2">
            <option>Today</option>
            <option>Yesterday</option>
            <option>This Week</option>
            <option>This Month</option>
            <option>All Records</option>
            <option>Custom Date Range</option>
          </select>
        </div>
        <div id="profitCustomRange" class="form-inline mb-3" style="display:none;">
          <label>Start Date:</label>
          <input type="date" id="profitStart" class="form-control ml-2" />
          <label class="ml-3">End Date:</label>
          <input type="date" id="profitEnd" class="form-control ml-2" />
          <button class="btn btn-primary btn-sm ml-3" id="profitGenerate">Generate</button>
        </div>
        <div class="table-responsive" style="max-height:400px; overflow:auto;">
          <table class="table table-sm table-bordered" id="profitTable">
            <thead><tr><th>Date</th><th>Invoice</th><th>Product</th><th>Qty</th><th>Sales</th><th>Cost</th><th>VAT (12%)</th><th>Profit</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <div id="profitSummary" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="profitPrint">Print Profit Report</button>
      </div>
    </div>
  </div>
</div>


<!-- Tax Modal -->
<script>
// System File Backup logic
$(function(){
  $('#btnCreateBackup').on('click', function(){
    var $btn = $(this);
    $btn.prop('disabled', true).text('Creating...');
    $('#backupStatusMsg').removeClass().text('');
    $.ajax({
      url: 'backup_system.php',
      method: 'POST',
      dataType: 'json',
      success: function(res){
        if(res.success && res.url){
          $('#backupStatusMsg').addClass('alert alert-success').text('System files backup created successfully. Downloading...');
          // Trigger download
          var link = document.createElement('a');
          link.href = res.url;
          link.download = res.filename;
          document.body.appendChild(link);
          link.click();
          setTimeout(function(){ document.body.removeChild(link); }, 1000);
        }else{
          $('#backupStatusMsg').addClass('alert alert-danger').text(res.message || 'Backup failed.');
        }
      },
      error: function(xhr){
        $('#backupStatusMsg').addClass('alert alert-danger').text('Backup failed.');
      },
      complete: function(){
        $btn.prop('disabled', false).text('Create Backup');
      }
    });
  });
  // Reset modal on open
  $('#backupSystemModal').on('show.bs.modal', function(){
    $('#backupStatusMsg').removeClass().text('');
    $('#btnCreateBackup').prop('disabled', false).text('Create Backup');
  });
});
</script>
<div class="modal fade" id="taxModal" tabindex="-1" role="dialog" aria-labelledby="taxModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="taxModalLabel">VAT (12%) Report</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-inline mb-2">
          <label>Filter: </label>
          <select id="taxFilter" class="form-control ml-2">
            <option>Today</option>
            <option>Yesterday</option>
            <option>This Week</option>
            <option>This Month</option>
            <option>All Records</option>
            <option>Custom Date Range</option>
          </select>
        </div>
        <div id="taxCustomRange" class="form-inline mb-3" style="display:none;">
          <label>Start Date:</label>
          <input type="date" id="taxStart" class="form-control ml-2" />
          <label class="ml-3">End Date:</label>
          <input type="date" id="taxEnd" class="form-control ml-2" />
          <button class="btn btn-primary btn-sm ml-3" id="taxGenerate">Generate</button>
        </div>
        <div class="table-responsive" style="max-height:400px; overflow:auto;">
          <table class="table table-sm table-bordered" id="taxTable">
            <thead><tr><th>Date</th><th>Invoice</th><th>Sales</th><th>VAT</th><th>Total</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <div id="taxSummary" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="taxPrint">Print VAT Report</button>
      </div>
    </div>
  </div>
</div>

<?php include_once "footer.php"; ?>

<script>
  $(document).ready(function() {
    // Order Status Card Click Handler
    $('.order-status-card').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const status = $(this).attr('data-status');

      if (status === 'PendingAdmin') {
        window.location.href = 'orderlist.php?status=Pending&role=Admin';
        return;
      }

      if (status === 'PendingUser') {
        window.location.href = 'admin_pending_orders.php';
        return;
      }

      if (status === 'Complete') {
        const today = new Date().toISOString().split('T')[0];
        window.location.href = 'orderlist.php?status=Complete&date=' + today;
        return;
      }

      if (status) {
        window.location.href = 'orderlist.php?status=' + status;
      }
    });

    // Make cards look clickable
    $('.order-status-card').css('cursor', 'pointer');

    // Additional dashboard card clicks open modals
    $('#cardDeposits').on('click', function(){
        $('#depositModal').modal('show');
        loadDepositData('Today');
    });
    $('#cardProfit').on('click', function(){
        $('#profitModal').modal('show');
        loadProfitData('Today');
    });
    $('#cardTax').on('click', function(){
        $('#taxModal').modal('show');
        loadTaxData('Today');
    });

    // Filter change behaviour for all three
    function handleFilter(selector, customDiv, generateBtn, loadFunc) {
        $(selector).on('change', function(){
            const val = $(this).val();
            if (val === 'Custom Date Range') {
                $(customDiv).show();
            } else {
                $(customDiv).hide();
                loadFunc(val);
            }
        });
        $(generateBtn).on('click', function(){
            const start = $(customDiv + ' input[name=start]').val() || $(customDiv + ' input[id$="Start"]').val();
            const end = $(customDiv + ' input[name=end]').val() || $(customDiv + ' input[id$="End"]').val();
            loadFunc('Custom Date Range', start, end);
        });
    }

    handleFilter('#depositFilter','#depositCustomRange','#depositGenerate', loadDepositData);
    handleFilter('#profitFilter','#profitCustomRange','#profitGenerate', loadProfitData);
    handleFilter('#taxFilter','#taxCustomRange','#taxGenerate', loadTaxData);

    // data loading functions
    function loadDepositData(filter, start, end) {
        $.getJSON('fetch_deposits.php',{filter:filter,start:start,end:end}, function(res){
            if(res.error) return;
            let tbody='';
            res.rows.forEach(r=>{
                const amt = r.deposit_amount || 0;
                const amtFmt = '₱' + parseFloat(amt).toFixed(2);
                tbody += `<tr><td>${r.date}</td><td>${r.invoice}</td><td>${r.customer}</td><td>${amtFmt}</td><td>${r.tank_size || '11kg'}</td><td>${r.status}</td><td>` +
                    (amt > 0 ? `<button class="btn btn-sm btn-danger refundDeposit" data-invoice="${r.invoice}">Refund</button>` : '') +
                    `</td></tr>`;
            });
            $('#depositTable tbody').html(tbody);
            $('#depositSummary').html(`
              <p><strong>Total Deposit (net):</strong> ₱${res.summary.balance.toFixed(2)}</p>
            `);
        });
    }
    function loadProfitData(filter, start, end){
        $.getJSON('fetch_profit.php',{filter:filter,start:start,end:end},function(res){
          if(res.error) {
            $('#profitTable tbody').html('<tr><td colspan="8" class="text-center text-muted">No profit data available for selected range.</td></tr>');
            $('#profitSummary').html('<p class="text-muted">No profit summary available.</p>');
            return;
          }
          let tbody='';
          res.rows.forEach(r=>{
            tbody += `<tr><td>${r.date}</td><td>${r.invoice}</td><td>${r.product}</td><td>${r.qty}</td><td>₱${r.sales.toFixed(2)}</td><td>₱${r.cost.toFixed(2)}</td><td>₱${r.vat.toFixed(2)}</td><td>₱${r.profit.toFixed(2)}</td></tr>`;
          });
          if (!res.rows.length) {
            tbody = '<tr><td colspan="8" class="text-center text-muted">No profit data available for selected range.</td></tr>';
          }
          $('#profitTable tbody').html(tbody);
          $('#profitSummary').html(`
            <p><strong>Total Sales:</strong> ₱${res.summary.sales.toFixed(2)}<br>
             <strong>Total Cost:</strong> ₱${res.summary.cost.toFixed(2)}<br>
             <strong>Total VAT (12%):</strong> ₱${res.summary.vat.toFixed(2)}<br>
             <strong>Net Profit:</strong> ₱${res.summary.profit.toFixed(2)}<br>
             <em>Net Profit = Total Sales – Total Product Cost – VAT (12%)</em></p>
          `);
        });
    }
    function loadTaxData(filter, start, end){
        $.getJSON('fetch_tax.php',{filter:filter,start:start,end:end},function(res){
            if(res.error) return;
            let tbody='';
            res.rows.forEach(r=>{
                tbody += `<tr><td>${r.date}</td><td>${r.invoice}</td><td>₱${r.sales.toFixed(2)}</td><td>₱${r.vat.toFixed(2)}</td><td>₱${r.total.toFixed(2)}</td></tr>`;
            });
            $('#taxTable tbody').html(tbody);
            $('#taxSummary').html(`
              <p><strong>Total Taxable Sales:</strong> ₱${res.summary.sales.toFixed(2)}<br>
                 <strong>Total VAT(12%):</strong> ₱${res.summary.vat.toFixed(2)}<br>
                 <strong>Grand Total:</strong> ₱${res.summary.grand.toFixed(2)}</p>
            `);
        });
    }

    // print actions
    $('#depositPrint').on('click', function(){ printSection('depositModal','Deposit Report'); });
    $('#profitPrint').on('click', function(){ printSection('profitModal','Profit Report'); });
    $('#taxPrint').on('click', function(){ printSection('taxModal','VAT Report'); });

    function printSection(modalId,title){
        // only include table and summary for clean report
        const body = $('#' + modalId + ' .modal-body');
        // clone table and remove any action buttons/column before printing
        let tableClone = body.find('.table-responsive').clone();
        tableClone.find('button').remove();
        tableClone.find('th:contains("Action")').remove();
        tableClone.find('td:last-child').remove();
        const tableHtml = tableClone.html();
        const summaryHtml = body.find('> #'+modalId.replace('Modal','')+'Summary').html();
        const rangeText = body.find('select').val();
        const w = window.open('','_blank','width=900,height=700');
        w.document.write('<html><head><title>'+title+'</title>');
        w.document.write('<style>body{font-family:sans-serif;margin:20px;} table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:4px;}@media print{body{margin:0;}}</style>');
        w.document.write('</head><body>');
        w.document.write('<h2>'+ title +'</h2>');
        let filterLine = '<strong>Filter:</strong> ' + rangeText;
        if(rangeText === 'Custom Date Range'){
            const start = body.find('input[type=date]').first().val();
            const end = body.find('input[type=date]').last().val();
            filterLine += ' ('+start+' to '+end+')';
        }
        w.document.write('<p><strong>System:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_NAME']); ?><br>');
        w.document.write(filterLine + '</p>');
        w.document.write(tableHtml);
        w.document.write(summaryHtml);
        w.document.write('<p><strong>Prepared by:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?><br>' +
                        '<strong>Print Date:</strong> ' + new Date().toLocaleString() + '</p>');
        w.document.write('</body></html>');
        w.document.close();
        setTimeout(()=>{w.print(); w.close();},300);
    }
    // refund action handler
    $(document).on('click','.refundDeposit', function(){
        const invoice = $(this).data('invoice');
        if(!invoice) return;
        Swal.fire({
            title: 'Confirm Refund',
            text: 'Are you sure you want to refund invoice ' + invoice + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, refund it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('refund_deposit.php',{invoice:invoice}, function(resp){
                    if(resp.success){
                        Swal.fire('Refunded','The deposit has been marked as refunded.','success');
                        loadDepositData($('#depositFilter').val());
                    } else {
                        Swal.fire('Error', resp.error || 'Refund failed', 'error');
                    }
                },'json');
            }
        });
    });
  });
</script>
