<?php
// Rounds procedural helpers (migrated & expanded from RoundService)
require_once __DIR__.'/database.php';

function rounds_get(int $roundId): ?array {
	$pdo = Database::get();
	$stmt = $pdo->prepare('SELECT * FROM rounds WHERE id=?');
	$stmt->execute([$roundId]);
	return $stmt->fetch() ?: null;
}

function rounds_list_for_pageant(int $pageantId): array {
	$pdo = Database::get();
	$stmt = $pdo->prepare('SELECT * FROM rounds WHERE pageant_id=? ORDER BY sequence ASC, id ASC');
	$stmt->execute([$pageantId]);
	return $stmt->fetchAll();
}

function rounds_get_active(int $pageantId): ?array {
	$pdo = Database::get();
	$stmt = $pdo->prepare("SELECT * FROM rounds WHERE pageant_id=? AND state='OPEN' ORDER BY sequence LIMIT 1");
	$stmt->execute([$pageantId]);
	return $stmt->fetch() ?: null;
}

function rounds_get_current(int $pageantId): ?array {
	$pdo = Database::get();
	$stmt = $pdo->prepare("SELECT * FROM rounds WHERE pageant_id=? AND state IN ('OPEN','CLOSED') ORDER BY sequence DESC LIMIT 1");
	$stmt->execute([$pageantId]);
	return $stmt->fetch() ?: null;
}

function rounds_open(int $roundId): bool {
	$pdo = Database::get();
	$pdo->beginTransaction();
	try {
		$pidStmt = $pdo->prepare('SELECT pageant_id FROM rounds WHERE id=?');
		$pidStmt->execute([$roundId]);
		$pageantId = (int)$pidStmt->fetchColumn();
		if (!$pageantId) { $pdo->rollBack(); return false; }
		// Close any currently open rounds for this pageant
		$close = $pdo->prepare("UPDATE rounds SET state='CLOSED', closed_at=NOW() WHERE pageant_id=? AND state='OPEN'");
		$close->execute([$pageantId]);
		// Open target round
		$open = $pdo->prepare("UPDATE rounds SET state='OPEN', opened_at=NOW() WHERE id=?");
		$open->execute([$roundId]);
		$pdo->commit();
		return $open->rowCount()===1;
	} catch (Throwable $e) {
		$pdo->rollBack();
		throw $e;
	}
}

function rounds_close(int $roundId): bool {
	$pdo = Database::get();
	$stmt = $pdo->prepare("UPDATE rounds SET state='CLOSED', closed_at=NOW() WHERE id=? AND state='OPEN'");
	$stmt->execute([$roundId]);
	return $stmt->rowCount()===1;
}

// Criteria (full + leaf)
function rounds_list_criteria(int $roundId, bool $leafOnly=false): array {
	$pdo = Database::get();
	$sql = "SELECT c.*, rc.weight, rc.max_score, rc.display_order FROM criteria c INNER JOIN round_criteria rc ON c.id=rc.criterion_id WHERE rc.round_id=?";
	if ($leafOnly) $sql .= " AND c.is_leaf=1";
	$sql .= " ORDER BY rc.display_order, c.sort_order";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$roundId]);
	return $stmt->fetchAll();
}

function rounds_add_criterion(int $roundId, int $criterionId, float $weight, float $maxScore=10.0, int $displayOrder=0): bool {
	$pdo = Database::get();
	$stmt = $pdo->prepare("INSERT INTO round_criteria (round_id,criterion_id,weight,max_score,display_order) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE weight=VALUES(weight), max_score=VALUES(max_score), display_order=VALUES(display_order)");
	return $stmt->execute([$roundId,$criterionId,$weight,$maxScore,$displayOrder]);
}

function rounds_remove_criterion(int $roundId, int $criterionId): bool {
	$pdo = Database::get();
	$stmt = $pdo->prepare('DELETE FROM round_criteria WHERE round_id=? AND criterion_id=?');
	$stmt->execute([$roundId,$criterionId]);
	return $stmt->rowCount()===1;
}

// Status summary (used for guards & UI)
function rounds_status_summary(int $roundId): array {
	$round = rounds_get($roundId);
	if (!$round) return [];
	$pdo = Database::get();
	$criteriaCount = (int)$pdo->prepare('SELECT COUNT(*) FROM round_criteria WHERE round_id=?')->execute([$roundId]) ?: 0; // placeholder
	$stmt = $pdo->prepare('SELECT COUNT(*) as c FROM round_criteria WHERE round_id=?'); $stmt->execute([$roundId]); $criteriaCount = (int)$stmt->fetchColumn();
	$stmt = $pdo->prepare('SELECT COUNT(DISTINCT p.id) FROM participants p WHERE p.pageant_id=? AND p.is_active=1'); $stmt->execute([$round['pageant_id']]); $participantCount = (int)$stmt->fetchColumn();
	$stmt = $pdo->prepare("SELECT COUNT(*) FROM pageant_users WHERE pageant_id=? AND role='JUDGE'"); $stmt->execute([$round['pageant_id']]); $judgeCount = (int)$stmt->fetchColumn();
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM scores s INNER JOIN round_criteria rc ON s.criterion_id=rc.criterion_id AND rc.round_id=s.round_id WHERE s.round_id=?'); $stmt->execute([$roundId]); $submitted = (int)$stmt->fetchColumn();
	$expected = $criteriaCount * $participantCount * $judgeCount;
	return [
		'round'=>$round,
		'criteria_count'=>$criteriaCount,
		'participant_count'=>$participantCount,
		'judge_count'=>$judgeCount,
		'total_scores_expected'=>$expected,
		'scores_submitted'=>$submitted,
		'completion_percentage'=>$expected>0? round(($submitted/$expected)*100,1):0
	];
}

function rounds_can_open(int $roundId): bool {
	$s = rounds_status_summary($roundId);
	return !empty($s) && $s['criteria_count']>0 && $s['participant_count']>0 && $s['judge_count']>0;
}

function rounds_create(int $pageantId, string $name, int $sequence, string $scoringMode='PRELIM', ?int $advancementLimit=null): ?int {
	$pdo = Database::get();
	$stmt = $pdo->prepare('INSERT INTO rounds (pageant_id,name,sequence,state,scoring_mode,advancement_limit,created_at) VALUES (?,?,?,"PENDING",?,?,NOW())');
	$stmt->execute([$pageantId,$name,$sequence,$scoringMode,$advancementLimit]);
	return (int)$pdo->lastInsertId();
}

