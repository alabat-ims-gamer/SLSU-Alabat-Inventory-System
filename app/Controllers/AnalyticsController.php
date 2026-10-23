<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\Item;
use PDO;
use Exception;
use RuntimeException;

/**
 * Class AnalyticsController
 *
 * Provides high-performance server-side data aggregation, REST API endpoints,
 * CSV report streaming with formula injection protection, and print-ready executive briefings.
 */
class AnalyticsController {

    /**
     * Display the Reports & Property Analytics Studio (/reports)
     * Accessible to Property Custodians and Executive Administrators.
     */
    public static function index(): void {
        AuthMiddleware::check();
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        $role = $_SESSION['role'] ?? 'custodian';

        // Executive Admins are consolidated under the Executive Overview cockpit (/dashboard)
        if ($role === 'executive_admin') {
            redirect('/dashboard');
            return;
        }

        $db = Database::getConnection();
        $title = 'Reports & Property Analytics';
        $breadcrumb = 'Reports';

        $categoriesList = self::getAvailableCategories($db);
        $locationsList  = self::getAvailableLocations($db);

        require __DIR__ . '/../../views/reports/index.php';
    }

    /**
     * REST Endpoint: GET /api/analytics/executive
     * Returns consolidated JSON analytics for both Custodians and Executive Admins.
     */
    public static function getExecutiveData(): void {
        AuthMiddleware::check();
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        header('Content-Type: application/json; charset=utf-8');

        try {
            $db = Database::getConnection();

            $timeframe = trim($_GET['timeframe'] ?? '30d');
            $category = trim($_GET['category'] ?? '');
            $location = trim($_GET['location'] ?? '');
            $startDate = trim($_GET['start_date'] ?? '');
            $endDate = trim($_GET['end_date'] ?? '');
            $perspective = trim($_GET['perspective'] ?? 'all');

            $data = self::computeAnalytics($db, $timeframe, $category, $location, $startDate, $endDate, $perspective);

            echo json_encode([
                'success' => true,
                'filters' => [
                    'timeframe'  => $timeframe,
                    'category'   => $category,
                    'location'   => $location,
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
                'data' => $data,
            ], JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load executive analytics: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Compute comprehensive multi-perspective analytical datasets.
     */
    public static function computeAnalytics(
        PDO $db,
        string $timeframe = '30d',
        string $category = '',
        string $location = '',
        string $startDate = '',
        string $endDate = '',
        string $perspective = 'all'
    ): array {
        $dateFilter = self::resolveDateFilter($timeframe, $startDate, $endDate);
        
        // 1. Compute Base Item/Asset Statistics with Category and Location Scoping
        $itemWhere = [];
        $itemParams = [];

        if ($category !== '' && $category !== 'all') {
            $itemWhere[] = "category = :category";
            $itemParams[':category'] = $category;
        }
        if ($location !== '' && $location !== 'all') {
            $itemWhere[] = "location = :location";
            $itemParams[':location'] = $location;
        }

        $itemWhereClause = !empty($itemWhere) ? ' WHERE ' . implode(' AND ', $itemWhere) : '';

        // Status Distribution
        $statusStmt = $db->prepare("
            SELECT status, COUNT(*) as cnt 
            FROM items 
            {$itemWhereClause} 
            GROUP BY status
        ");
        $statusStmt->execute($itemParams);
        $statusRows = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $totalItems = array_sum($statusRows);
        $availableCount = (int)($statusRows['available'] ?? 0);
        $borrowedCount = (int)($statusRows['borrowed'] ?? 0);
        $maintenanceCount = (int)($statusRows['maintenance'] ?? 0);
        $forDisposalCount = (int)($statusRows['for_disposal'] ?? 0);
        $disposedCount = (int)($statusRows['disposed'] ?? 0);

        // Condition Distribution
        $condStmt = $db->prepare("
            SELECT item_condition, COUNT(*) as cnt 
            FROM items 
            {$itemWhereClause} 
            GROUP BY item_condition
        ");
        $condStmt->execute($itemParams);
        $condRows = $condStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $mintCount = (int)($condRows['mint'] ?? 0);
        $goodCount = (int)($condRows['good'] ?? 0);
        $fairCount = (int)($condRows['fair'] ?? 0);
        $damagedCount = (int)($condRows['damaged'] ?? 0);
        $notWorkingCount = (int)($condRows['not_working'] ?? 0);
        $condDisposalCount = (int)($condRows['for_disposal'] ?? 0);

        $serviceableCount = $mintCount + $goodCount;
        $availabilityRate = $totalItems > 0 ? round(($availableCount / $totalItems) * 100, 1) : 0;
        $serviceabilityRate = $totalItems > 0 ? round(($serviceableCount / $totalItems) * 100, 1) : 0;

        // Top Categories
        $catStmt = $db->prepare("
            SELECT category, COUNT(*) as cnt 
            FROM items 
            " . ($location !== '' && $location !== 'all' ? "WHERE location = :loc" : "") . "
            GROUP BY category 
            ORDER BY cnt DESC 
            LIMIT 7
        ");
        if ($location !== '' && $location !== 'all') {
            $catStmt->bindValue(':loc', $location, PDO::PARAM_STR);
        }
        $catStmt->execute();
        $topCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Department/Location Allocation
        $locStmt = $db->prepare("
            SELECT location, COUNT(*) as cnt 
            FROM items 
            " . ($category !== '' && $category !== 'all' ? "WHERE category = :cat" : "") . "
            GROUP BY location 
            ORDER BY cnt DESC 
            LIMIT 7
        ");
        if ($category !== '' && $category !== 'all') {
            $locStmt->bindValue(':cat', $category, PDO::PARAM_STR);
        }
        $locStmt->execute();
        $topLocations = $locStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 2. Circulation & Transaction Velocity
        $txnWhere = ["t.transaction_date BETWEEN :txn_start AND :txn_end"];
        $txnParams = [
            ':txn_start' => $dateFilter['start'],
            ':txn_end'   => $dateFilter['end'] . ' 23:59:59',
        ];

        if ($category !== '' && $category !== 'all') {
            $txnWhere[] = "i.category = :txn_cat";
            $txnParams[':txn_cat'] = $category;
        }
        if ($location !== '' && $location !== 'all') {
            $txnWhere[] = "i.location = :txn_loc";
            $txnParams[':txn_loc'] = $location;
        }

        $txnWhereClause = implode(' AND ', $txnWhere);

        // Transaction Velocity Time-Series
        $isDaily = in_array($timeframe, ['7d', '30d'], true) || ($dateFilter['days_diff'] <= 35);
        $timeFormat = $isDaily ? '%Y-%m-%d' : '%Y-%m';
        $displayFormat = $isDaily ? 'M j' : 'M Y';

        $velocityStmt = $db->prepare("
            SELECT 
                DATE_FORMAT(t.transaction_date, '{$timeFormat}') as time_bucket,
                SUM(CASE WHEN t.transaction_type = 'borrow' THEN 1 ELSE 0 END) as borrow_cnt,
                SUM(CASE WHEN t.transaction_type = 'return' THEN 1 ELSE 0 END) as return_cnt
            FROM transactions t
            INNER JOIN items i ON t.item_id = i.item_id
            WHERE {$txnWhereClause}
            GROUP BY time_bucket
            ORDER BY time_bucket ASC
        ");
        $velocityStmt->execute($txnParams);
        $velocityRows = $velocityStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $velocityMap = [];
        foreach ($velocityRows as $vr) {
            $velocityMap[$vr['time_bucket']] = [
                'borrows' => (int)$vr['borrow_cnt'],
                'returns' => (int)$vr['return_cnt'],
            ];
        }

        // Generate complete time-series buckets
        $velocityLabels = [];
        $velocityBorrows = [];
        $velocityReturns = [];

        if ($isDaily) {
            $curr = new \DateTime($dateFilter['start']);
            $endObj = new \DateTime($dateFilter['end']);
            while ($curr <= $endObj) {
                $k = $curr->format('Y-m-d');
                $velocityLabels[] = $curr->format($displayFormat);
                $velocityBorrows[] = $velocityMap[$k]['borrows'] ?? 0;
                $velocityReturns[] = $velocityMap[$k]['returns'] ?? 0;
                $curr->modify('+1 day');
            }
        } else {
            $curr = new \DateTime($dateFilter['start']);
            $curr->modify('first day of this month');
            $endObj = new \DateTime($dateFilter['end']);
            $endObj->modify('last day of this month');
            while ($curr <= $endObj) {
                $k = $curr->format('Y-m');
                $velocityLabels[] = $curr->format($displayFormat);
                $velocityBorrows[] = $velocityMap[$k]['borrows'] ?? 0;
                $velocityReturns[] = $velocityMap[$k]['returns'] ?? 0;
                $curr->modify('+1 month');
            }
        }

        // Total Loans and Returns in Timeframe
        $totalLoansInPeriod = array_sum($velocityBorrows);
        $totalReturnsInPeriod = array_sum($velocityReturns);

        // Borrower Demographics (Faculty vs Student)
        $borrowerRoleStmt = $db->prepare("
            SELECT u.role, COUNT(t.transaction_id) as cnt
            FROM transactions t
            INNER JOIN users u ON t.user_id = u.user_id
            INNER JOIN items i ON t.item_id = i.item_id
            WHERE t.transaction_type = 'borrow' AND {$txnWhereClause}
            GROUP BY u.role
        ");
        $borrowerRoleStmt->execute($txnParams);
        $borrowerRoles = $borrowerRoleStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $facultyLoans = 0;
        $studentLoans = 0;
        foreach ($borrowerRoles as $br) {
            if ($br['role'] === 'faculty') {
                $facultyLoans += (int)$br['cnt'];
            } elseif ($br['role'] === 'student') {
                $studentLoans += (int)$br['cnt'];
            }
        }

        // Day of Week Demand Heatmap/Bar
        $dowStmt = $db->prepare("
            SELECT DAYOFWEEK(t.transaction_date) as dow, COUNT(*) as cnt
            FROM transactions t
            INNER JOIN items i ON t.item_id = i.item_id
            WHERE t.transaction_type = 'borrow' AND {$txnWhereClause}
            GROUP BY dow
            ORDER BY dow ASC
        ");
        $dowStmt->execute($txnParams);
        $dowRows = $dowStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $daysOfWeek = [
            1 => 'Sun', 2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat'
        ];
        $dowLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $dowOrder = [2, 3, 4, 5, 6, 7, 1];
        $dowData = [];
        foreach ($dowOrder as $dIdx) {
            $dowData[] = (int)($dowRows[$dIdx] ?? 0);
        }

        // Active Borrowers & Overdue Loans
        $activeBorrowersCount = (int)$db->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM transactions 
            WHERE transaction_type = 'borrow' AND actual_return_date IS NULL
        ")->fetchColumn();

        $overdueCount = (int)$db->query("
            SELECT COUNT(*) 
            FROM transactions 
            WHERE transaction_type = 'borrow' 
              AND actual_return_date IS NULL 
              AND expected_return_date < NOW()
        ")->fetchColumn();

        // 3. Incidents & Accountability
        $incWhere = ["di.created_at BETWEEN :inc_start AND :inc_end"];
        $incParams = [
            ':inc_start' => $dateFilter['start'] . ' 00:00:00',
            ':inc_end'   => $dateFilter['end'] . ' 23:59:59',
        ];

        if ($category !== '' && $category !== 'all') {
            $incWhere[] = "i.category = :inc_cat";
            $incParams[':inc_cat'] = $category;
        }
        if ($location !== '' && $location !== 'all') {
            $incWhere[] = "i.location = :inc_loc";
            $incParams[':inc_loc'] = $location;
        }
        $incWhereClause = implode(' AND ', $incWhere);

        // Damage Severity Breakdown
        $sevStmt = $db->prepare("
            SELECT di.damage_condition, COUNT(*) as cnt
            FROM damage_incidents di
            INNER JOIN items i ON di.item_id = i.item_id
            WHERE {$incWhereClause}
            GROUP BY di.damage_condition
        ");
        $sevStmt->execute($incParams);
        $sevRows = $sevStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // Settlement Methods Breakdown
        $settleStmt = $db->prepare("
            SELECT di.settlement_type, COUNT(*) as cnt
            FROM damage_incidents di
            INNER JOIN items i ON di.item_id = i.item_id
            WHERE di.incident_status = 'cleared' AND {$incWhereClause}
            GROUP BY di.settlement_type
        ");
        $settleStmt->execute($incParams);
        $settleRows = $settleStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // Financial & Resolution Stats
        $finStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_incidents,
                SUM(CASE WHEN di.incident_status != 'cleared' THEN 1 ELSE 0 END) as open_holds,
                SUM(CASE WHEN di.incident_status = 'cleared' THEN 1 ELSE 0 END) as cleared_holds,
                SUM(COALESCE(di.estimated_cost, 0)) as total_estimated_cost,
                SUM(CASE WHEN di.incident_status = 'cleared' THEN COALESCE(di.amount_paid, di.estimated_cost, 0) ELSE 0 END) as total_cleared_cost,
                AVG(CASE WHEN di.incident_status = 'cleared' AND di.cleared_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, di.created_at, di.cleared_at) / 24.0 
                    ELSE NULL END) as avg_resolution_days
            FROM damage_incidents di
            INNER JOIN items i ON di.item_id = i.item_id
            WHERE {$incWhereClause}
        ");
        $finStmt->execute($incParams);
        $finRow = $finStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalIncidents = (int)($finRow['total_incidents'] ?? 0);
        $openDamageHolds = (int)($finRow['open_holds'] ?? 0);
        $clearedDamageHolds = (int)($finRow['cleared_holds'] ?? 0);
        $totalDamageCost = (float)($finRow['total_estimated_cost'] ?? 0.0);
        $totalClearedCost = (float)($finRow['total_cleared_cost'] ?? 0.0);
        $avgResolutionDays = $finRow['avg_resolution_days'] !== null ? round((float)$finRow['avg_resolution_days'], 1) : 0.0;

        // Return unified payload
        return [
            'kpi' => [
                'total_assets'          => $totalItems,
                'available_assets'      => $availableCount,
                'availability_rate'     => $availabilityRate,
                'serviceable_assets'    => $serviceableCount,
                'serviceability_rate'   => $serviceabilityRate,
                'borrowed_assets'       => $borrowedCount,
                'maintenance_assets'    => $maintenanceCount,
                'for_disposal_assets'   => $forDisposalCount,
                'disposed_assets'       => $disposedCount,
                'active_borrowers'      => $activeBorrowersCount,
                'overdue_count'         => $overdueCount,
                'period_loans'          => $totalLoansInPeriod,
                'period_returns'        => $totalReturnsInPeriod,
                'open_damage_holds'     => $openDamageHolds,
                'cleared_damage_holds'  => $clearedDamageHolds,
                'total_damage_cost'     => $totalDamageCost,
                'total_cleared_cost'    => $totalClearedCost,
                'avg_resolution_days'   => $avgResolutionDays,
            ],
            'asset_dynamics' => [
                'status_distribution' => [
                    'labels' => ['Available', 'Borrowed', 'Maintenance', 'For Disposal', 'Disposed'],
                    'data'   => [$availableCount, $borrowedCount, $maintenanceCount, $forDisposalCount, $disposedCount],
                ],
                'condition_distribution' => [
                    'labels' => ['Mint', 'Good', 'Fair', 'Damaged', 'Not Working', 'For Disposal'],
                    'data'   => [$mintCount, $goodCount, $fairCount, $damagedCount, $notWorkingCount, $condDisposalCount],
                ],
                'top_categories' => [
                    'labels' => array_column($topCategories, 'category'),
                    'data'   => array_map('intval', array_column($topCategories, 'cnt')),
                ],
                'top_locations' => [
                    'labels' => array_column($topLocations, 'location'),
                    'data'   => array_map('intval', array_column($topLocations, 'cnt')),
                ],
            ],
            'circulation' => [
                'velocity_series' => [
                    'labels'  => $velocityLabels,
                    'borrows' => $velocityBorrows,
                    'returns' => $velocityReturns,
                ],
                'borrower_demographics' => [
                    'labels' => ['Faculty Borrows', 'Student Borrows'],
                    'data'   => [$facultyLoans, $studentLoans],
                ],
                'dow_demand' => [
                    'labels' => $dowLabels,
                    'data'   => $dowData,
                ],
            ],
            'incidents' => [
                'severity_distribution' => [
                    'labels' => ['Damaged (Repairable)', 'Not Working (Heavy)'],
                    'data'   => [(int)($sevRows['damaged'] ?? 0), (int)($sevRows['not_working'] ?? 0)],
                ],
                'settlement_distribution' => [
                    'labels' => ['In-Kind Replacement', 'Repair Cost Settled', 'Admin Waiver'],
                    'data'   => [
                        (int)($settleRows['in_kind_replacement'] ?? 0),
                        (int)($settleRows['repair_cost'] ?? 0),
                        (int)($settleRows['admin_waiver'] ?? 0)
                    ],
                ],
                'financial_summary' => [
                    'total_cost'   => $totalDamageCost,
                    'cleared_cost' => $totalClearedCost,
                    'pending_cost' => max(0, $totalDamageCost - $totalClearedCost),
                ],
            ],
            'metadata' => [
                'timeframe_label' => $dateFilter['label'],
                'generated_at'    => date('Y-m-d H:i:s'),
                'categories'      => self::getAvailableCategories($db),
                'locations'       => self::getAvailableLocations($db),
            ],
        ];
    }

    /**
     * Get distinct available categories for filter dropdown (merged with canonical categories).
     */
    public static function getAvailableCategories(PDO $db): array {
        $stmt = $db->query("
            SELECT DISTINCT category 
            FROM items 
            WHERE category IS NOT NULL AND category != '' 
            ORDER BY category ASC
        ");
        $dbCategories = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $merged = array_values(array_unique(array_merge(Item::getStandardCategories(), $dbCategories)));
        sort($merged, SORT_NATURAL | SORT_FLAG_CASE);
        return $merged;
    }

    /**
     * Get distinct available locations for filter dropdown.
     */
    public static function getAvailableLocations(PDO $db): array {
        $stmt = $db->query("
            SELECT DISTINCT location 
            FROM items 
            WHERE location IS NOT NULL AND location != '' 
            ORDER BY location ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Resolves date range bounds based on timeframe identifier.
     */
    private static function resolveDateFilter(string $timeframe, string $startDate, string $endDate): array {
        $now = new \DateTime();
        $todayStr = $now->format('Y-m-d');

        switch ($timeframe) {
            case '7d':
                $start = (clone $now)->modify('-6 days')->format('Y-m-d');
                $end = $todayStr;
                $label = 'Last 7 Days';
                break;
            case '90d':
                $start = (clone $now)->modify('-89 days')->format('Y-m-d');
                $end = $todayStr;
                $label = 'Last 90 Days';
                break;
            case 'ytd':
                $start = $now->format('Y-01-01');
                $end = $todayStr;
                $label = 'Year-to-Date (' . $now->format('Y') . ')';
                break;
            case 'all':
                $start = '2020-01-01';
                $end = $todayStr;
                $label = 'All Time Recorded';
                break;
            case 'custom':
                if (!empty($startDate) && !empty($endDate)) {
                    $start = $startDate;
                    $end = $endDate;
                    $label = date('M j, Y', strtotime($startDate)) . ' – ' . date('M j, Y', strtotime($endDate));
                } else {
                    $start = (clone $now)->modify('-29 days')->format('Y-m-d');
                    $end = $todayStr;
                    $label = 'Last 30 Days';
                }
                break;
            case '30d':
            default:
                $start = (clone $now)->modify('-29 days')->format('Y-m-d');
                $end = $todayStr;
                $label = 'Last 30 Days';
                break;
        }

        $sObj = new \DateTime($start);
        $eObj = new \DateTime($end);
        $diff = (int)$sObj->diff($eObj)->format('%a');

        return [
            'start'      => $start,
            'end'        => $end,
            'label'      => $label,
            'days_diff'  => $diff,
        ];
    }

    /**
     * CSV Export Handler: GET /api/analytics/export-csv
     * Streams a sanitized CSV file containing multi-dimensional metrics.
     */
    public static function exportCsv(): void {
        AuthMiddleware::check();
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'user';

        try {
            \App\Models\AuditLog::log(
                $db,
                $userId,
                'REPORTS_EXPORT_CSV',
                "Exported institutional analytics report as CSV spreadsheet ({$userRole})"
            );
        } catch (\Throwable $e) {
            // Non-blocking audit log catch
        }

        $timeframe = trim($_GET['timeframe'] ?? '30d');
        $category = trim($_GET['category'] ?? '');
        $location = trim($_GET['location'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');

        $data = self::computeAnalytics($db, $timeframe, $category, $location, $startDate, $endDate);

        $filename = 'SLSU_Executive_Analytics_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Document Header
        fputcsv($out, [Item::neutralizeFormula('SOUTHERN LUZON STATE UNIVERSITY - ALABAT CAMPUS')]);
        fputcsv($out, [Item::neutralizeFormula('EXECUTIVE ANALYTICS & INSTITUTIONAL METRICS REPORT')]);
        fputcsv($out, [Item::neutralizeFormula('Generated At: ' . date('Y-m-d H:i:s'))]);
        fputcsv($out, [Item::neutralizeFormula('Timeframe: ' . $data['metadata']['timeframe_label'])]);
        fputcsv($out, [Item::neutralizeFormula('Category Filter: ' . ($category ?: 'All Categories'))]);
        fputcsv($out, [Item::neutralizeFormula('Location Filter: ' . ($location ?: 'All Locations'))]);
        fputcsv($out, []);

        // 1. Executive KPI Summary Table
        fputcsv($out, [Item::neutralizeFormula('--- 1. INSTITUTIONAL ASSET & VELOCITY KPIS ---')]);
        fputcsv($out, [Item::neutralizeFormula('Metric Name'), Item::neutralizeFormula('Value'), Item::neutralizeFormula('Context / Notes')]);
        fputcsv($out, [Item::neutralizeFormula('Total Tracked Properties'), $data['kpi']['total_assets'], Item::neutralizeFormula('Campus-wide active and archived properties')]);
        fputcsv($out, [Item::neutralizeFormula('Operational Availability'), $data['kpi']['available_assets'] . ' (' . $data['kpi']['availability_rate'] . '%)', Item::neutralizeFormula('Ready for immediate lending')]);
        fputcsv($out, [Item::neutralizeFormula('Serviceable Condition (Mint/Good)'), $data['kpi']['serviceable_assets'] . ' (' . $data['kpi']['serviceability_rate'] . '%)', Item::neutralizeFormula('Assets in prime functional state')]);
        fputcsv($out, [Item::neutralizeFormula('Active Borrowers'), $data['kpi']['active_borrowers'], Item::neutralizeFormula('Currently possessing campus equipment')]);
        fputcsv($out, [Item::neutralizeFormula('Overdue Delinquent Borrows'), $data['kpi']['overdue_count'], Item::neutralizeFormula('Past expected return date')]);
        fputcsv($out, [Item::neutralizeFormula('Period Borrow Transactions'), $data['kpi']['period_loans'], Item::neutralizeFormula('Borrow transactions within timeframe')]);
        fputcsv($out, [Item::neutralizeFormula('Period Return Transactions'), $data['kpi']['period_returns'], Item::neutralizeFormula('Return transactions within timeframe')]);
        fputcsv($out, [Item::neutralizeFormula('Open Damage Holds'), $data['kpi']['open_damage_holds'], Item::neutralizeFormula('Pending incident clearance')]);
        fputcsv($out, [Item::neutralizeFormula('Estimated Total Damage Cost'), 'PHP ' . number_format($data['kpi']['total_damage_cost'], 2), Item::neutralizeFormula('Recorded incident liability')]);
        fputcsv($out, [Item::neutralizeFormula('Cleared Damage Settlement Cost'), 'PHP ' . number_format($data['kpi']['total_cleared_cost'], 2), Item::neutralizeFormula('Recovered in-kind or repair value')]);
        fputcsv($out, [Item::neutralizeFormula('Avg Incident Resolution Time'), $data['kpi']['avg_resolution_days'] . ' Days', Item::neutralizeFormula('From incident report to clearance')]);
        fputcsv($out, []);

        // 2. Asset Category Distribution
        fputcsv($out, [Item::neutralizeFormula('--- 2. ASSET CATEGORY DISTRIBUTION ---')]);
        fputcsv($out, [Item::neutralizeFormula('Category Classification'), Item::neutralizeFormula('Asset Count'), Item::neutralizeFormula('Percentage Share')]);
        $catLabels = $data['asset_dynamics']['top_categories']['labels'] ?? [];
        $catData = $data['asset_dynamics']['top_categories']['data'] ?? [];
        $totalCat = array_sum($catData);
        foreach ($catLabels as $idx => $label) {
            $cnt = $catData[$idx] ?? 0;
            $pct = $totalCat > 0 ? round(($cnt / $totalCat) * 100, 1) : 0;
            fputcsv($out, [Item::neutralizeFormula((string)$label), $cnt, $pct . '%']);
        }
        fputcsv($out, []);

        // 3. Location / Department Allocation
        fputcsv($out, [Item::neutralizeFormula('--- 3. CAMPUS LOCATION ALLOCATION ---')]);
        fputcsv($out, [Item::neutralizeFormula('Location / Room'), Item::neutralizeFormula('Asset Count'), Item::neutralizeFormula('Percentage Share')]);
        $locLabels = $data['asset_dynamics']['top_locations']['labels'] ?? [];
        $locData = $data['asset_dynamics']['top_locations']['data'] ?? [];
        $totalLoc = array_sum($locData);
        foreach ($locLabels as $idx => $label) {
            $cnt = $locData[$idx] ?? 0;
            $pct = $totalLoc > 0 ? round(($cnt / $totalLoc) * 100, 1) : 0;
            fputcsv($out, [Item::neutralizeFormula((string)$label), $cnt, $pct . '%']);
        }
        fputcsv($out, []);

        // 4. Circulation Velocity Timeline
        fputcsv($out, [Item::neutralizeFormula('--- 4. CIRCULATION VELOCITY TIMELINE ---')]);
        fputcsv($out, [Item::neutralizeFormula('Date / Period'), Item::neutralizeFormula('Borrow Transactions'), Item::neutralizeFormula('Return Transactions')]);
        $vLabels = $data['circulation']['velocity_series']['labels'] ?? [];
        $vBorrows = $data['circulation']['velocity_series']['borrows'] ?? [];
        $vReturns = $data['circulation']['velocity_series']['returns'] ?? [];
        foreach ($vLabels as $idx => $label) {
            fputcsv($out, [
                Item::neutralizeFormula((string)$label),
                $vBorrows[$idx] ?? 0,
                $vReturns[$idx] ?? 0,
            ]);
        }
        fputcsv($out, []);

        // Signatory Footer
        fputcsv($out, [Item::neutralizeFormula('Certified Correct:')]);
        fputcsv($out, [Item::neutralizeFormula('Campus Administration / Executive Office')]);
        fputcsv($out, [Item::neutralizeFormula('Southern Luzon State University - Alabat Campus')]);

        fclose($out);
        exit;
    }

    /**
     * Print-Ready Executive Briefing: GET /api/analytics/print-briefing
     * Supports dynamic customizable sections, custom timeframes, and scope filters.
     */
    public static function printBriefing(): void {
        AuthMiddleware::check();
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'user';

        try {
            \App\Models\AuditLog::log(
                $db,
                $userId,
                'REPORTS_PRINT_BRIEFING',
                "Generated printable institutional report briefing ({$userRole})"
            );
        } catch (\Throwable $e) {
            // Non-blocking audit log catch
        }

        $timeframe = trim($_GET['timeframe'] ?? '30d');
        $category = trim($_GET['category'] ?? '');
        $location = trim($_GET['location'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');

        // Parse customizable modular sections
        $allPossibleSections = ['kpi', 'status', 'condition', 'velocity', 'categories', 'locations', 'dow', 'incidents', 'signatories'];
        $rawSections = $_GET['sections'] ?? null;

        $activeSections = [];
        if ($rawSections === null || $rawSections === '') {
            foreach ($allPossibleSections as $sec) {
                $activeSections[$sec] = true;
            }
        } else {
            $secList = is_array($rawSections) ? $rawSections : explode(',', (string)$rawSections);
            $secList = array_map('trim', $secList);
            foreach ($allPossibleSections as $sec) {
                $activeSections[$sec] = in_array($sec, $secList, true);
            }
        }

        $data = self::computeAnalytics($db, $timeframe, $category, $location, $startDate, $endDate);

        require __DIR__ . '/../../views/reports/executive_briefing.php';
        exit;
    }

    /**
     * 1-to-1 Official Institutional Property Report: GET /api/analytics/institutional-report
     * Faithful replication of Executive-Institutional-Property (1).pdf across all 5 sections.
     * Dual-Mode: Browser High-DPI Print View (default) or Server-Side Dompdf Binary Download (?format=pdf).
     */
    public static function institutionalReport(): void {
        AuthMiddleware::check();
        RoleMiddleware::allow(['custodian', 'executive_admin']);

        $db = Database::getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'user';

        try {
            \App\Models\AuditLog::log(
                $db,
                $userId,
                'REPORTS_INSTITUTIONAL_REPORT',
                "Generated 1-to-1 official institutional property audit report ({$userRole})"
            );
        } catch (\Throwable $e) {
            // Non-blocking audit log catch
        }

        $timeframe = trim($_GET['timeframe'] ?? '30d');
        $category = trim($_GET['category'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');

        $dateFilter = self::resolveDateFilter($timeframe, $startDate, $endDate);

        $metadata = [
            'timeframe_label' => $dateFilter['label'],
            'generated_on'    => date('Y-m-d H:i:s'),
            'target_scope'    => ($category !== '' && $category !== 'all') ? $category : 'All Categories',
            'location'        => 'All Locations',
        ];

        // ── 1. Section 1: Inventory Summary by Category ───────────────
        $standardCategories = [
            'IT Equipment',
            'Laboratory Tools',
            'Aquaculture Equipment',
            'Instructional Materials',
            'Office Equipment',
            'Furniture & Fixtures',
            'Audio/Visual Equipment',
            'Other / General'
        ];

        $hasCategoryFilter = ($category !== '' && $category !== 'all');
        $categoriesToDisplay = $hasCategoryFilter ? [$category] : $standardCategories;

        $catWhere = $hasCategoryFilter ? "WHERE category = :cat" : "";
        $catParams = $hasCategoryFilter ? [':cat' => $category] : [];

        $sumStmt = $db->prepare("
            SELECT 
                category,
                COUNT(*) as total_items,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as under_maintenance,
                SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost_and_missing,
                SUM(CASE WHEN item_condition IN ('damaged', 'not_working') THEN 1 ELSE 0 END) as damaged
            FROM items
            {$catWhere}
            GROUP BY category
        ");
        $sumStmt->execute($catParams);
        $rawSummary = $sumStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $summaryMap = [];
        foreach ($rawSummary as $rs) {
            $summaryMap[$rs['category']] = $rs;
        }

        $summaryRows = [];
        $summaryTotals = [
            'total_items' => 0, 'available' => 0, 'borrowed' => 0,
            'under_maintenance' => 0, 'lost_and_missing' => 0, 'damaged' => 0
        ];

        foreach ($categoriesToDisplay as $cName) {
            $row = $summaryMap[$cName] ?? [
                'category'          => $cName,
                'total_items'       => 0,
                'available'         => 0,
                'borrowed'          => 0,
                'under_maintenance' => 0,
                'lost_and_missing'  => 0,
                'damaged'           => 0,
            ];
            $summaryRows[] = $row;

            $summaryTotals['total_items']       += (int)$row['total_items'];
            $summaryTotals['available']         += (int)$row['available'];
            $summaryTotals['borrowed']          += (int)$row['borrowed'];
            $summaryTotals['under_maintenance'] += (int)$row['under_maintenance'];
            $summaryTotals['lost_and_missing']  += (int)$row['lost_and_missing'];
            $summaryTotals['damaged']           += (int)$row['damaged'];
        }

        // ── 2. Section 2: Borrowing and Return History ────────────────
        $bhWhere = ["t.transaction_type = 'borrow'"];
        $bhParams = [];
        if ($hasCategoryFilter) {
            $bhWhere[] = "i.category = :bh_cat";
            $bhParams[':bh_cat'] = $category;
        }
        $bhWhereSql = implode(' AND ', $bhWhere);

        $bhStmt = $db->prepare("
            SELECT 
                t.transaction_id,
                i.barcode,
                i.item_name,
                i.category,
                i.location,
                u.full_name as borrower_name,
                u.id_number as borrower_id_number,
                u.role,
                DATE_FORMAT(t.transaction_date, '%Y-%m-%d') as date_borrowed,
                DATE_FORMAT(t.actual_return_date, '%Y-%m-%d') as actual_return_date,
                DATE_FORMAT(t.expected_return_date, '%Y-%m-%d') as expected_return_date,
                t.returned_condition,
                CASE 
                    WHEN t.actual_return_date IS NULL AND t.expected_return_date IS NOT NULL AND t.expected_return_date < NOW() THEN 1 
                    ELSE 0 
                END as is_overdue
            FROM transactions t
            INNER JOIN items i ON t.item_id = i.item_id
            INNER JOIN users u ON t.user_id = u.user_id
            WHERE {$bhWhereSql}
            ORDER BY t.transaction_date DESC
            LIMIT 30
        ");
        $bhStmt->execute($bhParams);
        $rawBh = $bhStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $borrowingHistory = [];
        foreach ($rawBh as $b) {
            $borrowingHistory[] = [
                'barcode'               => $b['barcode'],
                'item_name'             => $b['item_name'],
                'borrower_name'         => $b['borrower_name'],
                'borrower_id_number'    => $b['borrower_id_number'],
                'role'                  => $b['role'],
                'date_borrowed'         => $b['date_borrowed'],
                'return_date'           => $b['actual_return_date'] ?: ($b['expected_return_date'] ?: '—'),
                'actual_return_date'    => $b['actual_return_date'],
                'location'              => $b['location'],
                'condition_upon_return' => $b['returned_condition'] ?: 'Good',
                'is_overdue'            => (bool)$b['is_overdue'],
            ];
        }

        // ── 3. Section 3: Damaged Properties Report ──────────────────
        $dpWhere = [];
        $dpParams = [];
        if ($hasCategoryFilter) {
            $dpWhere[] = "i.category = :dp_cat";
            $dpParams[':dp_cat'] = $category;
        }
        $dpWhereSql = !empty($dpWhere) ? "WHERE " . implode(' AND ', $dpWhere) : "";

        $dpStmt = $db->prepare("
            SELECT 
                di.incident_id,
                i.barcode,
                i.item_name,
                i.category,
                i.location,
                DATE_FORMAT(di.created_at, '%Y-%m-%d') as incident_date,
                di.damage_description,
                di.estimated_cost,
                di.incident_status,
                b.full_name as borrower_name,
                b.id_number as borrower_id_number,
                r.full_name as technician
            FROM damage_incidents di
            INNER JOIN items i ON di.item_id = i.item_id
            INNER JOIN users b ON di.borrower_id = b.user_id
            LEFT JOIN users r ON di.reported_by = r.user_id
            {$dpWhereSql}
            ORDER BY di.created_at DESC
            LIMIT 30
        ");
        $dpStmt->execute($dpParams);
        $rawDp = $dpStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $damagedProperties = [];
        foreach ($rawDp as $d) {
            $damagedProperties[] = [
                'barcode'            => $d['barcode'],
                'item_name'          => $d['item_name'],
                'incident_date'      => $d['incident_date'],
                'damage_description' => $d['damage_description'],
                'borrower_name'      => $d['borrower_name'],
                'borrower_id_number' => $d['borrower_id_number'],
                'location'           => $d['location'],
                'technician'         => $d['technician'] ?: 'Property Custodian',
                'repair_cost'        => (float)($d['estimated_cost'] ?? 0),
                'cost_type'          => ($d['incident_status'] === 'cleared') ? 'ACTUAL' : 'ESTIMATED',
                'status'             => ($d['incident_status'] === 'cleared') ? 'Repaired / Cleared' : 'Under Repair',
            ];
        }

        // ── 4. Section 4: Lost and Missing Properties Report ─────────
        $lpWhere = [];
        $lpParams = [];
        if ($hasCategoryFilter) {
            $lpWhere[] = "i.category = :lp_cat";
            $lpParams[':lp_cat'] = $category;
        }
        $lpWhereSql = !empty($lpWhere) ? "WHERE " . implode(' AND ', $lpWhere) : "";

        $lpStmt = $db->prepare("
            SELECT 
                lp.id,
                i.barcode,
                i.item_name,
                i.category,
                lp.last_recorded_location,
                lp.accountable_person,
                DATE_FORMAT(lp.date_reported, '%Y-%m-%d') as date_reported,
                lp.description,
                lp.investigation_status,
                lp.resolution
            FROM lost_properties lp
            INNER JOIN items i ON lp.item_id = i.item_id
            {$lpWhereSql}
            ORDER BY lp.date_reported DESC, lp.id DESC
            LIMIT 30
        ");
        $lpStmt->execute($lpParams);
        $lostProperties = $lpStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // ── 5. Section 5: Property Movement and Location History ─────
        $pmWhere = [];
        $pmParams = [];
        if ($hasCategoryFilter) {
            $pmWhere[] = "i.category = :pm_cat";
            $pmParams[':pm_cat'] = $category;
        }
        $pmWhereSql = !empty($pmWhere) ? "WHERE " . implode(' AND ', $pmWhere) : "";

        $pmStmt = $db->prepare("
            SELECT 
                pm.id,
                i.barcode,
                i.item_name,
                i.category,
                pm.previous_location,
                pm.new_location,
                DATE_FORMAT(pm.transfer_date, '%Y-%m-%d %H:%i') as transfer_date,
                pm.reason,
                u.full_name as transferred_by
            FROM property_movements pm
            INNER JOIN items i ON pm.item_id = i.item_id
            LEFT JOIN users u ON pm.transferred_by = u.user_id
            {$pmWhereSql}
            ORDER BY pm.transfer_date DESC, pm.id DESC
            LIMIT 30
        ");
        $pmStmt->execute($pmParams);
        $propertyMovements = $pmStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $reportData = [
            'metadata'           => $metadata,
            'summary_rows'       => $summaryRows,
            'summary_totals'     => $summaryTotals,
            'borrowing_history'  => $borrowingHistory,
            'damaged_properties' => $damagedProperties,
            'lost_properties'    => $lostProperties,
            'property_movements' => $propertyMovements,
        ];

        require __DIR__ . '/../../views/reports/institutional-print.php';
        exit;
    }
}
