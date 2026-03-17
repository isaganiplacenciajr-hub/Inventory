<?php
include_once 'connectdb.php';
session_start();
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header('location:../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    // Validation
    if ($username === '' || $fullname === '' || $password === '') {
        header('Location: user_management.php?error=missing');
        exit;
    }

    // Check if username exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tbl_user WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: user_management.php?error=exists');
        exit;
    }

    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert user (role always User, permissions default off)
    $insert = $pdo->prepare('INSERT INTO tbl_user (username, fullname, password, status, role, cod_self_completion_permission, cod_permission_expiry, admin_consent) VALUES (?, ?, ?, ?, ?, 0, NULL, 0)');
    $insert->execute([$username, $fullname, $hashed, $status, 'User']);

    header('Location: user_management.php?success=added');
    exit;
}
header('Location: user_management.php');
