<?php
require_once 'database.php';

/**
 * Participant Service
 * Handles participant management, registration, and related operations
 */
class ParticipantService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * List participants for a pageant, optionally filtered by division
     */
    public function list(int $pageantId, ?int $divisionId = null): array {
        $sql = "SELECT p.*, d.name as division_name 
                FROM participants p 
                LEFT JOIN divisions d ON p.division_id = d.id 
                WHERE p.pageant_id = ?";
        $params = [$pageantId];
        
        if ($divisionId !== null) {
            $sql .= " AND p.division_id = ?";
            $params[] = $divisionId;
        }
        
        $sql .= " ORDER BY d.name, p.number_label";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Bulk add participants
     */
    public function bulkAdd(int $pageantId, array $participants): array {
        $results = [];
        
        $this->db->beginTransaction();
        try {
            foreach ($participants as $participant) {
                $id = $this->addSingle($pageantId, $participant);
                $results[] = ['success' => true, 'id' => $id, 'data' => $participant];
            }
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * Get single participant
     */
    public function get(int $pageantId, int $participantId): ?array {
        return $this->db->fetch(
            "SELECT p.*, d.name as division_name 
             FROM participants p 
             LEFT JOIN divisions d ON p.division_id = d.id 
             WHERE p.pageant_id = ? AND p.id = ?",
            [$pageantId, $participantId]
        );
    }
    
    /**
     * Count active participants
     */
    public function activeCount(int $pageantId): int {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM participants WHERE pageant_id = ? AND is_active = 1",
            [$pageantId]
        );
        
        return (int)$result['count'];
    }
    
    /**
     * Update participant status
     */
    public function updateStatus(int $participantId, bool $isActive): void {
        $this->db->execute(
            "UPDATE participants SET is_active = ? WHERE id = ?",
            [$isActive ? 1 : 0, $participantId]
        );
    }
    
    /**
     * Get participants by division
     */
    public function getByDivision(int $pageantId, string $division): array {
        return $this->db->fetchAll(
            "SELECT p.*, d.name as division_name 
             FROM participants p 
             LEFT JOIN divisions d ON p.division_id = d.id 
             WHERE p.pageant_id = ? AND d.name = ? AND p.is_active = 1 
             ORDER BY p.number_label",
            [$pageantId, $division]
        );
    }
    
    /**
     * Add single participant (private helper)
     */
    private function addSingle(int $pageantId, array $data): int {
        $this->db->execute(
            "INSERT INTO participants (pageant_id, division_id, number_label, full_name, advocacy, is_active, created_at) 
             VALUES (?, ?, ?, ?, ?, 1, NOW())",
            [
                $pageantId,
                $data['division_id'],
                $data['number_label'],
                $data['full_name'],
                $data['advocacy'] ?? ''
            ]
        );
        
        return (int)$this->db->lastInsertId();
    }
}