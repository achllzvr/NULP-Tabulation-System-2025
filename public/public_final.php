<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Public Final Results';
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
    $conn = $con->opencon();
    $stmt = $conn->prepare("SELECT * FROM pageants WHERE id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pageant = $result->fetch_assoc();
        
        // Get rounds for this pageant
        $rounds = $con->getPageantRounds($pid);
        
        // Find the last CLOSED/FINALIZED round for final results
        $target_round = null;
        $closed_rounds = array_filter($rounds, function($round) {
            return $round['state'] === 'CLOSED' || $round['state'] === 'FINALIZED';
        });
        
        if (!empty($closed_rounds)) {
            // Get the last closed round
            $target_round = end($closed_rounds);
        } elseif (!empty($rounds)) {
            // Fallback to the last round if no closed rounds
            $target_round = end($rounds);
        }
        
        if ($target_round) {
            $leaderboard = $con->getRoundLeaderboard($target_round['id']);
        } else {
            $error_message = 'No final results available yet.';
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
    <h1 class="text-4xl font-bold text-slate-800 mb-2">🏆 Final Results</h1>
    <?php if ($pageant): ?>
      <p class="text-xl text-slate-600"><?php echo htmlspecialchars($pageant['name']); ?></p>
      <p class="text-sm text-slate-500">Code: <?php echo htmlspecialchars($pageant['code']); ?></p>
    <?php endif; ?>
  </div>

  <?php if ($error_message): ?>
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-6 py-4 rounded-lg text-center">
      <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 8.5c-.77.833.192 2.5 1.732 2.5z"/>
      </svg>
      <p class="font-medium"><?php echo htmlspecialchars($error_message); ?></p>
      <p class="text-sm mt-2">Final results will be available once judging is complete.</p>
    </div>
  <?php elseif (!empty($leaderboard)): ?>
    
    <!-- Winner's Podium -->
    <?php $top3 = array_slice($leaderboard, 0, 3); ?>
    <?php if (!empty($top3)): ?>
      <section class="mb-12">
        <h2 class="text-2xl font-semibold text-center text-slate-800 mb-8">🥇 Winners' Podium</h2>
        <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
          <?php foreach ($top3 as $index => $winner): ?>
            <div class="<?php echo $index === 0 ? 'order-2 md:order-1' : ($index === 1 ? 'order-1 md:order-2' : 'order-3'); ?> relative">
              <div class="bg-gradient-to-br <?php 
                echo $index === 0 ? 'from-yellow-400 to-yellow-600' : 
                     ($index === 1 ? 'from-gray-300 to-gray-500' : 'from-orange-400 to-orange-600'); 
              ?> rounded-xl p-6 text-white text-center shadow-xl transform <?php echo $index === 0 ? 'scale-110' : ''; ?>">
                
                <!-- Position Badge -->
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                  <div class="w-12 h-12 rounded-full <?php 
                    echo $index === 0 ? 'bg-yellow-500 border-4 border-yellow-300' : 
                         ($index === 1 ? 'bg-gray-400 border-4 border-gray-200' : 'bg-orange-500 border-4 border-orange-300'); 
                  ?> flex items-center justify-center text-white font-bold text-lg">
                    <?php echo $winner['rank']; ?>
                  </div>
                </div>
                
                <!-- Participant Info -->
                <div class="pt-4">
                  <div class="text-3xl mb-2">
                    <?php echo $index === 0 ? '👑' : ($index === 1 ? '🥈' : '🥉'); ?>
                  </div>
                  <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($winner['name']); ?></h3>
                  <p class="text-sm opacity-90 mb-2">#<?php echo htmlspecialchars($winner['number_label']); ?></p>
                  <p class="text-2xl font-mono font-bold"><?php echo $winner['score'] ?? '--'; ?></p>
                  <p class="text-xs opacity-75"><?php echo htmlspecialchars($winner['division']); ?> Division</p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- Complete Rankings -->
    <section>
      <h2 class="text-2xl font-semibold text-center text-slate-800 mb-6">Complete Rankings</h2>
      <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Rank</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Number</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Participant</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Division</th>
                <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">Final Score</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($leaderboard as $index => $participant): ?>
                <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-slate-25'; ?> hover:bg-blue-50 transition-colors duration-200 <?php echo $participant['rank'] <= 3 ? 'bg-gradient-to-r from-yellow-50 to-yellow-100' : ''; ?>">
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <?php if ($participant['rank'] <= 3): ?>
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold <?php 
                          echo $participant['rank'] === 1 ? 'bg-yellow-100 text-yellow-800' : 
                               ($participant['rank'] === 2 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800'); 
                        ?>">
                          <?php echo $participant['rank'] === 1 ? '🥇' : ($participant['rank'] === 2 ? '🥈' : '🥉'); ?>
                        </span>
                        <span class="ml-2 font-bold text-lg">#<?php echo $participant['rank']; ?></span>
                      <?php else: ?>
                        <span class="text-slate-600 font-medium text-lg">#<?php echo $participant['rank']; ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-100 text-blue-800">
                      <?php echo htmlspecialchars($participant['number_label']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="font-semibold text-slate-800 text-lg"><?php echo htmlspecialchars($participant['name']); ?></div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2 py-1 rounded text-sm font-medium <?php 
                      echo $participant['division'] === 'Mr' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; 
                    ?>">
                      <?php echo htmlspecialchars($participant['division']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <span class="font-mono text-xl font-bold text-slate-800">
                      <?php echo $participant['score'] ?? '--'; ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    
  <?php else: ?>
    <div class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      <h3 class="text-xl font-semibold text-slate-800 mb-2">No Final Results Available</h3>
      <p class="text-slate-600">Final results will appear here once all judging is complete.</p>
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

// Show loading notification
document.addEventListener('DOMContentLoaded', function() {
    showNotification('Final results loaded successfully', 'success', true);
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
