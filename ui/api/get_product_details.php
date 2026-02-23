<?php
include_once '../connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);
        
        $select = $pdo->prepare("SELECT * FROM tbl_product WHERE pid = :id");
        $select->bindParam(':id', $id, PDO::PARAM_INT);
        $select->execute();
        
        if ($row = $select->fetch(PDO::FETCH_ASSOC)) {
            // Sanitize data for JSON response
            $product = [
                'pid' => (int)$row['pid'],
                'product' => htmlspecialchars($row['product']),
                'category' => htmlspecialchars($row['category']),
                'valvetype' => htmlspecialchars($row['valvetype'] ?? ''),
                'purchaseprice' => (float)$row['purchaseprice'],
                'saleprice' => (float)$row['saleprice'],
                'stock' => (int)($row['stock'] ?? 0),
                'addedstock' => (int)($row['addedstock'] ?? 0),
                'brand' => htmlspecialchars($row['brand'] ?? ''),
                'expirydate' => $row['expirydate'] ?? '',
                'image' => htmlspecialchars($row['image']),
                'supplier_category' => htmlspecialchars($row['supplier_category'] ?? '')
            ];
            
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Product ID not provided']);
}
?>
