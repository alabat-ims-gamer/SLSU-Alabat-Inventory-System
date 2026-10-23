<?php
/**
 * Barcode Scanner Station View
 * Supports camera scanning (html5-qrcode for 1D barcodes), handheld USB laser scanners,
 * manual input lookup, and responsive role-adaptive modals.
 */

$title = 'Barcode Scanner Station';
$breadcrumb = 'Scanner';

require __DIR__ . '/../layouts/shell.php';
?>

<div class="max-w-4xl mx-auto space-y-6 animate-fade-in-up">

  <!-- ── Page Header & Status Indicators ────────────────────── -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200">
    <div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Barcode Scanner Station</h1>
      <p class="text-sm text-slate-600 mt-1">Scan equipment labels to request borrows, verify custody, or process returns</p>
    </div>

    <!-- Scanner Hardware Status Pills -->
    <div class="flex items-center gap-2.5 flex-wrap">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        Laser Scanner Ready
      </span>
      <span id="camera-status-pill" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-800 border border-indigo-300 shadow-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
        Camera Active
      </span>
    </div>
  </div>

  <?php if (!empty($eligibility['has_damage_hold'])): 
    $holdIncident = \App\Models\DamageIncident::getActiveHoldByBorrower($db, $userId);
  ?>
  <!-- ── Active Damage Clearance Hold Alert Banner ───────────── -->
  <div class="rounded-2xl p-5 border-2 border-rose-300 bg-rose-50 text-slate-900 shadow-sm space-y-3">
    <div class="flex items-start gap-4">
      <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 shadow-md">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <h3 class="text-base font-bold text-rose-950 tracking-tight">
            Borrowing Privileges Suspended &mdash; Damage Clearance Required
          </h3>
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-200 text-rose-900 border border-rose-300 shadow-sm">
            Account Lock Active
          </span>
        </div>
        <p class="text-xs text-rose-950 mt-1.5 leading-relaxed font-normal">
          Your account is currently placed on an administrative damage clearance hold due to equipment returned in damaged or defective condition.
          <?= $holdIncident ? "<br><span class='mt-1.5 inline-block text-rose-900 bg-white/80 px-2.5 py-1 rounded-lg border border-rose-200/90 font-medium'><strong>Asset:</strong> " . htmlspecialchars($holdIncident['item_name'], ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($holdIncident['barcode'], ENT_QUOTES, 'UTF-8') . ") &bull; <strong>Inspection:</strong> " . htmlspecialchars($holdIncident['damage_description'] ?? 'Damaged upon return', ENT_QUOTES, 'UTF-8') . "</span>" : "" ?>
        </p>
        <div class="mt-3.5 pt-3.5 border-t border-rose-200 flex items-center justify-between flex-wrap gap-3 text-xs">
          <span class="text-rose-950 font-semibold flex items-center gap-1.5">
            <span class="text-sm">📍</span> Next Step: Report to the Property Custodian Office with an in-kind replacement or to settle repair costs.
          </span>
          <a href="<?= url('/records') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition">
            View My Return Records &rarr;
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Camera Viewfinder Container ────────────────────────── -->
  <div class="relative bg-slate-900 rounded-2xl overflow-hidden shadow-xl border border-slate-700/60 aspect-[16/10] sm:aspect-[16/9] flex items-center justify-center">
    
    <!-- Floating Toolbar (Over Video) -->
    <div class="absolute top-3 inset-x-3 z-20 flex items-center justify-between pointer-events-none">
      <span class="px-2.5 py-1 rounded-lg text-[11px] font-mono bg-slate-900/80 backdrop-blur-md text-slate-300 border border-slate-700/50">
        Code 128 &bull; 1D Mode
      </span>
      <div class="flex items-center gap-1.5 pointer-events-auto">
        <button type="button" id="btn-switch-camera" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-900/80 hover:bg-slate-800 backdrop-blur-md text-white border border-slate-700/50 flex items-center gap-1.5 transition shadow-sm" title="Switch between available cameras">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
          <span class="hidden sm:inline">Switch</span>
        </button>
        <button type="button" id="btn-toggle-torch" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-900/80 hover:bg-slate-800 backdrop-blur-md text-white border border-slate-700/50 flex items-center gap-1.5 transition shadow-sm hidden" title="Toggle flashlight">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
          <span class="hidden sm:inline">Torch</span>
        </button>
      </div>
    </div>

    <!-- Html5Qrcode Video Mount Target -->
    <div id="scanner-viewport" class="w-full h-full object-cover"></div>

    <!-- Animated 1D Laser Target Overlay (Wide Rectangular Viewport) -->
    <div id="scanner-overlay" class="absolute inset-0 pointer-events-none flex items-center justify-center">
      <div class="w-[300px] h-[120px] rounded-xl border-2 border-indigo-400/80 relative shadow-[0_0_20px_rgba(99,102,241,0.25)] flex items-center">
        <!-- Corner brackets -->
        <div class="absolute -top-1 -left-1 w-4 h-4 border-t-2 border-l-2 border-indigo-400"></div>
        <div class="absolute -top-1 -right-1 w-4 h-4 border-t-2 border-r-2 border-indigo-400"></div>
        <div class="absolute -bottom-1 -left-1 w-4 h-4 border-b-2 border-l-2 border-indigo-400"></div>
        <div class="absolute -bottom-1 -right-1 w-4 h-4 border-b-2 border-r-2 border-indigo-400"></div>
        <!-- Animated glowing red laser line -->
        <div class="w-full h-0.5 bg-rose-500 shadow-[0_0_8px_#f43f5e] animate-pulse"></div>
      </div>
    </div>

    <!-- Fallback / Permission Denied Notice (Shown if camera is blocked or not found) -->
    <div id="scanner-fallback" class="hidden absolute inset-0 bg-slate-900/95 flex flex-col items-center justify-center p-6 text-center space-y-3 z-30">
      <div class="w-12 h-12 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
      </div>
      <div>
        <h3 class="text-sm font-semibold text-white">Camera Viewfinder Unavailable</h3>
        <p class="text-xs text-slate-400 max-w-sm mt-1">Camera permission is denied or no webcam was detected on this device. You can still scan anytime using your handheld laser scanner or enter the barcode below.</p>
      </div>
      <button type="button" id="btn-focus-manual" class="px-4 py-2 rounded-xl text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-sm">
        Use Manual Barcode Entry
      </button>
    </div>

  </div>

  <!-- ── Manual Barcode Entry Fallback ──────────────────────── -->
  <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
    <div class="flex items-center justify-between">
      <label for="manual-barcode-input" class="text-xs font-semibold text-slate-700 uppercase tracking-wider">
        Handheld Laser Scanner / Manual Lookup
      </label>
      <span class="text-[11px] text-slate-500 font-medium">Supports BC-XXXXXX format</span>
    </div>

    <form id="manual-lookup-form" class="flex gap-2">
      <div class="relative flex-1">
        <input 
          type="text" 
          id="manual-barcode-input" 
          placeholder="Scan barcode with laser gun or type code (e.g. BC-100293)..." 
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-slate-900 font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition uppercase"
          autocomplete="off"
        >
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
        </span>
      </div>
      <button 
        type="submit" 
        id="btn-manual-lookup"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-2 transition shadow-sm flex-shrink-0"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        <span>Lookup Asset</span>
      </button>
    </form>
  </div>

  <!-- ── Recent Scans Tray ──────────────────────────────────── -->
  <div id="recent-scans-tray" class="hidden space-y-2">
    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider block">Recently Scanned in This Session</span>
    <div id="recent-scans-list" class="flex items-center gap-2 flex-wrap"></div>
  </div>

</div>

<!-- ── Mount Role-Adaptive Modal ───────────────────────────── -->
<?php require __DIR__ . '/modals/item-modal.php'; ?>

<!-- ── In-Page Scanner Logic & Audio Synthesizer ─────────────── -->
<script>
(function() {
  'use strict';

  // Config & State
  const viewerRole = '<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>';
  const isCustodian = (viewerRole === 'custodian' || viewerRole === 'admin');
  const isInspector = (viewerRole === 'inspector');
  let html5QrCode = null;
  let isScanningActive = false;
  let currentCameraId = null;
  let availableCameras = [];
  let cameraIndex = 0;
  let scanCooldown = false;
  let activeItemData = null;

  // Recent scans local buffer
  let recentScans = [];

  // Web Audio API Sound Synthesizer (880Hz A5 Beep, 80ms duration)
  function playScanBeep() {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      const ctx = new AudioContext();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.12, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.08);
    } catch (e) {
      console.warn('AudioContext not allowed yet:', e);
    }
  }

  // 1. Initialize Camera Scanner via html5-qrcode
  function initCameraScanner() {
    if (typeof Html5Qrcode === 'undefined') {
      console.error('Html5Qrcode library not loaded.');
      showFallbackView();
      return;
    }

    html5QrCode = new Html5Qrcode("scanner-viewport");

    Html5Qrcode.getCameras().then(cameras => {
      if (cameras && cameras.length > 0) {
        availableCameras = cameras;
        // Prefer rear camera
        const rearCamera = cameras.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('rear') || c.label.toLowerCase().includes('environment'));
        currentCameraId = rearCamera ? rearCamera.id : cameras[0].id;
        cameraIndex = cameras.findIndex(c => c.id === currentCameraId);

        startCamera(currentCameraId);

        if (cameras.length > 1) {
          document.getElementById('btn-switch-camera').classList.remove('hidden');
        }
      } else {
        showFallbackView();
      }
    }).catch(err => {
      console.warn('Camera detection error:', err);
      showFallbackView();
    });
  }

  function startCamera(cameraId) {
    const config = {
      fps: 15,
      qrbox: { width: 300, height: 120 }, // Optimized wide 1D barcode bounding box
      aspectRatio: 1.777778,
      formatsToSupport: [
        Html5QrcodeSupportedFormats.CODE_128,
        Html5QrcodeSupportedFormats.CODE_39
      ],
      experimentalFeatures: {
        useBarCodeDetectorIfSupported: true
      }
    };

    html5QrCode.start(
      cameraId,
      config,
      (decodedText) => onBarcodeDetected(decodedText),
      (errorMessage) => { /* Ignore frame-level no-code errors */ }
    ).then(() => {
      isScanningActive = true;
      document.getElementById('scanner-fallback').classList.add('hidden');
      document.getElementById('camera-status-pill').classList.remove('hidden');
    }).catch(err => {
      console.warn('Failed to start camera feed:', err);
      showFallbackView();
    });
  }

  function showFallbackView() {
    document.getElementById('scanner-fallback').classList.remove('hidden');
    document.getElementById('camera-status-pill').classList.add('hidden');
  }

  // 2. Barcode Detection Handler
  function onBarcodeDetected(code) {
    const cleanCode = (code || '').trim().toUpperCase();
    if (!cleanCode || scanCooldown) return;

    // Trigger feedback
    scanCooldown = true;
    playScanBeep();
    if (navigator.vibrate) {
      navigator.vibrate(80);
    }

    // Freeze camera video
    if (html5QrCode && isScanningActive) {
      try { html5QrCode.pause(); } catch(e) {}
    }

    // Add to recent tray
    addRecentScan(cleanCode);

    // Fetch and show modal
    lookupBarcode(cleanCode);
  }

  // 3. Global Keystroke Sniffer for USB Laser Scanners
  let laserBuffer = '';
  let lastKeyTime = Date.now();

  document.addEventListener('keydown', function(e) {
    // If user is actively typing in an input or textarea, do not hijack
    if (e.target && (
      e.target.tagName === 'INPUT' || 
      e.target.tagName === 'TEXTAREA' || 
      e.target.isContentEditable ||
      e.target.id === 'modal-room-input' ||
      e.target.id === 'modal-subject-input' ||
      e.target.id === 'modal-purpose-input' || 
      e.target.id === 'modal-return-remarks'
    )) {
      if (e.target.id !== 'manual-barcode-input') {
        return;
      }
    }

    const now = Date.now();
    const delta = now - lastKeyTime;
    lastKeyTime = now;

    // Laser scanners type very fast (< 45ms between chars)
    if (e.key === 'Enter') {
      if (laserBuffer.length >= 4) {
        e.preventDefault();
        const scanned = laserBuffer.trim().toUpperCase();
        laserBuffer = '';
        onBarcodeDetected(scanned);
      }
      laserBuffer = '';
    } else if (e.key.length === 1) {
      if (delta > 100) {
        // Human typing or delay; reset buffer
        laserBuffer = e.key;
      } else {
        laserBuffer += e.key;
      }
    }
  });

  // Manual Form Submit
  document.getElementById('manual-lookup-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const val = document.getElementById('manual-barcode-input').value.trim();
    if (val) {
      onBarcodeDetected(val);
    }
  });

  document.getElementById('btn-focus-manual').addEventListener('click', function() {
    document.getElementById('manual-barcode-input').focus();
  });

  // Camera Switch Button
  document.getElementById('btn-switch-camera').addEventListener('click', function() {
    if (availableCameras.length <= 1 || !html5QrCode) return;
    cameraIndex = (cameraIndex + 1) % availableCameras.length;
    currentCameraId = availableCameras[cameraIndex].id;

    html5QrCode.stop().then(() => {
      startCamera(currentCameraId);
    }).catch(err => console.error(err));
  });

  // 4. API Lookup & Modal Mounting
  function lookupBarcode(barcode) {
    showModalLoading();

    fetch(window.appUrl(`/api/scan/lookup?code=${encodeURIComponent(barcode)}`))
      .then(res => res.json())
      .then(res => {
        if (!res.success) {
          Toast.error('Asset Lookup Failed', res.message || 'Asset not found.');
          closeModal();
          return;
        }

        activeItemData = res;
        renderModalContent(res);
      })
      .catch(err => {
        Toast.error('Network Error', 'Failed to connect to the inventory server.');
        closeModal();
      });
  }

  // 5. Render Modal Data
  function renderModalContent(data) {
    const item = data.item;
    const history = data.history || [];
    const eligibility = data.eligibility || {};

    // Header metadata
    document.getElementById('modal-item-name').textContent = item.item_name;
    document.getElementById('modal-barcode').textContent = item.barcode;
    document.getElementById('modal-property-number').textContent = item.property_number || 'No Property #';
    document.getElementById('modal-category-badge').textContent = item.category;
    document.getElementById('modal-location').textContent = item.location;
    const condLabel = (item.item_condition === 'for_disposal' || item.item_condition === 'condemned') 
      ? 'Condemned' 
      : item.item_condition;
    document.getElementById('modal-condition-badge').textContent = condLabel;
    document.getElementById('modal-condition-text').textContent = `${condLabel} Condition`;
    document.getElementById('modal-student-borrowable').textContent = item.is_student_borrowable ? 'Yes (Allowed)' : 'No (Faculty Only)';
    document.getElementById('modal-student-borrowable').className = item.is_student_borrowable ? 'font-semibold text-emerald-700' : 'font-semibold text-rose-700';
    document.getElementById('modal-description').textContent = item.description || 'No additional specifications logged.';

    // Status Pill
    const statusPill = document.getElementById('modal-status-badge');
    const statusLabels = {
      'available':    'AVAILABLE',
      'requested':    'REQUESTED',
      'borrowed':     'BORROWED',
      'maintenance':  'MAINTENANCE',
      'for_disposal': 'FOR DISPOSAL',
      'disposed':     'DISPOSED'
    };
    statusPill.textContent = statusLabels[item.status] || item.status.toUpperCase();
    statusPill.className = 'px-2.5 py-0.5 rounded-full text-xs font-medium ' + getStatusPillClass(item.status);

    // History count
    document.getElementById('modal-history-count').textContent = history.length;
    renderHistoryTimeline(history);

    // Dynamic Action Panel
    renderActionPanel(data);

    // Show modal
    openModal();
  }

  function getStatusPillClass(status) {
    switch (status) {
      case 'available':    return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
      case 'requested':    return 'bg-amber-100 text-amber-800 border border-amber-200';
      case 'borrowed':     return 'bg-indigo-100 text-indigo-800 border border-indigo-200';
      case 'maintenance':  return 'bg-amber-100 text-amber-800 border border-amber-200';
      case 'for_disposal': return 'bg-rose-100 text-rose-800 border border-rose-200';
      case 'disposed':     return 'bg-slate-100 text-slate-800 border border-slate-200';
      default:             return 'bg-slate-100 text-slate-800 border border-slate-200';
    }
  }

  // 6. Role-Adaptive Action Panel Rendering
  function renderActionPanel(data) {
    const item = data.item;
    const activeLoan = data.active_loan;
    const eligibility = data.eligibility || {};
    const panel = document.getElementById('modal-action-panel');
    panel.innerHTML = '';

    if (isInspector) {
      // ── INSPECTOR VIEW (Gate Pass Clearance & Verification) ─────
      renderInspectorAction(panel, item, activeLoan);
    } else if (!isCustodian) {
      // ── BORROWER VIEW (Student & Faculty) ──────────────────────
      renderBorrowerView(panel, item, activeLoan, eligibility);
    } else {
      // ── CUSTODIAN VIEW (Supply Office Counter) ────────────────
      renderCustodianView(panel, item, activeLoan);
    }
  }

  function renderInspectorAction(panel, item, activeLoan) {
    const isBorrowed = (item.status === 'borrowed' && activeLoan);
    const gatePassUrl = (window.appUrl ? window.appUrl('/inspector/gate-passes') : '/inspector/gate-passes') + '?barcode=' + encodeURIComponent(item.barcode);

    if (isBorrowed) {
      panel.className = 'p-4 rounded-xl border border-emerald-200 bg-emerald-50/70 space-y-3';
      panel.innerHTML = `
        <div class="flex items-start justify-between gap-3 p-3 bg-white rounded-lg border border-emerald-200 text-xs">
          <div>
            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <span>${escapeHtml(activeLoan.borrower_name || 'Active Borrower')}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">Active Campus Borrow</span>
            </div>
            <div class="text-slate-600 font-mono mt-0.5">${escapeHtml(activeLoan.id_number || '')} &bull; ${escapeHtml(activeLoan.department || '')}</div>
            <div class="text-slate-700 mt-1 italic">"${escapeHtml(activeLoan.purpose || 'Official Campus Activity')}"</div>
          </div>
          <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">
            Due: ${formatDate(activeLoan.expected_return_date)}
          </span>
        </div>

        <a href="${gatePassUrl}" class="w-full py-2.5 rounded-xl text-sm font-semibold bg-emerald-700 hover:bg-emerald-800 text-white flex items-center justify-center gap-2 transition shadow-sm text-decoration-none">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
          <span>Issue Official Gate Pass for Asset</span>
        </a>
      `;
    } else {
      panel.className = 'p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-3';
      panel.innerHTML = `
        <div class="text-xs text-slate-600">
          <div class="font-bold text-slate-900 mb-1">Equipment Status: <span class="uppercase">${escapeHtml(item.status)}</span></div>
          <p>This item is currently not marked as borrowed. Gate Passes are typically issued for borrowed equipment.</p>
        </div>
        <a href="${gatePassUrl}" class="w-full py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 flex items-center justify-center gap-2 transition shadow-sm text-decoration-none">
          <span>Stage on Gate Pass Desk Anyway &rarr;</span>
        </a>
      `;
    }
  }

  function renderBorrowerView(panel, item, activeLoan, eligibility) {
    if (item.status === 'available') {
      if (!eligibility.eligible) {
        panel.className = 'p-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-900 text-sm space-y-1.5';
        panel.innerHTML = `
          <div class="font-bold text-rose-950 flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Borrowing Suspended
          </div>
          <p class="text-xs text-rose-900 leading-relaxed">${escapeHtml(eligibility.reason || 'You are not eligible to borrow items.')}</p>
        `;
        return;
      }

      if (viewerRole === 'student' && !item.is_student_borrowable) {
        panel.className = 'p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm space-y-1';
        panel.innerHTML = `
          <div class="font-bold text-amber-950">Faculty Restricted Asset</div>
          <p class="text-xs text-amber-900 mt-1 leading-relaxed">This equipment is reserved for Faculty instruction only. Students cannot check out this item.</p>
        `;
        return;
      }

      // Available to request
      panel.className = 'p-4 rounded-xl border border-indigo-100 bg-slate-50 space-y-3';
      panel.innerHTML = `
        <div class="space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <div>
              <label for="modal-room-input" class="block text-xs font-semibold text-slate-800 mb-1">
                Room / Laboratory <span class="text-rose-500">*</span>
              </label>
              <input 
                type="text" 
                id="modal-room-input" 
                placeholder="e.g., Room 204, ComLab 1" 
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
              >
            </div>
            <div>
              <label for="modal-subject-input" class="block text-xs font-semibold text-slate-800 mb-1">
                Subject / Course <span class="text-rose-500">*</span>
              </label>
              <input 
                type="text" 
                id="modal-subject-input" 
                placeholder="e.g., IT 312, CS 201" 
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
              >
            </div>
          </div>
          <div>
            <label for="modal-purpose-input" class="block text-xs font-semibold text-slate-800 mb-1">
              Purpose / Activity Details <span class="text-rose-500">*</span>
            </label>
            <input 
              type="text" 
              id="modal-purpose-input" 
              placeholder="e.g., Hands-on lab experiment & microcontroller programming" 
              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
            >
          </div>
          <div class="flex items-center justify-between text-[11px] text-slate-600 font-medium pt-0.5">
            <span>Quota: ${eligibility.total_slots_used || 0} of ${eligibility.max_quota || 3} items used</span>
            <span>30-min reservation hold upon request</span>
          </div>
        </div>
        <button type="button" id="btn-submit-request" class="w-full py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center gap-2 transition shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
          Request to Borrow Item
        </button>
      `;

      document.getElementById('btn-submit-request').addEventListener('click', submitBorrowRequest);

    } else if (item.status === 'requested') {
      if (item.is_requested_by_viewer) {
        const minsLeft = Math.ceil((item.hold_seconds_left || 0) / 60);
        panel.className = 'p-4 rounded-xl border border-amber-200 bg-amber-50 space-y-3';
        panel.innerHTML = `
          <div class="flex items-start justify-between gap-2">
            <div>
              <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-900">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                Pending Custodian Approval
              </span>
              <p class="text-xs text-amber-900 mt-1 leading-relaxed">Your reservation hold is active. Present this screen to the Property Custodian to pick up the item.</p>
            </div>
            <span class="px-2.5 py-1 rounded-lg bg-amber-200 text-amber-950 font-mono text-xs font-bold border border-amber-300">
              ~${minsLeft}m left
            </span>
          </div>
          <button type="button" id="btn-cancel-request" class="w-full py-2 rounded-xl text-xs font-semibold border border-amber-300 text-amber-900 bg-white hover:bg-amber-100 transition shadow-sm">
            Cancel My Request
          </button>
        `;

        document.getElementById('btn-cancel-request').addEventListener('click', cancelBorrowRequest);
      } else {
        panel.className = 'p-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 text-sm';
        panel.innerHTML = `
          <div class="font-bold text-slate-900">Asset Currently Reserved</div>
          <p class="text-xs text-slate-600 mt-1">Another student or faculty member has requested this item. If not picked up within 30 minutes, it will automatically return to available status.</p>
        `;
      }
    } else if (item.status === 'borrowed') {
      const isBorrowedByViewer = (activeLoan && activeLoan.user_id === <?= (int)($_SESSION['user_id'] ?? 0) ?>);
      if (isBorrowedByViewer) {
        panel.className = 'p-4 rounded-xl border border-indigo-200 bg-indigo-50 space-y-2';
        panel.innerHTML = `
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-indigo-950">Currently Borrowed by You</span>
            <span class="text-xs font-mono font-semibold text-indigo-800">Due: ${formatDate(activeLoan.expected_return_date)}</span>
          </div>
          <p class="text-xs text-indigo-900 leading-relaxed">To check in this item, bring it to the Property Custodian at the Supply Office.</p>
        `;
      } else {
        panel.className = 'p-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 text-sm';
        panel.innerHTML = `
          <div class="font-bold text-slate-900">Asset Currently Checked Out</div>
          <p class="text-xs text-slate-600 mt-1">This item is in active use. Expected return date: ${activeLoan ? formatDate(activeLoan.expected_return_date) : 'N/A'}</p>
        `;
      }
    } else {
      panel.className = 'p-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 text-sm';
      panel.innerHTML = `<div class="font-bold text-slate-900">Status: ${escapeHtml(item.status.toUpperCase())}</div>`;
    }
  }

  function renderCustodianView(panel, item, activeLoan) {
    if (item.status === 'requested') {
      const minsLeft = Math.ceil((item.hold_seconds_left || 0) / 60);
      panel.className = 'p-4 rounded-xl border border-amber-200 bg-amber-50/70 space-y-4';
      panel.innerHTML = `
        <!-- Requester Info Box -->
        <div class="flex items-start justify-between gap-3 p-3 bg-white rounded-lg border border-amber-200 text-xs">
          <div>
            <div class="font-bold text-slate-900 text-sm">${escapeHtml(item.requested_by_name || 'Student')}</div>
            <div class="text-slate-600 font-mono mt-0.5">${escapeHtml(item.requested_by_id_number || '')} &bull; ${escapeHtml(item.requested_by_department || '')}</div>
            <div class="text-slate-800 mt-1 italic">"${escapeHtml(item.request_purpose || 'No purpose entered')}"</div>
          </div>
          <span class="px-2 py-1 rounded bg-amber-100 text-amber-900 font-mono text-[11px] font-bold border border-amber-200">
            ${minsLeft}m hold left
          </span>
        </div>

        <!-- Return Deadline Presets -->
        <div>
          <label class="block text-xs font-semibold text-slate-800 mb-1.5">Assign Return Deadline</label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
            <label class="p-2 rounded-lg border border-slate-200 bg-white flex items-center gap-1.5 cursor-pointer hover:border-indigo-300">
              <input type="radio" name="deadline_preset" value="4_hours" checked class="accent-indigo-600">
              <span class="font-medium text-slate-800">+4h (Session)</span>
            </label>
            <label class="p-2 rounded-lg border border-slate-200 bg-white flex items-center gap-1.5 cursor-pointer hover:border-indigo-300">
              <input type="radio" name="deadline_preset" value="today_5pm" class="accent-indigo-600">
              <span class="font-medium text-slate-800">Today 5 PM</span>
            </label>
            <label class="p-2 rounded-lg border border-slate-200 bg-white flex items-center gap-1.5 cursor-pointer hover:border-indigo-300">
              <input type="radio" name="deadline_preset" value="tomorrow_5pm" class="accent-indigo-600">
              <span class="font-medium text-slate-800">Tomorrow 5 PM</span>
            </label>
            <label class="p-2 rounded-lg border border-slate-200 bg-white flex items-center gap-1.5 cursor-pointer hover:border-indigo-300">
              <input type="radio" name="deadline_preset" value="7_days" class="accent-indigo-600">
              <span class="font-medium text-slate-800">7 Days (Faculty)</span>
            </label>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2 pt-1">
          <button type="button" id="btn-custodian-deny" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 transition shadow-sm">
            Deny (1-Click)
          </button>
          <button type="button" id="btn-custodian-approve" class="flex-1 py-2.5 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center gap-2 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Approve &amp; Issue Item
          </button>
        </div>
      `;

      document.getElementById('btn-custodian-approve').addEventListener('click', approveBorrowRequest);
      document.getElementById('btn-custodian-deny').addEventListener('click', denyBorrowRequest);

    } else if (item.status === 'borrowed') {
      panel.className = 'p-4 rounded-xl border border-indigo-200 bg-indigo-50/60 space-y-4';
      panel.innerHTML = `
        <!-- Active Borrower Info Box -->
        <div class="p-3 bg-white rounded-lg border border-indigo-200 text-xs flex items-start justify-between">
          <div>
            <div class="font-bold text-slate-900 text-sm">${escapeHtml(activeLoan ? activeLoan.borrower_name : 'Unknown')}</div>
            <div class="text-slate-600 font-mono mt-0.5">${escapeHtml(activeLoan ? activeLoan.borrower_id_number : '')} &bull; ${escapeHtml(activeLoan ? activeLoan.borrower_department : '')}</div>
          </div>
          <div class="text-right">
            <span class="text-indigo-700 font-mono font-semibold block">Due: ${activeLoan ? formatDate(activeLoan.expected_return_date) : ''}</span>
          </div>
        </div>

        <!-- Return Condition Inspection -->
        <div>
          <label class="block text-xs font-semibold text-slate-800 mb-1.5">Physical Condition on Return</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs mb-2">
            <label class="p-2 rounded-lg border border-slate-200 bg-white flex items-center gap-1.5 cursor-pointer">
              <input type="radio" name="return_condition" value="good" checked class="accent-emerald-600">
              <span class="font-medium text-slate-800">Good Condition</span>
            </label>
            <label class="p-2 rounded-lg border border-slate-200 bg-white flex items-center gap-1.5 cursor-pointer">
              <input type="radio" name="return_condition" value="fair" class="accent-amber-600">
              <span class="font-medium text-slate-800">Fair Condition</span>
            </label>
            <label class="p-2 rounded-lg border border-slate-200 bg-white flex items-center gap-1.5 cursor-pointer">
              <input type="radio" name="return_condition" value="mint" class="accent-indigo-600">
              <span class="font-medium text-slate-800">Mint / Pristine</span>
            </label>
            <label class="p-2 rounded-lg border border-rose-300 bg-rose-50 flex items-center gap-1.5 cursor-pointer text-rose-800">
              <input type="radio" name="return_condition" value="damaged" class="accent-rose-600">
              <span class="font-semibold text-rose-900">Damaged (Repair)</span>
            </label>
            <label class="p-2 rounded-lg border border-rose-400 bg-rose-100 flex items-center gap-1.5 cursor-pointer text-rose-950 col-span-2 sm:col-span-2">
              <input type="radio" name="return_condition" value="not_working" class="accent-rose-600">
              <span class="font-bold text-rose-950">Not Working (Defective / Unserviceable)</span>
            </label>
          </div>

          <!-- Assessed Damage Fee Input (Revealed on Damaged/Not Working) -->
          <div id="modal-return-damage-fee-group" class="hidden p-3 rounded-xl border border-rose-200 bg-rose-50/80 space-y-1.5">
            <label for="modal-return-damage-fee" class="block text-xs font-semibold text-rose-950">
              Assessed Damage / Repair Fee (₱) <span class="text-rose-600">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-rose-900">₱</span>
              <input 
                type="number" 
                id="modal-return-damage-fee" 
                step="0.01" 
                min="0" 
                placeholder="e.g. 500.00" 
                class="w-full pl-7 pr-3.5 py-2 rounded-xl border border-rose-300 bg-white text-slate-900 text-xs font-mono font-semibold focus:ring-2 focus:ring-rose-500 focus:outline-none"
              >
            </div>
            <p class="text-[11px] text-rose-800 leading-tight">
              IMS sets this initial bill. The student will present this amount at the university cashier and upload their receipt.
            </p>
          </div>

          <div class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-[11px] text-rose-900 font-medium leading-relaxed">
            ⚠️ <strong>Accountability Rule:</strong> Marking an item Damaged or Not Working shifts the asset to Maintenance and places the borrower under an automated Clearance Hold.
          </div>
        </div>

        <!-- Remarks -->
        <div>
          <label for="modal-return-remarks" class="block text-xs font-semibold text-slate-800 mb-1">Inspection Remarks / Damage Notes</label>
          <input 
            type="text" 
            id="modal-return-remarks" 
            placeholder="Inspection findings, missing cables, broken screen, etc..." 
            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-slate-900 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          >
        </div>

        <button type="button" id="btn-custodian-return" class="w-full py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center gap-2 transition shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          Process Return &amp; Check-In
        </button>
      `;

      // Attach event listener for condition radio changes to toggle fee input
      document.querySelectorAll('input[name="return_condition"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
          const feeGroup = document.getElementById('modal-return-damage-fee-group');
          if (feeGroup) {
            if (e.target.value === 'damaged' || e.target.value === 'not_working') {
              feeGroup.classList.remove('hidden');
            } else {
              feeGroup.classList.add('hidden');
            }
          }
        });
      });

      document.getElementById('btn-custodian-return').addEventListener('click', processReturn);

    } else if (item.status === 'available') {
      panel.className = 'p-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 text-sm';
      panel.innerHTML = `
        <div class="font-bold text-slate-900">Asset Available in Catalog</div>
        <p class="text-xs text-slate-600 mt-1">Awaiting borrower scan &amp; request submission. (Custodians cannot issue without a borrower request, ensuring full accountability).</p>
      `;
    } else {
      panel.className = 'p-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 text-sm';
      panel.innerHTML = `<div class="font-bold text-slate-900">Asset is currently ${escapeHtml(item.status.toUpperCase())}.</div>`;
    }
  }

  // 7. Render History Timeline
  function renderHistoryTimeline(history) {
    const container = document.getElementById('modal-history-container');
    container.innerHTML = '';

    if (!history || history.length === 0) {
      container.innerHTML = `
        <div class="text-center py-8 text-slate-500 text-xs">
          <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          No previous borrowing records found for this asset.
        </div>
      `;
      return;
    }

    const timeline = document.createElement('div');
    timeline.className = 'relative pl-6 border-l-2 border-slate-200 space-y-6';

    history.forEach(item => {
      const dot = document.createElement('div');
      dot.className = 'relative';
      dot.innerHTML = `
        <div class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full bg-indigo-600 border-2 border-white shadow-sm"></div>
        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="font-bold text-slate-900">${escapeHtml(item.full_name)}</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-800 border border-slate-300">
              ${escapeHtml(item.department_program || 'Student')}
            </span>
          </div>
          ${isCustodian && item.id_number ? `<div class="text-[11px] font-mono text-slate-600 font-medium">ID: ${escapeHtml(item.id_number)}</div>` : ''}
          <div class="flex items-center gap-2 text-slate-600 text-[11px] font-mono pt-1">
            <span>${formatDate(item.transaction_date)}</span>
            <span>&rarr;</span>
            <span class="${item.actual_return_date ? 'text-emerald-700 font-semibold' : 'text-amber-700 font-semibold'}">
              ${item.actual_return_date ? formatDate(item.actual_return_date) : 'Active Borrow'}
            </span>
          </div>
          ${item.returned_condition ? `
            <div class="pt-1 text-[11px]">
              <span class="text-slate-500 font-medium">Returned Condition:</span>
              <span class="font-semibold text-slate-800 capitalize">${escapeHtml(item.returned_condition)}</span>
            </div>
          ` : ''}
          ${isCustodian && item.remarks ? `<div class="pt-1 text-[11px] text-slate-600 italic">"${escapeHtml(item.remarks)}"</div>` : ''}
        </div>
      `;
      timeline.appendChild(dot);
    });

    container.appendChild(timeline);
  }

  // 8. Action API Calls
  function submitBorrowRequest() {
    const room = (document.getElementById('modal-room-input')?.value || '').trim();
    const subject = (document.getElementById('modal-subject-input')?.value || '').trim();
    const purpose = (document.getElementById('modal-purpose-input')?.value || '').trim();

    if (!room || !subject || !purpose) {
      Toast.warning('Required Fields', 'Please enter the room, subject, and purpose before submitting.');
      return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    formData.append('csrf_token', csrf);
    formData.append('item_id', activeItemData.item.item_id);
    formData.append('room', room);
    formData.append('subject', subject);
    formData.append('purpose', purpose);

    fetch(window.appUrl('/api/transaction/request'), {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        Toast.success('Request Submitted', res.message);
        lookupBarcode(activeItemData.item.barcode);
      } else {
        Toast.error('Request Failed', res.message);
      }
    })
    .catch(() => Toast.error('Error', 'Failed to submit request.'));
  }

  function cancelBorrowRequest() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    formData.append('csrf_token', csrf);
    formData.append('item_id', activeItemData.item.item_id);

    fetch(window.appUrl('/api/transaction/cancel-request'), {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        Toast.info('Request Cancelled', res.message);
        lookupBarcode(activeItemData.item.barcode);
      } else {
        Toast.error('Error', res.message);
      }
    })
    .catch(() => Toast.error('Error', 'Failed to cancel request.'));
  }

  function approveBorrowRequest() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const preset = document.querySelector('input[name="deadline_preset"]:checked')?.value || '4_hours';
    const formData = new FormData();
    formData.append('csrf_token', csrf);
    formData.append('item_id', activeItemData.item.item_id);
    formData.append('deadline_option', preset);

    fetch(window.appUrl('/api/transaction/approve'), {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        Toast.success('Borrow Approved & Issued', res.message);
        closeModal();
        const manualInput = document.getElementById('manual-barcode-input');
        if (manualInput) manualInput.value = '';
        activeItemData = null;
      } else {
        Toast.error('Approval Error', res.message);
      }
    })
    .catch(() => Toast.error('Error', 'Failed to approve borrow request.'));
  }

  function denyBorrowRequest() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    formData.append('csrf_token', csrf);
    formData.append('item_id', activeItemData.item.item_id);

    fetch(window.appUrl('/api/transaction/deny'), {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        Toast.info('Request Denied', res.message);
        closeModal();
        const manualInput = document.getElementById('manual-barcode-input');
        if (manualInput) manualInput.value = '';
        activeItemData = null;
      } else {
        Toast.error('Denial Error', res.message);
      }
    })
    .catch(() => Toast.error('Error', 'Failed to deny request.'));
  }

  function processReturn() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const cond = document.querySelector('input[name="return_condition"]:checked')?.value || 'good';
    const remarks = (document.getElementById('modal-return-remarks')?.value || '').trim();
    const feeInput = document.getElementById('modal-return-damage-fee');
    const assessedCost = (feeInput && (cond === 'damaged' || cond === 'not_working')) ? feeInput.value.trim() : '';

    const formData = new FormData();
    formData.append('csrf_token', csrf);
    formData.append('item_id', activeItemData.item.item_id);
    formData.append('condition', cond);
    formData.append('remarks', remarks);
    if (assessedCost !== '') {
      formData.append('assessed_cost', assessedCost);
    }

    fetch(window.appUrl('/api/transaction/return'), {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        if (res.is_damaged) {
          Toast.warning('Return Recorded with Damage', res.message);
        } else {
          Toast.success('Return Completed', res.message);
        }
        closeModal();
        const manualInput = document.getElementById('manual-barcode-input');
        if (manualInput) manualInput.value = '';
        activeItemData = null;
      } else {
        Toast.error('Return Error', res.message);
      }
    })
    .catch(() => Toast.error('Error', 'Failed to process return.'));
  }

  // 9. Recent Scans Tray Management
  function addRecentScan(code) {
    if (!recentScans.includes(code)) {
      recentScans.unshift(code);
      if (recentScans.length > 5) recentScans.pop();
      renderRecentScans();
    }
  }

  function renderRecentScans() {
    const tray = document.getElementById('recent-scans-tray');
    const list = document.getElementById('recent-scans-list');
    if (recentScans.length === 0) {
      tray.classList.add('hidden');
      return;
    }

    tray.classList.remove('hidden');
    list.innerHTML = '';
    recentScans.forEach(code => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'px-3 py-1 rounded-lg text-xs font-mono bg-slate-100 text-slate-800 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-300 transition flex items-center gap-1.5 font-medium shadow-sm';
      chip.innerHTML = `<span>${code}</span>`;
      chip.addEventListener('click', () => onBarcodeDetected(code));
      list.appendChild(chip);
    });
  }

  // 10. Modal Display & Tab Toggles
  const tabBtnOverview = document.getElementById('tab-btn-overview');
  const tabBtnHistory = document.getElementById('tab-btn-history');
  const tabContentOverview = document.getElementById('tab-content-overview');
  const tabContentHistory = document.getElementById('tab-content-history');

  function openModal() {
    if (window.Modal && typeof window.Modal.open === 'function') {
      window.Modal.open('barcode-item-modal');
    }
  }

  function closeModal() {
    if (window.Modal && typeof window.Modal.close === 'function') {
      window.Modal.close('barcode-item-modal');
    }
    scanCooldown = false;
    // Resume camera safely
    if (html5QrCode && isScanningActive) {
      try { html5QrCode.resume(); } catch(e) {}
    }
  }

  document.getElementById('modal-close-btn')?.addEventListener('click', closeModal);

  function showModalLoading() {
    document.getElementById('modal-item-name').textContent = 'Fetching Asset Data...';
    document.getElementById('modal-barcode').textContent = '...';
    document.getElementById('modal-property-number').textContent = '';
    document.getElementById('modal-action-panel').innerHTML = `
      <div class="flex items-center justify-center py-6 text-slate-500 font-medium">
        <svg class="animate-spin w-5 h-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span>Retrieving equipment specifications...</span>
      </div>
    `;
    switchTab('overview');
    openModal();
  }

  function switchTab(tab) {
    if (tab === 'overview') {
      tabBtnOverview.className = 'py-3 px-4 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 flex items-center gap-2 transition';
      tabBtnHistory.className = 'py-3 px-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition';
      tabContentOverview.classList.remove('hidden');
      tabContentHistory.classList.add('hidden');
    } else {
      tabBtnHistory.className = 'py-3 px-4 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 flex items-center gap-2 transition';
      tabBtnOverview.className = 'py-3 px-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition';
      tabContentHistory.classList.remove('hidden');
      tabContentOverview.classList.add('hidden');
    }
  }

  tabBtnOverview.addEventListener('click', () => switchTab('overview'));
  tabBtnHistory.addEventListener('click', () => switchTab('history'));

  // Utility helpers
  function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, m => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[m]);
  }

  function formatDate(dStr) {
    if (!dStr) return '';
    const d = new Date(dStr);
    if (isNaN(d.getTime())) return dStr;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
  }

  // Auto-start scanner on page load & check URL query params for deep-linked barcode auto-lookup
  window.addEventListener('DOMContentLoaded', function() {
    initCameraScanner();

    // Deep-link handshake: ?barcode=BC-XXXXXX (from Active Borrowals operations table)
    const urlParams = new URLSearchParams(window.location.search);
    const paramBarcode = urlParams.get('barcode');
    if (paramBarcode) {
      const cleanParamCode = paramBarcode.trim().toUpperCase();
      const manualInput = document.getElementById('manual-barcode-input');
      if (manualInput) {
        manualInput.value = cleanParamCode;
      }
      // Trigger lookup and open inspection modal
      setTimeout(() => {
        onBarcodeDetected(cleanParamCode);
      }, 300);
    }
  });

  // Realtime Integration: Protect against concurrent borrowing/approval race conditions
  if (window.RealtimeHub) {
    window.RealtimeHub.on('ITEM_STATUS_CHANGED', function(event) {
      const payload = event.payload || {};
      if (activeItemData && activeItemData.item && activeItemData.item.item_id == payload.item_id) {
        if (window.Toast && typeof window.Toast.warning === 'function') {
          window.Toast.warning('Status Changed Live', `Asset "${activeItemData.item.item_name}" was just updated to ${payload.status} by another user.`);
        }
        lookupBarcode(activeItemData.item.barcode);
      }
    });

    window.RealtimeHub.on('LOAN_APPROVED', function(event) {
      const payload = event.payload || {};
      if (activeItemData && activeItemData.item && activeItemData.item.item_id == payload.item_id) {
        if (window.Toast && typeof window.Toast.info === 'function') {
          window.Toast.info('Loan Approved', 'This asset was just checked out.');
        }
        lookupBarcode(activeItemData.item.barcode);
      }
    });

    window.RealtimeHub.on('LOAN_RETURNED', function(event) {
      const payload = event.payload || {};
      if (activeItemData && activeItemData.item && activeItemData.item.item_id == payload.item_id) {
        if (window.Toast && typeof window.Toast.success === 'function') {
          window.Toast.success('Asset Returned', 'This asset was just checked back into inventory.');
        }
        lookupBarcode(activeItemData.item.barcode);
      }
    });
  }

})();
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
