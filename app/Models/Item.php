<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use Exception;
use RuntimeException;
use InvalidArgumentException;

/**
 * Item Model — Enterprise Physical Asset Entity & Barcode Engine
 * Follows OWASP Secure Coding Standards, Strict Type Safety, and PDO Parameterization.
 */
class Item {

    /**
     * Allowed conditions for physical asset health
     */
    public const VALID_CONDITIONS = [
        'mint',
        'good',
        'fair',
        'damaged',
        'not_working',
        'for_disposal'
    ];

    /**
     * Allowed operational borrowing states
     */
    public const VALID_STATUSES = [
        'available',
        'requested',
        'borrowed',
        'maintenance',
        'for_disposal',
        'disposed',
        'lost'
    ];

    /**
     * Standard institutional asset categories
     */
    public const STANDARD_CATEGORIES = [
        'IT Equipment',
        'Laboratory Tools',
        'Aquaculture Equipment',
        'Instructional Materials',
        'Office Equipment',
        'Furniture & Fixtures',
        'Audio/Visual Equipment',
        'Other / General'
    ];

    /**
     * Get the standardized canonical item categories.
     * @return string[]
     */
    public static function getStandardCategories(): array {
        return self::STANDARD_CATEGORIES;
    }

    /**
     * Map physical condition to initial/synchronized operational status.
     */
    public static function mapConditionToStatus(string $condition): string {
        return match ($condition) {
            'mint', 'good', 'fair'   => 'available',
            'damaged', 'not_working' => 'maintenance',
            'for_disposal'           => 'for_disposal',
            default                  => 'available',
        };
    }

