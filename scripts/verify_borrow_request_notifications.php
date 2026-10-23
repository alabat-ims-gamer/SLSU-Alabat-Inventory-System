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
echo "  TEST SUITE: Borrow Request Accept & Deny Bell Notifications\n";
echo "=======================================================\n\n";

// 1. Prepare users (Student & Custodian)
echo "[1/4] Preparing test accounts and asset...\n";
$studentStmt = $db->query("SELECT user_id, full_name, email FROM users WHERE role = 'student' AND account_status = 'approved' LIMIT 1");
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    // Fallback: pick any non-custodian user
    $studentStmt = $db->query("SELECT user_id, full_name, email FROM users WHERE role != 'custodian' LIMIT 1");
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
}
$studentId = (int)$student['user_id'];

$custodianStmt = $db->query("SELECT user_id, full_name FROM users WHERE role = 'custodian' AND account_status = 'approved' LIMIT 1");
$custodian = $custodianStmt->fetch(PDO::FETCH_ASSOC);
if (!$custodian) {
    $custodianStmt = $db->query("SELECT user_id, full_name FROM users LIMIT 1");
    $custodian = $custodianStmt->fetch(PDO::FETCH_ASSOC);
}
$custodianId = (int)$custodian['user_id'];

// Create or find an available item
$itemStmt = $db->query("SELECT item_id, barcode, item_name, status FROM items WHERE status = 'available' LIMIT 1");
$item = $itemStmt->fetch(PDO::FETCH_ASSOC);
if (!$item) {
    $db->prepare("INSERT INTO items (barcode, item_name, category, location, status, is_student_borrowable) VALUES ('TEST-NOTIF-999', 'Notification Test Scope', 'Laboratory Instruments', 'Science Lab Room 101', 'available', 1)")->execute();
    $itemId = (int)$db->lastInsertId();
    $item = \App\Models\Item::findById($db, $itemId);
} else {
    $itemId = (int)$item['item_id'];
}

assertTest("Student user ID: $studentId, Custodian user ID: $custodianId, Item ID: $itemId", $studentId > 0 && $custodianId > 0 && $itemId > 0);

// Clear recent notifications for student and custodian to have clean state
$db->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$studentId]);
$db->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$custodianId]);

// 2. Test Accept Flow & Custodian Pending Borrows Badge
echo "\n[2/4] Testing Borrow Request Submission, Custodian Badge, and ACCEPTANCE...\n";
\App\Models\Item::submitBorrowRequest($db, $itemId, $studentId, "Research Project Experiment");

// Broadcast notification to custodians
\App\Services\NotificationService::broadcastToRole(
    $db,
    'custodian',
    \App\Services\NotificationService::CAT_LOAN_CHECKOUT,
    "New Borrow Request",
    "{$student['full_name']} submitted a borrow request for {$item['item_name']} ({$item['barcode']}). Awaiting pickup & approval at the counter.",
    \App\Services\NotificationService::TYPE_INFO,
    "/scan"
);

// Verify Custodian's sidebar badge has pending_borrows >= 1
$custodianBadges = \App\Services\NotificationService::getSidebarBadges($db, $custodianId, 'custodian');
assertTest("Custodian sidebar badge 'pending_borrows' is >= 1 (actual: {$custodianBadges['pending_borrows']})", $custodianBadges['pending_borrows'] >= 1);

// Verify Custodian received broadcast notification
$custodianFeed = \App\Services\NotificationService::getFeed($db, $custodianId);
assertTest("Custodian received 'New Borrow Request' notification", $custodianFeed['notifications'][0]['title'] === "New Borrow Request");

$deadline = date('Y-m-d H:i:s', strtotime('+4 hours'));
$txId = \App\Models\Transaction::createLoan($db, $itemId, $studentId, $deadline, "Research Project Experiment", $custodianId);

