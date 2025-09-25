<?php
require_once __DIR__.'/database.php';
function awards_list(int $pageantId): array { $pdo=Database::get(); $stmt=$pdo->prepare('SELECT * FROM awards WHERE pageant_id=? ORDER BY id ASC'); $stmt->execute([$pageantId]); return $stmt->fetchAll(); }
function awards_set_manual_winners(int $awardId,array $participantIds): bool { $pdo=Database::get(); $pdo->beginTransaction(); $del=$pdo->prepare('DELETE FROM award_results WHERE award_id=?'); $del->execute([$awardId]); $ins=$pdo->prepare('INSERT INTO award_results (award_id, participant_id, ordering) VALUES (?,?,?)'); $o=1; foreach($participantIds as $pid){ $ins->execute([$awardId,(int)$pid,$o++]); } $pdo->commit(); return true; }
