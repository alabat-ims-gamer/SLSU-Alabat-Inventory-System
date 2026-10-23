-- =====================================================================
-- SLSU-Alabat Web-Based Inventory Management & Properties Tracking System
-- Seed File: Initial System Administrator Account
-- =====================================================================

-- Note: The password hash here is for 'Admin@12345'
-- Generated using Argon2id or Bcrypt. In a real environment, you should use PHP's password_hash().
-- Example bcrypt hash for 'Admin@12345': $2y$10$wN9a2HhJj/72Y.HqS/d9p.gC0vT83eG4kC91ZJ0T5Kq0Y34D/wVdK
INSERT INTO users (
    id_number,
    username,
    full_name,
    email,
    password_hash,
    role,
    department_program,
    account_status,
    max_concurrent_loans
) VALUES (
    'ADMIN-001',
    'admin',
    'System Administrator',
    'admin@slsu.edu.ph',
    '$2y$10$zyhqzcIMbaamMhk/5encyusrLaqDU/qiv3r3sYfoLr1NPA0jR3ED6',
    'admin',
    'Campus Administration',
    'approved',
    10
) ON DUPLICATE KEY UPDATE account_status = 'approved';
