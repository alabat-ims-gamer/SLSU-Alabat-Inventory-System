<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

$db = require ROOT_PATH . '/config/database.php';

function assertTest(string $description, bool $condition, ?string $details = null): void {
    if ($condition) {
        echo "  [PASS] $description\n";
    } else {
        echo "  [FAIL] $description" . ($details ? " -> $details" : "") . "\n";
        exit(1);
    }
}

echo "\n=======================================================\n";
echo "  TEST SUITE: Unified Notification Hub & Auto-Pruning\n";
echo "=======================================================\n\n";

// 1. Fetch or create test user
echo "[1/6] Preparing test environment...\n";
$stmt = $db->query("SELECT user_id, role FROM users WHERE email LIKE '%admin%' OR role = 'admin' LIMIT 1");
$testUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$testUser) {
    $stmt = $db->query("SELECT user_id, role FROM users LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
}
$userId = (int)$testUser['user_id'];
$role   = (string)$testUser['role'];
assertTest("Found valid user for testing (User ID: $userId, Role: $role)", $userId > 0);

// Clean previous test notifications for test user
$db->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$userId]);

// 2. Test Basic Insert
echo "\n[2/6] Testing Notification Creation...\n";
$created = \App\Services\NotificationService::create(
    $db,
    $userId,
    \App\Services\NotificationService::CAT_LOAN_CHECKOUT,
    "Test Checkout",
    "You borrowed Test Item #1.",
    \App\Services\NotificationService::TYPE_SUCCESS,
    "/records"
);
assertTest("NotificationService::create inserted notification", $created);

$feed = \App\Services\NotificationService::getFeed($db, $userId);
assertTest("NotificationService::getFeed returns 1 item", count($feed['notifications']) === 1);
assertTest("Unread count is 1", $feed['unread_count'] === 1);
assertTest("Notification title matches", $feed['notifications'][0]['title'] === "Test Checkout");
assertTest("Relative time_ago is formatted", !empty($feed['notifications'][0]['time_ago']));

// 3. Test Auto-Pruning (Ring Buffer of 10 items)
echo "\n[3/6] Testing Auto-Prune Ring Buffer (Inserting 15 notifications)...\n";
for ($i = 2; $i <= 16; $i++) {
    \App\Services\NotificationService::create(
        $db,
        $userId,
        \App\Services\NotificationService::CAT_LOAN_DUE,
        "Test Alert #$i",
        "Automated notice payload $i",
        \App\Services\NotificationService::TYPE_INFO
    );
}

$feedAfter15 = \App\Services\NotificationService::getFeed($db, $userId);
$countInDbStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$countInDbStmt->execute([$userId]);
$totalInDb = (int)$countInDbStmt->fetchColumn();

assertTest("Database total rows for user is strictly capped at 10 (actual: $totalInDb)", $totalInDb === 10);
assertTest("Feed contains exactly 10 notifications", count($feedAfter15['notifications']) === 10);
assertTest("Most recent notification is Test Alert #16", $feedAfter15['notifications'][0]['title'] === "Test Alert #16");
assertTest("Oldest preserved notification is Test Alert #7", $feedAfter15['notifications'][9]['title'] === "Test Alert #7");

// 4. Test Mark As Read
echo "\n[4/6] Testing Mark As Read Actions...\n";
$singleNotifId = (int)$feedAfter15['notifications'][0]['notification_id'];
$markedSingle = \App\Services\NotificationService::markAsRead($db, $userId, $singleNotifId);
assertTest("Marked single notification #$singleNotifId as read", $markedSingle);

$feedAfterSingleRead = \App\Services\NotificationService::getFeed($db, $userId);
assertTest("Unread count reduced to 9", $feedAfterSingleRead['unread_count'] === 9);
assertTest("First item is_read is true", $feedAfterSingleRead['notifications'][0]['is_read'] === true);
assertTest("Second item is_read is false", $feedAfterSingleRead['notifications'][1]['is_read'] === false);

$markedAll = \App\Services\NotificationService::markAsRead($db, $userId);
assertTest("Marked all notifications as read", $markedAll);
$feedAfterAllRead = \App\Services\NotificationService::getFeed($db, $userId);
assertTest("Unread count is now 0", $feedAfterAllRead['unread_count'] === 0);

// 5. Test Notification Preferences Filtering
echo "\n[5/6] Testing Preference Gating...\n";
$disabledPrefs = [
    'alert_due_reminders'   => false,
    'alert_overdue_alerts'  => true,
    'alert_security_logins' => true,
];
\App\Models\User::updateNotificationPreferences($db, $userId, $disabledPrefs);
$_SESSION['notification_preferences'] = $disabledPrefs;

// Attempting to create a CAT_LOAN_DUE notification (should be suppressed)
$suppressed = \App\Services\NotificationService::create(
    $db,
    $userId,
    \App\Services\NotificationService::CAT_LOAN_DUE,
    "Suppressed Reminder",
    "This should not be inserted"
);
assertTest("NotificationService::create suppressed notification disabled by user preference", $suppressed === false);

// Attempting to create a CAT_LOAN_OVERDUE notification (should be allowed)
$allowed = \App\Services\NotificationService::create(
    $db,
    $userId,
    \App\Services\NotificationService::CAT_LOAN_OVERDUE,
    "Allowed Overdue Alert",
    "This alert is enabled and should be delivered",
    \App\Services\NotificationService::TYPE_DANGER
);
assertTest("NotificationService::create allowed enabled alert", $allowed === true);

// Restore default preferences
$defaultPrefs = [
    'alert_due_reminders'   => true,
    'alert_overdue_alerts'  => true,
    'alert_security_logins' => true,
];
\App\Models\User::updateNotificationPreferences($db, $userId, $defaultPrefs);
$_SESSION['notification_preferences'] = $defaultPrefs;

// 6. Test Sidebar & Topbar Badges
echo "\n[6/6] Testing Unified Sidebar & Topbar Badges...\n";
$badges = \App\Services\NotificationService::getSidebarBadges($db, $userId, $role);
assertTest("getSidebarBadges returns unread_notifs count >= 1", $badges['unread_notifs'] >= 1);
assertTest("getSidebarBadges returns array structure with expected keys", 
    isset($badges['overdue'], $badges['pending'], $badges['holds'], $badges['my_obligations'], $badges['unread_notifs'])
);

echo "\n=======================================================\n";
echo "  [SUCCESS] All Notification Hub tests passed cleanly!\n";
echo "=======================================================\n\n";
