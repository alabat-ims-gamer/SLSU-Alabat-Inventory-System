<?php
declare(strict_types=1);

/**
 * RememberMe Feature Test Suite
 * Tests 30-day device token generation, validation, rotation, and revocation.
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

echo "========================================================\n";
echo "  SLSU-Alabat IMS - RememberMe Feature Test Suite\n";
echo "========================================================\n\n";

$passCount = 0;
function testAssert(bool $condition, string $message): void {
    global $passCount;
    if (!$condition) {
        echo "[FAIL] {$message}\n";
        exit(1);
    }
    $passCount++;
    echo "[PASS] {$message}\n";
}

// ── 1. Create In-Memory SQLite Database with Users & Remember Tokens ─
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqlite->exec("
    CREATE TABLE users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_number TEXT UNIQUE,
        username TEXT UNIQUE,
        full_name TEXT,
        email TEXT UNIQUE,
        password_hash TEXT,
        role TEXT,
        department_program TEXT,
        avatar_path TEXT,
        phone_number TEXT,
        account_status TEXT,
        session_version INTEGER DEFAULT 1
    );
");

$sqlite->exec("
    CREATE TABLE remember_tokens (
        token_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        selector TEXT NOT NULL UNIQUE,
        token_hash TEXT NOT NULL,
        expires_at TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
");

$sqlite->exec("
    INSERT INTO users (id_number, username, full_name, email, password_hash, role, department_program, account_status, session_version)
    VALUES ('2026-0001', 'custodian01', 'Test Custodian', 'custodian@slsu.edu.ph', 'hash123', 'custodian', 'Campus Administration', 'approved', 1);
");

$userId = 1;

// ── 2. Test Token Creation ───────────────────────────────────────────
echo "\n--- [Suite 1: Token Generation & Cookie Set] ---\n";
$_COOKIE = [];
$created = \App\Helpers\RememberMe::createToken($sqlite, $userId);
testAssert($created === true, "createToken returned true");

$stmt = $sqlite->query("SELECT * FROM remember_tokens WHERE user_id = 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
testAssert(!empty($row), "remember_tokens table contains a token record for user_id 1");
testAssert(strlen($row['selector']) === 32, "Selector is 32 hex characters");
testAssert(strlen($row['token_hash']) === 64, "Token hash is 64 hex characters (SHA-256)");

testAssert(isset($_COOKIE[\App\Helpers\RememberMe::COOKIE_NAME]), "slsu_remember cookie was populated");
$cookieVal = $_COOKIE[\App\Helpers\RememberMe::COOKIE_NAME];
testAssert(strpos($cookieVal, ':') !== false, "Cookie value formatted as selector:validator");
[$cSelector, $cValidator] = explode(':', $cookieVal, 2);
testAssert($cSelector === $row['selector'], "Cookie selector matches database selector");
testAssert(hash('sha256', $cValidator) === $row['token_hash'], "Cookie validator hashes to stored token_hash");

// ── 3. Test Session Validation & Re-authentication ────────────────────
echo "\n--- [Suite 2: Session Restoration & Token Rotation] ---\n";
$_SESSION = [];
$initialHash = $row['token_hash'];

$userRecord = \App\Helpers\RememberMe::validateAndLogin($sqlite);
testAssert($userRecord !== null, "validateAndLogin successfully restored user session");
testAssert($_SESSION['user_id'] === 1, "Session user_id is set to 1");
testAssert($_SESSION['role'] === 'custodian', "Session role restored as custodian");
testAssert($_SESSION['is_remembered'] === true, "Session is_remembered flag set to true");

// Check rotation
$stmt = $sqlite->query("SELECT * FROM remember_tokens WHERE user_id = 1");
$rotatedRow = $stmt->fetch(PDO::FETCH_ASSOC);
testAssert($rotatedRow['token_hash'] !== $initialHash, "Token hash rotated after successful login");

// ── 4. Test Tampered Validator Rejection ─────────────────────────────
echo "\n--- [Suite 3: Security & Tampering Resistance] ---\n";
$_COOKIE[\App\Helpers\RememberMe::COOKIE_NAME] = $rotatedRow['selector'] . ':bogusvalidator1234567890abcdef1234567890abcdef1234567890abcdef';
$_SESSION = [];
$failRecord = \App\Helpers\RememberMe::validateAndLogin($sqlite);
testAssert($failRecord === null, "Tampered validator was rejected");
testAssert(!isset($_SESSION['user_id']), "Tampered attempt did not populate session");

// Verify that the compromised token was revoked from database
$stmt = $sqlite->prepare("SELECT * FROM remember_tokens WHERE selector = ?");
$stmt->execute([$rotatedRow['selector']]);
testAssert($stmt->fetch() === false, "Compromised token was revoked from database upon failed verification");

// ── 5. Test Manual Revocation & Clear Cookie ─────────────────────────
echo "\n--- [Suite 4: Revocation on Logout & Password Change] ---\n";
// Create a new valid token
\App\Helpers\RememberMe::createToken($sqlite, $userId);
testAssert(isset($_COOKIE[\App\Helpers\RememberMe::COOKIE_NAME]), "New token created for logout test");

\App\Helpers\RememberMe::clearCookieAndRevoke($sqlite, $userId);
testAssert(!isset($_COOKIE[\App\Helpers\RememberMe::COOKIE_NAME]), "Cookie cleared on logout");

$stmt = $sqlite->query("SELECT COUNT(*) FROM remember_tokens WHERE user_id = 1");
testAssert((int)$stmt->fetchColumn() === 0, "Token removed from DB on logout");

// Test user-wide revocation
\App\Helpers\RememberMe::createToken($sqlite, $userId);
\App\Helpers\RememberMe::revokeAllForUser($sqlite, $userId);
$stmt = $sqlite->query("SELECT COUNT(*) FROM remember_tokens WHERE user_id = 1");
testAssert((int)$stmt->fetchColumn() === 0, "revokeAllForUser clears all tokens for user");

// ── 6. Test Registration Notice Rewording ─────────────────────────────
echo "\n--- [Suite 5: Registration Notice Verification] ---\n";
$regContent = file_get_contents(__DIR__ . '/../views/auth/register.php');
testAssert(!str_contains($regContent, 'You will receive an email notification'), "Old misleading email notification text removed");
testAssert(str_contains($regContent, 'Once your account has been reviewed and approved by an Administrator'), "New honest registration approval text present");

echo "\n========================================================\n";
echo "  [SUCCESS] All {$passCount} RememberMe Tests Passed!\n";
echo "========================================================\n\n";
