-- ==============================================================================
-- SLSU-Alabat IMS: Inspection and Acceptance Report (IAR) Tables
-- Form AFA-SAF-1.016 (Southern Luzon State University - Alabat Campus)
-- Date: 2026-10-20
-- ==============================================================================

CREATE TABLE IF NOT EXISTS `inspection_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `iar_no` VARCHAR(32) NOT NULL UNIQUE,
    `entity_name` VARCHAR(64) NOT NULL DEFAULT 'SLSU-ALABAT',
    `fund_cluster` VARCHAR(64) NOT NULL DEFAULT 'PRE-STF',
    `supplier_name` VARCHAR(191) NOT NULL,
    `po_no` VARCHAR(64) NOT NULL,
    `po_date` DATE NOT NULL,
    `invoice_no` VARCHAR(64) NOT NULL,
    `invoice_date` DATE NOT NULL,
    `office_dept` VARCHAR(128) NOT NULL,
    `rcc` VARCHAR(64) NULL,
    `date_inspected` DATE NOT NULL,
    `date_received` DATE NOT NULL,
    `inspection_officer` VARCHAR(191) NOT NULL DEFAULT 'MANUEL S. VALDERAMA, MIT',
    `property_officer` VARCHAR(191) NOT NULL DEFAULT 'VERONICA C. OBIEN',
    `is_inspected_order` TINYINT(1) NOT NULL DEFAULT 1,
    `acceptance_type` ENUM('COMPLETE', 'PARTIAL') NOT NULL DEFAULT 'COMPLETE',
    `partial_remarks` TEXT NULL,
    `scanned_document_path` VARCHAR(255) NULL,
    `status` ENUM('PENDING_SIGNATURES', 'TRANSMITTED', 'REGISTERED', 'CANCELLED') NOT NULL DEFAULT 'PENDING_SIGNATURES',
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_iar_no` (`iar_no`),
    INDEX `idx_iar_status` (`status`),
    INDEX `idx_iar_supplier` (`supplier_name`),
    INDEX `idx_iar_creator` (`created_by`),
    CONSTRAINT `fk_iar_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inspection_report_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `iar_id` INT NOT NULL,
    `item_no` INT NOT NULL,
    `item_description` TEXT NOT NULL,
    `unit` VARCHAR(32) NOT NULL DEFAULT 'units',
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `model` VARCHAR(128) NULL,
    `serial_no` VARCHAR(128) NULL,
    `is_registered` TINYINT(1) NOT NULL DEFAULT 0,
    `registered_item_id` INT NULL,
    INDEX `idx_iari_report` (`iar_id`),
    INDEX `idx_iari_registered` (`is_registered`),
    INDEX `idx_iari_item` (`registered_item_id`),
    CONSTRAINT `fk_iari_report` FOREIGN KEY (`iar_id`) REFERENCES `inspection_reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_iari_item` FOREIGN KEY (`registered_item_id`) REFERENCES `items`(`item_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
