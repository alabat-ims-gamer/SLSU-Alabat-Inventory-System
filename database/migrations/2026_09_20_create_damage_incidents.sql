-- =====================================================================
-- SLSU-Alabat IMS Migration: Damaged Returns, Incident Management & Clearance
-- Creates damage_incidents table, expands items.status to include 'for_disposal',
-- and sets up relationship constraints for the accountability ledger.
-- =====================================================================

-- 1. Ensure items status ENUM supports 'for_disposal'
ALTER TABLE items 
  MODIFY COLUMN status ENUM('available', 'requested', 'borrowed', 'maintenance', 'for_disposal', 'disposed') DEFAULT 'available';

-- 2. Create damage_incidents table
CREATE TABLE IF NOT EXISTS damage_incidents (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    item_id INT NOT NULL,
    borrower_id INT NOT NULL,
    reported_by INT NOT NULL,
    damage_condition ENUM('damaged', 'not_working') NOT NULL,
    damage_description TEXT NOT NULL,
    estimated_cost DECIMAL(10,2) NULL,
    settlement_type ENUM('in_kind_replacement', 'repair_cost', 'admin_waiver') NULL,
    settlement_reference VARCHAR(255) NULL,
    settlement_notes TEXT NULL,
    incident_status ENUM('open_hold', 'cleared') DEFAULT 'open_hold',
    cleared_by INT NULL,
    cleared_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incident_status (incident_status),
    INDEX idx_incident_borrower (borrower_id, incident_status),
    INDEX idx_incident_item (item_id),
    CONSTRAINT fk_incident_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
    CONSTRAINT fk_incident_item FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_incident_borrower FOREIGN KEY (borrower_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_incident_reported_by FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_incident_cleared_by FOREIGN KEY (cleared_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
