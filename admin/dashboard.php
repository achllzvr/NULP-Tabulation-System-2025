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
$pageant_id = $_SESSION['pageant_id'] ?? 1; // fallback to 1 if not set

// Fetch dashboard data
// Get participants count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM participants WHERE pageant_id = ? AND is_active = 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$participantsCount = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get judges count
$stmt = $conn->prepare("SELECT COUNT(DISTINCT u.id) as count FROM users u JOIN pageant_users pu ON u.id = pu.user_id WHERE pu.pageant_id = ? AND LOWER(TRIM(pu.role)) = 'judge' AND u.is_active = 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$judgesCount = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get rounds info
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN state = 'CLOSED' THEN 1 ELSE 0 END) as completed FROM rounds WHERE pageant_id = ?");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$roundsData = $stmt->get_result()->fetch_assoc();
$totalRounds = $roundsData['total'];
$completedRounds = $roundsData['completed'];
$stmt->close();

// Get specific round statuses
$stmt = $conn->prepare("SELECT name, state FROM rounds WHERE pageant_id = ? ORDER BY sequence");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$roundsResult = $stmt->get_result();
$rounds = $roundsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate setup progress
$setupSteps = [
    'participants' => $participantsCount > 0,
    'judges' => $judgesCount > 0,
    'preliminary' => false,
    'final' => false
];

// Check round completion status
foreach ($rounds as $round) {
    if (strtolower($round['name']) == 'preliminary round' || strpos(strtolower($round['name']), 'preliminary') !== false) {
        $setupSteps['preliminary'] = ($round['state'] == 'CLOSED');
    }
    if (strtolower($round['name']) == 'final round' || strpos(strtolower($round['name']), 'final') !== false) {
        $setupSteps['final'] = ($round['state'] == 'CLOSED');
    }
}

$completedSteps = array_sum($setupSteps);
$totalSetupSteps = count($setupSteps);
$progressPercentage = ($completedSteps / $totalSetupSteps) * 100;

