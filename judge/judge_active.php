<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if judge is logged in
if (!isset($_SESSION['judgeID'])) {
    $currentPage = urlencode('judge/' . basename($_SERVER['PHP_SELF']));
    header("Location: ../login_judge.php?redirect=" . $currentPage);
    exit();
}

// Include the database class file
require_once('../classes/database.php');

// Create an instance of the database class
$con = new database();

// Fetch judge panel visibility settings
$settings = [];

// Ensure $pageant_id is set for advancements validation logic
$pageant_id = $_SESSION['pageantID'] ?? 1;
$judge_id = $_SESSION['judgeID'];

// --- Advancements Validation Panel Logic (Judge) ---
function getActiveAdvancementVerification($con, $pageant_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT * FROM advancement_verification WHERE pageant_id = ? AND is_active = 1 LIMIT 1");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();
  $conn->close();
  return $row;
}

function getJudgeAdvancementConfirmation($con, $verification_id, $judge_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT * FROM advancement_verification_judges WHERE advancement_verification_id = ? AND judge_user_id = ? LIMIT 1");
  $stmt->bind_param("ii", $verification_id, $judge_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();
  $conn->close();
  return $row;
}

function getAdvancingParticipants($con, $pageant_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT a.participant_id, p.full_name, p.number_label, d.name as division FROM advancements a JOIN participants p ON a.participant_id = p.id JOIN divisions d ON p.division_id = d.id JOIN rounds r ON a.to_round_id = r.id WHERE r.pageant_id = ?");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $rows = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $conn->close();
  return $rows;
}

function getParticipantScoresByJudge($con, $participant_id, $judge_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT s.round_id, r.name as round_name, c.name as criterion_name, s.raw_score FROM scores s JOIN criteria c ON s.criterion_id = c.id JOIN rounds r ON s.round_id = r.id WHERE s.participant_id = ? AND s.judge_user_id = ? ORDER BY s.round_id, c.id");
  $stmt->bind_param("ii", $participant_id, $judge_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $rows = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $conn->close();
  return $rows;
}

// Handle judge confirmation action
if (isset($_POST['confirm_advancements'])) {
  $pageant_id = $_SESSION['pageantID'];
  $judge_id = $_SESSION['judgeID'];
  $verification_id = intval($_POST['verification_id']);
  $conn = $con->opencon();
  $stmt = $conn->prepare("UPDATE advancement_verification_judges SET confirmed = 1, confirmed_at = NOW() WHERE advancement_verification_id = ? AND judge_user_id = ?");
  $stmt->bind_param("ii", $verification_id, $judge_id);
  $stmt->execute();
  $stmt->close();
  $conn->close();
  $success_message = "Thank you for confirming your advancements. Please wait for the admin to close validation.";
}

$active_verification = getActiveAdvancementVerification($con, $pageant_id);
$judge_confirmation = $active_verification ? getJudgeAdvancementConfirmation($con, $active_verification['id'], $judge_id) : null;
$advancing_participants = $active_verification ? getAdvancingParticipants($con, $pageant_id) : [];
try {
  $conn_settings = $con->opencon();
  $result = $conn_settings->query("SELECT setting_key, setting_value FROM pageant_settings");
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $settings[$row['setting_key']] = $row['setting_value'];
    }
  }
  $conn_settings->close();
} catch (Exception $e) {
  // Ignore if settings table does not exist
}

