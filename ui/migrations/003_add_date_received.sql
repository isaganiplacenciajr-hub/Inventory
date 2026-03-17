-- Migration: Add date_received column to tbl_product
-- Created: 2026-03-03
-- Adds a nullable DATE column `date_received` to store received date for products

ALTER TABLE tbl_product
  ADD COLUMN date_received DATE DEFAULT NULL;

-- Optional: replace '0000-00-00' placeholders with NULL
-- UPDATE tbl_product SET date_received = NULL WHERE date_received = '0000-00-00';
