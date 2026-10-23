<?php
namespace App\Middleware;

use App\Helpers\Security;
use App\Config\Database;

class AuthMiddleware {
    /**
     * Ensure the user is authenticated and session is valid.
     * Synchronizes live user data from DB to reflect administrative updates in real-time.
     */
    public static function check(): void {
        // Validate session state and fingerprint (handles idle timeout and hijacking checks)
        if (!Security::validateSessionFingerprint()) {
            $restored = false;
            try {
                $db = Database::getConnection();
                $restoredUser = \App\Helpers\RememberMe::validateAndLogin($db);
                if ($restoredUser) {
                    $restored = true;
                }
            } catch (\Throwable $e) {}

            if (!$restored) {
                Security::destroySession();
                redirect('/login?notice=session_expired');
            }
        }

        if (!isset($_SESSION['is_remembered'])) {
            $_SESSION['is_remembered'] = !empty($_COOKIE[\App\Helpers\RememberMe::COOKIE_NAME] ?? null);
        }

        if (isset($_SESSION['user_id'])) {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("
                    SELECT user_id, id_number, full_name, email, role, department_program, 
                           avatar_path, phone_number, account_status, max_concurrent_loans, 
                           notification_preferences, session_version 
                    FROM users 
                    WHERE user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $freshUser = $stmt->fetch();

                if (!$freshUser) {
                    // Account was deleted while session was active
                    Security::destroySession();
                    redirect('/login?error=' . urlencode("Your account has been deleted or no longer exists."));
                }

                // Live status enforcement
                if ($freshUser['account_status'] !== 'approved') {
                    $status = $freshUser['account_status'];
                    Security::destroySession();
                    redirect('/login?notice=' . urlencode($status));
                }

                // Session version enforcement (Sign out of other devices)
                $dbVersion = (int)($freshUser['session_version'] ?? 1);
                if (isset($_SESSION['session_version']) && $dbVersion > (int)$_SESSION['session_version']) {
                    Security::destroySession();
                    redirect('/login?notice=' . urlencode("session_revoked"));
                }
                if (!isset($_SESSION['session_version'])) {
                    $_SESSION['session_version'] = $dbVersion;
                }

                // Refresh active session attributes immediately
                $_SESSION['user_name'] = $freshUser['full_name'];
                $_SESSION['full_name'] = $freshUser['full_name'];
                $_SESSION['email'] = $freshUser['email'];
                $_SESSION['role'] = $freshUser['role'];
                $_SESSION['account_status'] = $freshUser['account_status'];
                $_SESSION['avatar_path'] = $freshUser['avatar_path'];
                $_SESSION['department_program'] = $freshUser['department_program'];
                $_SESSION['phone_number'] = $freshUser['phone_number'];
                $_SESSION['notification_preferences'] = !empty($freshUser['notification_preferences']) 
                    ? json_decode($freshUser['notification_preferences'], true) 
                    : [];
            } catch (\Exception $e) {
                // If DB check fails, fallback to session values
            }
        }
    }
}

