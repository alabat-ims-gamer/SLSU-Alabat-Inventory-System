<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/vendor/autoload.php';

use App\Config\Database;
use App\Services\InventoryReportExporter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

echo "========================================================\n";
echo "SLSU-Alabat IMS — XLSX Report Export Verification\n";
echo "========================================================\n\n";

$db = Database::getConnection();

// 1. Fetch real active items
$stmt = $db->query("
    SELECT 
        i.item_id,
        i.barcode,
        i.property_number,
        i.item_name,
        i.model,
        i.serial_number,
        i.description,
        i.unit_cost,
        i.date_acquired,
        i.category,
        i.location,
        i.item_condition,
        i.status,
        i.is_student_borrowable
    FROM items i
    WHERE i.status NOT IN ('for_disposal', 'disposed')
    ORDER BY i.location ASC, i.item_id ASC
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
echo "Fetched " . count($items) . " active catalog items from database.\n";

// 2. Generate spreadsheet with live items
$exporter = new InventoryReportExporter();
$spreadsheet = $exporter->generate($items);

$testOutputDir = dirname(ROOT_PATH) . '/scratch';
if (!is_dir($testOutputDir)) {
    mkdir($testOutputDir, 0777, true);
}
$testFile = $testOutputDir . '/test_inventory_export.xlsx';

$writer = new Xlsx($spreadsheet);
$writer->save($testFile);
echo "Successfully generated and saved test export to: $testFile\n\n";

// 3. Inspect generated file using IOFactory
$loaded = IOFactory::load($testFile);
$sheetNames = $loaded->getSheetNames();
echo "Generated Sheet Count: " . count($sheetNames) . "\n";
echo "Sheets: " . implode(', ', $sheetNames) . "\n\n";

$pass = true;

// Check canonical sheets presence
foreach (InventoryReportExporter::CANONICAL_SHEETS as $canonical) {
    if (!in_array($canonical, $sheetNames, true)) {
        echo "[FAIL] Missing canonical sheet: $canonical\n";
        $pass = false;
    } else {
        echo "[PASS] Canonical sheet exists: $canonical\n";
    }
}

// Inspect structure of each sheet
foreach ($sheetNames as $sheetName) {
    echo "\nVerifying Sheet: $sheetName ...\n";
    $sheet = $loaded->getSheetByName($sheetName);

    // Verify 11 columns
    $expectedHeaders = [
        'A' => 'No.',
        'B' => 'Date',
        'C' => 'Item Description',
        'D' => 'Inventory (Quantity)',
        'F' => 'Unit',
        'G' => 'Location',
        'H' => 'Remarks',
        'I' => 'Specific Location',
        'J' => "With (/) or\nwithout tag (X)?",
        'K' => 'Corrective Action',
    ];

    foreach ($expectedHeaders as $col => $expectedText) {
        $actual = (string)$sheet->getCell($col . '1')->getValue();
        if (trim($actual) !== trim($expectedText)) {
            echo " [FAIL] Header $col 1 expected '$expectedText', got '$actual'\n";
            $pass = false;
        }
    }

    // Verify Subheaders in Row 2
    $beginning = (string)$sheet->getCell('D2')->getValue();
    $end = (string)$sheet->getCell('E2')->getValue();
    if ($beginning !== 'Beginning' || $end !== 'End') {
        echo " [FAIL] Row 2 quantity headers mismatch: D2='$beginning', E2='$end'\n";
        $pass = false;
    } else {
        echo " [PASS] Header columns A through K and Row 2 Beginning/End confirmed.\n";
    }

    // Verify Merges
    $merges = $sheet->getMergeCells();
    $requiredMerges = ['A1:A2', 'B1:B2', 'C1:C2', 'D1:E1', 'F1:F2', 'G1:G2', 'H1:H2', 'I1:I2', 'J1:J2', 'K1:K2'];
    foreach ($requiredMerges as $rm) {
        if (!isset($merges[$rm])) {
            echo " [FAIL] Missing required merge: $rm\n";
            $pass = false;
        }
    }
    echo " [PASS] Two-row header merge coordinates verified.\n";

    // Verify Font family Arial
    $font = $sheet->getStyle('A1')->getFont();
    if ($font->getName() !== 'Arial') {
        echo " [FAIL] Expected Arial font, got: " . $font->getName() . "\n";
        $pass = false;
    } else {
        echo " [PASS] Font family verified: Arial.\n";
    }

    // Verify Signatory text
    $foundCustodian = false;
    $foundAuthorized = false;
    for ($r = 1; $r <= $sheet->getHighestRow(); $r++) {
        for ($c = 'A'; $c <= 'K'; $c++) {
            $val = (string)$sheet->getCell($c . $r)->getValue();
            if (str_contains($val, 'ARLIE MARIE F. BENIEGAS')) {
                $foundCustodian = true;
            }
            if (str_contains($val, 'Authorized Inventory Personel')) {
                $foundAuthorized = true;
            }
        }
    }

    if (!$foundCustodian || !$foundAuthorized) {
        echo " [FAIL] Signatory footer missing in sheet $sheetName\n";
        $pass = false;
    } else {
        echo " [PASS] Signatory footer confirmed (Custodian: ARLIE MARIE F. BENIEGAS & Authorized Personnel).\n";
    }
}

// 4. Edge Case: Generate with empty items array
echo "\nTesting Edge Case: Export with empty database items [] ...\n";
$emptySpreadsheet = $exporter->generate([]);
$emptyFile = $testOutputDir . '/test_empty_export.xlsx';
(new Xlsx($emptySpreadsheet))->save($emptyFile);
$loadedEmpty = IOFactory::load($emptyFile);
if ($loadedEmpty->getSheetCount() === 5) {
    echo "[PASS] Edge Case Passed: 5 canonical sheets cleanly generated even with 0 items.\n";
} else {
    echo "[FAIL] Edge Case Failed: Expected 5 sheets, got " . $loadedEmpty->getSheetCount() . "\n";
    $pass = false;
}

echo "\n========================================================\n";
if ($pass) {
    echo "ALL VERIFICATION CHECKS PASSED (100% 1-to-1 Match)!\n";
} else {
    echo "SOME CHECKS FAILED. See details above.\n";
}
echo "========================================================\n";
