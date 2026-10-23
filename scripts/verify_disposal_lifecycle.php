<?php
/**
 * Automated Verification Test Suite — Asset Disposal & Condemnation Module
 * Validates ISO/IEC 25010 Functional Suitability, OWASP Security, and State Transitions.
 */

declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

$db = require ROOT_PATH . '/config/database.php';

echo "\n==========================================================\n";
echo "  SLSU-Alabat IMS: Asset Disposal Lifecycle Test Suite\n";
echo "==========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $title, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$title}\n";
        if ($detail !== '') {
            echo "         ↳ {$detail}\n";
        }
    } else {
        $failCount++;
        echo "  [FAIL] {$title}\n";
        if ($detail !== '') {
            echo "         ↳ {$detail}\n";
        }
    }
}

// ── Setup: Find or Create Custodian & Faculty Users ─────────────
$custodianUser = $db->query("SELECT * FROM users WHERE role = 'custodian' AND account_status = 'approved' LIMIT 1")->fetch();
if (!$custodianUser) {
    $db->exec("INSERT INTO users (id_number, username, full_name, email, password_hash, role, account_status)
               VALUES ('TEST-CUST-001', 'test_custodian', 'Test Custodian', 'custodian_test@slsu.edu.ph', 'hash', 'custodian', 'approved')");
    $custodianId = (int)$db->lastInsertId();
} else {
    $custodianId = (int)$custodianUser['user_id'];
}

// ── TEST 1: Staging Active Asset for Disposal ───────────────────
echo "--- Test Suite 1: Asset Staging Lifecycle ---\n";
$barcode1 = 'BC-TEST-DISP-01';
$propNum1 = 'PROP-TEST-DISP-01';

// Clean up if previous run left test item
$db->exec("DELETE FROM items WHERE barcode IN ('{$barcode1}', 'BC-TEST-DISP-02', 'BC-TEST-DISP-03', 'BC-TEST-DISP-04', 'BC-TEST-DISP-05')");

$db->exec("
    INSERT INTO items (barcode, property_number, item_name, category, location, is_student_borrowable, registered_by, status, item_condition, created_at)
    VALUES ('{$barcode1}', '{$propNum1}', 'Test Oscilloscope Unit A', 'Laboratory Tools', 'EE Lab', 1, {$custodianId}, 'available', 'good', NOW())
");
$itemId1 = (int)$db->lastInsertId();

$stageRes = \App\Models\Item::stageForDisposal($db, $itemId1, 'Burned internal transformer beyond repair', $custodianId);
$updatedItem1 = \App\Models\Item::findById($db, $itemId1);

assertTest(
    "TC-DISP-01: Asset staged for disposal",
    $stageRes['success'] === true && $updatedItem1['status'] === 'for_disposal' && $updatedItem1['item_condition'] === 'for_disposal',
    "Status updated to 'for_disposal', condition set to 'for_disposal'"
);

// ── TEST 2: Active Borrow Lockout Enforcement ───────────────────
echo "\n--- Test Suite 2: Active Loan Lockout Enforcement ---\n";
$barcode2 = 'BC-TEST-DISP-02';
$propNum2 = 'PROP-TEST-DISP-02';
$db->exec("
    INSERT INTO items (barcode, property_number, item_name, category, location, is_student_borrowable, registered_by, status, item_condition, created_at)
    VALUES ('{$barcode2}', '{$propNum2}', 'Test Laptop On Loan', 'IT Equipment', 'Faculty Room', 1, {$custodianId}, 'borrowed', 'good', NOW())
");
$itemId2 = (int)$db->lastInsertId();

$lockoutStagingPassed = false;
try {
    \App\Models\Item::stageForDisposal($db, $itemId2, 'Attempting to condemn borrowed laptop', $custodianId);
} catch (\RuntimeException $e) {
    $lockoutStagingPassed = (strpos($e->getMessage(), 'borrowed') !== false);
}

assertTest(
    "TC-DISP-02A: Staging blocked when item is actively borrowed",
    $lockoutStagingPassed,
    "Strict server-side exception thrown preventing staging of checked-out equipment"
);

$lockoutDecomPassed = false;
try {
    \App\Models\Item::decommissionItem($db, $itemId2, ['disposal_method' => 'scrapped', 'disposal_reason' => 'Test', 'salvage_value' => 0], $custodianId);
} catch (\RuntimeException $e) {
    $lockoutDecomPassed = (strpos($e->getMessage(), 'borrowed') !== false);
}

assertTest(
    "TC-DISP-02B: Decommissioning blocked when item is actively borrowed",
    $lockoutDecomPassed,
    "Strict server-side exception thrown preventing decommissioning of checked-out equipment"
);

// ── TEST 3: Single Decommissioning & Ledger Persistence ─────────
echo "\n--- Test Suite 3: Single Asset Decommissioning & Ledger Recording ---\n";
$decomRes = \App\Models\Item::decommissionItem($db, $itemId1, [
    'disposal_method' => 'scrapped',
    'disposal_reason' => 'Transformer burned, parts obsolete',
    'salvage_value'   => 450.00,
    'disposal_date'   => date('Y-m-d'),
    'remarks'         => 'Certified unserviceable by supply inspector'
], $custodianId);

$decomItem1 = \App\Models\Item::findById($db, $itemId1);
$dispRecord1 = \App\Models\DisposalRecord::findByItemId($db, $itemId1);

$txnCheck = $db->query("
    SELECT COUNT(*) FROM transactions 
    WHERE item_id = {$itemId1} AND transaction_type = 'disposal'
")->fetchColumn();

assertTest(
    "TC-DISP-03: Asset officially decommissioned and recorded in ledger",
    $decomRes['success'] === true &&
    $decomItem1['status'] === 'disposed' &&
    $dispRecord1 !== null &&
    $dispRecord1['disposal_method'] === 'scrapped' &&
    (float)$dispRecord1['salvage_value'] === 450.00 &&
    (int)$txnCheck > 0,
    "Item status='disposed', disposal_record logged with method=scrapped and salvage=₱450.00, transaction log created"
);

// ── TEST 4: Batch Decommissioning in Atomic Transaction ─────────
echo "\n--- Test Suite 4: Batch Asset Decommissioning ---\n";
$barcode3 = 'BC-TEST-DISP-03';
$barcode4 = 'BC-TEST-DISP-04';
$db->exec("
    INSERT INTO items (barcode, property_number, item_name, category, location, is_student_borrowable, registered_by, status, item_condition, created_at)
    VALUES 
    ('{$barcode3}', 'PROP-TEST-DISP-03', 'Student Chair Batch A', 'Furniture & Fixtures', 'Room 101', 0, {$custodianId}, 'for_disposal', 'for_disposal', NOW()),
    ('{$barcode4}', 'PROP-TEST-DISP-04', 'Student Chair Batch B', 'Furniture & Fixtures', 'Room 101', 0, {$custodianId}, 'for_disposal', 'for_disposal', NOW())
");

$itemId3 = (int)$db->query("SELECT item_id FROM items WHERE barcode = '{$barcode3}'")->fetchColumn();
$itemId4 = (int)$db->query("SELECT item_id FROM items WHERE barcode = '{$barcode4}'")->fetchColumn();

$batchRes = \App\Models\Item::batchDecommission($db, [$itemId3, $itemId4], [
    'disposal_method' => 'donated',
    'disposal_reason' => 'Donated to community learning center',
    'salvage_value'   => 0.00,
    'disposal_date'   => date('Y-m-d'),
    'remarks'         => 'Turned over via official memorandum'
], $custodianId);

$item3Check = \App\Models\Item::findById($db, $itemId3);
$item4Check = \App\Models\Item::findById($db, $itemId4);

assertTest(
    "TC-DISP-04: Batch decommissioning of multiple assets in atomic transaction",
    $batchRes['success'] === true &&
    $batchRes['count'] === 2 &&
    $item3Check['status'] === 'disposed' &&
    $item4Check['status'] === 'disposed',
    "Both units transitioned to 'disposed' in single transaction"
);

// ── TEST 5: Safety Reinstatement / Restoration ──────────────────
echo "\n--- Test Suite 5: Safety Restoration Workflow ---\n";

// 5A: Restoring an already decommissioned asset MUST be blocked
$blockedDecomRestore = false;
try {
    \App\Models\Item::restoreFromDisposal($db, $itemId3, 'available', 'Attempting to restore decommissioned item', $custodianId);
} catch (\RuntimeException $e) {
    $blockedDecomRestore = (strpos($e->getMessage(), 'permanently archived') !== false || strpos($e->getMessage(), 'cannot be reinstated') !== false);
}

assertTest(
    "TC-DISP-05A: Restoring officially decommissioned asset is strictly blocked",
    $blockedDecomRestore,
    "Strict server-side exception thrown preventing reinstatement of archived/disposed assets"
);

// 5B: Restoring a staged asset from the staging queue MUST succeed
$barcodeStaged = 'BC-TEST-DISP-STG';
$db->exec("
    INSERT INTO items (barcode, property_number, item_name, category, location, is_student_borrowable, registered_by, status, item_condition, created_at)
    VALUES ('{$barcodeStaged}', 'PROP-TEST-DISP-STG', 'Test Staged Unit for Reinstatement', 'General', 'Supply Room', 1, {$custodianId}, 'for_disposal', 'for_disposal', NOW())
");
$itemIdStaged = (int)$db->lastInsertId();

$restoreRes = \App\Models\Item::restoreFromDisposal($db, $itemIdStaged, 'available', 'Refurbished with replacement capacitors', $custodianId);
$restoredItemStaged = \App\Models\Item::findById($db, $itemIdStaged);

assertTest(
    "TC-DISP-05B: Asset restored from Disposal Staging Queue back to active inventory",
    $restoreRes['success'] === true &&
    $restoredItemStaged['status'] === 'available' &&
    $restoredItemStaged['item_condition'] === 'good',
    "Status returned to 'available', condition set to 'good' from staging queue"
);

// ── TEST 6: Disposal Statistics & KPI Aggregations ──────────────
echo "\n--- Test Suite 6: Disposal Statistics & KPI Verification ---\n";
$stats = \App\Models\DisposalRecord::getDisposalStats($db);

assertTest(
    "TC-DISP-06: Disposal KPI aggregation metrics return valid values",
    is_int($stats['staged_count']) &&
    is_int($stats['total_disposed']) &&
    is_float($stats['total_salvage_value']) &&
    $stats['total_disposed'] >= 2,
    "Staged: {$stats['staged_count']}, Total Disposed: {$stats['total_disposed']}, Salvage: ₱" . number_format($stats['total_salvage_value'], 2)
);

// ── TEST 7: Asset Deletion with Loan History Stages to Disposal Queue ───
echo "\n--- Test Suite 7: Deletion Safeguard & Catalog Declutter Verification ---\n";
$barcode5 = 'BC-TEST-DISP-05';
$db->exec("
    INSERT INTO items (barcode, property_number, item_name, category, location, is_student_borrowable, registered_by, status, item_condition, created_at)
    VALUES ('{$barcode5}', 'PROP-TEST-DISP-05', 'Test Projector with Loan History', 'AV Equipment', 'AV Room', 1, {$custodianId}, 'available', 'good', NOW())
");
$itemId5 = (int)$db->lastInsertId();

// Create fake transaction history
$db->exec("
    INSERT INTO transactions (item_id, user_id, transaction_type, transaction_date, actual_return_date, processed_by)
    VALUES ({$itemId5}, {$custodianId}, 'borrow', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY, {$custodianId})
");

$delResult = \App\Models\Item::deleteItem($db, $itemId5, 'Projector burned bulb and casing cracked', $custodianId);
$stagedItem5 = \App\Models\Item::findById($db, $itemId5);

// Check catalog pagination exclusion
$catalogCheck = \App\Models\Item::getPaginated($db, 1, 50, 'Test Projector with Loan History');

assertTest(
    "TC-DISP-07: Deleting asset with loan history queues to Disposal Staging and is excluded from catalog",
    $delResult['type'] === 'staged_for_disposal' &&
    $stagedItem5['status'] === 'for_disposal' &&
    $stagedItem5['item_condition'] === 'for_disposal' &&
    $catalogCheck['total'] === 0,
    "Item status='for_disposal', condition='for_disposal' (Condemned), excluded from active catalog query"
);

// ── TEST 8: Registration Condition Restrictions ────────────────
echo "\n--- Test Suite 8: Registration Physical Condition Validation ---\n";
$blockedNotWorking = false;
try {
    \App\Models\Item::createBatch($db, [
        'item_name'       => 'Defective Tablet',
        'category'        => 'IT Equipment',
        'location'        => 'IT Dept',
        'item_condition'  => 'not_working',
    ], 1, $custodianId);
} catch (\InvalidArgumentException $e) {
    $blockedNotWorking = true;
}

$blockedCondemned = false;
try {
    \App\Models\Item::createBatch($db, [
        'item_name'       => 'Condemned Server',
        'category'        => 'IT Equipment',
        'location'        => 'Server Room',
        'item_condition'  => 'for_disposal',
    ], 1, $custodianId);
} catch (\InvalidArgumentException $e) {
    $blockedCondemned = true;
}

assertTest(
    "TC-DISP-08: Registering new asset with 'not_working' or 'for_disposal' is blocked",
    $blockedNotWorking && $blockedCondemned,
    "Strict server-side exception thrown preventing invalid health condition on new assets"
);

// ── TEST 9: Clean up test artifacts ─────────────────────────────
echo "\n--- Cleanup ---\n";
$db->exec("DELETE FROM transactions WHERE item_id IN ({$itemId1}, {$itemId2}, {$itemId3}, {$itemId4}, {$itemId5}, {$itemIdStaged})");
$db->exec("DELETE FROM items WHERE barcode IN ('{$barcode1}', '{$barcode2}', '{$barcode3}', '{$barcode4}', '{$barcode5}', '{$barcodeStaged}')");
echo "  [.] Cleaned up test items.\n";

echo "\n==========================================================\n";
echo "  TEST SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "==========================================================\n\n";

if ($failCount === 0) {
    echo ">>> ALL ASSET DISPOSAL LIFECYCLE TESTS PASSED (100% SUCCESS) <<<\n\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n\n";
    exit(1);
}
