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
$conn = $con->opencon();

// Get pageant ID from session
$pageant_id = $_SESSION['pageant_id'] ?? 1; // Use consistent session variable

// Handle round state changes
if (isset($_POST['toggle_round'])) {
    $round_id = $_POST['round_id'];
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
    
    $stmt = $conn->prepare("UPDATE rounds SET state = ?, opened_at = CASE WHEN ? = 'OPEN' THEN NOW() ELSE opened_at END, closed_at = CASE WHEN ? IN ('CLOSED', 'FINALIZED') THEN NOW() ELSE closed_at END WHERE id = ?");
    $stmt->bind_param("sssi", $new_state, $new_state, $new_state, $round_id);
    
    if ($stmt->execute()) {
        $success_message = "Round status updated successfully.";
        $show_success_alert = true;
    } else {
        $error_message = "Error updating round status.";
        $show_error_alert = true;
    }
    $stmt->close();
}

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

// Fetch criteria for the pageant
$stmt = $conn->prepare("SELECT * FROM criteria WHERE pageant_id = ? AND is_active = 1 ORDER BY sort_order");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$criteria = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$total_rounds = count($rounds);
$open_rounds = count(array_filter($rounds, fn($r) => $r['state'] === 'OPEN'));
$closed_rounds = count(array_filter($rounds, fn($r) => in_array($r['state'], ['CLOSED', 'FINALIZED'])));

$conn->close();

$pageTitle = 'Rounds & Criteria';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="bg-slate-50 min-h-screen">
  <div class="mx-auto max-w-7xl px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 mb-2">Rounds & Criteria</h1>
          <p class="text-slate-600">Manage judging rounds and scoring criteria</p>
        </div>
        <div class="flex gap-3">
          <button onclick="showModal('manageCriteriaModal')" class="bg-slate-600 hover:bg-slate-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Manage Criteria
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Total Rounds</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo $total_rounds; ?></p>
        <p class="text-sm text-slate-600">Created rounds</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Open Rounds</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo $open_rounds; ?></p>
        <p class="text-sm text-slate-600">Currently judging</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Completed</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo $closed_rounds; ?></p>
        <p class="text-sm text-slate-600">Finished rounds</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Criteria</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo count($criteria); ?></p>
        <p class="text-sm text-slate-600">Scoring criteria</p>
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
    <div class="grid lg:grid-cols-3 gap-8">
      <!-- Rounds List -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
          <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-800">Judging Rounds</h3>
            <p class="text-sm text-slate-600 mt-1">Control round status and monitor progress</p>
          </div>
          
          <div class="p-6">
            <?php if (!empty($rounds)): ?>
              <div class="space-y-4">
                <?php foreach ($rounds as $round): ?>
                  <div class="border border-slate-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                      <div>
                        <h4 class="text-lg font-semibold text-slate-800"><?php echo htmlspecialchars($round['name']); ?></h4>
                        <p class="text-sm text-slate-600">Round <?php echo $round['sequence']; ?> • <?php echo $round['criteria_count']; ?> criteria assigned</p>
                      </div>
                      <span class="px-3 py-1 text-sm font-medium rounded-full <?php 
                        switch ($round['state']) {
                          case 'OPEN':
                            echo 'bg-blue-100 text-blue-800';
                            break;
                          case 'CLOSED':
                          case 'FINALIZED':
                            echo 'bg-green-100 text-green-800';
                            break;
                          default:
                            echo 'bg-slate-100 text-slate-600';
                        }
                      ?>">
                        <?php echo $round['state']; ?>
                      </span>
                    </div>
                    
                    <div class="mb-4">
                      <p class="text-sm text-slate-600">
                        <strong>Type:</strong> <?php echo ucfirst(strtolower($round['scoring_mode'])); ?> Round
                        <?php if ($round['advancement_limit']): ?>
                          • <strong>Advances:</strong> Top <?php echo $round['advancement_limit']; ?>
                        <?php endif; ?>
                      </p>
                      <?php if ($round['opened_at']): ?>
                        <p class="text-sm text-slate-600 mt-1">
                          <strong>Opened:</strong> <?php echo date('M j, Y g:i A', strtotime($round['opened_at'])); ?>
                        </p>
                      <?php endif; ?>
                      <?php if ($round['closed_at']): ?>
                        <p class="text-sm text-slate-600 mt-1">
                          <strong>Closed:</strong> <?php echo date('M j, Y g:i A', strtotime($round['closed_at'])); ?>
                        </p>
                      <?php endif; ?>
                    </div>
                    
                    <div class="flex gap-2 flex-wrap">
                      <?php if ($round['state'] === 'PENDING'): ?>
                        <form method="POST" class="inline">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="open">
                          <button name="toggle_round" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Open Round
                          </button>
                        </form>
                      <?php elseif ($round['state'] === 'OPEN'): ?>
                        <form method="POST" class="inline">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="close">
                          <button name="toggle_round" type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Close Round
                          </button>
                        </form>
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to revert this round to pending status?')">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="pending">
                          <button name="toggle_round" type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Revert to Pending
                          </button>
                        </form>
                      <?php elseif ($round['state'] === 'CLOSED'): ?>
                        <form method="POST" class="inline">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="finalize">
                          <button name="toggle_round" type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Finalize
                          </button>
                        </form>
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reopen this round?')">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="open">
                          <button name="toggle_round" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Reopen Round
                          </button>
                        </form>
                      <?php elseif ($round['state'] === 'FINALIZED'): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to revert this finalized round to closed status? This action should only be done if there was an error.')">
                          <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
                          <input type="hidden" name="action" value="close">
                          <button name="toggle_round" type="submit" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            Revert to Closed
                          </button>
                        </form>
                      <?php endif; ?>
                      <button onclick="viewRoundDetails(<?php echo $round['id']; ?>, '<?php echo htmlspecialchars($round['name'], ENT_QUOTES); ?>', '<?php echo $round['state']; ?>', '<?php echo $round['criteria_count']; ?>')" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        View Details
                      </button>
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

      <!-- Criteria Panel -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
          <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-800">Scoring Criteria</h3>
            <p class="text-sm text-slate-600 mt-1">Active judging criteria</p>
          </div>
          
          <div class="p-6">
            <?php if (!empty($criteria)): ?>
              <div class="space-y-4">
                <?php foreach ($criteria as $criterion): ?>
                  <div class="border border-slate-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="font-medium text-slate-800"><?php echo htmlspecialchars($criterion['name']); ?></h4>
                      <span class="text-sm text-slate-600"><?php echo $criterion['default_max_score']; ?> pts</span>
                    </div>
                    <?php if ($criterion['description']): ?>
                      <p class="text-sm text-slate-600"><?php echo htmlspecialchars($criterion['description']); ?></p>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-8">
                <svg class="mx-auto h-8 w-8 text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm text-slate-500">No criteria configured</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php
