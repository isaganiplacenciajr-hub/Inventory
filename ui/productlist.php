<?php
 
 include_once 'connectdb.php';
 session_start();

include_once"header.php";


?>


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <button class="btn btn-success" data-toggle="modal" data-target="#addProductModal">
              <i class="fas fa-plus"></i> Add New Product
            </button>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
             <!-- <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li> -->
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            

          <div class="card card-primary card-outline">
              <div class="card-header">
                <h5 class="m-0">Product List</h5>
              </div>
              <div class="card-body">
              

              <table class="table table-striped table-hover" id="table_product">
  <thead> 
<tr>

  <td>Product Code</td>
  <td>Category</td>
  <td>Supplier Category</td>
  <td>Valve Type</td>
  <td>Added Stock</td>
  <td>Current Stock (Total)</td>
  <td>Purchase Price</td>
  <td>Sale Price</td>
  <td>Product Image</td>
  <td>Action Icons</td>
 

</tr>

</thead>




<tbody>
<?php 

$select = $pdo->prepare("select * from tbl_product order by pid ASC");
$select->execute();

while($row=$select->fetch(PDO::FETCH_OBJ))

{
  $supplier_category = !empty($row->supplier_category) ? htmlspecialchars($row->supplier_category) : 'N/A';

  echo'
  <tr>
  <td>'.$row->product.'</td>
  <td>'.$row->category.'</td>
  <td>'.$supplier_category.'</td>
  <td>'.$row->valvetype.'</td>
  <td>'.$row->addedstock.'</td>
  <td>'.$row->stock.'</td>
  <td>'.$row->purchaseprice.'</td>
  <td>'.$row->saleprice.'</td>
  <td><img src="productimages/'.$row->image.'" class="img-rounded" width="40px"/></td>


  <td>
<div class="btn-group">


<button class="btn btn-warning btn-xs btnview" data-id="'.$row->pid.'" data-toggle="tooltip" title="View Product"><span class="fa fa-eye" style="color:#ffffff"></span> </button>



<button id="'.$row->pid.'"class="btn btn-danger btn-xs btndelete"><span class="fa fa-trash" style="color:#ffffff" data-toggle="tooltip" title="Delete Product"></span> </button>

</div>
</td>
</tr>
';

}

?>

</tbody>
</table>

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

<!-- VIEW PRODUCT MODAL -->
<div class="modal fade" id="viewProductModal" tabindex="-1" role="dialog" aria-labelledby="viewProductModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewProductModalLabel">Product Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="viewProductContent">
        <!-- Product details will be loaded here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" id="btnEditProduct">Edit</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
 
 
 <?php

include_once"footer.php";


?>





<script>

$(document).ready(function () {     
    $('#table_product').DataTable({
      'responsive': true,
      'autoWidth': false
    });
} );


</script>


<script>

$(document).ready(function () {     
   $('[data-toggle="tooltip"]').tooltip();
} );


</script>

