<?php
if (!isset($pdo)) {
  require_once __DIR__ . '/connectdb.php';
}
?>
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

  <!-- ✅ FIX: CENTER ADMIN DASHBOARD + DARK MODE -->
  <style>
    .brand-link {
      text-align: center !important;
    }
    .brand-text {
      display: block;
      width: 100%;
    }
    .nav-link.active {
      background-color: #007bff !important;
    }

    html, body, .wrapper, .main-header, .main-sidebar, .content-wrapper, .content, .card, .table, .navbar, .sidebar, .dropdown-menu, .form-control, .btn, .modal-content {
      transition: background-color 0.35s ease, color 0.35s ease, border-color 0.35s ease;
    }

    html.dark-mode,
    body.dark-mode {
      background-color: #121212 !important;
      color: #e5e5e5 !important;
    }

    body.dark-mode {
      background-color: #121212 !important;
      color: #e5e5e5 !important;
    }
    body.dark-mode .wrapper,
    body.dark-mode .main-header,
    body.dark-mode .main-sidebar,
    body.dark-mode .content-wrapper,
    body.dark-mode .content,
    body.dark-mode .content-header,
    body.dark-mode .card,
    body.dark-mode .table,
    body.dark-mode .table th,
    body.dark-mode .table td,
    body.dark-mode .modal-content,
    body.dark-mode .form-control,
    body.dark-mode .select2-container--default .select2-selection--single,
    body.dark-mode .btn,
    body.dark-mode .info-box,
    body.dark-mode .small-box,
    body.dark-mode .breadcrumb,
    body.dark-mode .card-header,
    body.dark-mode .card-footer,
    body.dark-mode .modal-header,
    body.dark-mode .modal-footer,
    body.dark-mode .overlay,
    body.dark-mode .dropdown-menu,
    body.dark-mode .list-group-item {
      background-color: #1e1e1e !important;
      border-color: #2c2c2c !important;
      color: #e5e5e5 !important;
    }

    body.dark-mode .main-sidebar .nav-sidebar .nav-item > .nav-link {
      background: transparent !important;
      color: #c1c1c1 !important;
    }
    body.dark-mode .main-sidebar .nav-sidebar .nav-item > .nav-link:hover,
    body.dark-mode .main-sidebar .nav-sidebar .nav-item > .nav-link.active {
      background: #343a40 !important;
      color: #ffffff !important;
    }

    body.dark-mode .form-control, body.dark-mode .select2-container--default .select2-selection--single {
      background-color: #222 !important;
      color: #eee !important;
      border-color: #444 !important;
    }

    body.dark-mode .table th, body.dark-mode .table td {
      border-color: #333 !important;
      background-color: #1e1e1e !important;
      color: #e7e7e7 !important;
    }

    body.dark-mode .btn {
      background-color: #2a6496 !important;
      color: #ffffff !important;
      border-color: #204d74 !important;
      transition: background-color 0.25s ease, border-color 0.25s ease, color 0.25s ease;
    }

    body.dark-mode .btn-success {
      background-color: #1f7a34 !important;
      border-color: #166228 !important;
      color: #fff !important;
    }

    body.dark-mode .btn:hover,
    body.dark-mode .btn:focus {
      background-color: #1d4f7f !important;
      border-color: #1a456f !important;
      color: #fff !important;
    }

    body.dark-mode .main-sidebar .nav-link,
    body.dark-mode .main-sidebar .nav-item > .nav-link {
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    body.dark-mode .card .card-body,
    body.dark-mode .card .card-footer {
      background-color: #1f1f1f !important;
    }
    body.dark-mode .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link {
      color: #adb5bd;
    }
    body.dark-mode .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
      background-color: #007bff;
      color: #fff;
    }

    body.dark-mode a, body.dark-mode .navbar, body.dark-mode .nav-link {
      color: #f1f1f1 !important;
    }

    body.dark-mode .card-header,
    body.dark-mode .card-footer,
    body.dark-mode .modal-header,
    body.dark-mode .modal-footer {
      background-color: #242424 !important;
      color: #f1f1f1 !important;
    }

    /* Additional dark mode support for receipt modal content */
    body.dark-mode .receipt-container,
    body.dark-mode .receipt-container table,
    body.dark-mode .receipt-container th,
    body.dark-mode .receipt-container td,
    body.dark-mode .receipt-container p,
    body.dark-mode .receipt-container strong,
    body.dark-mode .receipt-container div {
      background-color: #1a1a1a !important;
      color: #edf2ff !important;
      border-color: #333 !important;
    }
    body.dark-mode .receipt-container hr {
      border-top: 1px solid #444 !important;
    }
    body.dark-mode .receipt-container td,
    body.dark-mode .receipt-container th {
      color: #d6dff4 !important;
    }

    /* Make receipt badge text readable on light badge colors */
    body.dark-mode .receipt-container span {
      color: #111 !important;
    }

    /* SweetAlert dark mode toast / popup text */
    body.dark-mode .swal2-popup,
    body.dark-mode .swal2-toast {
      background: #1f1f1f !important;
      color: #f7f9ff !important;
      border: 1px solid #444 !important;
      box-shadow: 0 0 20px rgba(0,0,0,0.55) !important;
    }
    body.dark-mode .swal2-title,
    body.dark-mode .swal2-content,
    body.dark-mode .swal2-html-container {
      color: #f1f3ff !important;
    }

    /* Ensure Select2 dropdowns and components remain readable */
    body.dark-mode .select2-container--default .select2-results__option[aria-selected=true],
    body.dark-mode .select2-container--default .select2-results__option:hover {
      background: #2b2f37 !important;
      color: #fff !important;
    }
  </style>
  <script>
    (function() {
      var storedDarkMode = localStorage.getItem('darkMode');
      if (storedDarkMode === 'true') {
        document.documentElement.classList.add('dark-mode');
        if (document.body) document.body.classList.add('dark-mode');
      } else if (storedDarkMode === 'false') {
        document.documentElement.classList.remove('dark-mode');
        if (document.body) document.body.classList.remove('dark-mode');
      }
    })();
  </script>
  <?php 
    $currentPage = basename($_SERVER['PHP_SELF']);
  ?>
