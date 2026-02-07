<?php
/**
 * Archive Feature - Usage Examples
 * 
 * This file demonstrates how to use the Archive Manager class
 * for various archive operations in your application.
 */

// ============================================
// EXAMPLE 1: Basic Archive Operation
// ============================================

/*
<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    die('User not authenticated');
}

// Initialize ArchiveManager
$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);

// Archive an invoice
$invoiceId = 42;
$result = $archiveManager->archiveInvoice($invoiceId, 'User requested deletion');

if ($result['success']) {
    echo "Invoice {$invoiceId} has been archived successfully";
    // Invoice moved to archive tables
    // Stock restored to inventory
    // Activity logged
} else {
    echo "Error: " . $result['message'];
}
*/

// ============================================
// EXAMPLE 2: Restore Archived Invoice
// ============================================

/*
<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);

// Restore an archived invoice
$archiveId = 1;  // From tbl_invoice_archive.archive_id
$result = $archiveManager->restoreInvoice($archiveId, 'Mistake in deletion');

if ($result['success']) {
    echo "Invoice restored successfully";
    // Invoice moved back to active tables
    // Stock deducted from inventory again
    // Archive status updated to 'restored'
} else {
    echo "Error: " . $result['message'];
}
*/

// ============================================
// EXAMPLE 3: Permanently Delete from Archive
// ============================================

/*
<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

// Only admins should perform this action
if ($_SESSION['role'] !== 'Admin') {
    die('Only admins can permanently delete archived items');
}

$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);

// Permanently delete from archive (irreversible)
$archiveId = 1;
$reason = 'Audit complete, data no longer needed, confirmed with management';
$result = $archiveManager->permanentlyDeleteArchived($archiveId, $reason);

if ($result['success']) {
    echo "Invoice permanently deleted from archive";
    // Status changed to 'permanently_deleted'
    // Audit trail maintained
    // Data cannot be restored
} else {
    echo "Error: " . $result['message'];
}
*/

// ============================================
// EXAMPLE 4: Get All Archived Invoices
// ============================================

/*
<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);

// Get archived invoices (currently archived only)
$archivedInvoices = $archiveManager->getArchivedInvoices('archived');

echo "Currently Archived Invoices:\n";
foreach ($archivedInvoices as $invoice) {
    echo "Invoice {$invoice['invoice_id']} - ₱{$invoice['total']} - {$invoice['order_date']}\n";
    echo "  Archived by: {$invoice['deleted_by_user']}\n";
    echo "  Archived on: {$invoice['deleted_at']}\n";
    echo "  Items: {$invoice['item_count']}\n";
    echo "\n";
}

// Get restored invoices
$restoredInvoices = $archiveManager->getArchivedInvoices('restored');

// Get permanently deleted invoices
$deletedInvoices = $archiveManager->getArchivedInvoices('permanently_deleted');

// Get all (complete history)
$allArchives = $archiveManager->getArchivedInvoices('all');
*/

// ============================================
// EXAMPLE 5: Get Invoice Details from Archive
// ============================================

/*
<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);

// Get details of archived invoice
$invoiceId = 42;
$details = $archiveManager->getArchivedInvoiceDetails($invoiceId);

echo "Archived Invoice Details:\n";
foreach ($details as $item) {
    echo "Product: {$item['product_name']}\n";
    echo "  Quantity: {$item['qty']}\n";
    echo "  Rate: ₱{$item['rate']}\n";
    echo "  Total: ₱{$item['saleprice']}\n";
    echo "  Service Type: {$item['servicetype']}\n";
    echo "  Additional Fee: ₱{$item['addfee']}\n";
    echo "\n";
}
*/

// ============================================
// EXAMPLE 6: Get Archive Statistics
// ============================================

/*
<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);

// Get statistics
$stats = $archiveManager->getArchiveStatistics();

echo "Archive Statistics:\n";
echo "Total Archived: {$stats['total_archived']}\n";
echo "Total Restored: {$stats['total_restored']}\n";
echo "Total Permanently Deleted: {$stats['total_deleted']}\n";
echo "Total Archived Value: ₱" . number_format($stats['archived_value'], 2) . "\n";
*/

// ============================================
// EXAMPLE 7: Get Activity Log
// ============================================

/*
<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);

// Get all activity logs
$allLogs = $archiveManager->getActivityLog();

echo "All Archive Activities:\n";
foreach ($allLogs as $log) {
    echo "[{$log['performed_at']}] {$log['action']} - Invoice #{$log['invoice_id']}\n";
    echo "  By: {$log['username']}\n";
    echo "  Notes: {$log['notes']}\n";
    echo "\n";
}

// Get activity log for specific invoice
$invoiceId = 42;
$invoiceLogs = $archiveManager->getActivityLog($invoiceId);

echo "\nActivity for Invoice #{$invoiceId}:\n";
foreach ($invoiceLogs as $log) {
    echo "[{$log['performed_at']}] {$log['action']} by {$log['username']}\n";
}
*/

// ============================================
// EXAMPLE 8: Using with Forms (HTML)
// ============================================

