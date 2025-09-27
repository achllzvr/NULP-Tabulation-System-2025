<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session  
session_start();

// Include the database class file
require_once('../classes/database.php');

// Create an instance of the database class
$con = new database();

// Handle form submission
if (isset($_POST['lookup_pageant'])) {
    $code = trim($_POST['code']);
    $section = $_POST['section'];
    
    if (!empty($code)) {
        // Look up pageant by code
        $pageant = $con->getPageantByCode($code);
        
        if ($pageant) {
            $pageant_id = $pageant['id'];
            
            // Redirect based on section
            if ($section === 'prelim') {
                header("Location: public_prelim.php?pageant_id=" . $pageant_id);
            } elseif ($section === 'final') {
                header("Location: public_final.php?pageant_id=" . $pageant_id);
            } else {
                header("Location: public_awards.php?pageant_id=" . $pageant_id);
            }
            exit();
        } else {
            $error_message = "Invalid pageant code. Please check and try again.";
            $error_type = "INVALID_PAGEANT_CODE";
            $error_details = [
                'attempted_code' => $code,
                'section' => $section,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            $show_error_alert = true;
        }
    } else {
        $error_message = "Pageant code is required.";
    }
}

$pageTitle = 'Enter Pageant Code';
include __DIR__ . '/../partials/head.php';
?>
<main class="mx-auto max-w-sm w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">View Public Results</h1>
  
  <?php if (isset($error_message)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>
  
  <form id="pageantForm" method="POST" class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Pageant Code</label>
      <input name="code" type="text" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:border-blue-500 uppercase tracking-wide" />
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Section</label>
      <select name="section" class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
        <option value="prelim">Preliminary Standings</option>
        <option value="final">Final Results</option>
        <option value="awards">Awards</option>
      </select>
    </div>
    <button name="lookup_pageant" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded w-full transition-all">Continue</button>
  </form>
  <p class="text-xs text-slate-500">Enter the official code distributed by event organizers.</p>
</main>

<?php if (isset($show_error_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showDetailedError('<?= $error_type ?>', '<?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>', <?= json_encode($error_details) ?>);
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Make the form show loading state when submitted
    makeFormLoadingEnabled('pageantForm', 'Looking up pageant...', true);
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
