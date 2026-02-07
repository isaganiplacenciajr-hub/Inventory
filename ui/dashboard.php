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

  <div class="row mb-2">
    <div class="col-sm-6">
      <h1 class="m-0">Dashboard</h1>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <div class="container-fluid">

      <!-- SUMMARY CARDS -->
      <div class="row">

        <!-- TOTAL PRODUCTS -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-info shadow border-0">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-box fa-2x"></i></div>
              <div>
                <div>Total Product Categories</div>
                <h3 class="mb-0">
                  <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM tbl_product");
                    echo $stmt->fetchColumn();
                  ?>
                </h3>
              </div>
            </div>
          </div>
        </div>

        <!-- TOTAL ORDERS -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-info shadow border-0">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-file-invoice fa-2x"></i></div>
              <div>
                <div>Total Orders</div>
                <h3 class="mb-0">
                  <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM tbl_invoice");
                    echo $stmt->fetchColumn();
                  ?>
                </h3>
              </div>
            </div>
          </div>
        </div>

        <!-- TOTAL SALES -->
        <div class="col-lg-4 col-12 mb-4">
          <div class="card card-info shadow border-0">
            <div class="card-body d-flex align-items-center">
              <div class="mr-3"><i class="fas fa-coins fa-2x"></i></div>
              <div>
                <div>Total Sales</div>
                <h3 class="mb-0">
                  ₱<?php
                    $stmt = $pdo->query("SELECT SUM(total) FROM tbl_invoice");
                    echo number_format($stmt->fetchColumn() ?? 0, 2);
                  ?>
                </h3>
              </div>
            </div>
          </div>
        </div>

      </div> <!-- end summary cards -->

      <!-- STOCK NOTIFICATION -->
      <div class="row">
        <div class="col-lg-12">
          <div class="card card-warning card-outline shadow-sm">

            <div class="card-header">
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

            <!-- LEGEND -->
            <div class="card-footer">
              <h6><strong>Legend:</strong></h6>

              <span style="display:inline-block; margin-bottom:6px; background:red;color:white;padding:5px 10px;border-radius:5px;">
                OUT OF STOCK
              </span> – Stock 0<br>

              <span style="display:inline-block; margin-bottom:6px; background:orange;color:white;padding:5px 10px;border-radius:5px;">
                LOW
              </span> – Stock 1–15<br>

              <span style="display:inline-block; margin-bottom:6px; background:green;color:white;padding:5px 10px;border-radius:5px;">
                GOOD STOCK
              </span> – Stock 16–30<br>

              <span style="display:inline-block; background:blue;color:white;padding:5px 10px;border-radius:5px;">
                HIGH STOCK
              </span> – Stock 31+
            </div>

          </div>
        </div>
      </div> <!-- end stock notification -->

      <!-- BEST SELLING CATEGORIES -->
      <div class="row mt-4">
        <div class="col-lg-6">
          <div class="card card-success shadow-sm">
            <div class="card-header"><h5 class="m-0">Best Selling Categories (All Time)</h5></div>
            <div class="card-body">
              <?php
              $stmt = $pdo->prepare("SELECT
                  CASE
                    WHEN LOWER(b.category) LIKE '%50%' THEN '50 Kg (Extra Large)'
                    WHEN LOWER(b.category) LIKE '%22%' THEN '22 Kg (Large)'
                    WHEN LOWER(b.category) LIKE '%11%' THEN '11 Kg (Standard)'
                    WHEN LOWER(b.category) LIKE '%5%' AND LOWER(b.category) NOT LIKE '%50%' THEN '5 Kg (Medium)'
                    ELSE 'Other'
                  END AS kg,
                  SUM(a.qty) AS total_qty
                FROM tbl_invoice_details a
                JOIN tbl_product b ON a.product_id = b.pid
                GROUP BY kg
                HAVING kg <> 'Other'
                ORDER BY total_qty DESC");
              $stmt->execute();
              $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

              if (!$cats) {
                echo '<p>No sales data yet.</p>';
              } else {
                $top = $cats[0];
                echo "<div style=\"font-size:1.25rem;font-weight:700;\">" . htmlspecialchars($top['kg']) . "</div>";
                echo "<div style=\"font-size:1rem;color:#666;margin-bottom:8px;\">Sold: " . (int)$top['total_qty'] . "</div>";
                echo '<hr><div style="font-weight:600;">Top 3</div><ul class="pl-3">';
                $i = 0;
                foreach ($cats as $c) {
                  if ($i++ >= 3) break;
                  echo '<li>' . htmlspecialchars($c['kg']) . ' — ' . (int)$c['total_qty'] . ' pcs</li>';
                }
                echo '</ul>';
              }
              ?>
            </div>
          </div>
        </div>
      </div> <!-- end best selling categories -->

    </div> <!-- end container-fluid -->
  </div> <!-- end content -->

</div> <!-- end content-wrapper -->
