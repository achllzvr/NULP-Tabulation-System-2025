<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['adminID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
require_once('../classes/database.php');
$con = new database();
$conn = $con->opencon();

$action = $_GET['action'] ?? '';
try {
    if ($action === 'participant_details') {
        $participantId = isset($_GET['participant_id']) ? (int)$_GET['participant_id'] : 0;
        $roundId = isset($_GET['round_id']) && $_GET['round_id'] !== 'all' ? (int)$_GET['round_id'] : null;
        $stage = $_GET['stage'] ?? 'overall';
        if ($participantId <= 0) {
            throw new Exception('participant_id required');
        }
        // Load criteria depending on round or all
        if ($roundId) {
            $stmt = $conn->prepare("SELECT rc.criterion_id, rc.weight, rc.max_score, c.name
                                     FROM round_criteria rc JOIN criteria c ON rc.criterion_id=c.id
                                     WHERE rc.round_id = ? ORDER BY rc.display_order");
            $stmt->bind_param('i', $roundId);
        } else {
            // overall: include criteria across closed/finalized rounds only
            $stmt = $conn->prepare("SELECT DISTINCT rc.criterion_id, rc.weight, rc.max_score, c.name
                                     FROM round_criteria rc
                                     JOIN rounds r ON r.id=rc.round_id
                                     JOIN criteria c ON rc.criterion_id=c.id
                                     WHERE r.state IN ('CLOSED','FINALIZED')");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $criteria = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch scores for this participant
        if ($roundId) {
            $stmt = $conn->prepare("SELECT s.criterion_id, COALESCE(s.override_score, s.raw_score) as score
                                     FROM scores s JOIN round_criteria rc ON s.criterion_id=rc.criterion_id AND rc.round_id=?
                                     WHERE s.participant_id = ?");
            $stmt->bind_param('ii', $roundId, $participantId);
        } else {
            $stmt = $conn->prepare("SELECT s.criterion_id, COALESCE(s.override_score, s.raw_score) as score
                                     FROM scores s
                                     JOIN round_criteria rc ON s.criterion_id=rc.criterion_id
                                     JOIN rounds r ON r.id=rc.round_id
                                     WHERE s.participant_id = ? AND r.state IN ('CLOSED','FINALIZED')");
            $stmt->bind_param('i', $participantId);
        }
        $stmt->execute();
        $rs = $stmt->get_result();
        $scores = [];
        while ($r = $rs->fetch_assoc()) { $scores[(int)$r['criterion_id']] = (float)$r['score']; }
        $stmt->close();

        $items = [];
        $total = 0.0;
        foreach ($criteria as $c) {
            $cid = (int)$c['criterion_id'];
            $raw = $scores[$cid] ?? 0.0;
            $weight = (float)$c['weight'];
            $weighted = $raw * (($weight>1)? ($weight/100.0) : $weight);
            $items[] = [
                'criterion_id' => $cid,
                'name' => $c['name'],
                'weight' => $weight,
                'raw' => $raw,
                'weighted' => $weighted
            ];
            $total += $weighted;
        }
        echo json_encode(['success' => true, 'items' => $items, 'total' => $total]);
        exit();
    }
    if ($action === 'override_score') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $participant_id = (int)($body['participant_id'] ?? 0);
        $criterion_id = (int)($body['criterion_id'] ?? 0);
        $judge_user_id = (int)($body['judge_user_id'] ?? 0);
        $new_raw = isset($body['raw_score']) ? (float)$body['raw_score'] : null;
        $reason = trim($body['reason'] ?? '');
    // username is no longer needed; we validate by judge_user_id + password
    $judge_password = (string)($body['judge_password'] ?? '');
        if (!$participant_id || !$criterion_id || !$judge_user_id || $new_raw===null || $reason==='') {
            throw new Exception('Missing fields');
        }
        if ($judge_password === '') {
            throw new Exception('Judge password required');
        }
        // Validate judge credentials against the participant's pageant context
        // Always try to derive from participant first to avoid session mismatches
        $pageant_id = null;
        if ($participant_id) {
            $stmtP = $conn->prepare("SELECT pageant_id FROM participants WHERE id=? LIMIT 1");
            $stmtP->bind_param('i', $participant_id);
            $stmtP->execute();
            $rp = $stmtP->get_result();
            if ($rw = $rp->fetch_assoc()) { $pageant_id = (int)$rw['pageant_id']; }
            $stmtP->close();
        }
        if (!$pageant_id) {
            // Fallback to session if participant lookup failed
            $pageant_id = $_SESSION['pageant_id'] ?? ($_SESSION['pageantID'] ?? null);
        }
        if (!$pageant_id) { throw new Exception('Pageant context missing'); }
        // Prefer verifying by selected judge_user_id to avoid username mismatch issues
    $stmtJ = $conn->prepare("SELECT u.id, u.username, u.password_hash FROM users u JOIN pageant_users pu ON pu.user_id=u.id WHERE u.id=? AND u.is_active=1 AND pu.pageant_id=? AND LOWER(TRIM(pu.role))='judge' LIMIT 1");
        $stmtJ->bind_param('ii', $judge_user_id, $pageant_id);
        $stmtJ->execute();
        $resJ = $stmtJ->get_result();
        $judgeRow = $resJ->fetch_assoc();
        $stmtJ->close();
        if (!$judgeRow) { throw new Exception('Invalid judge for this pageant'); }
        if (!password_verify($judge_password, $judgeRow['password_hash'])) {
            throw new Exception('Invalid judge password');
        }
        // If username provided and doesn't match the selected judge, we can still proceed since ID+password are valid
        // Update scores row; create if missing
        $stmt = $conn->prepare("SELECT id FROM scores WHERE participant_id=? AND criterion_id=? AND judge_user_id=? LIMIT 1");
        $stmt->bind_param('iii', $participant_id, $criterion_id, $judge_user_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $score_id = null; if ($row = $r->fetch_assoc()) { $score_id = (int)$row['id']; }
        $stmt->close();
        if ($score_id) {
            $stmtU = $conn->prepare("UPDATE scores SET raw_score=?, override_reason=?, overridden_by_user_id=?, updated_at=NOW() WHERE id=?");
            $adminId = (int)$_SESSION['adminID'];
            $stmtU->bind_param('dsii', $new_raw, $reason, $adminId, $score_id);
            $stmtU->execute();
            $stmtU->close();
        } else {
            // Need round_id to insert minimal row; infer from round_criteria if unique mapping exists
            $stmtR = $conn->prepare("SELECT DISTINCT round_id FROM round_criteria WHERE criterion_id=? LIMIT 1");
            $stmtR->bind_param('i', $criterion_id);
            $stmtR->execute();
            $rr = $stmtR->get_result();
            $round_id = ($rw = $rr->fetch_assoc()) ? (int)$rw['round_id'] : null;
            $stmtR->close();
            if (!$round_id) throw new Exception('Cannot infer round for criterion');
            $stmtI = $conn->prepare("INSERT INTO scores(round_id, criterion_id, participant_id, judge_user_id, raw_score, override_reason, overridden_by_user_id, created_at, updated_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW())");
            $adminId = (int)$_SESSION['adminID'];
            $stmtI->bind_param('iiiidsi', $round_id, $criterion_id, $participant_id, $judge_user_id, $new_raw, $reason, $adminId);
            $stmtI->execute();
            $score_id = $stmtI->insert_id;
            $stmtI->close();
        }
        // Insert audit log (schema: pageant_id, user_id, action_type, entity_type, entity_id, before_json, after_json)
        $after = json_encode([
            'participant_id' => $participant_id,
            'criterion_id' => $criterion_id,
            'judge_user_id' => $judge_user_id,
            'raw_score' => $new_raw,
            'reason' => $reason
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmtAL = $conn->prepare("INSERT INTO audit_logs(pageant_id, user_id, action_type, entity_type, entity_id, before_json, after_json) VALUES(?, ?, 'override_score', 'score', ?, NULL, ?)");
        $adminId = isset($_SESSION['adminID']) ? (int)$_SESSION['adminID'] : null;
        $stmtAL->bind_param('iiis', $pageant_id, $adminId, $score_id, $after);
        $stmtAL->execute();
        $stmtAL->close();
        echo json_encode(['success' => true]);
        exit();
    }
    if ($action === 'save_awards') {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        if (!is_array($body) || !isset($body['divisions'])) {
            throw new Exception('Invalid payload');
        }
        $pageant_id = $_SESSION['pageant_id'] ?? 1;
        // Ensure tables exist
        $con->getPublicAwards($pageant_id);
        $db = $con->opencon();
        $db->begin_transaction();
        try {
            foreach (['Mr','Ms'] as $div) {
                $positions = $body['divisions'][$div] ?? [];
                // Upsert award row for division major title
                $awardName = 'Overall Winner';
                $stmt = $db->prepare("SELECT id FROM awards WHERE pageant_id=? AND name=? AND division_scope=? LIMIT 1");
                $stmt->bind_param('iss', $pageant_id, $awardName, $div);
                $stmt->execute();
                $r = $stmt->get_result();
                $awardId = null;
                if ($row = $r->fetch_assoc()) { $awardId = (int)$row['id']; }
                $stmt->close();
                if (!$awardId) {
                    $stmtI = $db->prepare("INSERT INTO awards(pageant_id, name, division_scope, sequence) VALUES(?,?,?,?)");
                    $seq = ($div === 'Mr') ? 1 : 2;
                    $stmtI->bind_param('issi', $pageant_id, $awardName, $div, $seq);
                    $stmtI->execute();
                    $awardId = $stmtI->insert_id;
                    $stmtI->close();
                }
                // Reset existing winners for positions 1..3 under this division major track
                $db->query("DELETE aw FROM award_winners aw JOIN awards a ON aw.award_id=a.id WHERE a.id={$awardId}");
                // Insert provided positions
                for ($i=0;$i<3;$i++) {
                    $pid = $positions[$i] ?? null;
                    if ($pid) {
                        $stmtW = $db->prepare("INSERT INTO award_winners(award_id, participant_id, position) VALUES(?,?,?)");
                        $pos = $i+1;
                        $stmtW->bind_param('iii', $awardId, $pid, $pos);
                        $stmtW->execute();
                        $stmtW->close();
                    }
                }
            }
            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        exit();
    }
    if ($action === 'auto_generate_awards') {
        $pageant_id = $_SESSION['pageant_id'] ?? 1;
        // Build leaders per division from FINAL rounds
        // Use single connection
        // Get top 3 per division
        $leaders = ['Mr'=>[], 'Ms'=>[]];
        foreach (['Mr','Ms'] as $div) {
            $stmt = $conn->prepare(
                "SELECT p.id, SUM(COALESCE(s.override_score, s.raw_score) * (CASE WHEN rc.weight>1 THEN rc.weight/100.0 ELSE rc.weight END)) as total
                 FROM participants p
                 JOIN divisions d ON p.division_id=d.id
                 JOIN scores s ON s.participant_id=p.id
                 JOIN round_criteria rc ON rc.criterion_id=s.criterion_id
                 JOIN rounds r ON r.id=rc.round_id
                 WHERE r.pageant_id=? AND r.scoring_mode='FINAL' AND r.state IN ('CLOSED','FINALIZED') AND p.is_active=1 AND d.name=?
                 GROUP BY p.id
                 ORDER BY total DESC, p.full_name ASC
                 LIMIT 3"
            );
            $stmt->bind_param('is', $pageant_id, $div);
            $stmt->execute();
            $rs = $stmt->get_result();
            while ($row = $rs->fetch_assoc()) { $leaders[$div][] = (int)$row['id']; }
            $stmt->close();
        }
        // Upsert using existing save_awards transaction path for consistency
        $_POST = [];
        $payload = ['divisions' => $leaders];
        // Reuse code: open new db for transaction
        $db = $con->opencon();
        $db->begin_transaction();
        try {
            foreach (['Mr','Ms'] as $div) {
                $positions = $leaders[$div];
                $awardName = 'Overall Winner';
                $stmt = $db->prepare("SELECT id FROM awards WHERE pageant_id=? AND name=? AND division_scope=? LIMIT 1");
                $stmt->bind_param('iss', $pageant_id, $awardName, $div);
                $stmt->execute();
                $r = $stmt->get_result();
                $awardId = null;
                if ($row = $r->fetch_assoc()) { $awardId = (int)$row['id']; }
                $stmt->close();
                if (!$awardId) {
                    $stmtI = $db->prepare("INSERT INTO awards(pageant_id, name, division_scope, sequence) VALUES(?,?,?,?)");
                    $seq = ($div === 'Mr') ? 1 : 2;
                    $stmtI->bind_param('issi', $pageant_id, $awardName, $div, $seq);
                    $stmtI->execute();
                    $awardId = $stmtI->insert_id;
                    $stmtI->close();
                }
                $db->query("DELETE aw FROM award_winners aw JOIN awards a ON aw.award_id=a.id WHERE a.id={$awardId}");
                for ($i=0;$i<count($positions);$i++) {
                    $pid = $positions[$i];
                    if ($pid) {
                        $stmtW = $db->prepare("INSERT INTO award_winners(award_id, participant_id, position) VALUES(?,?,?)");
                        $pos = $i+1;
                        $stmtW->bind_param('iii', $awardId, $pid, $pos);
                        $stmtW->execute();
                        $stmtW->close();
                    }
                }
            }
            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        exit();
    }
    if ($action === 'toggle_publish_awards') {
        // Flip pageant_settings.reveal_awards value
        $stmt = $conn->prepare("SELECT setting_value FROM pageant_settings WHERE setting_key='reveal_awards' LIMIT 1");
        $stmt->execute();
        $val = 0;
        if ($r = $stmt->get_result()->fetch_assoc()) { $val = (int)$r['setting_value']; }
        $stmt->close();
        $newVal = $val ? 0 : 1;
        // Upsert
        $conn->query("INSERT INTO pageant_settings(setting_key, setting_value) VALUES('reveal_awards', '0') ON DUPLICATE KEY UPDATE setting_value=setting_value");
        $stmtU = $conn->prepare("UPDATE pageant_settings SET setting_value=? WHERE setting_key='reveal_awards'");
        $stmtU->bind_param('s', $sv = (string)$newVal);
        $stmtU->execute();
        $stmtU->close();
        echo json_encode(['success' => true, 'reveal_awards' => $newVal]);
        exit();
    }
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
