<?php
class awards {
    private database $db;

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function list_awards(int $pageantId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT * FROM awards 
                 WHERE pageant_id = ? 
                 ORDER BY award_type, sequence_number, name"
            );
            $stmt->execute([$pageantId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("List awards error: " . $e->getMessage());
            return [];
        }
    }

    public function compute_award_overall(int $awardId): array {
        // Stub placeholder for future expansion
        // This would contain logic to automatically compute award winners
        // based on criteria like highest scores, specific round performance, etc.
        return [];
    }

    public function set_manual_award_results(int $awardId, array $participantIds): bool {
        try {
            $pdo = $this->db->opencon();
            $pdo->beginTransaction();

            // Clear existing results for this award
            $stmt = $pdo->prepare("DELETE FROM award_results WHERE award_id = ?");
            $stmt->execute([$awardId]);

            // Insert new results with ranking
            $stmt = $pdo->prepare(
                "INSERT INTO award_results (award_id, participant_id, rank, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );

            foreach ($participantIds as $rank => $participantId) {
                $stmt->execute([$awardId, $participantId, $rank + 1]);
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            error_log("Set manual award results error: " . $e->getMessage());
            return false;
        }
    }

    public function fetch_award_results(int $pageantId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT ar.*, a.name as award_name, a.award_type, 
                        p.number_label, p.full_name, p.division_id,
                        d.name as division_name
                 FROM award_results ar
                 JOIN awards a ON ar.award_id = a.id
                 JOIN participants p ON ar.participant_id = p.id
                 LEFT JOIN divisions d ON p.division_id = d.id
                 WHERE a.pageant_id = ?
                 ORDER BY a.sequence_number, a.name, ar.rank"
            );
            $stmt->execute([$pageantId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Fetch award results error: " . $e->getMessage());
            return [];
        }
    }

    public function get_award_details(int $awardId): ?array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare("SELECT * FROM awards WHERE id = ?");
            $stmt->execute([$awardId]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            error_log("Get award details error: " . $e->getMessage());
            return null;
        }
    }

    public function create_manual_vote(int $awardId, int $judgeUserId, int $participantId, int $rank): bool {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "INSERT INTO manual_votes (award_id, judge_user_id, participant_id, rank, created_at) 
                 VALUES (?, ?, ?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE 
                 rank = VALUES(rank), updated_at = NOW()"
            );
            $stmt->execute([$awardId, $judgeUserId, $participantId, $rank]);
            return true;
        } catch (Exception $e) {
            error_log("Create manual vote error: " . $e->getMessage());
            return false;
        }
    }

    public function get_manual_votes(int $awardId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT mv.*, u.full_name as judge_name, p.number_label, p.full_name as participant_name
                 FROM manual_votes mv
                 JOIN users u ON mv.judge_user_id = u.id
                 JOIN participants p ON mv.participant_id = p.id
                 WHERE mv.award_id = ?
                 ORDER BY mv.judge_user_id, mv.rank"
            );
            $stmt->execute([$awardId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get manual votes error: " . $e->getMessage());
            return [];
        }
    }
}