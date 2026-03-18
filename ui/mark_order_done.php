<?php
include_once 'connectdb.php';
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['userid']) || $_SESSION['role'] === 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Check order (allow pending orders for any payment type; COD has extra permission check)
$stmt = $pdo->prepare('SELECT * FROM tbl_invoice WHERE invoice_id = ? AND LOWER(status) = ?');
$stmt->execute([$order_id, 'pending']);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not eligible']);
    exit;
}

// For COD orders, enforce self-completion permission
if (strtolower(trim($order['payment_type'])) === 'cod') {
    $stmt = $pdo->prepare('SELECT cod_self_completion_permission, cod_permission_expiry, admin_consent FROM tbl_user WHERE userid = ?');
    $stmt->execute([$_SESSION['userid']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !$user['cod_self_completion_permission'] || !$user['admin_consent']) {
        echo json_encode(['success' => false, 'message' => 'Permission denied for COD completion']);
        exit;
    }
    if (!empty($user['cod_permission_expiry']) && strtotime($user['cod_permission_expiry']) < time()) {
        echo json_encode(['success' => false, 'message' => 'COD completion permission expired']);
        exit;
    }
}

// Allow completion by owner or permitted user (optional strict mode)
// if (!($order['created_by'] == $_SESSION['userid'] || (isset($order['created_by_id']) && $order['created_by_id'] == $_SESSION['userid']))) {
//     echo json_encode(['success' => false, 'message' => 'Not eligible for this order']);
//     exit;
// }

// Mark as done
try {
    $updateStmt = $pdo->prepare('UPDATE tbl_invoice SET status = ?, completed_by = ? WHERE invoice_id = ?');
    if (!$updateStmt->execute(['Complete', $_SESSION['username'], $order_id])) {
        $errorInfo = $updateStmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . ($errorInfo[2] ?? 'Unknown')]);
        exit;
    }

    // Log action (optional)
    $desc = 'Completed by: ' . $_SESSION['username'] . ' (COD Self Completion Permission)';
    $checkLogTableStmt = $pdo->query("SHOW TABLES LIKE 'transaction_log'");
    $tableExists = (bool)$checkLogTableStmt->fetch(PDO::FETCH_ASSOC);

    if ($tableExists) {
        $logStmt = $pdo->prepare('INSERT INTO transaction_log (invoice_id, action, description, created_at) VALUES (?, ?, ?, NOW())');
        if (!$logStmt->execute([$order_id, 'Complete', $desc])) {
            $errorInfo = $logStmt->errorInfo();
            // Non-fatal: log failure should not break order completion
            error_log('transaction_log insert failed: ' . ($errorInfo[2] ?? 'Unknown'));
        }
    } else {
        error_log('transaction_log table not found; skipping log entry');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    exit;
}
