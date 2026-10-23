<?php
/**
 * Inspection and Acceptance Report (IAR) Desk — Form AFA-SAF-1.016
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 */

$title = 'Inspection & Acceptance (IAR)';
$breadcrumb = 'Inspector / IAR Desk';

require dirname(__DIR__) . '/layouts/shell.php';
?>

<!-- ── Flash Notifications ───────────────────────────────────── -->
<?php if (!empty($_GET['success'])): ?>
  <div style="background:var(--color-primary-bg-subdued-hover,#dcfce7);border:1px solid rgba(22,101,52,0.2);color:var(--color-primary-deep,#14532d);padding:12px 16px;border-radius:8px;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;font-size:14px;">
    <div style="display:flex;align-items:center;gap:8px;">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
      <span><?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php if (!empty($_GET['open_print'])): ?>
      <a href="<?= url('/inspector/iar/print?id=' . (int)$_GET['open_print']) ?>" target="_blank" class="btn btn-sm btn-primary" style="border-radius:9999px;padding:4px 12px;font-size:12px;text-decoration:none;">
        Print IAR Form AFA-SAF-1.016
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
  <div style="background:var(--color-ruby-light,#fee2e2);border:1px solid rgba(220,38,38,0.2);color:var(--color-ruby,#dc2626);padding:12px 16px;border-radius:8px;margin-bottom:1.5rem;font-size:14px;display:flex;align-items:center;gap:8px;">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
    <span><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></span>
  </div>
