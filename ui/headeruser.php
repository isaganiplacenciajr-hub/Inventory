<?php
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
  <title>USER DASHBOARD</title>

  <!-- Google Font -->
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

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

  <!-- SweetAlert2 (needed for logout confirmation) -->
  <link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">

  <!-- OPTIONAL: CENTER BRAND SAME AS ADMIN -->
  <style>
    .brand-link {
      text-align: center;
    }
    .brand-text {
      display: block;
      width: 100%;
    }
    .nav-link.active {
      background-color: #007bff !important;
    }

    /* Dark mode payload */
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

    body.dark-mode .my-today-sales-card {
      border-color: #28a745 !important;
      background: linear-gradient(135deg, #1f2a1c 0%, #0f1710 100%) !important;
      color: #b6f1a5 !important;
    }
    body.dark-mode .my-today-sales-card .card-header {
      background-color: #1f4f27 !important;
      color: #e5f8e4 !important;
    }
    body.dark-mode .my-today-sales-card h3,
    body.dark-mode .my-today-sales-card .card-body {
      color: #c8f7b5 !important;
    }

    /* Ensure all titles are visible with dark mode */
    body.dark-mode .card-header h5,
    body.dark-mode .card-header h6,
    body.dark-mode .card-title,
    body.dark-mode .content h1,
    body.dark-mode .content h2,
    body.dark-mode .content h3,
    body.dark-mode .content h4,
    body.dark-mode .content h5,
    body.dark-mode .content h6 {
      color: #e9f7ff !important;
    }

    /* Specific dashboard title chips in dark mode */
    body.dark-mode .card-header.bg-success h5,
    body.dark-mode .card-header.bg-warning h5,
    body.dark-mode .card-header.bg-info h5,
    body.dark-mode .card-header.bg-danger h5,
    body.dark-mode .card-header.bg-secondary h5,
    body.dark-mode .card-header.bg-dark h5 {
      color: #ffffff !important;
      text-shadow: 0 0 5px rgba(0,0,0,0.45);
    }

    body.dark-mode .card-header.bg-warning { background-color: #5e5a00 !important; }
    body.dark-mode .card-header.bg-success { background-color: #1f5225 !important; }
    body.dark-mode .card-header.bg-info { background-color: #1d4d6b !important; }
    body.dark-mode .card-header.bg-danger { background-color: #6f1f2c !important; }
    body.dark-mode .card-header.bg-secondary { background-color: #2e2f33 !important; }
    body.dark-mode .card-header.bg-dark { background-color: #111a20 !important; }

    body.dark-mode .table thead th {
      color: #f0f8ff !important;
    }

    /* All user dashboard text elements in dark mode */
    body.dark-mode .content-wrapper,
    body.dark-mode .content,
    body.dark-mode .card,
    body.dark-mode .card-body,
    body.dark-mode .card-footer,
    body.dark-mode .card-header,
    body.dark-mode .small-box,
    body.dark-mode .info-box,
    body.dark-mode .table,
    body.dark-mode .table th,
    body.dark-mode .table td,
    body.dark-mode .btn,
    body.dark-mode .badge,
    body.dark-mode .form-control,
    body.dark-mode .nav-link,
    body.dark-mode .breadcrumb,
    body.dark-mode .list-group-item,
    body.dark-mode .custom-control-label,
    body.dark-mode .text-muted,
    body.dark-mode .text-primary,
    body.dark-mode .text-success,
    body.dark-mode .text-info,
    body.dark-mode .text-warning,
    body.dark-mode .text-danger,
    body.dark-mode .text-secondary {
      transition: background-color 0.3s ease, color 0.3s ease !important;
    }

    body.dark-mode .content-wrapper,
    body.dark-mode .content,
    body.dark-mode .card,
    body.dark-mode .card-body,
    body.dark-mode .card-footer,
    body.dark-mode .card-header,
    body.dark-mode .table,
    body.dark-mode .list-group-item,
    body.dark-mode .dropdown-menu,
    body.dark-mode .sidebar,
    body.dark-mode .main-sidebar {
      background-color: #1c1c1c !important;
      color: #e5e5e5 !important;
    }

    body.dark-mode .badge {
      background-color: rgba(100, 144, 255, 0.25) !important;
      color: #e9f7ff !important;
    }

    body.dark-mode .btn {
      background-color: #264d73 !important;
      color: #e9f7ff !important;
      border-color: #3a5f8a !important;
    }

    body.dark-mode .form-control {
      background-color: #2a2a2a !important;
      color: #f5f5f5 !important;
      border-color: #444 !important;
    }

    body.dark-mode .table thead th,
    body.dark-mode .table td,
    body.dark-mode .table th {
      border-color: #3a3a3a !important;
    }

    body.dark-mode .text-muted {
      color: #bcd0f5 !important;
    }

    body.dark-mode .text-primary,
    body.dark-mode .text-success,
    body.dark-mode .text-info,
    body.dark-mode .text-warning,
    body.dark-mode .text-danger,
    body.dark-mode .text-secondary {
      opacity: 0.95 !important;
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
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<script>
  function setDarkMode(enabled) {
    if (enabled) {
      document.body.classList.add('dark-mode');
    } else {
      document.body.classList.remove('dark-mode');
    }
    localStorage.setItem('darkMode', enabled ? 'true' : 'false');
    var switchControl = document.getElementById('darkModeSwitch');
    if (switchControl) {
      switchControl.checked = enabled;
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    var stored = localStorage.getItem('darkMode');
    var enabled = stored === 'true';
    setDarkMode(enabled);

    var switchControl = document.getElementById('darkModeSwitch');
    var switchControlTop = document.getElementById('darkModeSwitchTop');

    [switchControl, switchControlTop].forEach(function(sw) {
      if (sw) {
        sw.checked = enabled;
        sw.addEventListener('change', function () {
          setDarkMode(this.checked);
          if (switchControl && sw !== switchControl) switchControl.checked = this.checked;
          if (switchControlTop && sw !== switchControlTop) switchControlTop.checked = this.checked;
        });
      }
    });
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

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">

    <a href="user.php" class="brand-link">
      <span class="brand-text font-weight-light">USER DASHBOARD</span>
    </a>

    <div class="sidebar">

      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../dist/img/Spmlogo.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">
            <?php echo htmlspecialchars($_SESSION['username']); ?>
          </a>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">

          <li class="nav-item">
            <a href="user.php"
               class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='user.php'?'active':''; ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="userpos.php"
               class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='userpos.php'?'active':''; ?>">
              <i class="nav-icon fas fa-shopping-cart"></i>
              <p>POS</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="userorderlist.php"
               class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='userorderlist.php'?'active':''; ?>">
              <i class="nav-icon fas fa-list"></i>
              <p>Order List</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="user_settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='user_settings.php'?'active':''; ?>">
              <i class="nav-icon fas fa-cog"></i>
              <p>Settings</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="logout.php" class="nav-link" onclick="logoutConfirm(event)">
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
    <section class="content pt-3">
      <div class="container-fluid">