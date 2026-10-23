<?php
declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

$db = require ROOT_PATH . '/config/database.php';

echo "\n========================================================\n";
echo "  SLSU-Alabat IMS - Automated Incremental Migrations\n";
echo "========================================================\n\n";

// 1. Check & Add Missing Columns in Users Table
echo "[1/4] Checking 'users' table columns...\n";
$userColumns = [
    'avatar_path' => "ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER department_program",
    'phone_number' => "ALTER TABLE users ADD COLUMN phone_number VARCHAR(25) NULL AFTER avatar_path",
    'notification_preferences' => "ALTER TABLE users ADD COLUMN notification_preferences JSON NULL AFTER max_concurrent_loans",
    'session_version' => "ALTER TABLE users ADD COLUMN session_version INT DEFAULT 1 AFTER notification_preferences",
    'has_damage_hold' => "ALTER TABLE users ADD COLUMN has_damage_hold BOOLEAN DEFAULT FALSE AFTER max_concurrent_loans",
    'rejection_reason' => "ALTER TABLE users ADD COLUMN rejection_reason TEXT NULL AFTER session_version",
    'role_assigned_by' => "ALTER TABLE users ADD COLUMN role_assigned_by INT NULL AFTER rejection_reason",
    'role_assigned_at' => "ALTER TABLE users ADD COLUMN role_assigned_at DATETIME NULL AFTER role_assigned_by",
];

foreach ($userColumns as $col => $sql) {
    try {
        $check = $db->query("SHOW COLUMNS FROM users LIKE '$col'")->fetch();
        if (!$check) {
            $db->exec($sql);
            echo "  [+] Added users.$col\n";
        } else {
            echo "  [.] users.$col verified\n";
        }
    } catch (\PDOException $e) {
        echo "  [!] Error on users.$col: " . $e->getMessage() . "\n";
    }
}

// Ensure 'inspector' is part of users.role ENUM
try {
    $roleCol = $db->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
    if ($roleCol && strpos((string)$roleCol['Type'], "'inspector'") === false) {
        $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'custodian', 'executive_admin', 'faculty', 'student', 'inspector') DEFAULT 'student'");
        echo "  [+] Updated users.role ENUM to include 'inspector'\n";
    } else {
        echo "  [.] users.role ENUM contains 'inspector'\n";
    }
} catch (\PDOException $e) {
    echo "  [!] Error verifying users.role: " . $e->getMessage() . "\n";
}

// Standardize 'Faculty & Staff' to 'Faculty'
try {
    $db->exec("UPDATE users SET department_program = 'Faculty' WHERE department_program = 'Faculty & Staff'");
    echo "  [.] Standardized legacy 'Faculty & Staff' department records to 'Faculty'\n";
} catch (\PDOException $e) {
    echo "  [!] Error standardizing faculty department: " . $e->getMessage() . "\n";
}

// 2. Check & Add Missing Columns in Items Table
echo "\n[2/4] Checking 'items' table columns...\n";
$itemColumns = [
    'property_number' => "ALTER TABLE items ADD COLUMN property_number VARCHAR(60) UNIQUE AFTER item_id",
    'is_student_borrowable' => "ALTER TABLE items ADD COLUMN is_student_borrowable BOOLEAN DEFAULT TRUE AFTER location",
    'registered_by' => "ALTER TABLE items ADD COLUMN registered_by INT NULL AFTER is_student_borrowable",
    'requested_by' => "ALTER TABLE items ADD COLUMN requested_by INT NULL AFTER registered_by",
    'reservation_expires_at' => "ALTER TABLE items ADD COLUMN reservation_expires_at DATETIME NULL AFTER status",
    'request_purpose' => "ALTER TABLE items ADD COLUMN request_purpose VARCHAR(255) NULL AFTER reservation_expires_at",
    'model' => "ALTER TABLE items ADD COLUMN model VARCHAR(128) NULL AFTER item_name",
    'serial_number' => "ALTER TABLE items ADD COLUMN serial_number VARCHAR(128) NULL AFTER model",
    'unit_cost' => "ALTER TABLE items ADD COLUMN unit_cost DECIMAL(12,2) DEFAULT 0.00 AFTER description",
    'date_acquired' => "ALTER TABLE items ADD COLUMN date_acquired DATE NULL AFTER unit_cost",
];

foreach ($itemColumns as $col => $sql) {
    try {
        $check = $db->query("SHOW COLUMNS FROM items LIKE '$col'")->fetch();
        if (!$check) {
            $db->exec($sql);
            echo "  [+] Added items.$col\n";
        } else {
            echo "  [.] items.$col verified\n";
        }
    } catch (\PDOException $e) {
        echo "  [!] Error on items.$col: " . $e->getMessage() . "\n";
    }
}

