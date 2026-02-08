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

/**
 * Save order
 */
if (isset($_POST['btnsaveorder'])) {
    try {
        $pdo->beginTransaction();

        $orderdate     = date("Y-m-d");
        $subtotal      = floatval($_POST['txtsubtotal'] ?? 0);
        $discount      = floatval($_POST['txtdiscount'] ?? 0);
        $sgst          = floatval($_POST['txtsgst'] ?? 0);
        $cgst          = floatval($_POST['txtcgst'] ?? 0);
        $total         = floatval($_POST['txttotal'] ?? 0);
        $payment_type  = $_POST['rb'] ?? 'cash';
        $due           = floatval($_POST['txtdue'] ?? 0);
        $paid          = floatval($_POST['txtpaid'] ?? 0);

        // Arrays from form
        $arr_pid       = $_POST['pid_arr'] ?? [];
        $arr_name      = $_POST['product_arr'] ?? [];
        $arr_stock     = $_POST['stock_c_arr'] ?? [];
        $arr_qty       = $_POST['quantity_arr'] ?? [];
        $arr_price     = $_POST['price_arr'] ?? [];
        $arr_total     = $_POST['saleprice_arr'] ?? [];
        $arr_service   = $_POST['service_c_arr'] ?? [];
        $arr_addfee    = $_POST['addfee_c_arr'] ?? [];

        // Insert into tbl_invoice (main)
        $insertInvoice = $pdo->prepare("
            INSERT INTO tbl_invoice 
            (order_date, subtotal, discount, sgst, cgst, total, payment_type, due, paid)
            VALUES 
            (:order_date, :subtotal, :discount, :sgst, :cgst, :total, :payment_type, :due, :paid)
        ");

        $insertInvoice->execute([
            ':order_date'   => $orderdate,
            ':subtotal'     => $subtotal,
            ':discount'     => $discount,
            ':sgst'         => $sgst,
            ':cgst'         => $cgst,
            ':total'        => $total,
            ':payment_type' => $payment_type,
            ':due'          => $due,
            ':paid'         => $paid,
        ]);

        $invoice_id = $pdo->lastInsertId();

        if ($invoice_id) {
            // Update product stock
            $updateStock = $pdo->prepare("UPDATE tbl_product SET stock = :stock WHERE pid = :pid");

            // Insert invoice details
            $insertDetail = $pdo->prepare("
                INSERT INTO tbl_invoice_details
                (invoice_id, product_id, product_name, qty, rate, saleprice, order_date, servicetype, addfee)
                VALUES 
                (:invoice_id, :pid, :product_name, :qty, :rate, :saleprice, :order_date, :servicetype, :addfee)
            ");

            for ($i = 0; $i < count($arr_pid); $i++) {
                $pid   = intval($arr_pid[$i]);
                $name  = $arr_name[$i] ?? '';
                $stock = isset($arr_stock[$i]) ? intval($arr_stock[$i]) : 0;
                $qty   = isset($arr_qty[$i]) ? intval($arr_qty[$i]) : 0;
                $rate  = isset($arr_price[$i]) ? floatval($arr_price[$i]) : 0;
                $lineTotal = isset($arr_total[$i]) ? floatval($arr_total[$i]) : 0;
                $service = $arr_service[$i] ?? 'Pick up';
                $addfee = isset($arr_addfee[$i]) ? floatval($arr_addfee[$i]) : 0.00;

                // Update stock
                $remaining = $stock - $qty;
                if ($remaining < 0) {
                    $pdo->rollBack();
                    header('Location: pos.php?error=insufficient_stock');
                    exit;
                }

                $updateStock->execute([
                    ':stock' => $remaining,
                    ':pid'   => $pid,
                ]);

                // Insert details
                $insertDetail->execute([
                    ':invoice_id'   => $invoice_id,
                    ':pid'          => $pid,
                    ':product_name' => $name,
                    ':qty'          => $qty,
                    ':rate'         => $rate,
                    ':saleprice'    => $lineTotal,
                    ':order_date'   => $orderdate,
                    ':servicetype'  => $service,
                    ':addfee'       => $addfee,
                ]);
            }
        }

        $pdo->commit();
        header('Location: orderlist.php');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<pre>❌ ERROR: " . $e->getMessage() . "</pre>";
        exit;
    }
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
                  <select id="product_select" class="form-control" style="width: 100%;">
                    <option value=""> Select Product</option>
                    <?php echo fill_product($pdo); ?>
                  </select>
                  <br>

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
                  <input type="text" class="form-control" name="txtdiscount" id="txtdiscount_p" value="<?php echo htmlspecialchars($row->discount ?? 0); ?>">
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
                    <button type="submit" class="btn btn-success" name="btnsaveorder">Save Order</button>
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
    const btnSave = $("button[name='btnsaveorder']");
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