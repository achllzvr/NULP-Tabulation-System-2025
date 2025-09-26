<?php
/**
 * progress_steps.php
 * Expected: $steps = [ ['label'=>'Participants','state'=>'done|current|pending'], ... ]
 */
?>
<ol class="flex items-center gap-4 text-sm">
  <?php if (!empty($steps)):
    foreach ($steps as $s):
      $state = $s['state'] ?? 'pending';
      $clr = match($state){
        'done' => 'bg-green-500 text-white',
        'current' => 'bg-blue-600 text-white',
        default => 'bg-slate-300 text-slate-600'
      }; ?>
      <li class="flex items-center">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-semibold <?= $clr ?>">&nbsp;</span>
        <span class="ml-2 <?= $state==='pending'?'text-slate-500':'text-slate-800 font-medium' ?>"><?= htmlspecialchars($s['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
      </li>
    <?php endforeach; endif; ?>
</ol>