// 2.1 Verify items.status ENUM includes 'lost'
try {
    $statusCol = $db->query("SHOW COLUMNS FROM items LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
    if ($statusCol && strpos((string)$statusCol['Type'], "'lost'") === false) {
        $db->exec("ALTER TABLE items MODIFY COLUMN status ENUM('available', 'requested', 'borrowed', 'maintenance', 'for_disposal', 'disposed', 'lost') DEFAULT 'available'");
        echo "  [+] Updated items.status ENUM to include 'lost'\n";
    } else {
        echo "  [.] items.status ENUM contains 'lost'\n";
    }
} catch (\PDOException $e) {
    echo "  [!] Error verifying items.status: " . $e->getMessage() . "\n";
}

// 2.5 Check & Add Missing Columns in Damage Incidents Table
echo "\n[2.5/4] Checking 'damage_incidents' table columns...\n";
$incidentColumns = [
    'estimated_cost'          => "ALTER TABLE damage_incidents ADD COLUMN estimated_cost DECIMAL(10,2) NULL",
    'receipt_number'          => "ALTER TABLE damage_incidents ADD COLUMN receipt_number VARCHAR(100) NULL AFTER estimated_cost",
    'amount_paid'             => "ALTER TABLE damage_incidents ADD COLUMN amount_paid DECIMAL(10,2) NULL AFTER receipt_number",
    'remaining_balance'       => "ALTER TABLE damage_incidents ADD COLUMN remaining_balance DECIMAL(10,2) NULL AFTER amount_paid",
    'payment_date'            => "ALTER TABLE damage_incidents ADD COLUMN payment_date DATE NULL AFTER remaining_balance",
    'receipt_submitted_at'    => "ALTER TABLE damage_incidents ADD COLUMN receipt_submitted_at DATETIME NULL AFTER payment_date",
    'student_notes'           => "ALTER TABLE damage_incidents ADD COLUMN student_notes TEXT NULL AFTER receipt_submitted_at",
    'settlement_type'         => "ALTER TABLE damage_incidents ADD COLUMN settlement_type ENUM('in_kind_replacement', 'repair_cost', 'admin_waiver') NULL",
    'settlement_reference'    => "ALTER TABLE damage_incidents ADD COLUMN settlement_reference VARCHAR(255) NULL",
    'settlement_notes'        => "ALTER TABLE damage_incidents ADD COLUMN settlement_notes TEXT NULL",
    'rejection_reason'        => "ALTER TABLE damage_incidents ADD COLUMN rejection_reason TEXT NULL AFTER settlement_notes",
    'clearance_document_path' => "ALTER TABLE damage_incidents ADD COLUMN clearance_document_path VARCHAR(255) NULL",
];

foreach ($incidentColumns as $col => $sql) {
    try {
        $checkIncCol = $db->query("SHOW COLUMNS FROM damage_incidents LIKE '$col'")->fetch();
        if (!$checkIncCol) {
            $db->exec($sql);
            echo "  [+] Added damage_incidents.$col\n";
        } else {
            echo "  [.] damage_incidents.$col verified\n";
        }
    } catch (\PDOException $e) {
        echo "  [.] Notice on damage_incidents.$col: " . $e->getMessage() . "\n";
    }
}

// 2.6 Verify damage_incidents.incident_status ENUM (includes 'partially_paid')
try {
    $db->exec("ALTER TABLE damage_incidents MODIFY COLUMN incident_status ENUM('awaiting_payment', 'pending_verification', 'partially_paid', 'cleared', 'rejected', 'open_hold') NOT NULL DEFAULT 'awaiting_payment'");
    $db->exec("UPDATE damage_incidents SET incident_status = 'awaiting_payment' WHERE incident_status = 'open_hold'");
    echo "  [+] damage_incidents.incident_status ENUM updated to include 'partially_paid'\n";
} catch (\PDOException $e) {
    echo "  [.] Notice on incident_status ENUM: " . $e->getMessage() . "\n";
}

// 3. Check & Create Tables
echo "\n[3/4] Checking auxiliary database tables...\n";
$tables = [
    'login_attempts' => "
        CREATE TABLE IF NOT EXISTS login_attempts (
            attempt_id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            identifier VARCHAR(120) NOT NULL,
            attempted_at DATETIME NOT NULL,
            INDEX idx_attempt_lookup (ip_address, identifier, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'damage_incidents' => "
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
    ",
    'disposal_records' => "
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
    ",
    'notifications' => "
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
    ",
    'remember_tokens' => "
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
    ",
    'damage_incident_payments' => "
        CREATE TABLE IF NOT EXISTS damage_incident_payments (
            payment_id INT AUTO_INCREMENT PRIMARY KEY,
            incident_id INT NOT NULL,
            borrower_id INT NOT NULL,
            receipt_number VARCHAR(100) NOT NULL,
            amount_paid DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            clearance_document_path VARCHAR(255) NOT NULL,
            student_notes TEXT NULL,
            payment_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
            rejection_reason TEXT NULL,
            verified_by INT NULL,
            verified_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_incident_payments (incident_id, payment_status),
            INDEX idx_borrower_payments (borrower_id),
            CONSTRAINT fk_dip_incident FOREIGN KEY (incident_id) REFERENCES damage_incidents(incident_id) ON DELETE CASCADE,
            CONSTRAINT fk_dip_borrower FOREIGN KEY (borrower_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'gate_passes' => "
        CREATE TABLE IF NOT EXISTS gate_passes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gate_pass_no VARCHAR(32) NOT NULL UNIQUE,
            requisitioner_id INT NULL,
            requisitioner_name VARCHAR(191) NOT NULL,
            requisitioning_office VARCHAR(191) NOT NULL,
            purpose TEXT NOT NULL,
            pass_type ENUM('BORROWED', 'REPAIR', 'INTER_CAMPUS_TRANSPORT', 'OTHER') NOT NULL DEFAULT 'BORROWED',
            status ENUM('ISSUED', 'CANCELLED') NOT NULL DEFAULT 'ISSUED',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_gp_no (gate_pass_no),
            INDEX idx_gp_status (status),
            INDEX idx_gp_requisitioner (requisitioner_id),
            CONSTRAINT fk_gp_requisitioner FOREIGN KEY (requisitioner_id) REFERENCES users(user_id) ON DELETE SET NULL,
            CONSTRAINT fk_gp_creator FOREIGN KEY (created_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'gate_pass_items' => "
        CREATE TABLE IF NOT EXISTS gate_pass_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gate_pass_id INT NOT NULL,
            item_id INT NULL,
            transaction_id INT NULL,
            item_description TEXT NOT NULL,
            model VARCHAR(128) NULL,
            serial_no VARCHAR(128) NULL,
            date_acquired DATE NULL,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            remarks TEXT NULL,
            INDEX idx_gpi_pass_id (gate_pass_id),
            INDEX idx_gpi_item_id (item_id),
            INDEX idx_gpi_transaction_id (transaction_id),
            CONSTRAINT fk_gpi_pass FOREIGN KEY (gate_pass_id) REFERENCES gate_passes(id) ON DELETE CASCADE,
            CONSTRAINT fk_gpi_item FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE SET NULL,
            CONSTRAINT fk_gpi_tx FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'inspection_reports' => "
        CREATE TABLE IF NOT EXISTS inspection_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            iar_no VARCHAR(32) NOT NULL UNIQUE,
            entity_name VARCHAR(64) NOT NULL DEFAULT 'SLSU-ALABAT',
            fund_cluster VARCHAR(64) NOT NULL DEFAULT 'PRE-STF',
            supplier_name VARCHAR(191) NOT NULL,
            po_no VARCHAR(64) NOT NULL,
            po_date DATE NOT NULL,
            invoice_no VARCHAR(64) NOT NULL,
            invoice_date DATE NOT NULL,
            office_dept VARCHAR(128) NOT NULL,
            rcc VARCHAR(64) NULL,
            date_inspected DATE NOT NULL,
            date_received DATE NOT NULL,
            inspection_officer VARCHAR(191) NOT NULL DEFAULT 'MANUEL S. VALDERAMA, MIT',
            property_officer VARCHAR(191) NOT NULL DEFAULT 'VERONICA C. OBIEN',
            is_inspected_order TINYINT(1) NOT NULL DEFAULT 1,
            acceptance_type ENUM('COMPLETE', 'PARTIAL') NOT NULL DEFAULT 'COMPLETE',
            partial_remarks TEXT NULL,
            scanned_document_path VARCHAR(255) NULL,
            status ENUM('PENDING_SIGNATURES', 'TRANSMITTED', 'REGISTERED', 'CANCELLED') NOT NULL DEFAULT 'PENDING_SIGNATURES',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_iar_no (iar_no),
            INDEX idx_iar_status (status),
            INDEX idx_iar_supplier (supplier_name),
            INDEX idx_iar_creator (created_by),
            CONSTRAINT fk_iar_creator FOREIGN KEY (created_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'inspection_report_items' => "
        CREATE TABLE IF NOT EXISTS inspection_report_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            iar_id INT NOT NULL,
            item_no INT NOT NULL,
            item_description TEXT NOT NULL,
            unit VARCHAR(32) NOT NULL DEFAULT 'units',
            quantity INT NOT NULL DEFAULT 1,
            unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            model VARCHAR(128) NULL,
            serial_no VARCHAR(128) NULL,
            is_registered TINYINT(1) NOT NULL DEFAULT 0,
            registered_item_id INT NULL,
            INDEX idx_iari_report (iar_id),
            INDEX idx_iari_registered (is_registered),
            INDEX idx_iari_item (registered_item_id),
            CONSTRAINT fk_iari_report FOREIGN KEY (iar_id) REFERENCES inspection_reports(id) ON DELETE CASCADE,
            CONSTRAINT fk_iari_item FOREIGN KEY (registered_item_id) REFERENCES items(item_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'property_movements' => "
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
    ",
    'lost_properties' => "
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
    ",
    'system_events' => "
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
    ",
];

foreach ($tables as $tName => $tSql) {
    try {
        $db->exec($tSql);
        echo "  [+] Verified table '$tName'\n";
    } catch (\PDOException $e) {
        echo "  [!] Error on table '$tName': " . $e->getMessage() . "\n";
    }
}

// 3.5 Backfill existing receipts into damage_incident_payments and calculate balances
try {
    $db->exec("
        INSERT INTO damage_incident_payments (
            incident_id, borrower_id, receipt_number, amount_paid, payment_date, 
            clearance_document_path, student_notes, payment_status, rejection_reason, 
            verified_by, verified_at, created_at
        )
        SELECT 
            di.incident_id, 
            di.borrower_id, 
            di.receipt_number, 
            COALESCE(di.amount_paid, di.estimated_cost, 0.00), 
            COALESCE(di.payment_date, CURDATE()), 
            di.clearance_document_path, 
            di.student_notes,
            CASE 
                WHEN di.incident_status = 'cleared' THEN 'verified'
                WHEN di.incident_status = 'rejected' THEN 'rejected'
                ELSE 'pending'
            END,
            di.rejection_reason,
            di.cleared_by,
            di.cleared_at,
            COALESCE(di.receipt_submitted_at, di.created_at)
        FROM damage_incidents di
        LEFT JOIN damage_incident_payments dip ON di.incident_id = dip.incident_id
        WHERE di.receipt_number IS NOT NULL 
          AND di.clearance_document_path IS NOT NULL
          AND dip.payment_id IS NULL
    ");

    $db->exec("
        UPDATE damage_incidents
        SET remaining_balance = CASE
            WHEN incident_status = 'cleared' THEN 0.00
            WHEN estimated_cost IS NOT NULL THEN GREATEST(0.00, estimated_cost - COALESCE(amount_paid, 0.00))
            ELSE NULL
        END
    ");

    $db->exec("
        UPDATE damage_incidents
        SET incident_status = 'partially_paid'
        WHERE incident_status NOT IN ('cleared', 'pending_verification')
          AND amount_paid IS NOT NULL 
          AND amount_paid > 0
          AND estimated_cost IS NOT NULL
          AND amount_paid < estimated_cost
    ");

    echo "  [+] Balance tracking initialized & existing payment receipts backfilled.\n";
} catch (\PDOException $e) {
    echo "  [.] Notice on payments backfill: " . $e->getMessage() . "\n";
}

// 4. Verify Final State
echo "\n[4/6] Verifying all database tables...\n";
$stmt = $db->query("SHOW TABLES;");
$dbTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
foreach ($dbTables as $t) {
    echo "  - {$t}\n";
}

// 5. Verify & Scaffold Upload Storage Directories
echo "\n[5/6] Verifying upload storage directories...\n";
$uploadDirs = [
    ROOT_PATH . '/public/uploads',
    ROOT_PATH . '/public/uploads/avatars',
    ROOT_PATH . '/public/uploads/clearances',
    ROOT_PATH . '/public/uploads/iar',
];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0755, true)) {
            echo "  [+] Created directory: " . basename($dir) . "\n";
        } else {
            echo "  [!] Could not create directory: " . basename($dir) . "\n";
        }
    } else {
        echo "  [.] Verified directory: " . basename($dir) . "\n";
    }
}

// 6. Normalize Legacy Academic Program Strings
echo "\n[6/6] Normalizing user academic program codes...\n";
try {
    $stmtIt = $db->prepare("UPDATE users SET department_program = 'BSIT' WHERE department_program IN ('BS Information Technology', 'BS in Information Technology', 'BS Info Tech')");
    $stmtIt->execute();
    $affectedIt = $stmtIt->rowCount();

    $stmtFas = $db->prepare("UPDATE users SET department_program = 'BSFAS' WHERE department_program IN ('BS Food & Agro-Industrial Studies', 'BS Food and Agro-Industrial Studies', 'BS Food & Agro', 'BSFAIS')");
    $stmtFas->execute();
    $affectedFas = $stmtFas->rowCount();

    echo "  [+] Academic program normalization complete (Updated: {$affectedIt} BSIT, {$affectedFas} BSFAS).\n";
} catch (\PDOException $e) {
    echo "  [!] Notice on academic program normalization: " . $e->getMessage() . "\n";
}

echo "\n[+] Incremental migrations, program normalization & storage check completed successfully!\n";
echo "========================================================\n\n";

