<?php
session_start();
include_once 'connectdb.php';
include_once 'utils.php';

// Ensure user is logged in
if (!isset($_SESSION['userid']) || $_SESSION['role'] === 'Admin') {
    header('location:../index.php');
    exit;
}

include_once "headeruser.php";
include_once __DIR__ . '/../config/branch.php';
if (empty($_SESSION['branch']) && !empty($activeBranch)) {
    $_SESSION['branch'] = $activeBranch;
}

// Get status filter from URL parameter
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$validStatuses = ['Complete', 'Pending', 'Rejected'];
if (!in_array($filterStatus, $validStatuses)) {
  $filterStatus = '';
}

// Get date filter from URL parameter
$filterDate = isset($_GET['date']) ? trim($_GET['date']) : '';
if ($filterDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
  $filterDate = '';
}

// Branch filter from URL or session branch (match admin behavior)
$filterBranch = isset($_GET['branch']) ? trim($_GET['branch']) : trim($_SESSION['branch'] ?? '');
$showAllBranches = ($filterBranch === 'all');

// Check branch column exists to avoid fatal query failures
$branchColumnExists = false;
try {
    $colInfo = $pdo->prepare("SHOW COLUMNS FROM tbl_invoice LIKE 'branch'");
    $colInfo->execute();
    $branchColumnExists = (bool) $colInfo->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $branchColumnExists = false;
}

// Check created_by_id column exists (new schema stores author in created_by_id)
$createdByIdExists = false;
try {
    $colInfo2 = $pdo->prepare("SHOW COLUMNS FROM tbl_invoice LIKE 'created_by_id'");
    $colInfo2->execute();
    $createdByIdExists = (bool) $colInfo2->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $createdByIdExists = false;
}

$createdByCondition = $createdByIdExists
    ? "(inv.created_by = :userid OR inv.created_by_id = :userid)"
    : "inv.created_by = :userid";

