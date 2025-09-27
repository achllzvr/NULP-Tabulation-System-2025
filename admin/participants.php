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
if (isset($_POST['add_participant'])) {
    $division = $_POST['division'];
    $number_label = $_POST['number_label'];
    $full_name = $_POST['full_name'];
    $advocacy = $_POST['advocacy'];
    $pageant_id = $_SESSION['pageantID'];
    
    // Add participant to database
    $conn = $con->opencon();
    $stmt = $conn->prepare("INSERT INTO participants (pageant_id, division, number_label, full_name, advocacy, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("issss", $pageant_id, $division, $number_label, $full_name, $advocacy);
    
    if ($stmt->execute()) {
        $success_message = "Participant added successfully.";
        $show_success_alert = true;
    } else {
        $error_message = "Error adding participant.";
        $error_type = "FORM_SUBMISSION_ERROR";
        $error_details = [
            'form_type' => 'add_participant',
            'mysql_error' => $conn->error,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $show_error_alert = true;
    }
    $stmt->close();
    $conn->close();
}

// Fetch participants
$conn = $con->opencon();
$pageant_id = $_SESSION['pageantID'];
$stmt = $conn->prepare("SELECT * FROM participants WHERE pageant_id = ? ORDER BY number_label");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$participants = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Participants';
$columns = [
  ['header'=>'Number','field'=>'number_label'],
  ['header'=>'Division','field'=>'division'],
  ['header'=>'Name','field'=>'full_name'],
  ['header'=>'Advocacy','field'=>'advocacy'],
  ['header'=>'Active','field'=>'is_active'],
];
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="mx-auto max-w-7xl w-full p-6 space-y-6">
  <div class="flex justify-between items-center">
    <h1 class="text-xl font-semibold text-slate-800">Participants</h1>
    <button onclick="showModal('addParticipantModal')" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Add Participant</button>
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
$modalId = 'addParticipantModal';
$title = 'Add Participant';
$bodyHtml = '<form id="addParticipantForm" method="POST" class="space-y-4">'
  .'<div><label class="block text-xs font-medium mb-1">Division</label><select name="division" class="w-full border rounded px-2 py-1"><option>Mr</option><option>Ms</option></select></div>'
  .'<div><label class="block text-xs font-medium mb-1">Number Label</label><input name="number_label" class="w-full border rounded px-2 py-1" required /></div>'
  .'<div><label class="block text-xs font-medium mb-1">Full Name</label><input name="full_name" class="w-full border rounded px-2 py-1" required /></div>'
  .'<div><label class="block text-xs font-medium mb-1">Advocacy</label><textarea name="advocacy" class="w-full border rounded px-2 py-1" rows="3"></textarea></div>'
  .'<div class="text-right"><button name="add_participant" type="submit" class="bg-blue-600 hover:bg-blue-700 transition-all text-white px-4 py-2 rounded text-sm">Save</button></div>'
  .'</form>'
  .'<script>makeFormLoadingEnabled("addParticipantForm", "Adding participant...", true);</script>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';
?>

<?php if (isset($show_success_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showSuccess('Success!', '<?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>');
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
