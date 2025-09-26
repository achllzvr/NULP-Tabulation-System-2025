		case 'list_ties':
			$user = require_auth(['ADMIN']);
			$roundId = (int)($_GET['round_id'] ?? json_body()['round_id'] ?? 0);
			if ($roundId<=0) respond(['success'=>false,'error'=>'round_id required'],422);
			require_once __DIR__ . '/classes/TieService.php';
			$tieSvc = new TieService();
			$tie = $tieSvc->listBoundaryTie($roundId);
			respond(['success'=>true,'data'=>$tie]);

		case 'resolve_tie':
			$user = require_auth(['ADMIN']);
			require_csrf();
			require_once __DIR__ . '/classes/TieService.php';
			$body = json_body();
			$roundId = (int)($body['round_id'] ?? 0);
			$advanceIds = $body['advance_participant_ids'] ?? [];
			if ($roundId<=0 || !is_array($advanceIds)) respond(['success'=>false,'error'=>'round_id and advance_participant_ids required'],422);
			$tieSvc = new TieService();
			$res = $tieSvc->resolveBoundaryTie($roundId, $advanceIds);
			if (!empty($res['success'])) AuditLogger::log($user['user_id'], 'TIE_RESOLVE', 'round', $roundId, ['chosen'=>$advanceIds]);
			respond(['success'=> (bool)($res['success'] ?? false), 'result'=>$res]);

		case 'public_leaderboard':
			$roundId = (int)($_GET['round_id'] ?? 0);
			if ($roundId<=0) respond(['success'=>false,'error'=>'round_id required'],422);
			// fetch pageant from round
			$pdo = Database::getConnection();
			$stP = $pdo->prepare('SELECT pageant_id FROM rounds WHERE id = ?');
			$stP->execute([$roundId]);
			$rowP = $stP->fetch(PDO::FETCH_ASSOC);
			if(!$rowP) respond(['success'=>false,'error'=>'round not found'],404);
			require_once __DIR__ . '/classes/VisibilityService.php';
			$visSvc = new VisibilityService();
			$flags = $visSvc->getFlags((int)$rowP['pageant_id']);
			$svc = new LeaderboardService();
			$rows = $svc->getRoundStandings($roundId);
			$public = array_map(function($r) use ($flags){
				return [
					'participant_id'=>$flags['reveal_names'] ? $r['participant_id'] : null,
					'name'=>$flags['reveal_names'] ? $r['full_name'] : 'Hidden',
					'division'=>$flags['reveal_names'] ? $r['division'] : null,
					'score'=>$flags['reveal_scores'] ? $r['weighted_total'] : null,
					'rank'=>$r['rank']
				];
			}, $rows);
			respond(['success'=>true,'rows'=>$public,'flags'=>$flags]);
		case 'advancement_preview':
			$user = require_auth(['ADMIN']);
			$roundId = (int)($_GET['round_id'] ?? json_body()['round_id'] ?? 0);
			if ($roundId<=0) respond(['success'=>false,'error'=>'round_id required'],422);
			require_once __DIR__ . '/classes/AdvancementService.php';
			$adv = new AdvancementService();
			$data = $adv->preview($roundId);
			respond(['success'=>true,'data'=>$data]);

		case 'advancement_commit':
			$user = require_auth(['ADMIN']);
			require_csrf();
			$roundId = (int)(json_body()['round_id'] ?? $_POST['round_id'] ?? 0);
			if ($roundId<=0) respond(['success'=>false,'error'=>'round_id required'],422);
			require_once __DIR__ . '/classes/AdvancementService.php';
			$adv = new AdvancementService();
			$count = $adv->commit($roundId, $user['user_id']);
			AuditLogger::log($user['user_id'], 'ADVANCEMENT_COMMIT', 'round', $roundId, ['count'=>$count]);
			respond(['success'=>true,'advanced'=>$count]);
<?php
// Unified API dispatcher
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/ScoreService.php';
require_once __DIR__ . '/classes/AwardsService.php';
require_once __DIR__ . '/classes/PageantService.php';
require_once __DIR__ . '/classes/RoundService.php';
require_once __DIR__ . '/classes/LeaderboardService.php';
require_once __DIR__ . '/classes/database.php';
require_once __DIR__ . '/classes/AuditLogger.php';

AuthService::start();
header('Content-Type: application/json');

