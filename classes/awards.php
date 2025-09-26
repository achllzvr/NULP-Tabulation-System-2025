<?php
/**
 * Awards management functions
 * Handles listing awards and setting manual winners
 */

/**
 * List all awards for current pageant
 * @return array Array of award data
 */
function awards_list() {
    $pageant = pageant_get_current();
    if (!$pageant) {
        return [];
    }
    
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT * FROM awards WHERE pageant_id = ? ORDER BY award_order ASC, award_name ASC");
    $stmt->execute([$pageant['pageant_id']]);
    return $stmt->fetchAll();
}

/**
 * Set manual winner for an award
 * @param int $award_id Award ID
 * @param int $participant_id Participant ID
 * @return bool Success status
 */
function awards_set_winner($award_id, $participant_id) {
    $pdo = database::opencon();
    
    try {
        $pdo->beginTransaction();
        
        // Clear existing winner for this award
        $stmt = $pdo->prepare("UPDATE awards SET winner_participant_id = NULL WHERE award_id = ?");
        $stmt->execute([$award_id]);
        
        // Set new winner
        $stmt = $pdo->prepare("UPDATE awards SET winner_participant_id = ?, awarded_at = NOW() WHERE award_id = ?");
        $stmt->execute([$participant_id, $award_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Get award details with winner info
 * @param int $award_id Award ID
 * @return array|null Award data with winner details
 */
function awards_get_details($award_id) {
    $pdo = database::opencon();
    
    $sql = "SELECT 
                a.*,
                p.contestant_number,
                p.first_name as winner_first_name,
                p.last_name as winner_last_name
            FROM awards a
            LEFT JOIN participants p ON a.winner_participant_id = p.participant_id
            WHERE a.award_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$award_id]);
    return $stmt->fetch();
}

/**
 * Get automatic award suggestions based on final scores
 * @return array Array of suggested winners for auto-awards
 * TODO: Implement logic for different award types (1st place, 2nd place, etc.)
 */
function awards_get_suggestions() {
    $pageant = pageant_get_current();
    if (!$pageant) {
        return [];
    }
    
    // TODO: Implement automatic award calculation based on final round scores
    // This would typically look at the final round results and suggest winners
    // for positional awards (1st, 2nd, 3rd place, etc.)
    
    return [
        // Placeholder structure for manual implementation
        // 'first_place' => ['participant_id' => X, 'score' => Y],
        // 'second_place' => ['participant_id' => X, 'score' => Y],
        // etc.
    ];
}

/**
 * Clear winner for an award
 * @param int $award_id Award ID
 * @return bool Success status
 */
function awards_clear_winner($award_id) {
    $pdo = database::opencon();
    
    $stmt = $pdo->prepare("UPDATE awards SET winner_participant_id = NULL, awarded_at = NULL WHERE award_id = ?");
    return $stmt->execute([$award_id]);
}