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

// Derived dashboard metrics and datasets
// 1) Round states
$has_open_round = false;
$stmtOpen = $conn->prepare("SELECT COUNT(*) AS c FROM rounds WHERE pageant_id=? AND state='OPEN'");
$stmtOpen->bind_param('i', $pageant_id);
$stmtOpen->execute();
$rOpen = $stmtOpen->get_result()->fetch_assoc();
$stmtOpen->close();
$has_open_round = ($rOpen && (int)$rOpen['c'] > 0);

$stmtComp = $conn->prepare("SELECT COUNT(*) AS c FROM rounds WHERE pageant_id=? AND state IN ('CLOSED','FINALIZED')");
$stmtComp->bind_param('i', $pageant_id);
$stmtComp->execute();
$rComp = $stmtComp->get_result()->fetch_assoc();
$stmtComp->close();
$completed_rounds = (int)($rComp['c'] ?? 0);
$finalized_rounds = $completed_rounds;

// 2) Prelim gating for awards
$stmtPre = $conn->prepare("SELECT COUNT(*) AS c FROM rounds WHERE pageant_id=? AND scoring_mode='PRELIM' AND state IN ('CLOSED','FINALIZED')");
$stmtPre->bind_param('i', $pageant_id);
$stmtPre->execute();
$rPre = $stmtPre->get_result()->fetch_assoc();
$stmtPre->close();
$prelim_rounds_closed = (int)($rPre['c'] ?? 0);
$awards_prelim_ready = ($prelim_rounds_closed >= 7); // Updated threshold: now requires seven prelim rounds

// 3) Leaderboard datasets per division based on stage filter
// Leaderboard computation with round-level weighting
$leaderboardRows = ['Ambassador'=>[], 'Ambassadress'=>[]];
$stageFilter = ($selected_stage === 'final') ? 'FINAL' : 'PRELIM';

// Fetch sum of overall_weight for closed/finalized rounds in this stage (fallback equal if NULLs)
$stmtW = $conn->prepare("SELECT id, COALESCE(overall_weight,0) AS w FROM rounds WHERE pageant_id=? AND scoring_mode=? AND state IN ('CLOSED','FINALIZED')");
$stmtW->bind_param('is', $pageant_id, $stageFilter);
$stmtW->execute();
$resW = $stmtW->get_result();
$roundWeights = []; $sumWeights = 0.0; $allZero = true;
while ($rw = $resW->fetch_assoc()) {
  $roundWeights[(int)$rw['id']] = (float)$rw['w'];
  if ((float)$rw['w'] > 0) { $sumWeights += (float)$rw['w']; $allZero = false; }
}
$stmtW->close();

// If all weights are zero (unset), treat each closed round equally.
if ($allZero) {
  $count = count($roundWeights) ?: 1;
  foreach ($roundWeights as $rid => $_) { $roundWeights[$rid] = 100.0 / $count; }
  $sumWeights = 100.0;
}

