<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    $currentPage = urlencode('admin/' . basename($_SERVER['PHP_SELF'])); 
    header("Location: ../login_admin.php?redirect=" . $currentPage);
    exit();
}

// Include the database class file
require_once('../classes/database.php');

// Create an instance of the database class
$con = new database();

// Get pageant ID from session
$pageant_id = $_SESSION['pageant_id'] ?? 1;

// --- Advancements Validation Panel Logic ---
// Helper: Get all judges for this pageant
function getPageantJudges($con, $pageant_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT u.id, u.full_name FROM users u JOIN pageant_users pu ON pu.user_id = u.id WHERE pu.pageant_id = ? AND pu.role = 'JUDGE'");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $judges = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $conn->close();
  return $judges;
}

// Helper: Get active advancement verification session
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

// Helper: Get judge confirmations for a session
function getAdvancementJudgeConfirmations($con, $verification_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT avj.*, u.full_name FROM advancement_verification_judges avj JOIN users u ON avj.judge_user_id = u.id WHERE avj.advancement_verification_id = ?");
  $stmt->bind_param("i", $verification_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $rows = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $conn->close();
  return $rows;
}

// Handle: Open Advancements Validation Panel
if (isset($_POST['open_advancement_validation'])) {
  $conn = $con->opencon();
  $conn->begin_transaction();
  try {
    // Create advancement_verification if not already active
    $stmt = $conn->prepare("SELECT id FROM advancement_verification WHERE pageant_id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $stmt->bind_result($existing_id);
    $stmt->fetch();
    $stmt->close();
    if (!$existing_id) {
      $stmt = $conn->prepare("INSERT INTO advancement_verification (pageant_id, is_active, activated_at) VALUES (?, 1, NOW())");
      $stmt->bind_param("i", $pageant_id);
      $stmt->execute();
      $verification_id = $stmt->insert_id;
      $stmt->close();
      // Insert judge confirmations
      $judges = getPageantJudges($con, $pageant_id);
      foreach ($judges as $judge) {
        $stmt = $conn->prepare("INSERT INTO advancement_verification_judges (advancement_verification_id, judge_user_id, confirmed) VALUES (?, ?, 0)");
        $stmt->bind_param("ii", $verification_id, $judge['id']);
        $stmt->execute();
        $stmt->close();
      }
    }
    $conn->commit();
    $success_message = "Advancements validation panel activated.";
    $show_success_alert = true;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to activate advancements validation: " . $e->getMessage();
    $show_error_alert = true;
  }
  $conn->close();
}

// Handle: Close Advancements Validation
if (isset($_POST['close_advancement_validation'])) {
  $conn = $con->opencon();
  $conn->begin_transaction();
  try {
    // Find active session
    $stmt = $conn->prepare("SELECT id FROM advancement_verification WHERE pageant_id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $stmt->bind_result($verification_id);
    $stmt->fetch();
    $stmt->close();
    if ($verification_id) {
      // Set is_active=0, closed_at=NOW()
      $stmt = $conn->prepare("UPDATE advancement_verification SET is_active = 0, closed_at = NOW() WHERE id = ?");
      $stmt->bind_param("i", $verification_id);
      $stmt->execute();
      $stmt->close();
      // Immediately revert panels to idle state:
      // 1) Close any in-progress tie groups under this pageant so judges see no tie-breaker context
      $stmtT = $conn->prepare("UPDATE tie_groups SET state='closed', tie_breaker_status='CLOSED', tie_breaker_closed_at = NOW() WHERE pageant_id = ? AND (state = 'in_progress' OR tie_breaker_status IN ('OPEN','TIMER_ENDED','CLOSE_ENABLED'))");
      $stmtT->bind_param("i", $pageant_id);
      $stmtT->execute();
      $stmtT->close();

      // 2) Ensure no rounds are accidentally left OPEN; admin must explicitly open the Final round next
      $stmtR = $conn->prepare("UPDATE rounds SET state='CLOSED', closed_at = COALESCE(closed_at, NOW()) WHERE pageant_id = ? AND state = 'OPEN'");
      $stmtR->bind_param("i", $pageant_id);
      $stmtR->execute();
      $stmtR->close();
    }
    $conn->commit();
    $success_message = "Advancements validation finalized. Final round can now be opened.";
    $show_success_alert = true;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to finalize advancements validation: " . $e->getMessage();
    $show_error_alert = true;
  }
  $conn->close();
}

// Get current/active advancement verification session
$active_verification = getActiveAdvancementVerification($con, $pageant_id);
$judge_confirmations = $active_verification ? getAdvancementJudgeConfirmations($con, $active_verification['id']) : [];
$all_judges_confirmed = $active_verification && $judge_confirmations && count($judge_confirmations) > 0 && !array_filter($judge_confirmations, function($j) { return !$j['confirmed']; });

// Get advancement count from query parameter (default 5) - used by auto-advance too
$advancement_count = isset($_GET['count']) ? max(1, min(20, (int)$_GET['count'])) : 5;

// Helpers for auto-advancement
function findAdvancementRounds($con, $pageant_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT id, name, sequence FROM rounds WHERE pageant_id = ? AND state IN ('CLOSED','FINALIZED') ORDER BY sequence DESC LIMIT 1");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $from = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $to = null;
  if ($from) {
    $stmt = $conn->prepare("SELECT id, name FROM rounds WHERE pageant_id = ? AND sequence > ? ORDER BY sequence ASC LIMIT 1");
    $stmt->bind_param("ii", $pageant_id, $from['sequence']);
    $stmt->execute();
    $to = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
  $conn->close();
  return [$from, $to];
}

function getTopForRound($con, $round_id, $division, $limit) {
  $rows = $con->getRoundLeaderboard($round_id, $division);
  return array_slice($rows, 0, max(0, (int)$limit));
}

function autoAdvanceIfReady($con, $pageant_id, $advancement_count) {
  [$from, $to] = findAdvancementRounds($con, $pageant_id);
  if (!$from || !$to) { return ['ok' => false, 'reason' => 'rounds_not_ready']; }
  $from_round_id = (int)$from['id'];
  $to_round_id = (int)$to['id'];

  $conn = $con->opencon();
  $conn->begin_transaction();
  try {
    $inserted = 0; $skipped = 0;
    $insertOne = function($pid) use ($conn, $from_round_id, $to_round_id, &$inserted, &$skipped) {
      $pid = (int)$pid;
      $check = $conn->prepare("SELECT id FROM advancements WHERE from_round_id = ? AND to_round_id = ? AND participant_id = ? LIMIT 1");
      $check->bind_param("iii", $from_round_id, $to_round_id, $pid);
      $check->execute();
      $exists = $check->get_result()->fetch_assoc();
      $check->close();
      if ($exists) { $skipped++; return; }
      $stmt = $conn->prepare("INSERT INTO advancements (from_round_id, to_round_id, participant_id, rank_at_advancement, is_override) VALUES (?, ?, ?, 0, 0)");
      $stmt->bind_param("iii", $from_round_id, $to_round_id, $pid);
      $stmt->execute();
      $stmt->close();
      $inserted++;
    };

  $mrTop = getTopForRound($con, $from_round_id, 'Ambassador', $advancement_count);
    foreach ($mrTop as $row) { $insertOne($row['id']); }
  $msTop = getTopForRound($con, $from_round_id, 'Ambassadress', $advancement_count);
    foreach ($msTop as $row) { $insertOne($row['id']); }

    $conn->commit();
    $conn->close();
    return ['ok' => true, 'inserted' => $inserted, 'skipped' => $skipped, 'to_round_name' => $to['name']];
  } catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    return ['ok' => false, 'reason' => 'db_error', 'error' => $e->getMessage()];
  }
}

// AJAX endpoint for live state and auto-advance trigger
if (isset($_GET['ajax']) && $_GET['ajax'] === 'state') {
  header('Content-Type: application/json');
  // Refresh current state
  $active_verification = getActiveAdvancementVerification($con, $pageant_id);
  $judge_confirmations = $active_verification ? getAdvancementJudgeConfirmations($con, $active_verification['id']) : [];
  $all_judges_confirmed = $active_verification && $judge_confirmations && count($judge_confirmations) > 0 && !array_filter($judge_confirmations, function($j) { return !$j['confirmed']; });

  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT COUNT(*) as count FROM advancements a JOIN rounds r ON a.to_round_id = r.id WHERE r.pageant_id = ?");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $advCount = ($stmt->get_result()->fetch_assoc()['count'] ?? 0);
  $stmt->close();
  $conn->close();
  $advancements_confirmed = $advCount > 0;

  $autoResult = null;
  if ($all_judges_confirmed && !$advancements_confirmed) {
    $autoResult = autoAdvanceIfReady($con, $pageant_id, $advancement_count);
    // refresh confirmation state after auto
    if ($autoResult && $autoResult['ok']) { $advancements_confirmed = true; }
  }

  echo json_encode([
    'active' => (bool)$active_verification,
    'allConfirmed' => (bool)$all_judges_confirmed,
    'advancementsConfirmed' => (bool)$advancements_confirmed,
    'judgeConfirmations' => $judge_confirmations,
    'autoResult' => $autoResult
  ]);
  exit;
}

// Handle advancement confirmation (dynamic mapping from latest CLOSED to next round)
if (isset($_POST['confirm_advancement'])) {
  $mr_participants = $_POST['mr_participants'] ?? [];
  $ms_participants = $_POST['ms_participants'] ?? [];

  $conn = $con->opencon();
  $success_count = 0;
  $error_count = 0;

  // Find the latest CLOSED or FINALIZED round as the from_round
  $stmt = $conn->prepare("SELECT id, name, sequence FROM rounds WHERE pageant_id = ? AND state IN ('CLOSED','FINALIZED') ORDER BY sequence DESC LIMIT 1");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $fromRow = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$fromRow) {
    $conn->close();
    $error_message = "No closed round found to advance from.";
    $show_error_alert = true;
  } else {
    // Find the next round by sequence
    $stmt = $conn->prepare("SELECT id, name FROM rounds WHERE pageant_id = ? AND sequence > ? ORDER BY sequence ASC LIMIT 1");
    $stmt->bind_param("ii", $pageant_id, $fromRow['sequence']);
    $stmt->execute();
    $toRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$toRow) {
      $conn->close();
      $error_message = "No next round found to advance to. Please configure rounds order.";
      $show_error_alert = true;
    } else {
      $from_round_id = (int)$fromRow['id'];
      $to_round_id = (int)$toRow['id'];

      $conn->begin_transaction();
      try {
        // Helper closure to insert if not exists
        $insertAdv = function($pid) use ($conn, $from_round_id, $to_round_id, &$success_count, &$error_count) {
          $check = $conn->prepare("SELECT id FROM advancements WHERE from_round_id = ? AND to_round_id = ? AND participant_id = ? LIMIT 1");
          $check->bind_param("iii", $from_round_id, $to_round_id, $pid);
          $check->execute();
          $exists = $check->get_result()->fetch_assoc();
          $check->close();
          if ($exists) { return; }
          $stmt = $conn->prepare("INSERT INTO advancements (from_round_id, to_round_id, participant_id, rank_at_advancement, is_override) VALUES (?, ?, ?, 0, 1)");
          $stmt->bind_param("iii", $from_round_id, $to_round_id, $pid);
          if ($stmt->execute()) { $success_count++; } else { $error_count++; }
          $stmt->close();
        };

        foreach ($mr_participants as $participant_id) { $insertAdv((int)$participant_id); }
        foreach ($ms_participants as $participant_id) { $insertAdv((int)$participant_id); }

        $conn->commit();

        if ($success_count > 0) {
          $success_message = "Successfully advanced {$success_count} participants to the next round (" . htmlspecialchars($toRow['name']) . ").";
          $show_success_alert = true;
        }
        if ($error_count > 0) {
          $error_message = "Failed to advance {$error_count} participants.";
          $show_error_alert = true;
        }
      } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to advance participants: " . $e->getMessage();
        $show_error_alert = true;
      }
      $conn->close();
    }
  }
}

