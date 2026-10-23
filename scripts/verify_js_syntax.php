<?php
/**
 * Validate all rendered Javascript across dashboard views
 */
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Test User';
$_SESSION['id_number'] = 'EMP-001';
$_SESSION['role'] = 'executive_admin';
$_SESSION['account_status'] = 'approved';

$db = App\Config\Database::getConnection();
$analyticsData = App\Controllers\AnalyticsController::computeAnalytics($db, '30d');
$categoriesList = App\Controllers\AnalyticsController::getAvailableCategories($db);
$locationsList = App\Controllers\AnalyticsController::getAvailableLocations($db);

$dashboards = [
    'executive' => __DIR__ . '/../views/dashboard/executive.php',
    'admin'     => __DIR__ . '/../views/dashboard/admin.php',
    'custodian' => __DIR__ . '/../views/dashboard/custodian.php',
];

$hasError = false;

foreach ($dashboards as $name => $file) {
    ob_start();
    include $file;
    $html = ob_get_clean();

    if (preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches)) {
        foreach ($matches[1] as $idx => $js) {
            $tempFile = __DIR__ . "/temp_{$name}_{$idx}.js";
            file_put_contents($tempFile, $js);
            $output = [];
            $returnVar = 0;
            exec("node -c \"{$tempFile}\" 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                echo "[FAIL] JS syntax error in {$name} (script #{$idx}):\n";
                echo implode("\n", $output) . "\n";
                $hasError = true;
            } else {
                echo "[PASS] {$name} dashboard JS script #{$idx} is syntactically valid.\n";
            }
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}

if (!$hasError) {
    echo "\nAll dashboard JavaScript blocks passed syntax verification!\n";
    exit(0);
} else {
    echo "\nOne or more JavaScript syntax errors found!\n";
    exit(1);
}
