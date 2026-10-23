-- =====================================================================
-- Migration: Remove 'staff' Role from Users Table
-- =====================================================================

-- 1. Migrate any existing users with 'staff' role to 'faculty'
UPDATE users SET role = 'faculty' WHERE role = 'staff';

-- 2. Modify role ENUM column definition
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'custodian', 'executive_admin', 'faculty', 'student') DEFAULT 'student';
