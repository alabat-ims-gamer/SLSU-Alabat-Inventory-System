<?php
/**
 * Custodian Damage Incidents & Receipt Verification Console
 * Authoritative Institutional Green Design System (stripe-DESIGN.md)
 * Empowers Property Custodians and Administrators to review student-submitted
 * cashier receipts, verify damage liabilities, and grant official account clearance.
 * 
 * Strict OWASP Compliance: CSRF validation, parameter sanitization, and output encoding.
 */
$title = 'Damage Incidents & Clearances';
$breadcrumb = 'Clearances';

require __DIR__ . '/../layouts/shell.php';
?>

<!-- ── Page Header ────────────────────────────────────────── -->
<div class="section-header" style="margin-bottom:var(--space-xl);">
  <div>
    <h1 class="section-title" style="display:flex;align-items:center;gap:8px;">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary);display:inline-block;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
      Damage Incidents &amp; Clearance Verification
    </h1>
    <div class="section-subtitle">Review student-submitted cashier receipts, verify assessed damage payments, and grant official account clearance.</div>
  </div>
  <div style="display:flex;gap:var(--space-sm);flex-wrap:wrap;align-items:center;">
    <?php if ($role === 'executive_admin'): ?>
      <span class="badge" style="background:var(--color-canvas-soft, #f1f5f9);color:var(--color-ink-mute, #64748b);border:1px solid var(--color-hairline, #e2e8f0);padding:5px 12px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border-radius:20px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        Executive Read-Only View
      </span>
      <a href="<?= url('/clearances/export-xlsx') ?>" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;" title="Export damage incidents and clearance ledger to Microsoft Excel (.xlsx)">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Export Clearances (.xlsx)
      </a>
    <?php else: ?>
      <a href="<?= url('/clearances/export-xlsx') ?>" class="btn btn-secondary btn-sm" title="Export incident and clearance ledger to Microsoft Excel (.xlsx)" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Export Excel (.xlsx)
      </a>
      <a href="<?= url('/scan') ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="M3 9h18"></path><path d="M3 15h18"></path><path d="M9 3v18"></path><path d="M15 3v18"></path></svg>
        Scanner Station
      </a>
      <a href="<?= url('/inventory') ?>" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        Asset Catalog
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- ── Metric Cards ───────────────────────────────────────── -->
<div class="grid-kpi-stats stagger-children">
  <?php
  $props = [
    'title'    => 'Needs Verification',
    'value'    => $pendingVerifCount ?? 0,
    'icon'     => 'clock',
    'color'    => ($pendingVerifCount > 0 ? 'gold' : 'blue'),
    'subtitle' => 'Receipts uploaded by students awaiting your review'
  ];
  require __DIR__ . '/../components/stat-card.php';

  $props = [
    'title'    => 'Awaiting Student Payment',
    'value'    => $awaitingPayCount ?? 0,
    'icon'     => 'alert-triangle',
    'color'    => ($awaitingPayCount > 0 ? 'ruby' : 'green'),
    'subtitle' => 'Damaged returns awaiting student cashier payment'
  ];
  require __DIR__ . '/../components/stat-card.php';

  $props = [
    'title'    => 'Cleared & Settled',
    'value'    => $clearedCount ?? 0,
    'icon'     => 'user-check',
    'color'    => 'green',
    'subtitle' => 'Verified cashier payments with lifted holds'
  ];
  require __DIR__ . '/../components/stat-card.php';

  $props = [
    'title'    => 'Total Incident Log',
    'value'    => $totalCount ?? 0,
    'icon'     => 'clipboard-list',
    'color'    => 'indigo',
    'subtitle' => 'All recorded equipment return damages'
  ];
  require __DIR__ . '/../components/stat-card.php';
  ?>
</div>

