<?php
include_once 'connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'utils.php';
include_once "header.php";

if (isset($_POST['btnsave'])) {

    $product = $_POST['txtProductcode'];
    $category = $_POST['txtBarcode'];
    $description = $_POST['txtdescription'];
    $servicetype = $_POST['txtstock'];
    $additionalfee = $_POST['txtsaleprice'];
    $purchaseprice = $_POST['txtpurchaseprice'];
    $saleprice = $_POST['txtsaleprice2'];
    $stockqty = isset($_POST['txtStockQty']) ? $_POST['txtStockQty'] : null;
    $brand = isset($_POST['txtBrand']) ? $_POST['txtBrand'] : null;
    $expirydate = isset($_POST['txtExpirydate']) && $_POST['txtExpirydate'] !== '' ? $_POST['txtExpirydate'] : null;


    // File upload handling
    $f_name = $_FILES['myfile']['name'];
    
    // If updating and no new file selected, keep existing image
    if (empty($f_name) && !empty($_POST['txtID'])) {
        // Fetch existing image from DB
        $id = $_POST['txtID'];
        $select = $pdo->prepare("SELECT image FROM tbl_product WHERE pid = :id");
        $select->bindParam(':id', $id);
        $select->execute();
        $row = $select->fetch(PDO::FETCH_ASSOC);
        $productimage = $row['image'];
    } else {
        $f_tmp = $_FILES['myfile']['tmp_name'];
        $f_size = $_FILES['myfile']['size'];

        $f_extension = explode('.', $f_name);
        $f_extension = strtolower(end($f_extension));
        $f_newfile = uniqid() . '.' . $f_extension;

        // Auto-create folder if not exist
        $folder = __DIR__ . "/productimages";
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $store = $folder . "/" . $f_newfile;

        if (in_array($f_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            if ($f_size >= 1000000) {
                $_SESSION['status'] = "max file should be 1MB";
                $_SESSION['status_code'] = "warning";
                // Redirect or stop execution if file is too big
                 header('location:addproduct.php');
                 exit; 
            } else {
                if (move_uploaded_file($f_tmp, $store)) {
                    $productimage = $f_newfile;
                } else {
                    $_SESSION['status'] = "Failed to upload image";
                    $_SESSION['status_code'] = "error";
                     header('location:addproduct.php');
                     exit;
                }
            }
        } else {
             $_SESSION['status'] = "Only JPG, JPEG, PNG, and GIF can be uploaded";
             $_SESSION['status_code'] = "warning";
             header('location:addproduct.php');
             exit;
        }
    }


    if (!empty($_POST['txtID'])) {
        // UPDATE EXISTING PRODUCT
        $id = $_POST['txtID'];
        $update = $pdo->prepare("UPDATE tbl_product SET product=:product, category=:category, description=:description, servicetype=:servicetype, additionalfee=:additionalfee, purchaseprice=:pprice, saleprice=:saleprice, image=:img, stock=:stock, brand=:brand, expirydate=:expirydate WHERE pid=:id");

        $update->bindParam(':product', $product);
        $update->bindParam(':category', $category);
        $update->bindParam(':description', $description);
        $update->bindParam(':servicetype', $servicetype);
        $update->bindParam(':additionalfee', $additionalfee);
        $update->bindParam(':pprice', $purchaseprice);
        $update->bindParam(':saleprice', $saleprice);
        $update->bindParam(':img', $productimage);
        $update->bindParam(':stock', $stockqty);
        $update->bindParam(':brand', $brand);
        $update->bindParam(':expirydate', $expirydate);
        $update->bindParam(':id', $id);

        if ($update->execute()) {
            $_SESSION['status'] = "Product updated successfully";
            $_SESSION['status_code'] = "success";
             // Log the activity
             $user = $_SESSION['useremail'] ?? $_SESSION['username'] ?? 'Unknown';
             logActivity(
                 $user,
                 'Update Product',
                 'Inventory',
                 "Product updated: '$product' (ID: $id, Stock: $stockqty)",
                 'INFO'
             );
        } else {
            $_SESSION['status'] = "Product update failed";
            $_SESSION['status_code'] = "error";
        }

    } else {
        // INSERT NEW PRODUCT
        $insert = $pdo->prepare("INSERT INTO tbl_product(product, category, description, servicetype, additionalfee, purchaseprice, saleprice, image, stock, brand, expirydate)
            VALUES(:product, :category, :description, :servicetype, :additionalfee, :pprice, :saleprice, :img, :stock, :brand, :expirydate)");

        $insert->bindParam(':product', $product);
        $insert->bindParam(':category', $category);
        $insert->bindParam(':description', $description);
        $insert->bindParam(':servicetype', $servicetype);
        $insert->bindParam(':additionalfee', $additionalfee);
        $insert->bindParam(':pprice', $purchaseprice);
        $insert->bindParam(':saleprice', $saleprice);
        $insert->bindParam(':img', $productimage);
        $insert->bindParam(':stock', $stockqty);
        $insert->bindParam(':brand', $brand);
        $insert->bindParam(':expirydate', $expirydate);

        if ($insert->execute()) {
            $product_id = $pdo->lastInsertId();
            $_SESSION['status'] = "Product inserted successfully";
            $_SESSION['status_code'] = "success";

            // Log the activity
            $user = $_SESSION['useremail'] ?? $_SESSION['username'] ?? 'Unknown';
            logActivity(
                $user,
                'Add Product',
                'Inventory',
                "Product added: '$product' (Category: $category, Description: $description, Stock: $stockqty, Sale Price: PHP $saleprice)",
                'INFO',
                [
                    'product_id' => $product_id,
                    'product_code' => $product,
                    'category' => $category,
                    'description' => $description,
                    'brand' => $brand,
                    'stock_quantity' => $stockqty,
                    'purchase_price' => $purchaseprice,
                    'sale_price' => $saleprice,
                    'expiry_date' => $expirydate
                ]
            );
        } else {
            $_SESSION['status'] = "Product insertion failed";
            $_SESSION['status_code'] = "error";

            // Log the failed attempt
            $user = $_SESSION['useremail'] ?? $_SESSION['username'] ?? 'Unknown';
            logActivity(
                $user,
                'Add Product Failed',
                'Inventory',
                "Failed to add product: '$product' - Database error",
                'ERROR'
            );
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

          <div class="card card-warning card-outline">
            <div class="card-header">
              <h5 class="m-0">Product</h5>
            </div>

<form action="" method="post" enctype="multipart/form-data">
  <input type="hidden" name="txtID" id="txtID">
  <div class="row">

    <!-- LEFT COLUMN -->
    <div class="col-md-6">
      <div class="form-group">
        <label>Product Code:</label>
        <input type="text" class="form-control" placeholder="Enter Product Code" name="txtProductcode" id="txtProductcode" required>
      </div>

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
        <label>Description:</label>
        <input type="text" class="form-control" placeholder="Enter Description" name="txtdescription" id="txtdescription" required>
      </div>

      <div class="form-group">
        <label>Service Type:</label>
        <select class="form-control" name="txtstock" id="servicetype" required>
          <option value="" disabled selected>Select Service Type</option>
          <option value="Delivery">Delivery</option>
          <option value="Pick-up">Pick-up</option>
        </select>
      </div>

      <div class="form-group">
        <label>Additional Fee:</label>
        <input type="text" class="form-control" placeholder="Enter Additional Fee" name="txtsaleprice" id="additionalfee" required>
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
        <label>Stock Quantity:</label>
        <input type="number" id="txtStockQty" min="0" step="1" class="form-control" placeholder="Enter Stock Quantity" name="txtStockQty">
      </div>

      <div class="form-group">
        <label>Brand:</label>
        <input type="text" class="form-control" placeholder="Enter Brand" name="txtBrand" id="txtBrand" readonly>
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
    '5 kg (Medium)': { purchase: '400.00', sale: '450.00', brand: 'Pryce Gas' },
    '11 Kg (Standard)': { purchase: '900.00', sale: '1000.00', brand: 'Pryce Gas' },
    '22 Kg (Large)': { purchase: '1700.00', sale: '1850.00', brand: 'Pryce Gas' },
    '50 Kg (Extra Large)': { purchase: '3800.00', sale: '4200.00', brand: 'Pryce Gas' }
  };

  const cat = document.getElementById('category');
  const purchase = document.getElementById('txtpurchaseprice');
  const sale = document.getElementById('txtsaleprice2');
  const brand = document.getElementById('txtBrand');
  
  // New fields for autofill
  const txtID = document.getElementById('txtID');
  const txtProductcode = document.getElementById('txtProductcode');
  const txtdescription = document.getElementById('txtdescription');
  const servicetype = document.getElementById('servicetype');
  const additionalfee = document.getElementById('additionalfee');
  const txtStockQty = document.getElementById('txtStockQty');
  const txtExpirydate = document.getElementById('txtExpirydate');
  const btnsave = document.getElementById('btnsave');
  const myfile = document.getElementById('myfile');

  if (!cat) return;

  function applyDefaults(val) {
    const cfg = mapping[val];
    if (cfg) {
      // Only apply defaults if we are NOT in update mode (i.e. if txtID is empty)
      // OR if we want to enforce these defaults even when editing. 
      // For now, let's trust the DB data if it exists, otherwise fall back to defaults.
      if (!txtID.value) {
          purchase.value = cfg.purchase;
          sale.value = cfg.sale;
          brand.value = cfg.brand;
      }
    } else {
       // Don't clear if we might have fetched data
       if (!txtID.value) {
          purchase.value = '';
          sale.value = '';
          brand.value = '';
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
            txtdescription.value = data.description;
            servicetype.value = data.servicetype;
            additionalfee.value = data.additionalfee;
            purchase.value = data.purchaseprice;
            sale.value = data.saleprice;
            txtStockQty.value = data.stock;
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
              text: 'Existing product details loaded. You can update the stock.',
              toast: true,
              position: 'top-end',
              showConfirmButton: false,
              timer: 3000
            });

        } else {
            // Product does not exist, clear ID and let user enter new
            txtID.value = '';
            // We might want to clear other fields or leave them as is?
            // Let's clear them to avoid confusion if they switch from an existing category to a new one
            txtProductcode.value = '';
            txtdescription.value = '';
            // servicetype.value = ''; // Keep default or clear?
            // additionalfee.value = '';
            txtStockQty.value = '';
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const serviceType = document.getElementById('servicetype');
  const additionalFee = document.getElementById('additionalfee');
  if (serviceType && additionalFee) {
    serviceType.addEventListener('change', function() {
      if (this.value === 'Delivery') {
        additionalFee.value = '50.00';
      } else if (this.value === 'Pick-up') {
        additionalFee.value = '0.00';
      } else {
        additionalFee.value = '';
      }
    });
  }
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