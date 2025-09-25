<?php
// Unified minimal Database singleton (replaces previous duplicate implementations)
class Database {
    private static ?PDO $conn = null;
    public static function get(): PDO {
        if (self::$conn === null) {
            $host = 'localhost'; $db = 'pageant_db'; $user = 'root'; $pass = '';
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$conn = new PDO($dsn, $user, $pass, $options);
            self::$conn->exec("SET time_zone = '+08:00'");
        }
        return self::$conn;
    }
}