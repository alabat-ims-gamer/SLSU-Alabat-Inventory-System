-- =====================================================================
-- SLSU-Alabat IMS Migration: Barcode Scanning, Requests & Transactions
-- Adds reservation timestamps, request purpose, damage clearance hold,
-- and return inspection condition tracking.
-- =====================================================================

-- 1. Expand items table with 'requested' status and reservation fields
ALTER TABLE items 
  MODIFY COLUMN status ENUM('available', 'requested', 'borrowed', 'maintenance', 'disposed') DEFAULT 'available',
  ADD COLUMN IF NOT EXISTS requested_by INT NULL AFTER registered_by,
  ADD COLUMN IF NOT EXISTS reservation_expires_at DATETIME NULL AFTER status,
  ADD COLUMN IF NOT EXISTS request_purpose VARCHAR(255) NULL AFTER reservation_expires_at;

-- Add foreign key constraint if it doesn't already exist
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_NAME = 'fk_items_requested_by' 
  AND TABLE_SCHEMA = DATABASE()
);
SET @sql = IF(@fk_exists = 0, 'ALTER TABLE items ADD CONSTRAINT fk_items_requested_by FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add reservation index for fast lookups
SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS 
  WHERE INDEX_NAME = 'idx_items_reservation' 
  AND TABLE_NAME = 'items' 
  AND TABLE_SCHEMA = DATABASE()
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE items ADD INDEX idx_items_reservation (status, reservation_expires_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Expand users table with damage clearance hold
ALTER TABLE users 
  ADD COLUMN IF NOT EXISTS has_damage_hold BOOLEAN DEFAULT FALSE AFTER max_concurrent_loans;

-- 3. Expand transactions table with returned condition
ALTER TABLE transactions 
  ADD COLUMN IF NOT EXISTS returned_condition ENUM('mint', 'good', 'fair', 'damaged', 'not_working') NULL AFTER remarks;
