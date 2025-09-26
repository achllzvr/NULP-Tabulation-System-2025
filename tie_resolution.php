<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    header("Location: login_admin.php");
    exit();
}

// Include the database class file
require_once('classes/database.php');

// Create an instance of the database class
$con = new database();

$pageTitle = 'Tie Resolution';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-6xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Tie Resolution</h1>
  <div class="bg-white border border-slate-200 rounded p-4">Tie groups listing placeholder.</div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
