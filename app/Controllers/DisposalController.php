<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Exception;
use Throwable;
use InvalidArgumentException;
use RuntimeException;
use App\Helpers\Security;
use App\Models\Item;
use App\Models\DisposalRecord;
use App\Models\AuditLog;
use App\Config\Database;
use App\Middleware\RoleMiddleware;
use App\Services\DisposalReportExporter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * DisposalController — Asset Condemnation & Decommissioning Command Center
 * 
 * Fulfills REVISE_RESEARCH.pdf (pp. 6, 21, 23, 40-43) lifecycle requirements.
 * Implements strict RBAC, OWASP security, and atomic state transitions.
 */
class DisposalController {

    /**
     * Helper to render standardized JSON responses
     */
    private static function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Display the 2-Tab Asset Disposal & Condemnation Console
     */
    public static function index(): void {
        RoleMiddleware::allow(['custodian', 'admin', 'executive_admin']);

        $db = Database::getConnection();
        $stats = DisposalRecord::getDisposalStats($db);
        $csrfToken = Security::generateCsrfToken();

        // Fetch distinct categories for filtering (merged with canonical categories)
        $catStmt = $db->query("
            SELECT DISTINCT category 
            FROM items 
            WHERE category IS NOT NULL AND category != '' 
            ORDER BY category ASC
        ");
        $dbCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $categories = array_values(array_unique(array_merge(Item::getStandardCategories(), $dbCategories)));
        sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

        // Fetch active items list for quick stage search dropdown (only available or maintenance)
        $activeItemsStmt = $db->query("
            SELECT item_id, barcode, property_number, item_name, category, location, status, item_condition
            FROM items
            WHERE status IN ('available', 'maintenance')
            ORDER BY item_name ASC
            LIMIT 300
        ");
        $activeItems = $activeItemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $methods = DisposalRecord::VALID_METHODS;
        $methodLabels = DisposalRecord::METHOD_LABELS;
        $methodBadges = DisposalRecord::METHOD_BADGE_CLASSES;

        require ROOT_PATH . '/views/disposal/index.php';
    }

    /**
     * Async JSON API for Staging Queue (status = 'for_disposal')
     */
    public static function listStaged(): void {
        RoleMiddleware::allow(['custodian', 'admin', 'executive_admin']);

        $db = Database::getConnection();

        $page     = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = max(1, min(100, (int)($_GET['pageSize'] ?? 15)));
        $search   = trim((string)($_GET['search'] ?? ''));
        $filters  = [
            'category' => trim((string)($_GET['category'] ?? '')),
        ];

        try {
            $data = DisposalRecord::getStagedList($db, $page, $pageSize, $search, $filters);
            self::jsonResponse([
                'success' => true,
                'data'    => $data
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve staged items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Async JSON API for Decommissioned Archive (status = 'disposed')
     */
    public static function listDisposed(): void {
        RoleMiddleware::allow(['custodian', 'admin', 'executive_admin']);

        $db = Database::getConnection();

        $page     = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = max(1, min(100, (int)($_GET['pageSize'] ?? 15)));
        $search   = trim((string)($_GET['search'] ?? ''));
        $filters  = [
            'category'  => trim((string)($_GET['category'] ?? '')),
            'method'    => trim((string)($_GET['method'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to'   => trim((string)($_GET['date_to'] ?? '')),
        ];

        try {
            $data = DisposalRecord::getDisposedList($db, $page, $pageSize, $search, $filters);
            self::jsonResponse([
                'success' => true,
                'data'    => $data
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve disposed items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stage an asset for disposal (POST /disposal/stage)
     */
    public static function stageItem(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            self::jsonResponse(['success' => false, 'message' => 'Security validation failed (Invalid CSRF Token).'], 403);
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($itemId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Please select a valid asset to stage.'], 422);
        }

        if (mb_strlen($reason) < 3) {
            self::jsonResponse(['success' => false, 'message' => 'A valid justification reason is required (minimum 3 characters).'], 422);
        }

        $db = Database::getConnection();
        try {
            $result = Item::stageForDisposal($db, $itemId, $reason, $userId);
            $stats = DisposalRecord::getDisposalStats($db);
            self::jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'item'    => $result['item'],
                'stats'   => $stats,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Decommission a single asset (POST /disposal/decommission)
     */
    public static function decommissionItem(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            self::jsonResponse(['success' => false, 'message' => 'Security validation failed (Invalid CSRF Token).'], 403);
        }

        $itemId   = (int)($_POST['item_id'] ?? 0);
        $method   = strtolower(trim((string)($_POST['disposal_method'] ?? 'scrapped')));
        $reason   = trim((string)($_POST['disposal_reason'] ?? ''));
        $salvage  = (float)($_POST['salvage_value'] ?? 0.00);
        $dateStr  = trim((string)($_POST['disposal_date'] ?? date('Y-m-d')));
        $remarks  = trim((string)($_POST['remarks'] ?? ''));
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        if ($itemId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Please select a valid asset to decommission.'], 422);
        }

        if (!in_array($method, DisposalRecord::VALID_METHODS, true)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid disposal method selected.'], 422);
        }

        if (mb_strlen($reason) < 3) {
            self::jsonResponse(['success' => false, 'message' => 'A detailed disposal reason/justification is required.'], 422);
        }

        $db = Database::getConnection();
        try {
            $disposalData = [
                'disposal_method' => $method,
                'disposal_reason' => $reason,
                'salvage_value'   => $salvage,
                'disposal_date'   => $dateStr,
                'remarks'         => $remarks,
            ];

            $result = Item::decommissionItem($db, $itemId, $disposalData, $userId);
            $stats = DisposalRecord::getDisposalStats($db);

            self::jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'item'    => $result['item'],
                'stats'   => $stats,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Batch decommission multiple staged assets (POST /disposal/batch-decommission)
     */
    public static function batchDecommission(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            self::jsonResponse(['success' => false, 'message' => 'Security validation failed (Invalid CSRF Token).'], 403);
        }

        $rawIds   = $_POST['item_ids'] ?? [];
        if (!is_array($rawIds) || empty($rawIds)) {
            self::jsonResponse(['success' => false, 'message' => 'No items were selected for batch decommissioning.'], 422);
        }

        $itemIds  = array_filter(array_map('intval', $rawIds));
        $method   = strtolower(trim((string)($_POST['disposal_method'] ?? 'scrapped')));
        $reason   = trim((string)($_POST['disposal_reason'] ?? ''));
        $salvage  = (float)($_POST['salvage_value'] ?? 0.00);
        $dateStr  = trim((string)($_POST['disposal_date'] ?? date('Y-m-d')));
        $remarks  = trim((string)($_POST['remarks'] ?? ''));
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        if (empty($itemIds)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid asset selection.'], 422);
        }

        if (!in_array($method, DisposalRecord::VALID_METHODS, true)) {
            self::jsonResponse(['success' => false, 'message' => 'Invalid disposal method selected.'], 422);
        }

        if (mb_strlen($reason) < 3) {
            self::jsonResponse(['success' => false, 'message' => 'A valid justification reason is required for batch disposal.'], 422);
        }

        $db = Database::getConnection();
        try {
            $disposalData = [
                'disposal_method' => $method,
                'disposal_reason' => $reason,
                'salvage_value'   => $salvage,
                'disposal_date'   => $dateStr,
                'remarks'         => $remarks,
            ];

            $result = Item::batchDecommission($db, $itemIds, $disposalData, $userId);
            $stats = DisposalRecord::getDisposalStats($db);

            self::jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'count'   => $result['count'],
                'stats'   => $stats,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Restore an asset from disposal back to active inventory (POST /disposal/restore)
     */
    public static function restoreItem(): void {
        RoleMiddleware::allow(['custodian', 'admin']);

        $csrfToken = (string)($_POST['csrf_token'] ?? '');
        if (!Security::validateCsrfToken($csrfToken)) {
            self::jsonResponse(['success' => false, 'message' => 'Security validation failed (Invalid CSRF Token).'], 403);
        }

        $itemId       = (int)($_POST['item_id'] ?? 0);
        $targetStatus = strtolower(trim((string)($_POST['target_status'] ?? 'available')));
        $reason       = trim((string)($_POST['restore_reason'] ?? ''));
        $userId       = (int)($_SESSION['user_id'] ?? 0);

        if ($itemId <= 0) {
            self::jsonResponse(['success' => false, 'message' => 'Please select a valid asset to restore.'], 422);
        }

        if (!in_array($targetStatus, ['available', 'maintenance'], true)) {
            $targetStatus = 'available';
        }

        if (mb_strlen($reason) < 3) {
            self::jsonResponse(['success' => false, 'message' => 'A detailed justification reason is required for restoration.'], 422);
        }

        $db = Database::getConnection();
        try {
            $result = Item::restoreFromDisposal($db, $itemId, $targetStatus, $reason, $userId);
            $stats = DisposalRecord::getDisposalStats($db);

            self::jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'item'    => $result['item'],
                'stats'   => $stats,
            ]);
        } catch (Throwable $e) {
            self::jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Stream Export Decommissioned Assets & Disposal Ledger as Microsoft Excel (.xlsx) Report
     * Recreates the exact layout, dimensions, typography, and signatories of FINAL_INVENTORY-REPORT.xlsx.
     */
    public static function exportXlsx(): void {
        RoleMiddleware::allow(['custodian', 'admin', 'executive_admin']);

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            AuditLog::log(
                $db,
                $userId,
                'DISPOSAL_EXPORT_XLSX',
                'Exported official SLSU-Alabat Decommissioned Assets & Disposal Ledger as Excel report (.xlsx).'
            );
        } catch (Throwable $e) {
            // Non-blocking audit log catch
        }

        $query = "
            SELECT 
                dr.id AS record_id,
                i.property_number,
                i.barcode,
                i.item_name,
                i.category,
                i.location,
                dr.disposal_method,
                dr.disposal_reason,
                dr.salvage_value,
                dr.disposal_date,
                u.full_name AS disposed_by_name,
                dr.remarks
            FROM items i
            LEFT JOIN disposal_records dr ON i.item_id = dr.item_id
            LEFT JOIN users u ON dr.disposed_by_user_id = u.user_id
            WHERE i.status = 'disposed'
            ORDER BY COALESCE(dr.disposal_date, i.updated_at) DESC, i.item_id DESC
        ";

        $stmt = $db->query($query);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $exporter = new DisposalReportExporter();
        $spreadsheet = $exporter->generate($records);

        $filename = 'slsu_alabat_decommissioned_assets_' . date('Y-m-d_His') . '.xlsx';

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
}
