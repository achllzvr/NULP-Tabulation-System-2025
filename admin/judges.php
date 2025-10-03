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

// Handle form submissions
if (isset($_POST['add_judge'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $pageant_id = $_SESSION['pageant_id'] ?? 1; // Use consistent session variable
    
    // Validate required fields
    if (empty($full_name) || empty($email)) {
        $error_message = "Full name and email are required.";
        $show_error_alert = true;
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
        $show_error_alert = true;
    } else {
        // Check if email already exists
        $conn = $con->opencon();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "A user with this email address already exists.";
            $show_error_alert = true;
        } else {
            // Generate username and password
            $username = strtolower(str_replace(' ', '', $full_name)) . rand(100, 999);
            $password = 'judge' . rand(1000, 9999);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // First add to users table (set global_role to null since 'judge' might not be a valid enum value)
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $password_hash, $full_name, $email);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
        // Then add to pageant_users mapping
        $stmt2 = $conn->prepare("INSERT INTO pageant_users (pageant_id, user_id, role) VALUES (?, ?, 'judge')");
        $stmt2->bind_param("ii", $pageant_id, $user_id);
        $stmt2->execute();
        $stmt2->close();
                
        if (true) {
                    $success_message = "Judge '$full_name' added successfully. Username: $username, Password: $password";
                    $show_success_alert = true;
                }
            } else {
                $error_message = "Error adding judge.";
                $error_type = "FORM_SUBMISSION_ERROR";
                $error_details = [
                    'form_type' => 'add_judge_user',
                    'mysql_error' => $conn->error,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $show_error_alert = true;
            }
        }
        $stmt->close();
        $conn->close();
    }
}

// Handle judge password reset
if (isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $pageant_id = $_SESSION['pageant_id'] ?? 1;
    
    // Generate new password
    $new_password = 'judge' . rand(1000, 9999);
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $conn = $con->opencon();
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $password_hash, $user_id);
    
    if ($stmt->execute()) {
        // Get judge name for success message
        $stmt2 = $conn->prepare("SELECT full_name, username FROM users WHERE id = ?");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $judge = $result->fetch_assoc();
        
        $success_message = "Password reset for {$judge['full_name']}. New password: $new_password";
        $show_success_alert = true;
        $stmt2->close();
    } else {
        $error_message = "Error resetting password.";
        $show_error_alert = true;
    }
    $stmt->close();
    $conn->close();
}

// Handle judge removal
if (isset($_POST['remove_judge'])) {
    $user_id = $_POST['user_id'];
    $pageant_id = $_SESSION['pageant_id'] ?? 1;
    
    $conn = $con->opencon();
    
    // First remove from pageant_users mapping
  $stmt = $conn->prepare("DELETE FROM pageant_users WHERE pageant_id = ? AND user_id = ? AND role = 'judge'");
    $stmt->bind_param("ii", $pageant_id, $user_id);
    
    if ($stmt->execute()) {
        // Check if user is used in other pageants
        $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM pageant_users WHERE user_id = ?");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $count = $result->fetch_assoc()['count'];
        
        // If not used in other pageants, remove from users table
        if ($count == 0) {
            $stmt3 = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt3->bind_param("i", $user_id);
            $stmt3->execute();
            $stmt3->close();
        }
        
        $success_message = "Judge removed successfully.";
        $show_success_alert = true;
        $stmt2->close();
    } else {
        $error_message = "Error removing judge.";
        $show_error_alert = true;
    }
    $stmt->close();
    $conn->close();
}

