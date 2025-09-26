<?php
require_once __DIR__ . '/classes/AuthService.php';
AuthService::start();
$pageTitle = 'Leaderboard';
$rows = [];
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Leaderboard</h1>
  <?php include __DIR__ . '/components/leaderboard_table.php'; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
