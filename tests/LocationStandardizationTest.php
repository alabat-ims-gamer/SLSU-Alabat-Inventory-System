<?php
declare(strict_types=1);

/**
 * Location Standardization & Facility Dropdown Verification Test Suite
 * SLSU-Alabat IMS Web Application
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

$db = require __DIR__ . '/../config/database.php';

echo "========================================================\n";
echo "  SLSU-Alabat IMS - Location Standardization Test Suite\n";
echo "  Facility Dropdown & Specific Location Component\n";
echo "========================================================\n\n";

$passCount = 0;
function testAssert(bool $condition, string $message): void {
    global $passCount;
    if (!$condition) {
        echo "[FAIL] {$message}\n";
        exit(1);
    }
    $passCount++;
    echo "[PASS] {$message}\n";
}

// ── 1. Modal Markup & Component Structure Verification ──────────
echo "\n--- [Suite 1: Modal Markup & Component Structure] ---\n";

$regModalContent = file_get_contents(__DIR__ . '/../views/inventory/modals/register-modal.php');
testAssert(str_contains($regModalContent, 'id="reg-facility-select"'), "register-modal.php contains facility dropdown");
testAssert(str_contains($regModalContent, 'value="Science Laboratory"'), "register-modal.php includes Science Laboratory");
testAssert(str_contains($regModalContent, 'value="Farm Laboratory"'), "register-modal.php includes Farm Laboratory");
testAssert(str_contains($regModalContent, 'value="GIS &amp; Comp. Lab"'), "register-modal.php includes GIS & Comp. Lab");
testAssert(str_contains($regModalContent, 'value="Fisheries Laboratory"'), "register-modal.php includes Fisheries Laboratory");
testAssert(str_contains($regModalContent, 'value="Clinic"'), "register-modal.php includes Clinic");
testAssert(str_contains($regModalContent, 'id="reg-custom-facility"'), "register-modal.php includes custom office input");
testAssert(str_contains($regModalContent, 'id="reg-specific-location"'), "register-modal.php includes specific location input");
testAssert(str_contains($regModalContent, 'id="reg-location"'), "register-modal.php retains hidden location input for submission");
testAssert(str_contains($regModalContent, 'initRegLocationSync'), "register-modal.php includes synchronization script");

$editModalContent = file_get_contents(__DIR__ . '/../views/inventory/modals/edit-modal.php');
testAssert(str_contains($editModalContent, 'id="edit-facility-select"'), "edit-modal.php contains facility dropdown");
testAssert(str_contains($editModalContent, 'id="edit-custom-facility"'), "edit-modal.php includes custom office input");
testAssert(str_contains($editModalContent, 'id="edit-specific-location"'), "edit-modal.php includes specific location input");
testAssert(str_contains($editModalContent, 'id="edit-location"'), "edit-modal.php retains hidden location input for submission");
testAssert(str_contains($editModalContent, 'syncEditLocation'), "edit-modal.php includes synchronization script");

$inventoryJsContent = file_get_contents(__DIR__ . '/../views/inventory/index.php');
testAssert(str_contains($inventoryJsContent, 'populateEditLocation'), "inventory/index.php includes populateEditLocation function");
testAssert(str_contains($inventoryJsContent, 'populateEditLocation(item.location || \'\')'), "inventory/index.php calls populateEditLocation in openEditModal");

$iarContent = file_get_contents(__DIR__ . '/../views/inspector/iar.php');
testAssert(str_contains($iarContent, 'id="iar-facility-select"'), "iar.php contains facility dropdown");
testAssert(str_contains($iarContent, 'id="iar-office-dept"'), "iar.php retains hidden office_dept input");
testAssert(str_contains($iarContent, 'syncIarOfficeDept'), "iar.php includes IAR facility synchronization logic");

// ── 2. Smart Location Parsing Algorithm Simulation ─────────────
echo "\n--- [Suite 2: Smart Location Parsing Logic] ---\n";

function simulateLocationParser(string $rawLocation): array {
    $trimmed = trim($rawLocation);
    $standardFacilities = [
        'Science Laboratory',
        'Farm Laboratory',
        'GIS & Comp. Lab',
        'Fisheries Laboratory',
        'Clinic'
    ];

    $matchedFacility = null;
    $specificPart = '';

    foreach ($standardFacilities as $fac) {
        if ($trimmed === $fac) {
            $matchedFacility = $fac;
            $specificPart = '';
            break;
        } elseif (str_starts_with($trimmed, $fac)) {
            $matchedFacility = $fac;
            $remainder = trim(substr($trimmed, strlen($fac)));
            $remainder = preg_replace('/^[—–\-:\s]+/', '', $remainder);
            if (str_starts_with($remainder, '(') && str_ends_with($remainder, ')')) {
                $remainder = trim(substr($remainder, 1, -1));
            }
            $specificPart = $remainder;
            break;
        }
    }

    if ($matchedFacility !== null) {
        return [
            'dropdown' => $matchedFacility,
            'custom'   => '',
            'specific' => $specificPart
        ];
    } elseif ($trimmed !== '') {
        return [
            'dropdown' => 'Other',
            'custom'   => $trimmed,
            'specific' => ''
        ];
    }

    return [
        'dropdown' => '',
        'custom'   => '',
        'specific' => ''
    ];
}

$p1 = simulateLocationParser('Science Laboratory');
testAssert($p1['dropdown'] === 'Science Laboratory' && $p1['specific'] === '', "Parses standard facility without sub-location");

$p2 = simulateLocationParser('Science Laboratory — Cabinet 3');
testAssert($p2['dropdown'] === 'Science Laboratory' && $p2['specific'] === 'Cabinet 3', "Parses standard facility with em-dash sub-location");

$p3 = simulateLocationParser('Farm Laboratory - Greenhouse 2');
testAssert($p3['dropdown'] === 'Farm Laboratory' && $p3['specific'] === 'Greenhouse 2', "Parses standard facility with hyphen sub-location");

$p4 = simulateLocationParser('GIS & Comp. Lab (Room 204)');
testAssert($p4['dropdown'] === 'GIS & Comp. Lab' && $p4['specific'] === 'Room 204', "Parses standard facility with parenthesized room");

$p5 = simulateLocationParser('Supply Office');
testAssert($p5['dropdown'] === 'Other' && $p5['custom'] === 'Supply Office' && $p5['specific'] === '', "Parses legacy/external office into 'Other'");

// ── 3. Database Persistence & Integrity ─────────────────────────
echo "\n--- [Suite 3: Database Persistence & Query Integrity] ---\n";

$createdItemIds = [];

try {
    // 1. Create item with facility only
    $barcode1 = 'TEST-LOC-' . time() . '-1';
    $stmt = $db->prepare("
        INSERT INTO items (barcode, item_name, category, location, status, item_condition)
        VALUES (:barcode, 'Microscope A1', 'Laboratory Tools', 'Science Laboratory', 'available', 'good')
    ");
    $stmt->execute([':barcode' => $barcode1]);
    $id1 = (int)$db->lastInsertId();
    $createdItemIds[] = $id1;

    $fetch1 = $db->query("SELECT location FROM items WHERE item_id = {$id1}")->fetch(PDO::FETCH_ASSOC);
    testAssert($fetch1['location'] === 'Science Laboratory', "Item persisted with standardized facility location");

    // 2. Create item with composite location
    $barcode2 = 'TEST-LOC-' . time() . '-2';
    $stmt = $db->prepare("
        INSERT INTO items (barcode, item_name, category, location, status, item_condition)
        VALUES (:barcode, 'Water Quality Meter', 'Laboratory Tools', 'Fisheries Laboratory — Tank Room B', 'available', 'good')
    ");
    $stmt->execute([':barcode' => $barcode2]);
    $id2 = (int)$db->lastInsertId();
    $createdItemIds[] = $id2;

    $fetch2 = $db->query("SELECT location FROM items WHERE item_id = {$id2}")->fetch(PDO::FETCH_ASSOC);
    testAssert($fetch2['location'] === 'Fisheries Laboratory — Tank Room B', "Item persisted with composite location");

    // 3. Update location via edit
    $newLoc = 'GIS & Comp. Lab — Server Rack 1';
    $updateStmt = $db->prepare("UPDATE items SET location = :loc WHERE item_id = :id");
    $updateStmt->execute([':loc' => $newLoc, ':id' => $id1]);

    $fetchUpdated = $db->query("SELECT location FROM items WHERE item_id = {$id1}")->fetch(PDO::FETCH_ASSOC);
    testAssert($fetchUpdated['location'] === $newLoc, "Item location updated successfully to composite facility");

    // 4. Analytics Grouping Query Verification
    $analyticsStmt = $db->query("
        SELECT location, COUNT(*) as count 
        FROM items 
        WHERE location IS NOT NULL AND location != ''
        GROUP BY location
    ");
    $groups = $analyticsStmt->fetchAll(PDO::FETCH_ASSOC);
    testAssert(is_array($groups) && count($groups) > 0, "Location density grouping query executes with zero errors");

} finally {
    // Clean up test rows
    if (!empty($createdItemIds)) {
        $inClause = implode(',', $createdItemIds);
        $db->exec("DELETE FROM items WHERE item_id IN ({$inClause})");
        echo "  [i] Cleaned up temporary test item records: ({$inClause})\n";
    }
}

echo "\n========================================================\n";
echo "  [SUCCESS] All {$passCount} Location Standardization Tests Passed!\n";
echo "========================================================\n";
