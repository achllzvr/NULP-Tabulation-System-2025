<?php
require_once __DIR__ . '/classes/AuthService.php';
AuthService::start();
$pageTitle = 'Final Round Control';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Final Round</h1>
  <div class="flex gap-4">
    <button class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded">Open Final Round</button>
    <button class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded">Close Final Round</button>
  </div>
  <div id="finalJudgeProgress" class="mt-6"></div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