foreach (['Ambassador','Ambassadress'] as $div) {
  // Per-round normalized scores first
  $stmtLb = $conn->prepare(
    "WITH per_round AS (
        SELECT p.id AS participant_id, p.full_name, p.number_label, r.id AS round_id,
               SUM(CASE WHEN rc.max_score>0 THEN (COALESCE(s.override_score, s.raw_score)/rc.max_score) * (CASE WHEN rc.weight>1 THEN rc.weight/100.0 ELSE rc.weight END) ELSE 0 END) AS round_norm
        FROM participants p
        JOIN divisions d ON p.division_id=d.id
        JOIN scores s ON s.participant_id=p.id
        JOIN round_criteria rc ON rc.criterion_id=s.criterion_id AND rc.round_id = s.round_id
        JOIN rounds r ON r.id=rc.round_id
        WHERE p.pageant_id=? AND p.is_active=1 AND d.name=?
          AND r.scoring_mode=? AND r.state IN ('CLOSED','FINALIZED')
        GROUP BY p.id, p.full_name, p.number_label, r.id
    )
    SELECT pr.participant_id AS id, pr.full_name AS name, pr.number_label,
           SUM(pr.round_norm * (? / NULLIF(?,0)) * (CASE WHEN r.overall_weight IS NULL OR r.overall_weight=0 THEN 1 ELSE r.overall_weight END)) AS weighted_sum,
           SUM(CASE WHEN r.overall_weight IS NULL OR r.overall_weight=0 THEN 0 ELSE r.overall_weight END) AS used_weights
    FROM per_round pr
    JOIN rounds r ON r.id = pr.round_id
    GROUP BY pr.participant_id, pr.full_name, pr.number_label"
  );
  // We pass a scaling factor so that if weights are defined they normalize to 100; if they were all zero, we already substituted equal distribution.
  // Factor logic: we want (round_norm * round_weight / sumWeights)*100. We'll pass 100 and sumWeights as params, and inside query multiply by round_weight.
  $hundred = 100.0; $sumParam = $sumWeights ?: 100.0;
  $stmtLb->bind_param('issdd', $pageant_id, $div, $stageFilter, $hundred, $sumParam);
  $stmtLb->execute();
  $resLb = $stmtLb->get_result();
  $rowsTmp = [];
  while ($row = $resLb->fetch_assoc()) {
    $total = (float)($row['weighted_sum'] ?? 0);
    // If allZero we scaled manually by equal distribution but the query multiplies by raw weights (which were set to equal 100/count); we still divided by sumWeights (100) and multiplied by 100 -> correct 0..100.
    $rowsTmp[] = [
      'id' => (int)$row['id'],
      'name' => $row['name'],
      'number_label' => $row['number_label'],
      'total_score' => number_format($total, 2)
    ];
  }
  $stmtLb->close();
  // Sort (descending) and assign ranks
  usort($rowsTmp, function($a,$b){ return (float)$b['total_score'] <=> (float)$a['total_score']; });
  $ranked = []; $rk=1; foreach ($rowsTmp as $r) { $r['rank']=$rk++; $ranked[]=$r; }
  $leaderboardRows[$div] = $ranked;
}

// 4) Current leader overall
$current_leader = null;
$allRows = array_merge($leaderboardRows['Ambassador'], $leaderboardRows['Ambassadress']);
if (!empty($allRows)) {
  usort($allRows, function($a,$b){ return (float)$b['total_score'] <=> (float)$a['total_score']; });
  $top = $allRows[0];
  $current_leader = ['name' => $top['name'], 'total_score' => $top['total_score']];
}

