<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Exception;
use Throwable;
use App\Helpers\Security;
use App\Models\Item;
use App\Models\AuditLog;
use App\Config\Database;
use App\Middleware\RoleMiddleware;
use App\Services\InventoryReportExporter;
use App\Services\EventDispatcher;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Inventory Controller — Asset Lifecycle & Catalog Control Center
 * Restricts access to Property Custodian and System Administrator roles.
 * Enforces Zero-Trust server-side validation, CSRF verification, and audit logging.
 */
class InventoryController {

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
     * Display the Asset Catalog Control Center (Main Table & Modals)
     */
    public static function index(): void {
        RoleMiddleware::allow(['custodian', 'executive_admin', 'faculty', 'student']);

        $db = Database::getConnection();
        $stats = Item::getStats($db);
        $csrfToken = Security::generateCsrfToken();

        // Distinct categories for filter dropdown (merged with canonical categories)
        $catStmt = $db->query("SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $dbCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $categories = array_values(array_unique(array_merge(Item::getStandardCategories(), $dbCategories)));
        sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

        // Transmitted IARs certified by Local Inspector awaiting Custodian asset registration
        $pendingIars = \App\Models\InspectionReport::getPendingIngestion($db);

        require __DIR__ . '/../../views/inventory/index.php';
    }

    /**
     * Async JSON API for DataTables search, filtering, and pagination
     */
    public static function listItems(): void {
        RoleMiddleware::allow(['custodian', 'executive_admin', 'faculty', 'student']);

        $db = Database::getConnection();

        $page     = (int)($_GET['page'] ?? 1);
        $pageSize = (int)($_GET['pageSize'] ?? 10);
        $search   = trim((string)($_GET['search'] ?? ''));
        $filters  = [
            'category'  => trim((string)($_GET['category'] ?? '')),
            'condition' => trim((string)($_GET['condition'] ?? '')),
            'status'    => trim((string)($_GET['status'] ?? '')),
        ];

        try {
            $data = Item::getPaginated($db, $page, $pageSize, $search, $filters);
            self::jsonResponse([
                'success' => true,
                'data'    => $data
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve asset records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream Export Inventory Catalog as Microsoft Excel (.xlsx) Report
     * 1-to-1 recreation of FINAL_INVENTORY-REPORT.xlsx across facility sheets.
     */
    public static function exportXlsx(): void {
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            AuditLog::log(
                $db,
                $userId,
                'INVENTORY_EXPORT_XLSX',
                'Exported complete institutional inventory catalog as 1-to-1 Excel report (.xlsx).'
            );
        } catch (Throwable $e) {
            // Non-blocking audit log catch
        }

        $query = "
            SELECT 
                i.item_id,
                i.barcode,
                i.property_number,
                i.item_name,
                i.model,
                i.serial_number,
                i.description,
                i.unit_cost,
                i.date_acquired,
                i.category,
                i.location,
                i.item_condition,
                i.status,
                i.is_student_borrowable,
                COALESCE(u.full_name, 'System') AS registered_by_name,
                i.created_at,
                i.updated_at
            FROM items i
            LEFT JOIN users u ON i.registered_by = u.user_id
            WHERE i.status NOT IN ('for_disposal', 'disposed')
            ORDER BY i.location ASC, i.item_id ASC
        ";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $exporter = new InventoryReportExporter();
        $spreadsheet = $exporter->generate($items);

        $filename = 'slsu_alabat_inventory_catalog_' . date('Y-m-d_His') . '.xlsx';

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
    public static function exportCsv(): void {
        self::exportXlsx();
    }


    /**
     * Check if a property number already exists (Live conflict debounce)
     */
    public static function checkPropertyNumber(): void {
        RoleMiddleware::allow(['custodian']);

        $propNum = trim((string)($_GET['property_number'] ?? ''));
        if ($propNum === '') {
            self::jsonResponse(['exists' => false]);
        }

        $db = Database::getConnection();
        $existing = Item::findByPropertyNumber($db, $propNum);

        if ($existing) {
            self::jsonResponse([
                'exists'     => true,
                'registrant' => $existing['registered_by_name'] ?? 'Another Custodian',
                'date'       => date('M d, Y', strtotime($existing['created_at'])),
                'item_name'  => $existing['item_name'],
                'item_id'    => (int)$existing['item_id'],
            ]);
        }

        self::jsonResponse(['exists' => false]);
    }

    /**
     * Register a new asset (Single or Multiplier Batch)
     */
    public static function register(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Security validation error: Invalid or expired CSRF token.'
            ], 403);
        }

        $itemName = trim((string)($_POST['item_name'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($itemName === '' || $category === '' || $location === '') {
            self::jsonResponse([
                'success' => false,
                'message' => 'Item Name, Category, and Storage Location are required fields.'
            ], 422);
        }

        if ($quantity < 1 || $quantity > 100) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Quantity multiplier must be between 1 and 100.'
            ], 422);
        }

        $itemCondition = strtolower(trim((string)($_POST['item_condition'] ?? 'mint')));
        if (in_array($itemCondition, ['not_working', 'for_disposal', 'condemned'], true)) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Newly registered assets cannot be assigned a "Not Working" or "Condemned" condition.'
            ], 422);
        }

