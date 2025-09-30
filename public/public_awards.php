<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$pageTitle = 'Public Awards';
$pid = isset($_GET['pageant_id']) ? (int)$_GET['pageant_id'] : 0;
$tie_breaker_in_progress = false;

// Include the database class file
require_once('../classes/database.php');

// Create an instance of the database class
$con = new database();

// Initialize variables
$pageant = null;
$awards = [];
$visibility_flags = [];
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
    // Get awards if they should be revealed
    if ($visibility_flags['reveal_awards']) {
      $awards = $con->getPublicAwards($pid);
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
        <h1 class="text-4xl font-bold text-white mb-2">🏆 Awards & Recognition</h1>
        <?php if ($pageant): ?>
          <p class="text-xl text-slate-200"><?php echo htmlspecialchars($pageant['name']); ?></p>
          <p class="text-sm text-slate-300">Code: <?php echo htmlspecialchars($pageant['code']); ?></p>
        <?php endif; ?>
      </div>


      <?php if ($error_message): ?>
        <div class="bg-yellow-100 bg-opacity-20 border border-yellow-300 text-yellow-200 px-6 py-4 rounded-lg text-center">
          <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 8.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          <p class="font-medium"><?php echo htmlspecialchars($error_message); ?></p>
        </div>
      <?php elseif (!$visibility_flags['reveal_awards']): ?>
        <!-- Awards not yet revealed -->
        <div class="text-center py-16">
          <div class="text-8xl mb-6">🏆</div>
          <h2 class="text-3xl font-bold text-slate-100 mb-4">Awards Not Yet Revealed</h2>
          <p class="text-lg text-slate-200 mb-6">The awards ceremony hasn't begun yet.</p>
          <p class="text-slate-300">Please check back later or stay tuned for the announcement!</p>
          <div class="mt-8 inline-flex items-center px-4 py-2 bg-blue-200 bg-opacity-30 border border-blue-300 border-opacity-30 rounded-lg text-blue-100">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium">Awards will be announced soon</span>
          </div>
        </div>
      <?php elseif (!empty($awards)): ?>
        <!-- Awards Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <?php foreach ($awards as $index => $award): ?>
            <div class="bg-white bg-opacity-20 border-2 border-white border-opacity-20 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1" 
                 style="animation: fadeInUp 0.6s ease-out <?php echo $index * 0.1; ?>s both;">
              <!-- Award Header -->
              <div class="text-center border-b border-white border-opacity-20 pb-4 mb-4">
                <div class="text-3xl mb-2">🏅</div>
                <h3 class="text-xl font-bold text-slate-100"><?php echo htmlspecialchars($award['name']); ?></h3>
                <p class="text-sm text-slate-200 font-medium mt-1">
                  <?php echo htmlspecialchars($award['division_scope']); ?> Division
                </p>
              </div>
              <!-- Winners List -->
              <div class="space-y-3">
                <?php if (!empty($award['winners'])): ?>
                  <?php foreach ($award['winners'] as $winnerIndex => $winner): ?>
                    <div class="flex items-center justify-between p-3 bg-gradient-to-r <?php 
                      echo $winnerIndex === 0 ? 'from-yellow-200 to-yellow-300 border-yellow-400' : 
                           ($winnerIndex === 1 ? 'from-gray-200 to-gray-300 border-gray-400' : 'from-orange-200 to-orange-300 border-orange-400'); 
                    ?> border rounded-lg">
                      <div>
                        <p class="font-semibold text-slate-100"><?php echo htmlspecialchars($winner['full_name']); ?></p>
                        <p class="text-sm text-slate-200">#<?php echo htmlspecialchars($winner['number_label']); ?></p>
                      </div>
                      <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold <?php 
                          echo $winnerIndex === 0 ? 'bg-yellow-500 text-white' : 
                               ($winnerIndex === 1 ? 'bg-gray-500 text-white' : 'bg-orange-500 text-white'); 
                        ?>">
                          <?php 
                            $position = $winnerIndex + 1;
                            echo $position . ($position === 1 ? 'st' : ($position === 2 ? 'nd' : ($position === 3 ? 'rd' : 'th')));
                          ?>
                        </span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-6 text-slate-300 italic">
                    <p>To be announced</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <!-- No awards yet -->
        <div class="text-center py-16">
          <div class="text-8xl mb-6">🏅</div>
          <h2 class="text-3xl font-bold text-slate-100 mb-4">No Awards Available</h2>
          <p class="text-lg text-slate-200 mb-6">Awards have not been set up for this pageant yet.</p>
          <p class="text-slate-300">Please check back later!</p>
        </div>
      <?php endif; ?>

      <!-- Auto-refresh notice -->
      <div class="text-center text-sm text-slate-200 mt-8">
        <p>Awards are updated in real-time. Last updated: <?php echo date('g:i A'); ?></p>
        <button onclick="location.reload()" class="mt-2 text-blue-200 hover:text-blue-100 font-medium transition-colors">
          🔄 Refresh Awards
        </button>
      </div>
    </div>
  </div>
</main>

<style>
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

<script>
// Auto-refresh every 60 seconds for awards (less frequent than leaderboards)
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 60000);

// Show loading notification
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!$visibility_flags['reveal_awards']): ?>
        showNotification('Awards not yet revealed - check back later!', 'info', true);
    <?php elseif (!empty($awards)): ?>
        showNotification('Awards loaded successfully', 'success', true);
    <?php else: ?>
        showNotification('No awards configured yet', 'info', true);
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
