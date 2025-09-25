<?php
require_once 'database.php';

/**
 * Round Service
 * Handles round management, states, and round-specific operations
 */
class RoundService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get rounds for a pageant
     */
    public function listForPageant(int $pageantId): array {
        return $this->db->fetchAll(
            "SELECT * FROM rounds WHERE pageant_id = ? ORDER BY sequence",
            [$pageantId]
        );
    }
    
    /**
     * Get round by ID
     */
    public function getById(int $roundId): ?array {
        return $this->db->fetch(
            "SELECT * FROM rounds WHERE id = ?",
            [$roundId]
        );
    }
    
    /**
     * Get active round for pageant
     */
    public function getActiveRound(int $pageantId): ?array {
        return $this->db->fetch(
            "SELECT * FROM rounds WHERE pageant_id = ? AND state = 'OPEN' ORDER BY sequence LIMIT 1",
            [$pageantId]
        );
    }
    
    /**
     * Get current round (latest opened or closed)
     */
    public function getCurrentRound(int $pageantId): ?array {
        return $this->db->fetch(
            "SELECT * FROM rounds WHERE pageant_id = ? AND state IN ('OPEN', 'CLOSED') ORDER BY sequence DESC LIMIT 1",
            [$pageantId]
        );
    }
    
    /**
     * Update round state
     */
    public function updateState(int $roundId, string $state): void {
        $validStates = ['PENDING', 'OPEN', 'CLOSED', 'FINALIZED'];
        if (!in_array($state, $validStates)) {
            throw new Exception("Invalid round state: $state");
        }
        
        // Update timestamps based on state
        $updateFields = "state = ?";
        $params = [$state];
        
        if ($state === 'OPEN') {
            $updateFields .= ", opened_at = NOW()";
        } elseif ($state === 'CLOSED') {
            $updateFields .= ", closed_at = NOW()";
        }
        
        $params[] = $roundId;
        
        $this->db->execute(
            "UPDATE rounds SET $updateFields WHERE id = ?",
            $params
        );
    }
    
    /**
     * Get criteria for a round
     */
    public function getCriteria(int $roundId): array {
        return $this->db->fetchAll(
            "SELECT c.*, rc.weight, rc.max_score, rc.display_order 
             FROM criteria c 
             INNER JOIN round_criteria rc ON c.id = rc.criterion_id 
             WHERE rc.round_id = ? 
             ORDER BY rc.display_order, c.sort_order",
            [$roundId]
        );
    }
    
    /**
     * Get all leaf criteria for a round (only criteria that can be scored)
     */
    public function getLeafCriteria(int $roundId): array {
        return $this->db->fetchAll(
            "SELECT c.*, rc.weight, rc.max_score, rc.display_order 
             FROM criteria c 
             INNER JOIN round_criteria rc ON c.id = rc.criterion_id 
             WHERE rc.round_id = ? AND c.is_leaf = 1 
             ORDER BY rc.display_order, c.sort_order",
            [$roundId]
        );
    }
    
    /**
     * Add criterion to round
     */
    public function addCriterion(int $roundId, int $criterionId, float $weight, float $maxScore = 10.0, int $displayOrder = 0): void {
        $this->db->execute(
            "INSERT INTO round_criteria (round_id, criterion_id, weight, max_score, display_order) 
             VALUES (?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE weight = VALUES(weight), max_score = VALUES(max_score), display_order = VALUES(display_order)",
            [$roundId, $criterionId, $weight, $maxScore, $displayOrder]
        );
    }
    
    /**
     * Remove criterion from round
     */
    public function removeCriterion(int $roundId, int $criterionId): void {
        $this->db->execute(
            "DELETE FROM round_criteria WHERE round_id = ? AND criterion_id = ?",
            [$roundId, $criterionId]
        );
    }
    
    /**
     * Get round status summary
     */
    public function getStatusSummary(int $roundId): array {
        $round = $this->getById($roundId);
        if (!$round) {
            return [];
        }
        
        // Get total criteria count
        $criteriaCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM round_criteria WHERE round_id = ?",
            [$roundId]
        )['count'];
        
        // Get total participants
        $participantCount = $this->db->fetch(
            "SELECT COUNT(DISTINCT p.id) as count 
             FROM participants p 
             WHERE p.pageant_id = ? AND p.is_active = 1",
            [$round['pageant_id']]
        )['count'];
        
        // Get judges assigned to this pageant
        $judgeCount = $this->db->fetch(
            "SELECT COUNT(*) as count 
             FROM pageant_users pu 
             WHERE pu.pageant_id = ? AND pu.role = 'JUDGE'",
            [$round['pageant_id']]
        )['count'];
        
        // Get scoring progress
        $totalScores = $criteriaCount * $participantCount * $judgeCount;
        $submittedScores = $this->db->fetch(
            "SELECT COUNT(*) as count 
             FROM scores s 
             INNER JOIN round_criteria rc ON s.criterion_id = rc.criterion_id 
             WHERE rc.round_id = ?",
            [$roundId]
        )['count'];
        
        return [
            'round' => $round,
            'criteria_count' => $criteriaCount,
            'participant_count' => $participantCount,
            'judge_count' => $judgeCount,
            'total_scores_expected' => $totalScores,
            'scores_submitted' => $submittedScores,
            'completion_percentage' => $totalScores > 0 ? round(($submittedScores / $totalScores) * 100, 1) : 0
        ];
    }
    
    /**
     * Check if round can be opened
     */
    public function canOpen(int $roundId): bool {
        $status = $this->getStatusSummary($roundId);
        return $status['criteria_count'] > 0 && $status['participant_count'] > 0 && $status['judge_count'] > 0;
    }
    
    /**
     * Create new round
     */
    public function create(int $pageantId, string $name, int $sequence, string $scoringMode = 'PRELIM', ?int $advancementLimit = null): int {
        $this->db->execute(
            "INSERT INTO rounds (pageant_id, name, sequence, state, scoring_mode, advancement_limit, created_at) 
             VALUES (?, ?, ?, 'PENDING', ?, ?, NOW())",
            [$pageantId, $name, $sequence, $scoringMode, $advancementLimit]
        );
        
        return (int)$this->db->lastInsertId();
    }
}