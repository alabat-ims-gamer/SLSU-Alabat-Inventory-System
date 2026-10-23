<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\AuditLog;
use App\Middleware\RoleMiddleware;
use App\Helpers\Security;

class AuditLogController {

    /**
     * Display the Dedicated Security & Audit Trail Control Center
     */
    public static function index() {
        RoleMiddleware::allow(['admin', 'executive_admin']);
        
        $db = Database::getConnection();

        // 1. Ingest filters and pagination
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'action_type' => trim($_GET['action_type'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // 2. Fetch data
        $totalLogs = AuditLog::countFilteredLogs($db, $filters);
        $logs = AuditLog::getFilteredLogs($db, $filters, $limit, $offset);
        $actionTypes = AuditLog::getDistinctActionTypes($db);
        $stats = AuditLog::getSummaryStats($db);

        $totalPages = max(1, (int)ceil($totalLogs / $limit));

        // 3. Security token & View rendering
        Security::generateCsrfToken();
        $title = 'Security & Audit Trail';
        $breadcrumb = 'Audit Trail';

        require __DIR__ . '/../../views/admin/audit_logs.php';
    }
}
