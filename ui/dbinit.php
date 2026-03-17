<?php
/**
 * Database Schema Initialization
 * Ensures all required columns exist in the database
 */

if (!isset($pdo)) {
    include_once __DIR__ . '/connectdb.php';
}

try {
    // Check and fix order_date column type (DATE to DATETIME)
    $checkOrderDate = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'order_date'");
    if ($checkOrderDate->rowCount() > 0) {
        $column = $checkOrderDate->fetch(PDO::FETCH_ASSOC);
        if (strpos($column['Type'], 'datetime') === false) {
            // Convert DATE to DATETIME
            $pdo->exec("ALTER TABLE `tbl_invoice` MODIFY COLUMN `order_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            error_log('[DB Init] Converted order_date column from DATE to DATETIME');
        }
    }
    
    // Check if created_by column exists in tbl_invoice
    $checkColumn = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'created_by'");
    if ($checkColumn->rowCount() === 0) {
        // Add created_by column
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `created_by` INT(11) DEFAULT 0 AFTER `customer_address`");
        error_log('[DB Init] Added created_by column to tbl_invoice');
    }

    // Check if created_by_id/name/role columns exist and create them if missing
    $checkCreatedById = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'created_by_id'")->rowCount();
    if ($checkCreatedById === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `created_by_id` INT NOT NULL DEFAULT 0");
        error_log('[DB Init] Added created_by_id column to tbl_invoice');
    }
    $checkCreatedByName = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'created_by_name'")->rowCount();
    if ($checkCreatedByName === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `created_by_name` VARCHAR(100) NOT NULL DEFAULT ''");
        error_log('[DB Init] Added created_by_name column to tbl_invoice');
    }
    $checkCreatedByRole = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'created_by_role'")->rowCount();
    if ($checkCreatedByRole === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `created_by_role` VARCHAR(50) NOT NULL DEFAULT ''");
        error_log('[DB Init] Added created_by_role column to tbl_invoice');
    }
    
    // Check if status column exists in tbl_invoice
    $checkStatus = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'status'");
    if ($checkStatus->rowCount() === 0) {
        // Add status column
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `status` VARCHAR(50) DEFAULT 'Complete' AFTER `created_by`");
        error_log('[DB Init] Added status column to tbl_invoice');
    }
    
    // Create indexes if they don't exist
    $indexCheck = $pdo->query("SHOW INDEXES FROM tbl_invoice WHERE Key_name = 'idx_created_by'");
    if ($indexCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD INDEX `idx_created_by` (`created_by`)");
        error_log('[DB Init] Added idx_created_by index to tbl_invoice');
    }
    
    $statusIndexCheck = $pdo->query("SHOW INDEXES FROM tbl_invoice WHERE Key_name = 'idx_status'");
    if ($statusIndexCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD INDEX `idx_status` (`status`)");
        error_log('[DB Init] Added idx_status index to tbl_invoice');
    }

    // Add branch column for orders to track creation branch permanently
    $checkBranch = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'branch'");
    if ($checkBranch->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD COLUMN `branch` VARCHAR(100) NOT NULL DEFAULT 'Unknown'");
        error_log('[DB Init] Added branch column to tbl_invoice');
    }

    // Branch index for fast branch filtering
    $branchIndexCheck = $pdo->query("SHOW INDEXES FROM tbl_invoice WHERE Key_name = 'idx_branch'");
    if ($branchIndexCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `tbl_invoice` ADD INDEX `idx_branch` (`branch`)");
        error_log('[DB Init] Added idx_branch index to tbl_invoice');
    }
    
} catch (PDOException $e) {
    error_log('[DB Init Error] ' . $e->getMessage());
    // Continue anyway - columns might already exist
}

?>
