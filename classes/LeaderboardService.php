<?php
/**
 * LeaderboardService
 * Provides ordered standings for a round (and later overall/award aggregations).
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/CacheService.php';

class LeaderboardService {
    private PDO $db;
    public function __construct() { $this->db = Database::getConnection(); }

    /**
     * Get standings for a round.
     * Returns array of rows: participant_id, full_name, division, weighted_total, rank
     */
    public function getRoundStandings(int $roundId, ?callable $normalizer = null): array {
        if ($normalizer === null) {
            // Auto-pick based on round configuration (if column exists)
            $strategy = $this->detectStrategy($roundId);
            if ($strategy !== null && $strategy !== 'RAW') {
                $normalizer = function(array $rows) use ($strategy, $roundId) {
                    return $this->applyNormalization($roundId, $rows, $strategy);
                };
            }
        }
        $cacheKey = "round_lb_{$roundId}";
        if ($normalizer === null) { // only cache RAW form; normalization depends on strategy embedded
            $cached = CacheService::get($cacheKey);
            if ($cached) return $cached;
        }
        // Prefer the view if it exists
        $sqlView = "SELECT rpt.round_id, rpt.participant_id, rpt.weighted_total, p.full_name, p.division
                    FROM v_round_participant_totals rpt
                    INNER JOIN participants p ON p.id = rpt.participant_id
                    WHERE rpt.round_id = ?
                    ORDER BY rpt.weighted_total DESC, p.full_name";
        try {
            $stmt = $this->db->prepare($sqlView);
            $stmt->execute([$roundId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                if ($normalizer) { $rows = $normalizer($rows, $roundId); }
                return $this->assignDenseRank($rows);
            }
        } catch (Exception $e) {
            // fallback if view missing; compute inline
        }

        $fallback = "SELECT s.participant_id,
                            p.full_name,
                            p.division,
                            ROUND(SUM( (COALESCE(s.override_score, s.raw_score) / rc.max_score) * rc.weight ),4) AS weighted_total
                     FROM scores s
                     INNER JOIN round_criteria rc ON rc.round_id = s.round_id AND rc.criterion_id = s.criterion_id
                     INNER JOIN participants p ON p.id = s.participant_id
                     WHERE s.round_id = ?
                     GROUP BY s.participant_id, p.full_name, p.division
                     ORDER BY weighted_total DESC, p.full_name";
        $stmt2 = $this->db->prepare($fallback);
        $stmt2->execute([$roundId]);
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    if ($normalizer) { $rows = $normalizer($rows, $roundId); }
    $ranked = $this->assignDenseRank($rows);
    if ($normalizer === null) CacheService::put($cacheKey, $ranked);
    return $ranked;
    }

    private function assignDenseRank(array $rows): array {
        $rank = 0; $last = null; $out = [];
        foreach ($rows as $r) {
            $wt = (float)$r['weighted_total'];
            if ($last === null || abs($wt - $last) > 0.0000001) { $rank++; $last = $wt; }
            $r['rank'] = $rank;
            $out[] = $r;
        }
        return $out;
    }

    private function detectStrategy(int $roundId): ?string {
        try {
            $stmt = $this->db->prepare('SELECT score_normalization_strategy FROM rounds WHERE id = ?');
            $stmt->execute([$roundId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
            $val = strtoupper(trim($row['score_normalization_strategy'] ?? ''));
            if ($val === '') return 'RAW';
            return $val;
        } catch (Exception $e) { return null; }
    }

    private function applyNormalization(int $roundId, array $rows, string $strategy): array {
        switch ($strategy) {
            case 'Z_SCORE':
                return $this->normalizeZScore($roundId, $rows);
            case 'MIN_MAX_PER_JUDGE':
                return $this->normalizeMinMaxPerJudge($roundId, $rows);
            default:
                return $rows; // RAW or unknown
        }
    }

    private function normalizeZScore(int $roundId, array $rows): array {
        // Need per participant judge-summed raw totals to derive distribution; we already have weighted_total as aggregate of normalized criterion weight.
        // For Z-score, treat weighted_total as variable X.
        $values = array_map(fn($r)=>(float)$r['weighted_total'], $rows);
        $n = count($values);
        if ($n < 2) return $rows; // can't compute variance
        $mean = array_sum($values)/$n;
        $variance = 0.0;
        foreach ($values as $v) { $variance += ($v - $mean) ** 2; }
        $variance /= ($n - 1);
        $std = $variance > 0 ? sqrt($variance) : 0.0;
        if ($std == 0.0) return $rows; // all equal
        foreach ($rows as &$r) {
            $z = ((float)$r['weighted_total'] - $mean) / $std;
            // rescale z to positive scale: optional keep raw z; store in weighted_total for ranking
            $r['weighted_total'] = round($z, 6);
        }
        unset($r);
        // Re-rank after transformation
        usort($rows, function($a,$b){ return ($b['weighted_total'] <=> $a['weighted_total']) ?: strcmp($a['full_name'],$b['full_name']); });
        return $rows;
    }

    private function normalizeMinMaxPerJudge(int $roundId, array $rows): array {
        // Re-compute by pulling raw per judge participant totals, normalizing each judge's distribution to 0..1, then averaging and finally applying weights scale
        // Step 1: fetch per judge raw total (sum of (raw/ max * weight))
        $sql = 'SELECT s.judge_user_id, s.participant_id, ROUND(SUM((COALESCE(s.override_score, s.raw_score) / rc.max_score) * rc.weight),6) AS judge_weighted
                FROM scores s
                INNER JOIN round_criteria rc ON rc.round_id = s.round_id AND rc.criterion_id = s.criterion_id
                WHERE s.round_id = ?
                GROUP BY s.judge_user_id, s.participant_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roundId]);
        $byJudge = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $jid = (int)$r['judge_user_id'];
            $pid = (int)$r['participant_id'];
            $byJudge[$jid][$pid] = (float)$r['judge_weighted'];
        }
        if (!$byJudge) return $rows;
        // Step 2: per judge min-max normalize
        $normalizedPerJudge = [];
        foreach ($byJudge as $jid => $scores) {
            $vals = array_values($scores);
            $min = min($vals); $max = max($vals); $range = $max - $min;
            foreach ($scores as $pid=>$val) {
                $norm = ($range <= 0) ? 0.5 : ($val - $min)/$range; // center if all equal
                $normalizedPerJudge[$pid][] = $norm;
            }
        }
        // Step 3: average normalized per participant
        $normTotals = [];
        foreach ($normalizedPerJudge as $pid => $arr) {
            $normTotals[$pid] = array_sum($arr)/count($arr); // 0..1
        }
        // Step 4: map back onto rows preserving division/name
        foreach ($rows as &$r) {
            $pid = (int)$r['participant_id'];
            if (isset($normTotals[$pid])) {
                $r['weighted_total'] = round($normTotals[$pid], 6);
            }
        }
        unset($r);
        usort($rows, function($a,$b){ return ($b['weighted_total'] <=> $a['weighted_total']) ?: strcmp($a['full_name'],$b['full_name']); });
        return $rows;
    }
}
