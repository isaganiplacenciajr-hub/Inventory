<?php
include_once 'connectdb.php';
session_start();
include_once "header.php";

// Get status filter from URL parameter
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$validStatuses = ['Complete', 'Pending'];
if (!in_array($filterStatus, $validStatuses)) {
  $filterStatus = '';
}

// Get date filter from URL parameter
$filterDate = isset($_GET['date']) ? trim($_GET['date']) : '';
if ($filterDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
  $filterDate = '';
}

// Get creator role filter
$filterRole = isset($_GET['role']) ? trim($_GET['role']) : '';
$validRoles = ['Admin','User'];
if (!in_array($filterRole, $validRoles)) {
  $filterRole = '';
}

// Get branch filter from URL or session branch (match user version and keep a branch for logged admins)
$filterBranch = isset($_GET['branch']) ? trim($_GET['branch']) : trim($_SESSION['branch'] ?? '');

// Accept an explicit 'all' switch to disable filtering
$showAllBranches = ($filterBranch === 'all');

// Recent transactions flag (last 7 days)
$filterRecent = isset($_GET['recent']) && $_GET['recent'] == '1';

// Check branch column existence
$branchColumnExists = false;
try {
  $colInfo = $pdo->prepare("SHOW COLUMNS FROM tbl_invoice LIKE 'branch'");
  $colInfo->execute();
  $branchColumnExists = (bool) $colInfo->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $branchColumnExists = false;
}

// Display branch badge in heading if filtering is active
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Order List
            <?php if ($filterBranch && $filterBranch !== 'all'): ?>
              <span class="badge badge-secondary" style="margin-left: 10px;"><?php echo htmlspecialchars($filterBranch); ?></span>
            <?php endif; ?>
            <?php if ($filterStatus): ?>
              <span class="badge badge-primary" style="margin-left: 10px;"><?php echo htmlspecialchars($filterStatus); ?></span>
            <?php endif; ?>
            <?php if ($filterDate): ?>
              <span class="badge badge-info" style="margin-left: 5px;"><?php echo htmlspecialchars($filterDate); ?></span>
            <?php endif; ?>
          </h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <?php if ($filterStatus || $filterDate || $filterRole): ?>
              <li class="breadcrumb-item"><a href="orderlist.php">All Orders</a></li>
              <li class="breadcrumb-item active"><?php echo htmlspecialchars($filterStatus); ?><?php if ($filterDate): ?> - <?php echo htmlspecialchars($filterDate); ?><?php endif; ?><?php if ($filterRole): ?> - <?php echo htmlspecialchars($filterRole); ?><?php endif; ?></li>
            <?php endif; ?>
          </ol>
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
              <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="m-0">Orders <?php if ($filterStatus): ?> - <?php echo htmlspecialchars($filterStatus); ?><?php endif; ?><?php if ($filterDate): ?> on <?php echo htmlspecialchars($filterDate); ?><?php endif; ?></h5>
                <form method="get" class="form-inline">
                  <div class="input-group input-group-sm">
                    <select name="branch" class="form-control">
                      <option value="all" <?php echo $filterBranch === 'all' ? 'selected' : ''; ?>>All Branches</option>
                      <?php
                      if ($branchColumnExists) {
                        $stmtBranch = $pdo->query("SELECT DISTINCT branch FROM tbl_invoice WHERE branch IS NOT NULL AND branch <> '' ORDER BY branch");
                        $branches = $stmtBranch->fetchAll(PDO::FETCH_COLUMN);
                      } else {
                        $branches = [];
                      }
                      if (empty($branches)) {
                        $branches = [$activeBranch ?? 'Unknown'];
                      }
                      foreach ($branches as $br) {
                        $branchName = trim($br);
                        if ($branchName === '') continue;
                        echo '<option value="' . htmlspecialchars($branchName) . '"' . ($filterBranch === $branchName ? ' selected' : '') . '>' . htmlspecialchars($branchName) . '</option>';
                      }
                      ?>
                    </select>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($filterDate); ?>">
                    <?php if ($filterRole): ?><input type="hidden" name="role" value="<?php echo htmlspecialchars($filterRole); ?>"><?php endif; ?>
                    <?php if ($filterRecent): ?><input type="hidden" name="recent" value="1"><?php endif; ?>
                    <div class="input-group-append">
                      <button class="btn btn-secondary" type="submit"><i class="fas fa-filter"></i></button>
                    </div>
                  </div>
                </form>
                <?php if ($filterBranch || $filterStatus || $filterDate || $filterRole || $filterRecent): ?>
                  <a href="orderlist.php" class="btn btn-sm btn-secondary ml-2"><i class="fas fa-times"></i> Clear Filters</a>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-body">

              <table class="table table-striped table-hover" id="table_orderlist">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Branch</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Products</th>
                    <th>Total QTY</th>
                    <th>Items</th>
                    <th>Fee</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Payment Type</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Fetch invoices - one row per invoice, no duplicates
                  $query = "
                  SELECT 
                    inv.invoice_id,
                    " . ($branchColumnExists ? "inv.branch,\n                    " : "") . "
                    inv.order_date,
                    inv.payment_type,
                    inv.total,
                    inv.customer_name,
                    inv.customer_contact,
                    inv.customer_address,
                    inv.status,
                    COALESCE(NULLIF(inv.created_by_role, ''), NULLIF(u.role, ''), 'User') as creator_role
                  FROM tbl_invoice inv
                  LEFT JOIN tbl_user u ON inv.created_by = u.userid
                  WHERE 1=1";
  
  $params = [];
  
  // Add status filter if applied
  if ($filterStatus) {
    $query .= " AND inv.status = :status";
    $params[':status'] = $filterStatus;
  }
  
  // Add date filter if applied
  if ($filterDate) {
    $query .= " AND DATE(inv.order_date) = :date";
    $params[':date'] = $filterDate;
  }

  // Add role filter if applied
  if ($filterRole) {
    $query .= " AND COALESCE(u.role,'User') = :role";
    $params[':role'] = $filterRole;
  }

  // Add branch filter if applied (from branch settings or URL branch parameter)
  if ($branchColumnExists && !$showAllBranches && !empty($filterBranch)) {
    $query .= " AND inv.branch = :branch";
    $params[':branch'] = $filterBranch;
  }
  
  $query .= " ORDER BY inv.invoice_id DESC";
  
  $select = $pdo->prepare($query);
  $select->execute($params);

