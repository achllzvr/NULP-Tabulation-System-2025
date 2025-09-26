<?php
/**
 * Pageant management functions
 * Handles pageant selection and rounds listing
 */

/**
 * Get current selected pageant from session
 * @return array|null Current pageant data or null
 */
function pageant_get_current() {
    if (!isset($_SESSION['pageant_id'])) {
        return null;
    }
    
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT * FROM pageants WHERE pageant_id = ? AND active = 1");
    $stmt->execute([$_SESSION['pageant_id']]);
    return $stmt->fetch();
}

/**
 * Set current pageant in session
 * @param int $pageant_id Pageant ID
 * @return bool Success status
 */
function pageant_set_current($pageant_id) {
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT pageant_id FROM pageants WHERE pageant_id = ? AND active = 1");
    $stmt->execute([$pageant_id]);
    
    if ($stmt->fetch()) {
        $_SESSION['pageant_id'] = $pageant_id;
        return true;
    }
    
    return false;
}

/**
 * List all active pageants
 * @return array Array of pageant data
 */
function pageant_list_all() {
    $pdo = database::opencon();
    $stmt = $pdo->query("SELECT * FROM pageants WHERE active = 1 ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Get rounds for current pageant
 * @return array Array of round data
 */
function pageant_list_rounds() {
    $pageant = pageant_get_current();
    if (!$pageant) {
        return [];
    }
    
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT * FROM rounds WHERE pageant_id = ? ORDER BY round_order ASC");
    $stmt->execute([$pageant['pageant_id']]);
    return $stmt->fetchAll();
}

/**
 * Get participants for current pageant
 * @return array Array of participant data
 */
function pageant_list_participants() {
    $pageant = pageant_get_current();
    if (!$pageant) {
        return [];
    }
    
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT * FROM participants WHERE pageant_id = ? AND active = 1 ORDER BY contestant_number ASC");
    $stmt->execute([$pageant['pageant_id']]);
    return $stmt->fetchAll();
}