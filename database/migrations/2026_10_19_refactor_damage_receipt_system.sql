-- =====================================================================
-- SLSU-Alabat IMS Migration: Refactor Damage Clearance to Submit & Verify
-- Date: 2026-10-19
-- Simplifies damage resolution to Student Receipt Submission & Custodian Verification
-- =====================================================================

-- 1. Modify incident_status ENUM to support new Submit & Verify workflow
ALTER TABLE damage_incidents 
  MODIFY COLUMN incident_status ENUM('awaiting_payment', 'pending_verification', 'cleared', 'rejected', 'open_hold') NOT NULL DEFAULT 'awaiting_payment';

-- 2. Add receipt submission columns
ALTER TABLE damage_incidents
  ADD COLUMN IF NOT EXISTS receipt_number VARCHAR(100) NULL AFTER estimated_cost,
  ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) NULL AFTER receipt_number,
  ADD COLUMN IF NOT EXISTS payment_date DATE NULL AFTER amount_paid,
  ADD COLUMN IF NOT EXISTS receipt_submitted_at DATETIME NULL AFTER payment_date,
  ADD COLUMN IF NOT EXISTS student_notes TEXT NULL AFTER receipt_submitted_at,
  ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL AFTER settlement_notes;

-- 3. Migrate any legacy 'open_hold' records to 'awaiting_payment'
UPDATE damage_incidents 
SET incident_status = 'awaiting_payment' 
WHERE incident_status = 'open_hold';

-- 4. Add index for faster verification queue filtering
ALTER TABLE damage_incidents
  ADD INDEX IF NOT EXISTS idx_incident_queue (incident_status, receipt_submitted_at);
