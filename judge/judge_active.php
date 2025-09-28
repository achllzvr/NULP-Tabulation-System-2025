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
if (isset($_POST['submit_scores'])) {
    $participant_id = $_POST['participant_id'];
    $judge_id = $_SESSION['judgeID'];
    $pageant_id = $_SESSION['pageantID'];
    
    $conn = $con->opencon();
    $success_count = 0;
    $error_count = 0;
    
    // Get the active round
    $stmt = $conn->prepare("SELECT id FROM rounds WHERE pageant_id = ? AND state = 'OPEN' LIMIT 1");
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $round = $result->fetch_assoc();
    $stmt->close();
    
    if ($round) {
        $round_id = $round['id'];
        
        // Process each criterion score
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'criterion_') === 0) {
                $criterion_id = intval(str_replace('criterion_', '', $key));
                $score = floatval($value);
                
                if ($score >= 0) { // Only save non-negative scores
                    // Insert or update score
                    $stmt = $conn->prepare("INSERT INTO scores (round_id, criterion_id, participant_id, judge_user_id, raw_score) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE raw_score = VALUES(raw_score), updated_at = CURRENT_TIMESTAMP");
                    $stmt->bind_param("iiiid", $round_id, $criterion_id, $participant_id, $judge_id, $score);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                    $stmt->close();
                }
            }
        }
        
        if ($success_count > 0) {
            $success_message = "Saved $success_count score(s) successfully.";
        }
        if ($error_count > 0) {
            $error_message = "Failed to save $error_count score(s).";
        }
    } else {
        $error_message = "No active round found.";
    }
    
    $conn->close();
}

// Fetch data for judge interface
$conn = $con->opencon();
$pageant_id = $_SESSION['pageantID'] ?? 1;
$judge_id = $_SESSION['judgeID'];

// Get active round
$stmt = $conn->prepare("SELECT * FROM rounds WHERE pageant_id = ? AND state = 'OPEN' ORDER BY sequence LIMIT 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$active_round = $result->fetch_assoc();
$stmt->close();

// Initialize variables
$participants = [];
$criteria = [];
$existingScores = [];
$current_participant = null;

if ($active_round) {
    // Get participants
    $stmt = $conn->prepare("SELECT p.*, d.name as division FROM participants p JOIN divisions d ON p.division_id = d.id WHERE p.pageant_id = ? AND p.is_active = 1 ORDER BY p.number_label");
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $participants = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get criteria for this round
    $stmt = $conn->prepare("SELECT c.*, rc.weight, rc.max_score FROM criteria c JOIN round_criteria rc ON c.id = rc.criterion_id WHERE rc.round_id = ? AND c.is_active = 1 ORDER BY rc.display_order");
    $stmt->bind_param("i", $active_round['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $criteria = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get current participant (first one for now, or from GET parameter)
    $participant_index = intval($_GET['participant'] ?? 0);
    if (isset($participants[$participant_index])) {
        $current_participant = $participants[$participant_index];
        
        // Get existing scores for this participant from this judge
        $stmt = $conn->prepare("SELECT criterion_id, raw_score FROM scores WHERE round_id = ? AND participant_id = ? AND judge_user_id = ?");
        $stmt->bind_param("iii", $active_round['id'], $current_participant['id'], $judge_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existingScores[$row['criterion_id']] = ['score_value' => $row['raw_score']];
        }
        $stmt->close();
    }
}

$conn->close();

$pageTitle = 'Judge Active Round';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_judge.php';
?>
<main class="mx-auto max-w-4xl w-full p-6 space-y-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Judge Scoring Panel</h1>
    <div class="text-sm text-slate-600">
      Welcome, <?= htmlspecialchars($_SESSION['judgeFN'] ?? 'Judge', ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>

  <?php if (isset($success_message)): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded text-sm">
      <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (isset($error_message)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
      <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (!$active_round): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
      <svg class="mx-auto h-12 w-12 text-yellow-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
      </svg>
      <h3 class="text-lg font-medium text-yellow-800 mb-2">No Active Round</h3>
      <p class="text-yellow-700">There are currently no rounds open for judging. Please wait for the administrator to open a round.</p>
    </div>
  <?php elseif (empty($participants)): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
      <svg class="mx-auto h-12 w-12 text-blue-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      <h3 class="text-lg font-medium text-blue-800 mb-2">No Participants</h3>
      <p class="text-blue-700">No participants are registered for this pageant yet.</p>
    </div>
  <?php elseif (empty($criteria)): ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
      <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <h3 class="text-lg font-medium text-red-800 mb-2">No Scoring Criteria</h3>
      <p class="text-red-700">This round has no scoring criteria assigned. Please contact the administrator.</p>
    </div>
  <?php else: ?>
    <!-- Round Information -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
      <h2 class="text-lg font-semibold text-blue-800 mb-2"><?= htmlspecialchars($active_round['name'], ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="text-blue-700 text-sm">Currently judging: <?= count($participants) ?> participants • <?= count($criteria) ?> criteria</p>
    </div>

    <!-- Participant Navigation -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 mb-6">
      <h3 class="text-lg font-semibold text-slate-800 mb-4">Select Participant to Score</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <?php foreach ($participants as $index => $participant): ?>
          <a href="?participant=<?= $index ?>" 
             class="block text-center p-3 rounded-lg border transition-colors <?= ($current_participant && $current_participant['id'] == $participant['id']) ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
            <div class="font-semibold">#<?= htmlspecialchars($participant['number_label'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-xs mt-1"><?= htmlspecialchars($participant['division'], ENT_QUOTES, 'UTF-8') ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($current_participant): ?>
      <!-- Scoring Form -->
      <div class="bg-white border border-slate-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-slate-800">Scoring: Participant #<?= htmlspecialchars($current_participant['number_label'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="text-slate-600"><?= htmlspecialchars($current_participant['full_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($current_participant['division'], ENT_QUOTES, 'UTF-8') ?>)</p>
          </div>
          <div class="text-sm text-slate-500">
            Participant <?= $participant_index + 1 ?> of <?= count($participants) ?>
          </div>
        </div>
        
        <?php 
        // Set up variables for the score form component
        $participant = $current_participant;
        include __DIR__ . '/../components/score_form.php'; 
        ?>
      </div>
    <?php else: ?>
      <div class="bg-slate-50 border border-slate-200 rounded-lg p-6 text-center">
        <p class="text-slate-600">Select a participant above to begin scoring.</p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
