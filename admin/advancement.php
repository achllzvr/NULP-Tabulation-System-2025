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

// Get pageant ID from session
$pageant_id = $_SESSION['pageant_id'] ?? 1;

// --- Advancements Validation Panel Logic ---
// Helper: Get all judges for this pageant
function getPageantJudges($con, $pageant_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT u.id, u.full_name FROM users u JOIN pageant_users pu ON pu.user_id = u.id WHERE pu.pageant_id = ? AND pu.role = 'JUDGE'");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $judges = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $conn->close();
  return $judges;
}

// Helper: Get active advancement verification session
function getActiveAdvancementVerification($con, $pageant_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT * FROM advancement_verification WHERE pageant_id = ? AND is_active = 1 LIMIT 1");
  $stmt->bind_param("i", $pageant_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();
  $conn->close();
  return $row;
}

// Helper: Get judge confirmations for a session
function getAdvancementJudgeConfirmations($con, $verification_id) {
  $conn = $con->opencon();
  $stmt = $conn->prepare("SELECT avj.*, u.full_name FROM advancement_verification_judges avj JOIN users u ON avj.judge_user_id = u.id WHERE avj.advancement_verification_id = ?");
  $stmt->bind_param("i", $verification_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $rows = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $conn->close();
  return $rows;
}

// Handle: Open Advancements Validation Panel
if (isset($_POST['open_advancement_validation'])) {
  $conn = $con->opencon();
  $conn->begin_transaction();
  try {
    // Create advancement_verification if not already active
    $stmt = $conn->prepare("SELECT id FROM advancement_verification WHERE pageant_id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $stmt->bind_result($existing_id);
    $stmt->fetch();
    $stmt->close();
    if (!$existing_id) {
      $stmt = $conn->prepare("INSERT INTO advancement_verification (pageant_id, is_active, activated_at) VALUES (?, 1, NOW())");
      $stmt->bind_param("i", $pageant_id);
      $stmt->execute();
      $verification_id = $stmt->insert_id;
      $stmt->close();
      // Insert judge confirmations
      $judges = getPageantJudges($con, $pageant_id);
      foreach ($judges as $judge) {
        $stmt = $conn->prepare("INSERT INTO advancement_verification_judges (advancement_verification_id, judge_user_id, confirmed) VALUES (?, ?, 0)");
        $stmt->bind_param("ii", $verification_id, $judge['id']);
        $stmt->execute();
        $stmt->close();
      }
    }
    $conn->commit();
    $success_message = "Advancements validation panel activated.";
    $show_success_alert = true;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to activate advancements validation: " . $e->getMessage();
    $show_error_alert = true;
  }
  $conn->close();
}

// Handle: Close Advancements Validation
if (isset($_POST['close_advancement_validation'])) {
  $conn = $con->opencon();
  $conn->begin_transaction();
  try {
    // Find active session
    $stmt = $conn->prepare("SELECT id FROM advancement_verification WHERE pageant_id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("i", $pageant_id);
    $stmt->execute();
    $stmt->bind_result($verification_id);
    $stmt->fetch();
    $stmt->close();
    if ($verification_id) {
      // Set is_active=0, closed_at=NOW()
      $stmt = $conn->prepare("UPDATE advancement_verification SET is_active = 0, closed_at = NOW() WHERE id = ?");
      $stmt->bind_param("i", $verification_id);
      $stmt->execute();
      $stmt->close();
    }
    $conn->commit();
    $success_message = "Advancements validation finalized. Final round can now be opened.";
    $show_success_alert = true;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to finalize advancements validation: " . $e->getMessage();
    $show_error_alert = true;
  }
  $conn->close();
}

// Get current/active advancement verification session
$active_verification = getActiveAdvancementVerification($con, $pageant_id);
$judge_confirmations = $active_verification ? getAdvancementJudgeConfirmations($con, $active_verification['id']) : [];
$all_judges_confirmed = $active_verification && $judge_confirmations && count($judge_confirmations) > 0 && !array_filter($judge_confirmations, function($j) { return !$j['confirmed']; });

// Handle advancement confirmation
if (isset($_POST['confirm_advancement'])) {
    $mr_participants = $_POST['mr_participants'] ?? [];
    $ms_participants = $_POST['ms_participants'] ?? [];
    
    $conn = $con->opencon();
    $success_count = 0;
    $error_count = 0;
    
    $conn->begin_transaction();
    
    try {
        // Advance Mr participants
        foreach ($mr_participants as $participant_id) {
            // Insert advancement record (assuming advancing from current round to next round)
            $from_round_id = 1; // You may need to determine this based on current context
            $to_round_id = 2;   // You may need to determine this based on current context
            $stmt = $conn->prepare("INSERT INTO advancements (from_round_id, to_round_id, participant_id, rank_at_advancement, is_override) VALUES (?, ?, ?, 0, 1)");
            $stmt->bind_param("iii", $from_round_id, $to_round_id, $participant_id);
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $stmt->close();
        }
        
        // Advance Ms participants
        foreach ($ms_participants as $participant_id) {
            // Insert advancement record (assuming advancing from current round to next round)
            $from_round_id = 1; // You may need to determine this based on current context
            $to_round_id = 2;   // You may need to determine this based on current context
            $stmt = $conn->prepare("INSERT INTO advancements (from_round_id, to_round_id, participant_id, rank_at_advancement, is_override) VALUES (?, ?, ?, 0, 1)");
            $stmt->bind_param("iii", $from_round_id, $to_round_id, $participant_id);
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $stmt->close();
        }
        
        $conn->commit();
        
        if ($success_count > 0) {
            $success_message = "Successfully advanced {$success_count} participants to the next round.";
            $show_success_alert = true;
        }
        
        if ($error_count > 0) {
            $error_message = "Failed to advance {$error_count} participants.";
            $show_error_alert = true;
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to advance participants: " . $e->getMessage();
        $show_error_alert = true;
    }
    
    $conn->close();
}

// Get advancement count from query parameter (default 5)
$advancement_count = isset($_GET['count']) ? max(1, min(20, (int)$_GET['count'])) : 5;

// Get top participants for each division
$mr_top = $con->getTopParticipants($pageant_id, 'Mr', $advancement_count);
$ms_top = $con->getTopParticipants($pageant_id, 'Ms', $advancement_count);

// Check if advancements have already been confirmed
$conn = $con->opencon();
$stmt = $conn->prepare("SELECT COUNT(*) as count, r.name as next_round_name FROM advancements a 
                        JOIN rounds r ON a.to_round_id = r.id 
                        WHERE r.pageant_id = ? 
                        GROUP BY a.to_round_id 
                        ORDER BY a.to_round_id DESC 
                        LIMIT 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$advancement_result = $result->fetch_assoc();
$stmt->close();
$conn->close();

$advancements_confirmed = $advancement_result && $advancement_result['count'] > 0;
$next_round_name = $advancement_result['next_round_name'] ?? 'Next Round';

$pageTitle = 'Advancement Review';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
  <!-- Advancements Validation Panel (Admin) -->
  <div class="mb-8">
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
      <h2 class="text-lg font-semibold text-white mb-2">Advancements Validation Panel</h2>
      <?php if (!$active_verification): ?>
        <form method="POST">
          <button type="submit" name="open_advancement_validation" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition-colors">
            Open Advancements Validation Panel
          </button>
        </form>
      <?php else: ?>
        <div class="mb-4">
          <h3 class="text-base font-medium text-white mb-1">Judge Confirmations</h3>
          <ul class="list-disc list-inside text-slate-200">
            <?php foreach ($judge_confirmations as $j): ?>
              <li>
                <span class="font-semibold"><?php echo htmlspecialchars($j['full_name']); ?></span>:
                <?php if ($j['confirmed']): ?>
                  <span class="text-green-400">Confirmed</span>
                  <span class="text-xs text-slate-400">(<?php echo $j['confirmed_at'] ? htmlspecialchars($j['confirmed_at']) : ''; ?>)</span>
                <?php else: ?>
                  <span class="text-yellow-300">Pending</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <form method="POST">
          <button type="submit" name="close_advancement_validation" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg transition-colors" <?php if(!$all_judges_confirmed) echo 'disabled style="opacity:0.6;cursor:not-allowed;"'; ?>>
            Close Advancements Validation
          </button>
          <?php if(!$all_judges_confirmed): ?>
            <p class="text-sm text-yellow-200 mt-2">All judges must confirm before closing validation.</p>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>
  </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="bg-white bg-opacity-15 backdrop-blur-md border border-white border-opacity-20 text-white px-6 py-4 rounded-xl shadow-sm text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
      <div class="bg-white bg-opacity-15 backdrop-blur-md border border-white border-opacity-20 text-white px-6 py-4 rounded-xl shadow-sm text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- Advancement Confirmed Warning -->
    <?php if ($advancements_confirmed): ?>
  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6 mb-6">
        <div class="flex items-start">
          <svg class="w-6 h-6 text-blue-400 mt-0.5 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-white mb-2">Advancements Already Confirmed</h3>
            <p class="text-slate-200 mb-3">
              Participant advancements have been confirmed for <strong class="text-blue-200"><?php echo htmlspecialchars($next_round_name, ENT_QUOTES, 'UTF-8'); ?></strong>. 
              All advancement controls have been disabled to prevent accidental changes.
            </p>
            <div class="text-sm text-slate-200">
              <p class="font-medium">If you need to modify advancements:</p>
              <ul class="list-disc list-inside mt-1 space-y-1">
                <li>Contact system administrator</li>
                <li>Or revert advancements through database management</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>

<script>
function updateAdvancementCount() {
  const count = document.getElementById('advancementCount').value;
  const url = new URL(window.location);
  url.searchParams.set('count', count);
  window.location.href = url.toString();
}

function toggleAllMr(checkbox) {
  const mrCheckboxes = document.querySelectorAll('.mr-participants-checkbox');
  mrCheckboxes.forEach(cb => cb.checked = checkbox.checked);
  updateSelectedCount();
}

function toggleAllMs(checkbox) {
  const msCheckboxes = document.querySelectorAll('.ms-participants-checkbox');
  msCheckboxes.forEach(cb => cb.checked = checkbox.checked);
  updateSelectedCount();
}

function clearAllSelections() {
  const allCheckboxes = document.querySelectorAll('input[type="checkbox"]');
  allCheckboxes.forEach(cb => cb.checked = false);
  updateSelectedCount();
}

function updateSelectedCount() {
  const mrSelected = document.querySelectorAll('.mr-participants-checkbox:checked').length;
  const msSelected = document.querySelectorAll('.ms-participants-checkbox:checked').length;
  const total = mrSelected + msSelected;
  
  document.getElementById('selectedCount').textContent = total;
  document.getElementById('confirmButton').disabled = total === 0;
  
  // Update select all checkboxes
  const mrTotal = document.querySelectorAll('.mr-participants-checkbox').length;
  const msTotal = document.querySelectorAll('.ms-participants-checkbox').length;
  
  if (mrTotal > 0) {
    document.getElementById('selectAllMr').checked = mrSelected === mrTotal;
    document.getElementById('selectAllMr').indeterminate = mrSelected > 0 && mrSelected < mrTotal;
  }
  
  if (msTotal > 0) {
    document.getElementById('selectAllMs').checked = msSelected === msTotal;
    document.getElementById('selectAllMs').indeterminate = msSelected > 0 && msSelected < msTotal;
  }
}

// Add event listeners to all participant checkboxes
document.addEventListener('DOMContentLoaded', function() {
  const participantCheckboxes = document.querySelectorAll('.mr-participants-checkbox, .ms-participants-checkbox');
  participantCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
  });
  
  // Initial count
  updateSelectedCount();
  
  // Form submission confirmation
  document.getElementById('advancementForm').addEventListener('submit', function(e) {
    const selectedCount = parseInt(document.getElementById('selectedCount').textContent);
    if (selectedCount === 0) {
      e.preventDefault();
      showNotification('Please select at least one participant to advance', 'error', true);
      return;
    }
    
    if (!confirm(`Are you sure you want to advance ${selectedCount} participants to the next round? This action cannot be undone.`)) {
      e.preventDefault();
      return;
    }
    
    showNotification('Processing advancement...', 'info', true);
  });
});
</script>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
