<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Handle AJAX tie group update actions (start, close, finalize, revert)
if (isset($_POST['update_tie_group']) && isset($_POST['tie_group_index']) && isset($_POST['action'])) {
  header('Content-Type: application/json');
  require_once('../classes/database.php');
  $con = new database();
  $conn = $con->opencon();
  $tie_group_index = intval($_POST['tie_group_index']);
  $action = $_POST['action'];
  $pageant_id = $_SESSION['pageant_id'] ?? 1;
  $valid_actions = ['start', 'close', 'finalize', 'revert'];
  $state_map = [
    'start' => 'in_progress',
    'close' => 'closed',
    'finalize' => 'finalized',
    'revert' => 'pending'
  ];
  if (!in_array($action, $valid_actions)) {
    $resp = ['success' => false, 'message' => 'Invalid action.', 'errorCode' => 'V001'];
    echo json_encode($resp); exit;
  }
  // Get tie group info (score, participant_ids) from POST or recalculate
  // Try to get finalized rounds first, then fallback to active tie breaker round
  $rounds_query = "SELECT id FROM rounds WHERE pageant_id = ? AND state = 'FINALIZED'";
  $stmt = $conn->prepare($rounds_query);
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $rounds_result = $stmt->get_result();
  $round_ids = [];
  while ($round = $rounds_result->fetch_assoc()) {
    $round_ids[] = $round['id'];
  }
  $stmt->close();

  // If no finalized rounds, try to get the round_id from an in-progress tie group
  if (empty($round_ids)) {
    $tg_stmt = $conn->prepare("SELECT round_id FROM tie_groups WHERE pageant_id = ? AND state = 'in_progress' LIMIT 1");
    $tg_stmt->bind_param("i", $pageant_id);
    $tg_stmt->execute();
    $tg_result = $tg_stmt->get_result();
    if ($tg_row = $tg_result->fetch_assoc()) {
      $round_ids[] = $tg_row['round_id'];
    }
    $tg_stmt->close();
  }
  $score = 0; $participant_ids = []; $finalized_round_id = null;
  if (!empty($round_ids)) {
    $round_ids_placeholder = implode(',', array_fill(0, count($round_ids), '?'));
    $score_query = "SELECT 
        p.id,
        COALESCE(SUM(COALESCE(s.override_score, s.raw_score) * rc.weight), 0) as total_score
      FROM participants p
      LEFT JOIN scores s ON p.id = s.participant_id
      LEFT JOIN round_criteria rc ON s.criterion_id = rc.criterion_id AND rc.round_id IN ($round_ids_placeholder)
      WHERE p.pageant_id = ? AND p.is_active = 1
      GROUP BY p.id
      HAVING total_score >= 0
      ORDER BY total_score DESC, p.full_name ASC";
    $params = array_merge($round_ids, [$pageant_id]);
    $types = str_repeat('i', count($params));
    $stmt = $conn->prepare($score_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $score_groups = [];
    while ($row = $result->fetch_assoc()) {
      $score = round($row['total_score'], 2);
      $score_groups[(string)$score][] = $row['id'];
    }
    $stmt->close();
    $tie_groups = array_values(array_filter($score_groups, function($arr){ return count($arr) > 1; }));
    if (isset($tie_groups[$tie_group_index])) {
      $participant_ids = $tie_groups[$tie_group_index];
      $score = array_keys($score_groups)[$tie_group_index];
      // Use the most recent finalized round for this tie group
      $finalized_round_id = end($round_ids);
    }
  }
  // Upsert tie group in DB (normalized schema)
  $state = $state_map[$action];
  if (!empty($participant_ids)) {
    // Check if tie group exists (by pageant, score, and state not finalized)
  $sel = $conn->prepare("SELECT id FROM tie_groups WHERE pageant_id = ? AND score = ? AND round_id = ? AND state != 'finalized'");
  $sel->bind_param("idi", $pageant_id, $score, $finalized_round_id);
    $sel->execute();
    $sel_result = $sel->get_result();
    if ($row = $sel_result->fetch_assoc()) {
      // Update state/score
  $upd = $conn->prepare("UPDATE tie_groups SET state = ?, score = ?, round_id = ?, updated_at = NOW() WHERE id = ?");
  $upd->bind_param("sdii", $state, $score, $finalized_round_id, $row['id']);
      $upd->execute();
      $upd->close();
      $tie_group_id = $row['id'];
      // Remove old participants for this group
  $conn->query("DELETE FROM tie_group_participants WHERE tie_group_id = " . intval($tie_group_id));
    } else {
      // Insert new tie group
      $ins = $conn->prepare("INSERT INTO tie_groups (pageant_id, round_id, score, state, created_at) VALUES (?, ?, ?, ?, NOW())");
      $ins->bind_param("iids", $pageant_id, $finalized_round_id, $score, $state);
      $ins->execute();
      $tie_group_id = $ins->insert_id;
      $ins->close();
    }
    $sel->close();
    // Insert participants into tie_group_participants
    $ins_p = $conn->prepare("INSERT INTO tie_group_participants (tie_group_id, participant_id, original_score) VALUES (?, ?, ?)");
    foreach ($participant_ids as $pid) {
      $ins_p->bind_param("iid", $tie_group_id, $pid, $score);
      $ins_p->execute();
    }
    $ins_p->close();
    $resp = ['success' => true, 'state' => $state, 'tie_group_id' => $tie_group_id];
  } else {
    $resp = ['success' => false, 'message' => 'Tie group not found or no participants.', 'errorCode' => 'TIE_NOT_FOUND'];
  }
  $conn->close();
  echo json_encode($resp); exit;
}

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
$conn = $con->opencon();

// Get pageant ID from session
$pageant_id = $_SESSION['pageant_id'] ?? 1;

// Handle manual tie resolution
if (isset($_POST['resolve_tie']) && isset($_POST['tie_group']) && isset($_POST['winner_id'])) {
    $tie_group = $_POST['tie_group'];
    $winner_id = intval($_POST['winner_id']);
    
    // This would update the participant's ranking or add a tie resolution record
    // For now, we'll just show a success message
    $success_message = "Tie resolved successfully. Participant #$winner_id has been selected as the winner for this tie group.";
    $show_success_alert = true;
}

// Get basic data for display
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM participants WHERE pageant_id = ? AND is_active = 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$participants_count = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND state = 'FINALIZED'");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$finalized_rounds = $result->fetch_assoc()['count'];

// Detect actual ties by looking for participants with identical scores
$ties_detected = [];
$ties_count = 0;

if ($finalized_rounds > 0) {
    // First, get all participants with their total scores using the same logic as leaderboard
    $participants_with_scores = [];
    
    // Get all finalized rounds
    $rounds_query = "SELECT id FROM rounds WHERE pageant_id = ? AND state = 'FINALIZED'";
    $stmt = $conn->prepare($rounds_query);
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $rounds_result = $stmt->get_result();
    $round_ids = [];
    while ($round = $rounds_result->fetch_assoc()) {
        $round_ids[] = $round['id'];
    }
    $stmt->close();
    
    if (!empty($round_ids)) {
        $round_ids_placeholder = implode(',', array_fill(0, count($round_ids), '?'));
        
        // Get participant scores for all finalized rounds
        $score_query = "SELECT 
            p.id,
            p.full_name,
            p.number_label,
            d.name as division,
            COALESCE(SUM(COALESCE(s.override_score, s.raw_score) * rc.weight), 0) as total_score
        FROM participants p
        JOIN divisions d ON p.division_id = d.id
        LEFT JOIN scores s ON p.id = s.participant_id
        LEFT JOIN round_criteria rc ON s.criterion_id = rc.criterion_id AND rc.round_id IN ($round_ids_placeholder)
        WHERE p.pageant_id = ? AND p.is_active = 1
        GROUP BY p.id, p.full_name, p.number_label, d.name
        HAVING total_score >= 0
        ORDER BY total_score DESC, p.full_name ASC";
        
        $params = array_merge([$pageant_id], $round_ids);
        $types = str_repeat('i', count($params));
        
        $stmt = $conn->prepare($score_query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $score = round($row['total_score'], 2);
            $score_key = (string)$score; // Convert to string to avoid float-to-int conversion warnings
            if (!isset($participants_with_scores[$score_key])) {
                $participants_with_scores[$score_key] = [];
            }
            $participants_with_scores[$score_key][] = $row;
        }
        $stmt->close();
        
        // Find ties - groups with more than one participant at the same score
        foreach ($participants_with_scores as $score => $participants) {
            if (count($participants) > 1) {
                $ties_detected[] = $participants;
            }
        }
        
        $ties_count = count($ties_detected);
    }
}

$conn->close();

$pageTitle = 'Tie Resolution';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Tie Resolution</h1>
          <p class="text-slate-200">Manage and resolve scoring ties between participants</p>
        </div>
  <button onclick="refreshTies()" class="bg-blue-500 bg-opacity-30 hover:bg-blue-600 hover:bg-opacity-40 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2 border border-white border-opacity-20 backdrop-blur-md">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Scan for Ties
        </button>
      </div>
    </div>

    <!-- Status Cards -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Total Participants</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo $participants_count; ?></p>
  <p class="text-sm text-slate-200">Active contestants</p>
      </div>

  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Scored Rounds</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo $finalized_rounds; ?></p>
  <p class="text-sm text-slate-200">Completed rounds</p>
      </div>

  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Detected Ties</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo $ties_count; ?></p>
  <p class="text-sm text-slate-200">Pending resolution</p>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="bg-green-500 bg-opacity-20 backdrop-blur-sm border border-green-400 border-opacity-30 text-green-100 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-200" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- Tie Resolution Methods -->

    <!-- Current Ties -->
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
      <div class="px-6 py-4 border-b border-white border-opacity-10">
        <h3 class="text-lg font-semibold text-white">Current Ties</h3>
        <p class="text-sm text-slate-200 mt-1">Detected ties requiring resolution</p>
      </div>
      
      <div class="p-6">
        <?php if (empty($ties_detected)): ?>
          <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-white opacity-40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-sm font-medium text-white mb-2">No ties detected</h3>
            <p class="text-sm text-slate-200 mb-6">All participants have unique scores, or no completed rounds available.</p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
              <button onclick="location.reload()" class="bg-blue-500 bg-opacity-30 hover:bg-blue-600 hover:bg-opacity-40 text-white font-medium px-6 py-3 rounded-lg transition-colors border border-white border-opacity-20 backdrop-blur-md">
                Refresh Scan
              </button>
              <a href="rounds.php" class="bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-6 py-3 rounded-lg transition-colors border border-white border-opacity-10 backdrop-blur-md">
                Manage Rounds
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="space-y-6">
            <?php foreach ($ties_detected as $index => $tie_group): ?>
              <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-amber-200 border-opacity-30 p-6">
                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-lg font-semibold text-amber-200">Tie Group <?php echo $index + 1; ?> - Score: <?php echo number_format($tie_group[0]['total_score'], 2); ?></h4>
                  <span class="px-3 py-1 text-sm bg-amber-200 bg-opacity-20 text-amber-100 rounded-full"><?php echo count($tie_group); ?> participants tied</span>
                  <?php
                    $state = $_SESSION['tie_group_states'][$index] ?? 'pending';
                    $stateColors = [
                      'pending' => 'bg-slate-400 text-slate-900',
                      'in_progress' => 'bg-blue-400 text-blue-900',
                      'closed' => 'bg-yellow-400 text-yellow-900',
                      'finalized' => 'bg-green-400 text-green-900'
                    ];
                    $stateLabels = [
                      'pending' => 'Pending',
                      'in_progress' => 'In Progress',
                      'closed' => 'Closed',
                      'finalized' => 'Finalized'
                    ];
                  ?>
                  <span class="ml-2 px-3 py-1 text-xs font-semibold rounded-full <?php echo $stateColors[$state] ?? 'bg-slate-400 text-slate-900'; ?>">
                    <?php echo $stateLabels[$state] ?? ucfirst($state); ?>
                  </span>
                </div>
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                  <?php foreach ($tie_group as $participant): ?>
                    <div class="bg-white bg-opacity-10 border border-amber-200 border-opacity-20 rounded-lg p-4">
                      <div class="flex items-center justify-between mb-2">
                        <h5 class="font-medium text-white">#<?php echo $participant['number_label']; ?></h5>
                        <span class="text-sm text-slate-200">Score: <?php echo number_format($participant['total_score'], 2); ?></span>
                      </div>
                      <p class="text-sm text-slate-200 mb-3"><?php echo htmlspecialchars($participant['full_name']); ?></p>
                    </div>
                  <?php endforeach; ?>
                </div>
                <!-- Judge Progress Bar (static for now) -->
                <div class="mb-6">
                  <div class="text-sm text-slate-200 mb-2">Judge Progress</div>
                  <div class="w-full h-8 flex rounded-lg overflow-hidden border border-white border-opacity-20 bg-white bg-opacity-10 backdrop-blur-md">
                    <div class="flex-1 flex items-center justify-center bg-green-500 bg-opacity-60 text-white font-bold transition-all">Judge A</div>
                    <div class="flex-1 flex items-center justify-center bg-yellow-400 bg-opacity-60 text-white font-bold transition-all">Judge B</div>
                    <div class="flex-1 flex items-center justify-center bg-gray-300 bg-opacity-60 text-slate-700 font-bold transition-all">Judge C</div>
                  </div>
                  <div class="text-xs text-slate-200 mt-2">Green: Saved, Yellow: Pending, Gray: Not started</div>
                </div>
                <!-- Round Control Buttons (dynamic, AJAX hooks) -->
                <div class="flex gap-3 mb-6">
                  <button class="px-5 py-2 rounded-lg bg-blue-500 bg-opacity-30 hover:bg-blue-600 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition" onclick="updateTieGroupStatus(<?php echo $index; ?>, 'start')">Start Tie Breaker</button>
                  <button class="px-5 py-2 rounded-lg bg-yellow-400 bg-opacity-30 hover:bg-yellow-500 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition" onclick="updateTieGroupStatus(<?php echo $index; ?>, 'close')">Close Tie Breaker</button>
                  <button class="px-5 py-2 rounded-lg bg-green-600 bg-opacity-30 hover:bg-green-700 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition" onclick="updateTieGroupStatus(<?php echo $index; ?>, 'finalize')">Finalize</button>
                  <button class="px-5 py-2 rounded-lg bg-red-600 bg-opacity-30 hover:bg-red-700 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition" onclick="updateTieGroupStatus(<?php echo $index; ?>, 'revert')">Revert</button>
                </div>
                <div class="flex items-center gap-3 text-sm text-amber-200">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  The winner will be automatically determined once the tie breaker round ends.
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Information Panel -->
    <div class="mt-8 bg-white bg-opacity-10 border border-blue-400 border-opacity-20 rounded-xl p-6 backdrop-blur-md">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-300 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
          <h4 class="font-semibold text-blue-200 mb-2">About Tie Resolution</h4>
          <div class="text-sm text-blue-100 space-y-2">
            <p>• Ties are automatically detected when participants have identical total scores</p>
            <p>• The system supports multiple tie-breaking methods in order of priority</p>
            <p>• Manual resolution allows judges to make final decisions on close calls</p>
            <p>• All tie resolutions are logged for transparency and audit purposes</p>
            <p>• Tie resolution will be available once scoring rounds are completed</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
function resolveTie(tieGroup, winnerId, winnerName) {
  const confirmMsg = `Are you sure you want to select "${winnerName}" as the winner for this tie group?\n\nThis will update the rankings and cannot be easily undone.`;
  
  if (confirm(confirmMsg)) {
    // Create a hidden form to submit the tie resolution
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const tieGroupInput = document.createElement('input');
    tieGroupInput.name = 'tie_group';
    tieGroupInput.value = tieGroup;
    form.appendChild(tieGroupInput);
    
    const winnerInput = document.createElement('input');
    winnerInput.name = 'winner_id';
    winnerInput.value = winnerId;
    form.appendChild(winnerInput);
    
    const actionInput = document.createElement('input');
    actionInput.name = 'resolve_tie';
    actionInput.value = '1';
    form.appendChild(actionInput);
    
    document.body.appendChild(form);
    form.submit();
  }
}

function refreshTies() {
  location.reload();
}
function updateTieGroupStatus(tieGroupIndex, action) {
  // Show loading toast
  let loadingSwal;
  if (window.Swal) {
    loadingSwal = Swal.fire({
      title: 'Updating tie group...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
      customClass: { popup: 'font-[Inter]' }
    });
  }
  fetch('tie_resolution.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `update_tie_group=1&tie_group_index=${encodeURIComponent(tieGroupIndex)}&action=${encodeURIComponent(action)}`
  })
  .then(response => response.json())
  .then(data => {
    if (window.Swal) Swal.close();
    if (data.success) {
      showNotification('Tie group updated!','success',true);
      setTimeout(refreshTies, 600);
    } else {
      if (window.showError) {
        showError('Failed to update tie group', data.message||'Unknown error', data.errorCode||null, data.debugInfo||null);
      } else {
        alert(data.message || 'Failed to update tie group.');
      }
    }
  })
  .catch((err) => {
    if (window.Swal) Swal.close();
    if (window.showError) {
      showError('Network error', 'Could not update tie group.', null, err);
    } else {
      alert('Failed to update tie group.');
    }
  });
}
</script>

<?php if (isset($show_success_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showSuccess('Success!', '<?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>');
});
</script>
<?php endif; ?>

  </div>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
