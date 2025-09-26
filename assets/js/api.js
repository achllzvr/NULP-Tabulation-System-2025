// api.js - generic API wrapper
window.API = function(action, payload = {}) {
  const base = window.APP_API_BASE || 'api/api.php';
  return fetch(base + '?action=' + encodeURIComponent(action), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).then(r => r.json());
};
