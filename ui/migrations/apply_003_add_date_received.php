<?php
// Safe migration runner for 003_add_date_received.sql
// Usage (browser): http://localhost/full-file-main/ui/migrations/apply_003_add_date_received.php
// Usage (CLI): php apply_003_add_date_received.php

require_once __DIR__ . '/../connectdb.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    // Check DB connection
    if (!isset($pdo)) throw new Exception('Database connection not available. Start MySQL/XAMPP first.');

    // Check if column already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_product' AND COLUMN_NAME = 'date_received'");
    $stmt->execute();
    $exists = (bool)$stmt->fetchColumn();

    if ($exists) {
        echo "Column `date_received` already exists on tbl_product.\n";
    } else {
        // Run ALTER TABLE
        echo "Adding column `date_received` to tbl_product...\n";
        $pdo->exec("ALTER TABLE tbl_product ADD COLUMN date_received DATE DEFAULT NULL;");
        echo "ALTER TABLE executed.\n";

        // Optional: convert '0000-00-00' to NULL if present
        echo "Cleaning up any '0000-00-00' placeholders...\n";
        $pdo->exec("UPDATE tbl_product SET date_received = NULL WHERE date_received = '0000-00-00';");
        echo "Cleanup executed.\n";
    }

    // Verify
    $verify = $pdo->prepare("SHOW COLUMNS FROM tbl_product LIKE 'date_received'");
    $verify->execute();
    $col = $verify->fetch(PDO::FETCH_ASSOC);
    if ($col) {
        echo "Migration complete. Column details:\n";
        foreach ($col as $k => $v) {
            echo "$k: $v\n";
        }
    } else {
        echo "Migration finished but could not verify column.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>