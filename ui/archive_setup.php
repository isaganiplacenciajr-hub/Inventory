<?php
/**
 * Archive Feature - Database Setup Script
 * Creates required archive tables safely with PDO
 * 
 * Tables Created:
 * - tbl_invoice_archive: Archived invoice records
 * - tbl_invoice_details_archive: Archived invoice line items
 * - tbl_archive_activity_log: Audit trail
 */

include_once 'connectdb.php';
session_start();

// Security: Check if user is admin
if (!isset($_SESSION['userid']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

include_once "header.php";

$alert_type = '';
$alert_message = '';
$success = false;

// ============================================
// HANDLER: Create Archive Tables
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_archive'])) {
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();
        
        // ==========================================
        // 1. Create tbl_invoice_archive
        // ==========================================
        $sql_invoice_archive = "
            CREATE TABLE IF NOT EXISTS `tbl_invoice_archive` (
                `archive_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `invoice_id` INT(11) NOT NULL,
                `customer_name` VARCHAR(255) NULL,
                `total_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
                `payment_type` VARCHAR(50) NULL,
                `order_date` DATE NOT NULL,
                `subtotal` DECIMAL(10, 2) NULL,
                `discount` DECIMAL(10, 2) NULL,
                `tax` DECIMAL(10, 2) NULL,
                `archived_by` INT(11) NOT NULL,
                `archived_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `status` ENUM('archived', 'restored', 'permanently_deleted') DEFAULT 'archived',
                KEY `idx_invoice_id` (`invoice_id`),
                KEY `idx_order_date` (`order_date`),
                KEY `idx_archived_at` (`archived_at`),
                KEY `idx_status` (`status`),
                KEY `idx_archived_by` (`archived_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        
        $pdo->exec($sql_invoice_archive);
        
        // ==========================================
        // 2. Create tbl_invoice_details_archive
        // ==========================================
        $sql_details_archive = "
            CREATE TABLE IF NOT EXISTS `tbl_invoice_details_archive` (
                `archive_detail_id` INT(11) NOT NULL AUTO_INCREMENT,
                `detail_id` INT(11) NULL,
                `invoice_id` INT(11) NOT NULL,
                `product_id` INT(11) NOT NULL,
                `product_name` VARCHAR(255) NOT NULL,
                `qty` INT(11) NOT NULL,
                `price` DECIMAL(10, 2) NOT NULL,
                `total_price` DECIMAL(10, 2) NOT NULL,
                `service_type` VARCHAR(100) NULL,
                `additional_fee` DECIMAL(10, 2) NULL,
                `archived_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`archive_detail_id`),
                KEY `idx_invoice_id` (`invoice_id`),
                KEY `idx_product_id` (`product_id`),
                KEY `idx_archived_at` (`archived_at`),
                CONSTRAINT `fk_archive_details_invoice` 
                    FOREIGN KEY (`invoice_id`) 
                    REFERENCES `tbl_invoice_archive` (`invoice_id`) 
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        
        $pdo->exec($sql_details_archive);
        
        // ==========================================
        // 3. Create tbl_archive_activity_log
        // ==========================================
        $sql_activity_log = "
            CREATE TABLE IF NOT EXISTS `tbl_archive_activity_log` (
                `log_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `invoice_id` INT(11) NULL,
                `action` VARCHAR(100) NOT NULL,
                `description` TEXT NULL,
                `user_id` INT(11) NOT NULL,
                `user_email` VARCHAR(255) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_invoice_id` (`invoice_id`),
                KEY `idx_action` (`action`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        
        $pdo->exec($sql_activity_log);
        
        // ==========================================
        // 4. Log the table creation in activity log
        // ==========================================
        $stmt_log = $pdo->prepare("
            INSERT INTO `tbl_archive_activity_log` 
            (`invoice_id`, `action`, `description`, `user_id`, `user_email`, `created_at`)
            VALUES (NULL, :action, :description, :user_id, :user_email, NOW())
        ");
        
        $stmt_log->execute([
            ':action' => 'CREATE_TABLES',
            ':description' => 'Archive tables created successfully via setup wizard',
            ':user_id' => $_SESSION['userid'],
            ':user_email' => isset($_SESSION['useremail']) ? $_SESSION['useremail'] : 'admin@system'
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        $alert_type = 'success';
        $alert_message = '✅ Archive tables created successfully! The archive feature is now ready to use.';
        $success = true;
        
    } catch (PDOException $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Check if tables already exist
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $alert_type = 'info';
            $alert_message = 'ℹ️ Archive tables already exist. No action needed.';
            $success = true;
        } else {
            $alert_type = 'danger';
            $alert_message = '❌ Error: ' . htmlspecialchars($e->getMessage());
        }
        
    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $alert_type = 'danger';
        $alert_message = '❌ Unexpected error: ' . htmlspecialchars($e->getMessage());
    }
}

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Archive Feature Setup</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Archive Setup</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            
            <!-- Alert Messages -->
            <?php if (!empty($alert_message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $alert_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 offset-md-2">
                    
                    <!-- Setup Card -->
                    <div class="card card-primary card-outline">
                        <div class="card-header bg-primary">
                            <h5 class="m-0 text-white">Database Setup Wizard</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="alert alert-info border-left-info" role="alert">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Important:</strong> The Archive feature requires three database tables. 
                                Click the button below to create them automatically. This is safe to run multiple times.
                            </div>

                            <h5 class="mb-3">📊 Tables That Will Be Created:</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="list-group">
                                        <div class="list-group-item">
                                            <h6 class="mb-1"><i class="fas fa-archive text-primary"></i> tbl_invoice_archive</h6>
                                            <p class="mb-0 text-muted small">Stores archived invoice records with original details and archive metadata</p>
                                        </div>
                                        <div class="list-group-item">
                                            <h6 class="mb-1"><i class="fas fa-list text-success"></i> tbl_invoice_details_archive</h6>
                                            <p class="mb-0 text-muted small">Stores archived invoice line items and product details</p>
                                        </div>
                                        <div class="list-group-item">
                                            <h6 class="mb-1"><i class="fas fa-clipboard-list text-warning"></i> tbl_archive_activity_log</h6>
                                            <p class="mb-0 text-muted small">Audit trail tracking all archive operations for compliance</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Setup Button Form -->
                            <form method="POST" action="" id="setupForm">
                                <input type="hidden" name="setup_archive" value="1">
                                <button type="submit" class="btn btn-success btn-lg btn-block">
                                    <i class="fas fa-database"></i> Create Archive Tables
                                </button>
                            </form>

                            <div class="mt-4 p-3 bg-light border rounded">
                                <strong><i class="fas fa-check-circle text-success"></i> What This Setup Does:</strong>
                                <ul class="mt-3 mb-0">
                                    <li>✅ Creates 3 new tables in your database</li>
                                    <li>✅ Sets up proper indexes for optimal performance</li>
                                    <li>✅ Configures foreign key relationships (data integrity)</li>
                                    <li>✅ Logs the setup action in the activity log</li>
                                    <li>✅ Enables the archive feature for all admin users</li>
                                    <li>✅ Safe to run multiple times (uses IF NOT EXISTS)</li>
                                </ul>
                            </div>

                        </div>
                    </div>

                    <!-- Table Structure Reference -->
                    <div class="card card-secondary card-outline mt-4">
                        <div class="card-header">
                            <h5 class="m-0"><i class="fas fa-code"></i> Table Structure Reference</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">For advanced users: Here's what gets created in each table:</p>
                            
                            <div class="accordion" id="tableStructureAccordion">
                                
                                <!-- Table 1 -->
                                <div class="card border-0">
                                    <div class="card-header bg-light border-bottom" id="heading1">
                                        <h6 class="mb-0">
                                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse1">
                                                <i class="fas fa-database"></i> tbl_invoice_archive
                                            </button>
                                        </h6>
                                    </div>
                                    <div id="collapse1" class="collapse" data-parent="#tableStructureAccordion">
                                        <div class="card-body p-2">
                                            <code style="font-size: 11px; line-height: 1.6;">
                                                archive_id (PRIMARY KEY, AUTO_INCREMENT)<br>
                                                invoice_id, customer_name, total_amount<br>
                                                payment_type, order_date, subtotal<br>
                                                discount, tax, archived_by, archived_at<br>
                                                status (archived | restored | permanently_deleted)<br>
                                                <strong>Indexes:</strong> invoice_id, order_date, archived_at, status
                                            </code>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table 2 -->
                                <div class="card border-0">
                                    <div class="card-header bg-light border-bottom" id="heading2">
                                        <h6 class="mb-0">
                                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse2">
                                                <i class="fas fa-database"></i> tbl_invoice_details_archive
                                            </button>
                                        </h6>
                                    </div>
                                    <div id="collapse2" class="collapse" data-parent="#tableStructureAccordion">
                                        <div class="card-body p-2">
                                            <code style="font-size: 11px; line-height: 1.6;">
                                                archive_detail_id (PRIMARY KEY, AUTO_INCREMENT)<br>
                                                detail_id, invoice_id (FOREIGN KEY)<br>
                                                product_id, product_name, qty, price<br>
                                                total_price, service_type, additional_fee<br>
                                                archived_at<br>
                                                <strong>Indexes:</strong> invoice_id, product_id, archived_at<br>
                                                <strong>Foreign Key:</strong> invoice_id → tbl_invoice_archive
                                            </code>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table 3 -->
                                <div class="card border-0">
                                    <div class="card-header bg-light border-bottom" id="heading3">
                                        <h6 class="mb-0">
                                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse3">
                                                <i class="fas fa-database"></i> tbl_archive_activity_log
                                            </button>
                                        </h6>
                                    </div>
                                    <div id="collapse3" class="collapse" data-parent="#tableStructureAccordion">
                                        <div class="card-body p-2">
                                            <code style="font-size: 11px; line-height: 1.6;">
                                                log_id (PRIMARY KEY, AUTO_INCREMENT)<br>
                                                invoice_id, action (CREATE_TABLES, ARCHIVE_INVOICE, etc.)<br>
                                                description, user_id, user_email, created_at<br>
                                                <strong>Indexes:</strong> invoice_id, action, created_at, user_id
                                            </code>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>

<!-- Auto-redirect on Success -->
<?php if ($success && $alert_type === 'success'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        // Show alert first
        Swal && Swal.fire({
            icon: 'success',
            title: 'Setup Complete!',
            text: 'Archive tables created successfully. Redirecting to Archive Management...',
            timer: 2500,
            didClose: function() {
                window.location.href = 'archive.php';
            }
        });
    }, 1000);
});
</script>
<?php endif; ?>

<!-- Display Error Alert -->
<?php if (!empty($alert_message) && $alert_type === 'danger'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal && Swal.fire({
        icon: 'error',
        title: 'Setup Error',
        text: '<?php echo addslashes(strip_tags($alert_message)); ?>',
        confirmButtonText: 'Retry'
    });
});
</script>
<?php endif; ?>
