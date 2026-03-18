<?php
// Refactored user POS - organized PHP/HTML/CSS/JS
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
session_start();

require_once 'connectdb.php';
// prevent admins from using user POS
if (!isset($_SESSION['userid']) || ($_SESSION['role'] ?? '') === 'Admin') {
    header('location:../index.php');
    exit;
}
require_once 'headeruser.php';

/**
 * Helper: produce <option> list for product modal
 */
function fill_product($pdo)
{
    $out = '';
    $stmt = $pdo->prepare("SELECT pid, product FROM tbl_product ORDER BY product ASC");
    $stmt->execute();
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out .= '<option value="' . htmlspecialchars($r['pid']) . '">' . htmlspecialchars($r['product']) . '</option>';
    }
    return $out;
}

// get tax/discount defaults (if exists)
$cfg = $pdo->query("SELECT * FROM tbl_taxdis WHERE taxdis_id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
ob_end_flush();
?>

<div class="content-wrapper">
  <section class="content pt-3">
    <div class="container-fluid">

<!-- === Styles: sticky header + layout === -->
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
  /* hide product code column from user view but keep cells present for hidden data */
  #producttable th.d-none,
  #producttable td.pcode-col {
    display: none;
  }
  table { border-collapse: collapse; width: 100%; }
  th, td { padding: 8px 12px; }
  th { background: #eee; }
</style>

<!-- === Main content === -->
<div class="row mb-2">
  <div class="col-lg-12">
        <div class="card card-primary card-outline">
          <div class="card-header"><h5 class="m-0">POS</h5></div>
          <div class="card-body">
            <form action="" method="post" name="posform" id="posform">
            <div class="row">
              <div class="col-md-9">
                  <!-- Customer Information & Product Selection Buttons (admin-style) -->
                  <div class="row mb-3">
                    <div class="col-md-6 pr-2">
                      <button type="button" class="btn btn-info btn-lg btn-block" id="btnCustomerInfo" data-toggle="modal" data-target="#customerModal">
                        <i class="fas fa-user"></i> Customer Information
                      </button>
                    </div>
                    <div class="col-md-6 pl-2">
                      <button type="button" class="btn btn-warning btn-lg btn-block" id="btnSelectProduct" data-toggle="modal" data-target="#productModal">
                        <i class="fas fa-box"></i> Click to Select Category
                      </button>
                    </div>
                  </div>

                  <div class="tableFixHead">
                    <table id="producttable" class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <!-- product code column hidden for user POS -->
                          <th class="d-none">Product Code</th>
                          <th>Brand</th>
                          <th>Category</th>
                          <th>Valve Type</th>
                          <th>Empty Tank?</th>
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
                  <input type="text" class="form-control" name="txtdiscount" id="txtdiscount_p" value="">
                  <div class="input-group-append"><span class="input-group-text">%</span></div>
                </div>

                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">DISCOUNT(₱)</span></div>
                  <input type="text" class="form-control" id="txtdiscount_n" value="">
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>

                <!-- VAT percent and amount -->
                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">VAT(%)</span></div>
                  <input type="text" class="form-control" id="txtvat_p" name="txtvat_p" value="12">
                  <div class="input-group-append"><span class="input-group-text">%</span></div>
                </div>
                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">VAT AMT(₱)</span></div>
                  <input type="text" class="form-control" id="txtvat_n" name="txtvat_n" readonly>
                  <div class="input-group-append"><span class="input-group-text">₱</span></div>
                </div>

                <div class="input-group mb-2">
                  <div class="input-group-prepend"><span class="input-group-text">DEPOSIT(₱)</span></div>
                  <input type="text" class="form-control" id="txtdeposit" name="txtdeposit" readonly>
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

                <div class="icheck-success d-inline" style="margin-left: 20px;">
                  <input type="radio" name="rb" value="cod" id="radioSuccess2">
                  <label for="radioSuccess2">COD (Cash on Delivery)</label>
                </div>

                <hr>

                <div id="paymentFieldsContainer">
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
                </div>

                <hr>

                <div class="card-footer">
                  <div class="text-center mb-2">
                    <span id="paymentStatus" style="font-weight:bold; font-size:14px;">Complete Payment</span>
                  </div>
                  <div class="text-center">
                    <button type="button" class="btn btn-success" id="btnSaveOrderAjax" name="btnsaveorder">Save Order</button>
                  </div>
                </div>
              </div> <!-- /.col-md-9 -->
            </div> <!-- /.row -->
          </div> <!-- /.card-body -->
        </div> <!-- /.card -->
      </div> <!-- /.col-lg-12 -->
    </div> <!-- /.row -->
    </form>

<!-- === Product Modal (server populated select) === -->
<div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Select Product</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <div class="form-group">
          <label>Product</label>
          <select id="product_select" class="form-control">
            <option value="">-- Select product --</option>
            <?php echo fill_product($pdo); ?>
          </select>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- === Customer Modal === -->
<div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Customer Information</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <div class="form-group"><label>Name <span class="text-danger">*</span></label><input id="txtcustomer_name" class="form-control" required placeholder="Enter name"><small class="form-text text-muted">Customer name is required</small></div>
        <div class="form-group"><label>Contact</label><input id="txtcustomer_contact" class="form-control"></div>
        <div class="form-group"><label>Address</label><textarea id="txtcustomer_address" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal">Close</button><button id="btnSaveCustomerInfo" class="btn btn-primary" data-dismiss="modal">Save</button></div>
    </div>
  </div>
</div>

<!-- === Receipt Modal (loads get_receipt.php?id=...) === -->
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
      </div>
      <div class="modal-footer" style="padding: 12px 15px;">
        <button type="button" class="btn btn-primary btn-sm" id="btnPrintReceipt"><i class="fas fa-print"></i> Print</button>
        <button type="button" class="btn btn-success btn-sm" id="btnNewOrder">New Order</button>
      </div>
    </div>
  </div>
</div>

    </div>
  </section>
</div>

<?php require_once 'footer.php'; ?>

<!-- === Page Scripts: organized and optimized === -->
<script>
/*
  POS client logic
  - Uses server endpoints: getproduct.php (GET?id=) and saveorder_ajax.php (POST)
  - Keeps IDs and form field names compatible with server save logic
*/
(function($){
  'use strict';

  // State
  let discountPesoManual = false;
  let productArr = [];
  function formatMoney(n){ return parseFloat(n||0).toFixed(2); }

  // Add a product row (admin logic)
  function addRow(data){
    const pid = data.pid;
    if (!pid) return;
    if (productArr.includes(String(pid))) return incrementQty(pid, 1);
    const price = parseFloat(data.saleprice || 0);
    const stock = parseInt(data.stock || 0, 10) || 0;
    const service = data.servicetype || 'Pick up';
    const addfee = parseFloat(data.additionalfee || 0);
    const qtyDefault = 1;
    const feePerQty = (service === 'Delivery') ? 50 : 0;
    const fee = feePerQty * qtyDefault;
    const lineTotal = (price * qtyDefault) + fee;
    const is11kg = /11\s*k?g/i.test(data.category || '');
    const emptyHtml = is11kg ? '<select class="form-control form-control-sm emptytank" name="emptytank_arr[]"><option value="No" selected>No</option><option value="Yes">Yes</option></select>' : '';
    const $tr = $(
      '<tr data-pid="'+pid+'">' +
        '<td class="pcode-col"><span class="badge badge-dark">'+escapeHtml(data.product || '')+'</span>' +
          '<input type="hidden" name="pid_arr[]" value="'+pid+'">' +
          '<input type="hidden" name="product_arr[]" value="'+escapeHtml(data.product||'')+'">' +
        '</td>' +
        '<td><span class="badge badge-primary">'+escapeHtml(data.brand||'')+'</span><input type="hidden" name="brand_c_arr[]" value="'+escapeHtml(data.brand||'')+'"></td>' +
        '<td><span class="badge badge-info">'+escapeHtml(data.category||'')+'</span><input type="hidden" name="category_c_arr[]" value="'+escapeHtml(data.category||'')+'"></td>' +
        '<td><span class="badge badge-secondary">'+escapeHtml(data.valvetype||'')+'</span><input type="hidden" name="valvetype_c_arr[]" value="'+escapeHtml(data.valvetype||'')+'"></td>' +
        '<td>'+emptyHtml+'<input type="hidden" name="is11kg_arr[]" value="'+(is11kg?1:0)+'"></td>' +
        '<td><span class="badge badge-secondary">'+escapeHtml(data.expirydate||'')+'</span><input type="hidden" name="expiry_c_arr[]" value="'+escapeHtml(data.expirydate||'')+'"></td>' +
        '<td class="text-right"><span class="badge badge-warning price">'+formatMoney(price)+'</span><input type="hidden" name="price_arr[]" value="'+formatMoney(price)+'"></td>' +
        '<td><input type="number" class="form-control qty" name="quantity_arr[]" value="'+qtyDefault+'" min="1" data-stock="'+stock+'"><input type="hidden" name="stock_c_arr[]" value="'+stock+'"></td>' +
        '<td><select class="form-control form-control-sm service-select" name="service_c_arr[]"><option value="Pick up" selected>Pick up</option><option value="Delivery">Delivery</option></select></td>' +
        '<td><span class="badge badge-primary addfee-display">'+formatMoney(fee)+'</span><input type="hidden" class="addfee" name="addfee_c_arr[]" value="'+formatMoney(fee)+'"></td>' +
        '<td class="text-right"><span class="badge badge-success totalamt">'+formatMoney(lineTotal)+'</span><input type="hidden" class="saleprice" name="saleprice_arr[]" value="'+formatMoney(lineTotal)+'"></td>' +
        '<td class="text-center"><button class="btn btn-danger btn-sm btnremove" type="button" data-id="'+pid+'"><i class="fas fa-trash"></i></button></td>' +
      '</tr>'
    );
    $('#itemtable').append($tr);
    productArr.push(String(pid));
    calculate();
  }

  function incrementQty(pid, delta){
    const $tr = $('#itemtable').find('tr[data-pid="'+pid+'"]');
    if (!$tr.length) return;
    const $qty = $tr.find('.qty');
    const stock = parseInt($qty.data('stock')||0,10);
    let val = parseInt($qty.val()||0,10) + (delta||1);
    if (val < 1) val = 1;
    if (stock && val > stock) { Swal.fire('Warning','Quantity exceeds stock','warning'); val = stock; }
    $qty.val(val);
    updateRowTotal($tr[0]);
    calculate();
  }

  function updateRowTotal(row){
    const unit = parseFloat($(row).find('.price').text()||0);
    const qty = parseFloat($(row).find('.qty').val()||0);
    const addfee = parseFloat($(row).find('.addfee').val()||0);
    const total = (unit * qty) + addfee;
    $(row).find('.totalamt').text(formatMoney(total));
    $(row).find('.saleprice').val(formatMoney(total));
  }

  function computeTotals(){
    let subtotal = 0, serviceFee = 0, depositTotal = 0;
    $('#itemtable tr').each(function(){
      const $r = $(this);
      const unit = parseFloat($r.find('.price').text()||0);
      const qty = parseFloat($r.find('.qty').val()||0);
      const addfee = parseFloat($r.find('.addfee').val()||0);
      const is11kg = $r.find('input[name="is11kg_arr[]"]').val() === '1';
      const emptyVal = $r.find('.emptytank').val() || 'No';
      subtotal += unit * qty;
      serviceFee += addfee;
      if (is11kg && emptyVal === 'No') {
        depositTotal += 1200 * qty;
      }
    });
    $('#txtsubtotal_id').val(formatMoney(subtotal));
    $('#txtservicefee').val(formatMoney(serviceFee));
    $('#txtdeposit').val(formatMoney(depositTotal));

    const discountPct = parseFloat($('#txtdiscount_p').val()||0);
    let discountN = (discountPct / 100) * subtotal;
    if (!discountPesoManual && document.activeElement.id !== 'txtdiscount_n') {
      $('#txtdiscount_n').val(formatMoney(discountN));
    } else {
      const manualVal = $('#txtdiscount_n').val();
      discountN = manualVal === "" ? 0 : parseFloat(manualVal) || 0;
    }

    const vatPct = parseFloat($('#txtvat_p').val()||0);
    let vatN = 0;
    const baseForVat = subtotal - discountN;
    if (vatPct > 0) {
      vatN = baseForVat - (baseForVat / (1 + vatPct / 100));
    }
    $('#txtvat_n').val(formatMoney(vatN));

    const total = subtotal + serviceFee + depositTotal - discountN;
    let paid = parseFloat($('#txtpaid').val()||0) || 0;
    let due = total - paid;
    let change = paid - total;

    const paymentMethod = $('input[name="rb"]:checked').val();

    if (paymentMethod === 'cod') {
      // COD means payment handled later, use total as due 0 and change 0 in POS UI
      paid = 0;
      due = 0;
      change = 0;
    }

    if (due < 0) due = 0;
    if (change < 0) change = 0;

    $('#txttotal').val(formatMoney(total));
    $('#txtdue').val(formatMoney(due));
    $('#txtchange').val(formatMoney(change));

    const $btn = $('#btnSaveOrderAjax');
    let statusText = '';

    if (paymentMethod === 'cod') {
      $btn.prop('disabled', false)
          .removeClass('btn-danger').addClass('btn-success')
          .text('Save Order');
      statusText = 'Complete Payment';
    } else {
      if (paid < total - 0.01) {
        $btn.prop('disabled', true)
            .removeClass('btn-success').addClass('btn-danger')
            .text('Incomplete Payment');
        statusText = 'Incomplete Payment';
      } else {
        $btn.prop('disabled', false)
            .removeClass('btn-danger').addClass('btn-success')
            .text('Save Order');
        statusText = 'Complete Payment';
      }
    }

    $('#paymentStatus').text(statusText);
  }

  // keep backward compatibility with existing calculate() calls
  function calculate() {
    computeTotals();
  }

  // helper to show/hide payment fields just like admin POS
  function togglePaymentFields() {
    const paymentMethod = $('input[name="rb"]:checked').val();
    const $container = $('#paymentFieldsContainer');
    const $btn = $('#btnSaveOrderAjax');
    if (paymentMethod === 'cod') {
      $container.hide();
      $('#txtdue').val('0.00');
      $('#txtpaid').val('0.00');
      $('#txtchange').val('0.00');
      $btn.prop('disabled', false)
          .removeClass('btn-danger').addClass('btn-success')
          .text('Save Order');
      $('#paymentStatus').text('Complete Payment');
    } else {
      $container.show();
      calculate();
    }

    // keep totals in sync when switching payment type
    calculate();
  }

  // Escape HTML helper
  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]; }); }

  // Event bindings
  $(function(){

    // --- Discount field sync logic (with validation and formatting) ---
    let discountSyncing = false;
    function clamp(val, min, max) {
      return Math.max(min, Math.min(max, val));
    }
    // When DISCOUNT(%) changes, update DISCOUNT(₱) (typing-friendly, allow up to 999)
    $('#txtdiscount_p').on('input', function () {
      if (discountSyncing) return;
      let val = $(this).val();
      // Allow empty or partial input for typing
      if (val === "" || !/^\d{0,3}\.?\d*$/.test(val)) {
        $('#txtdiscount_n').val("");
        calculate();
        return;
      }
      // Only sync if valid number
      let pct = parseFloat(val);
      if (isNaN(pct)) {
        $('#txtdiscount_n').val("");
        calculate();
        return;
      }
      pct = clamp(pct, 0, 999); // allow up to 999
      discountSyncing = true;
      const subtotal = parseFloat($('#txtsubtotal_id').val()) || 0;
      let discount = subtotal * (pct / 100);
      discount = clamp(discount, 0, subtotal);
      $('#txtdiscount_n').val(discount ? discount.toFixed(2) : "");
      calculate();
      discountSyncing = false;
    }).on('blur', function() {
      let val = $(this).val();
      if (val !== "" && !isNaN(val)) {
        let pct = parseFloat(val);
        if (!isNaN(pct)) {
          pct = clamp(pct, 0, 999); // allow up to 999
          $(this).val(pct.toFixed(2));
        }
      }
    });
    // Discount (₱) field UX: clear 0.00 on focus, restore 0.00 on blur if empty, only format on blur, prevent negatives
    $('#txtdiscount_n').on('focus', function() {
      if ($(this).val() === '0.00') {
        $(this).val('');
      }
    });
    $('#txtdiscount_n').on('input', function () {
      if (discountSyncing) return;
      let val = $(this).val();
      // Allow only digits and one dot, any leading zeros, up to 2 decimals, no forced formatting while typing
      val = val.replace(/[^\d.]/g, '');
      // Only allow one dot
      const firstDot = val.indexOf('.');
      if (firstDot !== -1) {
        // Remove any additional dots
        val = val.substring(0, firstDot + 1) + val.substring(firstDot + 1).replace(/\./g, '');
        // Limit to 2 decimals if present
        const parts = val.split('.');
        if (parts.length > 1) {
          parts[1] = parts[1].slice(0, 2);
          val = parts[0] + '.' + parts[1];
        }
      }
      $(this).val(val);
      const subtotal = parseFloat($('#txtsubtotal_id').val()) || 0;
      if (val === "" || val === ".") {
        $('#txtdiscount_p').val("");
        calculate();
        return;
      }
      let peso = parseFloat(val);
      if (isNaN(peso)) {
        $('#txtdiscount_p').val("");
        calculate();
        return;
      }
      discountSyncing = true;
      let pct = subtotal > 0 ? (peso / subtotal) * 100 : 0;
      pct = clamp(pct, 0, 999);
      $('#txtdiscount_p').val(pct ? pct.toFixed(2) : "");
      calculate();
      discountSyncing = false;
    });
    $('#txtdiscount_n').on('blur', function() {
      let val = $(this).val();
      const subtotal = parseFloat($('#txtsubtotal_id').val()) || 0;
      if (val === "" || isNaN(parseFloat(val))) {
        $(this).val('0.00');
        $('#txtdiscount_p').val('0.00');
        calculate();
        return;
      }
      let peso = parseFloat(val);
      if (isNaN(peso) || peso < 0) peso = 0;
      if (subtotal > 0 && peso > subtotal) peso = subtotal;
      // Clamp to 6 digits before decimal
      if (peso > 999999.99) peso = 999999.99;
      $(this).val(peso.toFixed(2));
      // Sync percent on blur as well
      discountSyncing = true;
      let pct = subtotal > 0 ? (peso / subtotal) * 100 : 0;
      pct = clamp(pct, 0, 999);
      $('#txtdiscount_p').val(pct ? pct.toFixed(2) : "0.00");
      calculate();
      discountSyncing = false;
    });
    // When subtotal changes, re-sync discount fields
    $('#txtsubtotal_id').on('input', function () {
      $('#txtdiscount_p').trigger('input');
    });

    // Paid and discount changes should recalc totals immediately
    $('#txtpaid, #txtdiscount_n, #txtdiscount_p').on('input', function () {
      calculate();
    });

    // Service fee can be mutated by row-level service-select, but calculate now runs on change events via delegated handlers.
    // If there is any separate service dropdown, recalc there too.
    $('#service, #txtservicefee').on('change input', function() {
      calculate();
    });

    // Toggle payment fields when method changes (and keep button colors in sync)
    $('input[name="rb"]').on('change', function(){
      togglePaymentFields();
    });

    // Customer info modal handling
    $('#customerModal').on('hidden.bs.modal', function(){
      updateCustomerInfoButton();
    });
    $('#btnSaveCustomerInfo').on('click', function(){
      const name = $('#txtcustomer_name').val().trim();
      if (!name) {
        Swal.fire('Error','Please enter customer name','error');
        return;
      }
      // will close automatically thanks to data-dismiss
    });
    function updateCustomerInfoButton(){
      const name = $('#txtcustomer_name').val().trim();
      if (name) {
        $('#btnCustomerInfo').html('<i class="fas fa-user"></i> '+name);
      } else {
        $('#btnCustomerInfo').html('<i class="fas fa-user"></i> Customer Information');
      }
    }

    // Product select -> AJAX load product details -> addRow
    $('#product_select').on('change', function(){
      const id = $(this).val(); if (!id) return;
      $.getJSON('getproduct.php', { id: id })
        .done(function(data){ if (!data || !data.pid) return; addRow(data); })
        .fail(function(){ Swal.fire('Error','Failed to load product','error'); });
      $(this).val(''); $('#productModal').modal('hide');
    });

    // Delegate qty/service/addfee changes and row delete
    $('#itemtable')
      .on('input change', '.qty', function(){ 
        const $tr = $(this).closest('tr');
        // recalc service fee when qty changes (50 per tank for Delivery)
        const qty = parseInt($tr.find('.qty').val()||0,10) || 0;
        const serviceVal = $tr.find('.service-select').val();
        const feePer = (serviceVal === 'Delivery') ? 50 : 0;
        const fee = feePer * qty;
        $tr.find('.addfee').val(fee.toFixed(2));
        $tr.find('.addfee-display').text(fee.toFixed(2));
        updateRowTotal($tr[0]);
        calculate(); // Only recalc once, button state handled in calculate()
      })
      .on('change', '.service-select', function(){ 
        const $tr = $(this).closest('tr'); 
        const qty = parseInt($tr.find('.qty').val()||0,10) || 0; 
        const feePer = ($(this).val()==='Delivery')?50:0; 
        const fee = feePer * qty; 
        $tr.find('.addfee').val(fee.toFixed(2)); 
        $tr.find('.addfee-display').text(fee.toFixed(2));
        // no background color; user rows stay plain like admin version
        updateRowTotal($tr[0]); 
        calculate();
      })
      .on('change', '.emptytank', function() {
        calculate();
      })
      .on('click', '.btnremove', function(){
        const $tr = $(this).closest('tr');
        const pid = $tr.data('pid');
        $tr.remove();
        if (pid) productArr.delete(String(pid));
        calculate();
      });

    // AJAX Form Submission
    $('#btnSaveOrderAjax').click(function(){
      const $btn = $(this);
      const originalText = $btn.text();

      // Validate customer name again
      const customerName = $('#txtcustomer_name').val().trim();
      if (!customerName) {
        Swal.fire('Error','Please enter customer name','error');
        return;
      }

      // ensure cart not empty
      if ($('.details tr').length === 0) {
        Swal.fire('Error','Please add items to cart','error');
        return;
      }

      $btn.prop('disabled', true).text('Saving...');

      const fd = new FormData($('#posform')[0]);
      fd.append('btnsaveorder', '1');
      fd.append('txtcustomer_name', $('#txtcustomer_name').val());
      fd.append('txtcustomer_contact', $('#txtcustomer_contact').val());
      fd.append('txtcustomer_address', $('#txtcustomer_address').val());

      // Post to existing backend endpoint
      $.ajax({ url: 'saveorder_ajax.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
        .done(function(res){ if (res.success && res.invoice_id){ $('#receiptContent').load('get_receipt.php?id='+res.invoice_id+'&user_view=1', function(){ $('#receiptModal').modal('show'); }); } else { Swal.fire('Error', res.message||'Save failed','error'); } })
        .fail(function(xhr){ Swal.fire('Error','Save request failed','error'); })
        .always(function(){ $btn.prop('disabled', false).text(originalText); });
    });

    // Print receipt
    $('#btnPrintReceipt').on('click', function(){ const html = $('#receiptContent').html(); const w = window.open('','_blank'); w.document.write('<html><head><title>Receipt</title></head><body>'+html+'</body></html>'); w.print(); setTimeout(()=>w.close(),500); });

    // New order reset
    $('#btnNewOrder').on('click', function(){ $('#itemtable').empty(); productArr.clear(); $('#posform')[0].reset(); calculate(); $('#receiptModal').modal('hide'); });

    // init visibility
    togglePaymentFields();
    calculate();
  });

})(jQuery);
</script>

<!-- Only page-specific scripts (global libraries loaded in footer) -->
<script src="../plugins/sweetalert2/sweetalert2.min.js"></script>

</body>
</html>
