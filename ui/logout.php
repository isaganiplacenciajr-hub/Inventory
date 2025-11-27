<?php
if (file_exists('connectdb.php')) {
    include_once 'connectdb.php';
}
if (file_exists('utils.php')) {
    include_once 'utils.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log logout before destroying session
if (function_exists('logActivity') && isset($_SESSION['useremail'])) {
    logActivity($_SESSION['useremail'], 'Logout', 'Authentication', 'User logged out');
}

session_destroy();

header('location:../index.php');




?>