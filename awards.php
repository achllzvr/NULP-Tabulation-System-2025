<?php
require __DIR__.'/includes/bootstrap.php';
auth_require_login();
pageant_ensure_session();
$pageTitle='Awards';
$user = auth_user();
$pageant = pageant_get_current();
$rounds = $pageant ? pageant_list_rounds($pageant['id']) : [];
// Identify final round (scoring_mode FINAL) and verify CLOSED
$final = null; foreach ($rounds as $r) { if ($r['scoring_mode']==='FINAL') { $final=$r; break; } }
$guardMessage=null; $canAssign=false;
if (!$pageant) { $guardMessage='No pageant context.'; }
elseif (!$final) { $guardMessage='No final round configured.'; }
elseif ($final['state']!=='CLOSED') { $guardMessage='Final round must be CLOSED before assigning awards.'; }
else { $canAssign=true; }

require __DIR__.'/includes/head.php';
?>
<div class="max-w-5xl mx-auto p-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Awards</h1>
    <?php if ($canAssign): ?>
      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Final Closed</span>
    <?php endif; ?>
  </div>

  <?php if ($guardMessage): ?>
    <div class="p-4 rounded-md bg-gray-50 border border-gray-200 text-sm text-gray-700 flex gap-3">
      <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z"/></svg>
      <span><?= esc($guardMessage) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($canAssign): ?>
    <div class="bg-white shadow rounded-lg p-6 space-y-6">
      <h2 class="text-lg font-semibold">Assign Awards</h2>
      <p class="text-sm text-gray-600">Interface placeholder. Future: pull top finalists, allow manual selection, persist to awards table.</p>
      <form method="POST" class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Best in Gown</label>
            <select name="award_best_gown" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
              <option value="">-- Select --</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Best in Talent</label>
            <select name="award_best_talent" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
              <option value="">-- Select --</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end">
          <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">Save Awards</button>
        </div>
      </form>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
