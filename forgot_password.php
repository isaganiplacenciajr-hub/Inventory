<?php
include_once "ui/connectdb.php";
if (file_exists('ui/utils.php')) {
    include_once 'ui/utils.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$status = '';
$status_code = '';
$step = 1;
$emailOrUser = '';
$displayName = '';

if (isset($_POST['btn_send_code'])) {
    $emailOrUser = trim($_POST['txt_email_username'] ?? '');

    if ($emailOrUser === '') {
        $status = 'Please enter your email address or username.';
        $status_code = 'error';
    } else {
        $query = $pdo->prepare("SELECT * FROM tbl_user WHERE useremail=:input OR username=:input LIMIT 1");
        $query->execute([':input' => $emailOrUser]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $status = 'No user found for the given email/username.';
            $status_code = 'error';
        } else {
            $step = 2;
            $displayName = $user['username'];
            $_SESSION['reset_userid'] = $user['userid'];

            $status = 'User found. Please enter your new password below.';
            $status_code = 'success';
        }
    }
}

if (isset($_POST['btn_reset_password'])) {
    $userId = $_SESSION['reset_userid'] ?? null;
    $newPassword = trim($_POST['txt_new_password'] ?? '');
    $confirmPassword = trim($_POST['txt_confirm_password'] ?? '');

    if (!$userId || !$newPassword || !$confirmPassword) {
        $status = 'Please fill in all reset fields.';
        $status_code = 'error';
        $step = 2;
    } elseif (strlen($newPassword) < 8) {
        $status = 'Password must be at least 8 characters long.';
        $status_code = 'error';
        $step = 2;
    } elseif ($newPassword !== $confirmPassword) {
        $status = 'New password and confirm password do not match.';
        $status_code = 'error';
        $step = 2;
    } else {
        $update = $pdo->prepare("UPDATE tbl_user SET userpassword=:password WHERE userid=:userid");
        $update->execute([':password' => $newPassword, ':userid' => $userId]);

        unset($_SESSION['reset_userid']);

        $status = 'Password updated successfully. You can now login.';
        $status_code = 'success';
        $step = 1;

        header('refresh:3;url=index.php');
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SPM LPG INVENTORY | Forgot Password</title>

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
body.login-page {
    background: url('dist/img/shop.jpg') no-repeat center center fixed;
    background-size: cover;
}
body.login-page::before {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.35);
    z-index: -1;
}
.card { background: rgba(255,255,255,0.35) !important; backdrop-filter: blur(10px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.form-control { background: rgba(255,255,255,0.65); border: none; }
.form-control:focus { background: rgba(255,255,255,0.8); box-shadow: none; }
</style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h1 d-block mb-0"><b>SPM LPG</b> INVENTORY</a>
      <p class="text-white">Forgot Password</p>
    </div>
    <div class="card-body">
      <?php if($step === 1): ?>
        <p class="login-box-msg text-white">Enter your email or username to begin reset.</p>
        <form method="post">
          <div class="input-group mb-3">
            <input type="text" class="form-control" name="txt_email_username" placeholder="Username or Email" required value="<?php echo htmlspecialchars($emailOrUser); ?>">
            <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
          </div>
          <div class="row justify-content-center">
            <div class="col-6"><button type="submit" name="btn_send_code" class="btn btn-primary btn-block">Submit</button></div>
          </div>
        </form>
      <?php endif; ?>

      <?php if($step === 2): ?>
        <p class="login-box-msg text-white">Hello <strong><?php echo htmlspecialchars($displayName); ?></strong>. Please set your new password below.</p>

        <form method="post">
          <div class="input-group mb-3">
            <input type="password" class="form-control" name="txt_new_password" placeholder="New Password" required>
            <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
          </div>
          <div class="input-group mb-3">
            <input type="password" class="form-control" name="txt_confirm_password" placeholder="Confirm Password" required>
            <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
          </div>

          <div class="row justify-content-center">
            <div class="col-6"><button type="submit" name="btn_reset_password" class="btn btn-success btn-block">Reset Password</button></div>
          </div>
        </form>
      <?php endif; ?>

      <p class="mt-3 mb-1 text-center"><a href="index.php">Back to Login</a></p>
    </div>
  </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<?php if($status !== ''): ?>
<script>
Swal.fire({
  toast: true,
  position: 'top',
  icon: '<?php echo $status_code; ?>',
  title: '<?php echo htmlspecialchars($status, ENT_QUOTES); ?>',
  showConfirmButton: false,
  timer: 4000
});
</script>
<?php endif; ?>

</body>
</html>