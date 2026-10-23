<?php
/**
 * Universal Database Connection Engine (PDO)
 * Auto-detects InfinityFree environment vs Local XAMPP
 */

$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: ($_ENV['DB_HOST'] ?? ($_ENV['MYSQLHOST'] ?? '127.0.0.1')));
$port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: ($_ENV['DB_PORT'] ?? ($_ENV['MYSQLPORT'] ?? '3306')));
$db   = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: ($_ENV['DB_NAME'] ?? ($_ENV['MYSQLDATABASE'] ?? 'slsu_inventory')));
$user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: ($_ENV['DB_USER'] ?? ($_ENV['MYSQLUSER'] ?? 'root')));
$pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: ($_ENV['DB_PASS'] ?? ($_ENV['MYSQLPASSWORD'] ?? '')));

// Support Railway MYSQL_URL / DATABASE_URL connection strings
$dbUrl = getenv('MYSQL_URL') ?: (getenv('DATABASE_URL') ?: ($_ENV['MYSQL_URL'] ?? ($_ENV['DATABASE_URL'] ?? '')));
if (!empty($dbUrl)) {
    $dbParts = parse_url($dbUrl);
    if (!empty($dbParts['host'])) $host = $dbParts['host'];
    if (!empty($dbParts['port'])) $port = (string)$dbParts['port'];
    if (!empty($dbParts['user'])) $user = $dbParts['user'];
    if (isset($dbParts['pass']))  $pass = $dbParts['pass'];
    if (!empty($dbParts['path'])) $db   = ltrim($dbParts['path'], '/');
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Synchronize timezone to Philippine Standard Time (PST / UTC+8)
    date_default_timezone_set('Asia/Manila');
    try {
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (\Throwable $e) {
        // Fallback silently if timezone tables are unpopulated in bare MySQL
    }
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

return $pdo;
