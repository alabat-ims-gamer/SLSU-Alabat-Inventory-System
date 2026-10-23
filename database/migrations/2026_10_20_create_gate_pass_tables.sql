-- Migration: 2026_10_20_create_gate_pass_tables.sql
-- Description: Creates gate_passes and gate_pass_items tables for off-campus borrowed equipment permits.
-- Follows OWASP secure schema design with foreign key constraints, UTF8mb4 character encoding, and proper indexing.

CREATE TABLE IF NOT EXISTS `gate_passes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `gate_pass_no` VARCHAR(32) NOT NULL UNIQUE,
  `requisitioner_id` INT UNSIGNED NULL,
  `requisitioner_name` VARCHAR(191) NOT NULL,
  `requisitioning_office` VARCHAR(191) NOT NULL,
  `purpose` TEXT NOT NULL,
  `pass_type` ENUM('BORROWED', 'REPAIR', 'INTER_CAMPUS_TRANSPORT', 'OTHER') NOT NULL DEFAULT 'BORROWED',
  `status` ENUM('ISSUED', 'CANCELLED') NOT NULL DEFAULT 'ISSUED',
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_gp_no` (`gate_pass_no`),
  INDEX `idx_gp_status` (`status`),
  INDEX `idx_gp_requisitioner` (`requisitioner_id`),
  CONSTRAINT `fk_gp_requisitioner` FOREIGN KEY (`requisitioner_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_gp_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gate_pass_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `gate_pass_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NULL,
  `transaction_id` INT UNSIGNED NULL,
  `item_description` TEXT NOT NULL,
  `model` VARCHAR(128) NULL,
  `serial_no` VARCHAR(128) NULL,
  `date_acquired` DATE NULL,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `remarks` TEXT NULL,
  INDEX `idx_gpi_pass_id` (`gate_pass_id`),
  INDEX `idx_gpi_item_id` (`item_id`),
  INDEX `idx_gpi_transaction_id` (`transaction_id`),
  CONSTRAINT `fk_gpi_pass` FOREIGN KEY (`gate_pass_id`) REFERENCES `gate_passes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gpi_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_gpi_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