    /**
     * Generate a unique, standardized Code 128 barcode (e.g. BC-100293).
     * Incorporates atomic database sequence inspection.
     */
    public static function generateBarcode(PDO $db): string {
        // Query the highest existing numeric suffix or calculate safe next seed
        $stmt = $db->query("
            SELECT barcode FROM items 
            WHERE barcode LIKE 'BC-%' 
            ORDER BY item_id DESC 
            LIMIT 1
        ");
        $lastBarcode = $stmt->fetchColumn();

        $nextNum = 100001;
        if ($lastBarcode && preg_match('/^BC-(\d+)$/', (string)$lastBarcode, $matches)) {
            $nextNum = (int)$matches[1] + 1;
        }

        return sprintf('BC-%06d', $nextNum);
    }

    /**
     * Check if a property number already exists in the database.
     * Returns the existing item record if found, or null if available.
     */
    public static function findByPropertyNumber(PDO $db, string $propertyNumber): ?array {
        $trimmed = trim($propertyNumber);
        if ($trimmed === '') {
            return null;
        }

        $stmt = $db->prepare("
            SELECT i.*, u.full_name AS registered_by_name 
            FROM items i
            LEFT JOIN users u ON i.registered_by = u.user_id
            WHERE i.property_number = :prop_num
            LIMIT 1
        ");
        $stmt->bindValue(':prop_num', $trimmed, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Lazy on-access evaluation for 30-minute reservation hold.
     * If an item is in 'requested' status and reservation_expires_at has passed,
     * automatically prune and reset the item to 'available'.
     */
    public static function checkAndPruneLazyExpiry(PDO $db, array &$item): void {
        $isDbExpired = !empty($item['is_reservation_expired']);
        $isPhpExpired = false;
        if (($item['status'] ?? '') === 'requested' && !empty($item['reservation_expires_at'])) {
            $expiresTime = strtotime($item['reservation_expires_at']);
            if ($expiresTime !== false && $expiresTime <= time()) {
                $isPhpExpired = true;
            }
        }

        if ($isDbExpired || $isPhpExpired) {
            $resetStmt = $db->prepare("
                UPDATE items 
                SET status = 'available',
                    requested_by = NULL,
                    reservation_expires_at = NULL,
                    request_purpose = NULL,
                    updated_at = NOW()
                WHERE item_id = :item_id AND status = 'requested'
            ");
            $resetStmt->execute([':item_id' => $item['item_id']]);

            $item['status'] = 'available';
            $item['requested_by'] = null;
            $item['requested_by_name'] = null;
            $item['requested_by_id_number'] = null;
            $item['requested_by_department'] = null;
            $item['requested_by_role'] = null;
            $item['reservation_expires_at'] = null;
            $item['request_purpose'] = null;
            $item['is_reservation_expired'] = 0;
        }
    }

    /**
     * Find item by unique barcode with lazy expiration evaluation.
     */
    public static function findByBarcode(PDO $db, string $barcode): ?array {
        $stmt = $db->prepare("
            SELECT i.*, 
                   u.full_name AS registered_by_name,
                   req.full_name AS requested_by_name,
                   req.id_number AS requested_by_id_number,
                   req.department_program AS requested_by_department,
                   req.role AS requested_by_role,
                   (CASE WHEN i.status = 'requested' AND i.reservation_expires_at IS NOT NULL AND i.reservation_expires_at <= NOW() THEN 1 ELSE 0 END) AS is_reservation_expired
            FROM items i
            LEFT JOIN users u ON i.registered_by = u.user_id
            LEFT JOIN users req ON i.requested_by = req.user_id
            WHERE i.barcode = :barcode
            LIMIT 1
        ");
        $stmt->bindValue(':barcode', trim($barcode), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            self::checkAndPruneLazyExpiry($db, $row);
            return $row;
        }

        return null;
    }

    /**
     * Find item by unique barcode OR property number with lazy expiration evaluation.
     */
    public static function findByBarcodeOrPropertyNumber(PDO $db, string $code): ?array {
        $cleanCode = trim($code);
        if ($cleanCode === '') {
            return null;
        }

        $stmt = $db->prepare("
            SELECT i.*, 
                   u.full_name AS registered_by_name,
                   req.full_name AS requested_by_name,
                   req.id_number AS requested_by_id_number,
                   req.department_program AS requested_by_department,
                   req.role AS requested_by_role,
                   (CASE WHEN i.status = 'requested' AND i.reservation_expires_at IS NOT NULL AND i.reservation_expires_at <= NOW() THEN 1 ELSE 0 END) AS is_reservation_expired
            FROM items i
            LEFT JOIN users u ON i.registered_by = u.user_id
            LEFT JOIN users req ON i.requested_by = req.user_id
            WHERE i.barcode = :code1 OR (i.property_number IS NOT NULL AND i.property_number = :code2)
            LIMIT 1
        ");
        $stmt->bindValue(':code1', $cleanCode, PDO::PARAM_STR);
        $stmt->bindValue(':code2', $cleanCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            self::checkAndPruneLazyExpiry($db, $row);
            return $row;
        }

        return null;
    }

    /**
     * Find item by primary key ID with lazy expiration evaluation.
     */
    public static function findById(PDO $db, int $itemId): ?array {
        $stmt = $db->prepare("
            SELECT i.*, 
                   u.full_name AS registered_by_name,
                   req.full_name AS requested_by_name,
                   req.id_number AS requested_by_id_number,
                   req.department_program AS requested_by_department,
                   req.role AS requested_by_role,
                   (CASE WHEN i.status = 'requested' AND i.reservation_expires_at IS NOT NULL AND i.reservation_expires_at <= NOW() THEN 1 ELSE 0 END) AS is_reservation_expired
            FROM items i
            LEFT JOIN users u ON i.registered_by = u.user_id
            LEFT JOIN users req ON i.requested_by = req.user_id
            WHERE i.item_id = :id
            LIMIT 1
        ");
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            self::checkAndPruneLazyExpiry($db, $row);
            return $row;
        }

        return null;
    }

    /**
     * Submit a borrow request placing a 30-minute timed reservation hold on the asset.
     */
    public static function submitBorrowRequest(
        PDO $db, 
        int $itemId, 
        int $userId, 
        string $purpose, 
        int $holdMinutes = 30
    ): bool {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                SELECT item_id, status, is_student_borrowable, reservation_expires_at,
                       (CASE WHEN status = 'requested' AND reservation_expires_at IS NOT NULL AND reservation_expires_at <= NOW() THEN 1 ELSE 0 END) AS is_reservation_expired
                FROM items 
                WHERE item_id = :item_id 
                FOR UPDATE
            ");
            $stmt->execute([':item_id' => $itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new InvalidArgumentException("Asset not found.");
            }

            // If requested but expired, treat as available
            if ($item['status'] === 'requested') {
                $expiresTime = !empty($item['reservation_expires_at']) ? strtotime($item['reservation_expires_at']) : 0;
                if (!empty($item['is_reservation_expired']) || ($expiresTime > 0 && $expiresTime <= time())) {
                    $item['status'] = 'available';
                }
            }

            if ($item['status'] !== 'available') {
                throw new RuntimeException("Asset is not currently available for borrowing (Status: {$item['status']}).");
            }

            $update = $db->prepare("
                UPDATE items 
                SET status = 'requested',
                    requested_by = :user_id,
                    reservation_expires_at = DATE_ADD(NOW(), INTERVAL :hold_minutes MINUTE),
                    request_purpose = :purpose,
                    updated_at = NOW()
                WHERE item_id = :item_id
            ");
            $update->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $update->bindValue(':hold_minutes', $holdMinutes, PDO::PARAM_INT);
            $update->bindValue(':purpose', trim($purpose), PDO::PARAM_STR);
            $update->bindValue(':item_id', $itemId, PDO::PARAM_INT);
            $update->execute();

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Cancel own pending borrow request.
     */
    public static function cancelBorrowRequest(PDO $db, int $itemId, int $userId): bool {
        $stmt = $db->prepare("
            UPDATE items 
            SET status = 'available',
                requested_by = NULL,
                reservation_expires_at = NULL,
                request_purpose = NULL,
                updated_at = NOW()
            WHERE item_id = :item_id 
              AND requested_by = :user_id 
              AND status = 'requested'
        ");
        $stmt->execute([
            ':item_id' => $itemId,
            ':user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Custodian 1-click denial of a pending borrow request.
     */
    public static function denyBorrowRequest(PDO $db, int $itemId): bool {
        $stmt = $db->prepare("
            UPDATE items 
            SET status = 'available',
                requested_by = NULL,
                reservation_expires_at = NULL,
                request_purpose = NULL,
                updated_at = NOW()
            WHERE item_id = :item_id 
              AND status = 'requested'
        ");
        $stmt->execute([':item_id' => $itemId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Count pending borrow requests submitted by a specific user.
     */
    public static function countPendingRequestsByUser(PDO $db, int $userId): int {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM items 
            WHERE requested_by = :user_id 
              AND status = 'requested' 
              AND reservation_expires_at > NOW()
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count total pending borrow requests campus-wide.
     */
    public static function getPendingRequestsCount(PDO $db): int {
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM items 
            WHERE status = 'requested' 
              AND reservation_expires_at > NOW()
        ");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Register single or batch items atomically inside a database transaction.
     * Enforces unique Code 128 barcodes with an automatic 3-attempt collision retry loop.
     *
     * @param PDO $db
     * @param array $data Sanitized item metadata
     * @param int $quantity Multiplier count (1 to 100)
     * @param int $userId Authenticated custodian's user_id
     * @return array Array of newly created item records
     * @throws InvalidArgumentException|RuntimeException
     */
    public static function createBatch(PDO $db, array $data, int $quantity, int $userId): array {
        if ($quantity < 1 || $quantity > 100) {
            throw new InvalidArgumentException("Quantity multiplier must be between 1 and 100.");
        }

        $condition = strtolower(trim($data['item_condition'] ?? 'good'));
        if (in_array($condition, ['not_working', 'for_disposal', 'condemned'], true)) {
            throw new InvalidArgumentException("Newly registered assets cannot be registered with 'not_working' or 'for_disposal' (condemned) condition.");
        }
        if (!in_array($condition, self::VALID_CONDITIONS, true)) {
            $condition = 'good';
        }
        $status = self::mapConditionToStatus($condition);

        $basePropNum = trim((string)($data['property_number'] ?? ''));
        $createdItems = [];

        // Check if single base property number already exists
        if ($basePropNum !== '' && $quantity === 1) {
            $existing = self::findByPropertyNumber($db, $basePropNum);
            if ($existing) {
                $registrant = $existing['registered_by_name'] ?? 'another custodian';
                $regDate = date('M d, Y', strtotime($existing['created_at']));
                throw new RuntimeException(
                    "Duplicate Detected: Property Number '{$basePropNum}' was already registered by {$registrant} on {$regDate}."
                );
            }
        }

        $wasAlreadyInTransaction = $db->inTransaction();
        if (!$wasAlreadyInTransaction) {
            $db->beginTransaction();
        }

        try {
            for ($i = 1; $i <= $quantity; $i++) {
                // Determine suffixed property number if multiplier > 1
                $propNum = null;
                if ($basePropNum !== '') {
                    $propNum = ($quantity > 1) ? "{$basePropNum}-{$i}" : $basePropNum;
                    // Check duplicate for each suffixed unit
                    $existing = self::findByPropertyNumber($db, $propNum);
                    if ($existing) {
                        $registrant = $existing['registered_by_name'] ?? 'another custodian';
                        $regDate = date('M d, Y', strtotime($existing['created_at']));
                        throw new RuntimeException(
                            "Duplicate Detected: Unit Property Number '{$propNum}' already exists (registered by {$registrant} on {$regDate})."
                        );
                    }
                }

                // Barcode generation with collision retry loop (up to 3 attempts)
                $inserted = false;
                $attempts = 0;
                $lastInsertId = 0;
                $finalBarcode = '';

                while (!$inserted && $attempts < 3) {
                    $attempts++;
                    $finalBarcode = self::generateBarcode($db);
                    // If inserting multiple in same batch, bump by offset
                    if ($i > 1 && preg_match('/^BC-(\d+)$/', $finalBarcode, $m)) {
                        $finalBarcode = sprintf('BC-%06d', (int)$m[1] + ($i - 1));
                    }

                    try {
                        $stmt = $db->prepare("
                            INSERT INTO items (
                                property_number,
                                barcode,
                                item_name,
                                description,
                                category,
                                location,
                                is_student_borrowable,
                                registered_by,
                                status,
                                item_condition,
                                created_at
                            ) VALUES (
                                :prop_num,
                                :barcode,
                                :name,
                                :desc,
                                :cat,
                                :loc,
                                :is_student,
                                :registered_by,
                                :status,
                                :condition,
                                NOW()
                            )
                        ");

                        $stmt->bindValue(':prop_num', $propNum, $propNum !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                        $stmt->bindValue(':barcode', $finalBarcode, PDO::PARAM_STR);
                        $stmt->bindValue(':name', trim($data['item_name']), PDO::PARAM_STR);
                        $stmt->bindValue(':desc', trim($data['description'] ?? ''), PDO::PARAM_STR);
                        $stmt->bindValue(':cat', trim($data['category']), PDO::PARAM_STR);
                        $stmt->bindValue(':loc', trim($data['location']), PDO::PARAM_STR);
                        $stmt->bindValue(':is_student', !empty($data['is_student_borrowable']) ? 1 : 0, PDO::PARAM_INT);
                        $stmt->bindValue(':registered_by', $userId, PDO::PARAM_INT);
                        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
                        $stmt->bindValue(':condition', $condition, PDO::PARAM_STR);

                        $stmt->execute();
                        $lastInsertId = (int)$db->lastInsertId();
                        $inserted = true;
                    } catch (PDOException $pe) {
                        // Catch MySQL 1062 Duplicate Entry code and retry
                        if ($pe->getCode() === '23000' || (int)$pe->errorInfo[1] === 1062) {
                            if ($attempts >= 3) {
                                throw new RuntimeException("Barcode collision resolution failed after 3 attempts. Please retry.");
                            }
                            // Sleep 10ms to allow concurrent transaction to settle
                            usleep(10000);
                            continue;
                        }
                        throw $pe;
                    }
                }

                $createdItems[] = [
                    'item_id'         => $lastInsertId,
                    'barcode'         => $finalBarcode,
                    'property_number' => $propNum,
                    'item_name'       => trim($data['item_name']),
                    'category'        => trim($data['category']),
                    'location'        => trim($data['location']),
                    'item_condition'  => $condition,
                    'status'          => $status,
                ];
            }

            if (!$wasAlreadyInTransaction) {
                $db->commit();
            }

            return $createdItems;
        } catch (Exception $e) {
            if (!$wasAlreadyInTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update item metadata and condition with Optimistic Concurrency Control.
     * Enforces immutability on `item_id` and `barcode`.
     *
     * @param PDO $db
     * @param int $itemId
     * @param array $data Sanitized editable metadata
     * @param string|null $originalUpdatedAt Expected timestamp for optimistic locking
     * @param int $userId Authenticated custodian ID
     * @return array ['success' => bool, 'conflict' => bool, 'item' => array|null, 'message' => string]
     */
    public static function updateItem(
        PDO $db,
        int $itemId,
        array $data,
        ?string $originalUpdatedAt,
        int $userId
    ): array {
        $currentItem = self::findById($db, $itemId);
        if (!$currentItem) {
            return [
                'success'  => false,
                'conflict' => false,
                'item'     => null,
                'message'  => 'Asset not found.'
            ];
        }

        // Optimistic Locking Check: Verify if another user updated this record in the interim
        if ($originalUpdatedAt !== null && $originalUpdatedAt !== '') {
            $dbUpdatedAt = (string)($currentItem['updated_at'] ?? $currentItem['created_at']);
            if (strtotime($originalUpdatedAt) !== strtotime($dbUpdatedAt)) {
                return [
                    'success'  => false,
                    'conflict' => true,
                    'item'     => $currentItem,
                    'message'  => 'Conflict detected: This item was updated by another custodian moments ago. The latest details have been reloaded.'
                ];
            }
        }

        // Active Loan Guard: Cannot modify asset while currently borrowed
        if ($currentItem['status'] === 'borrowed') {
            return [
                'success'  => false,
                'conflict' => false,
                'item'     => $currentItem,
                'message'  => "Asset '{$currentItem['barcode']}' is currently borrowed. Modifying asset specifications or physical condition is locked until the item has been returned."
            ];
        }

        $condition = strtolower(trim($data['item_condition'] ?? $currentItem['item_condition']));
        if (!in_array($condition, self::VALID_CONDITIONS, true)) {
            $condition = $currentItem['item_condition'];
        }
        $newStatus = self::mapConditionToStatus($condition);

        // Check property number uniqueness if modified
        $newPropNum = trim((string)($data['property_number'] ?? ''));
        if ($newPropNum !== '' && $newPropNum !== (string)$currentItem['property_number']) {
            $existing = self::findByPropertyNumber($db, $newPropNum);
            if ($existing && (int)$existing['item_id'] !== $itemId) {
                $registrant = $existing['registered_by_name'] ?? 'another custodian';
                return [
                    'success'  => false,
                    'conflict' => false,
                    'item'     => $currentItem,
                    'message'  => "Property Number '{$newPropNum}' is already assigned to another asset (registered by {$registrant})."
                ];
            }
        }

        $propVal = $newPropNum !== '' ? $newPropNum : null;

        // Whitelist Update Query (strictly excludes item_id and barcode)
        $stmt = $db->prepare("
            UPDATE items SET
                item_name             = :name,
                description           = :desc,
                category              = :cat,
                location              = :loc,
                property_number       = :prop_num,
                is_student_borrowable = :is_student,
                item_condition        = :condition,
                status                = :status,
                updated_at            = NOW()
            WHERE item_id = :id
        ");

        $stmt->bindValue(':name', trim($data['item_name']), PDO::PARAM_STR);
        $stmt->bindValue(':desc', trim($data['description'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':cat', trim($data['category']), PDO::PARAM_STR);
        $stmt->bindValue(':loc', trim($data['location']), PDO::PARAM_STR);
        $stmt->bindValue(':prop_num', $propVal, $propVal !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':is_student', !empty($data['is_student_borrowable']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':condition', $condition, PDO::PARAM_STR);
        $stmt->bindValue(':status', $newStatus, PDO::PARAM_STR);
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);

        $stmt->execute();

        // Automatic Property Movement Tracking: Log movement if storage location changed
        $newLocation = trim($data['location']);
        $oldLocation = trim((string)($currentItem['location'] ?? ''));
        if ($newLocation !== '' && $oldLocation !== '' && strcasecmp($newLocation, $oldLocation) !== 0) {
            try {
                PropertyMovement::log(
                    $db,
                    $itemId,
                    $oldLocation,
                    $newLocation,
                    trim((string)($data['transfer_reason'] ?? 'Reallocation / Location Update')),
                    $userId
                );
            } catch (\Throwable $e) {
                // Non-blocking catch to ensure item update succeeds
            }
        }

        $refreshed = self::findById($db, $itemId);

        return [
            'success'  => true,
            'conflict' => false,
            'item'     => $refreshed,
            'message'  => 'Asset details updated successfully.'
        ];
    }

    /**
     * Mark an asset as Lost/Missing under investigation.
     */
    public static function reportLost(PDO $db, int $itemId, array $details, int $userId): bool {
        $item = self::findById($db, $itemId);
        if (!$item) {
            return false;
        }

        $lostData = [
            'item_id'                => $itemId,
            'last_recorded_location' => $item['location'],
            'accountable_person'     => $details['accountable_person'] ?? 'General Custody',
            'date_reported'          => $details['date_reported'] ?? date('Y-m-d'),
            'description'            => $details['description'] ?? 'Item reported lost during audit',
            'investigation_status'   => $details['investigation_status'] ?? 'under_investigation',
            'resolution'             => 'pending',
            'reported_by'            => $userId,
        ];

        LostProperty::report($db, $lostData);

        $stmt = $db->prepare("UPDATE items SET status = 'lost', updated_at = NOW() WHERE item_id = :id");
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();

        AuditLog::log(
            $db,
            $userId,
            'ITEM_REPORT_LOST',
            "Reported asset '{$item['item_name']}' ({$item['barcode']}) as Lost/Missing under investigation."
        );

        return true;
    }

    /**
     * Retrieve paginated and filtered items for DataTables.
     */
    public static function getPaginated(
        PDO $db,
        int $page = 1,
        int $pageSize = 10,
        string $search = '',
        array $filters = []
    ): array {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $where = ["i.status NOT IN ('for_disposal', 'disposed')"];
        $params = [];

        // Search term across multiple fields
        $searchTerm = trim($search);
        if ($searchTerm !== '') {
            $where[] = "(
                i.item_name LIKE :search_name 
                OR i.barcode LIKE :search_bc 
                OR i.property_number LIKE :search_prop 
                OR i.category LIKE :search_cat 
                OR i.location LIKE :search_loc
            )";
            $wildcard = '%' . $searchTerm . '%';
            $params[':search_name'] = $wildcard;
            $params[':search_bc']   = $wildcard;
            $params[':search_prop'] = $wildcard;
            $params[':search_cat']  = $wildcard;
            $params[':search_loc']  = $wildcard;
        }

        // Category filter
        if (!empty($filters['category'])) {
            $where[] = "i.category = :filter_cat";
            $params[':filter_cat'] = trim($filters['category']);
        }

        // Condition filter
        if (!empty($filters['condition']) && in_array($filters['condition'], self::VALID_CONDITIONS, true)) {
            $where[] = "i.item_condition = :filter_cond";
            $params[':filter_cond'] = trim($filters['condition']);
        }

        // Status filter (strictly restricted to active catalog statuses)
        if (!empty($filters['status']) && in_array($filters['status'], ['available', 'borrowed', 'maintenance', 'requested'], true)) {
            $where[] = "i.status = :filter_status";
            $params[':filter_status'] = trim($filters['status']);
        }

        $whereClause = implode(' AND ', $where);

        // Count query
        $countStmt = $db->prepare("SELECT COUNT(*) FROM items i WHERE {$whereClause}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        // Data query
        $dataStmt = $db->prepare("
            SELECT 
                i.*, 
                u.full_name AS registered_by_name
            FROM items i
            LEFT JOIN users u ON i.registered_by = u.user_id
            WHERE {$whereClause}
            ORDER BY i.item_id DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $val) {
            $dataStmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $dataStmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rows'        => $rows,
            'total'       => $total,
            'currentPage' => $page,
            'pageSize'    => $pageSize,
            'totalPages'  => (int)ceil($total / $pageSize) ?: 1,
        ];
    }

    /**
     * Aggregate high-level asset metrics for Custodian KPI Cards.
     */
    public static function getStats(PDO $db): array {
        $stmt = $db->query("
            SELECT 
                COUNT(*) AS total_assets,
                SUM(CASE WHEN item_condition IN ('mint', 'good', 'fair') AND status = 'available' THEN 1 ELSE 0 END) AS healthy_count,
                SUM(CASE WHEN item_condition IN ('damaged', 'not_working') OR status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_count,
                SUM(CASE WHEN item_condition = 'for_disposal' OR status = 'disposed' THEN 1 ELSE 0 END) AS disposal_count,
                SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) AS borrowed_count
            FROM items
        ");
        $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total'       => (int)($counts['total_assets'] ?? 0),
            'healthy'     => (int)($counts['healthy_count'] ?? 0),
            'maintenance' => (int)($counts['maintenance_count'] ?? 0),
            'disposal'    => (int)($counts['disposal_count'] ?? 0),
            'borrowed'    => (int)($counts['borrowed_count'] ?? 0),
        ];
    }

    /**
     * Parse and import CSV rows with formula injection neutralization and atomic transaction.
     *
     * @param PDO $db
     * @param array $file $_FILES['csv_file']
     * @param int $userId
     * @return array ['success' => bool, 'imported_count' => int, 'message' => string]
     * @throws RuntimeException
     */
    public static function importCsv(PDO $db, array $file, int $userId): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException("No valid CSV file was uploaded.");
        }

        // Enforce 5MB limit
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException("File exceeds maximum allowed size of 5MB.");
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new RuntimeException("Failed to open uploaded CSV file.");
        }

        // Read and validate header
        $header = fgetcsv($handle, 2048, ',');
        if (!$header) {
            fclose($handle);
            throw new RuntimeException("The uploaded CSV file is empty.");
        }

        $cleanHeader = array_map(function($h) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', (string)$h)));
        }, $header);

        // Required columns: item_name, category, location
        $nameIdx = array_search('item_name', $cleanHeader, true);
        if ($nameIdx === false) {
            $nameIdx = array_search('name', $cleanHeader, true);
        }
        $catIdx  = array_search('category', $cleanHeader, true);
        $locIdx  = array_search('location', $cleanHeader, true);
        $propIdx = array_search('property_number', $cleanHeader, true);
        $descIdx = array_search('description', $cleanHeader, true);
        $condIdx = array_search('condition', $cleanHeader, true);

        if ($nameIdx === false || $catIdx === false || $locIdx === false) {
            fclose($handle);
            throw new RuntimeException(
                "Invalid CSV structure. Header must contain at least: 'item_name', 'category', 'location'."
            );
        }

        $rowsToInsert = [];
        $lineNum = 1;

        while (($row = fgetcsv($handle, 2048, ',')) !== false) {
            $lineNum++;
            if (empty(array_filter($row))) {
                continue; // Skip empty rows
            }

            $rawName = $row[$nameIdx] ?? '';
            $rawCat  = $row[$catIdx] ?? '';
            $rawLoc  = $row[$locIdx] ?? '';
            $rawProp = ($propIdx !== false && isset($row[$propIdx])) ? $row[$propIdx] : '';
            $rawDesc = ($descIdx !== false && isset($row[$descIdx])) ? $row[$descIdx] : '';
            $rawCond = ($condIdx !== false && isset($row[$condIdx])) ? $row[$condIdx] : 'good';

            // Neutralize formula injection
            $cleanName = self::neutralizeFormula($rawName);
            $cleanCat  = self::neutralizeFormula($rawCat);
            $cleanLoc  = self::neutralizeFormula($rawLoc);
            $cleanProp = self::neutralizeFormula($rawProp);
            $cleanDesc = self::neutralizeFormula($rawDesc);
            $cleanCond = strtolower(trim(self::neutralizeFormula($rawCond)));

            if ($cleanName === '' || $cleanCat === '' || $cleanLoc === '') {
                fclose($handle);
                throw new RuntimeException("Line {$lineNum} has missing required fields (Item Name, Category, or Location).");
            }

            if (!in_array($cleanCond, self::VALID_CONDITIONS, true)) {
                $cleanCond = 'good';
            }

            $rowsToInsert[] = [
                'item_name'             => $cleanName,
                'category'              => $cleanCat,
                'location'              => $cleanLoc,
                'property_number'       => $cleanProp,
                'description'           => $cleanDesc,
                'item_condition'        => $cleanCond,
                'is_student_borrowable' => 1,
            ];
        }
        fclose($handle);

        if (empty($rowsToInsert)) {
            throw new RuntimeException("CSV contains no valid item rows to import.");
        }

        // Execute batch insert in atomic transaction
        $db->beginTransaction();
        $importedCount = 0;

        try {
            foreach ($rowsToInsert as $itemData) {
                self::createBatch($db, $itemData, 1, $userId);
                $importedCount++;
            }
            $db->commit();

            return [
                'success'        => true,
                'imported_count' => $importedCount,
                'message'        => "Successfully imported {$importedCount} assets."
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new RuntimeException("CSV Import Failed: " . $e->getMessage());
        }
    }

    /**
     * Neutralize CSV / Spreadsheet Formula Injection vulnerabilities.
     * Prepends single quote if cell starts with dangerous execution characters (=, +, -, @, \t, \r).
     */
    public static function neutralizeFormula(string $value): string {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        $firstChar = $trimmed[0];
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $trimmed;
        }

        return $trimmed;
    }

    /**
     * Delete or Decommission an Asset with Smart Hybrid Safeguards.
     * 
     * Invariants enforced:
     * 1. Active Loan Lockout: If status === 'borrowed', throws exception.
     * 2. Zero-History Hard Delete: If 0 transaction records, deletes from `items`.
     * 3. Historical Record Soft Disposal: If >= 1 transactions, updates status to 'disposed'.
     *
     * @param PDO $db
     * @param int $itemId
     * @param string $reason
     * @param int $userId Authenticated custodian ID
     * @return array ['success' => bool, 'type' => 'hard_deleted'|'disposed', 'message' => string, 'item' => array]
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function deleteItem(PDO $db, int $itemId, string $reason, int $userId): array {
        $trimmedReason = trim($reason);
        if (mb_strlen($trimmedReason) < 3) {
            throw new InvalidArgumentException("A valid reason for deletion or disposal is required (minimum 3 characters).");
        }

        $item = self::findById($db, $itemId);
        if (!$item) {
            throw new RuntimeException("Asset not found (ID #{$itemId}).");
        }

        // Invariant 1: Active Loan Lockout
        if ($item['status'] === 'borrowed') {
            throw new RuntimeException(
                "Cannot delete or dispose of asset '{$item['barcode']}' ({$item['item_name']}) because it is currently borrowed. The physical item must be returned first."
            );
        }

        // Invariant 2: Check historical loan count
        $txnStmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE item_id = :id");
        $txnStmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $txnStmt->execute();
        $historyCount = (int)$txnStmt->fetchColumn();

        $operationType = 'hard_deleted';
        $message = '';

        if ($historyCount === 0) {
            // Hard delete: 0 transaction records
            $delStmt = $db->prepare("DELETE FROM items WHERE item_id = :id");
            $delStmt->bindValue(':id', $itemId, PDO::PARAM_INT);
            $delStmt->execute();

            $operationType = 'hard_deleted';
            $message = "Asset '{$item['barcode']}' was permanently deleted (zero loan history).";
            
            // Record audit log
            AuditLog::log(
                $db,
                $userId,
                'ITEM_DELETED',
                "Permanently deleted asset #{$itemId} '{$item['item_name']}' ({$item['barcode']}). Reason: {$trimmedReason}"
            );
        } else {
            // Soft archive / stage for disposal: preserve transaction ledger for auditing and queue into Disposal Staging Queue
            $dispStmt = $db->prepare("
                UPDATE items SET
                    status         = 'for_disposal',
                    item_condition = 'for_disposal',
                    updated_at     = NOW()
                WHERE item_id = :id
            ");
            $dispStmt->bindValue(':id', $itemId, PDO::PARAM_INT);
            $dispStmt->execute();

            $operationType = 'staged_for_disposal';
            $message = "Asset '{$item['barcode']}' has historical transaction records and was moved to the Disposal Staging Queue for institutional condemnation.";

            // Record audit log
            AuditLog::log(
                $db,
                $userId,
                'ASSET_STAGED_FOR_DISPOSAL',
                "Staged asset #{$itemId} '{$item['item_name']}' ({$item['barcode']}) to Disposal Staging Queue due to archive/deletion request. Reason: {$trimmedReason}"
            );
        }

        return [
            'success' => true,
            'type'    => $operationType,
            'message' => $message,
            'item'    => $item,
        ];
    }

    /**
     * Get real category distribution for charts and metric overviews.
     */
    public static function getCategoryDistribution(PDO $db): array {
        $stmt = $db->query("
            SELECT category, COUNT(*) as count 
            FROM items 
            WHERE category IS NOT NULL AND category != ''
            GROUP BY category 
            ORDER BY count DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        $labels = [];
        $data = [];
        foreach ($rows as $r) {
            $labels[] = $r['category'];
            $data[]   = (int)$r['count'];
        }
        return [
            'labels' => $labels,
            'data'   => $data,
            'raw'    => $rows,
        ];
    }

    /**
     * Get inventory asset breakdown by location/department.
     */
    public static function getDepartmentBreakdown(PDO $db): array {
        $totalItems = (int)$db->query("SELECT COUNT(*) FROM items")->fetchColumn();
        if ($totalItems === 0) {
            return [];
        }

        $stmt = $db->query("
            SELECT location, COUNT(*) as count 
            FROM items 
            WHERE location IS NOT NULL AND location != ''
            GROUP BY location 
            ORDER BY count DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $colors = ['#533afd', '#059669', '#d97706', '#3b5bdb', '#64748d', '#e11d48', '#8b5cf6'];
        $result = [];
        foreach ($rows as $idx => $r) {
            $cnt = (int)$r['count'];
            $pct = $totalItems > 0 ? (int)round(($cnt / $totalItems) * 100) : 0;
            $result[] = [
                'name'  => $r['location'],
                'count' => $cnt,
                'pct'   => $pct,
                'color' => $colors[$idx % count($colors)],
            ];
        }
        return $result;
    }

    /**
     * Restore an asset from 'maintenance' back to 'available' with a refreshed condition.
     */
    public static function restoreFromMaintenance(PDO $db, int $itemId, string $condition, ?string $notes, int $custodianId): array {
        if (!in_array($condition, ['mint', 'good', 'fair'], true)) {
            throw new InvalidArgumentException("Restored asset condition must be Mint, Good, or Fair.");
        }

        $item = self::findById($db, $itemId);
        if (!$item) {
            throw new RuntimeException("Asset not found (ID #{$itemId}).");
        }

        if ($item['status'] !== 'maintenance') {
            throw new RuntimeException("Only items currently in Maintenance status can be restored to service.");
        }

        $stmt = $db->prepare("
            UPDATE items 
            SET status = 'available',
                item_condition = :condition,
                updated_at = NOW()
            WHERE item_id = :id
        ");
        $stmt->execute([
            ':condition' => $condition,
            ':id'        => $itemId,
        ]);

        $logNotes = "Restored asset '{$item['item_name']}' ({$item['barcode']}) to AVAILABLE status. New Condition: {$condition}.";
        if ($notes !== null && trim($notes) !== '') {
            $logNotes .= " Maintenance Notes: " . trim($notes);
        }

        AuditLog::log($db, $custodianId, 'ASSET_RESTORED_TO_SERVICE', $logNotes);

        return [
            'success' => true,
            'message' => "Asset '{$item['barcode']}' has been successfully restored to Available status.",
            'item'    => self::findById($db, $itemId),
        ];
    }

    /**
     * Declare an asset Beyond Economic Repair (B.E.R.) and transition to 'for_disposal'.
     */
    public static function declareBer(PDO $db, int $itemId, string $reason, int $custodianId): array {
        $trimmedReason = trim($reason);
        if (mb_strlen($trimmedReason) < 3) {
            throw new InvalidArgumentException("A valid condemnation reason is required (minimum 3 characters).");
        }

        $item = self::findById($db, $itemId);
        if (!$item) {
            throw new RuntimeException("Asset not found (ID #{$itemId}).");
        }

        if ($item['status'] === 'borrowed') {
            throw new RuntimeException("Cannot condemn asset while it is actively borrowed.");
        }

        $stmt = $db->prepare("
            UPDATE items 
            SET status = 'for_disposal',
                item_condition = 'for_disposal',
                updated_at = NOW()
            WHERE item_id = :id
        ");
        $stmt->execute([':id' => $itemId]);

        AuditLog::log(
            $db,
            $custodianId,
            'ASSET_DECLARED_BER',
            "Declared asset #{$itemId} '{$item['item_name']}' ({$item['barcode']}) Beyond Economic Repair (For Disposal). Justification: {$trimmedReason}"
        );

        return [
            'success' => true,
            'message' => "Asset '{$item['barcode']}' has been declared Beyond Economic Repair and queued for official disposal.",
            'item'    => self::findById($db, $itemId),
        ];
    }

    /**
     * Stage an active or maintenance asset for disposal (status -> 'for_disposal').
     *
     * @param PDO $db
     * @param int $itemId
     * @param string $reason
     * @param int $custodianId
     * @return array
     * @throws InvalidArgumentException|RuntimeException
     */
    public static function stageForDisposal(PDO $db, int $itemId, string $reason, int $custodianId): array {
        $trimmedReason = trim($reason);
        if (mb_strlen($trimmedReason) < 3) {
            throw new InvalidArgumentException("A valid condemnation reason is required (minimum 3 characters).");
        }

        $item = self::findById($db, $itemId);
        if (!$item) {
            throw new RuntimeException("Asset not found (ID #{$itemId}).");
        }

        if ($item['status'] === 'borrowed') {
            throw new RuntimeException("Cannot condemn asset while it is actively borrowed. Item must be returned first.");
        }

        if ($item['status'] === 'disposed') {
            throw new RuntimeException("Asset is already decommissioned to Disposed status.");
        }

        $stmt = $db->prepare("
            UPDATE items 
            SET status = 'for_disposal',
                item_condition = 'for_disposal',
                updated_at = NOW()
            WHERE item_id = :id
        ");
        $stmt->execute([':id' => $itemId]);

        AuditLog::log(
            $db,
            $custodianId,
            'ASSET_STAGED_FOR_DISPOSAL',
            "Asset #{$itemId} '{$item['item_name']}' ({$item['barcode']}) was queued for disposal. Reason: {$trimmedReason}"
        );

        return [
            'success' => true,
            'message' => "Asset '{$item['barcode']}' has been staged for disposal.",
            'item'    => self::findById($db, $itemId),
        ];
    }

    /**
     * Decommission a single asset with full method tagging, salvage recovery, and audit logs.
     *
     * @param PDO $db
     * @param int $itemId
     * @param array $disposalData ['disposal_method', 'disposal_reason', 'salvage_value', 'disposal_date', 'remarks']
     * @param int $custodianId
     * @return array
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function decommissionItem(PDO $db, int $itemId, array $disposalData, int $custodianId): array {
        $item = self::findById($db, $itemId);
        if (!$item) {
            throw new RuntimeException("Asset not found (ID #{$itemId}).");
        }

        if ($item['status'] === 'borrowed') {
            throw new RuntimeException("Cannot decommission asset while it is actively borrowed / checked out on loan. The physical item must be returned first.");
        }

        $wasInTransaction = $db->inTransaction();
        if (!$wasInTransaction) {
            $db->beginTransaction();
        }

        try {
            // Update items table status to 'disposed'
            $stmt = $db->prepare("
                UPDATE items 
                SET status = 'disposed',
                    item_condition = 'for_disposal',
                    updated_at = NOW()
                WHERE item_id = :id
            ");
            $stmt->execute([':id' => $itemId]);

            // Create disposal record in dedicated table
            $disposalData['item_id'] = $itemId;
            $disposalData['disposed_by_user_id'] = $custodianId;
            $recordId = DisposalRecord::createRecord($db, $disposalData);

            // Insert audit transaction log into transactions table
            $method = $disposalData['disposal_method'] ?? 'scrapped';
            $reason = $disposalData['disposal_reason'] ?? '';
            $salvage = (float)($disposalData['salvage_value'] ?? 0.00);

            $txnStmt = $db->prepare("
                INSERT INTO transactions (
                    item_id,
                    user_id,
                    transaction_type,
                    transaction_date,
                    remarks,
                    processed_by
                ) VALUES (
                    :item_id,
                    :user_id,
                    'disposal',
                    NOW(),
                    :remarks,
                    :processed_by
                )
            ");
            $txnRemark = "Asset Decommissioned [Method: " . strtoupper($method) . "] Reason: {$reason}" . ($salvage > 0 ? " | Salvage: ₱" . number_format($salvage, 2) : "");
            $txnStmt->execute([
                ':item_id'      => $itemId,
                ':user_id'      => $custodianId,
                ':remarks'      => $txnRemark,
                ':processed_by' => $custodianId,
            ]);

            AuditLog::log(
                $db,
                $custodianId,
                'ASSET_DECOMMISSIONED',
                "Decommissioned asset #{$itemId} '{$item['item_name']}' ({$item['barcode']}) via " . strtoupper($method) . ". Reason: {$reason}"
            );

            if (!$wasInTransaction) {
                $db->commit();
            }

            return [
                'success'            => true,
                'disposal_record_id' => $recordId,
                'message'            => "Asset '{$item['barcode']}' has been officially decommissioned.",
                'item'               => self::findById($db, $itemId),
            ];
        } catch (\Throwable $e) {
            if (!$wasInTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Batch decommission multiple staged assets in a single atomic transaction.
     *
     * @param PDO $db
     * @param int[] $itemIds
     * @param array $disposalData
     * @param int $custodianId
     * @return array ['success' => bool, 'count' => int, 'items' => array]
     */
    public static function batchDecommission(PDO $db, array $itemIds, array $disposalData, int $custodianId): array {
        if (empty($itemIds)) {
            throw new InvalidArgumentException("Please select at least one asset to decommission.");
        }

        $db->beginTransaction();
        $processed = [];

        try {
            foreach ($itemIds as $rawId) {
                $id = (int)$rawId;
                if ($id <= 0) continue;
                $res = self::decommissionItem($db, $id, $disposalData, $custodianId);
                $processed[] = $res['item'];
            }

            $db->commit();
            return [
                'success' => true,
                'count'   => count($processed),
                'message' => "Successfully decommissioned " . count($processed) . " asset(s).",
                'items'   => $processed,
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Restore an asset from 'for_disposal' or 'disposed' back to active circulation.
     *
     * @param PDO $db
     * @param int $itemId
     * @param string $targetStatus 'available' or 'maintenance'
     * @param string $reason
     * @param int $custodianId
     * @return array
     */
    public static function restoreFromDisposal(
        PDO $db,
        int $itemId,
        string $targetStatus,
        string $reason,
        int $custodianId
    ): array {
        $trimmedReason = trim($reason);
        if (mb_strlen($trimmedReason) < 3) {
            throw new InvalidArgumentException("A valid justification is required for asset restoration (minimum 3 characters).");
        }

        if (!in_array($targetStatus, ['available', 'maintenance'], true)) {
            $targetStatus = 'available';
        }

        $item = self::findById($db, $itemId);
        if (!$item) {
            throw new RuntimeException("Asset not found (ID #{$itemId}).");
        }

        if ($item['status'] === 'disposed') {
            throw new RuntimeException("Cannot restore asset '{$item['barcode']}'. Decommissioned assets are permanently archived and cannot be reinstated into the inventory system.");
        }

        if ($item['status'] !== 'for_disposal') {
            throw new RuntimeException("Only assets currently staged for disposal can be reinstated to active inventory.");
        }

        $condition = ($targetStatus === 'available') ? 'good' : 'damaged';

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE items 
                SET status = :status,
                    item_condition = :condition,
                    updated_at = NOW()
                WHERE item_id = :id
            ");
            $stmt->execute([
                ':status'    => $targetStatus,
                ':condition' => $condition,
                ':id'        => $itemId,
            ]);

            // Remove from disposal_records if it was formally archived
            DisposalRecord::deleteByItemId($db, $itemId);

            AuditLog::log(
                $db,
                $custodianId,
                'ASSET_RESTORED_FROM_DISPOSAL',
                "Reinstated asset #{$itemId} '{$item['item_name']}' ({$item['barcode']}) from {$item['status']} to {$targetStatus}. Justification: {$trimmedReason}"
            );

            $db->commit();

            return [
                'success' => true,
                'message' => "Asset '{$item['barcode']}' has been reinstated to " . ucfirst($targetStatus) . " status.",
                'item'    => self::findById($db, $itemId),
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get recent assets for dashboard tables and overviews.
     *
     * @param PDO $db
     * @param int $limit
     * @return array
     */
    public static function getRecentAssets(PDO $db, int $limit = 10): array {
        $stmt = $db->prepare("
            SELECT i.*, u.full_name AS registered_by_name
            FROM items i
            LEFT JOIN users u ON i.registered_by = u.user_id
            ORDER BY i.created_at DESC, i.item_id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}


