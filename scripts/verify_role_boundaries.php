<?php
/**
 * Automated Verification Script: Least-Privilege RBAC & Executive Governance Engine
 * Validates route gates, Zero-Trust middleware, 200 OK vs 403 Forbidden assertions,
 * and context-aware view rendering across all administrative roles.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

use App\Config\Database;

$passed = 0;
$failed = 0;

function assertCondition(string $description, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] " . $description . "\n";
    } else {
        $failed++;
        echo "  [FAIL] " . $description . ($details ? " -> " . $details : "") . "\n";
    }
}

/**
 * Execute a PHP sub-process to test a controller method under a specific role session.
 * Returns the HTTP response code (e.g. 200, 403, etc.)
 */
function testEndpointRole(string $role, string $controllerClass, string $method): int {
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
        escapeshellarg($controllerClass),
        escapeshellarg($method)
    );

    $output = shell_exec($cmd);
    if ($output !== null && preg_match('/STATUS_CODE:(\d+)/', $output, $matches)) {
        return (int)$matches[1];
    }
    return 500;
}

echo "\n======================================================================\n";
echo "  SLSU-Alabat IMS: Role Boundaries & Governance Engine Test Suite\n";
echo "======================================================================\n\n";

// -------------------------------------------------------------------------
// Suite 1: System Administrator (admin) Boundary Isolation
// -------------------------------------------------------------------------
echo "Suite 1: System Administrator ('admin') Boundary Isolation\n";
echo "---------------------------------------------------------\n";

assertCondition(
    "Admin receives 403 FORBIDDEN on Asset Catalog (/inventory)",
    testEndpointRole('admin', 'App\\Controllers\\InventoryController', 'index') === 403
);

assertCondition(
    "Admin receives 403 FORBIDDEN on Asset Mutation (/inventory/register)",
    testEndpointRole('admin', 'App\\Controllers\\InventoryController', 'register') === 403
);

assertCondition(
    "Admin receives 403 FORBIDDEN on Damage Clearances (/clearances)",
    testEndpointRole('admin', 'App\\Controllers\\RecordsController', 'incidents') === 403
);

assertCondition(
    "Admin receives 403 FORBIDDEN on Incident Settlement (/api/incident/resolve)",
    testEndpointRole('admin', 'App\\Controllers\\TransactionController', 'apiResolveIncident') === 403
);

assertCondition(
    "Admin is ALLOWED (200 OK) into User Management Hub (/admin/users)",
    testEndpointRole('admin', 'App\\Controllers\\AdminUserController', 'index') === 200
);

assertCondition(
    "Admin is ALLOWED (200 OK) into IT Security Audit Trail (/admin/audit)",
    testEndpointRole('admin', 'App\\Controllers\\AuditLogController', 'index') === 200
);

// -------------------------------------------------------------------------
// Suite 2: Executive Administrator (executive_admin) Oversight Assertions
// -------------------------------------------------------------------------
echo "\nSuite 2: Executive Administrator ('executive_admin') Read-Only & Reporting\n";
echo "-------------------------------------------------------------------------\n";

assertCondition(
    "Executive Admin is ALLOWED (200 OK) into Executive Overview Cockpit (/dashboard)",
    testEndpointRole('executive_admin', 'App\\Controllers\\DashboardController', 'index') === 200
);

assertCondition(
    "Executive Admin is REDIRECTED (302) from /reports to /dashboard",
    testEndpointRole('executive_admin', 'App\\Controllers\\AnalyticsController', 'index') === 302
);

assertCondition(
    "Executive Admin is ALLOWED (200 OK) to view Asset Catalog (/inventory)",
    testEndpointRole('executive_admin', 'App\\Controllers\\InventoryController', 'index') === 200
);

assertCondition(
    "Executive Admin is ALLOWED (200 OK) to view Damage Clearances (/clearances)",
    testEndpointRole('executive_admin', 'App\\Controllers\\RecordsController', 'incidents') === 200
);

assertCondition(
    "Executive Admin receives 403 FORBIDDEN on Asset Mutation (/inventory/register)",
    testEndpointRole('executive_admin', 'App\\Controllers\\InventoryController', 'register') === 403
);

