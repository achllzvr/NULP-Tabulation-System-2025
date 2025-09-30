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
  // Use a single connection for all queries
  $conn = $con->opencon();
  // Check for tie breaker in progress
  $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tie_groups WHERE pageant_id = ? AND state = 'in_progress'");
  $stmt->bind_param("i", $pid);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $tie_breaker_in_progress = ($row && $row['cnt'] > 0);
  $stmt->close();

  // Get pageant by ID directly
  $stmt = $conn->prepare("SELECT * FROM pageants WHERE id = ?");
  $stmt->bind_param("i", $pid);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0 && !$tie_breaker_in_progress) {
    $pageant = $result->fetch_assoc();
    // Get visibility flags
    $visibility_flags = $con->getVisibilityFlags($pid);
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
  } elseif ($tie_breaker_in_progress) {
    $error_message = 'Public viewing is temporarily disabled during tie breaker.';
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
  <div class="mx-auto max-w-3xl w-full">
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-2xl shadow-xl border border-white border-opacity-20 p-8 mb-8">
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Preliminary Standings</h1>
        <?php if ($pageant): ?>
          <p class="text-xl text-slate-200"><?php echo htmlspecialchars($pageant['name']); ?></p>
          <p class="text-sm text-slate-300">Code: <?php echo htmlspecialchars($pageant['code']); ?></p>
        <?php endif; ?>
      </div>

      <?php
        // Gather unique divisions from leaderboard, only 'Mr.' and 'Ms.'
        $divisions = [];
        foreach ($leaderboard as $p) {
          $div = isset($p['division']) ? $p['division'] : '';
          if ($div && !in_array($div, $divisions) && in_array($div, ['Mr', 'Ms'])) $divisions[] = $div;
        }
        $selected_division = isset($_GET['division']) ? $_GET['division'] : 'Overall';
      ?>
      <?php if (!empty($divisions)): ?>
      <div class="flex justify-center mb-8">
        <div class="inline-flex rounded-xl bg-white bg-opacity-10 p-1 shadow-inner backdrop-blur">
          <a href="?pageant_id=<?php echo $pid; ?>" class="px-5 py-2 rounded-lg font-semibold text-base transition-all <?php echo ($selected_division === 'Overall' ? 'bg-white bg-opacity-80 text-blue-900 shadow' : 'text-white hover:bg-white hover:bg-opacity-20'); ?>">Overall</a>
          <?php foreach ($divisions as $div): ?>
            <a href="?pageant_id=<?php echo $pid; ?>&division=<?php echo urlencode($div); ?>" class="px-5 py-2 rounded-lg font-semibold text-base transition-all ml-1 <?php echo ($selected_division === $div ? 'bg-white bg-opacity-80 text-blue-900 shadow' : 'text-white hover:bg-white hover:bg-opacity-20'); ?>"><?php echo $div === 'Mr' ? 'Mr.' : ($div === 'Ms' ? 'Ms.' : htmlspecialchars($div)); ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php
        // Filter leaderboard if division selected
        $filtered_leaderboard = $leaderboard;
        if ($selected_division !== 'Overall') {
          $filtered_leaderboard = array_filter($leaderboard, function($p) use ($selected_division) {
            return isset($p['division']) && $p['division'] === $selected_division;
          });
        }
      ?>
      <?php if ($error_message): ?>
        <div class="bg-yellow-100 bg-opacity-30 border border-yellow-300 border-opacity-30 text-yellow-200 px-6 py-4 rounded-xl text-center mb-6">
          <svg class="w-6 h-6 mx-auto mb-2 text-yellow-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 8.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          <p class="font-medium"><?php echo htmlspecialchars($error_message); ?></p>
          <p class="text-sm mt-2">Please check back later or contact event organizers.</p>
        </div>
  <?php elseif (!empty($filtered_leaderboard)): ?>
        <!-- Leaderboard Table -->
        <div class="bg-white bg-opacity-20 rounded-2xl shadow-lg border border-white border-opacity-20 overflow-hidden">
          <div class="custom-blue-gradient px-6 py-4 rounded-t-2xl">
            <h2 class="text-white font-semibold text-lg">Current Rankings</h2>
            <p class="text-blue-100 text-sm">Live preliminary standings</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-white bg-opacity-10 border-b border-white border-opacity-10">
                <tr>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-200">Rank</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-200">Number</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-200">Participant</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-200">Division</th>
                  <th class="px-6 py-4 text-right text-sm font-semibold text-slate-200">Score</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white divide-opacity-10">
                <?php foreach (array_values($filtered_leaderboard) as $index => $participant): ?>
                  <tr class="<?php echo $index % 2 === 0 ? 'bg-white bg-opacity-10' : 'bg-white bg-opacity-5'; ?> hover:bg-blue-200 hover:bg-opacity-20 transition-colors duration-200">
                    <td class="px-6 py-4">
                      <div class="flex items-center">
                        <?php if ($participant['rank'] <= 3): ?>
                          <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold <?php 
                            echo $participant['rank'] === 1 ? 'bg-yellow-200 bg-opacity-80 text-yellow-900' : 
                                 ($participant['rank'] === 2 ? 'bg-gray-200 bg-opacity-80 text-gray-900' : 'bg-orange-200 bg-opacity-80 text-orange-900'); 
                          ?>">
                            #<?php echo $participant['rank']; ?>
                          </span>
                        <?php else: ?>
                          <span class="text-slate-200 font-medium">#<?php echo $participant['rank']; ?></span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-200 bg-opacity-60 text-blue-900">
                        <?php echo htmlspecialchars($participant['number_label']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="font-medium text-slate-100">
                        <?php echo $visibility_flags['reveal_names'] ? htmlspecialchars($participant['name']) : 'Hidden'; ?>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <?php if ($visibility_flags['reveal_names']): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php 
                          echo $participant['division'] === 'Mr' ? 'bg-blue-200 bg-opacity-60 text-blue-900' : 'bg-pink-200 bg-opacity-60 text-pink-900'; 
                        ?>">
                          <?php echo htmlspecialchars($participant['division']); ?>
                        </span>
                      <?php else: ?>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-200 bg-opacity-60 text-gray-900">
                          Hidden
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <span class="font-mono text-lg font-semibold text-slate-100">
                        <?php echo $visibility_flags['reveal_scores'] ? ($participant['score'] ?? '--') : 'Hidden'; ?>
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
          <svg class="w-16 h-16 mx-auto text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          <h3 class="text-xl font-semibold text-slate-100 mb-2">No Standings Available</h3>
          <p class="text-slate-200">Preliminary results will appear here once judging begins.</p>
        </div>
      <?php endif; ?>

      <!-- Auto-refresh notice -->
      <div class="text-center text-sm text-slate-200 mt-8">
        <p>Results are updated in real-time. Last updated: <?php echo date('g:i A'); ?></p>
        <button onclick="location.reload()" class="mt-2 text-blue-200 hover:text-blue-100 font-medium transition-colors">
          🔄 Refresh Results
        </button>
      </div>
    </div>
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