        $payload = [
            'item_name'             => $itemName,
            'category'              => $category,
            'location'              => $location,
            'property_number'       => trim((string)($_POST['property_number'] ?? '')),
            'description'           => trim((string)($_POST['description'] ?? '')),
            'item_condition'        => $itemCondition,
            'is_student_borrowable' => !empty($_POST['is_student_borrowable']) ? 1 : 0,
        ];

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $createdItems = Item::createBatch($db, $payload, $quantity, $userId);
            
            // Record audit log
            $count = count($createdItems);
            $summaryDesc = $count === 1
                ? "Registered new asset '{$itemName}' ({$createdItems[0]['barcode']})"
                : "Batch registered {$count} units of '{$itemName}' (Barcodes: {$createdItems[0]['barcode']} - " . end($createdItems)['barcode'] . ")";
            
            AuditLog::log($db, $userId, 'ITEM_REGISTER', $summaryDesc);

            // Real-Time Broadcast: Catalog expansion & newly created items
            foreach ($createdItems as $ci) {
                EventDispatcher::dispatch(
                    $db,
                    EventDispatcher::EVENT_ITEM_MUTATED,
                    'item',
                    (int)$ci['item_id'],
                    [
                        'action'    => 'registered',
                        'item_id'   => (int)$ci['item_id'],
                        'item_name' => $ci['item_name'],
                        'barcode'   => $ci['barcode'],
                        'status'    => 'available',
                        'category'  => $ci['category'],
                        'location'  => $ci['location']
                    ]
                );
            }

