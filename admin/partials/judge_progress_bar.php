<?php
/**
 * judge_progress_bar.php
 * Inputs (prefer in this order):
 * - $judge_confirmations_local (array of ['full_name','confirmed','confirmed_at'])
 * - falls back to $judge_confirmations if local is not set
 */

$list = isset($judge_confirmations_local) ? $judge_confirmations_local : ($judge_confirmations ?? []);
$total = is_array($list) ? count($list) : 0;
$confirmed = 0;
foreach ($list as $j) { if (!empty($j['confirmed'])) $confirmed++; }
$pct = $total > 0 ? round(($confirmed / max(1,$total)) * 100) : 0;
?>

<div class="mt-4">
	<div class="flex items-center justify-between mb-2">
		<div class="text-sm text-slate-200">
				Judges confirmed: <span class="font-semibold text-white"><span id="judgeProgressConfirmed"><?= $confirmed ?></span>/<span id="judgeProgressTotal"><?= $total ?></span></span>
			</div>
			<div id="judgeProgressPct" class="text-xs text-slate-300">
				<?= $pct ?>%
			</div>
	</div>
	<div class="w-full h-2 rounded-full bg-white bg-opacity-10 overflow-hidden">
		<div id="judgeProgressBarInner" class="h-2 bg-green-400" style="width: <?= $pct ?>%"></div>
	</div>

	<?php if ($total): ?>
		<ul id="judgeProgressList" class="mt-3 grid sm:grid-cols-2 gap-2">
			<?php foreach ($list as $j): ?>
				<li class="flex items-center justify-between text-sm bg-white bg-opacity-10 backdrop-blur-sm border border-white border-opacity-10 rounded px-3 py-2">
					<span class="text-slate-100 truncate mr-2" title="<?= htmlspecialchars($j['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
						<?= htmlspecialchars($j['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
					</span>
					<?php if (!empty($j['confirmed'])): ?>
						<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-500 bg-opacity-30 text-green-100">Confirmed</span>
					<?php else: ?>
						<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500 bg-opacity-30 text-yellow-100">Pending</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>