// Handle score submissions
if (isset($_POST['submit_scores'])) {
    $participant_id = $_POST['participant_id'];
    $judge_id = $_SESSION['judgeID'];
    $pageant_id = $_SESSION['pageantID'];
    
    $conn = $con->opencon();
    $success_count = 0;
    $error_count = 0;
    
  // Try to get the round_id from an in-progress tie_group first
  $stmt = $conn->prepare("SELECT round_id FROM tie_groups WHERE pageant_id = ? AND state = 'in_progress' ORDER BY created_at DESC LIMIT 1");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $tg_result = $stmt->get_result();
  $tg_row = $tg_result->fetch_assoc();
  $stmt->close();

  if ($tg_row) {
    $round_id = $tg_row['round_id'];
  } else {
    // Fallback to OPEN round
    $stmt = $conn->prepare("SELECT id FROM rounds WHERE pageant_id = ? AND state = 'OPEN' LIMIT 1");
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $round = $result->fetch_assoc();
    $stmt->close();
    $round_id = $round ? $round['id'] : null;
  }

  if ($round_id) {
    // Process each criterion score
    foreach ($_POST as $key => $value) {
      if (strpos($key, 'criterion_') === 0) {
        $criterion_id = intval(str_replace('criterion_', '', $key));
        $score = floatval($value);
        if ($score >= 0) { // Only save non-negative scores
          // Insert or update score
          $stmt = $conn->prepare("INSERT INTO scores (round_id, criterion_id, participant_id, judge_user_id, raw_score) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE raw_score = VALUES(raw_score), updated_at = CURRENT_TIMESTAMP");
          $stmt->bind_param("iiiid", $round_id, $criterion_id, $participant_id, $judge_id, $score);
          if ($stmt->execute()) {
            $success_count++;
          } else {
            $error_count++;
          }
          $stmt->close();
        }
      }
    }
    if ($success_count > 0) {
      $success_message = "Saved $success_count score(s) successfully.";
    }
    if ($error_count > 0) {
      $error_message = "Failed to save $error_count score(s).";
    }
  } else {
    $error_message = "No active round found.";
  }
    
    $conn->close();
}

