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

// Ensure participants table has photo_path column (one-time migration)
function ensurePhotoColumn($con) {
  $conn = $con->opencon();
  $exists = false;
  if ($res = $conn->query("SHOW COLUMNS FROM participants LIKE 'photo_path'")) {
    $exists = $res->num_rows > 0;
    $res->close();
  }
  if (!$exists) {
    // Best-effort; ignore errors if lacking permission
    @$conn->query("ALTER TABLE participants ADD COLUMN photo_path VARCHAR(255) NULL AFTER advocacy");
  }
  $conn->close();
}

// Save uploaded photo and return relative path (assets/media/participants/{pageant_id}/filename.ext)
function saveUploadedPhoto($fileInfo, $pageant_id) {
  if (!isset($fileInfo) || !is_array($fileInfo) || ($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    return null;
  }
  $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
  $mime = mime_content_type($fileInfo['tmp_name']);
  if (!isset($allowed[$mime])) {
    return ['error' => 'Unsupported image type. Allowed: JPG, PNG, WEBP.'];
  }
  $maxBytes = 5 * 1024 * 1024; // 5MB
  if (($fileInfo['size'] ?? 0) > $maxBytes) {
    return ['error' => 'Image too large. Max size is 5MB.'];
  }
  $ext = $allowed[$mime];
  $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$pageant_id);
  $subdir = "assets/media/participants/{$safeBase}";
  $root = dirname(__DIR__);
  $targetDir = $root . '/' . $subdir;
  if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0775, true);
  }
  $fname = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ".{$ext}";
  $targetPath = $targetDir . '/' . $fname;
  if (!move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
    return ['error' => 'Failed to move uploaded file.'];
  }
  // Tighten permissions
  @chmod($targetPath, 0644);
  return $subdir . '/' . $fname;
}

function deletePhotoFile($photo_path) {
  if (!$photo_path) return;
  $root = dirname(__DIR__);
  $full = $root . '/' . ltrim($photo_path, '/');
  if (is_file($full)) {
    @unlink($full);
  }
}

// Run schema check
ensurePhotoColumn($con);

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
            // Convert division name to division_id
            $division_id = ($division === 'Ambassador') ? 1 : (($division === 'Ambassadress') ? 2 : 1);
            
      // Handle optional photo upload
      $upload = saveUploadedPhoto($_FILES['photo'] ?? null, $pageant_id);
      if (is_array($upload) && isset($upload['error'])) {
        $error_message = $upload['error'];
        $show_error_alert = true;
      } else {
        $photo_path = is_string($upload) ? $upload : null;
        // Add participant to database
        $stmt = $conn->prepare("INSERT INTO participants (pageant_id, division_id, number_label, full_name, advocacy, photo_path, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("iissss", $pageant_id, $division_id, $number_label, $full_name, $advocacy, $photo_path);
            
        if ($stmt->execute()) {
          $success_message = "Participant '$full_name' (#$number_label) added successfully.";
          $show_success_alert = true;
        } else {
          // If DB insert fails, clean up uploaded file if any
          if ($photo_path) { deletePhotoFile($photo_path); }
          $error_message = "Error adding participant: " . $conn->error;
          $show_error_alert = true;
        }
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
  // Fetch photo to delete from disk
  $old = null;
  $stmt = $conn->prepare("SELECT photo_path FROM participants WHERE id = ? AND pageant_id = ?");
  $stmt->bind_param("ii", $participant_id, $pageant_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $row = $res->fetch_assoc()) { $old = $row['photo_path']; }
  $stmt->close();
    $stmt = $conn->prepare("DELETE FROM participants WHERE id = ? AND pageant_id = ?");
    $stmt->bind_param("ii", $participant_id, $pageant_id);
    
    if ($stmt->execute()) {
    if ($old) { deletePhotoFile($old); }
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
            // Convert division name to division_id
            $division_id = ($division === 'Ambassador') ? 1 : (($division === 'Ambassadress') ? 2 : 1);
            
      // Check for new photo upload
      $newPhotoPath = null;
      $upload = saveUploadedPhoto($_FILES['photo_edit'] ?? null, $pageant_id);
      if (is_array($upload) && isset($upload['error'])) {
        $error_message = $upload['error'];
        $show_error_alert = true;
      } else {
        if (is_string($upload)) {
          // Delete old photo file
          $stmtOld = $conn->prepare("SELECT photo_path FROM participants WHERE id = ? AND pageant_id = ?");
          $stmtOld->bind_param("ii", $participant_id, $pageant_id);
          $stmtOld->execute();
          $rOld = $stmtOld->get_result();
          if ($rOld && $rowOld = $rOld->fetch_assoc()) {
            if (!empty($rowOld['photo_path'])) { deletePhotoFile($rowOld['photo_path']); }
          }
          $stmtOld->close();
          $newPhotoPath = $upload;
        }

        if ($newPhotoPath) {
          $stmt = $conn->prepare("UPDATE participants SET division_id = ?, number_label = ?, full_name = ?, advocacy = ?, photo_path = ? WHERE id = ? AND pageant_id = ?");
          $stmt->bind_param("issssii", $division_id, $number_label, $full_name, $advocacy, $newPhotoPath, $participant_id, $pageant_id);
        } else {
          $stmt = $conn->prepare("UPDATE participants SET division_id = ?, number_label = ?, full_name = ?, advocacy = ? WHERE id = ? AND pageant_id = ?");
          $stmt->bind_param("isssii", $division_id, $number_label, $full_name, $advocacy, $participant_id, $pageant_id);
        }
                
        if ($stmt->execute()) {
          $success_message = "Participant '$full_name' (#$number_label) updated successfully.";
          $show_success_alert = true;
        } else {
          // If DB update fails, clean up newly uploaded file
          if ($newPhotoPath) { deletePhotoFile($newPhotoPath); }
          $error_message = "Error updating participant: " . $conn->error;
          $show_error_alert = true;
        }
      }
        }
        $stmt->close();
        $conn->close();
    }
}

