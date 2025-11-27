
<?php
include_once "ui/connectdb.php";
session_start();

if (isset($_POST['btn_login'])) {
    $userInput = $_POST['txt_email'];
    $password = $_POST['txt_password'];

    
    if (strpos($userInput, '@') !== false) {
     
        $select = $pdo->prepare("SELECT * FROM tbl_user WHERE useremail=:user AND userpassword=:password");
    } else {
      
        $select = $pdo->prepare("SELECT * FROM tbl_user WHERE usercontact=:user AND userpassword=:password");
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

        if ($row['role'] == "Admin") {
          header('refresh:1; ui/dashboard.php');
        } 
    } else {
        $_SESSION['status'] = "Invalid email/username or password";
        $_SESSION['status_code'] = "error";
    }
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title> Log in</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="../../index2.html"><b>Admin</b>LTE</a>
  </div>
  <!-- /.login-logo -->
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <form action="ui/dashboard.php" method="post">
        <div class="input-group mb-3">
          <input type="email" class="form-control" placeholder="Email">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">
                Remember Me
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          </div>
          <!-- /.col -->
        </div>
      </form>

      <p class="mb-0">
        <a href="register.html" class="text-center">Register a new membership</a>
        <a href="ui/createuser2.php" class="nav-link">
      </p>
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
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
