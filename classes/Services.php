<?php
require_once 'database.php';

/**
 * Round Service - Manages rounds and criteria
 */
class RoundService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function list(int $pageantId): array {
        return $this->db->fetchAll(
            "SELECT * FROM rounds WHERE pageant_id = ? ORDER BY sequence",
            [$pageantId]
        );
    }
    
    public function open(int $roundId, int $adminUserId): void {
        $this->db->execute(
            "UPDATE rounds SET status = 'OPEN', opened_at = NOW(), opened_by = ? WHERE id = ?",
            [$adminUserId, $roundId]
        );
    }
    
    public function close(int $roundId, int $adminUserId): void {
        $this->db->execute(
            "UPDATE rounds SET status = 'CLOSED', closed_at = NOW(), closed_by = ? WHERE id = ?",
            [$adminUserId, $roundId]
        );
    }
    
    public function currentOpen(int $pageantId): ?array {
        return $this->db->fetch(
            "SELECT * FROM rounds WHERE pageant_id = ? AND status = 'OPEN' LIMIT 1",
            [$pageantId]
        );
    }
}

/**
 * Score Service - Manages scoring operations
 */
class ScoreService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function saveScore(int $roundId, int $criterionId, int $participantId, int $judgeUserId, float $value): void {
        $this->db->execute(
            "INSERT INTO scores (round_id, criterion_id, participant_id, judge_user_id, score, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE score = VALUES(score), updated_at = NOW()",
            [$roundId, $criterionId, $participantId, $judgeUserId, $value]
        );
    }
    
    public function aggregateRoundScores(int $roundId): array {
        return $this->db->fetchAll(
            "SELECT p.id, p.full_name, p.number_label, d.name as division,
                    AVG(s.score * c.weight / 100) as weighted_average
             FROM participants p
             LEFT JOIN scores s ON p.id = s.participant_id
             LEFT JOIN criteria c ON s.criterion_id = c.id
             LEFT JOIN divisions d ON p.division_id = d.id
             WHERE s.round_id = ? AND p.is_active = 1
             GROUP BY p.id
             ORDER BY d.name, weighted_average DESC",
            [$roundId]
        );
    }
}

/**
 * Advancement Service - Manages participant advancement
 */
class AdvancementService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function computeTopN(int $roundId, int $n = 5): array {
        // This would implement the top N selection logic
        return [];
    }
}

/**
 * Award and Visibility Services (basic stubs)
 */
class AwardService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function list(int $pageantId): array {
        return $this->db->fetchAll("SELECT * FROM awards WHERE pageant_id = ?", [$pageantId]);
    }
}

class VisibilityService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function updateFlags(int $pageantId, array $flags): void {
        foreach ($flags as $key => $value) {
            $this->db->execute(
                "INSERT INTO pageant_settings (pageant_id, setting_key, setting_value, updated_at) 
                 VALUES (?, ?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
                [$pageantId, $key, $value ? '1' : '0']
            );
        }
    }
}

class TieService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function listTieGroups(int $roundId): array {
        return []; // Stub implementation
    }
}