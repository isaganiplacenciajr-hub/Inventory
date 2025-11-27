<?php
require_once(__DIR__ . '/fpdf/fpdf.php');
include_once(__DIR__ . '/connectdb.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$select = $pdo->prepare("SELECT * FROM tbl_invoice WHERE invoice_id = ?");
$select->execute([$id]);
$row = $select->fetch(PDO::FETCH_OBJ);

$formattedDate = $row && !empty($row->order_date) ? date("F j, Y", strtotime($row->order_date)) : '';

$pdf = new FPDF('P', 'mm', array(80, 135));
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();
$pdf->SetMargins(6, 4, 6);

function addSummaryRow($pdf, $label, $value, $showPhp = true) {
    $pdf->SetX(6);
    $pdf->SetFont('Courier', 'B', 8);
    $pdf->Cell(35, 5, strtoupper($label), 0, 0, 'L');
    $displayValue = $showPhp ? 'Php ' . $value : $value;
    $pdf->Cell(26, 5, $displayValue, 0, 1, 'R');
}

// ===== HEADER =====
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(68, 6, 'SPM LPG Trading', 0, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$pdf->Cell(68, 4, 'Branches: San Isidro | Sawmil | Matain', 0, 1, 'C');
$pdf->Cell(68, 4, 'Subic, Zambales', 0, 1, 'C');
$pdf->Cell(68, 4, 'Contact: 0981-243-6970', 0, 1, 'C');
$pdf->Ln(2);
$y = $pdf->GetY();
$pdf->Line(6, $y, 74, $y);
$pdf->Ln(3);

// ===== TITLE =====
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(68, 5, 'OFFICIAL RECEIPT', 0, 1, 'C');
$pdf->Ln(1);

// ===== INFO =====
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(18, 5, 'Bill No:', 0, 0);
$pdf->SetFont('Courier', '', 8);
$pdf->Cell(50, 5, $row->invoice_id ?? '', 0, 1);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(18, 5, 'Date:', 0, 0);
$pdf->SetFont('Courier', '', 8);
$pdf->Cell(50, 5, $formattedDate, 0, 1);

$pdf->Ln(2);

// ===== TABLE HEADER =====
$pdf->SetX(6);
$pdf->SetFont('Courier', 'B', 8);
$pdf->Cell(30, 6, 'PRODUCT', 1, 0, 'C');
$pdf->Cell(8, 6, 'QTY', 1, 0, 'C');
$pdf->Cell(14, 6, 'PRC', 1, 0, 'C');
$pdf->Cell(18, 6, 'TOTAL', 1, 1, 'C');

// ===== PRODUCTS =====
$select = $pdo->prepare("SELECT * FROM tbl_invoice_details WHERE invoice_id = ?");
$select->execute([$id]);
$pdf->SetFont('Courier', '', 8);

$totalAddFee = 0;

while ($product = $select->fetch(PDO::FETCH_OBJ)) {
    $pdf->SetX(6);
    $unit_price = $product->qty > 0 ? $product->saleprice / $product->qty : $product->saleprice;
    $total_price = $unit_price * $product->qty;

    $pdf->Cell(30, 5, substr($product->product_name, 0, 28), 1, 0, 'L');
    $pdf->Cell(8, 5, $product->qty, 1, 0, 'C');
    $pdf->Cell(14, 5, number_format($unit_price, 2), 1, 0, 'R');
    $pdf->Cell(18, 5, number_format($total_price, 2), 1, 1, 'R');

    // Add service & additional fee (for delivery)
    if (!empty($product->servicetype)) {
        $pdf->SetX(8);
        $pdf->SetFont('Courier', '', 7);
        $pdf->Cell(68, 4, "Service: " . ucfirst($product->servicetype), 0, 1, 'L');
    }

    if (!empty($product->addfee) && $product->addfee > 0) {
        $pdf->SetX(8);
        $pdf->SetFont('Courier', '', 7);
        $pdf->Cell(68, 4, "Add. Fee: Php " . number_format($product->addfee, 2), 0, 1, 'L');
        $totalAddFee += floatval($product->addfee);
    }

    $pdf->Ln(1);
}

$pdf->Ln(2);
$pdf->SetFont('Courier', 'B', 8);

// ===== SUMMARY =====
$subtotal = $row->subtotal ?? 0;
$discount_pct = $row->discount ?? 0; 
$discount_rs = ($discount_pct / 100) * $subtotal;
$gtotal = $subtotal + $totalAddFee - $discount_rs;
$paid = $row->paid ?? 0;

// FIXED: accurate due/change computation
if ($paid < $gtotal) {
    $due = $gtotal - $paid;
    $change = 0;
} else {
    $due = 0;
    $change = $paid - $gtotal;
}

// Format numbers
addSummaryRow($pdf, 'Subtotal', number_format($subtotal, 2));
addSummaryRow($pdf, 'Add. Fee', number_format($totalAddFee, 2));
addSummaryRow($pdf, 'Discount(&)', $discount_pct, false);
addSummaryRow($pdf, 'Discount(php)', number_format($discount_rs, 2));
addSummaryRow($pdf, 'G-Total', number_format($gtotal, 2));
addSummaryRow($pdf, 'Paid', number_format($paid, 2));

if ($due > 0) {
    addSummaryRow($pdf, 'Due', number_format($due, 2));
} elseif ($change > 0) {
    addSummaryRow($pdf, 'Change', number_format($change, 2));
} else {
    addSummaryRow($pdf, 'Due', '0.00');
}

$pdf->Ln(2);
$pdf->SetX(6);
$pdf->SetFont('Courier', 'B', 8);
$pdf->Cell(68, 5, 'Important Notice', 0, 1, 'C');

$pdf->SetFont('Arial', '', 6);
$pdf->SetX(6);
$pdf->MultiCell(68, 3.5, "Please check your items before leaving. No refund or replacement after leaving the store.", 0, 'C');

$pdf->Ln(1);
$pdf->SetFont('Arial', 'I', 7);
$pdf->Cell(68, 4, 'Thank you for your purchase!', 0, 1, 'C');

$pdf->Output();
?>
