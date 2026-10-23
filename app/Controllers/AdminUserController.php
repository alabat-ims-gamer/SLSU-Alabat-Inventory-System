<?php
namespace App\Controllers;

use App\Helpers\Security;
use App\Models\User;
use App\Models\AuditLog;
use App\Config\Database;
use App\Middleware\RoleMiddleware;
use App\Services\EventDispatcher;

class AdminUserController {
    
    /**
     * Display the User Management Control Center (Pending Approvals & User Directory)
     */
    public static function index() {
        // Enforce Admin Role only
        RoleMiddleware::allow(['admin']);

        $db = Database::getConnection();

        // Fetch data for the 2 user management tabs
        $pendingUsers = User::getPendingUsers($db);
        $allUsers = User::getAllUsers($db);

        Security::generateCsrfToken();
        
        // Render the view
        require __DIR__ . '/../../views/admin/user_management.php';
    }

    /**
     * Approve a user and assign a role
     */
    public static function approveUser() {
        RoleMiddleware::allow(['admin']);
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';
        $maxLoans = (int)($_POST['max_concurrent_loans'] ?? 3);
        $adminId = $_SESSION['user_id'];

        $validRoles = ['admin', 'custodian', 'executive_admin', 'inspector', 'faculty', 'student'];
        if (!in_array($role, $validRoles)) {
            redirect('/admin/users?error=' . urlencode("Invalid role selected."));
        }

        try {
            if (User::approve($db, $userId, $role, $maxLoans, $adminId)) {
                AuditLog::log($db, $adminId, 'USER_APPROVED', "Approved user ID $userId and assigned role '$role'");
                \App\Services\NotificationService::create(
                    $db,
                    $userId,
                    \App\Services\NotificationService::CAT_ACCOUNT,
                    "Account Approved",
                    "Welcome to SLSU-Alabat IMS! Your account has been reviewed and granted the '" . ucfirst(str_replace('_', ' ', $role)) . "' role.",
                    \App\Services\NotificationService::TYPE_SUCCESS,
                    url('/dashboard')
                );

                // Real-Time Broadcast: Update admin queue and dashboard approvals count
                EventDispatcher::dispatch(
                    $db,
                    EventDispatcher::EVENT_USER_STATUS_CHANGED,
                    'user',
                    $userId,
                    [
                        'action'  => 'approved',
                        'user_id' => $userId,
                        'role'    => $role
                    ],
                    'admin'
                );

                redirect('/admin/users?success=' . urlencode("User approved successfully."));
            } else {
                redirect('/admin/users?error=' . urlencode("Failed to approve user. They may no longer be pending."));
            }
        } catch (\Exception $e) {
            redirect('/admin/users?error=' . urlencode("An error occurred."));
        }
    }

    /**
     * Reject a pending user
     */
    public static function rejectUser() {
        RoleMiddleware::allow(['admin']);
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? '');
        $adminId = $_SESSION['user_id'];

        try {
            if (User::reject($db, $userId, $reason)) {
                AuditLog::log($db, $adminId, 'USER_REJECTED', "Rejected user ID $userId with reason: $reason");

                // Real-Time Broadcast: Remove rejected user from admin queue
                EventDispatcher::dispatch(
                    $db,
                    EventDispatcher::EVENT_USER_STATUS_CHANGED,
                    'user',
                    $userId,
                    [
                        'action'  => 'rejected',
                        'user_id' => $userId,
                        'reason'  => $reason
                    ],
                    'admin'
                );

                redirect('/admin/users?success=' . urlencode("User registration rejected."));
            } else {
                redirect('/admin/users?error=' . urlencode("Failed to reject user."));
            }
        } catch (\Exception $e) {
            redirect('/admin/users?error=' . urlencode("An error occurred."));
        }
    }

    /**
     * Purge (hard delete) a pending user
     */
    public static function purgeUser() {
        RoleMiddleware::allow(['admin']);
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $adminId = $_SESSION['user_id'];

        try {
            if (User::purge($db, $userId)) {
                AuditLog::log($db, $adminId, 'USER_PURGED', "Purged pending registration for user ID $userId");
                redirect('/admin/users?success=' . urlencode("Pending registration purged successfully."));
            } else {
                redirect('/admin/users?error=' . urlencode("Failed to purge user. Only pending users can be purged."));
            }
        } catch (\Exception $e) {
            redirect('/admin/users?error=' . urlencode("An error occurred."));
        }
    }

    /**
     * Edit an existing user (Directory Tab)
     */
    public static function editUser() {
        RoleMiddleware::allow(['admin']);
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $adminId = $_SESSION['user_id'];
        
        // Prevent admin from editing their own account in User Management Center
        if ($userId === $adminId) {
            redirect('/admin/users?error=' . urlencode("You cannot edit your own account from the User Management Center."));
        }

        // Fetch existing status or default to approved
        $stmt = $db->prepare("SELECT account_status FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $currentStatus = $stmt->fetchColumn() ?: 'approved';

        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'department_program' => trim($_POST['department_program'] ?? ''),
            'role' => $_POST['role'] ?? '',
            'account_status' => $_POST['account_status'] ?? $currentStatus,
            'max_concurrent_loans' => (int)($_POST['max_concurrent_loans'] ?? 3)
        ];

        try {
            if (User::updateUser($db, $userId, $data)) {
                AuditLog::log($db, $adminId, 'ROLE_MODIFIED', "Updated profile for user ID $userId to role '{$data['role']}' and max loans {$data['max_concurrent_loans']}");
                redirect('/admin/users?success=' . urlencode("User updated successfully."));
            } else {
                redirect('/admin/users?error=' . urlencode("Failed to update user."));
            }
        } catch (\Exception $e) {
            // Handle duplicate email unique constraint violations here
            redirect('/admin/users?error=' . urlencode("Error updating user. Email may already be in use."));
        }
    }

    /**
     * Override User Password
     */
    public static function overridePassword() {
        RoleMiddleware::allow(['admin']);
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        $adminId = $_SESSION['user_id'];

        // Prevent admin from overriding their own password in User Management Center
        if ($userId === $adminId) {
            redirect('/admin/users?error=' . urlencode("You cannot override your own password from the User Management Center."));
        }

        if (strlen($newPassword) < 8) {
            redirect('/admin/users?error=' . urlencode("Password must be at least 8 characters long."));
        }

        try {
            if (User::overridePassword($db, $userId, $newPassword)) {
                AuditLog::log($db, $adminId, 'PASSWORD_OVERRIDE', "Admin executed password override for user ID $userId");
                redirect('/admin/users?success=' . urlencode("Password successfully overridden."));
            } else {
                redirect('/admin/users?error=' . urlencode("Failed to override password."));
            }
        } catch (\Exception $e) {
            redirect('/admin/users?error=' . urlencode("An error occurred."));
        }
    }

    /**
     * Safely Delete User
     */
    public static function deleteUser() {
        RoleMiddleware::allow(['admin']);
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $adminId = $_SESSION['user_id'];

        if ($userId === $adminId) {
            redirect('/admin/users?error=' . urlencode("You cannot delete your own account."));
        }

        try {
            User::safeDelete($db, $userId);
            AuditLog::log($db, $adminId, 'USER_DELETED', "Admin deleted user ID $userId");
            redirect('/admin/users?success=' . urlencode("User deleted successfully."));
        } catch (\Exception $e) {
            // This catches the "Cannot delete user: The user has active or historical borrowing records..." exception
            redirect('/admin/users?error=' . urlencode($e->getMessage()));
        }
    }
}
