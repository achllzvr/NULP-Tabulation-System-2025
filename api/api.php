<?php
/**
 * Pageant Tabulation System API (Scaffold)
 * ---------------------------------------
 * IMPORTANT:
 * - Secure this file (do not expose publicly without authentication hardening).
 * - Replace DB credentials & consider environment variables.
 * - Add rate limiting + stronger CSRF for production.
 */

header('Content-Type: application/json');
session_start();

// ---------------------- CONFIG / DB CONNECTION ----------------------
require_once '../classes/Database.php';

try {
  $db = Database::getInstance();
  $pdo = $db->getConnection();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'DB connection failed']);
  exit;
}

// ---------------------- HELPERS ----------------------
function respond($data, $code = 200) {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

function require_auth() {
  if (empty($_SESSION['user_id'])) {
    respond(['success' => false, 'message' => 'Not authenticated'], 401);
  }
}

function require_role($pageantId, $roles, $pdo) {
  require_auth();
  $roles = (array)$roles;
  $stmt = $pdo->prepare("SELECT role FROM pageant_users WHERE pageant_id=? AND user_id=? LIMIT 1");
  $stmt->execute([$pageantId, $_SESSION['user_id']]);
  $r = $stmt->fetch();
  if (!$r || !in_array($r['role'], $roles, true)) {
    respond(['success'=>false,'message'=>'Forbidden'],403);
  }
}

function random_code($len=8) {
  return strtoupper(substr(bin2hex(random_bytes($len)),0,$len));
}

function hash_password($plain) { return password_hash($plain, PASSWORD_BCRYPT); }

function sanitize_int($v) { return (int)$v; }

// ---------------------- AUTH ENDPOINTS ----------------------
function register_user($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $email = trim($input['email'] ?? '');
  $full_name = trim($input['full_name'] ?? '');
  $password = trim($input['password'] ?? '');
  if ($email==='' || $full_name==='' || $password==='') {
    respond(['success'=>false,'message'=>'Missing fields'],400);
  }
  $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  if ($stmt->fetch()) respond(['success'=>false,'message'=>'Email already used'],409);

  $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, global_role) VALUES (?,?,?,?)");
  $stmt->execute([$email, $full_name, hash_password($password), 'STANDARD']);
  respond(['success'=>true,'user_id'=>$pdo->lastInsertId()]);
}

function login($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $email = trim($input['email'] ?? '');
  $password = trim($input['password'] ?? '');
  if ($email===''||$password==='') respond(['success'=>false,'message'=>'Missing credentials'],400);

  // Support both email and username login
  $stmt = $pdo->prepare("SELECT id, password_hash, full_name, global_role, email, username FROM users WHERE (email=? OR username=?) AND is_active=1 LIMIT 1");
  $stmt->execute([$email, $email]);
  $u = $stmt->fetch();
  if (!$u || !password_verify($password, $u['password_hash'])) {
    respond(['success'=>false,'message'=>'Invalid credentials'],401);
  }
  session_regenerate_id(true);
  $_SESSION['user_id'] = (int)$u['id'];
  $_SESSION['user_email'] = $u['email'];
  $_SESSION['user_username'] = $u['username'];
  $_SESSION['user_name'] = $u['full_name'];
  $_SESSION['user_role'] = $u['global_role'];
  $_SESSION['full_name'] = $u['full_name'];
  $_SESSION['global_role'] = $u['global_role'];
  respond(['success'=>true,'user_id'=>$u['id'],'full_name'=>$u['full_name'],'global_role'=>$u['global_role']]);
}

function logout() {
  $_SESSION = [];
  session_destroy();
  respond(['success'=>true,'message'=>'Logged out']);
}