// recent transactions flag (last 7 days)
$filterRecent = isset($_GET['recent']) && $_GET['recent'] == '1';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <section class="content pt-3">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-lg-12">
          <div class="card card-primary card-outline">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="m-0">
            Orders
            <?php if ($filterBranch && $filterBranch !== 'all'): ?> - <?php echo htmlspecialchars($filterBranch); ?><?php endif; ?>
            <?php if ($filterRecent): ?> - Recent Transactions<?php endif; ?>
            <?php if ($filterStatus): ?> - <?php echo htmlspecialchars($filterStatus); ?><?php endif; ?>
            <?php if ($filterDate): ?> on <?php echo htmlspecialchars($filterDate); ?><?php endif; ?>
          </h5>
          <form method="get" class="form-inline">
            <div class="input-group input-group-sm">
              <select name="branch" class="form-control">
                <option value="all" <?php echo $filterBranch === 'all' ? 'selected' : ''; ?>>All Branches</option>
                <?php
                  $branchOptions = [];
                  if ($branchColumnExists) {
                    $stmt = $pdo->query("SELECT DISTINCT branch FROM tbl_invoice WHERE branch IS NOT NULL AND branch <> '' ORDER BY branch");
                    $branchOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                  }
                  if (empty($branchOptions)) {
                    $branchOptions = [$activeBranch ?? ''];
                  }
                  foreach ($branchOptions as $br) {
                    $brValue = trim($br);
                    if ($brValue === '' || strtolower($brValue) === 'unknown') continue;
                    echo '<option value="' . htmlspecialchars($brValue) . '"' . ($filterBranch === $brValue ? ' selected' : '') . '>' . htmlspecialchars($brValue) . '</option>';
                  }
                ?>
              </select>
              <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>">
              <input type="hidden" name="date" value="<?php echo htmlspecialchars($filterDate); ?>">
              <?php if ($filterRecent): ?><input type="hidden" name="recent" value="1"><?php endif; ?>
              <div class="input-group-append">
                <button class="btn btn-secondary" type="submit"><i class="fas fa-filter"></i></button>
              </div>
            </div>
          </form>
        </div>
        <?php if ($filterBranch || $filterStatus || $filterDate || $filterRecent): ?>
        <div style="float: right;">
          <a href="userorderlist.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-times"></i> Clear Filter
          </a>
        </div>
        <?php endif; ?>

      </div>
      <div class="card-body">
        <div class="table-responsive">
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
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Fetch invoices for current user only
                  try {
                    $query = "
                    SELECT 
                      inv.invoice_id,
                      " . ($branchColumnExists ? "COALESCE(NULLIF(inv.branch, ''), 'Unknown') AS branch,\n                      " : "'' AS branch,\n                      ") . "
                      inv.order_date,
                      inv.payment_type,
                      inv.total,
                      inv.customer_name,
                      inv.customer_contact,
                      inv.customer_address,
                      COALESCE(inv.status, 'Complete') as status
                    FROM tbl_invoice inv
                    WHERE $createdByCondition";
                    
                    $params = [':userid' => $_SESSION['userid']];
                    
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

                    // Add branch filter if applied
                    if ($branchColumnExists && !$showAllBranches && !empty($filterBranch)) {
                      $query .= " AND inv.branch = :branch";
                      $params[':branch'] = $filterBranch;
                    }

                    // Add recent filter if applied (last 7 days)
                    if ($filterRecent) {
                      $query .= " AND DATE(inv.order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    }
                    
                    $query .= " ORDER BY inv.invoice_id DESC";
                    
                    $select = $pdo->prepare($query);
                    $select->execute($params);

                    // For each invoice, get the products
                    $invoices = $select->fetchAll(PDO::FETCH_OBJ);

                    if (count($invoices) === 0) {
                      echo '<tr><td colspan="13" class="text-center text-muted">No orders found</td></tr>';
                    } else {
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

  // Get item count, total qty, and total fee
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
  if ($invoiceBranch === '' || strtolower($invoiceBranch) === 'unknown') {
      $invoiceBranch = trim($filterBranch ?: ($_SESSION['branch'] ?? ''));
      if ($invoiceBranch === '') {
          $invoiceBranch = 'Unknown';
      }
  }
  echo '<tr>';
  echo '<td>' . htmlspecialchars($invoice->invoice_id) . '</td>';
  echo '<td>' . htmlspecialchars($invoiceBranch) . '</td>';
  echo '<td>' . htmlspecialchars($invoice->customer_name ?? 'N/A') . '</td>';
  echo '<td>' . htmlspecialchars($invoice->customer_contact ?? 'N/A') . '</td>';
  echo '<td>' . htmlspecialchars($invoice->customer_address ?? 'N/A') . '</td>';
  echo '<td>' . htmlspecialchars($products ?: 'N/A') . '</td>';
  echo '<td>' . htmlspecialchars($total_qty) . '</td>';
  echo '<td style="font-size: 10px; font-weight: bold; text-align: center;"><span class="badge badge-primary" style="font-size: 10px; padding: 10px 12px;">' . htmlspecialchars($item_count) . ' item(s)</span></td>';
  echo '<td><strong>₱' . number_format($total_fee, 2) . '</strong></td>';
  echo '<td><strong>₱' . number_format($invoice->total, 2) . '</strong></td>';
  // format date/time like admin
  $formatted = htmlspecialchars(date('Y-m-d H:i:s', strtotime($invoice->order_date)));
  echo '<td>' . $formatted . '</td>';

  if ($invoice->payment_type == "cash") {
    echo '<td><span class="badge badge-warning">Cash</span></td>';
  } elseif ($invoice->payment_type == "cod") {
    echo '<td><span class="badge badge-info">COD</span></td>';
  } elseif ($invoice->payment_type == "card") {
    echo '<td><span class="badge badge-success">Card</span></td>';
  } else {
    echo '<td><span class="badge badge-danger">' . htmlspecialchars($invoice->payment_type) . '</span></td>';
  }

  // Normalize status and payment type for comparisons
  $status = isset($invoice->status) ? trim($invoice->status) : 'Complete';
  $statusLower = strtolower($status);
  $paymentType = isset($invoice->payment_type) ? trim($invoice->payment_type) : '';
  $paymentTypeLower = strtolower($paymentType);

  // Display status badge
  $statusBadge = 'badge-secondary';
  if ($statusLower === 'complete') {
    $statusBadge = 'badge-success';
  } elseif ($statusLower === 'pending') {
    $statusBadge = 'badge-warning';
  } elseif ($statusLower === 'rejected') {
    $statusBadge = 'badge-danger';
  }
  echo '<td><span class="badge ' . $statusBadge . '">' . htmlspecialchars($status) . '</span></td>';

  // Action buttons
  echo '<td>';
  echo '<button class="btn btn-info btnprint" data-invoice-id="' . $invoice->invoice_id . '" type="button" data-toggle="tooltip" title="Print Bill">';
  echo '<span class="fa fa-print"></span>';
  echo '</button>';

  // Mark as Done logic (Pending only, COD permission enforced; non-COD pending also allowed)
  $isPending = ($statusLower === 'pending');
  $isCOD = ($paymentTypeLower === 'cod');

  if ($isPending) {
    $canMark = true;
    if ($isCOD) {
      // COD users require self-completion permission and admin consent
      $userStmt = $pdo->prepare("SELECT cod_self_completion_permission, cod_permission_expiry, admin_consent FROM tbl_user WHERE userid = ?");
      $userStmt->execute([$_SESSION['userid']]);
      $userPerm = $userStmt->fetch(PDO::FETCH_ASSOC);
      $canMark = false;
      if ($userPerm && $userPerm['cod_self_completion_permission'] && $userPerm['admin_consent']) {
        if (empty($userPerm['cod_permission_expiry']) || strtotime($userPerm['cod_permission_expiry']) > time()) {
          $canMark = true;
        }
      }
    }

    if ($canMark) {
      echo ' <button class="btn btn-success btn-sm btn-mark-done ml-1" style="padding: 6px 10px; font-size: 13px; display:inline-flex; align-items:center; gap:5px;" data-invoice-id="' . $invoice->invoice_id . '" data-toggle="tooltip" title="Mark as Done"><i class="fas fa-check"></i>Done</button>';
    } else {
      echo ' <button class="btn btn-secondary btn-sm ml-1" style="padding: 6px 10px; font-size: 13px; display:inline-flex; align-items:center; gap:5px;" disabled title="COD completion not enabled or expired"><i class="fas fa-lock"></i>Locked</button>';
    }
  }
  echo '</td>';
  echo '</tr>';
}
                    }
                  } catch (Exception $e) {
                    echo '<tr><td colspan="13" class="text-center text-danger">Error loading orders: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
  </section>
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

<?php
// prepare page-specific javascript to be appended by footer after libraries
$pageScript = <<<EOD
<script>
  $(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();

    // Print button - load receipt in modal
    $('.btnprint').click(function() {
      var invoiceId = $(this).data('invoice-id');
      
      $('#receiptContent').load('get_receipt.php?id=' + invoiceId + '&user_view=1', function() {
          $('#receiptModal').modal('show');
      });
    });

    // Print receipt from modal

    // Mark as Done button
    $(document).on('click', '.btn-mark-done', function() {
      var btn = $(this);
      var invoiceId = btn.data('invoice-id');
      Swal.fire({
        title: 'Mark as Done',
        text: 'Are you sure you want to complete this order?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Complete',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          btn.prop('disabled', true);
          $.post('mark_order_done.php', {order_id: invoiceId}, function(resp) {
            console.log('mark_order_done response:', resp);
            if (resp && resp.success) {
              var row = btn.closest('tr');
              var pendingBadge = row.find('td span.badge').filter(function() {
                return $(this).text().trim().toLowerCase() === 'pending';
              });
              if (pendingBadge.length) {
                pendingBadge.removeClass('badge-warning').addClass('badge-success').text('Complete');
              }
              btn.remove();
              Swal.fire('Completed!', 'Order marked as complete.', 'success');
            } else {
              var msg = (resp && resp.message) ? resp.message : 'Failed to complete order. Please try again.';
              Swal.fire('Error', msg, 'error');
              btn.prop('disabled', false);
            }
          }, 'json').fail(function(xhr, textStatus, errorThrown) {
            console.error('mark_order_done fail', textStatus, errorThrown, xhr.responseText);
            var errorMessage = 'Request failed: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : (xhr.responseText || textStatus));
            Swal.fire('Error', errorMessage, 'error');
            btn.prop('disabled', false);
          });
        }
      });
    });
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
          \${receiptContent}
        </body>
        </html>
      `);
      
      printWindow.document.close();
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 250);
    });

    $('#table_orderlist').DataTable({
      "order": [[0, "desc"]]
    });
  });
</script>
EOD;
?>

<!-- Close content tags opened in headeruser.php -->
</div>          <!-- End col -->
</div>          <!-- End row -->
</div>      <!-- End container-fluid -->
</section>   <!-- End content section -->
</div>      <!-- End content-wrapper -->
</div>      <!-- End wrapper -->

<?php include_once "footer.php"; ?>