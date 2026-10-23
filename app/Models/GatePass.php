<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use RuntimeException;
use InvalidArgumentException;

/**
 * GatePass Model — Official Campus Equipment Exit Permit Engine
 * 
 * Manages official Gate Pass Permits for borrowed university equipment leaving campus.
 * Strictly adheres to OWASP secure coding, parameterized queries, and transactional snapshotting.
 */
class GatePass {

    public const PASS_TYPE_BORROWED = 'BORROWED';
    public const PASS_TYPE_REPAIR = 'REPAIR';
    public const PASS_TYPE_TRANSFER = 'INTER_CAMPUS_TRANSPORT';

    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Atomically generate the next sequential Gate Pass Permit number (e.g. GP-2026-0001).
     */
    public static function generatePassNo(PDO $db): string {
        $year = date('Y');
        $prefix = "GP-{$year}-";

        $stmt = $db->prepare("
            SELECT gate_pass_no 
            FROM gate_passes 
            WHERE gate_pass_no LIKE :prefix 
            ORDER BY id DESC 
            LIMIT 1 
            FOR UPDATE
        ");
        $stmt->execute([':prefix' => "{$prefix}%"]);
        $lastPassNo = $stmt->fetchColumn();

        if ($lastPassNo) {
            $lastSeq = (int)substr((string)$lastPassNo, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf("%s%04d", $prefix, $nextSeq);
    }

    /**
     * Hybrid Lookup: Lookup active borrowed item by Barcode tag.
     * Returns item specs, active borrower, and transaction details.
     */
    public static function lookupByBarcode(PDO $db, string $barcode): ?array {
        $cleanBarcode = trim($barcode);
        if ($cleanBarcode === '') {
            return null;
        }

        // 1. First check if the item exists
        $itemStmt = $db->prepare("
            SELECT 
                item_id, item_name, description, model, serial_number, 
                property_number, barcode, unit_cost, date_acquired, 
                status, item_condition, location
            FROM items 
            WHERE barcode = :barcode OR property_number = :barcode_prop
            LIMIT 1
        ");
        $itemStmt->execute([
            ':barcode'      => $cleanBarcode,
            ':barcode_prop' => $cleanBarcode,
        ]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return ['error' => 'not_found', 'message' => "No asset found with barcode '{$cleanBarcode}'."];
        }

        // 2. Check if the item is currently checked out under an active loan
        $txStmt = $db->prepare("
            SELECT 
                t.transaction_id, t.user_id, t.transaction_date, 
                t.expected_return_date, t.remarks AS loan_remarks,
                u.full_name AS borrower_name, u.email AS borrower_email, 
                u.department_program AS borrower_dept, u.role AS borrower_role
            FROM transactions t
            INNER JOIN users u ON t.user_id = u.user_id
            WHERE t.item_id = :item_id 
              AND t.transaction_type = 'borrow'
              AND t.actual_return_date IS NULL
            ORDER BY t.transaction_id DESC
            LIMIT 1
        ");
        $txStmt->execute([':item_id' => $item['item_id']]);
        $activeLoan = $txStmt->fetch(PDO::FETCH_ASSOC);

        if (!$activeLoan || $item['status'] !== 'borrowed') {
            return [
                'error'   => 'not_borrowed',
                'item'    => $item,
                'message' => "Asset '{$item['item_name']}' is currently in '{$item['status']}' status and has no active borrow record. Gate passes can only be issued for borrowed equipment."
            ];
        }

        // Complete snapshot payload
        return [
            'status' => 'success',
            'item'   => [
                'item_id'       => (int)$item['item_id'],
                'item_name'     => $item['item_name'],
                'description'   => !empty($item['description']) ? $item['description'] : $item['item_name'],
                'model'         => $item['model'] ?? 'N/A',
                'serial_no'     => $item['serial_number'] ?? 'N/A',
                'property_no'   => $item['property_number'] ?? '',
                'barcode'       => $item['barcode'],
                'date_acquired' => $item['date_acquired'],
                'unit_price'    => (float)($item['unit_cost'] ?? 0.00),
                'total_amount'  => (float)($item['unit_cost'] ?? 0.00),
                'condition'     => $item['item_condition'] ?? 'good',
            ],
            'borrower' => [
                'user_id'    => (int)$activeLoan['user_id'],
                'name'       => $activeLoan['borrower_name'],
                'email'      => $activeLoan['borrower_email'],
                'department' => $activeLoan['borrower_dept'] ?? 'General Academic',
                'role'       => $activeLoan['borrower_role'],
            ],
            'loan' => [
                'transaction_id'       => (int)$activeLoan['transaction_id'],
                'borrow_date'          => $activeLoan['transaction_date'],
                'expected_return_date' => $activeLoan['expected_return_date'],
                'purpose'              => $activeLoan['loan_remarks'] ?? '',
            ]
        ];
    }

    /**
     * Hybrid Lookup: Search active borrowed items by Borrower Name or ID.
     */
    public static function lookupByBorrower(PDO $db, string $query): array {
        $clean = trim($query);
        if ($clean === '') {
            return [];
        }

        $stmt = $db->prepare("
            SELECT 
                t.transaction_id, t.user_id, t.transaction_date, t.expected_return_date, t.remarks AS loan_remarks,
                u.full_name AS borrower_name, u.department_program AS borrower_dept, u.role AS borrower_role,
                i.item_id, i.item_name, i.description, i.model, i.serial_number, 
                i.property_number, i.barcode, i.unit_cost, i.date_acquired, i.item_condition
            FROM transactions t
            INNER JOIN users u ON t.user_id = u.user_id
            INNER JOIN items i ON t.item_id = i.item_id
            WHERE t.transaction_type = 'borrow'
              AND t.actual_return_date IS NULL
              AND i.status = 'borrowed'
              AND (u.full_name LIKE :search OR u.user_id = :exact_id OR u.email LIKE :search)
            ORDER BY t.transaction_id DESC
            LIMIT 25
        ");

        $searchParam = "%{$clean}%";
        $exactId = ctype_digit($clean) ? (int)$clean : 0;
        $stmt->execute([
            ':search'   => $searchParam,
            ':exact_id' => $exactId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create an official Gate Pass with child item snapshots in an atomic transaction.
     * 
     * @param PDO $db
     * @param array{
     *   requisitioner_id: ?int,
     *   requisitioner_name: string,
     *   requisitioning_office: string,
     *   purpose: string,
     *   pass_type?: string
     * } $data
     * @param array<int, array{
     *   item_id?: ?int,
     *   transaction_id?: ?int,
     *   item_description: string,
     *   model?: ?string,
     *   serial_no?: ?string,
     *   date_acquired?: ?string,
     *   unit_price?: float,
     *   total_amount?: float,
     *   remarks?: ?string
     * }> $items
     * @param int $inspectorId
     * @return array{id: int, gate_pass_no: string}
     */
    public static function create(PDO $db, array $data, array $items, int $inspectorId): array {
        if (empty($items)) {
            throw new InvalidArgumentException("A Gate Pass must contain at least one item.");
        }

        $requisitionerName = trim($data['requisitioner_name'] ?? '');
        $requisitioningOffice = trim($data['requisitioning_office'] ?? '');
        $purpose = trim($data['purpose'] ?? '');

        if ($requisitionerName === '') {
            throw new InvalidArgumentException("Requisitioner name is required.");
        }
        if ($requisitioningOffice === '') {
            throw new InvalidArgumentException("Requisitioning office is required.");
        }
        if ($purpose === '') {
            throw new InvalidArgumentException("Purpose is required.");
        }

        $passType = $data['pass_type'] ?? self::PASS_TYPE_BORROWED;

        $db->beginTransaction();
        try {
            $passNo = self::generatePassNo($db);

            $stmt = $db->prepare("
                INSERT INTO gate_passes (
                    gate_pass_no, requisitioner_id, requisitioner_name, 
                    requisitioning_office, purpose, pass_type, status, created_by
                ) VALUES (
                    :gate_pass_no, :requisitioner_id, :requisitioner_name, 
                    :requisitioning_office, :purpose, :pass_type, 'ISSUED', :created_by
                )
            ");
            $stmt->execute([
                ':gate_pass_no'           => $passNo,
                ':requisitioner_id'       => $data['requisitioner_id'] ?? null,
                ':requisitioner_name'     => $requisitionerName,
                ':requisitioning_office'  => $requisitioningOffice,
                ':purpose'                => $purpose,
                ':pass_type'              => $passType,
                ':created_by'             => $inspectorId,
            ]);

            $gatePassId = (int)$db->lastInsertId();

            $itemStmt = $db->prepare("
                INSERT INTO gate_pass_items (
                    gate_pass_id, item_id, transaction_id, item_description, 
                    model, serial_no, date_acquired, unit_price, total_amount, remarks
                ) VALUES (
                    :gate_pass_id, :item_id, :transaction_id, :item_description, 
                    :model, :serial_no, :date_acquired, :unit_price, :total_amount, :remarks
                )
            ");

            foreach ($items as $item) {
                $desc = trim($item['item_description'] ?? '');
                if ($desc === '') {
                    continue;
                }

                $unitPrice = (float)($item['unit_price'] ?? 0.00);
                $totalAmount = (float)($item['total_amount'] ?? $unitPrice);

                $itemStmt->execute([
                    ':gate_pass_id'     => $gatePassId,
                    ':item_id'          => !empty($item['item_id']) ? (int)$item['item_id'] : null,
                    ':transaction_id'   => !empty($item['transaction_id']) ? (int)$item['transaction_id'] : null,
                    ':item_description' => $desc,
                    ':model'            => !empty($item['model']) ? trim($item['model']) : null,
                    ':serial_no'        => !empty($item['serial_no']) ? trim($item['serial_no']) : null,
                    ':date_acquired'    => !empty($item['date_acquired']) ? $item['date_acquired'] : null,
                    ':unit_price'       => $unitPrice,
                    ':total_amount'     => $totalAmount,
                    ':remarks'          => !empty($item['remarks']) ? trim($item['remarks']) : 'Good condition',
                ]);
            }

            // Record in audit log
            AuditLog::record(
                $db,
                $inspectorId,
                'gate_pass_created',
                "Issued Gate Pass {$passNo} for {$requisitionerName} ({$requisitioningOffice}) containing " . count($items) . " item(s)."
            );

            $db->commit();

            return [
                'id'           => $gatePassId,
                'gate_pass_no' => $passNo,
            ];

        } catch (PDOException $e) {
            $db->rollBack();
            throw new RuntimeException("Database error during Gate Pass creation: " . $e->getMessage(), (int)$e->getCode(), $e);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Retrieve complete Gate Pass record with its child item snapshots.
     */
    public static function getById(PDO $db, int $id): ?array {
        $stmt = $db->prepare("
            SELECT gp.*, u.full_name AS inspector_name, u.email AS inspector_email
            FROM gate_passes gp
            LEFT JOIN users u ON gp.created_by = u.user_id
            WHERE gp.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $pass = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pass) {
            return null;
        }

        $itemsStmt = $db->prepare("
            SELECT gpi.*, i.barcode, i.property_number
            FROM gate_pass_items gpi
            LEFT JOIN items i ON gpi.item_id = i.item_id
            WHERE gpi.gate_pass_id = :pass_id
            ORDER BY gpi.id ASC
        ");
        $itemsStmt->execute([':pass_id' => $id]);
        $pass['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        return $pass;
    }

    /**
     * Retrieve paginated Gate Passes for the Data Table registry.
     * 
     * @return array{passes: array, total: int}
     */
    public static function listAll(PDO $db, string $search = '', int $limit = 20, int $offset = 0): array {
        $search = trim($search);
        $whereSql = "";
        $params = [];

        if ($search !== '') {
            $whereSql = "WHERE (gp.gate_pass_no LIKE :s1 OR gp.requisitioner_name LIKE :s2 OR gp.requisitioning_office LIKE :s3)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM gate_passes gp {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Data rows with item count and total valuation
        $sql = "
            SELECT 
                gp.*,
                COUNT(gpi.id) AS item_count,
                COALESCE(SUM(gpi.total_amount), 0.00) AS total_valuation,
                u.full_name AS inspector_name
            FROM gate_passes gp
            LEFT JOIN gate_pass_items gpi ON gp.id = gpi.gate_pass_id
            LEFT JOIN users u ON gp.created_by = u.user_id
            {$whereSql}
            GROUP BY gp.id
            ORDER BY gp.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'passes' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'  => $total,
        ];
    }
}
