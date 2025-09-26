<?php
/** score_form.php
 * Expected: $criteria (array), $participant (array), $existingScores (criterion_id=>score_value)
 */
?>
<form id="score-form" class="space-y-4" onsubmit="return submitScores(event)">
  <input type="hidden" name="participant_id" value="<?= (int)($participant['id'] ?? 0) ?>" />
  <div class="space-y-2">
    <?php foreach ($criteria as $c): $cid = (int)$c['id']; $val = $existingScores[$cid]['score_value'] ?? ''; ?>
      <div class="flex items-center gap-4">
        <label class="w-48 text-sm font-medium text-slate-700"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></label>
        <input type="number" step="0.01" min="0" max="100" name="criterion_<?= $cid ?>" value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" class="border border-slate-300 rounded px-2 py-1 w-32 focus:outline-none focus:ring focus:ring-blue-200" />
        <span class="text-xs text-slate-500"><?= (float)$c['weight_percentage'] ?>%</span>
      </div>
    <?php endforeach; ?>
  </div>
  <div>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Save Scores</button>
  </div>
</form>
