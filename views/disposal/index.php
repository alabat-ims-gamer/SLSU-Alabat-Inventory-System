<?php
/**
 * Asset Disposal & Condemnation Console
 * 
 * Fulfills REVISE_RESEARCH.pdf (pp. 6, 21, 23, 40-43) requirements.
 * Design inspired by stripe-DESIGN.md: editorial display typography, tabular numbers,
 * tight pill CTAs, refined 2-tab layout, and zero-trust server-side state enforcement.
 */

$title      = 'Asset Disposal & Condemnation';
$breadcrumb = 'Disposals';

require __DIR__ . '/../layouts/shell.php';
?>

<style>
/* Stripi-inspired design tokens for Disposal Command Center */
.disposal-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.disposal-tabs-nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border-bottom: 1px solid var(--color-hairline, #e3e8ee);
  padding-bottom: 0.5rem;
  margin-bottom: 1rem;
}

.disposal-tab-btn {
  background: transparent;
  border: none;
  font-size: 14px;
  font-weight: 500;
  color: var(--color-ink-mute, #64748d);
  padding: 8px 16px;
  border-radius: 9999px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.15s ease-in-out;
}

.disposal-tab-btn:hover {
  color: var(--color-ink, #0d253d);
  background: var(--color-canvas-soft, #f6f9fc);
}

.disposal-tab-btn.active {
  color: #ffffff;
  background: var(--color-primary, #166534);
  font-weight: 500;
  box-shadow: 0 1px 3px rgba(22, 101, 52, 0.25);
}

.tab-badge {
  font-family: var(--font-tabular, 'SF Pro Display', system-ui, sans-serif);
  font-feature-settings: "tnum" 1;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 9999px;
  background: rgba(0, 0, 0, 0.08);
}

.disposal-tab-btn.active .tab-badge {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

.batch-actions-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f0f4ff;
  border: 1px solid #c7d2fe;
  padding: 10px 16px;
  border-radius: 8px;
  margin-bottom: 1rem;
  animation: fadeIn 0.2s ease-out;
}

.batch-actions-bar.hidden {
  display: none;
}

.num-tabular {
  font-family: var(--font-tabular, 'SF Pro Display', system-ui, sans-serif);
  font-feature-settings: "tnum" 1;
  font-variant-numeric: tabular-nums;
}

.badge-ruby {
  background: #ffe4e6;
  color: #be123c;
  border: 1px solid #fecdd3;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.badge-emerald {
  background: #d1fae5;
  color: #047857;
  border: 1px solid #a7f3d0;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.badge-cyan {
  background: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.badge-amber {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.badge-indigo {
  background: #ede9fe;
  color: #6d28d9;
  border: 1px solid #ddd6fe;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.badge-condemned {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
}

.row-selected {
  background-color: #f8fafc !important;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="disposal-container">

  <!-- ── 1. Page Header ─────────────────────────────────────────── -->
  <div class="section-header">
    <div>
      <h1 class="section-title">Asset Disposal &amp; Condemnation Console</h1>
      <div class="section-subtitle">
        Official property lifecycle decommissioning, unserviceable equipment staging, and permanent audit archive
      </div>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
      <?php if ($role === 'executive_admin'): ?>
        <span class="badge" style="background:var(--color-canvas-soft, #f1f5f9);color:var(--color-ink-mute, #64748b);border:1px solid var(--color-hairline, #e2e8f0);padding:5px 12px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border-radius:20px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
          Executive Read-Only View
        </span>
        <a href="<?= url('/disposal/export-xlsx') ?>" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 16px;" title="Export official decommissioned assets ledger to Microsoft Excel (.xlsx)">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Export Disposals (.xlsx)
        </a>
      <?php else: ?>
        <a href="<?= url('/disposal/export-xlsx') ?>" class="btn btn-ghost btn-sm" title="Export official decommissioned assets ledger to Microsoft Excel (.xlsx)" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 16px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Export Excel (.xlsx)
        </a>
        <button type="button" class="btn btn-primary btn-sm" id="btn-open-stage-modal" data-modal-open="stageModal" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 16px;background:var(--color-primary, #166534);">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Stage Asset for Disposal
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── 2. Top KPI Stat Cards ──────────────────────────────────── -->
  <div class="grid-kpi-stats stagger-children" id="disposal-stats-grid">
    <?php
    $statCards = [
      ['title' => 'Staged for Disposal',       'value' => number_format((int)($stats['staged_count'] ?? 0)),        'icon' => 'clock',   'color' => 'amber',   'trend' => 'Pending Action',    'trend_dir' => 'down'],
      ['title' => 'Total Decommissioned',     'value' => number_format((int)($stats['total_disposed'] ?? 0)),      'icon' => 'trash-2', 'color' => 'ruby',    'trend' => 'Permanent Archive', 'trend_dir' => 'down'],
      ['title' => 'Scrapped / Waste Units',   'value' => number_format((int)($stats['scrapped_count'] ?? 0)),      'icon' => 'archive', 'color' => 'indigo',  'trend' => 'Unserviceable',     'trend_dir' => 'down'],
      ['title' => 'Total Salvage Recovered',  'value' => '₱' . number_format((float)($stats['total_salvage_value'] ?? 0), 2), 'icon' => 'peso-sign', 'color' => 'emerald', 'trend' => 'Monetary Yield', 'trend_dir' => 'up'],
    ];
    foreach ($statCards as $stat):
      $props = $stat;
      require __DIR__ . '/../components/stat-card.php';
    endforeach;
    ?>
  </div>

  <!-- ── 3. Tab Navigation ─────────────────────────────────────── -->
  <div class="disposal-tabs-nav">
    <button type="button" class="disposal-tab-btn active" id="tab-btn-staged" data-tab="staged">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
      For Disposal
      <span class="tab-badge" id="tab-badge-staged"><?= number_format((int)($stats['staged_count'] ?? 0)) ?></span>
    </button>
    <button type="button" class="disposal-tab-btn" id="tab-btn-disposed" data-tab="disposed">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
      Decommissioned Archive
      <span class="tab-badge" id="tab-badge-disposed"><?= number_format((int)($stats['total_disposed'] ?? 0)) ?></span>
    </button>
  </div>

  <!-- ── 4. Batch Actions Bar (For Staged Tab) ─────────────────── -->
  <div class="batch-actions-bar hidden" id="batch-actions-bar">
    <div style="display:flex;align-items:center;gap:10px;">
      <span style="font-weight:600;font-size:13px;color:#1e3a8a;">
        <span id="selected-count" class="num-tabular">0</span> item(s) selected
      </span>
      <button type="button" class="btn btn-ghost btn-xs" id="btn-deselect-all" style="color:#475569;font-size:12px;">
        Deselect All
      </button>
    </div>
    <div style="display:flex;gap:8px;">
      <?php if ($role !== 'executive_admin'): ?>
        <button type="button" class="btn btn-primary btn-sm" id="btn-open-batch-modal" style="background:#dc2626;border-color:#dc2626;border-radius:9999px;padding:6px 14px;font-size:12px;">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          Decommission Selected Batch
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── 5. TAB 1: Staging Queue View ──────────────────────────── -->
  <div id="view-staged-tab" class="animate-fade-in-up">
    <div class="data-table-wrap">
      <!-- Toolbar -->
      <div class="data-table-toolbar">
        <div class="data-table-search">
          <span class="data-table-search-icon">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </span>
          <input
            type="text"
            id="staged-search-input"
            class="form-input data-table-search-input"
            placeholder="Search staged assets by name, barcode, property #, or location..."
            autocomplete="off"
          >
        </div>

        <select id="staged-filter-category" class="form-input" style="width:auto;min-width:140px;height:36px;font-size:12px;">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>

        <button type="button" id="btn-refresh-staged" class="btn btn-ghost btn-icon-sm" title="Refresh Staged Queue">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
        </button>
      </div>

      <!-- Table Body -->
      <div class="data-table-body" style="position:relative;min-height:240px;overflow-x:auto;">
        <table class="data-table" id="staged-table" style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <?php if ($role !== 'executive_admin'): ?>
                <th style="width:40px;text-align:center;">
                  <input type="checkbox" id="select-all-staged" title="Select all visible">
                </th>
              <?php endif; ?>
              <th>Property # / Barcode</th>
              <th>Asset Name &amp; Description</th>
              <th>Category</th>
              <th>Current Location</th>
              <th>Status</th>
              <th>Date Staged</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody id="staged-tbody">
            <tr>
              <td colspan="<?= $role === 'executive_admin' ? 7 : 8 ?>" style="text-align:center;padding:2rem;color:var(--color-ink-mute);">
                Loading staged assets...
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="data-table-footer" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid var(--color-hairline, #e3e8ee);">
        <div style="font-size:12px;color:var(--color-ink-mute);" id="staged-pagination-info">
          Showing 0 of 0 assets
        </div>
        <div style="display:flex;gap:4px;" id="staged-pagination-controls">
          <!-- Rendered dynamically -->
        </div>
      </div>
    </div>
  </div>

  <!-- ── 6. TAB 2: Decommissioned Archive View ────────────────── -->
  <div id="view-disposed-tab" class="animate-fade-in-up" style="display:none;">
    <div class="data-table-wrap">
      <!-- Toolbar -->
      <div class="data-table-toolbar" style="flex-wrap:wrap;gap:8px;">
        <div class="data-table-search" style="flex:1;min-width:240px;">
          <span class="data-table-search-icon">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </span>
          <input
            type="text"
            id="disposed-search-input"
            class="form-input data-table-search-input"
            placeholder="Search archive by property #, name, method, or reason..."
            autocomplete="off"
          >
        </div>

        <select id="disposed-filter-method" class="form-input" style="width:auto;min-width:140px;height:36px;font-size:12px;">
          <option value="">All Disposal Methods</option>
          <?php foreach ($methods as $m): ?>
            <option value="<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($methodLabels[$m] ?? ucfirst($m), ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select id="disposed-filter-category" class="form-input" style="width:auto;min-width:130px;height:36px;font-size:12px;">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>

        <input type="date" id="disposed-date-from" class="form-input" style="width:auto;height:36px;font-size:12px;" title="Disposal Date From">
        <input type="date" id="disposed-date-to" class="form-input" style="width:auto;height:36px;font-size:12px;" title="Disposal Date To">

        <button type="button" id="btn-refresh-disposed" class="btn btn-ghost btn-icon-sm" title="Refresh Disposed Archive">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
        </button>
      </div>

      <!-- Table Body -->
      <div class="data-table-body" style="position:relative;min-height:240px;overflow-x:auto;">
        <table class="data-table" id="disposed-table" style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <th>Property # / Barcode</th>
              <th>Asset Name</th>
              <th>Category</th>
              <th>Disposal Method</th>
              <th>Disposal Reason / Justification</th>
              <th style="text-align:right;">Salvage Value</th>
              <th>Disposal Date</th>
              <th>Authorized Custodian</th>
            </tr>
          </thead>
          <tbody id="disposed-tbody">
            <tr>
              <td colspan="8" style="text-align:center;padding:2rem;color:var(--color-ink-mute);">
                Loading decommissioned archive...
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="data-table-footer" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid var(--color-hairline, #e3e8ee);">
        <div style="font-size:12px;color:var(--color-ink-mute);" id="disposed-pagination-info">
          Showing 0 of 0 archived assets
        </div>
        <div style="display:flex;gap:4px;" id="disposed-pagination-controls">
          <!-- Rendered dynamically -->
        </div>
      </div>
    </div>
  </div>

</div>

<!-- ── 7. MODALS ─────────────────────────────────────────────── -->

<!-- Modal 1: Stage Asset For Disposal -->
<div id="stageModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="stageModalTitle">
  <div class="modal-panel md" style="max-height:90vh;display:flex;flex-direction:column;">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="stageModalTitle" style="color:var(--color-ink);display:flex;align-items:center;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Stage Asset for Disposal
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Condemn an active or damaged asset and queue it for official university decommissioning
        </div>
      </div>
      <button type="button" class="modal-close" data-modal-close="stageModal" aria-label="Close" onclick="closeModal('stageModal')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <form id="stageForm" style="display:flex;flex-direction:column;flex:1;overflow-y:auto;margin:0;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <div class="modal-body" style="padding:1.25rem 1.5rem;overflow-y:auto;">
        
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label required" for="stage_item_id" style="font-weight:500;">
            Select Asset to Condemn
          </label>
          <select id="stage_item_id" name="item_id" class="form-input" style="width:100%;font-size:13px;" required>
            <option value="">-- Choose active or maintenance asset --</option>
            <?php foreach ($activeItems as $ai): ?>
              <option value="<?= (int)$ai['item_id'] ?>">
                <?= htmlspecialchars($ai['property_number'] ?: $ai['barcode'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($ai['item_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ai['category'], ENT_QUOTES, 'UTF-8') ?> | Status: <?= ucfirst($ai['status']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label required" for="stage_reason" style="font-weight:500;">
            Condemnation Reason / Justification
          </label>
          <textarea
            id="stage_reason"
            name="reason"
            class="form-input"
            rows="3"
            placeholder="e.g. Declared Beyond Economic Repair (B.E.R.) following motherboard failure; Obsolete instructional equipment..."
            required
            style="width:100%;font-size:13px;resize:vertical;min-height:75px;"
          ></textarea>
        </div>

        <div style="background:rgba(217,119,6,0.06);border:1px solid rgba(217,119,6,0.25);padding:10px 14px;border-radius:var(--radius-md);font-size:12px;color:var(--color-ink-secondary);display:flex;gap:8px;align-items:flex-start;line-height:1.4;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#d97706" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          <div>
            <strong style="color:#b45309;">Operational Policy:</strong> Staging an item immediately removes it from borrowing availability and queues it in the Staging Queue for institutional decommissioning.
          </div>
        </div>

      </div>
      <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--color-hairline);background:var(--color-canvas);">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="stageModal" onclick="closeModal('stageModal')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-stage" style="border-radius:var(--radius-pill);background:var(--color-primary);border-color:var(--color-primary);color:#fff;">
          Stage for Disposal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 2: Single Decommission Modal -->
<div id="decommissionModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="decomModalTitle">
  <div class="modal-panel md" style="max-height:90vh;display:flex;flex-direction:column;">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="decomModalTitle" style="color:var(--color-ruby);display:flex;align-items:center;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          Official Asset Decommissioning
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Record formal disposal method, salvage valuation, and move to permanent archive
        </div>
      </div>
      <button type="button" class="modal-close" data-modal-close="decommissionModal" aria-label="Close" onclick="closeModal('decommissionModal')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <form id="decommissionForm" style="display:flex;flex-direction:column;flex:1;overflow-y:auto;margin:0;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="item_id" id="decom_item_id" value="">
      
      <div class="modal-body" style="padding:1.25rem 1.5rem;overflow-y:auto;display:flex;flex-direction:column;gap:1rem;">
        
        <!-- Item Info Capsule -->
        <div class="card-soft" style="padding:10px 14px;border-left:3px solid var(--color-ruby);background:var(--color-surface);">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--color-ink-mute);">Target Asset Summary</div>
          <div style="font-size:14px;font-weight:600;color:var(--color-ink);margin-top:2px;" id="decom_item_title">Dell Optiplex 7080 Desktop</div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" id="decom_item_meta">Property #: SLSU-2026-001 | Category: IT Equipment</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label required" for="decom_method" style="font-weight:500;">
              Disposal Method
            </label>
            <select id="decom_method" name="disposal_method" class="form-input" style="width:100%;font-size:13px;" required>
              <option value="scrapped">Scrapped / Waste Materials</option>
              <option value="sold">Sold / Public Auction</option>
              <option value="donated">Donated</option>
              <option value="cannibalized">Cannibalized (Spare Parts)</option>
              <option value="transferred">Transferred to other Unit</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label required" for="decom_date" style="font-weight:500;">
              Disposal Date
            </label>
            <input type="date" id="decom_date" name="disposal_date" class="form-input" value="<?= date('Y-m-d') ?>" style="width:100%;font-size:13px;" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="decom_salvage" style="font-weight:500;">
            Salvage / Scrap Recovery Value (₱ PHP)
          </label>
          <input
            type="number"
            step="0.01"
            min="0"
            id="decom_salvage"
            name="salvage_value"
            class="form-input"
            value="0.00"
            style="width:100%;font-size:13px;"
            placeholder="0.00"
          >
          <div style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;">
            Estimated or realized appraisal amount if sold/auctioned or scrap recovery.
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label required" for="decom_reason" style="font-weight:500;">
            Official Disposal Reason &amp; Reference
          </label>
          <textarea
            id="decom_reason"
            name="disposal_reason"
            class="form-input"
            rows="2"
            placeholder="e.g. Unserviceable due to component corrosion; Board Resolution #2026-42 approval..."
            required
            style="width:100%;font-size:13px;resize:vertical;"
          ></textarea>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="decom_remarks" style="font-weight:500;">
            Inspection &amp; Witness Remarks
          </label>
          <input
            type="text"
            id="decom_remarks"
            name="remarks"
            class="form-input"
            placeholder="e.g. Inspected by Supply Officer &amp; Property Custodian"
            style="width:100%;font-size:13px;"
          >
        </div>

      </div>
      <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--color-hairline);background:var(--color-canvas);">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="decommissionModal" onclick="closeModal('decommissionModal')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-decom" style="border-radius:var(--radius-pill);background:var(--color-ruby);border-color:var(--color-ruby);color:#fff;">
          Confirm Decommissioning
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 3: Batch Decommission Modal -->
<div id="batchDecommissionModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="batchDecomModalTitle">
  <div class="modal-panel md" style="max-height:90vh;display:flex;flex-direction:column;">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="batchDecomModalTitle" style="color:var(--color-ruby);display:flex;align-items:center;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          Batch Asset Decommissioning
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Decommission multiple staged assets in a single atomic transaction
        </div>
      </div>
      <button type="button" class="modal-close" data-modal-close="batchDecommissionModal" aria-label="Close" onclick="closeModal('batchDecommissionModal')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <form id="batchDecommissionForm" style="display:flex;flex-direction:column;flex:1;overflow-y:auto;margin:0;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <div id="batch-hidden-inputs"></div>
      
      <div class="modal-body" style="padding:1.25rem 1.5rem;overflow-y:auto;display:flex;flex-direction:column;gap:1rem;">
        
        <div style="background:rgba(22, 101, 52, 0.06);border:1px solid rgba(22, 101, 52, 0.2);padding:12px 14px;border-radius:var(--radius-md);">
          <div style="font-size:13px;font-weight:600;color:var(--color-primary);">
            Ready to decommission <span id="batch-modal-count" class="num-tabular">0</span> selected asset(s)
          </div>
          <div style="font-size:12px;color:var(--color-ink-secondary);margin-top:4px;max-height:80px;overflow-y:auto;" id="batch-items-preview">
            <!-- Selected property numbers rendered here -->
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label required" for="batch_method" style="font-weight:500;">
              Disposal Method
            </label>
            <select id="batch_method" name="disposal_method" class="form-input" style="width:100%;font-size:13px;" required>
              <option value="scrapped">Scrapped / Waste Materials</option>
              <option value="sold">Sold / Public Auction</option>
              <option value="donated">Donated</option>
              <option value="cannibalized">Cannibalized (Spare Parts)</option>
              <option value="transferred">Transferred to other Unit</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label required" for="batch_date" style="font-weight:500;">
              Disposal Date
            </label>
            <input type="date" id="batch_date" name="disposal_date" class="form-input" value="<?= date('Y-m-d') ?>" style="width:100%;font-size:13px;" required>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="batch_salvage" style="font-weight:500;">
            Total Batch Salvage / Scrap Recovery Value (₱ PHP)
          </label>
          <input
            type="number"
            step="0.01"
            min="0"
            id="batch_salvage"
            name="salvage_value"
            class="form-input"
            value="0.00"
            style="width:100%;font-size:13px;"
          >
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label required" for="batch_reason" style="font-weight:500;">
            Batch Disposal Justification &amp; Authority
          </label>
          <textarea
            id="batch_reason"
            name="disposal_reason"
            class="form-input"
            rows="2"
            placeholder="e.g. Annual campus unserviceable property bulk condemnation (IIRUP Form #2026-Q4)..."
            required
            style="width:100%;font-size:13px;resize:vertical;"
          ></textarea>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="batch_remarks" style="font-weight:500;">
            Witness / Committee Remarks
          </label>
          <input
            type="text"
            id="batch_remarks"
            name="remarks"
            class="form-input"
            placeholder="e.g. Approved by Disposal and Appraisal Committee"
            style="width:100%;font-size:13px;"
          >
        </div>

      </div>
      <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--color-hairline);background:var(--color-canvas);">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="batchDecommissionModal" onclick="closeModal('batchDecommissionModal')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-batch" style="border-radius:var(--radius-pill);background:var(--color-ruby);border-color:var(--color-ruby);color:#fff;">
          Execute Batch Decommissioning
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 4: Restore Asset Safety Net Modal -->
<div id="restoreModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="restoreModalTitle">
  <div class="modal-panel sm" style="max-height:90vh;display:flex;flex-direction:column;">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="restoreModalTitle" style="color:#059669;display:flex;align-items:center;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
          Reinstate Asset to Active Inventory
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Reverse condemnation and return asset to active circulation
        </div>
      </div>
      <button type="button" class="modal-close" data-modal-close="restoreModal" aria-label="Close" onclick="closeModal('restoreModal')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <form id="restoreForm" style="display:flex;flex-direction:column;flex:1;overflow-y:auto;margin:0;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="item_id" id="restore_item_id" value="">
      
      <div class="modal-body" style="padding:1.25rem 1.5rem;overflow-y:auto;display:flex;flex-direction:column;gap:1rem;">
        
        <div class="card-soft" style="padding:10px 14px;border-left:3px solid #059669;background:var(--color-surface);">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--color-ink-mute);">Target Asset</div>
          <div style="font-size:14px;font-weight:600;color:var(--color-ink);margin-top:2px;" id="restore_item_title">Item Name</div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" id="restore_item_meta">Property #: ---</div>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label required" for="restore_target_status" style="font-weight:500;">
            Target Operational Status
          </label>
          <select id="restore_target_status" name="target_status" class="form-input" style="width:100%;font-size:13px;" required>
            <option value="available">Available (Ready for circulation - Good condition)</option>
            <option value="maintenance">Maintenance (Requires further repair)</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label required" for="restore_reason" style="font-weight:500;">
            Reinstatement Justification
          </label>
          <textarea
            id="restore_reason"
            name="restore_reason"
            class="form-input"
            rows="3"
            placeholder="e.g. Asset successfully refurbished by laboratory technician; mistakenly tagged for condemnation..."
            required
            style="width:100%;font-size:13px;resize:vertical;min-height:75px;"
          ></textarea>
        </div>

      </div>
      <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--color-hairline);background:var(--color-canvas);">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="restoreModal" onclick="closeModal('restoreModal')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" id="btn-submit-restore" style="border-radius:var(--radius-pill);background:#059669;border-color:#059669;color:#ffffff;">
          Confirm Reinstatement
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── 8. CLIENT JAVASCRIPT ENGINE ──────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const CSRF_TOKEN = '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>';
  const IS_EXEC_READONLY = <?= $role === 'executive_admin' ? 'true' : 'false' ?>;

  // Tab State
  let currentTab = 'staged';
  let stagedPage = 1;
  let disposedPage = 1;
  const pageSize = 15;
  let selectedStagedIds = new Set();

  // Method Labels & Badges
  const methodLabels = {
    'scrapped': 'Scrapped / Waste',
    'sold': 'Sold / Auctioned',
    'donated': 'Donated',
    'cannibalized': 'Cannibalized (Parts)',
    'transferred': 'Transferred'
  };

  const methodBadges = {
    'scrapped': 'badge-ruby',
    'sold': 'badge-emerald',
    'donated': 'badge-cyan',
    'cannibalized': 'badge-amber',
    'transferred': 'badge-indigo'
  };

  // ── Helper: Safe HTML Escaping (OWASP Anti-XSS) ─────────────
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // ── Helper: Format Currency ─────────────────────────────────
  function formatMoney(amount) {
    const val = Number(amount) || 0;
    return '₱' + val.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // ── Helper: Safe Toast Notification ────────────────────────
  function notify(type, msg) {
    if (window.Toast && typeof window.Toast[type] === 'function') {
      window.Toast[type](msg);
    } else if (typeof showToast === 'function') {
      showToast(type, msg);
    } else if (window.Toast && typeof window.Toast.show === 'function') {
      window.Toast.show('Notice', msg, { type: type || 'info' });
    }
  }

  // ── Helper: Modal Manager ───────────────────────────────────
  function openModal(id) {
    if (window.Modal && typeof window.Modal.open === 'function') {
      window.Modal.open(id);
    }
  }

  function closeModal(id) {
    if (window.Modal && typeof window.Modal.close === 'function') {
      window.Modal.close(id);
    }
  }

  window.openModal = openModal;
  window.closeModal = closeModal;

  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-modal-close');
      closeModal(target);
    });
  });

  // ── Tab Switching ───────────────────────────────────────────
  const tabBtnStaged = document.getElementById('tab-btn-staged');
  const tabBtnDisposed = document.getElementById('tab-btn-disposed');
  const viewStaged = document.getElementById('view-staged-tab');
  const viewDisposed = document.getElementById('view-disposed-tab');

  function switchTab(tab) {
    currentTab = tab;
    if (tab === 'staged') {
      tabBtnStaged.classList.add('active');
      tabBtnDisposed.classList.remove('active');
      viewStaged.style.display = 'block';
      viewDisposed.style.display = 'none';
      loadStagedItems();
    } else {
      tabBtnDisposed.classList.add('active');
      tabBtnStaged.classList.remove('active');
      viewDisposed.style.display = 'block';
      viewStaged.style.display = 'none';
      loadDisposedItems();
    }
  }

  tabBtnStaged.addEventListener('click', () => switchTab('staged'));
  tabBtnDisposed.addEventListener('click', () => switchTab('disposed'));

  // ── 1. Load Staged Queue ────────────────────────────────────
  async function loadStagedItems() {
    const tbody = document.getElementById('staged-tbody');
    const search = document.getElementById('staged-search-input').value.trim();
    const category = document.getElementById('staged-filter-category').value;

    tbody.innerHTML = `
      <tr>
        <td colspan="${IS_EXEC_READONLY ? 7 : 8}" style="text-align:center;padding:2rem;color:var(--color-ink-mute);">
          <div style="display:inline-flex;align-items:center;gap:8px;">
            <div class="spinner-border spinner-border-sm" role="status"></div> Loading staged assets...
          </div>
        </td>
      </tr>
    `;

    try {
      const params = new URLSearchParams({
        page: stagedPage,
        pageSize: pageSize,
        search: search,
        category: category
      });

      const stagedUrl = (window.appUrl ? window.appUrl('/api/disposal/list-staged') : '/api/disposal/list-staged') + `?${params.toString()}`;
      const res = await fetch(stagedUrl);
      const result = await res.json();

      if (!result.success) {
        throw new Error(result.message || 'Failed to load staged items');
      }

      const { rows, total, totalPages } = result.data;
      document.getElementById('tab-badge-staged').textContent = Number(total).toLocaleString();

      if (rows.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="${IS_EXEC_READONLY ? 7 : 8}" style="text-align:center;padding:3rem;color:var(--color-ink-mute);">
              <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:8px;opacity:0.5;"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4M12 16h.01"></path></svg>
              <div style="font-weight:600;font-size:14px;color:var(--color-ink,#0d253d);">No Assets Currently Staged</div>
              <div style="font-size:12px;margin-top:2px;">All university properties are in active service or resolved.</div>
            </td>
          </tr>
        `;
        renderPagination('staged', 1, 1, 0);
        return;
      }

      let html = '';
      rows.forEach(item => {
        const isChecked = selectedStagedIds.has(item.item_id);
        const dateFormatted = item.updated_at ? new Date(item.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';

        html += `
          <tr class="${isChecked ? 'row-selected' : ''}" data-id="${item.item_id}">
            ${!IS_EXEC_READONLY ? `
              <td style="text-align:center;">
                <input type="checkbox" class="staged-row-checkbox" value="${item.item_id}" ${isChecked ? 'checked' : ''}>
              </td>
            ` : ''}
            <td>
              <div style="font-weight:600;color:var(--color-ink,#0d253d);" class="num-tabular">
                ${escapeHtml(item.property_number || 'N/A')}
              </div>
              <div style="font-size:11px;color:var(--color-ink-mute);" class="num-tabular">
                ${escapeHtml(item.barcode)}
              </div>
            </td>
            <td>
              <div style="font-weight:600;color:var(--color-ink,#0d253d);">${escapeHtml(item.item_name)}</div>
              <div style="font-size:11px;color:var(--color-ink-mute);max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                ${escapeHtml(item.description || 'No description recorded')}
              </div>
            </td>
            <td>
              <span class="badge" style="background:#f1f5f9;color:#475569;font-size:11px;">
                ${escapeHtml(item.category || 'General')}
              </span>
            </td>
            <td style="font-size:13px;color:var(--color-ink,#0d253d);">
              ${escapeHtml(item.location || 'Central Supply')}
            </td>
            <td>
              <span class="badge-condemned">For Disposal</span>
            </td>
            <td style="font-size:12px;color:var(--color-ink-mute);" class="num-tabular">
              ${dateFormatted}
            </td>
            <td style="text-align:right;">
              ${!IS_EXEC_READONLY ? `
                <div style="display:inline-flex;gap:6px;">
                  <button type="button" class="btn btn-primary btn-xs btn-action-decom" 
                    data-id="${item.item_id}" 
                    data-name="${escapeHtml(item.item_name)}" 
                    data-prop="${escapeHtml(item.property_number || item.barcode)}"
                    data-cat="${escapeHtml(item.category)}"
                    style="background:#dc2626;border-color:#dc2626;border-radius:9999px;padding:4px 10px;font-size:11px;">
                    Decommission
                  </button>
                  <button type="button" class="btn btn-ghost btn-xs btn-action-restore" 
                    data-id="${item.item_id}" 
                    data-name="${escapeHtml(item.item_name)}" 
                    data-prop="${escapeHtml(item.property_number || item.barcode)}"
                    style="color:#059669;border-radius:9999px;padding:4px 10px;font-size:11px;" title="Reinstate asset">
                    Restore
                  </button>
                </div>
              ` : `
                <span style="font-size:11px;color:var(--color-ink-mute);">Read-Only</span>
              `}
            </td>
          </tr>
        `;
      });

      tbody.innerHTML = html;
      attachStagedRowListeners();
      renderPagination('staged', stagedPage, totalPages, total);

    } catch (err) {
      tbody.innerHTML = `
        <tr>
          <td colspan="${IS_EXEC_READONLY ? 7 : 8}" style="text-align:center;padding:2rem;color:#e11d48;">
            Failed to load staged assets: ${escapeHtml(err.message)}
          </td>
        </tr>
      `;
    }
  }

  // ── 2. Load Decommissioned Archive ──────────────────────────
  async function loadDisposedItems() {
    const tbody = document.getElementById('disposed-tbody');
    const search = document.getElementById('disposed-search-input').value.trim();
    const method = document.getElementById('disposed-filter-method').value;
    const category = document.getElementById('disposed-filter-category').value;
    const dateFrom = document.getElementById('disposed-date-from').value;
    const dateTo = document.getElementById('disposed-date-to').value;

    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align:center;padding:2rem;color:var(--color-ink-mute);">
          <div style="display:inline-flex;align-items:center;gap:8px;">
            <div class="spinner-border spinner-border-sm" role="status"></div> Loading decommissioned archive...
          </div>
        </td>
      </tr>
    `;

    try {
      const params = new URLSearchParams({
        page: disposedPage,
        pageSize: pageSize,
        search: search,
        method: method,
        category: category,
        date_from: dateFrom,
        date_to: dateTo
      });

      const disposedUrl = (window.appUrl ? window.appUrl('/api/disposal/list-disposed') : '/api/disposal/list-disposed') + `?${params.toString()}`;
      const res = await fetch(disposedUrl);
      const result = await res.json();

      if (!result.success) {
        throw new Error(result.message || 'Failed to load disposed archive');
      }

      const { rows, total, totalPages } = result.data;
      document.getElementById('tab-badge-disposed').textContent = Number(total).toLocaleString();

      if (rows.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="8" style="text-align:center;padding:3rem;color:var(--color-ink-mute);">
              <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:8px;opacity:0.5;"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect></svg>
              <div style="font-weight:600;font-size:14px;color:var(--color-ink,#0d253d);">No Decommissioned Records Found</div>
              <div style="font-size:12px;margin-top:2px;">No historical assets match your active search filters.</div>
            </td>
          </tr>
        `;
        renderPagination('disposed', 1, 1, 0);
        return;
      }

      let html = '';
      rows.forEach(item => {
        const methodKey = (item.disposal_method || 'scrapped').toLowerCase();
        const badgeCls = methodBadges[methodKey] || 'badge-ruby';
        const methodLabel = methodLabels[methodKey] || (methodKey.charAt(0).toUpperCase() + methodKey.slice(1));
        const salvageVal = formatMoney(item.salvage_value);
        const dateFormatted = item.disposal_date || (item.updated_at ? item.updated_at.split(' ')[0] : 'N/A');

        html += `
          <tr data-id="${item.item_id}">
            <td>
              <div style="font-weight:600;color:var(--color-ink,#0d253d);" class="num-tabular">
                ${escapeHtml(item.property_number || 'N/A')}
              </div>
              <div style="font-size:11px;color:var(--color-ink-mute);" class="num-tabular">
                ${escapeHtml(item.barcode)}
              </div>
            </td>
            <td>
              <div style="font-weight:600;color:var(--color-ink,#0d253d);">${escapeHtml(item.item_name)}</div>
              <div style="font-size:11px;color:var(--color-ink-mute);">${escapeHtml(item.category || 'N/A')}</div>
            </td>
            <td>
              <span class="badge" style="background:#f1f5f9;color:#475569;font-size:11px;">
                ${escapeHtml(item.category || 'General')}
              </span>
            </td>
            <td>
              <span class="${badgeCls}">${escapeHtml(methodLabel)}</span>
            </td>
            <td style="font-size:13px;color:var(--color-ink,#0d253d);max-width:260px;">
              ${escapeHtml(item.disposal_reason || 'Decommissioned')}
              ${item.disposal_remarks ? `<div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Note: ${escapeHtml(item.disposal_remarks)}</div>` : ''}
            </td>
            <td style="text-align:right;font-weight:600;color:#047857;" class="num-tabular">
              ${salvageVal}
            </td>
            <td style="font-size:12px;color:var(--color-ink-mute);" class="num-tabular">
              ${escapeHtml(dateFormatted)}
            </td>
            <td style="font-size:12px;color:var(--color-ink,#0d253d);">
              ${escapeHtml(item.disposed_by_name || 'Property Custodian')}
            </td>
          </tr>
        `;
      });

      tbody.innerHTML = html;
      attachDisposedRowListeners();
      renderPagination('disposed', disposedPage, totalPages, total);

    } catch (err) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" style="text-align:center;padding:2rem;color:#e11d48;">
            Failed to load decommissioned archive: ${escapeHtml(err.message)}
          </td>
        </tr>
      `;
    }
  }

  // ── Pagination Renderer ─────────────────────────────────────
  function renderPagination(type, page, totalPages, total) {
    const info = document.getElementById(`${type}-pagination-info`);
    const controls = document.getElementById(`${type}-pagination-controls`);

    const start = total === 0 ? 0 : (page - 1) * pageSize + 1;
    const end = Math.min(page * pageSize, total);
    info.textContent = `Showing ${start}–${end} of ${Number(total).toLocaleString()} asset(s)`;

    let btns = '';
    if (totalPages > 1) {
      btns += `
        <button type="button" class="btn btn-ghost btn-xs" ${page <= 1 ? 'disabled' : ''} data-page="${page - 1}" data-type="${type}" style="border-radius:6px;">
          &laquo; Prev
        </button>
      `;

      for (let p = Math.max(1, page - 2); p <= Math.min(totalPages, page + 2); p++) {
        btns += `
          <button type="button" class="btn ${p === page ? 'btn-primary' : 'btn-ghost'} btn-xs" data-page="${p}" data-type="${type}" style="border-radius:6px;min-width:28px;">
            ${p}
          </button>
        `;
      }

      btns += `
        <button type="button" class="btn btn-ghost btn-xs" ${page >= totalPages ? 'disabled' : ''} data-page="${page + 1}" data-type="${type}" style="border-radius:6px;">
          Next &raquo;
        </button>
      `;
    }

    controls.innerHTML = btns;
    controls.querySelectorAll('button[data-page]').forEach(btn => {
      btn.addEventListener('click', () => {
        const targetP = parseInt(btn.getAttribute('data-page'), 10);
        if (type === 'staged') {
          stagedPage = targetP;
          loadStagedItems();
        } else {
          disposedPage = targetP;
          loadDisposedItems();
        }
      });
    });
  }

  // ── Batch Selection Management ──────────────────────────────
  const batchBar = document.getElementById('batch-actions-bar');
  const selectedCountSpan = document.getElementById('selected-count');
  const selectAllStaged = document.getElementById('select-all-staged');

  function updateBatchBar() {
    if (!batchBar) return;
    const count = selectedStagedIds.size;
    selectedCountSpan.textContent = count;
    if (count > 0) {
      batchBar.classList.remove('hidden');
    } else {
      batchBar.classList.add('hidden');
    }
  }

  if (selectAllStaged) {
    selectAllStaged.addEventListener('change', (e) => {
      const isChecked = e.target.checked;
      document.querySelectorAll('.staged-row-checkbox').forEach(cb => {
        cb.checked = isChecked;
        const id = parseInt(cb.value, 10);
        const tr = cb.closest('tr');
        if (isChecked) {
          selectedStagedIds.add(id);
          tr.classList.add('row-selected');
        } else {
          selectedStagedIds.delete(id);
          tr.classList.remove('row-selected');
        }
      });
      updateBatchBar();
    });
  }

  document.getElementById('btn-deselect-all')?.addEventListener('click', () => {
    selectedStagedIds.clear();
    document.querySelectorAll('.staged-row-checkbox').forEach(cb => {
      cb.checked = false;
      cb.closest('tr').classList.remove('row-selected');
    });
    if (selectAllStaged) selectAllStaged.checked = false;
    updateBatchBar();
  });

  function attachStagedRowListeners() {
    document.querySelectorAll('.staged-row-checkbox').forEach(cb => {
      cb.addEventListener('change', () => {
        const id = parseInt(cb.value, 10);
        const tr = cb.closest('tr');
        if (cb.checked) {
          selectedStagedIds.add(id);
          tr.classList.add('row-selected');
        } else {
          selectedStagedIds.delete(id);
          tr.classList.remove('row-selected');
        }
        updateBatchBar();
      });
    });

    // Attach row events
  }

  function attachDisposedRowListeners() {
    // Dynamic row listeners handled via delegation
  }

  // ── Global Event Delegation for Dynamic Action Buttons ──────
  document.addEventListener('click', (e) => {
    // 1. Single Decommission button click
    const decomBtn = e.target.closest('.btn-action-decom');
    if (decomBtn) {
      e.preventDefault();
      const id = decomBtn.getAttribute('data-id');
      const name = decomBtn.getAttribute('data-name') || '';
      const prop = decomBtn.getAttribute('data-prop') || '';
      const cat = decomBtn.getAttribute('data-cat') || '';

      const idEl = document.getElementById('decom_item_id');
      const titleEl = document.getElementById('decom_item_title');
      const metaEl = document.getElementById('decom_item_meta');
      const salvageEl = document.getElementById('decom_salvage');
      const reasonEl = document.getElementById('decom_reason');
      const remarksEl = document.getElementById('decom_remarks');

      if (idEl) idEl.value = id;
      if (titleEl) titleEl.textContent = name;
      if (metaEl) metaEl.textContent = `Property #: ${prop} | Category: ${cat}`;
      if (salvageEl) salvageEl.value = '0.00';
      if (reasonEl) reasonEl.value = '';
      if (remarksEl) remarksEl.value = '';

      openModal('decommissionModal');
      return;
    }

    // 2. Restore asset button click
    const restoreBtn = e.target.closest('.btn-action-restore');
    if (restoreBtn) {
      e.preventDefault();
      const id = restoreBtn.getAttribute('data-id');
      const name = restoreBtn.getAttribute('data-name') || '';
      const prop = restoreBtn.getAttribute('data-prop') || '';

      const idEl = document.getElementById('restore_item_id');
      const titleEl = document.getElementById('restore_item_title');
      const metaEl = document.getElementById('restore_item_meta');
      const reasonEl = document.getElementById('restore_reason');

      if (idEl) idEl.value = id;
      if (titleEl) titleEl.textContent = name;
      if (metaEl) metaEl.textContent = `Property #: ${prop}`;
      if (reasonEl) reasonEl.value = '';

      openModal('restoreModal');
      return;
    }

    // 3. Top Stage Asset for Disposal button
    const stageBtn = e.target.closest('#btn-open-stage-modal');
    if (stageBtn) {
      e.preventDefault();
      const idEl = document.getElementById('stage_item_id');
      const reasonEl = document.getElementById('stage_reason');
      if (idEl) idEl.value = '';
      if (reasonEl) reasonEl.value = '';
      openModal('stageModal');
      return;
    }
  });

  // ── 3. Stage Asset Form Submission ──────────────────────────
  document.getElementById('stageForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-stage');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Staging...';

    const formData = new FormData(e.target);

    try {
      const stageUrl = window.appUrl ? window.appUrl('/disposal/stage') : '/disposal/stage';
      const res = await fetch(stageUrl, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (!data.success) {
        throw new Error(data.message || 'Failed to stage asset');
      }

      closeModal('stageModal');
      notify('success', data.message || 'Asset queued for disposal.');

      refreshKPIs(data.stats);
      loadStagedItems();
    } catch (err) {
      notify('error', err.message);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Stage for Disposal';
    }
  });

  // ── 4. Single Decommission Form Submission ──────────────────
  document.getElementById('decommissionForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-decom');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';

    const formData = new FormData(e.target);

    try {
      const decomUrl = window.appUrl ? window.appUrl('/disposal/decommission') : '/disposal/decommission';
      const res = await fetch(decomUrl, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (!data.success) {
        throw new Error(data.message || 'Decommissioning failed');
      }

      closeModal('decommissionModal');
      notify('success', data.message || 'Asset officially decommissioned.');

      // Remove from selected set if present
      selectedStagedIds.delete(parseInt(formData.get('item_id'), 10));
      updateBatchBar();

      refreshKPIs(data.stats);
      loadStagedItems();
    } catch (err) {
      notify('error', err.message);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Confirm Decommissioning';
    }
  });

  // ── 5. Batch Decommission Modal Open & Submit ───────────────
  document.getElementById('btn-open-batch-modal')?.addEventListener('click', () => {
    if (selectedStagedIds.size === 0) {
      notify('warning', 'Please select at least one item.');
      return;
    }

    const container = document.getElementById('batch-hidden-inputs');
    container.innerHTML = '';
    const previewList = [];

    selectedStagedIds.forEach(id => {
      const inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'item_ids[]';
      inp.value = id;
      container.appendChild(inp);

      const tr = document.querySelector(`tr[data-id="${id}"]`);
      if (tr) {
        const prop = tr.querySelector('td:nth-child(2) div:first-child')?.textContent.trim() || `#${id}`;
        const name = tr.querySelector('td:nth-child(3) div:first-child')?.textContent.trim() || '';
        previewList.push(`• ${prop} (${name})`);
      }
    });

    document.getElementById('batch-modal-count').textContent = selectedStagedIds.size;
    document.getElementById('batch-items-preview').innerHTML = previewList.join('<br>');
    document.getElementById('batch_salvage').value = '0.00';
    document.getElementById('batch_reason').value = '';
    document.getElementById('batch_remarks').value = '';

    openModal('batchDecommissionModal');
  });

  document.getElementById('batchDecommissionForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-batch');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Decommissioning Batch...';

    const formData = new FormData(e.target);

    try {
      const batchUrl = window.appUrl ? window.appUrl('/disposal/batch-decommission') : '/disposal/batch-decommission';
      const res = await fetch(batchUrl, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (!data.success) {
        throw new Error(data.message || 'Batch decommissioning failed');
      }

      closeModal('batchDecommissionModal');
      selectedStagedIds.clear();
      updateBatchBar();

      notify('success', data.message || 'Batch assets decommissioned.');

      refreshKPIs(data.stats);
      loadStagedItems();
    } catch (err) {
      notify('error', err.message);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Execute Batch Decommissioning';
    }
  });

  // ── 6. Restore Form Submission ──────────────────────────────
  document.getElementById('restoreForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-restore');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Reinstating...';

    const formData = new FormData(e.target);

    try {
      const restoreUrl = window.appUrl ? window.appUrl('/disposal/restore') : '/disposal/restore';
      const res = await fetch(restoreUrl, {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (!data.success) {
        throw new Error(data.message || 'Restoration failed');
      }

      closeModal('restoreModal');
      notify('success', data.message || 'Asset reinstated to active inventory.');

      refreshKPIs(data.stats);
      if (currentTab === 'staged') {
        loadStagedItems();
      } else {
        loadDisposedItems();
      }
    } catch (err) {
      notify('error', err.message);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Confirm Reinstatement';
    }
  });

  // ── Helper: Refresh KPI Cards ───────────────────────────────
  function refreshKPIs(stats) {
    if (!stats) return;
    const cards = document.querySelectorAll('.stat-card-value');
    if (cards.length >= 4) {
      cards[0].textContent = Number(stats.staged_count || 0).toLocaleString();
      cards[1].textContent = Number(stats.total_disposed || 0).toLocaleString();
      cards[2].textContent = Number(stats.scrapped_count || 0).toLocaleString();
      cards[3].textContent = formatMoney(stats.total_salvage_value || 0);
    }
    document.getElementById('tab-badge-staged').textContent = Number(stats.staged_count || 0).toLocaleString();
    document.getElementById('tab-badge-disposed').textContent = Number(stats.total_disposed || 0).toLocaleString();
  }

  // ── Debounced Search & Filter Listeners ─────────────────────
  let searchTimer;
  function debounce(func, delay = 300) {
    return function (...args) {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  document.getElementById('staged-search-input')?.addEventListener('input', debounce(() => {
    stagedPage = 1;
    loadStagedItems();
  }));

  document.getElementById('staged-filter-category')?.addEventListener('change', () => {
    stagedPage = 1;
    loadStagedItems();
  });

  document.getElementById('btn-refresh-staged')?.addEventListener('click', () => {
    loadStagedItems();
  });

  document.getElementById('disposed-search-input')?.addEventListener('input', debounce(() => {
    disposedPage = 1;
    loadDisposedItems();
  }));

  document.getElementById('disposed-filter-method')?.addEventListener('change', () => {
    disposedPage = 1;
    loadDisposedItems();
  });

  document.getElementById('disposed-filter-category')?.addEventListener('change', () => {
    disposedPage = 1;
    loadDisposedItems();
  });

  document.getElementById('disposed-date-from')?.addEventListener('change', () => {
    disposedPage = 1;
    loadDisposedItems();
  });

  document.getElementById('disposed-date-to')?.addEventListener('change', () => {
    disposedPage = 1;
    loadDisposedItems();
  });

  document.getElementById('btn-refresh-disposed')?.addEventListener('click', () => {
    loadDisposedItems();
  });

  // Initial Load
  loadStagedItems();
});
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
