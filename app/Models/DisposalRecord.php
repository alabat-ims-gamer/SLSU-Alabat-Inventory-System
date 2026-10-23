<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

/**
 * DisposalRecord Model — Enterprise Asset Condemnation & Decommissioning Ledger
 * 
 * Fulfills SLSU-Alabat Property Lifecycle requirements (REVISE_RESEARCH.pdf).
 * Fully protected with PDO prepared statements, OWASP security, and strict type safety.
 */
class DisposalRecord {

    /**
     * Official government-standard asset disposal methods
     */
    public const VALID_METHODS = [
        'scrapped',
        'sold',
        'donated',
        'cannibalized',
        'transferred',
    ];

    /**
     * Human-readable label map for disposal methods
     */
    public const METHOD_LABELS = [
        'scrapped'     => 'Scrapped / Waste',
        'sold'         => 'Sold / Auctioned',
        'donated'      => 'Donated',
        'cannibalized' => 'Cannibalized (Parts)',
        'transferred'  => 'Transferred',
    ];

    /**
     * Badge CSS class map
     */
    public const METHOD_BADGE_CLASSES = [
        'scrapped'     => 'badge-ruby',
        'sold'         => 'badge-emerald',
        'donated'      => 'badge-cyan',
        'cannibalized' => 'badge-amber',
        'transferred'  => 'badge-indigo',
    ];

