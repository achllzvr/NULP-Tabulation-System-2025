<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Determine current user role
$user = null;
$role = '';
if (isset($_SESSION['adminID'])) {
    $user = [
        'user_id' => $_SESSION['adminID'],
        'name' => $_SESSION['adminFN'],
        'role' => 'admin'
    ];
    $role = 'ADMIN';
} elseif (isset($_SESSION['judgeID'])) {
    $user = [
        'user_id' => $_SESSION['judgeID'],
        'name' => $_SESSION['judgeFN'],
        'role' => 'judge'
    ];
    $role = 'JUDGE';
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$pageTitle = 'Access Restricted';
include __DIR__ . '/partials/head.php';
?>
<main class="mx-auto max-w-lg w-full p-8 space-y-6">
  <h1 class="text-2xl font-semibold text-red-600">Access Restricted</h1>
  <p class="text-sm text-slate-700">You don't have permission to view that page with your current account.</p>
  <?php if($user): ?>
    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded p-4">
      Logged in as <strong><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($user['name'] ?? ('User #'.$user['user_id']), ENT_QUOTES, 'UTF-8') ?>).
    </div>
    <div class="flex flex-wrap gap-3 pt-2">
      <?php if($role==='ADMIN'): ?>
        <a href="dashboard.php" class="px-4 py-2 rounded bg-blue-600 text-white text-sm">Go to Dashboard</a>
      <?php elseif($role==='JUDGE'): ?>
        <a href="judge_active.php" class="px-4 py-2 rounded bg-blue-600 text-white text-sm">Go to Judge Panel</a>
      <?php endif; ?>
      <form method="post">
        <button name="logout" class="px-4 py-2 rounded bg-slate-200 hover:bg-slate-300 text-sm" type="submit">Logout</button>
      </form>
    </div>
  <?php else: ?>
    <p class="text-sm">You are not logged in.</p>
    <div class="flex gap-3">
      <a href="login_admin.php" class="px-4 py-2 rounded bg-blue-600 text-white text-sm">Admin Login</a>
      <a href="login_judge.php" class="px-4 py-2 rounded bg-indigo-600 text-white text-sm">Judge Login</a>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
