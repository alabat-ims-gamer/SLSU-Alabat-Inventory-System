-- =====================================================================
-- SLSU-Alabat Web-Based Inventory Management & Properties Tracking System
-- Complete MySQL Database Schema (RBAC, Inventory, Transactions, Audit)
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (RBAC & Account Approval Engine)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(50) NOT NULL UNIQUE,
    username VARCHAR(60) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'custodian', 'executive_admin', 'faculty', 'student', 'inspector') DEFAULT 'student',
    department_program VARCHAR(100) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    phone_number VARCHAR(25) NULL,
    account_status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    max_concurrent_loans INT DEFAULT 3,
    has_damage_hold BOOLEAN DEFAULT FALSE,
    notification_preferences JSON NULL,
    session_version INT DEFAULT 1,
    rejection_reason TEXT NULL,
    role_assigned_by INT NULL,
    role_assigned_at DATETIME NULL,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_assigned_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_status (account_status),
    INDEX idx_user_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.5 Rate Limiting / Login Attempts
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    identifier VARCHAR(120) NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX idx_attempt_lookup (ip_address, identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Items / Assets Table (Categorization, Barcode & Condition)
CREATE TABLE IF NOT EXISTS items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    property_number VARCHAR(60) UNIQUE,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    item_name VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(60) NOT NULL,
    location VARCHAR(100) NOT NULL,
    is_student_borrowable BOOLEAN DEFAULT TRUE,
    registered_by INT NULL,
    requested_by INT NULL,
    status ENUM('available', 'requested', 'borrowed', 'maintenance', 'for_disposal', 'disposed', 'lost') DEFAULT 'available',
    reservation_expires_at DATETIME NULL,
    request_purpose VARCHAR(255) NULL,
    item_condition ENUM('mint', 'good', 'fair', 'damaged', 'not_working', 'for_disposal') DEFAULT 'good',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (registered_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_items_status (status),
    INDEX idx_items_condition (item_condition),
    INDEX idx_items_category (category),
    INDEX idx_items_reservation (status, reservation_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Transactions Table (Borrow, Return, Maintenance, Disposal Tracking)
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    transaction_type ENUM('borrow', 'return', 'maintenance', 'disposal') NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATETIME NULL,
    actual_return_date DATETIME NULL,
    remarks TEXT,
    returned_condition ENUM('mint', 'good', 'fair', 'damaged', 'not_working') NULL,
    processed_by INT NULL,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.5 Damage Incidents Table (Accountability & Clearance Ledger)
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
    clearance_document_path VARCHAR(255) NULL,
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

-- 3.75 Asset Disposal & Condemnation Records Table
CREATE TABLE IF NOT EXISTS disposal_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    disposed_by_user_id INT NOT NULL,
    disposal_method ENUM('scrapped', 'sold', 'donated', 'cannibalized', 'transferred') NOT NULL DEFAULT 'scrapped',
    disposal_reason VARCHAR(255) NOT NULL,
    salvage_value DECIMAL(10, 2) DEFAULT 0.00,
    disposal_date DATE NOT NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_disposal_item FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_disposal_user FOREIGN KEY (disposed_by_user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_disposal_method (disposal_method),
    INDEX idx_disposal_date (disposal_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Audit Trail & Security Logs Table (Immutable Logging)
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_type VARCHAR(60) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_audit_action (action_type),
    INDEX idx_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
    category VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created (user_id, created_at DESC),
    INDEX idx_user_unread (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Persistent Device Auth Tokens (30-day Remember Me)
CREATE TABLE IF NOT EXISTS remember_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector VARCHAR(32) NOT NULL UNIQUE,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_selector (selector),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Property Movement & Location History Table (Section 5)
CREATE TABLE IF NOT EXISTS property_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    previous_location VARCHAR(100) NOT NULL,
    new_location VARCHAR(100) NOT NULL,
    transfer_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255) NOT NULL DEFAULT 'Reallocation / Location Update',
    transferred_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pm_item FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_pm_user FOREIGN KEY (transferred_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_pm_item_date (item_id, transfer_date),
    INDEX idx_pm_date (transfer_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Lost & Missing Properties Table (Section 4)
CREATE TABLE IF NOT EXISTS lost_properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    last_recorded_location VARCHAR(100) NULL,
    accountable_person VARCHAR(150) NOT NULL DEFAULT 'Unassigned / General Custody',
    date_reported DATE NOT NULL,
    description TEXT NOT NULL,
    investigation_status ENUM('under_investigation', 'located', 'confirmed_lost') DEFAULT 'under_investigation',
    resolution ENUM('pending', 'located', 'confirmed_lost') DEFAULT 'pending',
    reported_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lp_item FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    CONSTRAINT fk_lp_user FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_lp_item (item_id),
    INDEX idx_lp_status (investigation_status),
    INDEX idx_lp_date (date_reported)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Real-Time System Events Table (Unified Real-Time Synchronization Engine)
CREATE TABLE IF NOT EXISTS system_events (
    event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(60) NOT NULL,
    entity_type VARCHAR(30) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    payload JSON NOT NULL,
    target_role VARCHAR(30) NULL DEFAULT NULL,
    target_user_id INT NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_lookup (event_id, target_role, target_user_id),
    INDEX idx_events_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


