<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

function testAssert(bool $condition, string $message): void {
    if (!$condition) {
        echo "[FAIL] {$message}\n";
        exit(1);
    }
    echo "[PASS] {$message}\n";
}

echo "========================================================\n";
echo "  Direct Document Download Verification Suite\n";
echo "========================================================\n\n";

echo "--- [Task 1: Output Buffer Cleansing in PDF Print Engines] ---\n";
$instPrint = file_get_contents(__DIR__ . '/../views/reports/institutional-print.php');
testAssert(strpos($instPrint, 'ob_clean()') !== false, "institutional-print.php cleans active buffer before streaming PDF");

$iarPrint = file_get_contents(__DIR__ . '/../views/inspector/iar-print.php');
testAssert(strpos($iarPrint, 'ob_clean()') !== false, "iar-print.php cleans active buffer before streaming PDF");

$gpPrint = file_get_contents(__DIR__ . '/../views/inspector/gate-pass-print.php');
testAssert(strpos($gpPrint, 'ob_clean()') !== false, "gate-pass-print.php cleans active buffer before streaming PDF");

echo "\n--- [Task 2: Reports Module Direct PDF Download Transition] ---\n";
$reportsIndex = file_get_contents(__DIR__ . '/../views/reports/index.php');
testAssert(strpos($reportsIndex, "format: 'pdf'") !== false || strpos($reportsIndex, "format: \"pdf\"") !== false, "reports index passes format=pdf for official audit reports");
$mode1Pos = strpos($reportsIndex, "selectedFormat === 'official'");
$mode2Pos = strpos($reportsIndex, "// Mode 2:");
$mode1Code = ($mode1Pos !== false && $mode2Pos !== false) ? substr($reportsIndex, $mode1Pos, $mode2Pos - $mode1Pos) : '';
testAssert(strpos($mode1Code, "window.open") === false, "reports index no longer opens institutional report in _blank tab");
testAssert(strpos($mode1Code, "window.location.assign") !== false, "reports index downloads institutional report directly in active window");

echo "\n--- [Task 3: Inspector IAR Forms Direct PDF Download Transition] ---\n";
$iarView = file_get_contents(__DIR__ . '/../views/inspector/iar.php');
testAssert(strpos($iarView, "inspector/iar/print?id=' . \$r['id'] . '&format=pdf") !== false, "iar.php passes format=pdf on IAR download link");
testAssert(strpos($iarView, "iar/print?id=' . \$r['id']) ?>\" target=\"_blank\"") === false, "iar.php table link has no target=_blank");

$inspectorDash = file_get_contents(__DIR__ . '/../views/inspector/dashboard.php');
testAssert(strpos($inspectorDash, "inspector/iar/print?id=' . \$iar['id'] . '&format=pdf") !== false, "dashboard.php passes format=pdf on IAR download link");
testAssert(strpos($inspectorDash, "iar/print?id=' . \$iar['id']) ?>\" target=\"_blank\"") === false, "dashboard.php IAR link has no target=_blank");

echo "\n--- [Task 4: Gate Pass Direct PDF Download Transition] ---\n";
$gpView = file_get_contents(__DIR__ . '/../views/inspector/gate-passes.php');
testAssert(strpos($gpView, "&format=pdf") !== false, "gate-passes.php appends format=pdf to print URLs");
testAssert(strpos($gpView, "target=\"_blank\"") === false, "gate-passes.php action buttons have no target=_blank");
testAssert(strpos($gpView, "window.open") === false, "gate-passes.php no longer opens print in new window");

$gpModal = file_get_contents(__DIR__ . '/../views/inspector/components/gate-pass-modal.php');
testAssert(strpos($gpModal, "window.open(result.print_url, '_blank')") === false, "gate-pass-modal.php no longer opens print_url in _blank tab");

testAssert(strpos($inspectorDash, "gate-passes/print?id=' . \$gp['id'] . '&format=pdf") !== false, "dashboard.php passes format=pdf on Gate Pass download link");
testAssert(strpos($inspectorDash, "gate-passes/print?id=' . \$gp['id']) ?>\" target=\"_blank\"") === false, "dashboard.php Gate Pass link has no target=_blank");
