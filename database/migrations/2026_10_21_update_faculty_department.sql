-- =====================================================================
-- Migration: Standardize 'Faculty & Staff' Department to 'Faculty'
-- =====================================================================

UPDATE users 
SET department_program = 'Faculty' 
WHERE department_program = 'Faculty & Staff';