<!-- ── Main Incidents Data Panel ───────────────────────────── -->
<div class="data-table-wrap" id="incidents-console-wrap" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;">

  <!-- Toolbar & Filters -->
  <div class="incidents-toolbar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);padding:var(--space-md) var(--space-lg);border-bottom:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
    
    <!-- Status Filter Tabs -->
    <div class="incident-status-tabs">
      <button type="button" class="tab-btn" data-status="pending_verification">
        Needs Verification <span id="cnt-pending" style="margin-left:4px;color:var(--color-gold-deep);font-feature-settings:'tnum';">(<?= (int)($pendingVerifCount ?? 0) ?>)</span>
      </button>
      <button type="button" class="tab-btn" data-status="partially_paid">
        Partially Paid <span id="cnt-partially-paid" style="margin-left:4px;color:var(--color-gold-deep);font-feature-settings:'tnum';">(<?= (int)($partiallyPaidCount ?? 0) ?>)</span>
      </button>
      <button type="button" class="tab-btn" data-status="awaiting_payment">
        Awaiting Payment <span id="cnt-awaiting" style="margin-left:4px;color:var(--color-ruby);font-feature-settings:'tnum';">(<?= (int)($awaitingPayCount ?? 0) ?>)</span>
      </button>
      <button type="button" class="tab-btn" data-status="cleared">
        Cleared Records <span id="cnt-cleared" style="margin-left:4px;color:var(--color-emerald, #059669);font-feature-settings:'tnum';">(<?= (int)($clearedCount ?? 0) ?>)</span>
      </button>
      <button type="button" class="tab-btn active" data-status="">
        All Incidents <span id="cnt-all" style="margin-left:4px;opacity:0.85;font-feature-settings:'tnum';">(<?= (int)($totalCount ?? 0) ?>)</span>
      </button>
    </div>

    <!-- Search Box -->
    <div class="incident-search-wrap" style="position:relative;display:flex;align-items:center;min-width:260px;">
      <input 
        type="text" 
        id="incident-search-input" 
        placeholder="Search borrower, barcode, or receipt #..." 
        class="form-input" 
        style="width:100%;font-size:13px;padding:6px 12px 6px 32px;height:36px;border-radius:var(--radius-md);"
      >
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:10px;color:var(--color-ink-mute);pointer-events:none;">
        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
    </div>
  </div>

  <style>
    /* Category Filter Tabs Responsiveness */
    .incident-status-tabs {
      display: flex;
      align-items: center;
      gap: var(--space-xs);
      flex-wrap: wrap;
    }
    .incident-status-tabs .tab-btn {
      padding: 6px 14px;
      font-size: 12px;
      font-weight: 500;
      border-radius: var(--radius-pill);
      border: 1px solid var(--color-hairline);
      background: var(--color-canvas);
      color: var(--color-ink-secondary);
      cursor: pointer;
      transition: all var(--transition-fast);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      white-space: nowrap;
    }
    .incident-status-tabs .tab-btn:hover {
      background: var(--color-canvas-soft);
      border-color: var(--color-primary);
    }
    .incident-status-tabs .tab-btn.active {
      background: var(--color-primary) !important;
      color: #ffffff !important;
      border-color: var(--color-primary) !important;
    }
    .incident-status-tabs .tab-btn.active span {
      color: rgba(255, 255, 255, 0.9) !important;
    }

    @media (max-width: 768px) {
      .incidents-toolbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: var(--space-md) !important;
      }
      .incident-status-tabs {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
        width: 100% !important;
      }
      .incident-status-tabs .tab-btn {
        width: 100% !important;
        min-height: 38px !important;
        padding: 8px 10px !important;
        font-size: 11.5px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        box-sizing: border-box !important;
      }
      .incident-status-tabs .tab-btn:last-child {
        grid-column: span 2 !important;
      }
      .incident-search-wrap {
        width: 100% !important;
        min-width: 100% !important;
      }
    }

    /* Strict Column Isolation & Text Wrap (Prevents global .data-table nowrap bleed) */
    .incidents-table {
      width: 100% !important;
      min-width: 1140px !important;
      table-layout: fixed !important;
      border-collapse: collapse !important;
      text-align: left !important;
      font-size: 13px !important;
    }
    .incidents-table th,
    .incidents-table td {
      white-space: normal !important;
      word-break: break-word !important;
      overflow-wrap: break-word !important;
      vertical-align: top !important;
      box-sizing: border-box !important;
    }
    .incidents-table td div {
      white-space: normal;
      word-break: break-word;
      overflow-wrap: break-word;
    }
    .incidents-table .cell-nowrap,
    .incidents-table .cell-nowrap div,
    .incidents-table .cell-nowrap span {
      white-space: nowrap !important;
    }
    .incidents-table th {
      padding: 12px 14px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .incidents-table td {
      padding: 12px 14px;
    }
    /* Action buttons: Exact match to Asset Catalog (.btn.btn-ghost.btn-icon-sm) */
    .incidents-table .cell-actions .btn-icon-sm {
      width: 28px !important;
      height: 28px !important;
      padding: 0 !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      border-radius: var(--radius-sm, 6px) !important;
      border: 1px solid var(--color-hairline, rgba(22, 101, 52, 0.18)) !important;
      background: rgba(255, 255, 255, 0.95) !important;
      box-shadow: none !important;
      transition: all var(--transition-fast, 0.15s ease) !important;
      cursor: pointer;
    }
    .incidents-table .cell-actions .btn-icon-sm:hover {
      background: #ffffff !important;
      border-color: var(--color-primary, #166534) !important;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
    }
    .incidents-table .cell-actions .btn-icon-sm svg {
      width: 13px !important;
      height: 13px !important;
      display: inline-block !important;
    }
  </style>

  <!-- Responsive Table with Strict Column Widths & Zero Overlap -->
  <div style="overflow-x:auto;">
    <table class="data-table incidents-table">
      <colgroup>
        <col style="width: 170px;">
        <col style="width: 160px;">
        <col style="width: 240px;">
        <col style="width: 170px;">
        <col style="width: 150px;">
        <col style="width: 150px;">
        <col style="width: 100px;">
      </colgroup>
      <thead>
        <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
          <th>Borrower</th>
          <th>Damaged Asset</th>
          <th>Inspection &amp; Damage</th>
          <th>Assessed Fee &amp; Balance</th>
          <th>Latest Receipt</th>
          <th>Clearance Status</th>
          <th class="cell-nowrap" style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody id="incidents-table-body">
        <tr>
          <td colspan="7" style="text-align:center;padding:48px 16px;color:var(--color-ink-mute);">
            <div style="display:inline-block;animation:spin 1s linear infinite;margin-bottom:8px;">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="10"></circle></svg>
            </div>
            <div>Loading incident records...</div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Pagination / Summary Footer -->
  <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);font-size:12px;color:var(--color-ink-mute);">
    <div id="incidents-page-info">Showing incidents...</div>
    <div style="display:flex;gap:6px;" id="incidents-pagination-btns"></div>
  </div>
</div>

<!-- ── Custodian Review & Verify Modal ────────────────────── -->
<div id="modal-verify-incident" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-panel lg" style="background:#ffffff;border-radius:var(--radius-lg);width:100%;max-width:700px;box-shadow:0 20px 25px -5px rgba(0, 0, 0, 0.2);overflow:hidden;">
    
    <div class="modal-header" style="padding:18px 24px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas-soft);">
      <div>
        <h2 class="modal-title" style="font-size:17px;font-weight:600;color:var(--color-ink);margin:0;">
          Verify Cashier Receipt &amp; Grant Clearance
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" id="verify-modal-subtitle">
          Incident Review
        </div>
      </div>
      <button type="button" onclick="closeVerifyModal()" aria-label="Close" style="background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:4px;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <div class="modal-body" style="padding:20px 24px;max-height:calc(85vh - 130px);overflow-y:auto;">
      
      <!-- Incident & Borrower Card -->
      <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <div style="font-size:11px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">Borrower</div>
          <div style="font-size:14px;font-weight:600;color:var(--color-ink);" id="verify-borrower-name"></div>
          <div style="font-size:12px;color:var(--color-ink-mute);font-family:monospace;" id="verify-borrower-id"></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">Damaged Asset</div>
          <div style="font-size:14px;font-weight:600;color:var(--color-ink);" id="verify-asset-name"></div>
          <div style="font-size:12px;color:var(--color-primary);font-family:monospace;" id="verify-asset-barcode"></div>
        </div>
        <div style="grid-column:span 2;padding-top:8px;border-top:1px dashed var(--color-hairline);">
          <div style="font-size:11px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">Inspection Report</div>
          <div style="font-size:12px;color:var(--color-ink-secondary);margin-top:2px;" id="verify-damage-desc"></div>
        </div>
      </div>

      <!-- Financial Price Comparison Strip (With Balance Tracking) -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:12px;background:#f8fafc;border:1px solid var(--color-hairline);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:16px;">
        <div>
          <div style="font-size:11px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">Assessed Total</div>
          <div style="font-size:18px;font-weight:600;color:var(--color-ink);font-feature-settings:'tnum';" id="verify-assessed-cost">₱0.00</div>
          <div style="font-size:10px;color:var(--color-ink-mute);">Inventory office fee</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">This Receipt</div>
          <div style="font-size:18px;font-weight:600;color:var(--color-primary);font-feature-settings:'tnum';" id="verify-receipt-amount">₱0.00</div>
          <div style="font-size:10px;color:var(--color-ink-mute);">Amount on submitted O.R.</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">Already Credited</div>
          <div style="font-size:18px;font-weight:600;color:#059669;font-feature-settings:'tnum';" id="verify-total-paid">₱0.00</div>
          <div style="font-size:10px;color:var(--color-ink-mute);">Sum of verified payments</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--color-ink-mute);font-weight:600;text-transform:uppercase;">Balance After Approval</div>
          <div style="font-size:18px;font-weight:600;color:var(--color-ruby);font-feature-settings:'tnum';" id="verify-remaining-bal">₱0.00</div>
          <div id="verify-price-badge" style="margin-top:2px;"></div>
        </div>
      </div>

      <!-- Underpayment Triage Options (Visible when payment is less than remaining balance) -->
      <div id="verify-underpay-box" style="display:none;background:#fefce8;border:1px solid #fef08a;border-radius:var(--radius-md);padding:14px 16px;margin-bottom:16px;">
        <div style="font-size:12px;font-weight:700;color:#854d0e;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          Partial Payment Triage Option
        </div>
        <div style="font-size:12px;color:#713f12;line-height:1.4;margin-bottom:10px;" id="verify-underpay-msg">
          The receipt submitted does not cover the full assessed liability.
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:12px;color:var(--color-ink);">
            <input type="radio" name="settlement_mode" value="partial" id="mode-partial" checked onchange="handleSettlementModeChange()" style="margin-top:2px;">
            <div>
              <strong>Verify as Partial Payment (Recommended)</strong>
              <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">
                Credit this payment to the student's liability. The student will owe the remaining balance and clearance hold stays locked.
              </div>
            </div>
          </label>
          <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:12px;color:var(--color-ink);">
            <input type="radio" name="settlement_mode" value="full" id="mode-full" onchange="handleSettlementModeChange()" style="margin-top:2px;">
            <div>
              <strong>Accept as Full Settlement (Waive Remaining Balance)</strong>
              <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">
                Treat this payment as complete settlement of the incident and fully lift the borrower's clearance hold.
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- Payment History Ledger Section (if multi-payment exists) -->
      <div id="verify-payments-ledger-wrap" style="display:none;margin-bottom:16px;">
        <div style="font-size:11px;font-weight:600;color:var(--color-ink-mute);text-transform:uppercase;margin-bottom:6px;">Payment History Ledger</div>
        <div style="max-height:140px;overflow-y:auto;border:1px solid var(--color-hairline);border-radius:var(--radius-md);">
          <table style="width:100%;border-collapse:collapse;font-size:11px;text-align:left;">
            <thead>
              <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                <th style="padding:6px 10px;">O.R. #</th>
                <th style="padding:6px 10px;">Amount</th>
                <th style="padding:6px 10px;">Payment Date</th>
                <th style="padding:6px 10px;">Status</th>
                <th style="padding:6px 10px;">Verified By</th>
              </tr>
            </thead>
            <tbody id="verify-payments-ledger-tbody"></tbody>
          </table>
        </div>
      </div>

      <!-- Receipt Details & Document Viewer -->
      <div style="margin-bottom:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <div style="font-size:12px;font-weight:600;color:var(--color-ink);">
            Official Receipt: <span id="verify-receipt-number" style="font-family:monospace;font-weight:700;"></span>
            &bull; Date: <span id="verify-receipt-date" style="font-weight:500;"></span>
          </div>
          <a href="#" id="verify-doc-external-link" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-xs" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--color-primary);padding:2px 8px;border:1px solid var(--color-hairline);border-radius:4px;">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            Open Original File
          </a>
        </div>

        <div id="verify-student-notes-wrap" style="display:none;background:#f8fafc;padding:8px 12px;border-radius:6px;font-size:12px;color:var(--color-ink-secondary);margin-bottom:10px;">
          <strong>Student Remarks:</strong> <span id="verify-student-notes"></span>
        </div>

        <!-- Embedded Viewer Container -->
        <div id="verify-doc-viewer" style="border:1px solid var(--color-hairline);border-radius:var(--radius-md);overflow:hidden;background:#0f291e;min-height:220px;max-height:360px;display:flex;align-items:center;justify-content:center;">
          <img id="verify-doc-img" src="" alt="Receipt Document" style="max-width:100%;max-height:360px;object-fit:contain;display:none;">
          <iframe id="verify-doc-pdf" src="" style="width:100%;height:360px;border:none;display:none;"></iframe>
          <div id="verify-doc-loading" style="color:#ffffff;font-size:12px;">Loading document preview...</div>
        </div>
      </div>

      <!-- Custodian Audit Notes -->
      <div class="form-group" style="margin-bottom:14px;">
        <label class="form-label" for="verify-settlement-notes" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--color-ink);">
          Clearance Verification Remarks / Audit Notes
        </label>
        <textarea 
          id="verify-settlement-notes" 
          class="form-input" 
          rows="2" 
          placeholder="e.g. Verified with accounting cashier log. OR confirmed valid..." 
          style="width:100%;font-size:13px;resize:vertical;"
        ></textarea>
      </div>

    </div>

    <!-- Modal Footer Actions -->
    <div class="modal-footer" style="padding:14px 24px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeVerifyModal()" style="border-radius:9999px;">Cancel</button>
      
      <div style="display:flex;align-items:center;gap:8px;">
        <button 
          type="button" 
          id="btn-open-reject-modal" 
          class="btn btn-sm" 
          style="background:#ffffff;border:1px solid var(--color-ruby);color:var(--color-ruby);border-radius:9999px;padding:6px 14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;"
          onclick="openRejectModal()"
        >
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
          Reject Receipt
        </button>

        <button 
          type="button" 
          id="btn-confirm-verify" 
          class="btn btn-primary btn-sm" 
          style="background:#166534;border-color:#166534;border-radius:9999px;padding:7px 20px;font-weight:600;display:inline-flex;align-items:center;gap:6px;"
          onclick="submitVerificationApproval()"
        >
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <span id="btn-confirm-verify-text">Verify &amp; Grant Clearance</span>
        </button>
      </div>
    </div>

  </div>
