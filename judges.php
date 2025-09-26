<?php
require_once __DIR__ . '/classes/AuthService.php';
AuthService::start();
$pageTitle = 'Judges';
$judges = [];
$columns = [
  ['header'=>'Name','field'=>'full_name'],
  ['header'=>'Email','field'=>'email'],
  ['header'=>'User ID','field'=>'id'],
];
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-7xl w-full p-6 space-y-6">
  <div class="flex justify-between items-center">
    <h1 class="text-xl font-semibold text-slate-800">Judges</h1>
    <button onclick="showModal('addJudgeModal')" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Add Judge</button>
  </div>
  <?php include __DIR__ . '/components/table.php'; ?>
</main>
<?php
$modalId = 'addJudgeModal';
$title = 'Add Judge';
$bodyHtml = '<form id="judgeAddForm" onsubmit="return addJudge(event)" class="space-y-4">'
  +'<div><label class="block text-xs font-medium mb-1">Full Name</label><input name="full_name" class="w-full border rounded px-2 py-1" required /></div>'
  +'<div><label class="block text-xs font-medium mb-1">Email</label><input type="email" name="email" class="w-full border rounded px-2 py-1" required /></div>'
  +'<div class="text-right"><button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Create</button></div>'
  +'</form>';
$footerHtml = '';
include __DIR__ . '/components/modal.php';
include __DIR__ . '/partials/footer.php';
?>
<script>
function addJudge(e){
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = [{ full_name: fd.get('full_name'), email: fd.get('email') }];
  API('add_judges',{judges: payload}).then(res=>{
    if(res.success){ showToast('Judge added','success'); location.reload(); } else { showToast(res.error||'Error','error'); }
  });
  return false;
}
</script>
