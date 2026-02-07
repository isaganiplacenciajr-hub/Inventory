<?php
/**
 * Archive Feature - Debug & Test Script
 * This script helps diagnose issues with the archive feature
 */

include_once 'connectdb.php';

echo "<h1>Archive Feature Debug Report</h1>";

// 1. Check ArchiveManager class
echo "<h2>1. Checking ArchiveManager Class</h2>";
if (file_exists('ArchiveManager.php')) {
    echo "✅ ArchiveManager.php file exists<br>";
    include_once 'ArchiveManager.php';
    if (class_exists('ArchiveManager')) {
        echo "✅ ArchiveManager class is defined<br>";
    } else {
        echo "❌ ArchiveManager class NOT found<br>";
    }
} else {
    echo "❌ ArchiveManager.php file NOT found<br>";
}

// 2. Check database connection
echo "<h2>2. Checking Database Connection</h2>";
try {
    if ($pdo) {
        echo "✅ Database connection successful<br>";
        
        // Check if archive tables exist
        echo "<h3>Archive Tables Status:</h3>";
        
        $tables = ['tbl_invoice_archive', 'tbl_invoice_details_archive', 'tbl_archive_activity_log'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ $table exists<br>";
            } else {
                echo "❌ $table does NOT exist<br>";
            }
        }
    } else {
        echo "❌ No database connection<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// 3. Test ArchiveManager
echo "<h2>3. Testing ArchiveManager</h2>";
try {
    session_start();
    $_SESSION['userid'] = 13; // Admin user
    $_SESSION['role'] = 'Admin';
    
    $archiveManager = new ArchiveManager($pdo, $_SESSION['userid']);
    echo "✅ ArchiveManager instantiated successfully<br>";
    
    // Test getting statistics
    $stats = $archiveManager->getArchiveStatistics();
    echo "<h3>Archive Statistics:</h3>";
    echo "Total Archived: " . $stats['total_archived'] . "<br>";
    echo "Total Restored: " . $stats['total_restored'] . "<br>";
    echo "Total Deleted: " . $stats['total_deleted'] . "<br>";
    echo "Archived Value: ₱" . number_format($stats['archived_value'], 2) . "<br>";
    
    // Test getting archived invoices
    $archives = $archiveManager->getArchivedInvoices('archived');
    echo "✅ Retrieved " . count($archives) . " archived invoices<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 4. Check file permissions
echo "<h2>4. Checking File Permissions</h2>";
$files = [
    'archive.php',
    'ArchiveManager.php',
    'api/get_archives.php',
    'api/get_archive_details.php',
    'api/restore_archive.php',
    'api/delete_archive.php',
    'api/get_archive_stats.php',
    'api/get_archive_activity.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "✅ $file exists and is readable<br>";
        } else {
            echo "⚠️ $file exists but is NOT readable<br>";
        }
    } else {
        echo "❌ $file does NOT exist<br>";
    }
}

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
h3 { color: #888; margin-top: 15px; }
</style>
