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
                'reveal_awards' => $reveal_awards
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

$pageTitle = 'Settings';
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="mx-auto max-w-4xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Visibility & Settings</h1>
  <form id="visibilityForm" class="space-y-4 max-w-md" onsubmit="return saveVisibility(event)">
    <input type="hidden" name="action" value="save_settings">
    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="show_participant_names" class="rounded" <?php echo isset($settings['reveal_names']) && $settings['reveal_names'] ? 'checked' : ''; ?> /> 
      <span>Reveal Participant Names</span>
    </label>
    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="show_scores" class="rounded" <?php echo isset($settings['reveal_scores']) && $settings['reveal_scores'] ? 'checked' : ''; ?> /> 
      <span>Reveal Scores</span>
    </label>
    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="show_awards" class="rounded" <?php echo isset($settings['reveal_awards']) && $settings['reveal_awards'] ? 'checked' : ''; ?> /> 
      <span>Reveal Awards</span>
    </label>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Apply</button>
  </form>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
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
