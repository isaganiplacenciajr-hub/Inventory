<?php
include_once '../connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['txtID']) && !empty($_POST['txtID']) ? $_POST['txtID'] : null;
        
        // Fetch existing data if updating
        $old_data = null;
        if (!empty($id)) {
            $select = $pdo->prepare("SELECT * FROM tbl_product WHERE pid = :id");
            $select->bindParam(':id', $id);
            $select->execute();
            $old_data = $select->fetch(PDO::FETCH_ASSOC);
        }

        $product = !empty($_POST['txtProductcode']) ? $_POST['txtProductcode'] : ($old_data ? $old_data['product'] : '');
        $category = !empty($_POST['txtBarcode']) ? $_POST['txtBarcode'] : ($old_data ? $old_data['category'] : '');
        $valvetype = !empty($_POST['txtvalvetype']) ? $_POST['txtvalvetype'] : ($old_data ? $old_data['valvetype'] : '');
        $purchaseprice = isset($_POST['txtpurchaseprice']) && $_POST['txtpurchaseprice'] !== '' ? $_POST['txtpurchaseprice'] : ($old_data ? $old_data['purchaseprice'] : 0);
        $saleprice = isset($_POST['txtsaleprice2']) && $_POST['txtsaleprice2'] !== '' ? $_POST['txtsaleprice2'] : ($old_data ? $old_data['saleprice'] : 0);
        $quantity_to_add = isset($_POST['txtStockQty']) && $_POST['txtStockQty'] !== '' ? (int)$_POST['txtStockQty'] : 0;
        $brand = !empty($_POST['txtBrand']) ? $_POST['txtBrand'] : ($old_data ? $old_data['brand'] : '');
        $expirydate = !empty($_POST['txtExpirydate']) ? $_POST['txtExpirydate'] : ($old_data ? $old_data['expirydate'] : null);
        $supplier_category = !empty($_POST['txtSupplierCategory']) ? $_POST['txtSupplierCategory'] : ($old_data ? $old_data['supplier_category'] : null);

        // Silent migration: ensure addedstock column exists
        try {
            $pdo->exec("ALTER TABLE tbl_product ADD COLUMN addedstock INT DEFAULT 0 AFTER stock");
        } catch (PDOException $e) {}

        // Silent migration: ensure supplier_category column exists
        try {
            $pdo->exec("ALTER TABLE tbl_product ADD COLUMN supplier_category VARCHAR(200) DEFAULT NULL");
        } catch (PDOException $e) {}

        // File upload handling
        $f_name = isset($_FILES['myfile']['name']) ? $_FILES['myfile']['name'] : '';
        $productimage = $old_data ? $old_data['image'] : 'noimage.png';
        
        if (!empty($f_name)) {
            $f_tmp = $_FILES['myfile']['tmp_name'];
            $f_size = $_FILES['myfile']['size'];
            $f_extension = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
            $f_newfile = uniqid() . '.' . $f_extension;

            $folder = __DIR__ . "/../productimages";
            if (!file_exists($folder)) mkdir($folder, 0777, true);
            $store = $folder . "/" . $f_newfile;

            if (in_array($f_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                if ($f_size < 1000000) {
                    if (move_uploaded_file($f_tmp, $store)) {
                        $productimage = $f_newfile;
                    }
                }
            }
        }

        if (!empty($id) && $old_data) {
            // UPDATE EXISTING PRODUCT
            $current_stock = (int)$old_data['stock'];
            $total_stock = $current_stock + $quantity_to_add;
            
            $final_addedstock = ($quantity_to_add > 0) ? $quantity_to_add : (isset($old_data['addedstock']) ? $old_data['addedstock'] : 0);

            $update = $pdo->prepare("UPDATE tbl_product SET product=:product, category=:category, valvetype=:valvetype, purchaseprice=:pprice, saleprice=:saleprice, image=:img, stock=:total_stock, addedstock=:addedstock, brand=:brand, expirydate=:expirydate, supplier_category=:supplier_category WHERE pid=:id");

            $update->bindParam(':product', $product);
            $update->bindParam(':category', $category);
            $update->bindParam(':valvetype', $valvetype);
            $update->bindParam(':pprice', $purchaseprice);
            $update->bindParam(':saleprice', $saleprice);
            $update->bindParam(':img', $productimage);
            $update->bindParam(':total_stock', $total_stock);
            $update->bindParam(':addedstock', $final_addedstock);
            $update->bindParam(':brand', $brand);
            $update->bindParam(':expirydate', $expirydate);
            $update->bindParam(':supplier_category', $supplier_category);
            $update->bindParam(':id', $id);

            if ($update->execute()) {
                logActivity($_SESSION['useremail'] ?? 'System', 'Update Product', 'Inventory', "Product updated: '$product' (ID: $id, Added: $quantity_to_add, New Total: $total_stock)", 'INFO');
                echo json_encode(['success' => true, 'message' => "Product updated successfully. New Total Stock: $total_stock"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product update failed']);
            }

        } else {
            // INSERT NEW PRODUCT
            $total_stock = $quantity_to_add;
            $insert = $pdo->prepare("INSERT INTO tbl_product(product, category, valvetype, purchaseprice, saleprice, image, stock, addedstock, brand, expirydate, supplier_category)
                VALUES(:product, :category, :valvetype, :pprice, :saleprice, :img, :total_stock, :addedstock, :brand, :expirydate, :supplier_category)");

            $insert->bindParam(':product', $product);
            $insert->bindParam(':category', $category);
            $insert->bindParam(':valvetype', $valvetype);
            $insert->bindParam(':pprice', $purchaseprice);
            $insert->bindParam(':saleprice', $saleprice);
            $insert->bindParam(':img', $productimage);
            $insert->bindParam(':total_stock', $total_stock);
            $insert->bindParam(':addedstock', $quantity_to_add);
            $insert->bindParam(':brand', $brand);
            $insert->bindParam(':expirydate', $expirydate);
            $insert->bindParam(':supplier_category', $supplier_category);

            if ($insert->execute()) {
                logActivity($_SESSION['useremail'] ?? 'System', 'Add Product', 'Inventory', "Product added: '$product'", 'INFO');
                echo json_encode(['success' => true, 'message' => 'Product inserted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product insertion failed']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
