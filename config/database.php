<?php
/**
 * Database Connection Configuration
 * Uses PDO with prepared statements for security.
 * Credentials loaded from .env via app.php
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host    = $_ENV['DB_HOST']  ?? 'localhost';
            $dbname  = $_ENV['DB_NAME']  ?? 'cardvault';
            $user    = $_ENV['DB_USER']  ?? 'cardvault_user';
            $pass    = $_ENV['DB_PASS']  ?? '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection error. Please contact the administrator.");
            }
        }

        return self::$instance;
    }

    private function __clone() {}
}
