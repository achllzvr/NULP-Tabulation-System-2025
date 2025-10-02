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

// Tab and filters
$tab = $_GET['tab'] ?? 'leaderboard'; // leaderboard | awards | tabulated
$selected_round = $_GET['round'] ?? 'all';
// Leaderboard stage filter: overall pre-Q&A (all closed prelim rounds) vs final (final rounds only)
$selected_stage = $_GET['stage'] ?? 'overall'; // overall | round:<id>
// Keep division filter for Tabulated Data section only
$selected_division = $_GET['division'] ?? 'all';

// Get participants count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM participants WHERE pageant_id = ? AND is_active = 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$participants_count = $result->fetch_assoc()['count'];
$stmt->close();

// Get rounds for dropdown filter
$stmt = $conn->prepare("SELECT * FROM rounds WHERE pageant_id = ? ORDER BY sequence");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$rounds = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Completed/finalized rounds stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND state IN ('CLOSED', 'FINALIZED')");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$completed_rounds = $result->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND state = 'FINALIZED'");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$finalized_rounds = $result->fetch_assoc()['count'];
$stmt->close();

// Awards-specific flags
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND scoring_mode = 'FINAL' AND state = 'FINALIZED'");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$final_rounds_completed = $result->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND scoring_mode = 'FINAL'");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$total_final_rounds = $result->fetch_assoc()['count'];
$stmt->close();

$all_final_rounds_completed = ($total_final_rounds > 0 && $final_rounds_completed >= $total_final_rounds);

// Detect if any round is currently OPEN (to blur results during live scoring)
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM rounds WHERE pageant_id = ? AND state = 'OPEN'");
$stmt->bind_param('i', $pageant_id);
$stmt->execute();
$resOpen = $stmt->get_result();
$rowOpen = $resOpen->fetch_assoc();
$has_open_round = ($rowOpen && (int)$rowOpen['cnt'] > 0);
$stmt->close();

// Data for leaderboard tab
$leaderboardRows = [];
$current_leader = null;
if ($tab === 'leaderboard') {
  $leaderboardRows = ['Ambassador'=>[], 'Ambassadress'=>[]];
  $stage = $selected_stage; // 'prelim' or 'final'
  $mode = ($stage === 'final') ? 'FINAL' : 'PRELIM';
  foreach (['Ambassador','Ambassadress'] as $div) {
    $leaderboardRows[$div] = $con->getStageLeaderboard($pageant_id, $div, $mode);
  }
  $current_leader = ($leaderboardRows['Ambassador'][0] ?? $leaderboardRows['Ambassadress'][0] ?? null);
}

// Data for awards tab
$awardGroups = [];
if ($tab === 'awards') {
    $awardGroups = $con->getPublicAwards($pageant_id);
}

// Note: Keep connection open for subsequent tab-specific queries below; we'll close at the end

