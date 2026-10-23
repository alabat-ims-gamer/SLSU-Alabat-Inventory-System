<?php
/**
 * Automated Database Migration Runner
 * Usage: php database/migrate.php (or composer migrate)
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__);

// 1. Load Composer Autoloader if exists
if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
}

// 2. Load .env variables
$envFile = $baseDir . '/.env';
if (file_exists($envFile)) {
    if (class_exists(\Dotenv\Dotenv::class)) {
        $dotenv = \Dotenv\Dotenv::createImmutable($baseDir);
        $dotenv->safeLoad();
    } else {
        // Fallback manual parser for .env if vendor not installed yet
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                $_ENV[$k] = $v;
                putenv("{$k}={$v}");
            }
        }
    }
}

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

echo "\n========================================================\n";
echo "  SLSU-Alabat IMS - Automated Database Schema Migration\n";
echo "========================================================\n";
echo "[i] Host:     {$host}:{$port}\n";
echo "[i] Database: {$db}\n";
echo "[i] User:     {$user}\n\n";

try {
    // 3. Connect to MySQL server to ensure target database exists
    echo "[*] Connecting to MySQL server...\n";
    $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($serverDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "[*] Ensuring database '{$db}' exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "[+] Database '{$db}' verified.\n";

    // 4. Connect directly to target database
    $dbDsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $dbPdo = new PDO($dbDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // 5. Read schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("Schema file not found at: {$schemaFile}");
    }

    echo "[*] Reading schema definitions from database/schema.sql...\n";
    $sql = file_get_contents($schemaFile);

    // 6. Execute SQL batch
    echo "[*] Executing schema DDL queries...\n";
    $dbPdo->exec($sql);

    // 6.5 Execute seed.sql if available
    $seedFile = __DIR__ . '/seed.sql';
    if (file_exists($seedFile)) {
        echo "[*] Reading initial data from database/seed.sql...\n";
        $seedSql = file_get_contents($seedFile);
        if (!empty(trim($seedSql))) {
            echo "[*] Seeding default administrator and initial data...\n";
            $dbPdo->exec($seedSql);
            echo "[+] Database seeded successfully.\n";
        }
    }

    // 6.7 Execute incremental schema updates & column verifications
    $scriptsMigrate = $baseDir . '/scripts/migrate.php';
    if (file_exists($scriptsMigrate)) {
        echo "\n[*] Applying incremental column updates from scripts/migrate.php...\n";
        require $scriptsMigrate;
    }

    // 7. Verify tables created
    $stmt = $dbPdo->query("SHOW TABLES;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "\n[+] Migration completed successfully!\n";
    $targetDbName = is_string($db) ? $db : 'slsu_inventory';
    echo "[+] Tables in database '{$targetDbName}':\n";
    foreach ($tables as $table) {
        echo "    - {$table}\n";
    }
    echo "========================================================\n\n";

} catch (PDOException $e) {
    echo "\n[!] Database Error: " . $e->getMessage() . "\n";
    echo "[!] Please ensure your MySQL server (XAMPP MySQL) is running on port {$port}.\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n[!] Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
