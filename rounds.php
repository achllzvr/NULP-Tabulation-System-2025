<?php
require_once __DIR__ . '/classes/AuthService.php';
AuthService::start();
$pageTitle = 'Rounds & Criteria';
$rounds = [];// placeholder
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Rounds & Criteria</h1>
  <div class="bg-white border border-slate-200 rounded p-4 text-sm">Configuration UI placeholder (locked weights, view-only).</div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
