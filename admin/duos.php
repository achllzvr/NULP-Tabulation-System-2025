<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['adminID'])) {
  header('Location: ../login_admin.php');
  exit();
}

require_once('../classes/database.php');
$con = new database();
$conn = $con->opencon();
$pageant_id = $_SESSION['pageant_id'] ?? 1;

// Handle create/edit duo
if (isset($_POST['save_duo'])) {
  $duo_id = intval($_POST['duo_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $members = array_unique(array_filter(array_map('intval', $_POST['members'] ?? [])));
  // Enforce exactly two members
  if (count($members) !== 2) {
    $error_message = 'Please select exactly two members for a duo.';
  } else try {
    $conn->begin_transaction();
    if ($duo_id > 0) {
      $stmt = $conn->prepare('UPDATE duos SET name=?, notes=? WHERE id=? AND pageant_id=?');
      $stmt->bind_param('ssii', $name, $notes, $duo_id, $pageant_id);
      $stmt->execute();
      $stmt->close();
      $stmt = $conn->prepare('DELETE FROM duo_members WHERE duo_id=?');
      $stmt->bind_param('i', $duo_id);
      $stmt->execute();
      $stmt->close();
    } else {
      $stmt = $conn->prepare('INSERT INTO duos(pageant_id, name, notes) VALUES(?,?,?)');
      $stmt->bind_param('iss', $pageant_id, $name, $notes);
      $stmt->execute();
      $duo_id = $stmt->insert_id;
      $stmt->close();
    }
    if (!empty($members)) {
      $stmt = $conn->prepare('INSERT INTO duo_members(duo_id, participant_id) VALUES(?,?)');
      foreach ($members as $pid) {
        $stmt->bind_param('ii', $duo_id, $pid);
        $stmt->execute();
      }
      $stmt->close();
    }
    $conn->commit();
    $success_message = 'Duo saved successfully';
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = 'Error saving duo: ' . $e->getMessage();
  }
}

// Fetch duos and participants
$duos = [];
$stmt = $conn->prepare('SELECT * FROM duos WHERE pageant_id=? ORDER BY id DESC');
$stmt->bind_param('i', $pageant_id);
$stmt->execute();
$duos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$participants = [];
$stmt = $conn->prepare('SELECT p.id, p.full_name, p.number_label, d.name as division FROM participants p JOIN divisions d ON p.division_id=d.id WHERE p.pageant_id=? AND p.is_active=1 ORDER BY d.name, CAST(p.number_label AS UNSIGNED), p.number_label');
$stmt->bind_param('i', $pageant_id);
$stmt->execute();
$participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/sidebar_admin.php';
?>
<main class="px-6 py-8">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-white">Duos / Pairs</h1>
    <p class="text-slate-200">Create duo groups for Pre-Pageant pair scoring</p>
    <?php if (!empty($success_message)): ?>
      <div class="mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded text-sm"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
      <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
  </div>

  <div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
      <h2 class="text-lg font-semibold text-white mb-4">Create / Edit Duo</h2>
      <form method="POST" class="space-y-4" id="duoForm">
        <input type="hidden" name="duo_id" value="0">
        <div>
          <label class="block text-sm text-slate-200 mb-1">Duo Name</label>
          <input name="name" required class="w-full px-3 py-2 rounded bg-white bg-opacity-20 border border-white border-opacity-20 text-white" />
        </div>
        <div>
          <label class="block text-sm text-slate-200 mb-1">Notes (optional)</label>
          <input name="notes" class="w-full px-3 py-2 rounded bg-white bg-opacity-20 border border-white border-opacity-20 text-white" />
        </div>
        <div>
          <label class="block text-sm text-slate-200 mb-2">Members (select two)</label>
          <div class="grid md:grid-cols-2 gap-2 max-h-64 overflow-auto">
            <?php foreach ($participants as $p): ?>
              <label class="inline-flex items-center gap-2 text-slate-200">
                <input type="checkbox" name="members[]" value="<?= (int)$p['id'] ?>" class="rounded">
                <span>#<?= htmlspecialchars($p['number_label']) ?> — <?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['division']) ?>)</span>
              </label>
            <?php endforeach; ?>
          </div>
          <p class="text-xs text-slate-300 mt-1">Exactly two members are required. The form won’t submit otherwise.</p>
        </div>
        <button type="submit" name="save_duo" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Save Duo</button>
      </form>
      <script>
      // Client-side limit: allow max 2 selections and require exactly 2 on submit
      (function(){
        const form = document.getElementById('duoForm');
        const checkboxes = form.querySelectorAll('input[name="members[]"]');
        function updateLimit(e){
          const checked = Array.from(checkboxes).filter(cb => cb.checked);
          if (checked.length >= 2) {
            // disable unchecked boxes
            checkboxes.forEach(cb => { if (!cb.checked) cb.disabled = true; });
          } else {
            checkboxes.forEach(cb => { cb.disabled = false; });
          }
        }
        checkboxes.forEach(cb => cb.addEventListener('change', updateLimit));
        form.addEventListener('submit', function(ev){
          const selected = Array.from(checkboxes).filter(cb => cb.checked).length;
          if (selected !== 2) {
            ev.preventDefault();
            alert('Please select exactly two members for a duo.');
          }
        });
      })();
      </script>
    </div>

    <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
      <h2 class="text-lg font-semibold text-white mb-4">Existing Duos</h2>
      <div class="space-y-3">
        <?php if (empty($duos)): ?>
          <div class="text-slate-300">No duos yet.</div>
        <?php endif; ?>
        <?php foreach ($duos as $d): ?>
          <?php 
            $members = [];
            $stmt = $conn->prepare('SELECT p.number_label, p.full_name FROM duo_members dm JOIN participants p ON p.id=dm.participant_id WHERE dm.duo_id=? ORDER BY p.number_label');
            $stmt->bind_param('i', $d['id']);
            $stmt->execute();
            $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
          ?>
          <div class="border border-white border-opacity-20 rounded p-4">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-white font-semibold"><?= htmlspecialchars($d['name']) ?></div>
                <div class="text-slate-300 text-sm">Members: 
                  <?php if ($members): ?>
                    <?php foreach ($members as $m): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded bg-white bg-opacity-10 text-slate-100 mr-1">#<?= htmlspecialchars($m['number_label']) ?> — <?= htmlspecialchars($m['full_name']) ?></span>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span class="text-slate-400">None</span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($d['notes'])): ?>
                  <div class="text-slate-400 text-xs mt-1">Notes: <?= htmlspecialchars($d['notes']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</main>
<?php 
include __DIR__ . '/../partials/sidebar_close.php';
include __DIR__ . '/../partials/footer.php';
?>
