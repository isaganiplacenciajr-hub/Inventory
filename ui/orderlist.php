<?php
include_once 'connectdb.php';
session_start();
include_once "header.php";
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Order List</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right"></ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">

          <div class="card card-primary card-outline">
            <div class="card-header">
              <h5 class="m-0">Orders</h5>
            </div>
            <div class="card-body">

              <table class="table table-striped table-hover" id="table_orderlist">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>QTY</th>
                    <th>Service Type</th>
                    <th>Add Fee</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Payment Type</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Join invoice and details
                  $select = $pdo->prepare("
  SELECT 
    inv.invoice_id,
    inv.order_date,
    inv.payment_type,
    inv.total,               -- ✅ total now comes from tbl_invoice
    det.product_name,
    det.qty,
    det.servicetype,
    det.addfee
  FROM tbl_invoice inv
  INNER JOIN tbl_invoice_details det ON inv.invoice_id = det.invoice_id
  ORDER BY inv.invoice_id DESC
");
$select->execute();

while ($row = $select->fetch(PDO::FETCH_OBJ)) {
  echo '<tr>';
  echo '<td>' . htmlspecialchars($row->invoice_id) . '</td>';
  echo '<td>' . htmlspecialchars($row->product_name) . '</td>';
  echo '<td>' . htmlspecialchars($row->qty) . '</td>';
  echo '<td>' . htmlspecialchars($row->servicetype) . '</td>';
  echo '<td>₱' . number_format($row->addfee, 2) . '</td>';

  // ✅ total now from tbl_invoice, not details
  echo '<td><strong>₱' . number_format($row->total, 2) . '</strong></td>';

  echo '<td>' . htmlspecialchars($row->order_date) . '</td>';

  if ($row->payment_type == "cash") {
    echo '<td><span class="badge badge-warning">Cash</span></td>';
  } elseif ($row->payment_type == "card") {
    echo '<td><span class="badge badge-success">Card</span></td>';
  } else {
    echo '<td><span class="badge badge-danger">' . htmlspecialchars($row->payment_type) . '</span></td>';
  }

  echo '
    <td>
      <div class="btn-group">
        <a href="printbill.php?id=' . $row->invoice_id . '" class="btn btn-warning" role="button">
          <span class="fa fa-print" style="color:#fff" data-toggle="tooltip" title="Print Bill"></span>
        </a>
    
        <button id="' . $row->invoice_id . '" class="btn btn-danger btndelete">
          <span class="fa fa-trash" style="color:#fff" data-toggle="tooltip" title="Delete Order"></span>
        </button>
      </div>
    </td>
  ';
  echo '</tr>';
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

<?php include_once "footer.php"; ?>

<script>
  $(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();

    $('.btndelete').click(function() {
      var tdh = $(this);
      var id = $(this).attr("id");

      Swal.fire({
        title: "Do you want to delete this order?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: 'orderdelete.php',
            type: "post",
            data: { pidd: id },
            success: function(data) {
              try {
                var response = typeof data === 'string' ? JSON.parse(data) : data;
                
                if (response.success) {
                  tdh.parents('tr').hide();
                  Swal.fire("Archived!", "Order has been moved to archive", "success");
                } else {
                  Swal.fire("Error!", response.message || "Failed to archive order", "error");
                }
              } catch (e) {
                Swal.fire("Error!", "Invalid response from server", "error");
              }
            },
            error: function(xhr, status, error) {
              Swal.fire("Error!", "Failed to delete order: " + error, "error");
            }
          });
        }
      });
    });

    $('#table_orderlist').DataTable({
      "order": [[0, "desc"]]
    });
  });
</script>
