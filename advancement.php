<?php
require_once __DIR__ . '/classes/AuthService.php';
AuthService::start();
$pageTitle = 'Advancement Review';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Advancement Review</h1>
  <div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white border border-slate-200 rounded p-4"><h2 class="font-semibold text-sm mb-3">Mr Top 5</h2><ul class="space-y-2 text-sm" id="mrTop5"></ul></div>
    <div class="bg-white border border-slate-200 rounded p-4"><h2 class="font-semibold text-sm mb-3">Ms Top 5</h2><ul class="space-y-2 text-sm" id="msTop5"></ul></div>
  </div>
  <div class="text-right">
    <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Confirm Advancement</button>
  </div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
