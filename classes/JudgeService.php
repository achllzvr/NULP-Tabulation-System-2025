<?php
require_once 'database.php';

/**
 * Judge Service
 * Handles judge management, credentials, and judging assignments
 */
class JudgeService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Add judges to pageant
     */
    public function addJudges(int $pageantId, array $judges): array {
        $results = [];
        
        $this->db->beginTransaction();
        try {
            foreach ($judges as $judge) {
                // Create user account for judge if not exists
                $userId = $this->createOrGetJudgeUser($judge);
                
                // Assign judge role to pageant
                $this->db->execute(
                    "INSERT INTO pageant_users (pageant_id, user_id, role, created_at) 
                     VALUES (?, ?, 'judge', NOW()) 
                     ON DUPLICATE KEY UPDATE role = 'judge'",
                    [$pageantId, $userId]
                );
                
                $results[] = [
                    'success' => true,
                    'user_id' => $userId,
                    'data' => $judge
                ];
            }
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * List judges for pageant
     */
    public function list(int $pageantId): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.full_name, u.email, pu.created_at as assigned_at
             FROM users u 
             JOIN pageant_users pu ON u.id = pu.user_id 
             WHERE pu.pageant_id = ? AND pu.role = 'judge' 
             ORDER BY u.full_name",
            [$pageantId]
        );
    }
    
    /**
     * Get judge credentials for export
     */
    public function fetchCredentialsExport(int $pageantId): array {
        return $this->db->fetchAll(
            "SELECT u.full_name, u.email, 
                    CONCAT('judge_', LOWER(REPLACE(u.full_name, ' ', '_'))) as username,
                    'temp_password_123' as temp_password
             FROM users u 
             JOIN pageant_users pu ON u.id = pu.user_id 
             WHERE pu.pageant_id = ? AND pu.role = 'judge' 
             ORDER BY u.full_name",
            [$pageantId]
        );
    }
    
    /**
     * Get judge by user ID and pageant
     */
    public function getJudge(int $pageantId, int $userId): ?array {
        return $this->db->fetch(
            "SELECT u.*, pu.role 
             FROM users u 
             JOIN pageant_users pu ON u.id = pu.user_id 
             WHERE pu.pageant_id = ? AND u.id = ? AND pu.role = 'judge'",
            [$pageantId, $userId]
        );
    }
    
    /**
     * Remove judge from pageant
     */
    public function removeJudge(int $pageantId, int $userId): void {
        $this->db->execute(
            "DELETE FROM pageant_users WHERE pageant_id = ? AND user_id = ? AND role = 'judge'",
            [$pageantId, $userId]
        );
    }
    
    /**
     * Create or get existing judge user account
     */
    private function createOrGetJudgeUser(array $judgeData): int {
        // Check if user exists
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE email = ?",
            [$judgeData['email']]
        );
        
        if ($user) {
            // Update name if provided
            if (!empty($judgeData['full_name'])) {
                $this->db->execute(
                    "UPDATE users SET full_name = ?, updated_at = NOW() WHERE id = ?",
                    [$judgeData['full_name'], $user['id']]
                );
            }
            return (int)$user['id'];
        }
        
        // Create new user
        $tempPassword = 'temp_' . substr(md5($judgeData['email']), 0, 8);
        $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        $this->db->execute(
            "INSERT INTO users (full_name, email, password_hash, is_active, created_at) 
             VALUES (?, ?, ?, 1, NOW())",
            [
                $judgeData['full_name'],
                $judgeData['email'],
                $passwordHash
            ]
        );
        
        return (int)$this->db->lastInsertId();
    }
}