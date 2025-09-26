<?php
/**
 * ScoreService
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/CacheService.php';

class ScoreService {
    public static function getRoundCriteria(int $roundId): array {
        $pdo = Database::getConnection();
        $sql = 'SELECT rc.round_id, rc.criterion_id, c.name, rc.weight AS weight, rc.max_score
                FROM round_criteria rc
                INNER JOIN criteria c ON c.id = rc.criterion_id
                WHERE rc.round_id = ?
                ORDER BY rc.weight DESC, rc.criterion_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$roundId]);
        return $stmt->fetchAll();
    }

    public static function getScoresForParticipant(int $roundId, int $participantId, int $judgeUserId): array {
        $pdo = Database::getConnection();
        $sql = 'SELECT rc.criterion_id, c.name, rc.max_score, s.raw_score
                FROM round_criteria rc
                INNER JOIN criteria c ON c.id = rc.criterion_id
                LEFT JOIN scores s ON s.round_id = rc.round_id AND s.criterion_id = rc.criterion_id AND s.participant_id = ? AND s.judge_user_id = ?
                WHERE rc.round_id = ?
                ORDER BY rc.weight DESC, rc.criterion_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$participantId, $judgeUserId, $roundId]);
        $res = [];
        while ($r = $stmt->fetch()) {
            $res[$r['criterion_id']] = $r;
        }
        return $res;
    }

    public static function saveScore(int $roundId, int $participantId, int $criterionId, int $judgeUserId, float $value): bool {
        $pdo = Database::getConnection();
        // Fetch criterion constraints
        $cStmt = $pdo->prepare('SELECT rc.max_score FROM round_criteria rc WHERE rc.round_id = ? AND rc.criterion_id = ?');
        $cStmt->execute([$roundId, $criterionId]);
        $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
        if (!$cRow) {
            throw new RuntimeException('Criterion not part of round');
        }
        $max = (float)$cRow['max_score'];
        if ($value < 0) $value = 0; // clamp lower
        if ($value > $max) $value = $max; // clamp upper

        $stmt = $pdo->prepare('INSERT INTO scores (round_id, participant_id, criterion_id, judge_user_id, raw_score, updated_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE raw_score=VALUES(raw_score), updated_at=NOW()');
        $ok = $stmt->execute([$roundId, $participantId, $criterionId, $judgeUserId, $value]);
        if ($ok) {
            CacheService::forget("round_lb_{$roundId}");
        }
        return $ok;
    }
}