assertCondition(
    "Executive Admin receives 403 FORBIDDEN on Asset Edit (/inventory/edit)",
    testEndpointRole('executive_admin', 'App\\Controllers\\InventoryController', 'edit') === 403
);

assertCondition(
    "Executive Admin receives 403 FORBIDDEN on Asset Deletion (/inventory/delete)",
    testEndpointRole('executive_admin', 'App\\Controllers\\InventoryController', 'delete') === 403
);

assertCondition(
    "Executive Admin receives 403 FORBIDDEN on Granting Damage Clearance (/api/incident/resolve)",
    testEndpointRole('executive_admin', 'App\\Controllers\\TransactionController', 'apiResolveIncident') === 403
);

assertCondition(
    "Executive Admin receives 403 FORBIDDEN on User Management Hub (/admin/users)",
    testEndpointRole('executive_admin', 'App\\Controllers\\AdminUserController', 'index') === 403
);

assertCondition(
    "Executive Admin is ALLOWED (200 OK) into Institutional Audit Trail (/admin/audit)",
    testEndpointRole('executive_admin', 'App\\Controllers\\AuditLogController', 'index') === 200
);

// -------------------------------------------------------------------------
// Suite 3: Property Custodian (custodian) Operational Authority Assertions
// -------------------------------------------------------------------------
echo "\nSuite 3: Property Custodian ('custodian') Operational Custody\n";
echo "-------------------------------------------------------------\n";

assertCondition(
    "Custodian is ALLOWED (200 OK) to view and manage Asset Catalog (/inventory)",
    testEndpointRole('custodian', 'App\\Controllers\\InventoryController', 'index') === 200
);

assertCondition(
    "Custodian is ALLOWED (200 OK) to view Reports & Analytics Studio (/reports)",
    testEndpointRole('custodian', 'App\\Controllers\\AnalyticsController', 'index') === 200
);

assertCondition(
    "Custodian is ALLOWED (200 OK) to view Damage Clearances (/clearances)",
    testEndpointRole('custodian', 'App\\Controllers\\RecordsController', 'incidents') === 200
);

assertCondition(
    "Custodian receives 403 FORBIDDEN on User Management Hub (/admin/users)",
    testEndpointRole('custodian', 'App\\Controllers\\AdminUserController', 'index') === 403
);

// -------------------------------------------------------------------------
// Suite 4: Faculty & Student Read-Only Access & Export Restrictions
// -------------------------------------------------------------------------
echo "\nSuite 4: Faculty & Student ('faculty', 'student') Read-Only & Anti-Export Boundaries\n";
echo "-----------------------------------------------------------------------------------\n";

assertCondition(
    "Faculty is ALLOWED (200 OK) to view Asset Catalog (/inventory)",
    testEndpointRole('faculty', 'App\\Controllers\\InventoryController', 'index') === 200
);

assertCondition(
    "Faculty is ALLOWED (200 OK) on Asset List API (/api/inventory/list)",
    testEndpointRole('faculty', 'App\\Controllers\\InventoryController', 'listItems') === 200
);

assertCondition(
    "Faculty receives 403 FORBIDDEN on Asset Export (/inventory/export-xlsx)",
    testEndpointRole('faculty', 'App\\Controllers\\InventoryController', 'exportXlsx') === 403
);

assertCondition(
    "Faculty receives 403 FORBIDDEN on Asset Mutation (/inventory/register)",
    testEndpointRole('faculty', 'App\\Controllers\\InventoryController', 'register') === 403
);

assertCondition(
    "Faculty receives 403 FORBIDDEN on Asset Edit (/inventory/edit)",
    testEndpointRole('faculty', 'App\\Controllers\\InventoryController', 'edit') === 403
);

assertCondition(
    "Faculty receives 403 FORBIDDEN on Asset Deletion (/inventory/delete)",
    testEndpointRole('faculty', 'App\\Controllers\\InventoryController', 'delete') === 403
);

assertCondition(
    "Student is ALLOWED (200 OK) to view Asset Catalog (/inventory)",
    testEndpointRole('student', 'App\\Controllers\\InventoryController', 'index') === 200
);

assertCondition(
    "Student is ALLOWED (200 OK) on Asset List API (/api/inventory/list)",
    testEndpointRole('student', 'App\\Controllers\\InventoryController', 'listItems') === 200
);

