<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use RuntimeException;
use InvalidArgumentException;

/**
 * DamageIncident Model — Property Damage & Receipt Verification Ledger
 * Tracks physical equipment damage, manages borrower holds, records student
 * cashier receipt submissions, and enables custodian verification.
 */
class DamageIncident {

    // Lifecycle Statuses
    public const STATUS_AWAITING_PAYMENT     = 'awaiting_payment';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_PARTIALLY_PAID       = 'partially_paid';
    public const STATUS_CLEARED              = 'cleared';
    public const STATUS_REJECTED             = 'rejected';
    public const STATUS_OPEN_HOLD            = 'open_hold'; // Legacy alias

    public const ACTIVE_HOLD_STATUSES = [
        self::STATUS_AWAITING_PAYMENT,
        self::STATUS_PENDING_VERIFICATION,
        self::STATUS_PARTIALLY_PAID,
        self::STATUS_REJECTED,
        self::STATUS_OPEN_HOLD,
    ];

    /**
     * Create a new damage incident record and enforce user clearance hold
     */
    public static function create(PDO $db, array $data): int {
        $required = ['transaction_id', 'item_id', 'borrower_id', 'reported_by', 'damage_condition', 'damage_description'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                throw new InvalidArgumentException("Missing required incident field: {$field}");
            }
        }

        $estimatedCost = isset($data['estimated_cost']) && is_numeric($data['estimated_cost']) && (float)$data['estimated_cost'] >= 0
            ? (float)$data['estimated_cost']
            : null;

        $stmt = $db->prepare("
            INSERT INTO damage_incidents (
                transaction_id,
                item_id,
                borrower_id,
                reported_by,
                damage_condition,
                damage_description,
                estimated_cost,
                remaining_balance,
                incident_status
            ) VALUES (
                :transaction_id,
                :item_id,
                :borrower_id,
                :reported_by,
                :damage_condition,
                :damage_description,
                :estimated_cost,
                :remaining_balance,
                'awaiting_payment'
            )
        ");

        $stmt->execute([
            ':transaction_id'      => (int)$data['transaction_id'],
            ':item_id'             => (int)$data['item_id'],
            ':borrower_id'         => (int)$data['borrower_id'],
            ':reported_by'         => (int)$data['reported_by'],
            ':damage_condition'    => (string)$data['damage_condition'],
            ':damage_description'  => trim((string)$data['damage_description']),
            ':estimated_cost'      => $estimatedCost,
            ':remaining_balance'   => $estimatedCost,
        ]);

        $incidentId = (int)$db->lastInsertId();

        // Enforce borrower account clearance hold
        $userUpdate = $db->prepare("UPDATE users SET has_damage_hold = TRUE, updated_at = NOW() WHERE user_id = :uid");
        $userUpdate->execute([':uid' => (int)$data['borrower_id']]);

        return $incidentId;
    }

