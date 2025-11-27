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

    $select->bindParam(':user', $userInput);
    $select->bindParam(':password', $password);
    $select->execute();

    $row = $select->fetch(PDO::FETCH_ASSOC);

    if ($row) {
       
        $_SESSION['status'] = "Login successful by " . ucfirst($row['role']); // Set the status message based on the user's role
        $_SESSION['status_code'] = "success";
        $_SESSION['userid'] = $row['userid'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['useremail'] = $row['useremail'];
        $_SESSION['role'] = $row['role'];

        // Log successful login
        if (function_exists('logActivity')) {
            logActivity($row['useremail'], 'Login', 'Authentication', 'User logged in successfully as ' . ucfirst($row['role']));
        }

        if ($row['role'] == "Admin") {
          header('refresh:1; ui/dashboard.php');
        } else if ($row['role'] == "User") {
          header('refresh:3; ui/user.php');
        }
    } else {
        $_SESSION['status'] = "Invalid email/username or password";
        $_SESSION['status_code'] = "error";
        
        // Log failed login attempt
        if (function_exists('logActivity')) {
            $attemptedUser = $_POST['txt_email'] ?? 'unknown';
            logActivity($attemptedUser, 'Login Failed', 'Authentication', 'Failed login attempt with invalid credentials');
        }
    }
}
?>











<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SPM LPG INVENTORY | Log in </title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">

   <!-- SweetAlert2 -->
   <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
   <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <!-- Toastr -->
  <link rel="stylesheet" href="plugins/toastr/toastr.min.css">


  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <!-- /.login-logo -->
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="../../index2.html" class="h1"><b>SPM LPG</b> INVENTORY</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Enter your details below to continue</p>

      <form action="" method="post">
        <div class="input-group mb-3">

        <input type="text" class="form-control" placeholder="Username" name="txt_email" required>


          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">

          <input type="password" class="form-control" placeholder="Password" name="txt_password"required>


          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
            <a href="forgot-password.html"> Forgot password</a>
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block" name="btn_login">Login</button>
          </div>
          <!-- /.col -->
        </div>
      </form>

    
      <!-- /.social-auth-links -->

      <p class="mb-1">
      
      </p>
      <p class="mb-0">
       
      </p>
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- sweetalert for confirmation deletion -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Toastr -->
<script src="plugins/toastr/toastr.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
</body>
</html>


<?php

if(isset($_SESSION['status']) &&  $_SESSION['status']!='')
 
{

?>
<script>
$(function() {
    var Toast = Swal.mixin({
      toast: true,
      position: 'top',
      showConfirmButton: false,
      timer: 5000
    });

    
      Toast.fire({
        icon: '<?php echo $_SESSION['status_code'];?>',
        title: '<?php echo $_SESSION['status'];?>'
      })
    });

</script>


<?php
unset($_SESSION['status']);
}


?>