// advancement_count is already set above

// Get top participants for each division
$mr_top = $con->getTopParticipants($pageant_id, 'Ambassador', $advancement_count);
$ms_top = $con->getTopParticipants($pageant_id, 'Ambassadress', $advancement_count);

// Check if advancements have already been confirmed
$conn = $con->opencon();
$stmt = $conn->prepare("SELECT COUNT(*) as count, r.name as next_round_name FROM advancements a 
                        JOIN rounds r ON a.to_round_id = r.id 
                        WHERE r.pageant_id = ? 
                        GROUP BY a.to_round_id 
                        ORDER BY a.to_round_id DESC 
                        LIMIT 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$advancement_result = $result->fetch_assoc();
$stmt->close();
$conn->close();

$advancements_confirmed = $advancement_result && $advancement_result['count'] > 0;
$next_round_name = $advancement_result['next_round_name'] ?? 'Next Round';

$pageTitle = 'Advancement Review';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
<div class="px-6 py-8">
  <!-- Header -->
  <div class="mb-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-white mb-2">Advancement Review</h1>
        <p class="text-slate-200">Manage advancements validation and selections</p>
      </div>
      <div class="flex items-center gap-3">
        <?php
          // Status chip
          $statusLabel = 'Pending';
          $statusClass = 'bg-white bg-opacity-10 text-slate-200';
          if ($active_verification) { $statusLabel = 'Active'; $statusClass = 'bg-blue-500 bg-opacity-30 text-blue-100'; }
          if (!$active_verification && $advancements_confirmed) { $statusLabel = 'Finalized'; $statusClass = 'bg-green-500 bg-opacity-30 text-green-100'; }
        ?>
  <span data-status-chip class="px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm border border-white border-opacity-20 <?= $statusClass ?>"><?= $statusLabel ?></span>

        <!-- Advancement slots selector -->
        <label for="advancementCount" class="hidden md:block text-sm text-slate-200">Slots</label>
        <input id="advancementCount" name="advancementCount" type="number" min="1" max="20" value="<?= $advancement_count ?>" class="w-20 px-3 py-2 rounded-lg bg-white bg-opacity-10 border border-white border-opacity-20 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" onchange="updateAdvancementCount()" />

        <!-- Header actions: open/close validation -->
        <?php if (!$active_verification): ?>
          <form method="POST">
            <button type="submit" name="open_advancement_validation" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Open Validation</button>
          </form>
        <?php else: ?>
          <form method="POST">
            <button id="closeValidationBtn" type="submit" name="close_advancement_validation" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors" <?php if(!$all_judges_confirmed) echo 'disabled style="opacity:0.6;cursor:not-allowed;"'; ?>>Close Validation</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <!-- Advancements Validation Panel (Admin) -->
  <div class="mb-8">
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
      <h2 class="text-lg font-semibold text-white mb-2">Advancements Validation Panel</h2>
      <?php if ($active_verification): ?>
        <div class="mb-4">
          <h3 class="text-base font-medium text-white mb-1">Judge Confirmations</h3>
          <ul id="judgeConfirmationsList" class="list-disc list-inside text-slate-200">
            <?php foreach ($judge_confirmations as $j): ?>
              <li>
                <span class="font-semibold"><?php echo htmlspecialchars($j['full_name']); ?></span>:
                <?php if ($j['confirmed']): ?>
                  <span class="text-green-400">Confirmed</span>
                  <span class="text-xs text-slate-400">(<?php echo $j['confirmed_at'] ? htmlspecialchars($j['confirmed_at']) : ''; ?>)</span>
                <?php else: ?>
                  <span class="text-yellow-300">Pending</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php if(!$all_judges_confirmed): ?>
          <p class="text-sm text-yellow-200">All judges must confirm before closing validation.</p>
        <?php else: ?>
          <p class="text-sm text-green-200">All judges confirmed. Auto-advancement will compute top candidates and lock in once finalized.</p>
        <?php endif; ?>
        <?php 
          // Compact progress bar include
          $judge_confirmations_local = $judge_confirmations; 
          include __DIR__ . '/partials/judge_progress_bar.php';
        ?>
      <?php else: ?>
        <p class="text-slate-200">Validation is not active. Use the Open Validation action above.</p>
      <?php endif; ?>
    </div>
  </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="bg-green-500 bg-opacity-20 backdrop-blur-sm border border-green-400 border-opacity-30 text-green-100 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
      <div class="bg-red-500 bg-opacity-20 backdrop-blur-sm border border-red-400 border-opacity-30 text-red-100 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-300" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- Advancement Confirmed Warning -->
    <?php if ($advancements_confirmed): ?>
  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6 mb-6">
        <div class="flex items-start">
          <svg class="w-6 h-6 text-blue-400 mt-0.5 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-white mb-2">Advancements Already Confirmed</h3>
            <p class="text-slate-200 mb-3">
              Participant advancements have been confirmed for <strong class="text-blue-200"><?php echo htmlspecialchars($next_round_name, ENT_QUOTES, 'UTF-8'); ?></strong>. 
              All advancement controls have been disabled to prevent accidental changes.
            </p>
            <div class="text-sm text-slate-200">
              <p class="font-medium">If you need to modify advancements:</p>
              <ul class="list-disc list-inside mt-1 space-y-1">
                <li>Contact system administrator</li>
                <li>Or revert advancements through database management</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Auto-Advancement Preview (replaces manual selection) -->
    <?php 
      [$fromRound, $toRound] = findAdvancementRounds($con, $pageant_id);
  $previewMr = $fromRound ? getTopForRound($con, (int)$fromRound['id'], 'Ambassador', $advancement_count) : [];
  $previewMs = $fromRound ? getTopForRound($con, (int)$fromRound['id'], 'Ambassadress', $advancement_count) : [];
    ?>
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6 mb-8">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-white">Auto-Advancement Preview <span class="text-sm text-slate-300">(Top <?= (int)$advancement_count ?> per division)</span></h3>
        <?php if ($fromRound && $toRound): ?>
          <span class="text-xs text-slate-300">From: <strong class="text-slate-100"><?= htmlspecialchars($fromRound['name'], ENT_QUOTES, 'UTF-8') ?></strong> → To: <strong class="text-slate-100"><?= htmlspecialchars($toRound['name'], ENT_QUOTES, 'UTF-8') ?></strong></span>
        <?php endif; ?>
      </div>
      <?php if (!$fromRound): ?>
        <p class="text-slate-200">No closed round found. Close a round to enable auto-advancement.</p>
      <?php elseif (!$toRound): ?>
        <p class="text-slate-200">No next round configured. Please set up the next round in Rounds & Criteria.</p>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 class="text-slate-100 font-medium mb-2">Ambassador Division</h4>
            <div class="overflow-x-auto rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-900 bg-opacity-30 text-blue-200 uppercase text-xs tracking-wide">
                  <tr>
                    <th class="px-3 py-2 text-left">#</th>
                    <th class="px-3 py-2 text-left">Name</th>
                    <th class="px-3 py-2 text-left">Score</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                  <?php if (!empty($previewMr)): foreach ($previewMr as $row): ?>
                    <tr class="hover:bg-blue-900/10">
                      <td class="px-3 py-2 font-semibold text-blue-200"><?= htmlspecialchars($row['number_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="px-3 py-2 text-slate-100"><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="px-3 py-2 text-blue-100 font-medium"><?= htmlspecialchars(number_format((float)($row['raw_score'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  <?php endforeach; else: ?>
                    <tr><td colspan="3" class="px-3 py-3 text-slate-300">No data</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div>
            <h4 class="text-slate-100 font-medium mb-2">Ambassadress Division</h4>
            <div class="overflow-x-auto rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-900 bg-opacity-30 text-pink-200 uppercase text-xs tracking-wide">
                  <tr>
                    <th class="px-3 py-2 text-left">#</th>
                    <th class="px-3 py-2 text-left">Name</th>
                    <th class="px-3 py-2 text-left">Score</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                  <?php if (!empty($previewMs)): foreach ($previewMs as $row): ?>
                    <tr class="hover:bg-pink-900/10">
                      <td class="px-3 py-2 font-semibold text-pink-200"><?= htmlspecialchars($row['number_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="px-3 py-2 text-slate-100"><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="px-3 py-2 text-pink-100 font-medium"><?= htmlspecialchars(number_format((float)($row['raw_score'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                  <?php endforeach; else: ?>
                    <tr><td colspan="3" class="px-3 py-3 text-slate-300">No data</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php if ($all_judges_confirmed && !$advancements_confirmed): ?>
          <div class="mt-4 text-sm text-green-200">All judges confirmed. The system will finalize the advancement automatically.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
</div>

<script>
function updateAdvancementCount() {
  const count = document.getElementById('advancementCount').value;
  const url = new URL(window.location);
  url.searchParams.set('count', count);
  window.location.href = url.toString();
}

function toggleAllMr(checkbox) {
  const mrCheckboxes = document.querySelectorAll('.mr-participants-checkbox');
  mrCheckboxes.forEach(cb => cb.checked = checkbox.checked);
  updateSelectedCount();
}

function toggleAllMs(checkbox) {
  const msCheckboxes = document.querySelectorAll('.ms-participants-checkbox');
  msCheckboxes.forEach(cb => cb.checked = checkbox.checked);
  updateSelectedCount();
}

function clearAllSelections() {
  const allCheckboxes = document.querySelectorAll('input[type="checkbox"]');
  allCheckboxes.forEach(cb => cb.checked = false);
  updateSelectedCount();
}

function updateSelectedCount() {
  const mrSelected = document.querySelectorAll('.mr-participants-checkbox:checked').length;
  const msSelected = document.querySelectorAll('.ms-participants-checkbox:checked').length;
  const total = mrSelected + msSelected;
  // Guard for pages without selection UI
  const selectedEl = document.getElementById('selectedCount');
  const confirmBtn = document.getElementById('confirmButton');
  if (!selectedEl || !confirmBtn) return;
  selectedEl.textContent = total;
  confirmBtn.disabled = total === 0;
  
  // Update select all checkboxes
  const mrTotal = document.querySelectorAll('.mr-participants-checkbox').length;
  const msTotal = document.querySelectorAll('.ms-participants-checkbox').length;
  
  if (mrTotal > 0) {
    document.getElementById('selectAllMr').checked = mrSelected === mrTotal;
    document.getElementById('selectAllMr').indeterminate = mrSelected > 0 && mrSelected < mrTotal;
  }
  
  if (msTotal > 0) {
    document.getElementById('selectAllMs').checked = msSelected === msTotal;
    document.getElementById('selectAllMs').indeterminate = msSelected > 0 && msSelected < msTotal;
  }
}

// Add event listeners to all participant checkboxes
document.addEventListener('DOMContentLoaded', function() {
  const participantCheckboxes = document.querySelectorAll('.mr-participants-checkbox, .ms-participants-checkbox');
  participantCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
  });
  
  // Initial count
  updateSelectedCount();
  
  // Form submission confirmation
  const advForm = document.getElementById('advancementForm');
  if (advForm) advForm.addEventListener('submit', function(e) {
    const selectedCount = parseInt(document.getElementById('selectedCount').textContent);
    if (selectedCount === 0) {
      e.preventDefault();
      showNotification('Please select at least one participant to advance', 'error', true);
      return;
    }
    
    if (!confirm(`Are you sure you want to advance ${selectedCount} participants to the next round? This action cannot be undone.`)) {
      e.preventDefault();
      return;
    }
    
    showNotification('Processing advancement...', 'info', true);
  });

  // Live polling for judge confirmations and auto-advancement state
  async function pollState() {
    try {
      const url = new URL(window.location.href);
      url.searchParams.set('ajax', 'state');
      const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      // If advancements just confirmed, provide feedback and reload to reflect disabled controls
      if (data.advancementsConfirmed) {
        if (window.showNotification) {
          showNotification('Advancements have been finalized automatically.', 'success', true);
        }
        setTimeout(() => { window.location.reload(); }, 1200);
        return;
      }
      // Update status chip text
      const statusChip = document.querySelector('[data-status-chip]');
      if (statusChip) {
        statusChip.textContent = data.active ? 'Active' : (data.advancementsConfirmed ? 'Finalized' : 'Pending');
      }

      // Update judge confirmations list
      const list = document.getElementById('judgeConfirmationsList');
      if (list && Array.isArray(data.judgeConfirmations)) {
        list.innerHTML = data.judgeConfirmations.map(j => {
          const safeName = j.full_name ? j.full_name : 'Judge';
          if (j.confirmed) {
            const ts = j.confirmed_at ? ` (<span class=\"text-xs text-slate-400\">${j.confirmed_at}</span>)` : '';
            return `<li><span class=\"font-semibold\">${safeName}</span>: <span class=\"text-green-400\">Confirmed</span>${ts}</li>`;
          } else {
            return `<li><span class=\"font-semibold\">${safeName}</span>: <span class=\"text-yellow-300\">Pending</span></li>`;
          }
        }).join('');
      }

      // Update progress counts and bar
      const confirmedCount = (data.judgeConfirmations || []).filter(j => j.confirmed).length;
      const totalCount = (data.judgeConfirmations || []).length;
      const pct = totalCount > 0 ? Math.round((confirmedCount / Math.max(1, totalCount)) * 100) : 0;
      const pctEl = document.getElementById('judgeProgressPct');
      const confEl = document.getElementById('judgeProgressConfirmed');
      const totEl = document.getElementById('judgeProgressTotal');
      const barEl = document.getElementById('judgeProgressBarInner');
      if (pctEl) pctEl.textContent = `${pct}%`;
      if (confEl) confEl.textContent = confirmedCount;
      if (totEl) totEl.textContent = totalCount;
      if (barEl) barEl.style.width = `${pct}%`;

      // Toggle Close Validation button availability
      const closeBtn = document.getElementById('closeValidationBtn');
      if (closeBtn) {
        if (data.allConfirmed) {
          closeBtn.disabled = false;
          closeBtn.style.opacity = '';
          closeBtn.style.cursor = '';
          closeBtn.classList.remove('opacity-60');
        } else {
          closeBtn.disabled = true;
          closeBtn.style.opacity = '0.6';
          closeBtn.style.cursor = 'not-allowed';
        }
      }
    } catch (e) {
      // ignore
    }
  }
  setInterval(pollState, 4000);
});
</script>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