    /**
     * Student submits official cashier receipt proof for verification
     */
    public static function submitReceipt(
        PDO $db,
        int $incidentId,
        int $borrowerId,
        string $receiptNumber,
        float $amountPaid,
        string $paymentDate,
        string $documentPath,
        ?string $studentNotes = null
    ): array {
        if ($incidentId <= 0 || $borrowerId <= 0) {
            throw new InvalidArgumentException("Invalid incident or borrower ID.");
        }
        if (trim($receiptNumber) === '') {
            throw new InvalidArgumentException("Official Receipt (O.R.) number is required.");
        }
        if ($amountPaid <= 0) {
            throw new InvalidArgumentException("Amount paid must be greater than zero.");
        }
        if (trim($documentPath) === '') {
            throw new InvalidArgumentException("Official receipt document is required.");
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT * FROM damage_incidents WHERE incident_id = :id FOR UPDATE");
            $stmt->execute([':id' => $incidentId]);
            $incident = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$incident) {
                throw new RuntimeException("Damage incident record #{$incidentId} not found.");
            }

            // Strict ownership verification
            if ((int)$incident['borrower_id'] !== $borrowerId) {
                throw new RuntimeException("Access denied: You can only submit receipts for your own damage incidents.");
            }

            if ($incident['incident_status'] === self::STATUS_CLEARED) {
                throw new RuntimeException("Incident #{$incidentId} has already been verified and cleared.");
            }

            if ($incident['incident_status'] === self::STATUS_PENDING_VERIFICATION) {
                throw new RuntimeException("Receipt for incident #{$incidentId} is currently pending custodian verification and cannot be modified while under review.");
            }

            // Record this payment installment into damage_incident_payments ledger
            $payStmt = $db->prepare("
                INSERT INTO damage_incident_payments (
                    incident_id,
                    borrower_id,
                    receipt_number,
                    amount_paid,
                    payment_date,
                    clearance_document_path,
                    student_notes,
                    payment_status,
                    created_at
                ) VALUES (
                    :iid,
                    :bid,
                    :rnum,
                    :amt,
                    :pdate,
                    :doc,
                    :notes,
                    'pending',
                    NOW()
                )
            ");
            $payStmt->execute([
                ':iid'   => $incidentId,
                ':bid'   => $borrowerId,
                ':rnum'  => trim($receiptNumber),
                ':amt'   => $amountPaid,
                ':pdate' => $paymentDate,
                ':doc'   => trim($documentPath),
                ':notes' => $studentNotes !== null ? trim($studentNotes) : null,
            ]);
            $paymentId = (int)$db->lastInsertId();

            // Update damage_incidents latest submission snapshot
            // Note: amount_paid is strictly preserved as verified funds and is not overwritten with pending amounts.
            $update = $db->prepare("
                UPDATE damage_incidents
                SET receipt_number          = :rnum,
                    payment_date            = :pdate,
                    clearance_document_path = :doc,
                    student_notes           = :notes,
                    receipt_submitted_at    = NOW(),
                    incident_status         = 'pending_verification',
                    settlement_type         = 'repair_cost',
                    settlement_reference    = :sref,
                    rejection_reason        = NULL,
                    updated_at              = NOW()
                WHERE incident_id = :id
            ");

            $update->execute([
                ':rnum'  => trim($receiptNumber),
                ':pdate' => $paymentDate,
                ':doc'   => trim($documentPath),
                ':notes' => $studentNotes !== null ? trim($studentNotes) : null,
                ':sref'  => 'O.R. #' . trim($receiptNumber) . ' (PHP ' . number_format($amountPaid, 2) . ')',
                ':id'    => $incidentId,
            ]);

            // Synchronize verified balances
            self::syncIncidentBalances($db, $incidentId);

            $db->commit();

            return [
                'success'     => true,
                'incident_id' => $incidentId,
                'payment_id'  => $paymentId,
                'item_id'     => (int)$incident['item_id'],
                'status'      => self::STATUS_PENDING_VERIFICATION,
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Synchronize and recalculate an incident's verified amount_paid and remaining_balance
     * strictly based on the authoritative damage_incident_payments ledger.
     */
    public static function syncIncidentBalances(PDO $db, int $incidentId): array {
        if ($incidentId <= 0) {
            return ['amount_paid' => 0.00, 'remaining_balance' => 0.00];
        }

        // 1. Compute total verified payments from ledger
        $sumStmt = $db->prepare("
            SELECT COALESCE(SUM(amount_paid), 0)
            FROM damage_incident_payments
            WHERE incident_id = :id AND payment_status = 'verified'
        ");
        $sumStmt->execute([':id' => $incidentId]);
        $verifiedPaid = (float)$sumStmt->fetchColumn();

        // 2. Fetch current incident record
        $stmt = $db->prepare("SELECT estimated_cost, incident_status FROM damage_incidents WHERE incident_id = :id");
        $stmt->execute([':id' => $incidentId]);
        $inc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inc) {
            return ['amount_paid' => $verifiedPaid, 'remaining_balance' => 0.00];
        }

        $estimatedCost = $inc['estimated_cost'] !== null ? (float)$inc['estimated_cost'] : 0.0;
        $isCleared = ($inc['incident_status'] === self::STATUS_CLEARED);
        $remainingBalance = $isCleared ? 0.00 : max(0.00, $estimatedCost - $verifiedPaid);

        // 3. Update damage_incidents record
        $update = $db->prepare("
            UPDATE damage_incidents
            SET amount_paid       = :amt,
                remaining_balance = :rem,
                updated_at        = NOW()
            WHERE incident_id = :id
        ");
        $update->execute([
            ':amt' => $verifiedPaid,
            ':rem' => $remainingBalance,
            ':id'  => $incidentId,
        ]);

        return [
            'amount_paid'       => $verifiedPaid,
            'remaining_balance' => $remainingBalance,
        ];
    }

    /**
     * Custodian verifies receipt, updates balance ledger, and grants clearance or partial payment status
     */
    public static function verifyIncident(
        PDO $db,
        int $incidentId,
        int $custodianId,
        ?string $settlementNotes = null,
        bool $acceptAsFull = false
    ): array {
        if ($incidentId <= 0 || $custodianId <= 0) {
            throw new InvalidArgumentException("Invalid incident or custodian ID.");
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT * FROM damage_incidents WHERE incident_id = :id FOR UPDATE");
            $stmt->execute([':id' => $incidentId]);
            $incident = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$incident) {
                throw new RuntimeException("Incident record #{$incidentId} not found.");
            }

            if ($incident['incident_status'] === self::STATUS_CLEARED) {
                throw new RuntimeException("Incident #{$incidentId} has already been verified and cleared.");
            }

            if ($incident['incident_status'] !== self::STATUS_PENDING_VERIFICATION) {
                throw new RuntimeException("Cannot verify incident #{$incidentId}: Status is '{$incident['incident_status']}', not pending verification.");
            }

            if (empty($incident['clearance_document_path'])) {
                throw new RuntimeException("Cannot verify incident without an uploaded official receipt document.");
            }

            $borrowerId = (int)$incident['borrower_id'];

            // 1. Mark pending payment records in ledger as verified
            $payUpdate = $db->prepare("
                UPDATE damage_incident_payments
                SET payment_status = 'verified',
                    verified_by    = :cby,
                    verified_at    = NOW()
                WHERE incident_id = :id AND payment_status = 'pending'
            ");
            $payUpdate->execute([
                ':cby' => $custodianId,
                ':id'  => $incidentId,
            ]);

            // 2. Synchronize and compute total verified payments from ledger
            $bal = self::syncIncidentBalances($db, $incidentId);
            $totalVerified = $bal['amount_paid'];

            $assessedCost = $incident['estimated_cost'] !== null ? (float)$incident['estimated_cost'] : 0.0;
            $rawRemaining = max(0.0, $assessedCost - $totalVerified);

            // Determine if incident is cleared or partially paid
            $isCleared = ($rawRemaining <= 0.009) || $acceptAsFull;
            $newStatus = $isCleared ? self::STATUS_CLEARED : self::STATUS_PARTIALLY_PAID;
            $remainingBalance = $isCleared ? 0.00 : $rawRemaining;

            // 3. Update damage_incidents record
            $updateStmt = $db->prepare("
                UPDATE damage_incidents
                SET incident_status   = :st,
                    amount_paid       = :amt,
                    remaining_balance = :rem,
                    settlement_type   = 'repair_cost',
                    settlement_notes  = :snotes,
                    cleared_by        = CASE WHEN :is_cleared = 1 THEN :cby ELSE cleared_by END,
                    cleared_at        = CASE WHEN :is_cleared_2 = 1 THEN NOW() ELSE cleared_at END,
                    updated_at        = NOW()
                WHERE incident_id = :id
            ");
            $updateStmt->execute([
                ':st'           => $newStatus,
                ':amt'          => $totalVerified,
                ':rem'          => $remainingBalance,
                ':snotes'       => $settlementNotes !== null ? trim($settlementNotes) : null,
                ':is_cleared'   => $isCleared ? 1 : 0,
                ':cby'          => $custodianId,
                ':is_cleared_2' => $isCleared ? 1 : 0,
                ':id'           => $incidentId,
            ]);

            // 4. Synchronize borrower hold status (check if remaining open holds exist)
            $remainingHoldStmt = $db->prepare("
                SELECT COUNT(*) FROM damage_incidents
                WHERE borrower_id = :uid
                  AND incident_status IN ('awaiting_payment', 'pending_verification', 'partially_paid', 'rejected', 'open_hold')
                  AND incident_id != :id
            ");
            $remainingHoldStmt->execute([
                ':uid' => $borrowerId,
                ':id'  => $incidentId
            ]);
            $otherActiveHolds = (int)$remainingHoldStmt->fetchColumn();

            $holdLifted = false;
            if ($isCleared && $otherActiveHolds === 0) {
                $userClearStmt = $db->prepare("
                    UPDATE users 
                    SET has_damage_hold = FALSE, 
                        updated_at = NOW() 
                    WHERE user_id = :uid
                ");
                $userClearStmt->execute([':uid' => $borrowerId]);
                $holdLifted = true;
            } else {
                // Ensure borrower hold remains locked
                $userHoldStmt = $db->prepare("
                    UPDATE users 
                    SET has_damage_hold = TRUE, 
                        updated_at = NOW() 
                    WHERE user_id = :uid
                ");
                $userHoldStmt->execute([':uid' => $borrowerId]);
                $holdLifted = false;
            }

            $db->commit();

            return [
                'success'           => true,
                'incident_id'       => $incidentId,
                'borrower_id'       => $borrowerId,
                'item_id'           => (int)$incident['item_id'],
                'status'            => $newStatus,
                'is_cleared'        => $isCleared,
                'total_paid'        => $totalVerified,
                'remaining_balance' => $remainingBalance,
                'other_holds'       => $otherActiveHolds,
                'hold_fully_lifted' => $holdLifted,
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Custodian rejects submitted receipt with feedback reason
     */
    public static function rejectIncident(
        PDO $db,
        int $incidentId,
        int $custodianId,
        string $rejectionReason
    ): array {
        if ($incidentId <= 0 || $custodianId <= 0) {
            throw new InvalidArgumentException("Invalid incident or custodian ID.");
        }
        if (trim($rejectionReason) === '') {
            throw new InvalidArgumentException("Please specify a reason for rejecting the receipt.");
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT * FROM damage_incidents WHERE incident_id = :id FOR UPDATE");
            $stmt->execute([':id' => $incidentId]);
            $incident = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$incident) {
                throw new RuntimeException("Incident record #{$incidentId} not found.");
            }

            if ($incident['incident_status'] === self::STATUS_CLEARED) {
                throw new RuntimeException("Cannot reject an incident that has already been cleared.");
            }

            if ($incident['incident_status'] !== self::STATUS_PENDING_VERIFICATION) {
                throw new RuntimeException("Cannot reject receipt for incident #{$incidentId}: Status is '{$incident['incident_status']}', not pending verification.");
            }

            // Mark any pending payment as rejected
            $payReject = $db->prepare("
                UPDATE damage_incident_payments
                SET payment_status   = 'rejected',
                    rejection_reason = :reason,
                    verified_by      = :cby,
                    verified_at      = NOW()
                WHERE incident_id = :id AND payment_status = 'pending'
            ");
            $payReject->execute([
                ':reason' => trim($rejectionReason),
                ':cby'    => $custodianId,
                ':id'     => $incidentId,
            ]);

            // Re-synchronize verified balances strictly from ledger
            $bal = self::syncIncidentBalances($db, $incidentId);
            $hasVerified = $bal['amount_paid'] > 0;

            // If it was already partially paid, preserve partially_paid; otherwise rejected
            $newStatus = $hasVerified ? self::STATUS_PARTIALLY_PAID : self::STATUS_REJECTED;

            $update = $db->prepare("
                UPDATE damage_incidents
                SET incident_status  = :st,
                    rejection_reason = :reason,
                    updated_at       = NOW()
                WHERE incident_id = :id
            ");
            $update->execute([
                ':st'     => $newStatus,
                ':reason' => trim($rejectionReason),
                ':id'     => $incidentId,
            ]);

            // Ensure borrower hold remains locked
            $userHoldStmt = $db->prepare("UPDATE users SET has_damage_hold = TRUE WHERE user_id = :uid");
            $userHoldStmt->execute([':uid' => (int)$incident['borrower_id']]);

            $db->commit();

            return [
                'success'          => true,
                'incident_id'      => $incidentId,
                'borrower_id'      => (int)$incident['borrower_id'],
                'item_id'          => (int)$incident['item_id'],
                'status'           => $newStatus,
                'rejection_reason' => trim($rejectionReason),
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Custodian updates or sets the assessed damage fee and recalculates remaining balance
     */
    public static function updateAssessedFee(PDO $db, int $incidentId, float $newCost): bool {
        if ($incidentId <= 0 || $newCost < 0) {
            throw new InvalidArgumentException("Invalid incident ID or fee amount.");
        }

        $stmt = $db->prepare("SELECT incident_id FROM damage_incidents WHERE incident_id = :id AND incident_status != 'cleared'");
        $stmt->execute([':id' => $incidentId]);
        if (!$stmt->fetch()) {
            return false;
        }

        $update = $db->prepare("
            UPDATE damage_incidents
            SET estimated_cost = :cost,
                updated_at     = NOW()
            WHERE incident_id = :id AND incident_status != 'cleared'
        ");
        $res = $update->execute([
            ':cost' => $newCost,
            ':id'   => $incidentId,
        ]);

        if ($res) {
            self::syncIncidentBalances($db, $incidentId);
        }

        return $res;
    }

    /**
     * Get payment history ledger for an incident
     */
    public static function getPayments(PDO $db, int $incidentId): array {
        if ($incidentId <= 0) return [];

        $stmt = $db->prepare("
            SELECT 
                dip.*,
                u.full_name AS verified_by_name
            FROM damage_incident_payments dip
            LEFT JOIN users u ON dip.verified_by = u.user_id
            WHERE dip.incident_id = :id
            ORDER BY dip.payment_id DESC
        ");
        $stmt->execute([':id' => $incidentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Find single incident by ID with joined borrower, asset, and custodian details
     */
    public static function findById(PDO $db, int $incidentId): ?array {
        $stmt = $db->prepare("
            SELECT 
                di.*,
                i.item_name,
                i.barcode,
                i.property_number,
                i.category AS item_category,
                i.location AS item_location,
                u.full_name AS borrower_name,
                u.id_number AS borrower_id_number,
                u.email AS borrower_email,
                u.role AS borrower_role,
                u.department_program AS borrower_department,
                r.full_name AS reported_by_name,
                c.full_name AS cleared_by_name,
                t.transaction_date AS loan_start_date,
                t.actual_return_date
            FROM damage_incidents di
            JOIN items i ON di.item_id = i.item_id
            JOIN users u ON di.borrower_id = u.user_id
            JOIN users r ON di.reported_by = r.user_id
            LEFT JOIN users c ON di.cleared_by = c.user_id
            LEFT JOIN transactions t ON di.transaction_id = t.transaction_id
            WHERE di.incident_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $incidentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Self-healing balance alignment with verified ledger
            $sumStmt = $db->prepare("
                SELECT COALESCE(SUM(amount_paid), 0)
                FROM damage_incident_payments
                WHERE incident_id = :id AND payment_status = 'verified'
            ");
            $sumStmt->execute([':id' => $incidentId]);
            $verifiedSum = (float)$sumStmt->fetchColumn();

            if (abs((float)($row['amount_paid'] ?? 0) - $verifiedSum) > 0.001) {
                $synced = self::syncIncidentBalances($db, $incidentId);
                $row['amount_paid'] = $synced['amount_paid'];
                $row['remaining_balance'] = $synced['remaining_balance'];
            }
        }

        return $row ?: null;
    }

    /**
     * Find active open incident for a specific borrower (if any)
     */
    public static function getActiveHoldByBorrower(PDO $db, int $borrowerId): ?array {
        $stmt = $db->prepare("
            SELECT 
                di.*,
                i.item_name,
                i.barcode,
                i.property_number,
                i.category AS item_category,
                r.full_name AS reported_by_name,
                t.actual_return_date
            FROM damage_incidents di
            JOIN items i ON di.item_id = i.item_id
            JOIN users r ON di.reported_by = r.user_id
            LEFT JOIN transactions t ON di.transaction_id = t.transaction_id
            WHERE di.borrower_id = :uid 
              AND di.incident_status IN ('awaiting_payment', 'pending_verification', 'partially_paid', 'rejected', 'open_hold')
            ORDER BY di.incident_id DESC
            LIMIT 1
        ");
        $stmt->execute([':uid' => $borrowerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get all damage incidents for a borrower (for student's My Clearances portal)
     */
    public static function getByBorrower(PDO $db, int $borrowerId): array {
        $stmt = $db->prepare("
            SELECT 
                di.*,
                i.item_name,
                i.barcode,
                i.property_number,
                i.category AS item_category,
                r.full_name AS reported_by_name,
                c.full_name AS cleared_by_name,
                t.actual_return_date
            FROM damage_incidents di
            JOIN items i ON di.item_id = i.item_id
            JOIN users r ON di.reported_by = r.user_id
            LEFT JOIN users c ON di.cleared_by = c.user_id
            LEFT JOIN transactions t ON di.transaction_id = t.transaction_id
            WHERE di.borrower_id = :uid
            ORDER BY 
                CASE 
                    WHEN di.incident_status = 'pending_verification' THEN 0
                    WHEN di.incident_status = 'rejected' THEN 1
                    WHEN di.incident_status = 'partially_paid' THEN 2
                    WHEN di.incident_status IN ('awaiting_payment', 'open_hold') THEN 3
                    ELSE 4
                END,
                di.incident_id DESC
        ");
        $stmt->execute([':uid' => $borrowerId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            $iid = (int)$r['incident_id'];
            $sumStmt = $db->prepare("
                SELECT COALESCE(SUM(amount_paid), 0)
                FROM damage_incident_payments
                WHERE incident_id = :id AND payment_status = 'verified'
            ");
            $sumStmt->execute([':id' => $iid]);
            $verifiedSum = (float)$sumStmt->fetchColumn();

            if (abs((float)($r['amount_paid'] ?? 0) - $verifiedSum) > 0.001) {
                $synced = self::syncIncidentBalances($db, $iid);
                $r['amount_paid'] = $synced['amount_paid'];
                $r['remaining_balance'] = $synced['remaining_balance'];
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Check if a borrower has any open damage incidents
     */
    public static function hasActiveHold(PDO $db, int $borrowerId): bool {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM damage_incidents 
            WHERE borrower_id = :uid 
              AND incident_status IN ('awaiting_payment', 'pending_verification', 'partially_paid', 'rejected', 'open_hold')
        ");
        $stmt->execute([':uid' => $borrowerId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Count incidents grouped by status (for dashboard tabs and badges)
     */
    public static function countByStatus(PDO $db): array {
        $stmt = $db->query("
            SELECT incident_status, COUNT(*) as cnt
            FROM damage_incidents
            GROUP BY incident_status
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $pendingVerif = (int)($rows[self::STATUS_PENDING_VERIFICATION] ?? 0);
        $awaitingPay  = (int)($rows[self::STATUS_AWAITING_PAYMENT] ?? 0) + (int)($rows[self::STATUS_OPEN_HOLD] ?? 0);
        $partiallyPaid = (int)($rows[self::STATUS_PARTIALLY_PAID] ?? 0);
        $cleared      = (int)($rows[self::STATUS_CLEARED] ?? 0);
        $rejected     = (int)($rows[self::STATUS_REJECTED] ?? 0);
        $total        = $pendingVerif + $awaitingPay + $partiallyPaid + $cleared + $rejected;
        $activeHolds  = $pendingVerif + $awaitingPay + $partiallyPaid + $rejected;

        return [
            'total'                => $total,
            'active_holds'         => $activeHolds,
            'pending_verification' => $pendingVerif,
            'awaiting_payment'     => $awaitingPay,
            'partially_paid'       => $partiallyPaid,
            'cleared'              => $cleared,
            'rejected'             => $rejected,
        ];
    }

    /**
     * List incidents with status filtering, search query, and pagination
     */
    public static function listAll(
        PDO $db,
        ?string $status = null,
        string $search = '',
        int $limit = 50,
        int $offset = 0
    ): array {
        $whereClauses = [];
        $params = [];

        if ($status !== null && $status !== '') {
            if ($status === 'active_holds') {
                $whereClauses[] = "di.incident_status IN ('awaiting_payment', 'pending_verification', 'partially_paid', 'rejected', 'open_hold')";
            } elseif ($status === self::STATUS_AWAITING_PAYMENT) {
                $whereClauses[] = "di.incident_status IN ('awaiting_payment', 'open_hold')";
            } elseif (in_array($status, [self::STATUS_PENDING_VERIFICATION, self::STATUS_PARTIALLY_PAID, self::STATUS_CLEARED, self::STATUS_REJECTED], true)) {
                $whereClauses[] = "di.incident_status = :status";
                $params[':status'] = $status;
            }
        }

        if (trim($search) !== '') {
            $searchTerm = '%' . trim($search) . '%';
            $whereClauses[] = "(
                u.full_name LIKE :s1 OR 
                u.id_number LIKE :s2 OR 
                i.item_name LIKE :s3 OR 
                i.barcode LIKE :s4 OR 
                di.receipt_number LIKE :s5 OR
                di.settlement_reference LIKE :s6
            )";
            $params[':s1'] = $searchTerm;
            $params[':s2'] = $searchTerm;
            $params[':s3'] = $searchTerm;
            $params[':s4'] = $searchTerm;
            $params[':s5'] = $searchTerm;
            $params[':s6'] = $searchTerm;
        }

        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $sql = "
            SELECT 
                di.*,
                i.item_name,
                i.barcode,
                i.property_number,
                i.category AS item_category,
                u.full_name AS borrower_name,
                u.id_number AS borrower_id_number,
                u.role AS borrower_role,
                u.department_program AS borrower_department,
                r.full_name AS reported_by_name,
                c.full_name AS cleared_by_name,
                t.actual_return_date
            FROM damage_incidents di
            JOIN items i ON di.item_id = i.item_id
            JOIN users u ON di.borrower_id = u.user_id
            JOIN users r ON di.reported_by = r.user_id
            LEFT JOIN users c ON di.cleared_by = c.user_id
            LEFT JOIN transactions t ON di.transaction_id = t.transaction_id
            {$whereSql}
            ORDER BY 
                CASE 
                    WHEN di.incident_status = 'pending_verification' THEN 0
                    WHEN di.incident_status = 'rejected' THEN 1
                    WHEN di.incident_status = 'partially_paid' THEN 2
                    WHEN di.incident_status IN ('awaiting_payment', 'open_hold') THEN 3
                    ELSE 4
                END,
                di.incident_id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$r) {
            $iid = (int)$r['incident_id'];
            $sumStmt = $db->prepare("
                SELECT COALESCE(SUM(amount_paid), 0)
                FROM damage_incident_payments
                WHERE incident_id = :id AND payment_status = 'verified'
            ");
            $sumStmt->execute([':id' => $iid]);
            $verifiedSum = (float)$sumStmt->fetchColumn();

            if (abs((float)($r['amount_paid'] ?? 0) - $verifiedSum) > 0.001) {
                $synced = self::syncIncidentBalances($db, $iid);
                $r['amount_paid'] = $synced['amount_paid'];
                $r['remaining_balance'] = $synced['remaining_balance'];
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Count total incidents matching filter
     */
    public static function countAll(PDO $db, ?string $status = null, string $search = ''): int {
        $whereClauses = [];
        $params = [];

        if ($status !== null && $status !== '') {
            if ($status === 'active_holds') {
                $whereClauses[] = "di.incident_status IN ('awaiting_payment', 'pending_verification', 'partially_paid', 'rejected', 'open_hold')";
            } elseif ($status === self::STATUS_AWAITING_PAYMENT) {
                $whereClauses[] = "di.incident_status IN ('awaiting_payment', 'open_hold')";
            } elseif (in_array($status, [self::STATUS_PENDING_VERIFICATION, self::STATUS_PARTIALLY_PAID, self::STATUS_CLEARED, self::STATUS_REJECTED], true)) {
                $whereClauses[] = "di.incident_status = :status";
                $params[':status'] = $status;
            }
        }

        if (trim($search) !== '') {
            $searchTerm = '%' . trim($search) . '%';
            $whereClauses[] = "(
                u.full_name LIKE :s1 OR 
                u.id_number LIKE :s2 OR 
                i.item_name LIKE :s3 OR 
                i.barcode LIKE :s4 OR 
                di.receipt_number LIKE :s5 OR
                di.settlement_reference LIKE :s6
            )";
            $params[':s1'] = $searchTerm;
            $params[':s2'] = $searchTerm;
            $params[':s3'] = $searchTerm;
            $params[':s4'] = $searchTerm;
            $params[':s5'] = $searchTerm;
            $params[':s6'] = $searchTerm;
        }

        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $sql = "
            SELECT COUNT(*)
            FROM damage_incidents di
            JOIN items i ON di.item_id = i.item_id
            JOIN users u ON di.borrower_id = u.user_id
            {$whereSql}
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }
}
