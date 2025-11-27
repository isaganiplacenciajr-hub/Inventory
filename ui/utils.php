<?php
/**
 * utils.php - Shared logging helper
 * Provides logActivity() to write to activity_logs table with support for logging levels and extra data
 */

if (!isset($pdo)) {
    if (file_exists(__DIR__ . '/connectdb.php')) {
        include_once __DIR__ . '/connectdb.php';
    }
}

/**
 * Log an activity to the database
 * 
 * @param string $user Username or email from session (or 'Unknown' if not set)
 * @param string $action Action name (e.g., "Add Product", "Login", "Delete Product")
 * @param string $module Module name (e.g., "Inventory", "Authentication", "User")
 * @param string $description Description of what happened
 * @param string $level Log level: 'INFO', 'WARNING', 'ERROR' (default: 'INFO')
 * @param array|null $extra Optional extra data as associative array (stored as JSON)
 * @return bool True on success, false on failure
 */
function logActivity($user, $action, $module, $description, $level = 'INFO', $extra = null)
{
    global $pdo;
    if (!isset($pdo) || !$pdo instanceof PDO) {
        error_log('[logActivity] No PDO connection available');
        return false;
    }

    try {
        // Ensure user is not empty
        $user = !empty($user) ? $user : 'Unknown';
        
        // Convert extra data to JSON if provided
        $extraJson = $extra !== null ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null;
        
        // Check if table has extra_data column, otherwise use basic columns
        $sql = "INSERT INTO activity_logs (datetime, `user`, action, module, description, level, extra_data) 
                VALUES (NOW(), :user, :action, :module, :description, :level, :extra)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user', $user);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':module', $module);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':extra', $extraJson);
        
        return $stmt->execute();
    } catch (Exception $e) {
        // If extra_data column doesn't exist, fall back to basic columns
        try {
            $user = !empty($user) ? $user : 'Unknown';
            $stmt = $pdo->prepare("INSERT INTO activity_logs (datetime, `user`, action, module, description) 
                                   VALUES (NOW(), :user, :action, :module, :description)");
            $stmt->bindParam(':user', $user);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':module', $module);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (Exception $e2) {
            error_log('[logActivity] ' . $e2->getMessage());
            return false;
        }
    }
}

?>
