<?php
declare(strict_types=1);

/**
 * Inspection and Acceptance Report (IAR) System Verification Test Suite
 * Form AFA-SAF-1.016 Physical Verification & Digital Handover
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
echo "  SLSU-Alabat IMS - IAR System Verification Suite\n";
echo "  Form AFA-SAF-1.016 Inspection & Acceptance Pipeline\n";
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

$iarTables = $db->query("SHOW TABLES LIKE 'inspection_reports'")->fetchAll(PDO::FETCH_COLUMN);
testAssert(count($iarTables) === 1, "'inspection_reports' table exists in database");

$itemTables = $db->query("SHOW TABLES LIKE 'inspection_report_items'")->fetchAll(PDO::FETCH_COLUMN);
testAssert(count($itemTables) === 1, "'inspection_report_items' table exists in database");

$reportCols = $db->query("SHOW COLUMNS FROM inspection_reports")->fetchAll(PDO::FETCH_COLUMN);
$requiredReportCols = [
    'id', 'iar_no', 'entity_name', 'fund_cluster', 'supplier_name',
    'po_no', 'po_date', 'invoice_no', 'invoice_date', 'office_dept',
    'rcc', 'date_inspected', 'date_received', 'inspection_officer',
    'property_officer', 'is_inspected_order', 'acceptance_type',
    'partial_remarks', 'scanned_document_path', 'status', 'created_by', 'created_at'
];
foreach ($requiredReportCols as $rc) {
    testAssert(in_array($rc, $reportCols, true), "inspection_reports contains column '{$rc}'");
}

$itemCols = $db->query("SHOW COLUMNS FROM inspection_report_items")->fetchAll(PDO::FETCH_COLUMN);
$requiredItemCols = [
    'id', 'iar_id', 'item_no', 'item_description', 'unit', 'quantity',
    'unit_cost', 'total_cost', 'model', 'serial_no', 'is_registered', 'registered_item_id'
];
foreach ($requiredItemCols as $ric) {
    testAssert(in_array($ric, $itemCols, true), "inspection_report_items contains column '{$ric}'");
}

// ── 2. Official PDF Template & Local Script Verification ────────
echo "\n--- [Suite 2: Official Template & Stamping Assets] ---\n";

$templatePath = dirname(__DIR__) . '/public/assets/templates/INSPECTION-AND-ACCEPTANCE.pdf';
testAssert(file_exists($templatePath), "Official template file INSPECTION-AND-ACCEPTANCE.pdf exists in public/assets/templates");
testAssert(filesize($templatePath) > 100000, "Official template file is non-empty (" . filesize($templatePath) . " bytes)");

$pdfLibPath = dirname(__DIR__) . '/public/js/pdf-lib.min.js';
testAssert(file_exists($pdfLibPath), "Local pdf-lib.min.js bundle exists in public/js/");
testAssert(filesize($pdfLibPath) > 400000, "Local pdf-lib.min.js is valid standalone bundle (" . filesize($pdfLibPath) . " bytes)");

$uploadDir = dirname(__DIR__) . '/public/uploads/iar';
testAssert(is_dir($uploadDir), "Upload directory public/uploads/iar exists");
testAssert(is_writable($uploadDir), "Upload directory public/uploads/iar is writable");

// ── 3. Numbering Sequence & Model Methods ───────────────────────
echo "\n--- [Suite 3: InspectionReport Model & Numbering Generator] ---\n";

$iarNo = \App\Models\InspectionReport::generateIarNo($db);
echo "  [i] Generated IAR No: {$iarNo}\n";
testAssert(preg_match('/^IAR-\d{4}-\d{4}$/', $iarNo) === 1, "Generated IAR number matches format IAR-YYYY-XXXX");

// ── 4. Validation Safeguards ────────────────────────────────────
echo "\n--- [Suite 4: Input Validation & Safeguards] ---\n";

$threwSupplier = false;
try {
    \App\Models\InspectionReport::create($db, [
        'supplier_name' => '',
        'po_no'         => 'PO-2026-001',
        'invoice_no'    => 'INV-101',
        'office_dept'   => 'CAS'
    ], [['item_description' => 'Test item', 'quantity' => 1]], 1);
} catch (\InvalidArgumentException $e) {
    $threwSupplier = true;
}
testAssert($threwSupplier, "Creation fails when supplier_name is empty");

$threwItems = false;
try {
    \App\Models\InspectionReport::create($db, [
        'supplier_name' => 'Valid Supplier Inc.',
        'po_no'         => 'PO-2026-001',
        'invoice_no'    => 'INV-101',
        'office_dept'   => 'CAS'
    ], [], 1);
} catch (\InvalidArgumentException $e) {
    $threwItems = true;
}
testAssert($threwItems, "Creation fails when line items array is empty");

// ── 5. Transactional IAR Creation & Retrieval ───────────────────
echo "\n--- [Suite 5: Transactional IAR Creation & Retrieval] ---\n";

// Find or create test user for creator
$adminUser = $db->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
$creatorId = $adminUser ? (int)$adminUser : 1;

$testData = [
    'entity_name'        => 'SLSU-ALABAT',
    'fund_cluster'       => 'PRE-STF',
    'supplier_name'      => 'AQUANET MARINE EQUIPMENT TRADING',
    'po_no'              => 'PO-2026-FSH-0089',
    'po_date'            => '2026-10-15',
    'invoice_no'         => 'SI-890214',
    'invoice_date'       => '2026-10-18',
    'office_dept'        => 'College of Fisheries (BSFAS)',
    'rcc'                => 'RCC-04-12',
    'date_inspected'     => '2026-10-20',
    'date_received'      => '2026-10-20',
    'inspection_officer' => 'MANUEL S. VALDERAMA, MIT',
    'property_officer'   => 'VERONICA C. OBIEN',
    'is_inspected_order' => 1,
    'acceptance_type'    => \App\Models\InspectionReport::ACCEPTANCE_COMPLETE,
    'partial_remarks'    => '',
];

$testItems = [
    [
        'item_description' => 'Submersible Water Quality Sonde with Bluetooth',
        'unit'             => 'units',
        'quantity'         => 2,
        'unit_cost'        => 38500.00,
        'total_cost'       => 77000.00,
        'model'            => 'YSI-ProDSS-4P',
        'serial_no'        => 'YS-99201A',
    ],
    [
        'item_description' => 'Precision Heavy Duty Laboratory Bench Balance',
        'unit'             => 'set',
        'quantity'         => 1,
        'unit_cost'        => 42000.00,
        'total_cost'       => 42000.00,
        'model'            => 'Ohaus Scout SPX',
        'serial_no'        => 'OH-88310',
    ],
];

$created = \App\Models\InspectionReport::create($db, $testData, $testItems, $creatorId);
testAssert(!empty($created['id']), "IAR record created with ID {$created['id']}");
testAssert(!empty($created['iar_no']), "IAR assigned official number {$created['iar_no']}");
testAssert($created['status'] === \App\Models\InspectionReport::STATUS_PENDING_SIGNATURES, "Initial status is PENDING_SIGNATURES");

$fetched = \App\Models\InspectionReport::getById($db, (int)$created['id']);
testAssert($fetched !== null, "Fetched created IAR record by ID");
testAssert($fetched['supplier_name'] === 'AQUANET MARINE EQUIPMENT TRADING', "Supplier name snapshot matches");
testAssert($fetched['po_no'] === 'PO-2026-FSH-0089', "PO number matches");
testAssert($fetched['office_dept'] === 'College of Fisheries (BSFAS)', "Office/Dept matches");
testAssert(count($fetched['items']) === 2, "IAR contains exactly 2 line items");
testAssert((float)$fetched['items'][0]['total_cost'] === 77000.00, "Item 1 total cost matches (77,000.00)");
testAssert((float)$fetched['items'][1]['unit_cost'] === 42000.00, "Item 2 unit cost matches (42,000.00)");

$byIarNo = \App\Models\InspectionReport::getByIarNo($db, $created['iar_no']);
testAssert($byIarNo !== null && (int)$byIarNo['id'] === (int)$created['id'], "getByIarNo successfully retrieves report");

// Test listAll filtering
$listResult = \App\Models\InspectionReport::listAll($db, \App\Models\InspectionReport::STATUS_PENDING_SIGNATURES, $created['iar_no']);
testAssert($listResult['total'] >= 1, "listAll finds report by status filter and search term");
testAssert((int)$listResult['reports'][0]['item_count'] === 2, "listAll aggregates correct line item count");
testAssert((float)$listResult['reports'][0]['total_valuation'] === 119000.00, "listAll aggregates correct total valuation (119,000.00)");

// ── 6. Signed Scan Upload & Transition to TRANSMITTED ───────────
echo "\n--- [Suite 6: Wet-Signed Scan Upload & Transmission] ---\n";

$dummyScanPath = '/uploads/iar/test_scan_' . time() . '.pdf';
$uploadSuccess = \App\Models\InspectionReport::uploadSignedScan($db, (int)$created['id'], $dummyScanPath, $creatorId);
testAssert($uploadSuccess === true, "uploadSignedScan returns true");

$transmittedReport = \App\Models\InspectionReport::getById($db, (int)$created['id']);
testAssert($transmittedReport['status'] === \App\Models\InspectionReport::STATUS_TRANSMITTED, "Status transitioned to TRANSMITTED after upload");
testAssert($transmittedReport['scanned_document_path'] === $dummyScanPath, "Scanned document path saved in database");

// Test getPendingIngestion
$pending = \App\Models\InspectionReport::getPendingIngestion($db);
$foundInPending = false;
foreach ($pending as $p) {
    if ((int)$p['id'] === (int)$created['id']) {
        $foundInPending = true;
        break;
    }
}
testAssert($foundInPending, "Report appears in getPendingIngestion for Property Custodians");

// ── 7. Custodian Ingestion Handover & Transition to REGISTERED ──
echo "\n--- [Suite 7: Custodian Ingestion & Status Progression] ---\n";

// Insert 2 temporary items to satisfy foreign key constraint fk_iari_item
$tempItemStmt = $db->prepare("
    INSERT INTO items (
        item_name, category, description, barcode, status, item_condition, registered_by, created_at
    ) VALUES (
        :name, 'IT Equipment', 'Temporary test asset', :barcode, 'available', 'good', :uid, NOW()
    )
");
$tempBarcode1 = 'TEST-IAR-' . bin2hex(random_bytes(4));
$tempBarcode2 = 'TEST-IAR-' . bin2hex(random_bytes(4));

$tempItemStmt->execute([':name' => 'Test Asset 1', ':barcode' => $tempBarcode1, ':uid' => $creatorId]);
$tempItemId1 = (int)$db->lastInsertId();

$tempItemStmt->execute([':name' => 'Test Asset 2', ':barcode' => $tempBarcode2, ':uid' => $creatorId]);
$tempItemId2 = (int)$db->lastInsertId();

// Register line item 1
$item1Id = (int)$transmittedReport['items'][0]['id'];
$item2Id = (int)$transmittedReport['items'][1]['id'];

// Simulate item 1 registration
$part1Success = \App\Models\InspectionReport::markItemsRegistered($db, (int)$created['id'], [
    ['line_id' => $item1Id, 'item_id' => $tempItemId1]
]);
testAssert($part1Success === true, "Marking item 1 as registered succeeded");

$part1Report = \App\Models\InspectionReport::getById($db, (int)$created['id']);
testAssert($part1Report['status'] === \App\Models\InspectionReport::STATUS_TRANSMITTED, "Status remains TRANSMITTED when partially registered");
testAssert((int)$part1Report['items'][0]['is_registered'] === 1, "Item 1 is marked as registered");
testAssert((int)$part1Report['items'][1]['is_registered'] === 0, "Item 2 is not yet registered");

// Simulate item 2 registration (all complete)
$part2Success = \App\Models\InspectionReport::markItemsRegistered($db, (int)$created['id'], [
    ['line_id' => $item2Id, 'item_id' => $tempItemId2]
]);
testAssert($part2Success === true, "Marking item 2 as registered succeeded");

$finalReport = \App\Models\InspectionReport::getById($db, (int)$created['id']);
testAssert($finalReport['status'] === \App\Models\InspectionReport::STATUS_REGISTERED, "Status auto-progressed to REGISTERED once all items registered");
testAssert((int)$finalReport['items'][1]['is_registered'] === 1, "Item 2 is marked as registered");

// ── 8. Inspector Metrics & Dashboard Aggregates ─────────────────
echo "\n--- [Suite 8: Metrics & Notification Badges] ---\n";

$metrics = \App\Models\InspectionReport::getMetrics($db);
testAssert(isset($metrics['active_passes_today']), "Metrics contains 'active_passes_today'");
testAssert(isset($metrics['total_active_passes']), "Metrics contains 'total_active_passes'");
testAssert(isset($metrics['pending_signatures']), "Metrics contains 'pending_signatures'");
testAssert(isset($metrics['transmitted_count']), "Metrics contains 'transmitted_count'");
testAssert(isset($metrics['items_inspected_ytd']), "Metrics contains 'items_inspected_ytd'");

$inspectorBadges = \App\Services\NotificationService::getSidebarBadges($db, $creatorId, 'inspector');
testAssert(isset($inspectorBadges['iar_pending']), "Inspector badges contains 'iar_pending'");

$custodianBadges = \App\Services\NotificationService::getSidebarBadges($db, $creatorId, 'custodian');
testAssert(isset($custodianBadges['iar_incoming']), "Custodian badges contains 'iar_incoming'");

// ── 9. Clean up test records ────────────────────────────────────
$db->exec("DELETE FROM inspection_report_items WHERE iar_id = " . (int)$created['id']);
$db->exec("DELETE FROM inspection_reports WHERE id = " . (int)$created['id']);
$db->exec("DELETE FROM items WHERE item_id IN ({$tempItemId1}, {$tempItemId2})");
echo "  [i] Cleaned up temporary test IAR ID {$created['id']} and temp assets\n";

// ── 10. UI Shell & Routing Consistency ──────────────────────────
echo "\n--- [Suite 10: Routing & Role Labels] ---\n";

ob_start();
$_SESSION['role'] = 'inspector';
$_SESSION['user_name'] = 'Test Inspector';
require dirname(__DIR__) . '/views/layouts/shell.php';
ob_end_clean();

testAssert(isset($roleLabels['inspector']), "Role label for 'inspector' exists in shell.php");
testAssert($roleLabels['inspector'] === 'Local Inspector', "Role label is 'Local Inspector'");
testAssert(isset($menus['inspector']), "Sidebar menu for 'inspector' exists in shell.php");

$inspectorMenuLabels = array_column($menus['inspector'], 'label');
testAssert(in_array('Gate Pass Desk', $inspectorMenuLabels, true), "Inspector sidebar contains 'Gate Pass Desk'");
testAssert(in_array('IAR Inspection', $inspectorMenuLabels, true), "Inspector sidebar contains 'IAR Inspection'");
testAssert(in_array('Scan & Verify', $inspectorMenuLabels, true), "Inspector sidebar contains 'Scan & Verify'");

// ── 11. From-Scratch IAR Document & Dompdf Pipeline ──────────────
echo "\n--- [Suite 11: From-Scratch IAR Document & Dompdf Pipeline] ---\n";

$iarTemplateFile = dirname(__DIR__) . '/views/inspector/documents/iar-template.php';
testAssert(file_exists($iarTemplateFile), "iar-template.php exists in views/inspector/documents/");

$dummyIar = [
    'iar_no' => 'IAR-2026-9999',
    'entity_name' => 'SLSU-ALABAT',
    'fund_cluster' => 'PRE-STF',
    'supplier_name' => 'Microdata Systems and Management',
    'po_no' => 'PO-2026-0042',
    'po_date' => '2026-01-10',
    'invoice_no' => 'INV-8821',
    'invoice_date' => '2026-01-12',
    'office_dept' => 'Computer Studies Department',
    'rcc' => '101',
    'date_inspected' => '2026-01-15',
    'date_received' => '2026-01-15',
    'inspection_officer' => 'MANUEL S. VALDERAMA, MIT',
    'property_officer' => 'VERONICA C. OBIEN',
    'is_inspected_order' => true,
    'acceptance_type' => 'complete',
    'partial_remarks' => '',
    'items' => [
        [
            'item_no' => 1,
            'item_description' => 'Epson L3210 EcoTank All-in-One Printer',
            'unit' => 'units',
            'quantity' => 2,
            'unit_cost' => 10500.00,
            'total_cost' => 21000.00,
            'model' => 'L3210',
            'serial_no' => 'EP-100293'
        ]
    ]
];

ob_start();
$report = $dummyIar;
require $iarTemplateFile;
$iarHtml = ob_get_clean();

testAssert(strpos($iarHtml, 'INSPECTION AND ACCEPTANCE REPORT') !== false, "IAR document contains official title");
testAssert(strpos($iarHtml, 'A4 portrait') !== false || strpos($iarHtml, '8.5in 13in') !== false, "IAR document CSS specifies official print dimensions (A4 portrait)");
testAssert(strpos($iarHtml, 'Epson L3210') !== false, "IAR document renders line items");

$iarPrintEngine = dirname(__DIR__) . '/views/inspector/iar-print.php';
$iarPrintContent = file_get_contents($iarPrintEngine);

testAssert(strpos($iarPrintContent, 'documents/iar-template.php') !== false, "iar-print.php includes native iar-template.php");
testAssert(strpos($iarPrintContent, 'pdf-lib.min.js') === false, "iar-print.php no longer includes pdf-lib.min.js");
testAssert(strpos($iarPrintContent, 'window.print()') !== false, "iar-print.php supports instant browser printing");
testAssert(strpos($iarPrintContent, 'format') !== false && strpos($iarPrintContent, 'Dompdf') !== false, "iar-print.php supports server-side Dompdf compilation via format=pdf");

// Dompdf compilation check with official A4 dimensions
testAssert(class_exists(\Dompdf\Dompdf::class), "Dompdf library is loaded and available in runtime");
$dompdfIar = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
$dompdfIar->setPaper('a4', 'portrait');
$dompdfIar->loadHtml($iarHtml);
$dompdfIar->render();
$iarPdfBinary = $dompdfIar->output();

testAssert(!empty($iarPdfBinary), "IAR Dompdf outputs non-empty binary content");
testAssert(substr($iarPdfBinary, 0, 4) === '%PDF', "IAR Dompdf binary starts with %PDF magic header");

echo "\n========================================================\n";
echo "  ALL {$passCount} IAR VERIFICATION TESTS PASSED!\n";
echo "========================================================\n\n";

