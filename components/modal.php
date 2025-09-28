<?php
/** modal.php
 * Expected: $modalId, $title, $bodyHtml, $footerHtml (raw HTML already escaped where needed prior)
 */
?>
<div id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>" class="hidden fixed inset-0 z-40 items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true">
  <div class="bg-white w-full max-w-lg max-h-[90vh] rounded shadow-lg overflow-hidden flex flex-col">
    <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center flex-shrink-0">
      <h2 class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
      <button type="button" class="text-slate-400 hover:text-slate-600" onclick="hideModal('<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>')">&times;</button>
    </div>
    <div class="p-4 text-sm overflow-y-auto flex-1" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>-body">
      <?= $bodyHtml ?? '' ?>
    </div>
    <?php if (!empty($footerHtml)): ?>
      <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 text-right flex-shrink-0" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>-footer">
        <?= $footerHtml ?>
      </div>
    <?php endif; ?>
  </div>
</div>
