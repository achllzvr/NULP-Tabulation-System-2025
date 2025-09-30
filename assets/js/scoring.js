// Auto-save and refresh logic for judge panel

window.judgeAutoSaveOnTimerEnd = function() {
  // Use a sessionStorage flag to prevent repeated auto-save/refresh
  const roundKey = document.body.getAttribute('data-tie-group-id') || 'default';
  const flagKey = 'judgeAutoSaved_' + roundKey;
  if (sessionStorage.getItem(flagKey)) return;

  const form = document.getElementById('score-form');
  if (!form || form._autoSaved || form.offsetParent === null) return;
  form._autoSaved = true;
  sessionStorage.setItem(flagKey, '1');
  if (typeof window.scoresSaved !== 'undefined') window.scoresSaved = true;
  if (typeof showToast === 'function') showToast('Time is up! Auto-saving your scores...', 'info');
  form.submit();
  setTimeout(function() {
    if (document.getElementById('score-form')) {
      window.location.reload();
    }
  }, 2000);
};

// On page load, clear the flag if the form is not present (i.e., round is over)
document.addEventListener('DOMContentLoaded', function() {
  const roundKey = document.body.getAttribute('data-tie-group-id') || 'default';
  const flagKey = 'judgeAutoSaved_' + roundKey;
  if (!document.getElementById('score-form')) {
    sessionStorage.removeItem(flagKey);
  }
});
// --- Judge Score Form Logic ---

function attachJudgeScoreFormHandler() {
  const form = document.getElementById('score-form');
  const saveBtn = document.getElementById('save-scores-btn');
  if (!form || !saveBtn) return;
  if (form._handlerAttached) return; // Prevent double binding
  form._handlerAttached = true;
  let scoresSaved = false;

  // Clear scores function (used by Clear All button)
  window.clearScores = function() {
    const inputs = document.querySelectorAll('#score-form input[type="number"]');
    inputs.forEach(input => {
      input.value = '';
      // Update progress bar
      const progressBar = input.closest('.bg-slate-50').querySelector('.bg-blue-600');
      if (progressBar) {
        progressBar.style.width = '0%';
      }
    });
  };

  form.addEventListener('submit', function(e) {
    if (!scoresSaved) {
      e.preventDefault();
      console.log('[JudgePanel] Intercepted submit, showing SweetAlert confirmation...');
      if (typeof showConfirm === 'function') {
        showConfirm('Save Scores', 'Are you sure you want to save your scores for this participant?', 'Yes, Save', 'Cancel')
          .then((result) => {
            if (result.isConfirmed) {
              scoresSaved = true;
              form.submit();
            }
          });
      } else if (typeof showToast === 'function') {
        showToast('Unable to save: Confirmation dialog not available. Please contact the administrator.', 'error');
      }
    }
  });

  // Update progress bars when scores change
  const scoreInputs = document.querySelectorAll('#score-form input[type="number"]');
  scoreInputs.forEach(input => {
    input.addEventListener('input', function() {
      const maxScore = parseFloat(this.max);
      const currentScore = parseFloat(this.value) || 0;
      const percentage = Math.min((currentScore / maxScore) * 100, 100);
      const progressBar = this.closest('.bg-slate-50').querySelector('.bg-blue-600');
      if (progressBar) {
        progressBar.style.width = percentage + '%';
      }
    });
  });

  // Last 10s warning modal if timer is present
  const tieTimerDiv = document.querySelector('[id^="tie-timer-"]');
  if (tieTimerDiv) {
    let warned = false;
    function checkLast10s() {
      if (warned || scoresSaved) return;
      const time = tieTimerDiv.textContent.trim();
      if (/^\d{2}:\d{2}$/.test(time)) {
        const [min, sec] = time.split(':').map(Number);
        const total = min * 60 + sec;
        if (total <= 10 && total > 0) {
          warned = true;
          if (typeof showWarning === 'function') {
            showWarning('Hurry up!', 'Less than 10 seconds left! Please save your scores now.');
          } else if (typeof showToast === 'function') {
            showToast('Less than 10 seconds left! Please save your scores now.', 'warning');
          }
        }
      }
      if (!warned && !scoresSaved) setTimeout(checkLast10s, 1000);
    }
    setTimeout(checkLast10s, 1000);
  }
}

// Attach as soon as possible
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', attachJudgeScoreFormHandler);
} else {
  attachJudgeScoreFormHandler();
}
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
      showNotification('Scores saved successfully!','success', true);
    } else {
      showNotification(res.error||'Error saving scores','error', true);
    }
  });
  return false;
}

function showModal(id){ const el=document.getElementById(id); if(el){ el.classList.remove('hidden'); el.classList.add('flex'); }}
function hideModal(id){ const el=document.getElementById(id); if(el){ el.classList.add('hidden'); el.classList.remove('flex'); }}