// Handle removing a participant photo
if (isset($_POST['remove_photo'])) {
  $participant_id = intval($_POST['participant_id'] ?? 0);
  $pageant_id = $_SESSION['pageant_id'] ?? 1;
  if ($participant_id > 0) {
    $conn = $con->opencon();
    $old = null;
    $stmt = $conn->prepare("SELECT photo_path FROM participants WHERE id = ? AND pageant_id = ?");
    $stmt->bind_param("ii", $participant_id, $pageant_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) { $old = $row['photo_path']; }
    $stmt->close();
    $stmt = $conn->prepare("UPDATE participants SET photo_path = NULL WHERE id = ? AND pageant_id = ?");
    $stmt->bind_param("ii", $participant_id, $pageant_id);
    if ($stmt->execute()) {
      if ($old) { deletePhotoFile($old); }
      $success_message = "Photo removed successfully.";
      $show_success_alert = true;
    } else {
      $error_message = "Failed to remove photo.";
      $show_error_alert = true;
    }
    $stmt->close();
    $conn->close();
  }
}

// Fetch participants with filters (division, status, search)
$conn = $con->opencon();
$pageant_id = $_SESSION['pageant_id'] ?? 1; // Use consistent session variable

$divisionFilter = isset($_GET['division']) ? trim($_GET['division']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

$conditions = ["p.pageant_id = ?"];
$types = 'i';
$params = [$pageant_id];

if ($divisionFilter !== '' && in_array($divisionFilter, ['Ambassador','Ambassadress'])) {
  $conditions[] = 'd.name = ?';
  $types .= 's';
  $params[] = $divisionFilter;
}
if ($statusFilter !== '' && in_array($statusFilter, ['active','inactive'])) {
  $conditions[] = 'p.is_active = ?';
  $types .= 'i';
  $params[] = $statusFilter === 'active' ? 1 : 0;
}
if ($searchQuery !== '') {
  // search in name or number label
  $conditions[] = '(p.full_name LIKE ? OR p.number_label LIKE ?)';
  $types .= 'ss';
  $like = '%' . $searchQuery . '%';
  $params[] = $like; $params[] = $like;
}

$whereSql = implode(' AND ', $conditions);
$sql = "SELECT p.*, d.name as division FROM participants p JOIN divisions d ON p.division_id = d.id WHERE $whereSql ORDER BY p.number_label";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$participants = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Fetch divisions for form dropdowns
$conn = $con->opencon();
$stmt = $conn->prepare("SELECT * FROM divisions WHERE pageant_id = ? ORDER BY sort_order");
$stmt->bind_param("i", $pageant_id);
$stmt->execute();
$result = $stmt->get_result();
$divisions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Participants';
$rows = $participants; // Use fetched participants data for table
include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
      <div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Participants</h1>
          <p class="text-slate-200">Manage pageant participants and their information</p>
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
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Total Participants</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-white mb-1"><?php echo count($participants); ?></p>
        <p class="text-sm text-slate-200">Registered participants</p>
      </div>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Ambassador Division</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-white mb-1">
          <?php echo count(array_filter($participants, fn($p) => isset($p['division']) && $p['division'] === 'Ambassador')); ?>
        </p>
        <p class="text-sm text-slate-200">Male participants</p>
      </div>

      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-slate-200">Ambassadress Division</h3>
          <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
        </div>
        <p class="text-3xl font-bold text-white mb-1">
          <?php echo count(array_filter($participants, fn($p) => isset($p['division']) && $p['division'] === 'Ambassadress')); ?>
        </p>
        <p class="text-sm text-slate-200">Female participants</p>
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

    <!-- Filters Bar -->
    <div class="bg-white bg-opacity-10 backdrop-blur-md border border-white border-opacity-20 rounded-xl p-4 mb-4">
      <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block text-xs text-slate-300 mb-1">Division</label>
          <select name="division" class="w-full rounded-lg bg-white bg-opacity-10 border border-white border-opacity-20 text-white px-3 py-2">
            <option value="" <?= $divisionFilter===''? 'selected':'' ?>>All</option>
            <option value="Ambassador" <?= $divisionFilter==='Ambassador'? 'selected':'' ?>>Ambassador</option>
            <option value="Ambassadress" <?= $divisionFilter==='Ambassadress'? 'selected':'' ?>>Ambassadress</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-300 mb-1">Status</label>
          <select name="status" class="w-full rounded-lg bg-white bg-opacity-10 border border-white border-opacity-20 text-white px-3 py-2">
            <option value="" <?= $statusFilter===''? 'selected':'' ?>>All</option>
            <option value="active" <?= $statusFilter==='active'? 'selected':'' ?>>Active</option>
            <option value="inactive" <?= $statusFilter==='inactive'? 'selected':'' ?>>Inactive</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs text-slate-300 mb-1">Search</label>
          <div class="flex gap-2">
            <input type="text" name="q" placeholder="Search name or number..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" class="flex-1 rounded-lg bg-white bg-opacity-10 border border-white border-opacity-20 text-white px-3 py-2" />
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white">Filter</button>
            <a href="participants.php" class="px-4 py-2 rounded-lg bg-slate-600 text-white">Reset</a>
          </div>
        </div>
      </form>
    </div>

    <!-- Participants Table -->
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
      <div class="px-6 py-4 border-b border-white border-opacity-20">
        <h3 class="text-lg font-semibold text-white">Participant List</h3>
        <p class="text-sm text-slate-200 mt-1">All registered participants for this pageant</p>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-white bg-opacity-10 backdrop-blur-sm">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Photo</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Number</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Division</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Name</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Advocacy</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-200 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white divide-opacity-10">
            <?php if (!empty($participants)): ?>
              <?php foreach ($participants as $participant): ?>
                <tr class="hover:bg-white hover:bg-opacity-5 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php 
                      $src = !empty($participant['photo_path']) ? ('../' . $participant['photo_path']) : '';
                    ?>
                    <div class="w-12 h-12 rounded-md bg-white bg-opacity-10 border border-white border-opacity-20 overflow-hidden flex items-center justify-center">
                      <?php if ($src): ?>
                        <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>" alt="Photo" class="w-full h-full object-cover"/>
                      <?php else: ?>
                        <span class="text-[10px] text-slate-300">No Photo</span>
                      <?php endif; ?>
                    </div>
                  </td>
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
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $division === 'Ambassador' ? 'bg-blue-500 bg-opacity-30 text-blue-200 backdrop-blur-sm' : ($division === 'Ambassadress' ? 'bg-pink-500 bg-opacity-30 text-pink-200 backdrop-blur-sm' : 'bg-white bg-opacity-20 text-slate-200 backdrop-blur-sm'); ?>">
                      <?php echo htmlspecialchars($division); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($participant['full_name']); ?></div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-slate-200 max-w-xs truncate" title="<?php echo htmlspecialchars($participant['advocacy']); ?>">
                      <?php echo htmlspecialchars($participant['advocacy'] ?: 'No advocacy'); ?>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $participant['is_active'] ? 'bg-green-500 bg-opacity-30 text-green-200 backdrop-blur-sm' : 'bg-red-500 bg-opacity-30 text-red-200 backdrop-blur-sm'; ?>">
                      <?php echo $participant['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex flex-wrap gap-2 items-center">
                      <button onclick="editParticipant(<?php echo $participant['id']; ?>, '<?php echo htmlspecialchars($participant['full_name'], ENT_QUOTES); ?>', '<?php echo $participant['number_label']; ?>', '<?php echo isset($participant['division']) ? $participant['division'] : 'General'; ?>', '<?php echo htmlspecialchars($participant['advocacy'], ENT_QUOTES); ?>')" class="text-blue-300 hover:text-blue-200 font-medium">Edit</button>
                      
                      <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this participant?')">
                        <input type="hidden" name="participant_id" value="<?php echo $participant['id']; ?>">
                        <button name="delete_participant" type="submit" class="text-red-300 hover:text-red-200 font-medium">Delete</button>
                      </form>
                      
                      <form method="POST" class="inline">
                        <input type="hidden" name="participant_id" value="<?php echo $participant['id']; ?>">
                        <input type="hidden" name="new_status" value="<?php echo $participant['is_active'] ? '0' : '1'; ?>">
                        <button name="toggle_participant" type="submit" class="text-slate-300 hover:text-white font-medium">
                          <?php echo $participant['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                      </form>
                      <?php if (!empty($participant['photo_path'])): ?>
                      <form method="POST" class="inline" onsubmit="return confirm('Remove this photo?')">
                        <input type="hidden" name="participant_id" value="<?php echo $participant['id']; ?>">
                        <button name="remove_photo" type="submit" class="text-yellow-300 hover:text-yellow-200 font-medium">Remove Photo</button>
                      </form>
                      <?php endif; ?>
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
<?php
$modalId = 'addParticipantModal';
$title = 'Add New Participant';
$bodyHtml = '<form id="addParticipantForm" method="POST" enctype="multipart/form-data" class="space-y-6">'
  .'<div class="grid grid-cols-2 gap-4">'
    .'<div>'
      .'<label class="block text-sm font-medium text-slate-700 mb-2">Division</label>'
      .'<select name="division" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">';
foreach ($divisions as $division) {
    $bodyHtml .= '<option value="' . htmlspecialchars($division['name']) . '">' . htmlspecialchars($division['name']) . '</option>';
}
$bodyHtml .= '</select>'
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
  .'<div>'
    .'<label class="block text-sm font-medium text-slate-700 mb-2">Photo (optional)</label>'
    .'<input name="photo" type="file" accept="image/png,image/jpeg,image/webp" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm bg-white" />'
    .'<p class="text-xs text-slate-500 mt-1">Max size 5MB. JPG, PNG, or WEBP.</p>'
  .'</div>'
  .'<div class="flex gap-3 pt-4">'
  .'<button type="button" onclick="hideModal(\'addParticipantModal\')" class="flex-1 bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-6 py-3 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Cancel</button>'
  .'<button name="add_participant" type="submit" class="flex-1 bg-blue-500 bg-opacity-30 hover:bg-blue-500/40 text-white font-medium px-6 py-3 rounded-lg border border-blue-400 border-opacity-50 backdrop-blur-sm transition-colors flex items-center justify-center gap-2">'
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
$bodyHtml = '<form id="editParticipantForm" method="POST" enctype="multipart/form-data" class="space-y-6">'
  .'<input type="hidden" name="edit_participant" value="1">'
  .'<input type="hidden" name="participant_id" id="edit_participant_id">'
  .'<div class="grid grid-cols-2 gap-4">'
    .'<div>'
      .'<label class="block text-sm font-medium text-slate-700 mb-2">Division</label>'
      .'<select name="division" id="edit_division" class="w-full border border-slate-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">';
foreach ($divisions as $division) {
    $bodyHtml .= '<option value="' . htmlspecialchars($division['name']) . '">' . htmlspecialchars($division['name']) . '</option>';
}
$bodyHtml .= '</select>'
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
  .'<div>'
    .'<label class="block text-sm font-medium text-slate-700 mb-2">Replace Photo</label>'
    .'<input name="photo_edit" type="file" accept="image/png,image/jpeg,image/webp" class="w-full border border-slate-300 rounded-lg px-4 py-2 text-sm bg-white" />'
    .'<p class="text-xs text-slate-500 mt-1">Uploading a new image will replace the existing photo.</p>'
  .'</div>'
  .'<div class="flex gap-3 pt-4">'
  .'<button type="button" onclick="hideModal(\'editParticipantModal\')" class="flex-1 bg-white bg-opacity-10 hover:bg-white hover:bg-opacity-20 text-white font-medium px-6 py-3 rounded-lg border border-white border-opacity-20 backdrop-blur-sm transition-colors">Cancel</button>'
  .'<button name="edit_participant" type="submit" class="flex-1 bg-blue-500 bg-opacity-30 hover:bg-blue-500/40 text-white font-medium px-6 py-3 rounded-lg border border-blue-400 border-opacity-50 backdrop-blur-sm transition-colors">Update Participant</button>'
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

<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php'; ?>
