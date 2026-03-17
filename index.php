<?php
include_once "ui/connectdb.php";
if (file_exists('ui/utils.php')) {
    include_once 'ui/utils.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['btn_login'])) {
    $userInput = $_POST['txt_email'];
    $password = $_POST['txt_password'];

    if (strpos($userInput, '@') !== false) {
        $select = $pdo->prepare("SELECT * FROM tbl_user WHERE useremail=:user AND userpassword=:password");
    } else {
        $select = $pdo->prepare("SELECT * FROM tbl_user WHERE username=:user AND userpassword=:password");
    }

    $select->execute([':user' => $userInput, ':password' => $password]);
    $row = $select->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $_SESSION['status'] = "Login successful by " . ucfirst($row['role']);
        $_SESSION['status_code'] = "success";
        $_SESSION['userid'] = $row['userid'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['useremail'] = $row['useremail'];
        $_SESSION['role'] = $row['role'];

        // Log successful login
        if (function_exists('logActivity')) {
            $description = 'User logged in successfully as ' . ucfirst($row['role']);
            $extra = ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown', 'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)];
            logActivity($row['useremail'], 'Login', 'Authentication', $description, 'INFO', $extra);
        }

        if ($row['role'] == "Admin") {
            header('refresh:1; ui/dashboard.php');
        } else {
            header('refresh:3; ui/user.php');
        }
    } else {
        $_SESSION['status'] = "Invalid email/username or password";
        $_SESSION['status_code'] = "error";

        // Log failed login attempt
        if (function_exists('logActivity')) {
            $description = 'Failed login attempt with input: ' . htmlspecialchars($userInput);
            $extra = ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown', 'attempted_user' => $userInput];
            logActivity('Unknown', 'Login Failed', 'Authentication', $description, 'WARNING', $extra);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SPM LPG INVENTORY | Login</title>

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
/* BACKGROUND */
body.login-page {
    background: url("dist/img/shop.jpg") no-repeat center center fixed;
    background-size: cover;
}

body.login-page::before {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.35);
    z-index: -1;
}

/* GLASS CARD */
.card {
    background: rgba(255,255,255,0.35) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.3);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}

/* INPUTS */
.form-control {
    background: rgba(255,255,255,0.65);
    border: none;
}

.form-control:focus {
    background: rgba(255,255,255,0.8);
    box-shadow: none;
}

/* TEXT */
.card-header a {
    color: #fff !important;
}

/* HIGHLIGHTED BRANCH */
.branch-text {
    display: inline-block;
    margin-top: 6px;
    padding: 4px 14px;
    font-size: 14px;                /* bigger */
    font-weight: 600;
    letter-spacing: 1px;
    color: #fff;
    background: rgba(0,123,255,0.85); /* highlight */
    border-radius: 20px;
    box-shadow: 0 0 10px rgba(0,123,255,0.6);
}
</style>
</head>

<body class="hold-transition login-page">

<div class="login-box">
  <div class="card card-outline card-primary">

    <!-- HEADER -->
    <div class="card-header text-center">
      <a href="#" class="h1 d-block mb-0">
        <b>SPM LPG</b> INVENTORY
      </a>
      <?php 
        include_once __DIR__ . '/config/branch.php';
      ?>
      <span class="branch-text">
        <?php echo htmlspecialchars($activeBranchData['display']); ?>
      </span>
      <div style="color:#fff; font-size:13px; margin-top:2px;">
        <?php echo htmlspecialchars($activeBranchData['address']); ?>
      </div>
    </div>

    <div class="card-body">
      <p class="login-box-msg text-white">Enter your details below to continue</p>

      <form method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Username or Email" name="txt_email" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Password" name="txt_password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

        <div class="row justify-content-center mt-4">
          <div class="col-6">
            <button type="submit" class="btn btn-primary btn-block" name="btn_login">
              Login
            </button>
          </div>
        </div>
      </form>

      <div class="mt-3 text-center">
        <a href="forgot_password.php" class="text-white">Forgot Password?</a>
      </div>
    </div>
  </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<?php if(isset($_SESSION['status'])): ?>
<script>
Swal.fire({
  toast: true,
  position: 'top',
  icon: '<?php echo $_SESSION['status_code']; ?>',
  title: '<?php echo $_SESSION['status']; ?>',
  showConfirmButton: false,
  timer: 4000
});
</script>
<?php unset($_SESSION['status']); endif; ?>

</body>
</html>
