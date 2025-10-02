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
            $stmt = $conn->prepare("SELECT DISTINCT rc.criterion_id, rc.weight, rc.max_score, c.name
                                     FROM round_criteria rc JOIN criteria c ON rc.criterion_id=c.id");
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
                                     FROM scores s WHERE s.participant_id = ?");
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
            $weighted = $raw * ($weight / 100.0);
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
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
