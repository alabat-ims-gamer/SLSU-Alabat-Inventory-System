<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use InvalidArgumentException;

/**
 * PropertyMovement Model
 * Manages physical relocation records of campus properties across rooms and laboratories.
 * Backs Section 5 (Property Movement and Location History) of the Executive Institutional Property Report.
 */
class PropertyMovement {

    /**
     * Log a location movement/transfer for an asset.
     */
    public static function log(
        PDO $db,
        int $itemId,
        string $previousLocation,
        string $newLocation,
        string $reason = 'Reallocation / Location Update',
        ?int $transferredBy = null
    ): bool {
        if ($itemId <= 0) {
            throw new InvalidArgumentException("Invalid item ID for movement tracking.");
        }

        $stmt = $db->prepare("
            INSERT INTO property_movements (
                item_id, previous_location, new_location, reason, transferred_by, transfer_date
            ) VALUES (
                :item_id, :prev_loc, :new_loc, :reason, :user_id, NOW()
            )
        ");

        return $stmt->execute([
            ':item_id'  => $itemId,
            ':prev_loc' => trim($previousLocation),
            ':new_loc'  => trim($newLocation),
            ':reason'   => trim($reason) !== '' ? trim($reason) : 'Reallocation / Location Update',
            ':user_id'  => $transferredBy,
        ]);
    }

    /**
     * Retrieve all property movements with item & user joins, optionally scoped by category.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getHistory(PDO $db, ?string $category = null, int $limit = 100): array {
        $where = [];
        $params = [];

        if (!empty($category) && $category !== 'all') {
            $where[] = "i.category = :category";
            $params[':category'] = $category;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $db->prepare("
            SELECT 
                pm.id,
                pm.item_id,
                pm.previous_location,
                pm.new_location,
                pm.transfer_date,
                pm.reason,
                pm.transferred_by,
                i.item_name,
                i.barcode,
                i.property_number,
                i.category,
                COALESCE(u.full_name, 'Campus Custodian') AS transferred_by_name
            FROM property_movements pm
            INNER JOIN items i ON pm.item_id = i.item_id
            LEFT JOIN users u ON pm.transferred_by = u.user_id
            {$whereClause}
            ORDER BY pm.transfer_date DESC, pm.id DESC
            LIMIT :limit
        ");

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retrieve movement history for a specific asset.
     */
    public static function getHistoryByItem(PDO $db, int $itemId): array {
        $stmt = $db->prepare("
            SELECT 
                pm.*,
                COALESCE(u.full_name, 'Campus Custodian') AS transferred_by_name
            FROM property_movements pm
            LEFT JOIN users u ON pm.transferred_by = u.user_id
            WHERE pm.item_id = :item_id
            ORDER BY pm.transfer_date DESC, pm.id DESC
        ");
        $stmt->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
