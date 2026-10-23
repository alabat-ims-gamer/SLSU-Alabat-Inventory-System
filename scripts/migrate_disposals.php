<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

$db = require ROOT_PATH . '/config/database.php';

echo "Running Disposals Migration...\n";

try {
    $sql = "CREATE TABLE IF NOT EXISTS disposal_records (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo " [+] Created or verified 'disposal_records' table.\n";
} catch (\PDOException $e) {
    echo " [!] Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Disposals migration completed successfully.\n";
