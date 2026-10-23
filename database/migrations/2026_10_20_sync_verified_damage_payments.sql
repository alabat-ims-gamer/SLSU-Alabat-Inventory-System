-- =====================================================================
-- SLSU-Alabat IMS Migration: Sync Damage Payment Balances With Verified Ledger
-- Date: 2026-10-20
-- Ensures amount_paid and remaining_balance strictly reflect verified payments
-- =====================================================================

-- 1. Resynchronize amount_paid strictly to sum of verified payments
UPDATE damage_incidents di
SET amount_paid = (
    SELECT COALESCE(SUM(dip.amount_paid), 0.00)
    FROM damage_incident_payments dip
    WHERE dip.incident_id = di.incident_id 
      AND dip.payment_status = 'verified'
);

-- 2. Resynchronize remaining_balance based on verified payments
UPDATE damage_incidents di
SET remaining_balance = CASE
    WHEN di.incident_status = 'cleared' THEN 0.00
    WHEN di.estimated_cost IS NOT NULL THEN GREATEST(0.00, di.estimated_cost - COALESCE(di.amount_paid, 0.00))
    ELSE NULL
END;
