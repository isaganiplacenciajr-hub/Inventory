-- Add missing columns to tbl_invoice for User Dashboard feature
-- Run this if the columns don't exist

-- Add created_by column (tracks which user created the order)
ALTER TABLE `tbl_invoice` ADD COLUMN `created_by` INT(11) DEFAULT 0 AFTER `customer_address`;

-- Add status column if not already present
-- (Some systems might have this, comment out if it causes an error)
-- ALTER TABLE `tbl_invoice` ADD COLUMN `status` VARCHAR(50) DEFAULT 'Complete' AFTER `created_by`;

-- Update existing orders to be marked as complete
UPDATE `tbl_invoice` SET `status` = 'Complete' WHERE `status` IS NULL OR `status` = '';

-- Create index for faster queries
ALTER TABLE `tbl_invoice` ADD INDEX `idx_created_by` (`created_by`);
ALTER TABLE `tbl_invoice` ADD INDEX `idx_status` (`status`);

-- Add foreign key constraint (optional, for referential integrity)
-- ALTER TABLE `tbl_invoice` ADD CONSTRAINT `fk_invoice_user` FOREIGN KEY (`created_by`) REFERENCES `tbl_user`(`userid`) ON DELETE SET NULL;
