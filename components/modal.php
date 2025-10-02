<?php
/** modal.php
 * Expected: $modalId, $title, $bodyHtml, $footerHtml (raw HTML already escaped where needed prior)
 */
?>
<div id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" onclick="if(event.target===this) hideModal('<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>')">
  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-2xl shadow-2xl border border-white border-opacity-20 w-full max-w-xl max-h-[90vh] overflow-hidden flex flex-col">
    <div class="px-5 py-4 border-b border-white/20 flex justify-between items-center flex-shrink-0">
      <h2 class="font-semibold text-white text-base"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
      <?php if (empty($hideCloseButton)): ?>
        <button type="button" class="text-slate-300 hover:text-white text-xl leading-none" onclick="hideModal('<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>')">&times;</button>
      <?php endif; ?>
    </div>
    <div class="p-5 text-sm overflow-y-auto flex-1 text-slate-100" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>-body">
      <?= $bodyHtml ?? '' ?>
    </div>
    <?php if (!empty($footerHtml)): ?>
      <div class="px-5 py-4 border-t border-white/20 bg-white/10 text-right flex-shrink-0" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>-footer">
        <?= $footerHtml ?>
      </div>
    <?php endif; ?>
  </div>
  <!-- Ensure showModal makes the container flex -->
  <script>
    // no-op, styles rely on global showModal/hideModal in assets/js/scoring.js
  </script>
</div>
