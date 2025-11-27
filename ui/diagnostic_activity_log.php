<?php
include_once 'connectdb.php';

// Check if activity_logs table exists
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM activity_logs");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ activity_logs table exists. Total records: " . $row['cnt'] . "\n\n";
} catch (Exception $e) {
    echo "✗ Error accessing activity_logs: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if utils.php exists and logActivity works
if (file_exists('utils.php')) {
    echo "✓ utils.php exists\n";
    include_once 'utils.php';
    
    if (function_exists('logActivity')) {
        echo "✓ logActivity() function exists\n";
        
        // Try to insert a test log
        $result = logActivity('diagnostic@test.com', 'System Diagnostic', 'System', 'Checking if activity log is working');
        if ($result) {
            echo "✓ Test log inserted successfully\n\n";
            
            // Display latest logs
            $stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 5");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Latest 5 logs:\n";
            echo str_repeat("-", 100) . "\n";
            foreach ($logs as $log) {
                echo "ID: {$log['id']}\n";
                echo "DateTime: {$log['datetime']}\n";
                echo "User: {$log['user']}\n";
                echo "Action: {$log['action']}\n";
                echo "Module: {$log['module']}\n";
                echo "Description: {$log['description']}\n";
                echo str_repeat("-", 100) . "\n";
            }
        } else {
            echo "✗ Failed to insert test log\n";
        }
    } else {
        echo "✗ logActivity() function NOT found in utils.php\n";
    }
} else {
    echo "✗ utils.php NOT found\n";
}

?>