            self::jsonResponse([
                'success'       => true,
                'message'       => $count === 1 ? 'Asset registered successfully.' : "Successfully registered {$count} assets.",
                'created_items' => $createdItems,
                'stats'         => Item::getStats($db),
            ], 201);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Edit asset metadata & physical condition with Optimistic Concurrency Protection
     */
    public static function edit(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Security validation error: Invalid or expired CSRF token.'
            ], 403);
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId <= 0) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Invalid asset ID.'
            ], 422);
        }

        $itemName = trim((string)($_POST['item_name'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));

        if ($itemName === '' || $category === '' || $location === '') {
            self::jsonResponse([
                'success' => false,
                'message' => 'Item Name, Category, and Storage Location are required fields.'
            ], 422);
        }

        $payload = [
            'item_name'             => $itemName,
            'category'              => $category,
            'location'              => $location,
            'property_number'       => trim((string)($_POST['property_number'] ?? '')),
            'description'           => trim((string)($_POST['description'] ?? '')),
            'item_condition'        => trim((string)($_POST['item_condition'] ?? 'good')),
            'is_student_borrowable' => !empty($_POST['is_student_borrowable']) ? 1 : 0,
            'transfer_reason'       => trim((string)($_POST['transfer_reason'] ?? 'Reallocation / Location Update')),
        ];

        $originalUpdatedAt = $_POST['original_updated_at'] ?? null;
        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $result = Item::updateItem($db, $itemId, $payload, $originalUpdatedAt, $userId);

            if ($result['conflict']) {
                self::jsonResponse([
                    'success'  => false,
                    'conflict' => true,
                    'item'     => $result['item'],
                    'message'  => $result['message'],
                ], 409);
            }

            if (!$result['success']) {
                self::jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            AuditLog::log(
                $db,
                $userId,
                'ITEM_UPDATE',
                "Updated metadata for asset ID {$itemId} ('{$itemName}')"
            );

            // Real-Time Broadcast: Asset metadata and condition update
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_MUTATED,
                'item',
                $itemId,
                [
                    'action'         => 'updated',
                    'item_id'        => $itemId,
                    'item_name'      => $itemName,
                    'category'       => $category,
                    'location'       => $location,
                    'item_condition' => $payload['item_condition'],
                    'is_student_borrowable' => $payload['is_student_borrowable']
                ]
            );

            self::jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'item'    => $result['item'],
                'stats'   => Item::getStats($db),
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Failed to update asset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Bulk Spreadsheet (CSV) Intake
     */
    public static function importCsv(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Security validation error: Invalid or expired CSRF token.'
            ], 403);
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Please choose a valid CSV file to upload.'
            ], 422);
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv' && $ext !== 'txt') {
            self::jsonResponse([
                'success' => false,
                'message' => 'Invalid file extension. Please upload a standard CSV file (.csv).'
            ], 422);
        }

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $result = Item::importCsv($db, $file, $userId);

            AuditLog::log(
                $db,
                $userId,
                'ITEM_CSV_IMPORT',
                "Imported {$result['imported_count']} assets via bulk CSV spreadsheet"
            );

            self::jsonResponse([
                'success'        => true,
                'imported_count' => $result['imported_count'],
                'message'        => $result['message'],
                'stats'          => Item::getStats($db),
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete or Decommission an asset (Authorized for Property Custodians only)
     */
    public static function delete(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Security validation error: Invalid or expired CSRF token.'
            ], 403);
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId <= 0) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Invalid asset ID.'
            ], 422);
        }

        $reason = trim((string)($_POST['reason'] ?? ''));
        if (mb_strlen($reason) < 3) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Please provide a valid justification reason for deleting or decommissioning this asset (minimum 3 characters).'
            ], 422);
        }

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $result = Item::deleteItem($db, $itemId, $reason, $userId);

            // Real-Time Broadcast: Deletion / condemnation
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_MUTATED,
                'item',
                $itemId,
                [
                    'action'  => 'deleted',
                    'item_id' => $itemId,
                    'type'    => $result['type'],
                    'status'  => 'for_disposal'
                ]
            );

            self::jsonResponse([
                'success' => true,
                'type'    => $result['type'],
                'message' => $result['message'],
                'stats'   => Item::getStats($db),
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Restore an asset from maintenance status back to available.
     */
    public static function restore(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired CSRF token.'], 403);
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        $condition = trim((string)($_POST['condition'] ?? 'good'));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $db = Database::getConnection();
            $result = Item::restoreFromMaintenance($db, $itemId, $condition, $notes, $userId);
            $result['stats'] = Item::getStats($db);

            // Real-Time Broadcast: Restored to available
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_STATUS_CHANGED,
                'item',
                $itemId,
                [
                    'item_id'        => $itemId,
                    'status'         => 'available',
                    'item_condition' => $condition
                ]
            );

            self::jsonResponse($result);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Declare an asset Beyond Economic Repair (B.E.R.) and queue for disposal.
     */
    public static function declareBer(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired CSRF token.'], 403);
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $db = Database::getConnection();
            $result = Item::declareBer($db, $itemId, $reason, $userId);
            $result['stats'] = Item::getStats($db);

            // Real-Time Broadcast: Shifted to for_disposal
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_ITEM_STATUS_CHANGED,
                'item',
                $itemId,
                [
                    'item_id' => $itemId,
                    'status'  => 'for_disposal'
                ]
            );

            self::jsonResponse($result);
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * API: Get list of transmitted IARs pending asset catalog ingestion.
     */
    public static function apiPendingIar(): void {
        RoleMiddleware::allow(['custodian', 'admin', 'executive_admin']);
        $db = Database::getConnection();
        $pending = \App\Models\InspectionReport::getPendingIngestion($db);

        foreach ($pending as &$iar) {
            $report = \App\Models\InspectionReport::getById($db, (int)$iar['id']);
            $iar['items'] = $report['items'] ?? [];
        }
        unset($iar);

        self::jsonResponse(['status' => 'success', 'pending' => $pending]);
    }

    /**
     * API: Custodian ingests items from a certified IAR into the live master inventory.
     */
    public static function apiIngestIar(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid or expired CSRF token.'], 403);
        }

        $db = Database::getConnection();
        $custodianId = (int)($_SESSION['user_id'] ?? 0);
        $iarId = (int)($_POST['iar_id'] ?? 0);

        if ($iarId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid IAR identifier.'], 400);
        }

        $report = \App\Models\InspectionReport::getById($db, $iarId);
        if (!$report) {
            self::jsonResponse(['success' => false, 'message' => 'Inspection Report not found.'], 404);
        }

        if ($report['status'] !== \App\Models\InspectionReport::STATUS_TRANSMITTED && $report['status'] !== \App\Models\InspectionReport::STATUS_REGISTERED) {
            self::jsonResponse(['success' => false, 'message' => 'Only IARs with status TRANSMITTED can be ingested into inventory.'], 422);
        }

        $rawItems = $_POST['items'] ?? [];
        if (is_string($rawItems)) {
            $items = json_decode($rawItems, true);
        } else {
            $items = $rawItems;
        }

        if (empty($items) || !is_array($items)) {
            self::jsonResponse(['success' => false, 'message' => 'Please provide at least one asset to register.'], 422);
        }

        $wasInTransaction = $db->inTransaction();
        if (!$wasInTransaction) {
            $db->beginTransaction();
        }

        try {
            $registrations = [];
            $registeredCount = 0;

            foreach ($items as $itemData) {
                $lineId = (int)($itemData['line_id'] ?? 0);
                $itemName = trim((string)($itemData['item_name'] ?? ''));
                if ($itemName === '') {
                    continue;
                }

                $category = trim((string)($itemData['category'] ?? 'IT Equipment'));
                $description = trim((string)($itemData['description'] ?? ''));
                $model = !empty($itemData['model']) ? trim((string)$itemData['model']) : null;
                $serialNumber = !empty($itemData['serial_number']) ? trim((string)$itemData['serial_number']) : null;
                $location = trim((string)($itemData['location'] ?? 'Supply Office'));
                $unitCost = max(0.0, (float)($itemData['unit_cost'] ?? 0.0));
                $dateAcquired = !empty($itemData['date_acquired']) ? trim((string)$itemData['date_acquired']) : $report['date_received'];
                $isStudent = !empty($itemData['is_student_borrowable']) ? 1 : 0;
                $propertyNum = trim((string)($itemData['property_number'] ?? ''));

                // Barcode assignment
                $barcode = trim((string)($itemData['barcode'] ?? ''));
                if ($barcode === '') {
                    $barcode = Item::generateBarcode($db);
                }

                // Insert into items table
                $insertStmt = $db->prepare("
                    INSERT INTO items (
                        item_name, category, description, model, serial_number,
                        barcode, property_number, unit_cost, date_acquired,
                        location, status, item_condition, is_student_borrowable,
                        registered_by, created_at
                    ) VALUES (
                        :item_name, :category, :description, :model, :serial_number,
                        :barcode, :property_number, :unit_cost, :date_acquired,
                        :location, 'available', 'good', :is_student_borrowable,
                        :registered_by, NOW()
                    )
                ");

                $insertStmt->execute([
                    ':item_name'              => $itemName,
                    ':category'               => $category,
                    ':description'            => $description,
                    ':model'                  => $model,
                    ':serial_number'          => $serialNumber,
                    ':barcode'                => $barcode,
                    ':property_number'        => $propertyNum !== '' ? $propertyNum : null,
                    ':unit_cost'              => $unitCost,
                    ':date_acquired'          => $dateAcquired,
                    ':location'               => $location,
                    ':is_student_borrowable'  => $isStudent,
                    ':registered_by'          => $custodianId,
                ]);

                $newItemId = (int)$db->lastInsertId();
                $registeredCount++;

                if ($lineId > 0) {
                    $registrations[] = [
                        'line_id' => $lineId,
                        'item_id' => $newItemId,
                    ];
                }
            }

            // Update IAR line items and status
            if (!empty($registrations)) {
                \App\Models\InspectionReport::markItemsRegistered($db, $iarId, $registrations);
            }

            if (!$wasInTransaction) {
                $db->commit();
            }

            // Audit log
            try {
                AuditLog::log($db, $custodianId, 'INGEST_IAR_ASSETS', "Ingested {$registeredCount} items from IAR {$report['iar_no']} into live asset catalog", 'inspection_reports', $iarId);
            } catch (\Throwable $e) {}

            self::jsonResponse([
                'success'          => true,
                'registered_count' => $registeredCount,
                'message'          => "Successfully registered {$registeredCount} asset(s) from IAR {$report['iar_no']} into the catalog!",
                'stats'            => Item::getStats($db),
            ]);

        } catch (Throwable $e) {
            if (!$wasInTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[InventoryController::apiIngestIar] Error: ' . $e->getMessage());
            self::jsonResponse(['success' => false, 'message' => 'Ingestion error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Report an asset as Lost/Missing under investigation
     */
    public static function reportLost(): void {
        RoleMiddleware::allow(['custodian']);

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($csrf)) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Security validation error: Invalid or expired CSRF token.'
            ], 403);
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid asset ID.'], 422);
        }

        $person = trim((string)($_POST['accountable_person'] ?? ''));
        $desc   = trim((string)($_POST['description'] ?? ''));
        $date   = trim((string)($_POST['date_reported'] ?? date('Y-m-d')));
        $status = trim((string)($_POST['investigation_status'] ?? 'under_investigation'));

        if ($desc === '') {
            self::jsonResponse(['success' => false, 'message' => 'Incident description / findings is required.'], 422);
        }

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $success = Item::reportLost($db, $itemId, [
                'accountable_person'   => $person !== '' ? $person : 'Unassigned / General Custody',
                'date_reported'        => $date !== '' ? $date : date('Y-m-d'),
                'description'          => $desc,
                'investigation_status' => $status,
            ], $userId);

            if ($success) {
                // Real-Time Broadcast: Shifted to lost / missing
                EventDispatcher::dispatch(
                    $db,
                    EventDispatcher::EVENT_ITEM_STATUS_CHANGED,
                    'item',
                    $itemId,
                    [
                        'item_id' => $itemId,
                        'status'  => 'lost'
                    ]
                );

                self::jsonResponse([
                    'success' => true,
                    'message' => 'Asset status updated to Lost / Missing under investigation.',
                    'stats'   => Item::getStats($db),
                ]);
            } else {
                self::jsonResponse(['success' => false, 'message' => 'Asset not found or failed to record.'], 404);
            }
        } catch (Throwable $e) {
            self::jsonResponse(['success' => false, 'message' => 'Error reporting lost asset: ' . $e->getMessage()], 500);
        }
    }
}

