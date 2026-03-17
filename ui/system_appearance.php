<?php
// system_appearance.php: System Appearance settings page with dark mode toggle
require_once 'connectdb.php';
session_start();
if (!isset($_SESSION['userid']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('location:../index.php');
    exit;
}
require_once 'header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h2 class="m-0">System Appearance</h2></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right"></ol></div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="col-lg-8 mx-auto">
        <div class="card card-secondary card-outline">
          <div class="card-header"><h5 class="m-0">Dark Mode</h5></div>
          <div class="card-body">
            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="darkModeSwitch">
                <label class="custom-control-label" for="darkModeSwitch">Enable Dark Mode</label>
              </div>
            </div>
            <p class="text-muted">Save preference in browser with localStorage.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
