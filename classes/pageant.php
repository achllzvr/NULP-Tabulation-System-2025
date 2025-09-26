<?php
class pageant {
    private database $db;

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function get_current_pageant(): ?array {
        if (!isset($_SESSION['pageant_id'])) {
            return null;
        }

        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT * FROM pageants WHERE id = ? AND is_active = 1"
            );
            $stmt->execute([$_SESSION['pageant_id']]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            error_log("Get current pageant error: " . $e->getMessage());
            return null;
        }
    }

    public function set_current_pageant(int $pageantId): bool {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT id FROM pageants WHERE id = ? AND is_active = 1"
            );
            $stmt->execute([$pageantId]);
            
            if ($stmt->fetch()) {
                $_SESSION['pageant_id'] = $pageantId;
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Set current pageant error: " . $e->getMessage());
            return false;
        }
    }

    public function list_pageant_rounds(int $pageantId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT * FROM rounds 
                 WHERE pageant_id = ? 
                 ORDER BY sequence_number, created_at"
            );
            $stmt->execute([$pageantId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("List pageant rounds error: " . $e->getMessage());
            return [];
        }
    }

    public function compute_reveal_flags(array $pageantRow): array {
        return [
            'show_names' => (bool)($pageantRow['show_participant_names'] ?? false),
            'prelim_revealed' => (bool)($pageantRow['prelim_results_revealed'] ?? false),
            'final_revealed' => (bool)($pageantRow['final_results_revealed'] ?? false)
        ];
    }

    public function ensure_pageant_selected(): void {
        if (!isset($_SESSION['pageant_id'])) {
            header('Location: /dashboard.php');
            exit;
        }
    }

    public function list_user_pageants(int $userId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT p.*, pu.role as user_role 
                 FROM pageants p 
                 JOIN pageant_users pu ON p.id = pu.pageant_id 
                 WHERE pu.user_id = ? AND p.is_active = 1 
                 ORDER BY p.created_at DESC"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("List user pageants error: " . $e->getMessage());
            return [];
        }
    }

    public function get_pageant_participants(int $pageantId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT p.*, d.name as division_name 
                 FROM participants p 
                 LEFT JOIN divisions d ON p.division_id = d.id 
                 WHERE p.pageant_id = ? AND p.is_active = 1 
                 ORDER BY p.number_label"
            );
            $stmt->execute([$pageantId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get pageant participants error: " . $e->getMessage());
            return [];
        }
    }
}