/*
<form method="POST" action="archive_handler.php">
    <input type="hidden" name="action" value="archive">
    <input type="hidden" name="invoice_id" value="42">
    <textarea name="notes" placeholder="Reason for archiving..."></textarea>
    <button type="submit">Archive Invoice</button>
</form>

<?php
// archive_handler.php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $invoiceId = $_POST['invoice_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    
    if ($action === 'archive') {
        $result = $archiveManager->archiveInvoice($invoiceId, $notes);
    } elseif ($action === 'restore') {
        $result = $archiveManager->restoreInvoice($invoiceId, $notes);
    }
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode($result);
}
?>
*/

// ============================================
// EXAMPLE 9: Using with AJAX/JavaScript
// ============================================

/*
JavaScript/jQuery Example:

// Archive invoice via AJAX
function archiveInvoice(invoiceId, notes) {
    $.post('api/get_archives.php', {
        invoice_id: invoiceId,
        notes: notes
    }, function(data) {
        if (data.success) {
            alert('Invoice archived successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    }, 'json');
}

// Restore invoice via AJAX
function restoreInvoice(archiveId, notes) {
    $.post('api/restore_archive.php', {
        archive_id: archiveId,
        notes: notes
    }, function(data) {
        if (data.success) {
            alert('Invoice restored successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    }, 'json');
}

// Permanently delete from archive
function deleteArchived(archiveId, notes) {
    if (confirm('This action is IRREVERSIBLE! Proceed?')) {
        $.post('api/delete_archive.php', {
            archive_id: archiveId,
            notes: notes
        }, function(data) {
            if (data.success) {
                alert('Permanently deleted!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        }, 'json');
    }
}
*/

// ============================================
// EXAMPLE 10: Database Query Examples
// ============================================

/*
-- Get total archived amount by user
SELECT 
    u.username,
    COUNT(*) as count,
    SUM(ia.total) as total_value
FROM tbl_invoice_archive ia
JOIN tbl_user u ON ia.deleted_by = u.userid
WHERE ia.archive_status = 'archived'
GROUP BY u.username;

-- Get archived invoices from last 7 days
SELECT *
FROM tbl_invoice_archive
WHERE deleted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
AND archive_status = 'archived'
ORDER BY deleted_at DESC;

-- Get most active users in archive operations
SELECT 
    u.username,
    COUNT(*) as total_operations
FROM tbl_archive_activity_log aal
JOIN tbl_user u ON aal.performed_by = u.userid
GROUP BY u.username
ORDER BY total_operations DESC;

-- Track specific invoice's complete history
SELECT 
    aal.action,
    u.username,
    aal.performed_at,
    aal.notes
FROM tbl_archive_activity_log aal
JOIN tbl_user u ON aal.performed_by = u.userid
WHERE aal.invoice_id = 42
ORDER BY aal.performed_at DESC;

-- Find oldest archived invoices
SELECT 
    archive_id,
    invoice_id,
    deleted_at,
    total
FROM tbl_invoice_archive
WHERE archive_status = 'archived'
ORDER BY deleted_at ASC
LIMIT 10;

-- Calculate archive statistics
SELECT 
    archive_status,
    COUNT(*) as count,
    SUM(total) as total_value,
    AVG(total) as avg_value
FROM tbl_invoice_archive
GROUP BY archive_status;
*/

// ============================================
// EXAMPLE 11: Error Handling
// ============================================

/*
<?php
include_once 'connectdb.php';
include_once 'ArchiveManager.php';
session_start();

try {
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    
    // Attempt to archive
    $result = $archiveManager->archiveInvoice(9999);  // Non-existent invoice
    
    if (!$result['success']) {
        // Handle specific error
        switch ($result['message']) {
            case 'Invoice not found':
                echo 'Invoice does not exist';
                break;
            case 'Archive failed':
                echo 'Database error occurred';
                break;
            default:
                echo 'Unknown error: ' . $result['message'];
        }
    }
} catch (Exception $e) {
    // Log error
    error_log("Archive operation failed: " . $e->getMessage());
    echo "An unexpected error occurred";
}
?>
*/

// ============================================
// EXAMPLE 12: Security Best Practices
// ============================================

/*
<?php
// Always validate user role
if ($_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    die('Access denied');
}

// Always validate input
if (!isset($_POST['archive_id']) || !is_numeric($_POST['archive_id'])) {
    http_response_code(400);
    die('Invalid input');
}

// Use prepared statements (already done in ArchiveManager)
// Use transactions for data consistency (already done in ArchiveManager)

// Always log significant operations
$archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
$result = $archiveManager->archiveInvoice($_POST['invoice_id'], 'Admin archived');

if ($result['success']) {
    // Log to application log
    error_log("Invoice {$_POST['invoice_id']} archived by {$_SESSION['userid']}");
}
?>
*/

?>

<!-- This file is for documentation purposes. Remove or modify as needed. -->
<h2>Archive Feature - Usage Examples</h2>
<p>See source code comments for 12 detailed usage examples covering:</p>
<ul>
    <li>Basic archive operations</li>
    <li>Restoring archived invoices</li>
    <li>Permanent deletion</li>
    <li>Retrieving archive data</li>
    <li>Statistics and reporting</li>
    <li>Activity logs</li>
    <li>Form integration</li>
    <li>AJAX/JavaScript integration</li>
    <li>Database query examples</li>
    <li>Error handling</li>
    <li>Security best practices</li>
</ul>
