<?php
/**
 * Migration: Add status column to tbl_invoice
 * Description: Adds 'status' column for tracking order completion (Complete, Pending)
 */

require_once 'connectdb.php';

try {
    // Check if status column exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'status'");
    
    if ($checkColumn->rowCount() == 0) {
        // Add status column with default value 'Complete' for existing orders
        $sql = "ALTER TABLE tbl_invoice ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Complete' AFTER payment_type";
        $pdo->exec($sql);
        echo "✓ Status column added to tbl_invoice<br>";
    } else {
        echo "✓ Status column already exists in tbl_invoice<br>";
    }
    
    // Also add to archive table if needed
    $checkArchive = $pdo->query("SHOW COLUMNS FROM tbl_invoice_archive LIKE 'status'");
    if ($checkArchive->rowCount() == 0) {
        $sql = "ALTER TABLE tbl_invoice_archive ADD COLUMN invoice_status VARCHAR(20) DEFAULT 'Complete' AFTER status";
        $pdo->exec($sql);
        echo "✓ Status column added to tbl_invoice_archive<br>";
    }

    // Add branch column to invoice and archive if missing
    $checkBranch = $pdo->query("SHOW COLUMNS FROM tbl_invoice LIKE 'branch'");
    if ($checkBranch->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tbl_invoice ADD COLUMN branch VARCHAR(100) NOT NULL DEFAULT 'Unknown' AFTER created_by_role");
        echo "✓ Branch column added to tbl_invoice<br>";
    }
    $checkArchiveBranch = $pdo->query("SHOW COLUMNS FROM tbl_invoice_archive LIKE 'branch'");
    if ($checkArchiveBranch->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tbl_invoice_archive ADD COLUMN branch VARCHAR(100) NOT NULL DEFAULT 'Unknown' AFTER created_by_role");
        echo "✓ Branch column added to tbl_invoice_archive<br>";
    }
    
    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
