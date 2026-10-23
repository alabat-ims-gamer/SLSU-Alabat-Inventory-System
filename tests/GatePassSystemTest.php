<?php
declare(strict_types=1);

/**
 * Gate Pass System Verification Test Suite
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

$config = require __DIR__ . '/../config/app.php';
$db = require __DIR__ . '/../config/database.php';

echo "========================================================\n";
echo "  SLSU-Alabat IMS - Gate Pass System Verification Suite\n";
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

// ── 1. Database Schema Verification ─────────────────────────────
echo "\n--- [Suite 1: Database Schema & Integrity] ---\n";

$tables = $db->query("SHOW TABLES LIKE 'gate_passes'")->fetchAll(PDO::FETCH_COLUMN);
testAssert(count($tables) === 1, "'gate_passes' table exists in database");

$itemTables = $db->query("SHOW TABLES LIKE 'gate_pass_items'")->fetchAll(PDO::FETCH_COLUMN);
testAssert(count($itemTables) === 1, "'gate_pass_items' table exists in database");

$cols = $db->query("SHOW COLUMNS FROM gate_passes")->fetchAll(PDO::FETCH_COLUMN);
$requiredPassCols = ['id', 'gate_pass_no', 'requisitioner_name', 'requisitioning_office', 'purpose', 'pass_type', 'status', 'created_by'];
foreach ($requiredPassCols as $rc) {
    testAssert(in_array($rc, $cols, true), "gate_passes contains column '{$rc}'");
}

$itemCols = $db->query("SHOW COLUMNS FROM gate_pass_items")->fetchAll(PDO::FETCH_COLUMN);
$requiredItemCols = ['id', 'gate_pass_id', 'item_id', 'transaction_id', 'item_description', 'model', 'serial_no', 'unit_price', 'total_amount'];
foreach ($requiredItemCols as $ric) {
    testAssert(in_array($ric, $itemCols, true), "gate_pass_items contains column '{$ric}'");
}

// ── 2. Official PDF Template & Local Script Verification ────────
echo "\n--- [Suite 2: Official Template & Stamping Assets] ---\n";

$templatePath = dirname(__DIR__) . '/public/assets/templates/GATE-PASS-PERMIT.pdf';
testAssert(file_exists($templatePath), "Official template file GATE-PASS-PERMIT.pdf exists in public/assets/templates");
testAssert(filesize($templatePath) > 100000, "Official template file is non-empty (" . filesize($templatePath) . " bytes)");

$pdfLibPath = dirname(__DIR__) . '/public/js/pdf-lib.min.js';
testAssert(file_exists($pdfLibPath), "Local pdf-lib.min.js bundle exists in public/js/");
testAssert(filesize($pdfLibPath) > 400000, "Local pdf-lib.min.js is valid standalone bundle (" . filesize($pdfLibPath) . " bytes)");

// ── 3. Numbering Sequence & Model Methods ───────────────────────
echo "\n--- [Suite 3: GatePass Model & Numbering Generator] ---\n";

$passNo = \App\Models\GatePass::generatePassNo($db);
echo "  [i] Generated Pass No: {$passNo}\n";
testAssert(preg_match('/^GP-\d{4}-\d{4}$/', $passNo) === 1, "Generated pass number matches format GP-YYYY-XXXX");

// ── 4. Hybrid Lookup Security & Safeguards ──────────────────────
echo "\n--- [Suite 4: Hybrid Barcode Lookup Guards] ---\n";

$nonExistent = \App\Models\GatePass::lookupByBarcode($db, 'NON_EXISTENT_TAG_99999');
testAssert(isset($nonExistent['error']) && $nonExistent['error'] === 'not_found', "Querying non-existent barcode returns 'not_found'");

// Look for an available (non-borrowed) item to test the safeguard
$availItem = $db->query("SELECT barcode FROM items WHERE status = 'available' LIMIT 1")->fetchColumn();
if ($availItem) {
    $nonBorrowed = \App\Models\GatePass::lookupByBarcode($db, (string)$availItem);
    testAssert(isset($nonBorrowed['error']) && $nonBorrowed['error'] === 'not_borrowed', "Querying available item correctly blocks gate pass with 'not_borrowed'");
} else {
    echo "  [.] Skipped available item test (no available item found in DB)\n";
}

// ── 5. Transactional Permit Creation & Snapshotting ─────────────
echo "\n--- [Suite 5: Transactional Permit Creation & Snapshotting] ---\n";

// Find or create test user for creator
$adminUser = $db->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
$creatorId = $adminUser ? (int)$adminUser : 1;

$testData = [
    'requisitioner_id'      => null,
    'requisitioner_name'    => 'Dr. Maria Santos',
    'requisitioning_office' => 'College of Fisheries (BSFAS)',
    'purpose'               => 'Coastal Water Quality Assessment in Perez, Quezon',
    'pass_type'             => \App\Models\GatePass::PASS_TYPE_BORROWED,
];

$testItems = [
    [
        'item_id'          => null,
        'transaction_id'   => null,
        'item_description' => 'Garmin GPSMAP 65s Handheld GPS Navigator',
        'model'            => 'GPSMAP 65s',
        'serial_no'        => '7A2091482',
        'date_acquired'    => '2024-05-10',
        'unit_price'       => 24500.00,
        'total_amount'     => 24500.00,
        'remarks'          => 'Unit calibrated, complete with lanyard and carrying pouch',
    ],
    [
        'item_id'          => null,
        'transaction_id'   => null,
        'item_description' => 'Horiba LAQUA twin Salinity Meter',
        'model'            => 'LAQUAtwin Salt-11',
        'serial_no'        => '88219401',
        'date_acquired'    => '2024-06-12',
        'unit_price'       => 18200.00,
        'total_amount'     => 18200.00,
        'remarks'          => 'Sensor in good condition, includes 2 calibration standard bottles',
    ]
];

$created = \App\Models\GatePass::create($db, $testData, $testItems, $creatorId);
testAssert(!empty($created['id']), "Gate pass record created with ID {$created['id']}");
testAssert(!empty($created['gate_pass_no']), "Gate pass issued with number {$created['gate_pass_no']}");

// Fetch and assert snapshot fidelity
$fetched = \App\Models\GatePass::getById($db, (int)$created['id']);
testAssert($fetched !== null, "Fetched created gate pass from database");
testAssert($fetched['requisitioner_name'] === 'Dr. Maria Santos', "Requisitioner name snapshot matches");
testAssert($fetched['requisitioning_office'] === 'College of Fisheries (BSFAS)', "Requisitioning office snapshot matches");
testAssert(count($fetched['items']) === 2, "Gate pass contains exactly 2 child items");
testAssert($fetched['items'][0]['model'] === 'GPSMAP 65s', "Item 1 model snapshot matches");
testAssert((float)$fetched['items'][0]['unit_price'] === 24500.00, "Item 1 valuation snapshot matches");

// Assert List Registry
$list = \App\Models\GatePass::listAll($db, $created['gate_pass_no']);
testAssert($list['total'] >= 1, "listAll finds issued permit by pass number");
testAssert((int)$list['passes'][0]['item_count'] === 2, "listAll aggregates correct item count");
testAssert((float)$list['passes'][0]['total_valuation'] === 42700.00, "listAll aggregates correct total valuation (42,700.00)");

// Clean up test permit
$db->exec("DELETE FROM gate_passes WHERE id = " . (int)$created['id']);
echo "  [i] Cleaned up temporary test gate pass ID {$created['id']}\n";

// ── 6. UI Shell & Routing Consistency ───────────────────────────
echo "\n--- [Suite 6: Routing & Role Labels] ---\n";

ob_start();
$_SESSION['role'] = 'inspector';
$_SESSION['user_name'] = 'Test Inspector';
require_once dirname(__DIR__) . '/views/layouts/shell.php';
ob_end_clean();

testAssert(isset($roleLabels['inspector']), "Role label for 'inspector' exists in shell.php");
testAssert($roleLabels['inspector'] === 'Local Inspector', "Role label is 'Local Inspector'");
testAssert(isset($menus['inspector']), "Sidebar menu for 'inspector' exists in shell.php");

$inspectorMenuLabels = array_column($menus['inspector'], 'label');
testAssert(in_array('Gate Pass Desk', $inspectorMenuLabels, true), "Inspector sidebar contains 'Gate Pass Desk'");

// ── 7. From-Scratch Document Template Verification ─────────────────
echo "\n--- [Suite 7: From-Scratch Document Template Verification] ---\n";
$templateFile = dirname(__DIR__) . '/views/inspector/documents/gate-pass-template.php';
testAssert(file_exists($templateFile), "gate-pass-template.php exists in views/inspector/documents/");

$dummyPass = [
    'gate_pass_no' => 'GP-2026-9999',
    'created_at' => date('Y-m-d H:i:s'),
    'requisitioner_name' => 'Dr. Juan Dela Cruz',
    'requisitioning_office' => 'College of Agriculture',
    'purpose' => 'Field Research Equipment Clearance',
    'items' => [
        [
            'qty' => '1 unit',
            'item_description' => 'Dell Latitude 5420',
            'model' => 'Latitude 5420',
            'serial_no' => 'SN-998877',
            'date_acquired' => '2025-01-15',
            'unit_price' => '45000.00',
            'total_amount' => '45000.00',
            'remarks' => 'Good condition'
        ]
    ]
];

ob_start();
$pass = $dummyPass;
$isPrintView = true;
require $templateFile;
$renderedHtml = ob_get_clean();

testAssert(strpos($renderedHtml, 'SOUTHERN LUZON STATE UNIVERSITY') !== false, "Document contains institutional header");
testAssert(strpos($renderedHtml, 'GP-2026-9999') !== false, "Document renders pass number");
testAssert(strpos($renderedHtml, '8.5in 14in') !== false || strpos($renderedHtml, 'legal') !== false || strpos($renderedHtml, 'A4 portrait') !== false, "Document CSS specifies official print dimensions");
testAssert(strpos($renderedHtml, 'Dell Latitude 5420') !== false, "Document renders equipment item rows");

// ── 8. Gate Pass Print Engine & Dompdf Dual-Mode Verification ────
echo "\n--- [Suite 8: Gate Pass Print Engine & Dompdf Dual-Mode] ---\n";

$printEngineFile = dirname(__DIR__) . '/views/inspector/gate-pass-print.php';
$printContent = file_get_contents($printEngineFile);

testAssert(strpos($printContent, 'documents/gate-pass-template.php') !== false, "gate-pass-print.php includes native gate-pass-template.php");
testAssert(strpos($printContent, 'pdf-lib.min.js') === false, "gate-pass-print.php no longer includes pdf-lib.min.js coordinate stamping bundle");
testAssert(strpos($printContent, 'window.print()') !== false, "gate-pass-print.php supports instant browser printing");
testAssert(strpos($printContent, 'format') !== false && strpos($printContent, 'Dompdf') !== false, "gate-pass-print.php supports server-side Dompdf compilation via format=pdf");

// Verify Dompdf binary generation with official US Legal dimensions
testAssert(class_exists(\Dompdf\Dompdf::class), "Dompdf library is loaded and available in runtime");
$dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
$dompdf->setPaper('legal', 'portrait'); // Official US Legal: 8.5in x 14in (612pt x 1008pt)
$dompdf->loadHtml($renderedHtml);
$dompdf->render();
$pdfBinary = $dompdf->output();

// ── 9. Shared Gate Pass Issuance Modal Component Verification ────
echo "\n--- [Suite 9: Shared Gate Pass Issuance Modal Component] ---\n";

$modalComponentFile = dirname(__DIR__) . '/views/inspector/components/gate-pass-modal.php';
testAssert(file_exists($modalComponentFile), "gate-pass-modal.php exists in views/inspector/components/");

$gatePassesDeskFile = dirname(__DIR__) . '/views/inspector/gate-passes.php';
$deskContent = file_get_contents($gatePassesDeskFile);
testAssert(strpos($deskContent, 'components/gate-pass-modal.php') !== false, "gate-passes.php includes modular components/gate-pass-modal.php");

$modalContent = file_get_contents($modalComponentFile);
testAssert(strpos($modalContent, 'stageAndOpenGatePass') !== false, "gate-pass-modal.php exposes window.stageAndOpenGatePass global helper");
// ── 10. Dashboard Quick Scanner Verification ─────────────────────
echo "\n--- [Suite 10: Dashboard Quick Scanner Component] ---\n";

$quickScannerFile = dirname(__DIR__) . '/views/inspector/components/quick-scanner.php';
testAssert(file_exists($quickScannerFile), "quick-scanner.php exists in views/inspector/components/");

$dashFile = dirname(__DIR__) . '/views/inspector/dashboard.php';
$dashContent = file_get_contents($dashFile);
testAssert(strpos($dashContent, 'components/quick-scanner.php') === false, "Inspector dashboard cleanly omits quick-scanner banner per layout update");
testAssert(strpos($dashContent, 'components/gate-pass-modal.php') !== false, "Inspector dashboard includes components/gate-pass-modal.php");

$scannerContent = file_get_contents($quickScannerFile);
testAssert(strpos($scannerContent, 'stageAndOpenGatePass') !== false, "quick-scanner.php triggers stageAndOpenGatePass on scan");
// ── 11. Scanner Station (/scan) Inspector Adaptation ─────────────
echo "\n--- [Suite 11: Scanner Station Inspector Action Adaptation] ---\n";

$scanFile = dirname(__DIR__) . '/views/transactions/scan.php';
$scanContent = file_get_contents($scanFile);

testAssert(strpos($scanContent, 'inspector') !== false, "scan.php checks for inspector role session");
testAssert(strpos($scanContent, 'inspector/gate-passes?barcode=') !== false || strpos($scanContent, 'renderInspectorAction') !== false, "scan.php exposes gate pass issuance action for inspectors");

echo "\n========================================================\n";
echo "  ALL {$passCount} GATE PASS VERIFICATION TESTS PASSED!\n";
echo "========================================================\n\n";






