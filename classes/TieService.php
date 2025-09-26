<?php
/**
 * TieService
 * Detects boundary ties for advancement and manages resolution (placeholder simplified logic).
 */
require_once __DIR__ . '/AdvancementService.php';
require_once __DIR__ . '/LeaderboardService.php';
require_once __DIR__ . '/database.php';

class TieService {
    private AdvancementService $adv;
    private LeaderboardService $lb;
    private PDO $db;
    public function __construct() {
        $this->adv = new AdvancementService();
        $this->lb = new LeaderboardService();
        $this->db = Database::getConnection();
        $this->ensureTables();
    }

    private function ensureTables(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS tie_groups (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                round_id BIGINT UNSIGNED NOT NULL,
                boundary_rank INT NOT NULL,
                status ENUM('OPEN','RESOLVED') NOT NULL DEFAULT 'OPEN',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                resolved_at DATETIME NULL,
                CONSTRAINT fk_tg_round FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE,
                INDEX idx_tie_groups_round (round_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS tie_group_participants (
                tie_group_id BIGINT UNSIGNED NOT NULL,
                participant_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (tie_group_id, participant_id),
                CONSTRAINT fk_tgp_tg FOREIGN KEY (tie_group_id) REFERENCES tie_groups(id) ON DELETE CASCADE,
                CONSTRAINT fk_tgp_part FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->exec("CREATE TABLE IF NOT EXISTS tie_group_resolution (
                tie_group_id BIGINT UNSIGNED PRIMARY KEY,
                chosen_participant_ids JSON NOT NULL,
                method VARCHAR(50) NOT NULL DEFAULT 'MANUAL',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_tgr_tg FOREIGN KEY (tie_group_id) REFERENCES tie_groups(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) { /* silent */ }
    }

    /**
     * Returns tie info if boundary tie exists.
     */
    public function listBoundaryTie(int $roundId): array {
        $preview = $this->adv->preview($roundId);
        if (!$preview['blocked'] || !$preview['tie_at_boundary']) {
            return ['has_tie'=>false];
        }
        // determine boundary rank participants
        $round = $this->lb->getRoundStandings($roundId);
        if (empty($round)) return ['has_tie'=>false];
        $limit = $preview['advancement_limit'];
        if (!$limit) return ['has_tie'=>false];
        // gather participants at boundary rank
        // replicate selection to find cutoff rank first
        $count = 0; $cutoffRank=null; $selected=[];
        foreach ($round as $row) {
            $count++;
            if ($count <= $limit) { $selected[]=$row; $cutoffRank=$row['rank']; } else break;
        }
        if ($cutoffRank===null) return ['has_tie'=>false];
        $boundary = array_filter($round, fn($r)=>$r['rank']==$cutoffRank);
        // Attempt to fetch existing open tie group
        $stmt = $this->db->prepare('SELECT id FROM tie_groups WHERE round_id = ? AND status = "OPEN" AND boundary_rank = ? LIMIT 1');
        $stmt->execute([$roundId, $cutoffRank]);
        $tgId = $stmt->fetchColumn();
        if (!$tgId) {
            // create
            $ins = $this->db->prepare('INSERT INTO tie_groups (round_id, boundary_rank) VALUES (?,?)');
            $ins->execute([$roundId, $cutoffRank]);
            $tgId = (int)$this->db->lastInsertId();
            $insP = $this->db->prepare('INSERT INTO tie_group_participants (tie_group_id, participant_id) VALUES (?,?)');
            foreach ($boundary as $b) { $insP->execute([$tgId, $b['participant_id']]); }
        }
        return [
            'has_tie'=>true,
            'boundary_rank'=>$cutoffRank,
            'participants'=>array_values($boundary),
            'tie_group_id'=>(int)$tgId,
            'message'=>'Tie at advancement boundary. Resolve by setting chosen participants.'
        ];
    }

    /**
     * Placeholder resolution approach: choose specific participant IDs to advance, trimming others.
     * Actual tie resolution might capture additional scores or criteria; here we return filtered candidate list.
     */
    public function resolveBoundaryTie(int $roundId, array $advanceParticipantIds): array {
        $tie = $this->listBoundaryTie($roundId);
        if (!$tie['has_tie']) return ['success'=>false,'error'=>'No tie to resolve'];
        $tgId = (int)($tie['tie_group_id'] ?? 0);
        if (!$tgId) return ['success'=>false,'error'=>'Tie group missing'];
        $boundaryIds = array_map(fn($r)=>(int)$r['participant_id'],$tie['participants']);
        $advanceParticipantIds = array_values(array_unique(array_map('intval',$advanceParticipantIds)));
        if (empty($advanceParticipantIds)) return ['success'=>false,'error'=>'No participants chosen'];
        foreach ($advanceParticipantIds as $pid) {
            if (!in_array($pid,$boundaryIds,true)) return ['success'=>false,'error'=>'Participant not in tie boundary'];
        }
        // persist resolution
        $this->db->beginTransaction();
        try {
            $up = $this->db->prepare('UPDATE tie_groups SET status="RESOLVED", resolved_at = NOW() WHERE id = ?');
            $up->execute([$tgId]);
            $ins = $this->db->prepare('REPLACE INTO tie_group_resolution (tie_group_id, chosen_participant_ids, method) VALUES (?,?,?)');
            $ins->execute([$tgId, json_encode($advanceParticipantIds, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), 'MANUAL']);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success'=>false,'error'=>'Persist failed'];
        }
        return [
            'success'=>true,
            'tie_group_id'=>$tgId,
            'chosen'=>$advanceParticipantIds,
            'message'=>'Tie resolved and stored.'
        ];
    }
}
