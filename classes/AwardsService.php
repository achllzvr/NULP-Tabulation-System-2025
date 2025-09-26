<?php
/**
 * AwardsService
 */
require_once __DIR__ . '/database.php';

class AwardsService {
    public static function getAwards(int $pageant_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM awards WHERE pageant_id = ? ORDER BY display_order');
        $stmt->execute([$pageant_id]);
        return $stmt->fetchAll();
    }

    public static function getAwardWinners(int $award_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT aw.*, p.full_name, p.division FROM award_winners aw INNER JOIN participants p ON p.id = aw.participant_id WHERE aw.award_id = ? ORDER BY aw.rank_position');
        $stmt->execute([$award_id]);
        return $stmt->fetchAll();
    }

    public static function setManualWinners(int $award_id, array $participant_ids): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM award_winners WHERE award_id = ?')->execute([$award_id]);
            $stmt = $pdo->prepare('INSERT INTO award_winners (award_id, participant_id, rank_position, created_at) VALUES (?,?,?,NOW())');
            $rank = 1;
            foreach ($participant_ids as $pid) {
                $stmt->execute([$award_id, $pid, $rank++]);
            }
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Compute winners for a single award (does not persist unless $persist = true).
     * Expects awards table to have: id, pageant_id, award_type, division_scope, single_criterion_id (nullable), max_winners, aggregation_round_ids JSON
     * Award Types (assumed): SINGLE_CRITERION, MULTI_CRITERION, OVERALL, PEOPLE_CHOICE
     */
    public static function computeAward(array $awardRow, bool $persist = false): array {
        $type = $awardRow['award_type'] ?? '';
        $pageantId = (int)$awardRow['pageant_id'];
        $divisionScope = $awardRow['division_scope'] ?? 'ALL'; // 'ALL' or specific (e.g., 'Mr','Ms')
        $maxWinners = (int)($awardRow['max_winners'] ?? 1);
        if ($maxWinners < 1) { $maxWinners = 1; }
        $pdo = Database::getConnection();

        $candidates = [];
        switch ($type) {
            case 'SINGLE_CRITERION':
                $criterionId = (int)($awardRow['single_criterion_id'] ?? 0);
                if ($criterionId <= 0) { return ['award_id'=>$awardRow['id'], 'type'=>$type, 'winners'=>[], 'error'=>'single_criterion_id missing']; }
                $sql = "SELECT s.participant_id, p.full_name, p.division, AVG(COALESCE(s.override_score, s.raw_score)) AS metric
                        FROM scores s
                        INNER JOIN participants p ON p.id = s.participant_id
                        WHERE s.criterion_id = ?
                        " . ($divisionScope !== 'ALL' ? " AND p.division = :div" : "") . "
                        GROUP BY s.participant_id, p.full_name, p.division
                        ORDER BY metric DESC, p.full_name
                        LIMIT $maxWinners";
                $stmt = $pdo->prepare($sql);
                $params = [$criterionId];
                if ($divisionScope !== 'ALL') { $stmt->bindValue(':div', $divisionScope); }
                $stmt->execute($params);
                $candidates = $stmt->fetchAll();
                break;
            case 'MULTI_CRITERION':
                // Sum normalized weights across a provided set of criteria (round_criteria weights) across rounds in aggregation_round_ids
                $roundIds = self::decodeRoundIds($awardRow['aggregation_round_ids'] ?? null);
                if (!$roundIds) { return ['award_id'=>$awardRow['id'], 'type'=>$type, 'winners'=>[], 'error'=>'aggregation_round_ids empty']; }
                $in = implode(',', array_fill(0, count($roundIds), '?'));
                $sql = "SELECT s.participant_id, p.full_name, p.division,
                               ROUND(SUM( (COALESCE(s.override_score, s.raw_score) / rc.max_score) * rc.weight ),4) AS metric
                        FROM scores s
                        INNER JOIN round_criteria rc ON rc.round_id = s.round_id AND rc.criterion_id = s.criterion_id
                        INNER JOIN participants p ON p.id = s.participant_id
                        WHERE s.round_id IN ($in)" . ($divisionScope !== 'ALL' ? " AND p.division = :div" : "") . "
                        GROUP BY s.participant_id, p.full_name, p.division
                        ORDER BY metric DESC, p.full_name
                        LIMIT $maxWinners";
                $stmt = $pdo->prepare($sql);
                $bindIndex = 1;
                foreach ($roundIds as $rid) { $stmt->bindValue($bindIndex++, $rid, PDO::PARAM_INT); }
                if ($divisionScope !== 'ALL') { $stmt->bindValue(':div', $divisionScope); }
                $stmt->execute();
                $candidates = $stmt->fetchAll();
                break;
            case 'OVERALL':
                // Treat OVERALL as all rounds provided in aggregation_round_ids, aggregated same as MULTI_CRITERION
                $roundIds = self::decodeRoundIds($awardRow['aggregation_round_ids'] ?? null);
                if (!$roundIds) { return ['award_id'=>$awardRow['id'], 'type'=>$type, 'winners'=>[], 'error'=>'aggregation_round_ids empty']; }
                $in = implode(',', array_fill(0, count($roundIds), '?'));
                $sql = "SELECT s.participant_id, p.full_name, p.division,
                               ROUND(SUM( (COALESCE(s.override_score, s.raw_score) / rc.max_score) * rc.weight ),4) AS metric
                        FROM scores s
                        INNER JOIN round_criteria rc ON rc.round_id = s.round_id AND rc.criterion_id = s.criterion_id
                        INNER JOIN participants p ON p.id = s.participant_id
                        WHERE s.round_id IN ($in)" . ($divisionScope !== 'ALL' ? " AND p.division = :div" : "") . "
                        GROUP BY s.participant_id, p.full_name, p.division
                        ORDER BY metric DESC, p.full_name
                        LIMIT $maxWinners";
                $stmt = $pdo->prepare($sql);
                $bindIndex = 1;
                foreach ($roundIds as $rid) { $stmt->bindValue($bindIndex++, $rid, PDO::PARAM_INT); }
                if ($divisionScope !== 'ALL') { $stmt->bindValue(':div', $divisionScope); }
                $stmt->execute();
                $candidates = $stmt->fetchAll();
                break;
            case 'PEOPLE_CHOICE':
                // Expect a votes table or view v_people_choice_votes(participant_id, votes)
                $sql = "SELECT v.participant_id, p.full_name, p.division, v.votes AS metric
                        FROM v_people_choice_votes v
                        INNER JOIN participants p ON p.id = v.participant_id" . ($divisionScope !== 'ALL' ? " WHERE p.division = :div" : "") . "
                        ORDER BY v.votes DESC, p.full_name
                        LIMIT $maxWinners";
                $stmt = $pdo->prepare($sql);
                if ($divisionScope !== 'ALL') { $stmt->bindValue(':div', $divisionScope); }
                try {
                    $stmt->execute();
                    $candidates = $stmt->fetchAll();
                } catch (Exception $e) {
                    return ['award_id'=>$awardRow['id'], 'type'=>$type, 'winners'=>[], 'error'=>'people choice view missing'];
                }
                break;
            default:
                return ['award_id'=>$awardRow['id'], 'type'=>$type, 'winners'=>[], 'error'=>'unknown award_type'];
        }

        // If persist, replace award_winners rows
        if ($persist) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare('DELETE FROM award_winners WHERE award_id = ?')->execute([$awardRow['id']]);
                $ins = $pdo->prepare('INSERT INTO award_winners (award_id, participant_id, rank_position, created_at, source_auto) VALUES (?,?,?,?,NOW(),1)');
                $rank = 1;
                foreach ($candidates as $c) {
                    $ins->execute([$awardRow['id'], $c['participant_id'], $rank++]);
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                return ['award_id'=>$awardRow['id'], 'type'=>$type, 'winners'=>[], 'error'=>'persist_failed'];
            }
        }

        return [
            'award_id' => $awardRow['id'],
            'type' => $type,
            'winners' => $candidates,
            'persisted' => $persist,
        ];
    }

    public static function computeAll(int $pageantId, bool $persist = false): array {
        $awards = self::getAwards($pageantId);
        $out = [];
        foreach ($awards as $a) {
            $out[] = self::computeAward($a, $persist);
        }
        return $out;
    }

    private static function decodeRoundIds(?string $json): array {
        if (!$json) return [];
        $data = json_decode($json, true);
        if (!is_array($data)) return [];
        return array_values(array_filter(array_map('intval', $data), fn($v)=>$v>0));
    }
}
