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
    $pageant_id = $_SESSION['pageant_id'] ?? 1; // Use consistent session variable
    
    // Validate required fields
    if (empty($full_name) || empty($number_label)) {
        $error_message = "Full name and number label are required.";
        $show_error_alert = true;
    } else {
        // Check if number already exists
        $conn = $con->opencon();
        $stmt = $conn->prepare("SELECT id FROM participants WHERE pageant_id = ? AND number_label = ?");
        $stmt->bind_param("is", $pageant_id, $number_label);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Participant number '$number_label' already exists.";
            $show_error_alert = true;
        } else {
            // Add participant to database (assuming division column doesn't exist in DB yet)
            $stmt = $conn->prepare("INSERT INTO participants (pageant_id, number_label, full_name, advocacy, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("isss", $pageant_id, $number_label, $full_name, $advocacy);
            
            if ($stmt->execute()) {
                $success_message = "Participant '$full_name' (#$number_label) added successfully.";
                $show_success_alert = true;
            } else {
                $error_message = "Error adding participant: " . $conn->error;
                $show_error_alert = true;
            }
        }
        $stmt->close();
        $conn->close();
    }
}

// Handle participant deletion
if (isset($_POST['delete_participant'])) {
    $participant_id = $_POST['participant_id'];
    $pageant_id = $_SESSION['pageant_id'] ?? 1;
    
    $conn = $con->opencon();
    $stmt = $conn->prepare("DELETE FROM participants WHERE id = ? AND pageant_id = ?");
    $stmt->bind_param("ii", $participant_id, $pageant_id);
    
    if ($stmt->execute()) {
        $success_message = "Participant deleted successfully.";
        $show_success_alert = true;
    } else {
        $error_message = "Error deleting participant.";
        $show_error_alert = true;
    }
    $stmt->close();
    $conn->close();
}

// Handle participant status toggle
if (isset($_POST['toggle_participant'])) {
    $participant_id = $_POST['participant_id'];
    $new_status = $_POST['new_status'];
    $pageant_id = $_SESSION['pageant_id'] ?? 1;
    
    $conn = $con->opencon();
    $stmt = $conn->prepare("UPDATE participants SET is_active = ? WHERE id = ? AND pageant_id = ?");
    $stmt->bind_param("iii", $new_status, $participant_id, $pageant_id);
    
    if ($stmt->execute()) {
        $success_message = "Participant status updated successfully.";
        $show_success_alert = true;
    } else {
        $error_message = "Error updating participant status.";
        $show_error_alert = true;
    }
    $stmt->close();
    $conn->close();
}

// Handle participant editing
if (isset($_POST['edit_participant'])) {
    $participant_id = $_POST['participant_id'];
    $division = $_POST['division'];
    $number_label = $_POST['number_label'];
    $full_name = $_POST['full_name'];
    $advocacy = $_POST['advocacy'];
    $pageant_id = $_SESSION['pageant_id'] ?? 1;
    
    // Validate required fields
    if (empty($full_name) || empty($number_label)) {
        $error_message = "Full name and number label are required.";
        $show_error_alert = true;
    } else {
        // Check if number already exists (excluding current participant)
        $conn = $con->opencon();
        $stmt = $conn->prepare("SELECT id FROM participants WHERE pageant_id = ? AND number_label = ? AND id != ?");
        $stmt->bind_param("isi", $pageant_id, $number_label, $participant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Participant number '$number_label' already exists.";
            $show_error_alert = true;
        } else {
            // Update participant (assuming division column doesn't exist in DB yet)
            $stmt = $conn->prepare("UPDATE participants SET number_label = ?, full_name = ?, advocacy = ? WHERE id = ? AND pageant_id = ?");
            $stmt->bind_param("sssii", $number_label, $full_name, $advocacy, $participant_id, $pageant_id);
            
            if ($stmt->execute()) {
                $success_message = "Participant '$full_name' (#$number_label) updated successfully.";
                $show_success_alert = true;
            } else {
                $error_message = "Error updating participant: " . $conn->error;
                $show_error_alert = true;
            }
        }
        $stmt->close();
        $conn->close();
    }
}

