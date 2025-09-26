// toast.js - simple toast notifications
(function(){
  window.showToast = function(msg, type='info') {
    const root = document.getElementById('toast-root');
    if(!root) return;
    const el = document.createElement('div');
    const colors = {
      info: 'bg-blue-600',
      success: 'bg-green-600',
      error: 'bg-red-600',
      warning: 'bg-amber-500'
    };
    el.className = (colors[type]||colors.info)+ ' text-white text-sm px-4 py-2 rounded shadow flex items-center gap-2 animate-fade';
    el.textContent = msg;
    root.appendChild(el);
    setTimeout(()=>{ el.classList.add('opacity-0','transition'); setTimeout(()=> el.remove(), 500); }, 3000);
  };
})();