// Get recent activity (judges who have scored recently)
$stmt = $conn->prepare("SELECT u.full_name, r.name as round_name, s.created_at 
                        FROM scores s 
                        JOIN users u ON s.judge_user_id = u.id 
                        JOIN rounds r ON s.round_id = r.id 
                        WHERE r.pageant_id = ? 
                        ORDER BY s.created_at DESC 
                        LIMIT 5");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$recentActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

$steps = [
  ['label' => 'Participants', 'state' => $setupSteps['participants'] ? 'done' : 'pending'],
  ['label' => 'Judges', 'state' => $setupSteps['judges'] ? 'done' : ($setupSteps['participants'] ? 'current' : 'pending')],
  ['label' => 'Preliminary', 'state' => $setupSteps['preliminary'] ? 'done' : ($setupSteps['judges'] ? 'current' : 'pending')],
  ['label' => 'Final', 'state' => $setupSteps['final'] ? 'done' : ($setupSteps['preliminary'] ? 'current' : 'pending')],
];
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-white mb-2">Dashboard</h1>
      <p class="text-slate-200">Pageant setup progress and system overview</p>
    </div>

    <!-- Setup Progress Section -->
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-xl border border-white border-opacity-20 p-6 mb-8">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-lg font-semibold text-white mb-1">Setup Progress</h2>
          <p class="text-sm text-slate-200">Complete these steps to run your pageant</p>
        </div>
        <div class="text-right">
          <p class="text-sm text-slate-200 mb-1">Overall Progress</p>
          <p class="text-lg font-semibold text-white"><?php echo $completedSteps; ?>/<?php echo $totalSetupSteps; ?> steps</p>
        </div>
      </div>
      
      <!-- Progress Bar -->
      <div class="w-full bg-white bg-opacity-20 backdrop-blur-sm rounded-full h-2 mb-8">
        <div class="bg-blue-400 h-2 rounded-full" style="width: <?php echo round($progressPercentage); ?>%"></div>
      </div>

      <!-- Progress Steps Grid -->
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Participants Step -->
        <div class="flex items-center justify-between p-4 <?php echo $setupSteps['participants'] ? 'bg-green-500 bg-opacity-20 border border-green-400 border-opacity-30' : 'bg-white bg-opacity-10 border border-white border-opacity-20'; ?> backdrop-blur-sm rounded-lg">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 <?php echo $setupSteps['participants'] ? 'bg-green-500' : 'bg-slate-400'; ?> rounded-full flex items-center justify-center">
              <?php if ($setupSteps['participants']): ?>
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              <?php else: ?>
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" clip-rule="evenodd"/>
                </svg>
              <?php endif; ?>
            </div>
            <div>
              <h3 class="font-medium text-white">Participants Added</h3>
              <p class="text-sm <?php echo $setupSteps['participants'] ? 'text-green-200' : 'text-slate-200'; ?>">
                <?php echo $participantsCount; ?> <?php echo $participantsCount == 1 ? 'participant' : 'participants'; ?>
              </p>
            </div>
          </div>
          <a href="participants.php" class="text-sm text-blue-300 hover:text-blue-200 font-medium">
            <?php echo $setupSteps['participants'] ? 'View' : 'Add'; ?>
          </a>
        </div>

        <!-- Judges Step -->
        <div class="flex items-center justify-between p-4 <?php echo $setupSteps['judges'] ? 'bg-green-500 bg-opacity-20 border border-green-400 border-opacity-30' : 'bg-white bg-opacity-10 border border-white border-opacity-20'; ?> backdrop-blur-sm rounded-lg">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 <?php echo $setupSteps['judges'] ? 'bg-green-500' : 'bg-slate-400'; ?> rounded-full flex items-center justify-center">
              <?php if ($setupSteps['judges']): ?>
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              <?php else: ?>
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" clip-rule="evenodd"/>
                </svg>
              <?php endif; ?>
            </div>
            <div>
              <h3 class="font-medium text-white">Judges Assigned</h3>
              <p class="text-sm <?php echo $setupSteps['judges'] ? 'text-green-200' : 'text-slate-200'; ?>">
                <?php echo $judgesCount; ?> <?php echo $judgesCount == 1 ? 'judge' : 'judges'; ?>
              </p>
            </div>
          </div>
          <a href="judges.php" class="text-sm text-blue-300 hover:text-blue-200 font-medium">
            <?php echo $setupSteps['judges'] ? 'View' : 'Add'; ?>
          </a>
        </div>

        <!-- Preliminary Round Step -->
        <?php 
        $prelimRound = null;
        foreach ($rounds as $round) {
          if (strtolower($round['name']) == 'preliminary round' || strpos(strtolower($round['name']), 'preliminary') !== false) {
            $prelimRound = $round;
            break;
          }
        }
        ?>
        <div class="flex items-center justify-between p-4 <?php echo $setupSteps['preliminary'] ? 'bg-green-500 bg-opacity-20 border border-green-400 border-opacity-30' : 'bg-white bg-opacity-10 border border-white border-opacity-20'; ?> backdrop-blur-sm rounded-lg">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 <?php echo $setupSteps['preliminary'] ? 'bg-green-500' : 'bg-slate-400'; ?> rounded-full flex items-center justify-center">
              <?php if ($setupSteps['preliminary']): ?>
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              <?php else: ?>
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
              <?php endif; ?>
            </div>
            <div>
              <h3 class="font-medium text-white">
                <?php echo $prelimRound ? $prelimRound['name'] : 'Preliminary Round'; ?>
              </h3>
              <p class="text-sm <?php echo $setupSteps['preliminary'] ? 'text-green-200 font-medium' : 'text-slate-200'; ?>">
                <?php echo $prelimRound ? $prelimRound['state'] : 'NOT CREATED'; ?>
              </p>
            </div>
          </div>
          <a href="rounds.php" class="text-sm text-blue-300 hover:text-blue-200 font-medium">
            <?php echo $setupSteps['preliminary'] ? 'View' : 'Setup'; ?>
          </a>
        </div>

        <!-- Final Round Step -->
        <?php 
        $finalRound = null;
        foreach ($rounds as $round) {
          if (strtolower($round['name']) == 'final round' || strpos(strtolower($round['name']), 'final') !== false) {
            $finalRound = $round;
            break;
          }
        }
        ?>
        <div class="flex items-center justify-between p-4 <?php echo $setupSteps['final'] ? 'bg-green-500 bg-opacity-20 border border-green-400 border-opacity-30' : 'bg-white bg-opacity-10 border border-white border-opacity-20'; ?> backdrop-blur-sm rounded-lg">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 <?php echo $setupSteps['final'] ? 'bg-green-500' : 'bg-slate-400'; ?> rounded-full flex items-center justify-center">
              <?php if ($setupSteps['final']): ?>
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              <?php else: ?>
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
              <?php endif; ?>
            </div>
            <div>
              <h3 class="font-medium text-white">
                <?php echo $finalRound ? $finalRound['name'] : 'Final Round'; ?>
              </h3>
              <p class="text-sm <?php echo $setupSteps['final'] ? 'text-green-200 font-medium' : 'text-slate-200'; ?>">
                <?php echo $finalRound ? $finalRound['state'] : 'NOT CREATED'; ?>
              </p>
            </div>
          </div>
          <a href="rounds.php" class="text-sm text-blue-300 hover:text-blue-200 font-medium">
            <?php echo $setupSteps['final'] ? 'View' : 'Setup'; ?>
          </a>
        </div>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Participants</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-white mb-1"><?php echo $participantsCount; ?></p>
        <p class="text-sm text-slate-200"><?php echo $participantsCount > 0 ? '1 division' : 'No participants'; ?></p>
      </div>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Judges</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-white mb-1"><?php echo $judgesCount; ?></p>
        <p class="text-sm text-slate-200"><?php echo $judgesCount > 0 ? 'Ready to score' : 'No judges assigned'; ?></p>
      </div>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Rounds</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-white mb-1">
          <?php echo $completedRounds; ?>/<?php echo $totalRounds > 0 ? $totalRounds : '0'; ?>
        </p>
        <p class="text-sm text-slate-200">
          <?php echo $totalRounds > 0 ? 'Completed' : 'No rounds created'; ?>
        </p>
      </div>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Status</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
        </div>
        <p class="text-lg font-bold text-white mb-1">
          <?php 
            if ($completedSteps == $totalSetupSteps) {
              echo 'Complete';
            } elseif ($completedSteps > 0) {
              echo 'In Progress';
            } else {
              echo 'Setup Needed';
            }
          ?>
        </p>
        <p class="text-sm text-slate-200">Pageant status</p>
      </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid lg:grid-cols-2 gap-8">
      <!-- Current Status -->
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <h3 class="text-lg font-semibold text-white mb-6">Current Status</h3>
        
        <div class="space-y-4">
          <?php if (empty($rounds)): ?>
            <div class="flex items-center justify-between p-4 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg">
              <div>
                <h4 class="font-medium text-white">No Rounds Created</h4>
                <p class="text-sm text-slate-200">Set up rounds to begin judging</p>
              </div>
              <span class="px-3 py-1 bg-white bg-opacity-20 text-slate-200 rounded-full text-sm font-medium">SETUP NEEDED</span>
            </div>
          <?php else: ?>
            <?php foreach ($rounds as $round): ?>
              <div class="flex items-center justify-between p-4 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg">
                <div>
                  <h4 class="font-medium text-white"><?php echo htmlspecialchars($round['name']); ?></h4>
                  <p class="text-sm text-slate-200">
                    <?php 
                      switch ($round['state']) {
                        case 'CLOSED':
                        case 'FINALIZED':
                          echo 'Judging completed';
                          break;
                        case 'OPEN':
                          echo 'Currently open for judging';
                          break;
                        case 'PENDING':
                        default:
                          echo 'Ready to open';
                          break;
                      }
                    ?>
                  </p>
                </div>
                <span class="px-3 py-1 <?php 
                  switch ($round['state']) {
                    case 'CLOSED':
                    case 'FINALIZED':
                      echo 'bg-green-100 text-green-800';
                      break;
                    case 'OPEN':
                      echo 'bg-blue-100 text-blue-800';
                      break;
                    case 'PENDING':
                    default:
                      echo 'bg-slate-100 text-slate-600';
                      break;
                  }
                ?> rounded-full text-sm font-medium">
                  <?php echo $round['state']; ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="mt-6">
          <?php if ($setupSteps['preliminary'] && !$setupSteps['final']): ?>
            <a href="advancement.php" class="block w-full bg-slate-800 text-white py-3 px-4 rounded-lg font-medium hover:bg-slate-900 transition-colors text-center">
              Review Top 5 Advancement
            </a>
          <?php elseif (empty($rounds)): ?>
            <a href="rounds.php" class="block w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors text-center">
              Create Rounds
            </a>
          <?php else: ?>
            <a href="rounds.php" class="block w-full bg-slate-800 text-white py-3 px-4 rounded-lg font-medium hover:bg-slate-900 transition-colors text-center">
              Manage Rounds
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <h3 class="text-lg font-semibold text-white mb-6">Recent Activity</h3>
        
        <div class="space-y-4">
          <?php if (empty($recentActivity)): ?>
            <div class="text-center py-4">
              <p class="text-sm text-slate-200">No recent activity</p>
              <p class="text-xs text-slate-300 mt-1">Scoring activity will appear here</p>
            </div>
          <?php else: ?>
            <?php foreach ($recentActivity as $activity): ?>
              <div class="flex items-start gap-3">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                <div>
                  <p class="text-sm font-medium text-white">
                    <?php echo htmlspecialchars($activity['full_name']); ?> scored 
                    <?php echo htmlspecialchars($activity['round_name']); ?>
                  </p>
                  <p class="text-xs text-slate-200">
                    <?php 
                      $time_diff = time() - strtotime($activity['created_at']);
                      if ($time_diff < 3600) {
                        echo floor($time_diff / 60) . ' minutes ago';
                      } elseif ($time_diff < 86400) {
                        echo floor($time_diff / 3600) . ' hours ago';
                      } else {
                        echo floor($time_diff / 86400) . ' days ago';
                      }
                    ?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
          
          <!-- Always show system events if we have data -->
          <?php if ($setupSteps['participants']): ?>
            <div class="flex items-start gap-3">
              <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
              <div>
                <p class="text-sm font-medium text-white">
                  <?php echo $participantsCount; ?> participants added
                </p>
                <p class="text-xs text-slate-200">Setup completed</p>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if ($setupSteps['judges']): ?>
            <div class="flex items-start gap-3">
              <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
              <div>
                <p class="text-sm font-medium text-white">
                  <?php echo $judgesCount; ?> judges assigned
                </p>
                <p class="text-xs text-slate-200">Ready for judging</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6 mt-8">
      <h3 class="text-lg font-semibold text-white mb-6">Quick Actions</h3>
      <p class="text-sm text-slate-200 mb-6">Common administrative tasks</p>
      
      <div class="grid md:grid-cols-3 gap-4">
        <a href="participants.php" class="flex items-center gap-3 p-4 border border-white border-opacity-20 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg hover:bg-opacity-20 transition-all">
          <svg class="w-5 h-5 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          <span class="font-medium text-white">Manage Participants</span>
        </a>
        
        <a href="rounds.php" class="flex items-center gap-3 p-4 border border-white border-opacity-20 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg hover:bg-opacity-20 transition-all">
          <svg class="w-5 h-5 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span class="font-medium text-white">Control Rounds</span>
        </a>
        
          <a href="results.php" class="flex items-center gap-3 p-4 border border-white border-opacity-20 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg hover:bg-opacity-20 transition-all">
          <svg class="w-5 h-5 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          <span class="font-medium text-white">View Leaderboard</span>
        </a>
      </div>
    </div>
  </div>
<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php';