// Fetch judges
$conn = $con->opencon();
$pageant_id = $_SESSION['pageant_id'] ?? 1; // Use consistent session variable
$stmt = $conn->prepare("SELECT u.id, u.username, u.full_name, u.email, u.is_active, pu.role AS role
                        FROM users u 
                        JOIN pageant_users pu ON u.id = pu.user_id 
                        WHERE pu.pageant_id = ? AND pu.role = 'judge' 
                        ORDER BY u.full_name");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$judges = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Judges';
$rows = $judges; // Use fetched judges data for table
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Judges</h1>
          <p class="text-slate-200">Manage pageant judges and their credentials</p>
        </div>
        <button onclick="showModal('addJudgeModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Add Judge
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Total Judges</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-white mb-1"><?php echo count($judges); ?></p>
        <p class="text-sm text-slate-200">Assigned judges</p>
      </div>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Active Accounts</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-white mb-1">
          <?php echo count(array_filter($judges, fn($j) => $j['is_active'] ?? 1)); ?>
        </p>
        <p class="text-sm text-slate-200">Ready to judge</p>
      </div>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Judge Status</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
        </div>
        <p class="text-lg font-bold text-white mb-1">
          <?php echo count($judges) > 0 ? 'Ready' : 'Setup Needed'; ?>
        </p>
        <p class="text-sm text-slate-200">Judging readiness</p>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="bg-green-500 bg-opacity-20 backdrop-blur-sm border border-green-400 border-opacity-30 text-green-100 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-300" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
      <div class="bg-red-500 bg-opacity-20 backdrop-blur-sm border border-red-400 border-opacity-30 text-red-100 px-6 py-4 rounded-lg text-sm mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-300" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- Judges Table -->
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
      <div class="px-6 py-4 border-b border-white border-opacity-20">
        <h3 class="text-lg font-semibold text-white">Judge Accounts</h3>
        <p class="text-sm text-slate-200 mt-1">All judges assigned to this pageant</p>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-white bg-opacity-10 backdrop-blur-sm">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Judge</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Contact</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Account</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white divide-opacity-10">
            <?php if (!empty($judges)): ?>
              <?php foreach ($judges as $judge): ?>
                <tr class="hover:bg-white hover:bg-opacity-5 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-10 h-10 bg-white bg-opacity-20 backdrop-blur-sm rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($judge['full_name']); ?></div>
                        <div class="text-sm text-slate-200">Judge ID: <?php echo (int)$judge['id']; ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-white"><?php echo htmlspecialchars($judge['email']); ?></div>
                    <div class="text-sm text-slate-200"><?php echo htmlspecialchars($judge['username']); ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-white">Username: <?php echo htmlspecialchars($judge['username']); ?></div>
                    <div class="text-sm text-slate-200">Role: <?php echo ucfirst($judge['role']); ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($judge['is_active'] ?? 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                      <?php echo ($judge['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reset the password for <?php echo htmlspecialchars($judge['full_name'], ENT_QUOTES); ?>? A new password will be generated.')">
                        <input type="hidden" name="user_id" value="<?php echo $judge['id']; ?>">
                        <button name="reset_password" type="submit" class="text-blue-300 hover:text-blue-400 font-medium">Reset Password</button>
                      </form>
                      
                      <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($judge['full_name'], ENT_QUOTES); ?> as a judge? This action cannot be undone.')">
                        <input type="hidden" name="user_id" value="<?php echo $judge['id']; ?>">
                        <button name="remove_judge" type="submit" class="text-red-300 hover:text-red-400 font-medium">Remove</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="px-6 py-12 text-center">
                  <div class="text-slate-200">
                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h3 class="text-sm font-medium text-slate-900 mb-2">No judges found</h3>
                    <p class="text-sm text-slate-200 mb-4">Get started by adding your first judge.</p>
                    <button onclick="showModal('addJudgeModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition-colors">
                      Add Judge
                    </button>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php
$modalId = 'addJudgeModal';
$title = 'Add New Judge';
$bodyHtml = '<form id="addJudgeForm" method="POST" class="space-y-6">'
  .'<div>'
    .'<label class="block text-sm font-medium text-slate-700 mb-2">Full Name</label>'
    .'<input name="full_name" type="text" placeholder="Enter judge full name" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required />'
  .'</div>'
  .'<div>'
    .'<label class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>'
    .'<input type="email" name="email" placeholder="Enter judge email address" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required />'
  .'</div>'
  .'<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">'
    .'<div class="flex items-center gap-2 mb-2">'
      .'<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
        .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
      .'</svg>'
      .'<h4 class="text-sm font-medium text-blue-800">Account Information</h4>'
    .'</div>'
    .'<p class="text-sm text-blue-700">A username and password will be generated automatically. You will receive the login credentials after creating the judge account.</p>'
  .'</div>'
  .'<div class="flex gap-3 pt-4">'
  .'<button type="button" onclick="hideModal(\'addJudgeModal\')" class="flex-1 bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-6 py-3 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Cancel</button>'
  .'<button name="add_judge" type="submit" class="flex-1 bg-blue-500 bg-opacity-30 hover:bg-blue-500/40 text-white font-medium px-6 py-3 rounded-lg border border-blue-400 border-opacity-50 backdrop-blur-sm transition-colors flex items-center justify-center gap-2">'
      .'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
        .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'
      .'</svg>'
      .'Create Judge Account'
    .'</button>'
  .'</div>'
  .'</form>'
  .'<script>makeFormLoadingEnabled("addJudgeForm", "Creating judge account...", true);</script>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';
?>

<?php if (isset($show_success_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showSuccess('Judge Created!', '<?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>');
});
</script>
<?php endif; ?>

<?php if (isset($show_error_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showDetailedError('<?= $error_type ?>', '<?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>', <?= json_encode($error_details) ?>);
});
</script>
<?php endif; ?>

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
