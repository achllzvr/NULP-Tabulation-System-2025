<?php
declare(strict_types=1);

function rounds_open(int $round_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // TODO: Implement round opening logic
        $stmt = $pdo->prepare("UPDATE rounds SET status = 'open', opened_at = NOW() WHERE id = ?");
        $stmt->execute([$round_id]);
        
        return ['success' => true, 'message' => 'Round opened successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to open round: ' . $e->getMessage()];
    }
}

function rounds_close(int $round_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // TODO: Implement round closing logic
        $stmt = $pdo->prepare("UPDATE rounds SET status = 'closed', closed_at = NOW() WHERE id = ?");
        $stmt->execute([$round_id]);
        
        return ['success' => true, 'message' => 'Round closed successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to close round: ' . $e->getMessage()];
    }
}

function rounds_get_active(int $pageant_id): ?array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $stmt = $pdo->prepare("SELECT * FROM rounds WHERE pageant_id = ? AND status = 'open' ORDER BY round_order LIMIT 1");
        $stmt->execute([$pageant_id]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function rounds_list_criteria(int $round_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // TODO: Implement criteria listing
        $stmt = $pdo->prepare("SELECT * FROM criteria WHERE round_id = ? ORDER BY weight DESC");
        $stmt->execute([$round_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function rounds_get_by_pageant(int $pageant_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $stmt = $pdo->prepare("SELECT * FROM rounds WHERE pageant_id = ? ORDER BY round_order");
        $stmt->execute([$pageant_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}