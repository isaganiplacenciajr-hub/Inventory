<?php
require_once 'connectdb.php';

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

// Fetch invoice details
$details = $pdo->prepare("SELECT * FROM tbl_invoice_details WHERE invoice_id = ?");
$details->execute([$invoice_id]);
$products = $details->fetchAll(PDO::FETCH_OBJ);

$formattedDate = !empty($row->order_date) ? date("F j, Y", strtotime($row->order_date)) : '';
?>

<div class="receipt-container" style="font-family: 'Courier New', monospace; font-size: 11px; padding: 15px; line-height: 1.5; margin: 0 auto; text-align: center;">

    <!-- HEADER -->
    <div style="text-align: center; margin-bottom: 10px;">
        <h4 style="margin: 3px 0; font-size: 13px;">SPM LPG Trading</h4>
        <p style="margin: 2px 0; font-size: 10px;">Matain, Subic, Zambales Branch</p>
        <p style="margin: 2px 0; font-size: 10px;">Contact: 0981-243-6970</p>
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
        <tr>
            <td style="width: 60%; text-align: right; padding: 2px 0;"><strong>Item Subtotal:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($row->subtotal, 2); ?></td>
        </tr>
        <?php
            $totalAddFee = 0;
            foreach ($products as $p) {
                $totalAddFee += floatval($p->addfee ?? 0);
            }
            if ($totalAddFee > 0):
        ?>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Service Fee:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($totalAddFee, 2); ?></td>
        </tr>
        <?php endif; ?>

        <?php if ($row->discount > 0): ?>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Disc (%):</strong></td>
            <td style="text-align: right; padding: 2px 0;"><?php echo htmlspecialchars($row->discount); ?>%</td>
        </tr>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Disc (₱):</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format(($row->discount / 100) * $row->subtotal, 2); ?></td>
        </tr>
        <?php endif; ?>

        <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;">
            <td style="text-align: right; padding: 4px 0;"><strong>TOTAL:</strong></td>
            <td style="text-align: right; padding: 4px 0;"><strong>₱<?php echo number_format($row->total, 2); ?></strong></td>
        </tr>

        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Paid:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($row->paid, 2); ?></td>
        </tr>

        <?php
            $change = $row->paid - $row->total;
            if ($change > 0):
        ?>
        <tr>
            <td style="text-align: right; padding: 2px 0;"><strong>Change:</strong></td>
            <td style="text-align: right; padding: 2px 0;">₱<?php echo number_format($change, 2); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <hr style="margin: 5px 0; border: 0; border-top: 1px solid #000;">

    <!-- PAYMENT TYPE -->
    <div style="text-align: center; margin-bottom: 8px; font-size: 10px;">
        <strong>Payment: </strong>
        <?php
            if ($row->payment_type == "cash") {
                echo '<span style="background: #fff3cd; padding: 2px 6px; border-radius: 2px; font-size: 9px;">Cash</span>';
            } elseif ($row->payment_type == "card") {
                echo '<span style="background: #d4edda; padding: 2px 6px; border-radius: 2px; font-size: 9px;">Card</span>';
            } else {
                echo htmlspecialchars($row->payment_type);
            }
        ?>
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
