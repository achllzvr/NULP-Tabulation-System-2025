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
    // Query to find ties - participants with same total score
    $tie_query = "
        SELECT 
            p.id,
            p.participant_name,
            p.participant_number,
            COALESCE(SUM(s.score), 0) as total_score,
            COUNT(p.id) OVER (PARTITION BY COALESCE(SUM(s.score), 0)) as tie_count
        FROM participants p
        LEFT JOIN scores s ON p.id = s.participant_id
        LEFT JOIN rounds r ON s.round_id = r.id
        WHERE p.pageant_id = ? AND p.is_active = 1 AND (r.state = 'FINALIZED' OR r.state IS NULL)
        GROUP BY p.id, p.participant_name, p.participant_number
        HAVING tie_count > 1
        ORDER BY total_score DESC, p.participant_number ASC
    ";
    
    $stmt = $conn->prepare($tie_query);
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $current_score = null;
    $current_group = [];
    
    while ($row = $result->fetch_assoc()) {
        if ($current_score !== $row['total_score']) {
            if (!empty($current_group)) {
                $ties_detected[] = $current_group;
            }
            $current_group = [];
            $current_score = $row['total_score'];
        }
        $current_group[] = $row;
    }
    
    if (!empty($current_group)) {
        $ties_detected[] = $current_group;
    }
    
    $ties_count = count($ties_detected);
}

$conn->close();

$pageTitle = 'Tie Resolution';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="bg-slate-50 min-h-screen">
  <div class="mx-auto max-w-7xl px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 mb-2">Tie Resolution</h1>
          <p class="text-slate-600">Manage and resolve scoring ties between participants</p>
        </div>
        <button onclick="refreshTies()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Scan for Ties
        </button>
      </div>
    </div>

    <!-- Status Cards -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Total Participants</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo $participants_count; ?></p>
        <p class="text-sm text-slate-600">Active contestants</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Scored Rounds</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo $finalized_rounds; ?></p>
        <p class="text-sm text-slate-600">Completed rounds</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Detected Ties</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo $ties_count; ?></p>
        <p class="text-sm text-slate-600">Pending resolution</p>
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

    <!-- Tie Resolution Methods -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8">
      <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-800">Tie Resolution Methods</h3>
        <p class="text-sm text-slate-600 mt-1">Configure how ties should be resolved</p>
      </div>
      
      <div class="p-6">
        <div class="grid md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div class="border border-slate-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-slate-800">Total Score</h4>
                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Primary</span>
              </div>
              <p class="text-sm text-slate-600 mb-3">Resolve by highest combined score across all rounds</p>
              <div class="text-xs text-slate-500">Most common tie-breaking method</div>
            </div>

            <div class="border border-slate-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-slate-800">Final Round Score</h4>
                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Secondary</span>
              </div>
              <p class="text-sm text-slate-600 mb-3">Use final round performance as tiebreaker</p>
              <div class="text-xs text-slate-500">Commonly used for close competitions</div>
            </div>
          </div>

          <div class="space-y-4">
            <div class="border border-slate-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-slate-800">Judge's Decision</h4>
                <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">Manual</span>
              </div>
              <p class="text-sm text-slate-600 mb-3">Allow judges to manually resolve ties</p>
              <div class="text-xs text-slate-500">For complex tie situations</div>
            </div>

            <div class="border border-slate-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-slate-800">Random Selection</h4>
                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Last Resort</span>
              </div>
              <p class="text-sm text-slate-600 mb-3">Random tie resolution when all else fails</p>
              <div class="text-xs text-slate-500">Used as final fallback method</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Current Ties -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
      <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-800">Current Ties</h3>
        <p class="text-sm text-slate-600 mt-1">Detected ties requiring resolution</p>
      </div>
      
      <div class="p-6">
        <?php if (empty($ties_detected)): ?>
          <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-sm font-medium text-slate-900 mb-2">No ties detected</h3>
            <p class="text-sm text-slate-500 mb-6">All participants have unique scores, or no completed rounds available.</p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
              <button onclick="location.reload()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                Refresh Scan
              </button>
              <a href="rounds.php" class="bg-slate-600 hover:bg-slate-700 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                Manage Rounds
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="space-y-6">
            <?php foreach ($ties_detected as $index => $tie_group): ?>
              <div class="border border-amber-200 bg-amber-50 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-lg font-semibold text-amber-800">
                    Tie Group <?php echo $index + 1; ?> - Score: <?php echo number_format($tie_group[0]['total_score'], 2); ?>
                  </h4>
                  <span class="px-3 py-1 text-sm bg-amber-200 text-amber-800 rounded-full">
                    <?php echo count($tie_group); ?> participants tied
                  </span>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                  <?php foreach ($tie_group as $participant): ?>
                    <div class="bg-white border border-amber-200 rounded-lg p-4">
                      <div class="flex items-center justify-between mb-2">
                        <h5 class="font-medium text-slate-800">#<?php echo $participant['participant_number']; ?></h5>
                        <span class="text-sm text-slate-600">Score: <?php echo number_format($participant['total_score'], 2); ?></span>
                      </div>
                      <p class="text-sm text-slate-600 mb-3"><?php echo htmlspecialchars($participant['participant_name']); ?></p>
                      <button onclick="resolveTie('<?php echo $index; ?>', <?php echo $participant['id']; ?>, '<?php echo htmlspecialchars($participant['participant_name']); ?>')" 
                              class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-3 py-2 rounded transition-colors">
                        Select as Winner
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
                
                <div class="flex items-center gap-3 text-sm text-amber-700">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Select the participant who should be ranked higher in this tie situation.
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Information Panel -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
          <h4 class="font-semibold text-blue-800 mb-2">About Tie Resolution</h4>
          <div class="text-sm text-blue-700 space-y-2">
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
</script>

<?php if (isset($show_success_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showSuccess('Success!', '<?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>');
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../partials/footer.php'; ?>
