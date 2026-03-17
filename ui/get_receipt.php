<?php
require_once 'connectdb.php';
require_once 'utils.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// we no longer rely on user_view parameter; always attempt to show creator info
// $user_view = isset($_GET['user_view']) ? true : false;

if (!$invoice_id) {
    echo '<p class="text-danger">Invalid invoice ID</p>';
    exit;
}

// Fetch invoice data
$invoice = $pdo->prepare("SELECT * FROM tbl_invoice WHERE invoice_id = ?");
$invoice->execute([$invoice_id]);
$row = $invoice->fetch(PDO::FETCH_OBJ);

if (!$row) {
    echo '<p class="text-danger">Invoice not found</p>';
    exit;
}

// Determine branch display values using stored order branch; fallback to active branch config
$invoiceBranch = trim($row->branch ?? '');
$branchDisplay = '';
$branchAddress = '';
$branchContact = '';

include_once __DIR__ . '/../config/branch.php';

if (!empty($invoiceBranch) && isset($branches[$invoiceBranch])) {
    $branchDisplay = $branches[$invoiceBranch]['display'];
    $branchAddress = $branches[$invoiceBranch]['address'];
    $branchContact = $branches[$invoiceBranch]['contact'] ?? '';
} elseif (!empty($invoiceBranch)) {
    $branchDisplay = $invoiceBranch;
    $branchAddress = $activeBranchData['address'] ?? '';
    $branchContact = $activeBranchData['contact'] ?? '';
} else {
    $branchDisplay = $activeBranchData['display'] ?? 'Unknown Branch';
    $branchAddress = $activeBranchData['address'] ?? '';
    $branchContact = $activeBranchData['contact'] ?? '';
}

// Fetch invoice details
$details = $pdo->prepare("SELECT * FROM tbl_invoice_details WHERE invoice_id = ?");
$details->execute([$invoice_id]);
$products = $details->fetchAll(PDO::FETCH_OBJ);

$formattedDate = !empty($row->order_date) ? date("F j, Y", strtotime($row->order_date)) : '';
$formattedTime = !empty($row->order_date) ? date("h:i A", strtotime($row->order_date)) : '';

// determine which operator info to show
// prefer recorded creator values if present (covers new orders)
$showRole = $_SESSION['role'] ?? 'N/A';
$showName = $_SESSION['username'] ?? 'N/A';
if (!empty($row->created_by_role)) {
    $showRole = $row->created_by_role;
}
if (!empty($row->created_by_name)) {
    $showName = $row->created_by_name;
}

// fallback: if metadata is missing and we have a creator ID, look up the user table
if ((empty($row->created_by_name) || empty($row->created_by_role)) && !empty($row->created_by)) {
    try {
        $u = $pdo->prepare("SELECT username, role FROM tbl_user WHERE userid = ? LIMIT 1");
        $u->execute([$row->created_by]);
        if ($urow = $u->fetch(PDO::FETCH_ASSOC)) {
            if (empty($row->created_by_name)) {
                $showName = $urow['username'];
            }
            if (empty($row->created_by_role)) {
                $showRole = $urow['role'];
            }
        }
    } catch (Exception $e) {
        // ignore lookup failures
    }
}
?>