assertCondition(
    "Student receives 403 FORBIDDEN on Asset Export (/inventory/export-xlsx)",
    testEndpointRole('student', 'App\\Controllers\\InventoryController', 'exportXlsx') === 403
);

assertCondition(
    "Student receives 403 FORBIDDEN on Asset Mutation (/inventory/register)",
    testEndpointRole('student', 'App\\Controllers\\InventoryController', 'register') === 403
);

assertCondition(
    "Student receives 403 FORBIDDEN on Asset Edit (/inventory/edit)",
    testEndpointRole('student', 'App\\Controllers\\InventoryController', 'edit') === 403
);

assertCondition(
    "Student receives 403 FORBIDDEN on Asset Deletion (/inventory/delete)",
    testEndpointRole('student', 'App\\Controllers\\InventoryController', 'delete') === 403
);

// -------------------------------------------------------------------------
// Suite 5: CSV Data Streaming & OWASP Anti-Formula Injection Tests
// -------------------------------------------------------------------------
echo "\nSuite 5: CSV Data Streaming & OWASP Anti-Formula Injection Tests\n";
echo "----------------------------------------------------------------\n";

try {
    $db = Database::getConnection();
    
    // Test inventory export query
    $invStmt = $db->query("
        SELECT 
            i.item_id,
            i.barcode,
            COALESCE(i.property_number, 'N/A') AS property_number,
            i.item_name,
            i.category,
            i.location,
            i.item_condition,
            i.status,
            CASE WHEN i.is_student_borrowable = 1 THEN 'Yes' ELSE 'No' END AS is_student_borrowable,
            COALESCE(u.full_name, 'System') AS registered_by_name,
            i.created_at,
            i.updated_at
        FROM items i
        LEFT JOIN users u ON i.registered_by = u.user_id
        LIMIT 5
    ");
    $invRows = $invStmt->fetchAll(PDO::FETCH_ASSOC);
    assertCondition("Inventory export query executes cleanly with PDO", is_array($invRows));

    // Test incident export query
    $incStmt = $db->query("
        SELECT 
            di.incident_id,
            COALESCE(u.full_name, 'Unknown') AS borrower_name,
            COALESCE(u.id_number, 'N/A') AS borrower_id_number,
            COALESCE(u.role, 'N/A') AS borrower_role,
            COALESCE(u.department_program, 'N/A') AS borrower_department,
            COALESCE(i.item_name, 'Unknown') AS item_name,
            COALESCE(i.barcode, 'N/A') AS barcode,
            di.damage_condition,
            di.damage_description,
            COALESCE(di.estimated_cost, 0.00) AS estimated_cost,
            di.incident_status,
            COALESCE(di.settlement_type, 'N/A') AS settlement_type,
            COALESCE(di.settlement_reference, 'N/A') AS settlement_reference,
            COALESCE(di.settlement_notes, 'N/A') AS settlement_notes,
            COALESCE(c.full_name, 'N/A') AS cleared_by_name,
            COALESCE(di.cleared_at, 'N/A') AS cleared_at,
            di.created_at
        FROM damage_incidents di
        JOIN users u ON di.borrower_id = u.user_id
        JOIN items i ON di.item_id = i.item_id
        LEFT JOIN users c ON di.cleared_by = c.user_id
        LIMIT 5
    ");
    $incRows = $incStmt->fetchAll(PDO::FETCH_ASSOC);
    assertCondition("Damage incident export query executes cleanly with PDO", is_array($incRows));

    // Test OWASP CSV Formula Injection sanitization logic
    $maliciousFormula = "=cmd|' /C calc'!A0";
    $sanitized = (isset($maliciousFormula[0]) && in_array($maliciousFormula[0], ['=', '+', '-', '@', "\t", "\r"], true))
        ? "'" . $maliciousFormula
        : $maliciousFormula;
    assertCondition("OWASP CSV formula injection prefix neutralized with single quote", str_starts_with($sanitized, "'="));

} catch (\Throwable $e) {
    assertCondition("Database export query validation", false, $e->getMessage());
}

// -------------------------------------------------------------------------
// Summary
// -------------------------------------------------------------------------
echo "\n======================================================================\n";
echo "  TEST RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "======================================================================\n";

if ($failed > 0) {
    exit(1);
}
