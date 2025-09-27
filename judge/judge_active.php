<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if judge is logged in
if (!isset($_SESSION['judgeID'])) {
    $currentPage = urlencode('judge/' . basename($_SERVER['PHP_SELF']));
    header("Location: ../login_judge.php?redirect=" . $currentPage);
    exit();
}

// Include the database class file
require_once('../classes/database.php');

// Create an instance of the database class
$con = new database();

// Handle score submissions
if (isset($_POST['submit_score'])) {
    $participant_id = $_POST['participant_id'];
    $criterion_id = $_POST['criterion_id'];
    $score = $_POST['score'];
    $judge_id = $_SESSION['judgeID'];
    $pageant_id = $_SESSION['pageantID'];
    
    // Insert or update score
    $conn = $con->opencon();
    $stmt = $conn->prepare("INSERT INTO scores (pageant_id, participant_id, judge_id, criterion_id, score) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE score = VALUES(score)");
    $stmt->bind_param("iiiid", $pageant_id, $participant_id, $judge_id, $criterion_id, $score);
    
    if ($stmt->execute()) {
        $success_message = "Score submitted successfully.";
    } else {
        $error_message = "Error submitting score.";
    }
    $stmt->close();
    $conn->close();
}

$pageTitle = 'Judge Active Round';
$participant = ['id'=>0];
$criteria = [];
$existingScores = [];
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_judge.php';
?>
<main class="mx-auto max-w-3xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Active Round Scoring</h1>
  <div class="bg-white border border-slate-200 rounded p-4">
    <?php include __DIR__ . '/../components/score_form.php'; ?>
  </div>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
