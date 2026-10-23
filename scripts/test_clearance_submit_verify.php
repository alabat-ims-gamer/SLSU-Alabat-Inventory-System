<?php
declare(strict_types=1);

/**
 * End-to-end automated verification script for Student Damage Receipt Submit & Custodian Verification.
 * Run via: C:\xampp\php\php.exe slsu-inventory\scripts\test_clearance_submit_verify.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Models\DamageIncident;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Item;

echo "========================================================\n";
echo "AlabatIMS Clearance Submit & Verify Verification Test\n";
echo "========================================================\n\n";

// $pdo is provided by config/database.php
if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo "[FAIL] PDO connection not available.\n";
    exit(1);
}

function assertTest(string $desc, bool $condition, ?string $failDetails = null): void {
    if ($condition) {
        echo " [PASS] $desc\n";
    } else {
        echo " [FAIL] $desc\n";
        if ($failDetails) {
            echo "        Details: $failDetails\n";
        }
        exit(1);
    }
}

// 1. Setup mock borrower, item, and custodian
echo "Stage 1: Setting up mock borrower, item, and custodian...\n";

// Find or create dedicated test student (does not collide with actual student records)
$stmt = $pdo->prepare("SELECT user_id, email, full_name FROM users WHERE username = 'autotest_student' LIMIT 1");
$stmt->execute();
$testStudent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$testStudent) {
    $stmt = $pdo->prepare("INSERT INTO users (id_number, username, full_name, email, password_hash, role, has_damage_hold, account_status)
                           VALUES ('AUTO-STU-99', 'autotest_student', 'Auto Test Student', 'autotest@slsu.edu.ph', 'hash', 'student', 0, 'approved')");
    $stmt->execute();
    $studentId = (int)$pdo->lastInsertId();
} else {
    $studentId = (int)$testStudent['user_id'];
    $pdo->prepare("DELETE FROM damage_incidents WHERE borrower_id = ?")->execute([$studentId]);
    $pdo->prepare("UPDATE users SET has_damage_hold = 0 WHERE user_id = ?")->execute([$studentId]);
}

// Find or create test custodian
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE role IN ('custodian', 'admin') LIMIT 1");
$stmt->execute();
$custodianUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$custodianUser) {
    $stmt = $pdo->prepare("INSERT INTO users (id_number, username, full_name, email, password_hash, role, account_status)
                           VALUES ('TEST-CUST-01', 'testcust', 'Test Custodian', 'testcust@slsu.edu.ph', 'hash', 'custodian', 'approved')");
    $stmt->execute();
    $custodianId = (int)$pdo->lastInsertId();
} else {
    $custodianId = (int)$custodianUser['user_id'];
}

// Find an existing item or create one based on table schema
$stmt = $pdo->query("SELECT item_id, property_number, item_name, status FROM items LIMIT 1");
$testItem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$testItem) {
    // Insert minimal item based on DESCRIBE items
    $cols = $pdo->query("DESCRIBE items")->fetchAll(PDO::FETCH_COLUMN);
    $propNum = 'PROP-' . time();
    $barcode = 'BC-' . rand(100000, 999999);
    
    $insertCols = ['property_number', 'item_name', 'status'];
    $insertVals = [$propNum, 'Test Projector HD', 'available'];
    
    if (in_array('barcode', $cols, true)) {
        $insertCols[] = 'barcode';
        $insertVals[] = $barcode;
    }
    if (in_array('condition_status', $cols, true)) {
        $insertCols[] = 'condition_status';
        $insertVals[] = 'good';
    }
    if (in_array('category', $cols, true)) {
        $insertCols[] = 'category';
        $insertVals[] = 'Electronics';
    }
    
    $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
    $colList = implode(', ', $insertCols);
    $insertStmt = $pdo->prepare("INSERT INTO items ($colList) VALUES ($placeholders)");
    $insertStmt->execute($insertVals);
    $itemId = (int)$pdo->lastInsertId();
    $propNum = $propNum;
} else {
    $itemId = (int)$testItem['item_id'];
    $propNum = $testItem['property_number'];
}

assertTest("Borrower ID ($studentId) and Item ID ($itemId) ready", $studentId > 0 && $itemId > 0);

// 2. Simulate Loan and Return with Damage
echo "\nStage 2: Simulating Checkout and Return with Assessed Fee...\n";

// Ensure item is available and has no open borrow transactions before loan
$pdo->prepare("DELETE FROM transactions WHERE item_id = ? AND actual_return_date IS NULL")->execute([$itemId]);
$pdo->prepare("UPDATE items SET status = 'available' WHERE item_id = ?")->execute([$itemId]);

// Create Loan using Transaction::createLoan
$expectedReturn = date('Y-m-d H:i:s', strtotime('+2 days'));
$txId = Transaction::createLoan($pdo, $itemId, $studentId, $expectedReturn, 'Class presentation testing', $custodianId);
assertTest("Loan successfully created via Transaction::createLoan (tx #$txId)", $txId > 0);

// Process Return via Transaction::processReturn with damaged condition and assessed fee of 1500.00
$assessedFee = 1500.00;
$returnSuccess = Transaction::processReturn(
    $pdo,
    $itemId,
    'damaged',
    'Cracked lens during presentation',
    $custodianId,
    $assessedFee
);

assertTest("Return processed as damaged with assessed fee", $returnSuccess);

// Verify damage incident was created with status 'awaiting_payment'
$incidentStmt = $pdo->prepare("SELECT * FROM damage_incidents WHERE transaction_id = ? ORDER BY incident_id DESC LIMIT 1");
$incidentStmt->execute([$txId]);
$incident = $incidentStmt->fetch(PDO::FETCH_ASSOC);

assertTest("Damage incident created in database", $incident !== false);
assertTest("Damage incident status is 'awaiting_payment'", ($incident['incident_status'] ?? '') === 'awaiting_payment');
assertTest("Damage incident assessed cost is 1500.00", (float)($incident['estimated_cost'] ?? 0) === 1500.00);

// Verify borrower has damage hold
$holdCheck = (int)$pdo->query("SELECT has_damage_hold FROM users WHERE user_id = $studentId")->fetchColumn();
assertTest("User has_damage_hold flag is set to 1", $holdCheck === 1);

$incidentId = (int)$incident['incident_id'];

// 3. Custodian Adjusts Fee
echo "\nStage 3: Custodian adjusts assessed fee...\n";
$newFee = 1450.00;
$updateFeeResult = DamageIncident::updateAssessedFee($pdo, $incidentId, $newFee);
assertTest("DamageIncident::updateAssessedFee succeeds", $updateFeeResult === true);

$reloadedIncident = DamageIncident::findById($pdo, $incidentId);
assertTest("Reloaded incident reflects updated fee (1450.00)", (float)$reloadedIncident['estimated_cost'] === 1450.00);

// 4. Student Submits Receipt
echo "\nStage 4: Student submits cashier receipt...\n";
$mockReceiptPath = '/uploads/clearances/test_receipt_' . time() . '.pdf';
$submitResult = DamageIncident::submitReceipt(
    $pdo,
    $incidentId,
    $studentId,
    'OR-98765432',
    1450.00,
    date('Y-m-d'),
    $mockReceiptPath,
    'Paid at university cashier office window 2'
);

assertTest("DamageIncident::submitReceipt succeeds", $submitResult['success'] === true);

$submittedIncident = DamageIncident::findById($pdo, $incidentId);
assertTest("Incident status advanced to 'pending_verification'", $submittedIncident['incident_status'] === 'pending_verification');
assertTest("Receipt number recorded as OR-98765432", $submittedIncident['receipt_number'] === 'OR-98765432');
assertTest("Amount paid recorded as 1450.00", (float)$submittedIncident['amount_paid'] === 1450.00);
assertTest("Receipt path recorded", $submittedIncident['clearance_document_path'] === $mockReceiptPath);

// Hold should still be active while pending verification
$holdCheck2 = (int)$pdo->query("SELECT has_damage_hold FROM users WHERE user_id = $studentId")->fetchColumn();
assertTest("User hold remains active while pending verification", $holdCheck2 === 1);

// Verify that student cannot edit or re-submit while pending verification
$cannotEditCaught = false;
try {
    DamageIncident::submitReceipt($pdo, $incidentId, $studentId, 'OR-EDITED', 1450.00, date('Y-m-d'), $mockReceiptPath);
} catch (\Throwable $e) {
    $cannotEditCaught = true;
}
assertTest("Student editing is strictly blocked while pending verification", $cannotEditCaught === true);

// 5. Custodian Rejects Receipt (Needs clearer photo)
echo "\nStage 5: Custodian rejects receipt for unclear upload...\n";
$rejectReason = 'The O.R. number in the photo is blurry and illegible. Please upload a clear photo or PDF scan.';
$rejectResult = DamageIncident::rejectIncident($pdo, $incidentId, $custodianId, $rejectReason);
assertTest("DamageIncident::rejectIncident succeeds", $rejectResult['success'] === true);

$rejectedIncident = DamageIncident::findById($pdo, $incidentId);
assertTest("Incident status is now 'rejected'", $rejectedIncident['incident_status'] === 'rejected');
assertTest("Rejection reason is stored accurately", $rejectedIncident['rejection_reason'] === $rejectReason);

// Verify that custodian CANNOT verify an incident that is currently rejected
$cannotVerifyRejectedCaught = false;
try {
    DamageIncident::verifyIncident($pdo, $incidentId, $custodianId);
} catch (\Throwable $e) {
    $cannotVerifyRejectedCaught = true;
}
assertTest("Custodian cannot verify a rejected incident before student re-uploads", $cannotVerifyRejectedCaught === true);

// Verify that custodian CANNOT reject an already rejected incident
$cannotReRejectCaught = false;
try {
    DamageIncident::rejectIncident($pdo, $incidentId, $custodianId, 'Duplicate reject');
} catch (\Throwable $e) {
    $cannotReRejectCaught = true;
}
assertTest("Custodian cannot reject an already rejected incident", $cannotReRejectCaught === true);

// 6. Student Re-submits Receipt
echo "\nStage 6: Student resubmits clear receipt...\n";
$resubmitResult = DamageIncident::submitReceipt(
    $pdo,
    $incidentId,
    $studentId,
    'OR-98765432',
    1450.00,
    date('Y-m-d'),
    $mockReceiptPath,
    'Resubmitted high-resolution scan of official receipt.'
);
assertTest("Student successfully resubmits receipt", $resubmitResult['success'] === true);

$resubmittedIncident = DamageIncident::findById($pdo, $incidentId);
assertTest("Status returned to 'pending_verification' and rejection_reason cleared",
    $resubmittedIncident['incident_status'] === 'pending_verification' && empty($resubmittedIncident['rejection_reason']));

// 7. Custodian Approves / Verifies Receipt
echo "\nStage 7: Custodian verifies and approves clearance...\n";
$verifyResult = DamageIncident::verifyIncident($pdo, $incidentId, $custodianId, 'Official cashier receipt verified. Serial number and payment confirmed.');
assertTest("DamageIncident::verifyIncident succeeds", $verifyResult['success'] === true);

$verifiedIncident = DamageIncident::findById($pdo, $incidentId);
assertTest("Incident status is now 'cleared'", $verifiedIncident['incident_status'] === 'cleared');
assertTest("Cleared by custodian ID recorded", (int)$verifiedIncident['cleared_by'] === $custodianId);
assertTest("Cleared at timestamp is populated", !empty($verifiedIncident['cleared_at']));

// Hold should now be lifted on user!
$holdCheck3 = (int)$pdo->query("SELECT has_damage_hold FROM users WHERE user_id = $studentId")->fetchColumn();
assertTest("User has_damage_hold flag is automatically lifted to 0", $holdCheck3 === 0);

// 8. Test Data Analytics Query Compatibility
echo "\nStage 8: Verifying Data Analytics queries...\n";

// Count open holds (incident_status != 'cleared')
$openHoldsCount = (int)$pdo->query("SELECT COUNT(*) FROM damage_incidents WHERE incident_status != 'cleared'")->fetchColumn();
assertTest("Analytics open holds query executes smoothly", is_int($openHoldsCount));

// Sum total cleared cost
$stmt = $pdo->query("
    SELECT
        COUNT(CASE WHEN incident_status = 'cleared' THEN 1 END) as cleared_incidents,
        COALESCE(SUM(CASE WHEN incident_status = 'cleared' THEN COALESCE(amount_paid, estimated_cost, 0) ELSE 0 END), 0) as total_cleared_cost
    FROM damage_incidents
");
$analyticsRow = $stmt->fetch(PDO::FETCH_ASSOC);
assertTest("Analytics revenue / cleared cost query reflects verified amount_paid", (float)$analyticsRow['total_cleared_cost'] >= 1450.00);

// 9. Status Badging Count Verification
echo "\nStage 9: Verifying countByStatus helper...\n";
$counts = DamageIncident::countByStatus($pdo);
assertTest("countByStatus contains awaiting_payment key", array_key_exists('awaiting_payment', $counts));
assertTest("countByStatus contains pending_verification key", array_key_exists('pending_verification', $counts));
assertTest("countByStatus contains cleared key", array_key_exists('cleared', $counts));
assertTest("countByStatus contains rejected key", array_key_exists('rejected', $counts));
assertTest("countByStatus total is integer >= 1", ($counts['total'] ?? 0) >= 1);

// 10. Test Multi-Payment / Installment Ledger & Balance Tracking
echo "\nStage 10: Testing Multi-Payment / Installment Ledger & Balance Tracking...\n";

// Clean up first incident before second cycle
$pdo->prepare("DELETE FROM damage_incidents WHERE incident_id = ?")->execute([$incidentId]);
$pdo->prepare("DELETE FROM transactions WHERE transaction_id = ?")->execute([$txId]);
$pdo->prepare("UPDATE items SET status = 'available' WHERE item_id = ?")->execute([$itemId]);
$pdo->prepare("UPDATE users SET has_damage_hold = 0 WHERE user_id = ?")->execute([$studentId]);

// Create fresh loan and return with 2,000.00 assessed fee
$txId2 = Transaction::createLoan($pdo, $itemId, $studentId, date('Y-m-d H:i:s', strtotime('+2 days')), 'Multi-payment test', $custodianId);
Transaction::processReturn($pdo, $itemId, 'damaged', 'Burned motor coil', $custodianId, 2000.00);

$stmt = $pdo->prepare("SELECT * FROM damage_incidents WHERE transaction_id = ? ORDER BY incident_id DESC LIMIT 1");
$stmt->execute([$txId2]);
$inc2 = $stmt->fetch(PDO::FETCH_ASSOC);
$incId2 = (int)$inc2['incident_id'];

assertTest("Incident #$incId2 created with assessed fee 2000.00", (float)$inc2['estimated_cost'] === 2000.00);
assertTest("Initial remaining_balance initialized to 2000.00", (float)$inc2['remaining_balance'] === 2000.00);
assertTest("Student placed on hold (has_damage_hold = 1)", (int)$pdo->query("SELECT has_damage_hold FROM users WHERE user_id = $studentId")->fetchColumn() === 1);

// Step 10a: First partial payment (₱600.00)
$sub1 = DamageIncident::submitReceipt($pdo, $incId2, $studentId, 'OR-PART-01', 600.00, date('Y-m-d'), '/uploads/clearances/part1.pdf', 'First installment 600');
assertTest("First receipt submitted successfully", $sub1['success'] === true);

// Verify without accept_as_full (standard partial payment)
$ver1 = DamageIncident::verifyIncident($pdo, $incId2, $custodianId, 'Verified 1st installment', false);
assertTest("Custodian verifies partial payment", $ver1['success'] === true);
assertTest("Incident status is now 'partially_paid'", $ver1['status'] === 'partially_paid');
assertTest("Total paid recorded is 600.00", (float)$ver1['total_paid'] === 600.00);
assertTest("Remaining balance calculated as 1400.00", (float)$ver1['remaining_balance'] === 1400.00);
assertTest("Hold remains locked (has_damage_hold = 1)", (int)$pdo->query("SELECT has_damage_hold FROM users WHERE user_id = $studentId")->fetchColumn() === 1);

// Step 10b: Second partial payment (₱400.00)
$sub2 = DamageIncident::submitReceipt($pdo, $incId2, $studentId, 'OR-PART-02', 400.00, date('Y-m-d'), '/uploads/clearances/part2.pdf', 'Second installment 400');
assertTest("Second receipt submitted successfully", $sub2['success'] === true);

$ver2 = DamageIncident::verifyIncident($pdo, $incId2, $custodianId, 'Verified 2nd installment', false);
assertTest("Custodian verifies 2nd installment", $ver2['success'] === true);
assertTest("Status remains 'partially_paid'", $ver2['status'] === 'partially_paid');
assertTest("Total cumulative paid is 1000.00", (float)$ver2['total_paid'] === 1000.00);
assertTest("Remaining balance calculated as 1000.00", (float)$ver2['remaining_balance'] === 1000.00);
assertTest("Hold remains active on borrower", (int)$pdo->query("SELECT has_damage_hold FROM users WHERE user_id = $studentId")->fetchColumn() === 1);

// Check payment ledger history
$paymentsLedger = DamageIncident::getPayments($pdo, $incId2);
assertTest("Ledger returns 2 payment records", count($paymentsLedger) === 2);
assertTest("Both ledger records are verified", $paymentsLedger[0]['payment_status'] === 'verified' && $paymentsLedger[1]['payment_status'] === 'verified');

// Step 10c: Custodian adjusts fee down to ₱1,500.00
DamageIncident::updateAssessedFee($pdo, $incId2, 1500.00);
$inc2AfterFee = DamageIncident::findById($pdo, $incId2);
assertTest("Fee updated to 1500.00 and remaining_balance recalculated to 500.00 (1500 - 1000)", (float)$inc2AfterFee['remaining_balance'] === 500.00);

// Step 10d: Third payment paying remaining balance in full (₱500.00)
$sub3 = DamageIncident::submitReceipt($pdo, $incId2, $studentId, 'OR-PART-03', 500.00, date('Y-m-d'), '/uploads/clearances/part3.pdf', 'Final balance payment');
assertTest("Final balance receipt submitted", $sub3['success'] === true);

$ver3 = DamageIncident::verifyIncident($pdo, $incId2, $custodianId, 'All installments settled.', false);
assertTest("Final payment verified", $ver3['success'] === true);
assertTest("Incident status is now 'cleared'", $ver3['status'] === 'cleared');
assertTest("Remaining balance is 0.00", (float)$ver3['remaining_balance'] === 0.00);
assertTest("Total paid reflects 1500.00", (float)$ver3['total_paid'] === 1500.00);
assertTest("Borrower has_damage_hold is automatically lifted to 0", (int)$pdo->query("SELECT has_damage_hold FROM users WHERE user_id = $studentId")->fetchColumn() === 0);

// 11. Test Custodian "Accept as Full Settlement" Underpayment Triage
echo "\nStage 11: Testing Custodian 'Accept as Full Settlement' for Underpayment...\n";

// Clean up
$pdo->prepare("DELETE FROM damage_incidents WHERE incident_id = ?")->execute([$incId2]);
$pdo->prepare("DELETE FROM transactions WHERE transaction_id = ?")->execute([$txId2]);
$pdo->prepare("UPDATE items SET status = 'available' WHERE item_id = ?")->execute([$itemId]);
$pdo->prepare("UPDATE users SET has_damage_hold = 0 WHERE user_id = ?")->execute([$studentId]);

// Create 3rd loan and incident with fee 1000.00
$txId3 = Transaction::createLoan($pdo, $itemId, $studentId, date('Y-m-d H:i:s', strtotime('+2 days')), 'Full settlement test', $custodianId);
Transaction::processReturn($pdo, $itemId, 'damaged', 'Scratched enclosure', $custodianId, 1000.00);

$stmt = $pdo->prepare("SELECT * FROM damage_incidents WHERE transaction_id = ? ORDER BY incident_id DESC LIMIT 1");
$stmt->execute([$txId3]);
$inc3 = $stmt->fetch(PDO::FETCH_ASSOC);
$incId3 = (int)$inc3['incident_id'];

// Student pays 850.00 (underpayment by 150.00)
DamageIncident::submitReceipt($pdo, $incId3, $studentId, 'OR-FULL-OVERRIDE', 850.00, date('Y-m-d'), '/uploads/clearances/full_override.pdf', 'Official cashier OR 850');

// Custodian accepts 850.00 as full settlement ($acceptAsFull = true)
$verOverride = DamageIncident::verifyIncident($pdo, $incId3, $custodianId, 'Accepted 850.00 as full settlement by administrative discretion.', true);
assertTest("verifyIncident succeeds with acceptAsFull = true", $verOverride['success'] === true);
assertTest("Incident is marked 'cleared' despite underpayment", $verOverride['status'] === 'cleared');
assertTest("Remaining balance set to 0.00", (float)$verOverride['remaining_balance'] === 0.00);
assertTest("Borrower damage hold is lifted to 0", (int)$pdo->query("SELECT has_damage_hold FROM users WHERE user_id = $studentId")->fetchColumn() === 0);

// 12. Final Clean up
echo "\nStage 12: Cleaning up all test artifacts from database...\n";
$pdo->prepare("DELETE FROM damage_incidents WHERE incident_id = ?")->execute([$incId3]);
$pdo->prepare("DELETE FROM transactions WHERE transaction_id = ?")->execute([$txId3]);
$pdo->prepare("UPDATE items SET status = 'available' WHERE item_id = ?")->execute([$itemId]);
$pdo->prepare("UPDATE users SET has_damage_hold = 0 WHERE user_id = ?")->execute([$studentId]);

echo "\n====================================================================\n";
echo " ALL CLEARANCE & BALANCE TRACKING TESTS PASSED (12 STAGES COMPLETE)!\n";
echo "====================================================================\n";