$pageTitle = 'Results';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <div class="relative">
      <?php if ($has_open_round && $tab !== 'tabulated'): ?>
        <div class="absolute inset-0 z-10 flex items-center justify-center">
          <div class="bg-black/60 rounded-xl px-6 py-4 border border-white/20 text-center">
            <div class="text-2xl font-bold text-white mb-1">Ongoing Round</div>
            <div class="text-slate-200">Results will reveal afterwards</div>
          </div>
        </div>
      <?php endif; ?>
      <div class="<?= ($has_open_round && $tab !== 'tabulated') ? 'pointer-events-none select-none blur-sm' : '' ?>">
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
            <label class="block text-sm font-medium text-slate-200 mb-2">Stage</label>
            <select id="stageFilter" name="stage" class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-colors" onchange="updateFilters()">
              <option value="prelim" <?php echo $selected_stage === 'prelim' ? 'selected' : ''; ?>>Pre-QnA / Prelims (Weighted)</option>
              <option value="final" <?php echo $selected_stage === 'final' ? 'selected' : ''; ?>>Finals (Weighted)</option>
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
            <div class="flex items-center gap-2">
              <button onclick="toggleSortLB('<?php echo $div; ?>')" class="text-xs px-2 py-1 rounded bg-white/10 hover:bg-white/20 text-white border border-white/20">Sort: <span id="lbSortLabel_<?php echo $div; ?>">High→Low</span></button>
              <span class="px-2 py-1 text-xs rounded-full <?php echo $div==='Ambassador' ? 'bg-blue-400/20 text-blue-200' : 'bg-pink-400/20 text-pink-200'; ?>"><?php echo count($rows); ?> entries</span>
            </div>
          </div>
          <div class="overflow-x-auto">
            <?php if (!empty($rows)): ?>
            <table id="lbTable_<?php echo $div; ?>" class="w-full">
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

      <?php if (!$awards_prelim_ready): ?>
        <div class="mb-6 bg-white bg-opacity-10 border border-yellow-400 border-opacity-20 rounded-lg p-4 backdrop-blur-md">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <div>
              <h4 class="text-sm font-medium text-yellow-300">Awards Not Ready</h4>
              <p class="text-sm text-yellow-200 mt-1">Awards become available after at least seven Pre-Q&A (Prelim) rounds are closed.<br/>Currently <?php echo $prelim_rounds_closed; ?> prelim round(s) closed.</p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Award Results preview panel removed per request -->

  <?php if ($awards_prelim_ready): ?>
      <?php
        // Build Pre-Q&A (PRELIM) leaderboard per division for proposal and options
  $leadersByDivision = ['Ambassador' => [], 'Ambassadress' => []];
  foreach (['Ambassador','Ambassadress'] as $div) {
            $stmtFL = $conn->prepare(
                "SELECT p.id, p.full_name, p.number_label, d.name as division,
                        SUM(
                          CASE WHEN rc.max_score IS NOT NULL AND rc.max_score > 0
                               THEN (COALESCE(s.override_score, s.raw_score) / rc.max_score) * (CASE WHEN rc.weight>1 THEN rc.weight/100.0 ELSE rc.weight END)
                               ELSE 0
                          END
                        ) * 100.0 as total
                 FROM participants p
                 JOIN divisions d ON p.division_id = d.id
                 JOIN scores s ON s.participant_id = p.id
                 JOIN round_criteria rc ON rc.criterion_id = s.criterion_id AND rc.round_id = s.round_id
                 JOIN rounds r ON r.id = rc.round_id
                 WHERE r.pageant_id = ? AND r.scoring_mode = 'PRELIM' AND r.state IN ('CLOSED','FINALIZED')
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
            <p class="text-sm text-slate-200 mt-1">Propose from Pre-Q&A leaderboard (after Round 6), then save winners</p>
          </div>
          <div class="flex gap-2">
            <button onclick="proposeWinners()" class="bg-blue-500 bg-opacity-30 hover:bg-blue-600/40 text-white font-medium px-4 py-2 rounded-lg border border-white border-opacity-20">Propose Top 3</button>
            <button onclick="saveWinners()" class="bg-green-500 bg-opacity-30 hover:bg-green-600/40 text-white font-medium px-4 py-2 rounded-lg border border-white border-opacity-20">Save Winners (Top 3)</button>
            <button onclick="togglePublishMajorAwards()" class="bg-emerald-500/30 hover:bg-emerald-600/40 text-white font-medium px-4 py-2 rounded-lg border border-white/20">Publish/Hide Major</button>
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
          if (typeof showSuccess==='function') showSuccess('Updated', `Awards ${data.visibility_state === 'REVEALED' ? 'published' : 'hidden'}`);
          setTimeout(()=>window.location.reload(), 600);
        } catch (e) { if (typeof showError==='function') showError('Error', e.message); }
      }
      async function togglePublishMajorAwards() {
        try {
          const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
          url.searchParams.set('action', 'toggle_publish_major_awards');
          const res = await fetch(url.toString(), { credentials: 'same-origin' });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.error||'Failed to toggle');
          if (typeof showSuccess==='function') showSuccess('Updated', `Major awards ${data.visibility_state === 'REVEALED' ? 'published' : 'hidden'}`);
          setTimeout(()=>window.location.reload(), 600);
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
      <!-- Special Awards (Calculate/Select/Save + Publish toggle) -->
      <?php
        // Compute current special awards published state
        $special_codes = ['BEST_ADVOCACY','BEST_TALENT','BEST_PRODUCTION','BEST_UNIFORM','BEST_SPORTS','BEST_FORMAL','PHOTOGENIC','PEOPLES_CHOICE','CONGENIALITY'];
        $placeholders = implode(',', array_fill(0, count($special_codes), '?'));
        $typesSp = 'i' . str_repeat('s', count($special_codes));
        $paramsSp = array_merge([$pageant_id], $special_codes);
        $stmtSp = $conn->prepare("SELECT COUNT(*) AS cnt FROM awards WHERE pageant_id=? AND code IN ($placeholders) AND visibility_state='REVEALED'");
        $stmtSp->bind_param($typesSp, ...$paramsSp);
        $stmtSp->execute();
        $rowSp = $stmtSp->get_result()->fetch_assoc();
        $stmtSp->close();
        $special_published = ($rowSp && (int)$rowSp['cnt'] > 0);

        // Participants per division for selectors
        $divisions = ['Ambassador','Ambassadress'];
        $participantsByDiv = [];
        foreach ($divisions as $div) {
          $stmt = $conn->prepare("SELECT p.id, p.number_label, p.full_name FROM participants p JOIN divisions d ON p.division_id=d.id WHERE p.pageant_id=? AND p.is_active=1 AND d.name=? ORDER BY p.number_label");
          $stmt->bind_param('is', $pageant_id, $div);
          $stmt->execute();
          $res = $stmt->get_result();
          $participantsByDiv[$div] = $res->fetch_all(MYSQLI_ASSOC);
          $stmt->close();
        }
      ?>
      <div class="mt-8 bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
        <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-white">Special Awards</h3>
            <p class="text-sm text-slate-200 mt-1">Calculate, review, adjust, then save all special awardees.</p>
          </div>
          <div class="flex items-center gap-4">
            <button onclick="calculateSpecialAwards()" class="bg-purple-500/30 hover:bg-purple-600/40 text-white text-sm px-4 py-2 rounded border border-white/20">Calculate Special Awardees</button>
            <label class="flex items-center gap-2 text-slate-200 text-sm">
              <span>Public</span>
              <input id="specialPublishToggle" type="checkbox" <?php echo $special_published ? 'checked' : ''; ?> onchange="onToggleSpecialPublish(this)" class="appearance-none w-10 h-6 rounded-full bg-white/20 border border-white/20 relative cursor-pointer">
            </label>
          </div>
        </div>
        <div class="p-6">
          <div class="grid md:grid-cols-2 gap-6">
            <?php
              $categories = [
                ['code'=>'BEST_ADVOCACY','label'=>'Best in Advocacy'],
                ['code'=>'BEST_TALENT','label'=>'Best in Talent'],
                ['code'=>'BEST_PRODUCTION','label'=>'Best in Production Number'],
                ['code'=>'BEST_UNIFORM','label'=>'Best in Uniform Wear'],
                ['code'=>'BEST_SPORTS','label'=>'Best in Sports Wear'],
                ['code'=>'BEST_FORMAL','label'=>'Best in Formal Wear'],
                ['code'=>'PHOTOGENIC','label'=>'Photogenic'],
                ['code'=>'PEOPLES_CHOICE','label'=>"People's Choice"],
                ['code'=>'CONGENIALITY','label'=>'Congeniality']
              ];
            ?>
            <?php foreach ($categories as $cat): ?>
            <div class="border border-white/10 rounded-lg p-4 bg-white/10">
              <h4 class="font-semibold text-white mb-3"><?php echo htmlspecialchars($cat['label']); ?></h4>
              <div class="grid grid-cols-1 gap-3">
                <?php foreach ($divisions as $div): ?>
                <label class="block">
                  <span class="text-sm text-slate-200"><?php echo $div; ?></span>
                  <select id="spec_<?php echo $cat['code']; ?>_<?php echo $div; ?>" class="mt-1 w-full bg-white/20 border border-white/30 rounded px-3 py-2 text-white text-sm">
                    <option value="0">-- Select --</option>
                    <?php foreach ($participantsByDiv[$div] as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>">#<?php echo htmlspecialchars($p['number_label']); ?> — <?php echo htmlspecialchars($p['full_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-6 flex items-center justify-end gap-2">
            <button onclick="saveSpecialPicks()" class="bg-blue-500/30 hover:bg-blue-600/40 text-white text-sm px-4 py-2 rounded border border-white/20">Save Special Awards</button>
          </div>
        </div>
      </div>
      <script>
      async function onToggleSpecialPublish(el) {
        try {
          const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
          url.searchParams.set('action', 'toggle_publish_special_awards');
          const res = await fetch(url.toString(), { credentials:'same-origin' });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.error||'Failed to toggle');
          if (typeof showSuccess==='function') showSuccess('Updated', `Special awards ${data.visibility_state==='REVEALED'?'published':'hidden'}`);
          el.checked = data.visibility_state==='REVEALED';
        } catch (e) { if (typeof showError==='function') showError('Error', e.message); el.checked = !el.checked; }
      }
      async function calculateSpecialAwards() {
        try {
          const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
          url.searchParams.set('action', 'calculate_special_awards');
          const res = await fetch(url.toString(), { credentials:'same-origin' });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.error||'Failed to calculate');
          // data.picks: { CODE: { Ambassador: pid, Ambassadress: pid } }
          Object.keys(data.picks||{}).forEach(code => {
            const perDiv = data.picks[code];
            Object.keys(perDiv).forEach(div => {
              const el = document.getElementById(`spec_${code}_${div}`);
              if (el) el.value = String(perDiv[div]||0);
            });
          });
          // Auto-save Best-in categories immediately; leave manual categories for admin to adjust
          const bestCodes = ['BEST_ADVOCACY','BEST_TALENT','BEST_PRODUCTION','BEST_UNIFORM','BEST_SPORTS','BEST_FORMAL'];
          const picksToSave = { picks: {} };
          bestCodes.forEach(code => { if (data.picks && data.picks[code]) { picksToSave.picks[code] = data.picks[code]; } });
          if (Object.keys(picksToSave.picks).length > 0) {
            const saveUrl = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
            saveUrl.searchParams.set('action', 'save_special_awards_picks');
            const saveRes = await fetch(saveUrl.toString(), { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify(picksToSave) });
            const saveData = await saveRes.json();
            if (!saveRes.ok || !saveData.success) throw new Error(saveData.error||'Failed to save best-in awards');
          }
          if (typeof showSuccess==='function') showSuccess('Calculated', 'Best-in awardees computed and saved. Adjust manual awards as needed.');
        } catch (e) { if (typeof showError==='function') showError('Error', e.message); }
      }
      async function saveSpecialPicks() {
        // Collect picks from all selectors
        const codes = ['BEST_ADVOCACY','BEST_TALENT','BEST_PRODUCTION','BEST_UNIFORM','BEST_SPORTS','BEST_FORMAL','PHOTOGENIC','PEOPLES_CHOICE','CONGENIALITY'];
        const payload = { picks: {} };
        codes.forEach(code => {
          payload.picks[code] = {};
          ['Ambassador','Ambassadress'].forEach(div => {
            const el = document.getElementById(`spec_${code}_${div}`);
            payload.picks[code][div] = el ? parseInt(el.value||'0', 10) : 0;
          });
        });
        try {
          const url = new URL(window.location.origin + window.location.pathname.replace(/\/admin\/results\.php$/, '/admin/api_results.php'));
          url.searchParams.set('action', 'save_special_awards_picks');
          const res = await fetch(url.toString(), { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify(payload) });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.error||'Failed to save');
          if (typeof showSuccess==='function') showSuccess('Saved', 'Special awards saved');
        } catch (e) { if (typeof showError==='function') showError('Error', e.message); }
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
                if ($selected_judge) {
                  // Raw view for a single judge: use raw_score for that judge only
                  $typesS = str_repeat('i', count($participantIds) + count($critIds)) . 'i';
                  $sqlS = "SELECT participant_id, criterion_id, raw_score AS score\n                           FROM scores\n                           WHERE participant_id IN ($inIds) AND criterion_id IN ($inCrt) AND judge_user_id = ?";
                  $bindVals = array_merge($participantIds, $critIds, [$selected_judge]);
                } else {
                  // All Judges (Weighted): average across all judges using override when present
                  $typesS = str_repeat('i', count($participantIds) + count($critIds));
                  $sqlS = "SELECT participant_id, criterion_id, AVG(COALESCE(override_score, raw_score)) AS score\n                           FROM scores\n                           WHERE participant_id IN ($inIds) AND criterion_id IN ($inCrt)\n                           GROUP BY participant_id, criterion_id";
                  $bindVals = array_merge($participantIds, $critIds);
                }
                $stmtS = $conn->prepare($sqlS);
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
                  $maxScore = isset($c['max_score']) ? (float)$c['max_score'] : 0.0;
                  $weighted = ($maxScore > 0) ? (($raw / $maxScore) * $factor * 100.0) : 0.0;
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
            <button onclick="toggleTabSort()" class="bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-4 py-2 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors text-sm">Sort Total: <span id="tabSortLabel">High→Low</span></button>
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

