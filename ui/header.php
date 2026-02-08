<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>INVENTORY MANAGEMENT SYSTEM</title>

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">

  <!-- iCheck -->
  <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">

  <!-- Select2 -->
  <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

  <!-- AdminLTE -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">

  <!-- ✅ FIX: CENTER ADMIN DASHBOARD -->
  <style>
    .brand-link {
      text-align: center !important;
    }
    .brand-text {
      display: block;
      width: 100%;
    }
  </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button">
          <i class="fas fa-bars"></i>
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">

    <!-- ✅ FIXED: CENTERED BRAND -->
    <a href="dashboard.php" class="brand-link">
      <span class="brand-text font-weight-light">ADMIN DASHBOARD</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">

      <!-- User panel -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../dist/img/Spmlogo.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">
            <?php echo $_SESSION['username']; ?>
          </a>
        </div>
      </div>

   

      <!-- Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

          <li class="nav-item">
            <a href="dashboard.php" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="addproduct.php" class="nav-link">
              <i class="nav-icon fas fa-edit"></i>
              <p>Add Stock</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="productlist.php" class="nav-link">
              <i class="nav-icon fas fa-eye"></i>
              <p>Product List</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="pos.php" class="nav-link">
              <i class="nav-icon fas fa-book"></i>
              <p>POS</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="orderlist.php" class="nav-link">
              <i class="nav-icon fas fa-list"></i>
              <p>Order List</p>
            </a>
          </li>

          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>
                Sales Report
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="sales_report_daily.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Daily Sales Report</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="sales_report_weekly.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Weekly Sales Report</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="sales_report_monthly.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Monthly Sales Report</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item">
            <a href="stockmonitoring.php" class="nav-link">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Stock Monitoring</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="registration.php" class="nav-link">
              <i class="nav-icon fas fa-plus-square"></i>
              <p>Registration</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="utilities.php" class="nav-link">
              <i class="nav-icon fas fa-user-lock"></i>
              <p>Utilities</p>
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
    <!-- /.sidebar -->
  </aside>