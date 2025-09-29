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

// Handle filter changes
$selected_round = $_GET['round'] ?? 'all';
$selected_division = $_GET['division'] ?? 'all';

// Get participants count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM participants WHERE pageant_id = ? AND is_active = 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$participants_count = $result->fetch_assoc()['count'];

// Get completed rounds count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND state IN ('CLOSED', 'FINALIZED')");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$completed_rounds = $result->fetch_assoc()['count'];

// Get rounds for dropdown filter
$stmt = $conn->prepare("SELECT * FROM rounds WHERE pageant_id = ? ORDER BY sequence");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$rounds = $result->fetch_all(MYSQLI_ASSOC);

// Get leaderboard data
$rows = [];
$current_leader = null;
if ($selected_round === 'all') {
    $rows = $con->getOverallLeaderboard($pageant_id, $selected_division);
} else {
    $rows = $con->getRoundLeaderboard((int)$selected_round, $selected_division);
}

// Get current leader info
if (!empty($rows)) {
    $current_leader = $rows[0];
}

$conn->close();

$pageTitle = 'Leaderboard';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 mb-2">Leaderboard</h1>
          <p class="text-slate-600">Real-time participant rankings and scores</p>
        </div>
        <div class="flex gap-3">
          <button onclick="refreshLeaderboard()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
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
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo $completed_rounds; ?></p>
        <p class="text-sm text-slate-600">Completed rounds</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Current Leader</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
          </svg>
        </div>
        <p class="text-lg font-bold text-slate-800 mb-1"><?php echo ($current_leader && isset($current_leader['full_name'])) ? htmlspecialchars($current_leader['full_name']) : 'TBD'; ?></p>
        <p class="text-sm text-slate-600"><?php echo ($current_leader && isset($current_leader['total_score'])) ? 'Score: ' . number_format($current_leader['total_score'], 2) : 'When scores available'; ?></p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Last Updated</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-lg font-bold text-slate-800 mb-1" id="lastUpdate">--:--</p>
        <p class="text-sm text-slate-600">Real-time updates</p>
      </div>
    </div>

    <!-- Leaderboard Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8">
      <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-800">Filters & Options</h3>
        <p class="text-sm text-slate-600 mt-1">Customize leaderboard view</p>
      </div>
      
      <div class="p-6">
        <div class="grid md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Round Filter</label>
            <select id="roundFilter" name="round" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" onchange="updateFilters()">
              <option value="all" <?php echo $selected_round === 'all' ? 'selected' : ''; ?>>All Rounds (Overall)</option>
              <?php foreach ($rounds as $round): ?>
                <option value="<?php echo $round['id']; ?>" <?php echo $selected_round == $round['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($round['name']); ?> (<?php echo ucfirst(strtolower($round['state'])); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Division Filter</label>
            <select id="divisionFilter" name="division" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" onchange="updateFilters()">
              <option value="all" <?php echo $selected_division === 'all' ? 'selected' : ''; ?>>All Divisions</option>
              <option value="Mr" <?php echo $selected_division === 'Mr' ? 'selected' : ''; ?>>Mr Division</option>
              <option value="Ms" <?php echo $selected_division === 'Ms' ? 'selected' : ''; ?>>Ms Division</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">View Mode</label>
            <select id="viewMode" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" onchange="changeViewMode()">
              <option value="ranking">Ranking View</option>
              <option value="scores">Detailed Scores</option>
              <option value="comparison">Score Comparison</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
      <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-800">Current Rankings</h3>
        <p class="text-sm text-slate-600 mt-1">Live participant standings</p>
      </div>
      
      <div class="overflow-x-auto">
        <div id="leaderboardContent">
          <?php if (!empty($rows)): ?>
            <!-- Enhanced Leaderboard Table -->
            <table class="w-full">
              <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700 rank-column">Rank</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Number</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Name</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Division</th>
                  <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">Score</th>
                  <th class="px-6 py-4 text-center text-sm font-semibold text-slate-700 score-details" style="display: none;">Details</th>
                  <th class="px-6 py-4 text-center text-sm font-semibold text-slate-700">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <?php foreach ($rows as $index => $participant): ?>
                  <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-slate-25'; ?> hover:bg-blue-50 transition-colors duration-200">
                    <td class="px-6 py-4 rank-column">
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
                        <?php echo $participant['total_score'] ?? $participant['score'] ?? '--'; ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 text-center score-details" style="display: none;">
                      <div class="text-xs text-slate-500">
                        <div>Total: <?php echo $participant['total_score'] ?? $participant['score'] ?? '--'; ?></div>
                        <div>Rank: #<?php echo $participant['rank']; ?></div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <button onclick="viewParticipantDetails(<?php echo $participant['id']; ?>)" 
                              class="text-blue-600 hover:text-blue-700 font-medium transition-colors text-sm">
                        View Details
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="p-12 text-center">
              <svg class="mx-auto h-16 w-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
              <h3 class="text-xl font-semibold text-slate-800 mb-2">No Scores Available</h3>
              <p class="text-slate-600 mb-6">The leaderboard will be available once rounds are completed and scores are finalized.</p>
              <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="rounds.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  Manage Rounds
                </a>
                <a href="dashboard.php" class="bg-slate-600 hover:bg-slate-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                  Dashboard
                </a>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

<script>
function refreshLeaderboard() {
  document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
  showNotification('Refreshing leaderboard...', 'info', true);
  setTimeout(() => {
    location.reload();
  }, 500);
}

function updateFilters() {
  const roundFilter = document.getElementById('roundFilter').value;
  const divisionFilter = document.getElementById('divisionFilter').value;
  
  const url = new URL(window.location);
  url.searchParams.set('round', roundFilter);
  url.searchParams.set('division', divisionFilter);
  
  showNotification('Updating filters...', 'info', true);
  window.location.href = url.toString();
}

function changeViewMode() {
  const viewMode = document.getElementById('viewMode').value;
  const leaderboardContent = document.getElementById('leaderboardContent');
  
  // Show loading state
  showNotification('Changing view mode...', 'info', true);
  
  // Apply different view modes
  switch(viewMode) {
    case 'ranking':
      // Show basic ranking view (default)
      leaderboardContent.querySelectorAll('.score-details').forEach(el => el.style.display = 'none');
      leaderboardContent.querySelectorAll('.rank-column').forEach(el => el.style.display = 'table-cell');
      break;
    case 'scores':
      // Show detailed scores
      leaderboardContent.querySelectorAll('.score-details').forEach(el => el.style.display = 'table-cell');
      leaderboardContent.querySelectorAll('.rank-column').forEach(el => el.style.display = 'table-cell');
      break;
    case 'comparison':
      // Show score comparison view
      leaderboardContent.querySelectorAll('.score-details').forEach(el => el.style.display = 'table-cell');
      leaderboardContent.querySelectorAll('.rank-column').forEach(el => el.style.display = 'none');
      break;
  }
  
  showNotification(`Switched to ${viewMode} view`, 'success', true);
}

function viewParticipantDetails(participantId) {
  // Open participant details in a new tab/window
  window.open(`participants.php?view=${participantId}`, '_blank');
}

// Update last update time on page load
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
  
  <?php if (!empty($rows)): ?>
    showNotification('Leaderboard loaded with <?php echo count($rows); ?> participants', 'success', true);
  <?php endif; ?>
});

// Auto-refresh every 30 seconds
setInterval(function() {
    if (document.visibilityState === 'visible') {
        document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        // Optional: Auto-reload every 2 minutes
        if (Math.floor(Date.now() / 1000) % 120 === 0) {
            location.reload();
        }
    }
}, 30000);
</script>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
