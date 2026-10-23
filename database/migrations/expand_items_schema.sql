-- =====================================================================
-- SLSU-Alabat IMS Migration: Expand Items Schema for Asset Lifecycle
-- Adds 5-tier condition taxonomy, registered_by FK, updated_at, and indexes.
-- =====================================================================

ALTER TABLE items 
  MODIFY COLUMN item_condition ENUM('mint', 'good', 'fair', 'damaged', 'not_working', 'for_disposal') DEFAULT 'good',
  MODIFY COLUMN status ENUM('available', 'borrowed', 'maintenance', 'disposed') DEFAULT 'available',
  ADD COLUMN IF NOT EXISTS registered_by INT NULL AFTER is_student_borrowable,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD INDEX IF NOT EXISTS idx_items_status (status),
  ADD INDEX IF NOT EXISTS idx_items_condition (item_condition),
  ADD INDEX IF NOT EXISTS idx_items_category (category);
