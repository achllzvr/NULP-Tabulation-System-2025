<?php
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/AwardsService.php';
AuthService::start();
$pageTitle = 'Awards Management';
$awards = [];
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/nav_admin.php';
?>
<main class="mx-auto max-w-6xl w-full p-6 space-y-6">
  <h1 class="text-xl font-semibold text-slate-800">Awards</h1>
  <div class="flex flex-wrap gap-3">
    <button onclick="previewAwards()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-2 rounded">Preview Computation</button>
    <button onclick="persistAwards()" class="bg-green-600 hover:bg-green-700 text-white text-xs font-medium px-3 py-2 rounded">Compute & Persist</button>
  </div>
  <div class="grid md:grid-cols-3 gap-6" id="awardsGrid">
    <!-- dynamic award cards -->
  </div>
  <div id="awardPreview" class="hidden">
    <h2 class="text-sm font-semibold text-slate-700 mt-8 mb-2">Computation Preview</h2>
    <div id="awardPreviewList" class="space-y-4"></div>
  </div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
let awards = [];
function loadAwards(){
  API('list_awards',{}).then(r=>{
    if(!r.success) return showToast(r.error||'Error','error');
    awards = r.awards||[];
    renderAwards();
  });
}
function renderAwards(){
  const grid = document.getElementById('awardsGrid');
  grid.innerHTML = '';
  if(!awards.length){ grid.innerHTML = '<div class="col-span-3 text-slate-400 text-sm">No awards configured</div>'; return; }
  awards.forEach(a=>{
    const card = document.createElement('div');
    card.className='border border-slate-200 rounded p-4 bg-white shadow-sm text-sm flex flex-col gap-2';
    card.innerHTML = `<div class='font-semibold text-slate-800 text-xs tracking-wide uppercase'>${escapeHtml(a.name||'Award')}</div>
    <div class='text-xs text-slate-500'>Type: ${a.award_type||''}</div>
    <div class='text-xs text-slate-500'>Scope: ${a.division_scope||'ALL'}</div>`;
    grid.appendChild(card);
  });
}
function previewAwards(){
  API('compute_awards',{}).then(r=>{
    if(!r.success) return showToast(r.error||'Preview error','error');
    const list = document.getElementById('awardPreviewList');
    const wrap = document.getElementById('awardPreview');
    list.innerHTML='';
    (r.preview||[]).forEach(entry=>{
      const aw = awards.find(a=>a.id==entry.award_id)||{};
      const e = document.createElement('div');
      e.className='border border-slate-200 rounded p-3 bg-white';
      if(entry.error){
        e.innerHTML = `<div class='text-sm font-medium text-red-600'>${escapeHtml(aw.name||'Award')} - Error: ${escapeHtml(entry.error)}</div>`;
      } else {
        const winners = (entry.winners||[]).map((w,i)=>`<li>${escapeHtml(w.full_name||'')} <span class='text-xs text-slate-500'>(${w.division||''})</span> <span class='text-[10px] bg-slate-100 px-1 rounded ml-1'>${w.metric??''}</span></li>`).join('');
        e.innerHTML = `<div class='text-sm font-semibold text-slate-700 mb-1'>${escapeHtml(aw.name||'Award')}</div><ul class='list-disc pl-5 text-xs space-y-1'>${winners||'<li class=\'text-slate-400\'>No winners</li>'}</ul>`;
      }
      list.appendChild(e);
    });
    wrap.classList.remove('hidden');
    showToast('Preview ready','success');
  });
}
function persistAwards(){
  API('compute_awards_persist',{csrf_token: window.csrfToken}).then(r=>{
    if(!r.success) return showToast(r.error||'Persist error','error');
    showToast('Awards persisted','success');
    previewAwards();
  });
}
function escapeHtml(str){
  return (str||'').replace(/[&<>"']/g,s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[s]));
}
document.addEventListener('DOMContentLoaded',()=>{ loadAwards(); });
</script>
