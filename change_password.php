<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include the database class file
require_once('classes/database.php');

// Create an instance of the database class
$con = new database();

// Determine current user
$user = null;
$user_id = null;
if (isset($_SESSION['adminID'])) {
    $user = [
        'user_id' => $_SESSION['adminID'],
        'name' => $_SESSION['adminFN'],
        'role' => 'admin'
    ];
    $user_id = $_SESSION['adminID'];
} elseif (isset($_SESSION['judgeID'])) {
    $user = [
        'user_id' => $_SESSION['judgeID'],
        'name' => $_SESSION['judgeFN'],
        'role' => 'judge'
    ];
    $user_id = $_SESSION['judgeID'];
}

// Handle password change
if (isset($_POST['change_password']) && $user_id) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password === $confirm_password) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $conn = $con->opencon();
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Password changed successfully.";
            $redirect = $_GET['redirect'] ?? ($user && strtoupper($user['role'])==='ADMIN' ? 'dashboard.php' : 'judge_active.php');
            header("Location: " . $redirect);
            exit();
        } else {
            $error_message = "Error changing password.";
        }
        $stmt->close();
        $conn->close();
    } else {
        $error_message = "Passwords do not match.";
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/partials/head.php';
?>
<main class="mx-auto max-w-sm w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Change Password</h1>
  
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
  
  <?php if(!$user): ?>
    <p class="text-sm text-slate-600">You must login first.</p>
    <div class="flex gap-3"><a class="text-blue-600 text-sm" href="login_admin.php">Admin Login</a><a class="text-indigo-600 text-sm" href="login_judge.php">Judge Login</a></div>
  <?php else: ?>
    <form id="changePasswordForm" method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
        <input type="password" name="new_password" minlength="8" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
        <input type="password" name="confirm_password" minlength="8" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm" />
      </div>
      <button name="change_password" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded w-full transition-all">Save New Password</button>
    </form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    makeFormLoadingEnabled('changePasswordForm', 'Updating password...', true);
});
</script>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
