<?php
// Temporary new unified API (will replace existing api.php after verification)
require __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
try {
    switch ($action) {
        case 'open_round':
            auth_require_login(); $rid=(int)($_POST['round_id']??0); $ok=rounds_open($rid); echo json_encode(['success'=>$ok]); break;
        case 'close_round':
            auth_require_login(); $rid=(int)($_POST['round_id']??0); $ok=rounds_close($rid); echo json_encode(['success'=>$ok]); break;
        case 'submit_score':
            auth_require_login(); $roundId=(int)($_POST['round_id']??0); $criterionId=(int)($_POST['criterion_id']??0); $participantId=(int)($_POST['participant_id']??0); $value=(float)($_POST['value']??0); $u=auth_user(); $ok=scores_save($roundId,$criterionId,$participantId,$u['id'],$value); echo json_encode(['success'=>$ok]); break;
        case 'leaderboard':
            $roundId=(int)($_GET['round_id']??0); $rows=scores_aggregate_round($roundId); echo json_encode(['success'=>true,'data'=>$rows]); break;
        default: echo json_encode(['success'=>false,'error'=>'Unknown action']);
    }
} catch (Throwable $e) { http_response_code(500); echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
