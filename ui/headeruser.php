<?php
// ❗ NO session_start() HERE
// session_start() is handled in headeruser.php
include_once 'connectdb.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === '') {
  header('Location: ../index.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Dashboard</title>

  <!-- Google Font -->
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">

  <!-- AdminLTE -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">

  <style>
    .brand-link {
      text-align: center;
    }
    .brand-text {
      display: block;
      width: 100%;
      font-weight: 600;
      text-transform: uppercase;
    }

    /* 🔥 REMOVE EXTRA SPACE */
    html, body {
      height: auto !important;
    }

    .content-wrapper {
      padding-top: 0 !important;
      min-height: unset !important;
      height: 90vh !important;
    }

    .content-header {
      padding: 5px 15px;
      margin-bottom: 0;
    }

    .content {
      min-height: unset !important;
      padding-bottom: 10px !important;
    }

    .card {
      margin-bottom: 0;
    }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#">
          <i class="fas fa-bars"></i>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="dashboard.php" class="brand-link">
      <span class="brand-text">USER DASHBOARD</span>
    </a>

    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../dist/img/Spmlogo.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block"><?php echo $_SESSION['username']; ?></a>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="logout.php" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <!-- CONTENT -->
  <div class="content-wrapper">

    <div class="content-header">
      <h1 class="m-0">User Dashboard</h1>
    </div>

    <section class="content">
      <div class="container-fluid">

        <!-- 🔵 WELCOME BANNER -->
        <div class="card bg-primary rounded-0">
          <div class="card-body text-center py-2">
            <h5 class="m-0 text-white">
              Welcome — Hello User <b><?php echo $_SESSION['username']; ?></b>, welcome back!
            </h5>
          </div>
        </div>

        <!-- DASHBOARD CARDS -->
        <div class="row mt-3">

          <!-- TODAY'S SALES -->
          <div class="col-lg-4 col-12 mb-3">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <div class="mr-3 text-primary">
                  <i class="fas fa-coins fa-2x"></i>
                </div>
                <div>
                  <div class="text-muted">Today's Sales</div>
                  <h3 class="mb-0">
                    <?php
                      $today = date('Y-m-d');
                      $stmt = $pdo->prepare("SELECT SUM(total) FROM tbl_invoice WHERE order_date = :d");
                      $stmt->execute([':d' => $today]);
                      echo '₱' . number_format($stmt->fetchColumn() ?: 0, 2);
                    ?>
                  </h3>
                </div>
              </div>
            </div>
          </div>

          <!-- TODAY'S TRANSACTIONS -->
          <div class="col-lg-4 col-12 mb-3">
            <div class="card shadow-sm border-0">
              <div class="card-body d-flex align-items-center">
                <div class="mr-3 text-success">
                  <i class="fas fa-file-invoice fa-2x"></i>
                </div>
                <div>
                  <div class="text-muted">Today's Transactions</div>
                  <h3 class="mb-0">
                    <?php
                      $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_invoice WHERE order_date = :d");
                      $stmt->execute([':d' => $today]);
                      echo (int)$stmt->fetchColumn();
                    ?>
                  </h3>
                </div>
              </div>
            </div>
          </div>

          <!-- STOCK ALERTS -->
          <div class="col-lg-4 col-12 mb-3">
            <div class="card shadow-sm border-0">
              <div class="card-body">
                <h5 class="mb-3">Stock Alerts</h5>

                <?php
                  $stmt = $pdo->query("
                    SELECT
                      SUM(CASE WHEN COALESCE(stock,0) = 0 THEN 1 ELSE 0 END) AS out_count,
                      SUM(CASE WHEN COALESCE(stock,0) > 0 AND COALESCE(stock,0) <= 15 THEN 1 ELSE 0 END) AS low_count
                    FROM tbl_product
                  ");
                  $r = $stmt->fetch(PDO::FETCH_ASSOC);

                  $out = (int)$r['out_count'];
                  $low = (int)$r['low_count'];

                  if ($out > 0) {
                    echo '<div class="mb-2">❌ <strong>Out of stock detected</strong></div>';
                  }
                  if ($low > 0) {
                    echo '<div class="mb-2">⚠️ <strong>Low stock detected</strong></div>';
                  }
                  if ($out === 0 && $low === 0) {
                    echo '<div class="text-success font-weight-bold">✅ All stocks are sufficient</div>';
                  }
                ?>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>
  </div>
</div>

<!-- Scripts -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>

</body>
</html>