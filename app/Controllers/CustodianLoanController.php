<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Security;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Config\Database;
use App\Middleware\RoleMiddleware;
use App\Services\BorrowLogsReportExporter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * CustodianLoanController — Borrow Logs & Custody Operations Center
 * Empowers Property Custodians and Executive Admins with centralized loan tracking,
 * overdue metrics, and institutional multi-tab spreadsheet exports.
 */
class CustodianLoanController {

    /**
     * Display the Borrow Logs & Loan Operations Center (3-Tab Interface)
     */
    public static function index(): void {
        // Enforce RBAC: Property Custodians, System Administrators, and Executive Admins
        RoleMiddleware::allow(['custodian', 'admin', 'executive_admin']);

        $db = Database::getConnection();

        // 1. Fetch live datasets for the 3 tabs
        $activeLoans         = Transaction::getAllActiveLoans($db);
        $pendingReservations = Transaction::getPendingReservations($db);
        $completedLoans      = Transaction::getCompletedLoanHistory($db, 50);

        // 2. Compute aggregate KPI metrics
        $totalActiveLoans = count($activeLoans);
        $overdueCount     = Transaction::getOverdueCount($db);
        $pendingHoldsCount= count($pendingReservations);

        // 3. Count due today
        $dueTodayCount = 0;
        foreach ($activeLoans as $loan) {
            if (!empty($loan['is_due_today'])) {
                $dueTodayCount++;
            }
        }

        Security::generateCsrfToken();

        // 4. Render the Operations Center View
        require __DIR__ . '/../../views/custodian/loans.php';
    }

    /**
     * Stream Export Borrow Logs as Multi-Sheet Microsoft Excel (.xlsx) Report
     * Creates 3 distinct worksheets: ACTIVE BORROWS, PENDING PICKUPS, and COMPLETED LOGS.
     */
    public static function exportXlsx(): void {
        RoleMiddleware::allow(['custodian', 'admin', 'executive_admin']);

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            AuditLog::log(
                $db,
                $userId,
                'BORROW_LOGS_EXPORT_XLSX',
                'Exported complete 3-sheet campus borrow logs and custody ledger as Excel report (.xlsx).'
            );
        } catch (Throwable $e) {
            // Non-blocking audit log catch
        }

        // Fetch datasets for all three tabs (completed loans unconstrained for full audit ledger)
        $activeLoans         = Transaction::getAllActiveLoans($db);
        $pendingReservations = Transaction::getPendingReservations($db);
        $completedLoans      = Transaction::getCompletedLoanHistory($db, 0);

        $exporter = new BorrowLogsReportExporter();
        $spreadsheet = $exporter->generate($activeLoans, $pendingReservations, $completedLoans);

        $filename = 'slsu_alabat_borrow_logs_' . date('Y-m-d_His') . '.xlsx';

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