</div>

<!-- ── Custodian Edit Assessed Fee Modal ───────────────────── -->
<div id="modal-edit-fee" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-panel sm" style="background:#ffffff;border-radius:var(--radius-lg);width:100%;max-width:440px;box-shadow:0 20px 25px -5px rgba(0, 0, 0, 0.2);overflow:hidden;">
    
    <div class="modal-header" style="padding:16px 20px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas-soft);">
      <div>
        <h2 class="modal-title" style="font-size:16px;font-weight:600;color:var(--color-ink);margin:0;">
          Update Assessed Damage Fee
        </h2>
        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;" id="edit-fee-subtitle">
          Adjust required cashier payment
        </div>
      </div>
      <button type="button" onclick="closeEditFeeModal()" aria-label="Close" style="background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:4px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <form id="form-edit-fee" onsubmit="handleUpdateFeeSubmit(event)">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" id="edit-fee-incident-id" name="incident_id" value="">

      <div class="modal-body" style="padding:18px 20px;">
        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label" for="input-new-fee" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--color-ink);">
            Assessed Damage / Repair Fee (₱) <span style="color:var(--color-ruby);">*</span>
          </label>
          <div style="position:relative;">
            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-weight:600;color:var(--color-ink-mute);font-size:13px;">₱</span>
            <input 
              type="number" 
              step="0.01" 
              min="0" 
              id="input-new-fee" 
              name="estimated_cost" 
              required 
              class="form-input" 
              style="width:100%;font-size:14px;padding-left:26px;font-family:monospace;font-weight:600;"
            >
          </div>
          <div style="font-size:11px;color:var(--color-ink-mute);margin-top:5px;">
            The borrower will be notified of this updated fee on their My Clearances page.
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding:12px 20px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:flex-end;gap:8px;">
        <button type="button" class="btn btn-ghost btn-sm" onclick="closeEditFeeModal()" style="border-radius:9999px;">Cancel</button>
        <button type="submit" id="btn-save-fee" class="btn btn-primary btn-sm" style="border-radius:9999px;padding:6px 18px;font-weight:600;">
          Save Updated Fee
        </button>
      </div>
    </form>

  </div>
