<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

/** @var \PDO $db */
$db = require ROOT_PATH . '/config/database.php';

echo "--- Current Asset Catalog Status ---\n";
$itemCount = (int)$db->query("SELECT COUNT(*) FROM items")->fetchColumn();
$txCount = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

echo "Items: " . $itemCount . "\n";
echo "Transactions: " . $txCount . "\n";
