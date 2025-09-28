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

// Handle live control actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'get_current_status':
            // Get current round status and live data
            $stmt = $conn->prepare("SELECT r.*, COUNT(DISTINCT pu.user_id) as judges_count 
                                    FROM rounds r 
                                    LEFT JOIN pageant_users pu ON pu.pageant_id = r.pageant_id AND pu.role = 'judge'
                                    WHERE r.pageant_id = ? AND r.state = 'OPEN'
                                    GROUP BY r.id 
                                    ORDER BY r.sequence");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $active_rounds = $result->fetch_all(MYSQLI_ASSOC);
            
            $response['success'] = true;
            $response['data'] = [
                'active_rounds' => $active_rounds,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch initial data
$stmt = $conn->prepare("SELECT * FROM rounds WHERE pageant_id = ? ORDER BY sequence");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$rounds = $result->fetch_all(MYSQLI_ASSOC);

// Get participants count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM participants WHERE pageant_id = ? AND is_active = 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$participants_count = $result->fetch_assoc()['count'];

// Get judges count
$stmt = $conn->prepare("SELECT COUNT(DISTINCT pu.user_id) as count FROM pageant_users pu WHERE pu.pageant_id = ? AND pu.role = 'judge'");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$judges_count = $result->fetch_assoc()['count'];

$conn->close();

$pageTitle = 'Live Control Center';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="bg-slate-50 min-h-screen">
  <div class="mx-auto max-w-7xl px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 mb-2">Live Control Center</h1>
          <p class="text-slate-600">Real-time pageant monitoring and control</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-sm text-slate-600">Live</span>
          </div>
          <span class="text-sm text-slate-500" id="lastUpdate">Last updated: --</span>
        </div>
      </div>
    </div>

    <!-- Status Overview -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Active Rounds</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1" id="activeRoundsCount">
          <?php echo count(array_filter($rounds, fn($r) => $r['state'] === 'OPEN')); ?>
        </p>
        <p class="text-sm text-slate-600">Currently judging</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Participants</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo $participants_count; ?></p>
        <p class="text-sm text-slate-600">Active contestants</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Judges Online</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1" id="judgesOnlineCount"><?php echo $judges_count; ?></p>
        <p class="text-sm text-slate-600">Total assigned</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">System Status</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-lg font-bold text-green-600 mb-1">Operational</p>
        <p class="text-sm text-slate-600">All systems running</p>
      </div>
    </div>

    <!-- Live Round Control -->
    <div class="grid lg:grid-cols-2 gap-8">
      <!-- Current Rounds -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200">
          <h3 class="text-lg font-semibold text-slate-800">Round Status</h3>
          <p class="text-sm text-slate-600 mt-1">Real-time round monitoring</p>
        </div>
        
        <div class="p-6">
          <div class="space-y-4" id="roundsList">
            <?php if (!empty($rounds)): ?>
              <?php foreach ($rounds as $round): ?>
                <div class="border border-slate-200 rounded-lg p-4">
                  <div class="flex items-center justify-between mb-2">
                    <h4 class="font-medium text-slate-800"><?php echo htmlspecialchars($round['name']); ?></h4>
                    <span class="px-3 py-1 text-sm font-medium rounded-full <?php 
                      switch ($round['state']) {
                        case 'OPEN':
                          echo 'bg-green-100 text-green-800';
                          break;
                        case 'CLOSED':
                        case 'FINALIZED':
                          echo 'bg-blue-100 text-blue-800';
                          break;
                        default:
                          echo 'bg-slate-100 text-slate-600';
                      }
                    ?>">
                      <?php echo $round['state']; ?>
                    </span>
                  </div>
                  <p class="text-sm text-slate-600">Round <?php echo $round['sequence']; ?></p>
                  <?php if ($round['state'] === 'OPEN'): ?>
                    <div class="mt-3 flex items-center gap-2 text-sm text-green-600">
                      <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                      <span>Live judging in progress</span>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-sm font-medium text-slate-900 mb-2">No rounds configured</h3>
                <p class="text-sm text-slate-500">Configure rounds in the Rounds & Criteria page.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Live Activity Feed -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200">
          <h3 class="text-lg font-semibold text-slate-800">Live Activity</h3>
          <p class="text-sm text-slate-600 mt-1">Recent system activity</p>
        </div>
        
        <div class="p-6">
          <div class="space-y-4" id="activityFeed">
            <div class="flex items-start gap-3">
              <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
              <div>
                <p class="text-sm text-slate-800 font-medium">System started</p>
                <p class="text-xs text-slate-500">Live control center is active</p>
              </div>
            </div>
            
            <div class="flex items-start gap-3">
              <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
              <div>
                <p class="text-sm text-slate-800 font-medium">Monitoring active</p>
                <p class="text-xs text-slate-500">Real-time updates enabled</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
      <h3 class="text-lg font-semibold text-slate-800 mb-4">Quick Actions</h3>
      <div class="grid md:grid-cols-3 gap-4">
        <button onclick="refreshStatus()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Refresh Status
        </button>
        
        <a href="rounds.php" class="bg-slate-600 hover:bg-slate-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Manage Rounds
        </a>
        
        <a href="dashboard.php" class="bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          Dashboard
        </a>
      </div>
    </div>
  </div>
</main>

<script>
// Auto-refresh functionality
let refreshInterval;

function refreshStatus() {
  fetch('live_control.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=get_current_status'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      updateStatus(data.data);
      document.getElementById('lastUpdate').textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
    }
  })
  .catch(error => {
    console.error('Error refreshing status:', error);
  });
}

function updateStatus(data) {
  // Update active rounds count
  document.getElementById('activeRoundsCount').textContent = data.active_rounds.length;
  
  // Add to activity feed
  const activityFeed = document.getElementById('activityFeed');
  const newActivity = document.createElement('div');
  newActivity.className = 'flex items-start gap-3';
  newActivity.innerHTML = `
    <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
    <div>
      <p class="text-sm text-slate-800 font-medium">Status updated</p>
      <p class="text-xs text-slate-500">${new Date().toLocaleTimeString()}</p>
    </div>
  `;
  activityFeed.insertBefore(newActivity, activityFeed.firstChild);
  
  // Keep only last 10 activities
  while (activityFeed.children.length > 10) {
    activityFeed.removeChild(activityFeed.lastChild);
  }
}

// Start auto-refresh
document.addEventListener('DOMContentLoaded', function() {
  refreshStatus(); // Initial load
  refreshInterval = setInterval(refreshStatus, 30000); // Refresh every 30 seconds
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
