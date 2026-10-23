<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\User;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Config\Database;

class DashboardController {
    
    /**
     * Dispatch to the correct dashboard based on the user's role
     */
    public static function index() {
        AuthMiddleware::check();

        $role = $_SESSION['role'] ?? 'student';
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $db = Database::getConnection();

        // Dispatches to the appropriate dashboard view with live database data
        switch ($role) {
            case 'admin':
                $pendingUsers = User::getPendingUsers($db);
                $pendingUsersCount = count($pendingUsers);
                $allUsers = User::getAllUsers($db);
                $liveItemStats = Item::getStats($db);
                $activeUsersCount = User::getActiveUsersCount($db);
                $overdueCount = Transaction::getOverdueCount($db);
                $categoryDist = Item::getCategoryDistribution($db);
                $monthlyActivity = Transaction::getMonthlyBorrowingStats($db, 7);
                $recentAuditLogs = AuditLog::getRecentLogs($db, 5);
                $recentAssets = Item::getRecentAssets($db, 10);
                $pendingRequestsCount = Item::getPendingRequestsCount($db);
                $recentTransactions = Transaction::getRecentTransactions($db, 10);
                require __DIR__ . '/../../views/dashboard/admin.php';
                break;
            case 'executive_admin':
                $analyticsData = AnalyticsController::computeAnalytics($db, '30d');
                $categoriesList = AnalyticsController::getAvailableCategories($db);
                $locationsList = AnalyticsController::getAvailableLocations($db);
                require __DIR__ . '/../../views/dashboard/executive.php';
                break;
            case 'custodian':
                $pendingUsersCount = count(User::getPendingUsers($db));
                $liveItemStats = Item::getStats($db);
                $pendingRequestsCount = Item::getPendingRequestsCount($db);
                $recentTransactions = Transaction::getRecentTransactions($db, 15);
                $overdueCount = Transaction::getOverdueCount($db);
                $borrowingActivity = Transaction::getMonthlyBorrowingStats($db, 7);
                require __DIR__ . '/../../views/dashboard/custodian.php';
                break;
            case 'faculty':
                $activeLoans = Transaction::getActiveLoansByUser($db, $userId);
                $loanHistory = Transaction::getUserLoanHistory($db, $userId, 5);
                $loanCount = count($activeLoans);
                $isOverdue = Transaction::hasOverdueLoans($db, $userId);
                $overdueCount = $isOverdue ? 1 : 0;
                $damageHold = \App\Models\DamageIncident::getActiveHoldByBorrower($db, $userId);
                $returnedThisMonth = Transaction::getUserReturnedThisMonth($db, $userId);
                $totalLoansYtd = Transaction::getUserTotalLoansYtd($db, $userId);
                $maxItems = (int)($_SESSION['max_concurrent_loans'] ?? 10);
                $pendingRequests = Item::countPendingRequestsByUser($db, $userId);
                require __DIR__ . '/../../views/dashboard/faculty.php';
                break;
            case 'inspector':
                \App\Controllers\InspectorController::index();
                break;
            case 'student':
            default:
                $activeLoans = Transaction::getActiveLoansByUser($db, $userId);
                $loanHistory = Transaction::getUserLoanHistory($db, $userId, 5);
                $loanCount = count($activeLoans);
                $isOverdue = Transaction::hasOverdueLoans($db, $userId);
                $overdueCount = $isOverdue ? 1 : 0;
                $damageHold = \App\Models\DamageIncident::getActiveHoldByBorrower($db, $userId);
                $maxItems = (int)($_SESSION['max_concurrent_loans'] ?? 3);
                $pendingRequests = Item::countPendingRequestsByUser($db, $userId);
                require __DIR__ . '/../../views/dashboard/student.php';
                break;
        }
    }
}

