<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use RuntimeException;
use InvalidArgumentException;
use App\Services\NotificationService;

/**
 * InspectionReport Model — Form AFA-SAF-1.016 (Inspection and Acceptance Report)
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 * 
 * Governs the formal physical verification and certification of incoming shipments
 * before custodial registration into live inventory.
 */
class InspectionReport {

    public const STATUS_PENDING_SIGNATURES = 'PENDING_SIGNATURES';
    public const STATUS_TRANSMITTED        = 'TRANSMITTED';
    public const STATUS_REGISTERED         = 'REGISTERED';
    public const STATUS_CANCELLED          = 'CANCELLED';

    public const ACCEPTANCE_COMPLETE = 'COMPLETE';
    public const ACCEPTANCE_PARTIAL  = 'PARTIAL';

    /**
     * Atomically generate the next sequential IAR number (e.g. IAR-2026-0001).
     */
    public static function generateIarNo(PDO $db): string {
        $year = date('Y');
        $prefix = "IAR-{$year}-";

        $stmt = $db->prepare("
            SELECT iar_no 
            FROM inspection_reports 
            WHERE iar_no LIKE :prefix 
            ORDER BY id DESC 
            LIMIT 1 
            FOR UPDATE
        ");
        $stmt->execute([':prefix' => "{$prefix}%"]);
        $lastIarNo = $stmt->fetchColumn();

        if ($lastIarNo) {
            $lastSeq = (int)substr((string)$lastIarNo, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf("%s%04d", $prefix, $nextSeq);
    }

    /**
     * Create a new Inspection and Acceptance Report with line items.
     * Transactionally creates parent and child line items.
     */
    public static function create(PDO $db, array $reportData, array $items, int $creatorId): array {
        if (empty($reportData['supplier_name'])) {
            throw new InvalidArgumentException("Supplier name is required.");
        }
        if (empty($reportData['po_no'])) {
            throw new InvalidArgumentException("Purchase Order (PO) number is required.");
        }
        if (empty($reportData['invoice_no'])) {
            throw new InvalidArgumentException("Invoice number is required.");
        }
        if (empty($reportData['office_dept'])) {
            throw new InvalidArgumentException("Requisitioning office/department is required.");
        }
        if (empty($items)) {
            throw new InvalidArgumentException("At least one line item must be attached to the IAR.");
        }

        $wasInTransaction = $db->inTransaction();
        if (!$wasInTransaction) {
            $db->beginTransaction();
        }

        try {
            $iarNo = self::generateIarNo($db);

            $stmt = $db->prepare("
                INSERT INTO inspection_reports (
                    iar_no, entity_name, fund_cluster, supplier_name,
                    po_no, po_date, invoice_no, invoice_date,
                    office_dept, rcc, date_inspected, date_received,
                    inspection_officer, property_officer,
                    is_inspected_order, acceptance_type, partial_remarks,
                    status, created_by, created_at
                ) VALUES (
                    :iar_no, :entity_name, :fund_cluster, :supplier_name,
                    :po_no, :po_date, :invoice_no, :invoice_date,
                    :office_dept, :rcc, :date_inspected, :date_received,
                    :inspection_officer, :property_officer,
                    :is_inspected_order, :acceptance_type, :partial_remarks,
                    :status, :created_by, NOW()
                )
            ");

            $stmt->execute([
                ':iar_no'             => $iarNo,
                ':entity_name'        => trim($reportData['entity_name'] ?? 'SLSU-ALABAT'),
                ':fund_cluster'       => trim($reportData['fund_cluster'] ?? 'PRE-STF'),
                ':supplier_name'      => trim($reportData['supplier_name']),
                ':po_no'              => trim($reportData['po_no']),
                ':po_date'            => !empty($reportData['po_date']) ? $reportData['po_date'] : date('Y-m-d'),
                ':invoice_no'         => trim($reportData['invoice_no']),
                ':invoice_date'       => !empty($reportData['invoice_date']) ? $reportData['invoice_date'] : date('Y-m-d'),
                ':office_dept'        => trim($reportData['office_dept']),
                ':rcc'                => !empty($reportData['rcc']) ? trim($reportData['rcc']) : null,
                ':date_inspected'     => !empty($reportData['date_inspected']) ? $reportData['date_inspected'] : date('Y-m-d'),
                ':date_received'      => !empty($reportData['date_received']) ? $reportData['date_received'] : date('Y-m-d'),
                ':inspection_officer' => trim($reportData['inspection_officer'] ?? 'MANUEL S. VALDERAMA, MIT'),
                ':property_officer'   => trim($reportData['property_officer'] ?? 'VERONICA C. OBIEN'),
                ':is_inspected_order' => !empty($reportData['is_inspected_order']) ? 1 : 1,
                ':acceptance_type'    => ($reportData['acceptance_type'] ?? '') === self::ACCEPTANCE_PARTIAL ? self::ACCEPTANCE_PARTIAL : self::ACCEPTANCE_COMPLETE,
                ':partial_remarks'    => !empty($reportData['partial_remarks']) ? trim($reportData['partial_remarks']) : null,
                ':status'             => self::STATUS_PENDING_SIGNATURES,
                ':created_by'         => $creatorId,
            ]);

            $iarId = (int)$db->lastInsertId();

            // Insert line items
            $itemStmt = $db->prepare("
                INSERT INTO inspection_report_items (
                    iar_id, item_no, item_description, unit, quantity,
                    unit_cost, total_cost, model, serial_no, is_registered
                ) VALUES (
                    :iar_id, :item_no, :item_description, :unit, :quantity,
                    :unit_cost, :total_cost, :model, :serial_no, 0
                )
            ");

            $itemSeq = 1;
            foreach ($items as $item) {
                $desc = trim($item['item_description'] ?? '');
                if ($desc === '') {
                    continue;
                }

                $qty = max(1, (int)($item['quantity'] ?? 1));
                $unitCost = max(0.0, (float)($item['unit_cost'] ?? 0.0));
                $totalCost = (float)($item['total_cost'] ?? ($unitCost * $qty));

                $itemStmt->execute([
                    ':iar_id'           => $iarId,
                    ':item_no'          => $itemSeq++,
                    ':item_description' => $desc,
                    ':unit'             => trim($item['unit'] ?? 'units'),
                    ':quantity'         => $qty,
                    ':unit_cost'        => $unitCost,
                    ':total_cost'       => $totalCost,
                    ':model'            => !empty($item['model']) ? trim($item['model']) : null,
                    ':serial_no'        => !empty($item['serial_no']) ? trim($item['serial_no']) : null,
                ]);
            }

            if (!$wasInTransaction) {
                $db->commit();
            }

            return [
                'id'     => $iarId,
                'iar_no' => $iarNo,
                'status' => self::STATUS_PENDING_SIGNATURES,
            ];
        } catch (PDOException $e) {
            if (!$wasInTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[InspectionReport::create] Database error: ' . $e->getMessage());
            throw new RuntimeException("Failed to create Inspection and Acceptance Report: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch complete IAR details including all items and creator info.
     */
    public static function getById(PDO $db, int $id): ?array {
        $stmt = $db->prepare("
            SELECT 
                r.*,
                u.full_name AS creator_name,
                u.role AS creator_role
            FROM inspection_reports r
            LEFT JOIN users u ON r.created_by = u.user_id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            return null;
        }

        // Fetch line items
        $itemStmt = $db->prepare("
            SELECT 
                i.*,
                it.barcode AS registered_barcode,
                it.property_number AS registered_prop_no
            FROM inspection_report_items i
            LEFT JOIN items it ON i.registered_item_id = it.item_id
            WHERE i.iar_id = :iar_id
            ORDER BY i.item_no ASC
        ");
        $itemStmt->execute([':iar_id' => $id]);
        $report['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        return $report;
    }

    /**
     * Fetch IAR by official report number.
     */
    public static function getByIarNo(PDO $db, string $iarNo): ?array {
        $stmt = $db->prepare("SELECT id FROM inspection_reports WHERE iar_no = :iar_no LIMIT 1");
        $stmt->execute([':iar_no' => trim($iarNo)]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            return null;
        }

        return self::getById($db, (int)$id);
    }

    /**
     * Upload wet-signed IAR scan and advance state to TRANSMITTED.
     */
    public static function uploadSignedScan(PDO $db, int $id, string $filePath, int $userId): bool {
        $cleanPath = trim($filePath);
        if ($cleanPath === '') {
            throw new InvalidArgumentException("File path cannot be empty.");
        }

        $report = self::getById($db, $id);
        if (!$report) {
            throw new RuntimeException("Inspection Report ID {$id} not found.");
        }

        $stmt = $db->prepare("
            UPDATE inspection_reports 
            SET scanned_document_path = :path,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ");
        $success = $stmt->execute([
            ':path'   => $cleanPath,
            ':status' => self::STATUS_TRANSMITTED,
            ':id'     => $id,
        ]);

        if ($success) {
            // Notify all active Property Custodians about new IAR awaiting ingestion
            try {
                $custodianStmt = $db->query("SELECT user_id FROM users WHERE role = 'custodian' AND account_status = 'approved'");
                $custodians = $custodianStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($custodians as $cid) {
                    NotificationService::create(
                        $db,
                        (int)$cid,
                        'IAR_TRANSMITTED',
                        'New IAR Ready for Ingestion',
                        "IAR {$report['iar_no']} ({$report['supplier_name']}) has been certified and transmitted for asset tagging.",
                        NotificationService::TYPE_SUCCESS,
                        url('/inventory')
                    );
                }
            } catch (\Throwable $e) {
                error_log('[InspectionReport::uploadSignedScan] Notification notice: ' . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * List all IAR records with flexible search, filtering, and pagination.
     */
    public static function listAll(
        PDO $db,
        string $status = 'ALL',
        string $search = '',
        int $limit = 50,
        int $offset = 0
    ): array {
        $where = [];
        $params = [];

        if ($status !== 'ALL' && $status !== '') {
            $where[] = "r.status = :status";
            $params[':status'] = $status;
        }

        if (trim($search) !== '') {
            $where[] = "(r.iar_no LIKE :s1 OR r.supplier_name LIKE :s2 OR r.po_no LIKE :s3 OR r.invoice_no LIKE :s4 OR r.office_dept LIKE :s5)";
            $term = '%' . trim($search) . '%';
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
            $params[':s5'] = $term;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM inspection_reports r {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Paginated rows with aggregates
        $query = "
            SELECT 
                r.id,
                r.iar_no,
                r.supplier_name,
                r.po_no,
                r.po_date,
                r.invoice_no,
                r.office_dept,
                r.date_inspected,
                r.acceptance_type,
                r.scanned_document_path,
                r.status,
                r.created_at,
                u.full_name AS creator_name,
                COUNT(i.id) AS item_count,
                SUM(i.quantity) AS total_quantity,
                SUM(i.total_cost) AS total_valuation,
                SUM(CASE WHEN i.is_registered = 1 THEN 1 ELSE 0 END) AS registered_count
            FROM inspection_reports r
            LEFT JOIN inspection_report_items i ON r.id = i.iar_id
            LEFT JOIN users u ON r.created_by = u.user_id
            {$whereClause}
            GROUP BY r.id
            ORDER BY r.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'total'   => $total,
            'reports' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    /**
     * Get pending IARs for the Custodian Ingestion desk.
     */
    public static function getPendingIngestion(PDO $db): array {
        $stmt = $db->query("
            SELECT 
                r.id,
                r.iar_no,
                r.supplier_name,
                r.po_no,
                r.invoice_no,
                r.office_dept,
                r.scanned_document_path,
                r.date_inspected,
                r.created_at,
                COUNT(i.id) AS item_count,
                SUM(i.quantity) AS total_quantity,
                SUM(i.total_cost) AS total_valuation
            FROM inspection_reports r
            INNER JOIN inspection_report_items i ON r.id = i.iar_id
            WHERE r.status = 'TRANSMITTED'
            GROUP BY r.id
            ORDER BY r.id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark line items as registered in master inventory and advance IAR status to REGISTERED if complete.
     */
    public static function markItemsRegistered(PDO $db, int $iarId, array $registrations): bool {
        $wasInTransaction = $db->inTransaction();
        if (!$wasInTransaction) {
            $db->beginTransaction();
        }

        try {
            $updateStmt = $db->prepare("
                UPDATE inspection_report_items 
                SET is_registered = 1,
                    registered_item_id = :item_id
                WHERE id = :line_id AND iar_id = :iar_id
            ");

            foreach ($registrations as $reg) {
                $lineId = (int)($reg['line_id'] ?? 0);
                $itemId = (int)($reg['item_id'] ?? 0);
                if ($lineId > 0 && $itemId > 0) {
                    $updateStmt->execute([
                        ':item_id' => $itemId,
                        ':line_id' => $lineId,
                        ':iar_id'  => $iarId,
                    ]);
                }
            }

            // Check if all items in this IAR are now registered
            $checkStmt = $db->prepare("
                SELECT COUNT(*) 
                FROM inspection_report_items 
                WHERE iar_id = :iar_id AND is_registered = 0
            ");
            $checkStmt->execute([':iar_id' => $iarId]);
            $unregisteredCount = (int)$checkStmt->fetchColumn();

            if ($unregisteredCount === 0) {
                $advanceStmt = $db->prepare("
                    UPDATE inspection_reports 
                    SET status = :status,
                        updated_at = NOW() 
                    WHERE id = :id
                ");
                $advanceStmt->execute([
                    ':status' => self::STATUS_REGISTERED,
                    ':id'     => $iarId,
                ]);
            }

            if (!$wasInTransaction) {
                $db->commit();
            }

            return true;
        } catch (PDOException $e) {
            if (!$wasInTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[InspectionReport::markItemsRegistered] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Compute summary metrics for the Inspector dashboard.
     */
    public static function getMetrics(PDO $db): array {
        try {
            // Active Gate Passes Today
            $activePasses = (int)$db->query("
                SELECT COUNT(*) FROM gate_passes 
                WHERE status = 'ISSUED' AND DATE(created_at) = CURDATE()
            ")->fetchColumn();

            // Total Active Gate Passes
            $totalActivePasses = (int)$db->query("
                SELECT COUNT(*) FROM gate_passes 
                WHERE status = 'ISSUED'
            ")->fetchColumn();

            // IARs Pending Signatures (need wet-signed scan uploaded)
            $pendingSignatures = (int)$db->query("
                SELECT COUNT(*) FROM inspection_reports 
                WHERE status = 'PENDING_SIGNATURES'
            ")->fetchColumn();

            // IARs Transmitted to Custodian
            $transmitted = (int)$db->query("
                SELECT COUNT(*) FROM inspection_reports 
                WHERE status = 'TRANSMITTED'
            ")->fetchColumn();

            // Total Physical Items Inspected (YTD)
            $itemsInspected = (int)$db->query("
                SELECT COALESCE(SUM(quantity), 0) FROM inspection_report_items i
                INNER JOIN inspection_reports r ON i.iar_id = r.id
                WHERE r.status != 'CANCELLED' AND YEAR(r.created_at) = YEAR(CURDATE())
            ")->fetchColumn();

            return [
                'active_passes_today' => $activePasses,
                'total_active_passes' => $totalActivePasses,
                'pending_signatures'  => $pendingSignatures,
                'transmitted_count'   => $transmitted,
                'items_inspected_ytd' => $itemsInspected,
            ];
        } catch (PDOException $e) {
            error_log('[InspectionReport::getMetrics] Error: ' . $e->getMessage());
            return [
                'active_passes_today' => 0,
                'total_active_passes' => 0,
                'pending_signatures'  => 0,
                'transmitted_count'   => 0,
                'items_inspected_ytd' => 0,
            ];
        }
    }
}
