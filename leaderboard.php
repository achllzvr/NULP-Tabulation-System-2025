<?php
require __DIR__.'/includes/bootstrap.php';
auth_require_login();
pageant_ensure_session();
$pageTitle='Leaderboard';
$user = auth_user();
$pageant = pageant_get_current();
$activeRound = $pageant ? rounds_get_active($pageant['id']) : null; // OPEN round
$currentRound = $pageant ? rounds_get_current($pageant['id']) : null; // last opened or closed

// Guard rules:
// - Leaderboard only visible for a CLOSED round (last current round must be CLOSED)
// - If an OPEN round exists, prompt user to wait until it's closed
// - If no rounds yet, show placeholder
$roundForBoard = null; $guardMessage = null; $guardState='info';
if (!$pageant) {
    $guardMessage = 'No pageant context selected.'; $guardState='warn';
} elseif (!$currentRound) {
    $guardMessage = 'No rounds have been created yet.'; $guardState='info';
} elseif ($activeRound && $activeRound['id'] === $currentRound['id'] && $currentRound['state']==='OPEN') {
    $guardMessage = 'Leaderboard will appear once the active round is closed.'; $guardState='info';
} elseif ($currentRound['state'] !== 'CLOSED') {
    $guardMessage = 'Latest round is not closed yet.'; $guardState='info';
} else {
    $roundForBoard = $currentRound; // CLOSED round eligible
}

// Fetch leaderboard if allowed
$leaderboard = [];
if ($roundForBoard) {
    try { $leaderboard = scores_leaderboard($roundForBoard['id']); }
    catch (Throwable $e) { $guardMessage = 'Failed to load leaderboard: '.$e->getMessage(); $guardState='error'; }
}

require __DIR__.'/includes/head.php';
?>
<div class="max-w-6xl mx-auto p-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Leaderboard</h1>
    <?php if ($roundForBoard): ?>
      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">Round: <?= esc($roundForBoard['name']) ?></span>
    <?php endif; ?>
  </div>

  <?php if ($guardMessage): ?>
    <div class="rounded-md p-4 <?php echo $guardState==='error'?'bg-red-50 border border-red-200':'bg-gray-50 border border-gray-200';?>">
      <div class="flex">
        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z" />
        </svg>
        <div class="ml-3 text-sm text-gray-700"><?= esc($guardMessage) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($roundForBoard && $leaderboard): ?>
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participant</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Division</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rank (Div)</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <?php $i=1; foreach ($leaderboard as $row): ?>
            <tr>
              <td class="px-4 py-2 text-sm text-gray-600"><?= $i++ ?></td>
              <td class="px-4 py-2 text-sm font-medium text-gray-900">#<?= esc($row['number_label']) ?> <?= esc($row['full_name']) ?></td>
              <td class="px-4 py-2 text-sm text-gray-600"><?= esc($row['division']) ?></td>
              <td class="px-4 py-2 text-sm text-right font-semibold text-blue-600"><?= esc(number_format($row['total_score'],2)) ?></td>
              <td class="px-4 py-2 text-sm text-right text-gray-500"><?= esc($row['rank_division']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php elseif ($roundForBoard && !$leaderboard): ?>
    <div class="bg-white shadow rounded p-6 text-center text-gray-600">No scores available yet.</div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
