<?php
/** score_form.php
 * Expected: $criteria (array), $participant (array), $existingScores (criterion_id=>score_value)
 */
?>
<form method="post" class="space-y-6" id="scoreForm">
  <input type="hidden" name="participant_id" value="<?= $participant['id'] ?>">
  
  <?php foreach ($criteria as $criterion): ?>
    <div class="mb-4 bg-white bg-opacity-10 border border-white border-opacity-10 rounded-xl shadow-sm backdrop-blur-md p-5 transition hover:bg-opacity-20">
      <div class="flex items-center justify-between mb-2">
        <span class="font-semibold text-white text-base"><?= htmlspecialchars($criterion['name'], ENT_QUOTES, 'UTF-8') ?></span>
        <span class="text-xs text-blue-200">Weight: <?= number_format($criterion['weight'] * 100, 1) ?>% • Max: <?= number_format($criterion['max_score'], 0) ?> pts</span>
      </div>
      <div class="flex items-center gap-4">
        <input type="number" step="0.01" min="0" max="<?= $criterion['max_score'] ?>" name="criterion_<?= $criterion['id'] ?>" value="<?= isset($existingScores[$criterion['id']]) ? htmlspecialchars($existingScores[$criterion['id']]['score_value']) : '' ?>" class="w-24 px-4 py-2 rounded-lg border-0 bg-white bg-opacity-30 text-blue-900 font-bold text-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition placeholder:text-blue-300 shadow-sm" placeholder="0.00">
        <span class="text-blue-200 font-semibold">/ <?= number_format($criterion['max_score'], 0) ?></span>
        <div class="flex-1 h-2 bg-blue-200 bg-opacity-30 rounded-full overflow-hidden">
          <div class="h-2 bg-blue-500 transition-all" style="width: <?= isset($existingScores[$criterion['id']]) ? min(100, ($existingScores[$criterion['id']]['score_value'] / $criterion['max_score']) * 100) : 0 ?>%"></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="flex gap-3 mt-8">
    <button type="submit" name="submit_scores" class="flex-1 bg-blue-600 bg-opacity-80 hover:bg-blue-700 hover:bg-opacity-90 text-white font-semibold py-3 rounded-lg shadow transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
      <span>Save Scores</span>
    </button>
    <button type="button" onclick="clearScores()" class="flex-1 bg-white bg-opacity-30 hover:bg-white hover:bg-opacity-50 text-blue-900 font-semibold py-3 rounded-lg shadow transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
      Clear All
    </button>
  </div>
</form>
    </div>
    
    <div class="flex gap-3 pt-4">
      <button id="save-scores-btn" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Save Scores
      </button>
      <button type="button" onclick="clearScores()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium px-6 py-3 rounded-lg transition-colors">
        Clear All
      </button>
    </div>
</form>


<!-- JS moved to scoring.js -->
