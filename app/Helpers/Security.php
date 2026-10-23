<?php
namespace App\Helpers;

use PDO;
use Exception;

/**
 * Core Security Engine
 * Handles Sessions, Anti-Hijacking, CSRF, XSS, and Rate Limiting
 */
class Security {
    
    /**
     * Initialize hardened session
     */
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            
            // In production, require HTTPS cookies
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
        }
    }

    /**
     * Validate session fingerprint to prevent hijacking.
     * Enforces a 15-minute idle timeout.
     */
    public static function validateSessionFingerprint() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // 1. Idle Timeout Check (15 minutes for public/unremembered devices; 30 days for remembered devices)
        $isRemembered = !empty($_SESSION['is_remembered']) || !empty($_COOKIE[\App\Helpers\RememberMe::COOKIE_NAME] ?? null);
        $timeout = $isRemembered ? (30 * 86400) : 900;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::destroySession();
            return false;
        }
        $_SESSION['last_activity'] = time();

        // 2. Fingerprint Check (User-Agent + /24 IP Subnet)
        $currentFingerprint = self::generateFingerprint();
        if (!isset($_SESSION['fingerprint']) || !hash_equals($_SESSION['fingerprint'], $currentFingerprint)) {
            self::destroySession();
            return false;
        }

        return true;
    }

    /**
     * Generate session fingerprint based on IP subnet and User-Agent
     */
    public static function generateFingerprint(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        // Extract /24 subnet for IPv4 to handle slight IP changes on mobile networks
        $subnet = preg_replace('/(\d+\.\d+\.\d+)\.\d+/', '$1.0', $ip);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return hash('sha256', $subnet . '|' . $userAgent);
    }

    /**
     * Safely destroy a session
     */
    public static function destroySession() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Generate a cryptographic CSRF token
     */
    public static function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                // Fallback if random_bytes fails (rare)
                $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
            }
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token using constant-time comparison
     */
    public static function validateCsrfToken(?string $token): bool {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * XSS Prevention: Safely escape HTML entities
     */
    public static function escape(?string $string): string {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Hash password with Argon2id (or bcrypt fallback) and enforce minimum 8 chars
     */
    public static function hashPassword(string $password): string {
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }
        
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password hash
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Rate Limiting: Check if IP/Identifier is locked out (>= 5 attempts in 10 mins)
     */
    public static function isRateLimited(PDO $db, string $identifier, string $ip): bool {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM login_attempts 
            WHERE (ip_address = ? OR identifier = ?) 
            AND attempted_at > (NOW() - INTERVAL 10 MINUTE)
        ");
        $stmt->execute([$ip, $identifier]);
        $count = $stmt->fetchColumn();
        
        return $count >= 5;
    }

    /**
     * Rate Limiting: Record a failed attempt
     */
    public static function recordFailedAttempt(PDO $db, string $identifier, string $ip) {
        $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, identifier, attempted_at) VALUES (?, ?, NOW())");
        $stmt->execute([$ip, $identifier]);
    }

    /**
     * Rate Limiting: Clear attempts after successful login
     */
    public static function clearLoginAttempts(PDO $db, string $identifier, string $ip) {
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR identifier = ?");
        $stmt->execute([$ip, $identifier]);
    }
}
