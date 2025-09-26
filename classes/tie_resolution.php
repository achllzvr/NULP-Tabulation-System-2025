<?php
class tie_resolution {
    private database $db;

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function create_tie_group(int $roundId, array $participantIds): ?int {
        try {
            $pdo = $this->db->opencon();
            $pdo->beginTransaction();

            // Create tie group record
            $stmt = $pdo->prepare(
                "INSERT INTO tie_groups (round_id, status, created_at) 
                 VALUES (?, 'PENDING', NOW())"
            );
            $stmt->execute([$roundId]);
            $tieGroupId = $pdo->lastInsertId();

            // Get current scores for participants
            $placeholders = str_repeat('?,', count($participantIds) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT participant_id, 
                       SUM((COALESCE(override_score, raw_score) / rc.max_score) * rc.weight) as aggregate_score
                FROM scores s
                JOIN round_criteria rc ON s.criteria_id = rc.criteria_id AND s.round_id = rc.round_id
                WHERE s.round_id = ? AND s.participant_id IN ($placeholders)
                GROUP BY participant_id
            ");
            $stmt->execute(array_merge([$roundId], $participantIds));
            $scores = $stmt->fetchAll();

            // Add participants to tie group with their original scores
            $stmt = $pdo->prepare(
                "INSERT INTO tie_group_participants (tie_group_id, participant_id, original_score, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );

            foreach ($scores as $score) {
                $stmt->execute([$tieGroupId, $score['participant_id'], $score['aggregate_score']]);
            }

            $pdo->commit();
            return $tieGroupId;
        } catch (Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            error_log("Create tie group error: " . $e->getMessage());
            return null;
        }
    }

    public function resolve_tie_group(int $tieGroupId, array $participantRankMap): bool {
        try {
            $pdo = $this->db->opencon();
            $pdo->beginTransaction();

            // Update participant manual ranks
            $stmt = $pdo->prepare(
                "UPDATE tie_group_participants 
                 SET manual_rank = ?, resolved_at = NOW() 
                 WHERE tie_group_id = ? AND participant_id = ?"
            );

            foreach ($participantRankMap as $participantId => $rank) {
                $stmt->execute([$rank, $tieGroupId, $participantId]);
            }

            // Mark tie group as resolved
            $stmt = $pdo->prepare(
                "UPDATE tie_groups 
                 SET status = 'RESOLVED', resolved_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->execute([$tieGroupId]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            error_log("Resolve tie group error: " . $e->getMessage());
            return false;
        }
    }

    public function get_tie_groups_for_round(int $roundId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT tg.*, 
                        COUNT(tgp.participant_id) as participant_count
                 FROM tie_groups tg
                 LEFT JOIN tie_group_participants tgp ON tg.id = tgp.tie_group_id
                 WHERE tg.round_id = ?
                 GROUP BY tg.id
                 ORDER BY tg.created_at DESC"
            );
            $stmt->execute([$roundId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get tie groups for round error: " . $e->getMessage());
            return [];
        }
    }

    public function get_tie_group_participants(int $tieGroupId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT tgp.*, p.number_label, p.full_name, d.name as division_name
                 FROM tie_group_participants tgp
                 JOIN participants p ON tgp.participant_id = p.id
                 LEFT JOIN divisions d ON p.division_id = d.id
                 WHERE tgp.tie_group_id = ?
                 ORDER BY tgp.original_score DESC, p.number_label"
            );
            $stmt->execute([$tieGroupId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get tie group participants error: " . $e->getMessage());
            return [];
        }
    }

    public function delete_tie_group(int $tieGroupId): bool {
        try {
            $pdo = $this->db->opencon();
            $pdo->beginTransaction();

            // Delete participants first (foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM tie_group_participants WHERE tie_group_id = ?");
            $stmt->execute([$tieGroupId]);

            // Delete the tie group
            $stmt = $pdo->prepare("DELETE FROM tie_groups WHERE id = ?");
            $stmt->execute([$tieGroupId]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            error_log("Delete tie group error: " . $e->getMessage());
            return false;
        }
    }
}