<?php
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/ScoreService.php';
AuthService::start();
$pageTitle = 'Judge Active Round';
$participant = ['id'=>0];
$criteria = [];
$existingScores = [];
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_judge.php';
?>
<main class="mx-auto max-w-3xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Active Round Scoring</h1>
  <div class="bg-white border border-slate-200 rounded p-4">
    <?php include __DIR__ . '/components/score_form.php'; ?>
  </div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