// For each invoice, get the products
$invoices = $select->fetchAll(PDO::FETCH_OBJ);

foreach ($invoices as $invoice) {
  // Get all products for this invoice
  $detailsStmt = $pdo->prepare("
    SELECT 
      CONCAT(product_name, ' (Qty: ', qty, ')') as product_info
    FROM tbl_invoice_details 
    WHERE invoice_id = :invoice_id
  ");
  $detailsStmt->execute([':invoice_id' => $invoice->invoice_id]);
  $details = $detailsStmt->fetchAll(PDO::FETCH_COLUMN);
  
  // Get item count and total qty
  $statsStmt = $pdo->prepare("
    SELECT COUNT(*) as item_count, COALESCE(SUM(qty), 0) as total_qty, COALESCE(SUM(addfee), 0) as total_fee
    FROM tbl_invoice_details 
    WHERE invoice_id = :invoice_id
  ");
  $statsStmt->execute([':invoice_id' => $invoice->invoice_id]);
  $stats = $statsStmt->fetch(PDO::FETCH_OBJ);
  
  $products = implode(', ', $details);
  $item_count = $stats->item_count;
  $total_qty = $stats->total_qty;
  $total_fee = $stats->total_fee;
  
  $invoiceBranch = trim((string)($invoice->branch ?? ''));
  if ($invoiceBranch === '') {
      $invoiceBranch = 'Unknown';
  }
  echo '<tr>';
  echo '<td>' . htmlspecialchars($invoice->invoice_id) . '</td>';
  echo '<td>' . htmlspecialchars($invoiceBranch) . '</td>';
  echo '<td>' . htmlspecialchars($invoice->customer_name ?? 'N/A') . '</td>';
  echo '<td>' . htmlspecialchars($invoice->customer_contact ?? 'N/A') . '</td>';
  echo '<td>' . htmlspecialchars($invoice->customer_address ?? 'N/A') . '</td>';
  // Show all products in this order
  echo '<td>' . htmlspecialchars($products ?: 'N/A') . '</td>';
  // Show total quantity of items
  echo '<td>' . htmlspecialchars($total_qty) . '</td>';
  echo '<td style="font-size: 10px; font-weight: bold; text-align: center;"><span class="badge badge-primary" style="font-size: 10px; padding: 10px 12px;">' . htmlspecialchars($item_count) . ' item(s)</span></td>';
  echo '<td><strong>₱' . number_format($total_fee, 2) . '</strong></td>';

  // total now from tbl_invoice, not details
  echo '<td><strong>₱' . number_format($invoice->total, 2) . '</strong></td>';

  echo '<td>' . htmlspecialchars($invoice->order_date) . '</td>';

  if ($invoice->payment_type == "cash") {
    echo '<td><span class="badge badge-warning">Cash</span></td>';
  } elseif ($invoice->payment_type == "cod") {
    echo '<td><span class="badge badge-info">COD</span></td>';
  } elseif ($invoice->payment_type == "card") {
    echo '<td><span class="badge badge-success">Card</span></td>';
  } else {
    echo '<td><span class="badge badge-danger">' . htmlspecialchars($invoice->payment_type) . '</span></td>';
  }

  // Creator role column
  $creatorRole = isset($invoice->creator_role) ? $invoice->creator_role : 'User';
  $roleClass = ($creatorRole === 'Admin') ? 'badge-danger' : 'badge-secondary';
  echo '<td><span class="badge ' . $roleClass . '">' . htmlspecialchars($creatorRole) . '</span></td>';

  // Display status badge
  $statusBadge = 'badge-secondary';
  if ($invoice->status === 'Complete') {
    $statusBadge = 'badge-success';
  } elseif ($invoice->status === 'Pending') {
    $statusBadge = 'badge-warning';
  }
  echo '<td><span class="badge ' . $statusBadge . '">' . htmlspecialchars($invoice->status ?? 'Unknown') . '</span></td>';

  // Action buttons
  echo '<td>';
  echo '<div class="btn-group btn-group-sm" role="group">';
  
  // Print button
  echo '<button class="btn btn-info btnprint" data-invoice-id="' . $invoice->invoice_id . '" type="button" data-toggle="tooltip" title="Print Bill">';
  echo '<span class="fa fa-print"></span>';
  echo '</button>';
  
  // Mark Complete button (only for Pending orders)
  if ($invoice->status === 'Pending') {
    echo '<button class="btn btn-success btn-mark-complete" data-invoice-id="' . $invoice->invoice_id . '" type="button" data-toggle="tooltip" title="Mark as Complete">';
    echo '<span class="fa fa-check"></span>';
    echo '</button>';
  }
  
  // Delete button
  echo '<button class="btn btn-danger btndelete" data-invoice-id="' . $invoice->invoice_id . '" data-toggle="tooltip" title="Delete Order">';
  echo '<span class="fa fa-trash"></span>';
  echo '</button>';
  
  echo '</div>';
  echo '</td>';
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
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include_once "footer.php"; ?>

<script>
  $(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();

    // Print button - load receipt in modal
    $('.btnprint').click(function() {
      var invoiceId = $(this).data('invoice-id');
      
      $('#receiptContent').load('get_receipt.php?id=' + invoiceId, function() {
        $('#receiptModal').modal('show');
      });
    });

    // Print receipt from modal
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

    $('.btndelete').click(function() {
      var tdh = $(this);
      var id = $(this).data('invoice-id');

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

    // Mark order as complete (for Pending COD orders)
    $('.btn-mark-complete').click(function() {
      var btn = $(this);
      var invoiceId = $(this).data('invoice-id');
      
      Swal.fire({
        title: "Mark as Complete?",
        text: "This will move the order to the Sales Report.",
        icon: "info",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, mark as complete!"
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: 'update_order_status.php',
            type: "post",
            dataType: 'json',
            data: { invoice_id: invoiceId, status: 'Complete' },
            success: function(response) {
              if (response.success) {
                // Update the status badge
                btn.closest('tr').find('.badge').each(function() {
                  if ($(this).text() === 'Pending') {
                    $(this).removeClass('badge-warning').addClass('badge-success');
                    $(this).text('Complete');
                  }
                });
                // Remove the mark complete button
                btn.remove();
                Swal.fire("Success!", "Order marked as complete and added to Sales Report", "success");
              } else {
                Swal.fire("Error!", response.message || "Failed to update order", "error");
              }
            },
            error: function(xhr, status, error) {
              Swal.fire("Error!", "Failed to update order: " + error, "error");
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
