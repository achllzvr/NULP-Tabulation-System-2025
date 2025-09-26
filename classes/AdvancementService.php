<?php
/**
 * AdvancementService
 * Determines which participants advance from a round based on leaderboard standings and advancement_limit.
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/LeaderboardService.php';

class AdvancementService {
    private PDO $db;
    private LeaderboardService $leaderboard;
    public function __construct() {
        $this->db = Database::getConnection();
        $this->leaderboard = new LeaderboardService();
    }

    /**
     * Preview advancement: returns structure with candidates, cutoff_rank, tie_at_boundary(bool), blocked(bool)
     */
    public function preview(int $roundId): array {
        $round = $this->fetchRound($roundId);
        if (!$round) throw new RuntimeException('Round not found');
        $limit = $round['advancement_limit'];
        if ($limit === null) {
            return [
                'advancement_limit'=>null,
                'candidates'=>[],
                'cutoff_rank'=>null,
                'tie_at_boundary'=>false,
                'blocked'=>false,
                'reason'=>'No advancement_limit set'
            ];
        }
        $standings = $this->leaderboard->getRoundStandings($roundId); // already dense ranked
        if (empty($standings)) {
            return [
                'advancement_limit'=>$limit,
                'candidates'=>[],
                'cutoff_rank'=>null,
                'tie_at_boundary'=>false,
                'blocked'=>true,
                'reason'=>'No scores yet'
            ];
        }
        // Determine participants whose rank <=? but watch boundary tie rule: if tie spans boundary, block
        $cutoffRank = null;
        $maxRankAllowed = null;
        // We want all participants up to rank value such that total count of participants with rank <= that doesn't exceed limit
        $count = 0; $selected=[]; $ranksEncountered=[];
        foreach ($standings as $row) {
            $rank = $row['rank'];
            if (!isset($ranksEncountered[$rank])) $ranksEncountered[$rank] = 0;
            $ranksEncountered[$rank]++;
            $count++;
            if ($count <= $limit) {
                $selected[] = $row;
                $cutoffRank = $rank;
            } else {
                break;
            }
        }
        // Check tie extension beyond limit: collect all rows with cutoffRank
        $tieAtBoundary = false;
        if ($cutoffRank !== null) {
            $sameRankTotal = 0; $sameRankWithinLimit = 0;
            foreach ($standings as $row) {
                if ($row['rank'] == $cutoffRank) {
                    $sameRankTotal++;
                }
            }
            foreach ($selected as $s) { if ($s['rank'] == $cutoffRank) $sameRankWithinLimit++; }
            if ($sameRankTotal > $sameRankWithinLimit) {
                // Boundary rank includes participants we could not include due to limit => tie forcing block
                $tieAtBoundary = true;
            }
        }
        $blocked = $tieAtBoundary;
        return [
            'advancement_limit'=>$limit,
            'candidates'=>$tieAtBoundary ? [] : $selected,
            'cutoff_rank'=>$cutoffRank,
            'tie_at_boundary'=>$tieAtBoundary,
            'blocked'=>$blocked,
            'reason'=>$tieAtBoundary ? 'Tie at boundary rank; resolve tie first' : null
        ];
    }

    /**
     * Commit advancement results into advancements table.
     * Expects no boundary tie (preview first!).
     */
    public function commit(int $roundId, int $userId): int {
        $preview = $this->preview($roundId);
        if ($preview['blocked']) {
            throw new RuntimeException('Advancement blocked: '.$preview['reason']);
        }
        if (empty($preview['candidates'])) return 0;
        $this->db->beginTransaction();
        try {
            $ins = $this->db->prepare('INSERT INTO advancements (round_id, participant_id, created_at, created_by) VALUES (?,?,NOW(),?)');
            $count = 0;
            foreach ($preview['candidates'] as $row) {
                $ins->execute([$roundId, $row['participant_id'], $userId]);
                $count++;
            }
            $this->db->commit();
            return $count;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function fetchRound(int $roundId): ?array {
        $stmt = $this->db->prepare('SELECT id, advancement_limit FROM rounds WHERE id = ?');
        $stmt->execute([$roundId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
}
