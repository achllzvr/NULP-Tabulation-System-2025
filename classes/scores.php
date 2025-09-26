<?php
/**
 * Scoring functions
 * Handles saving scores and aggregating leaderboards
 */

/**
 * Save score with INSERT ... ON DUPLICATE KEY UPDATE
 * @param int $judge_id Judge ID
 * @param int $participant_id Participant ID
 * @param int $criteria_id Criteria ID
 * @param int $round_id Round ID
 * @param float $score Score value
 * @return bool Success status
 */
function scores_save($judge_id, $participant_id, $criteria_id, $round_id, $score) {
    $pdo = database::opencon();
    
    $sql = "INSERT INTO scores (judge_id, participant_id, criteria_id, round_id, score, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE 
            score = VALUES(score), 
            updated_at = NOW()";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$judge_id, $participant_id, $criteria_id, $round_id, $score]);
}

/**
 * Aggregate scores for a round
 * @param int $round_id Round ID
 * @return array Array of participant scores with weighted totals
 */
function scores_aggregate_round($round_id) {
    $pdo = database::opencon();
    
    $sql = "SELECT 
                p.participant_id,
                p.contestant_number,
                p.first_name,
                p.last_name,
                SUM(s.score * c.weight) as weighted_total,
                COUNT(DISTINCT s.criteria_id) as criteria_scored,
                COUNT(DISTINCT s.judge_id) as judges_scored
            FROM participants p
            JOIN scores s ON p.participant_id = s.participant_id
            JOIN criteria c ON s.criteria_id = c.criteria_id
            WHERE s.round_id = ? AND p.active = 1
            GROUP BY p.participant_id
            ORDER BY weighted_total DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$round_id]);
    return $stmt->fetchAll();
}

/**
 * Get individual scores for a participant in a round
 * @param int $participant_id Participant ID
 * @param int $round_id Round ID
 * @return array Array of detailed scores
 */
function scores_get_participant_details($participant_id, $round_id) {
    $pdo = database::opencon();
    
    $sql = "SELECT 
                s.*,
                c.criteria_name,
                c.weight,
                j.first_name as judge_first_name,
                j.last_name as judge_last_name
            FROM scores s
            JOIN criteria c ON s.criteria_id = c.criteria_id
            JOIN users j ON s.judge_id = j.user_id
            WHERE s.participant_id = ? AND s.round_id = ?
            ORDER BY c.criteria_order ASC, j.last_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$participant_id, $round_id]);
    return $stmt->fetchAll();
}

/**
 * Get scoring progress for a round
 * @param int $round_id Round ID
 * @return array Summary of scoring completion
 */
function scores_get_progress($round_id) {
    $pdo = database::opencon();
    
    $sql = "SELECT 
                COUNT(DISTINCT p.participant_id) as total_participants,
                COUNT(DISTINCT j.user_id) as total_judges,
                COUNT(DISTINCT c.criteria_id) as total_criteria,
                COUNT(s.score_id) as total_scores_submitted,
                (COUNT(DISTINCT p.participant_id) * COUNT(DISTINCT j.user_id) * COUNT(DISTINCT c.criteria_id)) as expected_scores
            FROM participants p
            CROSS JOIN users j
            CROSS JOIN criteria c
            LEFT JOIN scores s ON (
                s.participant_id = p.participant_id 
                AND s.judge_id = j.user_id 
                AND s.criteria_id = c.criteria_id 
                AND s.round_id = ?
            )
            WHERE p.pageant_id = (SELECT pageant_id FROM rounds WHERE round_id = ?)
                AND p.active = 1
                AND j.role = 'judge'
                AND j.active = 1
                AND c.round_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$round_id, $round_id, $round_id]);
    return $stmt->fetch();
}