// Sorting utilities
function parseNumeric(text) {
  if (!text) return 0;
  const n = parseFloat(String(text).replace(/[^0-9.\-]/g, ''));
  return isNaN(n) ? 0 : n;
}

// Leaderboard sorting
const lbSortDir = { 'Ambassador': 'desc', 'Ambassadress': 'desc' };
function toggleSortLB(div) {
  lbSortDir[div] = lbSortDir[div] === 'desc' ? 'asc' : 'desc';
  const label = document.getElementById('lbSortLabel_' + div);
  if (label) label.textContent = lbSortDir[div] === 'desc' ? 'High→Low' : 'Low→High';
  sortLeaderboardTable(div, lbSortDir[div]);
}

function sortLeaderboardTable(div, dir) {
  const table = document.getElementById('lbTable_' + div);
  if (!table) return;
  const tbody = table.querySelector('tbody');
  if (!tbody) return;
  const rows = Array.from(tbody.querySelectorAll('tr'));
  // Score is the 4th column (index 3)
  rows.sort((a,b) => {
    const av = parseNumeric(a.children[3]?.innerText);
    const bv = parseNumeric(b.children[3]?.innerText);
    return dir === 'desc' ? (bv - av) : (av - bv);
  });
  // Re-attach rows and re-number Rank (#1..)
  rows.forEach((tr, idx) => {
    const rankCell = tr.children[0];
    if (rankCell) rankCell.textContent = '#' + (idx + 1);
    tbody.appendChild(tr);
  });
}

