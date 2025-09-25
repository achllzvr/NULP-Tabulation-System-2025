<?php
// Scores procedural helpers (migrated & simplified from ScoreService)
require_once __DIR__.'/database.php';

// Save / upsert raw judge score
function scores_save(int $roundId,int $criterionId,int $participantId,int $judgeUserId,float $score): bool {
	$pdo = Database::get();
	// Validate criterion belongs to round and grab max
	$val = $pdo->prepare('SELECT max_score FROM round_criteria WHERE round_id=? AND criterion_id=?');
	$val->execute([$roundId,$criterionId]);
	$rc = $val->fetch();
	if(!$rc) throw new Exception('Criterion not in round');
	if ($score < 0 || $score > (float)$rc['max_score']) throw new Exception('Score out of range');
	$stmt=$pdo->prepare("INSERT INTO scores (round_id,criterion_id,participant_id,judge_user_id,raw_score,created_at,updated_at)
		VALUES (?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE raw_score=VALUES(raw_score), updated_at=NOW()");
	return $stmt->execute([$roundId,$criterionId,$participantId,$judgeUserId,$score]);
}

// Aggregate weighted total per participant (using weight% * normalized score)
function scores_leaderboard(int $roundId, ?int $divisionId=null): array {
	$pdo = Database::get();
	$divisionFilter = $divisionId ? ' AND p.division_id='.(int)$divisionId : '';
	$sql = "SELECT p.id AS participant_id, p.full_name, p.number_label, d.name AS division,
		ROUND(AVG( (COALESCE(s.override_score,s.raw_score)/rc.max_score)*100 * (rc.weight/100) ),4) AS total_score
		FROM participants p
		JOIN divisions d ON d.id=p.division_id
		LEFT JOIN scores s ON s.participant_id=p.id AND s.round_id=?
		LEFT JOIN round_criteria rc ON rc.round_id=? AND rc.criterion_id=s.criterion_id
		WHERE p.pageant_id=(SELECT pageant_id FROM rounds WHERE id=?) $divisionFilter
		GROUP BY p.id ORDER BY division,total_score DESC";
	$stmt=$pdo->prepare($sql);
	$stmt->execute([$roundId,$roundId,$roundId]);
	$rows=$stmt->fetchAll();
	// Append rank within division
	$rankByDiv=[]; foreach($rows as &$r){ $div=$r['division']; if(!isset($rankByDiv[$div])) $rankByDiv[$div]=1; $r['rank_division']=$rankByDiv[$div]++; }
	return $rows;
}

function scores_participant_total(int $roundId,int $participantId): float {
	$pdo = Database::get();
	$stmt=$pdo->prepare("SELECT SUM( (COALESCE(s.override_score,s.raw_score)/rc.max_score) * (rc.weight/100) * 100 ) AS total
		FROM scores s JOIN round_criteria rc ON rc.round_id=s.round_id AND rc.criterion_id=s.criterion_id
		WHERE s.round_id=? AND s.participant_id=?");
	$stmt->execute([$roundId,$participantId]);
	return (float)($stmt->fetch()['total'] ?? 0);
}

function scores_round_progress(int $roundId): array {
	$pdo = Database::get();
	$counts = [];
	$counts['criteria'] = (int)$pdo->query("SELECT COUNT(*) FROM round_criteria WHERE round_id=".(int)$roundId)->fetchColumn();
	$counts['participants'] = (int)$pdo->query("SELECT COUNT(*) FROM participants WHERE pageant_id=(SELECT pageant_id FROM rounds WHERE id=".(int)$roundId.") AND is_active=1")->fetchColumn();
	$counts['judges'] = (int)$pdo->query("SELECT COUNT(*) FROM pageant_users WHERE pageant_id=(SELECT pageant_id FROM rounds WHERE id=".(int)$roundId.") AND role='JUDGE'")->fetchColumn();
	$expected = $counts['criteria'] * $counts['participants'] * $counts['judges'];
	$submitted = (int)$pdo->query("SELECT COUNT(*) FROM scores WHERE round_id=".(int)$roundId)->fetchColumn();
	return [
		'expected_scores'=>$expected,
		'submitted_scores'=>$submitted,
		'completion_percentage'=>$expected>0? round(($submitted/$expected)*100,1):0,
		'is_complete'=>$expected>0 && $submitted >= $expected
	];
}

function scores_judge_progress(int $roundId,int $judgeUserId): array {
	$pdo = Database::get();
	$counts['criteria'] = (int)$pdo->query("SELECT COUNT(*) FROM round_criteria WHERE round_id=".(int)$roundId)->fetchColumn();
	$counts['participants'] = (int)$pdo->query("SELECT COUNT(*) FROM participants WHERE pageant_id=(SELECT pageant_id FROM rounds WHERE id=".(int)$roundId.") AND is_active=1")->fetchColumn();
	$expected = $counts['criteria'] * $counts['participants'];
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM scores WHERE round_id=? AND judge_user_id=?');
	$stmt->execute([$roundId,$judgeUserId]);
	$submitted = (int)$stmt->fetchColumn();
	return [
		'expected_scores'=>$expected,
		'submitted_scores'=>$submitted,
		'completion_percentage'=>$expected>0? round(($submitted/$expected)*100,1):0,
		'is_complete'=>$expected>0 && $submitted >= $expected
	];
}

function scores_override(int $scoreId,float $newScore,int $adminUserId,string $reason): bool {
	$pdo = Database::get();
	$stmt = $pdo->prepare('UPDATE scores SET override_score=?, override_reason=?, overridden_by_user_id=?, updated_at=NOW() WHERE id=?');
	return $stmt->execute([$newScore,$reason,$adminUserId,$scoreId]);
}

