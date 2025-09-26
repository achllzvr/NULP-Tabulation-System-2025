<?php
$pageTitle = 'Public Awards';
include __DIR__ . '/partials/head.php';
?>
<main class="mx-auto max-w-6xl w-full p-6 space-y-6">
  <h1 class="text-2xl font-semibold text-slate-800">Awards</h1>
  <div id="awardsPublicGrid" class="grid md:grid-cols-3 gap-6">Loading...</div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
function loadPublicAwards(){
  const grid = document.getElementById('awardsPublicGrid');
  API('public_awards',{pageant_id: window.PUBLIC_PAGEANT_ID||0}).then(r=>{
    if(!r.success){ grid.textContent='Error loading awards'; return; }
    if(!r.flags?.reveal_awards){ grid.textContent='Awards not yet revealed.'; return; }
    const awards = r.awards||[];
    if(!awards.length){ grid.textContent='No awards available'; return; }
    grid.innerHTML='';
    awards.forEach(a=>{
      const card = document.createElement('div');
      card.className='border border-slate-200 rounded p-4 bg-white shadow-sm text-sm flex flex-col gap-2';
      const winners = (a.winners||[]).map((w,i)=>`<li>${escapeHtml(w.full_name||'')} <span class='text-[10px] bg-slate-100 px-1 rounded ml-1'>${i+1}</span></li>`).join('');
      card.innerHTML = `<div class='font-semibold text-slate-800 text-xs tracking-wide uppercase'>${escapeHtml(a.name||'Award')}</div><div class='text-xs text-slate-500'>${escapeHtml(a.division_scope||'ALL')}</div><ul class='text-xs space-y-1 list-disc pl-5'>${winners || '<li class=\'text-slate-400\'>TBD</li>'}</ul>`;
      grid.appendChild(card);
    });
  });
}
function escapeHtml(str){
  return (str||'').replace(/[&<>"']/g,s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[s]));
}
document.addEventListener('DOMContentLoaded', loadPublicAwards);
</script>
