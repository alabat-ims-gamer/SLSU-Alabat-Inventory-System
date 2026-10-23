<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

// Simulation function representing index.php routing resolution logic
function resolveRoutingPath(string $requestUri, string $scriptName): array {
    $_SERVER['SCRIPT_NAME'] = $scriptName;
    $_SERVER['REQUEST_URI'] = $requestUri;
    \App\Helpers\Url::reset();

    $basePath = \App\Helpers\Url::basePath();
    $redirectUrl = null;

    // Handle legacy request paths on Railway
    if ($basePath === '' && strpos($requestUri, '/slsu-inventory/public') === 0) {
        $cleanUri = substr($requestUri, strlen('/slsu-inventory/public'));
        $redirectUrl = ($cleanUri === '' || $cleanUri === '/') ? '/' : $cleanUri;
        return ['path' => null, 'redirect' => $redirectUrl, 'code' => 301];
    }

    // Strip active base path if present
    if (!empty($basePath) && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    $uriParts = explode('?', $requestUri);
    $path = $uriParts[0];

    if ($path === '' || $path === '/') {
        $path = '/';
    }

    return ['path' => $path, 'redirect' => null, 'code' => 200];
}

// Test 1: XAMPP Normalization
$res1 = resolveRoutingPath('/slsu-inventory/public/records?page=2', '/slsu-inventory/public/index.php');
assert($res1['path'] === '/records', "XAMPP path should be /records, got: " . $res1['path']);
assert($res1['redirect'] === null, "XAMPP should not redirect valid path");

// Test 2: Railway Root Normalization
$res2 = resolveRoutingPath('/records?page=2', '/index.php');
assert($res2['path'] === '/records', "Railway path should be /records, got: " . $res2['path']);
assert($res2['redirect'] === null, "Railway should not redirect valid path");

// Test 3: Railway Legacy 301 Redirect
$res3 = resolveRoutingPath('/slsu-inventory/public/admin/users', '/index.php');
assert($res3['redirect'] === '/admin/users', "Legacy path should redirect to /admin/users, got: " . $res3['redirect']);
assert($res3['code'] === 301, "Legacy redirect should be 301");

// Test 4: Dynamic unauth redirect matches environment
$_SERVER['SCRIPT_NAME'] = '/slsu-inventory/public/index.php';
\App\Helpers\Url::reset();
assert(url('/login') === '/slsu-inventory/public/login', "Unauth redirect in XAMPP must be /slsu-inventory/public/login");

$_SERVER['SCRIPT_NAME'] = '/index.php';
\App\Helpers\Url::reset();
assert(url('/login') === '/login', "Unauth redirect in Railway must be /login");

echo "[PASS] RouterNormalizationTest passed successfully!\n";
