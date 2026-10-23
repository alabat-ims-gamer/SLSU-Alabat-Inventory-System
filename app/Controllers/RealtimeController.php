<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Throwable;
use App\Config\Database;
use App\Services\EventDispatcher;

/**
 * RealtimeController — Low-Latency Real-Time Synchronization Engine
 * 
 * Provides both Server-Sent Events (SSE) streaming and lightweight Delta Polling
 * with immediate session-lock release (session_write_close) and zero cloud service costs.
 */
class RealtimeController {

    /**
     * Send standard JSON response and exit
     */
    private static function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Fast Delta Polling API: /api/realtime/sync
     * Returns newly emitted system events since ?after_id=X in <2ms.
     */
    public static function sync(): void {
        // 1. Session verification & immediate lock release
        if (!isset($_SESSION['user_id'])) {
            self::jsonResponse(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $userId   = (int)$_SESSION['user_id'];
        $userRole = (string)($_SESSION['role'] ?? 'student');

        // CRITICAL: Release session file lock immediately so other user requests are never blocked
        session_write_close();

        $afterId = max(0, (int)($_GET['after_id'] ?? ($_GET['since'] ?? 0)));
        $limit   = min(50, max(1, (int)($_GET['limit'] ?? 50)));

        try {
            $db = Database::getConnection();

            // Client bootstrapping without watermark: give latest ID so it doesn't replay historical events
            if ($afterId === 0) {
                $latestId = EventDispatcher::getLatestEventId($db);
                self::jsonResponse([
                    'success'       => true,
                    'bootstrapped'  => true,
                    'last_event_id' => $latestId,
                    'events'        => []
                ]);
            }

            $events = EventDispatcher::getEventsSince($db, $afterId, $userRole, $userId, $limit);
            $newLastEventId = !empty($events) ? (int)end($events)['event_id'] : $afterId;

            self::jsonResponse([
                'success'       => true,
                'last_event_id' => $newLastEventId,
                'events'        => $events
            ]);
        } catch (Throwable $e) {
            error_log('[RealtimeController::sync] Error: ' . $e->getMessage());
            self::jsonResponse([
                'success'       => false,
                'last_event_id' => $afterId,
                'events'        => [],
                'error'         => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Native Server-Sent Events (SSE) Streaming API: /api/realtime/stream
     * Streams events in real-time with heartbeats for up to 25s, then gracefully terminates.
     * The browser's native EventSource automatically reconnects with Last-Event-ID.
     */
    public static function stream(): void {
        // 1. Session verification & immediate lock release
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo "event: error\ndata: " . json_encode(['message' => 'Unauthenticated']) . "\n\n";
            exit;
        }

        $userId   = (int)$_SESSION['user_id'];
        $userRole = (string)($_SESSION['role'] ?? 'student');

        // CRITICAL: Release session lock immediately before starting long-running streaming loop
        session_write_close();

        // 2. Set streaming response headers
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Clear any active output buffers
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $headerAfterId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? (int)$_SERVER['HTTP_LAST_EVENT_ID'] : 0;
        $queryAfterId  = isset($_GET['after_id']) ? (int)$_GET['after_id'] : 0;
        $afterId       = max($headerAfterId, $queryAfterId);

        try {
            $db = Database::getConnection();

            if ($afterId === 0) {
                $afterId = EventDispatcher::getLatestEventId($db);
            }

            echo ": connected\n";
            echo "retry: 3000\n\n";
            flush();

            // Run streaming loop for up to 25 seconds
            $startTime = time();
            $maxDuration = 25;

            while ((time() - $startTime) < $maxDuration) {
                if (connection_aborted() === 1) {
                    break;
                }

                $events = EventDispatcher::getEventsSince($db, $afterId, $userRole, $userId, 20);

                if (!empty($events)) {
                    foreach ($events as $ev) {
                        $afterId = (int)$ev['event_id'];
                        $payloadJson = json_encode($ev, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                        echo "id: {$afterId}\n";
                        echo "event: {$ev['event_type']}\n";
                        echo "data: {$payloadJson}\n\n";
                    }
                    flush();
                } else {
                    // Send periodic heartbeat ping comment to keep connection alive
                    echo ": ping\n\n";
                    flush();
                }

                sleep(2);
            }

            // Normal graceful closure; client EventSource will auto-reconnect
            exit;
        } catch (Throwable $e) {
            error_log('[RealtimeController::stream] Error: ' . $e->getMessage());
            echo "event: error\ndata: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
            flush();
            exit;
        }
    }
}
