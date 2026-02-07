-- Archive Tables Migration
-- This migration creates the archive tables for storing deleted transactions
-- Created: 2026-02-04

-- ========================================
-- 1. Archive table for invoices
-- ========================================
CREATE TABLE IF NOT EXISTS `tbl_invoice_archive` (
  `archive_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `subtotal` double NOT NULL,
  `discount` double NOT NULL,
  `sgst` float NOT NULL,
  `cgst` float NOT NULL,
  `total` double NOT NULL,
  `payment_type` tinytext NOT NULL,
  `due` double NOT NULL,
  `paid` double NOT NULL,
  `deleted_by` int(11) NOT NULL,
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `archive_status` enum('archived','restored','permanently_deleted') DEFAULT 'archived',
  `restoration_notes` text DEFAULT NULL,
  PRIMARY KEY (`archive_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_deleted_by` (`deleted_by`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_archive_status` (`archive_status`),
  FOREIGN KEY (`deleted_by`) REFERENCES `tbl_user`(`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- 2. Archive table for invoice details
-- ========================================
CREATE TABLE IF NOT EXISTS `tbl_invoice_details_archive` (
  `archive_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `barcode` varchar(200) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `qty` int(11) NOT NULL,
  `rate` double NOT NULL,
  `saleprice` double NOT NULL,
  `order_date` date NOT NULL,
  `servicetype` varchar(50) DEFAULT NULL,
  `addfee` decimal(10,2) DEFAULT 0.00,
  `deleted_by` int(11) NOT NULL,
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `archive_status` enum('archived','restored','permanently_deleted') DEFAULT 'archived',
  PRIMARY KEY (`archive_detail_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_deleted_by` (`deleted_by`),
  KEY `idx_deleted_at` (`deleted_at`),
  FOREIGN KEY (`deleted_by`) REFERENCES `tbl_user`(`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- 3. Activity log table for audit trail
-- ========================================
CREATE TABLE IF NOT EXISTS `tbl_archive_activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `action` enum('archived','restored','permanently_deleted') NOT NULL,
  `performed_by` int(11) NOT NULL,
  `performed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_performed_at` (`performed_at`),
  FOREIGN KEY (`performed_by`) REFERENCES `tbl_user`(`userid`),
  FOREIGN KEY (`invoice_id`) REFERENCES `tbl_invoice`(`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