</head>

<body class="hold-transition sidebar-mini">
<script>
  function setDarkMode(enabled) {
    if (enabled) {
      document.documentElement.classList.add('dark-mode');
      document.body.classList.add('dark-mode');
    } else {
      document.documentElement.classList.remove('dark-mode');
      document.body.classList.remove('dark-mode');
    }
    localStorage.setItem('darkMode', enabled ? 'true' : 'false');
  }

  document.addEventListener('DOMContentLoaded', function() {
    var stored = localStorage.getItem('darkMode');
    var enabled = stored === 'true';
    setDarkMode(enabled);

    var switchControl = document.getElementById('darkModeSwitch');
    if (switchControl) {
      switchControl.checked = enabled;
      switchControl.addEventListener('change', function() {
        setDarkMode(this.checked);
      });
    }
  });
</script>
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
            <a href="dashboard.php" class="nav-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="addproduct.php" class="nav-link <?php echo ($currentPage === 'addproduct.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-edit"></i>
              <p>Add Stock</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="productlist.php" class="nav-link <?php echo ($currentPage === 'productlist.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-eye"></i>
              <p>Product List</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="pos.php" class="nav-link <?php echo ($currentPage === 'pos.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-book"></i>
              <p>POS</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="orderlist.php" class="nav-link <?php echo ($currentPage === 'orderlist.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-list"></i>
              <p>Order List</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="admin_pending_orders.php" class="nav-link <?php echo ($currentPage === 'admin_pending_orders.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-hourglass-half"></i>
              <p>Pending Orders</p>
              <?php
              try {
                // count only orders created by non-admin users
                $pendingCount = $pdo->query(
                  "SELECT COUNT(*) FROM tbl_invoice inv LEFT JOIN tbl_user u ON inv.created_by = u.userid WHERE inv.status = 'Pending' AND COALESCE(u.role, 'User') != 'Admin'"
                )->fetchColumn();
                if ($pendingCount > 0) {
                  echo '<span class="badge badge-warning" style="float:right;">' . $pendingCount . '</span>';
                }
              } catch (Exception $e) {
                // Silently fail if query doesn't work
              }
              ?>
            </a>
          </li>

          <li class="nav-item">
            <a href="sales_report.php" class="nav-link <?php echo ($currentPage === 'sales_report.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Sales Report</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="stockmonitoring.php" class="nav-link <?php echo ($currentPage === 'stockmonitoring.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Stock Monitoring</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="registration.php" class="nav-link <?php echo ($currentPage === 'registration.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-plus-square"></i>
              <p>Registration</p>
            </a>
          </li>

          <li class="nav-item">
          <!-- User Management -->
          <li class="nav-item">
            <a href="user_management.php" class="nav-link <?php echo ($currentPage === 'user_management.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>User Management</p>
            </a>
          </li>

          <!-- Settings Dropdown -->
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="false" aria-controls="settingsMenu">
              <i class="nav-icon fas fa-cog"></i>
              <p>Settings <i class="right fas fa-angle-down"></i></p>
            </a>
            <div class="collapse" id="settingsMenu">
              <ul class="nav flex-column ms-3">
                <li class="nav-item">
                  <a href="system_backup.php" class="nav-link <?php echo ($currentPage === 'system_backup.php') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-hdd"></i>
                    <p>Backup System Files</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="branch_settings.php" class="nav-link <?php echo ($currentPage === 'branch_settings.php') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-code-branch"></i>
                    <p>Branch Settings</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="system_appearance.php" class="nav-link <?php echo ($currentPage === 'system_appearance.php') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-adjust"></i>
                    <p>System Appearance</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="utilities.php" class="nav-link <?php echo ($currentPage === 'utilities.php') ? 'active' : ''; ?>">
                    <i class="nav-icon fas fa-user-lock"></i>
                    <p>Utilities</p>
                  </a>
                </li>
              </ul>
            </div>
          </li>


          <li class="nav-item">
            <a href="#" onclick="logoutConfirm(event)" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>
          </li>

        </ul>
      </nav>

    </div>
    <!-- /.sidebar -->
  </aside>