<?php endif; ?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="section-header" style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 class="section-title" style="font-size:24px;font-weight:400;color:var(--color-ink,#0f291e);letter-spacing:-0.4px;margin:0 0 4px 0;">
      Inspection &amp; Acceptance Desk
    </h1>
    <div class="section-subtitle" style="font-size:14px;color:var(--color-ink-mute,#5c7367);font-weight:300;">
      Form AFA-SAF-1.016 · Verification and certified digital handover of incoming shipments
    </div>
  </div>

  <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
    <button type="button" class="btn btn-primary" id="btn-open-create-iar" data-modal-open="create-iar-modal" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 18px;background:var(--color-primary,#166534);color:#ffffff;font-size:14px;cursor:pointer;border:none;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Encode New IAR (Form AFA-SAF-1.016)
    </button>
  </div>
</div>

<!-- ── KPI Stat Cards ──────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem;margin-bottom:1.5rem;">
  <?php
  $statCards = [
    [
      'title'     => 'Total IARs Encoded',
      'value'     => number_format((int)($total ?? 0)),
      'icon'      => 'package',
      'color'     => 'emerald',
      'trend'     => 'Shipment ledger',
      'trend_dir' => 'up'
    ],
    [
      'title'     => 'Pending Signatures',
      'value'     => number_format((int)($metrics['pending_signatures'] ?? 0)),
      'icon'      => 'file-text',
      'color'     => 'amber',
      'trend'     => 'Awaiting scan upload',
      'trend_dir' => 'down'
    ],
    [
      'title'     => 'Transmitted to Custodian',
      'value'     => number_format((int)($metrics['transmitted_count'] ?? 0)),
      'icon'      => 'package',
      'color'     => 'indigo',
      'trend'     => 'Ready for inventory tag',
      'trend_dir' => 'up'
    ],
    [
      'title'     => 'Physical Units Verified (YTD)',
      'value'     => number_format((int)($metrics['items_inspected_ytd'] ?? 0)),
      'icon'      => 'shield',
      'color'     => 'emerald',
      'trend'     => 'Inspected order',
      'trend_dir' => 'up'
    ],
  ];

  foreach ($statCards as $stat):
    $props = $stat;
    require dirname(__DIR__) . '/components/stat-card.php';
  endforeach;
  ?>
</div>

<!-- ── IAR Table Workspace ─────────────────────────────────── -->
<div class="glass-card table-card" style="background:var(--color-canvas-glass-heavy,rgba(255,255,255,0.95));border:1px solid var(--color-hairline,rgba(22,101,52,0.12));border-radius:12px;overflow:hidden;box-shadow:var(--shadow-1);">
  
  <!-- Table Toolbar -->
  <div style="padding:16px 20px;border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
    
    <!-- Status Filter Tabs -->
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
      <?php
      $currentStatus = $_GET['status'] ?? 'ALL';
      $tabs = [
        'ALL'                => 'All Reports',
        'PENDING_SIGNATURES' => 'Pending Signatures',
        'TRANSMITTED'        => 'Transmitted to Custodian',
        'REGISTERED'         => 'Registered Assets',
      ];
      foreach ($tabs as $key => $lbl):
        $isActive = ($currentStatus === $key);
        $tabUrl = url('/inspector/iar' . ($key !== 'ALL' ? '?status=' . urlencode($key) : ''));
      ?>
        <a href="<?= $tabUrl ?>" style="text-decoration:none;font-size:12px;padding:6px 14px;border-radius:9999px;font-weight:400;transition:all 0.2s ease;<?= $isActive ? 'background:var(--color-primary,#166534);color:#ffffff;' : 'background:var(--color-canvas-soft,#f2f7f4);color:var(--color-ink-secondary,#244234);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));' ?>">
          <?= $lbl ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Search Form -->
    <form method="GET" action="<?= url('/inspector/iar') ?>" style="display:flex;gap:6px;align-items:center;margin:0;">
      <?php if ($currentStatus !== 'ALL'): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8') ?>">
      <?php endif; ?>
      <div style="position:relative;">
        <input 
          type="text" 
          name="search" 
          value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
          placeholder="Search IAR #, supplier, PO #..." 
          class="form-input"
          style="padding:6px 12px 6px 32px;font-size:13px;border-radius:9999px;border:1px solid var(--color-hairline-input,#b5d4c2);background:#ffffff;width:240px;"
        >
        <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--color-ink-mute,#5c7367);pointer-events:none;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </span>
      </div>
      <button type="submit" class="btn btn-sm" style="border-radius:9999px;padding:6px 14px;background:var(--color-canvas-soft,#f2f7f4);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));color:var(--color-ink,#0f291e);cursor:pointer;font-size:13px;">
        Filter
      </button>
    </form>
  </div>

  <!-- Table Content -->
  <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;font-feature-settings:'tnum';">
      <thead>
        <tr style="background:var(--color-canvas-soft,#f2f7f4);color:var(--color-ink-secondary,#244234);border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));text-align:left;">
          <th style="padding:12px 18px;font-weight:400;">IAR No. &amp; Date</th>
          <th style="padding:12px 18px;font-weight:400;">Supplier &amp; PO Number</th>
          <th style="padding:12px 18px;font-weight:400;">Office / Department</th>
          <th style="padding:12px 18px;font-weight:400;">Items / Valuation</th>
          <th style="padding:12px 18px;font-weight:400;">Acceptance</th>
          <th style="padding:12px 18px;font-weight:400;">Status</th>
          <th style="padding:12px 18px;font-weight:400;text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reports)): ?>
          <tr>
            <td colspan="7" style="padding:48px 16px;text-align:center;color:var(--color-ink-mute,#5c7367);">
              <div style="margin-bottom:12px;">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="var(--color-ink-mute,#5c7367)" stroke-width="1.5"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
              </div>
              <div style="font-size:15px;color:var(--color-ink,#0f291e);margin-bottom:4px;">No Inspection Reports found</div>
              <div style="font-size:13px;max-width:380px;margin:0 auto 16px auto;">
                Create a new Form AFA-SAF-1.016 to formally certify physical deliveries.
              </div>
              <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('btn-open-create-iar').click();" style="border-radius:9999px;padding:6px 16px;">
                + Encode New IAR
              </button>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($reports as $r): ?>
            <?php
            $statusBadge = match($r['status']) {
              'PENDING_SIGNATURES' => ['bg' => 'var(--color-gold-light,#fef3c7)', 'fg' => 'var(--color-gold-deep,#b45309)', 'label' => 'Pending Signatures'],
              'TRANSMITTED'        => ['bg' => 'var(--color-ocean-light,#e0f2fe)', 'fg' => 'var(--color-ocean,#0284c7)', 'label' => 'Transmitted to Custodian'],
              'REGISTERED'         => ['bg' => 'var(--color-primary-bg-subdued-hover,#dcfce7)', 'fg' => 'var(--color-primary-deep,#14532d)', 'label' => 'Registered'],
              default              => ['bg' => 'var(--color-canvas-soft,#f2f7f4)', 'fg' => 'var(--color-ink-mute,#5c7367)', 'label' => $r['status']],
            };
            ?>
            <tr style="border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.08));">
              <td style="padding:14px 18px;font-weight:500;color:var(--color-primary,#166534);">
                <?= htmlspecialchars($r['iar_no'], ENT_QUOTES, 'UTF-8') ?>
                <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);font-weight:300;">
                  Inspected: <?= date('M d, Y', strtotime($r['date_inspected'])) ?>
                </div>
              </td>
              <td style="padding:14px 18px;">
                <div style="color:var(--color-ink,#0f291e);font-weight:400;"><?= htmlspecialchars($r['supplier_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);">
                  PO: <?= htmlspecialchars($r['po_no'], ENT_QUOTES, 'UTF-8') ?> · Inv: <?= htmlspecialchars($r['invoice_no'], ENT_QUOTES, 'UTF-8') ?>
                </div>
              </td>
              <td style="padding:14px 18px;color:var(--color-ink,#0f291e);">
                <?= htmlspecialchars($r['office_dept'], ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td style="padding:14px 18px;color:var(--color-ink-secondary,#244234);">
                <?= (int)$r['item_count'] ?> line(s) (<?= (int)$r['total_quantity'] ?> units)
                <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);">
                  ₱ <?= number_format((float)$r['total_valuation'], 2) ?>
                </div>
              </td>
              <td style="padding:14px 18px;">
                <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;background:var(--color-canvas-soft,#f2f7f4);color:var(--color-ink-secondary,#244234);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));">
                  <?= htmlspecialchars($r['acceptance_type'], ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td style="padding:14px 18px;">
                <span style="display:inline-block;padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:500;background:<?= $statusBadge['bg'] ?>;color:<?= $statusBadge['fg'] ?>;">
                  <?= $statusBadge['label'] ?>
                </span>
              </td>
              <td style="padding:14px 18px;text-align:right;">
                <div style="display:inline-flex;gap:6px;align-items:center;">
                  <a href="<?= url('/inspector/iar/print?id=' . $r['id']) ?>" target="_blank" class="btn btn-sm" title="Print Official Form AFA-SAF-1.016" style="display:inline-flex;align-items:center;gap:4px;border-radius:9999px;padding:4px 10px;background:var(--color-canvas-soft,#f2f7f4);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));color:var(--color-primary,#166534);text-decoration:none;font-size:12px;">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Print
                  </a>

                  <?php if ($r['status'] === 'PENDING_SIGNATURES'): ?>
                    <button type="button" class="btn btn-sm btn-upload-scan" data-id="<?= $r['id'] ?>" data-no="<?= htmlspecialchars($r['iar_no'], ENT_QUOTES, 'UTF-8') ?>" data-supplier="<?= htmlspecialchars($r['supplier_name'], ENT_QUOTES, 'UTF-8') ?>" style="display:inline-flex;align-items:center;gap:4px;border-radius:9999px;padding:4px 10px;background:var(--color-gold-light,#fef3c7);border:1px solid rgba(217,119,6,0.25);color:var(--color-gold-deep,#b45309);font-size:12px;cursor:pointer;">
                      <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                      Upload Scan
                    </button>
                  <?php elseif (!empty($r['scanned_document_path'])): ?>
                    <a href="<?= url($r['scanned_document_path']) ?>" target="_blank" class="btn btn-sm" title="View uploaded wet-signed document" style="display:inline-flex;align-items:center;gap:4px;border-radius:9999px;padding:4px 10px;background:var(--color-ocean-light,#e0f2fe);border:1px solid rgba(2,132,199,0.25);color:var(--color-ocean,#0284c7);text-decoration:none;font-size:12px;">
                      <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      View Scan
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- MODAL A: Create New IAR (Form AFA-SAF-1.016)                   -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div id="create-iar-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="create-iar-modal-title">
  <div class="modal-panel xl" style="max-height:90vh;display:flex;flex-direction:column;box-shadow:var(--shadow-3);">
    
    <!-- Modal Header -->
    <div class="modal-header" style="padding:20px 24px;border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:flex;justify-content:space-between;align-items:center;background:var(--color-canvas-soft,#f2f7f4);flex-shrink:0;">
      <div>
        <div style="font-size:11px;font-weight:600;letter-spacing:0.5px;color:var(--color-primary,#166534);text-transform:uppercase;margin-bottom:2px;">
          FORM AFA-SAF-1.016
        </div>
        <h2 id="create-iar-modal-title" class="modal-title" style="font-size:18px;font-weight:400;color:var(--color-ink,#0f291e);margin:0;">
          Inspection and Acceptance Report Encoder
        </h2>
      </div>
      <button type="button" class="modal-close btn-close-modal" data-target="create-iar-modal" data-modal-close="create-iar-modal" aria-label="Close" style="background:none;border:none;cursor:pointer;color:var(--color-ink-mute,#5c7367);padding:6px;border-radius:8px;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Modal Form -->
    <form id="form-create-iar" method="POST" action="<?= url('/inspector/iar/create') ?>" style="display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden;margin:0;">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
      <input type="hidden" name="items" id="iar-items-json">

      <!-- Scrollable Form Body -->
      <div class="modal-body" style="padding:24px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Section 1: Entity & References -->
      <div style="background:var(--color-canvas-soft,#f2f7f4);padding:16px;border-radius:8px;border:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;">
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">Entity Name</label>
          <input type="text" name="entity_name" value="SLSU-ALABAT" class="form-input" style="width:100%;font-size:13px;padding:6px 10px;background:#ffffff;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;" required>
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">Fund Cluster</label>
          <input type="text" name="fund_cluster" value="PRE-STF" class="form-input" style="width:100%;font-size:13px;padding:6px 10px;background:#ffffff;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;" required>
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">Recipient Facility / Department <span style="color:var(--color-ruby,#dc2626);">*</span></label>
          <select id="iar-facility-select" class="form-input" required style="width:100%;font-size:13px;padding:6px 10px;background:#ffffff;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;">
            <option value="" disabled selected>Select destination facility...</option>
            <option value="Science Laboratory">Science Laboratory</option>
            <option value="Farm Laboratory">Farm Laboratory</option>
            <option value="GIS &amp; Comp. Lab">GIS &amp; Comp. Lab</option>
            <option value="Fisheries Laboratory">Fisheries Laboratory</option>
            <option value="Clinic">Clinic</option>
            <option value="Other">Other / Administrative Office</option>
          </select>
          <div id="iar-custom-facility-group" style="display:none;margin-top:6px;">
            <input type="text" id="iar-custom-facility" placeholder="Specify Department / Office name" class="form-input" style="width:100%;font-size:12px;padding:4px 8px;background:#ffffff;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:4px;" maxlength="60">
          </div>
          <input type="hidden" id="iar-office-dept" name="office_dept" value="" required>
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">Responsibility Center Code (RCC)</label>
          <input type="text" name="rcc" placeholder="e.g. RCC-ALB-04" class="form-input" style="width:100%;font-size:13px;padding:6px 10px;background:#ffffff;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;">
        </div>
      </div>

      <!-- Section 2: Supplier, PO & Invoice Details -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;">
        <div style="grid-column:1 / -1;">
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">Supplier / Vendor Name <span style="color:var(--color-ruby,#dc2626);">*</span></label>
          <input type="text" name="supplier_name" placeholder="Full registered company / vendor name" class="form-input" style="width:100%;font-size:13px;padding:6px 10px;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;" required>
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">Purchase Order (PO) No. <span style="color:var(--color-ruby,#dc2626);">*</span></label>
          <input type="text" name="po_no" placeholder="e.g. PO-2026-081" class="form-input" style="width:100%;font-size:13px;padding:6px 10px;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;" required>
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">PO Date</label>
          <input type="date" name="po_date" value="<?= date('Y-m-d') ?>" class="form-input" style="width:100%;font-size:13px;padding:6px 10px;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;" required>
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">Invoice / DR No. <span style="color:var(--color-ruby,#dc2626);">*</span></label>
          <input type="text" name="invoice_no" placeholder="e.g. INV-90214" class="form-input" style="width:100%;font-size:13px;padding:6px 10px;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;" required>
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:4px;">Invoice Date</label>
          <input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" class="form-input" style="width:100%;font-size:13px;padding:6px 10px;border:1px solid var(--color-hairline-input,#b5d4c2);border-radius:6px;" required>
        </div>
      </div>

      <!-- Section 3: Physical Line Items Staging Table -->
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <div>
            <h3 style="font-size:14px;font-weight:500;color:var(--color-ink,#0f291e);margin:0;">Delivered Line Items</h3>
            <div style="font-size:12px;color:var(--color-ink-mute,#5c7367);">Physical items inspected and verified against invoice</div>
          </div>
          <button type="button" id="btn-add-iar-item-row" class="btn btn-sm" style="display:inline-flex;align-items:center;gap:4px;border-radius:9999px;padding:4px 12px;background:var(--color-canvas-soft,#f2f7f4);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));color:var(--color-primary,#166534);cursor:pointer;font-size:12px;">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Add Line Item
          </button>
        </div>

        <div style="overflow-x:auto;border:1px solid var(--color-hairline,rgba(22,101,52,0.12));border-radius:8px;">
          <table id="iar-items-table" style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
              <tr style="background:var(--color-canvas-soft,#f2f7f4);color:var(--color-ink-secondary,#244234);border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));text-align:left;">
                <th style="padding:8px 10px;width:40px;">#</th>
                <th style="padding:8px 10px;">Item Description (w/ complete specs) <span style="color:var(--color-ruby,#dc2626);">*</span></th>
                <th style="padding:8px 10px;width:85px;">Unit</th>
                <th style="padding:8px 10px;width:75px;">Qty</th>
                <th style="padding:8px 10px;width:110px;">Unit Cost (₱)</th>
                <th style="padding:8px 10px;width:110px;">Total (₱)</th>
                <th style="padding:8px 10px;width:110px;">Model</th>
                <th style="padding:8px 10px;width:110px;">Serial No.</th>
                <th style="padding:8px 10px;width:40px;text-align:center;">Del</th>
              </tr>
            </thead>
            <tbody id="iar-items-tbody">
              <!-- Default Initial Row -->
              <tr class="iar-item-row" style="border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.06));">
                <td class="row-seq" style="padding:8px 10px;color:var(--color-ink-mute,#5c7367);font-weight:500;">1</td>
                <td style="padding:6px 10px;">
                  <input type="text" class="iar-item-desc form-input" placeholder="e.g. Dell Latitude 5420 Core i7 / 16GB / 512GB SSD" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;" required>
                </td>
                <td style="padding:6px 10px;">
                  <input type="text" class="iar-item-unit form-input" value="units" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;" required>
                </td>
                <td style="padding:6px 10px;">
                  <input type="number" class="iar-item-qty form-input" value="1" min="1" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;font-feature-settings:'tnum';" required>
                </td>
                <td style="padding:6px 10px;">
                  <input type="number" step="0.01" class="iar-item-cost form-input" placeholder="0.00" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;font-feature-settings:'tnum';">
                </td>
                <td style="padding:6px 10px;">
                  <input type="number" step="0.01" class="iar-item-total form-input" placeholder="0.00" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;background:#f8fafc;font-feature-settings:'tnum';" readonly>
                </td>
                <td style="padding:6px 10px;">
                  <input type="text" class="iar-item-model form-input" placeholder="Model" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;">
                </td>
                <td style="padding:6px 10px;">
                  <input type="text" class="iar-item-serial form-input" placeholder="Serial" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;">
                </td>
                <td style="padding:6px 10px;text-align:center;">
                  <button type="button" class="btn-remove-iar-row" style="background:none;border:none;color:var(--color-ruby,#dc2626);cursor:pointer;padding:2px;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr style="background:var(--color-canvas-soft,#f2f7f4);font-weight:500;">
                <td colspan="5" style="padding:8px 10px;text-align:right;">Total Estimated Valuation:</td>
                <td colspan="4" style="padding:8px 10px;color:var(--color-primary,#166534);font-feature-settings:'tnum';" id="iar-calc-grand-total">₱ 0.00</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Section 4: Certification, Acceptance & Signatories -->
      <div style="background:var(--color-canvas-soft,#f2f7f4);padding:16px;border-radius:8px;border:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
        
        <!-- Inspection Block -->
        <div>
          <div style="font-size:12px;font-weight:600;color:var(--color-primary,#166534);text-transform:uppercase;margin-bottom:8px;">
            1. Inspection Certification
          </div>
          <div style="margin-bottom:8px;">
            <label style="display:block;font-size:11px;font-weight:500;margin-bottom:2px;">Date Inspected</label>
            <input type="date" name="date_inspected" value="<?= date('Y-m-d') ?>" class="form-input" style="width:100%;font-size:12px;padding:4px 8px;border-radius:4px;" required>
          </div>
          <div style="margin-bottom:8px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
              <input type="checkbox" name="is_inspected_order" value="1" checked disabled>
              <span>Inspected, verified and found in order as to quantity and specifications</span>
            </label>
          </div>
          <div>
            <label style="display:block;font-size:11px;font-weight:500;margin-bottom:2px;">Inspection Officer</label>
            <input type="text" name="inspection_officer" value="MANUEL S. VALDERAMA, MIT" class="form-input" style="width:100%;font-size:12px;padding:4px 8px;border-radius:4px;" required>
          </div>
        </div>

        <!-- Acceptance Block -->
        <div>
          <div style="font-size:12px;font-weight:600;color:var(--color-primary,#166534);text-transform:uppercase;margin-bottom:8px;">
            2. Acceptance Determination
          </div>
          <div style="margin-bottom:8px;">
            <label style="display:block;font-size:11px;font-weight:500;margin-bottom:2px;">Date Received</label>
            <input type="date" name="date_received" value="<?= date('Y-m-d') ?>" class="form-input" style="width:100%;font-size:12px;padding:4px 8px;border-radius:4px;" required>
          </div>
          <div style="display:flex;gap:16px;margin-bottom:8px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
              <input type="radio" name="acceptance_type" value="COMPLETE" checked>
              <span>[X] Complete Delivery</span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
              <input type="radio" name="acceptance_type" value="PARTIAL">
              <span>[ ] Partial Delivery</span>
            </label>
          </div>
          <div id="iar-partial-remarks-box" style="display:none;margin-bottom:8px;">
            <label style="display:block;font-size:11px;font-weight:500;margin-bottom:2px;">Partial Delivery Remarks</label>
            <input type="text" name="partial_remarks" placeholder="Specify undelivered units or discrepancies..." class="form-input" style="width:100%;font-size:12px;padding:4px 8px;border-radius:4px;">
          </div>
          <div>
            <label style="display:block;font-size:11px;font-weight:500;margin-bottom:2px;">OIC, Supply and Property Officer</label>
            <input type="text" name="property_officer" value="VERONICA C. OBIEN" class="form-input" style="width:100%;font-size:12px;padding:4px 8px;border-radius:4px;" required>
          </div>
        </div>

      </div>

      </div><!-- /modal-body -->

      <!-- Modal Footer -->
      <div class="modal-footer" style="padding:14px 24px;display:flex;justify-content:flex-end;gap:8px;border-top:1px solid var(--color-hairline,rgba(22,101,52,0.12));background:var(--color-canvas-soft,#f2f7f4);flex-shrink:0;">
        <button type="button" class="btn btn-ghost btn-close-modal" data-target="create-iar-modal" data-modal-close="create-iar-modal" style="border-radius:9999px;padding:8px 16px;background:var(--color-canvas-soft,#f2f7f4);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));color:var(--color-ink,#0f291e);cursor:pointer;font-size:13px;">
          Cancel
        </button>
        <button type="submit" class="btn btn-primary" id="btn-submit-iar" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 20px;background:var(--color-primary,#166534);color:#ffffff;font-size:13px;border:none;cursor:pointer;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
          Save &amp; Generate Form AFA-SAF-1.016
        </button>
      </div>

    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- MODAL B: Upload Wet-Signed IAR Scan                            -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div id="upload-iar-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="upload-iar-modal-title">
  <div class="modal-panel md" style="max-height:90vh;display:flex;flex-direction:column;box-shadow:var(--shadow-3);">
    
    <div class="modal-header" style="padding:18px 20px;border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:flex;justify-content:space-between;align-items:center;background:var(--color-canvas-soft,#f2f7f4);flex-shrink:0;">
      <h3 id="upload-iar-modal-title" class="modal-title" style="font-size:16px;font-weight:400;color:var(--color-ink,#0f291e);margin:0;">
        Upload Signed IAR Scan
      </h3>
      <button type="button" class="modal-close btn-close-modal" data-target="upload-iar-modal" data-modal-close="upload-iar-modal" aria-label="Close" style="background:none;border:none;cursor:pointer;color:var(--color-ink-mute,#5c7367);padding:4px;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <form method="POST" action="<?= url('/inspector/iar/upload-scan') ?>" enctype="multipart/form-data" style="display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden;margin:0;">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
      <input type="hidden" name="iar_id" id="upload-scan-iar-id">

      <div class="modal-body" style="padding:20px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:1.25rem;">
        <div style="background:var(--color-canvas-soft,#f2f7f4);padding:12px;border-radius:8px;font-size:13px;color:var(--color-ink-secondary,#244234);">
          <div>Report: <strong id="upload-scan-iar-no" style="color:var(--color-primary,#166534);">IAR-XXXX-XXXX</strong></div>
          <div style="font-size:12px;color:var(--color-ink-mute,#5c7367);margin-top:2px;" id="upload-scan-supplier">Supplier Name</div>
        </div>

        <div>
          <label style="display:block;font-size:12px;font-weight:500;color:var(--color-ink-secondary,#244234);margin-bottom:6px;">
            Select Scanned Document (.PDF, .PNG, .JPG max 10MB) <span style="color:var(--color-ruby,#dc2626);">*</span>
          </label>
          <input 
            type="file" 
            name="scanned_document" 
            accept=".pdf,image/png,image/jpeg" 
            required
            style="width:100%;font-size:13px;padding:10px;border:1px dashed var(--color-hairline-input,#b5d4c2);border-radius:8px;background:var(--color-canvas-soft,#f2f7f4);cursor:pointer;"
          >
        </div>

        <div>
          <label style="display:flex;align-items:flex-start;gap:8px;font-size:12px;color:var(--color-ink,#0f291e);cursor:pointer;line-height:1.4;">
            <input type="checkbox" required style="margin-top:2px;">
            <span>I confirm that this document contains the physical wet-signatures of both the Inspection Officer and the OIC Supply and Property Officer.</span>
          </label>
        </div>
      </div>

      <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;border-top:1px solid var(--color-hairline,rgba(22,101,52,0.12));background:var(--color-canvas-soft,#f2f7f4);flex-shrink:0;">
        <button type="button" class="btn btn-ghost btn-close-modal" data-target="upload-iar-modal" data-modal-close="upload-iar-modal" style="border-radius:9999px;padding:8px 16px;background:var(--color-canvas-soft,#f2f7f4);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));color:var(--color-ink,#0f291e);cursor:pointer;font-size:13px;">
          Cancel
        </button>
        <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 20px;background:var(--color-primary,#166534);color:#ffffff;font-size:13px;border:none;cursor:pointer;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
          Upload &amp; Transmit
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── Script Interactivity for IAR Desk ──────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const modalCreate = document.getElementById('create-iar-modal');
  const modalUpload = document.getElementById('upload-iar-modal');
  const btnOpenCreate = document.getElementById('btn-open-create-iar');

  // Open Create Modal
  if (btnOpenCreate) {
    btnOpenCreate.addEventListener('click', () => {
      if (window.Modal && typeof window.Modal.open === 'function') {
        window.Modal.open('create-iar-modal');
      } else {
        if (modalCreate.parentElement !== document.body) document.body.appendChild(modalCreate);
        modalCreate.classList.add('open');
        document.body.classList.add('modal-open');
      }
    });
  }

  // Check URL params for auto-open
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('action') === 'new') {
    if (window.Modal && typeof window.Modal.open === 'function') {
      window.Modal.open('create-iar-modal');
    } else if (modalCreate) {
      if (modalCreate.parentElement !== document.body) document.body.appendChild(modalCreate);
      modalCreate.classList.add('open');
      document.body.classList.add('modal-open');
    }
  }

  // Close modals
  document.querySelectorAll('.btn-close-modal').forEach(btn => {
    btn.addEventListener('click', function() {
      const targetId = this.dataset.target;
      if (window.Modal && typeof window.Modal.close === 'function') {
        window.Modal.close(targetId);
      } else {
        const target = document.getElementById(targetId);
        if (target) {
          target.classList.remove('open');
          document.body.classList.remove('modal-open');
        }
      }
    });
  });

  // Open Upload Scan modal
  document.querySelectorAll('.btn-upload-scan').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      const no = this.dataset.no;
      const supplier = this.dataset.supplier;

      document.getElementById('upload-scan-iar-id').value = id;
      document.getElementById('upload-scan-iar-no').textContent = no;
      document.getElementById('upload-scan-supplier').textContent = supplier;

      if (window.Modal && typeof window.Modal.open === 'function') {
        window.Modal.open('upload-iar-modal');
      } else if (modalUpload) {
        if (modalUpload.parentElement !== document.body) document.body.appendChild(modalUpload);
        modalUpload.classList.add('open');
        document.body.classList.add('modal-open');
      }
    });
  });

  // Acceptance Radio Toggle
  const radioPartial = document.querySelectorAll('input[name="acceptance_type"]');
  const partialBox = document.getElementById('iar-partial-remarks-box');
  radioPartial.forEach(r => {
    r.addEventListener('change', function() {
      if (this.value === 'PARTIAL') {
        partialBox.style.display = 'block';
      } else {
        partialBox.style.display = 'none';
      }
    });
  });

  // Recipient Facility & Office Dept Sync
  const iarFacilitySel = document.getElementById('iar-facility-select');
  const iarCustomGroup = document.getElementById('iar-custom-facility-group');
  const iarCustomInput = document.getElementById('iar-custom-facility');
  const iarOfficeDept  = document.getElementById('iar-office-dept');

  function syncIarOfficeDept() {
    if (!iarFacilitySel || !iarOfficeDept) return;
    const val = iarFacilitySel.value;
    if (val === 'Other') {
      if (iarCustomGroup) iarCustomGroup.style.display = 'block';
      if (iarCustomInput) {
        iarCustomInput.required = true;
        iarOfficeDept.value = iarCustomInput.value.trim();
      }
    } else {
      if (iarCustomGroup) iarCustomGroup.style.display = 'none';
      if (iarCustomInput) {
        iarCustomInput.required = false;
      }
      iarOfficeDept.value = val ? val.trim() : '';
    }
  }

  if (iarFacilitySel) iarFacilitySel.addEventListener('change', syncIarOfficeDept);
  if (iarCustomInput) iarCustomInput.addEventListener('input', syncIarOfficeDept);

  // Dynamic Line Items Table
  const tbody = document.getElementById('iar-items-tbody');
  const btnAddRow = document.getElementById('btn-add-iar-item-row');
  const grandTotalEl = document.getElementById('iar-calc-grand-total');

  function updateGrandTotal() {
    let grand = 0;
    tbody.querySelectorAll('.iar-item-row').forEach((row, idx) => {
      row.querySelector('.row-seq').textContent = idx + 1;
      const qty = parseFloat(row.querySelector('.iar-item-qty').value) || 0;
      const cost = parseFloat(row.querySelector('.iar-item-cost').value) || 0;
      const total = qty * cost;
      row.querySelector('.iar-item-total').value = total > 0 ? total.toFixed(2) : '';
      grand += total;
    });
    grandTotalEl.textContent = '₱ ' + grand.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function attachRowListeners(row) {
    row.querySelector('.iar-item-qty').addEventListener('input', updateGrandTotal);
    row.querySelector('.iar-item-cost').addEventListener('input', updateGrandTotal);
    row.querySelector('.btn-remove-iar-row').addEventListener('click', function() {
      if (tbody.querySelectorAll('.iar-item-row').length > 1) {
        row.remove();
        updateGrandTotal();
      } else {
        alert('An IAR must have at least one line item.');
      }
    });
  }

  tbody.querySelectorAll('.iar-item-row').forEach(attachRowListeners);

  btnAddRow.addEventListener('click', function() {
    const newSeq = tbody.querySelectorAll('.iar-item-row').length + 1;
    const tr = document.createElement('tr');
    tr.className = 'iar-item-row';
    tr.style.borderBottom = '1px solid var(--color-hairline,rgba(22,101,52,0.06))';
    tr.innerHTML = `
      <td class="row-seq" style="padding:8px 10px;color:var(--color-ink-mute,#5c7367);font-weight:500;">${newSeq}</td>
      <td style="padding:6px 10px;">
        <input type="text" class="iar-item-desc form-input" placeholder="e.g. Technical specifications" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;" required>
      </td>
      <td style="padding:6px 10px;">
        <input type="text" class="iar-item-unit form-input" value="units" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;" required>
      </td>
      <td style="padding:6px 10px;">
        <input type="number" class="iar-item-qty form-input" value="1" min="1" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;font-feature-settings:'tnum';" required>
      </td>
      <td style="padding:6px 10px;">
        <input type="number" step="0.01" class="iar-item-cost form-input" placeholder="0.00" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;font-feature-settings:'tnum';">
      </td>
      <td style="padding:6px 10px;">
        <input type="number" step="0.01" class="iar-item-total form-input" placeholder="0.00" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;background:#f8fafc;font-feature-settings:'tnum';" readonly>
      </td>
      <td style="padding:6px 10px;">
        <input type="text" class="iar-item-model form-input" placeholder="Model" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;">
      </td>
      <td style="padding:6px 10px;">
        <input type="text" class="iar-item-serial form-input" placeholder="Serial" style="width:100%;font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;">
      </td>
      <td style="padding:6px 10px;text-align:center;">
        <button type="button" class="btn-remove-iar-row" style="background:none;border:none;color:var(--color-ruby,#dc2626);cursor:pointer;padding:2px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
    attachRowListeners(tr);
  });

  // Form Serialization on Submit
  document.getElementById('form-create-iar').addEventListener('submit', function(e) {
    const items = [];
    let hasError = false;

    tbody.querySelectorAll('.iar-item-row').forEach(row => {
      const desc = row.querySelector('.iar-item-desc').value.trim();
      const unit = row.querySelector('.iar-item-unit').value.trim();
      const qty = parseInt(row.querySelector('.iar-item-qty').value) || 1;
      const unitCost = parseFloat(row.querySelector('.iar-item-cost').value) || 0.0;
      const totalCost = parseFloat(row.querySelector('.iar-item-total').value) || (qty * unitCost);
      const model = row.querySelector('.iar-item-model').value.trim();
      const serial = row.querySelector('.iar-item-serial').value.trim();

      if (!desc) {
        hasError = true;
      }

      items.push({
        item_description: desc,
        unit: unit || 'units',
        quantity: qty,
        unit_cost: unitCost,
        total_cost: totalCost,
        model: model,
        serial_no: serial,
      });
    });

    syncIarOfficeDept();
    if (!iarOfficeDept || !iarOfficeDept.value.trim()) {
      e.preventDefault();
      alert('Please select a recipient facility or department.');
      if (iarFacilitySel) iarFacilitySel.focus();
      return;
    }

    if (hasError || items.length === 0) {
      e.preventDefault();
      alert('Please fill in complete descriptions for all delivered line items.');
      return;
    }

    document.getElementById('iar-items-json').value = JSON.stringify(items);
  });
});
</script>

<?php require dirname(__DIR__) . '/layouts/shell-footer.php'; ?>

