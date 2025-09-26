<?php
/** table.php
 * Expected: $columns = [['header'=>'Name','field'=>'full_name'], ...]
 *           $rows = [ ['full_name'=>'John'], ...]
 */
?>
<div class="overflow-x-auto bg-white shadow-sm rounded border border-slate-200">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600 uppercase text-xs tracking-wide">
      <tr>
        <?php foreach ($columns as $col): ?>
          <th class="px-3 py-2 text-left font-medium"><?= htmlspecialchars($col['header'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php if (!empty($rows)):
        foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-50">
            <?php foreach ($columns as $col): $field = $col['field']; ?>
              <td class="px-3 py-2">
                <?= htmlspecialchars((string)($r[$field] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="<?= count($columns) ?>" class="px-3 py-6 text-center text-slate-400">No data</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
