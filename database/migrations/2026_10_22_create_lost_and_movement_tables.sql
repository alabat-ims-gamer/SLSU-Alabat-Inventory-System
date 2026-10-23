-- Migration: 2026_10_22_create_lost_and_movement_tables.sql
-- Description: Creates property_movements and lost_properties tables and extends items.status to include 'lost'.
-- Follows OWASP secure schema design with foreign key constraints, UTF8mb4 character encoding, and proper indexing.

-- 1. Property Movements Table (Section 5 of Executive Institutional Property Report)
CREATE TABLE IF NOT EXISTS `property_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `previous_location` VARCHAR(100) NOT NULL,
    `new_location` VARCHAR(100) NOT NULL,
    `transfer_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reason` VARCHAR(255) NOT NULL DEFAULT 'Reallocation / Location Update',
    `transferred_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pm_item` FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pm_user` FOREIGN KEY (`transferred_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    INDEX `idx_pm_item_date` (`item_id`, `transfer_date`),
    INDEX `idx_pm_date` (`transfer_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Lost & Missing Properties Table (Section 4 of Executive Institutional Property Report)
CREATE TABLE IF NOT EXISTS `lost_properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `last_recorded_location` VARCHAR(100) NULL,
    `accountable_person` VARCHAR(150) NOT NULL DEFAULT 'Unassigned / General Custody',
    `date_reported` DATE NOT NULL,
    `description` TEXT NOT NULL,
    `investigation_status` ENUM('under_investigation', 'located', 'confirmed_lost') DEFAULT 'under_investigation',
    `resolution` ENUM('pending', 'located', 'confirmed_lost') DEFAULT 'pending',
    `reported_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_lp_item` FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lp_user` FOREIGN KEY (`reported_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    INDEX `idx_lp_item` (`item_id`),
    INDEX `idx_lp_status` (`investigation_status`),
    INDEX `idx_lp_date` (`date_reported`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Extend items.status ENUM to include 'lost'
ALTER TABLE `items` MODIFY COLUMN `status` ENUM('available', 'requested', 'borrowed', 'maintenance', 'for_disposal', 'disposed', 'lost') DEFAULT 'available';
