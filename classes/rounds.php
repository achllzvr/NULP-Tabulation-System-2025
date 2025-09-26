<?php
/**
 * Round management functions
 * Handles opening/closing rounds and fetching criteria
 */

/**
 * Open a round (auto-closes existing open round of same pageant)
 * @param int $round_id Round ID to open
 * @return bool Success status
 */
function rounds_open($round_id) {
    $pdo = database::opencon();
    
    // Get round info
    $stmt = $pdo->prepare("SELECT * FROM rounds WHERE round_id = ?");
    $stmt->execute([$round_id]);
    $round = $stmt->fetch();
    
    if (!$round) {
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Close any existing open round for this pageant
        $stmt = $pdo->prepare("UPDATE rounds SET status = 'closed' WHERE pageant_id = ? AND status = 'open'");
        $stmt->execute([$round['pageant_id']]);
        
        // Open the new round
        $stmt = $pdo->prepare("UPDATE rounds SET status = 'open', opened_at = NOW() WHERE round_id = ?");
        $stmt->execute([$round_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Close a round
 * @param int $round_id Round ID to close
 * @return bool Success status
 */
function rounds_close($round_id) {
    $pdo = database::opencon();
    
    $stmt = $pdo->prepare("UPDATE rounds SET status = 'closed', closed_at = NOW() WHERE round_id = ?");
    return $stmt->execute([$round_id]);
}

/**
 * Get current open round for pageant
 * @param int $pageant_id Pageant ID
 * @return array|null Round data or null
 */
function rounds_get_open($pageant_id) {
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT * FROM rounds WHERE pageant_id = ? AND status = 'open' LIMIT 1");
    $stmt->execute([$pageant_id]);
    return $stmt->fetch();
}

/**
 * Get criteria for a round
 * @param int $round_id Round ID
 * @return array Array of criteria data
 */
function rounds_get_criteria($round_id) {
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT * FROM criteria WHERE round_id = ? ORDER BY weight DESC, criteria_order ASC");
    $stmt->execute([$round_id]);
    return $stmt->fetchAll();
}

/**
 * Get round details by ID
 * @param int $round_id Round ID
 * @return array|null Round data or null
 */
function rounds_get_by_id($round_id) {
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT * FROM rounds WHERE round_id = ?");
    $stmt->execute([$round_id]);
    return $stmt->fetch();
}