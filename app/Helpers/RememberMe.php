<?php
declare(strict_types=1);

namespace App\Helpers;

use PDO;
use Throwable;
use App\Models\AuditLog;

/**
 * RememberMe Device Persistence Service
 * Implements the secure Selector + Validator pattern for persistent 30-day authentication.
 */
class RememberMe {
    public const COOKIE_NAME = 'slsu_remember';
    public const LIFETIME_SECONDS = 30 * 86400; // 30 days (2,592,000 seconds)

    /**
     * Issue a new 30-day remember token and set the persistent HttpOnly cookie.
     */
    public static function createToken(PDO $db, int $userId): bool {
        if ($userId <= 0) {
            return false;
        }

        try {
            $selector = bin2hex(random_bytes(16));  // 32 hex chars
            $validator = bin2hex(random_bytes(32)); // 64 hex chars
            $tokenHash = hash('sha256', $validator);
            $expiresAt = date('Y-m-d H:i:s', time() + self::LIFETIME_SECONDS);

            $stmt = $db->prepare("
                INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $selector, $tokenHash, $expiresAt]);

            self::setCookie($selector, $validator);
            return true;
        } catch (Throwable $e) {
            error_log('[RememberMe::createToken] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate an existing remember-me cookie and restore user session.
     */
    public static function validateAndLogin(PDO $db): ?array {
        if (empty($_COOKIE[self::COOKIE_NAME]) || !is_string($_COOKIE[self::COOKIE_NAME])) {
            return null;
        }

        $parts = explode(':', $_COOKIE[self::COOKIE_NAME], 2);
        if (count($parts) !== 2) {
            self::clearCookie();
            return null;
        }

        [$selector, $validator] = $parts;
        if (empty($selector) || empty($validator) || strlen($selector) !== 32) {
            self::clearCookie();
            return null;
        }

        $now = date('Y-m-d H:i:s');

        // Periodic maintenance: prune expired tokens (5% probability)
        if (random_int(1, 20) === 1) {
            try {
                $pruneStmt = $db->prepare("DELETE FROM remember_tokens WHERE expires_at < ?");
                $pruneStmt->execute([$now]);
            } catch (Throwable $e) {
                // Ignore cleanup errors
            }
        }

        try {
            $stmt = $db->prepare("
                SELECT r.token_id, r.user_id, r.token_hash, r.expires_at,
                       u.id_number, u.username, u.full_name, u.email, u.role, u.department_program,
                       u.avatar_path, u.phone_number, u.account_status, u.session_version
                FROM remember_tokens r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.selector = ? AND r.expires_at > ?
                LIMIT 1
            ");
            $stmt->execute([$selector, $now]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                self::clearCookie();
                return null;
            }

            // Verify constant-time validator hash
            if (!hash_equals($record['token_hash'], hash('sha256', $validator))) {
                // Potential token theft or tampering: revoke token
                $del = $db->prepare("DELETE FROM remember_tokens WHERE selector = ?");
                $del->execute([$selector]);
                self::clearCookie();
                return null;
            }

            // Ensure account is in approved status
            if ($record['account_status'] !== 'approved') {
                $del = $db->prepare("DELETE FROM remember_tokens WHERE selector = ?");
                $del->execute([$selector]);
                self::clearCookie();
                return null;
            }

            // Rotate validator for forward security
            $newValidator = bin2hex(random_bytes(32));
            $newHash = hash('sha256', $newValidator);
            $newExpires = date('Y-m-d H:i:s', time() + self::LIFETIME_SECONDS);

            $upd = $db->prepare("
                UPDATE remember_tokens 
                SET token_hash = ?, expires_at = ? 
                WHERE token_id = ?
            ");
            $upd->execute([$newHash, $newExpires, $record['token_id']]);

            self::setCookie($selector, $newValidator);

            // Establish active session
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id'] = (int)$record['user_id'];
            $_SESSION['role'] = $record['role'];
            $_SESSION['user_name'] = $record['full_name'];
            $_SESSION['full_name'] = $record['full_name'];
            $_SESSION['email'] = $record['email'];
            $_SESSION['account_status'] = $record['account_status'];
            $_SESSION['avatar_path'] = $record['avatar_path'] ?? null;
            $_SESSION['department_program'] = $record['department_program'] ?? '';
            $_SESSION['phone_number'] = $record['phone_number'] ?? '';
            $_SESSION['session_version'] = (int)($record['session_version'] ?? 1);
            $_SESSION['is_remembered'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['fingerprint'] = Security::generateFingerprint();
            $_SESSION['last_activity'] = time();

            if (class_exists(AuditLog::class)) {
                AuditLog::log($db, (int)$record['user_id'], 'LOGIN_SUCCESS', "User authenticated automatically via remembered device");
            }

            return $record;
        } catch (Throwable $e) {
            error_log('[RememberMe::validateAndLogin] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Clear cookie and revoke database token for current device.
     */
    public static function clearCookieAndRevoke(PDO $db, ?int $userId = null): void {
        $cookie = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!empty($cookie) && is_string($cookie)) {
            $parts = explode(':', $cookie, 2);
            $selector = $parts[0] ?? '';
            if (!empty($selector)) {
                try {
                    $stmt = $db->prepare("DELETE FROM remember_tokens WHERE selector = ?");
                    $stmt->execute([$selector]);
                } catch (Throwable $e) {}
            }
        }

        self::clearCookie();
    }

    /**
     * Revoke all remember tokens for a specific user (e.g. password change, session revoke).
     */
    public static function revokeAllForUser(PDO $db, int $userId): void {
        if ($userId <= 0) return;
        try {
            $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
        } catch (Throwable $e) {}
        self::clearCookie();
    }

    /**
     * Set the persistent cookie with modern security flags.
     */
    private static function setCookie(string $selector, string $validator): void {
        $value = $selector . ':' . $validator;
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, $value, [
                'expires'  => time() + self::LIFETIME_SECONDS,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        $_COOKIE[self::COOKIE_NAME] = $value;
    }

    /**
     * Unset the remember-me cookie.
     */
    public static function clearCookie(): void {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        unset($_COOKIE[self::COOKIE_NAME]);
    }
}
