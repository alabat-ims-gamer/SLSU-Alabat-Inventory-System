<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Helpers\Security;
use App\Services\NotificationService;
use Throwable;

/**
 * NotificationController
 * Handles asynchronous notification feed retrieval and status mutations.
 */
class NotificationController {

    /**
     * GET /api/notifications
     * Return JSON array of the latest 10 notifications + unread count.
     */
    public static function getFeed(): void {
        AuthMiddleware::check();
        header('Content-Type: application/json; charset=utf-8');

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        try {
            $db = Database::getConnection();
            $data = NotificationService::getFeed($db, $userId);

            echo json_encode([
                'success'       => true,
                'unread_count'  => $data['unread_count'],
                'notifications' => $data['notifications']
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to load notifications']);
        }
    }

    /**
     * POST /api/notifications/read
     * Mark all or a specific notification as read.
     */
    public static function markRead(): void {
        AuthMiddleware::check();
        header('Content-Type: application/json; charset=utf-8');

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        // Accept payload from either JSON body or POST form
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        // Validate CSRF token
        $token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            return;
        }

        $notificationId = isset($input['notification_id']) ? (int)$input['notification_id'] : null;

        try {
            $db = Database::getConnection();
            $success = NotificationService::markAsRead($db, $userId, $notificationId);

            $unreadCount = NotificationService::getFeed($db, $userId)['unread_count'];

            echo json_encode([
                'success'      => $success,
                'unread_count' => $unreadCount
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update notification state']);
        }
    }
}