// Tabulated Total sorting
let tabSortDir = 'desc';
function toggleTabSort() {
  tabSortDir = tabSortDir === 'desc' ? 'asc' : 'desc';
  const label = document.getElementById('tabSortLabel');
  if (label) label.textContent = tabSortDir === 'desc' ? 'High→Low' : 'Low→High';
  sortTabulatedByTotal(tabSortDir);
}

function sortTabulatedByTotal(dir) {
  // Find the main table under Tabulated Data card
  const container = document.querySelector('div.bg-white.bg-opacity-15.backdrop-blur-md.rounded-xl.shadow-sm.border.border-white.border-opacity-20 > div.overflow-x-auto');
  const table = container ? container.querySelector('table') : null;
  if (!table) return;
  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  if (!thead || !tbody) return;
  const ths = Array.from(thead.querySelectorAll('th'));
  const totalIdx = ths.length - 1; // last column is Total
  const rows = Array.from(tbody.querySelectorAll('tr'));
  rows.sort((a,b) => {
    const av = parseNumeric(a.children[totalIdx]?.innerText);
    const bv = parseNumeric(b.children[totalIdx]?.innerText);
    return dir === 'desc' ? (bv - av) : (av - bv);
  });
  rows.forEach(tr => tbody.appendChild(tr));
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
