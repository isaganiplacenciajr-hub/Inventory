<?php
include_once 'connectdb.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'utils.php';
include_once "header.php";
?>

<!-- PAGE CONTENT -->
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Add Stock</h1>
          <hr>
          <button class="btn btn-success" data-toggle="modal" data-target="#addProductModal">
            <i class="fas fa-plus"></i> Add New Product
          </button>
          <a href="productlist.php" class="btn btn-info"><i class="fas fa-list"></i> View Product List</a>
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
              <h5 class="m-0">Product Management</h5>
            </div>
            <div class="card-body">
              <p>Click the "Add New Product" button above to add or edit products using the modal form.</p>
              <p>View the <a href="productlist.php">Product List</a> to see all products with their Supplier Category information.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ADD/EDIT PRODUCT MODAL -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addProductModalLabel">Add/Edit Product</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="addProductForm" method="post" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="txtID" id="txtID">
          <div class="row">
            <!-- LEFT COLUMN -->
            <div class="col-md-6">
              <div class="form-group">
                <label>Category:</label>
                <select id="category" class="form-control" name="txtBarcode" required>
                  <option value="" disabled selected>Select Category</option>
                  <option value="2.7 kg (Small)">2.7 kg (Small)</option>
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
                <input type="text" class="form-control" placeholder="Enter Valve Type" name="txtvalvetype" id="txtvalvetype" required>
              </div>

              <div class="form-group">
                <label>Brand:</label>
                <input type="text" class="form-control" placeholder="Enter Brand" name="txtBrand" id="txtBrand">
              </div>

              <div class="form-group">
                <label>Current Stock:</label>
                <input type="number" id="txtCurrentStock" class="form-control" placeholder="0" readonly>
              </div>

              <div class="form-group">
                <label>Supplier Category:</label>
                <input type="text" class="form-control" placeholder="Enter Supplier Category" name="txtSupplierCategory" id="txtSupplierCategory">
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
                <input type="file" class="form-control-file" name="myfile" id="myfile" accept="image/*">
                <small class="text-muted">Upload image (Leave empty to keep existing image if updating)</small>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btnsave">Save Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once("footer.php"); ?>

