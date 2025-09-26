<?php
class database {
    private string $dbHost;
    private string $dbName;
    private string $dbUser;
    private string $dbPass;
    private ?string $lastError = null;

    public function __construct(?string $h = null, ?string $n = null, ?string $u = null, ?string $p = null) {
        $this->dbHost = $h ?? getenv('DB_HOST') ?: '127.0.0.1';
        $this->dbName = $n ?? getenv('DB_NAME') ?: 'NULP-Tabulation-DB';
        $this->dbUser = $u ?? getenv('DB_USER') ?: 'root';
        $this->dbPass = $p ?? getenv('DB_PASS') ?: '';
    }

    public function opencon(): PDO {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->dbHost, $this->dbName);
        $pdo = new PDO($dsn, $this->dbUser, $this->dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    }

    public function getLastError(): ?string {
        return $this->lastError;
    }

    public function safeFetchAll($stmt): array {
        try {
            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
}