<!-- MODAL FORM HANDLING -->
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
    // Only reset if not editing
    if (!$(e.relatedTarget).data('productId')) {
      $('#addProductForm')[0].reset();
      $('#txtID').val('');
      $('#btnsave').text('Save Product');
      $('#addProductModalLabel').text('Add New Product');
      originalValues = {};
    }
  });

  // Handle category change
  $('#category').on('change', function() {
    const selectedCategory = this.value;
    const txtID = $('#txtID').val();
    
    if (!selectedCategory) return;

    // Fetch product details by category
    $.ajax({
      url: 'get_product_by_category.php?category=' + encodeURIComponent(selectedCategory),
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        if (data) {
          // Product exists, autofill details
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
          // Product does not exist
          $('#txtID').val('');
          $('#txtProductcode').val('');
          $('#txtStockQty').val('');
          $('#txtCurrentStock').val(0);
          $('#txtExpirydate').val('');
          originalValues = {};
          
          // Apply hardcoded defaults
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
          
          // Close modal and reload table
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

  // View product button
  $(document).on('click', '.btnview', function() {
    const productId = $(this).data('id');
    
    $.ajax({
      url: 'api/get_product_details.php?id=' + productId,
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        if (data.success) {
          const product = data.product;
          const supplierCategory = product.supplier_category || 'N/A';
          const expiryDisplay = (product.expirydate && product.expirydate !== '0000-00-00') 
            ? new Date(product.expirydate).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})
            : 'N/A';
          
          const profit = parseFloat(product.saleprice) - parseFloat(product.purchaseprice);
          
          let html = `
            <div class="row">
              <div class="col-md-6">
                <ul class="list-group">
                  <li class="list-group-item list-group-item-info"><b>PRODUCT DETAILS</b></li>
                  <li class="list-group-item"><b>Product Code</b><span class="badge badge-warning float-right">${product.product}</span></li>
                  <li class="list-group-item"><b>Category</b><span class="badge badge-success float-right">${product.category}</span></li>
                  <li class="list-group-item"><b>Supplier Category</b><span class="badge badge-info float-right">${supplierCategory}</span></li>
                  <li class="list-group-item"><b>Valve Type</b><span class="badge badge-primary float-right">${product.valvetype}</span></li>
                  <li class="list-group-item"><b>Purchase Price</b><span class="badge badge-secondary float-right">${product.purchaseprice}</span></li>
                  <li class="list-group-item"><b>Sale Price</b><span class="badge badge-dark float-right">${product.saleprice}</span></li>
                  <li class="list-group-item"><b>Quantity Added</b><span class="badge badge-warning float-right">${product.addedstock || 0}</span></li>
                  <li class="list-group-item"><b>Current Stock (Total)</b><span class="badge badge-info float-right">${product.stock}</span></li>
                  <li class="list-group-item"><b>Brand</b><span class="badge badge-light float-right">${product.brand || 'N/A'}</span></li>
                  <li class="list-group-item"><b>Expiry Date</b><span class="badge badge-secondary float-right">${expiryDisplay}</span></li>
                  <li class="list-group-item"><b>Product Profit</b><span class="badge badge-success float-right">${profit}</span></li>
                </ul>
              </div>
              <div class="col-md-6">
                <center>
                  <img src="productimages/${product.image}" class="img-fluid rounded" style="max-width: 300px; height: auto;"/>
                </center>
              </div>
            </div>
          `;
          
          $('#viewProductContent').html(html);
          $('#viewProductModal').modal('show');
          
          // Store product ID for edit action
          $('#btnEditProduct').data('productId', productId);
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to load product details'
        });
      }
    });
  });

  // Edit button in view modal
  $('#btnEditProduct').on('click', function() {
    const productId = $(this).data('productId');
    
    // Load product data into form
    $.ajax({
      url: 'api/get_product_details.php?id=' + productId,
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        if (data.success) {
          const product = data.product;
          $('#txtID').val(product.pid);
          $('#category').val(product.category);
          $('#txtProductcode').val(product.product);
          $('#txtvalvetype').val(product.valvetype);
          $('#txtpurchaseprice').val(product.purchaseprice);
          $('#txtsaleprice2').val(product.saleprice);
          $('#txtCurrentStock').val(product.stock);
          $('#txtBrand').val(product.brand);
          $('#txtExpirydate').val(product.expirydate);
          $('#txtSupplierCategory').val(product.supplier_category || '');
          $('#txtStockQty').val('');
          
          // Store original values for change detection
          originalValues = {
            purchaseprice: product.purchaseprice,
            saleprice: product.saleprice,
            expirydate: product.expirydate,
            productcode: product.product,
            brand: product.brand,
            valvetype: product.valvetype,
            supplierCategory: product.supplier_category || ''
          };
          
          $('#btnsave').text('Update Product');
          $('#addProductModalLabel').text('Edit Product');
          updateButtonLabel();
          
          // Close view modal and open edit modal
          $('#viewProductModal').modal('hide');
          $('#addProductModal').modal('show');
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to load product for editing'
        });
      }
    });
  });

  // Delete button
  $(document).on('click', '.btndelete', function() {
    var tdh = $(this);
    var id = $(this).attr("id");

    Swal.fire({
      title: "Do you Want to Delete?",
      text: "You Won't Able to Revert This",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: 'productdelete.php',
          type: "post",
          data: {
            pidd: id
          },
          success: function(data) {
            tdh.parents('tr').hide();
            Swal.fire({
              title: "Deleted!",
              text: "Product deleted successfully",
              icon: "success"
            });
          }
        });
      }
    });
  });
});
</script>