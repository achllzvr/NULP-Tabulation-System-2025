<?php
declare(strict_types=1);

function awards_list(int $pageant_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $stmt = $pdo->prepare("
            SELECT a.*, p.name as winner_name 
            FROM awards a
            LEFT JOIN participants p ON a.winner_id = p.id
            WHERE a.pageant_id = ?
            ORDER BY a.order_priority, a.name
        ");
        $stmt->execute([$pageant_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function awards_set_manual_winners(int $pageant_id, array $awards_data): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $pdo->beginTransaction();
        
        foreach ($awards_data as $award) {
            // TODO: Implement manual award winner setting
            $stmt = $pdo->prepare("
                UPDATE awards 
                SET winner_id = ?, manually_set = 1, updated_at = NOW()
                WHERE id = ? AND pageant_id = ?
            ");
            $stmt->execute([$award['winner_id'], $award['award_id'], $pageant_id]);
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Award winners set successfully'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Failed to set winners: ' . $e->getMessage()];
    }
}

function awards_calculate_automatic(int $pageant_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // TODO: Implement automatic award calculation based on scores
        // This would typically calculate winners based on final round results
        
        $stmt = $pdo->prepare("
            SELECT 
                'Winner' as award_name,
                p.id as participant_id,
                p.name as participant_name,
                SUM(s.score * c.weight) as total_score
            FROM participants p
            JOIN scores s ON p.id = s.participant_id
            JOIN criteria c ON s.criteria_id = c.id
            JOIN rounds r ON s.round_id = r.id
            WHERE r.pageant_id = ? AND r.is_final = 1
            GROUP BY p.id
            ORDER BY total_score DESC
            LIMIT 5
        ");
        $stmt->execute([$pageant_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function awards_get_categories(int $pageant_id): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $stmt = $pdo->prepare("SELECT * FROM award_categories WHERE pageant_id = ? ORDER BY order_priority");
        $stmt->execute([$pageant_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}