// Manage Criteria Modal
$modalId = 'manageCriteriaModal';
$title = 'Manage Scoring Criteria';
$bodyHtml = '<div class="space-y-6">'
  .'<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">'
    .'<div class="flex items-center gap-2 mb-2">'
      .'<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
        .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
      .'</svg>'
      .'<h4 class="text-sm font-medium text-blue-800">Current Criteria</h4>'
    .'</div>'
    .'<p class="text-sm text-blue-700">The following criteria are currently active for judging rounds:</p>'
  .'</div>';

if (!empty($criteria)) {
    $bodyHtml .= '<div class="space-y-3">';
    foreach ($criteria as $criterion) {
        $bodyHtml .= '<div class="border border-slate-200 rounded-lg p-4">'
          .'<div class="flex items-center justify-between mb-2">'
            .'<h4 class="font-medium text-slate-800">'.htmlspecialchars($criterion['name']).'</h4>'
            .'<span class="text-sm text-slate-600">'.$criterion['default_max_score'].' points</span>'
          .'</div>';
        if ($criterion['description']) {
            $bodyHtml .= '<p class="text-sm text-slate-600">'.htmlspecialchars($criterion['description']).'</p>';
        }
        $bodyHtml .= '</div>';
    }
    $bodyHtml .= '</div>';
} else {
    $bodyHtml .= '<div class="text-center py-8">'
      .'<svg class="mx-auto h-8 w-8 text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
        .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'
      .'</svg>'
      .'<p class="text-sm text-slate-500">No criteria configured</p>'
    .'</div>';
}

$bodyHtml .= '<div class="pt-4">'
    .'<button onclick="hideModal(\'manageCriteriaModal\')" class="w-full bg-slate-200 hover:bg-slate-300 text-slate-800 font-medium px-6 py-3 rounded-lg transition-colors">Close</button>'
  .'</div>'
  .'</div>';
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
    .'<button onclick="hideModal(\'roundDetailsModal\')" class="w-full bg-slate-200 hover:bg-slate-300 text-slate-800 font-medium px-6 py-3 rounded-lg transition-colors">Close</button>'
  .'</div>'
  .'</div>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';
?>

<script>
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

<?php include __DIR__ . '/../partials/footer.php'; ?>
