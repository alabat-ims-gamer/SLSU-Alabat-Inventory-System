<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

use App\Config\Database;

$role       = $argv[1] ?? 'guest';
$controller = $argv[2] ?? '';
$method     = $argv[3] ?? '';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'TestRunner/1.0';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/test';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = Database::getConnection();
    
    // Look up or create verified user matching the desired test role
    $stmt = $db->prepare("SELECT user_id, full_name, role FROM users WHERE role = ? AND account_status = 'approved' LIMIT 1");
    $stmt->execute([$role]);
    $userRow = $stmt->fetch();

    if ($userRow) {
        $userId = (int)$userRow['user_id'];
        $fullName = $userRow['full_name'];
    } else {
        $idNum = 'TEST-' . strtoupper($role);
        $uName = 'test_' . $role;
        $email = 'test_' . $role . '@slsu.edu.ph';
        $passHash = password_hash('password123', PASSWORD_BCRYPT);
        
        $ins = $db->prepare("
            INSERT INTO users (id_number, username, full_name, email, password_hash, role, department_program, account_status)
            VALUES (?, ?, ?, ?, ?, ?, 'Administration', 'approved')
            ON DUPLICATE KEY UPDATE role = VALUES(role), account_status = 'approved'
        ");
        $ins->execute([$idNum, $uName, 'Test ' . ucfirst($role), $email, $passHash, $role]);
        
        $fetchStmt = $db->prepare("SELECT user_id, full_name FROM users WHERE username = ?");
        $fetchStmt->execute([$uName]);
        $created = $fetchStmt->fetch();
        $userId = (int)$created['user_id'];
        $fullName = $created['full_name'];
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = $role;
    $_SESSION['user_name'] = $fullName;
    $_SESSION['last_activity'] = time();
    $_SESSION['fingerprint'] = \App\Helpers\Security::generateFingerprint();

} catch (\Throwable $e) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = $role;
}

register_shutdown_function(static function () {
    $code = http_response_code() ?: 200;
    echo "\nSTATUS_CODE:" . $code . "\n";
});

ob_start();
try {
    if (class_exists($controller) && method_exists($controller, $method)) {
        $controller::$method();
        http_response_code(200);
    } else {
        http_response_code(404);
    }
} catch (\Throwable $e) {
    http_response_code(500);
}
ob_end_clean();
exit;
