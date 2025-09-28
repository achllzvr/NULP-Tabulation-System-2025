<?php
/** score_form.php
 * Expected: $criteria (array), $participant (array), $existingScores (criterion_id=>score_value)
 */
?>
<form id="score-form" method="POST" class="space-y-4">
  <input type="hidden" name="submit_scores" value="1" />
  <input type="hidden" name="participant_id" value="<?= (int)($participant['id'] ?? 0) ?>" />
  
  <?php if (empty($criteria)): ?>
    <div class="text-center py-6 text-slate-500">
      <p>No scoring criteria available for this round.</p>
    </div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($criteria as $c): 
        $cid = (int)$c['id']; 
        $val = $existingScores[$cid]['score_value'] ?? ''; 
        $maxScore = (float)($c['max_score'] ?? 10.00);
        $weight = (float)($c['weight'] ?? 0) * 100; // Convert to percentage
      ?>
        <div class="bg-slate-50 rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-slate-700"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></label>
            <div class="text-xs text-slate-500">
              Weight: <?= number_format($weight, 1) ?>% • Max: <?= $maxScore ?> points
            </div>
          </div>
          <?php if (!empty($c['description'])): ?>
            <p class="text-xs text-slate-600 mb-3"><?= htmlspecialchars($c['description'], ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
          <div class="flex items-center gap-3">
            <input type="number" 
                   step="0.01" 
                   min="0" 
                   max="<?= $maxScore ?>" 
                   name="criterion_<?= $cid ?>" 
                   value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" 
                   placeholder="0.00"
                   class="border border-slate-300 rounded-lg px-3 py-2 w-24 text-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            <span class="text-sm text-slate-600">/ <?= $maxScore ?></span>
            <div class="flex-1 bg-slate-200 rounded-full h-2 ml-4">
              <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                   style="width: <?= $val ? (($val / $maxScore) * 100) : 0 ?>%"></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <div class="flex gap-3 pt-4">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Save Scores
      </button>
      <button type="button" onclick="clearScores()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium px-6 py-3 rounded-lg transition-colors">
        Clear All
      </button>
    </div>
  <?php endif; ?>
</form>

<script>
function clearScores() {
  const inputs = document.querySelectorAll('#score-form input[type="number"]');
  inputs.forEach(input => {
    input.value = '';
    // Update progress bar
    const progressBar = input.closest('.bg-slate-50').querySelector('.bg-blue-600');
    if (progressBar) {
      progressBar.style.width = '0%';
    }
  });
}

// Update progress bars when scores change
document.addEventListener('DOMContentLoaded', function() {
  const scoreInputs = document.querySelectorAll('#score-form input[type="number"]');
  scoreInputs.forEach(input => {
    input.addEventListener('input', function() {
      const maxScore = parseFloat(this.max);
      const currentScore = parseFloat(this.value) || 0;
      const percentage = Math.min((currentScore / maxScore) * 100, 100);
      
      const progressBar = this.closest('.bg-slate-50').querySelector('.bg-blue-600');
      if (progressBar) {
        progressBar.style.width = percentage + '%';
      }
    });
  });
});
</script>
