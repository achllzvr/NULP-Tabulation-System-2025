<?php
require_once __DIR__ . '/classes/AuthService.php';
AuthService::start();
$pageTitle = 'Settings';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-4xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Visibility & Settings</h1>
  <form id="visibilityForm" class="space-y-4 max-w-md" onsubmit="return saveVisibility(event)">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_participant_names" class="rounded" /> <span>Reveal Participant Names</span></label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_scores" class="rounded" /> <span>Reveal Scores</span></label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_awards" class="rounded" /> <span>Reveal Awards</span></label>
    <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Apply</button>
  </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
function saveVisibility(e){
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = {};
  for(const [k,v] of fd.entries()){ payload[k] = true; }
  payload.csrf_token = window.csrfToken;
  API('set_visibility_flags', payload).then(res=>{
    if(res.success){
      if(res.csrf) window.csrfToken = res.csrf;
      showToast('Updated','success');
      applyFlags(res.flags||{});
    } else showToast(res.error||'Error','error');
  });
  return false;
}
function loadVisibility(){
  API('get_visibility_flags',{}).then(res=>{
    if(res.success){
      if(res.csrf) window.csrfToken = res.csrf;
      applyFlags(res.flags||{});
    }
  });
}
function applyFlags(flags){
  const f = document.getElementById('visibilityForm');
  if(!f) return;
  f.show_participant_names.checked = !!flags.reveal_names;
  f.show_scores.checked = !!flags.reveal_scores;
  f.show_awards.checked = !!flags.reveal_awards;
}
document.addEventListener('DOMContentLoaded', loadVisibility);
</script>
