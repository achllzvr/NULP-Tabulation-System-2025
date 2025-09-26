<?php
/** leaderboard_table.php
 * Expected: $rows with keys division, rank, number_label, full_name, total_score
 */
?>
<div class="overflow-x-auto bg-white shadow-sm rounded border border-slate-200">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600 uppercase text-xs tracking-wide">
      <tr>
        <th class="px-3 py-2 text-left">Division</th>
        <th class="px-3 py-2 text-left">Rank</th>
        <th class="px-3 py-2 text-left">#</th>
        <th class="px-3 py-2 text-left">Name</th>
        <th class="px-3 py-2 text-left">Score</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php if (!empty($rows)):
        foreach ($rows as $r): ?>
          <tr>
            <td class="px-3 py-2"><?= htmlspecialchars($r['division'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-3 py-2"><?= (int)($r['rank'] ?? 0) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['number_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-3 py-2 font-medium"><?= htmlspecialchars(number_format((float)($r['total_score'] ?? 0),2), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" class="px-3 py-6 text-center text-slate-400">No scores yet</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
