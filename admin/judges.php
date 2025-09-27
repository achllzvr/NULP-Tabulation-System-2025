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
    $pageant_id = $_SESSION['pageantID'];
    
    // Generate username and password
    $username = strtolower(str_replace(' ', '', $full_name)) . rand(100, 999);
    $password = 'judge' . rand(1000, 9999);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Add judge to database
    $conn = $con->opencon();
    
    // First add to users table
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, email, global_role) VALUES (?, ?, ?, ?, 'judge')");
    $stmt->bind_param("ssss", $username, $password_hash, $full_name, $email);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Then add to pageant_users mapping
        $stmt2 = $conn->prepare("INSERT INTO pageant_users (pageant_id, user_id, role) VALUES (?, ?, 'judge')");
        $stmt2->bind_param("ii", $pageant_id, $user_id);
        
        if ($stmt2->execute()) {
            $success_message = "Judge added successfully. Username: $username, Password: $password";
            $show_success_alert = true;
        } else {
            $error_message = "Error adding judge to pageant.";
            $error_type = "FORM_SUBMISSION_ERROR";
            $error_details = [
                'form_type' => 'add_judge_mapping',
                'mysql_error' => $conn->error,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $show_error_alert = true;
        }
        $stmt2->close();
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
    $stmt->close();
    $conn->close();
}

// Fetch judges
$conn = $con->opencon();
$pageant_id = $_SESSION['pageantID'];
$stmt = $conn->prepare("SELECT u.*, pu.role FROM users u JOIN pageant_users pu ON u.id = pu.user_id WHERE pu.pageant_id = ? AND pu.role = 'judge' ORDER BY u.full_name");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$judges = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Judges';
$columns = [
  ['header'=>'Name','field'=>'full_name'],
  ['header'=>'Email','field'=>'email'],
  ['header'=>'User ID','field'=>'id'],
];
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="mx-auto max-w-7xl w-full p-6 space-y-6">
  <div class="flex justify-between items-center">
    <h1 class="text-xl font-semibold text-slate-800">Judges</h1>
    <button onclick="showModal('addJudgeModal')" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Add Judge</button>
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
  
  <?php include __DIR__ . '/../components/table.php'; ?>
</main>
<?php
$modalId = 'addJudgeModal';
$title = 'Add Judge';
$bodyHtml = '<form id="addJudgeForm" method="POST" class="space-y-4">'
  .'<div><label class="block text-xs font-medium mb-1">Full Name</label><input name="full_name" class="w-full border rounded px-2 py-1" required /></div>'
  .'<div><label class="block text-xs font-medium mb-1">Email</label><input type="email" name="email" class="w-full border rounded px-2 py-1" required /></div>'
  .'<div class="text-right"><button name="add_judge" type="submit" class="bg-blue-600 hover:bg-blue-700 transition-all text-white px-4 py-2 rounded text-sm">Create</button></div>'
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

<?php include __DIR__ . '/../partials/footer.php'; ?>
