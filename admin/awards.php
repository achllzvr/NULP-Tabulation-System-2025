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
$conn = $con->opencon();

// Get pageant ID from session
$pageant_id = $_SESSION['pageant_id'] ?? 1;

// Handle award generation
if (isset($_POST['generate_awards'])) {
    // This is a simplified award system - you can expand this later
    $success_message = "Award generation feature will be implemented when scoring system is complete.";
    $show_success_alert = true;
}

// Get basic pageant data for awards display
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM participants WHERE pageant_id = ? AND is_active = 1");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$participants_count = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND state = 'FINALIZED'");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$finalized_rounds = $result->fetch_assoc()['count'];

// Check if final round is completed
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND scoring_mode = 'FINAL' AND state = 'FINALIZED'");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$final_rounds_completed = $result->fetch_assoc()['count'];

// Check total final rounds
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rounds WHERE pageant_id = ? AND scoring_mode = 'FINAL'");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$total_final_rounds = $result->fetch_assoc()['count'];

$all_final_rounds_completed = ($total_final_rounds > 0 && $final_rounds_completed >= $total_final_rounds);

$conn->close();

$pageTitle = 'Awards Management';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Awards Management</h1>
          <p class="text-slate-200">Generate and manage pageant awards and recognitions</p>
        </div>
        <div class="flex gap-3">
          <button onclick="generatePreview()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Preview Awards
          </button>
        </div>
      </div>
    </div>

    <!-- Status Cards -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Total Participants</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo $participants_count; ?></p>
  <p class="text-sm text-slate-200">Active contestants</p>
      </div>

  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Completed Rounds</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
  <p class="text-3xl font-bold text-white mb-1"><?php echo $finalized_rounds; ?></p>
  <p class="text-sm text-slate-200">Finalized rounds</p>
      </div>

  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Award Status</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
          </svg>
        </div>
  <p class="text-lg font-bold text-white mb-1">
          <?php echo $finalized_rounds > 0 ? 'Ready' : 'Pending'; ?>
        </p>
  <p class="text-sm text-slate-200">Generation status</p>
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

    <!-- Awards Configuration -->
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
      <div class="px-6 py-4 border-b border-white border-opacity-10">
        <h3 class="text-lg font-semibold text-white">Award Categories</h3>
        <p class="text-sm text-slate-200 mt-1">Configure and generate pageant awards</p>
      </div>
      
      <div class="p-6">
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Major Awards -->
          <div class="space-y-4">
            <h4 class="font-semibold text-white mb-4">Major Awards</h4>
            
            <div class="border border-white border-opacity-10 rounded-lg p-4 bg-white bg-opacity-10">
              <div class="flex items-center justify-between mb-2">
                <h5 class="font-medium text-white">Overall Winner</h5>
                <span class="px-2 py-1 text-xs bg-yellow-400 bg-opacity-20 text-yellow-200 rounded-full">Crown</span>
              </div>
              <p class="text-sm text-slate-200 mb-3">Highest total score across all rounds</p>
              <div class="text-xs text-slate-200">
                <span>• Mr Division Winner</span><br>
                <span>• Ms Division Winner</span>
              </div>
            </div>

            <div class="border border-white border-opacity-10 rounded-lg p-4 bg-white bg-opacity-10">
              <div class="flex items-center justify-between mb-2">
                <h5 class="font-medium text-white">Runner-up</h5>
                <span class="px-2 py-1 text-xs bg-slate-400 bg-opacity-20 text-slate-200 rounded-full">2nd Place</span>
              </div>
              <p class="text-sm text-slate-200 mb-3">Second highest total score</p>
              <div class="text-xs text-slate-200">
                <span>• 1st Runner-up Mr</span><br>
                <span>• 1st Runner-up Ms</span>
              </div>
            </div>

            <div class="border border-white border-opacity-10 rounded-lg p-4 bg-white bg-opacity-10">
              <div class="flex items-center justify-between mb-2">
                <h5 class="font-medium text-white">Second Runner-up</h5>
                <span class="px-2 py-1 text-xs bg-orange-400 bg-opacity-20 text-orange-200 rounded-full">3rd Place</span>
              </div>
              <p class="text-sm text-slate-200 mb-3">Third highest total score</p>
              <div class="text-xs text-slate-200">
                <span>• 2nd Runner-up Mr</span><br>
                <span>• 2nd Runner-up Ms</span>
              </div>
            </div>
          </div>

          <!-- Special Awards -->
          <div class="space-y-4">
            <h4 class="font-semibold text-white mb-4">Special Awards</h4>
            
            <div class="border border-white border-opacity-10 rounded-lg p-4 bg-white bg-opacity-10">
              <div class="flex items-center justify-between mb-2">
                <h5 class="font-medium text-white">People's Choice</h5>
                <span class="px-2 py-1 text-xs bg-blue-400 bg-opacity-20 text-blue-200 rounded-full">Popular</span>
              </div>
              <p class="text-sm text-slate-200">Audience favorite based on public voting</p>
            </div>

            <div class="border border-white border-opacity-10 rounded-lg p-4 bg-white bg-opacity-10">
              <div class="flex items-center justify-between mb-2">
                <h5 class="font-medium text-white">Best in Talent</h5>
                <span class="px-2 py-1 text-xs bg-green-400 bg-opacity-20 text-green-200 rounded-full">Performance</span>
              </div>
              <p class="text-sm text-slate-200">Highest score in talent round</p>
            </div>

            <div class="border border-white border-opacity-10 rounded-lg p-4 bg-white bg-opacity-10">
              <div class="flex items-center justify-between mb-2">
                <h5 class="font-medium text-white">Miss/Mr Congeniality</h5>
                <span class="px-2 py-1 text-xs bg-pink-400 bg-opacity-20 text-pink-200 rounded-full">Friendship</span>
              </div>
              <p class="text-sm text-slate-200">Voted by fellow contestants</p>
            </div>
          </div>
        </div>

        <!-- Final Round Status Alert -->
        <?php if (!$all_final_rounds_completed): ?>
          <div class="mt-6 bg-white bg-opacity-10 border border-yellow-400 border-opacity-20 rounded-lg p-4 backdrop-blur-md">
            <div class="flex items-start">
              <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              <div>
                <h4 class="text-sm font-medium text-yellow-300">Final Round Not Complete</h4>
                <p class="text-sm text-yellow-200 mt-1">
                  Awards generation is disabled until all final rounds are completed. 
                  <?php if ($total_final_rounds > 0): ?>
                    Currently <?php echo $final_rounds_completed; ?> of <?php echo $total_final_rounds; ?> final rounds completed.
                  <?php else: ?>
                    No final rounds have been configured yet.
                  <?php endif; ?>
                </p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Action Buttons -->
  <div class="mt-8 flex gap-4">
          <form method="POST" class="inline">
            <button name="generate_awards" 
                    type="submit" 
                    <?php echo !$all_final_rounds_completed ? 'disabled' : ''; ?>
                    class="<?php echo $all_final_rounds_completed ? 'bg-green-500 bg-opacity-30 hover:bg-green-500/40 border border-green-400 border-opacity-50' : 'bg-white bg-opacity-10 border border-white border-opacity-20 cursor-not-allowed'; ?> text-white font-medium px-6 py-3 rounded-lg backdrop-blur-sm transition-colors flex items-center gap-2"
                    <?php if (!$all_final_rounds_completed): ?>
                      title="Final round must be completed before generating awards"
                    <?php endif; ?>>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
              </svg>
              Generate Awards
            </button>
          </form>
          
          <button onclick="showModal('previewModal')" class="bg-blue-500 bg-opacity-30 hover:bg-blue-500/40 text-white font-medium px-6 py-3 rounded-lg border border-blue-400 border-opacity-50 backdrop-blur-sm transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Preview Results
          </button>
        </div>
      </div>
    </div>

    <!-- Information Panel -->
  <div class="bg-white bg-opacity-10 border border-blue-400 border-opacity-20 rounded-xl p-6 backdrop-blur-md">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
          <h4 class="font-semibold text-blue-300 mb-2">Award Generation Information</h4>
          <div class="text-sm text-blue-200 space-y-2">
            <p>• Awards are automatically calculated based on final scores from all completed rounds</p>
            <p>• Ensure all rounds are finalized before generating awards</p>
            <p>• Major awards are divided by gender division (Mr/Ms)</p>
            <p>• Special awards may require additional data collection</p>
            <p>• Generated awards can be reviewed before being published</p>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php
// Preview Modal
$modalId = 'previewModal';
$title = 'Awards Preview';
$bodyHtml = '<div class="space-y-4">'
  .'<div class="bg-white bg-opacity-10 border border-yellow-400 border-opacity-20 rounded-lg p-4 backdrop-blur-md">'
    .'<div class="flex items-center gap-2 mb-2">'
      .'<svg class="w-5 h-5 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
        .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
      .'</svg>'
      .'<h4 class="text-sm font-medium text-yellow-300">Preview Not Available</h4>'
    .'</div>'
    .'<p class="text-sm text-yellow-200">Award preview will be available after the scoring system is fully implemented and rounds are completed.</p>'
  .'</div>'
  .'<div class="pt-4">'
    .'<button onclick="hideModal(\'previewModal\')" class="w-full bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-6 py-3 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Close</button>'
  .'</div>'
  .'</div>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';
?>

<script>
function generatePreview() {
  showNotification('Awards preview will be available when scoring system is complete', 'info', true);
}
</script>

<?php if (isset($show_success_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showSuccess('Success!', '<?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>');
});
</script>
<?php endif; ?>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
