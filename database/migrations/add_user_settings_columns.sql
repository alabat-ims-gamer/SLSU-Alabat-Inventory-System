-- Migration: Add User Settings & Profile Customization Columns
-- Tables affected: users

ALTER TABLE users 
ADD COLUMN avatar_path VARCHAR(255) NULL AFTER department_program,
ADD COLUMN phone_number VARCHAR(25) NULL AFTER avatar_path,
ADD COLUMN notification_preferences JSON NULL AFTER max_concurrent_loans,
ADD COLUMN session_version INT DEFAULT 1 AFTER notification_preferences;
