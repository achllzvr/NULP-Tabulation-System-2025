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
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 mb-2">Advancement Review</h1>
          <p class="text-slate-600">Select participants to advance to the next round</p>
        </div>
        <div class="flex gap-3">
          <select id="advancementCount" onchange="updateAdvancementCount()" class="border border-slate-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="3" <?php echo $advancement_count === 3 ? 'selected' : ''; ?>>Top 3</option>
            <option value="5" <?php echo $advancement_count === 5 ? 'selected' : ''; ?>>Top 5</option>
            <option value="10" <?php echo $advancement_count === 10 ? 'selected' : ''; ?>>Top 10</option>
            <option value="15" <?php echo $advancement_count === 15 ? 'selected' : ''; ?>>Top 15</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- Advancement Confirmed Warning -->
    <?php if ($advancements_confirmed): ?>
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <div class="flex items-start">
          <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Advancements Already Confirmed</h3>
            <p class="text-blue-800 mb-3">
              Participant advancements have been confirmed for <strong><?php echo htmlspecialchars($next_round_name, ENT_QUOTES, 'UTF-8'); ?></strong>. 
              All advancement controls have been disabled to prevent accidental changes.
            </p>
            <div class="text-sm text-blue-700">
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

    <!-- Advancement Form -->
    <form method="POST" id="advancementForm" <?php echo $advancements_confirmed ? 'style="pointer-events: none; opacity: 0.6;"' : ''; ?>>
      <div class="grid lg:grid-cols-2 gap-8 mb-8">
        
        <!-- Mr Division -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
          <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-blue-50 to-blue-100">
            <h2 class="text-lg font-semibold text-slate-800">Mr Division - Top <?php echo $advancement_count; ?></h2>
            <p class="text-sm text-slate-600 mt-1">Select participants to advance</p>
          </div>
          
          <div class="p-6">
            <?php if (!empty($mr_top)): ?>
              <div class="space-y-3">
                <div class="flex items-center justify-between text-sm font-medium text-slate-700 border-b pb-2">
                  <span>
                    <input type="checkbox" id="selectAllMr" onchange="toggleAllMr(this)" class="mr-2">
                    Select All
                  </span>
                  <span>Score</span>
                </div>
                <?php foreach ($mr_top as $participant): ?>
                  <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-blue-50 transition-colors">
                    <div class="flex items-center">
                      <input type="checkbox" name="mr_participants[]" value="<?php echo $participant['id']; ?>" 
                             id="mr_<?php echo $participant['id']; ?>" class="mr-participants-checkbox mr-3">
                      <div>
                        <div class="flex items-center gap-2">
                          <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold <?php 
                            echo $participant['rank'] === 1 ? 'bg-yellow-100 text-yellow-800' : 
                                 ($participant['rank'] === 2 ? 'bg-gray-100 text-gray-800' : 
                                 ($participant['rank'] === 3 ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-600')); 
                          ?>">
                            <?php echo $participant['rank']; ?>
                          </span>
                          <span class="font-medium text-slate-800"><?php echo htmlspecialchars($participant['name']); ?></span>
                        </div>
                        <div class="text-sm text-slate-600">#<?php echo htmlspecialchars($participant['number_label']); ?></div>
                      </div>
                    </div>
                    <div class="font-mono font-semibold text-slate-700">
                      <?php echo $participant['score']; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-8 text-slate-500">
                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="font-medium">No Mr Division participants found</p>
                <p class="text-sm">Make sure participants are registered and scored.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Ms Division -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
          <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-pink-50 to-pink-100">
            <h2 class="text-lg font-semibold text-slate-800">Ms Division - Top <?php echo $advancement_count; ?></h2>
            <p class="text-sm text-slate-600 mt-1">Select participants to advance</p>
          </div>
          
          <div class="p-6">
            <?php if (!empty($ms_top)): ?>
              <div class="space-y-3">
                <div class="flex items-center justify-between text-sm font-medium text-slate-700 border-b pb-2">
                  <span>
                    <input type="checkbox" id="selectAllMs" onchange="toggleAllMs(this)" class="mr-2">
                    Select All
                  </span>
                  <span>Score</span>
                </div>
                <?php foreach ($ms_top as $participant): ?>
                  <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-pink-50 transition-colors">
                    <div class="flex items-center">
                      <input type="checkbox" name="ms_participants[]" value="<?php echo $participant['id']; ?>" 
                             id="ms_<?php echo $participant['id']; ?>" class="ms-participants-checkbox mr-3">
                      <div>
                        <div class="flex items-center gap-2">
                          <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold <?php 
                            echo $participant['rank'] === 1 ? 'bg-yellow-100 text-yellow-800' : 
                                 ($participant['rank'] === 2 ? 'bg-gray-100 text-gray-800' : 
                                 ($participant['rank'] === 3 ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-600')); 
                          ?>">
                            <?php echo $participant['rank']; ?>
                          </span>
                          <span class="font-medium text-slate-800"><?php echo htmlspecialchars($participant['name']); ?></span>
                        </div>
                        <div class="text-sm text-slate-600">#<?php echo htmlspecialchars($participant['number_label']); ?></div>
                      </div>
                    </div>
                    <div class="font-mono font-semibold text-slate-700">
                      <?php echo $participant['score']; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-center py-8 text-slate-500">
                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="font-medium">No Ms Division participants found</p>
                <p class="text-sm">Make sure participants are registered and scored.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-between items-center">
        <div class="text-sm text-slate-600">
          <span id="selectedCount">0</span> participants selected for advancement
        </div>
        <div class="flex gap-4">
          <button type="button" 
                  onclick="clearAllSelections()" 
                  <?php echo $advancements_confirmed ? 'disabled' : ''; ?>
                  class="<?php echo $advancements_confirmed ? 'bg-slate-400 cursor-not-allowed' : 'bg-slate-600 hover:bg-slate-700'; ?> text-white font-medium px-6 py-3 rounded-lg transition-colors">
            Clear All
          </button>
          <button type="submit" 
                  name="confirm_advancement" 
                  id="confirmButton" 
                  disabled 
                  class="bg-blue-600 hover:bg-blue-700 disabled:bg-slate-400 disabled:cursor-not-allowed text-white font-medium px-6 py-3 rounded-lg transition-colors">
            <?php echo $advancements_confirmed ? 'Advancement Confirmed' : 'Confirm Advancement'; ?>
          </button>
        </div>
      </div>
    </form>
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