// ---------- Helpers ----------
function json_body(): array {
	static $cache = null;
	if ($cache !== null) return $cache;
	$raw = file_get_contents('php://input');
	if ($raw === false || $raw === '') return $cache = [];
	$data = json_decode($raw, true);
	return $cache = (is_array($data) ? $data : []);
}

function respond($data, int $code = 200): void {
	http_response_code($code);
	echo json_encode($data);
	exit;
}

function ensure_method(string $expected): void {
	if (strcasecmp($_SERVER['REQUEST_METHOD'] ?? '', $expected) !== 0) {
		respond(['success'=>false,'error'=>'Method not allowed'], 405);
	}
}

function csrf_token(): string {
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
	}
	return $_SESSION['csrf_token'];
}

function require_csrf(): void {
	$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? (json_body()['csrf_token'] ?? null));
	if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
		respond(['success'=>false,'error'=>'Invalid CSRF token'], 419);
	}
}

function require_auth(array $roles = null): array {
	$user = AuthService::currentUser();
	if (!$user) respond(['success'=>false,'error'=>'Auth required'], 401);
	if ($roles && (!isset($user['role']) || !in_array($user['role'], $roles, true))) {
		respond(['success'=>false,'error'=>'Forbidden'], 403);
	}
	return $user;
}

// Identify action
$action = $_GET['action'] ?? $_POST['action'] ?? json_body()['action'] ?? null;
if (!$action) {
	respond(['success'=>false,'error'=>'Missing action']);
}

// Actions that change state (for CSRF enforcement)
$stateChanging = [
	'login','logout','save_score','open_round','close_round','set_manual_award'
];
if (in_array($action, $stateChanging, true) && $action !== 'login') {
	// Allow login to bootstrap CSRF
	csrf_token(); // ensure token exists
	require_csrf();
}

