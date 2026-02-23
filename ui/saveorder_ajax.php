<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
ob_start();

session_start();
require_once 'connectdb.php';

$response = [
    'success' => false,
    'message' => '',
    'invoice_id' => null
];

try {
    if (!isset($_POST['btnsaveorder'])) {
        throw new Exception('Invalid request');
    }

    $pdo->beginTransaction();

    $orderdate     = date("Y-m-d");
    $subtotal      = floatval($_POST['txtsubtotal'] ?? 0);
    $discount      = floatval($_POST['txtdiscount'] ?? 0);
    $sgst          = floatval($_POST['txtsgst'] ?? 0);
    $cgst          = floatval($_POST['txtcgst'] ?? 0);
    $total         = floatval($_POST['txttotal'] ?? 0);
    $payment_type  = $_POST['rb'] ?? 'cash';
    $due           = floatval($_POST['txtdue'] ?? 0);
    $paid          = floatval($_POST['txtpaid'] ?? 0);
    
    // Customer information
    $customer_name    = trim($_POST['txtcustomer_name'] ?? '');
    $customer_contact = trim($_POST['txtcustomer_contact'] ?? '');
    $customer_address = trim($_POST['txtcustomer_address'] ?? '');
    
    // Validate customer name is required
    if (empty($customer_name)) {
        throw new Exception('Customer name is required');
    }

    // Arrays from form
    $arr_pid       = $_POST['pid_arr'] ?? [];
    $arr_name      = $_POST['product_arr'] ?? [];
    $arr_stock     = $_POST['stock_c_arr'] ?? [];
    $arr_qty       = $_POST['quantity_arr'] ?? [];
    $arr_price     = $_POST['price_arr'] ?? [];
    $arr_total     = $_POST['saleprice_arr'] ?? [];
    $arr_service   = $_POST['service_c_arr'] ?? [];
    $arr_addfee    = $_POST['addfee_c_arr'] ?? [];

    // Check if cart is empty
    if (empty($arr_pid)) {
        throw new Exception('Cart is empty');
    }

    // Insert into tbl_invoice (main)
    $insertInvoice = $pdo->prepare("
        INSERT INTO tbl_invoice 
        (order_date, subtotal, discount, sgst, cgst, total, payment_type, due, paid, customer_name, customer_contact, customer_address)
        VALUES 
        (:order_date, :subtotal, :discount, :sgst, :cgst, :total, :payment_type, :due, :paid, :customer_name, :customer_contact, :customer_address)
    ");

    $insertInvoice->execute([
        ':order_date'       => $orderdate,
        ':subtotal'         => $subtotal,
        ':discount'         => $discount,
        ':sgst'             => $sgst,
        ':cgst'             => $cgst,
        ':total'            => $total,
        ':payment_type'     => $payment_type,
        ':due'              => $due,
        ':paid'             => $paid,
        ':customer_name'    => $customer_name,
        ':customer_contact' => $customer_contact,
        ':customer_address' => $customer_address,
    ]);

    $invoice_id = $pdo->lastInsertId();

    if ($invoice_id) {
        // Update product stock
        $updateStock = $pdo->prepare("UPDATE tbl_product SET stock = :stock WHERE pid = :pid");

        // Insert invoice details
        $insertDetail = $pdo->prepare("
            INSERT INTO tbl_invoice_details
            (invoice_id, product_id, product_name, qty, rate, saleprice, order_date, servicetype, addfee)
            VALUES 
            (:invoice_id, :pid, :product_name, :qty, :rate, :saleprice, :order_date, :servicetype, :addfee)
        ");

        for ($i = 0; $i < count($arr_pid); $i++) {
            $pid   = intval($arr_pid[$i]);
            $name  = $arr_name[$i] ?? '';
            $stock = isset($arr_stock[$i]) ? intval($arr_stock[$i]) : 0;
            $qty   = isset($arr_qty[$i]) ? intval($arr_qty[$i]) : 0;
            $rate  = isset($arr_price[$i]) ? floatval($arr_price[$i]) : 0;
            $lineTotal = isset($arr_total[$i]) ? floatval($arr_total[$i]) : 0;
            $service = $arr_service[$i] ?? 'Pick up';
            $addfee = isset($arr_addfee[$i]) ? floatval($arr_addfee[$i]) : 0.00;

            // Update stock
            $remaining = $stock - $qty;
            if ($remaining < 0) {
                throw new Exception('Insufficient stock for ' . htmlspecialchars($name));
            }

            $updateStock->execute([
                ':stock' => $remaining,
                ':pid'   => $pid,
            ]);

            // Insert details
            $insertDetail->execute([
                ':invoice_id'   => $invoice_id,
                ':pid'          => $pid,
                ':product_name' => $name,
                ':qty'          => $qty,
                ':rate'         => $rate,
                ':saleprice'    => $lineTotal,
                ':order_date'   => $orderdate,
                ':servicetype'  => $service,
                ':addfee'       => $addfee,
            ]);
        }

        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'Order saved successfully';
        $response['invoice_id'] = $invoice_id;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
exit;
?>
