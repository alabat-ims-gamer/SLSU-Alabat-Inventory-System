<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Exception;
use App\Helpers\Security;
use App\Models\User;
use App\Models\AuditLog;
use App\Config\Database;
use App\Middleware\AuthMiddleware;

class SettingsController {

    /**
     * Display the User Settings & Profile Center
     */
    public static function index(): void {
        AuthMiddleware::check();
        $db = Database::getConnection();

        $userId = (int)$_SESSION['user_id'];
        $user = User::findById($db, $userId);

        if (!$user) {
            Security::destroySession();
            redirect('/login?error=' . urlencode("User record not found."));
        }

        // Fetch user's personal activity / audit trail
        $personalLogs = AuditLog::getUserLogs($db, $userId, 15);

        // Fetch transaction statistics for account status & safety
        $hasHistory = User::hasTransactionHistory($db, $userId);

        // Parse notification preferences
        $preferences = !empty($user['notification_preferences'])
            ? json_decode($user['notification_preferences'], true)
            : [
                'alert_due_reminders'   => true,
                'alert_overdue_alerts'  => true,
                'alert_security_logins' => true,
            ];

        // Active tab
        $activeTab = $_GET['tab'] ?? 'profile';
        $validTabs = ['profile', 'security', 'sessions', 'notifications', 'activity'];
        if (!in_array($activeTab, $validTabs, true)) {
            $activeTab = 'profile';
        }

        // Device / session metadata for sessions overview
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';
        $loginTimestamp = $_SESSION['login_time'] ?? time();

        Security::generateCsrfToken();

        // Render the view
        require ROOT_PATH . '/views/settings/index.php';
    }

    /**
     * Update Profile Information (Full Name, Department, Phone)
     */
    public static function updateProfile(): void {
        AuthMiddleware::check();
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)$_SESSION['user_id'];
        $fullName = trim($_POST['full_name'] ?? '');
        $department = trim($_POST['department_program'] ?? '');
        $phone = trim($_POST['phone_number'] ?? '');

        if (empty($fullName)) {
            redirect('/settings?tab=profile&error=' . urlencode("Full Name cannot be blank."));
        }
        if (empty($department)) {
            redirect('/settings?tab=profile&error=' . urlencode("Department / Program is required."));
        }

        // Sanitize string lengths
        $fullName = mb_substr($fullName, 0, 120);
        $department = mb_substr($department, 0, 100);
        $phone = mb_substr($phone, 0, 25);

