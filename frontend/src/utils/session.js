// Utilities for strict session and back-button handling
export function preventCache() {
  // Replace current state to reduce bfcache restores
  try { window.history.replaceState(null, document.title, window.location.href); } catch {}
  // If page is restored from bfcache (back-forward cache), reload to re-run auth checks
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      window.location.reload();
    }
  });
}

export async function verifyServerSession() {
  try {
    const res = await fetch('/buildhub/backend/api/session_check.php', { credentials: 'include' });
    const data = await res.json();
    return !!data.authenticated;
  } catch (e) {
    return false;
  }
}