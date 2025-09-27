<?php
$pageTitle = 'Public Preliminary';
$pid = isset($_GET['pageant_id']) ? (int)$_GET['pageant_id'] : 0;
include __DIR__ . '/../partials/head.php';
echo "<script>window.PUBLIC_PAGEANT_ID=" . json_encode($pid) . ";</script>";
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-6">
  <h1 class="text-2xl font-semibold text-slate-800">Preliminary Standings</h1>
  <div id="publicPrelimTable" class="text-sm"><?php echo createLoadingSpinner('prelimLoader', 'Loading Preliminary Results', true); ?></div>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<script>
function loadPrelim(){
  const pid = window.PUBLIC_PAGEANT_ID||0;
  if(!pid){ window.location='public_select.php'; return; }
  API('public_pageant_meta',{pageant_id:pid}).then(meta=>{
    if(!meta.success){ document.getElementById('publicPrelimTable').textContent='Unavailable'; return; }
    const rounds = meta.rounds||[];
    // choose first CLOSED or OPEN round as prelim baseline
    let target = rounds.find(r=>r.state==='CLOSED') || rounds.find(r=>r.state==='OPEN');
    if(!target){ document.getElementById('publicPrelimTable').textContent='No rounds yet.'; return; }
    API('public_leaderboard',{},{round_id: target.id});
  });
}
// Adjust API wrapper for GET/POST flexibility for existing wrapper expecting POST JSON
// We'll just craft manual fetch for leaderboard to include round_id in query
window.API = window.API || function(action,payload={}){return fetch('api.php?action='+encodeURIComponent(action),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}).then(r=>r.json())};
function APILeaderboard(roundId){
  return fetch('api.php?action=public_leaderboard&round_id='+roundId).then(r=>r.json());
}
function renderTable(rows){
  const host = document.getElementById('publicPrelimTable');
  let html = '<div class="bg-white border border-slate-200 rounded-lg overflow-hidden"><table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left font-semibold">Rank</th><th class="px-4 py-3 text-left font-semibold">Name</th><th class="px-4 py-3 text-left font-semibold">Score</th></tr></thead><tbody class="divide-y divide-slate-200">'; 
  rows.forEach((r, index) => { 
    const rowClass = index % 2 === 0 ? 'bg-white' : 'bg-slate-50';
    html += `<tr class="${rowClass} hover:bg-blue-50 transition-colors"><td class="px-4 py-3 font-medium">#${r.rank}</td><td class="px-4 py-3">${r.name}</td><td class="px-4 py-3 font-mono">${r.score??'--'}</td></tr>`; 
  });
  html+='</tbody></table></div>';
  
  // Add fade-in animation
  host.style.opacity = '0';
  host.innerHTML = html;
  host.style.transition = 'opacity 0.5s ease-in';
  host.style.opacity = '1';
}
function API(action, payload={}){return fetch('api.php?action='+encodeURIComponent(action),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}).then(r=>r.json())}
document.addEventListener('DOMContentLoaded',()=>{
  const pid = window.PUBLIC_PAGEANT_ID||0;
  if(!pid){ window.location='public_select.php'; return; }
  
  // Initialize loading manager
  const loader = new LoadingManager('prelimLoader');
  loader.startStatusUpdates();
  
  loader.setCustomStatus('Fetching pageant information...');
  API('public_pageant_meta',{pageant_id:pid}).then(meta=>{
    if(!meta.success){ 
      loader.error('Pageant unavailable');
      return; 
    }
    
    loader.setCustomStatus('Looking for available rounds...');
    const rounds = meta.rounds||[];
    let target = rounds.find(r=>r.state==='CLOSED') || rounds.find(r=>r.state==='OPEN');
    
    if(!target){ 
      loader.error('No rounds available yet');
      return; 
    }
    
    loader.setCustomStatus('Loading leaderboard data...');
    fetch('api.php?action=public_leaderboard&round_id='+target.id).then(r=>r.json()).then(lb=>{
      if(!lb.success){ 
        loader.error('Leaderboard unavailable');
        return; 
      }
      
      loader.setCustomStatus('Rendering results...');
      setTimeout(() => {
        renderTable(lb.rows||[]);
        loader.finish('Results loaded successfully!');
      }, 500);
    }).catch(err => {
      loader.error('Failed to load leaderboard');
    });
  }).catch(err => {
    loader.error('Connection failed');
  });
});
</script>
