<?php
declare(strict_types=1);

/**
 * Mobile 2-Column KPI Grid Automated Verification Suite
 */

function testAssert(bool $condition, string $message): void {
    if (!$condition) {
        echo "[FAIL] {$message}\n";
        exit(1);
    }
    echo "[PASS] {$message}\n";
}

echo "=== Mobile 2-Column KPI Grid Verification Suite ===\n";

$cssPath = __DIR__ . '/../public/css/custom.css';
testAssert(file_exists($cssPath), "custom.css exists");
$cssContent = file_get_contents($cssPath);

// 1. Verify CSS definitions
testAssert(strpos($cssContent, 'grid-template-columns: repeat(2, 1fr) !important;') !== false, 
    "custom.css includes 2-column rule for mobile KPI grid");

testAssert(strpos($cssContent, ':last-child:nth-child(odd)') !== false, 
    "custom.css includes odd-numbered card selector");

testAssert(strpos($cssContent, 'grid-column: span 2 !important;') !== false, 
    "custom.css includes grid-column: span 2 for odd-numbered last card");

testAssert(strpos($cssContent, 'max-width: 359px') !== false, 
    "custom.css includes ultra-narrow (< 360px) screen breakpoint");

testAssert(strpos($cssContent, 'font-variant-numeric: tabular-nums') !== false, 
    "custom.css scales stat-card typography with tabular-nums on mobile");

// 2. Verify Inspector IAR (User reference screenshot page)
$iarPath = __DIR__ . '/../views/inspector/iar.php';
$iarContent = file_get_contents($iarPath);
testAssert(strpos($iarContent, 'grid-kpi-stats') !== false, 
    "inspector/iar.php uses grid-kpi-stats class");
testAssert(strpos($iarContent, 'display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem;margin-bottom:1.5rem;') === false, 
    "inspector/iar.php removed raw inline repeat(auto-fit) grid style");

// 3. Verify Inspector Gate Passes (3 cards - odd card test case)
$gpPath = __DIR__ . '/../views/inspector/gate-passes.php';
$gpContent = file_get_contents($gpPath);
testAssert(strpos($gpContent, 'grid-kpi-stats') !== false, 
    "inspector/gate-passes.php uses grid-kpi-stats class");
testAssert(strpos($gpContent, 'style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem;margin-bottom:1.5rem;"') === false, 
    "inspector/gate-passes.php removed raw inline repeat(auto-fit) grid style");

// 4. Verify Inspector Dashboard
$inspDashPath = __DIR__ . '/../views/inspector/dashboard.php';
$inspDashContent = file_get_contents($inspDashPath);
testAssert(strpos($inspDashContent, 'grid-kpi-stats') !== false, 
    "inspector/dashboard.php uses grid-kpi-stats class");

// 5. Verify Records Index
$recPath = __DIR__ . '/../views/records/index.php';
$recContent = file_get_contents($recPath);
testAssert(strpos($recContent, 'grid-kpi-stats') !== false, 
    "records/index.php uses grid-kpi-stats class");
testAssert(strpos($recContent, 'repeat(auto-fit, minmax(200px, 1fr))') === false, 
    "records/index.php removed raw inline repeat(auto-fit) grid style");

// 6. Verify Records Incidents
$incPath = __DIR__ . '/../views/records/incidents.php';
$incContent = file_get_contents($incPath);
testAssert(strpos($incContent, 'grid-kpi-stats') !== false, 
    "records/incidents.php uses grid-kpi-stats class");

// 7. Verify Clearances Student
$clrPath = __DIR__ . '/../views/clearances/student.php';
$clrContent = file_get_contents($clrPath);
testAssert(strpos($clrContent, 'grid-kpi-stats') !== false, 
    "clearances/student.php uses grid-kpi-stats class");

// 8. Verify Admin Audit Logs (4 cards with .card markup)
$auditPath = __DIR__ . '/../views/admin/audit_logs.php';
$auditContent = file_get_contents($auditPath);
testAssert(strpos($auditContent, 'grid-kpi-stats') !== false, 
    "admin/audit_logs.php uses grid-kpi-stats class");
testAssert(strpos($auditContent, 'style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1rem;margin-bottom:1.5rem;"') === false, 
    "admin/audit_logs.php removed raw inline repeat(auto-fit) grid style");

echo "\n>>> All Mobile 2-Column KPI Grid assertions PASSED! <<<\n";