        try {
            User::updateProfile($db, $userId, [
                'full_name' => $fullName,
                'department_program' => $department,
                'phone_number' => $phone
            ]);

            $_SESSION['full_name'] = $fullName;
            $_SESSION['user_name'] = $fullName;
            $_SESSION['department_program'] = $department;
            $_SESSION['phone_number'] = $phone;

            AuditLog::log($db, $userId, 'PROFILE_UPDATED', "User updated profile information (Name: $fullName, Dept: $department)");
            redirect('/settings?tab=profile&success=' . urlencode("Profile information updated successfully."));
        } catch (Exception $e) {
            redirect('/settings?tab=profile&error=' . urlencode("Failed to update profile."));
        }
    }

    /**
     * Handle Avatar Upload with GD Sanitization and Conversion to WebP
     */
    public static function uploadAvatar(): void {
        AuthMiddleware::check();
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)$_SESSION['user_id'];

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = match ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "File size exceeds server limit (max 2MB).",
                UPLOAD_ERR_PARTIAL => "The file was only partially uploaded.",
                UPLOAD_ERR_NO_FILE => "Please select an image file to upload.",
                default => "Failed to upload avatar image."
            };
            redirect('/settings?tab=profile&error=' . urlencode($errorMsg));
        }

        $file = $_FILES['avatar'];

        // 1. Enforce Max File Size (2MB)
        $maxSizeBytes = 2 * 1024 * 1024;
        if ($file['size'] > $maxSizeBytes) {
            redirect('/settings?tab=profile&error=' . urlencode("Avatar image cannot exceed 2MB."));
        }

        // 2. MIME Type Verification using finfo (Never trust client headers)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            redirect('/settings?tab=profile&error=' . urlencode("Invalid image format. Allowed formats: JPEG, PNG, WEBP."));
        }

        // 3. Re-encode Image with GD to strip malicious EXIF scripts / PHP polyglots
        $imageRes = null;
        switch ($mime) {
            case 'image/jpeg':
                $imageRes = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $imageRes = @imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                $imageRes = @imagecreatefromwebp($file['tmp_name']);
                break;
        }

        if (!$imageRes) {
            redirect('/settings?tab=profile&error=' . urlencode("The uploaded image file is corrupt or invalid."));
        }

        // Target directory setup
        $uploadDir = ROOT_PATH . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate cryptographically random, collision-resistant filename
        $fileName = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.webp';
        $destPath = $uploadDir . '/' . $fileName;
        $relativeWebPath = '/uploads/avatars/' . $fileName;

        // Save sanitized WebP image
        $saved = imagewebp($imageRes, $destPath, 90);
        imagedestroy($imageRes);

        if (!$saved) {
            redirect('/settings?tab=profile&error=' . urlencode("Could not process and save the image."));
        }

        // 4. Clean up previous avatar file from disk if it exists
        $user = User::findById($db, $userId);
        if (!empty($user['avatar_path'])) {
            $oldFilePath = ROOT_PATH . '/public' . $user['avatar_path'];
            if (file_exists($oldFilePath) && is_file($oldFilePath) && str_starts_with(realpath($oldFilePath) ?: '', realpath($uploadDir) ?: '')) {
                @unlink($oldFilePath);
            }
        }

        // 5. Update Database & Session
        User::updateAvatar($db, $userId, $relativeWebPath);
        $_SESSION['avatar_path'] = $relativeWebPath;

        AuditLog::log($db, $userId, 'AVATAR_UPLOADED', "User updated profile picture");
        redirect('/settings?tab=profile&success=' . urlencode("Profile picture updated successfully."));
    }

    /**
     * Remove Avatar (Revert to Initials)
     */
    public static function removeAvatar(): void {
        AuthMiddleware::check();
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)$_SESSION['user_id'];
        $user = User::findById($db, $userId);

        if (!empty($user['avatar_path'])) {
            $uploadDir = ROOT_PATH . '/public/uploads/avatars';
            $oldFilePath = ROOT_PATH . '/public' . $user['avatar_path'];
            if (file_exists($oldFilePath) && is_file($oldFilePath) && str_starts_with(realpath($oldFilePath) ?: '', realpath($uploadDir) ?: '')) {
                @unlink($oldFilePath);
            }
        }

        User::updateAvatar($db, $userId, null);
        $_SESSION['avatar_path'] = null;

        AuditLog::log($db, $userId, 'AVATAR_REMOVED', "User removed custom profile picture");
        redirect('/settings?tab=profile&success=' . urlencode("Profile picture removed. Reverted to default initials."));
    }

    /**
     * Update Credentials (Email and/or Password with Current Password Re-Auth)
     */
    public static function updateCredentials(): void {
        AuthMiddleware::check();
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)$_SESSION['user_id'];
        $user = User::findById($db, $userId);

        $currentPassword = $_POST['current_password'] ?? '';
        $newEmail = trim($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // 1. Mandatory Re-Authentication Guard
        if (empty($currentPassword) || !password_verify($currentPassword, $user['password_hash'])) {
            redirect('/settings?tab=security&error=' . urlencode("Verification failed: Incorrect current password."));
        }

        // 2. Validate Email
        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            redirect('/settings?tab=security&error=' . urlencode("Please provide a valid email address."));
        }

        // Check if email changed and is already taken
        $emailChanged = (strcasecmp($newEmail, $user['email']) !== 0);
        if ($emailChanged && User::isEmailTakenByOther($db, $newEmail, $userId)) {
            redirect('/settings?tab=security&error=' . urlencode("The email address '$newEmail' is already in use by another account."));
        }

        // 3. Validate New Password (if provided)
        $passwordChanged = false;
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                redirect('/settings?tab=security&error=' . urlencode("New password must be at least 8 characters long."));
            }
            if ($newPassword !== $confirmPassword) {
                redirect('/settings?tab=security&error=' . urlencode("New password and confirmation do not match."));
            }
            $passwordChanged = true;
        }

        try {
            if ($passwordChanged) {
                User::updateCredentials($db, $userId, $newEmail, $newPassword);
                \App\Helpers\RememberMe::revokeAllForUser($db, $userId);
                AuditLog::log($db, $userId, 'PASSWORD_CHANGED', "User changed their account password");
                \App\Services\NotificationService::create(
                    $db,
                    $userId,
                    \App\Services\NotificationService::CAT_SECURITY,
                    "Password Changed",
                    "Your account password was updated successfully. If this wasn't you, notify an administrator immediately.",
                    \App\Services\NotificationService::TYPE_WARNING,
                    url('/settings?tab=security')
                );
            } else {
                User::updateCredentials($db, $userId, $newEmail, null);
            }

            if ($emailChanged) {
                AuditLog::log($db, $userId, 'EMAIL_CHANGED', "User updated email address from '{$user['email']}' to '$newEmail'");
                $_SESSION['email'] = $newEmail;
                \App\Services\NotificationService::create(
                    $db,
                    $userId,
                    \App\Services\NotificationService::CAT_SECURITY,
                    "Email Address Updated",
                    "Your account email address was updated to '{$newEmail}'.",
                    \App\Services\NotificationService::TYPE_INFO,
                    url('/settings?tab=security')
                );
            }

            $successMsg = ($passwordChanged && $emailChanged) 
                ? "Email and password successfully updated." 
                : ($passwordChanged ? "Password successfully updated." : "Email address successfully updated.");

            redirect('/settings?tab=security&success=' . urlencode($successMsg));
        } catch (Exception $e) {
            redirect('/settings?tab=security&error=' . urlencode("An error occurred while updating credentials."));
        }
    }

    /**
     * Update Notification Preferences
     */
    public static function updateNotifications(): void {
        AuthMiddleware::check();
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)$_SESSION['user_id'];

        $preferences = [
            'alert_due_reminders'   => isset($_POST['alert_due_reminders']) || isset($_POST['email_due_reminders']),
            'alert_overdue_alerts'  => isset($_POST['alert_overdue_alerts']) || isset($_POST['email_overdue_alerts']),
            'alert_security_logins' => isset($_POST['alert_security_logins']) || isset($_POST['email_security_logins']),
        ];

        User::updateNotificationPreferences($db, $userId, $preferences);
        $_SESSION['notification_preferences'] = $preferences;

        AuditLog::log($db, $userId, 'NOTIFICATION_PREFERENCES_UPDATED', "User updated alert notification preferences");
        redirect('/settings?tab=notifications&success=' . urlencode("Notification preferences saved successfully."));
    }

    /**
     * Revoke / Sign Out All Other Connected Sessions
     */
    public static function revokeOtherSessions(): void {
        AuthMiddleware::check();
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)$_SESSION['user_id'];
        $newVersion = User::bumpSessionVersion($db, $userId);
        $_SESSION['session_version'] = $newVersion;
        \App\Helpers\RememberMe::revokeAllForUser($db, $userId);

        AuditLog::log($db, $userId, 'SESSIONS_REVOKED', "User signed out of all other active sessions");
        redirect('/settings?tab=sessions&success=' . urlencode("Successfully signed out of all other devices and active sessions."));
    }

    /**
     * Self-Account Termination: Safe Deletion or Deactivation
     */
    public static function deleteOrDeactivate(): void {
        AuthMiddleware::check();
        $db = Database::getConnection();

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $userId = (int)$_SESSION['user_id'];
        $user = User::findById($db, $userId);
        $currentPassword = $_POST['current_password'] ?? '';

        // Verification Guard
        if (empty($currentPassword) || !password_verify($currentPassword, $user['password_hash'])) {
            redirect('/settings?tab=activity&error=' . urlencode("Account deletion canceled: Incorrect password verification."));
        }

        // Prevent sole admin lockout
        if ($user['role'] === 'admin') {
            $adminCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND account_status = 'approved'")->fetchColumn();
            if ($adminCount <= 1) {
                redirect('/settings?tab=activity&error=' . urlencode("Cannot terminate the last active System Administrator account. Please appoint another Administrator first."));
            }
        }

        $hasHistory = User::hasTransactionHistory($db, $userId);
        \App\Helpers\RememberMe::revokeAllForUser($db, $userId);

        if ($hasHistory) {
            // Transition to Deactivated to preserve audit & borrowing history
            User::deactivateAccount($db, $userId);
            AuditLog::log($db, $userId, 'ACCOUNT_DEACTIVATED', "User self-deactivated account with active/historical transaction records");
            Security::destroySession();
            redirect('/login?notice=' . urlencode("Your account has been deactivated. Historical records have been preserved in compliance with audit policy."));
        } else {
            // Safe hard deletion
            User::safeDelete($db, $userId);
            AuditLog::log($db, null, 'ACCOUNT_SELF_DELETED', "User '{$user['full_name']}' (ID: $userId) permanently deleted their unencumbered account");
            Security::destroySession();
            redirect('/login?success=' . urlencode("Your account has been permanently deleted."));
        }
    }
}
