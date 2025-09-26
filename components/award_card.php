<?php
/** award_card.php
 * Expected: $award (array) with keys name, division_scope; $winners (array)
 */
?>
<div class="border border-slate-200 rounded p-4 bg-white shadow-sm">
  <h3 class="font-semibold text-slate-800 text-sm mb-2"><?= htmlspecialchars($award['name'] ?? 'Award', ENT_QUOTES, 'UTF-8') ?></h3>
  <p class="text-xs text-slate-500 mb-3">Scope: <?= htmlspecialchars($award['division_scope'] ?? 'All', ENT_QUOTES, 'UTF-8') ?></p>
  <ul class="text-sm list-disc pl-5 space-y-1">
    <?php if (!empty($winners)):
      foreach ($winners as $w): ?>
        <li><?= htmlspecialchars($w['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($w['division'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</li>
      <?php endforeach; else: ?>
      <li class="text-slate-400 italic">No winner set</li>
    <?php endif; ?>
  </ul>
</div>
