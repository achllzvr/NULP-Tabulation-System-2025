<?php
/** nav_admin.php : Admin navigation */
$items = [
    'Dashboard' => 'dashboard.php',
    'Participants' => 'participants.php',
    'Judges' => 'judges.php',
    'Rounds & Criteria' => 'rounds.php',
    'Live Control' => 'live_control.php',
    'Leaderboard' => 'leaderboard.php',
    'Advancement' => 'advancement.php',
    'Final Round' => 'final_round.php',
    'Awards' => 'awards.php',
    'Tie Resolution' => 'tie_resolution.php',
    'Settings' => 'settings.php'
];
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-white border-b border-slate-200">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex items-center h-14 gap-6">
    <div class="font-semibold text-slate-700">Admin Panel</div>
    <ul class="flex gap-4 text-sm">
      <?php foreach ($items as $label => $href): $active = ($href === $current); ?>
        <li>
          <a href="<?= $href ?>" class="px-2 py-1 rounded <?= $active ? 'bg-blue-600 text-white' : 'text-slate-600 hover:text-slate-900' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>
