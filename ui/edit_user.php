<?php
include_once 'connectdb.php';
session_start();
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header('location:../index.php');
    exit;
}

$userid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userid <= 0) {
    header('Location: user_management.php?error=invalid');
    exit;
}

// Fetch user
$stmt = $pdo->prepare('SELECT * FROM tbl_user WHERE userid = ? AND role = ?');
$stmt->execute([$userid, 'User']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: user_management.php?error=notfound');
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $cod_permission = isset($_POST['cod_self_completion_permission']) ? 1 : 0;
    $cod_expiry_date = $_POST['cod_permission_expiry_date'] ?? '';
    $cod_expiry_time = $_POST['cod_permission_expiry_time'] ?? '';
    $admin_consent = isset($_POST['admin_consent']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');

    $cod_permission_expiry = null;
    if ($cod_permission && $cod_expiry_date && $cod_expiry_time) {
        $cod_permission_expiry = $cod_expiry_date . ' ' . $cod_expiry_time;
    }

    $update = $pdo->prepare('UPDATE tbl_user SET fullname=?, status=?, cod_self_completion_permission=?, cod_permission_expiry=?, admin_consent=?, notes=? WHERE userid=?');
    $update->execute([$fullname, $status, $cod_permission, $cod_permission_expiry, $admin_consent, $notes, $userid]);

    header('Location: user_management.php?success=updated');
    exit;
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../plugins/sweetalert2/sweetalert2.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <div class="content-wrapper" style="margin-left:0;">
    <section class="content pt-3">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-lg-8 offset-lg-2">
            <div class="card card-primary card-outline">
              <div class="card-header">
                <h5 class="m-0">Edit User: <?php echo htmlspecialchars($user['username']); ?></h5>
              </div>
              <form method="post">
                <div class="card-body">
                  <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                      <option value="1" <?php if($user['status']==1) echo 'selected'; ?>>Active</option>
                      <option value="0" <?php if($user['status']==0) echo 'selected'; ?>>Inactive</option>
                    </select>
                  </div>
                  <hr>
                  <h6>COD Self Completion Permission</h6>
                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="codSwitch" name="cod_self_completion_permission" <?php if($user['cod_self_completion_permission']) echo 'checked'; ?>>
                      <label class="custom-control-label" for="codSwitch">Allow COD Self Completion</label>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label>Expiry Date</label>
                      <input type="date" name="cod_permission_expiry_date" class="form-control" value="<?php echo $user['cod_permission_expiry'] ? date('Y-m-d', strtotime($user['cod_permission_expiry'])) : ''; ?>">
                    </div>
                    <div class="form-group col-md-6">
                      <label>Expiry Time</label>
                      <input type="time" name="cod_permission_expiry_time" class="form-control" value="<?php echo $user['cod_permission_expiry'] ? date('H:i', strtotime($user['cod_permission_expiry'])) : ''; ?>">
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control"><?php echo htmlspecialchars($user['notes'] ?? ''); ?></textarea>
                  </div>
                  <div class="form-group">
                    <button type="button" class="btn btn-warning" id="adminConsentBtn">Confirm Admin Consent</button>
                    <input type="hidden" name="admin_consent" id="adminConsentInput" value="<?php echo $user['admin_consent']; ?>">
                    <span id="adminConsentStatus" class="ml-2">
                      <?php echo $user['admin_consent'] ? '<span class="badge badge-success">Confirmed</span>' : '<span class="badge badge-warning">Not Confirmed</span>'; ?>
                    </span>
                  </div>
                </div>
                <div class="card-footer text-right">
                  <a href="user_management.php" class="btn btn-secondary">Cancel</a>
                  <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plugins/sweetalert2/sweetalert2.min.js"></script>
<script>
$(function() {
  $('#adminConsentBtn').click(function(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Confirm Admin Consent',
      text: 'Are you sure you want to allow this user to complete their own COD pending orders?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Confirm',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $('#adminConsentInput').val('1');
        $('#adminConsentStatus').html('<span class="badge badge-success">Confirmed</span>');
      } else {
        $('#adminConsentInput').val('0');
        $('#adminConsentStatus').html('<span class="badge badge-warning">Not Confirmed</span>');
      }
    });
  });
});
</script>
</body>
</html>
