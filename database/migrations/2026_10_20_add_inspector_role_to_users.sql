-- =====================================================================
-- Migration: Add 'inspector' Role to Users Table ENUM
-- =====================================================================

ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'custodian', 'executive_admin', 'faculty', 'student', 'inspector') DEFAULT 'student';
