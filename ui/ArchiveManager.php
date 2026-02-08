<?php
/**
 * Archive Management Class
 * Handles archiving, restoring, and permanent deletion of transactions
 * 
 * Features:
 * - Archive deleted transactions instead of permanent deletion
 * - Restore archived transactions to active order list
 * - Permanently delete archived transactions
 * - Audit trail for all archive operations
 * - Read-only archived records
 */

class ArchiveManager {
    
    private $pdo;
    private $userid;
    
    public function __construct($pdo, $userid) {
        $this->pdo = $pdo;
        $this->userid = $userid;
        // Ensure archive tables exist; auto-create if missing so archive works even after DB reset
        $this->ensureArchiveTables();
    }

    /**
     * Ensure archive DB tables exist. If they don't, create them.
     * This allows archive operations to work even when the tables were dropped.
     * Any errors are suppressed to avoid exposing internals to callers.
     *
     * @return void
     */
    private function ensureArchiveTables() {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'tbl_invoice_archive'")->fetch();
            if ($check) {
                return; // tables already exist
            }

            // Use the same CREATE TABLE definitions as the setup script
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
                KEY `idx_archived_at` (`archived_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ";

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

            // Execute creates in a transaction to keep DB consistent
            $this->pdo->beginTransaction();
            $this->pdo->exec($sql_invoice_archive);
            $this->pdo->exec($sql_details_archive);
            $this->pdo->exec($sql_activity_log);
            $this->pdo->commit();

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Fail silently — archive operations will return errors if SQL differs
        }
    }
    
    /**
     * Archive an invoice and its details
     * Moves the invoice from active list to archive
     * 
     * @param int $invoiceId The invoice ID to archive
     * @param string $notes Optional notes about the archival
     * @return array ['success' => bool, 'message' => string]
     */
    public function archiveInvoice($invoiceId, $notes = '') {
        try {
            $this->pdo->beginTransaction();
            
            // Get invoice details
            $invoiceStmt = $this->pdo->prepare("SELECT * FROM tbl_invoice WHERE invoice_id = ?");
            $invoiceStmt->execute([$invoiceId]);
            $invoice = $invoiceStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$invoice) {
                return ['success' => false, 'message' => 'Invoice not found'];
            }
            
            // Get invoice items
            $itemsStmt = $this->pdo->prepare("SELECT * FROM tbl_invoice_details WHERE invoice_id = ?");
            $itemsStmt->execute([$invoiceId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Archive invoice - use correct column names matching archive_setup.php schema
            $archiveInvoiceSQL = "INSERT INTO tbl_invoice_archive (
                invoice_id, order_date, subtotal, discount, total_amount, 
                payment_type, archived_by, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'archived')";
            
            $archiveInvoiceStmt = $this->pdo->prepare($archiveInvoiceSQL);
            $archiveInvoiceStmt->execute([
                $invoice['invoice_id'],
                $invoice['order_date'],
                $invoice['subtotal'],
                $invoice['discount'],
                $invoice['total'],
                $invoice['payment_type'],
                $this->userid
            ]);
            
            // Archive invoice details - use correct column names matching archive_setup.php schema
            $archiveDetailSQL = "INSERT INTO tbl_invoice_details_archive (
                detail_id, invoice_id, product_id, product_name, qty, price, 
                total_price, service_type, additional_fee
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $archiveDetailStmt = $this->pdo->prepare($archiveDetailSQL);
            
            foreach ($items as $item) {
                // Calculate total price for this item
                $itemTotal = ($item['saleprice'] ?? $item['rate'] ?? 0) * ($item['qty'] ?? 0);
                
                $archiveDetailStmt->execute([
                    $item['id'] ?? NULL,
                    $item['invoice_id'],
                    $item['product_id'],
                    $item['product_name'],
                    $item['qty'],
                    $item['rate'] ?? 0,
                    $itemTotal,
                    $item['servicetype'] ?? NULL,
                    $item['addfee'] ?? 0
                ]);
                
                // Restore stock
                $updateStockSQL = "UPDATE tbl_product SET stock = stock + ? WHERE pid = ?";
                $updateStockStmt = $this->pdo->prepare($updateStockSQL);
                $updateStockStmt->execute([$item['qty'], $item['product_id']]);
            }
            
            // Delete from active tables
            $this->pdo->prepare("DELETE FROM tbl_invoice_details WHERE invoice_id = ?")->execute([$invoiceId]);
            $this->pdo->prepare("DELETE FROM tbl_invoice WHERE invoice_id = ?")->execute([$invoiceId]);
            
            // Log activity
            $this->logArchiveActivity($invoiceId, 'archived', $notes);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Invoice archived successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Archive failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Restore an archived invoice back to active orders
     * 
     * @param int $archiveId The archive record ID to restore
     * @param string $notes Optional notes about restoration
     * @return array ['success' => bool, 'message' => string]
     */
    public function restoreInvoice($archiveId, $notes = '') {
        try {
            $this->pdo->beginTransaction();
            
            // Get archived invoice
            $archivedInvoiceStmt = $this->pdo->prepare("SELECT * FROM tbl_invoice_archive WHERE archive_id = ?");
            $archivedInvoiceStmt->execute([$archiveId]);
            $archivedInvoice = $archivedInvoiceStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$archivedInvoice) {
                return ['success' => false, 'message' => 'Archived invoice not found'];
            }
            
            $invoiceId = $archivedInvoice['invoice_id'];
            
            // Check if invoice still exists in active table
            $existsStmt = $this->pdo->prepare("SELECT invoice_id FROM tbl_invoice WHERE invoice_id = ?");
            $existsStmt->execute([$invoiceId]);
            if ($existsStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Invoice already exists in active orders'];
            }
            
            // Get archived details
            $archivedDetailsStmt = $this->pdo->prepare("SELECT * FROM tbl_invoice_details_archive WHERE invoice_id = ?");
            $archivedDetailsStmt->execute([$invoiceId]);
            $archivedDetails = $archivedDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore invoice to active table - only restore columns that exist in both tables
            $restoreInvoiceSQL = "INSERT INTO tbl_invoice (
                invoice_id, order_date, subtotal, discount, sgst, cgst, total, payment_type, due, paid
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $restoreInvoiceStmt = $this->pdo->prepare($restoreInvoiceSQL);
            $restoreInvoiceStmt->execute([
                $archivedInvoice['invoice_id'],
                $archivedInvoice['order_date'],
                $archivedInvoice['subtotal'],
                $archivedInvoice['discount'],
                0,  // sgst (not stored in archive)
                0,  // cgst (not stored in archive)
                $archivedInvoice['total_amount'],
                $archivedInvoice['payment_type'],
                0,  // due (not stored in archive)
                0   // paid (not stored in archive)
            ]);
            
            // Restore invoice details
            $restoreDetailSQL = "INSERT INTO tbl_invoice_details (
                id, invoice_id, product_id, product_name, qty, rate, saleprice, order_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $restoreDetailStmt = $this->pdo->prepare($restoreDetailSQL);
            
            foreach ($archivedDetails as $detail) {
                $restoreDetailStmt->execute([
                    $detail['detail_id'],
                    $detail['invoice_id'],
                    $detail['product_id'],
                    $detail['product_name'],
                    $detail['qty'],
                    $detail['price'],
                    $detail['total_price'],
                    $archivedInvoice['order_date']
                ]);
                
                // Deduct stock
                $updateStockSQL = "UPDATE tbl_product SET stock = stock - ? WHERE pid = ?";
                $updateStockStmt = $this->pdo->prepare($updateStockSQL);
                $updateStockStmt->execute([$detail['qty'], $detail['product_id']]);
            }
            
            // Update archive status to restored
            $this->pdo->prepare("UPDATE tbl_invoice_archive SET status = 'restored' WHERE archive_id = ?")
                ->execute([$archiveId]);
            
            // Log activity
            $this->logArchiveActivity($invoiceId, 'restored', $notes);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Invoice restored successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Restoration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Permanently delete an archived invoice
     * This is the final deletion that cannot be undone
     * 
     * @param int $archiveId The archive record ID to permanently delete
     * @param string $notes Optional notes about deletion
     * @return array ['success' => bool, 'message' => string]
     */
    public function permanentlyDeleteArchived($archiveId, $notes = '') {
        try {
            $this->pdo->beginTransaction();
            
            // Get archived invoice
            $archivedInvoiceStmt = $this->pdo->prepare("SELECT invoice_id FROM tbl_invoice_archive WHERE archive_id = ?");
            $archivedInvoiceStmt->execute([$archiveId]);
            $archivedInvoice = $archivedInvoiceStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$archivedInvoice) {
                return ['success' => false, 'message' => 'Archived invoice not found'];
            }
            
            $invoiceId = $archivedInvoice['invoice_id'];
            
            // Update status to permanently_deleted instead of hard delete for audit trail
            $this->pdo->prepare("UPDATE tbl_invoice_archive SET status = 'permanently_deleted' WHERE archive_id = ?")
                ->execute([$archiveId]);
            
            // Log activity
            $this->logArchiveActivity($invoiceId, 'permanently_deleted', $notes);
            
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Invoice permanently deleted from archive'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Permanent deletion failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all archived invoices
     * Admin only view
     * 
     * @param string $status Filter by status (archived, restored, permanently_deleted, or 'all')
     * @return array Array of archived invoices
     */
    public function getArchivedInvoices($status = 'archived') {
        try {
            $sql = "
                SELECT 
                    ia.archive_id,
                    ia.invoice_id,
                    ia.customer_name,
                    ia.order_date,
                    ia.subtotal,
                    ia.discount,
                    ia.total_amount as total,
                    ia.payment_type,
                    ia.archived_by as deleted_by,
                    ia.archived_at as deleted_at,
                    ia.status as archive_status,
                    u.role as deleted_by_user,
                    COUNT(id_details.archive_detail_id) as item_count
                FROM tbl_invoice_archive ia
                LEFT JOIN tbl_user u ON ia.archived_by = u.userid
                LEFT JOIN tbl_invoice_details_archive id_details ON ia.invoice_id = id_details.invoice_id
            ";
            
            if ($status !== 'all') {
                $sql .= " WHERE ia.status = ?";
            }
            
            $sql .= " GROUP BY ia.archive_id ORDER BY ia.archived_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($status !== 'all') {
                $stmt->execute([$status]);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get archived invoice details
     * 
     * @param int $invoiceId The invoice ID
     * @return array Array of archived invoice details
     */
    public function getArchivedInvoiceDetails($invoiceId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM tbl_invoice_details_archive 
                WHERE invoice_id = ?
                ORDER BY archive_detail_id
            ");
            $stmt->execute([$invoiceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get archive activity log
     * 
     * @param int $invoiceId Optional: filter by invoice ID
     * @return array Array of activity logs
     */
    public function getActivityLog($invoiceId = null) {
        try {
            $sql = "
                SELECT 
                    aal.log_id,
                    aal.invoice_id,
                    aal.action,
                    aal.performed_by,
                    aal.performed_at,
                    aal.notes,
                    u.username,
                    u.useremail
                FROM tbl_archive_activity_log aal
                LEFT JOIN tbl_user u ON aal.performed_by = u.userid
            ";
            
            if ($invoiceId) {
                $sql .= " WHERE aal.invoice_id = ?";
            }
            
            $sql .= " ORDER BY aal.performed_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($invoiceId) {
                $stmt->execute([$invoiceId]);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Log archive activity
     * Internal method to record all archive operations
     * 
     * @param int $invoiceId The invoice ID
     * @param string $action The action performed (archived, restored, permanently_deleted)
     * @param string $notes Optional notes
     * @return void
     */
    private function logArchiveActivity($invoiceId, $action, $notes = '') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO tbl_archive_activity_log (invoice_id, action, performed_by, performed_at, notes)
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([$invoiceId, $action, $this->userid, $notes]);
        } catch (Exception $e) {
            // Log errors silently to not interrupt main operations
        }
    }
    
    /**
     * Get archive statistics
     * 
     * @return array Statistics about archived items
     */
    public function getArchiveStatistics() {
        try {
            $stats = [];
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM tbl_invoice_archive WHERE status = 'archived'");
            $stmt->execute();
            $stats['total_archived'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM tbl_invoice_archive WHERE status = 'restored'");
            $stmt->execute();
            $stats['total_restored'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM tbl_invoice_archive WHERE status = 'permanently_deleted'");
            $stmt->execute();
            $stats['total_deleted'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->pdo->prepare("SELECT SUM(total_amount) as total_value FROM tbl_invoice_archive WHERE status = 'archived'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['archived_value'] = $result['total_value'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            return [
                'total_archived' => 0,
                'total_restored' => 0,
                'total_deleted' => 0,
                'archived_value' => 0
            ];
        }
    }
}
?>
