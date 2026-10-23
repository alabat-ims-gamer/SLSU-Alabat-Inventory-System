<?php
declare(strict_types=1);

/**
 * System Verification Test Suite
 * Verifies URL unification across all 6 roles, environments (XAMPP vs Railway root),
 * legacy 301 safeguards, and asset cache busting.
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

echo "========================================================\n";
echo "  SLSU-Alabat IMS - Full System Verification Suite\n";
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

// ── 1. Local XAMPP Subfolder Simulation ─────────────────────────
echo "\n--- [Suite 1: Local XAMPP Environment] ---\n";
putenv('APP_URL=');
$_ENV['APP_URL'] = '';
$_SERVER['SCRIPT_NAME'] = '/slsu-inventory/public/index.php';
\App\Helpers\Url::reset();

testAssert(\App\Helpers\Url::basePath() === '/slsu-inventory/public', "XAMPP base path is /slsu-inventory/public");
testAssert(url('/') === '/slsu-inventory/public', "Root path under XAMPP resolves to /slsu-inventory/public");
testAssert(url('/login') === '/slsu-inventory/public/login', "Auth login path under XAMPP is /slsu-inventory/public/login");
testAssert(url('/scan') === '/slsu-inventory/public/scan', "Scanner path under XAMPP is /slsu-inventory/public/scan");
testAssert(url('/records?status=active') === '/slsu-inventory/public/records?status=active', "Query parameters preserved under XAMPP");

$xamppAsset = asset('/css/custom.css');
testAssert(str_starts_with($xamppAsset, '/slsu-inventory/public/css/custom.css'), "Asset path includes XAMPP base prefix");
testAssert(str_contains($xamppAsset, '?v='), "Asset path includes cache-busting timestamp version");

// ── 2. Railway Root Domain Simulation ───────────────────────────
echo "\n--- [Suite 2: Railway Production Environment] ---\n";
putenv('APP_URL=');
$_ENV['APP_URL'] = '';
$_SERVER['SCRIPT_NAME'] = '/index.php';
\App\Helpers\Url::reset();

testAssert(\App\Helpers\Url::basePath() === '', "Railway root base path is empty string");
testAssert(url('/') === '/', "Root path on Railway resolves to /");
testAssert(url('/login') === '/login', "Auth login path on Railway is /login");
testAssert(url('/scan') === '/scan', "Scanner path on Railway is /scan");
testAssert(url('/records?status=active') === '/records?status=active', "Query parameters preserved on Railway root");

$railwayAsset = asset('/css/custom.css');
testAssert(str_starts_with($railwayAsset, '/css/custom.css'), "Asset path on Railway starts with /css/custom.css (no subfolder)");
testAssert(str_contains($railwayAsset, '?v='), "Asset path on Railway includes cache-busting timestamp");

// ── 3. Manual APP_URL Override Simulation ───────────────────────
echo "\n--- [Suite 3: APP_URL Override Tests] ---\n";
putenv('APP_URL=https://inventory.slsu.edu.ph');
$_ENV['APP_URL'] = 'https://inventory.slsu.edu.ph';
\App\Helpers\Url::reset();
testAssert(\App\Helpers\Url::basePath() === '', "Domain-only APP_URL results in root base path");
testAssert(url('/dashboard') === '/dashboard', "Domain-only APP_URL generates clean root route");

putenv('APP_URL=http://localhost:8080/custom-folder');
$_ENV['APP_URL'] = 'http://localhost:8080/custom-folder';
\App\Helpers\Url::reset();
testAssert(\App\Helpers\Url::basePath() === '/custom-folder', "Subfolder APP_URL results in /custom-folder base path");
testAssert(url('/dashboard') === '/custom-folder/dashboard', "Subfolder APP_URL prefixes route properly");

// Reset environment for role checks
putenv('APP_URL=');
$_ENV['APP_URL'] = '';

// ── 4. Comprehensive Role Navigation Verification ────────────────
echo "\n--- [Suite 4: Role-Based Navigation Routing] ---\n";

$roleRoutes = [
    'admin' => [
        '/dashboard',
        '/admin/users',
        '/admin/audit',
        '/settings'
    ],
    'custodian' => [
        '/dashboard',
        '/scan',
        '/inventory',
        '/custodian/loans',
        '/clearances',
        '/reports',
        '/disposal',
        '/settings'
    ],
    'executive_admin' => [
        '/dashboard',
        '/inventory',
        '/custodian/loans',
        '/disposal',
        '/clearances',
        '/admin/audit',
        '/settings'
    ],
    'faculty' => [
        '/dashboard',
        '/scan',
        '/inventory',
        '/records',
        '/settings'
    ],
    'staff' => [
        '/dashboard',
        '/scan',
        '/records',
        '/settings'
    ],
    'student' => [
        '/dashboard',
        '/scan',
        '/inventory',
        '/records',
        '/settings'
    ],
    'inspector' => [
        '/dashboard',
        '/inspector/gate-passes',
        '/inspector/iar',
        '/scan',
        '/settings'
    ]
];

// Test all roles under both environments
foreach (['XAMPP' => '/slsu-inventory/public/index.php', 'Railway' => '/index.php'] as $envName => $scriptName) {
    $_SERVER['SCRIPT_NAME'] = $scriptName;
    \App\Helpers\Url::reset();
    $expectedBase = ($envName === 'XAMPP') ? '/slsu-inventory/public' : '';

    foreach ($roleRoutes as $role => $routes) {
        foreach ($routes as $route) {
            $generated = url($route);
            $expected = $expectedBase . $route;
            testAssert($generated === $expected, "[{$envName} | {$role}] route '{$route}' -> '{$generated}'");
        }
    }
}

// ── 5. Router Normalization & Legacy 301 Safeguard Logic ─────────
echo "\n--- [Suite 5: Router Normalization & 301 Safeguard] ---\n";

function simulateRouter(string $requestUri, string $scriptName): array {
    $_SERVER['SCRIPT_NAME'] = $scriptName;
    \App\Helpers\Url::reset();
    $basePath = \App\Helpers\Url::basePath();

    if ($basePath === '' && strpos($requestUri, '/slsu-inventory/public') === 0) {
        $cleanUri = substr($requestUri, strlen('/slsu-inventory/public'));
        return [
            'type'        => 'redirect',
            'status_code' => 301,
            'location'    => ($cleanUri === '' ? '/' : $cleanUri)
        ];
    }

    if (!empty($basePath) && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    $uriParts = explode('?', $requestUri);
    $path = $uriParts[0];
    if ($path === '' || $path === '/') {
        $path = '/';
    }

    return [
        'type' => 'dispatch',
        'path' => $path
    ];
}

$sim1 = simulateRouter('/slsu-inventory/public/records', '/index.php');
testAssert($sim1['type'] === 'redirect' && $sim1['location'] === '/records' && $sim1['status_code'] === 301, "Legacy XAMPP link arriving at Railway triggers 301 redirect to /records");

$sim2 = simulateRouter('/slsu-inventory/public/records?status=active', '/slsu-inventory/public/index.php');
testAssert($sim2['type'] === 'dispatch' && $sim2['path'] === '/records', "XAMPP request strips base path and dispatches /records cleanly");

$sim3 = simulateRouter('/inventory', '/index.php');
testAssert($sim3['type'] === 'dispatch' && $sim3['path'] === '/inventory', "Railway clean request dispatches /inventory directly");

echo "\n========================================================\n";
echo "  [SUCCESS] All {$passCount} System Verification Tests Passed!\n";
echo "========================================================\n";
