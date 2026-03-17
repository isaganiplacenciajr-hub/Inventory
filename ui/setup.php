<?php
include_once 'connectdb.php';

$setup_complete = true;
$messages = [];

try {
    // Check if created_by column exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'created_by'");
    if ($checkColumn->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `created_by` INT(11) DEFAULT 0 AFTER `customer_address`");
        $messages[] = '✓ Added created_by column to tbl_invoice';
    }
    
    // Check if status column exists
    $checkStatus = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'status'");
    if ($checkStatus->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `status` VARCHAR(50) DEFAULT 'Complete' AFTER `created_by`");
        $messages[] = '✓ Added status column to tbl_invoice';
    }
    
    // Create indexes if they don't exist
    $indexCheck = $pdo->query("SHOW INDEXES FROM tbl_invoice WHERE Key_name = 'idx_created_by'");
    if ($indexCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD INDEX `idx_created_by` (`created_by`)");
        $messages[] = '✓ Added idx_created_by index';
    }
    
    $statusIndexCheck = $pdo->query("SHOW INDEXES FROM tbl_invoice WHERE Key_name = 'idx_status'");
    if ($statusIndexCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD INDEX `idx_status` (`status`)");
        $messages[] = '✓ Added idx_status index';
    }

    // Add branch column if missing
    $checkBranch = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'branch'");
    if ($checkBranch->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `branch` VARCHAR(100) NOT NULL DEFAULT 'Unknown' AFTER `created_by_role`");
        $messages[] = '✓ Added branch column to tbl_invoice';
    }

    $branchIndexCheck = $pdo->query("SHOW INDEXES FROM tbl_invoice WHERE Key_name = 'idx_branch'");
    if ($branchIndexCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD INDEX `idx_branch` (`branch`)");
        $messages[] = '✓ Added idx_branch index';
    }

    if (empty($messages)) {
        $messages[] = '✓ Database schema is already up to date!';
    }
    
} catch (PDOException $e) {
    $setup_complete = false;
    $messages[] = 'Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Setup - SPM LPG Inventory</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
    <div class="login-box" style="width: 100%; max-width: 500px;">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h3 class="m-0">Database Setup</h3>
            </div>
            <div class="card-body">
                <div class="alert <?php echo $setup_complete ? 'alert-success' : 'alert-warning'; ?>">
                    <h5><?php echo $setup_complete ? '✓ Setup Complete!' : '⚠️ Setup Message'; ?></h5>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <?php foreach ($messages as $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <strong>User Dashboard Features Enabled:</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>User POS with order tracking</li>
                        <li>User Order List (view-only)</li>
                        <li>Admin Pending Order Approval</li>
                        <li>Order Status: Pending / Completed / Rejected</li>
                        <li>Activity Logging for all actions</li>
                    </ul>
                </div>
                
                <div class="text-center mt-3">
                    <a href="user.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to User Dashboard
                    </a>
                    <a href="dashboard.php" class="btn btn-info">
                        <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
</body>
</html>
