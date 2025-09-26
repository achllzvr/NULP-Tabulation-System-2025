<?php
class scores {
    private database $db;

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function upsert_score(int $roundId, int $criterionId, int $participantId, int $judgeUserId, float $rawScore): bool {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "INSERT INTO scores (round_id, criteria_id, participant_id, judge_user_id, raw_score, updated_at) 
                 VALUES (?, ?, ?, ?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE 
                 raw_score = VALUES(raw_score), updated_at = NOW()"
            );
            $stmt->execute([$roundId, $criterionId, $participantId, $judgeUserId, $rawScore]);
            return true;
        } catch (Exception $e) {
            error_log("Upsert score error: " . $e->getMessage());
            return false;
        }
    }

    public function aggregate_round_scores(int $roundId): array {
        try {
            $pdo = $this->db->opencon();
            
            // Get round criteria with weights
            $stmt = $pdo->prepare(
                "SELECT rc.criteria_id, rc.weight, rc.max_score 
                 FROM round_criteria rc 
                 WHERE rc.round_id = ?"
            );
            $stmt->execute([$roundId]);
            $criteria = $stmt->fetchAll();

            if (empty($criteria)) {
                return [];
            }

            // Calculate total weight for normalization
            $totalWeight = array_sum(array_column($criteria, 'weight'));

            // Get all scores for this round
            $stmt = $pdo->prepare(
                "SELECT s.participant_id, s.criteria_id, 
                        COALESCE(s.override_score, s.raw_score) as effective_score,
                        rc.weight, rc.max_score,
                        p.number_label, p.full_name, p.division_id
                 FROM scores s
                 JOIN round_criteria rc ON s.criteria_id = rc.criteria_id AND s.round_id = rc.round_id
                 JOIN participants p ON s.participant_id = p.id
                 WHERE s.round_id = ?
                 ORDER BY s.participant_id, s.criteria_id"
            );
            $stmt->execute([$roundId]);
            $scores = $stmt->fetchAll();

            // Group scores by participant
            $participantScores = [];
            foreach ($scores as $score) {
                $participantId = $score['participant_id'];
                
                if (!isset($participantScores[$participantId])) {
                    $participantScores[$participantId] = [
                        'participant_id' => $participantId,
                        'number_label' => $score['number_label'],
                        'full_name' => $score['full_name'],
                        'division_id' => $score['division_id'],
                        'criteria_scores' => [],
                        'weighted_total' => 0
                    ];
                }

                // Calculate weighted contribution
                $contribution = ($score['effective_score'] / $score['max_score']) * $score['weight'];
                $participantScores[$participantId]['criteria_scores'][] = [
                    'criteria_id' => $score['criteria_id'],
                    'score' => $score['effective_score'],
                    'max_score' => $score['max_score'],
                    'weight' => $score['weight'],
                    'contribution' => $contribution
                ];
                $participantScores[$participantId]['weighted_total'] += $contribution;
            }

            // Normalize if total weight is not 1.0
            if ($totalWeight != 1.0 && $totalWeight > 0) {
                foreach ($participantScores as &$participant) {
                    $participant['weighted_total'] /= $totalWeight;
                }
            }

            // Sort by weighted total descending
            uasort($participantScores, function($a, $b) {
                return $b['weighted_total'] <=> $a['weighted_total'];
            });

            return array_values($participantScores);
        } catch (Exception $e) {
            error_log("Aggregate round scores error: " . $e->getMessage());
            return [];
        }
    }

    public function get_judge_submission_status(int $roundId, int $judgeUserId): ?array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT * FROM judge_round_submissions 
                 WHERE round_id = ? AND judge_user_id = ?"
            );
            $stmt->execute([$roundId, $judgeUserId]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            error_log("Get judge submission status error: " . $e->getMessage());
            return null;
        }
    }

    public function set_judge_submission_status(int $roundId, int $judgeUserId, string $status): bool {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "INSERT INTO judge_round_submissions (round_id, judge_user_id, status, submitted_at) 
                 VALUES (?, ?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE 
                 status = VALUES(status), submitted_at = NOW()"
            );
            $stmt->execute([$roundId, $judgeUserId, $status]);
            return true;
        } catch (Exception $e) {
            error_log("Set judge submission status error: " . $e->getMessage());
            return false;
        }
    }

    public function get_participant_scores_for_judge(int $roundId, int $judgeUserId): array {
        try {
            $pdo = $this->db->opencon();
            $stmt = $pdo->prepare(
                "SELECT s.*, p.number_label, p.full_name, c.name as criteria_name, rc.max_score
                 FROM scores s
                 JOIN participants p ON s.participant_id = p.id
                 JOIN criteria c ON s.criteria_id = c.id
                 JOIN round_criteria rc ON s.criteria_id = rc.criteria_id AND s.round_id = rc.round_id
                 WHERE s.round_id = ? AND s.judge_user_id = ?
                 ORDER BY p.number_label, c.name"
            );
            $stmt->execute([$roundId, $judgeUserId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get participant scores for judge error: " . $e->getMessage());
            return [];
        }
    }
}