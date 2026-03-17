<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
ob_start();

session_start();
require_once 'connectdb.php';
require_once 'utils.php';

$response = [
    'success' => false,
    'message' => '',
    'invoice_id' => null
];

try {
    if (!isset($_POST['btnsaveorder'])) {
        throw new Exception('Invalid request');
    }

    // ensure creator metadata columns exist (first-time upgrade)
    $useMeta = false;
    try {
        $has = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'created_by_id'")->fetch();
        if (!$has) {
            $pdo->exec("ALTER TABLE tbl_invoice \
                ADD COLUMN created_by_id INT NOT NULL DEFAULT 0, \
                ADD COLUMN created_by_name VARCHAR(100) NOT NULL DEFAULT '', \
                ADD COLUMN created_by_role VARCHAR(50) NOT NULL DEFAULT ''");
            $useMeta = true;
        } else {
            $useMeta = true;
        }
    } catch (Exception $e) {
        // permission or other issue, we'll fall back later
        $useMeta = false;
    }

    // ensure VAT-related columns exist and track availability
    $useVat = false;
    try {
        $hasVat = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'vat_percent'")->fetch();
        if (!$hasVat) {
            $pdo->exec("ALTER TABLE tbl_invoice \
                ADD COLUMN vat_percent DECIMAL(10,2) NOT NULL DEFAULT 0, \
                ADD COLUMN vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0, \
                ADD COLUMN vat_number VARCHAR(100) NOT NULL DEFAULT ''");
        }
        // re-check after attempt
        $hasVat = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'vat_percent'")->fetch();
        if ($hasVat) {
            $useVat = true;
        }
    } catch (Exception $e) {
        // ignore; if migration fails we will still accept data and simply not store it
        $useVat = false;
    }

    // ensure deposit column exists (new tank deposit feature)
    try {
        $hasDep = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'deposit'")->fetch();
        if (!$hasDep) {
            $pdo->exec("ALTER TABLE tbl_invoice ADD COLUMN deposit DECIMAL(10,2) NOT NULL DEFAULT 0");
        }
    } catch (Exception $e) {
        // ignore failures; deposit simply won't be persisted
    }

    $pdo->beginTransaction();

    $orderdate     = date("Y-m-d H:i:s");
    $subtotal      = floatval($_POST['txtsubtotal'] ?? 0);
    $discount      = floatval($_POST['txtdiscount'] ?? 0);
    $sgst          = floatval($_POST['txtsgst'] ?? 0);
    $cgst          = floatval($_POST['txtcgst'] ?? 0);
    $deposit       = floatval($_POST['txtdeposit'] ?? 0);
    $total         = floatval($_POST['txttotal'] ?? 0);
    $payment_type  = $_POST['rb'] ?? 'cash';
    $due           = floatval($_POST['txtdue'] ?? 0);
    $paid          = floatval($_POST['txtpaid'] ?? 0);

    // VAT-related inputs (won't be used if columns aren't available)
    $vat_percent   = floatval($_POST['txtvat_p'] ?? 0);
    if ($vat_percent <= 0 && defined('DEFAULT_VAT_RATE')) {
        $vat_percent = DEFAULT_VAT_RATE;
    }
    $vat_amount    = floatval($_POST['txtvat_n'] ?? 0);
    $vat_number    = trim($_POST['txtvat_number'] ?? '');
    if (empty($vat_number) && defined('COMPANY_VAT_NUMBER')) {
        $vat_number = COMPANY_VAT_NUMBER;
    }
    
    // Determine order status based on role and payment method
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
        // Admin-created orders: if admin selects COD we still set Pending
        // so the admin can mark it Complete later; otherwise mark Complete
        $order_status = ($payment_type === 'cod') ? 'Pending' : 'Complete';
    } else {
        // Orders created by users: COD -> Pending, others -> Complete
        $order_status = ($payment_type === 'cod') ? 'Pending' : 'Complete';
    }

    // Branch tracking: persist current branch with created orders
    $branch = trim($_SESSION['branch'] ?? '');
    if (empty($branch)) {
        $branchFile = __DIR__ . '/../config/active_branch.json';
        if (file_exists($branchFile)) {
            $activeData = json_decode(file_get_contents($branchFile), true);
            $branch = trim($activeData['branch'] ?? '');
        }
    }
    if (empty($branch)) {
        $branch = 'Unknown';
    }

    // Check if branch column exists, so old DB doesn't fail saves
    $branchColumnExists = false;
    try {
        $branchInfo = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'branch'")->fetch(PDO::FETCH_ASSOC);
        $branchColumnExists = (bool) $branchInfo;
    } catch (Exception $e) {
        $branchColumnExists = false;
    }

    $branchColSql = $branchColumnExists ? ', branch' : '';
    $branchValSql = $branchColumnExists ? ', :branch' : '';

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

    // Insert into tbl_invoice (main) – build SQL according to available columns
    $vatCols = '';
    $vatParams = '';
    $vatBind = [];
    if ($useVat) {
        $vatCols = ', vat_percent, vat_amount, vat_number';
        $vatParams = ', :vat_percent, :vat_amount, :vat_number';
        $vatBind = [
            ':vat_percent' => $vat_percent,
            ':vat_amount'  => $vat_amount,
            ':vat_number'  => $vat_number,
        ];
    }

    $invoiceCols = [
        'order_date',
        'subtotal',
        'discount',
        'sgst',
        'cgst',
        'deposit',
        'total',
        'payment_type',
        'due',
        'paid',
        'customer_name',
        'customer_contact',
        'customer_address',
        'status',
    ];

    $invoiceParams = [
        ':order_date',
        ':subtotal',
        ':discount',
        ':sgst',
        ':cgst',
        ':deposit',
        ':total',
        ':payment_type',
        ':due',
        ':paid',
        ':customer_name',
        ':customer_contact',
        ':customer_address',
        ':status',
    ];

    $bind = [
        ':order_date'       => $orderdate,
        ':subtotal'         => $subtotal,
        ':discount'         => $discount,
        ':sgst'             => $sgst,
        ':cgst'             => $cgst,
        ':deposit'          => $deposit,
        ':total'            => $total,
        ':payment_type'     => $payment_type,
        ':due'              => $due,
        ':paid'             => $paid,
        ':customer_name'    => $customer_name,
        ':customer_contact' => $customer_contact,
        ':customer_address' => $customer_address,
        ':status'           => $order_status,
    ];

    if ($useVat) {
        $invoiceCols = array_merge($invoiceCols, ['vat_percent', 'vat_amount', 'vat_number']);
        $invoiceParams = array_merge($invoiceParams, [':vat_percent', ':vat_amount', ':vat_number']);
        $bind[':vat_percent'] = $vat_percent;
        $bind[':vat_amount']  = $vat_amount;
        $bind[':vat_number']  = $vat_number;
    }

    if ($branchColumnExists) {
        $invoiceCols[] = 'branch';
        $invoiceParams[] = ':branch';
        $bind[':branch'] = $branch;
    }

    if ($useMeta) {
        $invoiceCols = array_merge($invoiceCols, ['created_by_id', 'created_by_name', 'created_by_role']);
        $invoiceParams = array_merge($invoiceParams, [':created_by_id', ':created_by_name', ':created_by_role']);
        $bind[':created_by_id']   = $_SESSION['userid'] ?? 0;
        $bind[':created_by_name'] = $_SESSION['username'] ?? '';
        $bind[':created_by_role'] = $_SESSION['role'] ?? '';
    } else {
        $invoiceCols[] = 'created_by';
        $invoiceParams[] = ':created_by';
        $bind[':created_by'] = $_SESSION['userid'] ?? 0;
    }

    if (count($invoiceCols) !== count($invoiceParams)) {
        throw new Exception('Invoice insert structure mismatch: columns != params');
    }
    if (count($invoiceParams) !== count($bind)) {
        throw new Exception('Invoice bind mismatch: tokens=' . count($invoiceParams) . ' bound=' . count($bind));
    }

    $sql = "INSERT INTO tbl_invoice (" . implode(', ', $invoiceCols) . ") VALUES (" . implode(', ', $invoiceParams) . ")";
    $insertInvoice = $pdo->prepare($sql);
    $insertInvoice->execute($bind);

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

        // Log sales/invoice creation
        if (function_exists('logActivity')) {
            $details = sprintf(
                'Invoice #%d created | Customer: %s | Total: ₱%.2f | Items: %d | Payment: %s',
                $invoice_id,
                htmlspecialchars($customer_name),
                $total,
                count($arr_pid),
                $payment_type
            );
            logActivity($_SESSION['useremail'] ?? 'System', 'Create Sale', 'Sales', $details, 'INFO');
        }
        
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

    // Log failed sales/invoice creation
    if (function_exists('logActivity')) {
        $description = 'Failed to create invoice: ' . $e->getMessage();
        logActivity($_SESSION['useremail'] ?? 'System', 'Create Sale Failed', 'Sales', $description, 'ERROR');
    }
}

ob_end_clean();
echo json_encode($response);
exit;
?>
