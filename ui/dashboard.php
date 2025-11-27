<?php


include_once'connectdb.php';
session_start();


if($_SESSION['useremail']==""){

header('location:../index.php');

}






include_once"header.php";


?>


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-12">
            <div class="alert alert-primary text-center" style="font-size:1.3rem; font-weight:500;">Welcome back, Admin!</div>
          </div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-4 col-12 mb-4">
            <div class="card shadow border-0" style="background: linear-gradient(135deg, #17a2b8 60%, #138496 100%); color: #fff;">
              <div class="card-body d-flex align-items-center">
                <div class="mr-3" style="font-size:2.5rem;"><i class="fas fa-box"></i></div>
                <div>
                  <div style="font-size:1.1rem; font-weight:500;">Total Products</div>
                  <div style="font-size:2rem; font-weight:bold;">
                    <?php
                    $stmt = $pdo->prepare('SELECT COUNT(*) as total_products FROM tbl_product');
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo $row['total_products'] ?? 0;
                    ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-12 mb-4">
            <div class="card shadow border-0" style="background: linear-gradient(135deg, #28a745 60%, #218838 100%); color: #fff;">
              <div class="card-body d-flex align-items-center">
                <div class="mr-3" style="font-size:2.5rem;"><i class="fas fa-file-invoice"></i></div>
                <div>
                  <div style="font-size:1.1rem; font-weight:500;">Total Orders</div>
                  <div style="font-size:2rem; font-weight:bold;">
                    <?php
                    $stmt = $pdo->prepare('SELECT COUNT(*) as total_orders FROM tbl_invoice');
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo $row['total_orders'] ?? 0;
                    ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-12 mb-4">
            <div class="card shadow border-0" style="background: linear-gradient(135deg, #ffc107 60%, #e0a800 100%); color: #fff;">
              <div class="card-body d-flex align-items-center">
                <div class="mr-3" style="font-size:2.5rem;"><i class="fas fa-coins"></i></div>
                <div>
                  <div style="font-size:1.1rem; font-weight:500;">Total Sales</div>
                  <div style="font-size:2rem; font-weight:bold;">
                    <?php
                    $stmt = $pdo->prepare('SELECT SUM(total) as total_sales FROM tbl_invoice');
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo number_format($row['total_sales'] ?? 0, 2);
                    ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row mb-4">
          <div class="col-lg-4 col-12 mb-4">
            <div class="card shadow border-0">
              <div class="card-header bg-info text-white">
                <h5 class="m-0">Best-selling KG (All time)</h5>
              </div>
              <div class="card-body">
                <?php
                // Top-selling categories (kg sizes) by quantity sold
                $stmt = $pdo->prepare("SELECT b.category, SUM(a.qty) AS total_qty FROM tbl_invoice_details a JOIN tbl_product b ON a.product_id=b.pid GROUP BY b.category ORDER BY total_qty DESC LIMIT 3");
                $stmt->execute();
                $tops = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!$tops) {
                  echo '<p>No sales data yet.</p>';
                } else {
                  $first = $tops[0];
                  echo "<div style=\"font-size:1.4rem;font-weight:700;\">" . htmlspecialchars($first['category']) . "</div>";
                  echo "<div style=\"font-size:1.1rem;color:#666;margin-bottom:8px;\">Sold: " . (int)$first['total_qty'] . "</div>";
                  if (count($tops) > 1) {
                    echo '<hr><div style="font-weight:600;">Top 3</div><ul class="pl-3">';
                    foreach ($tops as $t) {
                      echo '<li>' . htmlspecialchars($t['category']) . ' — ' . (int)$t['total_qty'] . ' pcs</li>';
                    }
                    echo '</ul>';
                  }
                }
                ?>
              </div>
            </div>
          </div>
