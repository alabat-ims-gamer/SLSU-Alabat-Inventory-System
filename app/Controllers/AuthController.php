<?php
namespace App\Controllers;

use App\Helpers\Security;
use App\Models\User;
use App\Models\AuditLog;
use App\Config\Database;
use App\Services\EventDispatcher;

class AuthController {
    
    /**
     * Display the login page
     */
    public static function showLogin() {
        // If not authenticated in session, check for a valid remembered device (unless explicit notice/error was passed)
        if (!isset($_SESSION['user_id']) && empty($_GET['error']) && empty($_GET['notice']) && empty($_GET['success'])) {
            try {
                $db = Database::getConnection();
                \App\Helpers\RememberMe::validateAndLogin($db);
            } catch (\Throwable $e) {}
        }

        // If already authenticated and not in a pending state, redirect to dashboard
        if (isset($_SESSION['user_id']) && isset($_SESSION['account_status']) && $_SESSION['account_status'] === 'approved') {
            redirect('/dashboard');
        }

        Security::generateCsrfToken();
        $error = $_GET['error'] ?? null;
        $notice = $_GET['notice'] ?? null;
        $success = $_GET['success'] ?? null;

        require __DIR__ . '/../../views/auth/login.php';
    }

    /**
     * Handle the login form submission
     */
    public static function handleLogin() {
        $db = Database::getConnection();
        
        // 1. CSRF Validation
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        $identifier = trim($_POST['email'] ?? ''); // Accepts email or ID Number
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        if (empty($identifier) || empty($password)) {
            redirect('/login?error=' . urlencode("Please fill in all fields."));
        }

        // 2. Brute-Force Rate Limiting Check
        if (Security::isRateLimited($db, $identifier, $ip)) {
            redirect('/login?error=' . urlencode("Too many failed attempts. Please try again in 10 minutes."));
        }

        // 3. User Lookup
        $user = User::findByLoginCredential($db, $identifier);

        // 4. Password Verification
        if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
            Security::recordFailedAttempt($db, $identifier, $ip);
            AuditLog::log($db, $user['user_id'] ?? null, 'LOGIN_FAILED', "Failed login attempt for identifier: $identifier");
            redirect('/login?error=' . urlencode("Invalid login credentials."));
        }

        // 5. Account Status Enforcement
        if ($user['account_status'] === 'pending') {
            redirect('/login?notice=' . urlencode("Your registration is currently pending Administrator approval."));
        }
        if ($user['account_status'] === 'rejected') {
            redirect('/login?error=' . urlencode("Your registration was declined: " . $user['rejection_reason']));
        }
        if ($user['account_status'] === 'suspended') {
            redirect('/login?error=' . urlencode("This account has been deactivated. Please contact the administrator."));
        }

        // 6. Successful Login: Setup Session
        Security::clearLoginAttempts($db, $identifier, $ip);
        session_regenerate_id(true); // Prevent session fixation
        
        $rememberMe = !empty($_POST['remember']);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['account_status'] = $user['account_status'];
        $_SESSION['avatar_path'] = $user['avatar_path'] ?? null;
        $_SESSION['department_program'] = $user['department_program'] ?? '';
        $_SESSION['phone_number'] = $user['phone_number'] ?? '';
        $_SESSION['session_version'] = (int)($user['session_version'] ?? 1);
        $_SESSION['is_remembered'] = $rememberMe;
        $_SESSION['login_time'] = time();
        $_SESSION['fingerprint'] = Security::generateFingerprint();
        $_SESSION['last_activity'] = time();

        // 7. Process Remember Me Token (30-day device persistence)
        if ($rememberMe) {
            \App\Helpers\RememberMe::createToken($db, (int)$user['user_id']);
        } else {
            \App\Helpers\RememberMe::clearCookieAndRevoke($db, (int)$user['user_id']);
        }

        AuditLog::log($db, $user['user_id'], 'LOGIN_SUCCESS', "User logged in successfully" . ($rememberMe ? " (Remembered Device)" : ""));