$pageTitle = 'Results';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <div class="relative">
      <?php if ($has_open_round): ?>
        <div class="absolute inset-0 z-10 flex items-center justify-center">
          <div class="bg-black/60 rounded-xl px-6 py-4 border border-white/20 text-center">
            <div class="text-2xl font-bold text-white mb-1">Ongoing Round</div>
            <div class="text-slate-200">Results will reveal afterwards</div>
          </div>
        </div>
      <?php endif; ?>
      <div class="<?= $has_open_round ? 'pointer-events-none select-none blur-sm' : '' ?>">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Results</h1>
          <p class="text-slate-200">Centralized view for Leaderboard, Awards, and Tabulated data</p>
        </div>
        <div class="flex gap-3">
          <button onclick="location.reload()" class="bg-blue-500 bg-opacity-30 hover:bg-blue-600 hover:bg-opacity-40 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2 border border-white border-opacity-20 backdrop-blur-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
      <div class="inline-flex rounded-lg overflow-hidden border border-white border-opacity-20 bg-white bg-opacity-10 backdrop-blur">
        <?php
          $tabs = [
            'leaderboard' => 'Leaderboard',
            'awards' => 'Awards',
            'tabulated' => 'Tabulated Data'
          ];
          $i = 0;
          foreach ($tabs as $key => $label):
            $i++;
            $isActive = ($tab === $key);
            $classes = $isActive ? 'bg-blue-500 bg-opacity-30 text-white' : 'text-slate-200 hover:bg-white hover:bg-opacity-10';
            $border = $i < count($tabs) ? 'border-r border-white border-opacity-10' : '';
            // Build link preserving filters
            $href = 'results.php?tab=' . urlencode($key) . '&stage=' . urlencode($selected_stage) . '&round=' . urlencode($selected_round) . '&division=' . urlencode($selected_division);
        ?>
          <a href="<?= $href ?>" class="px-4 py-2 text-sm font-medium <?= $classes ?> <?= $border ?> inline-flex items-center gap-2">
            <?php if ($key === 'leaderboard'): ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <?php elseif ($key === 'awards'): ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            <?php else: ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v4a2 2 0 002 2h2m2-6h6a2 2 0 012 2v4a2 2 0 01-2 2h-6m2-6V4a2 2 0 00-2-2H9a2 2 0 00-2 2v1m2 0h4"/></svg>
            <?php endif; ?>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

  <?php if ($tab === 'leaderboard'): ?>
      <!-- Stats -->
      <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-slate-200">Total Participants</h3>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <p class="text-3xl font-bold text-white mb-1"><?php echo $participants_count; ?></p>
          <p class="text-sm text-slate-200">Active contestants</p>
        </div>
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-slate-200">Scored Rounds</h3>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <p class="text-3xl font-bold text-white mb-1"><?php echo $completed_rounds; ?></p>
          <p class="text-sm text-slate-200">Completed rounds</p>
        </div>
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-slate-200">Current Leader</h3>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
          </div>
          <p class="text-lg font-bold text-white mb-1"><?php echo ($current_leader && isset($current_leader['name'])) ? htmlspecialchars($current_leader['name']) : 'TBD'; ?></p>
          <p class="text-sm text-slate-200"><?php echo ($current_leader && (isset($current_leader['total_score']) || isset($current_leader['score']))) ? 'Score: ' . number_format((float)($current_leader['total_score'] ?? $current_leader['score'] ?? 0), 2) : 'When scores available'; ?></p>
        </div>
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-slate-200">Last Updated</h3>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <p class="text-lg font-bold text-white mb-1" id="lastUpdate">--:--</p>
          <p class="text-sm text-slate-200">Real-time updates</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 mb-8">
        <div class="px-6 py-4 border-b border-white border-opacity-10">
          <h3 class="text-lg font-semibold text-white">Leaderboard Filters</h3>
          <p class="text-sm text-slate-200 mt-1">Customize leaderboard view</p>
        </div>
        <div class="p-6 grid md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium text-slate-200 mb-2">Round</label>
            <select id="stageFilter" name="stage" class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-colors" onchange="updateFilters()">
              <option value="prelim" <?php echo $selected_stage === 'prelim' ? 'selected' : ''; ?>>Pageant (Pre-Q&A Overall)</option>
              <option value="final" <?php echo $selected_stage === 'final' ? 'selected' : ''; ?>>Final Q&A Round</option>
            </select>
          </div>
          <div class="hidden md:block"></div>
          <div class="flex items-end">
            <button onclick="refreshLeaderboard()" class="w-full bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-4 py-3 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Apply & Refresh</button>
          </div>
        </div>
      </div>

      <!-- Leaderboard Grids -->
      <div class="grid md:grid-cols-2 gap-6">
        <?php foreach (['Ambassador','Ambassadress'] as $div): $rows = $leaderboardRows[$div]; ?>
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
          <div class="px-6 py-4 border-b border-white border-opacity-10 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white"><?php echo $div; ?> Division</h3>
            <span class="px-2 py-1 text-xs rounded-full <?php echo $div==='Ambassador' ? 'bg-blue-400/20 text-blue-200' : 'bg-pink-400/20 text-pink-200'; ?>"><?php echo count($rows); ?> entries</span>
          </div>
          <div class="overflow-x-auto">
            <?php if (!empty($rows)): ?>
            <table class="w-full">
              <thead class="bg-white bg-opacity-10 border-b border-white border-opacity-10">
                <tr>
                  <th class="px-6 py-3 text-left text-sm font-semibold text-white">Rank</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold text-white">#</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold text-white">Name</th>
                  <th class="px-6 py-3 text-right text-sm font-semibold text-white">Score</th>
                  <th class="px-6 py-3 text-center text-sm font-semibold text-white">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/10">
                <?php foreach ($rows as $index => $participant): ?>
                <tr class="<?php echo $index % 2 === 0 ? 'bg-white/10' : 'bg-white/5'; ?> hover:bg-blue-500/10">
                  <td class="px-6 py-3 text-slate-200">#<?php echo $participant['rank']; ?></td>
                  <td class="px-6 py-3"><span class="inline-flex items-center px-2 py-1 rounded bg-white/10 text-white text-xs font-medium"><?php echo htmlspecialchars($participant['number_label']); ?></span></td>
                  <td class="px-6 py-3 text-white font-medium"><?php echo htmlspecialchars($participant['name']); ?></td>
                  <td class="px-6 py-3 text-right text-white font-mono font-semibold"><?php echo $participant['total_score'] ?? $participant['score'] ?? '--'; ?></td>
                  <td class="px-6 py-3 text-center">
                    <button class="text-blue-300 hover:text-blue-400 text-sm" onclick="openParticipantDetails(<?php echo (int)$participant['id']; ?>)">View</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php else: ?>
              <div class="p-8 text-center text-slate-200">No scores yet.</div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php elseif ($tab === 'awards'): ?>
      <!-- Awards Overview -->
      <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-slate-200">Total Participants</h3>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <p class="text-3xl font-bold text-white mb-1"><?php echo $participants_count; ?></p>
          <p class="text-sm text-slate-200">Active contestants</p>
        </div>
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-slate-200">Finalized Rounds</h3>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <p class="text-3xl font-bold text-white mb-1"><?php echo $finalized_rounds; ?></p>
          <p class="text-sm text-slate-200">Finalized rounds</p>
        </div>
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-slate-200">Award Status</h3>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
          </div>
          <p class="text-lg font-bold text-white mb-1"><?php echo $finalized_rounds > 0 ? 'Ready' : 'Pending'; ?></p>
          <p class="text-sm text-slate-200">Generation status</p>
        </div>
      </div>

      <?php if (!$all_final_rounds_completed): ?>
        <div class="mb-6 bg-white bg-opacity-10 border border-yellow-400 border-opacity-20 rounded-lg p-4 backdrop-blur-md">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <div>
              <h4 class="text-sm font-medium text-yellow-300">Final Round Not Complete</h4>
              <p class="text-sm text-yellow-200 mt-1">
                Awards are available after all final rounds are completed.
                <?php if ($total_final_rounds > 0): ?>
                  Currently <?php echo $final_rounds_completed; ?> of <?php echo $total_final_rounds; ?> final rounds completed.
                <?php else: ?>
                  No final rounds have been configured yet.
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
        <div class="px-6 py-4 border-b border-white border-opacity-10">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-white">Award Results</h3>
              <p class="text-sm text-slate-200 mt-1">Preview of configured awards and winners</p>
            </div>
            <div class="flex gap-2">
              <button onclick="autoGenerateAwards()" class="bg-purple-500/30 hover:bg-purple-600/40 text-white text-sm px-4 py-2 rounded border border-white/20">Auto-Generate</button>
              <button onclick="togglePublishAwards()" class="bg-emerald-500/30 hover:bg-emerald-600/40 text-white text-sm px-4 py-2 rounded border border-white/20">Publish / Hide</button>
            </div>
          </div>
        </div>
        <div class="p-6">
          <?php if (!empty($awardGroups)): ?>
            <div class="grid md:grid-cols-2 gap-6">
              <?php foreach ($awardGroups as $group): ?>
                <div class="border border-white border-opacity-10 rounded-lg p-4 bg-white bg-opacity-10">
                  <div class="flex items-center justify-between mb-2">
                    <h5 class="font-medium text-white"><?php echo htmlspecialchars($group['name']); ?></h5>
                    <span class="px-2 py-1 text-xs rounded-full <?php echo ($group['division_scope'] === 'Ambassador') ? 'bg-blue-400 bg-opacity-20 text-blue-200' : (($group['division_scope'] === 'Ambassadress') ? 'bg-pink-400 bg-opacity-20 text-pink-200' : 'bg-slate-400 bg-opacity-20 text-slate-200'); ?>">
                      <?php echo htmlspecialchars($group['division_scope']); ?>
                    </span>
                  </div>
                  <?php if (!empty($group['winners'])): ?>
                    <ul class="space-y-2">
                      <?php foreach ($group['winners'] as $winner): ?>
                        <li class="flex items-center justify-between text-sm text-slate-200">
                          <span>#<?php echo htmlspecialchars($winner['number_label']); ?> — <?php echo htmlspecialchars($winner['full_name']); ?></span>
                          <span class="text-xs text-slate-300">Pos <?php echo (int)$winner['position']; ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php else: ?>
                    <p class="text-sm text-slate-300">No winners configured yet.</p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center p-10">
              <p class="text-slate-200">No awards to display yet. Configure awards and winners, or generate once scoring is complete.</p>
              <div class="mt-4 text-slate-300 text-sm">Use the controls above to Auto-Generate and Publish/Hide awards. This is now the single place to manage awards.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($all_final_rounds_completed): ?>
      <?php
        // Build final leaderboard per division for proposal and options
  $leadersByDivision = ['Ambassador' => [], 'Ambassadress' => []];
  foreach (['Ambassador','Ambassadress'] as $div) {
            $stmtFL = $conn->prepare(
                "SELECT p.id, p.full_name, p.number_label, d.name as division,
                        SUM(COALESCE(s.override_score, s.raw_score) * (CASE WHEN rc.weight>1 THEN rc.weight/100.0 ELSE rc.weight END)) as total
                 FROM participants p
                 JOIN divisions d ON p.division_id = d.id
                 JOIN scores s ON s.participant_id = p.id
                 JOIN round_criteria rc ON rc.criterion_id = s.criterion_id
                 JOIN rounds r ON r.id = rc.round_id
                 WHERE r.pageant_id = ? AND r.scoring_mode = 'FINAL' AND r.state IN ('CLOSED','FINALIZED')
                   AND p.is_active=1 AND d.name = ?
                 GROUP BY p.id, p.full_name, p.number_label, d.name
                 ORDER BY total DESC, p.full_name ASC"
            );
            $stmtFL->bind_param('is', $pageant_id, $div);
            $stmtFL->execute();
            $resFL = $stmtFL->get_result();
            $leadersByDivision[$div] = $resFL->fetch_all(MYSQLI_ASSOC);
            $stmtFL->close();
        }
    // Current saved winners map for preselects (from canonical award_results)
  $savedMap = ['Ambassador'=>[], 'Ambassadress'=>[]];
    $stmtCW = $conn->prepare("SELECT a.division_scope, ar.position, ar.participant_id
               FROM awards a JOIN award_results ar ON ar.award_id = a.id
               WHERE a.pageant_id = ?");
        $stmtCW->bind_param('i', $pageant_id);
        $stmtCW->execute();
        $resCW = $stmtCW->get_result();
        while ($r = $resCW->fetch_assoc()) {
            $scope = $r['division_scope'];
      if ($scope === 'Ambassador' || $scope === 'Ambassadress') {
                $savedMap[$scope][(int)$r['position']] = (int)$r['participant_id'];
            }
        }
        $stmtCW->close();
      ?>
      <div class="mt-8 bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
        <div class="px-6 py-4 border-b border-white border-opacity-10 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-white">Assign Major Awards</h3>
            <p class="text-sm text-slate-200 mt-1">Propose from final leaderboard, then save winners</p>
          </div>
          <div class="flex gap-2">
            <button onclick="proposeWinners()" class="bg-blue-500 bg-opacity-30 hover:bg-blue-600/40 text-white font-medium px-4 py-2 rounded-lg border border-white border-opacity-20">Propose</button>
            <button onclick="saveWinners()" class="bg-green-500 bg-opacity-30 hover:bg-green-600/40 text-white font-medium px-4 py-2 rounded-lg border border-white border-opacity-20">Save Winners</button>
          </div>
        </div>
        <div class="p-6 grid md:grid-cols-2 gap-6">
          <?php foreach (['Ambassador','Ambassadress'] as $div): ?>
          <div class="border border-white border-opacity-10 rounded-lg p-4 bg-white bg-opacity-10">
            <h4 class="font-semibold text-white mb-4"><?php echo $div; ?> Division</h4>
            <?php
              $options = $leadersByDivision[$div];
              $optHtml = function($pidSel) use ($options) {
                $h = '<option value="">-- Select --</option>';
                foreach ($options as $row) {
                  $pid = (int)$row['id'];
                  $label = '#' . htmlspecialchars($row['number_label']) . ' — ' . htmlspecialchars($row['full_name']) . ' (' . number_format((float)($row['total'] ?? 0),2) . ')';
                  $sel = ($pidSel && $pidSel === $pid) ? ' selected' : '';
                  $h .= '<option value="' . $pid . '"' . $sel . '>' . $label . '</option>';
                }
                return $h;
              };
            ?>
            <div class="space-y-3">
              <label class="block">
                <span class="text-sm text-slate-200">Overall Winner (1st)</span>
                <select id="winner_<?php echo $div; ?>_1" class="mt-1 w-full bg-white bg-opacity-20 border border-white/30 rounded px-3 py-2 text-white text-sm"><?php echo $optHtml($savedMap[$div][1] ?? ($options[0]['id'] ?? null)); ?></select>
              </label>
              <label class="block">
                <span class="text-sm text-slate-200">1st Runner-up (2nd)</span>
                <select id="winner_<?php echo $div; ?>_2" class="mt-1 w-full bg-white bg-opacity-20 border border-white/30 rounded px-3 py-2 text-white text-sm"><?php echo $optHtml($savedMap[$div][2] ?? ($options[1]['id'] ?? null)); ?></select>
              </label>
              <label class="block">
                <span class="text-sm text-slate-200">2nd Runner-up (3rd)</span>
                <select id="winner_<?php echo $div; ?>_3" class="mt-1 w-full bg-white bg-opacity-20 border border-white/30 rounded px-3 py-2 text-white text-sm"><?php echo $optHtml($savedMap[$div][3] ?? ($options[2]['id'] ?? null)); ?></select>
              </label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <script>
      async function autoGenerateAwards() {
        try {
          const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
          url.searchParams.set('action', 'auto_generate_awards');
          const res = await fetch(url.toString(), { credentials: 'same-origin' });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.error||'Failed to generate');
          if (typeof showSuccess==='function') showSuccess('Generated','Awards generated from leaderboard');
          setTimeout(()=>window.location.reload(), 600);
        } catch (e) { if (typeof showError==='function') showError('Error', e.message); }
      }
      async function togglePublishAwards() {
        try {
          const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
          url.searchParams.set('action', 'toggle_publish_awards');
          const res = await fetch(url.toString(), { credentials: 'same-origin' });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.error||'Failed to toggle');
          if (typeof showSuccess==='function') showSuccess('Updated', 'Awards visibility toggled');
        } catch (e) { if (typeof showError==='function') showError('Error', e.message); }
      }
      function proposeWinners() {
        // Preselect top 3 per division based on the existing options order
        ['Ambassador','Ambassadress'].forEach(div => {
          for (let pos = 1; pos <= 3; pos++) {
            const sel = document.getElementById(`winner_${div}_${pos}`);
            if (sel && sel.options.length > pos) sel.selectedIndex = pos; // idx 1..3 maps to top3 with placeholder at 0
          }
        });
        if (typeof showNotification === 'function') showNotification('Proposed winners applied', 'success', true);
      }
      async function saveWinners() {
        const payload = { divisions: { Ambassador: [], Ambassadress: [] } };
        ['Ambassador','Ambassadress'].forEach(div => {
          for (let pos = 1; pos <= 3; pos++) {
            const sel = document.getElementById(`winner_${div}_${pos}`);
            const val = sel && sel.value ? parseInt(sel.value, 10) : null;
            payload.divisions[div][pos-1] = val;
          }
        });
        try {
          const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
          url.searchParams.set('action', 'save_awards');
          const res = await fetch(url.toString(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
          });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.error || 'Failed');
          if (typeof showSuccess === 'function') showSuccess('Saved', 'Awards saved successfully');
          setTimeout(() => { window.location.reload(); }, 600);
        } catch (e) {
          if (typeof showError === 'function') showError('Error', e.message || 'Failed to save awards');
        }
      }
      </script>
      <?php endif; ?>
    <?php else: ?>
      <?php
        // Build Tabulated Data: require a concrete round selection to show criteria breakdown
        $tab_round_id = ($selected_round === 'all' && !empty($rounds)) ? $rounds[0]['id'] : ($selected_round === 'all' ? null : (int)$selected_round);

        $criteria = [];
        $tableRows = [];
    if ($tab_round_id) {
  // Fetch criteria for this round (reuse existing connection)
      $stmt = $conn->prepare(
                "SELECT rc.criterion_id, rc.weight, rc.max_score, rc.display_order, c.name as criterion_name
                 FROM round_criteria rc
                 JOIN criteria c ON rc.criterion_id = c.id
                 WHERE rc.round_id = ?
                 ORDER BY rc.display_order"
            );
            $stmt->bind_param("i", $tab_round_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) { $criteria[] = $row; }
            $stmt->close();

    // Optional criterion filter
    $selected_criterion = isset($_GET['criterion']) ? $_GET['criterion'] : 'all';

    if ($selected_criterion !== 'all') {
      $criteria = array_values(array_filter($criteria, function($c) use ($selected_criterion) {
        return (string)$c['criterion_id'] === (string)$selected_criterion;
      }));
    }

    // Build participant rows with per-criterion weighted scores
      $divFilterSql = ($selected_division !== 'all') ? ' AND d.name = ?' : '';
      // Only bind division if filtering; no round param is needed for this participant list
      $types = ($selected_division !== 'all') ? 's' : '';
      $params = ($selected_division !== 'all') ? [$selected_division] : [];
            $sql = "SELECT p.id, p.full_name, p.number_label, d.name as division
                    FROM participants p
                    JOIN divisions d ON p.division_id = d.id
                    WHERE p.is_active = 1" . $divFilterSql . "
                    ORDER BY d.name, p.number_label";
      $stmtP = $conn->prepare($sql);
      if (!empty($params)) {
        $stmtP->bind_param($types, ...$params);
      }
            $stmtP->execute();
            $prs = $stmtP->get_result();
            $participants = $prs->fetch_all(MYSQLI_ASSOC);
            $stmtP->close();

          // Determine selected judge for Raw View
          $selected_judge = isset($_GET['judge']) ? (int)$_GET['judge'] : 0;
          // Preload scores for efficiency
            $participantIds = array_map(fn($p)=> (int)$p['id'], $participants);
            $scoresByPidCid = [];
      if (!empty($participantIds) && !empty($criteria)) {
                $inIds = implode(',', array_fill(0, count($participantIds), '?'));
                $critIds = array_map(fn($c)=> (int)$c['criterion_id'], $criteria);
                $inCrt = implode(',', array_fill(0, count($critIds), '?'));
                // Build dynamic bind
              $typesS = str_repeat('i', count($participantIds) + count($critIds)) . ($selected_judge ? 'i' : '');
              $sqlS = "SELECT participant_id, criterion_id, ".($selected_judge? 'raw_score':'COALESCE(override_score, raw_score)')." as score
                   FROM scores
                   WHERE participant_id IN ($inIds) AND criterion_id IN ($inCrt)" . ($selected_judge? " AND judge_user_id = ?" : "");
        $stmtS = $conn->prepare($sqlS);
              $bindVals = array_merge($participantIds, $critIds);
              if ($selected_judge) { $bindVals[] = $selected_judge; }
                $stmtS->bind_param($typesS, ...$bindVals);
                $stmtS->execute();
                $rsS = $stmtS->get_result();
                while ($r = $rsS->fetch_assoc()) {
                    $pid = (int)$r['participant_id'];
                    $cid = (int)$r['criterion_id'];
                    $scoresByPidCid[$pid][$cid] = (float)$r['score'];
                }
                $stmtS->close();
            }

            foreach ($participants as $p) {
                $row = [
                    'id' => (int)$p['id'],
                    'number_label' => $p['number_label'],
                    'name' => $p['full_name'],
                    'division' => $p['division'],
                    'criteria' => [],
                    'total' => 0.0
                ];
                $total = 0.0;
                foreach ($criteria as $c) {
                    $cid = (int)$c['criterion_id'];
                    $raw = $scoresByPidCid[$row['id']][$cid] ?? 0.0;
                if ($selected_judge) {
                  $weighted = $raw; // in raw view for single judge, show raw values
                } else {
                  $w = (float)$c['weight'];
                  $factor = ($w > 1.0) ? ($w/100.0) : $w;
                  $weighted = $raw * $factor;
                }
                    $row['criteria'][] = [
                        'criterion_id' => $cid,
                        'name' => $c['criterion_name'],
                        'weight' => (float)$c['weight'],
                        'raw' => $raw,
                        'weighted' => $weighted
                    ];
                    $total += $weighted;
                }
                $row['total'] = $total;
                $tableRows[] = $row;
            }
        }
      ?>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 mb-6">
        <div class="px-6 py-4 border-b border-white border-opacity-10 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-white">Tabulated Data</h3>
            <p class="text-sm text-slate-200 mt-1">Per-criterion breakdown for a selected round</p>
          </div>
          <div class="flex gap-2">
            <button onclick="exportCSV()" class="bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-4 py-2 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors text-sm">Export CSV</button>
          </div>
        </div>
  <div class="p-6 grid md:grid-cols-5 gap-6">
          <div>
            <label class="block text-sm font-medium text-slate-200 mb-2">Round</label>
            <select id="tabRound" class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white" onchange="onTabulatedFilterChange()">
              <?php foreach ($rounds as $r): ?>
                <option value="<?php echo $r['id']; ?>" <?php echo ($tab_round_id === (int)$r['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['name']); ?> (<?php echo ucfirst(strtolower($r['state'])); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-200 mb-2">Division</label>
            <select id="tabDivision" class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white" onchange="onTabulatedFilterChange()">
              <option value="all" <?php echo $selected_division === 'all' ? 'selected' : ''; ?>>All Divisions</option>
              <option value="Ambassador" <?php echo $selected_division === 'Ambassador' ? 'selected' : ''; ?>>Ambassador Division</option>
              <option value="Ambassadress" <?php echo $selected_division === 'Ambassadress' ? 'selected' : ''; ?>>Ambassadress Division</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-200 mb-2">Criterion</label>
            <?php
              $selected_criterion = isset($_GET['criterion']) ? $_GET['criterion'] : 'all';
            ?>
            <select id="tabCriterion" class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white" onchange="onTabulatedFilterChange()">
              <option value="all" <?php echo $selected_criterion==='all'?'selected':''; ?>>All Criteria</option>
              <?php foreach ($criteria as $c): ?>
                <option value="<?php echo (int)$c['criterion_id']; ?>" <?php echo ((string)$selected_criterion === (string)$c['criterion_id'])?'selected':''; ?>><?php echo htmlspecialchars($c['criterion_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php
            // Judges for selector (role must be 'JUDGE')
            $judges = [];
            $resJ = $conn->query("SELECT u.id, u.full_name FROM users u JOIN pageant_users pu ON pu.user_id=u.id WHERE pu.pageant_id=".(int)$pageant_id." AND LOWER(TRIM(pu.role))='judge' AND u.is_active=1");
            if ($resJ) { $judges = $resJ->fetch_all(MYSQLI_ASSOC); }
            $selected_judge = isset($_GET['judge']) ? (int)$_GET['judge'] : 0;
          ?>
          <div>
            <label class="block text-sm font-medium text-slate-200 mb-2">Judge</label>
            <select id="tabJudge" class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white" onchange="onTabulatedFilterChange()">
              <option value="0" <?php echo $selected_judge===0? 'selected':''; ?>>All Judges (Weighted)</option>
              <?php foreach ($judges as $j): ?>
                <option value="<?php echo (int)$j['id']; ?>" <?php echo $selected_judge===(int)$j['id']? 'selected':''; ?>><?php echo htmlspecialchars($j['full_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
        <div class="overflow-x-auto">
          <?php if ($tab_round_id && !empty($criteria)): ?>
            <table class="w-full">
              <thead class="bg-white bg-opacity-10 border-b border-white border-opacity-10">
                <tr>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-white">Number</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-white">Name</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-white">Division</th>
                  <?php foreach ($criteria as $c): ?>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-white"><?php echo htmlspecialchars($c['criterion_name']); ?>
                      <?php if (($selected_judge ?? 0) === 0): ?>
                        <span class="text-xs text-slate-300">(<?php echo number_format(((float)$c['weight']>1?$c['weight']:$c['weight']*100), 0); ?>%)</span>
                      <?php else: ?>
                        <span class="text-xs text-slate-300">(raw)</span>
                      <?php endif; ?>
                    </th>
                  <?php endforeach; ?>
                  <th class="px-6 py-4 text-right text-sm font-semibold text-white"><?php echo ($selected_judge ?? 0)===0? 'Total (weighted)':'Total (raw sum)'; ?></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white divide-opacity-5">
                <?php foreach ($tableRows as $row): ?>
                  <tr class="hover:bg-blue-500 hover:bg-opacity-10 transition-colors duration-200">
                    <td class="px-6 py-3 text-slate-200"><?php echo htmlspecialchars($row['number_label']); ?></td>
                    <td class="px-6 py-3 text-white font-medium"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="px-6 py-3">
                      <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php echo ($row['division'] === 'Ambassador') ? 'bg-blue-500 bg-opacity-20 text-blue-200' : 'bg-pink-500 bg-opacity-20 text-pink-200'; ?>"><?php echo htmlspecialchars($row['division']); ?></span>
                    </td>
                    <?php foreach ($row['criteria'] as $cell): ?>
                      <td class="px-6 py-3 text-right text-slate-100 font-mono text-sm">
                        <?php if (($selected_judge ?? 0) !== 0): ?>
                          <button class="underline decoration-dotted hover:text-white" title="Click to edit raw score"
                            onclick="openEditScore(<?php echo (int)$row['id']; ?>, <?php echo (int)$cell['criterion_id']; ?>, '<?php echo htmlspecialchars('#'.$row['number_label'].' — '.addslashes($row['name']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($cell['name']), ENT_QUOTES); ?>')">
                            <?php echo number_format($cell['raw'], 2); ?>
                          </button>
                        <?php else: ?>
                          <span title="Raw: <?php echo number_format($cell['raw'], 2); ?> | Weight: <?php echo number_format($cell['weight']>1?$cell['weight']:$cell['weight']*100, 0); ?>%">
                            <?php echo number_format($cell['weighted'], 2); ?>
                          </span>
                        <?php endif; ?>
                      </td>
                    <?php endforeach; ?>
                    <td class="px-6 py-3 text-right text-white font-semibold font-mono"><?php echo number_format($row['total'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="p-10 text-center">
              <p class="text-slate-200">Select a round with configured criteria to view tabulated data.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

      </div>
    </div>

<script>
function refreshLeaderboard() {
  const last = document.getElementById('lastUpdate');
  if (last) last.textContent = new Date().toLocaleTimeString();
  if (typeof showNotification === 'function') {
    showNotification('Refreshing...', 'info', true);
  }
  setTimeout(() => { location.reload(); }, 400);
}

function updateFilters() {
  const stageFilter = document.getElementById('stageFilter');
  const url = new URL(window.location);
  if (stageFilter) {
    const val = stageFilter.value;
    url.searchParams.set('stage', val);
    url.searchParams.set('round', 'all');
  }
  window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
  const last = document.getElementById('lastUpdate');
  if (last) last.textContent = new Date().toLocaleTimeString();
});

// Auto-refresh indicator every 30s (no hard reload)
setInterval(function() {
  if (document.visibilityState === 'visible') {
    const last = document.getElementById('lastUpdate');
    if (last) last.textContent = new Date().toLocaleTimeString();
  }
}, 30000);

// Tabulated filters
function onTabulatedFilterChange() {
  const tabRound = document.getElementById('tabRound');
  const tabDivision = document.getElementById('tabDivision');
  const tabCriterion = document.getElementById('tabCriterion');
  const tabJudge = document.getElementById('tabJudge');
  const url = new URL(window.location);
  url.searchParams.set('tab', 'tabulated');
  if (tabRound) url.searchParams.set('round', tabRound.value);
  if (tabDivision) url.searchParams.set('division', tabDivision.value);
  if (tabCriterion) url.searchParams.set('criterion', tabCriterion.value);
  if (tabJudge) url.searchParams.set('judge', tabJudge.value);
  window.location.href = url.toString();
}

// CSV export for current Tabulated table
function exportCSV() {
  const table = document.querySelector('div[id^="leaderboardContent"]') ? null : document.querySelector('table');
  const rows = [];
  if (!table) return;
  table.querySelectorAll('tr').forEach(tr => {
    const cols = Array.from(tr.querySelectorAll('th,td')).map(td => {
      const text = td.innerText.replace(/\s+/g, ' ').trim();
      if (text.includes(',') || text.includes('"')) {
        return '"' + text.replace(/"/g, '""') + '"';
      }
      return text;
    });
    rows.push(cols.join(','));
  });
  const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'tabulated_data.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}
</script>

<?php
// Inject details modal component using existing modal template
$modalId = 'participantDetailsModal';
$title = 'Participant Details';
$bodyHtml = '<div id="participantDetailsBody" class="space-y-3 text-slate-200 text-sm">Loading...</div>'
          . '<div class="pt-4"><button onclick="hideModal(\'participantDetailsModal\')" class="w-full bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-6 py-3 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Close</button></div>';
$footerHtml = '';
$hideCloseButton = true; // remove redundant header close; we already render a Cancel/Close control in body
include __DIR__ . '/../components/modal.php';
?>

<script>
let editContext = { participantId: 0, criterionId: 0 };
function openEditScore(pid, cid, participantLabel, criterionName) {
  editContext = { participantId: pid, criterionId: cid };
  const body = document.getElementById('participantDetailsBody');
  const judgeSel = document.getElementById('tabJudge');
  const judgeId = judgeSel ? parseInt(judgeSel.value, 10) : 0;
  const judgeName = judgeSel && judgeSel.selectedOptions && judgeSel.selectedOptions[0] ? judgeSel.selectedOptions[0].textContent : '';
  body.innerHTML = `
    <div class="space-y-3">
      <div class="text-white">${participantLabel}</div>
      <div class="text-slate-200 text-sm">Criterion: ${criterionName}</div>
      ${judgeId ? `<div class=\"text-slate-200 text-sm\">Judge: <span class=\"text-white font-medium\">${judgeName}</span></div>` : `<div class=\"text-yellow-200 text-sm\">Select a Judge first to edit raw scores.</div>`}
      <div>
        <label class="block text-sm text-slate-200 mb-1">New Raw Score</label>
        <input id="overrideScoreInput" type="number" step="0.01" class="w-full bg-white/20 border border-white/30 rounded px-3 py-2 text-white" />
      </div>
      <div>
        <label class="block text-sm text-slate-200 mb-1">Judge Password</label>
        <input id="overrideJudgePass" type="password" autocomplete="current-password" class="w-full bg-white/20 border border-white/30 rounded px-3 py-2 text-white" placeholder="Enter judge password" />
      </div>
      <div>
        <label class="block text-sm text-slate-200 mb-1">Reason (required)</label>
        <textarea id="overrideReasonInput" rows="3" class="w-full bg-white/20 border border-white/30 rounded px-3 py-2 text-white"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-2 pt-2">
        <button onclick="submitOverride()" class="bg-green-500/30 hover:bg-green-600/40 text-white px-4 py-2 rounded border border-white/20">Save</button>
        <button onclick="hideModal('participantDetailsModal')" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded border border-white/20">Cancel</button>
      </div>
    </div>`;
  showModal('participantDetailsModal');
}
async function submitOverride() {
  const score = parseFloat(document.getElementById('overrideScoreInput').value);
  const reason = (document.getElementById('overrideReasonInput').value||'').trim();
  const judge = document.getElementById('tabJudge') ? parseInt(document.getElementById('tabJudge').value, 10) : 0;
  const judgePassword = (document.getElementById('overrideJudgePass')?.value || '').trim();
  if (!judge) { if (typeof showError==='function') showError('Error','Select a Judge to edit raw scores'); return; }
  if (isNaN(score)) { if (typeof showError==='function') showError('Error','Enter a valid score'); return; }
  if (!reason) { if (typeof showError==='function') showError('Error','Reason is required'); return; }
  if (!judgePassword) { if (typeof showError==='function') showError('Error','Judge password is required'); return; }
  try {
    const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
    url.searchParams.set('action','override_score');
    const body = { participant_id: editContext.participantId, criterion_id: editContext.criterionId, judge_user_id: judge, raw_score: score, reason, judge_password: judgePassword };
    const res = await fetch(url.toString(), { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify(body) });
    const data = await res.json();
    if (!res.ok || !data.success) throw new Error(data.error||'Failed');
    if (typeof showSuccess==='function') showSuccess('Saved','Score overridden');
    setTimeout(()=>{ window.location.reload(); }, 600);
  } catch (e) { if (typeof showError==='function') showError('Error', e.message); }
}
async function openParticipantDetails(id) {
  try {
    const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
  const urlCurrent = new URL(window.location);
  const roundParam = urlCurrent.searchParams.get('round') || 'all';
  const stageParam = urlCurrent.searchParams.get('stage') || 'overall';
    url.searchParams.set('action', 'participant_details');
    url.searchParams.set('participant_id', id);
    url.searchParams.set('round_id', roundParam);
  url.searchParams.set('stage', stageParam);
    const res = await fetch(url.toString(), { credentials: 'same-origin' });
    const data = await res.json();
    const body = document.getElementById('participantDetailsBody');
    if (!res.ok || !data.success) {
      body.innerHTML = '<p class="text-red-300">Failed to load details.</p>';
    } else {
      let html = '';
      html += '<div class="grid grid-cols-1 gap-2">';
      data.items.forEach(item => {
        html += `<div class="flex items-center justify-between bg-white bg-opacity-10 border border-white/10 rounded px-3 py-2">
                  <div>
                    <div class="text-white font-medium">${item.name}</div>
                    <div class="text-xs text-slate-300">Weight: ${Number(item.weight).toFixed(0)}%</div>
                  </div>
                  <div class="text-right">
                    <div class="text-slate-200 text-sm">Raw: ${Number(item.raw).toFixed(2)}</div>
                    <div class="text-white font-semibold">Weighted: ${Number(item.weighted).toFixed(2)}</div>
                  </div>
                </div>`;
      });
      html += '</div>';
      html += `<div class="mt-3 text-right text-white font-semibold">Total: ${Number(data.total).toFixed(2)}</div>`;
      body.innerHTML = html;
    }
    showModal('participantDetailsModal');
  } catch (e) {
    const body = document.getElementById('participantDetailsBody');
    body.innerHTML = '<p class="text-red-300">Error loading details.</p>';
    showModal('participantDetailsModal');
  }
}
</script>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>

<?php // close the DB connection at the end
if ($conn) { $conn->close(); }
?>