// Handle duo score submissions
if (isset($_POST['submit_scores_duo'])) {
    $duo_id = intval($_POST['duo_id'] ?? 0);
    $judge_id = $_SESSION['judgeID'];
    $pageant_id = $_SESSION['pageantID'];
    if ($duo_id <= 0) {
      $error_message = "Invalid duo.";
    } else {
      $conn = $con->opencon();
      // Determine round context (tie-breaker overrides to a round if present)
      $stmt = $conn->prepare("SELECT round_id FROM tie_groups WHERE pageant_id = ? AND state = 'in_progress' ORDER BY created_at DESC LIMIT 1");
      $stmt->bind_param("i", $pageant_id);
      $stmt->execute();
      $tg_result = $stmt->get_result();
      $tg_row = $tg_result->fetch_assoc();
      $stmt->close();
      if ($tg_row) {
        $round_id = $tg_row['round_id'];
      } else {
        $stmt = $conn->prepare("SELECT id FROM rounds WHERE pageant_id = ? AND state = 'OPEN' LIMIT 1");
        $stmt->bind_param("i", $pageant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();
        $round_id = $round ? $round['id'] : null;
      }
      if (!$round_id) {
        $error_message = "No active round found.";
      } else {
        // Ensure pair scoring is only allowed for Advocacy or Talent rounds
        $stmt = $conn->prepare("SELECT name FROM rounds WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $rinfo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $rname = trim((string)($rinfo['name'] ?? ''));
        $allowedByName = (stripos($rname, 'advocacy') !== false) || (stripos($rname, 'talent') !== false);
        if (empty($rinfo) || !$allowedByName) {
          $error_message = "Duo scoring is only available for Advocacy or Talent rounds.";
        } else {
        $success_count = 0; $error_count = 0;
        // Save duo scores
        foreach ($_POST as $key => $value) {
          if (strpos($key, 'criterion_') === 0) {
            $criterion_id = intval(str_replace('criterion_', '', $key));
            $score = floatval($value);
            if ($score >= 0) {
              $stmt = $conn->prepare("INSERT INTO scores_duo (round_id, criterion_id, duo_id, judge_user_id, raw_score) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE raw_score = VALUES(raw_score), updated_at = CURRENT_TIMESTAMP");
              $stmt->bind_param("iiiid", $round_id, $criterion_id, $duo_id, $judge_id, $score);
              if ($stmt->execute()) { $success_count++; } else { $error_count++; }
              $stmt->close();
            }
          }
        }
        // Mirror to individual participants of the duo
        $member_ids = [];
        $stmt = $conn->prepare("SELECT participant_id FROM duo_members WHERE duo_id=?");
        $stmt->bind_param("i", $duo_id);
        $stmt->execute();
        $resM = $stmt->get_result();
        while ($row = $resM->fetch_assoc()) { $member_ids[] = (int)$row['participant_id']; }
        $stmt->close();
        if (!empty($member_ids)) {
          foreach ($_POST as $key => $value) {
            if (strpos($key, 'criterion_') === 0) {
              $criterion_id = intval(str_replace('criterion_', '', $key));
              $score = floatval($value);
              if ($score >= 0) {
                foreach ($member_ids as $pid) {
                  $stmt = $conn->prepare("INSERT INTO scores (round_id, criterion_id, participant_id, judge_user_id, raw_score) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE raw_score = VALUES(raw_score), updated_at = CURRENT_TIMESTAMP");
                  $stmt->bind_param("iiiid", $round_id, $criterion_id, $pid, $judge_id, $score);
                  $stmt->execute();
                  $stmt->close();
                }
              }
            }
          }
        }
        if ($success_count > 0) { $success_message = "Saved $success_count duo score(s) successfully."; }
        if ($error_count > 0) { $error_message = "Failed to save $error_count duo score(s)."; }
        }
      }
      $conn->close();
    }
}

// Fetch data for judge interface (tie-breaker aware, duo-aware)
$conn = $con->opencon();
$pageant_id = $_SESSION['pageantID'] ?? 1;
$judge_id = $_SESSION['judgeID'];

// Common defaults used by the view
$participants = [];
$criteria = [];
$existingScores = [];
$current_participant = null;
$is_pair_scoring = false;
$duos = [];
$current_duo = null;

// Check for active tie breaker group
$stmt = $conn->prepare("SELECT * FROM tie_groups WHERE pageant_id = ? AND state = 'in_progress' ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$tie_group = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($tie_group) {
    // Use the round associated with the tie group to derive criteria and context
    $round_id = (int)$tie_group['round_id'];
    $stmt = $conn->prepare("SELECT * FROM rounds WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $round_id);
    $stmt->execute();
    $active_round = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Participants limited to those in the tie group
    $participant_ids = [];
    $stmt = $conn->prepare("SELECT participant_id FROM tie_group_participants WHERE tie_group_id = ?");
    $stmt->bind_param("i", $tie_group['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $participant_ids[] = (int)$row['participant_id']; }
    $stmt->close();

    if (!empty($participant_ids)) {
        $placeholders = implode(',', array_fill(0, count($participant_ids), '?'));
        $types = str_repeat('i', count($participant_ids));
        $sql = "SELECT p.*, d.name as division FROM participants p JOIN divisions d ON p.division_id = d.id WHERE p.id IN ($placeholders) AND p.is_active = 1 ORDER BY p.number_label";
        $stmt = $conn->prepare($sql);
        // Spread params dynamically
        $stmt->bind_param($types, ...$participant_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        $participants = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Criteria for this round
    if (!empty($active_round)) {
        $stmt = $conn->prepare("SELECT c.*, rc.weight, rc.max_score FROM criteria c JOIN round_criteria rc ON c.id = rc.criterion_id WHERE rc.round_id = ? AND c.is_active = 1 ORDER BY rc.display_order");
        $stmt->bind_param("i", $active_round['id']);
        $stmt->execute();
        $criteria = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Current selected participant in tie-breaker mode
        $participant_index = intval($_GET['participant'] ?? 0);
        if (isset($participants[$participant_index])) {
            $current_participant = $participants[$participant_index];
            $stmt = $conn->prepare("SELECT criterion_id, raw_score FROM scores WHERE round_id = ? AND participant_id = ? AND judge_user_id = ?");
            $stmt->bind_param("iii", $active_round['id'], $current_participant['id'], $judge_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) { $existingScores[$row['criterion_id']] = ['score_value' => $row['raw_score']]; }
            $stmt->close();
        }
    }

    // Enrich $active_round for the tie-breaker timer UI
    if (!empty($active_round)) {
        $active_round['name'] = 'Tie Breaker: ' . $active_round['name'];
    } else {
        $active_round = ['id' => $round_id, 'name' => 'Tie Breaker Round', 'pair_scoring' => 0];
    }
    $active_round['start_time'] = $tie_group['start_time'] ?? null;
    $active_round['tie_group_id'] = $tie_group['id'];
    $active_round['state'] = $tie_group['state'];
} else {
    // Normal active round
    $stmt = $conn->prepare("SELECT * FROM rounds WHERE pageant_id = ? AND state = 'OPEN' ORDER BY sequence LIMIT 1");
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $active_round = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($active_round) {
        // Gate by assignments if exist
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM round_judges WHERE round_id=?");
        $stmt->bind_param("i", $active_round['id']);
        $stmt->execute();
        $hasAssignments = (int)$stmt->get_result()->fetch_assoc()['cnt'] > 0;
        $stmt->close();
        if ($hasAssignments) {
            $stmt = $conn->prepare("SELECT 1 FROM round_judges WHERE round_id=? AND judge_user_id=? LIMIT 1");
            $stmt->bind_param("ii", $active_round['id'], $judge_id);
            $stmt->execute();
            $allowed = (bool)$stmt->get_result()->fetch_row();
            $stmt->close();
            if (!$allowed) {
                $error_message = "You are not assigned to this round. Please wait for the admin to assign you.";
                // Do not proceed with loading data
                $conn->close();
                goto RENDER_VIEW;
            }
        }

    // Enforce pair scoring based on round name (Advocacy/Talent) regardless of DB flag
    $rname = trim((string)$active_round['name']);
    $is_pair_scoring = (stripos($rname, 'advocacy') !== false) || (stripos($rname, 'talent') !== false);

        // Load criteria once
        $stmt = $conn->prepare("SELECT c.*, rc.weight, rc.max_score FROM criteria c JOIN round_criteria rc ON c.id = rc.criterion_id WHERE rc.round_id = ? AND c.is_active = 1 ORDER BY rc.display_order");
        $stmt->bind_param("i", $active_round['id']);
        $stmt->execute();
        $criteria = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if ($is_pair_scoring) {
            // Duo mode
            $stmt = $conn->prepare("SELECT d.* FROM duos d WHERE d.pageant_id=? ORDER BY d.id");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $resD = $stmt->get_result();
            while ($d = $resD->fetch_assoc()) { $duos[] = $d; }
            $stmt->close();

            $duo_index = intval($_GET['duo'] ?? 0);
            if (isset($duos[$duo_index])) {
                $current_duo = $duos[$duo_index];
                $stmt = $conn->prepare("SELECT criterion_id, raw_score FROM scores_duo WHERE round_id = ? AND duo_id = ? AND judge_user_id = ?");
                $stmt->bind_param("iii", $active_round['id'], $current_duo['id'], $judge_id);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) { $existingScores[$row['criterion_id']] = ['score_value' => $row['raw_score']]; }
                $stmt->close();
            }
        } else {
            // Individual mode
            $stmt = $conn->prepare("SELECT p.*, d.name as division FROM participants p JOIN divisions d ON p.division_id = d.id WHERE p.pageant_id = ? AND p.is_active = 1 ORDER BY p.number_label");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $participants = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $participant_index = intval($_GET['participant'] ?? 0);
            if (isset($participants[$participant_index])) {
                $current_participant = $participants[$participant_index];
                $stmt = $conn->prepare("SELECT criterion_id, raw_score FROM scores WHERE round_id = ? AND participant_id = ? AND judge_user_id = ?");
                $stmt->bind_param("iii", $active_round['id'], $current_participant['id'], $judge_id);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) { $existingScores[$row['criterion_id']] = ['score_value' => $row['raw_score']]; }
                $stmt->close();
            }
        }
    }
}

$conn->close();

RENDER_VIEW:

$pageTitle = 'Judge Active Round';
include __DIR__ . '/../partials/head.php';
?>

<!-- Judge Navigation -->
<nav class="bg-white bg-opacity-10 backdrop-blur-sm border-b border-white border-opacity-20">
  <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 flex items-center justify-between h-14">
    <div class="flex items-center gap-6">
      <div class="font-semibold text-white">Judge Panel</div>
      <ul class="flex gap-4 text-sm">
        <li>
          <a href="judge_active.php" class="px-2 py-1 rounded bg-blue-600 bg-opacity-80 text-white">Active Round</a>
        </li>
      </ul>
    </div>
    <div class="flex items-center gap-4">
      <button type="button" class="text-sm px-3 py-1 rounded bg-blue-500 bg-opacity-70 hover:bg-blue-600 text-white transition-colors" onclick="window.location.reload()">
        <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Refresh
      </button>
      <span class="text-sm text-slate-200">Welcome, <?= htmlspecialchars($_SESSION['judgeFN'] ?? 'Judge', ENT_QUOTES, 'UTF-8') ?></span>
      <form method="post" action="../logout.php" class="inline" id="logoutForm">
        <button type="button" class="text-sm text-slate-200 hover:text-white transition-colors" onclick="confirmLogout()">Logout</button>
      </form>
    </div>
  </div>
</nav>

<main class="mx-auto max-w-4xl w-full p-6 space-y-6">
  <?php if ($active_verification): ?>
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6 mb-8">
      <h2 class="text-lg font-semibold text-white mb-2">Advancements Validation</h2>
      <?php if ($judge_confirmation && $judge_confirmation['confirmed']): ?>
        <div class="text-green-300 text-base font-medium mb-2">You have confirmed your advancements. Please wait for the admin to close validation.</div>
      <?php else: ?>
        <form method="POST" onsubmit="return confirm('By pressing this button, you confirm and sign that the scores you’ve placed are correct and participants can proceed to advancements. Continue?');">
          <input type="hidden" name="verification_id" value="<?= htmlspecialchars($active_verification['id']) ?>">
          <div class="mb-4">
            <h3 class="text-base font-medium text-white mb-2">Your Advancing Participants</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm text-white border border-white border-opacity-20 rounded-lg">
                <thead>
                  <tr class="bg-white bg-opacity-10">
                    <th class="px-3 py-2">#</th>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Division</th>
                    <th class="px-3 py-2">Scores (per round/criteria)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($advancing_participants as $ap): ?>
                    <tr class="border-b border-white border-opacity-10">
                      <td class="px-3 py-2"><?= htmlspecialchars($ap['number_label']) ?></td>
                      <td class="px-3 py-2"><?= htmlspecialchars($ap['full_name']) ?></td>
                      <td class="px-3 py-2"><?= htmlspecialchars($ap['division']) ?></td>
                      <td class="px-3 py-2">
                        <?php $scores = getParticipantScoresByJudge($con, $ap['participant_id'], $judge_id); ?>
                        <?php if ($scores): ?>
                          <ul class="list-disc list-inside">
                            <?php foreach ($scores as $s): ?>
                              <li><?= htmlspecialchars($s['round_name']) ?> - <?= htmlspecialchars($s['criterion_name']) ?>: <span class="font-mono text-blue-200"><?= htmlspecialchars($s['raw_score']) ?></span></li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <span class="text-yellow-200">No scores found</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <button type="submit" name="confirm_advancements" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition-colors">
            Confirm My Advancements
          </button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="text-center mb-6">
    <h1 class="text-2xl font-bold text-white">Judge Scoring Panel</h1>
  </div>

<script>
function confirmLogout() {
  if (typeof showConfirm === 'function') {
    showConfirm('Confirm Logout', 'Are you sure you want to logout?', 'Yes, Logout', 'Cancel')
    .then((result) => {
      if (result.isConfirmed) {
        document.getElementById('logoutForm').submit();
      }
    });
  } else {
    // Fallback to native confirm if SweetAlert2 isn't loaded yet
    if (confirm('Are you sure you want to logout?')) {
      document.getElementById('logoutForm').submit();
    }
  }
}
</script>

  <?php if (isset($success_message)): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded text-sm">
      <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (isset($error_message)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
      <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (!$active_round): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
      <svg class="mx-auto h-12 w-12 text-yellow-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
      </svg>
      <h3 class="text-lg font-medium text-yellow-800 mb-2">No Active Round</h3>
      <p class="text-yellow-700">There are currently no rounds open for judging. Please wait for the administrator to open a round.</p>
    </div>
  <?php elseif (empty($participants)): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
      <svg class="mx-auto h-12 w-12 text-blue-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      <h3 class="text-lg font-medium text-blue-800 mb-2">No Participants</h3>
      <p class="text-blue-700">No participants are registered for this pageant yet.</p>
    </div>
  <?php elseif (empty($criteria)): ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
      <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <h3 class="text-lg font-medium text-red-800 mb-2">No Scoring Criteria</h3>
      <p class="text-red-700">This round has no scoring criteria assigned. Please contact the administrator.</p>
    </div>
  <?php else: ?>
    <!-- Round Information -->
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6 mb-6">
      <h2 class="text-lg font-semibold text-white mb-2"><?= htmlspecialchars($active_round['name'], ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="text-slate-200 text-sm mb-4">Currently judging: <?= count($participants) ?> participants • <?= count($criteria) ?> criteria</p>
      <?php if (!empty($active_round['start_time']) && $active_round['state'] === 'in_progress'): ?>
        <div class="mt-4">
          <div class="text-sm text-slate-200 mb-1">Tie Breaker Timer</div>
          <div class="w-full flex items-center gap-3">
            <div id="tie-timer-<?= $active_round['tie_group_id'] ?>" class="text-2xl font-mono font-bold text-blue-200 bg-blue-900 bg-opacity-30 px-6 py-2 rounded-lg shadow-inner border border-blue-400 border-opacity-30"></div>
            <span class="text-slate-300">(2 minutes)</span>
          </div>
        </div>
        <script>
        function startTieTimer_<?= $active_round['tie_group_id'] ?>(startTime) {
          const timerEl = document.getElementById('tie-timer-<?= $active_round['tie_group_id'] ?>');
          const duration = 1 * 15; // 2 minutes in seconds
          let timerEnded = false;
          function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const start = Math.floor(new Date(startTime).getTime() / 1000);
            let elapsed = now - start;
            let remaining = duration - elapsed;
            if (remaining < 0) remaining = 0;
            const min = Math.floor(remaining / 60).toString().padStart(2, '0');
            const sec = (remaining % 60).toString().padStart(2, '0');
            timerEl.textContent = `${min}:${sec}`;
            if (remaining > 0) {
              setTimeout(updateTimer, 1000);
            } else {
              timerEl.textContent = '00:00';
              if (!timerEnded) {
                timerEnded = true;
                // Auto-save if form is present and not already saved
                if (window.judgeAutoSaveOnTimerEnd) {
                  window.judgeAutoSaveOnTimerEnd();
                }
              }
            }
          }
          updateTimer();
        }
        document.addEventListener('DOMContentLoaded', function() {
          startTieTimer_<?= $active_round['tie_group_id'] ?>(<?= json_encode($active_round['start_time']) ?>);
        });
        </script>
      <?php endif; ?>

    <!-- Participant Navigation -->
    <div class="bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl p-4 mb-6 backdrop-blur-md">
      <h3 class="text-lg font-semibold text-white mb-4"><?= $is_pair_scoring ? 'Select Duo to Score' : 'Select Participant to Score' ?></h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <?php if ($is_pair_scoring): ?>
          <?php foreach ($duos as $index => $duo):
            $isSelected = ($current_duo && $current_duo['id'] == $duo['id']);
            $baseClass = 'block text-center p-3 rounded-lg border transition-colors font-semibold shadow-sm';
            $selectedClass = 'bg-blue-500 bg-opacity-20 text-white border-2 border-yellow-400 drop-shadow-lg';
            $unselectedClass = 'bg-white bg-opacity-10 text-slate-200 border-white border-opacity-10 hover:bg-white hover:bg-opacity-20';
          ?>
            <a href="?duo=<?= $index ?>" class="<?= $baseClass . ' ' . ($isSelected ? $selectedClass : $unselectedClass) ?>">
              <div class="font-semibold"><?= htmlspecialchars($duo['name'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="text-xs mt-1 text-slate-200">Duo</div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
        <?php foreach ($participants as $index => $participant):
          $isSelected = ($current_participant && $current_participant['id'] == $participant['id']);
          $baseClass = 'block text-center p-3 rounded-lg border transition-colors font-semibold shadow-sm';
          $selectedClass = 'bg-blue-500 bg-opacity-20 text-white border-2 border-yellow-400 drop-shadow-lg';
          $unselectedClass = 'bg-white bg-opacity-10 text-slate-200 border-white border-opacity-10 hover:bg-white hover:bg-opacity-20';
        ?>
          <a href="?participant=<?= $index ?>"
             class="<?= $baseClass . ' ' . ($isSelected ? $selectedClass : $unselectedClass) ?> group"
             style="<?= $isSelected ? 'box-shadow: 0 0 0 4px rgba(255,215,0,0.25), 0 4px 24px 0 rgba(0,0,0,0.10); min-width: 8rem;' : 'min-width: 8rem;' ?>">
            <?php if (!empty($settings['judge_reveal_photos']) && $settings['judge_reveal_photos']): ?>
              <div class="relative flex justify-center">
                <div class="mx-auto mb-2 w-36 h-36 bg-slate-300 rounded-md flex items-center justify-center text-slate-500 text-base transition-transform duration-200 group-hover:scale-105" style="object-fit:cover;">Photo</div>
                <div class="absolute left-1/2 top-1/2 z-50 pointer-events-none opacity-0 scale-95 group-hover:opacity-100 group-hover:scale-100 transition-all duration-300 ease-in-out -translate-x-1/2 -translate-y-1/2">
                  <div class="w-72 h-72 bg-slate-300 rounded-md flex items-center justify-center text-slate-500 text-lg shadow-2xl border-4 border-yellow-300" style="object-fit:cover;">Photo Zoom</div>
                </div>
              </div>
            <?php endif; ?>
            <div class="font-semibold">#<?= htmlspecialchars($participant['number_label'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-xs mt-1 text-slate-200">
              <?php if (!empty($settings['judge_reveal_names']) && $settings['judge_reveal_names']): ?>
                <?= htmlspecialchars($participant['full_name'], ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
              <?php if (!empty($settings['judge_reveal_names']) && $settings['judge_reveal_names']): ?>
                &nbsp;|&nbsp;
              <?php endif; ?>
              <?= htmlspecialchars($participant['division'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($is_pair_scoring && $current_duo): ?>
      <div class="bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl p-6 backdrop-blur-md">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-white">Scoring: Duo <?= htmlspecialchars($current_duo['name'], ENT_QUOTES, 'UTF-8') ?></h3>
          </div>
          <div class="text-sm text-slate-300">Duo <?= $duo_index + 1 ?> of <?= count($duos) ?></div>
        </div>
        <?php $duo = $current_duo; include __DIR__ . '/../components/score_form_duo.php'; ?>
      </div>
    <?php elseif ($current_participant): ?>
      <!-- Scoring Form -->
      <div class="bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl p-6 backdrop-blur-md">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-white">Scoring: Participant #<?= htmlspecialchars($current_participant['number_label'], ENT_QUOTES, 'UTF-8') ?></h3>
          </div>
          <div class="text-sm text-slate-300">
            Participant <?= $participant_index + 1 ?> of <?= count($participants) ?>
          </div>
        </div>
        <?php 
        // Set up variables for the score form component
        $participant = $current_participant;
        include __DIR__ . '/../components/score_form.php'; 
        ?>
      </div>
    <?php else: ?>
      <div class="bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl p-6 text-center backdrop-blur-md">
        <p class="text-slate-200">Select a participant above to begin scoring.</p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>