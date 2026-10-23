<?php
/**
 * Layout Shell Footer — close tags + scripts
 * Include at the END of each page view
 */
?>
  </main><!-- /main-content -->
</div><!-- /main-wrapper -->

<!-- ─── Toast Container ─────────────────────────────────────── -->
<div id="toast-container" aria-live="polite" aria-atomic="false"></div>

<!-- ─── Session Timeout Modal ──────────────────────────────── -->
<div id="session-timeout-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="session-timeout-title">
  <div class="modal-panel sm">
    <div class="modal-header">
      <h2 class="modal-title" id="session-timeout-title">Session Expiring Soon</h2>
    </div>
    <div class="modal-body">
      <p style="font-size:14px;color:var(--color-ink-mute);line-height:1.6;">
        Your session will expire due to inactivity. To protect your account, you will be automatically signed out.
      </p>
      <div style="margin-top:1.5rem;text-align:center;">
        <span id="session-countdown" style="font-size:48px;font-weight:300;color:var(--color-ruby);font-feature-settings:'tnum';letter-spacing:-1px;">60</span>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:.25rem;">seconds remaining</div>
      </div>
    </div>
    <div class="modal-footer">
      <a href="<?= url('/logout') ?>" class="btn btn-ghost btn-sm">Sign Out Now</a>
      <button class="btn btn-primary btn-sm" data-modal-close="session-timeout-modal" onclick="document.dispatchEvent(new Event('session-renewed'))">
        Stay Logged In
      </button>
    </div>
  </div>
</div>

<!-- ─── Quick Asset Lookup Modal ────────────────────────────── -->
<div id="item-lookup-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="item-lookup-title">
  <div class="modal-panel md">
    <div class="modal-header">
      <h2 class="modal-title" id="item-lookup-title">Quick Asset Lookup</h2>
      <button class="modal-close" data-modal-close="item-lookup-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label" for="lookup-barcode">Barcode or Property Number</label>
        <input id="lookup-barcode" class="form-input" type="text" placeholder="Scan or type barcode (e.g. BC-100293)..." autocomplete="off">
      </div>
      <div id="lookup-result" style="margin-top:1rem;"></div>
    </div>
  </div>
</div>

<!-- ─── Confirm Dialog ──────────────────────────────────────── -->
<div id="confirm-dialog" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
  <div class="modal-panel sm">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="confirm-dialog-title">Confirm Action</h2>
        <div class="modal-subtitle">Please verify your intent before proceeding</div>
      </div>
      <button class="modal-close" data-modal-close="confirm-dialog" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body">
      <p id="confirm-dialog-message" style="font-size:14px;color:var(--color-ink-secondary);line-height:1.6;">
        Are you sure you want to proceed? This action cannot be undone.
      </p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost btn-sm" data-modal-close="confirm-dialog">Cancel</button>
      <button type="button" id="confirm-dialog-confirm" class="btn btn-primary btn-sm" style="border-radius:var(--radius-pill);background:var(--color-ruby);border-color:var(--color-ruby);">Confirm</button>
    </div>
  </div>
</div>

<!-- ─── Alert / Notice Dialog ────────────────────────────────── -->
<div id="alert-dialog" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="alert-dialog-title">
  <div class="modal-panel sm">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="alert-dialog-title">Notice</h2>
        <div class="modal-subtitle" id="alert-dialog-subtitle" style="display:none;">System Notification</div>
      </div>
      <button class="modal-close" data-modal-close="alert-dialog" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body">
      <p id="alert-dialog-message" style="font-size:14px;color:var(--color-ink-secondary);line-height:1.6;margin:0;">
      </p>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:flex-end;">
      <button type="button" id="alert-dialog-ok" class="btn btn-primary btn-sm" data-modal-close="alert-dialog" style="min-width:80px;border-radius:var(--radius-pill);">OK</button>
    </div>
  </div>
</div>

