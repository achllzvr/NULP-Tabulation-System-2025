<?php
/** nav_judge.php : Judge navigation */
// Determine if we're in a subdirectory
$isInJudgeDir = strpos($_SERVER['PHP_SELF'], '/judge/') !== false;
$prefix = $isInJudgeDir ? '' : 'judge/';

$items = [
  'Active Round' => $prefix . 'judge_active.php',
];
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-white border-b border-slate-200">
  <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 flex items-center justify-between h-14">
    <div class="flex items-center gap-6">
      <div class="font-semibold text-slate-700">Judge Panel</div>
      <ul class="flex gap-4 text-sm">
        <?php foreach ($items as $label => $href): $active = ($href === $current); ?>
          <li>
            <a href="<?= $href ?>" class="px-2 py-1 rounded <?= $active ? 'bg-blue-600 text-white' : 'text-slate-600 hover:text-slate-900' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="flex items-center gap-4">
      <span class="text-sm text-slate-600">Welcome, <?= htmlspecialchars($_SESSION['judgeFN'] ?? 'Judge', ENT_QUOTES, 'UTF-8') ?></span>
      <form method="post" action="<?= $isInJudgeDir ? '../logout.php' : 'logout.php' ?>" class="inline" id="logoutForm">
        <button type="button" class="text-sm text-slate-600 hover:text-slate-900" onclick="confirmLogout()">Logout</button>
      </form>
      <script>
      function confirmLogout() {
        if (typeof showConfirm === 'function') {
          showConfirm('Confirm Logout', 'Are you sure you want to logout?', 'Yes, Logout', 'Cancel')
          .then((result) => {
            if (result.isConfirmed) {
              document.getElementById('logoutForm').submit();
            }
          });
        } else {
          // Fallback to native confirm if SweetAlert2 isn't loaded yet
          if (confirm('Are you sure you want to logout?')) {
            document.getElementById('logoutForm').submit();
          }
        }
      }
      </script>
    </div>
  </div>
</nav>
