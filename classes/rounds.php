<?php
class rounds {
    private database $db;

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function open_round(int $roundId): bool {
        try {
            $pdo = $this->db->opencon();
            $pdo->beginTransaction();

            // Get round details
            $stmt = $pdo->prepare("SELECT pageant_id FROM rounds WHERE id = ?");
            $stmt->execute([$roundId]);
            $round = $stmt->fetch();
            
            if (!$round) {
                $pdo->rollBack();
                return false;
            }

            // Close any currently open rounds in the same pageant
            $stmt = $pdo->prepare(
                "UPDATE rounds 
                 SET state = 'CLOSED', closed_at = NOW() 
                 WHERE pageant_id = ? AND state = 'OPEN'"
            );
            $stmt->execute([$round['pageant_id']]);

            // Open the specified round
            $stmt = $pdo->prepare(
                "UPDATE rounds 
                 SET state = 'OPEN', opened_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->execute([$roundId]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            error_log("Open round error: " . $e->getMessage());
            return false;
        }
    }

    public function close_round(int $roundId): bool {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "UPDATE rounds 
                 SET state = 'CLOSED', closed_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->execute([$roundId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Close round error: " . $e->getMessage());
            return false;
        }
    }

    public function get_active_round(int $pageantId): ?array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT * FROM rounds 
                 WHERE pageant_id = ? AND state = 'OPEN' 
                 ORDER BY opened_at DESC 
                 LIMIT 1"
            );
            $stmt->execute([$pageantId]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            error_log("Get active round error: " . $e->getMessage());
            return null;
        }
    }

    public function get_round_criteria(int $roundId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT rc.*, c.name, c.description 
                 FROM round_criteria rc 
                 JOIN criteria c ON rc.criteria_id = c.id 
                 WHERE rc.round_id = ? 
                 ORDER BY rc.sequence_number, c.name"
            );
            $stmt->execute([$roundId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get round criteria error: " . $e->getMessage());
            return [];
        }
    }

    public function finalize_round(int $roundId): bool {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "UPDATE rounds 
                 SET state = 'FINALIZED', finalized_at = NOW() 
                 WHERE id = ? AND state = 'CLOSED'"
            );
            $stmt->execute([$roundId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Finalize round error: " . $e->getMessage());
            return false;
        }
    }

    public function record_advancements(int $fromRoundId, int $toRoundId, array $participantIds): bool {
        try {
            $pdo = $this->db->opencon();
            $pdo->beginTransaction();

            // Clear existing advancements for this progression
            $stmt = $pdo->prepare(
                "DELETE FROM advancements 
                 WHERE from_round_id = ? AND to_round_id = ?"
            );
            $stmt->execute([$fromRoundId, $toRoundId]);

            // Insert new advancements with sequential ranking
            $stmt = $pdo->prepare(
                "INSERT INTO advancements (from_round_id, to_round_id, participant_id, rank_at_advancement, created_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );

            foreach ($participantIds as $rank => $participantId) {
                $stmt->execute([$fromRoundId, $toRoundId, $participantId, $rank + 1]);
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            error_log("Record advancements error: " . $e->getMessage());
            return false;
        }
    }
}