<?php
/**
 * RoundService
 * Application-level enforcement for opening rounds (weights == 100 and criteria exist),
 * retrieval helpers, and basic state transitions. Further expansion will cover closing,
 * locking scores, advancement computation, etc.
 */

require_once __DIR__ . '/database.php';

class RoundService {
    private PDO $db;

    public function __construct() {
    $this->db = Database::getConnection();
    }

    /**
     * Returns array with keys: hasCriteria(bool), weightTotal(float|null), isValid(bool), missingReason(string|null)
     */
    public function canOpenRound(int $roundId): array {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt, ROUND(SUM(weight),3) AS total FROM round_criteria WHERE round_id = ?");
        $stmt->execute([$roundId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasCriteria = ((int)$row['cnt']) > 0;
        $total = $row['total'] !== null ? (float)$row['total'] : null;

        if(!$hasCriteria) {
            return [
                'hasCriteria' => false,
                'weightTotal' => $total,
                'isValid' => false,
                'missingReason' => 'No criteria defined for round'
            ];
        }
        if($total === null || abs($total - 100.000) > 0.0005) {
            return [
                'hasCriteria' => true,
                'weightTotal' => $total,
                'isValid' => false,
                'missingReason' => 'Criteria weights must sum to exactly 100.000'
            ];
        }
        return [
            'hasCriteria' => true,
            'weightTotal' => $total,
            'isValid' => true,
            'missingReason' => null
        ];
    }

    /**
     * Attempts to open a round. Throws RuntimeException on validation failure.
     */
    public function openRound(int $roundId, int $userId): bool {
        $check = $this->canOpenRound($roundId);
        if(!$check['isValid']) {
            throw new RuntimeException($check['missingReason']);
        }
        $stmt = $this->db->prepare("UPDATE rounds SET state='OPEN', opened_at = NOW(), opened_by = ? WHERE id = ? AND state <> 'OPEN'");
        $stmt->execute([$userId, $roundId]);
        return $stmt->rowCount() > 0; // true if state changed
    }

    public function closeRound(int $roundId, int $userId): bool {
        $stmt = $this->db->prepare("UPDATE rounds SET state='CLOSED', closed_at = NOW(), closed_by = ? WHERE id = ? AND state='OPEN'");
        $stmt->execute([$userId, $roundId]);
        return $stmt->rowCount() > 0;
    }

    public function getRound(int $roundId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM rounds WHERE id = ?");
        $stmt->execute([$roundId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function listRoundsByPageant(int $pageantId): array {
        $stmt = $this->db->prepare("SELECT * FROM rounds WHERE pageant_id = ? ORDER BY sequence ASC, id ASC");
        $stmt->execute([$pageantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
