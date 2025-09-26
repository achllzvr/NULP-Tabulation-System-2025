<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

$pageTitle = 'Landing | Pageant Tabulation System';
include __DIR__ . '/partials/head.php';
?>
<main class="mx-auto max-w-4xl w-full py-16 px-4">
  <h1 class="text-3xl font-bold text-slate-800 mb-8">Pageant Tabulation System</h1>
  <div class="grid sm:grid-cols-3 gap-6">
  <a href="login_admin.php" class="block bg-white border border-slate-200 rounded p-6 shadow hover:shadow-md transition">
        <h2 class="font-semibold mb-2">Admin Portal</h2>
      <p class="text-sm text-slate-600">Create or manage a pageant event.</p>
    </a>
  <a href="login_judge.php" class="block bg-white border border-slate-200 rounded p-6 shadow hover:shadow-md transition">
        <h2 class="font-semibold mb-2">Judge Login</h2>
      <p class="text-sm text-slate-600">Enter scores for active round.</p>
    </a>
      <a href="public_select.php" class="block bg-white border border-slate-200 rounded p-6 shadow hover:shadow-md transition">
      <h2 class="font-semibold mb-2">Public Results</h2>
      <p class="text-sm text-slate-600">View published leaderboards.</p>
    </a>
  </div>
</main>
<?php include __DIR__ . '/partials/footer.php';
