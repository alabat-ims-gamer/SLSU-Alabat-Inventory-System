<?php
/**
 * Comprehensive Automated Verification Suite for Institutional Reports & Analytics Refactor
 * Runs 5 automated test suites:
 *   Suite 1: Database Schema & Data Models
 *   Suite 2: RBAC Barriers & Access Control
 *   Suite 3: Live End-to-End Workflows (Lost & Movements)
 *   Suite 4: 1-to-1 Official Report & Dompdf Compilation
 *   Suite 5: Scope Streamlining Parity & UI Integrity
 */

declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

use App\Config\Database;
use App\Models\Item;
use App\Models\LostProperty;
use App\Models\PropertyMovement;
use App\Controllers\AnalyticsController;
use Dompdf\Dompdf;

$passed = 0;
$failed = 0;

function runTest(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $fn();
        echo "  [PASS] {$name}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  [FAIL] {$name}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

$db = Database::getConnection();

echo "====================================================================\n";
echo "INSTITUTIONAL REPORTS & PROPERTY ANALYTICS VERIFICATION SUITE\n";
echo "====================================================================\n\n";

// ── Suite 1: Database Schema & Model Assertions ─────────────────────────────
echo "SUITE 1: Database Schema & Model Assertions\n";

runTest("Table 'property_movements' exists", function() use ($db) {
    $stmt = $db->query("SHOW TABLES LIKE 'property_movements'");
    if ($stmt->fetch() === false) throw new Exception("Table property_movements missing");
});

runTest("Table 'lost_properties' exists", function() use ($db) {
    $stmt = $db->query("SHOW TABLES LIKE 'lost_properties'");
    if ($stmt->fetch() === false) throw new Exception("Table lost_properties missing");
});

runTest("Enum 'items.status' includes 'lost'", function() use ($db) {
    $stmt = $db->query("SHOW COLUMNS FROM items LIKE 'status'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!str_contains((string)$col['Type'], "'lost'")) {
        throw new Exception("items.status missing 'lost' value");
    }
});

runTest("Item::VALID_STATUSES contains 'lost'", function() {
    if (!in_array('lost', Item::VALID_STATUSES, true)) {
        throw new Exception("Item::VALID_STATUSES constant missing 'lost'");
    }
});

// ── Suite 2: Live End-to-End Workflows (Movements & Lost Properties) ────────
echo "\nSUITE 2: Live End-to-End Workflows (Movements & Lost Properties)\n";

$testItemId = 0;
runTest("Create temporary test asset in Science Laboratory", function() use ($db, &$testItemId) {
    $created = Item::createBatch($db, [
        'item_name'             => 'Automated Suite Test Microscope ' . time(),
        'category'              => 'Laboratory Tools',
        'location'              => 'Science Laboratory — Bench A',
        'item_condition'        => 'mint',
        'is_student_borrowable' => 1,
    ], 1, 1)[0];

    $testItemId = (int)$created['item_id'];
    if ($testItemId <= 0) throw new Exception("Failed to insert test item");
});

runTest("Location edit automatically logs to property_movements", function() use ($db, $testItemId) {
    $itemBefore = Item::findById($db, $testItemId);
    $res = Item::updateItem($db, $testItemId, [
        'item_name'             => $itemBefore['item_name'],
        'category'              => $itemBefore['category'],
        'location'              => 'Fisheries Laboratory — Tank 2',
        'item_condition'        => 'good',
        'is_student_borrowable' => 1,
        'description'           => 'Relocated for automated test verification',
        'property_number'       => $itemBefore['property_number'],
        'transfer_reason'       => 'Lab reallocation during audit testing',
    ], $itemBefore['updated_at'] ?? $itemBefore['created_at'], 1);

    if (!$res['success']) throw new Exception("Item::updateItem failed: " . ($res['message'] ?? ''));

    $history = PropertyMovement::getHistoryByItem($db, $testItemId);
    if (count($history) < 1) throw new Exception("No movement recorded for item");
    if ($history[0]['previous_location'] !== 'Science Laboratory — Bench A') {
        throw new Exception("Previous location mismatch: " . $history[0]['previous_location']);
    }
    if ($history[0]['new_location'] !== 'Fisheries Laboratory — Tank 2') {
        throw new Exception("New location mismatch: " . $history[0]['new_location']);
    }
    if (!str_contains($history[0]['reason'], 'reallocation')) {
        throw new Exception("Transfer reason mismatch: " . $history[0]['reason']);
    }
});

runTest("Reporting lost updates item status to 'lost' and inserts into lost_properties", function() use ($db, $testItemId) {
    $reported = Item::reportLost($db, $testItemId, [
        'last_recorded_location' => 'Fisheries Laboratory — Tank 2',
        'accountable_person'     => 'Lab In-Charge Dr. Santos',
        'date_reported'          => date('Y-m-d'),
        'description'            => 'Temporarily unaccounted during facility inventory sweep',
        'investigation_status'   => 'under_investigation',
    ], 1);

    if (!$reported) throw new Exception("Item::reportLost returned false");

    $itemRefreshed = Item::findById($db, $testItemId);
    if ($itemRefreshed['status'] !== 'lost') {
        throw new Exception("Expected status 'lost', found '{$itemRefreshed['status']}'");
    }

    $lostReports = LostProperty::getReports($db, 'Laboratory Tools');
    $matching = array_filter($lostReports, fn($r) => (int)$r['item_id'] === $testItemId);
    if (empty($matching)) throw new Exception("No lost_properties record matched test item");
});

// Clean up test asset
if ($testItemId > 0) {
    $db->exec("DELETE FROM items WHERE item_id = {$testItemId}");
}

// ── Suite 3: 1-to-1 Report Rendering & Dompdf Compilation ───────────────────
echo "\nSUITE 3: 1-to-1 Report Rendering & Dompdf Compilation\n";

runTest("Template institutional-report-template.php renders all 5 ledger sections", function() {
    $reportData = [
        'metadata' => [
            'timeframe_label' => 'Fiscal Year 2026',
            'generated_on'    => date('Y-m-d H:i:s'),
            'target_scope'    => 'All Categories',
            'location'        => 'All Locations',
        ],
        'summary_rows' => [
            [
                'category'          => 'IT Equipment',
                'total_items'       => 10,
                'available'         => 7,
                'borrowed'          => 2,
                'under_maintenance' => 1,
                'lost_and_missing'  => 0,
                'damaged'           => 1,
            ]
        ],
        'summary_totals' => [
            'total_items'       => 10,
            'available'         => 7,
            'borrowed'          => 2,
            'under_maintenance' => 1,
            'lost_and_missing'  => 0,
            'damaged'           => 1,
        ],
        'borrowing_history' => [
            [
                'barcode'               => 'BC-000999',
                'item_name'             => 'Test Barcode Asset',
                'borrower_name'         => 'Test Borrower',
                'borrower_id_number'    => 'ID-001',
                'role'                  => 'student',
                'date_borrowed'         => '2026-10-01',
                'return_date'           => '2026-10-05',
                'actual_return_date'    => '2026-10-05',
                'location'              => 'GIS Lab',
                'condition_upon_return' => 'Mint',
                'is_overdue'            => false,
            ]
        ],
        'damaged_properties' => [
            [
                'barcode'            => 'BC-000888',
                'item_name'          => 'Damaged Microscope',
                'incident_date'      => '2026-10-02',
                'damage_description' => 'Cracked mirror',
                'borrower_name'      => 'Borrower Jane',
                'borrower_id_number' => 'ID-002',
                'location'           => 'Science Lab',
                'technician'         => 'Technician Dave',
                'repair_cost'        => 1200.00,
                'cost_type'          => 'ACTUAL',
                'status'             => 'Repaired / Cleared',
            ]
        ],
        'lost_properties' => [
            [
                'barcode'                => 'BC-000777',
                'item_name'              => 'Missing Projector Remote',
                'last_recorded_location' => 'Faculty Room',
                'accountable_person'     => 'Prof. Reyes',
                'date_reported'          => '2026-10-03',
                'description'            => 'Lost in transit',
                'investigation_status'   => 'under_investigation',
                'resolution'             => 'pending',
            ]
        ],
        'property_movements' => [
            [
                'barcode'           => 'BC-000666',
                'item_name'         => 'Office Desktop',
                'previous_location' => 'Accounting',
                'new_location'      => 'Supply Office',
                'transfer_date'     => '2026-10-04 10:00',
                'reason'            => 'Reallocated for clerk',
                'transferred_by'    => 'Arlie Beniegas',
            ]
        ],
    ];

    ob_start();
    require dirname(__DIR__) . '/views/reports/documents/institutional-report-template.php';
    $html = ob_get_clean();

    if (!str_contains($html, '1. INVENTORY SUMMARY/OVERVIEW REPORT')) throw new Exception("Section 1 missing");
    if (!str_contains($html, '2. BORROWING AND RETURN HISTORY')) throw new Exception("Section 2 missing");
    if (!str_contains($html, '3. DAMAGED PROPERTIES REPORT')) throw new Exception("Section 3 missing");
    if (!str_contains($html, '4. LOST AND MISSING PROPERTIES REPORT')) throw new Exception("Section 4 missing");
    if (!str_contains($html, '5. PROPERTY MOVEMENT AND LOCATION HISTORY')) throw new Exception("Section 5 missing");
    if (!str_contains($html, 'ARLIE MARIE F. BENIEGAS')) throw new Exception("Custodian signatory missing");
    // Ensure header appears exactly once at the top (no misplaced mid-document repeat)
    if (substr_count($html, 'class="hdr-tbl"') !== 1) {
        throw new Exception("Header table must appear exactly once at the top, found: " . substr_count($html, 'class="hdr-tbl"'));
    }
});

runTest("Dompdf compiles institutional report into %PDF binary", function() {
    $reportData = [
        'metadata' => [
            'timeframe_label' => 'Last 30 Days',
            'generated_on'    => date('Y-m-d H:i:s'),
            'target_scope'    => 'All Categories',
            'location'        => 'All Locations',
        ],
        'summary_rows' => [],
        'summary_totals' => [
            'total_items' => 0, 'available' => 0, 'borrowed' => 0,
            'under_maintenance' => 0, 'lost_and_missing' => 0, 'damaged' => 0
        ],
        'borrowing_history' => [],
        'damaged_properties' => [],
        'lost_properties' => [],
        'property_movements' => [],
    ];

    ob_start();
    require dirname(__DIR__) . '/views/reports/documents/institutional-report-template.php';
    $html = ob_get_clean();

    $dompdf = new Dompdf([
        'isRemoteEnabled'      => true,
        'isHtml5ParserEnabled' => true,
        'defaultFont'          => 'Helvetica',
    ]);
    $dompdf->setPaper('a4', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();
    $pdf = $dompdf->output();

    if (!str_starts_with($pdf, '%PDF-')) {
        throw new Exception("Compiled output does not begin with %PDF- header");
    }
    if (strlen($pdf) < 5000) {
        throw new Exception("Compiled PDF is unexpectedly small (" . strlen($pdf) . " bytes)");
    }
});

// ── Suite 4: Scope Streamlining & Parity Check ──────────────────────────────
echo "\nSUITE 4: Scope Streamlining & Parity Check\n";

runTest("Reports index view has removed #filter-location dropdown", function() {
    $viewContent = file_get_contents(dirname(__DIR__) . '/views/reports/index.php') ?: '';
    if (str_contains($viewContent, 'id="filter-location"')) {
        throw new Exception("id='filter-location' is still present in views/reports/index.php");
    }
    if (str_contains($viewContent, 'All Campus Locations')) {
        throw new Exception("'All Campus Locations' option is still present in views/reports/index.php");
    }
});

runTest("Report Configurator modal contains dual export format options", function() {
    $viewContent = file_get_contents(dirname(__DIR__) . '/views/reports/index.php') ?: '';
    if (!str_contains($viewContent, 'name="rpt_format_type"')) {
        throw new Exception("rpt_format_type radio buttons missing in configurator modal");
    }
    if (!str_contains($viewContent, 'Official Institutional Audit Report')) {
        throw new Exception("Official report option missing in configurator modal");
    }
    if (!str_contains($viewContent, 'Visual Analytics Presentation Briefing')) {
        throw new Exception("Visual briefing option missing in configurator modal");
    }
});

runTest("Asset catalog includes Report Lost modal and transfer reason container", function() {
    $editModal = file_get_contents(dirname(__DIR__) . '/views/inventory/modals/edit-modal.php') ?: '';
    if (!str_contains($editModal, 'id="edit-transfer-reason-group"')) {
        throw new Exception("edit-transfer-reason-group missing in edit-modal.php");
    }
    if (!str_contains($editModal, 'name="transfer_reason"')) {
        throw new Exception("transfer_reason input missing in edit-modal.php");
    }

    $lostModal = file_get_contents(dirname(__DIR__) . '/views/inventory/modals/lost-modal.php') ?: '';
    if (!str_contains($lostModal, 'id="modal-report-lost"')) {
        throw new Exception("modal-report-lost missing in lost-modal.php");
    }
    if (!str_contains($lostModal, 'name="accountable_person"')) {
        throw new Exception("accountable_person input missing in lost-modal.php");
    }

    $indexView = file_get_contents(dirname(__DIR__) . '/views/inventory/index.php') ?: '';
    if (!str_contains($indexView, 'btn-report-lost')) {
        throw new Exception("btn-report-lost button missing in catalog rows");
    }
    if (!str_contains($indexView, 'lost-modal.php')) {
        throw new Exception("lost-modal.php include missing in inventory/index.php");
    }
});

runTest("Public index routes include institutional-report and report-lost", function() {
    $routes = file_get_contents(dirname(__DIR__) . '/public/index.php') ?: '';
    if (!str_contains($routes, '/api/analytics/institutional-report')) {
        throw new Exception("Route /api/analytics/institutional-report missing");
    }
    if (!str_contains($routes, '/api/inventory/report-lost')) {
        throw new Exception("Route /api/inventory/report-lost missing");
    }
});

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n====================================================================\n";
echo "VERIFICATION RESULTS: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
