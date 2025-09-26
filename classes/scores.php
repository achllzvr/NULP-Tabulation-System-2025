<?php
declare(strict_types=1);

function scores_save(int $judge_id, int $participant_id, int $round_id, int $criteria_id, float $score): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // TODO: Implement score saving with validation
        $stmt = $pdo->prepare("
            INSERT INTO scores (judge_id, participant_id, round_id, criteria_id, score, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE score = VALUES(score), updated_at = NOW()
        ");
        $stmt->execute([$judge_id, $participant_id, $round_id, $criteria_id, $score]);
        
        return ['success' => true, 'message' => 'Score saved successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to save score: ' . $e->getMessage()];
    }
}

function scores_aggregate_round(int $round_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // TODO: Implement score aggregation logic
        $stmt = $pdo->prepare("
            SELECT 
                p.id as participant_id,
                p.name as participant_name,
                SUM(s.score * c.weight) as total_score,
                AVG(s.score) as average_score
            FROM participants p
            LEFT JOIN scores s ON p.id = s.participant_id
            LEFT JOIN criteria c ON s.criteria_id = c.id
            WHERE s.round_id = ?
            GROUP BY p.id
            ORDER BY total_score DESC
        ");
        $stmt->execute([$round_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function scores_get_by_judge_round(int $judge_id, int $round_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $stmt = $pdo->prepare("
            SELECT s.*, p.name as participant_name, c.name as criteria_name
            FROM scores s
            JOIN participants p ON s.participant_id = p.id
            JOIN criteria c ON s.criteria_id = c.id
            WHERE s.judge_id = ? AND s.round_id = ?
            ORDER BY p.name, c.weight DESC
        ");
        $stmt->execute([$judge_id, $round_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function scores_get_leaderboard(int $round_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // TODO: Implement comprehensive leaderboard calculation
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.name,
                p.number,
                SUM(s.score * c.weight) as total_score,
                COUNT(DISTINCT s.judge_id) as judges_count
            FROM participants p
            LEFT JOIN scores s ON p.id = s.participant_id
            LEFT JOIN criteria c ON s.criteria_id = c.id
            WHERE s.round_id = ?
            GROUP BY p.id
            HAVING judges_count > 0
            ORDER BY total_score DESC
        ");
        $stmt->execute([$round_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}