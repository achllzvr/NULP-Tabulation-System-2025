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

require_once __DIR__ . '/advancement_helpers.php';
$conn = $con->opencon();

// Get pageant ID from session
$pageant_id = $_SESSION['pageant_id'] ?? 1; // Use consistent session variable

// Handle criteria assignment to rounds
if (isset($_POST['assign_criteria'])) {
    $round_id = intval($_POST['round_id']);
    $criteria_data = $_POST['criteria'] ?? [];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // First, remove existing criteria assignments for this round
        $stmt = $conn->prepare("DELETE FROM round_criteria WHERE round_id = ?");
        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $stmt->close();
        
        // Calculate total weight to validate it sums to 100% (which becomes 1.0 in database)
        $total_weight_percent = 0;
        foreach ($criteria_data as $criterion_data) {
            if (isset($criterion_data['selected']) && $criterion_data['selected']) {
                $weight_percent = floatval($criterion_data['weight']);
                $total_weight_percent += $weight_percent;
            }
        }
        
        // Validate total weight (should be 100%)
        if (abs($total_weight_percent - 100.0) > 0.1) {
            throw new Exception("Total weight must equal 100%. Current total: " . number_format($total_weight_percent, 1) . "%");
        }
        
        // Insert new criteria assignments
        $display_order = 1;
        foreach ($criteria_data as $criterion_id => $criterion_data) {
            if (isset($criterion_data['selected']) && $criterion_data['selected']) {
                $weight_percent = floatval($criterion_data['weight']);
                $weight = $weight_percent / 100.0; // Convert percentage to decimal for database
                $max_score = floatval($criterion_data['max_score'] ?? 10.00);
                
                $stmt = $conn->prepare("INSERT INTO round_criteria (round_id, criterion_id, weight, max_score, display_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiddi", $round_id, $criterion_id, $weight, $max_score, $display_order);
                $stmt->execute();
                $stmt->close();
                
                $display_order++;
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $success_message = "Criteria assigned to round successfully.";
        $show_success_alert = true;
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $error_message = "Error assigning criteria: " . $e->getMessage();
        $show_error_alert = true;
    }
}

// Handle round state changes
if (isset($_POST['toggle_round'])) {
    $round_id = intval($_POST['round_id']);
    $action = $_POST['action'];
    
    $new_state = '';
    switch ($action) {
        case 'open':
            $new_state = 'OPEN';
            break;
        case 'close':
            $new_state = 'CLOSED';
            break;
        case 'finalize':
            $new_state = 'FINALIZED';
            break;
        case 'pending':
            $new_state = 'PENDING';
            break;
        default:
            $new_state = 'PENDING';
    }
    
  try {
    // Use integer flags to avoid string collation comparisons inside CASE
    $is_open = ($new_state === 'OPEN') ? 1 : 0;
    $is_closed_or_final = (in_array($new_state, ['CLOSED', 'FINALIZED'], true)) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE rounds 
      SET state = ?, 
        opened_at = CASE WHEN ? = 1 THEN NOW() ELSE opened_at END, 
        closed_at = CASE WHEN ? = 1 THEN NOW() ELSE closed_at END 
      WHERE id = ?");
    $stmt->bind_param("siii", $new_state, $is_open, $is_closed_or_final, $round_id);
        
    if ($stmt->execute()) {
      // Create per-round signing session on transition to CLOSED
      if ($new_state === 'CLOSED') {
        // Only create if not exists
        $stmt2 = $conn->prepare("SELECT id FROM round_signing WHERE round_id = ? LIMIT 1");
        $stmt2->bind_param("i", $round_id);
        $stmt2->execute();
        $exists = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        if (!$exists) {
          // Create session
          $stmt3 = $conn->prepare("INSERT INTO round_signing (round_id, is_active, opened_at) VALUES (?, 1, NOW())");
          $stmt3->bind_param("i", $round_id);
          $stmt3->execute();
          $signing_id = $stmt3->insert_id;
          $stmt3->close();
          // Seed judge confirmations from assigned judges only
          // Use known $pageant_id instead of subquery to prevent 'Subquery returns more than 1 row' when duplicate id=0 rows exist
          $stmtJ = $conn->prepare("SELECT u.id
                                   FROM round_judges rj
                                   JOIN users u ON u.id = rj.judge_user_id
                                   WHERE rj.pageant_id = ?
                                     AND rj.round_id = ?
                                     AND u.is_active = 1");
          $stmtJ->bind_param("ii", $pageant_id, $round_id);
          $stmtJ->execute();
          $resJ = $stmtJ->get_result();
          $judgeIds = [];
          while ($row = $resJ->fetch_assoc()) { $judgeIds[] = (int)$row['id']; }
          $stmtJ->close();
          if (!empty($judgeIds)) {
            $stmtIns = $conn->prepare("INSERT INTO round_signing_judges (round_signing_id, judge_user_id, confirmed) VALUES (?, ?, 0)");
            foreach ($judgeIds as $jid) {
              $stmtIns->bind_param("ii", $signing_id, $jid);
              $stmtIns->execute();
            }
            $stmtIns->close();
          }
        }
      }
      // Guard finalization until all judges sign
      if ($new_state === 'FINALIZED') {
        // Require confirmations from assigned judges (dynamic to current assignments)
        // Find active signing session
        $stmtSig = $conn->prepare("SELECT id FROM round_signing WHERE round_id = ? AND is_active = 1 LIMIT 1");
        $stmtSig->bind_param("i", $round_id);
        $stmtSig->execute();
        $sigRow = $stmtSig->get_result()->fetch_assoc();
        $stmtSig->close();
        if (!$sigRow) {
          // Revert finalize and prompt
          $stmtB = $conn->prepare("UPDATE rounds SET state='CLOSED' WHERE id = ?");
          $stmtB->bind_param("i", $round_id);
          $stmtB->execute();
          $stmtB->close();
          throw new Exception('Signing session not found. Start signing and collect confirmations from assigned judges.');
        }
        $sid = (int)$sigRow['id'];
        // Assigned active judges
  $stmtA = $conn->prepare("SELECT COUNT(*) AS assigned
         FROM round_judges rj
         JOIN users u ON u.id = rj.judge_user_id AND u.is_active = 1
         WHERE rj.round_id = ? AND rj.pageant_id = ?");
  $stmtA->bind_param("ii", $round_id, $pageant_id);
        $stmtA->execute();
        $assigned = (int)($stmtA->get_result()->fetch_assoc()['assigned'] ?? 0);
        $stmtA->close();
        // Confirmed among assigned
  $stmtC = $conn->prepare("SELECT SUM(CASE WHEN rjs.confirmed=1 THEN 1 ELSE 0 END) AS confirmed
         FROM round_judges rj
         JOIN users u ON u.id = rj.judge_user_id AND u.is_active = 1
         LEFT JOIN round_signing_judges rjs ON rjs.judge_user_id = rj.judge_user_id AND rjs.round_signing_id = ?
         WHERE rj.round_id = ? AND rj.pageant_id = ?");
  $stmtC->bind_param("iii", $sid, $round_id, $pageant_id);
        $stmtC->execute();
        $confirmed = (int)($stmtC->get_result()->fetch_assoc()['confirmed'] ?? 0);
        $stmtC->close();
        if ($assigned === 0 || $confirmed < $assigned) {
          // Revert finalize and prompt
          $stmtB = $conn->prepare("UPDATE rounds SET state='CLOSED' WHERE id = ?");
          $stmtB->bind_param("i", $round_id);
          $stmtB->execute();
          $stmtB->close();
          throw new Exception('All assigned judges must sign this round before finalization.');
        }
        // Close signing session
        $stmtC2 = $conn->prepare("UPDATE round_signing SET is_active = 0, closed_at = NOW() WHERE id = ?");
        $stmtC2->bind_param("i", $sid);
        $stmtC2->execute();
        $stmtC2->close();
      }
      $success_message = "Round status updated successfully to " . $new_state . ".";
      $show_success_alert = true;
    } else {
            $error_message = "Error updating round status: " . $stmt->error;
            $show_error_alert = true;
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        // Handle specific errors from the database trigger/stored procedure
        if (strpos($e->getMessage(), 'Cannot open round') !== false) {
            $error_message = $e->getMessage();
        } else {
            $error_message = "Error updating round status: " . $e->getMessage();
        }
        $show_error_alert = true;
    }
}

// Manually start round signing for a closed round (for rounds closed before this feature)
if (isset($_POST['start_round_signing'])) {
  $round_id = intval($_POST['round_id'] ?? 0);
  try {
    // Ensure round is CLOSED
    $stmt = $conn->prepare("SELECT state FROM rounds WHERE id=?");
    $stmt->bind_param("i", $round_id);
    $stmt->execute();
    $st = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$st || $st['state'] !== 'CLOSED') { throw new Exception('Round must be CLOSED to start signing.'); }
    // Check exists
    $stmt = $conn->prepare("SELECT id FROM round_signing WHERE round_id=? AND is_active=1 LIMIT 1");
    $stmt->bind_param("i", $round_id);
    $stmt->execute();
    $ex = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($ex) { throw new Exception('Signing session already active.'); }
    // Create signing session
    $stmt = $conn->prepare("INSERT INTO round_signing (round_id, is_active, opened_at) VALUES (?, 1, NOW())");
    $stmt->bind_param("i", $round_id);
    $stmt->execute();
    $signing_id = $stmt->insert_id;
    $stmt->close();
    // Seed judge confirmations from assigned judges only
    $stmtJ = $conn->prepare("SELECT u.id
                             FROM round_judges rj
                             JOIN users u ON u.id = rj.judge_user_id
                             WHERE rj.pageant_id = ?
                               AND rj.round_id = ?
                               AND u.is_active = 1");
    $stmtJ->bind_param("ii", $pageant_id, $round_id);
    $stmtJ->execute();
    $resJ = $stmtJ->get_result();
    $judgeIds = [];
    while ($row = $resJ->fetch_assoc()) { $judgeIds[] = (int)$row['id']; }
    $stmtJ->close();
    if (!empty($judgeIds)) {
      $stmtIns = $conn->prepare("INSERT INTO round_signing_judges (round_signing_id, judge_user_id, confirmed) VALUES (?, ?, 0)");
      foreach ($judgeIds as $jid) {
        $stmtIns->bind_param("ii", $signing_id, $jid);
        $stmtIns->execute();
      }
      $stmtIns->close();
    }
    $show_success_alert = true;
    $success_message = 'Round signing session started.';
  } catch (Exception $e) {
    $show_error_alert = true;
    $error_message = 'Failed to start signing: ' . $e->getMessage();
  }
}

// Save judge assignments per round
if (isset($_POST['assign_judges_to_round'])) {
  $round_id = intval($_POST['round_id']);
  $judge_ids = array_map('intval', $_POST['judge_ids'] ?? []);
  try {
    $conn->begin_transaction();
  // Remove existing assignments for this round scoped to this pageant
  $stmt = $conn->prepare("DELETE FROM round_judges WHERE pageant_id=? AND round_id=?");
  $stmt->bind_param("ii", $pageant_id, $round_id);
    $stmt->execute();
    $stmt->close();
    // Insert posted judge IDs as-is (no dedup/whitelisting)
    if (!empty($judge_ids)) {
      $stmt = $conn->prepare("INSERT INTO round_judges(pageant_id, round_id, judge_user_id) VALUES(?,?,?)");
      foreach ($judge_ids as $jid) {
        $stmt->bind_param("iii", $pageant_id, $round_id, $jid);
        $stmt->execute();
      }
      $stmt->close();
    }
    $conn->commit();
    $show_success_alert = true;
    $success_message = 'Judge assignments updated.';
  } catch (Exception $e) {
    $conn->rollback();
    $show_error_alert = true;
    $error_message = 'Error updating judge assignments: ' . $e->getMessage();
  }
}

// Pair Scoring: enforced for Advocacy & Talent rounds; no manual toggle (UI removed)

// Fetch rounds with their criteria
$stmt = $conn->prepare("SELECT r.*, COUNT(rc.criterion_id) as criteria_count 
                        FROM rounds r 
                        LEFT JOIN round_criteria rc ON r.id = rc.round_id 
                        WHERE r.pageant_id = ? 
                        GROUP BY r.id 
                        ORDER BY r.sequence");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$rounds = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch criteria for the pageant with parent-child relationships
$stmt = $conn->prepare("
  SELECT c.*, pc.name as parent_name 
  FROM criteria c 
  LEFT JOIN criteria pc ON c.parent_criterion_id = pc.id 
  WHERE c.pageant_id = ? AND c.is_active = 1 
  ORDER BY COALESCE(c.parent_criterion_id, c.id), c.parent_criterion_id IS NULL DESC, c.sort_order
");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$criteria = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch judges list for assignments UI
$judges = [];
$stmt = $conn->prepare("SELECT u.id, u.full_name, u.username 
                        FROM users u 
                        JOIN pageant_users pu ON pu.user_id = u.id 
                        WHERE pu.pageant_id = ? AND pu.role='judge' AND u.is_active=1
                        ORDER BY u.full_name");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $judges[] = $row; }
$stmt->close();

// Pre-fetch advancements for all rounds (by round_id)
require_once __DIR__ . '/advancement_helpers.php';
$advancements_by_round = [];
foreach ($rounds as $r) {
  $advancements_by_round[$r['id']] = get_advancements_for_round($conn, $r['id']);
}

// Pre-fetch judge assignments for all rounds in this pageant
$assignments_by_round = [];
$stmt = $conn->prepare("SELECT round_id, judge_user_id FROM round_judges WHERE pageant_id = ?");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $rid = (int)$row['round_id'];
  if (!isset($assignments_by_round[$rid])) { $assignments_by_round[$rid] = []; }
  $assignments_by_round[$rid][] = (int)$row['judge_user_id'];
}
$stmt->close();

// Calculate statistics
$total_rounds = count($rounds);
$open_rounds = count(array_filter($rounds, fn($r) => $r['state'] === 'OPEN'));
$closed_rounds = count(array_filter($rounds, fn($r) => in_array($r['state'], ['CLOSED', 'FINALIZED'])));

$conn->close();

$pageTitle = 'Rounds & Criteria - Admin';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
<?php // ...existing code... ?>
<style>
  [data-tooltip] {
    position: relative;
    cursor: not-allowed;
  }
  [data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 50%;
    bottom: 120%;
    transform: translateX(-50%);
    background: rgba(30,41,59,0.95);
    color: #fff;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    white-space: pre-line;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s;
    z-index: 50;
    min-width: 180px;
    text-align: center;
  }
  [data-tooltip]:hover::after {
    opacity: 1;
  }
</style>
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Rounds & Criteria</h1>
          <p class="text-slate-200">Manage judging rounds and scoring criteria</p>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Total Rounds</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo $total_rounds; ?></p>
  <p class="text-sm text-slate-200">Created rounds</p>
      </div>

  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Open Rounds</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo $open_rounds; ?></p>
  <p class="text-sm text-slate-200">Currently judging</p>
      </div>

  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Completed</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo $closed_rounds; ?></p>
  <p class="text-sm text-slate-200">Finished rounds</p>
      </div>

  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Criteria</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo count($criteria); ?></p>
  <p class="text-sm text-slate-200">Scoring criteria</p>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- Rounds Management -->
      <!-- Rounds List -->
      <div class="lg:col-span-2">
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
          <div class="px-6 py-4 border-b border-white border-opacity-10">
            <h3 class="text-lg font-semibold text-white">Judging Rounds</h3>
            <p class="text-sm text-slate-200 mt-1">Control round status and monitor progress</p>
          </div>
          
          <div class="p-6">
            <?php if (!empty($rounds)): ?>
              <div class="space-y-4">
                <?php foreach ($rounds as $round):
                  // Check if this is the Final Round (last in sequence)
                  $is_final_round = false;
                  if (!empty($rounds)) {
                    $max_seq = max(array_column($rounds, 'sequence'));
                    $is_final_round = ($round['sequence'] == $max_seq);
                  }
                  $advancements = $advancements_by_round[$round['id']] ?? [];
                ?>
                  <?php
                    $final_blocked = $is_final_round && count($advancements) === 0;
                    // If round has a signing session active and not all judges confirmed, block finalization action
                    $signing_blocked = false;
                    $signing_tooltip = '';
                    $has_active_signing_session = false;
                    // Dynamic gating: require confirmations from currently assigned judges only
                    $connChk = $con->opencon();
                    // Check if a signing session is currently active
                    $stmtSig0 = $connChk->prepare("SELECT id FROM round_signing WHERE round_id = ? AND is_active = 1 LIMIT 1");
                    $stmtSig0->bind_param("i", $round['id']);
                    $stmtSig0->execute();
                    $sigRow0 = $stmtSig0->get_result()->fetch_assoc();
                    $stmtSig0->close();
                    $has_active_signing_session = (bool)$sigRow0;
                    // Count active assigned judges for this round
                    $stmtA = $connChk->prepare("SELECT COUNT(*) AS assigned
                                                 FROM round_judges rj
                                                 JOIN users u ON u.id = rj.judge_user_id
                                                 WHERE rj.round_id = ?
                                                   AND rj.pageant_id = ?
                                                   AND u.is_active = 1");
                    $stmtA->bind_param("ii", $round['id'], $pageant_id);
                    $stmtA->execute();
                    $assigned = (int)($stmtA->get_result()->fetch_assoc()['assigned'] ?? 0);
                    $stmtA->close();
                    if ($assigned > 0) {
                      if (!$has_active_signing_session) {
                        $signing_blocked = true;
                        $signing_tooltip = 'Start signing: assigned judges must confirm before finalization.';
                      } else {
                        $signing_id_chk = (int)$sigRow0['id'];
                        // Count how many assigned judges have confirmed in this signing session
                        $stmtC = $connChk->prepare("SELECT SUM(CASE WHEN rjs.confirmed = 1 THEN 1 ELSE 0 END) AS confirmed
                                                     FROM round_judges rj
                                                     JOIN users u ON u.id = rj.judge_user_id AND u.is_active = 1
                                                     LEFT JOIN round_signing_judges rjs ON rjs.judge_user_id = rj.judge_user_id AND rjs.round_signing_id = ?
                                                     WHERE rj.round_id = ?
                                                       AND rj.pageant_id = ?
                                                    ");
                        $stmtC->bind_param("iii", $signing_id_chk, $round['id'], $pageant_id);
                        $stmtC->execute();
                        $confirmed = (int)($stmtC->get_result()->fetch_assoc()['confirmed'] ?? 0);
                        $stmtC->close();
                        if ($confirmed < $assigned) {
                          $signing_blocked = true;
                          $signing_tooltip = 'Finalize disabled until all assigned judges sign this round.';
                        }
                      }
                    }
                    $connChk->close();
                    $tooltip = $final_blocked ? 'Cannot open/close/finalize Final Round until advancements are set.' : ($signing_blocked ? $signing_tooltip : '');
                  ?>
                  <div class="border border-white border-opacity-10 rounded-lg p-6 bg-white bg-opacity-10 relative group"<?php if($final_blocked || $signing_blocked) echo ' data-tooltip="' . htmlspecialchars($tooltip) . '"'; ?>>
                    <div class="flex items-center justify-between mb-4">
                      <div>
                        <h4 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($round['name']); ?></h4>
                        <p class="text-sm text-slate-200">
                          Round <?php echo $round['sequence']; ?> • 
                          <span class="<?php echo $round['criteria_count'] > 0 ? 'text-green-300 font-medium' : 'text-red-300 font-medium'; ?>">
                            <?php echo $round['criteria_count']; ?> criteria assigned
                          </span>
                          <?php if ($round['criteria_count'] == 0): ?>
                            <span class="text-red-300">⚠️ Cannot open without criteria</span>
                          <?php endif; ?>
                        </p>
                      </div>
                      <span class="px-3 py-1 text-sm font-medium rounded-full <?php 
                        switch ($round['state']) {
                          case 'OPEN':
                            echo 'bg-blue-500 bg-opacity-30 text-blue-200 backdrop-blur-sm';
                            break;
                          case 'CLOSED':
                          case 'FINALIZED':
                            echo 'bg-green-500 bg-opacity-30 text-green-200 backdrop-blur-sm';
                            break;
                          default:
                            echo 'bg-white bg-opacity-20 text-slate-200 backdrop-blur-sm';
                        }
                      ?>">
                        <?php echo $round['state']; ?>
                      </span>
                    </div>
                    
                    <div class="mb-4">
                      <p class="text-sm text-slate-200">
                        <?php $scoring_label = (isset($round['scoring_mode']) && $round['scoring_mode'] !== '') ? ucfirst(strtolower((string)$round['scoring_mode'])) : 'Prelim'; ?>
                        <strong>Type:</strong> <?php echo htmlspecialchars($scoring_label, ENT_QUOTES, 'UTF-8'); ?> Round
                        <?php if ($round['advancement_limit']): ?>
                          • <strong>Advances:</strong> Top <?php echo $round['advancement_limit']; ?>
                        <?php endif; ?>
                      </p>
                      <?php 
                        $rname = trim((string)$round['name']);
                        $isPairRoundByName = (stripos($rname, 'advocacy') !== false) || (stripos($rname, 'talent') !== false);
                      ?>
                      <div class="mt-2 flex items-center gap-2">
                        <?php if ($isPairRoundByName): ?>
                          <span class="inline-flex items-center gap-2 text-xs px-2 py-1 rounded border border-white border-opacity-20 bg-white bg-opacity-10 text-green-300">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414L8.5 11.586l6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Pair Scoring (duos) enforced
                          </span>
                        <?php else: ?>
                          <span class="inline-flex items-center gap-2 text-xs px-2 py-1 rounded border border-white border-opacity-20 bg-white bg-opacity-10 text-slate-200">
                            Individual scoring
                          </span>
                        <?php endif; ?>
                      </div>
                      <?php if ($round['opened_at']): ?>
                        <p class="text-sm text-slate-200 mt-1">
                          <strong>Opened:</strong> <?php echo date('M j, Y g:i A', strtotime($round['opened_at'])); ?>
                        </p>
                      <?php endif; ?>
                      <?php if ($round['closed_at']): ?>
                        <p class="text-sm text-slate-200 mt-1">
                          <strong>Closed:</strong> <?php echo date('M j, Y g:i A', strtotime($round['closed_at'])); ?>
                        </p>
                      <?php endif; ?>
                      <?php if ($round['state'] === 'CLOSED'): ?>
                        <?php
                          // Show progress among currently assigned judges only
                          $connProg = $con->opencon();
                          // Assigned active judges count
                          $stmtPA = $connProg->prepare("SELECT COUNT(*) AS assigned
                                                        FROM round_judges rj
                                                        JOIN users u ON u.id = rj.judge_user_id AND u.is_active = 1
                                                        WHERE rj.round_id = ?
                                                          AND rj.pageant_id = ?");
                          $stmtPA->bind_param("ii", $round['id'], $pageant_id);
                          $stmtPA->execute();
                          $assignedProg = (int)($stmtPA->get_result()->fetch_assoc()['assigned'] ?? 0);
                          $stmtPA->close();
                          // Active signing id
                          $stmtPS = $connProg->prepare("SELECT id FROM round_signing WHERE round_id=? AND is_active=1 LIMIT 1");
                          $stmtPS->bind_param("i", $round['id']);
                          $stmtPS->execute();
                          $sigProg = $stmtPS->get_result()->fetch_assoc();
                          $stmtPS->close();
                          $doneProg = 0;
                          if ($sigProg && $assignedProg > 0) {
                            $sid = (int)$sigProg['id'];
                            $stmtPC = $connProg->prepare("SELECT SUM(CASE WHEN rjs.confirmed=1 THEN 1 ELSE 0 END) AS confirmed
                                                          FROM round_judges rj
                                                          JOIN users u ON u.id = rj.judge_user_id AND u.is_active = 1
                                                          LEFT JOIN round_signing_judges rjs ON rjs.judge_user_id = rj.judge_user_id AND rjs.round_signing_id = ?
                                                          WHERE rj.round_id = ?
                                                            AND rj.pageant_id = ?");
                            $stmtPC->bind_param("iii", $sid, $round['id'], $pageant_id);
                            $stmtPC->execute();
                            $doneProg = (int)($stmtPC->get_result()->fetch_assoc()['confirmed'] ?? 0);
                            $stmtPC->close();
                          }
                          $connProg->close();
                          if ($assignedProg > 0):
                        ?>
                          <p class="text-sm text-slate-200 mt-1">
                            <strong>Signing:</strong> <?= $doneProg ?>/<?= $assignedProg ?> judges confirmed
                          </p>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                    <?php 
                      // Use prefetched assignments to avoid DB calls after connection close
                      $assigned = $assignments_by_round[$round['id']] ?? [];
                    ?>
                    <form method="POST" class="mb-4">
                      <input type="hidden" name="assign_judges_to_round" value="1">
                      <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                      <div class="bg-white bg-opacity-10 rounded-lg p-4 border border-white border-opacity-10">
                        <div class="flex items-center justify-between mb-2">
                          <label class="block text-sm font-medium text-slate-200">Assigned Judges</label>
                          <div class="flex items-center gap-3 text-xs text-slate-300">
                            <span>Check judges allowed to score this round</span>
                            <button type="button" class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-white border border-white/20" onclick="toggleRoundJudgeChecks(<?php echo $round['id']; ?>, true)">Select All</button>
                            <button type="button" class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-white border border-white/20" onclick="toggleRoundJudgeChecks(<?php echo $round['id']; ?>, false)">Select None</button>
                          </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-2 max-h-40 overflow-auto pr-1">
                          <?php foreach ($judges as $j): $checked = in_array((int)$j['id'], $assigned) ? 'checked' : ''; ?>
                            <label class="inline-flex items-center gap-2 text-slate-200">
                              <input type="checkbox" name="judge_ids[]" value="<?php echo (int)$j['id']; ?>" <?php echo $checked; ?> class="rounded round-judge-<?php echo $round['id']; ?>">
                              <span><?php echo htmlspecialchars($j['full_name'] ?: $j['username']); ?></span>
                            </label>
                          <?php endforeach; ?>
                        </div>
                      </div>
                      <button type="submit" class="mt-2 bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white text-sm font-medium px-4 py-2 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Save Judge Assignments</button>
                    </form>
                    
                    <div class="flex gap-2 flex-wrap">
                      <?php if ($round['state'] === 'PENDING'): ?>
                        <form method="POST" class="inline">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="open">
                          <button name="toggle_round" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors" <?php if($final_blocked) echo 'disabled style="opacity:0.6;cursor:not-allowed;"'; ?>>
                            Open Round
                          </button>
                        </form>
                      <?php elseif ($round['state'] === 'OPEN'): ?>
                        <form method="POST" class="inline">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="close">
                          <button name="toggle_round" type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors" <?php if($final_blocked) echo 'disabled style="opacity:0.6;cursor:not-allowed;"'; ?>>
                            Close Round
                          </button>
                        </form>
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to revert this round to pending status?')">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="pending">
                          <button name="toggle_round" type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors" <?php if($final_blocked) echo 'disabled style="opacity:0.6;cursor:not-allowed;"'; ?>>
                            Revert to Pending
                          </button>
                        </form>
                      <?php elseif ($round['state'] === 'CLOSED'): ?>
                        <form method="POST" class="inline">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="finalize">
                          <button name="toggle_round" type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors" <?php if($final_blocked || $signing_blocked) echo 'disabled style="opacity:0.6;cursor:not-allowed;"'; ?>>
                            Finalize
                          </button>
                        </form>
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reopen this round?')">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="open">
                          <button name="toggle_round" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors" <?php if($final_blocked) echo 'disabled style="opacity:0.6;cursor:not-allowed;"'; ?>>
                            Reopen Round
                          </button>
                        </form>
                        <?php if (!$has_active_signing_session): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Start round signing so judges can confirm their scores?');">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <button name="start_round_signing" type="submit" class="bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white text-sm font-medium px-4 py-2 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">
                            Start Signing
                          </button>
                        </form>
                        <?php endif; ?>
                        <div class="inline ml-2 align-middle text-slate-200 text-xs">Judges must sign before finalizing.</div>
                      <?php elseif ($round['state'] === 'FINALIZED'): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to revert this finalized round to closed status? This action should only be done if there was an error.')">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="close">
                          <button name="toggle_round" type="submit" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Revert to Closed
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <h3 class="text-sm font-medium text-slate-900 mb-2">No rounds configured</h3>
                <p class="text-sm text-slate-500">Rounds are pre-configured for this pageant system.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    <!-- Information Panel -->
  <div class="mt-8 bg-white bg-opacity-10 border border-blue-400 border-opacity-20 rounded-xl p-6 backdrop-blur-md">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
          <h4 class="font-semibold text-blue-300 mb-2">Round Management Guide</h4>
          <div class="text-sm text-blue-200 space-y-2">
            <p>• <strong>Manage Criteria:</strong> Assign scoring criteria to rounds before opening them</p>
            <p>• <strong>Weight Validation:</strong> Criteria weights must sum to exactly 1.0 (100%)</p>
            <p>• <strong>Opening Rounds:</strong> Rounds can only be opened if they have criteria assigned</p>
            <p>• <strong>State Flow:</strong> PENDING → OPEN → CLOSED → FINALIZED</p>
            <p>• Use the <strong>"Manage Criteria"</strong> button to assign and configure scoring criteria for each round</p>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php
// Manage Criteria Modal
$modalId = 'manageCriteriaModal';
$title = 'Manage Scoring Criteria';
$bodyHtml = '<div class="space-y-6">'
  .'<div class="bg-white bg-opacity-10 border border-blue-400 border-opacity-20 rounded-lg p-4 backdrop-blur-md">'
    .'<div class="flex items-center gap-2 mb-2">'
      .'<svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
        .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
      .'</svg>'
      .'<h4 class="text-sm font-medium text-blue-300">Current Criteria</h4>'
    .'</div>';
$bodyHtml .= '<p class="text-sm text-blue-200">The following criteria are currently active for judging rounds:</p>';
$bodyHtml .= '</div>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';

// Round Details Modal
$modalId = 'roundDetailsModal';
$title = 'Round Details';
$bodyHtml = '<div class="space-y-6">'
  .'<div id="roundDetailsContent">'
    .'<!-- Content will be populated by JavaScript -->'
  .'</div>'
  .'<div class="pt-4">'
  .'<button onclick="hideModal(\'roundDetailsModal\')" class="w-full bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-6 py-3 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Close</button>'
  .'</div>'
  .'</div>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';

// Criteria Assignment Modal
$modalId = 'criteriaAssignmentModal';
$title = 'Manage Round Criteria';
$bodyHtml = '<form method="POST" id="criteriaAssignmentForm">'
  .'<input type="hidden" name="assign_criteria" value="1">'
  .'<input type="hidden" name="round_id" id="modalRoundId">'
  .'<div class="space-y-6">'
    .'<div class="bg-white bg-opacity-10 border border-blue-400 border-opacity-20 rounded-lg p-4 backdrop-blur-md">'
      .'<div class="flex items-center gap-2 mb-2">'
        .'<svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
          .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        .'</svg>'
        .'<h4 class="text-sm font-medium text-blue-300">Weight Instructions</h4>'
      .'</div>'
      .'<p class="text-sm text-blue-200">• Select criteria to assign to this round</p>'
      .'<p class="text-sm text-blue-200">• Assign weights (0.0 to 1.0) - total must equal 1.0</p>'
      .'<p class="text-sm text-blue-200">• Set maximum scores for each criterion</p>'
    .'</div>'
    .'<div id="criteriaList" class="space-y-4">'
      .'<!-- Criteria will be populated by JavaScript -->'
    .'</div>'
    .'<div class="bg-white bg-opacity-10 rounded-lg p-4">'
      .'<div class="flex justify-between items-center">'
        .'<span class="text-sm font-medium text-slate-200">Total Weight:</span>'
        .'<span id="totalWeight" class="text-lg font-bold text-white">0.0%</span>'
      .'</div>'
    .'</div>'
  .'</div>'
  .'<div class="flex gap-3 mt-6">'
    .'<button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-lg transition-colors">Save Criteria Assignment</button>'
  .'<button type="button" onclick="hideModal(\'criteriaAssignmentModal\')" class="flex-1 bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-6 py-3 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Cancel</button>'
  .'</div>'
  .'</form>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';
?>
<!-- Tooltip CSS for Final Round gating -->
<style>
  [data-tooltip] {
    position: relative;
    cursor: not-allowed;
  }
  [data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 50%;
    top: 100%;
    margin-top: 12px;
    transform: translateX(-50%);
    background: rgba(30,41,59,0.95);
    color: #fff;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 0.95rem;
    white-space: pre-line;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s;
    z-index: 50;
    min-width: 220px;
    text-align: center;
    box-shadow: 0 4px 16px 0 rgba(0,0,0,0.18);
  }
  [data-tooltip]:hover::after,
  [data-tooltip]:focus::after {
    opacity: 1;
  }
</style>

<script>
function toggleRoundJudgeChecks(roundId, check) {
  const boxes = document.querySelectorAll('input.round-judge-' + roundId + '[type="checkbox"]');
  boxes.forEach(cb => { cb.checked = !!check; });
}
// Available criteria data (populated from PHP)
const availableCriteria = <?php echo json_encode($criteria); ?>;

function manageCriteria(roundId, roundName) {
  document.getElementById('modalRoundId').value = roundId;
  
  // Update modal title - check different possible selectors
  const titleElement = document.querySelector('#criteriaAssignmentModal h3') || 
                       document.querySelector('#criteriaAssignmentModal .text-lg') ||
                       document.querySelector('#criteriaAssignmentModal [role="dialog"] h3');
  if (titleElement) {
    titleElement.textContent = `Manage Criteria - ${roundName}`;
  }
  
  // Load current criteria assignments for this round
  loadRoundCriteria(roundId);
  
  showModal('criteriaAssignmentModal');
}

function loadRoundCriteria(roundId) {
  // Make AJAX call to get current assignments
  fetch(`get_round_criteria.php?round_id=${roundId}`)
    .then(response => response.json())
    .then(data => {
      populateCriteriaList(data.assignments);
    })
    .catch(error => {
      console.error('Error loading criteria:', error);
      populateCriteriaList([]);
    });
}

function populateCriteriaList(currentAssignments = []) {
  const criteriaList = document.getElementById('criteriaList');
  criteriaList.innerHTML = '';
  
  // Create assignment lookup
  const assignmentMap = {};
  currentAssignments.forEach(assignment => {
    assignmentMap[assignment.criterion_id] = assignment;
  });
  
  // Group criteria by parent
  const parentGroups = {};
  const orphanCriteria = [];
  
  availableCriteria.forEach(criterion => {
    if (criterion.parent_criterion_id) {
      if (!parentGroups[criterion.parent_criterion_id]) {
        parentGroups[criterion.parent_criterion_id] = [];
      }
      parentGroups[criterion.parent_criterion_id].push(criterion);
    } else {
      orphanCriteria.push(criterion);
    }
  });
  
  // Render parent criteria with their children
  orphanCriteria.forEach(parentCriterion => {
    const children = parentGroups[parentCriterion.id] || [];
    
    // If this parent has children, render as group
    if (children.length > 0) {
      const parentHtml = `
        <div class="bg-slate-50 border border-slate-300 rounded-lg p-4 mb-4">
          <h3 class="text-lg font-semibold text-slate-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h2a2 2 0 012 2v0H8v0z"></path>
            </svg>
            ${parentCriterion.name}
          </h3>
          ${parentCriterion.description ? `<p class="text-sm text-slate-600 mb-4">${parentCriterion.description}</p>` : ''}
          <div class="space-y-3">
            ${children.map(child => {
              const assignment = assignmentMap[child.id];
              const isSelected = !!assignment;
              const weightDecimal = assignment ? parseFloat(assignment.weight) : 0;
              const weightPercent = (weightDecimal * 100).toFixed(1);
              const maxScore = assignment ? assignment.max_score : child.default_max_score;
              
              return `
                <div class="bg-white border border-slate-200 rounded-lg p-3 ml-4">
                  <div class="flex items-center gap-3 mb-3">
                    <input type="checkbox" 
                           name="criteria[${child.id}][selected]" 
                           value="1" 
                           ${isSelected ? 'checked' : ''}
                           onchange="updateCriteriaState(this)"
                           class="rounded">
                    <div class="flex-1">
                      <h4 class="font-medium text-slate-700">${child.name}</h4>
                      ${child.description ? `<p class="text-xs text-slate-500">${child.description}</p>` : ''}
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-slate-600 mb-1">Weight (%)</label>
                      <div class="relative">
                        <input type="number" 
                               name="criteria[${child.id}][weight]" 
                               value="${weightPercent}"
                               step="0.1" 
                               min="0" 
                               max="100"
                               ${!isSelected ? 'disabled' : ''}
                               onchange="updateTotalWeight()"
                               class="w-full px-3 py-2 pr-8 border border-slate-300 rounded-lg text-sm">
                        <span class="absolute right-3 top-2 text-slate-500 text-sm">%</span>
                      </div>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-600 mb-1">Max Score</label>
                      <input type="number" 
                             name="criteria[${child.id}][max_score]" 
                             value="${maxScore}"
                             step="0.01" 
                             min="1"
                             ${!isSelected ? 'disabled' : ''}
                             class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        </div>
      `;
      criteriaList.insertAdjacentHTML('beforeend', parentHtml);
    } else {
      // Render as standalone criterion
      const assignment = assignmentMap[parentCriterion.id];
      const isSelected = !!assignment;
      const weightDecimal = assignment ? parseFloat(assignment.weight) : 0;
      const weightPercent = (weightDecimal * 100).toFixed(1);
      const maxScore = assignment ? assignment.max_score : parentCriterion.default_max_score;
      
      const criterionHtml = `
        <div class="border border-slate-200 rounded-lg p-4 mb-4">
          <div class="flex items-center gap-3 mb-3">
            <input type="checkbox" 
                   name="criteria[${parentCriterion.id}][selected]" 
                   value="1" 
                   ${isSelected ? 'checked' : ''}
                   onchange="updateCriteriaState(this)"
                   class="rounded">
            <div class="flex-1">
              <h4 class="font-medium text-slate-800">${parentCriterion.name}</h4>
              ${parentCriterion.description ? `<p class="text-sm text-slate-600">${parentCriterion.description}</p>` : ''}
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Weight (%)</label>
              <div class="relative">
                <input type="number" 
                       name="criteria[${parentCriterion.id}][weight]" 
                       value="${weightPercent}"
                       step="0.1" 
                       min="0" 
                       max="100"
                       ${!isSelected ? 'disabled' : ''}
                       onchange="updateTotalWeight()"
                       class="w-full px-3 py-2 pr-8 border border-slate-300 rounded-lg text-sm">
                <span class="absolute right-3 top-2 text-slate-500 text-sm">%</span>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Max Score</label>
              <input type="number" 
                     name="criteria[${parentCriterion.id}][max_score]" 
                     value="${maxScore}"
                     step="0.01" 
                     min="1"
                     ${!isSelected ? 'disabled' : ''}
                     class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
          </div>
        </div>
      `;
      criteriaList.insertAdjacentHTML('beforeend', criterionHtml);
    }
  });
  
  updateTotalWeight();
}

function updateCriteriaState(checkbox) {
  const criteriaContainer = checkbox.closest('.border');
  const inputs = criteriaContainer.querySelectorAll('input[type="number"]');
  
  inputs.forEach(input => {
    input.disabled = !checkbox.checked;
    if (!checkbox.checked) {
      input.value = input.name.includes('weight') ? '0.000' : input.defaultValue;
    }
  });
  
  updateTotalWeight();
}

function updateTotalWeight() {
  const weightInputs = document.querySelectorAll('input[name*="[weight]"]:not(:disabled)');
  let total = 0;
  
  weightInputs.forEach(input => {
    total += parseFloat(input.value) || 0;
  });
  
  const totalElement = document.getElementById('totalWeight');
  totalElement.textContent = total.toFixed(1) + '%';
  
  // Visual feedback for weight validation (should equal 100%)
  if (Math.abs(total - 100.0) < 0.1) {
    totalElement.className = 'text-lg font-bold text-green-600';
  } else {
    totalElement.className = 'text-lg font-bold text-red-600';
  }
}

function viewRoundDetails(roundId, roundName, state, criteriaCount) {
  const content = document.getElementById('roundDetailsContent');
  
  let stateClass = '';
  let stateText = state;
  switch (state) {
    case 'OPEN':
      stateClass = 'bg-blue-100 text-blue-800';
      break;
    case 'CLOSED':
    case 'FINALIZED':
      stateClass = 'bg-green-100 text-green-800';
      break;
    default:
      stateClass = 'bg-slate-100 text-slate-600';
  }
  
  content.innerHTML = `
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-800">${roundName}</h3>
        <span class="px-3 py-1 text-sm font-medium rounded-full ${stateClass}">
          ${stateText}
        </span>
      </div>
      
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">Round ID</label>
          <p class="text-sm text-slate-800">#${roundId}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">Status</label>
          <p class="text-sm text-slate-800">${stateText}</p>
        </div>
      </div>
      
      <div class="mb-4">
        <label class="block text-sm font-medium text-slate-600 mb-1">Assigned Criteria</label>
        <p class="text-sm text-slate-800">${criteriaCount} criteria assigned for scoring</p>
      </div>
      
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <h4 class="text-sm font-medium text-blue-800">Round Information</h4>
        </div>
        <p class="text-sm text-blue-700">This round uses the configured scoring criteria and allows judges to evaluate participants based on the assigned standards.</p>
      </div>
    </div>
  `;
  
  showModal('roundDetailsModal');
}
</script>

<?php if (isset($show_success_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showSuccess('Success!', '<?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>');
});
</script>
<?php endif; ?>

<?php if (isset($show_error_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showError('Error!', '<?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>');
});
</script>
<?php endif; ?>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
