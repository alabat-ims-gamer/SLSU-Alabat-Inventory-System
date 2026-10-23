<?php
declare(strict_types=1);

/**
 * SLSU-Alabat Inventory Management System
 * Asset Catalog Purge Utility
 *
 * Safely removes all existing assets from the asset catalog (`items` table)
 * and cascades/cleans related transaction records.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

/** @var \PDO $db */
$db = require ROOT_PATH . '/config/database.php';

echo "====================================================\n";
echo "   SLSU-Alabat IMS - Asset Catalog Purge Script   \n";
echo "====================================================\n\n";

try {
    // 1. Get pre-purge statistics
    $itemCount = (int)$db->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $txCount = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

    echo "[*] Found {$itemCount} item(s) in catalog and {$txCount} transaction record(s).\n";

    if ($itemCount === 0 && $txCount === 0) {
        echo "[i] Asset catalog is already completely empty. No action required.\n";
        exit(0);
    }

    // Disable foreign key checks for clean truncation / reset of auto-increment
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    $db->exec("TRUNCATE TABLE transactions;");
    $db->exec("TRUNCATE TABLE disposal_records;");
    $db->exec("TRUNCATE TABLE items;");
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 3. Log audit record
    \App\Models\AuditLog::log(
        $db,
        null,
        'ASSET_CATALOG_PURGED',
        "Purged all existing items ({$itemCount} assets) and associated transactions ({$txCount} records) via CLI maintenance script."
    );

    echo "[✓] Successfully purged all {$itemCount} asset(s) and {$txCount} transaction(s).\n";
    echo "[✓] Auto-increment counters for 'items' and 'transactions' have been reset.\n";
    echo "[✓] Audit log entry generated.\n\n";
    echo "Asset catalog is now fresh and ready for new entries.\n";

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n[!] Error during purge: " . $e->getMessage() . "\n";
    exit(1);
}
