<?php
require_once 'database.php';

/**
 * Pageant Service
 * Handles pageant management, divisions, and administrative operations
 */
class PageantService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get pageant by code
     */
    public function getByCode(string $code): ?array {
        return $this->db->fetch(
            "SELECT * FROM pageants WHERE code = ?", 
            [$code]
        );
    }
    
    /**
     * Get pageant by ID
     */
    public function getById(int $id): ?array {
        return $this->db->fetch(
            "SELECT * FROM pageants WHERE id = ?", 
            [$id]
        );
    }
    
    /**
     * Get pageant by ID (alias for compatibility)
     */
    public function getPageant(int $id): ?array {
        return $this->getById($id);
    }
    
    /**
     * List divisions for a pageant
     */
    public function listDivisions(int $pageantId): array {
        return $this->db->fetchAll(
            "SELECT * FROM divisions WHERE pageant_id = ? ORDER BY name",
            [$pageantId]
        );
    }
    
    /**
     * Create new pageant
     */
    public function getActivePageant(): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pageants WHERE status IN ('PRELIM_RUNNING', 'FINAL_RUNNING') ORDER BY created_at DESC LIMIT 1"
        );
    }
    
    public function getDefaultPageant(): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM pageants ORDER BY created_at DESC LIMIT 1"
        );
    }
    
    public function createPageant(string $name, string $code, int $year = null): array
    {
        if ($year === null) {
            $year = date('Y');
        }
        
        $this->db->execute(
            "INSERT INTO pageants (name, code, year, status, created_at) VALUES (?, ?, ?, 'DRAFT', NOW())",
            [$name, $code, $year]
        );
        
        return $this->getPageant($this->db->lastInsertId());
    }
    
    /**
     * Assign admin role to user for pageant
     */
    public function assignAdmin(int $pageantId, int $userId): void {
        $this->db->execute(
            "INSERT INTO pageant_users (pageant_id, user_id, role) VALUES (?, ?, 'ADMIN') 
             ON DUPLICATE KEY UPDATE role = 'ADMIN'",
            [$pageantId, $userId]
        );
    }
    
    /**
     * Assign judge role to user for pageant
     */
    public function assignJudge(int $pageantId, int $userId): void {
        $this->db->execute(
            "INSERT INTO pageant_users (pageant_id, user_id, role) VALUES (?, ?, 'JUDGE') 
             ON DUPLICATE KEY UPDATE role = 'JUDGE'",
            [$pageantId, $userId]
        );
    }
    
    /**
     * List all pageants
     */
    public function listPageants(): array {
        return $this->db->fetchAll(
            "SELECT * FROM pageants ORDER BY created_at DESC"
        );
    }
    
    /**
     * Update pageant status
     */
    public function updateStatus(int $pageantId, string $status): void {
        $this->db->execute(
            "UPDATE pageants SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $pageantId]
        );
    }
}