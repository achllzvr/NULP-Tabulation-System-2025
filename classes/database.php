<?php
declare(strict_types=1);

class database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    
    public function __construct() {
        // Read environment variables with fallbacks
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->dbname = $_ENV['DB_NAME'] ?? 'nulp_tabulation';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }
    
    public function opencon(): PDO {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    // Password helper methods
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    // Stub domain methods for future implementation
    /*
    public function getUserById(int $user_id): ?array {
        // TODO: Implement user retrieval
    }
    
    public function createUser(array $userData): int {
        // TODO: Implement user creation
    }
    
    public function updateUser(int $user_id, array $userData): bool {
        // TODO: Implement user update
    }
    */
}