// Fetch participants
$conn = $con->opencon();
$pageant_id = $_SESSION['pageant_id'] ?? 1; // Use consistent session variable
$stmt = $conn->prepare("SELECT * FROM participants WHERE pageant_id = ? ORDER BY number_label");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$participants = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Participants';
$rows = $participants; // Use fetched participants data for table
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/nav_admin.php';
?>
<main class="bg-slate-50 min-h-screen">
  <div class="mx-auto max-w-7xl px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 mb-2">Participants</h1>
          <p class="text-slate-600">Manage pageant participants and their information</p>
        </div>
        <button onclick="showModal('addParticipantModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          Add Participant
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Total Participants</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo count($participants); ?></p>
        <p class="text-sm text-slate-600">Registered participants</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Mr Division</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1">
          <?php echo count(array_filter($participants, fn($p) => isset($p['division']) && $p['division'] === 'Mr')); ?>
        </p>
        <p class="text-sm text-slate-600">Male participants</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-600">Ms Division</h3>
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-slate-800 mb-1">
          <?php echo count(array_filter($participants, fn($p) => isset($p['division']) && $p['division'] === 'Ms')); ?>
        </p>
        <p class="text-sm text-slate-600">Female participants</p>
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

    <!-- Participants Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
      <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-800">Participant List</h3>
        <p class="text-sm text-slate-600 mt-1">All registered participants for this pageant</p>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Number</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Division</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Advocacy</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <?php if (!empty($participants)): ?>
              <?php foreach ($participants as $participant): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-sm font-bold text-blue-600"><?php echo htmlspecialchars($participant['number_label']); ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php 
                    // Infer division from number or use default (since division column doesn't exist yet)
                    $division = isset($participant['division']) ? $participant['division'] : 'General';
                    ?>
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $division === 'Mr' ? 'bg-blue-100 text-blue-800' : ($division === 'Ms' ? 'bg-pink-100 text-pink-800' : 'bg-slate-100 text-slate-600'); ?>">
                      <?php echo htmlspecialchars($division); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($participant['full_name']); ?></div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-slate-600 max-w-xs truncate" title="<?php echo htmlspecialchars($participant['advocacy']); ?>">
                      <?php echo htmlspecialchars($participant['advocacy'] ?: 'No advocacy'); ?>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $participant['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                      <?php echo $participant['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <button onclick="editParticipant(<?php echo $participant['id']; ?>, '<?php echo htmlspecialchars($participant['full_name'], ENT_QUOTES); ?>', '<?php echo $participant['number_label']; ?>', '<?php echo isset($participant['division']) ? $participant['division'] : 'General'; ?>', '<?php echo htmlspecialchars($participant['advocacy'], ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-900 font-medium">Edit</button>
                      
                      <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this participant?')">
                        <input type="hidden" name="participant_id" value="<?php echo $participant['id']; ?>">
                        <button name="delete_participant" type="submit" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                      </form>
                      
                      <form method="POST" class="inline">
                        <input type="hidden" name="participant_id" value="<?php echo $participant['id']; ?>">
                        <input type="hidden" name="new_status" value="<?php echo $participant['is_active'] ? '0' : '1'; ?>">
                        <button name="toggle_participant" type="submit" class="text-slate-600 hover:text-slate-900 font-medium">
                          <?php echo $participant['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                  <div class="text-slate-400">
                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h3 class="text-sm font-medium text-slate-900 mb-2">No participants found</h3>
                    <p class="text-sm text-slate-500 mb-4">Get started by adding your first participant.</p>
                    <button onclick="showModal('addParticipantModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition-colors">
                      Add Participant
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
</main>
<?php
$modalId = 'addParticipantModal';
$title = 'Add New Participant';
$bodyHtml = '<form id="addParticipantForm" method="POST" class="space-y-6">'
  .'<div class="grid grid-cols-2 gap-4">'
    .'<div>'
      .'<label class="block text-sm font-medium text-slate-700 mb-2">Division</label>'
      .'<select name="division" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">'
        .'<option value="Mr">Mr</option>'
        .'<option value="Ms">Ms</option>'
      .'</select>'
    .'</div>'
    .'<div>'
      .'<label class="block text-sm font-medium text-slate-700 mb-2">Number Label</label>'
      .'<input name="number_label" type="text" placeholder="e.g., 1, 2, 3..." class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required />'
    .'</div>'
  .'</div>'
  .'<div>'
    .'<label class="block text-sm font-medium text-slate-700 mb-2">Full Name</label>'
    .'<input name="full_name" type="text" placeholder="Enter participant full name" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required />'
  .'</div>'
  .'<div>'
    .'<label class="block text-sm font-medium text-slate-700 mb-2">Advocacy</label>'
    .'<textarea name="advocacy" placeholder="Enter participant advocacy or cause (optional)" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none" rows="4"></textarea>'
  .'</div>'
  .'<div class="flex gap-3 pt-4">'
    .'<button type="button" onclick="hideModal(\'addParticipantModal\')" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-800 font-medium px-6 py-3 rounded-lg transition-colors">Cancel</button>'
    .'<button name="add_participant" type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2">'
      .'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
        .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>'
      .'</svg>'
      .'Add Participant'
    .'</button>'
  .'</div>'
  .'</form>'
  .'<script>makeFormLoadingEnabled("addParticipantForm", "Adding participant...", true);</script>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';

// Edit Participant Modal
$modalId = 'editParticipantModal';
$title = 'Edit Participant';
$bodyHtml = '<form id="editParticipantForm" method="POST" class="space-y-6">'
  .'<input type="hidden" name="edit_participant" value="1">'
  .'<input type="hidden" name="participant_id" id="edit_participant_id">'
  .'<div class="grid grid-cols-2 gap-4">'
    .'<div>'
      .'<label class="block text-sm font-medium text-slate-700 mb-2">Division</label>'
      .'<select name="division" id="edit_division" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">'
        .'<option value="Mr">Mr</option>'
        .'<option value="Ms">Ms</option>'
      .'</select>'
    .'</div>'
    .'<div>'
      .'<label class="block text-sm font-medium text-slate-700 mb-2">Number Label</label>'
      .'<input name="number_label" id="edit_number_label" type="text" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required />'
    .'</div>'
  .'</div>'
  .'<div>'
    .'<label class="block text-sm font-medium text-slate-700 mb-2">Full Name</label>'
    .'<input name="full_name" id="edit_full_name" type="text" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required />'
  .'</div>'
  .'<div>'
    .'<label class="block text-sm font-medium text-slate-700 mb-2">Advocacy</label>'
    .'<textarea name="advocacy" id="edit_advocacy" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none" rows="4"></textarea>'
  .'</div>'
  .'<div class="flex gap-3 pt-4">'
    .'<button type="button" onclick="hideModal(\'editParticipantModal\')" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-800 font-medium px-6 py-3 rounded-lg transition-colors">Cancel</button>'
    .'<button name="edit_participant" type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors">Update Participant</button>'
  .'</div>'
  .'</form>'
  .'<script>makeFormLoadingEnabled("editParticipantForm", "Updating participant...", true);</script>';
$footerHtml = '';
include __DIR__ . '/../components/modal.php';
?>

<script>
function editParticipant(id, name, number, division, advocacy) {
    document.getElementById('edit_participant_id').value = id;
    document.getElementById('edit_full_name').value = name;
    document.getElementById('edit_number_label').value = number;
    document.getElementById('edit_division').value = division;
    document.getElementById('edit_advocacy').value = advocacy;
    showModal('editParticipantModal');
}
</script>

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
