<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Throwable;
use InvalidArgumentException;
use RuntimeException;
use App\Helpers\Security;
use App\Models\DamageIncident;
use App\Models\Item;
use App\Models\User;
use App\Models\AuditLog;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\NotificationService;
use App\Services\EventDispatcher;

/**
 * ClearanceController — Damage Settlement & Official Receipt Verification Console
 * Manages student cashier receipt submissions, custodian review & verification,
 * and automated borrower clearance hold synchronization.
 * 
 * Strict OWASP Compliance: CSRF validation, strict MIME/magic byte inspection,
 * path traversal defense, and ownership authorization.
 */
class ClearanceController {

    private static function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Render the Student "My Clearances" Portal (/my-clearances)
     */
    public static function myClearances(): void {
        AuthMiddleware::check();

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role   = $_SESSION['role'] ?? 'student';
        $title  = 'My Clearances';
        $breadcrumb = 'Clearances';

        $db = Database::getConnection();
        $csrfToken = Security::generateCsrfToken();

        $incidents = DamageIncident::getByBorrower($db, $userId);

        // Compute summary metrics
        $totalUnsettledFee  = 0.0;
        $pendingVerifCount  = 0;
        $partiallyPaidCount = 0;
        $clearedCount       = 0;
        $activeHoldCount    = 0;

        foreach ($incidents as &$inc) {
            $st = $inc['incident_status'];
            $inc['payments'] = DamageIncident::getPayments($db, (int)$inc['incident_id']);

            if ($st === DamageIncident::STATUS_CLEARED) {
                $clearedCount++;
            } else {
                $activeHoldCount++;
                if ($st === DamageIncident::STATUS_PENDING_VERIFICATION) {
                    $pendingVerifCount++;
                } elseif ($st === DamageIncident::STATUS_PARTIALLY_PAID) {
                    $partiallyPaidCount++;
                }

                $rem = $inc['remaining_balance'] !== null 
                    ? (float)$inc['remaining_balance'] 
                    : ($inc['estimated_cost'] !== null ? (float)$inc['estimated_cost'] : 0.0);
                if ($rem > 0) {
                    $totalUnsettledFee += $rem;
                }
            }
        }
        unset($inc);

        require __DIR__ . '/../../views/clearances/student.php';
    }

