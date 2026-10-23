<?php
namespace App\Models;

use PDO;
use Exception;
use App\Helpers\Security;

class User {

    /**
     * Canonical Academic Programs (Students)
     */
    public const ACADEMIC_PROGRAMS = [
        'BSIT'     => 'Bachelor of Science in Industrial Technology (BSIT)',
        'BSFAS'    => 'Bachelor of Science in Fisheries and Aquatic Sciences (BSFAS)',
        'BIndTech' => 'Bachelor in Industrial Technology (BIndTech)',
    ];

    /**
     * Canonical Administrative & Faculty Units
     */
    public const DEPARTMENTS = [
        'Campus Administration' => 'Campus Administration',
        'Faculty'               => 'Faculty',
    ];

    /**
     * Resolve descriptive program or department label from code
     */
    public static function getProgramLabel(?string $code): string {
        if ($code === null || $code === '') {
            return '';
        }
        if ($code === 'Faculty & Staff') {
            return 'Faculty';
        }
        return self::ACADEMIC_PROGRAMS[$code] ?? self::DEPARTMENTS[$code] ?? $code;
    }

    /**
     * Find user by Email, ID Number, or Username.
     */
    public static function findByLoginCredential(PDO $db, string $identifier): ?array {
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE email = ? OR id_number = ? OR username = ?
        ");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Create a new pending user registration
     */
    public static function create(PDO $db, array $data): int {
        $stmt = $db->prepare("
            INSERT INTO users (id_number, username, full_name, email, password_hash, department_program, account_status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $data['id_number'],
            $data['username'],
            $data['full_name'],
            $data['email'],
            Security::hashPassword($data['password']),
            $data['department_program']
        ]);
        
        return (int)$db->lastInsertId();
    }

    /**
     * Get all pending users for Admin approval
     */
    public static function getPendingUsers(PDO $db): array {
        $stmt = $db->prepare("SELECT * FROM users WHERE account_status = 'pending' ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all users for the Admin directory
     */
    public static function getAllUsers(PDO $db): array {
        $stmt = $db->prepare("SELECT * FROM users ORDER BY full_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Approve a pending user and assign a role
     */
    public static function approve(PDO $db, int $userId, string $role, int $maxLoans, int $adminId): bool {
        $stmt = $db->prepare("
            UPDATE users 
            SET account_status = 'approved', role = ?, max_concurrent_loans = ?, approved_by = ?, approved_at = NOW() 
            WHERE user_id = ? AND account_status = 'pending'
        ");
        return $stmt->execute([$role, $maxLoans, $adminId, $userId]);
    }

    /**
     * Reject a pending user
     */
    public static function reject(PDO $db, int $userId, ?string $reason): bool {
        $stmt = $db->prepare("
            UPDATE users 
            SET account_status = 'rejected', rejection_reason = ? 
            WHERE user_id = ? AND account_status = 'pending'
        ");
        return $stmt->execute([$reason, $userId]);
    }

    /**
     * Hard delete a pending request (Purge)
     */
    public static function purge(PDO $db, int $userId): bool {
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ? AND account_status = 'pending'");
        return $stmt->execute([$userId]);
    }

    /**
     * Update user details (for Admin edit)
     */
    public static function updateUser(PDO $db, int $userId, array $data): bool {
        $stmt = $db->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, department_program = ?, role = ?, account_status = ?, max_concurrent_loans = ?
            WHERE user_id = ?
        ");
        return $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['department_program'],
            $data['role'],
            $data['account_status'],
            $data['max_concurrent_loans'],
            $userId
        ]);
    }

    /**
     * Override a user's password (Admin action)
     */
    public static function overridePassword(PDO $db, int $userId, string $newPassword): bool {
        $hash = Security::hashPassword($newPassword);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        return $stmt->execute([$hash, $userId]);
    }

    /**
     * Check if user has active or past transaction records.
     * Prevents hard deletion to maintain audit integrity.
     */
    public static function hasTransactionHistory(PDO $db, int $userId): bool {
        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        return $count > 0;
    }

    /**
     * Safely delete a user, respecting transaction integrity.
     */
    public static function safeDelete(PDO $db, int $userId): void {
        if (self::hasTransactionHistory($db, $userId)) {
            throw new Exception("Cannot delete user: The user has active or historical borrowing records. Please suspend the account instead.");
        }
        
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Find user by primary user_id
     */
    public static function findById(PDO $db, int $userId): ?array {
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Check if email is already in use by another user
     */
    public static function isEmailTakenByOther(PDO $db, string $email, int $excludeUserId): bool {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $excludeUserId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Update user profile information (Self-Service)
     */
    public static function updateProfile(PDO $db, int $userId, array $data): bool {
        $stmt = $db->prepare("
            UPDATE users 
            SET full_name = ?, department_program = ?, phone_number = ?
            WHERE user_id = ?
        ");
        return $stmt->execute([
            $data['full_name'],
            $data['department_program'],
            $data['phone_number'] ?? null,
            $userId
        ]);
    }

    /**
     * Update or remove user avatar path
     */
    public static function updateAvatar(PDO $db, int $userId, ?string $avatarPath): bool {
        $stmt = $db->prepare("UPDATE users SET avatar_path = ? WHERE user_id = ?");
        return $stmt->execute([$avatarPath, $userId]);
    }

    /**
     * Update user credentials (Email and/or Password)
     */
    public static function updateCredentials(PDO $db, int $userId, string $email, ?string $newPassword = null): bool {
        if ($newPassword !== null && strlen($newPassword) > 0) {
            $hash = Security::hashPassword($newPassword);
            $stmt = $db->prepare("UPDATE users SET email = ?, password_hash = ? WHERE user_id = ?");
            return $stmt->execute([$email, $hash, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            return $stmt->execute([$email, $userId]);
        }
    }

    /**
     * Update user notification preferences (JSON payload)
     */
    public static function updateNotificationPreferences(PDO $db, int $userId, array $prefs): bool {
        $json = json_encode($prefs, JSON_UNESCAPED_SLASHES);
        $stmt = $db->prepare("UPDATE users SET notification_preferences = ? WHERE user_id = ?");
        return $stmt->execute([$json, $userId]);
    }

    /**
     * Bump session_version to invalidate other active sessions
     */
    public static function bumpSessionVersion(PDO $db, int $userId): int {
        $stmt = $db->prepare("UPDATE users SET session_version = COALESCE(session_version, 1) + 1 WHERE user_id = ?");
        $stmt->execute([$userId]);

        $fetch = $db->prepare("SELECT session_version FROM users WHERE user_id = ?");
        $fetch->execute([$userId]);
        return (int)$fetch->fetchColumn();
    }

    /**
     * Self-deactivate account (Sets account_status to suspended)
     */
    public static function deactivateAccount(PDO $db, int $userId): bool {
        $stmt = $db->prepare("
            UPDATE users 
            SET account_status = 'suspended', rejection_reason = 'Self-deactivated by user'
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }

    /**
     * Check if a user is eligible to borrow items.
     * Evaluates account approval, damage hold flag, overdue loans, and active + pending quotas.
     */
    public static function checkBorrowEligibility(PDO $db, int $userId): array {
        $stmt = $db->prepare("
            SELECT user_id, full_name, role, account_status, max_concurrent_loans, has_damage_hold 
            FROM users 
            WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['eligible' => false, 'reason' => 'User not found.'];
        }

        if ($user['account_status'] !== 'approved') {
            return ['eligible' => false, 'reason' => 'Account is not approved for borrowing.'];
        }

        $hasDamageHold = (bool)($user['has_damage_hold'] ?? false);
        if ($hasDamageHold) {
            return [
                'eligible' => false, 
                'reason' => 'Account Locked: Pending Damage Clearance. Please report to the Property Custodian.',
                'has_damage_hold' => true,
                'has_overdue' => false,
            ];
        }

        $hasOverdue = Transaction::hasOverdueLoans($db, $userId);
        if ($hasOverdue) {
            return [
                'eligible' => false, 
                'reason' => 'Account Locked: You have overdue items. Return all overdue items to restore borrowing access.',
                'has_damage_hold' => false,
                'has_overdue' => true,
            ];
        }

        $activeLoans = Transaction::countActiveLoans($db, $userId);
        $pendingRequests = Item::countPendingRequestsByUser($db, $userId);
        $totalSlotsUsed = $activeLoans + $pendingRequests;
        $maxQuota = (int)($user['max_concurrent_loans'] ?? ($user['role'] === 'faculty' ? 10 : 3));

        if ($totalSlotsUsed >= $maxQuota) {
            return [
                'eligible' => false,
                'reason' => "Borrowing Quota Reached: You are currently using {$totalSlotsUsed} of {$maxQuota} allowed slots.",
                'active_loans' => $activeLoans,
                'pending_requests' => $pendingRequests,
                'total_slots_used' => $totalSlotsUsed,
                'max_quota' => $maxQuota,
                'has_damage_hold' => false,
                'has_overdue' => false,
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'active_loans' => $activeLoans,
            'pending_requests' => $pendingRequests,
            'total_slots_used' => $totalSlotsUsed,
            'max_quota' => $maxQuota,
            'has_damage_hold' => false,
            'has_overdue' => false,
        ];
    }

    /**
     * Clear or set damage clearance hold on a user account.
     */
    public static function setDamageHold(PDO $db, int $userId, bool $hasHold): bool {
        $stmt = $db->prepare("UPDATE users SET has_damage_hold = :hold, updated_at = NOW() WHERE user_id = :user_id");
        return $stmt->execute([
            ':hold' => $hasHold ? 1 : 0,
            ':user_id' => $userId,
        ]);
    }

    /**
     * Get total count of active/approved users across the campus.
     */
    public static function getActiveUsersCount(PDO $db): int {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE account_status = 'approved'");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get user count grouped by department/program.
     */
    public static function getDepartmentDistribution(PDO $db): array {
        $stmt = $db->query("
            SELECT department_program, COUNT(*) as count 
            FROM users 
            WHERE account_status = 'approved' AND department_program IS NOT NULL AND department_program != ''
            GROUP BY department_program 
            ORDER BY count DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

