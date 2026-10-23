<?php
/**
 * SLSU-Alabat IMS Front Controller
 */

declare(strict_types=1);

define('APP_START', microtime(true));
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// Load configurations
$config = require ROOT_PATH . '/config/app.php';

// Initialize Security & Sessions
\App\Helpers\Security::initSession();

// Initialize Router
$router = new \App\Helpers\Router();



// Regular Path-based Routing (RESTful approach)
// Default Route (Dashboard)
$router->get('/', [\App\Controllers\DashboardController::class, 'index']);
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index']);

// Borrower Transaction History Ledger (Student & Faculty)
$router->get('/records', [\App\Controllers\RecordsController::class, 'index']);


// Auth Routes
$router->get('/login', [\App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [\App\Controllers\AuthController::class, 'handleLogin']);

$router->get('/register', [\App\Controllers\AuthController::class, 'showRegister']);
$router->post('/register', [\App\Controllers\AuthController::class, 'handleRegister']);

$router->get('/logout', [\App\Controllers\AuthController::class, 'handleLogout']);

// System Admin - User Management Control Center
$router->get('/admin/users', [\App\Controllers\AdminUserController::class, 'index']);
$router->post('/admin/users/approve', [\App\Controllers\AdminUserController::class, 'approveUser']);
$router->post('/admin/users/reject', [\App\Controllers\AdminUserController::class, 'rejectUser']);
$router->post('/admin/users/purge', [\App\Controllers\AdminUserController::class, 'purgeUser']);
$router->post('/admin/users/edit', [\App\Controllers\AdminUserController::class, 'editUser']);
$router->post('/admin/users/password-override', [\App\Controllers\AdminUserController::class, 'overridePassword']);
$router->post('/admin/users/delete', [\App\Controllers\AdminUserController::class, 'deleteUser']);

// Security & Audit Trail Control Center
$router->get('/admin/audit', [\App\Controllers\AuditLogController::class, 'index']);
$router->get('/admin/audit-logs', [\App\Controllers\AuditLogController::class, 'index']);

// Institutional Reports & Property Analytics Studio (Custodian & Executive Admin)
$router->get('/reports', [\App\Controllers\AnalyticsController::class, 'index']);
$router->get('/analytics', [\App\Controllers\AnalyticsController::class, 'index']);
$router->get('/api/analytics/executive', [\App\Controllers\AnalyticsController::class, 'getExecutiveData']);
$router->get('/api/analytics/export-csv', [\App\Controllers\AnalyticsController::class, 'exportCsv']);
$router->get('/api/analytics/print-briefing', [\App\Controllers\AnalyticsController::class, 'printBriefing']);
$router->get('/api/analytics/institutional-report', [\App\Controllers\AnalyticsController::class, 'institutionalReport']);

// Property Custodian & Inventory Control Center
$router->get('/custodian/loans', [\App\Controllers\CustodianLoanController::class, 'index']);
$router->get('/custodian/loans/export-xlsx', [\App\Controllers\CustodianLoanController::class, 'exportXlsx']);
$router->get('/custodian/loans/export-csv', [\App\Controllers\CustodianLoanController::class, 'exportXlsx']);
$router->get('/inventory', [\App\Controllers\InventoryController::class, 'index']);
$router->get('/inventory/export-xlsx', [\App\Controllers\InventoryController::class, 'exportXlsx']);
$router->get('/inventory/export-csv', [\App\Controllers\InventoryController::class, 'exportXlsx']);
$router->get('/api/inventory/list', [\App\Controllers\InventoryController::class, 'listItems']);
$router->get('/api/inventory/check-property', [\App\Controllers\InventoryController::class, 'checkPropertyNumber']);
$router->post('/inventory/register', [\App\Controllers\InventoryController::class, 'register']);
$router->post('/inventory/edit', [\App\Controllers\InventoryController::class, 'edit']);
$router->post('/inventory/delete', [\App\Controllers\InventoryController::class, 'delete']);
$router->post('/inventory/import-csv', [\App\Controllers\InventoryController::class, 'importCsv']);
$router->post('/api/inventory/restore', [\App\Controllers\InventoryController::class, 'restore']);
$router->post('/api/inventory/ber', [\App\Controllers\InventoryController::class, 'declareBer']);
$router->post('/api/inventory/report-lost', [\App\Controllers\InventoryController::class, 'reportLost']);

// Asset Disposal & Condemnation Control Center
$router->get('/disposal', [\App\Controllers\DisposalController::class, 'index']);
$router->get('/disposal/export-xlsx', [\App\Controllers\DisposalController::class, 'exportXlsx']);
$router->get('/disposal/export-csv', [\App\Controllers\DisposalController::class, 'exportXlsx']);
$router->get('/api/disposal/list-staged', [\App\Controllers\DisposalController::class, 'listStaged']);
$router->get('/api/disposal/list-disposed', [\App\Controllers\DisposalController::class, 'listDisposed']);
$router->post('/disposal/stage', [\App\Controllers\DisposalController::class, 'stageItem']);
$router->post('/disposal/decommission', [\App\Controllers\DisposalController::class, 'decommissionItem']);
$router->post('/disposal/batch-decommission', [\App\Controllers\DisposalController::class, 'batchDecommission']);
$router->post('/disposal/restore', [\App\Controllers\DisposalController::class, 'restoreItem']);

// Damage Incidents & Account Clearance Center
$router->get('/my-clearances', [\App\Controllers\ClearanceController::class, 'myClearances']);
$router->get('/clearances', [\App\Controllers\RecordsController::class, 'incidents']);
$router->get('/clearances/export-xlsx', [\App\Controllers\TransactionController::class, 'exportIncidentsXlsx']);
$router->get('/clearances/export-csv', [\App\Controllers\TransactionController::class, 'exportIncidentsXlsx']);
$router->get('/clearances/document', [\App\Controllers\TransactionController::class, 'viewClearanceDocument']);
$router->get('/incidents', [\App\Controllers\RecordsController::class, 'incidents']);
$router->get('/incidents/export-xlsx', [\App\Controllers\TransactionController::class, 'exportIncidentsXlsx']);
$router->get('/incidents/export-csv', [\App\Controllers\TransactionController::class, 'exportIncidentsXlsx']);
$router->get('/api/incident/list', [\App\Controllers\TransactionController::class, 'apiListIncidents']);
$router->post('/api/incident/submit-receipt', [\App\Controllers\ClearanceController::class, 'apiSubmitReceipt']);
$router->post('/api/incident/verify', [\App\Controllers\ClearanceController::class, 'apiVerify']);
$router->post('/api/incident/reject', [\App\Controllers\ClearanceController::class, 'apiReject']);
$router->post('/api/incident/update-fee', [\App\Controllers\ClearanceController::class, 'apiUpdateFee']);
$router->post('/api/incident/resolve', [\App\Controllers\ClearanceController::class, 'apiVerify']);
$router->get('/api/incident/payments', [\App\Controllers\ClearanceController::class, 'apiGetPayments']);
$router->get('/api/incident/my-hold', [\App\Controllers\TransactionController::class, 'apiGetMyDamageHold']);

// Local Inspector & Gate Pass Permit Control Center
$router->get('/inspector', [\App\Controllers\InspectorController::class, 'index']);
$router->get('/inspector/gate-passes', [\App\Controllers\InspectorController::class, 'gatePasses']);
$router->get('/api/gate-pass/lookup', [\App\Controllers\InspectorController::class, 'apiLookup']);
$router->post('/inspector/gate-passes/create', [\App\Controllers\InspectorController::class, 'createGatePass']);
$router->get('/inspector/gate-passes/print', [\App\Controllers\InspectorController::class, 'printGatePass']);

// Local Inspector: Inspection & Acceptance Reports (IAR Form AFA-SAF-1.016)
$router->get('/inspector/iar', [\App\Controllers\InspectorController::class, 'iarList']);
$router->post('/inspector/iar/create', [\App\Controllers\InspectorController::class, 'createIar']);
$router->get('/inspector/iar/print', [\App\Controllers\InspectorController::class, 'printIar']);
$router->post('/inspector/iar/upload-scan', [\App\Controllers\InspectorController::class, 'uploadIarScan']);

// Property Custodian: IAR Ingestion into Live Inventory
$router->get('/api/custodian/iar/pending', [\App\Controllers\InventoryController::class, 'apiPendingIar']);
$router->post('/api/custodian/iar/ingest', [\App\Controllers\InventoryController::class, 'apiIngestIar']);

// Barcode Scanning & Equipment Custody Station (Universal for all authenticated roles)
$router->get('/scan', [\App\Controllers\TransactionController::class, 'scan']);
$router->get('/api/scan/lookup', [\App\Controllers\TransactionController::class, 'apiLookup']);
$router->post('/api/transaction/request', [\App\Controllers\TransactionController::class, 'apiRequest']);
$router->post('/api/transaction/cancel-request', [\App\Controllers\TransactionController::class, 'apiCancelRequest']);
$router->post('/api/transaction/approve', [\App\Controllers\TransactionController::class, 'apiApprove']);
$router->post('/api/transaction/deny', [\App\Controllers\TransactionController::class, 'apiDeny']);
$router->post('/api/transaction/return', [\App\Controllers\TransactionController::class, 'apiReturn']);
$router->get('/api/transaction/pending-queue', [\App\Controllers\TransactionController::class, 'apiPendingQueue']);

// User Settings & Profile Center (Universal for all authenticated roles)
$router->get('/settings', [\App\Controllers\SettingsController::class, 'index']);
$router->post('/settings/profile', [\App\Controllers\SettingsController::class, 'updateProfile']);
$router->post('/settings/avatar', [\App\Controllers\SettingsController::class, 'uploadAvatar']);
$router->post('/settings/avatar/remove', [\App\Controllers\SettingsController::class, 'removeAvatar']);
$router->post('/settings/credentials', [\App\Controllers\SettingsController::class, 'updateCredentials']);
$router->post('/settings/notifications', [\App\Controllers\SettingsController::class, 'updateNotifications']);
$router->post('/settings/sessions/revoke-others', [\App\Controllers\SettingsController::class, 'revokeOtherSessions']);
$router->post('/settings/account/delete', [\App\Controllers\SettingsController::class, 'deleteOrDeactivate']);

// Notification Hub API Endpoints (Universal for all authenticated roles)
$router->get('/api/notifications', [\App\Controllers\NotificationController::class, 'getFeed']);
$router->post('/api/notifications/read', [\App\Controllers\NotificationController::class, 'markRead']);

// Unified Real-Time Synchronization Engine (Universal for all authenticated roles)
$router->get('/api/realtime/sync', [\App\Controllers\RealtimeController::class, 'sync']);
$router->get('/api/realtime/stream', [\App\Controllers\RealtimeController::class, 'stream']);


// Dispatch Request
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$basePath = \App\Helpers\Url::basePath();

// Handle legacy request paths on Railway / root domain
if ($basePath === '' && strpos($requestUri, '/slsu-inventory/public') === 0) {
    $cleanUri = substr($requestUri, strlen('/slsu-inventory/public'));
    header("Location: " . ($cleanUri === '' ? '/' : $cleanUri), true, 301);
    exit;
}

// Strip active base path if running under a subfolder (e.g. /slsu-inventory/public/)
if (!empty($basePath) && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
$uriParts = explode('?', $requestUri);
$path = $uriParts[0];

if ($path === '' || $path === '/') {
    // If we land on index without query params, check session or attempt RememberMe restoration
    if (!isset($_SESSION['user_id'])) {
        try {
            $db = \App\Config\Database::getConnection();
            \App\Helpers\RememberMe::validateAndLogin($db);
        } catch (\Throwable $e) {}
    }
    if (!isset($_SESSION['user_id'])) {
        redirect('/login');
    }
    // Else dispatch to dashboard
    $path = '/';
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

$router->dispatch($path, $requestMethod);