    /**
     * Student API: Submit Official Cashier Receipt for Verification
     */
    public static function apiSubmitReceipt(): void {
        AuthMiddleware::check();

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $userId        = (int)($_SESSION['user_id'] ?? 0);
        $incidentId    = (int)($_POST['incident_id'] ?? 0);
        $receiptNumber = trim((string)($_POST['receipt_number'] ?? ''));
        $amountPaid    = isset($_POST['amount_paid']) && is_numeric($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : 0.0;
        $paymentDate   = trim((string)($_POST['payment_date'] ?? date('Y-m-d')));
        $studentNotes  = trim((string)($_POST['student_notes'] ?? ''));

        if ($incidentId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid incident ID.'], 422);
        }
        if ($receiptNumber === '') {
            self::jsonResponse(['success' => false, 'message' => 'Official Receipt (O.R.) number is required.'], 422);
        }
        if ($amountPaid <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Please enter the valid payment amount shown on your receipt.'], 422);
        }

        try {
            $db = Database::getConnection();
            $incident = DamageIncident::findById($db, $incidentId);

            if (!$incident) {
                self::jsonResponse(['success' => false, 'message' => 'Damage incident record not found.'], 404);
            }

            // Ownership check
            if ((int)$incident['borrower_id'] !== $userId) {
                self::jsonResponse(['success' => false, 'message' => 'Access denied: You can only submit receipts for your own liabilities.'], 403);
            }

            if ($incident['incident_status'] === DamageIncident::STATUS_CLEARED) {
                self::jsonResponse(['success' => false, 'message' => 'This incident has already been verified and cleared.'], 422);
            }

            if ($incident['incident_status'] === DamageIncident::STATUS_PENDING_VERIFICATION) {
                self::jsonResponse(['success' => false, 'message' => 'Your receipt has already been submitted and is currently pending verification. You cannot edit it while it is under review.'], 422);
            }

            // Secure Server-Side File Upload Inspection
            $uploadedFile = $_FILES['receipt_document'] ?? null;
            if (!$uploadedFile || !is_array($uploadedFile) || ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                // If re-submitting and document was already uploaded, allow keeping existing if no new file is attached
                if (empty($incident['clearance_document_path'])) {
                    self::jsonResponse(['success' => false, 'message' => 'Please upload a photo or PDF scan of your Official Receipt.'], 422);
                }
                $documentPath = $incident['clearance_document_path'];
            } else {
                if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                    self::jsonResponse(['success' => false, 'message' => 'File upload error occurred (Code: ' . (int)$uploadedFile['error'] . ').'], 400);
                }

                $maxBytes = 8 * 1024 * 1024; // 8 MB
                if ($uploadedFile['size'] > $maxBytes) {
                    self::jsonResponse(['success' => false, 'message' => 'Receipt file exceeds maximum allowed size of 8 MB.'], 422);
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

                // Magic bytes validation
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

                // Collision-resistant filename
                $randomHex = bin2hex(random_bytes(10));
                $safeFileName = "receipt_{$incidentId}_{$randomHex}.{$safeExt}";
                $destPath = $uploadDir . '/' . $safeFileName;

                if (!move_uploaded_file($uploadedFile['tmp_name'], $destPath)) {
                    self::jsonResponse(['success' => false, 'message' => 'Failed to store receipt on server storage.'], 500);
                }
                @chmod($destPath, 0644);
                $documentPath = '/uploads/clearances/' . $safeFileName;
            }

            // Record submission
            DamageIncident::submitReceipt(
                $db,
                $incidentId,
                $userId,
                $receiptNumber,
                $amountPaid,
                $paymentDate,
                $documentPath,
                $studentNotes
            );

            // Notify custodians
            NotificationService::broadcastToRole(
                $db,
                'custodian',
                NotificationService::CAT_DAMAGE_HOLD,
                "New Damage Receipt Submitted",
                "Borrower {$incident['borrower_name']} submitted receipt #{$receiptNumber} (PHP " . number_format($amountPaid, 2) . ") for {$incident['item_name']}. Please verify.",
                NotificationService::TYPE_INFO,
                url('/clearances')
            );

            AuditLog::record(
                $db,
                $userId,
                'DAMAGE_RECEIPT_SUBMITTED',
                "Submitted cashier receipt for Incident #{$incidentId} (Asset: {$incident['item_name']} [{$incident['barcode']}]). O.R. #{$receiptNumber}, Amount: PHP " . number_format($amountPaid, 2)
            );

            // Real-Time Broadcast: Notify custodians of pending receipt verification
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_CLEARANCE_MUTATED,
                'damage_incident',
                $incidentId,
                [
                    'action'        => 'receipt_submitted',
                    'incident_id'   => $incidentId,
                    'status'        => DamageIncident::STATUS_PENDING_VERIFICATION,
                    'borrower_id'   => $userId,
                    'borrower_name' => $incident['borrower_name'] ?? 'Borrower',
                    'item_name'     => $incident['item_name'] ?? 'Asset'
                ],
                'custodian'
            );

            self::jsonResponse([
                'success' => true,
                'message' => 'Official receipt submitted successfully. The Property Custodian will verify your submission.',
                'incident_id' => $incidentId,
                'status' => DamageIncident::STATUS_PENDING_VERIFICATION,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error submitting receipt: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Custodian API: Verify Official Receipt & Grant Clearance
     */
    public static function apiVerify(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $custodianId   = (int)($_SESSION['user_id'] ?? 0);
        $incidentId    = (int)($_POST['incident_id'] ?? 0);
        $notes         = trim((string)($_POST['settlement_notes'] ?? ''));
        $acceptAsFull  = !empty($_POST['accept_as_full']) && in_array($_POST['accept_as_full'], ['1', 'true', true], true);

        if ($incidentId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid incident ID.'], 422);
        }

        try {
            $db = Database::getConnection();
            $incident = DamageIncident::findById($db, $incidentId);

            if (!$incident) {
                self::jsonResponse(['success' => false, 'message' => 'Incident record not found.'], 404);
            }

            if ($incident['incident_status'] === DamageIncident::STATUS_CLEARED) {
                self::jsonResponse(['success' => false, 'message' => 'Incident is already cleared.'], 422);
            }

            if ($incident['incident_status'] !== DamageIncident::STATUS_PENDING_VERIFICATION) {
                self::jsonResponse(['success' => false, 'message' => 'Cannot verify receipt: This incident is not pending verification (current status: ' . htmlspecialchars($incident['incident_status'], ENT_QUOTES, 'UTF-8') . ').'], 422);
            }

            if (empty($incident['clearance_document_path'])) {
                self::jsonResponse(['success' => false, 'message' => 'No receipt document has been uploaded for this incident.'], 422);
            }

            $result = DamageIncident::verifyIncident($db, $incidentId, $custodianId, $notes, $acceptAsFull);

            // Audit Trail
            $orStr = !empty($incident['receipt_number']) ? " (O.R. #{$incident['receipt_number']}, Amount: PHP " . number_format((float)($incident['amount_paid'] ?? 0), 2) . ")" : "";
            
            if ($result['is_cleared']) {
                AuditLog::record(
                    $db,
                    $custodianId,
                    'DAMAGE_CLEARANCE_VERIFIED',
                    "Verified and cleared Incident #{$incidentId} for {$incident['borrower_name']} [{$incident['borrower_id_number']}]{$orStr}. Asset: {$incident['item_name']} [{$incident['barcode']}]."
                );

                if (!empty($incident['borrower_id'])) {
                    NotificationService::create(
                        $db,
                        (int)$incident['borrower_id'],
                        NotificationService::CAT_DAMAGE_HOLD,
                        "Damage Clearance Verified & Cleared",
                        "Your payment receipt for {$incident['item_name']} ({$incident['barcode']}) has been verified. " . ($result['hold_fully_lifted'] ? "Your borrowing privileges have been fully restored!" : "You have remaining holds on other items."),
                        NotificationService::TYPE_SUCCESS,
                        url('/my-clearances')
                    );
                }

                $msg = 'Official receipt verified and clearance granted.';
            } else {
                AuditLog::record(
                    $db,
                    $custodianId,
                    'DAMAGE_PARTIAL_PAYMENT_VERIFIED',
                    "Verified partial payment for Incident #{$incidentId} ({$incident['item_name']}). Total Paid: PHP " . number_format($result['total_paid'], 2) . ", Balance Due: PHP " . number_format($result['remaining_balance'], 2)
                );

                if (!empty($incident['borrower_id'])) {
                    NotificationService::create(
                        $db,
                        (int)$incident['borrower_id'],
                        NotificationService::CAT_DAMAGE_HOLD,
                        "Partial Payment Verified",
                        "Your payment of PHP " . number_format((float)$incident['amount_paid'], 2) . " for {$incident['item_name']} has been credited. Remaining balance due: PHP " . number_format($result['remaining_balance'], 2) . ". Please settle the balance at the cashier to clear your hold.",
                        NotificationService::TYPE_WARNING,
                        url('/my-clearances')
                    );
                }

                $msg = 'Partial payment verified. Remaining balance due: PHP ' . number_format($result['remaining_balance'], 2) . '.';
            }

            // Real-Time Broadcast: Clearance payment verified / hold status updated
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_CLEARANCE_MUTATED,
                'damage_incident',
                $incidentId,
                [
                    'action'            => 'verified',
                    'incident_id'       => $incidentId,
                    'status'            => $result['status'],
                    'is_cleared'        => $result['is_cleared'],
                    'remaining_balance' => $result['remaining_balance'],
                    'hold_fully_lifted' => $result['hold_fully_lifted'],
                    'borrower_id'       => (int)($incident['borrower_id'] ?? 0)
                ]
            );

            self::jsonResponse([
                'success'           => true,
                'message'           => $msg,
                'incident_id'       => $incidentId,
                'status'            => $result['status'],
                'is_cleared'        => $result['is_cleared'],
                'total_paid'        => $result['total_paid'],
                'remaining_balance' => $result['remaining_balance'],
                'hold_fully_lifted' => $result['hold_fully_lifted'],
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error verifying clearance: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Custodian API: Reject Receipt with Rejection Reason
     */
    public static function apiReject(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $custodianId = (int)($_SESSION['user_id'] ?? 0);
        $incidentId  = (int)($_POST['incident_id'] ?? 0);
        $reason      = trim((string)($_POST['rejection_reason'] ?? ''));

        if ($incidentId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid incident ID.'], 422);
        }
        if ($reason === '') {
            self::jsonResponse(['success' => false, 'message' => 'Please provide a reason for rejecting the receipt.'], 422);
        }

        try {
            $db = Database::getConnection();
            $incident = DamageIncident::findById($db, $incidentId);

            if (!$incident) {
                self::jsonResponse(['success' => false, 'message' => 'Incident record not found.'], 404);
            }

            if ($incident['incident_status'] === DamageIncident::STATUS_CLEARED) {
                self::jsonResponse(['success' => false, 'message' => 'Cannot reject an incident that has already been cleared.'], 422);
            }

            if ($incident['incident_status'] !== DamageIncident::STATUS_PENDING_VERIFICATION) {
                self::jsonResponse(['success' => false, 'message' => 'Cannot reject receipt: This incident does not have a receipt pending verification (current status: ' . htmlspecialchars($incident['incident_status'], ENT_QUOTES, 'UTF-8') . ').'], 422);
            }

            DamageIncident::rejectIncident($db, $incidentId, $custodianId, $reason);

            AuditLog::record(
                $db,
                $custodianId,
                'DAMAGE_RECEIPT_REJECTED',
                "Rejected receipt for Incident #{$incidentId} (Borrower: {$incident['borrower_name']}). Reason: {$reason}"
            );

            // Notify borrower
            if (!empty($incident['borrower_id'])) {
                NotificationService::create(
                    $db,
                    (int)$incident['borrower_id'],
                    NotificationService::CAT_DAMAGE_HOLD,
                    "Damage Receipt Rejected",
                    "Your submitted receipt for {$incident['item_name']} was rejected: \"{$reason}\". Please upload a valid official receipt.",
                    NotificationService::TYPE_DANGER,
                    url('/my-clearances')
                );
            }

            // Real-Time Broadcast: Receipt rejected, notify student and custodian queue
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_CLEARANCE_MUTATED,
                'damage_incident',
                $incidentId,
                [
                    'action'      => 'rejected',
                    'incident_id' => $incidentId,
                    'status'      => 'rejected',
                    'reason'      => $reason,
                    'borrower_id' => (int)($incident['borrower_id'] ?? 0)
                ]
            );

            self::jsonResponse([
                'success' => true,
                'message' => 'Receipt has been rejected and the student has been notified to re-upload.',
                'incident_id' => $incidentId,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error rejecting receipt: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Custodian API: Update Assessed Fee
     */
    public static function apiUpdateFee(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired security token.'], 403);
        }

        $custodianId = (int)($_SESSION['user_id'] ?? 0);
        $incidentId  = (int)($_POST['incident_id'] ?? 0);
        $newCost     = isset($_POST['estimated_cost']) && is_numeric($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : -1.0;

        if ($incidentId <= 0 || $newCost < 0) {
            self::jsonResponse(['success' => false, 'message' => 'Valid assessed fee amount is required.'], 422);
        }

        try {
            $db = Database::getConnection();
            $incident = DamageIncident::findById($db, $incidentId);

            if (!$incident) {
                self::jsonResponse(['success' => false, 'message' => 'Incident record not found.'], 404);
            }

            if ($incident['incident_status'] === DamageIncident::STATUS_CLEARED) {
                self::jsonResponse(['success' => false, 'message' => 'Cannot change fee on an already cleared incident.'], 422);
            }

            DamageIncident::updateAssessedFee($db, $incidentId, $newCost);

            AuditLog::record(
                $db,
                $custodianId,
                'DAMAGE_FEE_UPDATED',
                "Updated assessed damage fee for Incident #{$incidentId} ({$incident['item_name']}) to PHP " . number_format($newCost, 2)
            );

            // Notify borrower of fee update
            if (!empty($incident['borrower_id'])) {
                NotificationService::create(
                    $db,
                    (int)$incident['borrower_id'],
                    NotificationService::CAT_DAMAGE_HOLD,
                    "Damage Assessment Fee Updated",
                    "The assessed damage fee for {$incident['item_name']} has been set to PHP " . number_format($newCost, 2) . ". Please settle at the cashier.",
                    NotificationService::TYPE_WARNING,
                    url('/my-clearances')
                );
            }

            self::jsonResponse([
                'success' => true,
                'message' => 'Assessed damage fee updated successfully.',
                'incident_id' => $incidentId,
                'new_cost' => $newCost,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error updating fee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Get Payment History Ledger for an Incident (Custodian, Admin, or Borrower)
     */
    public static function apiGetPayments(): void {
        AuthMiddleware::check();

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role   = $_SESSION['role'] ?? 'student';
        $incidentId = (int)($_GET['incident_id'] ?? $_GET['id'] ?? 0);

        if ($incidentId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid incident ID.'], 422);
        }

        try {
            $db = Database::getConnection();
            $incident = DamageIncident::findById($db, $incidentId);

            if (!$incident) {
                self::jsonResponse(['success' => false, 'message' => 'Incident record not found.'], 404);
            }

            // Student/Faculty can only view payments for their own incidents
            if (!in_array($role, ['custodian', 'admin', 'executive_admin'], true) && (int)$incident['borrower_id'] !== $userId) {
                self::jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
            }

            $payments = DamageIncident::getPayments($db, $incidentId);

            self::jsonResponse([
                'success'           => true,
                'incident_id'       => $incidentId,
                'estimated_cost'    => (float)($incident['estimated_cost'] ?? 0),
                'amount_paid'       => (float)($incident['amount_paid'] ?? 0),
                'remaining_balance' => $incident['remaining_balance'] !== null ? (float)$incident['remaining_balance'] : max(0.0, (float)($incident['estimated_cost'] ?? 0) - (float)($incident['amount_paid'] ?? 0)),
                'incident_status'   => $incident['incident_status'],
                'payments'          => $payments,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error retrieving payments: ' . $e->getMessage()], 500);
        }
    }
}
