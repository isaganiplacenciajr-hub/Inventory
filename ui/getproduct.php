<?php
include_once 'connectdb.php';

header('Content-Type: application/json; charset=utf-8');

// Validate id
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product id']);
    exit;
}

try {
    // Enable PDO exceptions for debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch product info including servicetype and additionalfee
    $stmt = $pdo->prepare('
        SELECT pid, product, category, saleprice, stock, brand, valvetype, expirydate, description, image, servicetype, additionalfee 
        FROM tbl_product 
        WHERE pid = :id 
        LIMIT 1
    ');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Use actual servicetype and additionalfee from product, with safe defaults
        $row['servicetype'] = !empty($row['servicetype']) ? $row['servicetype'] : 'Pick up';
        $row['additionalfee'] = !empty($row['additionalfee']) ? floatval($row['additionalfee']) : 0;

        echo json_encode($row);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>