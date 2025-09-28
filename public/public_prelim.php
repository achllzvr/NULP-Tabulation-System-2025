<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Public Preliminary';
$pid = isset($_GET['pageant_id']) ? (int)$_GET['pageant_id'] : 0;

// Include the database class file
require_once('../classes/database.php');

// Create an instance of the database class
$con = new database();

// Initialize variables
$pageant = null;
$rounds = [];
$leaderboard = [];
$error_message = '';

if ($pid > 0) {
    // Get pageant information
    $pageant = $con->getPageantByCode(''); // We'll get by ID instead
    
    // Get pageant by ID directly
    $conn = $con->opencon();
    $stmt = $conn->prepare("SELECT * FROM pageants WHERE id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pageant = $result->fetch_assoc();
        
        // Get rounds for this pageant
        $rounds = $con->getPageantRounds($pid);
        
        // Find the best round to show (CLOSED first, then OPEN)
        $target_round = null;
        foreach ($rounds as $round) {
            if ($round['state'] === 'CLOSED' || $round['state'] === 'FINALIZED') {
                $target_round = $round;
                break;
            }
        }
        
        if (!$target_round) {
            foreach ($rounds as $round) {
                if ($round['state'] === 'OPEN') {
                    $target_round = $round;
                    break;
                }
            }
        }
        
        if ($target_round) {
            $leaderboard = $con->getRoundLeaderboard($target_round['id']);
        } elseif (!empty($rounds)) {
            $error_message = 'No active rounds available for scoring yet.';
        } else {
            $error_message = 'No rounds have been created for this pageant yet.';
        }
    } else {
        $error_message = 'Pageant not found.';
    }
    
    $stmt->close();
    $conn->close();
} else {
    header('Location: public_select.php');
    exit();
}

include __DIR__ . '/../partials/head.php';
?>
<main class="mx-auto max-w-6xl w-full p-6 space-y-8">
  <div class="text-center mb-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">Preliminary Standings</h1>
    <?php if ($pageant): ?>
      <p class="text-slate-600"><?php echo htmlspecialchars($pageant['name']); ?></p>
      <p class="text-sm text-slate-500">Code: <?php echo htmlspecialchars($pageant['code']); ?></p>
    <?php endif; ?>
  </div>
  <?php if ($error_message): ?>
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-6 py-4 rounded-lg text-center">
      <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 8.5c-.77.833.192 2.5 1.732 2.5z"/>
      </svg>
      <p class="font-medium"><?php echo htmlspecialchars($error_message); ?></p>
      <p class="text-sm mt-2">Please check back later or contact event organizers.</p>
    </div>
  <?php elseif (!empty($leaderboard)): ?>
    <!-- Leaderboard Table -->
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
        <h2 class="text-white font-semibold text-lg">Current Rankings</h2>
        <p class="text-blue-100 text-sm">Live preliminary standings</p>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Rank</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Number</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Participant</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Division</th>
              <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">Score</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($leaderboard as $index => $participant): ?>
              <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-slate-25'; ?> hover:bg-blue-50 transition-colors duration-200">
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <?php if ($participant['rank'] <= 3): ?>
                      <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold <?php 
                        echo $participant['rank'] === 1 ? 'bg-yellow-100 text-yellow-800' : 
                             ($participant['rank'] === 2 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800'); 
                      ?>">
                        #<?php echo $participant['rank']; ?>
                      </span>
                    <?php else: ?>
                      <span class="text-slate-600 font-medium">#<?php echo $participant['rank']; ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-100 text-blue-800">
                    <?php echo htmlspecialchars($participant['number_label']); ?>
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="font-medium text-slate-800"><?php echo htmlspecialchars($participant['name']); ?></div>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php 
                    echo $participant['division'] === 'Mr' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; 
                  ?>">
                    <?php echo htmlspecialchars($participant['division']); ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span class="font-mono text-lg font-semibold text-slate-800">
                    <?php echo $participant['score'] ?? '--'; ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php else: ?>
    <div class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      <h3 class="text-xl font-semibold text-slate-800 mb-2">No Standings Available</h3>
      <p class="text-slate-600">Preliminary results will appear here once judging begins.</p>
    </div>
  <?php endif; ?>
  
  <!-- Auto-refresh notice -->
  <div class="text-center text-sm text-slate-500 mt-8">
    <p>Results are updated in real-time. Last updated: <?php echo date('g:i A'); ?></p>
    <button onclick="location.reload()" class="mt-2 text-blue-600 hover:text-blue-700 font-medium transition-colors">
      🔄 Refresh Results
    </button>
  </div>
</main>

<script>
// Auto-refresh every 30 seconds
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 30000);

// Show last updated time
document.addEventListener('DOMContentLoaded', function() {
    showNotification('Standings loaded successfully', 'success', true);
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
