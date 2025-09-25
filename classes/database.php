<?php
/**
 * Database Connection Singleton
 * 
 * Provides a PDO connection with error handling and UTF-8 support.
 * Configure HOST, DB, USER, PASS constants below for your environment.
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration - Update these for your environment
    private const HOST = 'localhost';
    private const DB = 'pageant_tabulation';
    private const USER = 'root';
    private const PASS = '';
    private const CHARSET = 'utf8mb4';
    private const TIMEZONE = '+08:00';
    
    private function __construct() {
        $dsn = "mysql:host=" . self::HOST . ";dbname=" . self::DB . ";charset=" . self::CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->connection = new PDO($dsn, self::USER, self::PASS, $options);
            
            // Set timezone
            $this->connection->exec("SET time_zone = '" . self::TIMEZONE . "'");
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    // Helper method for prepared statements
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    // Helper method for single row
    public function fetch(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    // Helper method for all rows
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Helper method for INSERT/UPDATE/DELETE
    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }
    
    // Get last insert ID
    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }
    
    // Begin transaction
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    // Commit transaction
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    // Rollback transaction
    public function rollback(): bool {
        return $this->connection->rollback();
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}