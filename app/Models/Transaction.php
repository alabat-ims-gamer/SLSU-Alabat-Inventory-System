<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use RuntimeException;
use InvalidArgumentException;

/**
 * Transaction Model — Borrowing, Returning, Maintenance & Custody Audit Engine
 * Follows OWASP secure coding, strict parameterization, and transaction atomicity.
 */
class Transaction {

    /**
     * Create an approved equipment loan transaction.
     * Shifts item status to 'borrowed', clears reservation fields, and creates transaction row.
     */
    public static function createLoan(
        PDO $db, 
        int $itemId, 
        int $borrowerId, 
        string $expectedReturnDate, 
        ?string $purpose, 
        int $custodianId
    ): int {
        $db->beginTransaction();
        try {
            // Lock item row
            $itemStmt = $db->prepare("
                SELECT status FROM items WHERE item_id = :item_id FOR UPDATE
            ");
            $itemStmt->execute([':item_id' => $itemId]);
            $currentStatus = $itemStmt->fetchColumn();

            if (!$currentStatus) {
                throw new InvalidArgumentException("Asset not found.");
            }

            if (!in_array($currentStatus, ['available', 'requested'], true)) {
                throw new RuntimeException("Asset is not available for borrowing (Current status: {$currentStatus}).");
            }

            // Insert transaction record
            $txStmt = $db->prepare("
                INSERT INTO transactions (
                    item_id, user_id, transaction_type, transaction_date, 
                    expected_return_date, remarks, processed_by
                ) VALUES (
                    :item_id, :user_id, 'borrow', NOW(), 
                    :expected_return, :remarks, :processed_by
                )
            ");
            $txStmt->execute([
                ':item_id'         => $itemId,
                ':user_id'         => $borrowerId,
                ':expected_return' => $expectedReturnDate,
                ':remarks'         => $purpose ? trim($purpose) : null,
                ':processed_by'    => $custodianId,
            ]);
            $txId = (int)$db->lastInsertId();

            // Update item: status -> 'borrowed', clear reservation
            $updateStmt = $db->prepare("
                UPDATE items 
                SET status = 'borrowed',
                    requested_by = NULL,
                    reservation_expires_at = NULL,
                    request_purpose = NULL,
                    updated_at = NOW()
                WHERE item_id = :item_id
            ");
            $updateStmt->execute([':item_id' => $itemId]);

            $db->commit();
            return $txId;
        } catch (PDOException $e) {
            $db->rollBack();
            throw new RuntimeException("Database error creating loan: " . $e->getMessage(), (int)$e->getCode(), $e);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Process equipment return inspection and check-in.
     * Shifts item status to 'available' or 'maintenance' (if damaged).
     * If damaged, flags borrower's account with has_damage_hold = TRUE.
     */
    public static function processReturn(
        PDO $db,
        int $itemId,
        string $condition,
        ?string $remarks,
        int $custodianId,
        ?float $estimatedCost = null
    ): bool {
        $db->beginTransaction();
        try {
            // Find active loan transaction for this item
            $txStmt = $db->prepare("
                SELECT transaction_id, user_id 
                FROM transactions 
                WHERE item_id = :item_id 
                  AND transaction_type = 'borrow' 
                  AND actual_return_date IS NULL 
                ORDER BY transaction_id DESC 
                LIMIT 1 
                FOR UPDATE
            ");
            $txStmt->execute([':item_id' => $itemId]);
            $activeTx = $txStmt->fetch(PDO::FETCH_ASSOC);

            if (!$activeTx) {
                throw new RuntimeException("No active borrow transaction found for this asset.");
            }

            $txId = (int)$activeTx['transaction_id'];
            $borrowerId = (int)$activeTx['user_id'];

            // Close the transaction
            $remarksParam = ($remarks !== null && trim($remarks) !== '') ? trim($remarks) : null;
            if ($remarksParam !== null) {
                $closeTx = $db->prepare("
                    UPDATE transactions 
                    SET actual_return_date = NOW(),
                        returned_condition = :condition,
                        remarks = CASE 
                            WHEN remarks IS NULL OR remarks = '' THEN :remarks_new
                            ELSE CONCAT(remarks, ' | Return Remarks: ', :remarks_append)
                        END
                    WHERE transaction_id = :tx_id
                ");
                $closeTx->execute([
                    ':condition'      => $condition,
                    ':remarks_new'    => $remarksParam,
                    ':remarks_append' => $remarksParam,
                    ':tx_id'          => $txId,
                ]);
            } else {
                $closeTx = $db->prepare("
                    UPDATE transactions 
                    SET actual_return_date = NOW(),
                        returned_condition = :condition
                    WHERE transaction_id = :tx_id
                ");
                $closeTx->execute([
                    ':condition' => $condition,
                    ':tx_id'     => $txId,
                ]);
            }

            // Determine item's new status based on condition
            $isDamaged = in_array($condition, ['damaged', 'not_working'], true);
            $newStatus = $isDamaged ? 'maintenance' : 'available';

            $itemUpdate = $db->prepare("
                UPDATE items 
                SET status = :status,
                    item_condition = :condition,
                    requested_by = NULL,
                    reservation_expires_at = NULL,
                    request_purpose = NULL,
                    updated_at = NOW()
                WHERE item_id = :item_id
            ");
            $itemUpdate->execute([
                ':status'    => $newStatus,
                ':condition' => $condition,
                ':item_id'   => $itemId,
            ]);

            // If returned damaged, flag the borrower's account with a damage clearance hold and create an incident ticket
            if ($isDamaged) {
                $userUpdate = $db->prepare("
                    UPDATE users 
                    SET has_damage_hold = TRUE,
                        updated_at = NOW() 
                    WHERE user_id = :user_id
                ");
                $userUpdate->execute([':user_id' => $borrowerId]);

                $damageDesc = $remarksParam ?? 'Item returned in ' . ($condition === 'damaged' ? 'damaged' : 'defective / unserviceable') . ' condition during custodian inspection.';
                $costVal = ($estimatedCost !== null && $estimatedCost >= 0) ? $estimatedCost : null;

                $incidentInsert = $db->prepare("
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
                $incidentInsert->execute([
                    ':transaction_id'     => $txId,
                    ':item_id'            => $itemId,
                    ':borrower_id'        => $borrowerId,
                    ':reported_by'        => $custodianId,
                    ':damage_condition'   => $condition,
                    ':damage_description' => $damageDesc,
                    ':estimated_cost'     => $costVal,
                    ':remaining_balance'  => $costVal,
                ]);
            }

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            throw new RuntimeException("Database error processing return: " . $e->getMessage(), (int)$e->getCode(), $e);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Get active loan transaction for a specific item, if any.
     */
    public static function getActiveLoanByItem(PDO $db, int $itemId): ?array {
        $stmt = $db->prepare("
            SELECT t.*, u.full_name AS borrower_name, u.id_number AS borrower_id_number,
                   u.department_program AS borrower_department, u.role AS borrower_role,
                   u.email AS borrower_email, u.phone_number AS borrower_phone,
                   p.full_name AS processed_by_name
            FROM transactions t
            JOIN users u ON t.user_id = u.user_id
            LEFT JOIN users p ON t.processed_by = p.user_id
            WHERE t.item_id = :item_id 
              AND t.transaction_type = 'borrow' 
              AND t.actual_return_date IS NULL
            ORDER BY t.transaction_id DESC
            LIMIT 1
        ");
        $stmt->execute([':item_id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get active loan records for a specific borrower (Student or Faculty).
     */
    public static function getActiveLoansByUser(PDO $db, int $userId): array {
        $stmt = $db->prepare("
            SELECT t.transaction_id, t.transaction_date, t.expected_return_date, t.remarks,
                   i.item_id, i.item_name, i.barcode, i.property_number, i.category, i.location,
                   i.item_condition, i.status
            FROM transactions t
            JOIN items i ON t.item_id = i.item_id
            WHERE t.user_id = :user_id 
              AND t.transaction_type = 'borrow' 
              AND t.actual_return_date IS NULL
            ORDER BY t.expected_return_date ASC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get recent loan history for a user (both active and returned).
     */
    public static function getUserLoanHistory(PDO $db, int $userId, int $limit = 20): array {
        $stmt = $db->prepare("
            SELECT t.transaction_id, t.transaction_date, t.expected_return_date, 
                   t.actual_return_date, t.returned_condition, t.remarks,
                   i.item_name, i.barcode, i.category, i.property_number
            FROM transactions t
            JOIN items i ON t.item_id = i.item_id
            WHERE t.user_id = :user_id 
              AND t.transaction_type = 'borrow'
            ORDER BY t.transaction_date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get borrow history for an asset.
     * If $isCustodian is false, sensitive details (id_number, phone) are redacted for student privacy.
     */
    public static function getItemBorrowHistory(PDO $db, int $itemId, bool $isCustodian = false, int $limit = 10): array {
        $stmt = $db->prepare("
            SELECT t.transaction_id, t.transaction_date, t.expected_return_date, 
                   t.actual_return_date, t.returned_condition, t.remarks,
                   u.full_name, u.department_program, u.id_number, u.phone_number
            FROM transactions t
            JOIN users u ON t.user_id = u.user_id
            WHERE t.item_id = :item_id 
              AND t.transaction_type = 'borrow'
            ORDER BY t.transaction_date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (!$isCustodian) {
            // Redact student ID and phone number for student/faculty viewers per Data Privacy Act
            foreach ($rows as &$row) {
                unset($row['id_number'], $row['phone_number']);
            }
            unset($row);
        }

        return $rows;
    }

    /**
     * Count how many active loans a user currently holds.
     */
    public static function countActiveLoans(PDO $db, int $userId): int {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM transactions 
            WHERE user_id = :user_id 
              AND transaction_type = 'borrow' 
              AND actual_return_date IS NULL
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if a user has any overdue loans that are past their expected return date.
     */
    public static function hasOverdueLoans(PDO $db, int $userId): bool {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM transactions 
            WHERE user_id = :user_id 
              AND transaction_type = 'borrow' 
              AND actual_return_date IS NULL 
              AND expected_return_date < NOW()
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get recent transactions across the entire campus for Custodian and Admin dashboards.
     */
    public static function getRecentTransactions(PDO $db, int $limit = 10): array {
        $stmt = $db->prepare("
            SELECT t.transaction_id, t.transaction_type, t.transaction_date, 
                   t.expected_return_date, t.actual_return_date, t.returned_condition, t.remarks,
                   i.item_id, i.item_name, i.barcode, i.property_number,
                   u.user_id, u.full_name AS borrower_name, u.role AS borrower_role, u.department_program,
                   p.full_name AS processed_by_name
            FROM transactions t
            JOIN items i ON t.item_id = i.item_id
            JOIN users u ON t.user_id = u.user_id
            LEFT JOIN users p ON t.processed_by = p.user_id
            ORDER BY t.transaction_date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Count campus-wide overdue borrow loans that are past expected return date.
     */
    public static function getOverdueCount(PDO $db): int {
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM transactions 
            WHERE transaction_type = 'borrow' 
              AND actual_return_date IS NULL 
              AND expected_return_date < NOW()
        ");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get distinct count of users who currently have at least one active borrowed item.
     */
    public static function getActiveBorrowersCount(PDO $db): int {
        $stmt = $db->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM transactions 
            WHERE transaction_type = 'borrow' 
              AND actual_return_date IS NULL
        ");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count items returned by a specific user in the current calendar month.
     */
    public static function getUserReturnedThisMonth(PDO $db, int $userId): int {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM transactions 
            WHERE user_id = :user_id 
              AND transaction_type = 'borrow' 
              AND actual_return_date IS NOT NULL 
              AND MONTH(actual_return_date) = MONTH(NOW()) 
              AND YEAR(actual_return_date) = YEAR(NOW())
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count total loans checked out by a user in the current calendar year (YTD).
     */
    public static function getUserTotalLoansYtd(PDO $db, int $userId): int {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM transactions 
            WHERE user_id = :user_id 
              AND transaction_type = 'borrow' 
              AND YEAR(transaction_date) = YEAR(NOW())
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Aggregate monthly loan and return transaction counts for the last $months months.
     */
    public static function getMonthlyBorrowingStats(PDO $db, int $months = 6): array {
        $labels = [];
        $loans = [];
        $returns = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthTime = strtotime("-{$i} months");
            $monthLabel = date('M', $monthTime);
            $yearMonth = date('Y-m', $monthTime);
            $labels[] = $monthLabel;

            // Count loans checked out in this month
            $loanStmt = $db->prepare("
                SELECT COUNT(*) FROM transactions 
                WHERE transaction_type = 'borrow' 
                  AND DATE_FORMAT(transaction_date, '%Y-%m') = :ym
            ");
            $loanStmt->execute([':ym' => $yearMonth]);
            $loans[] = (int)$loanStmt->fetchColumn();

            // Count items returned in this month
            $returnStmt = $db->prepare("
                SELECT COUNT(*) FROM transactions 
                WHERE transaction_type = 'borrow' 
                  AND actual_return_date IS NOT NULL 
                  AND DATE_FORMAT(actual_return_date, '%Y-%m') = :ym
            ");
            $returnStmt->execute([':ym' => $yearMonth]);
            $returns[] = (int)$returnStmt->fetchColumn();
        }

        return [
            'labels'  => $labels,
            'loans'   => $loans,
            'returns' => $returns,
        ];
    }

    /**
     * Get all active equipment loans across campus with detailed borrower & asset metadata.
     * Computes server-side overdue status, days elapsed, and remaining duration.
     *
     * @param PDO $db
     * @return array
     */
    public static function getAllActiveLoans(PDO $db): array {
        $stmt = $db->query("
            SELECT t.transaction_id, t.transaction_type, t.transaction_date, 
                   t.expected_return_date, t.actual_return_date, t.remarks,
                   i.item_id, i.item_name, i.barcode, i.property_number, i.category, i.location, i.item_condition,
                   u.user_id AS borrower_id, u.full_name AS borrower_name, u.id_number AS borrower_id_number,
                   u.department_program AS borrower_department, u.role AS borrower_role,
                   u.email AS borrower_email, u.phone_number AS borrower_phone, u.has_damage_hold,
                   p.full_name AS processed_by_name
            FROM transactions t
            JOIN items i ON t.item_id = i.item_id
            JOIN users u ON t.user_id = u.user_id
            LEFT JOIN users p ON t.processed_by = p.user_id
            WHERE t.transaction_type = 'borrow' 
              AND t.actual_return_date IS NULL
            ORDER BY t.expected_return_date ASC, t.transaction_date DESC
        ");
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $now = time();
        $todayEnd = strtotime('today 23:59:59');

        foreach ($loans as &$loan) {
            $expectedTime = !empty($loan['expected_return_date']) ? strtotime($loan['expected_return_date']) : null;
            if ($expectedTime !== null) {
                $isOverdue = $expectedTime < $now;
                $diffSec = abs($expectedTime - $now);
                $diffDays = (int)floor($diffSec / 86400);
                $diffHours = (int)floor(($diffSec % 86400) / 3600);

                $loan['is_overdue'] = $isOverdue;
                $loan['is_due_today'] = (!$isOverdue && $expectedTime <= $todayEnd);
                
                if ($isOverdue) {
                    $loan['due_status_label'] = $diffDays > 0 ? "Overdue by {$diffDays}d {$diffHours}h" : "Overdue by {$diffHours}h";
                    $loan['filter_category'] = 'overdue';
                } elseif ($loan['is_due_today']) {
                    $loan['due_status_label'] = "Due today (" . date('h:i A', $expectedTime) . ")";
                    $loan['filter_category'] = 'due_today';
                } else {
                    $loan['due_status_label'] = "Due in " . ($diffDays > 0 ? "{$diffDays}d " : "") . "{$diffHours}h";
                    $loan['filter_category'] = 'upcoming';
                }
            } else {
                $loan['is_overdue'] = false;
                $loan['is_due_today'] = false;
                $loan['due_status_label'] = 'No due date set';
                $loan['filter_category'] = 'upcoming';
            }
        }
        unset($loan);

        return $loans;
    }

    /**
     * Get pending equipment reservation requests awaiting counter verification and pickup.
     *
     * @param PDO $db
     * @return array
     */
    public static function getPendingReservations(PDO $db): array {
        $stmt = $db->query("
            SELECT i.item_id, i.item_name, i.barcode, i.property_number, i.category, i.location, 
                   i.status, i.requested_by, i.reservation_expires_at, i.request_purpose,
                   u.user_id AS requester_id, u.full_name AS requester_name, u.id_number AS requester_id_number,
                   u.department_program AS requester_department, u.role AS requester_role,
                   u.email AS requester_email, u.phone_number AS requester_phone
            FROM items i
            JOIN users u ON i.requested_by = u.user_id
            WHERE i.status = 'requested'
            ORDER BY i.reservation_expires_at ASC
        ");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $now = time();
        foreach ($requests as &$req) {
            $expiryTime = !empty($req['reservation_expires_at']) ? strtotime($req['reservation_expires_at']) : null;
            if ($expiryTime !== null) {
                $isExpired = $expiryTime < $now;
                $diffSec = abs($expiryTime - $now);
                $diffMinutes = (int)floor($diffSec / 60);

                $req['is_expired'] = $isExpired;
                $req['time_remaining_label'] = $isExpired ? 'Hold Expired' : "{$diffMinutes} min left";
            } else {
                $req['is_expired'] = false;
                $req['time_remaining_label'] = 'Hold Active';
            }
        }
        unset($req);

        return $requests;
    }

    /**
     * Get completed loan history (returned / closed transactions) for audit and logging.
     *
     * @param PDO $db
     * @param int $limit
     * @return array
     */
    public static function getCompletedLoanHistory(PDO $db, int $limit = 50): array {
        $sql = "
            SELECT t.transaction_id, t.transaction_type, t.transaction_date, 
                   t.expected_return_date, t.actual_return_date, t.returned_condition, t.remarks,
                   i.item_id, i.item_name, i.barcode, i.property_number, i.category, i.location,
                   u.user_id AS borrower_id, u.full_name AS borrower_name, u.id_number AS borrower_id_number,
                   u.department_program AS borrower_department, u.role AS borrower_role,
                   p.full_name AS processed_by_name
            FROM transactions t
            JOIN items i ON t.item_id = i.item_id
            JOIN users u ON t.user_id = u.user_id
            LEFT JOIN users p ON t.processed_by = p.user_id
            WHERE t.transaction_type = 'borrow' 
              AND t.actual_return_date IS NOT NULL
            ORDER BY t.actual_return_date DESC
        ";
        if ($limit > 0) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $db->prepare($sql);
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get aggregate borrowing metrics and counts for a specific user.
     *
     * @param PDO $db
     * @param int $userId
     * @return array
     */
    public static function getUserRecordStats(PDO $db, int $userId): array {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) AS total_borrowed,
                SUM(CASE WHEN actual_return_date IS NULL THEN 1 ELSE 0 END) AS active_loans,
                SUM(CASE WHEN actual_return_date IS NOT NULL THEN 1 ELSE 0 END) AS returned_count,
                SUM(CASE WHEN actual_return_date IS NULL AND expected_return_date < NOW() THEN 1 ELSE 0 END) AS overdue_count
            FROM transactions
            WHERE user_id = :user_id 
              AND transaction_type = 'borrow'
        ");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_borrowed' => (int)($row['total_borrowed'] ?? 0),
            'active_loans'   => (int)($row['active_loans'] ?? 0),
            'returned_count' => (int)($row['returned_count'] ?? 0),
            'overdue_count'  => (int)($row['overdue_count'] ?? 0),
        ];
    }

    /**
     * Get paginated and filtered loan records for a specific borrower.
     * Enforces strict multi-tenant isolation (user_id).
     *
     * @param PDO $db
     * @param int $userId
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getUserRecordsPaged(
        PDO $db, 
        int $userId, 
        array $filters = [], 
        int $page = 1, 
        int $perPage = 10
    ): array {
        $conditions = ["t.user_id = :user_id", "t.transaction_type = 'borrow'"];
        $params = [':user_id' => $userId];

        // 1. Status Filter
        $status = strtolower(trim($filters['status'] ?? 'all'));
        if ($status === 'active') {
            $conditions[] = "t.actual_return_date IS NULL";
        } elseif ($status === 'returned') {
            $conditions[] = "t.actual_return_date IS NOT NULL";
        } elseif ($status === 'overdue') {
            $conditions[] = "t.actual_return_date IS NULL AND t.expected_return_date < NOW()";
        }

        // 2. Search Filter (Item Name, Barcode, Property Number, Category)
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $conditions[] = "(i.item_name LIKE :search_name OR i.barcode LIKE :search_barcode OR i.property_number LIKE :search_prop OR i.category LIKE :search_cat)";
            $searchWildcard = '%' . $search . '%';
            $params[':search_name']    = $searchWildcard;
            $params[':search_barcode'] = $searchWildcard;
            $params[':search_prop']    = $searchWildcard;
            $params[':search_cat']     = $searchWildcard;
        }

        // 3. Period Filter
        $period = strtolower(trim($filters['period'] ?? 'all'));
        if ($period === '30days') {
            $conditions[] = "t.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        } elseif ($period === 'semester') {
            $conditions[] = "t.transaction_date >= DATE_SUB(NOW(), INTERVAL 120 DAY)";
        } elseif ($period === 'year') {
            $conditions[] = "t.transaction_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        }

        $whereClause = implode(' AND ', $conditions);

        // Count Total
        $countSql = "
            SELECT COUNT(*) 
            FROM transactions t
            JOIN items i ON t.item_id = i.item_id
            WHERE {$whereClause}
        ";
        $countStmt = $db->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch Items
        $dataSql = "
            SELECT t.transaction_id, t.transaction_type, t.transaction_date, 
                   t.expected_return_date, t.actual_return_date, t.returned_condition, t.remarks,
                   i.item_id, i.item_name, i.barcode, i.property_number, i.category, i.location,
                   u_proc.full_name AS processed_by_name
            FROM transactions t
            JOIN items i ON t.item_id = i.item_id
            LEFT JOIN users u_proc ON t.processed_by = u_proc.user_id
            WHERE {$whereClause}
            ORDER BY t.transaction_date DESC
            LIMIT :limit OFFSET :offset
        ";
        $dataStmt = $db->prepare($dataSql);
        foreach ($params as $key => $val) {
            $dataStmt->bindValue($key, $val);
        }
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $items = $dataStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'items'       => $items,
            'total'       => $total,
            'currentPage' => $page,
            'pageSize'    => $perPage,
            'totalPages'  => $totalPages,
        ];
    }
}