        // Redirect based on role
        if ($user['role'] === 'admin') {
            redirect('/admin/users');
        } else {
            redirect('/dashboard');
        }
    }

    /**
     * Display the registration page
     */
    public static function showRegister() {
        Security::generateCsrfToken();
        $error = $_GET['error'] ?? null;
        $old = $_SESSION['reg_old'] ?? [];
        unset($_SESSION['reg_old']);
        require __DIR__ . '/../../views/auth/register.php';
    }

    /**
     * Handle the registration form submission
     */
    public static function handleRegister() {
        $db = Database::getConnection();

        // 1. CSRF Validation
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die("CSRF token validation failed.");
        }

        // Save old input in session in case of validation failure
        $_SESSION['reg_old'] = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'id_number' => trim($_POST['id_number'] ?? ''),
            'department_program' => trim($_POST['department_program'] ?? $_POST['department'] ?? '')
        ];

        // 2. Input Collection & Validation
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'id_number' => trim($_POST['id_number'] ?? ''),
            'department_program' => trim($_POST['department_program'] ?? $_POST['department'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];

        // Format derived fields
        $data['full_name'] = $data['first_name'] . ' ' . $data['last_name'];
        $data['username'] = $data['id_number'];

        // Required fields check with specific error messages
        if (empty($data['first_name']) || empty($data['last_name'])) {
            redirect('/register?error=' . urlencode("First name and last name are required."));
        }
        if (empty($data['email'])) {
            redirect('/register?error=' . urlencode("Institutional email address is required."));
        }
        if (empty($data['department_program'])) {
            redirect('/register?error=' . urlencode("Please select your Department / Academic Program."));
        }
        if (empty($data['id_number'])) {
            redirect('/register?error=' . urlencode("Student / Employee ID Number is required."));
        }
        if (empty($data['password']) || empty($data['password_confirm'])) {
            redirect('/register?error=' . urlencode("Password and password confirmation are required."));
        }

        // Email syntax validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            redirect('/register?error=' . urlencode("Invalid email format."));
        }

        // Password matching & length
        if ($data['password'] !== $data['password_confirm']) {
            redirect('/register?error=' . urlencode("Passwords do not match."));
        }
        if (strlen($data['password']) < 8) {
            redirect('/register?error=' . urlencode("Password must be at least 8 characters long."));
        }

        // Uniqueness check (Email, ID Number)
        $existing = User::findByLoginCredential($db, $data['email']);
        if ($existing) {
            redirect('/register?error=' . urlencode("An account with this email already exists."));
        }
        $existingId = User::findByLoginCredential($db, $data['id_number']);
        if ($existingId) {
            redirect('/register?error=' . urlencode("An account with this ID Number already exists."));
        }

        // 3. Create User
        try {
            $userId = User::create($db, $data);
            unset($_SESSION['reg_old']);
            AuditLog::log($db, $userId, 'USER_REGISTER', "New user registration submitted for ID: " . $data['id_number']);
            
            // Real-Time Broadcast: Notify admins of new pending registration
            EventDispatcher::dispatch(
                $db,
                EventDispatcher::EVENT_USER_REGISTERED,
                'user',
                $userId,
                [
                    'user_id'            => $userId,
                    'full_name'          => $data['first_name'] . ' ' . $data['last_name'],
                    'email'              => $data['email'],
                    'id_number'          => $data['id_number'],
                    'department_program' => $data['department_program'],
                    'created_at'         => date('Y-m-d H:i:s')
                ],
                'admin'
            );

            redirect('/login?success=' . urlencode("Registration submitted. Awaiting System Administrator approval and role assignment."));
        } catch (\Exception $e) {
            redirect('/register?error=' . urlencode("An unexpected error occurred. Please try again later."));
        }
    }

    /**
     * Handle user logout
     */
    public static function handleLogout() {
        $reason = $_GET['reason'] ?? '';
        $isIdle = ($reason === 'idle');

        try {
            $db = Database::getConnection();
            if (isset($_SESSION['user_id'])) {
                $logMsg = $isIdle ? "Session terminated due to inactivity" : "User logged out securely";
                AuditLog::log($db, $_SESSION['user_id'], 'LOGOUT', $logMsg);
            }
            if (!$isIdle) {
                \App\Helpers\RememberMe::clearCookieAndRevoke($db, isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null);
            }
        } catch (\Throwable $e) {}

        Security::destroySession();
        $notice = $isIdle 
            ? "Your session expired due to inactivity. Please sign in again."
            : "You have been securely logged out.";
        redirect('/login?notice=' . urlencode($notice));
    }
}