<!-- MODAL FORM HANDLING SCRIPT -->
<script>
$(document).ready(function() {
  const mapping = {
    '5 kg (Medium)': { purchase: '400.00', sale: '450.00', brand: 'Pryce Gas', valvetype: 'Standard' },
    '11 Kg (Standard)': { purchase: '900.00', sale: '1000.00', brand: 'Pryce Gas', valvetype: 'Standard' },
    '22 Kg (Large)': { purchase: '1700.00', sale: '1850.00', brand: 'Pryce Gas', valvetype: 'Standard' },
    '50 Kg (Extra Large)': { purchase: '3800.00', sale: '4200.00', brand: 'Pryce Gas', valvetype: 'Standard' }
  };

  // Store original values to detect changes
  let originalValues = {};
  let isEditMode = false;

  // Function to update button label based on form changes
  function updateButtonLabel() {
    const txtID = $('#txtID').val();
    const stockQty = parseInt($('#txtStockQty').val()) || 0;
    
    // Check if any non-stock fields have been changed
    const priceChanged = $('#txtpurchaseprice').val() !== '' && 
                         $('#txtpurchaseprice').val() !== originalValues.purchaseprice;
    const salePriceChanged = $('#txtsaleprice2').val() !== '' && 
                             $('#txtsaleprice2').val() !== originalValues.saleprice;
    const expiryChanged = $('#txtExpirydate').val() !== originalValues.expirydate;
    const productCodeChanged = $('#txtProductcode').val() !== originalValues.productcode;
    const brandChanged = $('#txtBrand').val() !== originalValues.brand;
    const valvetypeChanged = $('#txtvalvetype').val() !== originalValues.valvetype;
    const supplierCategoryChanged = $('#txtSupplierCategory').val() !== originalValues.supplierCategory;

    const detailsChanged = priceChanged || salePriceChanged || expiryChanged || 
                          productCodeChanged || brandChanged || valvetypeChanged || 
                          supplierCategoryChanged;

    if (!txtID) {
      // New product
      $('#btnsave').text('Save Product');
      $('#btnsave').removeClass('btn-warning').addClass('btn-primary');
    } else if (detailsChanged) {
      // Editing product details
      $('#btnsave').text('Update Product');
      $('#btnsave').removeClass('btn-warning').addClass('btn-primary');
    } else if (stockQty > 0) {
      // Only adding stock
      $('#btnsave').text('Add Stock');
      $('#btnsave').removeClass('btn-primary').addClass('btn-warning');
    } else {
      // No changes
      $('#btnsave').text('Add Stock');
      $('#btnsave').removeClass('btn-primary').addClass('btn-warning');
    }
  }

  // Reset form when modal is opened for new product
  $('#addProductModal').on('show.bs.modal', function(e) {
    if (!$(e.relatedTarget).data('productId')) {
      $('#addProductForm')[0].reset();
      $('#txtID').val('');
      $('#btnsave').text('Save Product');
      $('#addProductModalLabel').text('Add New Product');
      originalValues = {};
      isEditMode = false;
    }
  });

  // Handle category change
  $('#category').on('change', function() {
    const selectedCategory = this.value;
    const txtID = $('#txtID').val();
    
    if (!selectedCategory) return;

    $.ajax({
      url: 'get_product_by_category.php?category=' + encodeURIComponent(selectedCategory),
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        if (data) {
          isEditMode = true;
          $('#txtID').val(data.id);
          $('#txtProductcode').val(data.product);
          $('#txtvalvetype').val(data.valvetype || '');
          $('#txtpurchaseprice').val(data.purchaseprice);
          $('#txtsaleprice2').val(data.saleprice);
          $('#txtCurrentStock').val(data.stock);
          $('#txtStockQty').val('');
          $('#txtBrand').val(data.brand);
          $('#txtExpirydate').val(data.expirydate);
          $('#txtSupplierCategory').val(data.supplier_category || '');
          
          // Store original values for change detection
          originalValues = {
            purchaseprice: data.purchaseprice,
            saleprice: data.saleprice,
            expirydate: data.expirydate,
            productcode: data.product,
            brand: data.brand,
            valvetype: data.valvetype || '',
            supplierCategory: data.supplier_category || ''
          };
          
          $('#addProductModalLabel').text('Edit Product - Add Stock');
          updateButtonLabel();
          
          Swal.fire({
            icon: 'info',
            title: 'Product Found',
            text: 'Existing product details loaded. Modify as needed.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
          });
        } else {
          isEditMode = false;
          $('#txtID').val('');
          $('#txtProductcode').val('');
          $('#txtStockQty').val('');
          $('#txtCurrentStock').val(0);
          $('#txtExpirydate').val('');
          originalValues = {};
          
          const cfg = mapping[selectedCategory];
          if (cfg && !txtID) {
            $('#txtpurchaseprice').val(cfg.purchase);
            $('#txtsaleprice2').val(cfg.sale);
            $('#txtBrand').val(cfg.brand);
            $('#txtvalvetype').val(cfg.valvetype);
          }
          
          $('#btnsave').text('Save Product');
          $('#addProductModalLabel').text('Add New Product');
        }
      },
      error: function() {
        console.error('Error fetching product');
      }
    });
  });

  // Add event listeners to detect field changes
  $('#txtpurchaseprice, #txtsaleprice2, #txtExpirydate, #txtProductcode, #txtBrand, #txtvalvetype, #txtSupplierCategory, #txtStockQty').on('change keyup', function() {
    updateButtonLabel();
  });

  // Handle form submission
  $('#addProductForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
      url: 'api/save_product.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.message,
            timer: 2000
          });
          
          $('#addProductModal').modal('hide');
          setTimeout(function() {
            location.reload();
          }, 1500);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message
          });
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while saving the product'
        });
      }
    });
  });
});
</script>