</div>

<!-- ── Custodian Reject Receipt Modal ──────────────────────── -->
<div id="modal-reject-receipt" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-reject-receipt-title">
  <div class="modal-panel sm" style="background:#ffffff;border-radius:var(--radius-lg);width:100%;max-width:480px;box-shadow:0 20px 25px -5px rgba(0, 0, 0, 0.2);overflow:hidden;">
    
    <div class="modal-header" style="padding:16px 20px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas-soft);">
      <div>
        <h2 class="modal-title" id="modal-reject-receipt-title" style="font-size:16px;font-weight:600;color:var(--color-ruby);margin:0;display:flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
          Reject Official Receipt
        </h2>
        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;" id="reject-receipt-subtitle">
          Specify reason to notify student
        </div>
      </div>
      <button type="button" onclick="closeRejectModal()" aria-label="Close" style="background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:4px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <form id="form-reject-receipt" onsubmit="handleRejectReceiptSubmit(event)">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" id="reject-receipt-incident-id" name="incident_id" value="">

      <div class="modal-body" style="padding:18px 20px;">
        <!-- Alert Notice Banner -->
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-md);padding:10px 12px;margin-bottom:14px;font-size:12px;color:#991b1b;line-height:1.45;">
          <strong>Clearance hold will remain active.</strong> The student will be notified of this rejection and required to upload valid payment proof.
        </div>

        <!-- Quick Reason Suggestions -->
        <div style="margin-bottom:12px;">
          <div style="font-size:11px;font-weight:600;color:var(--color-ink-mute);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Common Reasons (click to insert):</div>
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <button type="button" class="btn btn-ghost btn-xs" onclick="setRejectReason('Unreadable or blurry receipt image. Please provide a clear, high-resolution photo.')" style="font-size:11px;padding:3px 8px;border-radius:12px;">📷 Blurry photo</button>
            <button type="button" class="btn btn-ghost btn-xs" onclick="setRejectReason('Paid amount does not match the assessed damage fee.')" style="font-size:11px;padding:3px 8px;border-radius:12px;">💰 Amount mismatch</button>
            <button type="button" class="btn btn-ghost btn-xs" onclick="setRejectReason('Invalid official receipt number or receipt not found in cashier log.')" style="font-size:11px;padding:3px 8px;border-radius:12px;">🧾 Invalid OR #</button>
            <button type="button" class="btn btn-ghost btn-xs" onclick="setRejectReason('Receipt document appears altered or does not pertain to this damage incident.')" style="font-size:11px;padding:3px 8px;border-radius:12px;">⚠️ Unverified</button>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:6px;">
          <label class="form-label" for="reject-modal-reason" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--color-ink);">
            Reason for Rejection <span style="color:var(--color-ruby);">*</span>
          </label>
          <textarea 
            id="reject-modal-reason" 
            name="rejection_reason" 
            required 
            rows="3" 
            class="form-input" 
            placeholder="Explain specifically why this receipt is rejected so the student knows what to correct..." 
            style="width:100%;font-size:13px;resize:vertical;"
          ></textarea>
          <div style="font-size:11px;color:var(--color-ink-mute);margin-top:5px;">
            This explanation is permanently logged and displayed on the student's My Clearances page.
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding:12px 20px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:flex-end;gap:8px;">
        <button type="button" class="btn btn-ghost btn-sm" onclick="closeRejectModal()" style="border-radius:9999px;">Cancel</button>
        <button type="submit" id="btn-submit-reject" class="btn btn-sm" style="background:var(--color-ruby);color:#ffffff;border-color:var(--color-ruby);border-radius:9999px;padding:6px 18px;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
          <span id="btn-submit-reject-text">Confirm Rejection</span>
        </button>
      </div>
    </form>

  </div>
</div>

<style>
.tab-btn.active {
  background: var(--color-primary) !important;
  color: #ffffff !important;
  border-color: var(--color-primary) !important;
}
@keyframes modalFadeIn {
  from { opacity: 0; transform: scale(0.97); }
  to { opacity: 1; transform: scale(1); }
}
</style>

<!-- ── Clearances Console Interactive Client ────────────────── -->
<script>
let currentIncidentStatus = '';
let currentIncidentSearch = '';
let currentIncidentPage = 1;
let activeVerifyingIncident = null;
const isExecutive = <?= json_encode($role === 'executive_admin') ?>;

document.addEventListener('DOMContentLoaded', () => {
  loadIncidents();

  // Tab Filtering
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
        b.style.background = '';
        b.style.color = '';
        b.style.borderColor = '';
      });
      btn.classList.add('active');
      btn.style.background = '';
      btn.style.color = '';
      btn.style.borderColor = '';

      currentIncidentStatus = btn.dataset.status;
      currentIncidentPage = 1;
      loadIncidents();
    });
  });

  // Search Debounce
  let searchTimer;
  const searchInput = document.getElementById('incident-search-input');
  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      currentIncidentSearch = searchInput.value.trim();
      currentIncidentPage = 1;
      loadIncidents();
    }, 300);
  });
});

function loadIncidents() {
  const tbody = document.getElementById('incidents-table-body');
  tbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
        <div style="display:inline-block;animation:spin 1s linear infinite;margin-bottom:8px;">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="10"></circle></svg>
        </div>
        <div>Refreshing incident list...</div>
      </td>
    </tr>
  `;

  const params = new URLSearchParams({
    status: currentIncidentStatus,
    search: currentIncidentSearch,
    page: currentIncidentPage,
    limit: 15
  });

  fetch(window.appUrl(`/api/incident/list?${params.toString()}`))
    .then(res => res.json())
    .then(res => {
      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--color-ruby);">${escapeHtml(res.message || 'Error loading incidents')}</td></tr>`;
        return;
      }

      // Update Tab Counter Badges if returned
      if (res.counts) {
        const pEl = document.getElementById('cnt-pending');
        const partEl = document.getElementById('cnt-partially-paid');
        const aEl = document.getElementById('cnt-awaiting');
        const cEl = document.getElementById('cnt-cleared');
        const allEl = document.getElementById('cnt-all');
        if (pEl) pEl.textContent = `(${res.counts.pending_verification || 0})`;
        if (partEl) partEl.textContent = `(${res.counts.partially_paid || 0})`;
        if (aEl) aEl.textContent = `(${res.counts.awaiting_payment || 0})`;
        if (cEl) cEl.textContent = `(${res.counts.cleared || 0})`;
        if (allEl) allEl.textContent = `(${res.counts.total || 0})`;
      }

      renderIncidentsTable(res.data || [], res.total_count, res.page, res.limit);
    })
    .catch(() => {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--color-ruby);">Failed to connect to incident server.</td></tr>`;
    });
}

let currentIncidentsMap = {};

