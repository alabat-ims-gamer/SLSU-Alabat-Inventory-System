<?php
namespace App\Models;

use PDO;

class AuditLog {
    
    /**
     * Log a security or administrative action
     * 
     * @param PDO $db The database connection
     * @param int|null $userId The ID of the user performing the action (null for system/guest)
     * @param string $actionType Action type (e.g., 'LOGIN_SUCCESS', 'USER_APPROVED')
     * @param string $description Detailed description of the action
     */
    public static function log(PDO $db, ?int $userId, string $actionType, string $description): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        
        // Verify user exists in database to prevent FK constraint failure (e.g. if user was deleted)
        if ($userId !== null && $userId > 0) {
            try {
                $chk = $db->prepare("SELECT user_id FROM users WHERE user_id = ?");
                $chk->execute([$userId]);
                if (!$chk->fetchColumn()) {
                    $userId = null;
                }
            } catch (\Exception $e) {
                $userId = null;
            }
        } else {
            $userId = null;
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action_type, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $actionType,
                $description,
                $ip,
                substr($userAgent, 0, 255) // Ensure it fits in the VARCHAR(255)
            ]);
        } catch (\PDOException $e) {
            // Fallback: If FK constraint fails for any reason, safely record with NULL user_id
            try {
                $stmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, action_type, description, ip_address, user_agent)
                    VALUES (NULL, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $actionType,
                    $description,
                    $ip,
                    substr($userAgent, 0, 255)
                ]);
            } catch (\Exception $ignored) {
                // Fail silently to prevent throwing fatal errors
            }
        }
    }
    
    /**
     * Alias for log() to support uniform recording across controllers
     */
    public static function record(PDO $db, ?int $userId, string $actionType, string $description): void {
        self::log($db, $userId, $actionType, $description);
    }

    /**
     * Get recent audit logs with optional filtering
     * 
     * @param PDO $db The database connection
     * @param int $limit Max number of logs to return
     * @param string|null $actionFilter Optional action type to filter by
     * @return array
     */
    public static function getRecentLogs(PDO $db, int $limit = 50, ?string $actionFilter = null): array {
        $sql = "
            SELECT a.*, u.full_name as user_name, u.email as user_email, u.avatar_path as user_avatar
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.user_id
        ";
        
        $params = [];
        if ($actionFilter) {
            $sql .= " WHERE a.action_type = ?";
            $params[] = $actionFilter;
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT " . (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get recent audit logs for a specific user (Personal Activity)
     * 
     * @param PDO $db The database connection
     * @param int $userId The ID of the user
     * @param int $limit Max logs to retrieve
     * @return array
     */
    public static function getUserLogs(PDO $db, int $userId, int $limit = 20): array {
        $stmt = $db->prepare("
            SELECT * FROM audit_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT " . (int)$limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get distinct action types currently present in the audit log
     */
    public static function getDistinctActionTypes(PDO $db): array {
        $stmt = $db->query("SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Get paginated and filtered audit logs
     */
    public static function getFilteredLogs(PDO $db, array $filters = [], int $limit = 20, int $offset = 0): array {
        $sql = "
            SELECT a.*, u.full_name as user_name, u.email as user_email, u.role as user_role, u.avatar_path as user_avatar
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['action_type'])) {
            $sql .= " AND a.action_type = ?";
            $params[] = $filters['action_type'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (a.description LIKE ? OR a.ip_address LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND a.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND a.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count total audit logs matching filters
     */
    public static function countFilteredLogs(PDO $db, array $filters = []): int {
        $sql = "
            SELECT COUNT(*)
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['action_type'])) {
            $sql .= " AND a.action_type = ?";
            $params[] = $filters['action_type'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (a.description LIKE ? OR a.ip_address LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND a.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND a.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get summary KPI statistics for the Audit Trail view
     */
    public static function getSummaryStats(PDO $db): array {
        $totalLogs = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        
        $past24h = (int)$db->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE created_at >= (NOW() - INTERVAL 24 HOUR)
        ")->fetchColumn();

        $securityEvents = (int)$db->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action_type IN ('LOGIN_SUCCESS', 'LOGIN_FAILED', 'RATE_LIMITED', 'LOGOUT', 'PASSWORD_OVERRIDE')
        ")->fetchColumn();

        $adminActions = (int)$db->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action_type IN ('USER_APPROVED', 'USER_REJECTED', 'USER_DELETED', 'USER_EDITED', 'PASSWORD_OVERRIDE', 'ROLE_CHANGED')
        ")->fetchColumn();

        return [
            'total' => $totalLogs,
            'past_24h' => $past24h,
            'security_events' => $securityEvents,
            'admin_actions' => $adminActions,
        ];
    }
}
