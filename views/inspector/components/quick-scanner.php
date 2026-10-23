<?php
/**
 * Dashboard Quick Barcode Scanner Component
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 * Listens for hardware USB laser barcode scanners and manual barcode entries,
 * immediately triggering the shared Gate Pass Issuance Modal.
 */
?>
<div class="glass-card" style="background:var(--color-canvas-glass-heavy,rgba(255,255,255,0.95));border:1px solid var(--color-hairline,rgba(22,101,52,0.14));border-radius:12px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;box-shadow:var(--shadow-1,0 1px 3px rgba(0,0,0,0.05));position:relative;overflow:hidden;">
  
  <!-- Subtle decorative top accent border -->
  <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #166534 0%, #22c55e 100%);"></div>

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;margin-bottom:0.75rem;">
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:36px;height:36px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;display:flex;align-items:center;justify-content:center;color:#044e29;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="18" height="18" rx="2"></rect>
          <path d="M3 9h18M3 15h18M9 3v18M15 3v18"></path>
        </svg>
      </div>
      <div>
        <h2 style="font-size:15px;font-weight:600;color:var(--color-ink,#0f291e);margin:0;">Quick Gate Pass Barcode Scanner</h2>
        <div style="font-size:12px;color:var(--color-ink-mute,#5c7367);">Scan any equipment barcode to immediately launch and stage an official Gate Pass Permit</div>
      </div>
    </div>

    <!-- Scanner Hardware Status Pills -->
    <div style="display:flex;align-items:center;gap:6px;">
      <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:9999px;font-size:11px;font-weight:600;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;">
        <span style="width:7px;height:7px;border-radius:50%;background:#10b981;box-shadow:0 0 6px #10b981;"></span>
        USB Laser Ready
      </span>
      <a href="<?= url('/scan') ?>" style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:9999px;font-size:11px;font-weight:600;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;text-decoration:none;">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
        Full Camera Station &rarr;
      </a>
    </div>
  </div>

  <!-- Barcode Input Form -->
  <form id="dashboard_quick_scan_form" onsubmit="event.preventDefault(); triggerDashboardScan();" style="display:flex;gap:8px;align-items:center;">
    <div style="position:relative;flex:1;">
      <input 
        type="text" 
        id="dashboard_quick_scan_input" 
        placeholder="Point laser gun or enter asset barcode (e.g. BC-100293 or Property Tag)..." 
        class="form-input" 
        style="width:100%;padding:10px 14px 10px 38px;font-family:monospace;font-size:14px;background:#ffffff;border:1px solid var(--color-hairline,rgba(22,101,52,0.2));border-radius:8px;color:#0f291e;"
        autocomplete="off"
      >
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#5c7367" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
        <line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="10" y2="21"></line><line x1="14" y1="3" x2="14" y2="21"></line>
      </svg>
    </div>

    <button 
      type="submit" 
      id="dashboard_quick_scan_btn"
      class="btn btn-primary" 
      style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:#166534;border:none;border-radius:8px;color:#ffffff;font-weight:600;font-size:13px;cursor:pointer;flex-shrink:0;box-shadow:0 1px 3px rgba(22,101,52,0.25);"
    >
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      <span>Scan &amp; Stage Pass</span>
    </button>
  </form>

  <div id="quick_scan_alert" style="display:none;margin-top:8px;font-size:12px;padding:6px 10px;border-radius:6px;"></div>
</div>

<script>
(function() {
  'use strict';

  // 1. Manual Form Submission Trigger
  window.triggerDashboardScan = function() {
    const input = document.getElementById('dashboard_quick_scan_input');
    if (!input) return;
    const barcode = input.value.trim().toUpperCase();
    if (!barcode) return;

    input.value = '';
    if (typeof window.stageAndOpenGatePass === 'function') {
      window.stageAndOpenGatePass(barcode);
    } else {
      console.warn('gate-pass-modal component not loaded.');
      window.location.href = window.appUrl ? window.appUrl('/inspector/gate-passes?barcode=' + encodeURIComponent(barcode)) : ('/inspector/gate-passes?barcode=' + encodeURIComponent(barcode));
    }
  };

  // 2. Global Keystroke Sniffer for USB Laser Scanners on Inspector Dashboard
  let laserBuffer = '';
  let lastKeyTime = Date.now();

  document.addEventListener('keydown', function(e) {
    // If user is actively typing in another input (except quick scan input), don't hijack
    const activeEl = document.activeElement;
    if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA')) {
      if (activeEl.id !== 'dashboard_quick_scan_input') {
        return;
      }
    }

    const now = Date.now();
    const delta = now - lastKeyTime;
    lastKeyTime = now;

    // Laser scanners type rapidly (<45ms between characters) ending in Enter
    if (e.key === 'Enter') {
      if (laserBuffer.length >= 4) {
        e.preventDefault();
        const scannedCode = laserBuffer.trim().toUpperCase();
        laserBuffer = '';
        if (typeof window.stageAndOpenGatePass === 'function') {
          window.stageAndOpenGatePass(scannedCode);
        }
      }
      laserBuffer = '';
    } else if (e.key.length === 1) {
      if (delta > 85) {
        laserBuffer = e.key;
      } else {
        laserBuffer += e.key;
      }
    }
  });

})();
</script>
