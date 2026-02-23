<?php
session_start();
include_once 'connectdb.php';

// Redirect unauthorized users
if(!isset($_SESSION['useremail']) || $_SESSION['role']=="User"){
    header('location:../index.php');
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

/////////////////////////////////////////////////
// DELETE USER
/////////////////////////////////////////////////
if(isset($_GET['delete_id'])){
    $delete = $pdo->prepare("DELETE FROM tbl_user WHERE userid=:id");
    $delete->bindParam(':id', $_GET['delete_id']);
    $delete->execute();

    $_SESSION['status'] = "User Deleted Successfully!";
    $_SESSION['status_code'] = "success";
    header("location: registration.php");
    exit();
}

/////////////////////////////////////////////////
// INSERT USER
/////////////////////////////////////////////////
if(isset($_POST['btnsave'])){
    $username = trim($_POST['txtuser']);
    $password = $_POST['txtpassword'];
    $contact = trim($_POST['txtcontact']);

    // Duplicate check
    $check = $pdo->prepare("SELECT * FROM tbl_user WHERE useremail=:user OR usercontact=:contact");
    $check->execute([':user'=>$username, ':contact'=>$contact]);
    if($check->rowCount() > 0){
        $_SESSION['status'] = "Duplicate Username or Contact Found!";
        $_SESSION['status_code'] = "error";
        header("location: registration.php");
        exit();
    }

    if(strpos($username, '@gmail.com') !== false){
        $_SESSION['status'] = "Username must not contain @gmail.com!";
        $_SESSION['status_code'] = "error";
        header("location: registration.php");
        exit();
    }

    if(strlen($password) < 8){
        $_SESSION['status'] = "Password must be at least 8 characters!";
        $_SESSION['status_code'] = "error";
        header("location: registration.php");
        exit();
    }

    if(!preg_match('/^[0-9]{11}$/', $contact)){
        $_SESSION['status'] = "Contact Number must be exactly 11 digits!";
        $_SESSION['status_code'] = "error";
        header("location: registration.php");
        exit();
    }

    $fulladdress = trim($_POST['street']).', '.trim($_POST['barangay']).', '.trim($_POST['city']).', '.trim($_POST['province']);

    $insert = $pdo->prepare("INSERT INTO tbl_user 
        (username,userage,birthday,gender,useremail,userpassword,usercontact,useraddress,role) 
        VALUES (:name,:age,:birthday,:gender,:user,:password,:contact,:address,:role)");

    $insert->execute([
        ':name'=>trim($_POST['txtname']),
        ':age'=>$_POST['txtage'],
        ':birthday'=>$_POST['txtbirthday'],
        ':gender'=>$_POST['txtgender'],
        ':user'=>$username,
        ':password'=>$password,
        ':contact'=>$contact,
        ':address'=>$fulladdress,
        ':role'=>$_POST['txtselect_option']
    ]);

    $_SESSION['status'] = "Registration Completed Successfully!";
    $_SESSION['status_code'] = "success";
    header("location: registration.php");
    exit();
}

/////////////////////////////////////////////////
// UPDATE USER
/////////////////////////////////////////////////
if(isset($_POST['btnupdate'])){
    $userId = $_POST['edit_id'];

    // Fetch current record
    $current = $pdo->prepare("SELECT * FROM tbl_user WHERE userid=:id");
    $current->execute([':id'=>$userId]);
    $curr = $current->fetch(PDO::FETCH_ASSOC);

    // Use new values if provided, otherwise keep old
    $name = !empty($_POST['edit_name']) ? trim($_POST['edit_name']) : $curr['username'];
    $age = !empty($_POST['edit_age']) ? $_POST['edit_age'] : $curr['userage'];
    $birthday = !empty($_POST['edit_birthday']) ? $_POST['edit_birthday'] : $curr['birthday'];
    $gender = !empty($_POST['edit_gender']) ? $_POST['edit_gender'] : $curr['gender'];
    $username = !empty($_POST['edit_useremail']) ? trim($_POST['edit_useremail']) : $curr['useremail'];
    $password = $_POST['edit_password']; // optional
    $contact = !empty($_POST['edit_contact']) ? trim($_POST['edit_contact']) : $curr['usercontact'];

    $addr = explode(',', $curr['useraddress']);
    $street = !empty($_POST['edit_street']) ? trim($_POST['edit_street']) : ($addr[0] ?? '');
    $barangay = !empty($_POST['edit_barangay']) ? trim($_POST['edit_barangay']) : ($addr[1] ?? '');
    $city = !empty($_POST['edit_city']) ? trim($_POST['edit_city']) : ($addr[2] ?? '');
    $province = !empty($_POST['edit_province']) ? trim($_POST['edit_province']) : ($addr[3] ?? '');
    $fulladdress = "$street, $barangay, $city, $province";

    $role = !empty($_POST['edit_role']) ? $_POST['edit_role'] : $curr['role'];

    // Duplicate check
    if($username != $curr['useremail'] || $contact != $curr['usercontact']){
        $check = $pdo->prepare("SELECT * FROM tbl_user WHERE (useremail=:user OR usercontact=:contact) AND userid != :id");
        $check->execute([':user'=>$username, ':contact'=>$contact, ':id'=>$userId]);
        if($check->rowCount() > 0){
            $_SESSION['status'] = "Duplicate Username or Contact Found!";
            $_SESSION['status_code'] = "error";
            header("location: registration.php");
            exit();
        }
    }

    // Password validation
    if(!empty($password) && strlen($password) < 8){
        $_SESSION['status'] = "Password must be at least 8 characters!";
        $_SESSION['status_code'] = "error";
        header("location: registration.php");
        exit();
    }

    // Contact validation
    if(!preg_match('/^[0-9]{11}$/', $contact)){
        $_SESSION['status'] = "Contact Number must be exactly 11 digits!";
        $_SESSION['status_code'] = "error";
        header("location: registration.php");
        exit();
    }

    // Update user
    $update = $pdo->prepare("UPDATE tbl_user SET
        username=:name,
        userage=:age,
        birthday=:birthday,
        gender=:gender,
        useremail=:useremail,
        userpassword=CASE WHEN :password != '' THEN :password ELSE userpassword END,
        usercontact=:contact,
        useraddress=:address,
        role=:role
        WHERE userid=:id");

    $update->execute([
        ':name'=>$name,
        ':age'=>$age,
        ':birthday'=>$birthday,
        ':gender'=>$gender,
        ':useremail'=>$username,
        ':password'=>$password,
        ':contact'=>$contact,
        ':address'=>$fulladdress,
        ':role'=>$role,
        ':id'=>$userId
    ]);

    $_SESSION['status'] = "User Information Updated Successfully!";
    $_SESSION['status_code'] = "success";
    header("location: registration.php");
    exit();
}

include_once "header.php";
?>

<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 mb-2">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#createUserModal">
                        Create New User Account
                    </button>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4>User List</h4></div>
                        <div class="card-body" style="overflow-x:auto;">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th><th>Name</th><th>Age</th><th>Birthday</th><th>Gender</th>
                                        <th>Username</th><th>Password</th><th>Contact</th>
                                        <th>Street</th><th>Barangay</th><th>City</th><th>Province</th>
                                        <th>Role</th><th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $select = $pdo->prepare("SELECT * FROM tbl_user ORDER BY userid ASC");
                                $select->execute();
                                $users = $select->fetchAll(PDO::FETCH_OBJ);
                                foreach($users as $row):
                                    $addr = explode(',', $row->useraddress);
                                    $street = $addr[0] ?? '';
                                    $barangay = $addr[1] ?? '';
                                    $city = $addr[2] ?? '';
                                    $province = $addr[3] ?? '';
                                ?>
                                    <tr>
                                        <td><?= $row->userid ?></td>
                                        <td><?= $row->username ?></td>
                                        <td><?= $row->userage ?></td>
                                        <td><?= $row->birthday ?></td>
                                        <td><?= $row->gender ?></td>
                                        <td><?= $row->useremail ?></td>
                                        <td><?= $row->userpassword ?></td>
                                        <td><?= $row->usercontact ?></td>
                                        <td><?= $street ?></td>
                                        <td><?= $barangay ?></td>
                                        <td><?= $city ?></td>
                                        <td><?= $province ?></td>
                                        <td><?= $row->role ?></td>
                                        <td>
                                            <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#editModal<?= $row->userid ?>">Edit</button>
                                            <a href="registration.php?delete_id=<?= $row->userid ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure to delete this user?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CREATE USER MODAL -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <form method="post" id="userForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Create New User Account</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- same fields as before -->
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Full Name</label>
                            <input type="text" name="txtname" class="form-control" placeholder="Full Name" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Age</label>
                            <input type="number" name="txtage" class="form-control" placeholder="Age" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Birthday</label>
                            <input type="date" name="txtbirthday" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="txtgender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="txtuser" id="txtuser" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <label>Password (min 8 characters)</label>
                        <input type="text" name="txtpassword" id="txtpassword" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="txtcontact" class="form-control" placeholder="11-digit Contact Number" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Street / House No.</label>
                            <input type="text" name="street" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Barangay</label>
                            <input type="text" name="barangay" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>City / Municipality</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Province</label>
                            <input type="text" name="province" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="txtselect_option" class="form-control" required>
                            <option value="">Select Role</option>
                            <option>Admin</option>
                            <option>User</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="btnsave" class="btn btn-primary">Register User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT MODALS -->
<?php foreach($users as $row):
    $addr = explode(',', $row->useraddress);
    $street = $addr[0] ?? '';
    $barangay = $addr[1] ?? '';
    $city = $addr[2] ?? '';
    $province = $addr[3] ?? '';
?>
<div class="modal fade" id="editModal<?= $row->userid ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_id" value="<?= $row->userid ?>">
                    <!-- all form fields same as before, prefilled with user values -->
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Full Name</label>
                            <input type="text" name="edit_name" class="form-control" value="<?= $row->username ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Age</label>
                            <input type="number" name="edit_age" class="form-control" value="<?= $row->userage ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Birthday</label>
                            <input type="date" name="edit_birthday" class="form-control" value="<?= $row->birthday ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="edit_gender" class="form-control">
                            <option <?= $row->gender=="Male"?'selected':'' ?>>Male</option>
                            <option <?= $row->gender=="Female"?'selected':'' ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="edit_useremail" class="form-control" value="<?= $row->useremail ?>">
                    </div>
                    <div class="form-group">
                        <label>Password (leave blank if not changing)</label>
                        <input type="text" name="edit_password" class="form-control" placeholder="Enter new password if changing">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="edit_contact" class="form-control" value="<?= $row->usercontact ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Street / House No.</label>
                            <input type="text" name="edit_street" class="form-control" value="<?= $street ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Barangay</label>
                            <input type="text" name="edit_barangay" class="form-control" value="<?= $barangay ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label>City / Municipality</label>
                            <input type="text" name="edit_city" class="form-control" value="<?= $city ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Province</label>
                            <input type="text" name="edit_province" class="form-control" value="<?= $province ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="edit_role" class="form-control">
                            <option <?= $row->role=="Admin"?'selected':'' ?>>Admin</option>
                            <option <?= $row->role=="User"?'selected':'' ?>>User</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="btnupdate" class="btn btn-info">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
    $('input[name="txtname"]').on('keyup', function(){
        let name = $(this).val().toLowerCase().replace(/\s+/g, '');
        $('input[name="txtuser"]').val(name);
    });

    $('#userForm').on('submit', function(e){
        let username = $('#txtuser').val();
        let password = $('#txtpassword').val();
        let contact = $('input[name="txtcontact"]').val();

        if(username.includes('@gmail.com')){
            e.preventDefault();
            Swal.fire({icon:'error',title:'Invalid Username',text:'Username must not contain @gmail.com!'});
            return false;
        }
        if(password.length < 8){
            e.preventDefault();
            Swal.fire({icon:'error',title:'Password Too Short',text:'Password must be at least 8 characters!'});
            return false;
        }
        if(!/^\d{11}$/.test(contact)){
            e.preventDefault();
            Swal.fire({icon:'error',title:'Invalid Contact',text:'Contact Number must be exactly 11 digits!'});
            return false;
        }
    });

    <?php if(isset($_SESSION['status'])){ ?>
    Swal.fire({icon:'<?php echo $_SESSION['status_code']; ?>',title:'<?php echo $_SESSION['status']; ?>',showConfirmButton:true,timer:3000});
    <?php unset($_SESSION['status']); } ?>
});
</script>
<style>
.modal-dialog-scrollable { max-height: 90vh; }
</style>
