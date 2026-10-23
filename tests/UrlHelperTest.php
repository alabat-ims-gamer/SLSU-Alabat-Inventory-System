<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

// Ensure Url helper is loaded
require_once __DIR__ . '/../app/Helpers/Url.php';

// Test 1: XAMPP Subfolder Detection
$_SERVER['SCRIPT_NAME'] = '/slsu-inventory/public/index.php';
putenv('APP_URL=');
$_ENV['APP_URL'] = '';
\App\Helpers\Url::reset();
assert(\App\Helpers\Url::basePath() === '/slsu-inventory/public', "XAMPP basePath should be /slsu-inventory/public");
assert(url('/admin/users') === '/slsu-inventory/public/admin/users', "url('/admin/users') failed under XAMPP");
assert(url('/') === '/slsu-inventory/public', "url('/') under XAMPP should be /slsu-inventory/public");

// Test 2: Railway Root Domain Detection
$_SERVER['SCRIPT_NAME'] = '/index.php';
putenv('APP_URL=');
$_ENV['APP_URL'] = '';
\App\Helpers\Url::reset();
assert(\App\Helpers\Url::basePath() === '', "Railway basePath should be empty string");
assert(url('/admin/users') === '/admin/users', "url('/admin/users') failed under Railway root");
assert(url('/') === '/', "url('/') failed under Railway root");

// Test 3: Asset Helper
$assetPath = asset('/css/custom.css');
assert(str_starts_with($assetPath, '/css/custom.css'), "Asset path format invalid: " . $assetPath);

// Test 4: APP_URL Override
$_ENV['APP_URL'] = 'https://inventory.slsu.edu.ph/ims/public';
putenv('APP_URL=https://inventory.slsu.edu.ph/ims/public');
\App\Helpers\Url::reset();
assert(\App\Helpers\Url::basePath() === '/ims/public', "APP_URL override failed, got: " . \App\Helpers\Url::basePath());
assert(url('/scan') === '/ims/public/scan', "url('/scan') with APP_URL override failed");

echo "[PASS] UrlHelperTest passed successfully!\n";
