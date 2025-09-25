<?php
require __DIR__.'/includes/bootstrap.php';
auth_require_login();
pageant_ensure_session();
$pageTitle='Final Round Scoring';
$pageant = pageant_get_current();
$rounds = $pageant ? pageant_list_rounds($pageant['id']) : [];
$final = null; foreach ($rounds as $r){ if ($r['scoring_mode']==='FINAL'){ $final=$r; break; } }
$guardMessage=null; $canScore=false;
if (!$pageant) { $guardMessage='No pageant context.'; }
elseif (!$final) { $guardMessage='No final round configured.'; }
elseif ($final['state']!=='OPEN') { $guardMessage='Final round is not OPEN for scoring.'; }
else { $canScore=true; }
require __DIR__.'/includes/head.php';
?>
<div class="max-w-4xl mx-auto p-6 space-y-6">
  <h1 class="text-2xl font-bold">Final Round</h1>
  <?php if ($guardMessage): ?>
    <div class="p-4 rounded-md bg-gray-50 border border-gray-200 text-sm text-gray-700 flex gap-3">
      <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z"/></svg>
      <span><?= esc($guardMessage) ?></span>
    </div>
  <?php endif; ?>
  <?php if ($canScore): ?>
    <div class="bg-white shadow rounded p-6">
      <p class="text-sm text-gray-600">Scoring interface placeholder for final round (criteria & participants to be loaded similarly to judge_active).</p>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
