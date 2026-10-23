-- =====================================================================
-- SLSU-Alabat IMS Migration: Balance Tracking & Multi-Payment Ledger
-- Date: 2026-10-19
-- Supports partial payments, installment receipts, and balance due tracking
-- =====================================================================

-- 1. Modify incident_status ENUM to include 'partially_paid'
ALTER TABLE damage_incidents 
  MODIFY COLUMN incident_status ENUM('awaiting_payment', 'pending_verification', 'partially_paid', 'cleared', 'rejected', 'open_hold') NOT NULL DEFAULT 'awaiting_payment';

-- 2. Add remaining_balance column to damage_incidents
ALTER TABLE damage_incidents
  ADD COLUMN IF NOT EXISTS remaining_balance DECIMAL(10,2) NULL AFTER amount_paid;

-- 3. Create damage_incident_payments table for multi-receipt audit trail
CREATE TABLE IF NOT EXISTS damage_incident_payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  incident_id INT NOT NULL,
  borrower_id INT NOT NULL,
  receipt_number VARCHAR(100) NOT NULL,
  amount_paid DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  clearance_document_path VARCHAR(255) NOT NULL,
  student_notes TEXT NULL,
  payment_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT NULL,
  verified_by INT NULL,
  verified_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_incident_payments (incident_id, payment_status),
  INDEX idx_borrower_payments (borrower_id),
  CONSTRAINT fk_dip_incident FOREIGN KEY (incident_id) REFERENCES damage_incidents(incident_id) ON DELETE CASCADE,
  CONSTRAINT fk_dip_borrower FOREIGN KEY (borrower_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Backfill existing receipts into damage_incident_payments
INSERT INTO damage_incident_payments (
  incident_id, borrower_id, receipt_number, amount_paid, payment_date, 
  clearance_document_path, student_notes, payment_status, rejection_reason, 
  verified_by, verified_at, created_at
)
SELECT 
  di.incident_id, 
  di.borrower_id, 
  di.receipt_number, 
  COALESCE(di.amount_paid, di.estimated_cost, 0.00), 
  COALESCE(di.payment_date, CURDATE()), 
  di.clearance_document_path, 
  di.student_notes,
  CASE 
    WHEN di.incident_status = 'cleared' THEN 'verified'
    WHEN di.incident_status = 'rejected' THEN 'rejected'
    ELSE 'pending'
  END,
  di.rejection_reason,
  di.cleared_by,
  di.cleared_at,
  COALESCE(di.receipt_submitted_at, di.created_at)
FROM damage_incidents di
LEFT JOIN damage_incident_payments dip ON di.incident_id = dip.incident_id
WHERE di.receipt_number IS NOT NULL 
  AND di.clearance_document_path IS NOT NULL
  AND dip.payment_id IS NULL;

-- 5. Initialize remaining_balance on all existing incidents
UPDATE damage_incidents
SET remaining_balance = CASE
  WHEN incident_status = 'cleared' THEN 0.00
  WHEN estimated_cost IS NOT NULL THEN GREATEST(0.00, estimated_cost - COALESCE(amount_paid, 0.00))
  ELSE NULL
END;

-- 6. Update status to 'partially_paid' if amount_paid > 0 but less than estimated_cost and not cleared
UPDATE damage_incidents
SET incident_status = 'partially_paid'
WHERE incident_status NOT IN ('cleared', 'pending_verification')
  AND amount_paid IS NOT NULL 
  AND amount_paid > 0
  AND estimated_cost IS NOT NULL
  AND amount_paid < estimated_cost;
