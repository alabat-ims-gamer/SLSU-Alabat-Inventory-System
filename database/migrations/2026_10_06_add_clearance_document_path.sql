-- Migration: Add clearance_document_path to damage_incidents
-- Purpose: Store uploaded proof files (OR receipts, admin waivers, replacement receipts) securely.

ALTER TABLE damage_incidents
ADD COLUMN clearance_document_path VARCHAR(255) NULL AFTER settlement_notes;