// ---------------------- PAGEANT SETUP ----------------------
function create_pageant($pdo) {
  require_auth();
  $input = json_decode(file_get_contents('php://input'), true);
  $name = trim($input['name'] ?? '');
  $year = (int)($input['year'] ?? date('Y'));
  if ($name==='') respond(['success'=>false,'message'=>'Name required'],400);

  $code = random_code(6);
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare("INSERT INTO pageants (code,name,year) VALUES (?,?,?)");
    $stmt->execute([$code,$name,$year]);
    $pageantId = $pdo->lastInsertId();

    // Add creator as ADMIN
    $stmt = $pdo->prepare("INSERT INTO pageant_users (pageant_id,user_id,role) VALUES (?,?,?)");
    $stmt->execute([$pageantId,$_SESSION['user_id'],'ADMIN']);

    // Insert default divisions
    $stmt = $pdo->prepare("INSERT INTO divisions (pageant_id,name,sort_order) VALUES (?,?,?)");
    $stmt->execute([$pageantId,'Mr',1]);
    $stmt->execute([$pageantId,'Ms',2]);

    $pdo->commit();
    respond(['success'=>true,'pageant_id'=>$pageantId,'code'=>$code]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Create failed','error'=>$e->getMessage()],500);
  }
}

function join_pageant_as_admin($pdo) {
  require_auth();
  $input = json_decode(file_get_contents('php://input'), true);
  $code = trim($input['code'] ?? '');
  if ($code==='') respond(['success'=>false,'message'=>'Code required'],400);

  $stmt = $pdo->prepare("SELECT id FROM pageants WHERE code=? LIMIT 1");
  $stmt->execute([$code]);
  $p = $stmt->fetch();
  if (!$p) respond(['success'=>false,'message'=>'Not found'],404);

  // If already mapped ignore
  $stmt = $pdo->prepare("SELECT id FROM pageant_users WHERE pageant_id=? AND user_id=? LIMIT 1");
  $stmt->execute([$p['id'], $_SESSION['user_id']]);
  if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO pageant_users (pageant_id,user_id,role) VALUES (?,?,?)");
    $stmt->execute([$p['id'], $_SESSION['user_id'], 'ADMIN']);
  }
  respond(['success'=>true,'pageant_id'=>$p['id']]);
}

