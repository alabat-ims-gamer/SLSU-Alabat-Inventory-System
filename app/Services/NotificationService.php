<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Throwable;

/**
 * Unified Notification Service
 * Manages in-app alerts, unread tracking, preference gating, and automatic ring-buffer pruning (max 10 items).
 */
class NotificationService {

    public const MAX_PER_USER = 10;

    // Notification visual types
    public const TYPE_INFO    = 'info';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_DANGER  = 'danger';

    // Notification categories
    public const CAT_LOAN_DUE      = 'LOAN_DUE';
    public const CAT_LOAN_OVERDUE  = 'LOAN_OVERDUE';
    public const CAT_LOAN_CHECKOUT = 'LOAN_CHECKOUT';
    public const CAT_LOAN_DENIED   = 'LOAN_DENIED';
    public const CAT_LOAN_RETURN   = 'LOAN_RETURN';
    public const CAT_DAMAGE_HOLD   = 'DAMAGE_HOLD';
    public const CAT_CLEARANCE     = 'CLEARANCE';
    public const CAT_ACCOUNT       = 'ACCOUNT';
    public const CAT_SECURITY      = 'SECURITY';

    /**
     * Dispatch a notification to a user with auto-pruning.
     * Respects user alert preferences if set.
     */
    public static function create(
        PDO $db,
        int $userId,
        string $category,
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        ?string $actionUrl = null
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        // 1. Verify user notification preferences
        if (!self::isAllowedByPreferences($db, $userId, $category)) {
            return false;
        }

        try {
            // 2. Insert notification
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, category, title, message, action_url, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
            ");
            $success = $stmt->execute([
                $userId,
                $type,
                $category,
                mb_substr($title, 0, 150),
                $message,
                $actionUrl ? mb_substr($actionUrl, 0, 255) : null
            ]);

            if (!$success) {
                return false;
            }

            // 3. Auto-Pruning: Enforce 10-notification ring buffer per user
            self::pruneUserNotifications($db, $userId);

            // 4. Real-Time Broadcast: Notify recipient client immediately
            $notifId = (int)$db->lastInsertId();
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_NOTIFICATION_DISPATCHED,
                'notification',
                $notifId,
                [
                    'notification_id' => $notifId,
                    'user_id'         => $userId,
                    'type'            => $type,
                    'category'        => $category,
                    'title'           => mb_substr($title, 0, 150),
                    'message'         => $message,
                    'action_url'      => $actionUrl ? mb_substr($actionUrl, 0, 255) : null,
                    'created_at'      => date('Y-m-d H:i:s')
                ],
                null,
                $userId
            );

            return true;
        } catch (Throwable $e) {
            error_log('[NotificationService::create] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to all users matching a specific role (e.g. all custodians or admins).
     */
    public static function broadcastToRole(
        PDO $db,
        string $role,
        string $category,
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        ?string $actionUrl = null
    ): int {
        try {
            $stmt = $db->prepare("SELECT user_id FROM users WHERE role = ? AND account_status = 'approved'");
            $stmt->execute([$role]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $count = 0;
            foreach ($userIds as $uid) {
                if (self::create($db, (int)$uid, $category, $title, $message, $type, $actionUrl)) {
                    $count++;
                }
            }
            return $count;
        } catch (Throwable $e) {
            error_log('[NotificationService::broadcastToRole] Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Auto-prune user notifications beyond the latest 10.
     */
    public static function pruneUserNotifications(PDO $db, int $userId): void {
        try {
            $stmt = $db->prepare("
                DELETE FROM notifications
                WHERE user_id = ?
                  AND notification_id NOT IN (
                      SELECT notification_id FROM (
                          SELECT notification_id 
                          FROM notifications 
                          WHERE user_id = ? 
                          ORDER BY created_at DESC, notification_id DESC 
                          LIMIT ?
                      ) AS keep_rows
                  )
            ");
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $userId, PDO::PARAM_INT);
            $stmt->bindValue(3, self::MAX_PER_USER, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('[NotificationService::prune] Error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve the latest 10 notifications along with unread count for a user.
     */
    public static function getFeed(PDO $db, int $userId): array {
        try {
            // Fetch latest up to 10
            $stmt = $db->prepare("
                SELECT notification_id, type, category, title, message, action_url, is_read, created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC, notification_id DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, self::MAX_PER_USER, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch unread count
            $countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $countStmt->execute([$userId]);
            $unreadCount = (int)$countStmt->fetchColumn();

            // Format human-friendly relative time
            foreach ($notifications as &$n) {
                $n['notification_id'] = (int)$n['notification_id'];
                $n['is_read']         = (int)$n['is_read'] === 1;
                $n['time_ago']        = self::formatTimeAgo($n['created_at']);
            }
            unset($n);

            return [
                'unread_count'  => $unreadCount,
                'notifications' => $notifications
            ];
        } catch (Throwable $e) {
            error_log('[NotificationService::getFeed] Error: ' . $e->getMessage());
            return [
                'unread_count'  => 0,
                'notifications' => []
            ];
        }
    }

    /**
     * Mark a single notification or all notifications as read for a user.
     */
    public static function markAsRead(PDO $db, int $userId, ?int $notificationId = null): bool {
        try {
            if ($notificationId !== null && $notificationId > 0) {
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND notification_id = ?");
                return $stmt->execute([$userId, $notificationId]);
            }

            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            return $stmt->execute([$userId]);
        } catch (Throwable $e) {
            error_log('[NotificationService::markAsRead] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user preferences allow this notification category.
     */
    private static function isAllowedByPreferences(PDO $db, int $userId, string $category): bool {
        try {
            // Check session cache first
            $prefs = $_SESSION['notification_preferences'] ?? null;
            if ($prefs === null) {
                $stmt = $db->prepare("SELECT notification_preferences FROM users WHERE user_id = ?");
                $stmt->execute([$userId]);
                $raw = $stmt->fetchColumn();
                $prefs = !empty($raw) ? json_decode((string)$raw, true) : [];
            }

            if (!is_array($prefs)) {
                return true;
            }

            // Map categories to preference keys
            return match ($category) {
                self::CAT_LOAN_DUE     => (bool)($prefs['alert_due_reminders'] ?? $prefs['email_due_reminders'] ?? true),
                self::CAT_LOAN_OVERDUE => (bool)($prefs['alert_overdue_alerts'] ?? $prefs['email_overdue_alerts'] ?? true),
                self::CAT_SECURITY     => (bool)($prefs['alert_security_logins'] ?? $prefs['email_security_logins'] ?? true),
                default                => true,
            };
        } catch (Throwable $e) {
            return true;
        }
    }

    /**
     * Compute unified sidebar and topbar badge metrics across roles.
     */
    public static function getSidebarBadges(PDO $db, int $userId, string $role): array {
        $badges = [
            'overdue'         => 0,
            'pending'         => 0,
            'holds'           => 0,
            'my_obligations'  => 0,
            'unread_notifs'   => 0,
            'pending_borrows' => 0,
        ];

        try {
            // 1. Unread notifications
            $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            $badges['unread_notifs'] = (int)$stmt->fetchColumn();

            // 2. Role-specific counts
            if ($role === 'admin') {
                $stmt = $db->query("SELECT COUNT(*) FROM users WHERE account_status = 'pending'");
                $badges['pending'] = (int)$stmt->fetchColumn();
            } elseif ($role === 'custodian') {
                $stmt = $db->query("
                    SELECT COUNT(*) FROM transactions 
                    WHERE transaction_type = 'borrow' AND actual_return_date IS NULL AND expected_return_date < NOW()
                ");
                $badges['overdue'] = (int)$stmt->fetchColumn();

                // Badge displays count of student receipts waiting for custodian verification
                $stmt = $db->query("SELECT COUNT(*) FROM damage_incidents WHERE incident_status = 'pending_verification'");
                $badges['holds'] = (int)$stmt->fetchColumn();

                // Transmitted IARs ready for ingestion into asset catalog
                $stmt = $db->query("SELECT COUNT(*) FROM inspection_reports WHERE status = 'TRANSMITTED'");
                $badges['iar_incoming'] = (int)$stmt->fetchColumn();

                // New incoming borrow requests awaiting counter verification & pickup
                $stmt = $db->query("
                    SELECT COUNT(*) FROM items 
                    WHERE status = 'requested' 
                      AND (reservation_expires_at IS NULL OR reservation_expires_at > NOW())
                ");
                $badges['pending_borrows'] = (int)$stmt->fetchColumn();
            } elseif ($role === 'inspector') {
                $stmt = $db->query("SELECT COUNT(*) FROM inspection_reports WHERE status = 'PENDING_SIGNATURES'");
                $badges['iar_pending'] = (int)$stmt->fetchColumn();
            } elseif ($role === 'executive_admin') {
                $stmt = $db->query("SELECT COUNT(*) FROM damage_incidents WHERE incident_status != 'cleared'");
                $badges['holds'] = (int)$stmt->fetchColumn();
            } elseif (in_array($role, ['student', 'faculty'], true)) {
                // Check user's overdue loans
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM transactions 
                    WHERE user_id = ? AND transaction_type = 'borrow' AND actual_return_date IS NULL AND expected_return_date < NOW()
                ");
                $stmt->execute([$userId]);
                $myOverdue = (int)$stmt->fetchColumn();

                // Check active damage holds on user
                $stmt = $db->prepare("SELECT COUNT(*) FROM damage_incidents WHERE borrower_id = ? AND incident_status != 'cleared'");
                $stmt->execute([$userId]);
                $myHolds = (int)$stmt->fetchColumn();

                $badges['my_obligations'] = $myOverdue + $myHolds;
                $badges['overdue'] = $myOverdue;
                $badges['holds']   = $myHolds;
            }
        } catch (Throwable $e) {
            error_log('[NotificationService::getSidebarBadges] Error: ' . $e->getMessage());
        }

        return $badges;
    }

    /**
     * Compute human-readable relative time string.
     */
    public static function formatTimeAgo(string $datetime): string {
        $timestamp = strtotime($datetime);
        if (!$timestamp) return 'Recently';

        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $mins = (int)floor($diff / 60);
            return $mins . 'm ago';
        }
        if ($diff < 86400) {
            $hours = (int)floor($diff / 3600);
            return $hours . 'h ago';
        }
        if ($diff < 172800) {
            return 'Yesterday';
        }
        $days = (int)floor($diff / 86400);
        if ($days < 30) {
            return $days . 'd ago';
        }
        return date('M j', $timestamp);
    }
}
