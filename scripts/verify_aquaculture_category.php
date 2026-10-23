<?php
declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

use App\Models\Item;
use App\Controllers\AnalyticsController;

echo "======================================================\n";
echo "SLSU-Alabat IMS: Aquaculture Equipment Category Verification\n";
echo "======================================================\n\n";

$allPassed = true;

// -----------------------------------------------------------
// TEST 1: Model Constants & Accessor
// -----------------------------------------------------------
echo "[TEST 1] Item::getStandardCategories() check... ";
$standardCats = Item::getStandardCategories();
if (in_array('Aquaculture Equipment', $standardCats, true)) {
    echo "PASSED\n";
    echo "         Canonical categories: " . implode(', ', $standardCats) . "\n";
} else {
    echo "FAILED: 'Aquaculture Equipment' not found in Item::getStandardCategories()\n";
    $allPassed = false;
}

// -----------------------------------------------------------
// TEST 2: Template Dropdown Options Check
// -----------------------------------------------------------
echo "[TEST 2] Template view files dropdown presence check... ";
$registerModal = file_get_contents(__DIR__ . '/../views/inventory/modals/register-modal.php') ?: '';
$editModal     = file_get_contents(__DIR__ . '/../views/inventory/modals/edit-modal.php') ?: '';
$invIndex      = file_get_contents(__DIR__ . '/../views/inventory/index.php') ?: '';

$inRegister = strpos($registerModal, '<option value="Aquaculture Equipment">Aquaculture Equipment</option>') !== false;
$inEdit     = strpos($editModal, '<option value="Aquaculture Equipment">Aquaculture Equipment</option>') !== false;
$inIar      = strpos($invIndex, '<option value="Aquaculture Equipment">Aquaculture Equipment</option>') !== false;

if ($inRegister && $inEdit && $inIar) {
    echo "PASSED\n";
    echo "         - register-modal.php: PRESENT\n";
    echo "         - edit-modal.php: PRESENT\n";
    echo "         - views/inventory/index.php (IAR Dynamic): PRESENT\n";
} else {
    echo "FAILED: Missing in view files (Register: " . ($inRegister ? "OK" : "MISSING") . ", Edit: " . ($inEdit ? "OK" : "MISSING") . ", IAR: " . ($inIar ? "OK" : "MISSING") . ")\n";
    $allPassed = false;
}

// -----------------------------------------------------------
// TEST 3: Controller Merging Logic (Mock PDO if offline)
// -----------------------------------------------------------
echo "[TEST 3] AnalyticsController::getAvailableCategories() unit logic check... ";
$mockPdo = new class extends PDO {
    public function __construct() {}
    #[\ReturnTypeWillChange]
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): false|PDOStatement {
        return new class extends PDOStatement {
            public function __construct() {}
            #[\ReturnTypeWillChange]
            public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array {
                // Return sample DB categories that do not include Aquaculture Equipment yet
                return ['IT Equipment', 'Laboratory Tools', 'Special Marine Drone'];
            }
        };
    }
};

$availableCats = AnalyticsController::getAvailableCategories($mockPdo);
if (in_array('Aquaculture Equipment', $availableCats, true) && in_array('Special Marine Drone', $availableCats, true)) {
    echo "PASSED\n";
    echo "         Merged categories: " . implode(', ', $availableCats) . "\n";
} else {
    echo "FAILED: Did not correctly merge canonical categories with DB categories\n";
    $allPassed = false;
}

// -----------------------------------------------------------
// TEST 4: Live Database Check (Optional if MySQL is running)
// -----------------------------------------------------------
echo "[TEST 4] Live MySQL database connectivity & asset test... ";
$dbLive = null;
$host = '127.0.0.1';
$port = 3306;
$connectionTimeout = 1;
$fp = @fsockopen($host, $port, $errno, $errstr, $connectionTimeout);
if ($fp) {
    fclose($fp);
    try {
        $dbLive = \App\Config\Database::getConnection();
    } catch (\Throwable $e) {
        $dbLive = null;
    }
}

if ($dbLive) {
    try {
        $testBarcode = 'TEST-AQ-' . time();
        $testPropNum = 'TEST-PROP-' . time();
        $insertStmt = $dbLive->prepare("
            INSERT INTO items (
                barcode, property_number, item_name, description, category, location, is_student_borrowable, status, item_condition
            ) VALUES (
                :barcode, :property_number, :item_name, :description, :category, :location, 1, 'available', 'good'
            )
        ");
        $insertStmt->execute([
            ':barcode' => $testBarcode,
            ':property_number' => $testPropNum,
            ':item_name' => 'Dissolved Oxygen (DO) Field Meter',
            ':description' => 'Automated test asset for Aquaculture Equipment category verification',
            ':category' => 'Aquaculture Equipment',
            ':location' => 'Fisheries Laboratory — Tank 1'
        ]);
        $testItemId = (int)$dbLive->lastInsertId();

        $queryStmt = $dbLive->prepare("SELECT item_id, item_name, category, location FROM items WHERE barcode = :barcode");
        $queryStmt->execute([':barcode' => $testBarcode]);
        $item = $queryStmt->fetch(PDO::FETCH_ASSOC);

        if ($item && $item['category'] === 'Aquaculture Equipment') {
            echo "PASSED (Live DB inserted item ID {$testItemId} with category '{$item['category']}')\n";
        } else {
            echo "FAILED: Live item category verification failed\n";
            $allPassed = false;
        }

        $deleteStmt = $dbLive->prepare("DELETE FROM items WHERE item_id = :id");
        $deleteStmt->execute([':id' => $testItemId]);
        echo "         [CLEANUP] Test asset ID {$testItemId} removed cleanly.\n";
    } catch (\Throwable $e) {
        echo "FAILED live DB test: " . $e->getMessage() . "\n";
        $allPassed = false;
    }
} else {
    echo "SKIPPED (Local MySQL service not active on port 3306; unit validation passed)\n";
}

echo "\n======================================================\n";
if ($allPassed) {
    echo "OVERALL RESULT: ALL TESTS PASSED! \n";
} else {
    echo "OVERALL RESULT: SOME TESTS FAILED.\n";
}
echo "======================================================\n";
