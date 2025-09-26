<?php
$pageTitle = 'Public Final Results';
$pid = isset($_GET['pageant_id']) ? (int)$_GET['pageant_id'] : 0;
include __DIR__ . '/partials/head.php';
echo "<script>window.PUBLIC_PAGEANT_ID=" . json_encode($pid) . ";</script>";
?>
<main class="mx-auto max-w-5xl w-full p-6 space-y-8">
  <h1 class="text-2xl font-semibold text-slate-800">Final Results</h1>
  <section id="podium" class="grid md:grid-cols-3 gap-4">Podium placeholder</section>
  <section id="finalRankings" class="text-sm">Rankings placeholder</section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const pid = window.PUBLIC_PAGEANT_ID||0;
  if(!pid){ window.location='public_select.php'; return; }
  fetch('api.php?action=public_pageant_meta', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({pageant_id:pid})})
    .then(r=>r.json()).then(meta=>{
      if(!meta.success){ document.getElementById('finalRankings').textContent='Unavailable'; return; }
      const rounds = meta.rounds||[];
      // choose last CLOSED round as final (fallback to last any)
      let closed = rounds.filter(r=>r.state==='CLOSED');
      let target = closed.length? closed[closed.length-1] : (rounds.length? rounds[rounds.length-1] : null);
      if(!target){ document.getElementById('finalRankings').textContent='No results yet.'; return; }
      fetch('api.php?action=public_leaderboard&round_id='+target.id).then(r=>r.json()).then(lb=>{
        if(!lb.success){ document.getElementById('finalRankings').textContent='Leaderboard unavailable'; return; }
        const rows = lb.rows||[];
        renderFinal(rows);
      });
    });
});
function renderFinal(rows){
  const podium = document.getElementById('podium');
  const ranking = document.getElementById('finalRankings');
  const top3 = rows.slice(0,3);
  podium.innerHTML = top3.map((r,i)=>`<div class='bg-white border border-slate-200 rounded p-4 text-center'><div class='text-xl font-bold'>${i+1}</div><div class='mt-2 font-medium'>${r.name}</div><div class='text-slate-500 text-xs mt-1'>${r.score??''}</div></div>`).join('');
  ranking.innerHTML = '<ol class="space-y-1">'+rows.map(r=>`<li>${r.rank}. ${r.name} ${r.score!==null? '('+r.score+')':''}</li>`).join('')+'</ol>';
}
</script>
