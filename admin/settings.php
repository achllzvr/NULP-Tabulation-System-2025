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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
    
  if ($_POST['action'] === 'save_settings') {
    try {
      $reveal_names = isset($_POST['show_participant_names']) ? 1 : 0;
      $reveal_scores = isset($_POST['show_scores']) ? 1 : 0;
      $reveal_awards = isset($_POST['show_awards']) ? 1 : 0;
      $reveal_numbers = isset($_POST['show_participant_numbers']) ? 1 : 0;

      $mysqli = $con->opencon();

      // Create settings table if it doesn't exist
      $mysqli->query("CREATE TABLE IF NOT EXISTS pageant_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )");

      // Update or insert settings
      $settings = [
        'reveal_names' => $reveal_names,
        'reveal_scores' => $reveal_scores,
        'reveal_awards' => $reveal_awards,
        'reveal_numbers' => $reveal_numbers
      ];

      foreach ($settings as $key => $value) {
        $stmt = $mysqli->prepare("INSERT INTO pageant_settings (setting_key, setting_value) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
        $stmt->close();
      }

      $mysqli->close();
      $response = ['success' => true, 'message' => 'Settings updated successfully!'];
    } catch (Exception $e) {
      $response = ['success' => false, 'error' => 'Error updating settings: ' . $e->getMessage()];
    }
  }
    
    echo json_encode($response);
    exit;
}

// Get current settings for display
$settings = [];
try {
    $mysqli = $con->opencon();
    $result = $mysqli->query("SELECT setting_key, setting_value FROM pageant_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    $mysqli->close();
} catch (Exception $e) {
    // Settings table might not exist yet, that's okay
}

$pageTitle = 'Settings - Admin';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Settings</h1>
          <p class="text-slate-200">Configure pageant display and visibility options</p>
        </div>
      </div>
    </div>

    <!-- Settings Form -->
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
      <div class="px-6 py-4 border-b border-white border-opacity-10">
        <h3 class="text-lg font-semibold text-white">Public Display Settings</h3>
        <p class="text-sm text-slate-200 mt-1">Control what information is visible on public pages</p>
      </div>
      
      <form onsubmit="saveVisibility(event)" class="p-6">
        <input type="hidden" name="action" value="save_settings">
        
        <div class="space-y-6">
          <!-- Show Participant Names -->
          <div class="flex items-center justify-between p-4 bg-white bg-opacity-10 border border-white border-opacity-10 rounded-lg">
            <div class="flex-1">
              <label class="block text-sm font-medium text-white mb-1">Show Participant Names</label>
              <p class="text-sm text-slate-200">Display participant names on public leaderboard and results</p>
            </div>
            <div class="flex items-center">
              <input type="checkbox" 
                     name="show_participant_names" 
                     id="show_participant_names"
                     <?php echo (isset($settings['reveal_names']) && $settings['reveal_names']) ? 'checked' : ''; ?>
                     class="w-4 h-4 text-blue-400 bg-white bg-opacity-20 border-white border-opacity-30 rounded focus:ring-blue-400 focus:ring-2">
              <label for="show_participant_names" class="ml-2 text-sm text-slate-200">Enable</label>
            </div>
          </div>

          <!-- Show Participant Numbers -->
          <div class="flex items-center justify-between p-4 bg-white bg-opacity-10 border border-white border-opacity-10 rounded-lg">
            <div class="flex-1">
              <label class="block text-sm font-medium text-white mb-1">Show Participant Numbers</label>
              <p class="text-sm text-slate-200">Display participant numbers on public leaderboard and results</p>
            </div>
            <div class="flex items-center">
              <input type="checkbox" 
                     name="show_participant_numbers" 
                     id="show_participant_numbers"
                     <?php echo (isset($settings['reveal_numbers']) && $settings['reveal_numbers']) ? 'checked' : ''; ?>
                     class="w-4 h-4 text-blue-400 bg-white bg-opacity-20 border-white border-opacity-30 rounded focus:ring-blue-400 focus:ring-2">
              <label for="show_participant_numbers" class="ml-2 text-sm text-slate-200">Enable</label>
            </div>
          </div>

          <!-- Show Scores -->
          <div class="flex items-center justify-between p-4 bg-white bg-opacity-10 border border-white border-opacity-10 rounded-lg">
            <div class="flex-1">
              <label class="block text-sm font-medium text-white mb-1">Show Scores</label>
              <p class="text-sm text-slate-200">Display scores and rankings on public pages</p>
            </div>
            <div class="flex items-center">
              <input type="checkbox" 
                     name="show_scores" 
                     id="show_scores"
                     <?php echo (isset($settings['reveal_scores']) && $settings['reveal_scores']) ? 'checked' : ''; ?>
                     class="w-4 h-4 text-blue-400 bg-white bg-opacity-20 border-white border-opacity-30 rounded focus:ring-blue-400 focus:ring-2">
              <label for="show_scores" class="ml-2 text-sm text-slate-200">Enable</label>
            </div>
          </div>

          <!-- Show Awards -->
          <div class="flex items-center justify-between p-4 bg-white bg-opacity-10 border border-white border-opacity-10 rounded-lg">
            <div class="flex-1">
              <label class="block text-sm font-medium text-white mb-1">Show Awards</label>
              <p class="text-sm text-slate-200">Display awards and winners on public pages</p>
            </div>
            <div class="flex items-center">
              <input type="checkbox" 
                     name="show_awards" 
                     id="show_awards"
                     <?php echo (isset($settings['reveal_awards']) && $settings['reveal_awards']) ? 'checked' : ''; ?>
                     class="w-4 h-4 text-blue-400 bg-white bg-opacity-20 border-white border-opacity-30 rounded focus:ring-blue-400 focus:ring-2">
              <label for="show_awards" class="ml-2 text-sm text-slate-200">Enable</label>
            </div>
          </div>
        </div>

        <!-- Save Button -->
        <div class="mt-8 flex justify-end">
          <button type="submit" class="bg-blue-500 bg-opacity-30 hover:bg-blue-600 hover:bg-opacity-40 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2 border border-white border-opacity-20 backdrop-blur-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Save Settings
          </button>
        </div>
      </form>
    </div>

    <!-- Information Panel -->
    <div class="mt-8 bg-white bg-opacity-10 border border-blue-400 border-opacity-20 rounded-xl p-6 backdrop-blur-md">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-300 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
          <h4 class="font-semibold text-blue-200 mb-2">About Public Display Settings</h4>
          <div class="text-sm text-blue-100 space-y-2">
            <p>• <strong>Show Participant Names:</strong> Controls whether participant names are visible on public pages</p>
            <p>• <strong>Show Participant Numbers:</strong> Controls whether participant numbers are visible on public pages</p>
            <p>• <strong>Show Scores:</strong> Controls whether scores and rankings are displayed publicly</p>
            <p>• <strong>Show Awards:</strong> Controls whether award winners and results are shown publicly</p>
            <p>• Settings are applied immediately to all public pages</p>
            <p>• These settings do not affect admin pages - admins can always see all information</p>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
<script>
function saveVisibility(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  
  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('Settings updated successfully!', 'success', true);
    } else {
      showNotification(data.error || 'Error updating settings', 'error', true);
    }
  })
  .catch(error => {
    showNotification('Error updating settings', 'error', true);
    console.error('Error:', error);
  });
  
  return false;
}
</script>