function add_participants($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $pageantId = (int)($input['pageant_id'] ?? 0);
  require_role($pageantId,['ADMIN'],$pdo);

  $participants = $input['participants'] ?? [];
  if (!is_array($participants) || empty($participants)) {
    respond(['success'=>false,'message'=>'No participants provided'],400);
  }

  // Get divisions
  $stmt = $pdo->prepare("SELECT id,name FROM divisions WHERE pageant_id=?");
  $stmt->execute([$pageantId]);
  $divMap = [];
  foreach ($stmt as $row) $divMap[strtolower($row['name'])] = $row['id'];

  $ins = $pdo->prepare("INSERT INTO participants (pageant_id,division_id,number_label,full_name,advocacy) VALUES (?,?,?,?,?)");
  $pdo->beginTransaction();
  try {
    foreach ($participants as $p) {
      $divisionKey = strtolower($p['division'] ?? '');
      if (!isset($divMap[$divisionKey])) throw new Exception("Invalid division: ".$p['division']);
      $ins->execute([
        $pageantId,
        $divMap[$divisionKey],
        $p['number_label'],
        $p['full_name'],
        $p['advocacy'] ?? null
      ]);
    }
    $pdo->commit();
    respond(['success'=>true,'count'=>count($participants)]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Insert failed','error'=>$e->getMessage()],500);
  }
}

function add_judges($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $pageantId = (int)($input['pageant_id'] ?? 0);
  require_role($pageantId,['ADMIN'],$pdo);

  $judges = $input['judges'] ?? [];
  if (!is_array($judges) || empty($judges)) {
    respond(['success'=>false,'message'=>'No judges provided'],400);
  }

  $userIns = $pdo->prepare("INSERT INTO users (email,full_name,password_hash,global_role) VALUES (?,?,?,?)");
  $mapIns  = $pdo->prepare("INSERT INTO pageant_users (pageant_id,user_id,role) VALUES (?,?,?)");
  $pdo->beginTransaction();
  $credentials = [];
  try {
    foreach ($judges as $j) {
      $plainPass = $j['password'] ?? substr(random_code(10),0,8);
      $userIns->execute([
        $j['email'] ?? null,
        $j['full_name'],
        hash_password($plainPass),
        'STANDARD'
      ]);
      $uid = $pdo->lastInsertId();
      $mapIns->execute([$pageantId,$uid,'JUDGE']);
      $credentials[] = ['full_name'=>$j['full_name'],'email'=>$j['email'] ?? null,'password'=>$plainPass];
    }
    $pdo->commit();
    respond(['success'=>true,'judges_created'=>count($credentials),'credentials'=>$credentials]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Judge creation failed','error'=>$e->getMessage()],500);
  }
}

function list_rounds($pdo) {
  $pageantId = (int)($_GET['pageant_id'] ?? 0);
  require_role($pageantId,['ADMIN','JUDGE'],$pdo);
  $stmt = $pdo->prepare("SELECT id,name,sequence,state,scoring_mode,advancement_limit FROM rounds WHERE pageant_id=? ORDER BY sequence");
  $stmt->execute([$pageantId]);
  respond(['success'=>true,'rounds'=>$stmt->fetchAll()]);
}

function open_round($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $roundId = (int)($input['round_id'] ?? 0);
  // Fetch pageant for role check
  $stmt = $pdo->prepare("SELECT pageant_id,state FROM rounds WHERE id=?");
  $stmt->execute([$roundId]);
  $r = $stmt->fetch();
  if (!$r) respond(['success'=>false,'message'=>'Round not found'],404);
  require_role($r['pageant_id'],['ADMIN'],$pdo);
  if ($r['state']!=='PENDING' && $r['state']!=='CLOSED') {
    respond(['success'=>false,'message'=>'Invalid state transition'],400);
  }
  $upd = $pdo->prepare("UPDATE rounds SET state='OPEN', opened_at=NOW() WHERE id=?");
  $upd->execute([$roundId]);

  // Log panel state
  $log = $pdo->prepare("INSERT INTO panel_state_logs (pageant_id,round_id,user_id,action) VALUES (?,?,?,?)");
  $log->execute([$r['pageant_id'],$roundId,$_SESSION['user_id'],'OPEN']);

  respond(['success'=>true,'round_id'=>$roundId,'state'=>'OPEN']);
}

function close_round($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $roundId = (int)($input['round_id'] ?? 0);
  $stmt = $pdo->prepare("SELECT pageant_id,state FROM rounds WHERE id=?");
  $stmt->execute([$roundId]);
  $r = $stmt->fetch();
  if (!$r) respond(['success'=>false,'message'=>'Round not found'],404);
  require_role($r['pageant_id'],['ADMIN'],$pdo);
  if ($r['state']!=='OPEN') respond(['success'=>false,'message'=>'Round not open'],400);

  // Force lock judge submissions
  $pdo->beginTransaction();
  try {
    $pdo->prepare("UPDATE rounds SET state='CLOSED', closed_at=NOW() WHERE id=?")->execute([$roundId]);
    $pdo->prepare("UPDATE judge_round_submissions SET status='LOCKED' WHERE round_id=? AND status='SUBMITTED'")->execute([$roundId]);
    $pdo->prepare("INSERT INTO panel_state_logs (pageant_id,round_id,user_id,action) VALUES (?,?,?,?)")
        ->execute([$r['pageant_id'],$roundId,$_SESSION['user_id'],'CLOSE']);
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Close failed','error'=>$e->getMessage()],500);
  }
  respond(['success'=>true,'round_id'=>$roundId,'state'=>'CLOSED']);
}

// ---------------------- JUDGE SCORING ----------------------
function judge_active_round($pdo) {
  require_auth();
  $pageantId = (int)($_GET['pageant_id'] ?? 0);
  require_role($pageantId,['JUDGE','ADMIN'],$pdo);
  $stmt = $pdo->prepare("SELECT id,name,scoring_mode FROM rounds WHERE pageant_id=? AND state='OPEN' ORDER BY sequence LIMIT 1");
  $stmt->execute([$pageantId]);
  $round = $stmt->fetch();
  if (!$round) respond(['success'=>true,'round'=>null]);
  // Get criteria for round
  $crit = $pdo->prepare("
    SELECT rc.id AS round_criterion_id,c.id AS criterion_id,c.name,rc.weight,rc.max_score
    FROM round_criteria rc
    JOIN criteria c ON c.id=rc.criterion_id
    WHERE rc.round_id=?
    ORDER BY rc.display_order, c.sort_order
  ");
  $crit->execute([$round['id']]);
  respond(['success'=>true,'round'=>$round,'criteria'=>$crit->fetchAll()]);
}

function submit_score($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $roundId = (int)($input['round_id'] ?? 0);
  $participantId = (int)($input['participant_id'] ?? 0);
  $criterionId = (int)($input['criterion_id'] ?? 0);
  $score = (float)($input['score'] ?? -1);

  // Validate judge role for the pageant of the round
  $stmt = $pdo->prepare("SELECT r.pageant_id, r.state FROM rounds r WHERE r.id=?");
  $stmt->execute([$roundId]);
  $r = $stmt->fetch();
  if (!$r) respond(['success'=>false,'message'=>'Round not found'],404);
  require_role($r['pageant_id'],['JUDGE'],$pdo);
  if ($r['state']!=='OPEN') respond(['success'=>false,'message'=>'Round not open'],400);

  // Check criterion belongs to round
  $stmt = $pdo->prepare("SELECT max_score FROM round_criteria WHERE round_id=? AND criterion_id=?");
  $stmt->execute([$roundId,$criterionId]);
  $rc = $stmt->fetch();
  if (!$rc) respond(['success'=>false,'message'=>'Criterion not in round'],400);
  if ($score < 0 || $score > (float)$rc['max_score']) {
    respond(['success'=>false,'message'=>'Invalid score'],422);
  }

  // Upsert score
  $pdo->beginTransaction();
  try {
    $ins = $pdo->prepare("
      INSERT INTO scores (round_id,criterion_id,participant_id,judge_user_id,raw_score)
      VALUES (?,?,?,?,?)
      ON DUPLICATE KEY UPDATE raw_score=VALUES(raw_score), updated_at=NOW()
    ");
    $ins->execute([$roundId,$criterionId,$participantId,$_SESSION['user_id'],$score]);

    // Mark submission status IN_PROGRESS if not yet
    $stmt = $pdo->prepare("INSERT INTO judge_round_submissions (round_id,judge_user_id,status) VALUES (?,?,?) 
        ON DUPLICATE KEY UPDATE status=IF(status='NOT_STARTED','IN_PROGRESS',status)");
    $stmt->execute([$roundId,$_SESSION['user_id'],'IN_PROGRESS']);
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Score save failed','error'=>$e->getMessage()],500);
  }
  respond(['success'=>true,'saved'=>true]);
}

function submit_round_final($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $roundId = (int)($input['round_id'] ?? 0);
  $stmt = $pdo->prepare("SELECT pageant_id,state FROM rounds WHERE id=?");
  $stmt->execute([$roundId]);
  $r = $stmt->fetch();
  if (!$r) respond(['success'=>false,'message'=>'Round not found'],404);
  require_role($r['pageant_id'],['JUDGE'],$pdo);
  if ($r['state']!=='OPEN') respond(['success'=>false,'message'=>'Round not open'],400);

  $upd = $pdo->prepare("UPDATE judge_round_submissions SET status='SUBMITTED', submitted_at=NOW() WHERE round_id=? AND judge_user_id=?");
  $upd->execute([$roundId,$_SESSION['user_id']]);
  respond(['success'=>true,'status'=>'SUBMITTED']);
}

// ---------------------- LEADERBOARD & ADVANCEMENT ----------------------
function leaderboard($pdo) {
  $roundId = (int)($_GET['round_id'] ?? 0);
  $stmt = $pdo->prepare("SELECT pageant_id,state FROM rounds WHERE id=?");
  $stmt->execute([$roundId]);
  $r = $stmt->fetch();
  if (!$r) respond(['success'=>false,'message'=>'Round not found'],404);
  require_role($r['pageant_id'],['ADMIN','JUDGE'],$pdo);

  // Aggregate weighted average per participant per division
  $sql = "
    SELECT 
      p.id AS participant_id,
      p.full_name,
      p.number_label,
      d.name AS division,
      ROUND(AVG( (COALESCE(s.override_score,s.raw_score)/rc.max_score)*100 * (rc.weight/100) ),4) AS total_score
    FROM participants p
    JOIN divisions d ON d.id=p.division_id
    LEFT JOIN scores s ON s.participant_id=p.id AND s.round_id=?
    LEFT JOIN round_criteria rc ON rc.round_id=? AND rc.criterion_id=s.criterion_id
    WHERE p.pageant_id = ?
    GROUP BY p.id
    ORDER BY division, total_score DESC
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$roundId,$roundId,$r['pageant_id']]);
  $rows = $stmt->fetchAll();

  respond(['success'=>true,'round_id'=>$roundId,'leaderboard'=>$rows]);
}

function set_advancements_top5($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $fromRound = (int)($input['from_round_id'] ?? 0);
  $toRound   = (int)($input['to_round_id'] ?? 0);

  // Validate roles
  $stmt = $pdo->prepare("SELECT pageant_id,state FROM rounds WHERE id=?");
  $stmt->execute([$fromRound]);
  $fr = $stmt->fetch();
  if (!$fr || $fr['state']!=='CLOSED') respond(['success'=>false,'message'=>'From round not closed'],400);

  $stmt->execute([$toRound]);
  $tr = $stmt->fetch();
  if (!$tr) respond(['success'=>false,'message'=>'To round not found'],404);
  require_role($fr['pageant_id'],['ADMIN'],$pdo);

  // Fetch top 5 per division
  $sql = "
    SELECT division, participant_id,total_score FROM (
      SELECT 
        d.name AS division,
        p.id AS participant_id,
        ROUND(AVG( (COALESCE(s.override_score,s.raw_score)/rc.max_score)*100 * (rc.weight/100) ),4) AS total_score,
        ROW_NUMBER() OVER (PARTITION BY d.id ORDER BY ROUND(AVG( (COALESCE(s.override_score,s.raw_score)/rc.max_score)*100 * (rc.weight/100) ),4) DESC) AS rn
      FROM participants p
      JOIN divisions d ON d.id=p.division_id
      JOIN rounds r ON r.id=?
      LEFT JOIN scores s ON s.participant_id=p.id AND s.round_id=r.id
      LEFT JOIN round_criteria rc ON rc.round_id=r.id AND rc.criterion_id=s.criterion_id
      WHERE p.pageant_id=?
      GROUP BY p.id
    ) ranked
    WHERE rn <= 5
    ORDER BY division,total_score DESC
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$fromRound,$fr['pageant_id']]);
  $rows = $stmt->fetchAll();

  $pdo->beginTransaction();
  try {
    $ins = $pdo->prepare("INSERT INTO advancements (from_round_id,to_round_id,participant_id,rank_at_advancement,is_override) VALUES (?,?,?,?,0)
       ON DUPLICATE KEY UPDATE rank_at_advancement=VALUES(rank_at_advancement)");
    $rankMap = [];
    foreach ($rows as $idx=>$r2) {
      if (!isset($rankMap[$r2['division']])) $rankMap[$r2['division']] = 1;
      $ins->execute([$fromRound,$toRound,$r2['participant_id'],$rankMap[$r2['division']]++]);
    }
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Advancement failed','error'=>$e->getMessage()],500);
  }
  respond(['success'=>true,'advanced'=>$rows]);
}

// ---------------------- MANUAL TIE RESOLUTION ----------------------
function create_tie_group($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $roundId = (int)($input['round_id'] ?? 0);
  $division = trim($input['division'] ?? '');
  $participants = $input['participants'] ?? [];
  $scores = $input['scores'] ?? [];
  if (empty($participants) || empty($scores)) {
    respond(['success'=>false,'message'=>'Participants & scores required'],400);
  }

  // Resolve pageant
  $stmt = $pdo->prepare("SELECT pageant_id FROM rounds WHERE id=?");
  $stmt->execute([$roundId]);
  $r = $stmt->fetch();
  if (!$r) respond(['success'=>false,'message'=>'Round not found'],404);
  require_role($r['pageant_id'],['ADMIN'],$pdo);

  // Division ID
  $stmt = $pdo->prepare("SELECT id FROM divisions WHERE pageant_id=? AND name=?");
  $stmt->execute([$r['pageant_id'],$division]);
  $div = $stmt->fetch();
  $divId = $div ? $div['id'] : null;

  $pdo->beginTransaction();
  try {
    $pdo->prepare("INSERT INTO tie_groups (pageant_id,round_id,division_id) VALUES (?,?,?)")
        ->execute([$r['pageant_id'],$roundId,$divId]);
    $tgId = $pdo->lastInsertId();
    $ins = $pdo->prepare("INSERT INTO tie_group_participants (tie_group_id,participant_id,original_score) VALUES (?,?,?)");
    foreach ($participants as $pId) {
      $orig = isset($scores[$pId]) ? (float)$scores[$pId] : 0.0;
      $ins->execute([$tgId,$pId,$orig]);
    }
    $pdo->commit();
    respond(['success'=>true,'tie_group_id'=>$tgId]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Tie group create failed','error'=>$e->getMessage()],500);
  }
}

function resolve_tie_group($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $tieGroupId = (int)($input['tie_group_id'] ?? 0);
  $ordering = $input['ordering'] ?? []; // array of participant_id in final rank order
  if (empty($ordering)) respond(['success'=>false,'message'=>'Ordering required'],400);

  $stmt = $pdo->prepare("SELECT pageant_id FROM tie_groups WHERE id=?");
  $stmt->execute([$tieGroupId]);
  $tg = $stmt->fetch();
  if (!$tg) respond(['success'=>false,'message'=>'Tie group not found'],404);
  require_role($tg['pageant_id'],['ADMIN'],$pdo);

  $pdo->beginTransaction();
  try {
    $rank = 1;
    $upd = $pdo->prepare("UPDATE tie_group_participants SET manual_rank=? WHERE tie_group_id=? AND participant_id=?");
    foreach ($ordering as $pid) {
      $upd->execute([$rank++,$tieGroupId,$pid]);
    }
    $pdo->prepare("UPDATE tie_groups SET resolved_at=NOW(), resolved_by_user_id=? WHERE id=?")
        ->execute([$_SESSION['user_id'],$tieGroupId]);
    $pdo->commit();
    respond(['success'=>true,'tie_group_id'=>$tieGroupId,'resolved'=>true]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Resolve failed','error'=>$e->getMessage()],500);
  }
}

// ---------------------- SCORE OVERRIDE ----------------------
function override_score($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $scoreId = (int)($input['score_id'] ?? 0);
  $newScore = (float)($input['override_score'] ?? -1);
  $reason = trim($input['reason'] ?? '');
  if ($newScore < 0) respond(['success'=>false,'message'=>'Invalid override score'],400);

  $stmt = $pdo->prepare("SELECT s.round_id, r.pageant_id, rc.max_score 
    FROM scores s
    JOIN rounds r ON r.id=s.round_id
    JOIN round_criteria rc ON rc.round_id=r.id AND rc.criterion_id=s.criterion_id
    WHERE s.id=?");
  $stmt->execute([$scoreId]);
  $row = $stmt->fetch();
  if (!$row) respond(['success'=>false,'message'=>'Score not found'],404);
  require_role($row['pageant_id'],['ADMIN'],$pdo);
  if ($newScore > (float)$row['max_score']) {
    respond(['success'=>false,'message'=>'Override exceeds max'],422);
  }
  $upd = $pdo->prepare("UPDATE scores SET override_score=?, override_reason=?, overridden_by_user_id=? WHERE id=?");
  $upd->execute([$newScore,$reason,$_SESSION['user_id'],$scoreId]);
  respond(['success'=>true,'score_id'=>$scoreId,'override_score'=>$newScore]);
}

// ---------------------- AWARDS & MANUAL ENTRIES ----------------------
function list_awards($pdo) {
  $pageantId = (int)($_GET['pageant_id'] ?? 0);
  require_role($pageantId,['ADMIN','JUDGE'],$pdo);
  $stmt = $pdo->prepare("SELECT id,name,code,aggregation_type,division_scope,visibility_state FROM awards WHERE pageant_id=? ORDER BY name");
  $stmt->execute([$pageantId]);
  respond(['success'=>true,'awards'=>$stmt->fetchAll()]);
}

function set_award_result_manual($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $awardId = (int)($input['award_id'] ?? 0);
  $participantIds = $input['participant_ids'] ?? [];
  if (empty($participantIds)) respond(['success'=>false,'message'=>'participant_ids required'],400);

  // Fetch award
  $stmt = $pdo->prepare("SELECT pageant_id,winners_count FROM awards WHERE id=?");
  $stmt->execute([$awardId]);
  $aw = $stmt->fetch();
  if (!$aw) respond(['success'=>false,'message'=>'Award not found'],404);
  require_role($aw['pageant_id'],['ADMIN'],$pdo);
  if (count($participantIds) > (int)$aw['winners_count']) {
    respond(['success'=>false,'message'=>'Too many winners supplied'],422);
  }

  $pdo->beginTransaction();
  try {
    $del = $pdo->prepare("DELETE FROM award_results WHERE award_id=?");
    $del->execute([$awardId]);
    $ins = $pdo->prepare("INSERT INTO award_results (award_id,participant_id,position,override_flag) VALUES (?,?,?,1)");
    $pos = 1;
    foreach ($participantIds as $pid) {
      $ins->execute([$awardId,$pid,$pos++]);
    }
    $pdo->commit();
    respond(['success'=>true,'award_id'=>$awardId,'winners'=>$participantIds]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    respond(['success'=>false,'message'=>'Manual award set failed','error'=>$e->getMessage()],500);
  }
}

function set_visibility_flags($pdo) {
  $input = json_decode(file_get_contents('php://input'), true);
  $pageantId = (int)($input['pageant_id'] ?? 0);
  require_role($pageantId,['ADMIN'],$pdo);

  $showNames = isset($input['show_participant_names']) ? (int)$input['show_participant_names'] : null;
  $prelimReveal = isset($input['prelim_results_revealed']) ? (int)$input['prelim_results_revealed'] : null;
  $finalReveal = isset($input['final_results_revealed']) ? (int)$input['final_results_revealed'] : null;

  $fields = [];
  $params = [];
  if ($showNames !== null) { $fields[] = "show_participant_names=?"; $params[]=$showNames; }
  if ($prelimReveal !== null) { $fields[] = "prelim_results_revealed=?"; $params[]=$prelimReveal; }
  if ($finalReveal !== null) { $fields[] = "final_results_revealed=?"; $params[]=$finalReveal; }
  if (empty($fields)) respond(['success'=>false,'message'=>'No flags provided'],400);

  $params[] = $pageantId;
  $sql = "UPDATE pageants SET ".implode(',',$fields)." WHERE id=?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  respond(['success'=>true,'updated'=>true]);
}

function public_leaderboard($pdo) {
  $pageantCode = trim($_GET['code'] ?? '');
  if ($pageantCode==='') respond(['success'=>false,'message'=>'Code required'],400);

  $stmt = $pdo->prepare("SELECT id,show_participant_names,prelim_results_revealed,final_results_revealed FROM pageants WHERE code=?");
  $stmt->execute([$pageantCode]);
  $p = $stmt->fetch();
  if (!$p) respond(['success'=>false,'message'=>'Not found'],404);

  // Choose which round to show (final if revealed else prelim)
  $round = null;
  if ($p['final_results_revealed']) {
    $stmt = $pdo->prepare("SELECT id,name FROM rounds WHERE pageant_id=? AND scoring_mode='FINAL' ORDER BY sequence DESC LIMIT 1");
    $stmt->execute([$p['id']]);
    $round = $stmt->fetch();
  } elseif ($p['prelim_results_revealed']) {
    $stmt = $pdo->prepare("SELECT id,name FROM rounds WHERE pageant_id=? AND scoring_mode='PRELIM' ORDER BY sequence ASC LIMIT 1");
    $stmt->execute([$p['id']]);
    $round = $stmt->fetch();
  }

  if (!$round) {
    respond(['success'=>true,'leaderboard'=>[],'message'=>'No revealed results yet']);
  }

  $sql = "
    SELECT 
      p.id AS participant_id,
      CASE WHEN ?=1 THEN p.full_name ELSE CONCAT('Contestant ', p.number_label) END AS display_name,
      d.name AS division,
      ROUND(AVG( (COALESCE(s.override_score,s.raw_score)/rc.max_score)*100 * (rc.weight/100) ),4) AS total_score
    FROM participants p
    JOIN divisions d ON d.id=p.division_id
    LEFT JOIN scores s ON s.participant_id=p.id AND s.round_id=?
    LEFT JOIN round_criteria rc ON rc.round_id=? AND rc.criterion_id=s.criterion_id
    WHERE p.pageant_id=?
    GROUP BY p.id
    ORDER BY division,total_score DESC
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$p['show_participant_names'],$round['id'],$round['id'],$p['id']]);
  $rows = $stmt->fetchAll();
  respond(['success'=>true,'round'=>$round,'leaderboard'=>$rows]);
}

// ---------------------- ROUTER ----------------------
$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
  // Auth
  case 'register_user': register_user($pdo); break;
  case 'login': login($pdo); break;
  case 'logout': logout(); break;

  // Pageant Setup
  case 'create_pageant': create_pageant($pdo); break;
  case 'join_pageant_as_admin': join_pageant_as_admin($pdo); break;
  case 'add_participants': add_participants($pdo); break;
  case 'add_judges': add_judges($pdo); break;
  case 'list_rounds': list_rounds($pdo); break;
  case 'open_round': open_round($pdo); break;
  case 'close_round': close_round($pdo); break;

  // Judge
  case 'judge_active_round': judge_active_round($pdo); break;
  case 'submit_score': submit_score($pdo); break;
  case 'submit_round_final': submit_round_final($pdo); break;

  // Leaderboard & Advancement
  case 'leaderboard': leaderboard($pdo); break;
  case 'set_advancements_top5': set_advancements_top5($pdo); break;

  // Ties
  case 'create_tie_group': create_tie_group($pdo); break;
  case 'resolve_tie_group': resolve_tie_group($pdo); break;

  // Overrides & Awards
  case 'override_score': override_score($pdo); break;
  case 'list_awards': list_awards($pdo); break;
  case 'set_award_result_manual': set_award_result_manual($pdo); break;

  // Visibility & Public
  case 'set_visibility_flags': set_visibility_flags($pdo); break;
  case 'public_leaderboard': public_leaderboard($pdo); break;

  default:
    respond(['success'=>false,'message'=>'Invalid or missing action'],400);
}