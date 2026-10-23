<?php
namespace App\Middleware;

class RoleMiddleware {
    /**
     * Ensure the authenticated user has one of the allowed roles.
     * 
     * @param array $allowedRoles Array of roles (e.g. ['admin', 'executive_admin'])
     */
    public static function allow(array $allowedRoles): void {
        // First, ensure the user is authenticated
        AuthMiddleware::check();

        $userRole = $_SESSION['role'] ?? null;

        if (!$userRole || !in_array($userRole, $allowedRoles, true)) {
            // User does not have permission
            http_response_code(403);
            
            // Optionally, load a 403 Forbidden view instead of plain text
            echo "<div style='font-family:sans-serif; text-align:center; padding-top: 50px;'>";
            echo "<h2>403 Forbidden</h2>";
            echo "<p>You do not have permission to access this resource.</p>";
            echo "<a href='" . htmlspecialchars(url('/dashboard'), ENT_QUOTES, 'UTF-8') . "' style='color:#059669; text-decoration:none;'>Return to Dashboard</a>";
            echo "</div>";
            exit;
        }
    }
}
