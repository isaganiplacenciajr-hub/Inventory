<?php
ob_start();

include_once 'connectdb.php';
session_start();

include_once "header.php";



function fill_product($pdo)
{

  $output = '';
  $select = $pdo->prepare("select * from tbl_product order by product asc");

  $select->execute();

  $result = $select->fetchAll();

  foreach ($result as $row) {
    $output .= '<option value="' . $row["pid"] . '">' . $row["product"] . '</option>';
  }
  return $output;
}

$id = $_GET["id"];

$select = $pdo->prepare("select * from tbl_invoice where invoice_id=$id");
$select->execute();
$row = $select->fetch(PDO::FETCH_ASSOC);


$order_date = date('y-m-d', strtotime($row['order_date']));

$subtotal       = $row['subtotal'];
$sgst           = $row['sgst'];
$cgst           = $row['cgst'];
$discount       = $row['discount'];
$total          = $row['total'];
$paid           = $row['paid'];
$due            = $row['due'];
$payment_type   = $row['payment_type'];


$select = $pdo->prepare("select * from tbl_invoice_details where invoice_id=$id");
$select->execute();
$row_invoice_details = $select->fetchAll(PDO::FETCH_ASSOC);











if (isset($_POST['btnupdateorder'])) {


  $txt_orderdate = date("Y-m-d ");
  $txt_subtotal      = $_POST['txtsubtotal'];
  $txt_discount      = $_POST['txtdiscount'];
  $txt_sgst          = $_POST['txtsgst'];
  $txt_cgst          = $_POST['txtcgst'];
  $txt_total         = $_POST['txttotal'];
  $txt_payment_type  = $_POST['rb'];
  $txt_due           = $_POST['txtdue'];
  $txt_paid          = $_POST['txtpaid'];

  ////////////////////////////////////////////////////////////////

  $arr_pid       = $_POST['product_arr'];
  $arr_name      = $_POST['pid_arr'];
  $arr_stock     = $_POST['stock_c_arr'];
  $arr_qty       = $_POST['quantity_arr'];
  $arr_price     = $_POST['stock_c'];
  $arr_total     = $_POST['saleprice_arr'];



  foreach ($row_invoice_details as $product_invoice_details) {

    $updateproduct_stock = $pdo->prepare("update tbl_product set stock=stock+"  . $product_invoice_details['qty'] . " where pid='" . $product_invoice_details['product_id'] . "'");
    $updateproduct_stock->execute();
  }


  $delete_invoice_details = $pdo->prepare("delete from tbl_invoice_details where invoice_id=$id");
  $delete_invoice_details->execute();










  $update_tbl_invoice = $pdo->prepare("update tbl_invoice SET order_date=:orderdate,subtotal=:subtotal,discount=:discount,sgst=:sgst,cgst=:cgst,total=:total,payment_type=:payment_type,due=:due,paid=:paid where invoice_id=$id");

  $update_tbl_invoice->bindparam(':orderdate', $txt_orderdate);
  $update_tbl_invoice->bindparam(':subtotal', $txt_subtotal);
  $update_tbl_invoice->bindparam(':discount', $txt_discount);
  $update_tbl_invoice->bindparam(':sgst', $txt_sgst);
  $update_tbl_invoice->bindparam(':cgst', $txt_cgst);
  $update_tbl_invoice->bindparam(':total', $txt_total);
  $update_tbl_invoice->bindparam(':payment_type', $txt_payment_type);
  $update_tbl_invoice->bindparam(':due', $txt_due);
  $update_tbl_invoice->bindparam(':paid', $txt_paid);

  $update_tbl_invoice->execute();


  // if($update_tbl_invoice->execute()){
  //   $_SESSION['status'] = "Order Update Successfully"; 
  //   $_SESSION['status_code'] = "success";

  // }else{

  //   $_SESSION['status'] = "Category Added Field!";
  //   $_SESSION['status_code'] = "warning";


  // }










  $invoice_id = $pdo->lastInsertId();

  if ($invoice_id != null) {

    for ($i = 0; $i < count($arr_pid); $i++) {

      $selectpdt = $pdo->prepare("select * from tbl_product where pid='" . $arr_pid[$i] . "'");
      $selectpdt->execute();

      while ($rowpdt = $selectpdt->fetch(PDO::FETCH_OBJ)) {

        $db_stock[$i] = $rowpdt->stock;



        $rem_qty = $db_stock[$i] - $arr_qty[$i];

        if ($rem_qty < 0) {
          return "Order is not completed";
        } else {
          $update = $pdo->prepare("update tbl_product SET stock='$rem_qty' where pid='" . $arr_pid[$i] . "'");
          $update->execute();
        } //else end here

      }





      $insert = $pdo->prepare("insert into tbl_invoice_details (invoice_id,product_id,product_name,qty,rate,saleprice,order_date) 
values (:invid,:pid,:name,:qty,:rate,:saleprice,:order_date)");

      $insert->bindParam('invid', $id);
      $insert->bindParam(':pid', $arr_pid[$i]);
      $insert->bindParam(':name', $arr_name[$i]);
      $insert->bindParam(':qty', $arr_qty[$i]);
      $insert->bindParam(':rate', $arr_price[$i]);
      $insert->bindParam(':saleprice', $arr_total[$i]);
      $insert->bindParam(':order_date', $txt_orderdate);

      if (!$insert->execute()) {

        print_r($insert->errorInfo());
      }
    } //end 2nd if


  } //end for loop
  header('location:orderlist.php');
} //1st if end


//  var_dump($arr_barcode);



ob_end_flush();




$select = $pdo->prepare(" select * from tbl_taxdis where taxdis_id =1");
$select->execute();
$row = $select->fetch(PDO::FETCH_OBJ);


?>

<style type="text/css">
  .tableFixHead {
    overflow: scroll;
    height: 520px;

  }

  .tableFixHead thead th {
    position: sticky;
    top: 0;
    z-index: 1;
  }

  table {
    border-collapse: collapse;
    width: 100px;
  }

  th,
  td {
    padding: 8px 16px;
  }

  th {
    background: #eee;
  }
</style>







<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <!-- <h1 class="m-0">Point of Sale</h1> -->
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
      <div class="col-lg-12">
        <div class="card card-danger card-outline">
          <div class="card-header">
            <h5 class="m-0"> Edit Order POS</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-8">
                <form action="" method="post" name="">
                  <select class="form-control select2" data-dropdown-css-class="select2-purple" style="width: 100%;">
                    <option> Select OR Search</option><?php echo  fill_product($pdo); ?>

                  </select>
                  </br>
                  <div class="tableFixHead">


                    <table id="producttable" class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th>Product </th>
                          <th>Stock </th>
                          <th>Price </th>
                          <th>QTY </th>
                          <th>Total </th>
                          <th>Del </th>
                        </tr>

                      </thead>




                      <tbody class="details" id="itemtable">

                        <tr data-widget="expandable-table" aria-expanded="false">


                        </tr>
                      </tbody>
                    </table>


                  </div>





              </div>


              <div class="col-md-4">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">SUBTOTAL(₱)</span>
                  </div>
                  <input type="text" class="form-control" name="txtsubtotal" value="<?php echo $subtotal; ?>" id="txtsubtotal_id" readonly>
                  <div class="input-group-append">
                    <span class="input-group-text">₱</span>
                  </div>
                </div>

                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">DISCOUNT(%)</span>
                  </div>
                  <input type="text" class="form-control" name="txtdiscount" id="txtdiscount_p" value="<?php echo $row->discount; ?>">
                  <div class="input-group-append">
                    <span class="input-group-text">%</span>
                  </div>
                </div>

                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">DISCOUNT(₱)</span>
                  </div>
                  <input type="text" class="form-control" id="txtdiscount_n" readonly>
                  <div class="input-group-append">
                    <span class="input-group-text">₱</span>
                  </div>
                </div>



                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">SGST(%)</span>
                  </div>
                  <input type="text" class="form-control" name="txtsgst" id="txtsgst_id_p" value="<?php echo $row->sgst; ?>" readonly>
                  <div class="input-group-append">
                    <span class="input-group-text">%</span>
                  </div>
                </div>


                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">CGST(%)</span>
                  </div>
                  <input type="text" class="form-control" name="txtcgst" id="txtcgst_id_p" value="<?php echo $row->cgst; ?>" readonly>
                  <div class="input-group-append">
                    <span class="input-group-text">%</span>
                  </div>
                </div>



                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">SGST(₱)</span>
                  </div>
                  <input type="text" class="form-control" id="txtsgst_id_n" readonly>
                  <div class="input-group-append">
                    <span class="input-group-text">₱</span>
                  </div>
                </div>


                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">CGST(₱)</span>
                  </div>
                  <input type="text" class="form-control" id="txtcgst_id_n" readonly>
                  <div class="input-group-append">
                    <span class="input-group-text">₱</span>
                  </div>
                </div>

                <hr style="height:3px; border-width:0; color:black; background-color:black">


                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">TOTAL(₱)</span>
                  </div>
                  <input type="text" class="form-control form-control-lg total" name="txttotal" value="<?php echo $total; ?>" id="txttotal" readonly>
                  <div class="input-group-append">
                    <span class="input-group-text">₱</span>
                  </div>
                </div>
                <hr style="height:3px; border-width:0; color:black; background-color:black">


                <div class="icheck-success d-inline">
                  <input type="radio" name="rb" id="radioSuccess1" value="cash" <?php echo ($payment_type == 'cash') ? 'checked' : '' ?>>
                  <label for="radioSuccess1">
                    CASH
                  </label>
                </div>
                <div class="icheck-primary d-inline">
                  <input type="radio" name="rb" id="radioSuccess2" value="card" <?php echo ($payment_type == 'card') ? 'checked' : '' ?>>
                  <label for="radioSuccess2">
                    CARD
                  </label>
                </div>
                <div class="icheck-danger d-inline">
                  <input type="radio" name="rb" id="radioSuccess3" value="check" <?php echo ($payment_type == 'check') ? 'checked' : '' ?>>
                  <label for="radioSuccess3">
                    CHECK
                  </label>
                </div>

                <hr style="height:3px; border-width:0; color:black; background-color:black">



                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">DUE(₱)</span>
                  </div>
                  <input type="text" class="form-control" name="txtdue" value="<?php echo $due; ?>" id="txtdue" readonly>
                  <div class="input-group-append">
                    <span class="input-group-text">₱</span>
                  </div>
                </div>



                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">PAID(₱)</span>
                  </div>
                  <input type="text" class="form-control" name="txtpaid" value="<?php echo $paid; ?>" id="txtpaid" required>
                  <div class="input-group-append">
                    <span class="input-group-text">₱</span>
                  </div>
                </div>

                <hr style="height:3px; border-width:0; color:black; background-color:black">

                <div class="card-footer">


                  <div class="text-center">
                    <button type="submit" class="btn btn-info" name="btnupdateorder">Update Order</button>
                  </div>
                </div>



              </div>


            </div>
          </div>
        </div>

      </div>





    </div>

    </form>


  </div>
  <!-- /.col-md-6 -->
</div>
<!-- /.row -->
</div><!-- /.container-fluid -->
</div>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->


<?php

include_once "footer.php";


?>

<script>
  //Initialize Select2 Elements
  $('.select2').select2()

  //Initialize Select2 Elements
  $('.select2bs4').select2({
    theme: 'bootstrap4'
  })




  var productarr = [];



  $.ajax({
    url: "getorderproduct.php",
    method: "get",
    dataType: "json",
    data: {
      id: <?php echo $_GET['id'] ?>
    },
    success: function(data) {

      //alert("pID");

      // console.log(data);
      $.each(data, function(key, data) {

        if (jQuery.inArray(data["product_id"], productarr) !== -1) {

          var actualqty = parseInt($('#qty_id' + data["product_id"]).val()) + 1;
          $('#qty_id' + data["product_id"].val(actualqty));



          var saleprice = parseInt(actualqty) * data["saleprice"];

          $('#saleprice_id' + data["product_id"]).html(saleprice);
          $('#saleprice_idd' + data["product_id"]).val(saleprice);

          // $("#txtbarcode_id").val("");

          calculate(0, 0);

        } else {

          //revised version

          addrow(data["product_id"], data["product_name"], data["qty"], data["rate"], data["saleprice"], data["stock"]);

          productarr.push(data["product_id"]);

          // $("#txtbarcode_id").val("");

          function addrow(product_id, product_name, qty, rate, saleprice, stock, barcode) {


            var tr = '<tr>' +

    

              '<td style="text-align: left; vertical-align: middle; font-size: 17px;"><class="form-control product_c" name="product_arr[]" <span class="badge badge-dark">' + product_name + '</span><input type="hidden" class="form-control pid" name="pid_arr[]" value="' + product_id + '"><input type="hidden" class="form-control product" name="product_arr[]" value="' + product_name + '"></td>' +

              '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
              '<span class="badge badge-primary stocklbl" name="stock_arr[]"  id="stock_id' + product_id + '">' + stock + '</span>' +
              '<input type="hidden" class="form-control stock_c" name="stock_c_arr[]" id="stock_idd' + product_id + '" value="' + stock + '"></td>' +

              '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
              '<span class="badge badge-warning price" name="price_arr[]"  id="price_id' + product_id + '">' + saleprice + '</span>' +
              '<input type="hidden" class="form-control stock_c" name="stock_c[]" id="stock_idd' + product_id + '" value="' + stock + '"></td>' +

              '<td><input type="text" class="form-control qty" name="quantity_arr[]" id="qty_id' + product_id + '" value="' + qty + '" size="1"></td>' +
              '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
              '<span class="badge badge-success totalamt" name="netamt_arr[]"  id="saleprice_id' + product_id + '">' + saleprice * qty + '</span>' +
              '<input type="hidden" class="form-control saleprice" name="saleprice_arr[]" id="saleprice_idd' + product_id + '" value="' + saleprice * qty + '"></td>' +


              '<td><center><button type="button" name="remove"  class=" btn btn-danger btn-sm btnremove" data-id="' + product_id + '"><span class="fas fa-trash"></span></button></center></td>' +

              '</tr>';

            $('.details').append(tr);

            calculate(0, 0);

          } //end f function addrow

        }
      }); //end function each

      $("#txtbarcode_id").val("");

    } //end pf success function

  }) //end of ajax request







  $(function() {

    $('#txtbarcode_id').on('change', function() {

      var barcode = $("#txtbarcode_id").val();

      $.ajax({
        url: "getproduct.php",
        method: "get",
        dataType: "json",
        data: {
          id: barcode
        },
        success: function(data) {

          // alert("pID");

          // console.log(data);

          if (jQuery.inArray(data["pid"], productarr) !== -1) {

            var actualqty = parseInt($('#qty_id' + data["pid"]).val()) + 1;
            $('#qty_id' + data["pid"].val(actualqty));



            var saleprice = parseInt(actualqty) * data["saleprice"];

            $('#saleprice_id' + data["pid"]).html(saleprice);
            $('#saleprice_idd' + data["pid"]).val(saleprice);

            // $("#txtbarcode_id").val("");

            calculate(0, 0);
            $("#txtpaid").val("");
            $("#txtdue").val("");

          } else {

            //revised version

            addrow(data["pid"], data["product"], data["saleprice"], data["stock"]);

            productarr.push(data["pid"]);

            // $("#txtbarcode_id").val("");

            function addrow(pid, product, saleprice, stock, ) {


              var tr = '<tr>' +

  

                '<td style="text-align: left; vertical-align: middle; font-size: 17px;"><class="form-control product_c" name="product_arr[]" <span class="badge badge-dark">' + product + '</span><input type="hidden" class="form-control pid" name="pid_arr[]" value="' + product + '"><input type="hidden" class="form-control product" name="product_arr[]" value="' + pid + '"></td>' +

                '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
                '<span class="badge badge-primary stocklbl" name="stock_arr[]"  id="stock_id' + pid + '">' + stock + '</span>' +
                '<input type="hidden" class="form-control stock_c" name="stock_c_arr[]" id="stock_idd' + pid + '" value="' + stock + '"></td>' +

                '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
                '<span class="badge badge-warning price" name="price_arr[]"  id="price_id' + pid + '">' + saleprice + '</span>' +
                '<input type="hidden" class="form-control stock_c" name="stock_c[]" id="stock_idd' + pid + '" value="' + stock + '"></td>' +

                '<td><input type="text" class="form-control qty" name="quantity_arr[]" id="qty_id' + pid + '" value="1" size="1"></td>' +
                '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
                '<span class="badge badge-success totalamt" name="netamt_arr[]"  id="saleprice_id' + pid + '">' + saleprice + '</span>' +
                '<input type="hidden" class="form-control saleprice" name="saleprice_arr[]" id="saleprice_idd' + pid + '" value="' + saleprice + '"></td>' +


                '<td><center><button type="button" name="remove"  class=" btn btn-danger btn-sm btnremove" data-id="' + pid + '"><span class="fas fa-trash"></span></button></center></td>' +

                '</tr>';

              $('.details').append(tr);

              calculate(0, 0);
              $("#txtpaid").val("");
              $("#txtdue").val("");

            } //end f function addrow

          }

          $("#txtbarcode_id").val("");

        } //end pf success function

      }) //end of ajax request

    }) // end of onchange function

  }); // end of main function


  var productarr = [];

  $(function() {

    $('.select2').on('change', function() {

      var productid = $(".select2").val();

      $.ajax({
        url: "getproduct.php",
        method: "get",
        dataType: "json",
        data: {
          id: productid
        },
        success: function(data) {

          // alert("pID");

          // console.log(data);

          if (jQuery.inArray(data["pid"], productarr) !== -1) {

            var actualqty = parseInt($('#qty_id' + data["pid"]).val()) + 1;
            $('#qty_id' + data["pid"].val(actualqty));



            var saleprice = parseInt(actualqty) * data["saleprice"];

            $('#saleprice_id' + data["pid"]).html(saleprice);
            $('#saleprice_idd' + data["pid"]).val(saleprice);

            // $("#txtbarcode_id").val("");

            calculate(0, 0);
            $("#txtpaid").val("");
            $("#txtdue").val("");

          } else {

            //revised version

            addrow(data["pid"], data["product"], data["saleprice"], data["stock"]);

            productarr.push(data["pid"]);

            // $("#txtbarcode_id").val("");

            function addrow(pid, product, saleprice, stock, barcode) {


              var tr = '<tr>' +

              

                '<td style="text-align: left; vertical-align: middle; font-size: 17px;"><class="form-control product_c" name="product_arr[]" <span class="badge badge-dark">' + product + '</span><input type="hidden" class="form-control pid" name="pid_arr[]" value="' + product + '"><input type="hidden" class="form-control product" name="product_arr[]" value="' + pid + '"></td>' +

                '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
                '<span class="badge badge-primary stocklbl" name="stock_arr[]"  id="stock_id' + pid + '">' + stock + '</span>' +
                '<input type="hidden" class="form-control stock_c" name="stock_c_arr[]" id="stock_idd' + pid + '" value="' + stock + '"></td>' +

                '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
                '<span class="badge badge-warning price" name="price_arr[]"  id="price_id' + pid + '">' + saleprice + '</span>' +
                '<input type="hidden" class="form-control stock_c" name="stock_c[]" id="stock_idd' + pid + '" value="' + stock + '"></td>' +

                '<td><input type="text" class="form-control qty" name="quantity_arr[]" id="qty_id' + pid + '" value="1" size="1"></td>' +
                '<td style="text-align:left; vertical-align:middle; font-size:17px;">' +
                '<span class="badge badge-success totalamt" name="netamt_arr[]"  id="saleprice_id' + pid + '">' + saleprice + '</span>' +
                '<input type="hidden" class="form-control saleprice" name="saleprice_arr[]" id="saleprice_idd' + pid + '" value="' + saleprice + '"></td>' +

                // '<td style="text-align: left; vertical-align: middle;"><center><name="remove" class="btnremove" data-id="'+pid+'"><span class="fas fa-trash" style="color:red"></span></center></td>'+
                '<td><center><button type="button" name="remove"  class=" btn btn-danger btn-sm btnremove" data-id="' + pid + '"><span class="fas fa-trash"></span></button></center></td>' +

                '</tr>';

              $('.details').append(tr);

              calculate(0, 0);
              $("#txtpaid").val("");
              $("#txtdue").val("");

            } //end f function addrow

          }

          $("#txtbarcode_id").val("");

        } //end pf success function

      }) //end of ajax request

    }) // end of onchange function

  }); // end of main function











  $("#itemtable").delegate(".qty", "keyup change", function() {

    var quantity = $(this);
    var tr = $(this).parent().parent();

    if ((quantity.val() - 0) > (tr.find(".stock_c").val() - 0)) {

      swal.fire("WARNING!", "SORRY! This Much of Quantity Is Not Available", "warning");
      quantity.val(1);

      tr.find(".totalamt").text(quantity.val() * tr.find(".price").text());

      tr.find(".saleprice").val(quantity.val() * tr.find(".price").text());
      calculate(0, 0);
      $("#txtpaid").val("");
      $("#txtdue").val("");
    } else {


      tr.find(".totalamt").text(quantity.val() * tr.find(".price").text());

      tr.find(".saleprice").val(quantity.val() * tr.find(".price").text());

      calculate(0, 0);
      $("#txtpaid").val("");
      $("#txtdue").val("");
    }



  });


  function calculate(dis, paid) {

    var subtotal = 0;
    var discount = dis;
    var sgst = 0;
    var csgt = 0;
    var total = 0;
    var paid_amt = paid;
    var due = 0;

    $(".saleprice").each(function() {

      subtotal = subtotal + ($(this).val() * 1);

    });

    $("#txtsubtotal_id").val(subtotal.toFixed(2));

    sgst = parseFloat($("#txtsgst_id_p").val());

    cgst = parseFloat($("#txtcgst_id_p").val());

    discount = parseFloat($("#txtdiscount_p").val());

    sgst = sgst / 100;
    sgst = sgst * subtotal;

    cgst = cgst / 100;
    cgst = cgst * subtotal;

    discount = discount / 100;
    discount = discount * subtotal;

    $("#txtsgst_id_n").val(sgst.toFixed(2));

    $("#txtcgst_id_n").val(cgst.toFixed(2));

    $("#txtdiscount_n").val(discount.toFixed(2));

    total = sgst + cgst + subtotal - discount;
    due = total - paid_amt;

    $("#txttotal").val(total.toFixed(2));

    paid_db = parseFloat($("#txtpaid").val());
    due_db = paid_db - total;

    $("#txtdue").val(due_db.toFixed(2));


  }

  $("#txtdiscount_p").keyup(function() {

    var discount = $(this).val();

    calculate(discount, 0);

  });

  $("#txtpaid").keyup(function() {

    var paid = $(this).val();
    var discount = $("#txtdiscount_p").val();
    calculate(discount, paid);


  });


  $(document).on('click', '.btnremove', function() {

    var removed = $(this).attr("data-id");
    productarr = jQuery.grep(productarr, function(value) {

      return value != removed;
      calculate(0, 0);
      $("#txtpaid").val("");
      $("#txtdue").val("");
    });

    $(this).closest("tr").remove();
    calculate(0, 0);
    $("#txtpaid").val("");
    $("#txtdue").val("");
  });
</script>


<script>
  Swal.fire({
    icon: '<?php echo $_SESSION['status_code']; ?>',
    title: '<?php echo $_SESSION['status']; ?>'
  });
</script>