function renderIncidentsTable(items, totalCount, page, limit) {
  const tbody = document.getElementById('incidents-table-body');
  if (items.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;padding:48px 16px;color:var(--color-ink-mute);">
          <div style="font-size:28px;margin-bottom:8px;">🛡️</div>
          <strong style="color:var(--color-ink);font-size:14px;display:block;">No Damage Incidents in this View</strong>
          <span style="font-size:12px;">All equipment returns and clearances are in good order.</span>
        </td>
      </tr>
    `;
    document.getElementById('incidents-page-info').textContent = 'Showing 0 of 0 records';
    document.getElementById('incidents-pagination-btns').innerHTML = '';
    return;
  }

  currentIncidentsMap = {};
  let html = '';
  items.forEach(inc => {
    currentIncidentsMap[inc.incident_id] = inc;

    const isCleared = inc.incident_status === 'cleared';
    const isPendingVerif = inc.incident_status === 'pending_verification';
    const isPartiallyPaid = inc.incident_status === 'partially_paid';
    const isRejected = inc.incident_status === 'rejected';

    let statusBadge = '';
    if (isCleared) {
      statusBadge = `<span class="badge badge-approved" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:20px;">✓ Cleared</span>`;
    } else if (isPendingVerif) {
      statusBadge = `<span class="badge badge-pending" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:20px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;">⏳ Needs Verification</span>`;
    } else if (isPartiallyPaid) {
      statusBadge = `<span class="badge badge-pending" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:20px;background:#fef3c7;color:#b45309;border:1px solid #fde68a;">🪙 Partially Paid</span>`;
    } else if (isRejected) {
      statusBadge = `<span class="badge badge-rejected" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:20px;">✗ Receipt Rejected</span>`;
    } else {
      statusBadge = `<span class="badge badge-rejected" style="padding:4px 10px;font-size:11px;font-weight:600;border-radius:20px;">⚠️ Awaiting Payment</span>`;
    }

    const conditionBadge = inc.damage_condition === 'damaged'
      ? `<span style="font-size:11px;font-weight:600;color:var(--color-gold-deep);background:rgba(217,119,6,0.1);padding:2px 6px;border-radius:4px;">Damaged</span>`
      : `<span style="font-size:11px;font-weight:600;color:var(--color-ruby);background:rgba(220,38,38,0.1);padding:2px 6px;border-radius:4px;">Not Working</span>`;

    const returnDate = inc.actual_return_date ? new Date(inc.actual_return_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';

    const assessedCost = inc.estimated_cost !== null && !isNaN(inc.estimated_cost) ? Number(inc.estimated_cost) : 0;
    const amountPaid   = inc.amount_paid !== null && !isNaN(inc.amount_paid) ? Number(inc.amount_paid) : 0;
    const remainingBal = inc.remaining_balance !== null && !isNaN(inc.remaining_balance) 
      ? Number(inc.remaining_balance) 
      : Math.max(0, assessedCost - amountPaid);

    let balanceTag = '';
    if (isCleared) {
      balanceTag = `<span style="display:inline-block;margin-top:3px;font-size:11px;font-weight:600;color:#059669;background:#d1fae5;padding:1px 6px;border-radius:10px;">✓ Settled in Full</span>`;
    } else if (isPartiallyPaid || (remainingBal > 0.009 && amountPaid > 0)) {
      balanceTag = `<span style="display:inline-block;margin-top:3px;font-size:11px;font-weight:600;color:#b45309;background:#fef3c7;padding:1px 6px;border-radius:10px;">⚠️ Due: ₱${remainingBal.toFixed(2)}</span>`;
    } else if (remainingBal <= 0.009 && amountPaid > 0) {
      balanceTag = `<span style="display:inline-block;margin-top:3px;font-size:11px;font-weight:600;color:#059669;background:#d1fae5;padding:1px 6px;border-radius:10px;">✓ Exact Match</span>`;
    } else {
      balanceTag = `<span style="display:inline-block;margin-top:3px;font-size:11px;font-weight:500;color:var(--color-ruby);background:rgba(220,38,38,0.08);padding:1px 6px;border-radius:10px;">Unpaid: ₱${assessedCost.toFixed(2)}</span>`;
    }

    html += `
      <tr style="border-bottom:1px solid var(--color-hairline);transition:background 0.15s ease;" onmouseover="this.style.background='var(--color-canvas-soft)'" onmouseout="this.style.background='transparent'">
        <td style="padding:12px 14px;vertical-align:top;white-space:normal;overflow-wrap:break-word;word-break:break-word;">
          <div style="font-weight:600;color:var(--color-ink);">${escapeHtml(inc.borrower_name)}</div>
          <div style="font-size:11px;color:var(--color-ink-mute);font-family:monospace;">${escapeHtml(inc.borrower_id_number)} &bull; <span style="text-transform:capitalize;">${escapeHtml(inc.borrower_role)}</span></div>
          <div style="font-size:11px;color:var(--color-ink-mute);">${escapeHtml(inc.borrower_department || '')}</div>
        </td>
        <td style="padding:12px 14px;vertical-align:top;white-space:normal;overflow-wrap:break-word;word-break:break-word;">
          <div style="font-weight:500;color:var(--color-ink);">${escapeHtml(inc.item_name)}</div>
          <div style="font-size:11px;color:var(--color-primary);font-family:monospace;">${escapeHtml(inc.barcode)}</div>
          ${inc.property_number ? `<div style="font-size:10px;color:var(--color-ink-mute);font-family:monospace;">SN: ${escapeHtml(inc.property_number)}</div>` : ''}
        </td>
        <td style="padding:12px 14px;vertical-align:top;white-space:normal;overflow-wrap:break-word;word-break:break-word;">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
            <span style="color:var(--color-ink);font-size:11px;font-weight:500;">${returnDate}</span>
            ${conditionBadge}
          </div>
          <div style="color:var(--color-ink-secondary);font-size:12px;line-height:1.45;white-space:normal;overflow-wrap:break-word;word-break:break-word;">
            ${escapeHtml(inc.damage_description || 'No detailed remarks recorded.')}
          </div>
        </td>
        <td style="padding:12px 14px;vertical-align:top;white-space:normal;overflow-wrap:break-word;">
          <div style="font-size:12px;color:var(--color-ink-mute);white-space:nowrap;">Assessed: <strong style="color:var(--color-ink);font-family:monospace;font-size:13px;">₱${assessedCost.toFixed(2)}</strong></div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;white-space:nowrap;">Paid: <strong style="color:var(--color-primary);font-family:monospace;font-size:13px;">₱${amountPaid.toFixed(2)}</strong></div>
          ${balanceTag}
        </td>
        <td style="padding:12px 14px;vertical-align:top;white-space:normal;overflow-wrap:break-word;">
          ${inc.receipt_number ? `
            <div style="font-weight:600;font-family:monospace;font-size:12px;color:var(--color-ink);white-space:nowrap;">O.R. #${escapeHtml(inc.receipt_number)}</div>
            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;white-space:nowrap;">${escapeHtml(inc.payment_date || '')}</div>
            ${inc.clearance_document_path ? `
              <div style="margin-top:4px;">
                <a href="${window.appUrl('/clearances/document?id=' + inc.incident_id)}" target="_blank" rel="noopener noreferrer" style="font-size:11px;color:var(--color-primary);text-decoration:none;display:inline-flex;align-items:center;gap:3px;font-weight:600;white-space:nowrap;">
                  <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  Proof Slip
                </a>
              </div>
            ` : ''}
          ` : `<span style="font-size:11px;color:var(--color-ink-mute);white-space:nowrap;">No receipt submitted</span>`}
        </td>
        <td style="padding:12px 14px;vertical-align:top;white-space:normal;overflow-wrap:break-word;">
          ${statusBadge}
          ${inc.cleared_by_name ? `<div style="font-size:10px;color:var(--color-ink-mute);margin-top:3px;white-space:nowrap;">Cleared by ${escapeHtml(inc.cleared_by_name)}</div>` : ''}
          ${isRejected && inc.rejection_reason ? `<div style="font-size:10px;color:var(--color-ruby);margin-top:3px;line-height:1.2;white-space:normal;overflow-wrap:break-word;word-break:break-word;">Reason: ${escapeHtml(inc.rejection_reason)}</div>` : ''}
        </td>
        <td class="cell-actions cell-nowrap" style="padding:12px 14px;text-align:right;vertical-align:top;white-space:nowrap;">
          ${!isExecutive ? `
            <div style="display:inline-flex;align-items:center;justify-content:flex-end;gap:4px;">
              ${isPendingVerif ? `
                <button type="button" class="btn btn-ghost btn-icon-sm btn-verify-action" title="Review Receipt & Verify Payment" data-incident-id="${inc.incident_id}" style="color:#059669;" aria-label="Review Receipt & Verify Payment">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </button>
              ` : ''}
              ${!isCleared ? `
                <button type="button" class="btn btn-ghost btn-icon-sm btn-edit-fee-action" title="Edit Assessed Damage Fee" data-incident-id="${inc.incident_id}" aria-label="Edit Assessed Fee">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
              ` : ''}
              ${inc.clearance_document_path ? `
                <a href="${window.appUrl('/clearances/document?id=' + inc.incident_id)}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-icon-sm" title="View Official Receipt Slip" aria-label="View Receipt Slip">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                </a>
              ` : ''}
            </div>
          ` : `
            <span class="badge" style="background:var(--color-canvas-soft, #f8fafc);color:var(--color-ink-mute, #64748b);border:1px solid var(--color-hairline, #e2e8f0);padding:3px 8px;font-size:11px;font-weight:500;border-radius:4px;">Read-Only</span>
          `}
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;

  // Attach event handlers to action buttons
  document.querySelectorAll('.btn-verify-action').forEach(btn => {
    btn.addEventListener('click', function() {
      const inc = currentIncidentsMap[this.getAttribute('data-incident-id')];
      if (inc) openVerifyModal(inc);
    });
  });

  document.querySelectorAll('.btn-edit-fee-action').forEach(btn => {
    btn.addEventListener('click', function() {
      const inc = currentIncidentsMap[this.getAttribute('data-incident-id')];
      if (inc) openEditFeeModal(inc);
    });
  });

  // Pagination info
  const start = (page - 1) * limit + 1;
  const end = Math.min(start + items.length - 1, totalCount);
  document.getElementById('incidents-page-info').textContent = `Showing ${start}-${end} of ${totalCount} incidents`;

  // Pagination buttons
  const totalPages = Math.ceil(totalCount / limit);
  let pBtns = '';
  if (page > 1) {
    pBtns += `<button type="button" class="btn btn-ghost btn-xs" style="padding:4px 8px;" onclick="goToPage(${page - 1})">Prev</button>`;
  }
  if (page < totalPages) {
    pBtns += `<button type="button" class="btn btn-ghost btn-xs" style="padding:4px 8px;" onclick="goToPage(${page + 1})">Next</button>`;
  }
  document.getElementById('incidents-pagination-btns').innerHTML = pBtns;
}

function goToPage(p) {
  currentIncidentPage = p;
  loadIncidents();
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// ── Verify Receipt Modal Operations ─────────────────────────
function openVerifyModal(incident) {
  if (!incident || incident.incident_status !== 'pending_verification') {
    if (window.Toast) {
      window.Toast.warning('Cannot Review', 'This incident does not have a receipt pending verification.');
    } else if (window.alertDialog) {
      window.alertDialog('This incident does not have a receipt pending verification.', 'Action Not Allowed');
    }
    return;
  }

  activeVerifyingIncident = incident;
  
  document.getElementById('verify-modal-subtitle').textContent = `Reviewing Incident #${incident.incident_id} — ${incident.item_name}`;
  document.getElementById('verify-borrower-name').textContent = incident.borrower_name;
  document.getElementById('verify-borrower-id').textContent = `${incident.borrower_id_number} (${incident.borrower_role})`;
  document.getElementById('verify-asset-name').textContent = incident.item_name;
  document.getElementById('verify-asset-barcode').textContent = incident.barcode;
  document.getElementById('verify-damage-desc').textContent = incident.damage_description || 'No detailed damage description.';
  
  const assessed = incident.estimated_cost !== null && !isNaN(incident.estimated_cost) ? Number(incident.estimated_cost) : 0;
  const verifiedPaid = incident.amount_paid !== null && !isNaN(incident.amount_paid) ? Number(incident.amount_paid) : 0;
  const remaining = incident.remaining_balance !== null && !isNaN(incident.remaining_balance) 
    ? Number(incident.remaining_balance) 
    : Math.max(0, assessed - verifiedPaid);
  
  document.getElementById('verify-assessed-cost').textContent = '₱' + assessed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  document.getElementById('verify-receipt-amount').textContent = 'Loading...';
  document.getElementById('verify-total-paid').textContent = '₱' + verifiedPaid.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  document.getElementById('verify-remaining-bal').textContent = '₱' + remaining.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  
  const badgeEl = document.getElementById('verify-price-badge');
  const underpayBox = document.getElementById('verify-underpay-box');

  if (remaining <= 0.009) {
    badgeEl.innerHTML = `<span style="font-size:10px;font-weight:600;color:#059669;background:#d1fae5;padding:2px 6px;border-radius:10px;">✓ Settled in Full</span>`;
    underpayBox.style.display = 'none';
    document.getElementById('btn-confirm-verify-text').textContent = 'Verify & Grant Clearance';
  } else {
    badgeEl.innerHTML = `<span style="font-size:10px;font-weight:600;color:#b91c1c;background:#fee2e2;padding:2px 6px;border-radius:10px;">⚠️ Bal Due: ₱${remaining.toFixed(2)}</span>`;
    underpayBox.style.display = 'block';
    document.getElementById('verify-underpay-msg').textContent = `Total payments will be less than the assessed fee (₱${assessed.toFixed(2)}). An unpaid balance of ₱${remaining.toFixed(2)} will remain.`;
    document.getElementById('mode-partial').checked = true;
    handleSettlementModeChange();
  }

  document.getElementById('verify-receipt-number').textContent = incident.receipt_number || 'N/A';
  document.getElementById('verify-receipt-date').textContent = incident.payment_date || 'N/A';
  
  const notesWrap = document.getElementById('verify-student-notes-wrap');
  if (incident.student_notes) {
    notesWrap.style.display = 'block';
    document.getElementById('verify-student-notes').textContent = incident.student_notes;
  } else {
    notesWrap.style.display = 'none';
  }

  // Load payment history ledger
  loadIncidentPaymentsLedger(incident.incident_id);

  // Load document preview
  const docUrl = window.appUrl('/clearances/document?id=' + incident.incident_id);
  document.getElementById('verify-doc-external-link').href = docUrl;

  const imgEl = document.getElementById('verify-doc-img');
  const pdfEl = document.getElementById('verify-doc-pdf');
  const loadingEl = document.getElementById('verify-doc-loading');

  imgEl.style.display = 'none';
  pdfEl.style.display = 'none';
  loadingEl.style.display = 'block';
  loadingEl.textContent = 'Loading receipt preview...';

  const docPath = (incident.clearance_document_path || '').toLowerCase();
  if (docPath.endsWith('.pdf')) {
    pdfEl.src = docUrl;
    pdfEl.style.display = 'block';
    loadingEl.style.display = 'none';
  } else {
    imgEl.onload = () => {
      loadingEl.style.display = 'none';
      imgEl.style.display = 'block';
    };
    imgEl.onerror = () => {
      loadingEl.textContent = 'Preview unavailable. Please click "Open Original File" above.';
    };
    imgEl.src = docUrl;
  }

  // Reset inputs safely
  const notesInput = document.getElementById('verify-settlement-notes');
  if (notesInput) notesInput.value = '';

  if (window.Modal && typeof window.Modal.open === 'function') {
    window.Modal.open('modal-verify-incident');
  } else {
    const m = document.getElementById('modal-verify-incident');
    if (m) {
      if (m.parentElement !== document.body) document.body.appendChild(m);
      m.classList.add('open');
      m.style.display = 'flex';
    }
  }
}

function handleSettlementModeChange() {
  const isFull = document.getElementById('mode-full')?.checked;
  const btnText = document.getElementById('btn-confirm-verify-text');
  if (btnText) {
    btnText.textContent = isFull ? 'Accept as Full Settlement & Clear Hold' : 'Verify Partial Payment & Keep Hold';
  }
}

function loadIncidentPaymentsLedger(incidentId) {
  const wrap = document.getElementById('verify-payments-ledger-wrap');
  const tbody = document.getElementById('verify-payments-ledger-tbody');
  if (!wrap || !tbody) return;

  fetch(window.appUrl('/api/incident/payments?incident_id=' + incidentId))
    .then(res => res.json())
    .then(res => {
      if (res.success && Array.isArray(res.payments) && res.payments.length > 0) {
        let pRows = '';
        let alreadyVerified = 0;
        let pendingAmt = 0;

        res.payments.forEach(p => {
          const amt = Number(p.amount_paid) || 0;
          if (p.payment_status === 'verified') {
            alreadyVerified += amt;
          } else if (p.payment_status === 'pending') {
            pendingAmt += amt;
          }

          const stBadge = p.payment_status === 'verified'
            ? `<span style="color:#059669;font-weight:600;">✓ Verified</span>`
            : (p.payment_status === 'rejected' ? `<span style="color:var(--color-ruby);font-weight:600;">✗ Rejected</span>` : `<span style="color:#b45309;font-weight:600;">⏳ Pending</span>`);

          pRows += `
            <tr style="border-bottom:1px solid var(--color-hairline);">
              <td style="padding:6px 10px;font-family:monospace;font-weight:600;">${escapeHtml(p.receipt_number)}</td>
              <td style="padding:6px 10px;font-family:monospace;color:var(--color-primary);font-weight:600;">₱${amt.toFixed(2)}</td>
              <td style="padding:6px 10px;color:var(--color-ink-mute);">${escapeHtml(p.payment_date || '')}</td>
              <td style="padding:6px 10px;">${stBadge}</td>
              <td style="padding:6px 10px;color:var(--color-ink-mute);">${escapeHtml(p.verified_by_name || '—')}</td>
            </tr>
          `;
        });
        tbody.innerHTML = pRows;
        wrap.style.display = 'block';

        // Recalculate and update financial strip with real ledger figures
        if (activeVerifyingIncident) {
          const assessed = activeVerifyingIncident.estimated_cost !== null && !isNaN(activeVerifyingIncident.estimated_cost) ? Number(activeVerifyingIncident.estimated_cost) : 0;
          const reviewAmt = pendingAmt > 0 ? pendingAmt : (Number(activeVerifyingIncident.amount_paid) || 0);
          const projectedPaid = alreadyVerified + reviewAmt;
          const projectedRemaining = Math.max(0, assessed - projectedPaid);

          document.getElementById('verify-receipt-amount').textContent = '₱' + reviewAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          document.getElementById('verify-total-paid').textContent = '₱' + alreadyVerified.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          document.getElementById('verify-remaining-bal').textContent = '₱' + projectedRemaining.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

          const badgeEl = document.getElementById('verify-price-badge');
          const underpayBox = document.getElementById('verify-underpay-box');

          if (projectedRemaining <= 0.009) {
            badgeEl.innerHTML = `<span style="font-size:10px;font-weight:600;color:#059669;background:#d1fae5;padding:2px 6px;border-radius:10px;">✓ Settled in Full</span>`;
            underpayBox.style.display = 'none';
            document.getElementById('btn-confirm-verify-text').textContent = 'Verify & Grant Clearance';
          } else {
            badgeEl.innerHTML = `<span style="font-size:10px;font-weight:600;color:#b91c1c;background:#fee2e2;padding:2px 6px;border-radius:10px;">⚠️ Bal Due: ₱${projectedRemaining.toFixed(2)}</span>`;
            underpayBox.style.display = 'block';
            document.getElementById('verify-underpay-msg').textContent = `Total payments (₱${projectedPaid.toFixed(2)}) will be less than the assessed fee (₱${assessed.toFixed(2)}). An unpaid balance of ₱${projectedRemaining.toFixed(2)} will remain.`;
            document.getElementById('mode-partial').checked = true;
            handleSettlementModeChange();
          }
        }
      } else {
        wrap.style.display = 'none';
        const recAmtEl = document.getElementById('verify-receipt-amount');
        if (recAmtEl && recAmtEl.textContent === 'Loading...') {
          recAmtEl.textContent = '₱0.00';
        }
      }
    })
    .catch(() => {
      wrap.style.display = 'none';
      const recAmtEl = document.getElementById('verify-receipt-amount');
      if (recAmtEl && recAmtEl.textContent === 'Loading...') {
        recAmtEl.textContent = '₱0.00';
      }
    });
}

function closeVerifyModal() {
  if (window.Modal && typeof window.Modal.close === 'function') {
    window.Modal.close('modal-verify-incident');
  } else {
    const m = document.getElementById('modal-verify-incident');
    if (m) {
      m.classList.remove('open');
      m.style.display = 'none';
    }
  }
  activeVerifyingIncident = null;
}

function submitVerificationApproval() {
  if (!activeVerifyingIncident) return;
  
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const notes = document.getElementById('verify-settlement-notes').value.trim();

  // Check if underpay box is active and whether custodian chose accept as full
  const underpayBox = document.getElementById('verify-underpay-box');
  const isUnderpayVisible = underpayBox && underpayBox.style.display !== 'none';
  const acceptAsFull = isUnderpayVisible && document.getElementById('mode-full')?.checked;

  const promptMsg = acceptAsFull
    ? `Accept total payments as full settlement and grant clearance for ${escapeHtml(activeVerifyingIncident.borrower_name)}? Any remaining fee will be resolved.`
    : (isUnderpayVisible
        ? `Record and verify partial payment for ${escapeHtml(activeVerifyingIncident.borrower_name)}? Student will remain on hold until the balance is settled.`
        : `Confirm verification of official receipt for ${escapeHtml(activeVerifyingIncident.borrower_name)} and grant immediate clearance?`);

  const confirmTitle = acceptAsFull ? 'Accept as Full Settlement' : (isUnderpayVisible ? 'Verify Installment Payment' : 'Confirm Clearance Verification');
  const confirmBtnText = acceptAsFull ? 'Accept & Settle' : (isUnderpayVisible ? 'Record Partial Payment' : 'Verify & Grant Clearance');

  window.confirmAction(
    promptMsg,
    function() {
      executeVerification(csrf, notes, acceptAsFull);
    },
    confirmTitle,
    confirmBtnText,
    false
  );
}

function executeVerification(csrf, notes, acceptAsFull) {
  const btn = document.getElementById('btn-confirm-verify');
  const origText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = 'Verifying...';

  const formData = new FormData();
  formData.append('csrf_token', csrf);
  formData.append('incident_id', activeVerifyingIncident.incident_id);
  formData.append('settlement_notes', notes);
  if (acceptAsFull) {
    formData.append('accept_as_full', '1');
  }

  fetch(window.appUrl('/api/incident/verify'), {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(res => {
    btn.disabled = false;
    btn.innerHTML = origText;

    if (res.success) {
      closeVerifyModal();
      if (window.Toast) {
        window.Toast.success('Payment Verified', res.message);
      }
      loadIncidents();
    } else {
      if (window.Toast) {
        window.Toast.error('Verification Error', res.message || 'Error granting clearance.');
      }
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = origText;
    if (window.Toast) {
      window.Toast.error('Network Error', 'Failed to connect to verification server.');
    }
  });
}

// ── Reject Receipt Modal Operations ─────────────────────────
function openRejectModal() {
  if (!activeVerifyingIncident) return;

  document.getElementById('reject-receipt-incident-id').value = activeVerifyingIncident.incident_id;
  document.getElementById('reject-receipt-subtitle').textContent = 
    `${activeVerifyingIncident.borrower_name} • ${activeVerifyingIncident.item_name} (${activeVerifyingIncident.barcode})`;
  
  const reasonTextarea = document.getElementById('reject-modal-reason');
  reasonTextarea.value = '';
  reasonTextarea.style.borderColor = '';

  if (window.Modal && typeof window.Modal.open === 'function') {
    window.Modal.open('modal-reject-receipt');
  } else {
    const m = document.getElementById('modal-reject-receipt');
    if (m) {
      if (m.parentElement !== document.body) document.body.appendChild(m);
      m.classList.add('open');
      m.style.display = 'flex';
    }
  }

  setTimeout(() => {
    reasonTextarea.focus();
  }, 150);
}

function closeRejectModal() {
  if (window.Modal && typeof window.Modal.close === 'function') {
    window.Modal.close('modal-reject-receipt');
  } else {
    const m = document.getElementById('modal-reject-receipt');
    if (m) {
      m.classList.remove('open');
      m.style.display = 'none';
    }
  }
}

function setRejectReason(text) {
  const t = document.getElementById('reject-modal-reason');
  if (t) {
    t.value = text;
    t.style.borderColor = '';
    t.focus();
  }
}

function handleRejectReceiptSubmit(e) {
  e.preventDefault();
  if (!activeVerifyingIncident) return;

  const reasonInput = document.getElementById('reject-modal-reason');
  const reason = reasonInput.value.trim();
  if (reason === '') {
    reasonInput.style.borderColor = 'var(--color-ruby)';
    reasonInput.focus();
    if (window.Toast) {
      window.Toast.warning('Rejection Reason Required', 'Please enter a specific reason for rejecting the receipt.');
    } else if (window.alertDialog) {
      window.alertDialog('Please enter a specific reason for rejecting the receipt.', 'Rejection Reason Required');
    }
    return;
  }
  reasonInput.style.borderColor = '';

  const btn = document.getElementById('btn-submit-reject');
  const origHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="10"></circle></svg> Rejecting...`;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const formData = new FormData();
  formData.append('csrf_token', csrf);
  formData.append('incident_id', activeVerifyingIncident.incident_id);
  formData.append('rejection_reason', reason);

  fetch(window.appUrl('/api/incident/reject'), {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(res => {
    btn.disabled = false;
    btn.innerHTML = origHtml;

    if (res.success) {
      closeRejectModal();
      closeVerifyModal();
      if (window.Toast) {
        window.Toast.success('Receipt Rejected', res.message || 'Receipt rejected successfully. Student has been notified.');
      }
      loadIncidents();
    } else {
      if (window.Toast) {
        window.Toast.error('Rejection Failed', res.message || 'Error rejecting receipt.');
      }
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = origHtml;
    if (window.Toast) {
      window.Toast.error('Network Error', 'Failed to connect to verification server.');
    }
  });
}

// ── Edit Fee Modal Operations ────────────────────────────────
function openEditFeeModal(incident) {
  document.getElementById('edit-fee-incident-id').value = incident.incident_id;
  document.getElementById('edit-fee-subtitle').textContent = `${incident.item_name} (${incident.barcode}) — ${incident.borrower_name}`;
  document.getElementById('input-new-fee').value = incident.estimated_cost !== null ? Number(incident.estimated_cost).toFixed(2) : '';

  if (window.Modal && typeof window.Modal.open === 'function') {
    window.Modal.open('modal-edit-fee');
  } else {
    const m = document.getElementById('modal-edit-fee');
    if (m) {
      if (m.parentElement !== document.body) document.body.appendChild(m);
      m.classList.add('open');
      m.style.display = 'flex';
    }
  }
}

function closeEditFeeModal() {
  if (window.Modal && typeof window.Modal.close === 'function') {
    window.Modal.close('modal-edit-fee');
  } else {
    const m = document.getElementById('modal-edit-fee');
    if (m) {
      m.classList.remove('open');
      m.style.display = 'none';
    }
  }
}

function handleUpdateFeeSubmit(e) {
  e.preventDefault();
  const form = document.getElementById('form-edit-fee');
  const btn = document.getElementById('btn-save-fee');
  const origText = btn.innerHTML;

  btn.disabled = true;
  btn.innerHTML = 'Saving...';

  const formData = new FormData(form);

  fetch(window.appUrl('/api/incident/update-fee'), {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(res => {
    btn.disabled = false;
    btn.innerHTML = origText;

    if (res.success) {
      closeEditFeeModal();
      if (window.Toast) {
        window.Toast.success('Fee Updated', res.message);
      }
      loadIncidents();
    } else {
      if (window.Toast) {
        window.Toast.error('Update Failed', res.message || 'Error updating fee.');
      }
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = origText;
    if (window.Toast) {
      window.Toast.error('Network Error', 'Failed to connect to fee server.');
    }
  });
}
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
