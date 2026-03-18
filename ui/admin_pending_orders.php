<?php
include_once 'connectdb.php';
include_once 'utils.php';
session_start();

// Ensure only admin can access
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Admin') {
    header('location:../index.php');
    exit;
}

// Handle approval/rejection via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $invoice_id = (int)($_POST['invoice_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if (!$invoice_id || !in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get invoice details including creator
        $stmt = $pdo->prepare("SELECT * FROM tbl_invoice WHERE invoice_id = :id");
        $stmt->execute([':id' => $invoice_id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            exit;
        }
        
        $new_status = ($action === 'approve') ? 'Complete' : 'Rejected';
        
        // Update invoice status
        $update = $pdo->prepare("UPDATE tbl_invoice SET status = :status WHERE invoice_id = :id");
        $update->execute([':status' => $new_status, ':id' => $invoice_id]);
        
        // Log the action
        $description = sprintf(
            'Pending order #%d %s by Admin %s. Amount: ₱%.2f | Notes: %s',
            $invoice_id,
            $action === 'approve' ? 'APPROVED' : 'REJECTED',
            $_SESSION['username'],
            $invoice['total'],
            $notes ?: 'None'
        );
        
        logActivity(
            $_SESSION['useremail'] ?? 'System',
            'Order ' . ucfirst($action),
            'Sales',
            $description,
            'INFO'
        );
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order ' . $action . 'ed successfully',
            'new_status' => $new_status
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Non-AJAX: Show pending orders page
include_once "header.php";
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">User Pending Orders</h1>
                    <small>Review and approve/reject pending user orders</small>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">User Pending Orders</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="m-0">Pending Orders Awaiting Approval</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table_pending">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Created By (User)</th>
                                            <th>Customer</th>
                                            <th>Products</th>
                                            <th>Total Items</th>
                                            <th>Total Amount</th>
                                            <th>Order Date</th>
                                            <th>Payment Type</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $query = "
                                            SELECT 
                                                inv.invoice_id,
                                                inv.order_date,
                                                inv.payment_type,
                                                inv.total,
                                                inv.customer_name,
                                                inv.created_by,
                                                inv.created_by_id,
                                                COALESCE(u.username, 'Unknown') as created_by_username,
                                                COUNT(det.id) as item_count,
                                                SUM(det.qty) as total_qty
                                            FROM tbl_invoice inv
                                            LEFT JOIN tbl_user u ON COALESCE(inv.created_by_id, inv.created_by) = u.userid
                                            LEFT JOIN tbl_invoice_details det ON inv.invoice_id = det.invoice_id
                                            WHERE inv.status = 'Pending'
                                            AND COALESCE(u.role, 'User') != 'Admin'
                                            GROUP BY inv.invoice_id
                                            ORDER BY inv.invoice_id DESC
                                            ";

                                            $stmt = $pdo->prepare($query);
                                            $stmt->execute();
                                            $pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (empty($pending_orders)) {
                                                echo '<tr><td colspan="9" class="text-center text-muted">No pending orders</td></tr>';
                                            } else {
                                                foreach ($pending_orders as $order) {
                                                    // Get products
                                                    $prodStmt = $pdo->prepare("
                                                        SELECT CONCAT(product_name, ' (', qty, ')') as product_info
                                                        FROM tbl_invoice_details
                                                        WHERE invoice_id = :id
                                                    ");
                                                    $prodStmt->execute([':id' => $order['invoice_id']]);
                                                    $products = $prodStmt->fetchAll(PDO::FETCH_COLUMN);
                                                    $product_list = implode(', ', $products);
                                                    
                                                    echo '<tr>';
                                                    echo '<td><strong>#' . htmlspecialchars($order['invoice_id']) . '</strong></td>';
                                                    echo '<td>' . htmlspecialchars($order['created_by_username']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($order['customer_name'] ?? 'N/A') . '</td>';
                                                    echo '<td><small>' . htmlspecialchars($product_list ?: 'N/A') . '</small></td>';
                                                    echo '<td class="text-center"><span class="badge badge-info">' . htmlspecialchars($order['item_count']) . '</span></td>';
                                                    echo '<td><strong>₱' . number_format($order['total'], 2) . '</strong></td>';
                                                    echo '<td>' . htmlspecialchars(date('F j, Y', strtotime($order['order_date']))) . '</td>';
                                                    
                                                    if ($order['payment_type'] == "cash") {
                                                        echo '<td><span class="badge badge-warning">Cash</span></td>';
                                                    } elseif ($order['payment_type'] == "cod") {
                                                        echo '<td><span class="badge badge-info">COD</span></td>';
                                                    } else {
                                                        echo '<td><span class="badge badge-secondary">' . htmlspecialchars($order['payment_type']) . '</span></td>';
                                                    }
                                                    
                                                    echo '<td>';
                                                    echo '  <button type="button" class="btn btn-sm btn-success btn-approve" data-invoice-id="' . $order['invoice_id'] . '" data-toggle="tooltip" title="Approve Order">';
                                                    echo '    <i class="fas fa-check"></i> Approve';
                                                    echo '  </button>';
                                                    echo '  <button type="button" class="btn btn-sm btn-danger btn-reject" data-invoice-id="' . $order['invoice_id'] . '" data-toggle="tooltip" title="Reject Order">';
                                                    echo '    <i class="fas fa-times"></i> Reject';
                                                    echo '  </button>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                        } catch (Exception $e) {
                                            echo '<tr><td colspan="9" class="text-center text-danger">Error loading pending orders: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" role="dialog" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
                <div class="form-group">
                    <label>Notes (Optional):</label>
                    <textarea id="approvalNotes" class="form-control" rows="3" placeholder="Add any notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>

<?php
// prepare javascript for page; will be output by footer after lib scripts
$pageScript = <<<EOD
<script>
console.log('[AdminPending] Script loaded, jQuery check...');
if (typeof jQuery === 'undefined') {
    console.error('[AdminPending] jQuery NOT LOADED');
} else {
    (function($){
        $(function(){
            console.log('[AdminPending] Ready, initializing');
            var table = $('#table_pending');
            if (table.length) {
                try {
                    table.DataTable({"order": [[0, "desc"]], "pageLength": 10});
                } catch(e) {
                    console.log('[AdminPending] DataTable note:', e.message);
                }
            }
            $('[data-toggle="tooltip"]').tooltip();
            
            // Delegate approve/reject on document (very robust)
            $(document).on('click', '.btn-approve', function(e){
                e.preventDefault();
                var id = $(this).data('invoice-id');
                console.log('[APPROVE clicked]', id);
                if (id) {
                    $('#approvalModal').data('invoice-id', id).data('action', 'approve');
                    $('#confirmMessage').html('Approve order #' + id + '?');
                    $('#approvalModal').modal('show');
                }
                return false;
            });
            
            $(document).on('click', '.btn-reject', function(e){
                e.preventDefault();
                var id = $(this).data('invoice-id');
                console.log('[REJECT clicked]', id);
                if (id) {
                    $('#approvalModal').data('invoice-id', id).data('action', 'reject');
                    $('#confirmMessage').html('Reject order #' + id + '?');
                    $('#approvalModal').modal('show');
                }
                return false;
            });
            
            $(document).on('click', '#confirmButton', function(e){
                e.preventDefault();
                var id = $('#approvalModal').data('invoice-id');
                var action = $('#approvalModal').data('action');
                var notes = $('#approvalNotes').val();
                console.log('[CONFIRM clicked]', {id, action});
                if (!id || !action) {
                    Swal.fire('Error', 'No selection', 'error');
                    return false;
                }
                $.ajax({
                    url: 'admin_pending_orders.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {action: action, invoice_id: id, notes: notes},
                    success: function(response){
                        console.log('[AJAX success]', response);
                        if (response.success) {
                            $('#approvalModal').modal('hide');
                            Swal.fire('Success', response.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr){
                        console.error('[AJAX error]', xhr.statusText, xhr.responseText);
                        Swal.fire('Error', 'Request failed', 'error');
                    }
                });
                return false;
            });
            console.log('[AdminPending] Setup complete');
        });
    })(jQuery);
}
</script>
EOD;
?>
<?php include_once "footer.php"; ?>