<div class="receipt-container" style="font-family: 'Courier New', monospace; font-size: 11px; padding: 15px; line-height: 1.5; margin: 0 auto; text-align: center;">

    <!-- HEADER -->
    <div style="text-align: center; margin-bottom: 10px;">
        <h4 style="margin: 3px 0; font-size: 13px;">SPM LPG Trading</h4>
        <p style="margin: 2px 0; font-size: 10px; font-weight:bold;">
          <?php echo htmlspecialchars($branchDisplay); ?>
        </p>
        <p style="margin: 2px 0; font-size: 10px;">
          <?php echo htmlspecialchars($branchAddress); ?>
        </p>
        <p style="margin: 2px 0; font-size: 10px;">Contact: <?php echo htmlspecialchars($branchContact); ?></p>
        <?php 
            $displayVat = '';
            if (!empty($row->vat_number)) {
                $displayVat = $row->vat_number;
            } elseif (defined('COMPANY_VAT_NUMBER')) {
                $displayVat = COMPANY_VAT_NUMBER;
            }
        ?>
        <?php if (!empty($displayVat)): ?>
        <p style="margin: 2px 0; font-size: 10px;">VAT No.: <?php echo htmlspecialchars($displayVat); ?></p>
        <?php endif; ?>
        <hr style="margin: 6px 0; border: 0; border-top: 1px solid #000;">
    </div>

    <!-- TITLE -->
    <div style="text-align: center; margin-bottom: 8px;">
        <strong style="font-size: 12px;">OFFICIAL RECEIPT</strong>
    </div>

    <!-- INVOICE INFO -->
    <table style="width: 100%; margin-bottom: 8px; font-size: 10px;">
        <tr>
            <td style="width: 40%; padding: 2px 0; text-align: right;"><strong>Bill No:</strong></td>
            <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars($row->invoice_id); ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0; text-align: right;"><strong>Date:</strong></td>
            <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars($formattedDate); ?></td>
        </tr>
        <tr>
            <td style="padding: 2px 0; text-align: right;"><strong>Time:</strong></td>
            <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars($formattedTime); ?></td>
        </tr>
    </table>

    <hr style="margin: 5px 0; border: 0; border-top: 1px solid #000;">

    <!-- CUSTOMER INFORMATION -->
    <div style="margin-bottom: 8px; font-size: 10px; text-align: left;">
        <strong style="display: block; text-align: center; margin-bottom: 3px;">CUSTOMER INFORMATION</strong>
        <table style="width: 100%; margin-top: 2px;">
            <tr>
                <td style="width: 40%; padding: 2px 0; text-align: right;"><strong>Name:</strong></td>
                <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars($row->customer_name ?? 'N/A'); ?></td>
            </tr>
            <?php if (!empty($row->customer_contact)): ?>
            <tr>
                <td style="padding: 2px 0; text-align: right;"><strong>Contact:</strong></td>
                <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars($row->customer_contact); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($row->customer_address)): ?>
            <tr>
                <td style="padding: 2px 0; text-align: right;"><strong>Address:</strong></td>
                <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars($row->customer_address); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <hr style="margin: 5px 0; border: 0; border-top: 1px solid #000;">

    <!-- PRODUCTS TABLE -->
    <table style="width: 100%; margin-bottom: 8px; border-collapse: collapse; font-size: 10px;">
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th style="text-align: left; padding: 3px 0;">PRODUCT</th>
                <th style="text-align: center; padding: 3px 0;">QTY</th>
                <th style="text-align: right; padding: 3px 0;">PRICE</th>
                <th style="text-align: right; padding: 3px 0;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <?php
                    $unit_price = $product->rate;
                    $total_price = $unit_price * $product->qty;
                ?>
                <tr style="border-bottom: 1px solid #ccc;">
                    <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars(substr($product->product_name, 0, 25)); ?></td>
                    <td style="text-align: center; padding: 2px 0;"><?php echo htmlspecialchars($product->qty); ?></td>
                    <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($unit_price, 2); ?></td>
                    <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($total_price, 2); ?></td>
                </tr>

                <?php if (!empty($product->servicetype)): ?>
                <tr style="border-bottom: 1px solid #ccc;">
                    <td colspan="4" style="padding: 1px 0; font-size: 9px; text-align: left;">
                        Service: <?php echo htmlspecialchars(ucfirst($product->servicetype)); ?>
                    </td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($product->addfee) && $product->addfee > 0): ?>
                <tr style="border-bottom: 1px solid #ccc;">
                    <td colspan="4" style="padding: 1px 0; font-size: 9px; text-align: left;">
                        Add. Fee: ₱<?php echo number_format($product->addfee, 2); ?>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr style="margin: 5px 0; border: 0; border-top: 1px solid #000;">

    <!-- SUMMARY -->
    <table style="width: 100%; margin-bottom: 8px; font-size: 10px;">
        <?php
            // Start with item subtotal (VAT-inclusive prices)
            $itemSubtotal = floatval($row->subtotal);

            // Calculate total service fee from line items
            $totalAddFee = 0;
            foreach ($products as $p) {
                $totalAddFee += floatval($p->addfee ?? 0);
            }

            // Discount amount based on subtotal
            $discountAmt = 0;
            $discountPct = 0;
            if (!empty($row->discount)) {
                $discountPct = floatval($row->discount);
                $discountAmt = ($discountPct/100) * $itemSubtotal;
            }

            // VAT rate (default to 12 if not set or 0)
            $vatPct = floatval($row->vat_percent ?? 0);
            if ($vatPct <= 0) {
                $vatPct = DEFAULT_VAT_RATE;
            }

            // Deposit amount (added after VAT; not subject to tax)
            $depositAmt = floatval($row->deposit ?? 0);

            // Base for VAT extraction is subtotal minus discount
            $amountBeforeVat = $itemSubtotal - $discountAmt;
            // Extract VAT amount using inclusive formula
            $vatAmt = 0;
            if ($vatPct > 0) {
                $vatAmt = $amountBeforeVat - ($amountBeforeVat / (1 + $vatPct/100));
            }

            // Total payable mirrors user POS: subtotal + service fee - discount + deposit
            $totalInclusive = $itemSubtotal + $totalAddFee + $depositAmt - $discountAmt;
        ?>
        <tr>
            <td style="width: 60%; text-align: right; padding: 2px 0;"><strong>Item Subtotal:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($itemSubtotal, 2); ?></td>
        </tr>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Service Fee:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($totalAddFee, 2); ?></td>
        </tr>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Discount (<?php echo number_format($discountPct,2); ?>%):</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($discountAmt, 2); ?></td>
        </tr>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>VAT (<?php echo htmlspecialchars($vatPct); ?>%):</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($vatAmt, 2); ?></td>
        </tr>
        <?php if ($depositAmt > 0): ?>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Tank Deposit:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($depositAmt, 2); ?></td>
        </tr>
        <?php endif; ?>
        <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;">
            <td style="text-align: right; padding: 4px 0;"><strong>TOTAL:</strong></td>
            <td style="text-align: right; padding: 4px 0;"><strong>₱<?php echo number_format($totalInclusive, 2); ?></strong></td>
        </tr>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Paid:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($row->paid, 2); ?></td>
        </tr>
        <?php
            $change = $row->paid - $totalInclusive;
            if ($change > 0):
        ?>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Change:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($change, 2); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <hr style="margin: 5px 0; border: 0; border-top: 1px solid #000;">

    <!-- PAYMENT TYPE & STATUS -->
    <div style="text-align: center; margin-bottom: 8px; font-size: 10px;">
        <strong>Payment: </strong>
        <?php
            if ($row->payment_type == "cash") {
                echo '<span style="background: #fff3cd; padding: 2px 6px; border-radius: 2px; font-size: 9px;">Cash</span>';
            } elseif ($row->payment_type == "cod") {
                echo '<span style="background: #cfe2ff; padding: 2px 6px; border-radius: 2px; font-size: 9px;">Cash on Delivery (COD)</span>';
            } elseif ($row->payment_type == "card") {
                echo '<span style="background: #d4edda; padding: 2px 6px; border-radius: 2px; font-size: 9px;">Card</span>';
            } else {
                echo htmlspecialchars($row->payment_type);
            }
        ?>
    </div>

    <?php if ($row->payment_type == "cod" || !empty($row->status)): ?>
    <div style="text-align: center; margin-bottom: 8px; font-size: 10px;">
        <strong>Status: </strong>
        <?php
            $status = $row->status ?? 'Complete';
            if ($status === 'Pending') {
                echo '<span style="background: #fff3cd; padding: 2px 6px; border-radius: 2px; font-size: 9px;">Pending</span>';
            } elseif ($status === 'Complete') {
                echo '<span style="background: #d4edda; padding: 2px 6px; border-radius: 2px; font-size: 9px;">Complete</span>';
            } else {
                echo htmlspecialchars($status);
            }
        ?>
    </div>
    <?php endif; ?>

    <!-- COMPOSED BY SECTION -->
    <hr style="margin: 5px 0; border: 0; border-top: 1px solid #000;">
    <div style="margin-bottom: 8px; font-size: 10px; text-align: left;">
        <strong style="display: block; text-align: center; margin-bottom: 3px;">COMPOSED BY</strong>
        <table style="width: 100%; margin-top: 2px;">
            <tr>
                <td style="width: 40%; padding: 2px 0; text-align: right;"><strong>Role:</strong></td>
                <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars($showRole); ?></td>
            </tr>
            <tr>
                <td style="padding: 2px 0; text-align: right;"><strong>Name:</strong></td>
                <td style="padding: 2px 0; text-align: left;"><?php echo htmlspecialchars($showName); ?></td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <hr style="margin: 5px 0; border: 0; border-top: 1px solid #000;">
    <div style="text-align: center; font-size: 9px; line-height: 1.4;">
        <p style="margin: 3px 0;">Please check items before leaving.</p>
        <p style="margin: 3px 0;">No refund after leaving store.</p>
        <p style="margin: 5px 0; font-weight: bold;">Thank you!</p>
    </div>

</div>

<style>
    @media (max-width: 640px) {
        .receipt-container {
            font-size: 11px;
            padding: 12px;
        }
    }
</style>