<!-- ─── Scripts (Dynamic Asset Resolvers) ───────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= asset('/js/chart-factory.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= asset('/js/realtime-hub.js') ?>"></script>
<script src="<?= asset('/js/realtime-ui.js') ?>"></script>
<script src="<?= asset('/js/app.js') ?>"></script>
<script>
  if (window.RealtimeHub && typeof window.RealtimeHub.init === 'function') {
    window.RealtimeHub.init();
  }
</script>

<script>
// Quick Lookup barcode live handler
(function () {
  const input = document.getElementById('lookup-barcode');
  if (!input) return;
  let debounce;
  input.addEventListener('input', function () {
    clearTimeout(debounce);
    const val = this.value.trim();
    const container = document.getElementById('lookup-result');
    if (!container) return;

    if (val.length < 2) {
      container.innerHTML = '';
      return;
    }

    container.innerHTML = '<div style="text-align:center;padding:1rem;color:var(--color-ink-mute);font-size:12px;">Searching catalog...</div>';

    debounce = setTimeout(function () {
      fetch(window.appUrl('/api/scan/lookup?code=' + encodeURIComponent(val)))
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data && data.success && data.item) {
            const item = data.item;
            const statusClass = item.status === 'available' ? 'badge-available' : (item.status === 'borrowed' ? 'badge-borrowed' : 'badge-maintenance');
            container.innerHTML = `
              <div class="card-soft" style="padding:1rem;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-md);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                  <div>
                    <div style="font-size:14px;font-weight:500;color:var(--color-ink);">${item.item_name || 'Asset'}</div>
                    <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;font-feature-settings:'tnum';">${item.barcode} &bull; ${item.category || 'General'} &bull; ${item.location || 'Campus'}</div>
                  </div>
                  <span class="badge ${statusClass}" style="text-transform:capitalize;">${item.status}</span>
                </div>
                <div style="margin-top:.75rem;display:flex;justify-content:flex-end;">
                  <a href="<?= url('/scan') ?>" class="btn btn-primary btn-sm" style="font-size:11px;padding:4px 10px;">Open in Scanner Station</a>
                </div>
              </div>`;
          } else {
            container.innerHTML = '<div style="text-align:center;padding:1rem;color:var(--color-ink-mute);font-size:12px;">No matching asset found with barcode "' + val + '".</div>';
          }
        })
        .catch(function() {
          container.innerHTML = '<div style="text-align:center;padding:1rem;color:var(--color-ruby);font-size:12px;">Lookup failed. Please check connection.</div>';
        });
    }, 350);
  });
})();

// Global confirm dialog helper (DRY bridge to Modal.confirmDialog)
window.confirmAction = function(message, onConfirm, title = 'Confirm Action', btnText = 'Confirm', isDestructive = true) {
  if (window.Modal && typeof window.Modal.confirmDialog === 'function') {
    window.Modal.confirmDialog(message, title, btnText, isDestructive).then(function(ok) {
      if (ok && typeof onConfirm === 'function') {
        onConfirm();
      }
    });
  } else if (confirm(message)) {
    if (typeof onConfirm === 'function') {
      onConfirm();
    }
  }
};

// Global alert dialog helper (DRY bridge to Modal.alertDialog)
window.alertDialog = function(message, title = 'Notice', subtitle = '') {
  if (window.Modal && typeof window.Modal.alertDialog === 'function') {
    return window.Modal.alertDialog(message, title, subtitle);
  } else if (window.Toast && typeof window.Toast.info === 'function') {
    window.Toast.info(title, message);
    return Promise.resolve();
  } else {
    alert(message);
    return Promise.resolve();
  }
};

// Intercept window.alert so all unexpected/legacy alerts become custom modals
if (typeof window !== 'undefined') {
  window.alert = function(message) {
    if (typeof message === 'object') {
      try { message = JSON.stringify(message); } catch (e) { message = String(message); }
    }
    return window.alertDialog(message);
  };
}
</script>
</body>
</html>
