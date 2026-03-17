<?php

include_once 'connectdb.php';
include_once 'utils.php';
session_start();

if(isset($_GET['id'])){

    $id = $_GET['id'];

    // Get user details before deletion for logging
    $select = $pdo->prepare("SELECT userid, username, useremail, role FROM tbl_user WHERE userid = :uid");
    $select->execute([':uid' => $id]);
    $user_data = $select->fetch(PDO::FETCH_ASSOC);

    $delete=$pdo->prepare("delete from tbl_user where userid=:id");
    $delete->execute([':id' => $id]);

    // Log user deletion
    if ($user_data && function_exists('logActivity')) {
        $description = 'User deleted: ' . $user_data['username'] . ' (' . $user_data['useremail'] . ') Role: ' . $user_data['role'];
        logActivity($_SESSION['useremail'] ?? 'System', 'Delete User', 'User Management', $description, 'INFO');
    }
}

?>