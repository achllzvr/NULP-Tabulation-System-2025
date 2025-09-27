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

$pageTitle = 'Leaderboard';
$rows = [];
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Leaderboard</h1>
  <?php include __DIR__ . '/../components/leaderboard_table.php'; ?>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
