<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Exception;
use Throwable;
use App\Helpers\Security;
use App\Models\Item;
use App\Models\User;
use App\Models\Transaction;
use App\Models\DamageIncident;
use App\Models\AuditLog;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\IncidentReportExporter;
use App\Services\EventDispatcher;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Transaction Controller — Barcode Scanner Station & Asset Custody Engine
 * Handles 1D Barcode Lookup, Borrower Request Submissions, Custodian Approvals,
 * and Condition-Inspected Return Processing.
 */
class TransactionController {

    /**
     * Helper to render clean JSON responses
     */
    private static function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Render the Unified Barcode Scanner Station (/scan)
     */
    public static function scan(): void {
        AuthMiddleware::check();

        $role = $_SESSION['role'] ?? 'student';
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $title = 'Barcode Scanner Station';
        $breadcrumb = 'Scanner';

        $db = Database::getConnection();
        $csrfToken = Security::generateCsrfToken();
        $eligibility = User::checkBorrowEligibility($db, $userId);

        require __DIR__ . '/../../views/transactions/scan.php';
    }

    /**
     * API: Barcode Lookup
     * Evaluates lazy expiration, retrieves asset metadata, active loan, and redacted/unredacted history.
     */
    public static function apiLookup(): void {
        AuthMiddleware::check();

        $barcode = trim((string)($_GET['code'] ?? ''));
        if ($barcode === '') {
            self::jsonResponse(['success' => false, 'message' => 'Barcode parameter is required.'], 400);
        }

        try {
            $db = Database::getConnection();
            $item = Item::findByBarcode($db, $barcode);

            if (!$item) {
                self::jsonResponse([
                    'success' => false,
                    'message' => "No asset found with barcode \"{$barcode}\". Please verify the label."
                ], 404);
            }

            $viewerRole = $_SESSION['role'] ?? 'student';
            $viewerId   = (int)($_SESSION['user_id'] ?? 0);
            $isCustodian = in_array($viewerRole, ['custodian', 'admin'], true);

            // Active loan details (if borrowed)
            $activeLoan = null;
            if ($item['status'] === 'borrowed') {
                $activeLoan = Transaction::getActiveLoanByItem($db, (int)$item['item_id']);
                if ($activeLoan && !$isCustodian && (int)$activeLoan['user_id'] !== $viewerId) {
                    // Redact borrower details for other students/faculty
                    unset($activeLoan['borrower_id_number'], $activeLoan['borrower_phone'], $activeLoan['borrower_email']);
                }
            }

            // Borrower history (redacted for non-custodians)
            $history = Transaction::getItemBorrowHistory($db, (int)$item['item_id'], $isCustodian, 10);

            // Viewer eligibility
            $eligibility = User::checkBorrowEligibility($db, $viewerId);

            // Reservation hold calculation
            $reservationTimeRemaining = 0;
            $isRequestedByViewer = false;
            if ($item['status'] === 'requested') {
                $isRequestedByViewer = ((int)($item['requested_by'] ?? 0) === $viewerId);
                if (!empty($item['reservation_expires_at'])) {
                    $expiresSec = strtotime($item['reservation_expires_at']);
                    $reservationTimeRemaining = max(0, $expiresSec - time());
                }
            }

            self::jsonResponse([
                'success' => true,
                'item' => [
                    'item_id'               => (int)$item['item_id'],
                    'barcode'               => $item['barcode'],
                    'property_number'       => $item['property_number'],
                    'item_name'             => $item['item_name'],
                    'description'           => $item['description'],
                    'category'              => $item['category'],
                    'location'              => $item['location'],
                    'item_condition'        => $item['item_condition'],
                    'status'                => $item['status'],
                    'is_student_borrowable' => (bool)$item['is_student_borrowable'],
                    'requested_by'          => $item['requested_by'] ? (int)$item['requested_by'] : null,
                    'requested_by_name'     => $item['requested_by_name'],
                    'requested_by_id_number'=> $isCustodian ? $item['requested_by_id_number'] : null,
                    'requested_by_department'=> $item['requested_by_department'],
                    'request_purpose'       => $item['request_purpose'],
                    'reservation_expires_at'=> $item['reservation_expires_at'],
                    'hold_seconds_left'     => $reservationTimeRemaining,
                    'is_requested_by_viewer'=> $isRequestedByViewer,
                ],
                'active_loan'     => $activeLoan,
                'history'         => $history,
                'eligibility'     => $eligibility,
                'viewer_role'     => $viewerRole,
                'server_time'     => time(),
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Error looking up barcode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Submit Borrow Request (Borrower Action)
     * Validates quota, overdue status, damage clearance, and student eligibility.
     * Places a 30-minute timed reservation hold on the asset.
     */
    public static function apiRequest(): void {
        AuthMiddleware::check();

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $userId   = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'student';
        $itemId   = (int)($_POST['item_id'] ?? 0);
        $room     = trim((string)($_POST['room'] ?? ''));
        $subject  = trim((string)($_POST['subject'] ?? ''));
        $purpose  = trim((string)($_POST['purpose'] ?? ''));

        if ($itemId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid asset selected.'], 422);
        }

        // Support both 3-field breakdown (room, subject, purpose) and unified purpose string
        if ($room !== '' || $subject !== '') {
            if ($room === '' || $subject === '' || $purpose === '') {
                self::jsonResponse(['success' => false, 'message' => 'Please provide the room, subject, and purpose for borrowing.'], 422);
            }
            $formattedPurpose = "Room: {$room} | Subject: {$subject} | Purpose: {$purpose}";
        } else {
            if ($purpose === '') {
                self::jsonResponse(['success' => false, 'message' => 'Please provide the academic purpose, class, or room for borrowing.'], 422);
            }
            $formattedPurpose = $purpose;
        }

        $formattedPurpose = mb_substr($formattedPurpose, 0, 255);

        try {
            $db = Database::getConnection();

            // 1. Check user eligibility
            $eligibility = User::checkBorrowEligibility($db, $userId);
            if (!$eligibility['eligible']) {
                self::jsonResponse([
                    'success' => false,
                    'message' => $eligibility['reason'] ?? 'You are not currently eligible to borrow equipment.'
                ], 403);
            }

            // 2. Fetch item
            $item = Item::findById($db, $itemId);
            if (!$item) {
                self::jsonResponse(['success' => false, 'message' => 'Asset not found.'], 404);
            }

            // 3. Student restriction check
            if ($userRole === 'student' && !$item['is_student_borrowable']) {
                self::jsonResponse([
                    'success' => false,
                    'message' => 'Policy Restriction: This equipment is reserved for Faculty instruction only.'
                ], 403);
            }

            // 4. Submit request (30-minute hold)
            Item::submitBorrowRequest($db, $itemId, $userId, $formattedPurpose, 30);

            // 5. Audit log
            AuditLog::record(
                $db,
                $userId,
                'BORROW_REQUEST_SUBMITTED',
                "Submitted borrow request for {$item['item_name']} ({$item['barcode']}) for: {$formattedPurpose}"
            );

            // 6. Notify active Property Custodians of new incoming borrow request
            $requesterName = trim((string)($_SESSION['user_name'] ?? 'Borrower'));
            \App\Services\NotificationService::broadcastToRole(
                $db,
                'custodian',
                \App\Services\NotificationService::CAT_LOAN_CHECKOUT,
                "New Borrow Request",
                "{$requesterName} submitted a borrow request for {$item['item_name']} ({$item['barcode']}). Awaiting pickup & approval at the counter.",
                \App\Services\NotificationService::TYPE_INFO,
                url('/scan')
            );

            // 7. Real-Time Broadcast: Update item status to 'requested' and notify custodians
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_STATUS_CHANGED,
                'item',
                $itemId,
                [
                    'item_id'      => $itemId,
                    'status'       => 'requested',
                    'requested_by' => $userId,
                    'item_name'    => $item['item_name'],
                    'barcode'      => $item['barcode']
                ]
            );
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_LOAN_REQUESTED,
                'item',
                $itemId,
                [
                    'item_id'        => $itemId,
                    'item_name'      => $item['item_name'],
                    'barcode'        => $item['barcode'],
                    'requester_name' => $requesterName,
                    'purpose'        => $formattedPurpose
                ],
                'custodian'
            );

            self::jsonResponse([
                'success' => true,
                'message' => 'Borrow request submitted successfully! Your item is reserved for 30 minutes. Please proceed to the Property Custodian.',
                'hold_minutes' => 30
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Failed to submit borrow request: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * API: Cancel Borrow Request (Borrower Action)
     */
    public static function apiCancelRequest(): void {
        AuthMiddleware::check();

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);

        try {
            $db = Database::getConnection();
            $cancelled = Item::cancelBorrowRequest($db, $itemId, $userId);

            if (!$cancelled) {
                self::jsonResponse(['success' => false, 'message' => 'No active pending request found to cancel.'], 400);
            }

            AuditLog::record($db, $userId, 'BORROW_REQUEST_CANCELLED', "Cancelled borrow request for item ID #{$itemId}");

            // Real-Time Broadcast: Restore item availability in catalog
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_STATUS_CHANGED,
                'item',
                $itemId,
                [
                    'item_id'      => $itemId,
                    'status'       => 'available',
                    'requested_by' => null
                ]
            );

            self::jsonResponse([
                'success' => true,
                'message' => 'Borrow request cancelled. The asset has been released back to the catalog.'
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error cancelling request: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Custodian Approve & Issue Item
     * Sets return deadline and shifts status to 'borrowed'.
     */
    public static function apiApprove(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $custodianId = (int)($_SESSION['user_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $deadlinOption = trim((string)($_POST['deadline_option'] ?? '4_hours'));
        $customDate = trim((string)($_POST['custom_deadline'] ?? ''));

        // Compute return deadline
        $now = time();
        $expectedReturnDate = match ($deadlinOption) {
            '4_hours'      => date('Y-m-d H:i:s', $now + (4 * 3600)),
            'today_5pm'    => date('Y-m-d 17:00:00', $now),
            'tomorrow_5pm' => date('Y-m-d 17:00:00', strtotime('+1 day', $now)),
            '7_days'       => date('Y-m-d 17:00:00', strtotime('+7 days', $now)),
            'custom'       => !empty($customDate) ? date('Y-m-d H:i:s', strtotime($customDate)) : date('Y-m-d H:i:s', $now + (4 * 3600)),
            default        => date('Y-m-d H:i:s', $now + (4 * 3600)),
        };

        try {
            $db = Database::getConnection();
            $item = Item::findById($db, $itemId);

            if (!$item) {
                self::jsonResponse(['success' => false, 'message' => 'Asset record not found.'], 404);
            }

            if ($item['status'] !== 'requested' || empty($item['requested_by'])) {
                self::jsonResponse(['success' => false, 'message' => 'This item does not have an active pending request.'], 400);
            }

            $borrowerId = (int)$item['requested_by'];
            $purpose = $item['request_purpose'] ?? 'Equipment Borrow';

            // Verify borrower quota one last time
            $eligibility = User::checkBorrowEligibility($db, $borrowerId);
            if ($eligibility['has_damage_hold']) {
                self::jsonResponse(['success' => false, 'message' => 'Cannot issue asset: Borrower has a damage clearance hold.'], 400);
            }
            if ($eligibility['has_overdue']) {
                self::jsonResponse(['success' => false, 'message' => 'Cannot issue asset: Borrower has an overdue item.'], 400);
            }

            // Create loan
            $txId = Transaction::createLoan($db, $itemId, $borrowerId, $expectedReturnDate, $purpose, $custodianId);

            AuditLog::record(
                $db,
                $custodianId,
                'LOAN_APPROVED_AND_ISSUED',
                "Approved loan #{$txId} for {$item['item_name']} ({$item['barcode']}) to User #{$borrowerId} (Due: {$expectedReturnDate})"
            );

            // Dispatch in-app notification to borrower
            \App\Services\NotificationService::create(
                $db,
                $borrowerId,
                \App\Services\NotificationService::CAT_LOAN_CHECKOUT,
                "Borrow Request Approved",
                "Your borrow request for {$item['item_name']} ({$item['barcode']}) was approved! Equipment has been issued. Return is due on " . date('M j, Y h:i A', strtotime($expectedReturnDate)) . ".",
                \App\Services\NotificationService::TYPE_SUCCESS,
                url('/records')
            );

            // Real-Time Broadcast: Asset shifted to borrowed, loan created
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_STATUS_CHANGED,
                'item',
                $itemId,
                [
                    'item_id'     => $itemId,
                    'status'      => 'borrowed',
                    'borrower_id' => $borrowerId,
                    'item_name'   => $item['item_name'],
                    'barcode'     => $item['barcode'],
                    'due_date'    => $expectedReturnDate
                ]
            );
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_LOAN_APPROVED,
                'transaction',
                $txId,
                [
                    'transaction_id' => $txId,
                    'item_id'        => $itemId,
                    'borrower_id'    => $borrowerId,
                    'item_name'      => $item['item_name'],
                    'barcode'        => $item['barcode'],
                    'due_date'       => $expectedReturnDate
                ]
            );

            self::jsonResponse([
                'success'        => true,
                'message'        => "Asset {$item['barcode']} successfully issued! Return due at " . date('M j, Y h:i A', strtotime($expectedReturnDate)),
                'transaction_id' => $txId,
                'due_date'       => $expectedReturnDate,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Failed to issue asset: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Custodian Instant 1-Click Denial
     * Releases item back to 'available' with no paperwork and notifies borrower.
     */
    public static function apiDeny(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $custodianId = (int)($_SESSION['user_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));

        try {
            $db = Database::getConnection();
            $item = Item::findById($db, $itemId);

            if (!$item) {
                self::jsonResponse(['success' => false, 'message' => 'Asset record not found.'], 404);
            }

            if ($item['status'] !== 'requested' || empty($item['requested_by'])) {
                self::jsonResponse(['success' => false, 'message' => 'No active pending request found to deny.'], 400);
            }

            $borrowerId = (int)$item['requested_by'];

            $denied = Item::denyBorrowRequest($db, $itemId);

            if (!$denied) {
                self::jsonResponse(['success' => false, 'message' => 'Failed to deny request.'], 400);
            }

            $reasonText = $reason !== '' ? " Reason: \"{$reason}\"." : "";
            AuditLog::record(
                $db,
                $custodianId,
                'BORROW_REQUEST_DENIED',
                "Denied pending borrow request on Item ID #{$itemId} ({$item['item_name']}) for Borrower ID #{$borrowerId}.{$reasonText}"
            );

            // Dispatch in-app notification to borrower
            \App\Services\NotificationService::create(
                $db,
                $borrowerId,
                \App\Services\NotificationService::CAT_LOAN_DENIED,
                "Borrow Request Denied",
                "Your borrow request for {$item['item_name']} ({$item['barcode']}) was declined by the Property Custodian.{$reasonText} The reservation has been released.",
                \App\Services\NotificationService::TYPE_DANGER,
                url('/inventory')
            );

            // Real-Time Broadcast: Release item back to available, notify borrower
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_STATUS_CHANGED,
                'item',
                $itemId,
                [
                    'item_id'      => $itemId,
                    'status'       => 'available',
                    'requested_by' => null,
                    'item_name'    => $item['item_name'],
                    'barcode'      => $item['barcode']
                ]
            );
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_LOAN_DENIED,
                'item',
                $itemId,
                [
                    'item_id'     => $itemId,
                    'borrower_id' => $borrowerId,
                    'item_name'   => $item['item_name'],
                    'barcode'     => $item['barcode'],
                    'reason'      => $reason
                ],
                null,
                $borrowerId
            );

            self::jsonResponse([
                'success' => true,
                'message' => 'Request denied and borrower notified. The asset is now immediately available in the catalog.'
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error denying request: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Custodian Process Return Inspection
     * Records condition, inspector remarks, and shifts item to 'available' or 'maintenance'.
     * If damaged, sets has_damage_hold = TRUE on the borrower.
     */
    public static function apiReturn(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $custodianId = (int)($_SESSION['user_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $condition = trim((string)($_POST['condition'] ?? 'good'));
        $remarks = trim((string)($_POST['remarks'] ?? ''));
        $assessedCost = isset($_POST['assessed_cost']) && is_numeric($_POST['assessed_cost']) && (float)$_POST['assessed_cost'] >= 0
            ? (float)$_POST['assessed_cost']
            : (isset($_POST['estimated_cost']) && is_numeric($_POST['estimated_cost']) && (float)$_POST['estimated_cost'] >= 0 ? (float)$_POST['estimated_cost'] : null);

        if (!in_array($condition, Item::VALID_CONDITIONS, true)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid physical condition selected.'], 422);
        }

        try {
            $db = Database::getConnection();
            $item = Item::findById($db, $itemId);

            if (!$item) {
                self::jsonResponse(['success' => false, 'message' => 'Asset record not found.'], 404);
            }

            // Find active loan borrower before closing
            $txStmt = $db->prepare("
                SELECT user_id FROM transactions 
                WHERE item_id = ? AND transaction_type = 'borrow' AND actual_return_date IS NULL 
                ORDER BY transaction_id DESC LIMIT 1
            ");
            $txStmt->execute([$itemId]);
            $borrowerId = (int)$txStmt->fetchColumn();

            Transaction::processReturn($db, $itemId, $condition, $remarks, $custodianId, $assessedCost);

            $isDamaged = in_array($condition, ['damaged', 'not_working'], true);
            $newStatus = $isDamaged ? 'maintenance' : 'available';
            $actionMsg = $isDamaged 
                ? "Asset returned with damage. Status routed to MAINTENANCE, assessed fee recorded, and borrower hold placed."
                : "Asset successfully returned and checked in as AVAILABLE.";

            $auditFeeStr = $assessedCost !== null ? " Assessed Fee: ₱" . number_format($assessedCost, 2) : "";
            AuditLog::record(
                $db,
                $custodianId,
                'ASSET_RETURN_PROCESSED',
                "Processed return for {$item['item_name']} ({$item['barcode']}). Condition: {$condition}. Remarks: {$remarks}{$auditFeeStr}"
            );

            // Dispatch notification to borrower
            if ($borrowerId > 0) {
                if ($isDamaged) {
                    $feeNote = $assessedCost !== null ? " Assessed fee: ₱" . number_format($assessedCost, 2) . "." : "";
                    \App\Services\NotificationService::create(
                        $db,
                        $borrowerId,
                        \App\Services\NotificationService::CAT_DAMAGE_HOLD,
                        "Damage Clearance Required",
                        "{$item['item_name']} ({$item['barcode']}) was returned with damage.{$feeNote} Please visit My Clearances to submit your cashier receipt.",
                        \App\Services\NotificationService::TYPE_DANGER,
                        url('/my-clearances')
                    );
                } else {
                    \App\Services\NotificationService::create(
                        $db,
                        $borrowerId,
                        \App\Services\NotificationService::CAT_LOAN_RETURN,
                        "Equipment Return Cleared",
                        "Your return of {$item['item_name']} ({$item['barcode']}) has been successfully processed and verified in good condition.",
                        \App\Services\NotificationService::TYPE_INFO,
                        url('/records')
                    );
                }
            }

            // Real-Time Broadcast: Update item status and broadcast return
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_STATUS_CHANGED,
                'item',
                $itemId,
                [
                    'item_id'        => $itemId,
                    'status'         => $newStatus,
                    'item_condition' => $condition,
                    'item_name'      => $item['item_name'],
                    'barcode'        => $item['barcode']
                ]
            );
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_LOAN_RETURNED,
                'item',
                $itemId,
                [
                    'item_id'     => $itemId,
                    'borrower_id' => $borrowerId,
                    'condition'   => $condition,
                    'is_damaged'  => $isDamaged
                ]
            );

            self::jsonResponse([
                'success'     => true,
                'message'     => $actionMsg,
                'condition'   => $condition,
                'is_damaged'  => $isDamaged,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error processing return: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Get Pending Request Queue Count
     */
    public static function apiPendingQueue(): void {
        AuthMiddleware::check();

        try {
            $db = Database::getConnection();
            $count = Item::getPendingRequestsCount($db);
            self::jsonResponse(['success' => true, 'pending_count' => $count]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'pending_count' => 0], 500);
        }
    }

    /**
     * API: List Damage Incidents & Holds (Custodian / Executive Admin)
     */
    public static function apiListIncidents(): void {
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        $status = trim((string)($_GET['status'] ?? ''));
        $validStatuses = ['pending_verification', 'awaiting_payment', 'partially_paid', 'cleared', 'rejected', 'active_holds', 'open_hold'];
        $statusFilter = in_array($status, $validStatuses, true) ? $status : null;
        $search = trim((string)($_GET['search'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        try {
            $db = Database::getConnection();
            $incidents = DamageIncident::listAll($db, $statusFilter, $search, $limit, $offset);
            $totalCount = DamageIncident::countAll($db, $statusFilter, $search);
            $counts = DamageIncident::countByStatus($db);

            self::jsonResponse([
                'success'     => true,
                'data'        => $incidents,
                'total_count' => $totalCount,
                'counts'      => $counts,
                'page'        => $page,
                'limit'       => $limit,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Failed to load damage incidents: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Stream Export Damage Incidents & Clearance Ledger as Microsoft Excel (.xlsx) Report
     * Recreates the exact layout, dimensions, typography, and signatories of FINAL_INVENTORY-REPORT.xlsx.
     */
    public static function exportIncidentsXlsx(): void {
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            AuditLog::log(
                $db,
                $userId,
                'CLEARANCES_EXPORT_XLSX',
                'Exported complete damage incidents and accountability clearance ledger as Excel report (.xlsx).'
            );
        } catch (Throwable $e) {
            // Non-blocking audit catch
        }

        $query = "
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
                COALESCE(di.receipt_number, 'N/A') AS receipt_number,
                COALESCE(di.amount_paid, 0.00) AS amount_paid,
                COALESCE(di.remaining_balance, GREATEST(0.00, COALESCE(di.estimated_cost, 0) - COALESCE(di.amount_paid, 0))) AS remaining_balance,
                COALESCE(di.payment_date, 'N/A') AS payment_date,
                COALESCE(di.receipt_submitted_at, 'N/A') AS receipt_submitted_at,
                di.incident_status,
                COALESCE(di.rejection_reason, 'None') AS rejection_reason,
                COALESCE(di.settlement_notes, 'None') AS settlement_notes,
                COALESCE(di.clearance_document_path, 'None') AS clearance_document_path,
                COALESCE(c.full_name, 'N/A') AS cleared_by_name,
                COALESCE(di.cleared_at, 'N/A') AS cleared_at,
                di.created_at
            FROM damage_incidents di
            JOIN users u ON di.borrower_id = u.user_id
            JOIN items i ON di.item_id = i.item_id
            LEFT JOIN users c ON di.cleared_by = c.user_id
            ORDER BY di.incident_id DESC
        ";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $exporter = new IncidentReportExporter();
        $spreadsheet = $exporter->generate($incidents);

        $filename = 'slsu_alabat_damage_clearances_' . date('Y-m-d_His') . '.xlsx';

        // Clean any output buffer to prevent corrupted binary XLSX
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Set download headers for XLSX
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }

    /**
     * Backward-compatible alias for CSV route
     */
    public static function exportIncidentsCsv(): void {
        self::exportIncidentsXlsx();
    }

    /**
     * API: Real-Time IRL Validation for Replacement Asset (Custodian / Admin)
     * Verifies that the entered barcode or property number exists in the asset catalog,
     * is available, and is not the same damaged item being cleared.
     */
    public static function apiValidateReplacement(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $code = trim((string)($_GET['code'] ?? ''));
        $incidentId = (int)($_GET['incident_id'] ?? 0);

        if ($code === '') {
            self::jsonResponse(['success' => false, 'valid' => false, 'message' => 'Barcode or Property Number is required.'], 400);
        }

        try {
            $db = Database::getConnection();
            $item = Item::findByBarcodeOrPropertyNumber($db, $code);

            if (!$item) {
                self::jsonResponse([
                    'success' => false,
                    'valid'   => false,
                    'message' => "Asset with barcode/serial \"{$code}\" does not exist in the asset catalog. Please register the physical unit first to prevent phantom inventory."
                ], 404);
            }

            if ($incidentId > 0) {
                $incident = DamageIncident::findById($db, $incidentId);
                if ($incident && (int)$incident['item_id'] === (int)$item['item_id']) {
                    self::jsonResponse([
                        'success' => false,
                        'valid'   => false,
                        'message' => "The replacement asset cannot be the same damaged item (#{$item['item_id']})."
                    ], 422);
                }
            }

            if ($item['status'] !== 'available') {
                self::jsonResponse([
                    'success' => false,
                    'valid'   => false,
                    'message' => "Asset \"{$item['item_name']}\" exists but is currently marked as '{$item['status']}'. Only available assets can serve as replacements."
                ], 422);
            }

            if (in_array($item['item_condition'], ['damaged', 'not_working', 'for_disposal'], true)) {
                self::jsonResponse([
                    'success' => false,
                    'valid'   => false,
                    'message' => "Asset \"{$item['item_name']}\" is currently in '{$item['item_condition']}' condition. Replacement unit must be in working condition."
                ], 422);
            }

            self::jsonResponse([
                'success' => true,
                'valid'   => true,
                'message' => 'Registered catalog asset verified.',
                'item'    => [
                    'item_id'         => (int)$item['item_id'],
                    'item_name'       => $item['item_name'],
                    'barcode'         => $item['barcode'],
                    'property_number' => $item['property_number'],
                    'category'        => $item['category'],
                    'location'        => $item['location'],
                    'status'          => $item['status'],
                    'item_condition'  => $item['item_condition'],
                ]
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'valid' => false, 'message' => 'Validation error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Stream / Download Clearance Proof Document (Secure Server-Side Handler)
     * Strictly validates authentication, RBAC boundaries, file paths, and MIME types.
     */
    public static function viewClearanceDocument(): void {
        AuthMiddleware::check();

        $incidentId = (int)($_GET['id'] ?? 0);
        if ($incidentId <= 0) {
            http_response_code(400);
            die("Invalid incident ID.");
        }

        $db = Database::getConnection();
        $incident = DamageIncident::findById($db, $incidentId);
        if (!$incident) {
            http_response_code(404);
            die("Clearance record not found.");
        }

        $viewerId   = (int)($_SESSION['user_id'] ?? 0);
        $viewerRole = $_SESSION['role'] ?? 'student';

        // RBAC: Custodian, Admin, and Executive Admin can view any document.
        // Borrowers can only view documents relating to their own damage incidents.
        $isStaff = in_array($viewerRole, ['custodian', 'admin', 'executive_admin'], true);
        if (!$isStaff && (int)$incident['borrower_id'] !== $viewerId) {
            http_response_code(403);
            die("Access denied. You do not have permission to view this document.");
        }

        $paymentId = (int)($_GET['payment_id'] ?? 0);
        $relPath   = null;
        if ($paymentId > 0) {
            $pStmt = $db->prepare("SELECT clearance_document_path FROM damage_incident_payments WHERE payment_id = :pid AND incident_id = :iid");
            $pStmt->execute([':pid' => $paymentId, ':iid' => $incidentId]);
            $relPath = $pStmt->fetchColumn() ?: null;
        }
        if (!$relPath) {
            $relPath = $incident['clearance_document_path'] ?? null;
        }

        if (empty($relPath)) {
            http_response_code(404);
            die("Clearance document not found.");
        }

        $uploadBase = realpath(ROOT_PATH . '/public/uploads/clearances');
        $fullPath   = realpath(ROOT_PATH . '/public' . $relPath);

        // Path traversal and existence check
        if (!$uploadBase || !$fullPath || !str_starts_with($fullPath, $uploadBase) || !is_file($fullPath)) {
            http_response_code(404);
            die("Document file missing or path is invalid.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fullPath);
        finfo_close($finfo);

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes, true)) {
            http_response_code(415);
            die("Unsupported media type.");
        }

        $safeDownloadName = 'Clearance_Proof_Incident_' . $incidentId . '.' . pathinfo($fullPath, PATHINFO_EXTENSION);

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($fullPath));
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        header('Content-Disposition: inline; filename="' . $safeDownloadName . '"');
        header('Cache-Control: private, max-age=3600');

        readfile($fullPath);
        exit;
    }

    /**
     * API: Custodian Grant Clearance & Settle Damage Incident (Strictly Custodian Only)
     */
    public static function apiResolveIncident(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $custodianId    = (int)($_SESSION['user_id'] ?? 0);
        $incidentId     = (int)($_POST['incident_id'] ?? 0);
        $settlementType = trim((string)($_POST['settlement_type'] ?? ''));
        $settlementRef  = trim((string)($_POST['settlement_reference'] ?? ''));
        $settlementNote = trim((string)($_POST['settlement_notes'] ?? ''));

        if ($incidentId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid incident ID.'], 422);
        }

        if (!in_array($settlementType, DamageIncident::VALID_SETTLEMENTS, true)) {
            self::jsonResponse(['success' => false, 'message' => 'Please select a valid settlement method.'], 422);
        }

        try {
            $db = Database::getConnection();
            $incident = DamageIncident::findById($db, $incidentId);
            if (!$incident) {
                self::jsonResponse(['success' => false, 'message' => 'Damage incident record not found.'], 404);
            }

            // 1. Strict Settlement-Specific Reference Validations
            if ($settlementType === DamageIncident::SETTLEMENT_IN_KIND) {
                if ($settlementRef === '') {
                    self::jsonResponse(['success' => false, 'message' => 'Replacement asset barcode or property number is required.'], 422);
                }

                $replacementItem = Item::findByBarcodeOrPropertyNumber($db, $settlementRef);
                if (!$replacementItem) {
                    self::jsonResponse([
                        'success' => false,
                        'message' => "Replacement barcode/serial \"{$settlementRef}\" does not exist in the asset catalog. Please register the physical unit first to prevent phantom inventory."
                    ], 422);
                }

                if ((int)$replacementItem['item_id'] === (int)$incident['item_id']) {
                    self::jsonResponse([
                        'success' => false,
                        'message' => "The replacement asset cannot be the same damaged item (#{$incident['item_id']})."
                    ], 422);
                }

                if ($replacementItem['status'] !== 'available') {
                    self::jsonResponse([
                        'success' => false,
                        'message' => "Replacement unit \"{$replacementItem['item_name']}\" is currently marked as '{$replacementItem['status']}'. Only available assets can serve as replacements."
                    ], 422);
                }

                if (in_array($replacementItem['item_condition'], ['damaged', 'not_working', 'for_disposal'], true)) {
                    self::jsonResponse([
                        'success' => false,
                        'message' => "Replacement unit \"{$replacementItem['item_name']}\" is in '{$replacementItem['item_condition']}' condition. Replacement unit must be in working condition."
                    ], 422);
                }

                // Standardize reference with barcode and asset name
                $settlementRef = $replacementItem['barcode'] . ' (' . $replacementItem['item_name'] . ')';
            } elseif ($settlementType === DamageIncident::SETTLEMENT_REPAIR_COST) {
                if ($settlementRef === '') {
                    self::jsonResponse(['success' => false, 'message' => 'Official Receipt (O.R.) number and amount are required.'], 422);
                }
            } elseif ($settlementType === DamageIncident::SETTLEMENT_ADMIN_WAIVER) {
                if ($settlementRef === '') {
                    self::jsonResponse(['success' => false, 'message' => 'Administrative authorization reference is required.'], 422);
                }
            }

            // 2. Secure Server-Side File Upload Processing
            $documentPath = null;
            $uploadedFile = $_FILES['clearance_document'] ?? null;
            $hasFile = ($uploadedFile && is_array($uploadedFile) && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

            // Require proof document upload for repair costs and administrative waivers
            if (in_array($settlementType, [DamageIncident::SETTLEMENT_REPAIR_COST, DamageIncident::SETTLEMENT_ADMIN_WAIVER], true)) {
                if (!$hasFile) {
                    $docTypeLabel = ($settlementType === DamageIncident::SETTLEMENT_REPAIR_COST) ? 'Official Receipt document' : 'Administrative waiver letter';
                    self::jsonResponse([
                        'success' => false,
                        'message' => "Please upload the {$docTypeLabel} (PDF or Image) to complete clearance."
                    ], 422);
                }
            }

            if ($hasFile) {
                if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                    self::jsonResponse(['success' => false, 'message' => 'Upload error occurred (Code: ' . (int)$uploadedFile['error'] . ').'], 400);
                }

                // File size ceiling: 8 MB
                $maxBytes = 8 * 1024 * 1024;
                if ($uploadedFile['size'] > $maxBytes) {
                    self::jsonResponse(['success' => false, 'message' => 'Document exceeds maximum allowed upload size of 8 MB.'], 422);
                }

                if (!is_uploaded_file($uploadedFile['tmp_name'])) {
                    self::jsonResponse(['success' => false, 'message' => 'Invalid file upload source.'], 400);
                }

                // Strict MIME inspection using PHP fileinfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
                finfo_close($finfo);

                $allowedMimes = [
                    'application/pdf' => 'pdf',
                    'image/jpeg'      => 'jpg',
                    'image/png'       => 'png',
                    'image/webp'      => 'webp',
                ];

                if (!isset($allowedMimes[$mimeType])) {
                    self::jsonResponse(['success' => false, 'message' => "Invalid file format ({$mimeType}). Only PDF documents and JPG/PNG/WEBP images are permitted."], 422);
                }
                $safeExt = $allowedMimes[$mimeType];

                // Client extension check
                $clientExt = strtolower(pathinfo((string)$uploadedFile['name'], PATHINFO_EXTENSION));
                $extMapping = [
                    'pdf'  => ['pdf'],
                    'jpg'  => ['jpg', 'jpeg'],
                    'jpeg' => ['jpg', 'jpeg'],
                    'png'  => ['png'],
                    'webp' => ['webp'],
                ];
                if (!isset($extMapping[$clientExt]) || !in_array($safeExt, $extMapping[$clientExt], true)) {
                    self::jsonResponse(['success' => false, 'message' => "File extension (.{$clientExt}) does not match detected format ({$mimeType})."], 422);
                }

                // Magic bytes validation for PDF and images
                if ($mimeType === 'application/pdf') {
                    $fh = fopen($uploadedFile['tmp_name'], 'rb');
                    $magic = fread($fh, 5);
                    fclose($fh);
                    if ($magic !== '%PDF-') {
                        self::jsonResponse(['success' => false, 'message' => 'Uploaded file is not a valid PDF document.'], 422);
                    }
                } elseif (in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    if (@getimagesize($uploadedFile['tmp_name']) === false) {
                        self::jsonResponse(['success' => false, 'message' => 'Uploaded file is corrupted or not a valid image.'], 422);
                    }
                }

                $uploadDir = ROOT_PATH . '/public/uploads/clearances';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Cryptographically random, collision-resistant filename
                $randomHex = bin2hex(random_bytes(10));
                $safeFileName = "clearance_{$incidentId}_{$randomHex}.{$safeExt}";
                $destPath = $uploadDir . '/' . $safeFileName;

                if (!move_uploaded_file($uploadedFile['tmp_name'], $destPath)) {
                    self::jsonResponse(['success' => false, 'message' => 'Failed to store uploaded document on server storage.'], 500);
                }
                @chmod($destPath, 0644);
                $documentPath = '/uploads/clearances/' . $safeFileName;
            }

            // 3. Resolve Incident with Document Path
            $result = DamageIncident::resolve($db, $incidentId, $settlementType, $settlementRef, $settlementNote, $custodianId, $documentPath);

            $methodLabels = [
                DamageIncident::SETTLEMENT_IN_KIND      => 'In-Kind Replacement',
                DamageIncident::SETTLEMENT_REPAIR_COST  => 'Repair Cost Paid',
                DamageIncident::SETTLEMENT_ADMIN_WAIVER => 'Administrative Waiver',
            ];
            $methodName = $methodLabels[$settlementType] ?? $settlementType;

            $docNote = $documentPath ? " | Proof Document: {$documentPath}" : "";
            AuditLog::record(
                $db,
                $custodianId,
                'DAMAGE_CLEARANCE_GRANTED',
                "Granted clearance for Incident #{$incidentId} (Borrower: {$incident['borrower_name']} [{$incident['borrower_id_number']}], Asset: {$incident['item_name']} [{$incident['barcode']}]). Method: {$methodName}. Ref: {$settlementRef}{$docNote}"
            );

            // Dispatch notification to borrower
            if (!empty($incident['borrower_id'])) {
                \App\Services\NotificationService::create(
                    $db,
                    (int)$incident['borrower_id'],
                    \App\Services\NotificationService::CAT_CLEARANCE,
                    "Damage Clearance Settled",
                    "Your damage incident #{$incidentId} for {$incident['item_name']} ({$incident['barcode']}) has been cleared via {$methodName}.",
                    \App\Services\NotificationService::TYPE_SUCCESS,
                    url('/records')
                );
            }

            $clearanceMsg = $result['hold_fully_lifted']
                ? "Clearance granted! The borrower's account hold has been completely lifted and borrowing rights are restored."
                : "Incident settled. Notice: The borrower still has {$result['remaining_holds']} other unresolved hold(s).";

            self::jsonResponse([
                'success'           => true,
                'message'           => $clearanceMsg,
                'incident_id'       => $incidentId,
                'document_path'     => $documentPath,
                'hold_fully_lifted' => $result['hold_fully_lifted'],
                'remaining_holds'   => $result['remaining_holds'],
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Failed to resolve incident: ' . $e->getMessage()], 400);
        }
    }

    /**
     * API: Get current user's active damage hold details (if any)
     */
    public static function apiGetMyDamageHold(): void {
        AuthMiddleware::check();

        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $db = Database::getConnection();
            $activeHold = DamageIncident::getActiveHoldByBorrower($db, $userId);

            self::jsonResponse([
                'success'     => true,
                'has_hold'    => $activeHold !== null,
                'incident'    => $activeHold,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'has_hold' => false, 'incident' => null], 500);
        }
    }
}

