<?php
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/PageantService.php';
AuthService::start();
// AuthService::requireRole(['admin']);
$pageTitle = 'Participants';
$participants = []; // placeholder fetch later
$columns = [
  ['header'=>'Number','field'=>'number_label'],
  ['header'=>'Division','field'=>'division'],
  ['header'=>'Name','field'=>'full_name'],
  ['header'=>'Advocacy','field'=>'advocacy'],
  ['header'=>'Active','field'=>'is_active'],
];
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-7xl w-full p-6 space-y-6">
  <div class="flex justify-between items-center">
    <h1 class="text-xl font-semibold text-slate-800">Participants</h1>
    <button onclick="showModal('addParticipantModal')" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Add Participant</button>
  </div>
  <?php include __DIR__ . '/components/table.php'; ?>
</main>
<?php
$modalId = 'addParticipantModal';
$title = 'Add Participant';
$bodyHtml = '<form id="participantAddForm" onsubmit="return addParticipant(event)" class="space-y-4">'
  .'<div><label class="block text-xs font-medium mb-1">Division</label><select name="division" class="w-full border rounded px-2 py-1"><option>Mr</option><option>Ms</option></select></div>'
  .'<div><label class="block text-xs font-medium mb-1">Number Label</label><input name="number_label" class="w-full border rounded px-2 py-1" required /></div>'
  .'<div><label class="block text-xs font-medium mb-1">Full Name</label><input name="full_name" class="w-full border rounded px-2 py-1" required /></div>'
  .'<div><label class="block text-xs font-medium mb-1">Advocacy</label><textarea name="advocacy" class="w-full border rounded px-2 py-1" rows="3"></textarea></div>'
  .'<div class="text-right"><button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Save</button></div>'
  .'</form>';
$footerHtml = '';
include __DIR__ . '/components/modal.php';
include __DIR__ . '/partials/footer.php';
?>
<script>
function addParticipant(e){
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = [{
    division: fd.get('division'),
    number_label: fd.get('number_label'),
    full_name: fd.get('full_name'),
    advocacy: fd.get('advocacy')
  }];
  API('add_participants',{participants: payload}).then(res=>{
    if(res.success){ showToast('Participant added','success'); location.reload(); } else { showToast(res.error||'Error','error'); }
  });
  return false;
}
</script>