try {
	switch ($action) {
		case 'ping':
			respond(['success'=>true,'pong'=>true,'csrf'=>csrf_token()]);

		case 'login':
			ensure_method('POST');
			$body = json_body();
			$username = trim($body['username'] ?? ($_POST['username'] ?? ''));
			$password = $body['password'] ?? ($_POST['password'] ?? '');
			if ($username === '' || $password === '') {
				respond(['success'=>false,'error'=>'Username & password required'], 422);
			}
			$pdo = Database::getConnection();
			$stmt = $pdo->prepare("SELECT id, username, role, pageant_id, name, password_hash FROM users WHERE username = ? LIMIT 1");
			$stmt->execute([$username]);
			$u = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$u || !password_verify($password, $u['password_hash'])) {
				respond(['success'=>false,'error'=>'Invalid credentials'], 401);
			}
			AuthService::regenerate();
			$_SESSION['user_id'] = (int)$u['id'];
			$_SESSION['role'] = $u['role'];
			$_SESSION['pageant_id'] = (int)$u['pageant_id'];
			$_SESSION['name'] = $u['name'];
			$pdo->prepare("UPDATE users SET last_login_at = NOW(), force_password_reset = 0 WHERE id = ?")->execute([$u['id']]);
			AuditLogger::log((int)$u['id'], 'LOGIN', 'user', (int)$u['id']);
			respond(['success'=>true,'user'=>[
				'id'=>(int)$u['id'],
				'role'=>$u['role'],
				'pageant_id'=>(int)$u['pageant_id'],
				'name'=>$u['name'],
			,'force_password_reset'=>(bool)$u['force_password_reset']
			],'csrf'=>csrf_token()]);

		case 'logout':
			ensure_method('POST');
			AuthService::start();
			$uid = $_SESSION['user_id'] ?? null;
			session_unset();
			session_destroy();
			if ($uid) AuditLogger::log((int)$uid, 'LOGOUT', 'user', (int)$uid);
			respond(['success'=>true]);

		case 'round_can_open':
			$user = require_auth(['ADMIN']);
			$roundId = (int)($_GET['round_id'] ?? json_body()['round_id'] ?? 0);
			if ($roundId <= 0) respond(['success'=>false,'error'=>'round_id required'],422);
			$svc = new RoundService();
			$result = $svc->canOpenRound($roundId);
			respond(['success'=>true,'data'=>$result]);

		case 'open_round':
			$user = require_auth(['ADMIN']);
			$roundId = (int)(json_body()['round_id'] ?? $_POST['round_id'] ?? 0);
			if ($roundId <= 0) respond(['success'=>false,'error'=>'round_id required'],422);
			$svc = new RoundService();
			$changed = $svc->openRound($roundId, $user['user_id']);
			if ($changed) AuditLogger::log($user['user_id'], 'ROUND_OPEN', 'round', $roundId);
			respond(['success'=>true,'changed'=>$changed]);

		case 'close_round':
			$user = require_auth(['ADMIN']);
			$roundId = (int)(json_body()['round_id'] ?? $_POST['round_id'] ?? 0);
			if ($roundId <= 0) respond(['success'=>false,'error'=>'round_id required'],422);
			$svc = new RoundService();
			$changed = $svc->closeRound($roundId, $user['user_id']);
			if ($changed) AuditLogger::log($user['user_id'], 'ROUND_CLOSE', 'round', $roundId);
			respond(['success'=>true,'changed'=>$changed]);

		case 'save_score':
			$user = require_auth(['JUDGE','ADMIN']);
			ensure_method('POST');
			$body = json_body();
			$roundId = (int)($body['round_id'] ?? 0);
			$participantId = (int)($body['participant_id'] ?? 0);
			$scoresArr = $body['scores'] ?? null; // { criterion_id: value }
			if ($roundId<=0 || $participantId<=0 || !is_array($scoresArr)) {
				respond(['success'=>false,'error'=>'round_id, participant_id, scores map required'],422);
			}
			// Optionally validate round open state
			$pdo = Database::getConnection();
			$st = $pdo->prepare("SELECT state FROM rounds WHERE id=?");
			$st->execute([$roundId]);
			$stRow = $st->fetch(PDO::FETCH_ASSOC);
			if (!$stRow || $stRow['state'] !== 'OPEN') {
				respond(['success'=>false,'error'=>'Round not open'],409);
			}
			$saved = 0; $errors=[];
			foreach ($scoresArr as $criterionId => $val) {
				$cId = (int)$criterionId; $v = (float)$val;
				if ($cId<=0) continue;
				try {
					// Expect raw_score field schema; if only score_value exists adapt here
					$pdo->prepare("INSERT INTO scores (round_id, participant_id, criterion_id, judge_user_id, raw_score, updated_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE raw_score=VALUES(raw_score), updated_at=NOW()")
						->execute([$roundId,$participantId,$cId,$user['user_id'],$v]);
					$saved++;
					AuditLogger::log($user['user_id'], 'SAVE_SCORE', 'score', null, [
						'round_id'=>$roundId,
						'participant_id'=>$participantId,
						'criterion_id'=>$cId,
						'value'=>$v
					]);
				} catch (Exception $e) { $errors[] = ['criterion_id'=>$cId,'error'=>$e->getMessage()]; }
			}
			respond(['success'=>true,'saved'=>$saved,'errors'=>$errors,'csrf'=>csrf_token()]);

		case 'leaderboard':
			$user = require_auth(['ADMIN','JUDGE']); // adjust for public exposure later
			$roundId = (int)($_GET['round_id'] ?? json_body()['round_id'] ?? 0);
			if ($roundId<=0) respond(['success'=>false,'error'=>'round_id required'],422);
			$svc = new LeaderboardService();
			$rows = $svc->getRoundStandings($roundId);
			respond(['success'=>true,'rows'=>$rows]);

		case 'list_awards':
			$user = require_auth(['ADMIN']);
			$pageantId = (int)($_GET['pageant_id'] ?? $user['pageant_id'] ?? 0);
			if ($pageantId<=0) respond(['success'=>false,'error'=>'pageant_id required'],422);
			$awards = AwardsService::getAwards($pageantId);
			respond(['success'=>true,'awards'=>$awards]);

		case 'award_winners':
			$user = require_auth(['ADMIN']);
			$awardId = (int)($_GET['award_id'] ?? 0);
			if ($awardId<=0) respond(['success'=>false,'error'=>'award_id required'],422);
			$winners = AwardsService::getAwardWinners($awardId);
			respond(['success'=>true,'winners'=>$winners]);

		case 'force_password_reset':
			$user = require_auth(['ADMIN']);
			require_csrf();
			$body = json_body();
			$targetId = (int)($body['user_id'] ?? 0);
			if ($targetId<=0) respond(['success'=>false,'error'=>'user_id required'],422);
			$pdo = Database::getConnection();
			$pdo->prepare('UPDATE users SET force_password_reset = 1 WHERE id = ?')->execute([$targetId]);
			AuditLogger::log($user['user_id'], 'FORCE_PASSWORD_RESET', 'user', $targetId);
			respond(['success'=>true]);

		case 'change_password':
			$user = require_auth(['ADMIN','JUDGE']); // user must be logged in
			require_csrf();
			$body = json_body();
			$newPass = $body['new_password'] ?? '';
			if (strlen($newPass) < 8) respond(['success'=>false,'error'=>'Password too short'],422);
			$hash = password_hash($newPass, PASSWORD_DEFAULT);
			$pdo = Database::getConnection();
			$pdo->prepare('UPDATE users SET password_hash=?, force_password_reset=0 WHERE id=?')->execute([$hash, $user['user_id']]);
			AuditLogger::log($user['user_id'], 'CHANGE_PASSWORD', 'user', $user['user_id']);
			respond(['success'=>true]);

		case 'get_visibility_flags':
			$user = require_auth(['ADMIN']);
			require_once __DIR__ . '/classes/VisibilityService.php';
			$svc = new VisibilityService();
			$flags = $svc->getFlags((int)$user['pageant_id']);
			respond(['success'=>true,'flags'=>$flags,'csrf'=>csrf_token()]);

		case 'set_visibility_flags':
			$user = require_auth(['ADMIN']);
			require_csrf();
			$body = json_body();
			require_once __DIR__ . '/classes/VisibilityService.php';
			$svc = new VisibilityService();
			$flags = $svc->setFlags((int)$user['pageant_id'],[
				'reveal_names'=>!empty($body['show_participant_names']),
				'reveal_scores'=>!empty($body['show_scores']),
				'reveal_awards'=>!empty($body['show_awards'])
			]);
			AuditLogger::log($user['user_id'], 'VISIBILITY_SET', 'pageant', (int)$user['pageant_id'], $flags);
			respond(['success'=>true,'flags'=>$flags,'csrf'=>csrf_token()]);

		case 'public_awards':
			// public awards only if reveal_awards true
			$pageantId = (int)($_GET['pageant_id'] ?? 0);
			if ($pageantId<=0) respond(['success'=>false,'error'=>'pageant_id required'],422);
			require_once __DIR__ . '/classes/VisibilityService.php';
			$vis = new VisibilityService();
			$flags = $vis->getFlags($pageantId);
			if (!$flags['reveal_awards']) respond(['success'=>true,'awards'=>[],'flags'=>$flags]);
			$awards = AwardsService::getAwards($pageantId);
			// attach winners
			foreach ($awards as &$a) {
				$a['winners'] = AwardsService::getAwardWinners($a['id']);
			}
			unset($a);
			respond(['success'=>true,'awards'=>$awards,'flags'=>$flags]);

		case 'compute_awards':
			$user = require_auth(['ADMIN']);
			$pageantId = (int)($_GET['pageant_id'] ?? json_body()['pageant_id'] ?? ($user['pageant_id'] ?? 0));
			if ($pageantId<=0) respond(['success'=>false,'error'=>'pageant_id required'],422);
			$results = AwardsService::computeAll($pageantId, false);
			AuditLogger::log($user['user_id'], 'AWARDS_COMPUTE_PREVIEW', 'pageant', $pageantId, ['count'=>count($results)]);
			respond(['success'=>true,'preview'=>$results]);

		case 'compute_awards_persist':
			$user = require_auth(['ADMIN']);
			require_csrf();
			$pageantId = (int)(json_body()['pageant_id'] ?? ($user['pageant_id'] ?? 0));
			if ($pageantId<=0) respond(['success'=>false,'error'=>'pageant_id required'],422);
			$results = AwardsService::computeAll($pageantId, true);
			AuditLogger::log($user['user_id'], 'AWARDS_COMPUTE_PERSIST', 'pageant', $pageantId, ['count'=>count($results)]);
			respond(['success'=>true,'persisted'=>$results]);

		default:
			respond(['success'=>false,'error'=>'Unknown action'],400);
	}
} catch (RuntimeException $re) {
	respond(['success'=>false,'error'=>$re->getMessage()], 422);
} catch (Exception $e) {
	respond(['success'=>false,'error'=>'Server error','detail'=>$e->getMessage()], 500);
}

