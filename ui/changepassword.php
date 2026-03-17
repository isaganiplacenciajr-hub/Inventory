<?php

include_once'connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include logging helper
if (file_exists(__DIR__ . '/utils.php')) {
    include_once __DIR__ . '/utils.php';
}

if($_SESSION['useremail']==""){

  header('location:../index.php');
  
  
  }



  if($_SESSION['role']=="Admin"){

    include_once"header.php";
  }else{

    include_once"headeruser.php";
  }







if(isset($_POST['btnupdate'])){

$oldpassword_txt=$_POST['txt_oldpassword'];
$newpassword_txt=$_POST['txt_newpassword'];
$rnewpassword_txt=$_POST['txt_rnewpassword'];

//echo $oldpassword_txt."-".$newpassword_txt."-".$rnewpassword_txt;



$email=$_SESSION['useremail'];

$select= $pdo->prepare("select * from tbl_user where useremail='$email'");

$select->execute();
$row=$select->fetch(PDO::FETCH_ASSOC);

$useremail_db = $row['useremail'];
$password_db= $row['userpassword'];






if($oldpassword_txt==$password_db){

if($newpassword_txt==$rnewpassword_txt){


  $update=$pdo->prepare("update tbl_user set userpassword=:pass where useremail=:email");




  $update->bindParam(':pass',$rnewpassword_txt);
  $update->bindParam(':email',$email);

  if($update->execute()){

$_SESSION['status']="Password Updated Successfully";
$_SESSION['status_code']="Success";

    // Log successful password change
    if (function_exists('logActivity')) {
        logActivity($email, 'Change Password', 'User', 'Password updated successfully');
    }

  }else{

  $_SESSION['status']="Password Does Not Updated Successfully";
  $_SESSION['status_code']="Error";

    if (function_exists('logActivity')) {
        logActivity($email, 'Change Password Failed', 'User', 'Database update failed');
    }

  }

  //$_SESSION['status']="New Password Matched";
  //$_SESSION['status_code']="Success";

}else{

  $_SESSION['status']="New Password Does Not Matched";
  $_SESSION['status_code']="Error";
  
  if (function_exists('logActivity')) {
      logActivity($email, 'Change Password Failed', 'User', 'New password and confirmation do not match');
  }

}





}else{

  $_SESSION['status']="Password Does Not Matched";
  $_SESSION['status_code']="Error";
  
  if (function_exists('logActivity')) {
      logActivity($email, 'Change Password Failed', 'User', 'Current password did not match');
  }

}






}








?>


<?php if($_SESSION['role']=="Admin"){ ?>
<!-- PAGE CONTENT -->
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Change Password</h1>
        </div>
      </div>
    </div>
  </div>
  <div class="content">
    <div class="container-fluid">
<?php } ?>

<div class="row mb-2">
  <div class="col-lg-12">
    <div class="card card-info card-outline">
      <div class="card-header">
        <h5 class="m-0">Change Password</h5>
      </div>
      <div class="card-body">
        <!-- form start -->
        <form class="form-horizontal" action="" method="post">
          <div class="card-body">

            <div class="form-group row">
              <label for="inputPassword3" class="col-sm-2 col-form-label">Old Password</label>
              <div class="col-sm-10">
                <input type="password" class="form-control" id="inputPassword3" placeholder="Old Password" name="txt_oldpassword">
              </div>
            </div>

            <div class="form-group row">
              <label for="inputPassword3" class="col-sm-2 col-form-label"> New Password</label>
              <div class="col-sm-10">
                <input type="password" class="form-control" id="inputPassword3" placeholder=" New Password" name="txt_newpassword">
              </div>
            </div>

            <div class="form-group row">
              <label for="inputPassword3" class="col-sm-2 col-form-label">Repeat New Password</label>
              <div class="col-sm-10">
                <input type="password" class="form-control" id="inputPassword3" placeholder="Repeat New Password" name="txt_rnewpassword">
              </div>
            </div>

          </div>
          <!-- /.card-body -->
          <div class="card-footer">
            <button type="submit" class="btn btn-info" name="btnupdate">Update Password</button>
          </div>
          <!-- /.card-footer -->
        </form>
      </div> <!-- /.card-body -->
    </div> <!-- /.card -->
  </div> <!-- /.col-lg-12 -->
</div> <!-- /.row -->

<?php if($_SESSION['role']=="Admin"){ ?>
    </div> <!-- /.container-fluid -->
  </div> <!-- /.content -->
</div> <!-- /.content-wrapper -->
<?php } else { ?>
<!-- Close content tags opened in headeruser.php -->
</div>          <!-- End col -->
</div>          <!-- End row -->
</div>      <!-- End container-fluid -->
</section>   <!-- End content section -->
</div>      <!-- End content-wrapper -->
</div>      <!-- End wrapper -->
<?php } ?>

<?php
include_once"footer.php";
?>






<?php
if(isset($_SESSION['status']) && $_SESSION['status']!='')

{

?>
<script>


   
      Swal.fire({
        icon: '<?php echo $_SESSION['status_code'];?>',
        title: '<?php echo $_SESSION['status'];?>'
      });


</script>



<?php
unset($_SESSION['status']);
}
?>
