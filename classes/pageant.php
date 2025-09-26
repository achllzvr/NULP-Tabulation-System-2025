<?php
declare(strict_types=1);

function pageant_set_current(int $pageant_id): bool {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // Verify pageant exists
        $stmt = $pdo->prepare("SELECT id, name FROM pageants WHERE id = ? AND active = 1");
        $stmt->execute([$pageant_id]);
        $pageant = $stmt->fetch();
        
        if ($pageant) {
            $_SESSION['pageant_id'] = $pageant_id;
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function pageant_get_current(): ?array {
    if (!isset($_SESSION['pageant_id'])) {
        return null;
    }
    
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $stmt = $pdo->prepare("SELECT * FROM pageants WHERE id = ?");
        $stmt->execute([$_SESSION['pageant_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function pageant_list_active(): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $stmt = $pdo->prepare("SELECT id, name, description, start_date, end_date FROM pageants WHERE active = 1 ORDER BY start_date DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function pageant_list_rounds(int $pageant_id): array {
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