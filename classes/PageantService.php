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
            "SELECT * FROM pageants WHERE code = ? AND is_active = 1", 
            [$code]
        );
    }
    
    /**
     * Get pageant by ID
     */
    public function getById(int $id): ?array {
        return $this->db->fetch(
            "SELECT * FROM pageants WHERE id = ? AND is_active = 1", 
            [$id]
        );
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
    public function create(string $name, int $year, int $creatorUserId): int {
        $code = strtoupper(substr($name, 0, 4)) . $year;
        
        $this->db->execute(
            "INSERT INTO pageants (name, year, code, creator_user_id, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$name, $year, $code, $creatorUserId]
        );
        
        $pageantId = (int)$this->db->lastInsertId();
        
        // Assign creator as admin
        $this->assignAdmin($pageantId, $creatorUserId);
        
        return $pageantId;
    }
    
    /**
     * Assign admin role to user for pageant
     */
    public function assignAdmin(int $pageantId, int $userId): void {
        $this->db->execute(
            "INSERT INTO pageant_users (pageant_id, user_id, role, created_at) VALUES (?, ?, 'admin', NOW()) 
             ON DUPLICATE KEY UPDATE role = 'admin'",
            [$pageantId, $userId]
        );
    }
    
    /**
     * Get pageant settings
     */
    public function getSettings(int $pageantId): array {
        $settings = $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM pageant_settings WHERE pageant_id = ?",
            [$pageantId]
        );
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        
        return $result;
    }
    
    /**
     * Update pageant setting
     */
    public function updateSetting(int $pageantId, string $key, string $value): void {
        $this->db->execute(
            "INSERT INTO pageant_settings (pageant_id, setting_key, setting_value, updated_at) 
             VALUES (?, ?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
            [$pageantId, $key, $value]
        );
    }
}