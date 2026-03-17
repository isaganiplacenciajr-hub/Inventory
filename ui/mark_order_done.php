<?php
include_once 'connectdb.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['userid']) || $_SESSION['role'] === 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Get user permissions
$stmt = $pdo->prepare('SELECT cod_self_completion_permission, cod_permission_expiry, admin_consent FROM tbl_user WHERE userid = ?');
$stmt->execute([$_SESSION['userid']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || !$user['cod_self_completion_permission'] || !$user['admin_consent']) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}
if (!$user['cod_permission_expiry'] || strtotime($user['cod_permission_expiry']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Permission expired']);
    exit;
}

// Check order
$stmt = $pdo->prepare('SELECT * FROM tbl_invoice WHERE invoice_id = ? AND created_by = ? AND status = ? AND payment_type = ?');
$stmt->execute([$order_id, $_SESSION['userid'], 'Pending', 'cod']);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not eligible']);
    exit;
}

// Mark as done
$pdo->prepare('UPDATE tbl_invoice SET status = ?, completed_by = ? WHERE invoice_id = ?')->execute(['Complete', $_SESSION['username'], $order_id]);

// Log action (simple log, adjust as needed)
$desc = 'Completed by: ' . $_SESSION['username'] . ' (COD Self Completion Permission)';
$pdo->prepare('INSERT INTO transaction_log (invoice_id, action, description, created_at) VALUES (?, ?, ?, NOW())')->execute([$order_id, 'Complete', $desc]);

echo json_encode(['success' => true]);
