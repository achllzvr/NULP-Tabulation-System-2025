<!-- Modal for public results selection -->
<div id="publicResultsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden">
  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-2xl shadow-2xl border border-white border-opacity-20 p-8 max-w-md w-full text-center relative">
    <button id="closePublicResultsModal" class="absolute top-3 right-3 text-slate-300 hover:text-slate-100 text-xl font-bold">&times;</button>
    <h2 class="text-2xl font-bold text-green-100 mb-2">View Public Results</h2>
    <form id="publicResultsForm" class="space-y-4" autocomplete="off">
      <div>
        <label class="block text-sm font-medium text-slate-200 mb-1">Pageant Code</label>
        <input name="code" type="text" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:border-blue-500 uppercase tracking-wide bg-white bg-opacity-10 text-slate-100 placeholder-slate-400" placeholder="Enter code..." />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-200 mb-1">Section</label>
        <select name="section" class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white bg-opacity-10 text-slate-100">
          <option value="prelim">Preliminary Standings</option>
          <option value="final">Final Results</option>
          <option value="awards">Awards</option>
        </select>
      </div>
      <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded w-full transition-all">Continue</button>
    </form>
    <div id="publicResultsError" class="text-red-400 text-sm mt-3 hidden"></div>
    <p class="text-xs text-slate-300 mt-4">Enter the official code distributed by event organizers.</p>
  </div>
</div>
<script>
(function() {
  const modal = document.getElementById('publicResultsModal');
  const closeBtn = document.getElementById('closePublicResultsModal');
  const form = document.getElementById('publicResultsForm');
  const errorDiv = document.getElementById('publicResultsError');

  window.openPublicResultsModal = function() {
    modal.classList.remove('hidden');
    form.reset();
    errorDiv.classList.add('hidden');
  };
  closeBtn.onclick = function() {
    modal.classList.add('hidden');
  };
  modal.onclick = function(e) {
    if (e.target === modal) modal.classList.add('hidden');
  };
  form.onsubmit = async function(e) {
    e.preventDefault();
    errorDiv.classList.add('hidden');
    const code = form.code.value.trim();
    const section = form.section.value;
    if (!code) {
      errorDiv.textContent = 'Pageant code is required.';
      errorDiv.classList.remove('hidden');
      return;
    }
    // AJAX to validate code and redirect
    try {
      const res = await fetch('public/public_select.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `lookup_pageant=1&code=${encodeURIComponent(code)}&section=${encodeURIComponent(section)}`
      });
      const url = res.url;
      if (res.redirected && url.includes('public_')) {
        window.location.href = url;
      } else {
        const text = await res.text();
        let msg = 'Invalid pageant code. Please check and try again.';
        if (text && text.length < 200) msg = text;
        errorDiv.textContent = msg;
        errorDiv.classList.remove('hidden');
      }
    } catch (err) {
      errorDiv.textContent = 'An error occurred. Please try again.';
      errorDiv.classList.remove('hidden');
    }
  };
})();
</script>
