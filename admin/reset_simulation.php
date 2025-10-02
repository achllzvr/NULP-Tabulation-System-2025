<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['adminID'])) {
    $currentPage = urlencode('admin/' . basename($_SERVER['PHP_SELF']));
    header("Location: ../login_admin.php?redirect=" . $currentPage);
    exit();
}

require_once('../classes/database.php');
$con = new database();
$conn = $con->opencon();

$pageant_id = $_SESSION['pageant_id'] ?? 1;

$success_message = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_simulation'])) {
    $confirm = trim($_POST['confirm'] ?? '');
    if ($confirm !== 'RESET') {
        $error_message = "Confirmation text did not match. Type RESET to proceed.";
    } else {
        try {
            $conn->begin_transaction();

            // 1) Delete scores (individual)
            $stmt = $conn->prepare("DELETE FROM scores WHERE round_id IN (SELECT id FROM rounds WHERE pageant_id = ?)");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $stmt->close();

            // 2) Delete duo scores
            $stmt = $conn->prepare("DELETE FROM scores_duo WHERE round_id IN (SELECT id FROM rounds WHERE pageant_id = ?)");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $stmt->close();

            // 3) Delete tie group participants then tie groups
            $stmt = $conn->prepare("DELETE tgp FROM tie_group_participants tgp WHERE tgp.tie_group_id IN (SELECT id FROM tie_groups WHERE pageant_id = ?)");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM tie_groups WHERE pageant_id = ?");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $stmt->close();

            // 4) Delete advancements (based on to_round_id within this pageant)
            // Note: If your schema also has from_round_id, add a similar clause.
            $stmt = $conn->prepare("DELETE a FROM advancements a WHERE a.to_round_id IN (SELECT id FROM rounds WHERE pageant_id = ?)");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $stmt->close();

            // 5) Delete advancement verification child records then parents
            $stmt = $conn->prepare("DELETE avj FROM advancement_verification_judges avj WHERE avj.advancement_verification_id IN (SELECT id FROM advancement_verification WHERE pageant_id = ?)");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM advancement_verification WHERE pageant_id = ?");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $stmt->close();

            // 6) Reset rounds to PENDING and clear timestamps
            $stmt = $conn->prepare("UPDATE rounds SET state='PENDING', opened_at = NULL, closed_at = NULL WHERE pageant_id = ?");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $success_message = "Simulation data has been cleared and rounds reset to PENDING.";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Reset failed: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Reset Simulation Data';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>

<div class="px-6 py-8">
  <div class="mb-6">
    <h1 class="text-3xl font-bold text-white">Reset Simulation Data</h1>
    <p class="text-slate-200">Delete previous test/automation runs to verify judge panel from a clean state.</p>
  </div>

  <?php if ($success_message): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg text-sm mb-6">
      <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if ($error_message): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg text-sm mb-6">
      <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
    <h2 class="text-lg font-semibold text-white mb-2">What this will do</h2>
    <ul class="list-disc list-inside text-slate-200 text-sm mb-4">
      <li>Delete all individual scores for this pageant</li>
      <li>Delete all duo/pair scores</li>
      <li>Delete all tie groups and their participants</li>
      <li>Delete advancements and advancement verification records</li>
      <li>Reset all rounds to PENDING (clears opened/closed timestamps)</li>
      <li>Does NOT delete participants, duos, duo members, criteria, or judge assignments</li>
    </ul>

    <form method="POST" onsubmit="return confirm('This will permanently delete test data. Continue?');">
      <input type="hidden" name="reset_simulation" value="1" />
      <label class="block text-sm font-medium text-slate-200 mb-2">Type <strong>RESET</strong> to confirm</label>
      <input name="confirm" type="text" placeholder="RESET" class="w-64 px-3 py-2 rounded border border-white border-opacity-20 bg-white bg-opacity-10 text-white placeholder-slate-300" />
      <div class="mt-4">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Delete Simulation Data</button>
      </div>
    </form>

    <p class="text-xs text-slate-300 mt-4">Tip: After resetting, go to Rounds and open the appropriate round to start a fresh judge simulation.</p>
  </div>
</div>

<?php include __DIR__ . '/../partials/sidebar_close.php'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<?php $conn->close(); ?>
