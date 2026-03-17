<?php
include_once '../connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $id = $_POST['txtID'] ?? null;

        // =========================
        // FETCH OLD DATA IF EDITING
        // =========================
        $old_data = null;
        if (!empty($id)) {
            $select = $pdo->prepare("SELECT * FROM tbl_product WHERE pid = :id");
            $select->execute([':id' => $id]);
            $old_data = $select->fetch(PDO::FETCH_ASSOC);

            if (!$old_data) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
        }

        // =========================
        // GET FORM DATA
        // =========================
        $product = $_POST['txtProductcode'] ?? '';
        $category = $_POST['txtCategory'] ?? '';
        $valvetype = $_POST['txtvalvetype'] ?? '';
        $purchaseprice = $_POST['txtpurchaseprice'] ?? 0;
        $saleprice = $_POST['txtsaleprice2'] ?? 0;
        $quantity_to_add = isset($_POST['txtStockQty']) ? (int)$_POST['txtStockQty'] : 0;
        $brand = $_POST['txtBrand'] ?? '';
        $expirydate = $_POST['txtExpirydate'] ?? null;
        $date_received = $_POST['txtDateReceived'] ?? null;
        $supplier_category = $_POST['txtSupplierCategory'] ?? null;
        $display_address = $_POST['txtDisplayAddress'] ?? null;

        // Check if `date_received` column exists in tbl_product for this database
        $hasDateReceived = false;
        try {
            $colStmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_product' AND COLUMN_NAME = 'date_received'");
            $colStmt->execute();
            $hasDateReceived = (bool) $colStmt->fetchColumn();
        } catch (Throwable $e) {
            $hasDateReceived = false;
        }
        // Check if `display_address` column exists in tbl_product for this database
        $hasDisplayAddress = false;
        try {
            $colStmt2 = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_product' AND COLUMN_NAME = 'display_address'");
            $colStmt2->execute();
            $hasDisplayAddress = (bool) $colStmt2->fetchColumn();
        } catch (Throwable $e) {
            $hasDisplayAddress = false;
        }
        // =========================
        // IMAGE UPLOAD
        // =========================
        $productimage = $old_data['image'] ?? 'noimage.png';

        if (!empty($_FILES['myfile']['name'])) {

            $f_tmp = $_FILES['myfile']['tmp_name'];
            $f_size = $_FILES['myfile']['size'];
            $f_extension = strtolower(pathinfo($_FILES['myfile']['name'], PATHINFO_EXTENSION));
            $f_newfile = uniqid() . '.' . $f_extension;

            $folder = __DIR__ . "/../productimages";
            if (!file_exists($folder)) mkdir($folder, 0777, true);

            if (in_array($f_extension, ['jpg','jpeg','png','gif']) && $f_size < 1000000) {
                if (move_uploaded_file($f_tmp, $folder . "/" . $f_newfile)) {
                    $productimage = $f_newfile;
                }
            }
        }

        // =====================================================
        // INSERT NEW PRODUCT
        // =====================================================
        if (empty($id)) {

            if ($hasDateReceived) {
                $cols = "product, category, valvetype, purchaseprice, saleprice, image, stock, addedstock, brand, expirydate, supplier_category, date_received";
                $placeholders = ":product,:category,:valvetype,:pprice,:saleprice,:img,:stock,:addedstock,:brand,:expirydate,:supplier_category,:date_received";
                if ($hasDisplayAddress) {
                    $cols .= ", display_address";
                    $placeholders .= ", :display_address";
                }

                $insert = $pdo->prepare("INSERT INTO tbl_product ($cols) VALUES ($placeholders)");

                $executeParams = [
                    ':product' => $product,
                    ':category' => $category,
                    ':valvetype' => $valvetype,
                    ':pprice' => $purchaseprice,
                    ':saleprice' => $saleprice,
                    ':img' => $productimage,
                    ':stock' => $quantity_to_add,
                    ':addedstock' => $quantity_to_add,
                    ':brand' => $brand,
                    ':expirydate' => $expirydate,
                    ':supplier_category' => $supplier_category,
                    ':date_received' => $date_received
                ];
                if ($hasDisplayAddress) {
                    $executeParams[':display_address'] = $display_address;
                }
            } else {
                $cols = "product, category, valvetype, purchaseprice, saleprice, image, stock, addedstock, brand, expirydate, supplier_category";
                $placeholders = ":product,:category,:valvetype,:pprice,:saleprice,:img,:stock,:addedstock,:brand,:expirydate,:supplier_category";
                if ($hasDisplayAddress) {
                    $cols .= ", display_address";
                    $placeholders .= ", :display_address";
                }

                $insert = $pdo->prepare("INSERT INTO tbl_product ($cols) VALUES ($placeholders)");

                $executeParams = [
                    ':product' => $product,
                    ':category' => $category,
                    ':valvetype' => $valvetype,
                    ':pprice' => $purchaseprice,
                    ':saleprice' => $saleprice,
                    ':img' => $productimage,
                    ':stock' => $quantity_to_add,
                    ':addedstock' => $quantity_to_add,
                    ':brand' => $brand,
                    ':expirydate' => $expirydate,
                    ':supplier_category' => $supplier_category
                ];
                if ($hasDisplayAddress) {
                    $executeParams[':display_address'] = $display_address;
                }
            }

            $insert->execute($executeParams);

            logActivity($_SESSION['useremail'] ?? 'System', 'Add Product', 'Inventory', "Product added: $product", 'INFO');

            echo json_encode([
                'success' => true,
                'message' => "New product added with stock: $quantity_to_add"
            ]);
            exit;
        }

        // =====================================================
        // UPDATE EXISTING PRODUCT
        // =====================================================

        $messages = [];

        // ---- CHECK STOCK CHANGE ----
        $current_stock = (int)$old_data['stock'];

        if ($quantity_to_add > 0) {
            $new_stock = $current_stock + $quantity_to_add;

            $stmt = $pdo->prepare("UPDATE tbl_product SET stock=:stock, addedstock=:addedstock WHERE pid=:id");
            $stmt->execute([
                ':stock' => $new_stock,
                ':addedstock' => $quantity_to_add,
                ':id' => $id
            ]);

            $messages[] = "Stock updated. New total stock: $new_stock";
        }

        // ---- CHECK DETAILS CHANGE ----
        $dateReceivedChanged = $hasDateReceived ? (($old_data['date_received'] ?? null) != $date_received) : false;

        $detailsChanged = (
            $old_data['product'] != $product ||
            $old_data['category'] != $category ||
            $old_data['valvetype'] != $valvetype ||
            $old_data['purchaseprice'] != $purchaseprice ||
            $old_data['saleprice'] != $saleprice ||
            $old_data['brand'] != $brand ||
            $old_data['expirydate'] != $expirydate ||
            $dateReceivedChanged ||
            $old_data['supplier_category'] != $supplier_category ||
            ($hasDisplayAddress ? ($old_data['display_address'] ?? null) != $display_address : false) ||
            $old_data['image'] != $productimage
        );

        if ($detailsChanged) {

            $updateSql = "UPDATE tbl_product SET
                product=:product,
                category=:category,
                valvetype=:valvetype,
                purchaseprice=:pprice,
                saleprice=:saleprice,
                image=:img,
                brand=:brand,
                expirydate=:expirydate,
                supplier_category=:supplier_category";
            $updateParams = [
                ':product' => $product,
                ':category' => $category,
                ':valvetype' => $valvetype,
                ':pprice' => $purchaseprice,
                ':saleprice' => $saleprice,
                ':img' => $productimage,
                ':brand' => $brand,
                ':expirydate' => $expirydate,
                ':supplier_category' => $supplier_category,
                ':id' => $id
            ];

            if ($hasDisplayAddress) {
                $updateSql .= ",\n                display_address=:display_address";
                $updateParams[':display_address'] = $display_address;
            }

            if ($hasDateReceived) {
                $updateSql .= ",\n                date_received=:date_received";
                $updateParams[':date_received'] = $date_received;
            }

            $updateSql .= "\n                WHERE pid=:id";

            $update = $pdo->prepare($updateSql);
            $update->execute($updateParams);

            $messages[] = "Product details successfully updated";
        }

        if (empty($messages)) {
            $messages[] = "No changes were made";
        }

        logActivity($_SESSION['useremail'] ?? 'System', 'Update Product', 'Inventory', implode(' | ', $messages), 'INFO');

        echo json_encode([
            'success' => true,
            'message' => implode(' & ', $messages)
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>