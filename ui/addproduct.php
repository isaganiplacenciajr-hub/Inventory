<?php
include_once 'connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'utils.php';
include_once "header.php";

if (isset($_POST['btnsave'])) {

    $id = isset($_POST['txtID']) ? $_POST['txtID'] : null;
    
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
    $purchaseprice = $_POST['txtpurchaseprice'] !== '' ? $_POST['txtpurchaseprice'] : ($old_data ? $old_data['purchaseprice'] : 0);
    $saleprice = $_POST['txtsaleprice2'] !== '' ? $_POST['txtsaleprice2'] : ($old_data ? $old_data['saleprice'] : 0);
    $quantity_to_add = isset($_POST['txtStockQty']) && $_POST['txtStockQty'] !== '' ? (int)$_POST['txtStockQty'] : 0;
    $brand = !empty($_POST['txtBrand']) ? $_POST['txtBrand'] : ($old_data ? $old_data['brand'] : '');
    $expirydate = !empty($_POST['txtExpirydate']) ? $_POST['txtExpirydate'] : ($old_data ? $old_data['expirydate'] : null);

    // Silent migration: ensure addedstock column exists
    try {
        $pdo->exec("ALTER TABLE tbl_product ADD COLUMN addedstock INT DEFAULT 0 AFTER stock");
    } catch (PDOException $e) {}

    // File upload handling
    $f_name = $_FILES['myfile']['name'];
    $productimage = $old_data ? $old_data['image'] : 'noimage.png'; // Default
    
    if (!empty($f_name)) {
        $f_tmp = $_FILES['myfile']['tmp_name'];
        $f_size = $_FILES['myfile']['size'];
        $f_extension = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
        $f_newfile = uniqid() . '.' . $f_extension;

        $folder = __DIR__ . "/productimages";
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
        
        // Preserve previous addedstock if no new stock is added this time
        $final_addedstock = ($quantity_to_add > 0) ? $quantity_to_add : $old_data['addedstock'];

        $update = $pdo->prepare("UPDATE tbl_product SET product=:product, category=:category, valvetype=:valvetype, purchaseprice=:pprice, saleprice=:saleprice, image=:img, stock=:total_stock, addedstock=:addedstock, brand=:brand, expirydate=:expirydate WHERE pid=:id");

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
        $update->bindParam(':id', $id);

        if ($update->execute()) {
            $_SESSION['status'] = "Product updated successfully. New Total Stock: $total_stock";
            $_SESSION['status_code'] = "success";
            logActivity($_SESSION['useremail'] ?? 'System', 'Update Product', 'Inventory', "Product updated: '$product' (ID: $id, Added: $quantity_to_add, New Total: $total_stock)", 'INFO');
        } else {
            $_SESSION['status'] = "Product update failed";
            $_SESSION['status_code'] = "error";
        }

    } else {
        // INSERT NEW PRODUCT
        $total_stock = $quantity_to_add;
        $insert = $pdo->prepare("INSERT INTO tbl_product(product, category, valvetype, purchaseprice, saleprice, image, stock, addedstock, brand, expirydate)
            VALUES(:product, :category, :valvetype, :pprice, :saleprice, :img, :total_stock, :addedstock, :brand, :expirydate)");

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

        if ($insert->execute()) {
            $_SESSION['status'] = "Product inserted successfully.";
            $_SESSION['status_code'] = "success";
        } else {
            $_SESSION['status'] = "Product insertion failed";
            $_SESSION['status_code'] = "error";
        }
    }
}
?>

<!-- PAGE CONTENT -->
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Add Product</h1>
          <hr>
          <a href="productlist.php" class="btn btn-info"><span class="report-count">View product you entered</span></a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">

          <div class="card px-4 card-warning card-outline">
            <div class="card-header">
              <h5 class="m-0">Product</h5>
            </div>

            <form action="" method="post" enctype="multipart/form-data">
              <input type="hidden" name="txtID" id="txtID">
              <div class="row">

                <!-- LEFT COLUMN -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Category:</label>
                    <select id="category" class="form-control" name="txtBarcode" required>
                      <option value="" disabled selected>Select Category</option>
                      <option value="5 kg (Medium)">5 kg (Medium)</option>
                      <option value="11 Kg (Standard)">11 Kg (Standard)</option>
                      <option value="22 Kg (Large)">22 Kg (Large)</option>
                      <option value="50 Kg (Extra Large)">50 Kg (Extra Large)</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Product Code:</label>
                    <input type="text" class="form-control" placeholder="Enter Product Code" name="txtProductcode" id="txtProductcode" required>
                  </div>

                  <div class="form-group">
                    <label>Valve Type:</label>
                    <input type="text" class="form-control" placeholder="Enter Valve Type" name="txtvalvetype" id="txtvalvetype" readonly required>
                  </div>

                  <div class="form-group">
                    <label>Brand:</label>
                    <input type="text" class="form-control" placeholder="Enter Brand" name="txtBrand" id="txtBrand" readonly>
                  </div>

                  <div class="form-group">
                    <label>Current Stock:</label>
                    <input type="number" id="txtCurrentStock" class="form-control" placeholder="0" readonly>
                  </div>

                </div>

                <!-- RIGHT COLUMN -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Purchase Price:</label>
                    <input type="text" id="txtpurchaseprice" class="form-control" placeholder="Enter Purchase Price" name="txtpurchaseprice" required>
                  </div>

                  <div class="form-group">
                    <label>Sale Price:</label>
                    <input type="text" id="txtsaleprice2" class="form-control" placeholder="Enter Sale Price" name="txtsaleprice2" required>
                  </div>

                  

                  <div class="form-group">
                    <label>Quantity to Add:</label>
                    <input type="number" id="txtStockQty" min="0" step="1" class="form-control" placeholder="Enter Quantity to Add" name="txtStockQty">
                  </div>

                  

                  <div class="form-group">
                    <label>Expiry Date:</label>
                    <input type="date" class="form-control" name="txtExpirydate" id="txtExpirydate">
                  </div>

                  <div class="form-group">
                    <label>Product Image:</label>
                    <input type="file" class="form-control-file" name="myfile" id="myfile">
                    <small class="text-muted">Upload image (Leave empty to keep existing image if updating)</small>
                  </div>
                </div>

              </div>

              <div class="card-footer text-center">
                <button type="submit" class="btn btn-primary" name="btnsave" id="btnsave">Save Product</button>
              </div>
            </form>

<!-- === AUTOMATIONS === -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const mapping = {
    '5 kg (Medium)': { purchase: '400.00', sale: '450.00', brand: 'Pryce Gas', valvetype: 'Standard' },
    '11 Kg (Standard)': { purchase: '900.00', sale: '1000.00', brand: 'Pryce Gas', valvetype: 'Standard' },
    '22 Kg (Large)': { purchase: '1700.00', sale: '1850.00', brand: 'Pryce Gas', valvetype: 'Standard' },
    '50 Kg (Extra Large)': { purchase: '3800.00', sale: '4200.00', brand: 'Pryce Gas', valvetype: 'Standard' }
  };

  const cat = document.getElementById('category');
  const purchase = document.getElementById('txtpurchaseprice');
  const sale = document.getElementById('txtsaleprice2');
  const brand = document.getElementById('txtBrand');
  const vtype = document.getElementById('txtvalvetype');
  const currentStock = document.getElementById('txtCurrentStock');
  
  // New fields for autofill
  const txtID = document.getElementById('txtID');
  const txtProductcode = document.getElementById('txtProductcode');
  const txtStockQty = document.getElementById('txtStockQty');
  const txtExpirydate = document.getElementById('txtExpirydate');
  const btnsave = document.getElementById('btnsave');
  const myfile = document.getElementById('myfile');

  if (!cat) return;

  function applyDefaults(val) {
    const cfg = mapping[val];
    if (cfg) {
      if (!txtID.value) {
          purchase.value = cfg.purchase;
          sale.value = cfg.sale;
          brand.value = cfg.brand;
          vtype.value = cfg.valvetype;
          currentStock.value = 0;
      }
    } else {
       if (!txtID.value) {
          purchase.value = '';
          sale.value = '';
          brand.value = '';
          vtype.value = '';
          currentStock.value = 0;
       }
    }
  }

  cat.addEventListener('change', function() {
    const selectedCategory = this.value;
    
    // Fetch product details by category
    fetch('get_product_by_category.php?category=' + encodeURIComponent(selectedCategory))
    .then(response => response.json())
    .then(data => {
        if (data) {
            // Product exists, autofill details
            txtID.value = data.id;
            txtProductcode.value = data.product;
            vtype.value = data.valvetype || '';
            purchase.value = data.purchaseprice;
            sale.value = data.saleprice;
            currentStock.value = data.stock;
            txtStockQty.value = ''; // Reset "Quantity to Add"
            brand.value = data.brand;
            txtExpirydate.value = data.expirydate;
            
            // Update button text
            btnsave.textContent = "Update Product";
            btnsave.classList.remove('btn-primary');
            btnsave.classList.add('btn-warning');
            
            // Make file upload optional visually
            myfile.required = false;
            
            Swal.fire({
              icon: 'info',
              title: 'Product Found',
              text: 'Existing product details loaded. You can add more stock.',
              toast: true,
              position: 'top-end',
              showConfirmButton: false,
              timer: 3000
            });

        } else {
            // Product does not exist, clear ID and let user enter new
            txtID.value = '';
            txtProductcode.value = '';
            txtStockQty.value = '';
            currentStock.value = 0;
            txtExpirydate.value = '';
            
            // Apply hardcoded defaults from mapping
            applyDefaults(selectedCategory);
            
            // Reset button
            btnsave.textContent = "Save Product";
            btnsave.classList.remove('btn-warning');
            btnsave.classList.add('btn-primary');
            
            // Make file upload required
            myfile.required = true;
        }
    })
    .catch(error => console.error('Error fetching product:', error));
  });
});
</script>


          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once("footer.php"); ?>

<?php 
if (isset($_SESSION['status']) && $_SESSION['status'] !== '') {
?>
<script>
Swal.fire({
  icon: '<?php echo $_SESSION['status_code'];?>',
  title: '<?php echo $_SESSION['status'];?>'
})
</script>
<?php
unset($_SESSION['status']);
}
?>