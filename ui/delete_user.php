<?php
include_once 'connectdb.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userid = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($userid <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent deleting admin or self
$stmt = $pdo->prepare('SELECT * FROM tbl_user WHERE userid = ?');
$stmt->execute([$userid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'User') {
    echo json_encode(['success' => false, 'message' => 'User not found or not allowed']);
    exit;
}

$pdo->prepare('DELETE FROM tbl_user WHERE userid = ?')->execute([$userid]);
echo json_encode(['success' => true]);
