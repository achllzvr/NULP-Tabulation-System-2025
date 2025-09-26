<?php
$pageTitle = 'Public Awards';
$pid = isset($_GET['pageant_id']) ? (int)$_GET['pageant_id'] : 0;
include __DIR__ . '/partials/head.php';
echo "<script>window.PUBLIC_PAGEANT_ID=" . json_encode($pid) . ";</script>";
?>
<main class="mx-auto max-w-6xl w-full p-6 space-y-6">
  <h1 class="text-2xl font-semibold text-slate-800">Awards</h1>
  <div id="awardsPublicGrid" class="grid md:grid-cols-3 gap-6"><?php echo createLoadingSpinner('awardsLoader', 'Loading Awards', true); ?></div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
function loadPublicAwards(){
  const grid = document.getElementById('awardsPublicGrid');
  
  // Initialize loading manager
  const loader = new LoadingManager('awardsLoader');
  loader.startStatusUpdates();
  
  loader.setCustomStatus('Connecting to awards system...');
  API('public_awards',{pageant_id: window.PUBLIC_PAGEANT_ID||0}).then(r=>{
    if(!r.success){ 
      loader.error('Failed to load awards'); 
      return; 
    }
    
    loader.setCustomStatus('Checking award visibility...');
    if(!r.flags?.reveal_awards){ 
      setTimeout(() => {
        grid.innerHTML = '<div class="col-span-full text-center text-slate-500 py-12"><div class="text-4xl mb-4">🏆</div><p class="text-lg font-medium">Awards not yet revealed</p><p class="text-sm">Please check back later</p></div>';
        loader.finish('Status updated');
      }, 1000);
      return; 
    }
    
    const awards = r.awards||[];
    if(!awards.length){ 
      setTimeout(() => {
        grid.innerHTML = '<div class="col-span-full text-center text-slate-500 py-12"><div class="text-4xl mb-4">🏅</div><p class="text-lg font-medium">No awards available</p></div>';
        loader.finish('Status updated');
      }, 1000);
      return; 
    }
    
    loader.setCustomStatus('Rendering award cards...');
    setTimeout(() => {
      grid.innerHTML='';
      awards.forEach((a, index) => {
        setTimeout(() => {
          const card = document.createElement('div');
          card.className='border border-slate-200 rounded-lg p-6 bg-white shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 text-sm flex flex-col gap-3 opacity-0';
          const winners = (a.winners||[]).map((w,i)=>`<li class="flex items-center justify-between"><span>${escapeHtml(w.full_name||'')}</span><span class='text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full font-medium'>${i+1}${i===0?'st':i===1?'nd':i===2?'rd':'th'}</span></li>`).join('');
          card.innerHTML = `
            <div class='font-bold text-slate-800 text-lg text-center border-b pb-2'>${escapeHtml(a.name||'Award')}</div>
            <div class='text-sm text-slate-500 text-center font-medium'>${escapeHtml(a.division_scope||'ALL')}</div>
            <ul class='text-sm space-y-2'>${winners || '<li class=\'text-slate-400 text-center italic\'>To be announced</li>'}</ul>
          `;
          grid.appendChild(card);
          
          // Animate in
          setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease-in';
            card.style.opacity = '1';
          }, 50);
        }, index * 150); // Stagger animation
      });
      
      setTimeout(() => {
        loader.finish(`Loaded ${awards.length} awards!`);
      }, awards.length * 150 + 500);
    }, 800);
  }).catch(err => {
    loader.error('Connection failed');
  });
}
function escapeHtml(str){
  return (str||'').replace(/[&<>"']/g,s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[s]));
}
document.addEventListener('DOMContentLoaded', loadPublicAwards);
// redirect if no pageant id
if(!window.PUBLIC_PAGEANT_ID){ window.location='public_select.php'; }
</script>
