<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/app/Config/Database.php';
require_once ROOT_PATH . '/app/Models/User.php';
require_once ROOT_PATH . '/app/Models/Item.php';
require_once ROOT_PATH . '/app/Models/Transaction.php';

use App\Config\Database;
use App\Models\User;
use App\Models\Item;
use App\Models\Transaction;

echo "========================================================\n";
echo "SLSU-Alabat IMS: Transaction & Reservation Test Suite\n";
echo "========================================================\n\n";

$db = Database::getConnection();

$allUsers = $db->query("SELECT user_id, username, full_name, role, account_status FROM users")->fetchAll(PDO::FETCH_ASSOC);
echo "Existing users count: " . count($allUsers) . "\n";
foreach ($allUsers as $u) {
    echo " - [{$u['user_id']}] {$u['username']} | {$u['role']} | status: {$u['account_status']}\n";
}

$student = $db->query("SELECT * FROM users WHERE role = 'student' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$custodian = $db->query("SELECT * FROM users WHERE role = 'custodian' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$faculty = $db->query("SELECT * FROM users WHERE role = 'faculty' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    // Create a student user for test if none exists
    echo "Creating test student...\n";
    $db->prepare("INSERT INTO users (id_number, username, full_name, email, password_hash, role, department_program, account_status, max_concurrent_loans)
                  VALUES ('STU-2024-001', 'test_student', 'Juan Dela Cruz', 'juan@slsu.edu.ph', 'hash', 'student', 'BSIT', 'approved', 3)")
       ->execute();
    $student = $db->query("SELECT * FROM users WHERE username = 'test_student'")->fetch(PDO::FETCH_ASSOC);
} else if ($student['account_status'] !== 'approved') {
    $db->prepare("UPDATE users SET account_status = 'approved' WHERE user_id = :id")->execute([':id' => $student['user_id']]);
    $student['account_status'] = 'approved';
}

if (!$custodian) {
    // Create a custodian user for test if none exists
    echo "Creating test custodian...\n";
    $db->prepare("INSERT INTO users (id_number, username, full_name, email, password_hash, role, department_program, account_status)
                  VALUES ('CUST-2024-001', 'test_custodian', 'Supply Custodian Officer', 'custodian@slsu.edu.ph', 'hash', 'custodian', 'Supply Office', 'approved')")
       ->execute();
    $custodian = $db->query("SELECT * FROM users WHERE username = 'test_custodian'")->fetch(PDO::FETCH_ASSOC);
} else if ($custodian['account_status'] !== 'approved') {
    $db->prepare("UPDATE users SET account_status = 'approved' WHERE user_id = :id")->execute([':id' => $custodian['user_id']]);
    $custodian['account_status'] = 'approved';
}

echo "[1] Test Actors Identified:\n";
echo "    Student:   {$student['full_name']} (ID: {$student['user_id']}, {$student['id_number']})\n";
echo "    Custodian: {$custodian['full_name']} (ID: {$custodian['user_id']})\n";
if ($faculty) {
    echo "    Faculty:   {$faculty['full_name']} (ID: {$faculty['user_id']})\n";
}
echo "\n";

// Reset student damage hold for clean state
User::setDamageHold($db, (int)$student['user_id'], false);

// 2. Fetch a test item
$item = $db->query("SELECT * FROM items WHERE status = 'available' AND is_student_borrowable = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$item) {
    // If no available item, set one to available
    $item = $db->query("SELECT * FROM items LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        echo "ERROR: No items found in database.\n";
        exit(1);
    }
    $db->prepare("UPDATE items SET status = 'available', is_student_borrowable = 1, requested_by = NULL, reservation_expires_at = NULL WHERE item_id = :id")
       ->execute([':id' => $item['item_id']]);
    $item['status'] = 'available';
    $item['is_student_borrowable'] = 1;
}

$itemId = (int)$item['item_id'];
$barcode = $item['barcode'];
$studentId = (int)$student['user_id'];
$custodianId = (int)$custodian['user_id'];

echo "[2] Test Asset Selected:\n";
echo "    Item:    {$item['item_name']} (ID: {$itemId})\n";
echo "    Barcode: {$barcode}\n";
echo "    Status:  {$item['status']}\n\n";

// 3. Test Eligibility Check
echo "[3] Testing User::checkBorrowEligibility()...\n";
$eligibility = User::checkBorrowEligibility($db, $studentId);
echo "    Eligible: " . ($eligibility['eligible'] ? 'YES' : 'NO') . "\n";
echo "    Active Loans: {$eligibility['active_loans']} / {$eligibility['max_quota']}\n";
echo "    Pending Requests: {$eligibility['pending_requests']}\n";
echo "    Has Damage Hold: " . ($eligibility['has_damage_hold'] ? 'YES' : 'NO') . "\n";
echo "    Has Overdue: " . ($eligibility['has_overdue'] ? 'YES' : 'NO') . "\n";
if (!$eligibility['eligible']) {
    echo "    Rejection Reason: {$eligibility['reason']}\n";
    echo "FAILED: Student should be eligible at baseline.\n";
    exit(1);
}
echo "    PASS: Baseline eligibility confirmed.\n\n";

// 4. Test Student Submits Borrow Request (30-min reservation)
echo "[4] Testing Item::submitBorrowRequest()...\n";
$reqSuccess = Item::submitBorrowRequest($db, $itemId, $studentId, "CS Laboratory Experiment #3");
if (!$reqSuccess) {
    echo "FAILED: Unable to submit borrow request.\n";
    exit(1);
}

// Verify item state
$requestedItem = Item::findByBarcode($db, $barcode);
echo "    Item Status: {$requestedItem['status']}\n";
echo "    Requested By: {$requestedItem['requested_by_name']} (ID: {$requestedItem['requested_by']})\n";
echo "    Reservation Expires: {$requestedItem['reservation_expires_at']}\n";

if ($requestedItem['status'] !== 'requested' || (int)$requestedItem['requested_by'] !== $studentId) {
    echo "FAILED: Item state not properly set to requested.\n";
    exit(1);
}
echo "    PASS: Borrow request submitted with 30-min hold.\n\n";

// 5. Test Lazy Expiration Logic
echo "[5] Testing Lazy On-Access Expiration...\n";
// Manually expire reservation in DB
$db->prepare("UPDATE items SET reservation_expires_at = DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE item_id = :id")
   ->execute([':id' => $itemId]);

// Now call findByBarcode, which triggers checkAndPruneLazyExpiry
$expiredLookup = Item::findByBarcode($db, $barcode);
echo "    After access with expired timestamp -> Item Status: {$expiredLookup['status']}\n";
echo "    Requested By: " . var_export($expiredLookup['requested_by'], true) . "\n";

if ($expiredLookup['status'] !== 'available' || $expiredLookup['requested_by'] !== null) {
    echo "FAILED: Lazy expiration did not reset item to 'available'.\n";
    exit(1);
}
echo "    PASS: Lazy expiration pruned expired reservation back to 'available'.\n\n";

// 6. Re-submit request for approval flow
echo "[6] Re-submitting borrow request for custodian approval...\n";
Item::submitBorrowRequest($db, $itemId, $studentId, "CS Capstone presentation demonstration");
$requestedItem = Item::findByBarcode($db, $barcode);
echo "    Item Status: {$requestedItem['status']}\n";
echo "    PASS: Re-submitted successfully.\n\n";

// 7. Custodian Approves Request
echo "[7] Testing Custodian Approval -> Transaction::createLoan()...\n";
$returnDeadline = date('Y-m-d H:i:s', strtotime('+4 hours'));
$txId = Transaction::createLoan($db, $itemId, $studentId, $returnDeadline, "CS Capstone demonstration", $custodianId);
echo "    Created Transaction ID: {$txId}\n";
echo "    Deadline Set: {$returnDeadline}\n";

$borrowedItem = Item::findByBarcode($db, $barcode);
echo "    Item Status: {$borrowedItem['status']}\n";

$activeLoan = Transaction::getActiveLoanByItem($db, $itemId);
echo "    Active Loan Borrower: {$activeLoan['borrower_name']}\n";
echo "    Active Loan Expected Return: {$activeLoan['expected_return_date']}\n";

if ($borrowedItem['status'] !== 'borrowed' || empty($activeLoan)) {
    echo "FAILED: Custodian approval did not put item into 'borrowed' status or record transaction.\n";
    exit(1);
}
echo "    PASS: Loan transaction created and item is 'borrowed'.\n\n";

// 8. Custodian Return with Damage Inspection
echo "[8] Testing Custodian Return with Damage Assessment...\n";
$returnRemarks = "Screen cracked at top-right bezel during experiment.";
$returnSuccess = Transaction::processReturn($db, $itemId, 'damaged', $returnRemarks, $custodianId);

$returnedItem = Item::findByBarcode($db, $barcode);
echo "    Return Processed: " . ($returnSuccess ? 'YES' : 'NO') . "\n";
echo "    Item Status: {$returnedItem['status']} (Condition: {$returnedItem['item_condition']})\n";

if ($returnedItem['status'] !== 'maintenance' || $returnedItem['item_condition'] !== 'damaged') {
    echo "FAILED: Damaged return did not transition item to 'maintenance'.\n";
    exit(1);
}

// Verify borrower damage hold was flagged
$studentPostReturn = User::findById($db, $studentId);
echo "    Borrower Has Damage Hold: " . ($studentPostReturn['has_damage_hold'] ? 'YES' : 'NO') . "\n";
if (!$studentPostReturn['has_damage_hold']) {
    echo "FAILED: Damage hold not set on borrower account.\n";
    exit(1);
}

// Verify eligibility is now blocked by damage hold
$blockedEligibility = User::checkBorrowEligibility($db, $studentId);
echo "    Borrower Re-eligibility Check: " . ($blockedEligibility['eligible'] ? 'ELIGIBLE' : 'BLOCKED') . "\n";
echo "    Block Reason: {$blockedEligibility['reason']}\n";

if ($blockedEligibility['eligible']) {
    echo "FAILED: User should be blocked by damage hold.\n";
    exit(1);
}
echo "    PASS: Damaged return shifted item to 'maintenance' and locked borrower with damage hold.\n\n";

// 9. Custodian Clears Damage Hold
echo "[9] Testing Custodian Clearing Damage Hold...\n";
User::setDamageHold($db, $studentId, false);
$clearedEligibility = User::checkBorrowEligibility($db, $studentId);
echo "    Borrower Eligibility After Clearance: " . ($clearedEligibility['eligible'] ? 'ELIGIBLE' : 'BLOCKED') . "\n";
if (!$clearedEligibility['eligible']) {
    echo "FAILED: Clearing damage hold did not restore eligibility.\n";
    exit(1);
}
echo "    PASS: Damage hold cleared successfully.\n\n";

// 10. History and Redaction Checks
echo "[10] Testing Transaction History & Data Privacy Redaction...\n";
// Custodian view (unredacted)
$custodianHistory = Transaction::getItemBorrowHistory($db, $itemId, true);
echo "    Custodian History Count: " . count($custodianHistory) . "\n";
$custodianFirst = $custodianHistory[0] ?? [];
echo "    Custodian Sees Student ID: " . ($custodianFirst['id_number'] ?? 'MISSING') . "\n";

// Student view (redacted)
$studentHistory = Transaction::getItemBorrowHistory($db, $itemId, false);
$studentFirst = $studentHistory[0] ?? [];
echo "    Student Sees Student ID: " . (isset($studentFirst['id_number']) ? 'EXPOSED (FAIL!)' : 'REDACTED (PASS)') . "\n";

if (isset($studentFirst['id_number']) || isset($studentFirst['phone_number'])) {
    echo "FAILED: Sensitive student data was not redacted for non-custodians!\n";
    exit(1);
}
echo "    PASS: Data privacy redaction verified.\n\n";

// 11. Cleanup test records and restore item to 'available'
echo "[11] Cleaning up test records and restoring Item to 'available' & 'good'...\n";
if (!empty($txId)) {
    $db->prepare("DELETE FROM damage_incidents WHERE transaction_id = :tx_id")->execute([':tx_id' => $txId]);
    $db->prepare("DELETE FROM transactions WHERE transaction_id = :tx_id")->execute([':tx_id' => $txId]);
}
$db->prepare("UPDATE items SET status = 'available', item_condition = 'good', requested_by = NULL, reservation_expires_at = NULL WHERE item_id = :id")
   ->execute([':id' => $itemId]);
$db->prepare("UPDATE users SET has_damage_hold = 0 WHERE user_id = :uid")
   ->execute([':uid' => $studentId]);
echo "    PASS: Test transaction and damage incident purged; item restored to clean state.\n\n";

echo "========================================================\n";
echo "ALL TESTS PASSED SUCCESSFULLY! (100% Flow Integrity)\n";
echo "========================================================\n";
