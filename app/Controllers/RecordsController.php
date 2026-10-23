<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\Transaction;
use App\Models\DamageIncident;
use App\Config\Database;
use App\Helpers\Security;

/**
 * RecordsController — Borrower Transaction Audit Ledger & Damage Clearance Center
 * Provides students and faculty with historical logs of equipment checkouts,
 * and empowers Property Custodians to manage incident holds and issue clearances.
 */
class RecordsController {

    /**
     * Display the Borrower Transaction Ledger view
     */
    public static function index(): void {
        AuthMiddleware::check();

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role   = $_SESSION['role'] ?? 'student';
        $db     = Database::getConnection();

        // 1. Sanitize query parameters
        $status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : 'all';
        if (!in_array($status, ['all', 'active', 'returned', 'overdue'], true)) {
            $status = 'all';
        }

        $search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        $period = isset($_GET['period']) ? strtolower(trim((string)$_GET['period'])) : 'all';
        if (!in_array($period, ['all', '30days', 'semester', 'year'], true)) {
            $period = 'all';
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;

        $filters = [
            'status' => $status,
            'q'      => $search,
            'period' => $period,
        ];

        // 2. Query aggregate stats and paginated records
        $stats = Transaction::getUserRecordStats($db, $userId);
        $result = Transaction::getUserRecordsPaged($db, $userId, $filters, $page, $perPage);

        $records     = $result['items'];
        $total       = $result['total'];
        $currentPage = $result['currentPage'];
        $totalPages  = $result['totalPages'];
        $pageSize    = $result['pageSize'];

        // 3. Render view
        require __DIR__ . '/../../views/records/index.php';
    }

    /**
     * Display the Clearance & Damage Incidents Console (/clearances)
     */
    public static function incidents(): void {
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        $db = Database::getConnection();
        $csrfToken = Security::generateCsrfToken();
        $title = 'Damage Incidents & Account Clearances';
        $breadcrumb = 'Clearances';

        $statusCounts       = DamageIncident::countByStatus($db);
        $pendingVerifCount  = $statusCounts['pending_verification'];
        $awaitingPayCount   = $statusCounts['awaiting_payment'];
        $partiallyPaidCount = $statusCounts['partially_paid'] ?? 0;
        $clearedCount       = $statusCounts['cleared'];
        $rejectedCount      = $statusCounts['rejected'];
        $totalCount         = $statusCounts['total'];
        $openHoldsCount     = $statusCounts['active_holds'];

        require __DIR__ . '/../../views/records/incidents.php';
    }
}

