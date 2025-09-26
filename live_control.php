<?php
require_once __DIR__ . '/classes/AuthService.php';
AuthService::start();
$pageTitle = 'Live Control';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-6xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Live Control</h1>
  <div class="grid md:grid-cols-3 gap-4" id="roundCards">
    <!-- Round cards populated later -->
  </div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
