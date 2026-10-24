<?php
/**
 * Asset Catalog — Property Custodian & Supply Office Control Center
 * Displays KPI summary cards, searchable/filterable inventory DataTables,
 * Single/Batch Registration, CSV Bulk Intake, and Optimistic-Locked Editing.
 */

$title      = 'Asset Catalog';
$breadcrumb = 'Asset Catalog';

require __DIR__ . '/../layouts/shell.php';
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="section-header">
  <div>
    <h1 class="section-title">Asset Catalog &amp; Properties Registry</h1>
    <div class="section-subtitle">Centralized single source of truth for SLSU-Alabat campus physical inventory</div>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
    <?php if ($role === 'executive_admin'): ?>
      <span class="badge" style="background:var(--color-canvas-soft, #f1f5f9);color:var(--color-ink-mute, #64748b);border:1px solid var(--color-hairline, #e2e8f0);padding:5px 12px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border-radius:20px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        Executive Read-Only View
      </span>
      <a href="<?= url('/inventory/export-xlsx') ?>" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;" title="Export official institutional report to Microsoft Excel (.xlsx)">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        Export Catalog (.xlsx)
      </a>
    <?php elseif (in_array($role, ['faculty', 'student'], true)): ?>
      <span class="badge" style="background:var(--color-canvas-soft, #f1f5f9);color:var(--color-ink-mute, #64748b);border:1px solid var(--color-hairline, #e2e8f0);padding:5px 12px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border-radius:20px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <?= $role === 'faculty' ? 'Faculty' : 'Student' ?> Read-Only View
      </span>
    <?php else: ?>
      <a href="<?= url('/inventory/export-xlsx') ?>" class="btn btn-ghost btn-sm" title="Export official institutional report to Microsoft Excel (.xlsx)" style="display:inline-flex;align-items:center;gap:6px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        Export Excel (.xlsx)
      </a>
      <button type="button" class="btn btn-ghost btn-sm" id="btn-open-csv-import">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
        Import CSV
      </button>
      <button type="button" class="btn btn-primary btn-sm" id="btn-open-register-modal">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Register New Asset
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- ── Certified IAR Deliveries Awaiting Custodian Ingestion ── -->
<?php if (!empty($pendingIars) && $role === 'custodian'): ?>
  <div class="glass-card" style="background:var(--color-gold-light, #fef3c7);border:1px solid rgba(217,119,6,0.3);padding:14px 20px;border-radius:12px;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;box-shadow:var(--shadow-1);">
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:38px;height:38px;border-radius:50%;background:#ffffff;border:1px solid rgba(217,119,6,0.3);display:flex;align-items:center;justify-content:center;color:var(--color-gold-deep,#b45309);flex-shrink:0;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
      </div>
      <div>
        <div style="font-size:14px;font-weight:500;color:var(--color-ink,#0f291e);">
          <?= count($pendingIars) ?> Certified Shipment(s) Transmitted by Local Inspector Awaiting Asset Registration
        </div>
        <div style="font-size:12px;color:var(--color-gold-deep,#b45309);">
          Form AFA-SAF-1.016 inspection reports are wet-signed and ready for barcode tagging and inventory intake.
        </div>
      </div>
    </div>

    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <?php foreach ($pendingIars as $pi): ?>
        <button type="button" class="btn btn-sm btn-open-ingest-iar" data-iar-id="<?= $pi['id'] ?>" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:6px 14px;background:var(--color-primary,#166534);color:#ffffff;border:none;cursor:pointer;font-size:12px;">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          Tag &amp; Ingest <?= htmlspecialchars($pi['iar_no'], ENT_QUOTES, 'UTF-8') ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- ── Top KPI Stat Cards ──────────────────────────────────── -->
<div class="grid-kpi-stats stagger-children" id="kpi-stats-grid" style="margin-bottom:1.5rem;">
  <?php
  $statCards = [
    ['title' => 'Total Registered Assets', 'value' => number_format((int)($stats['total'] ?? 0)),       'icon' => 'package',        'color' => 'indigo', 'trend' => 'Catalog SSOT', 'trend_dir' => 'up'],
    ['title' => 'Ready & Available',       'value' => number_format((int)($stats['healthy'] ?? 0)),     'icon' => 'clipboard-list', 'color' => 'emerald','trend' => 'Mint & Good',  'trend_dir' => 'up'],
    ['title' => 'Under Maintenance',       'value' => number_format((int)($stats['maintenance'] ?? 0)), 'icon' => 'tool',           'color' => 'amber',  'trend' => 'Damaged / Defect', 'trend_dir' => 'down'],
    ['title' => 'Pending Disposal',        'value' => number_format((int)($stats['disposal'] ?? 0)),    'icon' => 'trash-2',        'color' => 'ruby',   'trend' => 'Condemned',   'trend_dir' => 'down'],
  ];
  foreach ($statCards as $stat):
    $props = $stat;
    require __DIR__ . '/../components/stat-card.php';
  endforeach;
  ?>
</div>

<!-- ── Asset Data Table Container ─────────────────────────── -->
<div class="data-table-wrap animate-fade-in-up" id="assets-table-wrap">
  
  <!-- Toolbar: Search & Filters -->
  <div class="data-table-toolbar">
    
    <!-- Search Bar -->
    <div class="data-table-search">
      <span class="data-table-search-icon">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </span>
      <input
        type="text"
        id="catalog-search-input"
        class="form-input data-table-search-input"
        placeholder="Search by asset name, barcode, property #, or location..."
        autocomplete="off"
      >
    </div>

    <!-- Filter Dropdown: Category -->
    <select id="filter-category" class="form-input" style="width:auto;min-width:140px;height:36px;padding-top:0;padding-bottom:0;font-size:12px;">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>

    <!-- Filter Dropdown: Condition (Health) -->
    <select id="filter-condition" class="form-input" style="width:auto;min-width:130px;height:36px;padding-top:0;padding-bottom:0;font-size:12px;">
      <option value="">All Conditions</option>
      <option value="mint">Mint Condition</option>
      <option value="good">Good Condition</option>
      <option value="fair">Fair Condition</option>
      <option value="damaged">Damaged</option>
      <option value="not_working">Not Working</option>
    </select>

    <!-- Filter Dropdown: Status (Availability) -->
    <select id="filter-status" class="form-input" style="width:auto;min-width:120px;height:36px;padding-top:0;padding-bottom:0;font-size:12px;">
      <option value="">All Statuses</option>
      <option value="available">Available</option>
      <option value="borrowed">Borrowed</option>
      <option value="maintenance">Maintenance</option>
    </select>

    <!-- Quick Refresh Button -->
    <button type="button" id="btn-refresh-catalog" class="btn btn-ghost btn-icon-sm" title="Refresh Table" style="flex-shrink:0;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
    </button>
  </div>

  <!-- Table Body -->
  <div class="data-table-body" style="position:relative;min-height:280px;">
    
    <!-- Table Loading Skeleton Overlay -->
    <div id="table-loading-overlay" style="display:none;position:absolute;inset:0;background:rgba(255,255,255,0.7);backdrop-filter:blur(2px);z-index:2;align-items:center;justify-content:center;">
      <div style="font-size:13px;font-weight:500;color:var(--color-primary);display:flex;align-items:center;gap:.5rem;">
        <svg class="animate-spin" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.2"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg>
        Loading assets...
      </div>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th style="width:130px;">Barcode</th>
          <th>Item Name &amp; Category</th>
          <th>Storage Location</th>
          <th>Property Number</th>
          <th style="width:130px;">Health Condition</th>
          <th style="width:110px;">Status</th>
          <th style="width:120px;text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody id="catalog-table-body">
        <!-- Rows Injected Dynamically via JS -->
      </tbody>
    </table>

    <!-- Empty State Container -->
    <div id="catalog-empty-state" style="display:none;padding:3rem 1.5rem;text-align:center;">
      <div style="width:48px;height:48px;border-radius:50%;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--color-ink-mute);">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      </div>
      <div style="font-size:14px;font-weight:500;color:var(--color-ink);margin-bottom:2px;">No matching assets found</div>
      <div style="font-size:12px;color:var(--color-ink-mute);">Try adjusting your search criteria or register a new asset.</div>
    </div>
  </div>

  <!-- Pagination Footer -->
  <div class="data-table-footer">
    <div class="data-table-count" id="catalog-pagination-info">
      Showing 0 of 0 entries
    </div>
    <div style="display:flex;align-items:center;gap:.5rem;">
      <button type="button" id="btn-page-prev" class="btn btn-ghost btn-sm" disabled style="height:30px;padding:0 .625rem;font-size:12px;">
        &larr; Previous
      </button>
      <span id="catalog-page-num" style="font-size:12px;font-weight:500;color:var(--color-ink);padding:0 .5rem;">
        Page 1 of 1
      </span>
      <button type="button" id="btn-page-next" class="btn btn-ghost btn-sm" disabled style="height:30px;padding:0 .625rem;font-size:12px;">
        Next &rarr;
      </button>
    </div>
  </div>

</div>

<!-- ── Include Modals ─────────────────────────────────────── -->
<?php 
require __DIR__ . '/modals/register-modal.php'; 
require __DIR__ . '/modals/edit-modal.php'; 
require __DIR__ . '/modals/delete-modal.php'; 
require __DIR__ . '/modals/maintenance-modal.php'; 
require __DIR__ . '/modals/iar-ingest-modal.php'; 
require __DIR__ . '/modals/lost-modal.php'; 
?>

<!-- ── JsBarcode Library CDN for Barcode SVG Rendering ──────── -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<!-- ── Interactive Catalog Engine Script ────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const isReadOnly = <?= json_encode(in_array($role, ['executive_admin', 'faculty', 'student'], true)) ?>;
  const isExecutive = <?= json_encode($role === 'executive_admin') ?>;
  const readOnlyBadgeText = <?= json_encode($role === 'executive_admin' ? 'Audited' : 'Read-Only') ?>;
  
  // State
  let currentPage = 1;
  let totalPages = 1;
  let searchDebounceTimer = null;
  let propCheckTimer = null;
  let currentRegisteredItems = [];
  let currentItemsMap = {};

  // DOM Elements
  const searchInput    = document.getElementById('catalog-search-input');
  const catFilter      = document.getElementById('filter-category');
  const condFilter     = document.getElementById('filter-condition');
  const statusFilter   = document.getElementById('filter-status');
  const tableBody      = document.getElementById('catalog-table-body');
  const emptyState     = document.getElementById('catalog-empty-state');
  const loadingOverlay = document.getElementById('table-loading-overlay');
  const paginationInfo = document.getElementById('catalog-pagination-info');
  const pageNumDisplay = document.getElementById('catalog-page-num');
  const btnPrev        = document.getElementById('btn-page-prev');
  const btnNext        = document.getElementById('btn-page-next');
  const btnRefresh     = document.getElementById('btn-refresh-catalog');

  // Modals
  const registerModal = document.getElementById('register-asset-modal');
  const editModal     = document.getElementById('edit-asset-modal');

  // Modal Open/Close triggers
  document.getElementById('btn-open-register-modal')?.addEventListener('click', () => {
    resetRegisterModal();
    showTab('form');
    openModal('register-asset-modal');
  });

  document.getElementById('btn-open-csv-import')?.addEventListener('click', () => {
    resetRegisterModal();
    showTab('csv');
    openModal('register-asset-modal');
  });

  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', function () {
      const modalId = this.getAttribute('data-modal-close');
      closeModal(modalId);
    });
  });

  function openModal(id) {
    if (window.Modal && typeof window.Modal.open === 'function') {
      window.Modal.open(id);
    } else {
      const el = document.getElementById(id);
      if (el) {
        el.classList.add('open');
        document.body.classList.add('modal-open');
      }
    }
  }

  function closeModal(id) {
    if (window.Modal && typeof window.Modal.close === 'function') {
      window.Modal.close(id);
    } else {
      const el = document.getElementById(id);
      if (el) {
        el.classList.remove('open');
        document.body.classList.remove('modal-open');
      }
    }
  }

  window.openModal = openModal;
  window.closeModal = closeModal;

  // Tab switching in Register Modal
  const tabForm = document.getElementById('tab-btn-form');
  const tabCsv  = document.getElementById('tab-btn-csv');
  const viewForm = document.getElementById('register-asset-form');
  const viewCsv  = document.getElementById('register-csv-view');
  const viewSuccess = document.getElementById('register-success-view');

  tabForm?.addEventListener('click', () => showTab('form'));
  tabCsv?.addEventListener('click', () => showTab('csv'));

  function showTab(type) {
    if (viewSuccess) viewSuccess.style.display = 'none';
    if (type === 'form') {
      tabForm.style.borderBottomColor = 'var(--color-primary)';
      tabForm.style.color = 'var(--color-primary)';
      tabCsv.style.borderBottomColor = 'transparent';
      tabCsv.style.color = 'var(--color-ink-mute)';
      viewForm.style.display = 'block';
      viewCsv.style.display = 'none';
    } else {
      tabCsv.style.borderBottomColor = 'var(--color-primary)';
      tabCsv.style.color = 'var(--color-primary)';
      tabForm.style.borderBottomColor = 'transparent';
      tabForm.style.color = 'var(--color-ink-mute)';
      viewForm.style.display = 'none';
      viewCsv.style.display = 'block';
    }
  }

  function resetRegisterModal() {
    document.getElementById('register-asset-form')?.reset();
    document.getElementById('import-csv-form')?.reset();
    const alertBox = document.getElementById('register-modal-alert');
    if (alertBox) alertBox.style.display = 'none';
    const propIndicator = document.getElementById('prop-check-indicator');
    if (propIndicator) {
      propIndicator.textContent = 'Optional';
      propIndicator.style.color = 'var(--color-ink-mute)';
    }
    const csvName = document.getElementById('csv-selected-name');
    if (csvName) csvName.style.display = 'none';
  }

  // Live Property Number Conflict Check
  const propInput = document.getElementById('reg-property-number');
  propInput?.addEventListener('input', function () {
    clearTimeout(propCheckTimer);
    const val = this.value.trim();
    const indicator = document.getElementById('prop-check-indicator');
    if (!val) {
      if (indicator) {
        indicator.textContent = 'Optional';
        indicator.style.color = 'var(--color-ink-mute)';
      }
      return;
    }

    if (indicator) {
      indicator.textContent = 'Checking...';
      indicator.style.color = 'var(--color-primary)';
    }

    propCheckTimer = setTimeout(() => {
      const checkUrl = (window.appUrl ? window.appUrl('/api/inventory/check-property') : '/api/inventory/check-property') + '?property_number=' + encodeURIComponent(val);
      fetch(checkUrl)
        .then(res => res.json())
        .then(res => {
          if (!indicator) return;
          if (res.exists) {
            indicator.textContent = 'Already in use (' + res.registrant + ')';
            indicator.style.color = 'var(--color-ruby)';
          } else {
            indicator.textContent = 'Available';
            indicator.style.color = 'var(--color-success)';
          }
        })
        .catch(() => {
          if (indicator) indicator.textContent = 'Optional';
        });
    }, 400);
  });

  // CSV File select feedback
  const csvFileInput = document.getElementById('csv-file-input');
  csvFileInput?.addEventListener('change', function () {
    const nameBox = document.getElementById('csv-selected-name');
    if (this.files && this.files[0]) {
      if (nameBox) {
        nameBox.textContent = 'Selected: ' + this.files[0].name + ' (' + (this.files[0].size / 1024).toFixed(1) + ' KB)';
        nameBox.style.display = 'block';
      }
    }
  });

  // Sample CSV Download generator
  document.getElementById('download-sample-csv-btn')?.addEventListener('click', () => {
    const csvContent = "data:text/csv;charset=utf-8," 
      + "item_name,category,location,property_number,description,condition\n"
      + "Digital Storage Oscilloscope,IT Equipment,Room 204,SLSU-IT-2026-101,100MHz 2-Channel,mint\n"
      + "Vernier Caliper Stainless,Laboratory Tools,Tool Room B,SLSU-LAB-2026-102,0.02mm precision,good\n"
      + "Soldering Station 60W,Laboratory Tools,Room 204,SLSU-LAB-2026-103,Adjustable temperature,good";
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "slsu_inventory_sample.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });

  // ── Fetch & Render Asset Catalog ──────────────────────────
  function fetchCatalog(page = 1) {
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    const params = new URLSearchParams({
      page: page,
      pageSize: 10,
      search: searchInput ? searchInput.value.trim() : '',
      category: catFilter ? catFilter.value : '',
      condition: condFilter ? condFilter.value : '',
      status: statusFilter ? statusFilter.value : '',
    });

    const listUrl = (window.appUrl ? window.appUrl('/api/inventory/list') : '/api/inventory/list') + '?' + params.toString();
    fetch(listUrl)
      .then(res => res.json())
      .then(data => {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        if (!data.success) {
          showToast('error', 'Error loading catalog: ' + (data.message || 'Server error'));
          return;
        }

        renderTableRows(data.data.rows);
        updatePagination(data.data);
      })
      .catch(err => {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        showToast('error', 'Network error loading catalog.');
      });
  }

  function renderTableRows(rows) {
    if (!tableBody) return;
    tableBody.innerHTML = '';

    if (!rows || rows.length === 0) {
      if (emptyState) emptyState.style.display = 'block';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';

    currentItemsMap = {};
    rows.forEach(item => {
      currentItemsMap[item.item_id] = item;
      const tr = document.createElement('tr');
      tr.setAttribute('data-item-id', item.item_id);
      tr.id = 'asset-row-' + item.item_id;
      
      const condBadge = getConditionBadgeHtml(item.item_condition);
      const statusBadge = getStatusBadgeHtml(item.status);

      tr.innerHTML = `
        <td class="font-mono" style="font-weight:500;color:var(--color-primary);">
          ${escapeHtml(item.barcode)}
        </td>
        <td>
          <div style="font-weight:400;color:var(--color-ink);">${escapeHtml(item.item_name)}</div>
          <div style="font-size:11px;color:var(--color-ink-mute);">${escapeHtml(item.category)}</div>
        </td>
        <td style="font-size:13px;color:var(--color-ink-secondary);">
          ${escapeHtml(item.location)}
        </td>
        <td class="font-mono" style="font-size:12px;color:var(--color-ink-mute);">
          ${item.property_number ? escapeHtml(item.property_number) : '<span style="color:var(--color-hairline-input);">—</span>'}
        </td>
        <td>${condBadge}</td>
        <td>${statusBadge}</td>
        <td style="text-align:right;white-space:nowrap;">
          ${isReadOnly ? `
            <span class="badge" style="background:var(--color-canvas-soft, #f8fafc);color:var(--color-ink-mute, #64748b);border:1px solid var(--color-hairline, #e2e8f0);padding:3px 8px;font-size:11px;font-weight:500;border-radius:4px;display:inline-flex;align-items:center;gap:4px;">
              <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
              ${readOnlyBadgeText}
            </span>
          ` : `
            <div style="display:inline-flex;align-items:center;justify-content:flex-end;gap:4px;">
              ${item.status === 'maintenance' ? `
                <div style="display:inline-flex;align-items:center;background:rgba(217,119,6,0.06);border:1px solid rgba(217,119,6,0.22);border-radius:var(--radius-pill);padding:2px 4px;margin-right:2px;gap:2px;" title="Maintenance Triage Actions">
                  <button type="button" class="btn btn-ghost btn-icon-sm btn-restore-asset" title="Restore to Available (Maintenance Complete & Tested)" data-item-id="${item.item_id}" style="color:#059669;width:24px;height:24px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:var(--radius-pill);" aria-label="Restore Asset to Available">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                  </button>
                  <button type="button" class="btn btn-ghost btn-icon-sm btn-ber-asset" title="Declare Beyond Economic Repair (Condemn to Disposal Queue)" data-item-id="${item.item_id}" style="color:#d97706;width:24px;height:24px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:var(--radius-pill);" aria-label="Declare Beyond Economic Repair">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                  </button>
                </div>
              ` : ''}
              ${item.status === 'borrowed' ? `
                <button type="button" class="btn btn-ghost btn-icon-sm" disabled aria-disabled="true" title="Asset is currently borrowed. Modifying details or physical health is locked until returned." style="opacity:0.35;cursor:not-allowed;">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </button>
              ` : `
                <button type="button" class="btn btn-ghost btn-icon-sm btn-edit-asset" title="Edit Asset Details" data-item-id="${item.item_id}">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
              `}
              <button type="button" class="btn btn-ghost btn-icon-sm btn-print-single" title="Print Barcode Label" data-barcode="${escapeHtml(item.barcode)}" data-name="${escapeHtml(item.item_name)}" data-prop="${escapeHtml(item.property_number || '')}" data-cat="${escapeHtml(item.category)}" data-room="${escapeHtml(item.location)}">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
              </button>
              ${item.status !== 'lost' && item.status !== 'disposed' ? `
                <button type="button" class="btn btn-ghost btn-icon-sm btn-report-lost" title="Report Lost or Missing Property" data-item-id="${item.item_id}" style="color:var(--color-ruby, #dc2626);">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </button>
              ` : ''}
              ${item.status === 'borrowed' ? `
                <button type="button" class="btn btn-ghost btn-icon-sm" disabled aria-disabled="true" title="Asset is currently borrowed. Cannot be deleted or decommissioned until returned." style="opacity:0.35;cursor:not-allowed;color:var(--color-ruby);">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              ` : `
                <button type="button" class="btn btn-ghost btn-icon-sm btn-delete-asset" title="Delete or Archive Asset Record" data-item-id="${item.item_id}" style="color:var(--color-ruby);">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              `}
            </div>
          `}
        </td>
      `;
      tableBody.appendChild(tr);
    });

    if (isReadOnly) return;

    // Attach row action listeners
    document.querySelectorAll('.btn-restore-asset').forEach(btn => {
      btn.addEventListener('click', function () {
        const itemId = this.getAttribute('data-item-id');
        const item = currentItemsMap[itemId];
        if (item) openRestoreModal(item);
      });
    });

    document.querySelectorAll('.btn-ber-asset').forEach(btn => {
      btn.addEventListener('click', function () {
        const itemId = this.getAttribute('data-item-id');
        const item = currentItemsMap[itemId];
        if (item) openBerModal(item);
      });
    });

    document.querySelectorAll('.btn-edit-asset').forEach(btn => {
      btn.addEventListener('click', function () {
        if (this.disabled) return;
        const itemId = this.getAttribute('data-item-id');
        const item = currentItemsMap[itemId];
        if (item) {
          if (item.status === 'borrowed') {
            if (window.alertDialog) {
              window.alertDialog('Asset is currently borrowed. Modifying asset specifications or physical condition is locked until the item has been returned.', 'Action Locked', 'Asset Currently Borrowed');
            } else {
              showToast('warning', 'Asset is currently borrowed and cannot be modified.');
            }
            return;
          }
          openEditModal(item);
        }
      });
    });

    document.querySelectorAll('.btn-print-single').forEach(btn => {
      btn.addEventListener('click', function () {
        const item = {
          barcode: this.getAttribute('data-barcode'),
          item_name: this.getAttribute('data-name'),
          property_number: this.getAttribute('data-prop'),
          category: this.getAttribute('data-cat'),
          location: this.getAttribute('data-room'),
        };
        printBarcodeLabels([item]);
      });
    });

    document.querySelectorAll('.btn-report-lost').forEach(btn => {
      btn.addEventListener('click', function () {
        const itemId = this.getAttribute('data-item-id');
        const item = currentItemsMap[itemId];
        if (item) openLostModal(item);
      });
    });

    document.querySelectorAll('.btn-delete-asset').forEach(btn => {
      btn.addEventListener('click', function () {
        if (this.disabled) return;
        const itemId = this.getAttribute('data-item-id');
        const item = currentItemsMap[itemId];
        if (item) {
          if (item.status === 'borrowed') {
            if (window.alertDialog) {
              window.alertDialog('Asset is currently borrowed. Assets cannot be deleted or decommissioned while actively borrowed.', 'Action Prohibited', 'Active Borrow Restriction');
            } else {
              showToast('warning', 'Asset is currently borrowed and cannot be deleted.');
            }
            return;
          }
          openDeleteModal(item);
        }
      });
    });
  }

  function getConditionBadgeHtml(cond) {
    const c = (cond || 'good').toLowerCase();
    const map = {
      'mint':         { cls: 'badge-approved',    lbl: 'Mint Condition' },
      'good':         { cls: 'badge-approved',    lbl: 'Good Condition' },
      'fair':         { cls: 'badge-pending',     lbl: 'Fair Condition' },
      'damaged':      { cls: 'badge-maintenance', lbl: 'Damaged' },
      'not_working':  { cls: 'badge-rejected',    lbl: 'Not Working' },
      'for_disposal': { cls: 'badge-suspended',   lbl: 'Condemned' },
      'condemned':    { cls: 'badge-suspended',   lbl: 'Condemned' },
    };
    const b = map[c] || { cls: 'badge-pending', lbl: c };
    return `<span class="badge ${b.cls}">${b.lbl}</span>`;
  }

  function getStatusBadgeHtml(status) {
    const s = (status || 'available').toLowerCase();
    const map = {
      'available':    { cls: 'badge-approved',    lbl: 'Available' },
      'borrowed':     { cls: 'badge-borrowed',    lbl: 'Borrowed' },
      'maintenance':  { cls: 'badge-maintenance', lbl: 'Maintenance' },
      'for_disposal': { cls: 'badge-suspended',   lbl: 'For Disposal' },
      'disposed':     { cls: 'badge-suspended',   lbl: 'Disposed' },
    };
    const b = map[s] || { cls: 'badge-pending', lbl: s };
    return `<span class="badge ${b.cls}">${b.lbl}</span>`;
  }

  function updatePagination(data) {
    currentPage = data.currentPage;
    totalPages  = data.totalPages;

    const start = data.total > 0 ? ((currentPage - 1) * data.pageSize) + 1 : 0;
    const end   = Math.min(currentPage * data.pageSize, data.total);

    if (paginationInfo) {
      paginationInfo.textContent = `Showing ${start} to ${end} of ${data.total} assets`;
    }
    if (pageNumDisplay) {
      pageNumDisplay.textContent = `Page ${currentPage} of ${totalPages}`;
    }

    if (btnPrev) btnPrev.disabled = (currentPage <= 1);
    if (btnNext) btnNext.disabled = (currentPage >= totalPages);
  }

  // Filter Listeners
  searchInput?.addEventListener('input', () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => fetchCatalog(1), 350);
  });

  catFilter?.addEventListener('change', () => fetchCatalog(1));
  condFilter?.addEventListener('change', () => fetchCatalog(1));
  statusFilter?.addEventListener('change', () => fetchCatalog(1));
  btnRefresh?.addEventListener('click', () => fetchCatalog(currentPage));

  btnPrev?.addEventListener('click', () => {
    if (currentPage > 1) fetchCatalog(currentPage - 1);
  });
  btnNext?.addEventListener('click', () => {
    if (currentPage < totalPages) fetchCatalog(currentPage + 1);
  });

  // ── Single / Batch Form Submission ────────────────────────
  const regForm = document.getElementById('register-asset-form');
  regForm?.addEventListener('submit', function (e) {
    e.preventDefault();
    const submitBtn = document.getElementById('reg-submit-btn');
    const alertBox  = document.getElementById('register-modal-alert');
    if (alertBox) alertBox.style.display = 'none';

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Registering & Generating Barcodes...';
    }

    const formData = new FormData(this);
    const regUrl = window.appUrl ? window.appUrl('/inventory/register') : '/inventory/register';

    fetch(regUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(res => res.json())
    .then(res => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '+ Save & Generate Barcodes';
      }

      if (!res.success) {
        if (alertBox) {
          alertBox.className = 'bg-red-50 text-red-800 border border-red-200';
          alertBox.style.display = 'block';
          alertBox.textContent = res.message || 'Registration failed.';
        }
        return;
      }

      // Success: render printable barcode cards
      currentRegisteredItems = res.created_items || [];
      renderSuccessBarcodes(currentRegisteredItems);
      fetchCatalog(1);
      showToast('success', res.message);
    })
    .catch(err => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '+ Save & Generate Barcodes';
      }
      if (alertBox) {
        alertBox.className = 'bg-red-50 text-red-800 border border-red-200';
        alertBox.style.display = 'block';
        alertBox.textContent = 'A network error occurred. Please try again.';
      }
    });
  });

  function renderSuccessBarcodes(items) {
    if (viewForm) viewForm.style.display = 'none';
    if (viewCsv)  viewCsv.style.display = 'none';
    if (viewSuccess) viewSuccess.style.display = 'block';

    const container = document.getElementById('generated-barcodes-container');
    if (!container) return;
    container.innerHTML = '';

    items.forEach((item, index) => {
      const card = document.createElement('div');
      card.className = 'barcode-card';
      card.style.background = '#ffffff';
      card.style.padding = '.75rem';
      card.style.border = '1px solid var(--color-hairline)';
      card.style.borderRadius = 'var(--radius-md)';
      card.style.textAlign = 'center';

      const svgId = `barcode-svg-${index}`;

      card.innerHTML = `
        <div style="font-size:9px;font-weight:600;color:var(--color-ink);letter-spacing:.3px;">SLSU - Alabat Campus</div>
        <div style="font-size:8px;color:var(--color-ink-mute);margin-bottom:.25rem;">Property &amp; Inventory System</div>
        <svg id="${svgId}" style="width:100%;height:44px;"></svg>
        <div style="font-size:11px;font-weight:500;color:var(--color-ink);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(item.item_name)}</div>
        <div class="font-mono" style="font-size:10px;color:var(--color-ink-mute);">${item.property_number ? escapeHtml(item.property_number) : escapeHtml(item.barcode)}</div>
      `;
      container.appendChild(card);

      // Render Barcode via JsBarcode
      setTimeout(() => {
        try {
          JsBarcode(`#${svgId}`, item.barcode, {
            format: "CODE128",
            width: 1.5,
            height: 38,
            displayValue: true,
            fontSize: 10,
            font: "monospace",
            margin: 4,
          });
        } catch (e) {
          console.error("Barcode render error", e);
        }
      }, 50);
    });
  }

  document.getElementById('btn-register-another')?.addEventListener('click', () => {
    resetRegisterModal();
    showTab('form');
  });

  document.getElementById('btn-print-registered-barcodes')?.addEventListener('click', () => {
    if (currentRegisteredItems.length > 0) {
      printBarcodeLabels(currentRegisteredItems);
    }
  });

  // ── CSV Bulk Import Submission ────────────────────────────
  const csvForm = document.getElementById('import-csv-form');
  csvForm?.addEventListener('submit', function (e) {
    e.preventDefault();
    const submitBtn = document.getElementById('csv-submit-btn');
    const alertBox  = document.getElementById('register-modal-alert');
    if (alertBox) alertBox.style.display = 'none';

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Uploading & Importing...';
    }

    const formData = new FormData(this);
    const importUrl = window.appUrl ? window.appUrl('/inventory/import-csv') : '/inventory/import-csv';

    fetch(importUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(res => res.json())
    .then(res => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Upload & Import Spreadsheet';
      }

      if (!res.success) {
        if (alertBox) {
          alertBox.className = 'bg-red-50 text-red-800 border border-red-200';
          alertBox.style.display = 'block';
          alertBox.textContent = res.message || 'Import failed.';
        }
        return;
      }

      closeModal('register-asset-modal');
      fetchCatalog(1);
      showToast('success', res.message);
    })
    .catch(err => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Upload & Import Spreadsheet';
      }
      if (alertBox) {
        alertBox.className = 'bg-red-50 text-red-800 border border-red-200';
        alertBox.style.display = 'block';
        alertBox.textContent = 'A network error occurred while uploading CSV.';
      }
    });
  });

  // ── Edit Modal Handler ────────────────────────────────────
  function updateEditConditionHelp(cond) {
    const helpEl = document.getElementById('edit-condition-help');
    if (!helpEl) return;
    const c = (cond || '').toLowerCase();
    switch (c) {
      case 'mint':
      case 'good':
      case 'fair':
        helpEl.innerHTML = `<span style="color:#059669;font-weight:500;">✔ Active Circulation:</span> Normal operational health — Asset will be authorized as <strong>Available</strong> for borrowing.`;
        break;
      case 'damaged':
      case 'not_working':
        helpEl.innerHTML = `<span style="color:#d97706;font-weight:500;">⚠️ Maintenance Lock:</span> Automatically sets status to <strong>Maintenance</strong> and suspends active borrowing.`;
        break;
      case 'for_disposal':
        helpEl.innerHTML = `<span style="color:var(--color-ruby);font-weight:500;">⚠️ Condemned:</span> Sets status to <strong>For Disposal</strong> and queues asset in the Disposal Staging Queue.`;
        break;
      default:
        helpEl.textContent = '';
    }
  }

  document.getElementById('edit-condition')?.addEventListener('change', function () {
    updateEditConditionHelp(this.value);
  });

  function populateEditLocation(rawLocation) {
    const facilitySel = document.getElementById('edit-facility-select');
    const customGroup = document.getElementById('edit-custom-facility-group');
    const customInput = document.getElementById('edit-custom-facility');
    const specificInput = document.getElementById('edit-specific-location');
    const hiddenLoc = document.getElementById('edit-location');

    if (!facilitySel || !hiddenLoc) return;

    const trimmed = (rawLocation || '').trim();
    const standardFacilities = [
      'Science Laboratory',
      'Farm Laboratory',
      'GIS & Comp. Lab',
      'Fisheries Laboratory',
      'Clinic'
    ];

    let matchedFacility = null;
    let specificPart = '';

    for (const fac of standardFacilities) {
      if (trimmed === fac) {
        matchedFacility = fac;
        specificPart = '';
        break;
      } else if (trimmed.startsWith(fac)) {
        matchedFacility = fac;
        let remainder = trimmed.slice(fac.length).trim();
        remainder = remainder.replace(/^[—–\-:\s]+/, '');
        if (remainder.startsWith('(') && remainder.endsWith(')')) {
          remainder = remainder.slice(1, -1).trim();
        }
        specificPart = remainder;
        break;
      }
    }

    if (matchedFacility) {
      facilitySel.value = matchedFacility;
      if (customGroup) customGroup.style.display = 'none';
      if (customInput) {
        customInput.value = '';
        customInput.required = false;
      }
      if (specificInput) specificInput.value = specificPart;
    } else if (trimmed) {
      facilitySel.value = 'Other';
      if (customGroup) customGroup.style.display = 'block';
      if (customInput) {
        customInput.value = trimmed;
        customInput.required = true;
      }
      if (specificInput) specificInput.value = '';
    } else {
      facilitySel.value = '';
      if (customGroup) customGroup.style.display = 'none';
      if (customInput) {
        customInput.value = '';
        customInput.required = false;
      }
      if (specificInput) specificInput.value = '';
    }

    if (typeof window.syncEditLocation === 'function') {
      window.syncEditLocation();
    } else {
      hiddenLoc.value = trimmed;
    }
  }

  function openEditModal(item) {
    if (item.status === 'borrowed') {
      if (window.alertDialog) {
        window.alertDialog('Asset is currently borrowed. Modifying asset specifications or physical condition is locked until the item has been returned.', 'Action Locked', 'Asset Currently Borrowed');
      } else {
        showToast('warning', 'Asset is currently borrowed and cannot be modified.');
      }
      return;
    }
    const alertBox = document.getElementById('edit-modal-alert');
    if (alertBox) alertBox.style.display = 'none';

    document.getElementById('edit-item-id').value = item.item_id;
    document.getElementById('edit-original-updated-at').value = item.updated_at || item.created_at || '';
    document.getElementById('edit-display-barcode').textContent = item.barcode;
    document.getElementById('edit-display-id').textContent = '#' + item.item_id;
    document.getElementById('edit-display-status').innerHTML = getStatusBadgeHtml(item.status);

    document.getElementById('edit-item-name').value = item.item_name || '';
    document.getElementById('edit-category').value = item.category || '';
    
    // Smart location splitting (Facility dropdown + Specific location)
    populateEditLocation(item.location || '');

    const hiddenLoc = document.getElementById('edit-location');
    if (hiddenLoc) {
      hiddenLoc.setAttribute('data-original-location', (item.location || '').trim());
    }
    const transferReasonInput = document.getElementById('edit-transfer-reason');
    if (transferReasonInput) transferReasonInput.value = '';
    const reasonGroup = document.getElementById('edit-transfer-reason-group');
    if (reasonGroup) reasonGroup.style.display = 'none';

    document.getElementById('edit-property-number').value = item.property_number || '';
    
    const itemCond = item.item_condition || 'good';
    document.getElementById('edit-condition').value = itemCond;
    updateEditConditionHelp(itemCond);

    document.getElementById('edit-is-student-borrowable').checked = (parseInt(item.is_student_borrowable, 10) === 1);
    document.getElementById('edit-description').value = item.description || '';

    openModal('edit-asset-modal');
  }

  const editForm = document.getElementById('edit-asset-form');
  editForm?.addEventListener('submit', function (e) {
    e.preventDefault();
    const submitBtn = document.getElementById('edit-submit-btn');
    const alertBox  = document.getElementById('edit-modal-alert');
    if (alertBox) alertBox.style.display = 'none';

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Saving...';
    }

    const formData = new FormData(this);
    const editUrl = window.appUrl ? window.appUrl('/inventory/edit') : '/inventory/edit';

    fetch(editUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(res => res.json().then(data => ({ status: res.status, body: data })))
    .then(({ status, body }) => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Changes';
      }

      if (status === 409) {
        // Optimistic Concurrency Conflict: reload new data and notify
        if (alertBox) {
          alertBox.className = 'bg-amber-50 text-amber-800 border border-amber-200';
          alertBox.style.display = 'block';
          alertBox.textContent = body.message;
        }
        if (body.item) {
          openEditModal(body.item);
        }
        return;
      }

      if (!body.success) {
        if (alertBox) {
          alertBox.className = 'bg-red-50 text-red-800 border border-red-200';
          alertBox.style.display = 'block';
          alertBox.textContent = body.message || 'Update failed.';
        }
        return;
      }

      closeModal('edit-asset-modal');
      fetchCatalog(currentPage);
      if (body.stats) {
        updateStatsUi(body.stats);
      }
      showToast('success', body.message);
    })
    .catch(err => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Changes';
      }
      if (alertBox) {
        alertBox.className = 'bg-red-50 text-red-800 border border-red-200';
        alertBox.style.display = 'block';
        alertBox.textContent = 'A network error occurred. Please try again.';
      }
    });
  });

  // ── Delete Modal Handler ──────────────────────────────────
  function openDeleteModal(item) {
    if (item.status === 'borrowed') {
      if (window.alertDialog) {
        window.alertDialog('Asset is currently borrowed. Assets cannot be deleted or decommissioned while actively borrowed.', 'Action Prohibited', 'Active Borrow Restriction');
      } else {
        showToast('warning', 'Asset is currently borrowed and cannot be deleted.');
      }
      return;
    }
    const alertBox = document.getElementById('delete-modal-alert');
    if (alertBox) alertBox.style.display = 'none';

    document.getElementById('delete-item-id').value = item.item_id;
    document.getElementById('delete-display-name').textContent = item.item_name || 'Unnamed Asset';
    document.getElementById('delete-display-barcode').textContent = item.barcode;
    document.getElementById('delete-display-room').textContent = (item.category || '') + ' • ' + (item.location || 'Location Unspecified');
    
    const condBadge = document.getElementById('delete-display-cond');
    if (condBadge) {
      condBadge.innerHTML = getConditionBadgeHtml(item.item_condition);
    }

    document.getElementById('delete-reason').value = '';
    openModal('delete-asset-modal');
  }

  const deleteForm = document.getElementById('delete-asset-form');
  deleteForm?.addEventListener('submit', function (e) {
    e.preventDefault();
    const alertBox = document.getElementById('delete-modal-alert');
    const submitBtn = document.getElementById('btn-confirm-delete');
    const reason = document.getElementById('delete-reason').value.trim();

    if (reason.length < 3) {
      if (alertBox) {
        alertBox.textContent = 'Please provide a valid justification reason (minimum 3 characters).';
        alertBox.style.display = 'block';
      }
      return;
    }

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Processing...';
    }

    const formData = new FormData(this);
    const deleteUrl = window.appUrl ? window.appUrl('/inventory/delete') : '/inventory/delete';

    fetch(deleteUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(async res => {
      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.message || 'Failed to delete asset.');
      }
      return data;
    })
    .then(data => {
      closeModal('delete-asset-modal');
      showToast('success', data.message || 'Asset was successfully processed.');
      if (data.stats) {
        updateStatsUi(data.stats);
      }
      fetchCatalog(currentPage);
    })
    .catch(err => {
      if (alertBox) {
        alertBox.textContent = err.message || 'An error occurred during deletion.';
        alertBox.style.display = 'block';
      }
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Confirm Delete / Archive';
      }
    });
  });

  // ── Maintenance Restoration & B.E.R. Handlers ───────────
  window.openRestoreModal = function(item) {
    document.getElementById('restore-item-id').value = item.item_id;
    document.getElementById('restore-item-name').textContent = item.item_name || 'Unnamed Asset';
    document.getElementById('restore-item-barcode').textContent = item.barcode;
    document.getElementById('restore-condition').value = 'good';
    document.getElementById('restore-notes').value = '';
    
    openModal('modal-restore-asset');
  };

  window.closeRestoreModal = function() {
    closeModal('modal-restore-asset');
  };

  window.handleRestoreSubmit = function(e) {
    e.preventDefault();
    const form = document.getElementById('form-restore-asset');
    const submitBtn = document.getElementById('btn-submit-restore');
    const formData = new FormData(form);

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Restoring...';
    }

    const restoreUrl = window.appUrl ? window.appUrl('/api/inventory/restore') : '/api/inventory/restore';
    fetch(restoreUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(async res => {
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Failed to restore asset.');
      }
      return data;
    })
    .then(data => {
      window.closeRestoreModal();
      showToast('success', data.message || 'Asset restored to Available status.');
      if (data.stats) updateStatsUi(data.stats);
      fetchCatalog(currentPage);
    })
    .catch(err => {
      showToast('error', err.message || 'Error restoring asset.');
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Restore to Available';
      }
    });
  };

  window.openBerModal = function(item) {
    document.getElementById('ber-item-id').value = item.item_id;
    document.getElementById('ber-item-name').textContent = item.item_name || 'Unnamed Asset';
    document.getElementById('ber-item-barcode').textContent = item.barcode;
    document.getElementById('ber-reason').value = '';
    
    openModal('modal-ber-asset');
  };

  window.closeBerModal = function() {
    closeModal('modal-ber-asset');
  };

  window.handleBerSubmit = function(e) {
    e.preventDefault();
    const form = document.getElementById('form-ber-asset');
    const submitBtn = document.getElementById('btn-submit-ber');
    const reason = document.getElementById('ber-reason').value.trim();

    if (reason.length < 3) {
      showToast('warning', 'Please provide a valid condemnation justification (minimum 3 characters).');
      return;
    }

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Condemning...';
    }

    const formData = new FormData(form);

    const berUrl = window.appUrl ? window.appUrl('/api/inventory/ber') : '/api/inventory/ber';
    fetch(berUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(async res => {
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Failed to condemn asset.');
      }
      return data;
    })
    .then(data => {
      window.closeBerModal();
      showToast('success', data.message || 'Asset queued for disposal.');
      if (data.stats) updateStatsUi(data.stats);
      fetchCatalog(currentPage);
    })
    .catch(err => {
      showToast('error', err.message || 'Error declaring asset B.E.R.');
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Condemn to Disposal';
      }
    });
  };

  // ── Lost & Missing Reporting Handlers ───────────────────────
  window.openLostModal = function(item) {
    const alertBox = document.getElementById('lost-modal-alert');
    if (alertBox) alertBox.style.display = 'none';

    document.getElementById('lost-item-id').value = item.item_id;
    document.getElementById('lost-item-name').textContent = item.item_name || ('Asset #' + item.item_id);
    document.getElementById('lost-item-barcode').textContent = item.barcode || '—';
    document.getElementById('lost-item-location').textContent = item.location || 'Unassigned';
    document.getElementById('lost-accountable-person').value = '';
    document.getElementById('lost-date-reported').value = new Date().toISOString().split('T')[0];
    document.getElementById('lost-investigation-status').value = 'under_investigation';
    document.getElementById('lost-description').value = '';

    openModal('modal-report-lost');
  };

  window.closeLostModal = function() {
    closeModal('modal-report-lost');
  };

  const btnSubmitReportLost = document.getElementById('btn-submit-report-lost');
  if (btnSubmitReportLost) {
    btnSubmitReportLost.addEventListener('click', function(e) {
      e.preventDefault();
      const form = document.getElementById('form-report-lost');
      if (!form) return;
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const alertBox = document.getElementById('lost-modal-alert');
      if (alertBox) alertBox.style.display = 'none';

      btnSubmitReportLost.disabled = true;
      btnSubmitReportLost.innerHTML = 'Submitting Report...';

      const formData = new FormData(form);
      const lostUrl = window.appUrl ? window.appUrl('/api/inventory/report-lost') : '/api/inventory/report-lost';

      fetch(lostUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': csrfToken
        }
      })
      .then(async res => {
        const data = await res.json();
        if (!res.ok || !data.success) {
          throw new Error(data.message || 'Failed to report lost asset.');
        }
        return data;
      })
      .then(data => {
        window.closeLostModal();
        showToast('success', data.message || 'Asset successfully marked as Lost / Missing.');
        if (data.stats) updateStatsUi(data.stats);
        fetchCatalog(currentPage);
      })
      .catch(err => {
        if (alertBox) {
          alertBox.className = 'bg-red-50 text-red-800 border border-red-200';
          alertBox.style.display = 'block';
          alertBox.textContent = err.message || 'Error reporting lost asset.';
        } else {
          showToast('error', err.message || 'Error reporting lost asset.');
        }
      })
      .finally(() => {
        btnSubmitReportLost.disabled = false;
        btnSubmitReportLost.innerHTML = 'Submit Lost Report';
      });
    });
  }

  function updateStatsUi(stats) {
    if (!stats) return;
    const cards = document.querySelectorAll('#kpi-stats-grid .stat-card-value');
    if (cards.length >= 4) {
      cards[0].textContent = Number(stats.total || 0).toLocaleString();
      cards[1].textContent = Number(stats.healthy || 0).toLocaleString();
      cards[2].textContent = Number(stats.maintenance || 0).toLocaleString();
      cards[3].textContent = Number(stats.disposal || 0).toLocaleString();
    }
  }

  // ── Printable Barcode Labels Engine ───────────────────────
  function printBarcodeLabels(items) {
    const printWin = window.open('', '_blank', 'width=800,height=600');
    if (!printWin) {
      showToast('warning', 'Please allow browser popups to open the barcode label print view.', 'Popups Blocked');
      return;
    }

    let cardsHtml = '';
    items.forEach((item, i) => {
      cardsHtml += `
        <div class="print-card">
          <div class="header">SLSU - ALABAT CAMPUS</div>
          <div class="sub">PROPERTY &amp; INVENTORY SYSTEM</div>
          <svg id="p-bc-${i}"></svg>
          <div class="name">${escapeHtml(item.item_name)}</div>
          <div class="meta">${escapeHtml(item.property_number || item.barcode)} · ${escapeHtml(item.category || '')}</div>
        </div>
      `;
    });

    printWin.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Print Asset Barcodes — SLSU-Alabat IMS</title>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
        <style>
          @page { size: auto; margin: 10mm; }
          body { font-family: sans-serif; margin: 0; padding: 10px; background: #fff; }
          .print-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
          .print-card { border: 1.5px solid #000; border-radius: 6px; padding: 12px; text-align: center; page-break-inside: avoid; }
          .header { font-size: 11px; font-weight: bold; letter-spacing: 0.5px; }
          .sub { font-size: 8px; color: #444; margin-bottom: 4px; }
          svg { width: 100%; max-height: 48px; }
          .name { font-size: 12px; font-weight: bold; margin-top: 4px; }
          .meta { font-size: 10px; color: #333; font-family: monospace; }
        </style>
      </head>
      <body>
        <div class="print-grid">${cardsHtml}</div>
        <script>
          window.onload = function() {
            ${items.map((it, i) => `
              JsBarcode("#p-bc-${i}", "${it.barcode}", { format: "CODE128", width: 1.6, height: 42, displayValue: true, fontSize: 10, font: "monospace", margin: 2 });
            `).join('\n')}
            setTimeout(function() {
              window.print();
            }, 400);
          };
        <\/script>
      </body>
      </html>
    `);
    printWin.document.close();
  }

  // ── Utility Helpers ───────────────────────────────────────
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeJsonAttr(obj) {
    return escapeHtml(JSON.stringify(obj));
  }

  function showToast(type, msg, title) {
    const t = (typeof Toast !== 'undefined') ? Toast : (window.Toast || null);
    if (t && typeof t[type] === 'function') {
      const defaultTitle = type === 'success' ? 'Success' : (type === 'error' ? 'Error' : (type === 'warning' ? 'Warning' : 'Notice'));
      t[type](title || defaultTitle, msg);
    } else {
      console.warn(`[Toast] (${type}) ${msg}`);
    }
  }

  // ── IAR Ingestion Modal Logic ─────────────────────────────
  const ingestModal = document.getElementById('ingest-iar-modal');
  const ingestForm = document.getElementById('ingest-iar-form');
  const ingestTbody = document.getElementById('ingest-iar-items-tbody');

  document.querySelectorAll('.btn-open-ingest-iar').forEach(btn => {
    btn.addEventListener('click', function() {
      const iarId = parseInt(this.dataset.iarId) || 0;
      if (iarId <= 0) return;

      const endpoint = (window.appUrl ? window.appUrl('/api/custodian/iar/pending') : '/api/custodian/iar/pending');
      fetch(endpoint)
        .then(res => res.json())
        .then(res => {
          if (!res.pending || !Array.isArray(res.pending)) return;
          const iar = res.pending.find(p => parseInt(p.id) === iarId);
          if (!iar) {
            showToast('error', 'IAR details not found or already registered.');
            return;
          }

          document.getElementById('ingest-iar-id').value = iar.id;
          document.getElementById('ingest-iar-summary-title').textContent = `${iar.iar_no} · ${iar.supplier_name}`;
          document.getElementById('ingest-iar-summary-sub').textContent = `PO: ${iar.po_no} · Office: ${iar.office_dept} · Inspected: ${iar.date_inspected}`;

          const scanWrap = document.getElementById('ingest-iar-scan-preview-btn-wrap');
          const scanLink = document.getElementById('ingest-iar-scan-link');
          if (iar.scanned_document_path) {
            scanLink.href = (window.appUrl ? window.appUrl(iar.scanned_document_path) : iar.scanned_document_path);
            scanWrap.style.display = 'block';
          } else {
            scanWrap.style.display = 'none';
          }

          // Populate line items
          ingestTbody.innerHTML = '';
          const items = iar.items || [];
          items.forEach((item, idx) => {
            if (parseInt(item.is_registered) === 1) return;

            const seed = Date.now().toString().slice(-4) + Math.floor(Math.random() * 90 + 10);
            const autoBarcode = `BC-9${seed}`;
            const unitCost = parseFloat(item.unit_cost) || 0;
            const desc = item.item_description;

            const tr = document.createElement('tr');
            tr.className = 'ingest-item-row';
            tr.dataset.lineId = item.id;
            tr.style.borderBottom = '1px solid var(--color-hairline)';
            tr.innerHTML = `
              <td style="padding:8px 10px;color:var(--color-ink-mute);">${idx + 1}</td>
              <td style="padding:6px 10px;">
                <input type="text" class="form-input ingest-item-name" value="${escapeHtml(desc)}" style="width:100%;font-size:12px;padding:4px 8px;" required>
                <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">
                  ${item.model ? `Model: ${escapeHtml(item.model)} · ` : ''} Qty: ${item.quantity} ${item.unit}
                </div>
              </td>
              <td style="padding:6px 10px;">
                <input type="text" class="form-input ingest-item-barcode" value="${autoBarcode}" style="width:100%;font-size:12px;padding:4px 8px;font-family:monospace;" required>
              </td>
              <td style="padding:6px 10px;">
                <input type="text" class="form-input ingest-item-prop" placeholder="Prop #" style="width:100%;font-size:12px;padding:4px 8px;">
              </td>
              <td style="padding:6px 10px;">
                <select class="form-input ingest-item-cat" style="width:100%;font-size:12px;padding:4px 6px;">
                  <option value="IT Equipment">IT Equipment</option>
                  <option value="Laboratory Tools">Laboratory Tools</option>
                  <option value="Aquaculture Equipment">Aquaculture Equipment</option>
                  <option value="Instructional Materials">Instructional Materials</option>
                  <option value="Office Equipment">Office Equipment</option>
                  <option value="Furniture & Fixtures">Furniture & Fixtures</option>
                </select>
              </td>
              <td style="padding:6px 10px;">
                <input type="text" class="form-input ingest-item-loc" value="${escapeHtml(iar.office_dept || 'Supply Office')}" style="width:100%;font-size:12px;padding:4px 8px;">
              </td>
              <td style="padding:6px 10px;text-align:right;font-feature-settings:'tnum';">
                ₱ ${unitCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              </td>
              <td style="padding:6px 10px;text-align:center;">
                <input type="checkbox" class="ingest-item-borrowable" checked style="cursor:pointer;">
              </td>
            `;
            ingestTbody.appendChild(tr);
          });

          openModal('ingest-iar-modal');
        })
        .catch(err => {
          console.error(err);
          showToast('error', 'Network error fetching IAR details.');
        });
    });
  });

  if (ingestForm) {
    ingestForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const iarId = parseInt(document.getElementById('ingest-iar-id').value) || 0;
      if (iarId <= 0) return;

      const items = [];
      ingestTbody.querySelectorAll('.ingest-item-row').forEach(row => {
        items.push({
          line_id: parseInt(row.dataset.lineId) || 0,
          item_name: row.querySelector('.ingest-item-name').value.trim(),
          barcode: row.querySelector('.ingest-item-barcode').value.trim(),
          property_number: row.querySelector('.ingest-item-prop').value.trim(),
          category: row.querySelector('.ingest-item-cat').value,
          location: row.querySelector('.ingest-item-loc').value.trim(),
          is_student_borrowable: row.querySelector('.ingest-item-borrowable').checked ? 1 : 0,
        });
      });

      const btnSubmit = document.getElementById('btn-submit-ingest-iar');
      btnSubmit.disabled = true;
      btnSubmit.textContent = 'Registering Assets...';

      const endpoint = (window.appUrl ? window.appUrl('/api/custodian/iar/ingest') : '/api/custodian/iar/ingest');
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('iar_id', iarId);
      formData.append('items', JSON.stringify(items));

      fetch(endpoint, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        },
        body: formData
      })
      .then(res => res.json())
      .then(res => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Commit to Master Inventory`;

        if (res.success) {
          closeModal('ingest-iar-modal');
          showToast('success', res.message || 'Assets successfully registered into inventory!');
          fetchCatalog(1);
          const clickedBtn = document.querySelector(`.btn-open-ingest-iar[data-iar-id="${iarId}"]`);
          if (clickedBtn) {
            clickedBtn.closest('.glass-card')?.remove();
          }
        } else {
          showToast('error', res.message || 'Error ingesting IAR.');
        }
      })
      .catch(err => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Commit to Master Inventory`;
        showToast('error', 'Network error committing IAR assets.');
      });
    });
  }

  // Initial Load (support URL query parameters, e.g., ?search=BC-XXXXX or ?status=borrowed)
  const urlParams = new URLSearchParams(window.location.search);
  const searchParam = urlParams.get('search');
  if (searchParam && searchInput) {
    searchInput.value = searchParam;
  }
  const statusParam = urlParams.get('status');
  if (statusParam && statusFilter) {
    statusFilter.value = statusParam;
  }
  fetchCatalog(1);

  // ── Realtime Event Listeners for Live Asset Catalog ───────
  if (window.RealtimeHub) {
    window.RealtimeHub.on('ITEM_STATUS_CHANGED', function(event) {
      const p = event.payload || {};
      const row = document.querySelector(`tr[data-item-id="${p.item_id}"]`);
      if (row) {
        if (row.children[5]) {
          row.children[5].innerHTML = getStatusBadgeHtml(p.status);
        }
        if (p.item_condition && row.children[4]) {
          row.children[4].innerHTML = getConditionBadgeHtml(p.item_condition);
        }
        row.classList.remove('row-pulse-highlight');
        void row.offsetWidth;
        row.classList.add('row-pulse-highlight');
      }

      const openEditId = document.getElementById('edit-item-id')?.value;
      if (openEditId && openEditId == p.item_id) {
        showToast('warning', 'Notice: This asset was modified or checked out by another transaction.');
      }
    });

    window.RealtimeHub.on('ITEM_DELETED', function(event) {
      const p = event.payload || {};
      const row = document.querySelector(`tr[data-item-id="${p.item_id}"]`);
      if (row) {
        row.classList.add('row-fade-out');
        setTimeout(() => row.remove(), 400);
      }
    });

    window.RealtimeHub.on('ITEM_CREATED', function(event) {
      const p = event.payload || {};
      const isClean = (!searchInput || !searchInput.value.trim()) && (!catFilter || !catFilter.value) && (!condFilter || !condFilter.value) && (!statusFilter || !statusFilter.value);
      if (currentPage === 1 && isClean) {
        fetchCatalog(1);
      } else {
        showToast('info', 'New asset added to catalog: ' + (p.item_name || 'Item'));
      }
    });

    window.RealtimeHub.on('ITEM_UPDATED', function(event) {
      const p = event.payload || {};
      const row = document.querySelector(`tr[data-item-id="${p.item_id}"]`);
      if (row) {
        if (p.item_name && row.children[1]) {
          const nameEl = row.children[1].querySelector('div:first-child');
          if (nameEl) nameEl.textContent = p.item_name;
        }
        if (p.location && row.children[2]) {
          row.children[2].textContent = p.location;
        }
        if (p.item_condition && row.children[4]) {
          row.children[4].innerHTML = getConditionBadgeHtml(p.item_condition);
        }
        row.classList.remove('row-pulse-highlight');
        void row.offsetWidth;
        row.classList.add('row-pulse-highlight');
      }
    });
  }
});
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
