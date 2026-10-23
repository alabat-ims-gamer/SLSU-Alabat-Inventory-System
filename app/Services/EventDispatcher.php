<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Throwable;

/**
 * EventDispatcher — Lightweight Real-Time Event Publisher & Watermark Engine
 * 
 * Manages zero-dependency event publishing, role/user scoping, and lazy 1-hour
 * rolling window pruning. Works seamlessly across Apache, XAMPP, and Railway.
 */
class EventDispatcher {

    // Standardized System Event Types
    public const EVENT_ITEM_STATUS_CHANGED     = 'ITEM_STATUS_CHANGED';
    public const EVENT_ITEM_MUTATED            = 'ITEM_MUTATED';
    public const EVENT_LOAN_REQUESTED          = 'LOAN_REQUESTED';
    public const EVENT_LOAN_APPROVED           = 'LOAN_APPROVED';
    public const EVENT_LOAN_DENIED             = 'LOAN_DENIED';
    public const EVENT_LOAN_RETURNED           = 'LOAN_RETURNED';
    public const EVENT_USER_REGISTERED         = 'USER_REGISTERED';
    public const EVENT_USER_STATUS_CHANGED     = 'USER_STATUS_CHANGED';
    public const EVENT_CLEARANCE_MUTATED       = 'CLEARANCE_MUTATED';
    public const EVENT_NOTIFICATION_DISPATCHED = 'NOTIFICATION_DISPATCHED';

    /**
     * Dispatch an event to the real-time event log.
     *
     * @param PDO         $db           Active database connection
     * @param string      $eventType    Event classification (e.g. ITEM_STATUS_CHANGED)
     * @param string      $entityType   Subject table/entity (item, transaction, user, clearance)
     * @param int         $entityId     Primary key of target entity
     * @param array       $payload      Payload dictionary with change details
     * @param string|null $targetRole   Null for public broadcast, or specific role ('custodian', 'admin')
     * @param int|null    $targetUserId Null for public, or specific user ID for private alerts
     * @return int                      The newly created event_id, or 0 on failure
     */
    public static function dispatch(
        PDO $db,
        string $eventType,
        string $entityType,
        int $entityId,
        array $payload = [],
        ?string $targetRole = null,
        ?int $targetUserId = null
    ): int {
        try {
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($jsonPayload === false) {
                $jsonPayload = '{}';
            }

            $stmt = $db->prepare("
                INSERT INTO system_events 
                (event_type, entity_type, entity_id, payload, target_role, target_user_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $eventType,
                $entityType,
                $entityId,
                $jsonPayload,
                $targetRole !== '' ? $targetRole : null,
                $targetUserId !== null && $targetUserId > 0 ? $targetUserId : null
            ]);

            $eventId = (int)$db->lastInsertId();

            // Lazy rolling-window cleanup (1 in 25 chance per dispatch)
            // Deletes events older than 1 hour to keep table compact & fast
            if (mt_rand(1, 25) === 1) {
                self::pruneOldEvents($db);
            }

            return $eventId;
        } catch (Throwable $e) {
            error_log('[EventDispatcher::dispatch] Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retrieve events newer than a given watermark ID, strictly scoped by role and user ID.
     *
     * @param PDO         $db           Active database connection
     * @param int         $afterEventId The client's last observed event ID
     * @param string|null $userRole     The authenticated user's role
     * @param int|null    $userId       The authenticated user's ID
     * @param int         $limit        Maximum events to return per batch
     * @return array                    List of decoded event objects
     */
    public static function getEventsSince(
        PDO $db,
        int $afterEventId,
        ?string $userRole = null,
        ?int $userId = null,
        int $limit = 50
    ): array {
        try {
            $limit = max(1, min(100, $limit));

            $sql = "
                SELECT event_id, event_type, entity_type, entity_id, payload, target_role, target_user_id, created_at
                FROM system_events
                WHERE event_id > ?
                  AND (
                    (target_role IS NULL AND target_user_id IS NULL)
                    OR (? IS NOT NULL AND target_role = ? AND target_user_id IS NULL)
                    OR (? IS NOT NULL AND target_user_id = ?)
                  )
                ORDER BY event_id ASC
                LIMIT " . (int)$limit;

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $afterEventId,
                $userRole,
                $userRole,
                $userId,
                $userId
            ]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $events = [];

            foreach ($rows as $row) {
                $payload = json_decode((string)$row['payload'], true);
                if (!is_array($payload)) {
                    $payload = [];
                }

                $events[] = [
                    'event_id'       => (int)$row['event_id'],
                    'event_type'     => $row['event_type'],
                    'entity_type'    => $row['entity_type'],
                    'entity_id'      => (int)$row['entity_id'],
                    'payload'        => $payload,
                    'target_role'    => $row['target_role'],
                    'target_user_id' => $row['target_user_id'] !== null ? (int)$row['target_user_id'] : null,
                    'created_at'     => $row['created_at'],
                ];
            }

            return $events;
        } catch (Throwable $e) {
            error_log('[EventDispatcher::getEventsSince] Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the latest event_id currently in the database.
     * Useful for setting the initial watermark in <meta name="last-event-id">.
     */
    public static function getLatestEventId(PDO $db): int {
        try {
            $stmt = $db->query("SELECT COALESCE(MAX(event_id), 0) FROM system_events");
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Prune events older than 1 hour.
     */
    public static function pruneOldEvents(PDO $db): void {
        try {
            $db->exec("DELETE FROM system_events WHERE created_at < NOW() - INTERVAL 1 HOUR");
        } catch (Throwable $e) {
            error_log('[EventDispatcher::pruneOldEvents] Error: ' . $e->getMessage());
        }
    }
}
