-- Migration: 2026_10_23_create_system_events_table.sql
-- Description: Creates the system_events table for high-performance, real-time event streaming and delta polling.
-- Optimized for low latency (<1ms indexed cursor lookup) and zero cloud service dependencies.

CREATE TABLE IF NOT EXISTS `system_events` (
    `event_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(60) NOT NULL,
    `entity_type` VARCHAR(30) NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `payload` JSON NOT NULL,
    `target_role` VARCHAR(30) NULL DEFAULT NULL,
    `target_user_id` INT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_events_lookup` (`event_id`, `target_role`, `target_user_id`),
    INDEX `idx_events_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
