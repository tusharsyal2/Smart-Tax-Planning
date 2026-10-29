<?php
/**
 * Database Connection Manager (PDO)
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/app.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $dbname = env('DB_NAME', 'smart_tax_planning');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$instance = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // If the database does not exist yet, connect to MySQL server directly to initialize
            if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                try {
                    $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $serverPdo = new PDO($serverDsn, $user, $pass, $options);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    self::$instance = new PDO($dsn, $user, $pass, $options);
                    return self::$instance;
                } catch (Exception $inner) {
                    throw new RuntimeException("Database error: Could not auto-create database '{$dbname}'. " . $inner->getMessage());
                }
            }
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }

        return self::$instance;
    }

    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool {
        return self::getConnection()->rollBack();
    }
}

function getDb(): PDO {
    return Database::getConnection();
}
