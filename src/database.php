<?php

namespace natilosir\orm;

use PDO;
use PDOException;

class Database {
    private static ?PDO $pdo = null;

    public static function pdo(): PDO {
        if ( self::$pdo !== null ) {
            return self::$pdo;
        }

        $config = require __DIR__ . '/../../../../config.php';

        $host     = $config['database']['host'];
        $db_name  = $config['database']['database'];
        $username = $config['database']['user'];
        $password = $config['database']['password'];

        try {
            self::$pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch ( PDOException $e ) {
            throw new \Exception("DB Connection failed: " . $e->getMessage());
        }

        return self::$pdo;
    }
}