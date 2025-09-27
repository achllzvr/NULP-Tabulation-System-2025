<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    $currentPage = urlencode('admin/' . basename($_SERVER['PHP_SELF']));
    header("Location: ../login_admin.php?redirect=" . $currentPage);
    exit();
}

// Include the database class file
require_once('../classes/database.php');

// Create an instance of the database class
$con = new database();

$steps = [
  ['label' => 'Participants', 'state' => 'done'],
  ['label' => 'Judges', 'state' => 'current'],
  ['label' => 'Rounds', 'state' => 'pending'],
  ['label' => 'Live', 'state' => 'pending'],
];
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="mx-auto max-w-7xl w-full p-6 space-y-8">
  <section>
    <h1 class="text-2xl font-semibold text-slate-800 mb-4">Dashboard</h1>
    <?php $stepsVar = $steps; $steps = $stepsVar; include __DIR__ . '/../components/progress_steps.php'; ?>
  </section>
  <section class="grid md:grid-cols-3 gap-6">
    <div class="bg-white border border-slate-200 rounded p-4 shadow-sm">
      <h2 class="font-semibold mb-2 text-sm text-slate-700">Participants</h2>
      <p class="text-3xl font-bold">--</p>
    </div>
    <div class="bg-white border border-slate-200 rounded p-4 shadow-sm">
      <h2 class="font-semibold mb-2 text-sm text-slate-700">Judges</h2>
      <p class="text-3xl font-bold">--</p>
    </div>
    <div class="bg-white border border-slate-200 rounded p-4 shadow-sm">
      <h2 class="font-semibold mb-2 text-sm text-slate-700">Rounds</h2>
      <p class="text-3xl font-bold">--</p>
    </div>
  </section>
</main>
<?php include __DIR__ . '/../partials/footer.php';