    /**
     * Insert a new formal asset disposal record.
     *
     * @param PDO $db
     * @param array $data ['item_id', 'disposed_by_user_id', 'disposal_method', 'disposal_reason', 'salvage_value', 'disposal_date', 'remarks']
     * @return int Inserted record ID
     * @throws InvalidArgumentException|RuntimeException
     */
    public static function createRecord(PDO $db, array $data): int {
        $itemId = (int)($data['item_id'] ?? 0);
        $userId = (int)($data['disposed_by_user_id'] ?? 0);
        $method = strtolower(trim((string)($data['disposal_method'] ?? 'scrapped')));
        $reason = trim((string)($data['disposal_reason'] ?? ''));
        $salvage = max(0.0, (float)($data['salvage_value'] ?? 0.00));
        $dateStr = trim((string)($data['disposal_date'] ?? date('Y-m-d')));
        $remarks = isset($data['remarks']) ? trim((string)$data['remarks']) : null;

        if ($itemId <= 0) {
            throw new InvalidArgumentException("A valid item ID is required for disposal recording.");
        }
        if ($userId <= 0) {
            throw new InvalidArgumentException("A valid custodian user ID is required.");
        }
        if (!in_array($method, self::VALID_METHODS, true)) {
            throw new InvalidArgumentException("Invalid disposal method: '{$method}'.");
        }
        if (mb_strlen($reason) < 3) {
            throw new InvalidArgumentException("A valid disposal reason is required (minimum 3 characters).");
        }

        // Validate date format Y-m-d
        $d = \DateTime::createFromFormat('Y-m-d', $dateStr);
        if (!$d || $d->format('Y-m-d') !== $dateStr) {
            $dateStr = date('Y-m-d');
        }

        $stmt = $db->prepare("
            INSERT INTO disposal_records (
                item_id,
                disposed_by_user_id,
                disposal_method,
                disposal_reason,
                salvage_value,
                disposal_date,
                remarks,
                created_at
            ) VALUES (
                :item_id,
                :user_id,
                :method,
                :reason,
                :salvage,
                :disposal_date,
                :remarks,
                NOW()
            )
        ");

        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':method', $method, PDO::PARAM_STR);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        $stmt->bindValue(':salvage', $salvage);
        $stmt->bindValue(':disposal_date', $dateStr, PDO::PARAM_STR);
        $stmt->bindValue(':remarks', $remarks !== '' ? $remarks : null, $remarks !== '' && $remarks !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

        $stmt->execute();
        return (int)$db->lastInsertId();
    }

    /**
     * Retrieve paginated and filtered items staged for disposal (status = 'for_disposal').
     */
    public static function getStagedList(
        PDO $db,
        int $page = 1,
        int $pageSize = 15,
        string $search = '',
        array $filters = []
    ): array {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $where = ["i.status = 'for_disposal'"];
        $params = [];

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

        if (!empty($filters['category'])) {
            $where[] = "i.category = :filter_cat";
            $params[':filter_cat'] = trim($filters['category']);
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM items i WHERE {$whereClause}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $dataStmt = $db->prepare("
            SELECT 
                i.*, 
                u.full_name AS registered_by_name
            FROM items i
            LEFT JOIN users u ON i.registered_by = u.user_id
            WHERE {$whereClause}
            ORDER BY i.updated_at DESC, i.item_id DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $k => $v) {
            $dataStmt->bindValue($k, $v, PDO::PARAM_STR);
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
     * Retrieve paginated and filtered historical decommissioned assets (status = 'disposed').
     */
    public static function getDisposedList(
        PDO $db,
        int $page = 1,
        int $pageSize = 15,
        string $search = '',
        array $filters = []
    ): array {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $where = ["i.status = 'disposed'"];
        $params = [];

        $searchTerm = trim($search);
        if ($searchTerm !== '') {
            $where[] = "(
                i.item_name LIKE :search_name 
                OR i.barcode LIKE :search_bc 
                OR i.property_number LIKE :search_prop 
                OR i.category LIKE :search_cat 
                OR dr.disposal_reason LIKE :search_reason
            )";
            $wildcard = '%' . $searchTerm . '%';
            $params[':search_name']   = $wildcard;
            $params[':search_bc']     = $wildcard;
            $params[':search_prop']   = $wildcard;
            $params[':search_cat']    = $wildcard;
            $params[':search_reason'] = $wildcard;
        }

        if (!empty($filters['category'])) {
            $where[] = "i.category = :filter_cat";
            $params[':filter_cat'] = trim($filters['category']);
        }

        if (!empty($filters['method']) && in_array($filters['method'], self::VALID_METHODS, true)) {
            $where[] = "dr.disposal_method = :filter_method";
            $params[':filter_method'] = trim($filters['method']);
        }

        if (!empty($filters['date_from'])) {
            $where[] = "dr.disposal_date >= :date_from";
            $params[':date_from'] = trim($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where[] = "dr.disposal_date <= :date_to";
            $params[':date_to'] = trim($filters['date_to']);
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM items i
            LEFT JOIN disposal_records dr ON i.item_id = dr.item_id
            WHERE {$whereClause}
        ");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $dataStmt = $db->prepare("
            SELECT 
                i.*, 
                dr.id AS disposal_record_id,
                dr.disposal_method,
                dr.disposal_reason,
                dr.salvage_value,
                dr.disposal_date,
                dr.remarks AS disposal_remarks,
                u.full_name AS disposed_by_name,
                reg.full_name AS registered_by_name
            FROM items i
            LEFT JOIN disposal_records dr ON i.item_id = dr.item_id
            LEFT JOIN users u ON dr.disposed_by_user_id = u.user_id
            LEFT JOIN users reg ON i.registered_by = reg.user_id
            WHERE {$whereClause}
            ORDER BY COALESCE(dr.disposal_date, i.updated_at) DESC, i.item_id DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $k => $v) {
            $dataStmt->bindValue($k, $v, PDO::PARAM_STR);
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
     * Retrieve aggregate KPI metrics for the Disposals module.
     */
    public static function getDisposalStats(PDO $db): array {
        // Staged count
        $stagedCount = (int)$db->query("
            SELECT COUNT(*) FROM items WHERE status = 'for_disposal'
        ")->fetchColumn();

        // Disposed metrics
        $stmt = $db->query("
            SELECT 
                COUNT(*) AS total_disposed,
                COALESCE(SUM(salvage_value), 0.00) AS total_salvage_value,
                SUM(CASE WHEN disposal_method = 'scrapped' THEN 1 ELSE 0 END) AS scrapped_count,
                SUM(CASE WHEN disposal_method = 'sold' THEN 1 ELSE 0 END) AS sold_count,
                SUM(CASE WHEN disposal_method = 'donated' THEN 1 ELSE 0 END) AS donated_count,
                SUM(CASE WHEN disposal_method = 'cannibalized' THEN 1 ELSE 0 END) AS cannibalized_count,
                SUM(CASE WHEN disposal_method = 'transferred' THEN 1 ELSE 0 END) AS transferred_count
            FROM disposal_records
        ");
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Total lifetime disposed items from items table (including legacy)
        $totalLifetimeDisposed = (int)$db->query("
            SELECT COUNT(*) FROM items WHERE status = 'disposed'
        ")->fetchColumn();

        return [
            'staged_count'        => $stagedCount,
            'total_disposed'      => max($totalLifetimeDisposed, (int)($metrics['total_disposed'] ?? 0)),
            'total_salvage_value' => (float)($metrics['total_salvage_value'] ?? 0.00),
            'scrapped_count'      => (int)($metrics['scrapped_count'] ?? 0),
            'sold_count'          => (int)($metrics['sold_count'] ?? 0),
            'donated_count'       => (int)($metrics['donated_count'] ?? 0),
            'cannibalized_count'  => (int)($metrics['cannibalized_count'] ?? 0),
            'transferred_count'   => (int)($metrics['transferred_count'] ?? 0),
        ];
    }

    /**
     * Find disposal record by item_id.
     */
    public static function findByItemId(PDO $db, int $itemId): ?array {
        $stmt = $db->prepare("
            SELECT dr.*, u.full_name AS disposed_by_name
            FROM disposal_records dr
            LEFT JOIN users u ON dr.disposed_by_user_id = u.user_id
            WHERE dr.item_id = :item_id
            ORDER BY dr.id DESC
            LIMIT 1
        ");
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Delete disposal record for restoration safety.
     */
    public static function deleteByItemId(PDO $db, int $itemId): bool {
        $stmt = $db->prepare("DELETE FROM disposal_records WHERE item_id = :item_id");
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
