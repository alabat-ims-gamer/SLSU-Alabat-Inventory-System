<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use InvalidArgumentException;

/**
 * LostProperty Model
 * Manages missing and lost equipment incident records, accountable staff, and resolution states.
 * Backs Section 4 (Lost and Missing Properties Report) of the Executive Institutional Property Report.
 */
class LostProperty {

    public const VALID_STATUSES = [
        'under_investigation',
        'located',
        'confirmed_lost'
    ];

    public const VALID_RESOLUTIONS = [
        'pending',
        'located',
        'confirmed_lost'
    ];

    /**
     * Report an asset as lost or missing.
     *
     * @param array{
     *   item_id: int,
     *   last_recorded_location?: string,
     *   accountable_person?: string,
     *   date_reported?: string,
     *   description: string,
     *   investigation_status?: string,
     *   resolution?: string,
     *   reported_by?: int
     * } $data
     * @return int Inserted lost_property ID
     */
    public static function report(PDO $db, array $data): int {
        $itemId = (int)($data['item_id'] ?? 0);
        if ($itemId <= 0) {
            throw new InvalidArgumentException("Invalid item ID for lost property report.");
        }

        $desc = trim((string)($data['description'] ?? ''));
        if ($desc === '') {
            throw new InvalidArgumentException("Description is required when reporting an asset lost.");
        }

        $stmt = $db->prepare("
            INSERT INTO lost_properties (
                item_id, last_recorded_location, accountable_person, date_reported, 
                description, investigation_status, resolution, reported_by
            ) VALUES (
                :item_id, :last_loc, :accountable, :date_rep, :desc, :status, :resolution, :user_id
            )
        ");

        $stmt->execute([
            ':item_id'     => $itemId,
            ':last_loc'    => trim((string)($data['last_recorded_location'] ?? '')),
            ':accountable' => trim((string)($data['accountable_person'] ?? 'Unassigned / General Custody')),
            ':date_rep'    => !empty($data['date_reported']) ? $data['date_reported'] : date('Y-m-d'),
            ':desc'        => $desc,
            ':status'      => $data['investigation_status'] ?? 'under_investigation',
            ':resolution'  => $data['resolution'] ?? 'pending',
            ':user_id'     => $data['reported_by'] ?? null,
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Retrieve lost property reports with item & reporter joins, optionally scoped by category.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getReports(PDO $db, ?string $category = null, int $limit = 100): array {
        $where = [];
        $params = [];

        if (!empty($category) && $category !== 'all') {
            $where[] = "i.category = :category";
            $params[':category'] = $category;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $db->prepare("
            SELECT 
                lp.id,
                lp.item_id,
                lp.last_recorded_location,
                lp.accountable_person,
                lp.date_reported,
                lp.description,
                lp.investigation_status,
                lp.resolution,
                lp.created_at,
                i.item_name,
                i.barcode,
                i.property_number,
                i.category,
                i.location AS current_item_location,
                COALESCE(u.full_name, 'Campus Custodian') AS reported_by_name
            FROM lost_properties lp
            INNER JOIN items i ON lp.item_id = i.item_id
            LEFT JOIN users u ON lp.reported_by = u.user_id
            {$whereClause}
            ORDER BY lp.date_reported DESC, lp.id DESC
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
     * Update investigation status and resolution for a lost property report.
     */
    public static function updateResolution(PDO $db, int $id, string $status, string $resolution): bool {
        if (!in_array($status, self::VALID_STATUSES, true) || !in_array($resolution, self::VALID_RESOLUTIONS, true)) {
            throw new InvalidArgumentException("Invalid investigation status or resolution.");
        }

        $stmt = $db->prepare("
            UPDATE lost_properties 
            SET investigation_status = :status, resolution = :resolution, updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':status'     => $status,
            ':resolution' => $resolution,
            ':id'         => $id,
        ]);
    }
}
