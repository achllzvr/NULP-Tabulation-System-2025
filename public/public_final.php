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
        
        // Get visibility flags
        $visibility_flags = $con->getVisibilityFlags($pid);
        
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
<main class="min-h-screen custom-blue-gradient flex flex-col items-center justify-center py-12 px-4">
  <div class="mx-auto max-w-4xl w-full">
    <div class="bg-white bg-opacity-20 backdrop-blur-xl rounded-3xl shadow-2xl border border-white border-opacity-25 p-10 mb-10">
      <div class="text-center mb-10">
        <h1 class="text-5xl font-extrabold text-white mb-2 drop-shadow-lg">🏆 Final Results</h1>
        <?php if ($pageant): ?>
          <p class="text-2xl text-slate-100 font-semibold mb-1"><?php echo htmlspecialchars($pageant['name']); ?></p>
          <p class="text-base text-slate-200 tracking-wider">Code: <?php echo htmlspecialchars($pageant['code']); ?></p>
        <?php endif; ?>
      </div>

      <?php if ($error_message): ?>
        <div class="bg-yellow-100 bg-opacity-20 border border-yellow-300 text-yellow-200 px-6 py-4 rounded-lg text-center">
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
      <section class="mb-14">
        <h2 class="text-2xl font-bold text-center text-white mb-10 tracking-wide drop-shadow">🥇 Winners' Podium</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
          <?php foreach ($top3 as $index => $winner): ?>
            <div class="relative flex flex-col items-center <?php echo $index === 0 ? 'md:order-2' : ($index === 1 ? 'md:order-1' : 'md:order-3'); ?>">
              <div class="bg-gradient-to-br <?php 
                echo $index === 0 ? 'from-yellow-300 via-yellow-400 to-yellow-500' : 
                     ($index === 1 ? 'from-gray-200 via-gray-300 to-gray-400' : 'from-orange-300 via-orange-400 to-orange-500'); 
              ?> rounded-2xl p-8 text-white text-center shadow-2xl border-4 <?php echo $index === 0 ? 'border-yellow-200 scale-110 z-10' : ($index === 1 ? 'border-gray-200' : 'border-orange-200'); ?> transition-transform">
                <!-- Position Badge -->
                <div class="absolute -top-7 left-1/2 transform -translate-x-1/2">
                  <div class="w-14 h-14 rounded-full flex items-center justify-center text-white font-extrabold text-2xl shadow-lg border-4 <?php 
                    echo $index === 0 ? 'bg-yellow-400 border-yellow-200' : 
                         ($index === 1 ? 'bg-gray-400 border-gray-200' : 'bg-orange-400 border-orange-200'); 
                  ?>">
                    <?php echo $index + 1; ?>
                  </div>
                </div>
                <!-- Participant Info -->
                <div class="pt-6">
                  <div class="text-4xl mb-3">
                    <?php echo $index === 0 ? '👑' : ($index === 1 ? '🥈' : '🥉'); ?>
                  </div>
                  <h3 class="text-2xl font-extrabold mb-1 drop-shadow-sm"><?php echo htmlspecialchars($winner['name']); ?></h3>
                  <p class="text-base opacity-90 mb-1">#<?php echo htmlspecialchars($winner['number_label']); ?></p>
                  <p class="text-3xl font-mono font-extrabold mb-1 tracking-wider"><?php echo $winner['score'] ?? '--'; ?></p>
                  <p class="text-sm opacity-80"><?php echo htmlspecialchars($winner['division']); ?> Division</p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- Complete Rankings -->
    <section>
      <h2 class="text-2xl font-bold text-center text-white mb-8 tracking-wide drop-shadow">Complete Rankings</h2>
      <div class="bg-white bg-opacity-30 rounded-2xl shadow-xl border border-white border-opacity-20 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-white bg-opacity-10 border-b border-white border-opacity-10">
              <tr>
                <th class="px-6 py-4 text-left text-base font-bold text-slate-800">Rank</th>
                <th class="px-6 py-4 text-left text-base font-bold text-slate-800">Number</th>
                <th class="px-6 py-4 text-left text-base font-bold text-slate-800">Participant</th>
                <th class="px-6 py-4 text-left text-base font-bold text-slate-800">Division</th>
                <th class="px-6 py-4 text-right text-base font-bold text-slate-800">Final Score</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white divide-opacity-10">
              <?php foreach ($leaderboard as $index => $participant): ?>
                <tr class="<?php echo $participant['rank'] <= 3 ? 'bg-gradient-to-r from-yellow-100/60 to-yellow-200/60' : ($index % 2 === 0 ? 'bg-white bg-opacity-10' : 'bg-white bg-opacity-5'); ?> hover:bg-blue-200 hover:bg-opacity-20 transition-colors duration-200">
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <?php if ($participant['rank'] <= 3): ?>
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-lg font-bold <?php 
                          echo $participant['rank'] === 1 ? 'bg-yellow-300 text-yellow-900' : 
                               ($participant['rank'] === 2 ? 'bg-gray-300 text-gray-900' : 'bg-orange-300 text-orange-900'); 
                        ?>">
                          <?php echo $participant['rank'] === 1 ? '🥇' : ($participant['rank'] === 2 ? '🥈' : '🥉'); ?>
                        </span>
                        <span class="ml-2 font-bold text-lg text-slate-900">#<?php echo $participant['rank']; ?></span>
                      <?php else: ?>
                        <span class="text-slate-700 font-medium text-lg">#<?php echo $participant['rank']; ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-base font-medium bg-blue-200 bg-opacity-60 text-blue-900">
                      <?php echo htmlspecialchars($participant['number_label']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="font-semibold text-slate-900 text-lg">
                      <?php echo $visibility_flags['reveal_names'] ? htmlspecialchars($participant['name']) : 'Hidden'; ?>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <?php if ($visibility_flags['reveal_names']): ?>
                      <span class="inline-flex items-center px-2 py-1 rounded text-base font-medium <?php 
                        echo $participant['division'] === 'Mr' ? 'bg-blue-200 bg-opacity-60 text-blue-900' : 'bg-pink-200 bg-opacity-60 text-pink-900'; 
                      ?>">
                        <?php echo htmlspecialchars($participant['division']); ?>
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2 py-1 rounded text-base font-medium bg-gray-200 bg-opacity-60 text-gray-900">
                        Hidden
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <span class="font-mono text-2xl font-bold text-slate-900">
                      <?php echo $visibility_flags['reveal_scores'] ? ($participant['score'] ?? '--') : 'Hidden'; ?>
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
  <div class="text-center text-sm mt-8">
    <p class="text-slate-100 drop-shadow-sm">Results are updated in real-time. Last updated: <?php echo date('g:i A'); ?></p>
    <button onclick="location.reload()" class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white bg-opacity-20 text-blue-200 hover:bg-opacity-40 font-semibold shadow transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582M20 20v-5h-.581M5 19A9 9 0 0021 7.5M19 5A9 9 0 003 16.5"/></svg>
      Refresh Results
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
