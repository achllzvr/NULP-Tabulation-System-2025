<?php
require __DIR__.'/includes/bootstrap.php';
auth_require_login();
pageant_ensure_session();
$pageTitle='Advancement';
$user = auth_user();
$pageant = pageant_get_current();
$rounds = $pageant ? pageant_list_rounds($pageant['id']) : [];

// Define prelim (earliest sequence with scoring_mode PRELIM) and final (scoring_mode FINAL) heuristically
$prelim = null; $final = null;
foreach ($rounds as $r) {
  if ($r['scoring_mode']==='PRELIM' && !$prelim) $prelim=$r;
  if ($r['scoring_mode']==='FINAL' && !$final) $final=$r;
}

$guardMessage=null; $canAdvance=false; $advancementSourceRound=null;
if (!$pageant) {
  $guardMessage='No pageant context.';
} elseif (!$prelim) {
  $guardMessage='No preliminary round is configured.';
} elseif ($prelim['state']!=='CLOSED') {
  $guardMessage='Preliminary round must be CLOSED before selecting finalists.';
} elseif ($final && $final['state']==='OPEN') {
  $guardMessage='Final round already OPEN — advancement locked.';
} else {
  $canAdvance=true; $advancementSourceRound=$prelim;
}

// Placeholder: derive preliminary leaderboard (division-neutral) if allowed
$prelimBoard=[];
if ($canAdvance && $advancementSourceRound) {
  try { $prelimBoard = scores_leaderboard($advancementSourceRound['id']); }
  catch(Throwable $e){ $guardMessage='Unable to compute preliminary standings: '.$e->getMessage(); $canAdvance=false; }
}

require __DIR__.'/includes/head.php';
?>
<div class="max-w-5xl mx-auto p-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Advancement</h1>
    <?php if ($canAdvance && $advancementSourceRound): ?>
      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">From: <?= esc($advancementSourceRound['name']) ?></span>
    <?php endif; ?>
  </div>

  <?php if ($guardMessage): ?>
    <div class="p-4 rounded-md bg-gray-50 border border-gray-200 text-sm text-gray-700 flex gap-3">
      <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z"/></svg>
      <span><?= esc($guardMessage) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($canAdvance && $prelimBoard): ?>
    <form method="POST" class="space-y-6">
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"># / Name</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php $rank=1; foreach ($prelimBoard as $row): ?>
              <tr>
                <td class="px-4 py-2 text-sm"><input type="checkbox" name="advance[]" value="<?= esc($row['participant_id']) ?>" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"></td>
                <td class="px-4 py-2 text-sm font-medium text-gray-900">#<?= esc($row['number_label']) ?> <?= esc($row['full_name']) ?></td>
                <td class="px-4 py-2 text-sm text-right text-blue-600 font-semibold"><?= esc(number_format($row['total_score'],2)) ?></td>
                <td class="px-4 py-2 text-sm text-right text-gray-500"><?= $rank++ ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Select finalists to advance to the final round. (Persistence not yet implemented)</p>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-md">Save Advancement</button>
      </div>
    </form>
  <?php elseif ($canAdvance): ?>
    <div class="bg-white shadow rounded p-6 text-center text-gray-600">No preliminary scores available.</div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
