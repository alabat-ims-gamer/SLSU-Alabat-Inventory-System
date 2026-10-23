<?php
/**
 * Automated Verification Script: Custodian & Executive Reports & Analytics Parity
 * Validates Dual RBAC barriers, analytical aggregation, OWASP CSV protection,
 * views rendering, and audit log compliance.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

use App\Config\Database;
use App\Controllers\AnalyticsController;
use App\Models\Item;
use App\Models\AuditLog;

echo "====================================================================\n";
echo "SLSU-Alabat IMS: Custodian & Executive Analytics Parity Verification\n";
echo "====================================================================\n\n";

$db = Database::getConnection();
$totalPassed = 0;
$totalFailed = 0;

function assertCondition(string $name, bool $result): void {
    global $totalPassed, $totalFailed;
    if ($result) {
        echo "  [PASS] {$name}\n";
        $totalPassed++;
    } else {
        echo "  [FAIL] {$name}\n";
        $totalFailed++;
    }
}

// ─────────────────────────────────────────────────────────────────
// Suite 1: Dual RBAC Barrier Assertions
// ─────────────────────────────────────────────────────────────────
echo "--- Suite 1: Dual RBAC Barrier Assertions ---\n";

// Helper to execute sub-process role check
function testRoleAccess(string $role, string $controller = 'App\\Controllers\\AnalyticsController', string $method = 'getExecutiveData'): int {
    $phpExe = 'C:\\xampp\\php\\php.exe';
    if (!file_exists($phpExe)) {
        $phpExe = 'php';
    }

    $runnerScript = __DIR__ . '/test_runner_exec.php';
    $cmd = sprintf(
        '"%s" -d display_errors=0 "%s" %s %s %s',
        $phpExe,
        $runnerScript,
        escapeshellarg($role),
        escapeshellarg($controller),
        escapeshellarg($method)
    );

    $output = shell_exec($cmd);
    if ($output !== null && preg_match('/STATUS_CODE:(\d+)/', $output, $matches)) {
        return (int)$matches[1];
    }
    return 500;
}

// AnalyticsController::index
assertCondition(
    "Property Custodian is ALLOWED (200 OK) on Analytics Studio index()",
    testRoleAccess('custodian', 'App\\Controllers\\AnalyticsController', 'index') === 200
);

assertCondition(
    "Executive Admin is REDIRECTED (302) from Analytics Studio index() to /dashboard",
    testRoleAccess('executive_admin', 'App\\Controllers\\AnalyticsController', 'index') === 302
);

assertCondition(
    "System Admin is FORBIDDEN (403) from Analytics Studio index()",
    testRoleAccess('admin', 'App\\Controllers\\AnalyticsController', 'index') === 403
);

assertCondition(
    "Faculty is FORBIDDEN (403) from Analytics Studio index()",
    testRoleAccess('faculty', 'App\\Controllers\\AnalyticsController', 'index') === 403
);

assertCondition(
    "Student is FORBIDDEN (403) from Analytics Studio index()",
    testRoleAccess('student', 'App\\Controllers\\AnalyticsController', 'index') === 403
);

// AnalyticsController::getExecutiveData
assertCondition(
    "Property Custodian is ALLOWED (200 OK) on getExecutiveData() API",
    testRoleAccess('custodian', 'App\\Controllers\\AnalyticsController', 'getExecutiveData') === 200
);

assertCondition(
    "Executive Admin is ALLOWED (200 OK) on getExecutiveData() API",
    testRoleAccess('executive_admin', 'App\\Controllers\\AnalyticsController', 'getExecutiveData') === 200
);

assertCondition(
    "System Admin is FORBIDDEN (403) from getExecutiveData() API",
    testRoleAccess('admin', 'App\\Controllers\\AnalyticsController', 'getExecutiveData') === 403
);

// ─────────────────────────────────────────────────────────────────
// Suite 2: Analytics Data Aggregation & Integrity
// ─────────────────────────────────────────────────────────────────
echo "\n--- Suite 2: Analytics Data Aggregation & Integrity ---\n";

$analytics = AnalyticsController::computeAnalytics($db, '30d');

assertCondition(
    "Analytics payload contains top-level keys ('kpi', 'asset_dynamics', 'circulation', 'incidents', 'metadata')",
    isset($analytics['kpi'], $analytics['asset_dynamics'], $analytics['circulation'], $analytics['incidents'], $analytics['metadata'])
);

assertCondition(
    "KPI metrics are present and well-formed (total_assets, availability_rate, serviceability_rate)",
    isset($analytics['kpi']['total_assets'], $analytics['kpi']['availability_rate'], $analytics['kpi']['serviceability_rate']) &&
    $analytics['kpi']['total_assets'] >= 0
);

assertCondition(
    "Circulation analytics contains velocity series and day-of-week demand data",
    isset($analytics['circulation']['velocity_series']['labels'], $analytics['circulation']['dow_demand']['labels'])
);

// ─────────────────────────────────────────────────────────────────
// Suite 3: OWASP Formula Injection Neutralization
// ─────────────────────────────────────────────────────────────────
echo "\n--- Suite 3: OWASP Formula Injection Protection ---\n";

assertCondition(
    "Formula '=SUM(1+1)' is neutralized with leading quote: '" . Item::neutralizeFormula('=SUM(1+1)') . "'",
    Item::neutralizeFormula('=SUM(1+1)') === "'=SUM(1+1)"
);

assertCondition(
    "Payload '+CMD' is neutralized: '" . Item::neutralizeFormula('+CMD') . "'",
    Item::neutralizeFormula('+CMD') === "'+CMD"
);

assertCondition(
    "Payload '-ALERT' is neutralized: '" . Item::neutralizeFormula('-ALERT') . "'",
    Item::neutralizeFormula('-ALERT') === "'-ALERT"
);

assertCondition(
    "Payload '@calc' is neutralized: '" . Item::neutralizeFormula('@calc') . "'",
    Item::neutralizeFormula('@calc') === "'@calc"
);

// ─────────────────────────────────────────────────────────────────
// Suite 4: Views Rendering & DRY Verification
// ─────────────────────────────────────────────────────────────────
echo "\n--- Suite 4: Views Rendering & DRY Verification ---\n";

// 4.1 Render views/reports/index.php as Custodian
$_SESSION['role'] = 'custodian';
$_SESSION['user_name'] = 'Campus Custodian';
ob_start();
$analyticsData = $analytics;
$categoriesList = AnalyticsController::getAvailableCategories($db);
$locationsList = AnalyticsController::getAvailableLocations($db);
require ROOT_PATH . '/views/reports/index.php';
$custodianReportsHtml = ob_get_clean();

assertCondition(
    "Reports Studio renders successfully for Custodian with HTTP 200 equivalent",
    !empty($custodianReportsHtml) && strlen($custodianReportsHtml) > 500
);

assertCondition(
    "Custodian header displays 'Reports & Property Analytics Studio' and 'Property Custodian Intelligence'",
    str_contains($custodianReportsHtml, 'Reports &amp; Property Analytics Studio') &&
    str_contains($custodianReportsHtml, 'Property Custodian Intelligence')
);

assertCondition(
    "Custodian Reports Studio includes all Chart.js canvas elements and modals",
    str_contains($custodianReportsHtml, 'summary-health-chart') &&
    str_contains($custodianReportsHtml, 'summary-velocity-chart') &&
    str_contains($custodianReportsHtml, 'dynamics-condition-chart') &&
    str_contains($custodianReportsHtml, 'velocity-detailed-chart') &&
    str_contains($custodianReportsHtml, 'modal-report-configurator')
);

// 4.2 Render views/dashboard/executive.php as Executive Admin
$_SESSION['role'] = 'executive_admin';
$_SESSION['user_name'] = 'Campus Director';
ob_start();
$analyticsData = $analytics;
$categoriesList = AnalyticsController::getAvailableCategories($db);
$locationsList = AnalyticsController::getAvailableLocations($db);
require ROOT_PATH . '/views/dashboard/executive.php';
$execDashboardHtml = ob_get_clean();

assertCondition(
    "Executive Overview renders via delegation to reports/index.php (DRY)",
    !empty($execDashboardHtml) &&
    str_contains($execDashboardHtml, 'Executive Analytics &amp; Institutional Intelligence') &&
    str_contains($execDashboardHtml, 'Executive Governance View')
);

// ─────────────────────────────────────────────────────────────────
// Suite 5: Audit Log Compliance
// ─────────────────────────────────────────────────────────────────
echo "\n--- Suite 5: Audit Log Compliance ---\n";

$auditLogs = AuditLog::getRecentLogs($db, 20);
assertCondition(
    "Audit Log subsystem is active and accessible",
    is_array($auditLogs)
);

// ─────────────────────────────────────────────────────────────────
// Summary
// ─────────────────────────────────────────────────────────────────
echo "\n====================================================================\n";
echo "Verification Summary: {$totalPassed} Passed, {$totalFailed} Failed\n";
echo "====================================================================\n";

if ($totalFailed > 0) {
    exit(1);
}
