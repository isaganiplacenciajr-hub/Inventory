-- Migration: Add supplier_category column to tbl_product
-- Description: Adds a new field for Supplier Category in the product table

ALTER TABLE tbl_product ADD COLUMN supplier_category VARCHAR(200) DEFAULT NULL;
