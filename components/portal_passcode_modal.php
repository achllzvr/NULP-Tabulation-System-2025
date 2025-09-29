<!-- Modal for passcode entry -->
<div id="portalPasscodeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden">
  <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-2xl shadow-2xl border border-white border-opacity-20 p-8 max-w-md w-full text-center relative">
    <button id="closePasscodeModal" class="absolute top-3 right-3 text-slate-300 hover:text-slate-100 text-xl font-bold">&times;</button>
    <h2 class="text-2xl font-bold text-blue-100 mb-2" id="modalTitle">Portal Login</h2>
    <p class="text-slate-200 mb-4 text-sm">To ensure you are an <span id="modalRole">admin/judge</span>, input the given code to you by the event host.</p>
    <input type="password" id="portalPasscodeInput" class="w-full px-4 py-2 rounded-lg border border-white border-opacity-30 bg-white bg-opacity-10 text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-400 mb-4 text-lg text-center placeholder-slate-400" placeholder="Enter passcode..." autocomplete="off" />
    <div id="passcodeError" class="text-red-400 text-sm mb-2 hidden">Incorrect passcode. Please try again.</div>
    <button id="proceedLoginBtn" class="w-full py-2 rounded-lg bg-blue-600 bg-opacity-80 text-white font-semibold text-lg hover:bg-blue-700 transition mb-2">Proceed to Login</button>
    <div class="text-xs text-slate-300">Passcode: <span class="font-mono">NULPANASYS2025</span></div>
  </div>
</div>
<script>
(function() {
  const modal = document.getElementById('portalPasscodeModal');
  const closeBtn = document.getElementById('closePasscodeModal');
  const passcodeInput = document.getElementById('portalPasscodeInput');
  const proceedBtn = document.getElementById('proceedLoginBtn');
  const errorMsg = document.getElementById('passcodeError');
  let targetUrl = '';
  let role = '';

  window.openPortalModal = function(roleType, url) {
    modal.classList.remove('hidden');
    passcodeInput.value = '';
    errorMsg.classList.add('hidden');
    targetUrl = url;
    role = roleType;
    document.getElementById('modalRole').textContent = roleType;
    document.getElementById('modalTitle').textContent = roleType.charAt(0).toUpperCase() + roleType.slice(1) + ' Portal Login';
    passcodeInput.focus();
  };

  closeBtn.onclick = function() {
    modal.classList.add('hidden');
  };
  modal.onclick = function(e) {
    if (e.target === modal) modal.classList.add('hidden');
  };
  proceedBtn.onclick = function() {
    if (passcodeInput.value === 'NULPANASYS2025') {
      window.location.href = targetUrl;
    } else {
      errorMsg.classList.remove('hidden');
      passcodeInput.focus();
    }
  };
  passcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') proceedBtn.click();
  });
})();
</script>
