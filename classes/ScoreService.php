<?php
require_once 'database.php';

/**
 * Score Service
 * Handles score submission, calculation, and leaderboard generation
 */
class ScoreService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Submit a score
     */
    public function submitScore(int $roundId, int $criterionId, int $participantId, int $judgeUserId, float $score): void {
        // Get the round criteria to validate max score
        $roundCriterion = $this->db->fetch(
            "SELECT max_score FROM round_criteria WHERE round_id = ? AND criterion_id = ?",
            [$roundId, $criterionId]
        );
        
        if (!$roundCriterion) {
            throw new Exception("Criterion not found in this round");
        }
        
        if ($score < 0 || $score > $roundCriterion['max_score']) {
            throw new Exception("Score must be between 0 and {$roundCriterion['max_score']}");
        }
        
        $this->db->execute(
            "INSERT INTO scores (round_id, criterion_id, participant_id, judge_user_id, raw_score, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, NOW(), NOW()) 
             ON DUPLICATE KEY UPDATE raw_score = VALUES(raw_score), updated_at = NOW()",
            [$roundId, $criterionId, $participantId, $judgeUserId, $score]
        );
    }
    
    /**
     * Get scores for a specific participant in a round
     */
    public function getParticipantScores(int $roundId, int $participantId): array {
        return $this->db->fetchAll(
            "SELECT s.*, c.name as criterion_name, u.full_name as judge_name, rc.weight, rc.max_score
             FROM scores s 
             INNER JOIN criteria c ON s.criterion_id = c.id 
             INNER JOIN users u ON s.judge_user_id = u.id 
             INNER JOIN round_criteria rc ON s.round_id = rc.round_id AND s.criterion_id = rc.criterion_id 
             WHERE s.round_id = ? AND s.participant_id = ? 
             ORDER BY rc.display_order, c.sort_order",
            [$roundId, $participantId]
        );
    }
    
    /**
     * Get all scores for a judge in a round
     */
    public function getJudgeScores(int $roundId, int $judgeUserId): array {
        return $this->db->fetchAll(
            "SELECT s.*, c.name as criterion_name, p.full_name as participant_name, p.number_label, rc.weight, rc.max_score
             FROM scores s 
             INNER JOIN criteria c ON s.criterion_id = c.id 
             INNER JOIN participants p ON s.participant_id = p.id 
             INNER JOIN round_criteria rc ON s.round_id = rc.round_id AND s.criterion_id = rc.criterion_id 
             WHERE s.round_id = ? AND s.judge_user_id = ? 
             ORDER BY p.number_label, rc.display_order",
            [$roundId, $judgeUserId]
        );
    }
    
    /**
     * Calculate leaderboard for a round
     */
    public function calculateLeaderboard(int $roundId, ?int $divisionId = null): array {
        $divisionFilter = $divisionId ? "AND p.division_id = $divisionId" : "";
        
        $participants = $this->db->fetchAll(
            "SELECT DISTINCT p.*, d.name as division_name 
             FROM participants p 
             INNER JOIN divisions d ON p.division_id = d.id 
             WHERE p.pageant_id = (SELECT pageant_id FROM rounds WHERE id = ?) 
             AND p.is_active = 1 $divisionFilter 
             ORDER BY p.number_label",
            [$roundId]
        );
        
        $leaderboard = [];
        
        foreach ($participants as $participant) {
            $totalScore = $this->calculateParticipantTotalScore($roundId, $participant['id']);
            
            $leaderboard[] = [
                'participant' => $participant,
                'total_score' => $totalScore,
                'scores_by_judge' => $this->getParticipantScoresByJudge($roundId, $participant['id'])
            ];
        }
        
        // Sort by total score descending
        usort($leaderboard, function($a, $b) {
            return $b['total_score'] <=> $a['total_score'];
        });
        
        // Add rankings
        foreach ($leaderboard as $index => &$entry) {
            $entry['rank'] = $index + 1;
        }
        
        return $leaderboard;
    }
    
    /**
     * Calculate total weighted score for a participant in a round
     */
    private function calculateParticipantTotalScore(int $roundId, int $participantId): float {
        $result = $this->db->fetch(
            "SELECT SUM(
                CASE 
                    WHEN s.override_score IS NOT NULL THEN s.override_score * (rc.weight / 100)
                    ELSE s.raw_score * (rc.weight / 100)
                END
             ) as total_score
             FROM scores s 
             INNER JOIN round_criteria rc ON s.round_id = rc.round_id AND s.criterion_id = rc.criterion_id 
             WHERE s.round_id = ? AND s.participant_id = ?",
            [$roundId, $participantId]
        );
        
        return (float)($result['total_score'] ?? 0);
    }
    
    /**
     * Get participant scores broken down by judge
     */
    private function getParticipantScoresByJudge(int $roundId, int $participantId): array {
        return $this->db->fetchAll(
            "SELECT u.full_name as judge_name, 
                    SUM(
                        CASE 
                            WHEN s.override_score IS NOT NULL THEN s.override_score * (rc.weight / 100)
                            ELSE s.raw_score * (rc.weight / 100)
                        END
                    ) as judge_total_score,
                    COUNT(s.id) as scores_submitted
             FROM scores s 
             INNER JOIN users u ON s.judge_user_id = u.id 
             INNER JOIN round_criteria rc ON s.round_id = rc.round_id AND s.criterion_id = rc.criterion_id 
             WHERE s.round_id = ? AND s.participant_id = ? 
             GROUP BY s.judge_user_id, u.full_name 
             ORDER BY u.full_name",
            [$roundId, $participantId]
        );
    }
    
    /**
     * Get scoring progress for a round
     */
    public function getScoringProgress(int $roundId): array {
        // Get expected scores count
        $expected = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT rc.criterion_id) * 
                COUNT(DISTINCT p.id) * 
                COUNT(DISTINCT pu.user_id) as expected_scores
             FROM round_criteria rc 
             CROSS JOIN participants p 
             CROSS JOIN pageant_users pu 
             WHERE rc.round_id = ? 
             AND p.pageant_id = (SELECT pageant_id FROM rounds WHERE id = ?) 
             AND p.is_active = 1 
             AND pu.pageant_id = p.pageant_id 
             AND pu.role = 'JUDGE'",
            [$roundId, $roundId]
        );
        
        // Get submitted scores count
        $submitted = $this->db->fetch(
            "SELECT COUNT(*) as submitted_scores 
             FROM scores s 
             INNER JOIN round_criteria rc ON s.round_id = rc.round_id AND s.criterion_id = rc.criterion_id 
             WHERE s.round_id = ?",
            [$roundId]
        );
        
        $expectedCount = (int)($expected['expected_scores'] ?? 0);
        $submittedCount = (int)($submitted['submitted_scores'] ?? 0);
        
        return [
            'expected_scores' => $expectedCount,
            'submitted_scores' => $submittedCount,
            'completion_percentage' => $expectedCount > 0 ? round(($submittedCount / $expectedCount) * 100, 1) : 0,
            'is_complete' => $expectedCount > 0 && $submittedCount >= $expectedCount
        ];
    }
    
    /**
     * Override a score (admin function)
     */
    public function overrideScore(int $scoreId, float $newScore, int $adminUserId, string $reason): void {
        $this->db->execute(
            "UPDATE scores 
             SET override_score = ?, override_reason = ?, overridden_by_user_id = ?, updated_at = NOW() 
             WHERE id = ?",
            [$newScore, $reason, $adminUserId, $scoreId]
        );
    }
    
    /**
     * Remove score override
     */
    public function removeOverride(int $scoreId): void {
        $this->db->execute(
            "UPDATE scores 
             SET override_score = NULL, override_reason = NULL, overridden_by_user_id = NULL, updated_at = NOW() 
             WHERE id = ?",
            [$scoreId]
        );
    }
    
    /**
     * Check if judge has completed scoring for a round
     */
    public function hasJudgeCompletedRound(int $roundId, int $judgeUserId): bool {
        $progress = $this->getJudgeScoringProgress($roundId, $judgeUserId);
        return $progress['is_complete'];
    }
    
    /**
     * Get judge's scoring progress for a round
     */
    public function getJudgeScoringProgress(int $roundId, int $judgeUserId): array {
        // Get expected scores for this judge
        $expected = $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT rc.criterion_id) * COUNT(DISTINCT p.id) as expected_scores
             FROM round_criteria rc 
             CROSS JOIN participants p 
             WHERE rc.round_id = ? 
             AND p.pageant_id = (SELECT pageant_id FROM rounds WHERE id = ?) 
             AND p.is_active = 1",
            [$roundId, $roundId]
        );
        
        // Get submitted scores by this judge
        $submitted = $this->db->fetch(
            "SELECT COUNT(*) as submitted_scores 
             FROM scores s 
             INNER JOIN round_criteria rc ON s.round_id = rc.round_id AND s.criterion_id = rc.criterion_id 
             WHERE s.round_id = ? AND s.judge_user_id = ?",
            [$roundId, $judgeUserId]
        );
        
        $expectedCount = (int)($expected['expected_scores'] ?? 0);
        $submittedCount = (int)($submitted['submitted_scores'] ?? 0);
        
        return [
            'expected_scores' => $expectedCount,
            'submitted_scores' => $submittedCount,
            'completion_percentage' => $expectedCount > 0 ? round(($submittedCount / $expectedCount) * 100, 1) : 0,
            'is_complete' => $expectedCount > 0 && $submittedCount >= $expectedCount
        ];
    }
}