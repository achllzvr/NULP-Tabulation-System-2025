// scoring.js - judge scoring helpers
function submitScores(e){
  e.preventDefault();
  const form = e.target;
  const fd = new FormData(form);
  const participant_id = parseInt(fd.get('participant_id'),10);
  const payload = { participant_id, scores: [] };
  for (const [k,v] of fd.entries()) {
    if(k.startsWith('criterion_')) {
      const cid = parseInt(k.replace('criterion_',''),10);
      const val = parseFloat(v);
      if(!isNaN(cid) && !isNaN(val)) payload.scores.push({criterion_id: cid, value: val});
    }
  }
  API('submit_score', payload).then(res => {
    if(res.success){
      showToast('Scores saved','success');
    } else {
      showToast(res.error||'Error saving scores','error');
    }
  });
  return false;
}

function showModal(id){ const el=document.getElementById(id); if(el){ el.classList.remove('hidden'); el.classList.add('flex'); }}
function hideModal(id){ const el=document.getElementById(id); if(el){ el.classList.add('hidden'); el.classList.remove('flex'); }}
