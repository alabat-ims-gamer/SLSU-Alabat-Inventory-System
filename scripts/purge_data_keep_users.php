<?php
declare(strict_types=1);

/**
 * SLSU-Alabat Inventory Management System
 * System Reset & Data Purge Utility
 *
 * Purges all operational data (items, transactions, damage incidents, login attempts,
 * non-registration audit logs, and borrower holds) while strictly preserving all
 * user accounts, credentials, and their assigned roles.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

/** @var \PDO $db */
$db = require ROOT_PATH . '/config/database.php';

echo "========================================================\n";
echo "   SLSU-Alabat IMS - Full Data Purge (Retain Users)    \n";
echo "========================================================\n\n";

try {
    // 1. Gather pre-purge statistics
    $userCount      = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $itemCount      = (int)$db->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $txCount        = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    $incidentCount  = (int)$db->query("SELECT COUNT(*) FROM damage_incidents")->fetchColumn();
    $disposalCount  = (int)$db->query("SELECT COUNT(*) FROM disposal_records")->fetchColumn();
    $notifCount     = (int)$db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    $loginCount     = (int)$db->query("SELECT COUNT(*) FROM login_attempts")->fetchColumn();
    $totalLogs      = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    
    $retainedLogsCount = (int)$db->query(
        "SELECT COUNT(*) FROM audit_logs WHERE action_type IN ('USER_REGISTER', 'USER_APPROVED', 'ROLE_ASSIGNED', 'ROLE_MODIFIED')"
    )->fetchColumn();
    $purgedLogsCount = $totalLogs - $retainedLogsCount;

    echo "[*] PRE-PURGE SYSTEM AUDIT:\n";
    echo "  - Registered Users (TO RETAIN):  {$userCount}\n";
    echo "  - Registration Audit Logs (KEPT): {$retainedLogsCount}\n";
    echo "  - General Audit Logs (TO PURGE):  {$purgedLogsCount}\n";
    echo "  - Asset Items (TO PURGE):         {$itemCount}\n";
    echo "  - Transactions (TO PURGE):        {$txCount}\n";
    echo "  - Damage Incidents (TO PURGE):    {$incidentCount}\n";
    echo "  - Disposal Records (TO PURGE):    {$disposalCount}\n";
    echo "  - Notifications (TO PURGE):       {$notifCount}\n";
    echo "  - Login Attempts (TO PURGE):      {$loginCount}\n\n";

    echo "[*] Executing purge operations...\n";

    // Disable foreign key checks for clean truncation / reset of auto-increment
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Truncate operational tables
    $db->exec("TRUNCATE TABLE damage_incidents;");
    $db->exec("TRUNCATE TABLE transactions;");
    $db->exec("TRUNCATE TABLE disposal_records;");
    $db->exec("TRUNCATE TABLE items;");
    $db->exec("TRUNCATE TABLE login_attempts;");
    $db->exec("TRUNCATE TABLE notifications;");

    // Purge non-registration audit logs
    $db->exec("DELETE FROM audit_logs WHERE action_type NOT IN ('USER_REGISTER', 'USER_APPROVED', 'ROLE_ASSIGNED', 'ROLE_MODIFIED');");

    // Reset any active holds on retained users
    $db->exec("UPDATE users SET has_damage_hold = 0;");

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Log the purge action
    \App\Models\AuditLog::log(
        $db,
        null,
        'SYSTEM_RESET',
        "Operational system purge executed. Preserved {$userCount} registered user accounts; purged catalog items, transactions, incidents, disposals, and notifications."
    );

    // 2. Post-purge verification
    $postUsers     = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $postItems     = (int)$db->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $postTx        = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    $postIncidents = (int)$db->query("SELECT COUNT(*) FROM damage_incidents")->fetchColumn();
    $postDisposals = (int)$db->query("SELECT COUNT(*) FROM disposal_records")->fetchColumn();
    $postNotifs    = (int)$db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    $postLogins    = (int)$db->query("SELECT COUNT(*) FROM login_attempts")->fetchColumn();
    $postLogs      = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

    echo "\n[✓] PURGE COMPLETE - VERIFICATION RESULTS:\n";
    echo "  - Users Preserved:           {$postUsers} / {$userCount}\n";
    echo "  - Audit Logs Retained:       {$postLogs}\n";
    echo "  - Items Remaining:           {$postItems}\n";
    echo "  - Transactions Remaining:    {$postTx}\n";
    echo "  - Damage Incidents Remaining:{$postIncidents}\n";
    echo "  - Disposal Records Remaining:{$postDisposals}\n";
    echo "  - Notifications Remaining:   {$postNotifs}\n";
    echo "  - Login Attempts Remaining:  {$postLogins}\n\n";

    echo "--- Retained User Accounts ---\n";
    $users = $db->query("SELECT user_id, username, full_name, role, account_status FROM users ORDER BY user_id ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo sprintf("  [%d] %-15s | %-24s | Role: %-10s | Status: %s\n", $u['user_id'], $u['username'], $u['full_name'], $u['role'], $u['account_status']);
    }

    echo "\n--- Retained Registration Audit Logs ---\n";
    $logs = $db->query("SELECT log_id, action_type, description, created_at FROM audit_logs ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($logs as $l) {
        echo sprintf("  [%d] %-15s (%s): %s\n", $l['log_id'], $l['action_type'], $l['created_at'], $l['description']);
    }

    echo "\n========================================================\n";
    echo "  System successfully reset while preserving all users! \n";
    echo "========================================================\n";

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n[!] Error during purge execution: " . $e->getMessage() . "\n";
    exit(1);
}
