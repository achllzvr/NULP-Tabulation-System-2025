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

$pageTitle = 'Final Round Control';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Final Round</h1>
  <div class="flex gap-4">
    <button class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded">Open Final Round</button>
    <button class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded">Close Final Round</button>
  </div>
  <div id="finalJudgeProgress" class="mt-6"></div>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
