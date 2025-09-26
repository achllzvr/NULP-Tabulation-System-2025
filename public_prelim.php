<?php
$pageTitle = 'Public Preliminary';
$pid = isset($_GET['pageant_id']) ? (int)$_GET['pageant_id'] : 0;
include __DIR__ . '/partials/head.php';
echo "<script>window.PUBLIC_PAGEANT_ID=" . json_encode($pid) . ";</script>";
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-6">
  <h1 class="text-2xl font-semibold text-slate-800">Preliminary Standings</h1>
  <div id="publicPrelimTable" class="text-sm">Loading...</div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
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
  let html = '<table class="min-w-full text-sm"><thead><tr><th class="px-2 py-1 text-left">Rank</th><th class="px-2 py-1 text-left">Name</th><th class="px-2 py-1 text-left">Score</th></tr></thead><tbody>'; 
  rows.forEach(r=>{ html += `<tr class="border-t"><td class="px-2 py-1">${r.rank}</td><td class="px-2 py-1">${r.name}</td><td class="px-2 py-1">${r.score??''}</td></tr>`; });
  html+='</tbody></table>';
  host.innerHTML = html;
}
function API(action, payload={}){return fetch('api.php?action='+encodeURIComponent(action),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}).then(r=>r.json())}
document.addEventListener('DOMContentLoaded',()=>{
  const pid = window.PUBLIC_PAGEANT_ID||0;
  if(!pid){ window.location='public_select.php'; return; }
  API('public_pageant_meta',{pageant_id:pid}).then(meta=>{
    if(!meta.success){ document.getElementById('publicPrelimTable').textContent='Unavailable'; return; }
    const rounds = meta.rounds||[];
    let target = rounds.find(r=>r.state==='CLOSED') || rounds.find(r=>r.state==='OPEN');
    if(!target){ document.getElementById('publicPrelimTable').textContent='No rounds yet.'; return; }
    fetch('api.php?action=public_leaderboard&round_id='+target.id).then(r=>r.json()).then(lb=>{
      if(!lb.success){ document.getElementById('publicPrelimTable').textContent='Leaderboard unavailable'; return; }
      renderTable(lb.rows||[]);
    });
  });
});
</script>
