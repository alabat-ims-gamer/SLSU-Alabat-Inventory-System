<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            // Load from the existing config file
            self::$connection = require ROOT_PATH . '/config/database.php';
        }
        return self::$connection;
    }
}
