<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
session_start();


require_once 'connectdb.php';
require_once 'header.php';

/**
 * Return <option> list of products (value = pid)
 */
function fill_product($pdo)
{
    $output = '';
    $select = $pdo->prepare("SELECT pid, product FROM tbl_product ORDER BY product ASC");
    $select->execute();
    $result = $select->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as $row) {
        $output .= '<option value="' . htmlspecialchars($row['pid']) . '">' . htmlspecialchars($row['product']) . '</option>';
    }
    return $output;
}

ob_end_flush();
// Fetch tax/discount config
$select = $pdo->prepare("SELECT * FROM tbl_taxdis WHERE taxdis_id = 1 LIMIT 1");
$select->execute();
$row = $select->fetch(PDO::FETCH_OBJ);
?>
<!------------- HTML / View -------------->

<style type="text/css">
  .tableFixHead {
    overflow: auto;
    height: 520px;
  }
  .tableFixHead thead th {
    position: sticky;
    top: 0;
    z-index: 1;
  }
  table { border-collapse: collapse; width: 100%; }
  th, td { padding: 8px 12px; }
  th { background: #eee; }
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right"></ol></div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="col-lg-12">
        <div class="card card-primary card-outline">
          <div class="card-header"><h5 class="m-0">POS</h5></div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-9">
                <form action="" method="post" name="posform" id="posform">
                  <!-- Customer Information Button -->
                  <div class="row mb-3">
                    <div class="col-md-12">
                      <button type="button" class="btn btn-info btn-lg btn-block" id="btnCustomerInfo" data-toggle="modal" data-target="#customerModal">
                        <i class="fas fa-user"></i> Customer Information
                      </button>
                    </div>
                  </div>

                  <!-- Product Selection Button -->
                  <div class="row mb-3">
                    <div class="col-md-12">
                      <button type="button" class="btn btn-warning btn-lg btn-block" id="btnSelectProduct" data-toggle="modal" data-target="#productModal">
                        <i class="fas fa-box"></i> Click to Select Category
                      </button>
                    </div>
                  </div>

                  <div class="tableFixHead">
                    <table id="producttable" class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th>Product Code</th>
                          <th>Brand</th>
                          <th>Category</th>
                          <th>Valve Type</th>
                          <th>Expiry</th>
                          <th>Price</th>
                          <th>QTY</th>
                          <th>Service</th>
                          <th>Add fee</th>
                          <th>Total</th>
                          <th>Del</th>
                        </tr>
                      </thead>
                      <tbody class="details" id="itemtable">
                        <!-- rows appended here -->
                      </tbody>
                    </table>
                  </div>
                <!-- form continues in right column -->
              </div>

              <div class="col-md-3">
                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">ITEM SUBTOTAL(₱)</span></div>
                  <input type="text" class="form-control" name="txtsubtotal" id="txtsubtotal_id" readonly>
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>

                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">SERVICE FEE(₱)</span></div>
                  <input type="text" class="form-control" name="txtservicefee" id="txtservicefee" readonly>
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>

                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">DISCOUNT(%)</span></div>
                  <input type="text" class="form-control" name="txtdiscount" id="txtdiscount_p" value="0">
                  <div class="input-group-append"><span class="input-group-text">%</span></div>
                </div>

                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">DISCOUNT(₱)</span></div>
                  <input type="text" class="form-control" id="txtdiscount_n" readonly>
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>



                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">TOTAL(₱)</span></div>
                  <input type="text" class="form-control form-control-lg total" name="txttotal" id="txttotal" readonly>
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>

                <hr>

                <div class="icheck-success d-inline">
                  <input type="radio" name="rb" value="cash" checked id="radioSuccess1">
                  <label for="radioSuccess1">CASH</label>
                </div>

                
                <hr>

                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">DUE(₱)</span></div>
                  <input type="text" class="form-control" name="txtdue" id="txtdue" readonly>
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>

                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">PAID(₱)</span></div>
                  <input type="text" class="form-control" name="txtpaid" id="txtpaid">
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>

                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">CHANGE(₱)</span></div>
                  <input type="text" class="form-control" name="txtchange" id="txtchange" readonly>
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>

                <hr>

                <div class="card-footer">
                  <div class="text-center">
                    <button type="button" class="btn btn-success" id="btnSaveOrderAjax" name="btnsaveorder">Save Order</button>
                  </div>
                </div>
              </div> <!-- /.col-md-4 -->
            </div> <!-- /.row -->
          </div> <!-- /.card-body -->
        </div> <!-- /.card -->
      </div> <!-- /.col-lg-12 -->
    </div> <!-- /.container-fluid -->
    </form>
  </div> <!-- /.content -->
</div> <!-- /.content-wrapper -->

<!-- Product Selection Modal -->
<div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productModalLabel">Select Product</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="product_select"><strong>Choose Product</strong></label>
          <select id="product_select" class="form-control">
            <option value="">-- Select a Product --</option>
            <?php echo fill_product($pdo); ?>
          </select>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Customer Information Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-labelledby="customerModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerModalLabel">Customer Information</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="txtcustomer_name"><strong>Customer Name <span class="text-danger">*</span></strong></label>
          <input type="text" class="form-control" id="txtcustomer_name" name="txtcustomer_name" placeholder="Enter customer name" required>
          <small class="form-text text-muted">Customer name is required</small>
        </div>

        <div class="form-group">
          <label for="txtcustomer_contact"><strong>Contact Number</strong></label>
          <input type="text" class="form-control" id="txtcustomer_contact" name="txtcustomer_contact" placeholder="Enter contact number">
        </div>

        <div class="form-group">
          <label for="txtcustomer_address"><strong>Address</strong></label>
          <textarea class="form-control" id="txtcustomer_address" name="txtcustomer_address" placeholder="Enter address" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="btnSaveCustomerInfo" data-dismiss="modal">Save Customer Info</button>
      </div>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document" style="max-width: 480px;">
    <div class="modal-content">
      <div class="modal-header" style="padding: 12px 15px;">
        <h5 class="modal-title" id="receiptModalLabel">Order Receipt</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="receiptContent" style="max-height: 70vh; overflow-y: auto; padding: 0;">
        <!-- Receipt content will be loaded here -->
      </div>
      <div class="modal-footer" style="padding: 12px 15px;">
        <button type="button" class="btn btn-primary btn-sm" id="btnPrintReceipt"><i class="fas fa-print"></i> Print</button>
        <button type="button" class="btn btn-success btn-sm" id="btnNewOrder">New Order</button>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
  // Initialize Select2 Elements (if any others exist, but product_select is now standard)
  // $('.select2').select2(); // Disabled as per request to use simple dropdowns

  let productarr = [];

  /**
   * addRow: inserts a new product row in the table
   * params order: pid, product, brand, category, expirydate, saleprice (unit), stock, servicetype, additionalfee
   */
  function addRow(pid, product, brand, category, valvetype, expirydate, saleprice, stock, servicetype, addfee) {
    const unitPrice = parseFloat(saleprice) || 0;
    const qtyDefault = 1;
    
    // Determine fee based on service type
    let feePerQty = 0;
    if (servicetype === 'Delivery') {
      feePerQty = 50;
    } else {
      feePerQty = 0;
    }
    
    const totalFee = feePerQty * qtyDefault;
    const lineTotal = (unitPrice * qtyDefault) + totalFee;

    const tr = `
      <tr data-pid="${pid}">
        <td style="text-align:left; vertical-align:middle; font-size:17px;">
          <span class="badge badge-dark">${product}</span>
          <input type="hidden" name="pid_arr[]" value="${pid}">
          <input type="hidden" name="product_arr[]" value="${product}">
        </td>

        <td style="text-align:left; vertical-align:middle; font-size:17px;">
          <span class="badge badge-primary brandlbl">${brand}</span>
          <input type="hidden" name="brand_c_arr[]" value="${brand}">
        </td>

        <td style="text-align:left; vertical-align:middle; font-size:17px;">
          <span class="badge badge-info categorylbl">${category}</span>
          <input type="hidden" name="category_c_arr[]" value="${category}">
        </td>

        <td style="text-align:left; vertical-align:middle; font-size:17px;">
          <span class="badge badge-secondary valvetype">${valvetype}</span>
          <input type="hidden" name="valvetype_c_arr[]" value="${valvetype}">
        </td>

        <td style="text-align:left; vertical-align:middle; font-size:17px;">
          <span class="badge badge-secondary expiry">${expirydate}</span>
          <input type="hidden" name="expiry_c_arr[]" value="${expirydate}">
        </td>

        <td style="text-align:left; vertical-align:middle; font-size:17px;">
          <span class="badge badge-warning price">${unitPrice.toFixed(2)}</span>
          <input type="hidden" name="price_arr[]" value="${unitPrice.toFixed(2)}">
        </td>

        <td>
          <input type="number" class="form-control qty" name="quantity_arr[]" id="qty_id${pid}" value="${qtyDefault}" min="1" data-stock="${stock}">
          <input type="hidden" name="stock_c_arr[]" value="${stock}">
        </td>

        <td style="text-align:left; vertical-align:middle;">
          <select class="form-control form-control-sm service-select" name="service_c_arr[]">
            <option value="Pick up" ${servicetype === 'Pick up' ? 'selected' : ''}>Pick up</option>
            <option value="Delivery" ${servicetype === 'Delivery' ? 'selected' : ''}>Delivery</option>
          </select>
        </td>

        <td style="text-align:left; vertical-align:middle; font-size:17px;">
          <span class="badge badge-primary addfee-display">${totalFee.toFixed(2)}</span>
          <input type="hidden" class="addfee" name="addfee_c_arr[]" value="${totalFee.toFixed(2)}">
        </td>

        <td style="text-align:left; vertical-align:middle; font-size:17px;">
          <span class="badge badge-success totalamt">${lineTotal.toFixed(2)}</span>
          <input type="hidden" class="saleprice" name="saleprice_arr[]" value="${lineTotal.toFixed(2)}">
        </td>

        <td>
          <center>
            <button type="button" class="btn btn-danger btn-sm btnremove" data-id="${pid}">
              <span class="fas fa-trash"></span>
            </button>
          </center>
        </td>
      </tr>
    `;

    // Append row and recalc totals
    $('.details').append(tr);
    calculate(0, 0);
  }

  // AJAX fetch for barcode input (if you use txtbarcode_id)
  $(function () {
    // Update customer info button when modal closes
    $('#customerModal').on('hidden.bs.modal', function () {
      updateCustomerInfoButton();
    });

    // Save customer info button
    $('#btnSaveCustomerInfo').click(function () {
      const customerName = $('#txtcustomer_name').val().trim();
      if (!customerName) {
        Swal.fire('Error', 'Please enter customer name', 'error');
        return;
      }
      // Modal will close automatically with data-dismiss
    });

    // Function to update button text based on customer info
    function updateCustomerInfoButton() {
      const customerName = $('#txtcustomer_name').val().trim();
      const btn = $('#btnCustomerInfo');
      
      if (customerName) {
        btn.removeClass('btn-info').addClass('btn-success');
        btn.html('<i class="fas fa-user"></i> ' + htmlEscapeCustomerName(customerName));
      } else {
        btn.removeClass('btn-success').addClass('btn-info');
        btn.html('<i class="fas fa-user"></i> Customer Information');
      }
    }

    // Helper function to escape HTML in customer name
    function htmlEscapeCustomerName(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, m => map[m]).substring(0, 25) + (text.length > 25 ? '...' : '');
    }

    // Initialize button on page load
    updateCustomerInfoButton();

    $('#txtbarcode_id').on('change', function () {
      const barcode = $(this).val();
      if (!barcode) return;
      $.ajax({
        url: 'getproduct.php',
        method: 'GET',
        dataType: 'json',
        data: { id: barcode },
        success: function (data) {
          if (!data || !data.pid) return;
          if ($.inArray(String(data.pid), productarr) !== -1) {
            const qtyEl = $('#qty_id' + data.pid);
            qtyEl.val((parseInt(qtyEl.val() || 0, 10) + 1)).trigger('change');
          } else {
            // Use data from DB
            const service = data.servicetype || 'Pick up';
            const addFee = parseFloat(data.additionalfee) || 0; 
            
            addRow(data.pid, data.product, data.brand, data.category, data.valvetype, data.expirydate, data.saleprice, data.stock, service, addFee);
            productarr.push(String(data.pid));
          }
          $('#txtbarcode_id').val('');
        }
      });
    });

    // Select2 product select flow
    $('#product_select').on('change', function () {
      const productid = $(this).val();
      if (!productid) return;
      $.ajax({
        url: 'getproduct.php',
        method: 'GET',
        dataType: 'json',
        data: { id: productid },
        success: function (data) {
          if (!data || !data.pid) return;
          if ($.inArray(String(data.pid), productarr) !== -1) {
            const qtyEl = $('#qty_id' + data.pid);
            qtyEl.val((parseInt(qtyEl.val() || 0, 10) + 1)).trigger('change');
          } else {
            const service = data.servicetype || 'Pick up';
            const addFee = parseFloat(data.additionalfee) || 0;
            addRow(data.pid, data.product, data.brand, data.category, data.valvetype || '', data.expirydate, data.saleprice, data.stock, service, addFee);
            productarr.push(String(data.pid));
          }
          $('#product_select').val('').trigger('change');
          // Close the product modal after selection
          $('#productModal').modal('hide');
        }
      });
    });
    // Delegate qty change
    $("#itemtable").on("input change", ".qty", function () {
      const $this = $(this);
      const tr = $this.closest('tr');
      const qty = parseInt(tr.find('.qty').val() || 0, 10);
      const stock = parseInt(tr.find('.qty').data('stock') || tr.find('input[name="stock_c_arr[]"]').val() || 0, 10);

      if (qty > stock) {
        Swal.fire("WARNING!", "SORRY! This Much of Quantity Is Not Available", "warning");
        tr.find('.qty').val(1);
      }

      // Re-read current qty after validation
      const validQty = parseInt(tr.find('.qty').val() || 0, 10);
      
      // compute add fee based on service type and quantity
      const serviceVal = tr.find('.service-select').val();
      const feePerQty = (serviceVal === 'Delivery') ? 50 : 0;
      const fee = validQty * feePerQty;

      tr.find('.addfee-display').text(fee.toFixed(2));
      tr.find('.addfee').val(fee.toFixed(2));

      updateRowTotal(tr[0]);
      calculate(0, 0);
    });

    // Handle service type change
    $("#itemtable").on("change", ".service-select", function () {
      const $this = $(this);
      const tr = $this.closest('tr');
      const serviceVal = $this.val();
      const qty = parseInt(tr.find('.qty').val() || 0, 10);
      
      
      const feePerQty = (serviceVal === 'Delivery') ? 50 : 0;
      const fee = qty * feePerQty;

      tr.find('.addfee-display').text(fee.toFixed(2));
      tr.find('.addfee').val(fee.toFixed(2));

      updateRowTotal(tr[0]);
      calculate(0, 0);
    });

    // Remove row
    $(document).on('click', '.btnremove', function () {
      const removed = $(this).attr("data-id");
      productarr = $.grep(productarr, function (value) { return value != removed; });
      $(this).closest("tr").remove();
      calculate(0, 0);
    });

    // Discount / Paid events
    $("#txtdiscount_p").on('input', function () {
      calculate(parseFloat($(this).val() || 0), 0);
    });

    $("#txtpaid").on('input', function () {
      calculate(parseFloat($("#txtdiscount_p").val() || 0), parseFloat($(this).val() || 0));
    });

    // AJAX Form Submission
    $('#btnSaveOrderAjax').click(function() {
      const $this = $(this);
      const originalText = $this.text();
      
      // Validate customer name again
      const customerName = $('#txtcustomer_name').val().trim();
      if (!customerName) {
        Swal.fire('Error', 'Please enter customer name', 'error');
        return;
      }

      // Check if cart is empty
      if ($('.details tr').length === 0) {
        Swal.fire('Error', 'Please add items to cart', 'error');
        return;
      }

      // Disable button and show loading state
      $this.prop('disabled', true).text('Saving...');

      const formData = new FormData($('#posform')[0]);
      formData.append('btnsaveorder', '1');
      
      // Append customer information from modal inputs
      formData.append('txtcustomer_name', $('#txtcustomer_name').val());
      formData.append('txtcustomer_contact', $('#txtcustomer_contact').val());
      formData.append('txtcustomer_address', $('#txtcustomer_address').val());

      $.ajax({
        url: 'saveorder_ajax.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
          if (response.success && response.invoice_id) {
            // Load receipt into modal
            $('#receiptContent').load('get_receipt.php?id=' + response.invoice_id, function() {
              // Show the modal
              $('#receiptModal').modal('show');
            });
          } else {
            Swal.fire('Error', response.message || 'Failed to save order', 'error');
          }
        },
        error: function(xhr, status, error) {
          Swal.fire('Error', 'Failed to save order: ' + error, 'error');
        },
        complete: function() {
          $this.prop('disabled', false).text(originalText);
        }
      });
    });

    // Print Receipt
    $('#btnPrintReceipt').click(function() {
      const printWindow = window.open('', '', 'width=800,height=600');
      const receiptContent = $('#receiptContent').html();
      
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Receipt</title>
          <style>
            body { font-family: 'Courier New', monospace; margin: 10px; }
            @media print {
              body { margin: 0; padding: 0; }
            }
          </style>
        </head>
        <body>
          ${receiptContent}
        </body>
        </html>
      `);
      
      printWindow.document.close();
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 250);
    });

    // New Order - Reset form
    $('#btnNewOrder').click(function() {
      // Reset customer info
      $('#txtcustomer_name').val('');
      $('#txtcustomer_contact').val('');
      $('#txtcustomer_address').val('');
      
      // Clear cart
      $('.details').empty();
      productarr = [];
      
      // Reset form
      $('#posform')[0].reset();
      
      // Reset calculations
      calculate(0, 0);
      
      // Close modal
      $('#receiptModal').modal('hide');
      
      // Focus on customer name field
      $('#txtcustomer_name').focus();
    });
  });

  // Recalculate one row total and update hidden input
  function updateRowTotal(row) {
    const unitPrice = parseFloat(row.querySelector('.price')?.textContent || 0);
    const qty = parseFloat(row.querySelector('.qty')?.value || 0);
    const addFee = parseFloat(row.querySelector('.addfee')?.value || 0);

    const lineTotal = (unitPrice * qty) + addFee;
    row.querySelector('.totalamt').textContent = lineTotal.toFixed(2);
    row.querySelector('.saleprice').value = lineTotal.toFixed(2);
  }

  // Recompute subtotal, taxes, discount, total, due
  function calculate(discountPercent, paidAmt) {
    let subtotal = 0;
    let serviceFee = 0;

    $(".details tr").each(function () {
      const rowPrice = parseFloat($(this).find('.price').text() || 0);
      const rowQty = parseFloat($(this).find('.qty').val() || 0);
      const rowFee = parseFloat($(this).find('.addfee').val() || 0);

      subtotal += (rowPrice * rowQty);
      serviceFee += rowFee;
    });

    $("#txtsubtotal_id").val(subtotal.toFixed(2));
    $("#txtservicefee").val(serviceFee.toFixed(2));

    const discountPct = parseFloat($("#txtdiscount_p").val() || 0);
    const discountN = (discountPct / 100) * subtotal;

    $("#txtdiscount_n").val(discountN.toFixed(2));

    const total = subtotal + serviceFee - discountN;
    const paid = parseFloat(paidAmt || 0);
    const due = total; // Due is now fixed at the total amount
    let change = paid - total;

    if (change < 0) {
      change = 0;
    }

    $("#txttotal").val(total.toFixed(2));
    $("#txtdue").val(due.toFixed(2));
    $("#txtchange").val(change.toFixed(2));

    // Disable Save Order button if payment is insufficient
    const btnSave = $("#btnSaveOrderAjax");
    if (paid < (total - 0.01)) { // Using 0.01 to handle minor float precision issues
      btnSave.prop('disabled', true);
      btnSave.text("Incomplete Payment");
      btnSave.removeClass('btn-success').addClass('btn-danger');
    } else {
      btnSave.prop('disabled', false);
      btnSave.text("Save Order");
      btnSave.removeClass('btn-danger').addClass('btn-success');
    }
  }
</script>