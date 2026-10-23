<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

$db = require ROOT_PATH . '/config/database.php';

use App\Models\User;
use App\Models\AuditLog;
use App\Helpers\Security;

echo "=======================================================\n";
echo " SLSU-Alabat IMS: User Settings & Security Verification\n";
echo "=======================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest(string $name, bool $condition) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
    }
}

// 1. Test Finding user and checking columns
$admin = User::findByLoginCredential($db, 'admin');
assertTest("Admin user exists in DB", $admin !== null);
assertTest("avatar_path column is accessible", array_key_exists('avatar_path', $admin));
assertTest("phone_number column is accessible", array_key_exists('phone_number', $admin));
assertTest("session_version column is accessible", array_key_exists('session_version', $admin));
assertTest("notification_preferences column is accessible", array_key_exists('notification_preferences', $admin));

// 2. Test User Profile Update
$adminId = (int)$admin['user_id'];
$updateRes = User::updateProfile($db, $adminId, [
    'full_name' => 'System Administrator',
    'department_program' => 'Administration & IT Services',
    'phone_number' => '+63 912 345 6789'
]);
assertTest("User::updateProfile updates full_name, dept, phone", $updateRes);

$reloadedAdmin = User::findById($db, $adminId);
assertTest("Updated phone number persists in database", $reloadedAdmin['phone_number'] === '+63 912 345 6789');

// 3. Test Email Uniqueness Check
$isTakenSelf = User::isEmailTakenByOther($db, $reloadedAdmin['email'], $adminId);
assertTest("isEmailTakenByOther ignores current user's own email", !$isTakenSelf);

// 4. Test Notification Preferences Update
$samplePrefs = [
    'email_due_reminders' => true,
    'email_overdue_alerts' => false,
    'email_security_logins' => true,
];
$prefRes = User::updateNotificationPreferences($db, $adminId, $samplePrefs);
assertTest("User::updateNotificationPreferences saves JSON payload", $prefRes);

$reloadedAdmin2 = User::findById($db, $adminId);
$decoded = json_decode($reloadedAdmin2['notification_preferences'], true);
assertTest("Decoded notification preferences match input", ($decoded['email_overdue_alerts'] ?? null) === false);

// 5. Test Session Version Invalidation Bump
$currVer = (int)($reloadedAdmin2['session_version'] ?? 1);
$newVer = User::bumpSessionVersion($db, $adminId);
assertTest("User::bumpSessionVersion increments version counter ($currVer -> $newVer)", $newVer > $currVer);

// 6. Test Audit Logging for Settings Actions
AuditLog::log($db, $adminId, 'PROFILE_UPDATED', 'Verification test profile update log');
$userLogs = AuditLog::getUserLogs($db, $adminId, 5);
assertTest("AuditLog::getUserLogs retrieves recent user events", count($userLogs) > 0 && $userLogs[0]['action_type'] === 'PROFILE_UPDATED');

// 7. Test GD Library and WebP Generation
$gdAvailable = extension_loaded('gd') && function_exists('imagewebp');
assertTest("PHP GD extension and WebP export support available", $gdAvailable);

if ($gdAvailable) {
    $img = imagecreatetruecolor(64, 64);
    $bg = imagecolorallocate($img, 83, 58, 253);
    imagefill($img, 0, 0, $bg);
    $testPath = ROOT_PATH . '/public/uploads/avatars/test_verify.webp';
    $webSaved = imagewebp($img, $testPath, 90);
    imagedestroy($img);
    assertTest("WebP avatar test image successfully created and saved", $webSaved && file_exists($testPath));
    if (file_exists($testPath)) {
        @unlink($testPath);
    }
}

// 8. Test Conditional Safe Deletion vs Deactivation logic
// Create a temporary mock user with no transaction history
$testIdNumber = 'TEST-' . time();
$tempUserId = User::create($db, [
    'id_number' => $testIdNumber,
    'username' => 'testuser_' . time(),
    'full_name' => 'Temporary Test User',
    'email' => 'testuser_' . time() . '@gmail.com',
    'password' => 'SecurePass123!',
    'department_program' => 'Testing Dept'
]);
assertTest("Temporary user created for deletion tests (ID: $tempUserId)", $tempUserId > 0);

$hasHist = User::hasTransactionHistory($db, $tempUserId);
assertTest("Unencumbered user has 0 transaction history", !$hasHist);

User::safeDelete($db, $tempUserId);
$deletedUser = User::findById($db, $tempUserId);
assertTest("User::safeDelete permanently purges unencumbered user", $deletedUser === null);

echo "\n-------------------------------------------------------\n";
echo " Test Results: $testsPassed / $totalTests tests passed.\n";
echo "=======================================================\n";
