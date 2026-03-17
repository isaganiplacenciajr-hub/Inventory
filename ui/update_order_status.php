<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
ob_start();

session_start();
require_once 'connectdb.php';

$response = [
    'success' => false,
    'message' => ''
];

try {
    if (!isset($_POST['invoice_id']) || !isset($_POST['status'])) {
        throw new Exception('Missing required parameters');
    }

    $invoice_id = intval($_POST['invoice_id']);
    $status = $_POST['status'];

    // Validate status
    if (!in_array($status, ['Complete', 'Pending'])) {
        throw new Exception('Invalid status');
    }

    // Update invoice status
    $update = $pdo->prepare("UPDATE tbl_invoice SET status = :status WHERE invoice_id = :invoice_id");
    $update->execute([
        ':status' => $status,
        ':invoice_id' => $invoice_id
    ]);

    $response['success'] = true;
    $response['message'] = 'Order status updated successfully';

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
exit;
?>