$notifSuccess = \App\Services\NotificationService::create(
    $db,
    $studentId,
    \App\Services\NotificationService::CAT_LOAN_CHECKOUT,
    "Borrow Request Approved",
    "Your borrow request for {$item['item_name']} ({$item['barcode']}) was approved! Equipment has been issued. Return is due on " . date('M j, Y h:i A', strtotime($deadline)) . ".",
    \App\Services\NotificationService::TYPE_SUCCESS,
    "/records"
);
assertTest("Approved notification created successfully", $notifSuccess);

$feed = \App\Services\NotificationService::getFeed($db, $studentId);
assertTest("Student feed has 1 unread notification", $feed['unread_count'] >= 1);
assertTest("Title is 'Borrow Request Approved'", $feed['notifications'][0]['title'] === "Borrow Request Approved");
assertTest("Category is LOAN_CHECKOUT", $feed['notifications'][0]['category'] === \App\Services\NotificationService::CAT_LOAN_CHECKOUT);
assertTest("Visual type is success", $feed['notifications'][0]['type'] === \App\Services\NotificationService::TYPE_SUCCESS);
assertTest("Action URL points to /records", $feed['notifications'][0]['action_url'] === "/records");

// Close out loan and return item to available
\App\Models\Transaction::processReturn($db, $itemId, 'good', 'Test return', $custodianId);
$db->prepare("DELETE FROM transactions WHERE transaction_id = ?")->execute([$txId]);
$db->prepare("UPDATE items SET status = 'available', requested_by = NULL, reservation_expires_at = NULL WHERE item_id = ?")->execute([$itemId]);

// 3. Test Deny Flow
echo "\n[3/4] Testing Borrow Request DENIAL Notification...\n";
\App\Models\Item::submitBorrowRequest($db, $itemId, $studentId, "Fieldwork documentation");

$reqItem = \App\Models\Item::findById($db, $itemId);
assertTest("Item is currently requested", $reqItem['status'] === 'requested');

// Simulate custodian denial
$denied = \App\Models\Item::denyBorrowRequest($db, $itemId);
assertTest("Item::denyBorrowRequest returned true", $denied);

$denialReason = "Priority needed for scheduled senior lab class";
$reasonText = " Reason: \"{$denialReason}\".";
$denyNotifSuccess = \App\Services\NotificationService::create(
    $db,
    $studentId,
    \App\Services\NotificationService::CAT_LOAN_DENIED,
    "Borrow Request Denied",
    "Your borrow request for {$item['item_name']} ({$item['barcode']}) was declined by the Property Custodian.{$reasonText} The reservation has been released.",
    \App\Services\NotificationService::TYPE_DANGER,
    "/inventory"
);
assertTest("Denial notification created successfully", $denyNotifSuccess);

$feedAfterDenial = \App\Services\NotificationService::getFeed($db, $studentId);
$latestNotif = $feedAfterDenial['notifications'][0];
assertTest("Title is 'Borrow Request Denied'", $latestNotif['title'] === "Borrow Request Denied");
assertTest("Category is LOAN_DENIED", $latestNotif['category'] === \App\Services\NotificationService::CAT_LOAN_DENIED);
assertTest("Visual type is danger", $latestNotif['type'] === \App\Services\NotificationService::TYPE_DANGER);
assertTest("Message contains reason", str_contains($latestNotif['message'], $denialReason));
assertTest("Action URL points to /inventory", $latestNotif['action_url'] === "/inventory");

// Verify item state is back to available
$restoredItem = \App\Models\Item::findById($db, $itemId);
assertTest("Asset status is restored to 'available'", $restoredItem['status'] === 'available');
assertTest("Asset requested_by is reset to NULL", $restoredItem['requested_by'] === null);

// 4. Cleanup
echo "\n[4/4] Cleaning up test data...\n";
$db->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$studentId]);
if ($item['barcode'] === 'TEST-NOTIF-999') {
    $db->prepare("DELETE FROM items WHERE item_id = ?")->execute([$itemId]);
}
echo "  [PASS] Test notifications and temporary states cleaned.\n";

echo "\n=======================================================\n";
echo "  [SUCCESS] All Accept & Deny Bell Notification tests PASSED!\n";
echo "=======================================================\n\n";
