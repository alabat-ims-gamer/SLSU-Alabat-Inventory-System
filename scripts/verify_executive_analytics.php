<?php
/**
 * Automated Verification Script: Executive Analytics & Reporting Engine
 * Validates RBAC barriers, analytical data aggregation, formula injection protection, and view rendering.
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

echo "====================================================================\n";
echo "SLSU-Alabat IMS: Executive Analytics & Reporting Engine Verification\n";
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
// Suite 1: RBAC Boundary Assertions
// ─────────────────────────────────────────────────────────────────
echo "--- Suite 1: RBAC Boundary Assertions ---\n";

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

assertCondition(
    "Executive Admin is ALLOWED (200 OK) to access Executive Analytics Engine",
    testRoleAccess('executive_admin') === 200
);

assertCondition(
    "System Admin is FORBIDDEN (403) from Executive Analytics Engine",
    testRoleAccess('admin') === 403
);

assertCondition(
    "Property Custodian is ALLOWED (200 OK) to access Analytics Engine",
    testRoleAccess('custodian') === 200
);

assertCondition(
    "Faculty is FORBIDDEN (403) from Executive Analytics Engine",
    testRoleAccess('faculty') === 403
);

assertCondition(
    "Student is FORBIDDEN (403) from Executive Analytics Engine",
    testRoleAccess('student') === 403
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
    "KPI contains valid metrics (total_assets >= 0, availability_rate, serviceability_rate)",
    isset($analytics['kpi']['total_assets'], $analytics['kpi']['availability_rate'], $analytics['kpi']['serviceability_rate']) &&
    $analytics['kpi']['total_assets'] >= 0
);

assertCondition(
    "Asset Dynamics contains status, condition, categories, and locations distributions",
    isset(
        $analytics['asset_dynamics']['status_distribution']['labels'],
        $analytics['asset_dynamics']['condition_distribution']['labels'],
        $analytics['asset_dynamics']['top_categories']['labels'],
        $analytics['asset_dynamics']['top_locations']['labels']
    )
);

assertCondition(
    "Circulation velocity series contains aligned labels and transaction counts",
    isset(
        $analytics['circulation']['velocity_series']['labels'],
        $analytics['circulation']['velocity_series']['borrows'],
        $analytics['circulation']['velocity_series']['returns']
    ) && count($analytics['circulation']['velocity_series']['borrows']) === count($analytics['circulation']['velocity_series']['labels'])
);

assertCondition(
    "Incidents perspective contains severity, settlement, and financial summary",
    isset(
        $analytics['incidents']['severity_distribution']['labels'],
        $analytics['incidents']['settlement_distribution']['labels'],
        $analytics['incidents']['financial_summary']['total_cost']
    )
);

// Test timeframe variations
$timeframes = ['7d', '90d', 'ytd', 'all'];
foreach ($timeframes as $tf) {
    $tfData = AnalyticsController::computeAnalytics($db, $tf);
    assertCondition(
        "Timeframe '{$tf}' computes successfully with label '{$tfData['metadata']['timeframe_label']}'",
        !empty($tfData['metadata']['timeframe_label']) && isset($tfData['kpi']['total_assets'])
    );
}

// ─────────────────────────────────────────────────────────────────
// Suite 3: OWASP Formula Injection Neutralization
// ─────────────────────────────────────────────────────────────────
echo "\n--- Suite 3: OWASP Formula Injection Protection ---\n";

$payload1 = '=SUM(1+1)';
$neutralized1 = Item::neutralizeFormula($payload1);
assertCondition(
    "Formula '=SUM(1+1)' is neutralized with leading quote: '{$neutralized1}'",
    $neutralized1 === "'=SUM(1+1)"
);

$payload2 = '+12345';
$neutralized2 = Item::neutralizeFormula($payload2);
assertCondition(
    "Payload '+12345' is neutralized: '{$neutralized2}'",
    $neutralized2 === "'+12345"
);

$payload3 = '@cmd|/c calc!A0';
$neutralized3 = Item::neutralizeFormula($payload3);
assertCondition(
    "Payload '@cmd...' is neutralized: '{$neutralized3}'",
    $neutralized3 === "'@cmd|/c calc!A0"
);

$safePayload = 'Dell OptiPlex 7090';
assertCondition(
    "Safe string 'Dell OptiPlex 7090' remains unquoted: '" . Item::neutralizeFormula($safePayload) . "'",
    Item::neutralizeFormula($safePayload) === 'Dell OptiPlex 7090'
);

// ─────────────────────────────────────────────────────────────────
// Suite 4: View Output Verification
// ─────────────────────────────────────────────────────────────────
echo "\n--- Suite 4: View Output Verification ---\n";

// Render executive dashboard view in buffer
$_SESSION['role'] = 'executive_admin';
$_SESSION['user_name'] = 'Campus Director';

ob_start();
$analyticsData = $analytics;
$categoriesList = AnalyticsController::getAvailableCategories($db);
$locationsList = AnalyticsController::getAvailableLocations($db);
require ROOT_PATH . '/views/dashboard/executive.php';
$renderedHtml = ob_get_clean();

assertCondition(
    "Executive Overview renders successfully with HTTP 200 equivalent (non-empty output)",
    !empty($renderedHtml) && strlen($renderedHtml) > 500
);

assertCondition(
    "Executive Overview includes the Global Analytics Toolbar and Segmented Controls",
    str_contains($renderedHtml, 'analytics-toolbar') && str_contains($renderedHtml, 'segmented-group')
);

assertCondition(
    "Executive Overview includes all 4 Analytical Perspective Tabs",
    str_contains($renderedHtml, 'data-target="tab-summary"') &&
    str_contains($renderedHtml, 'data-target="tab-assets"') &&
    str_contains($renderedHtml, 'data-target="tab-velocity"') &&
    str_contains($renderedHtml, 'data-target="tab-incidents"')
);

assertCondition(
    "Executive Overview includes all Chart.js canvas elements",
    str_contains($renderedHtml, 'summary-health-chart') &&
    str_contains($renderedHtml, 'summary-velocity-chart') &&
    str_contains($renderedHtml, 'summary-location-chart') &&
    str_contains($renderedHtml, 'summary-category-chart') &&
    str_contains($renderedHtml, 'dynamics-condition-chart') &&
    str_contains($renderedHtml, 'velocity-detailed-chart') &&
    str_contains($renderedHtml, 'incident-severity-chart')
);

assertCondition(
    "Executive Overview has REMOVED the raw 'Institutional Assets Register' table",
    !str_contains($renderedHtml, 'exec-inventory-table')
);

assertCondition(
    "Executive Overview has REMOVED the raw 'Recent Audit Activity' table",
    !str_contains($renderedHtml, 'exec-audit-table')
);

assertCondition(
    "Executive Overview includes the Report Configurator Modal and Section Checkbox Grid",
    str_contains($renderedHtml, 'modal-report-configurator') &&
    str_contains($renderedHtml, 'report-preset-pills') &&
    str_contains($renderedHtml, 'rpt-sections-grid') &&
    str_contains($renderedHtml, 'btn-open-report-configurator')
);

// Render Full Print Briefing View
ob_start();
$data = $analytics;
$activeSections = [
    'kpi' => true, 'status' => true, 'condition' => true, 'velocity' => true,
    'categories' => true, 'locations' => true, 'dow' => true, 'incidents' => true, 'signatories' => true
];
require ROOT_PATH . '/views/reports/executive_briefing.php';
$briefingHtml = ob_get_clean();

assertCondition(
    "Executive Briefing view renders institutional header and printable tables",
    str_contains($briefingHtml, 'Executive Institutional Property &amp; Circulation Report') &&
    str_contains($briefingHtml, 'Supply Office &amp; Custodian') &&
    str_contains($briefingHtml, 'Campus Director')
);

assertCondition(
    "Executive Briefing view includes high-DPI vector print charts when enabled",
    str_contains($briefingHtml, 'rpt-status-chart') &&
    str_contains($briefingHtml, 'rpt-condition-chart') &&
    str_contains($briefingHtml, 'rpt-velocity-chart') &&
    str_contains($briefingHtml, 'rpt-dow-chart')
);

// Render Filtered Print Briefing View (Summary Only: KPI + Status only)
ob_start();
$data = $analytics;
$activeSections = [
    'kpi' => true, 'status' => true, 'condition' => false, 'velocity' => false,
    'categories' => false, 'locations' => false, 'dow' => false, 'incidents' => false, 'signatories' => false
];
require ROOT_PATH . '/views/reports/executive_briefing.php';
$filteredBriefingHtml = ob_get_clean();

$condStatus = str_contains($filteredBriefingHtml, 'rpt-status-chart');
$condNoVel = !str_contains($filteredBriefingHtml, 'rpt-velocity-chart');
$condNoDow = !str_contains($filteredBriefingHtml, 'rpt-dow-chart');
$condNoSign = !str_contains($filteredBriefingHtml, '<div class="signatory-block">');

assertCondition(
    "Filtered Briefing includes selected sections (KPI, Status) and excludes omitted ones (Velocity, Signatories)",
    $condStatus && $condNoVel && $condNoDow && $condNoSign
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
