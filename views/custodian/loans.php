<?php
/**
 * Borrow Logs & Custody Operations Center
 * Property Custodian & Supply Office Command Station
 * Reusable layout shell, Alpine.js tabs & live filtering, and Stripe-inspired design system.
 */
$role       = $role ?? ($_SESSION['role'] ?? 'guest');
$title      = 'Borrow Logs';
$breadcrumb = ($role === 'executive_admin') ? 'Executive / Borrow Logs' : 'Custodian / Borrow Logs';

require __DIR__ . '/../layouts/shell.php';
?>

<!-- Alpine.js for lightweight tab transitions & client-side table filter/search -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<style>
/* ── Segmented Filter Control Group ───────────────────────── */
.segmented-control-wrap {
  display: inline-flex;
  align-items: center;
  background: var(--color-canvas-soft, #f6f9fc);
  border: 1px solid var(--color-hairline, #e3e8ee);
  border-radius: var(--radius-pill, 9999px);
  padding: 3px;
  gap: 3px;
  box-shadow: inset 0 1px 2px rgba(0, 55, 112, 0.04);
}

.segmented-pill-btn {
  appearance: none;
  border: 1px solid transparent;
  background: transparent;
  color: var(--color-ink-mute, #64748d);
  font-size: 12px;
  font-weight: 500;
  padding: 5px 13px;
  border-radius: var(--radius-pill, 9999px);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
  outline: none;
  user-select: none;
  white-space: nowrap;
}

.segmented-pill-btn:hover:not(.active-all):not(.active-overdue):not(.active-due) {
  color: var(--color-ink, #0d253d);
  background: rgba(255, 255, 255, 0.65);
}

.segmented-pill-btn.active-all {
  background: var(--color-canvas, #ffffff);
  color: var(--color-primary, #166534);
  font-weight: 600;
  border-color: rgba(0, 55, 112, 0.06);
  box-shadow: 0 1px 3px rgba(0, 55, 112, 0.08), 0 1px 1px rgba(0, 0, 0, 0.02);
}

.segmented-pill-btn.active-overdue {
  background: var(--color-canvas, #ffffff);
  color: var(--color-ruby, #ea2261);
  font-weight: 600;
  border-color: rgba(234, 34, 97, 0.15);
  box-shadow: 0 1px 3px rgba(234, 34, 97, 0.12), 0 1px 1px rgba(0, 0, 0, 0.02);
}

.segmented-pill-btn.active-due {
  background: var(--color-canvas, #ffffff);
  color: var(--color-warning, #d97706);
  font-weight: 600;
  border-color: rgba(217, 119, 6, 0.15);
  box-shadow: 0 1px 3px rgba(217, 119, 6, 0.12), 0 1px 1px rgba(0, 0, 0, 0.02);
}

.pill-metric-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 6px;
  border-radius: var(--radius-pill, 9999px);
  font-size: 11px;
  font-weight: 600;
  font-family: monospace;
  font-feature-settings: "tnum";
  background: rgba(13, 37, 61, 0.06);
  color: inherit;
  transition: all 0.16s ease;
}

.active-all .pill-metric-badge {
  background: rgba(22, 101, 52, 0.1);
  color: var(--color-primary, #166534);
}

.active-overdue .pill-metric-badge {
  background: rgba(234, 34, 97, 0.12);
  color: var(--color-ruby, #ea2261);
}

.active-due .pill-metric-badge {
  background: rgba(217, 119, 6, 0.12);
  color: var(--color-warning, #d97706);
}

.pulse-dot-ruby {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--color-ruby, #ea2261);
  box-shadow: 0 0 0 2px rgba(234, 34, 97, 0.25);
  animation: pulse-ring 1.8s cubic-bezier(0.45, 0, 0.55, 1) infinite;
  display: inline-block;
}

@keyframes pulse-ring {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(234, 34, 97, 0.4); }
  70% { transform: scale(1); box-shadow: 0 0 0 4px rgba(234, 34, 97, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(234, 34, 97, 0); }
}

.search-input-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  width: 100%;
  max-width: 280px;
}

.search-input-field {
  width: 100%;
  height: 34px;
  padding: 6px 28px 6px 32px;
  font-size: 12px;
  border: 1px solid var(--color-hairline, #e3e8ee);
  border-radius: var(--radius-pill, 9999px);
  background: var(--color-canvas, #ffffff);
  color: var(--color-ink, #0d253d);
  outline: none;
  transition: all 0.15s ease;
  box-shadow: 0 1px 2px rgba(0, 55, 112, 0.02);
}

.search-input-field:focus {
  border-color: var(--color-primary, #166534);
  box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.12);
}

.borrower-avatar-chip {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(22, 101, 52, 0.08);
  color: var(--color-primary, #166534);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 11px;
  flex-shrink: 0;
  border: 1px solid rgba(22, 101, 52, 0.15);
}
</style>

<div class="space-y-6 animate-fade-in-up">

  <!-- ── 1. Page Header & Quick Station Bar ──────────────────── -->
  <div class="section-header">
    <div>
      <h1 class="section-title">Borrow Logs</h1>
      <div class="section-subtitle">Real-time campus equipment tracking, return deadlines, and counter verification</div>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
      <?php if ($role === 'executive_admin'): ?>
        <span class="badge" style="background:var(--color-canvas-soft, #f1f5f9);color:var(--color-ink-mute, #64748b);border:1px solid var(--color-hairline, #e2e8f0);padding:5px 12px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border-radius:20px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
          Executive Read-Only View
        </span>
        <a href="<?= url('/custodian/loans/export-xlsx') ?>" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 16px;" title="Export official 3-sheet borrow logs to Microsoft Excel (.xlsx)">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Export Excel (.xlsx)
        </a>
      <?php else: ?>
        <a href="<?= url('/custodian/loans/export-xlsx') ?>" class="btn btn-secondary btn-sm" title="Export official 3-sheet borrow logs to Microsoft Excel (.xlsx)" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          <span>Export Excel (.xlsx)</span>
        </a>
        <a href="<?= url('/inventory') ?>" class="btn btn-secondary btn-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
          <span>Asset Catalog</span>
        </a>
        <a href="<?= url('/scan') ?>" class="btn btn-primary btn-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;border-radius:9999px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18M15 3v18"></path></svg>
          <span>Open Scan Station</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── 2. Top Summary KPI Cards ────────────────────────────── -->
  <div class="grid-kpi-stats-3col stagger-children" style="margin-bottom:var(--space-xl);">
    <?php
    $stats = [
      [
        'title'     => 'Total Active Borrows',
        'value'     => number_format((int)$totalActiveLoans),
        'icon'      => 'layers',
        'color'     => 'indigo',
        'trend'     => 'In Circulation',
        'trend_dir' => 'up',
        'subtitle'  => 'Across all campus departments',
      ],
      [
        'title'     => 'Overdue Borrowings',
        'value'     => number_format((int)$overdueCount),
        'icon'      => 'alert-triangle',
        'color'     => ($overdueCount > 0 ? 'ruby' : 'emerald'),
        'trend'     => ($overdueCount > 0 ? 'Action Required' : 'All Clear'),
        'trend_dir' => ($overdueCount > 0 ? 'down' : 'up'),
        'subtitle'  => ($overdueCount > 0 ? 'Past expected return deadline' : 'Zero overdue items'),
      ],
      [
        'title'     => 'Pending Counter Holds',
        'value'     => number_format((int)$pendingHoldsCount),
        'icon'      => 'inbox',
        'color'     => ($pendingHoldsCount > 0 ? 'amber' : 'indigo'),
        'trend'     => ($pendingHoldsCount > 0 ? 'Awaiting Release' : 'Queue Clear'),
        'trend_dir' => ($pendingHoldsCount > 0 ? 'up' : 'down'),
        'subtitle'  => 'Self-service online reservations',
      ],
    ];

    foreach ($stats as $stat):
      $props = $stat;
      require __DIR__ . '/../components/stat-card.php';
    endforeach;
    ?>
  </div>

  <!-- ── 3. Main 3-Tab Operations Center ─────────────────────── -->
  <div 
    x-data="{ 
      tab: 'active', 
      filter: 'all', 
      search: '',
      matchesSearch(text) {
        if (!this.search.trim()) return true;
        return (text || '').toLowerCase().includes(this.search.toLowerCase().trim());
      }
    }" 
    class="card" 
    style="padding:0;overflow:hidden;border:1px solid var(--color-hairline);"
  >

    <!-- Tab Header Bar -->
    <div class="tabs-header-scroll" style="display:flex;justify-content:space-between;align-items:center;background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);padding:0 var(--space-md);flex-wrap:wrap;gap:.5rem;">
      <div style="display:flex;gap:.25rem;">
        <button 
          @click="tab = 'active'" 
          :class="{'tab-active': tab === 'active'}" 
          class="tab-btn"
          style="display:flex;align-items:center;gap:.5rem;padding:var(--space-md) var(--space-lg);font-weight:500;font-size:14px;"
        >
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect><line x1="12" y1="11" x2="16" y2="11"></line><line x1="12" y1="16" x2="16" y2="16"></line><line x1="8" y1="11" x2="8.01" y2="11"></line><line x1="8" y1="16" x2="8.01" y2="16"></line></svg>
          <span>Active &amp; Overdue Borrows</span>
          <?php if ($totalActiveLoans > 0): ?>
            <span class="badge badge-borrowed num" style="font-size:11px;"><?= $totalActiveLoans ?></span>
          <?php endif; ?>
          <?php if ($overdueCount > 0): ?>
            <span class="badge badge-maintenance num" style="font-size:11px;background:rgba(234,34,97,0.12);color:var(--color-ruby);border:1px solid rgba(234,34,97,0.25);">
              <?= $overdueCount ?> Overdue
            </span>
          <?php endif; ?>
        </button>

        <button 
          @click="tab = 'pending'" 
          :class="{'tab-active': tab === 'pending'}" 
          class="tab-btn"
          style="display:flex;align-items:center;gap:.5rem;padding:var(--space-md) var(--space-lg);font-weight:500;font-size:14px;"
        >
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>
          <span>Pending Pickups</span>
          <?php if ($pendingHoldsCount > 0): ?>
            <span class="badge badge-pending num" style="font-size:11px;"><?= $pendingHoldsCount ?></span>
          <?php endif; ?>
        </button>

        <button 
          @click="tab = 'history'" 
          :class="{'tab-active': tab === 'history'}" 
          class="tab-btn"
          style="display:flex;align-items:center;gap:.5rem;padding:var(--space-md) var(--space-lg);font-weight:500;font-size:14px;"
        >
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          <span>Borrowal History &amp; Logs</span>
          <span class="badge badge-neutral num" style="font-size:11px;"><?= count($completedLoans) ?></span>
        </button>
      </div>

      <div style="font-size:12px;color:var(--color-ink-mute);padding:var(--space-sm) 0;">
        <span style="font-weight:500;color:var(--color-ink);">Live Counter Station</span>
      </div>
    </div>

    <!-- ── TAB 1: Active & Overdue Loans ───────────────────────── -->
    <div x-show="tab === 'active'" style="padding:var(--space-lg);">
      
      <!-- Sub-Filter Controls & Search Toolbar -->
      <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:var(--space-lg);flex-wrap:wrap;">
        
        <!-- Segmented Pill Group -->
        <div class="segmented-control-wrap">
          <!-- All Active Pill -->
          <button 
            type="button" 
            @click="filter = 'all'" 
            :class="{'active-all': filter === 'all'}" 
            class="segmented-pill-btn"
            title="Show all currently borrowed equipment"
          >
            <span>All Active</span>
            <span class="pill-metric-badge"><?= $totalActiveLoans ?></span>
          </button>

          <!-- Overdue Only Pill -->
          <button 
            type="button" 
            @click="filter = 'overdue'" 
            :class="{'active-overdue': filter === 'overdue'}" 
            class="segmented-pill-btn"
            title="Filter items that exceeded their expected return deadline"
          >
            <?php if ($overdueCount > 0): ?>
              <span class="pulse-dot-ruby"></span>
            <?php endif; ?>
            <span>Overdue Only</span>
            <span class="pill-metric-badge"><?= $overdueCount ?></span>
          </button>

          <!-- Due Today Pill -->
          <button 
            type="button" 
            @click="filter = 'due_today'" 
            :class="{'active-due': filter === 'due_today'}" 
            class="segmented-pill-btn"
            title="Filter items due for return today"
          >
            <span>Due Today</span>
            <span class="pill-metric-badge"><?= $dueTodayCount ?></span>
          </button>
        </div>

        <!-- Search Bar with Instant Filter -->
        <div class="search-input-wrap">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--color-ink-mute);pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input 
            type="text" 
            x-model="search" 
            placeholder="Search borrower, asset, barcode..." 
            class="search-input-field"
          >
          <button 
            type="button" 
            x-show="search.length > 0" 
            @click="search = ''" 
            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:2px;"
            title="Clear search"
          >
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>

      </div>

      <?php if (empty($activeLoans)): ?>
        <div style="padding:3.5rem 1.5rem;text-align:center;background:var(--color-canvas-soft);border-radius:var(--radius-md);border:1px dashed var(--color-hairline);margin:var(--space-md) 0;">
          <div style="width:48px;height:48px;border-radius:50%;background:rgba(5,150,105,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-sm) auto;">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <h3 style="font-size:15px;font-weight:600;color:var(--color-ink);">No Active Equipment Borrows</h3>
          <p style="font-size:13px;color:var(--color-ink-mute);margin-top:4px;max-width:380px;margin-left:auto;margin-right:auto;">
            All physical tools and laboratory assets are safely accounted for in the Supply Office.
          </p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table" style="min-width:760px;">
            <thead>
              <tr style="border-bottom:1px solid var(--color-hairline);text-align:left;font-size:12px;color:var(--color-ink-mute);">
                <th style="padding:.75rem;">Asset Details</th>
                <th style="padding:.75rem;">Borrower Identity</th>
                <th style="padding:.75rem;">Checkout Date</th>
                <th style="padding:.75rem;">Return Deadline</th>
                <th style="padding:.75rem;">Status</th>
                <?php if ($role !== 'executive_admin'): ?>
                  <th style="padding:.75rem;text-align:right;">Action</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($activeLoans as $loan): 
                $itemName = htmlspecialchars($loan['item_name'] ?? 'Item', ENT_QUOTES, 'UTF-8');
                $barcode = htmlspecialchars($loan['barcode'] ?? '', ENT_QUOTES, 'UTF-8');
                $propertyNum = htmlspecialchars($loan['property_number'] ?? '', ENT_QUOTES, 'UTF-8');
                $category = htmlspecialchars($loan['category'] ?? 'General', ENT_QUOTES, 'UTF-8');
                $borrowerName = htmlspecialchars($loan['borrower_name'] ?? 'Borrower', ENT_QUOTES, 'UTF-8');
                $borrowerInitials = strtoupper(substr($borrowerName, 0, 1) . (substr(strstr($borrowerName, ' '), 1, 1) ?? ''));
                $borrowerIdNum = htmlspecialchars($loan['borrower_id_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $borrowerDept = htmlspecialchars($loan['borrower_department'] ?? 'General', ENT_QUOTES, 'UTF-8');
                $borrowerRole = ucfirst(str_replace('_', ' ', htmlspecialchars($loan['borrower_role'] ?? 'student', ENT_QUOTES, 'UTF-8')));
                $borrowerPhone = htmlspecialchars($loan['borrower_phone'] ?? '', ENT_QUOTES, 'UTF-8');
                $borrowerEmail = htmlspecialchars($loan['borrower_email'] ?? '', ENT_QUOTES, 'UTF-8');
                
                $txDateFormatted = !empty($loan['transaction_date']) ? date('M j, Y • h:i A', strtotime($loan['transaction_date'])) : '—';
                $expectedFormatted = !empty($loan['expected_return_date']) ? date('M j, Y • h:i A', strtotime($loan['expected_return_date'])) : '—';
                
                $isOverdue = !empty($loan['is_overdue']);
                $isDueToday = !empty($loan['is_due_today']);
                $dueStatusLabel = htmlspecialchars($loan['due_status_label'] ?? 'Active Borrow', ENT_QUOTES, 'UTF-8');
                $filterCategory = htmlspecialchars($loan['filter_category'] ?? 'upcoming', ENT_QUOTES, 'UTF-8');
                $searchContent = strtolower("$itemName $barcode $propertyNum $category $borrowerName $borrowerIdNum $borrowerDept");
              ?>
                <tr 
                  data-item-id="<?= (int)($loan['item_id'] ?? 0) ?>"
                  data-transaction-id="<?= (int)($loan['transaction_id'] ?? 0) ?>"
                  x-show="(filter === 'all' || filter === '<?= $filterCategory ?>' || (filter === 'overdue' && <?= $isOverdue ? 'true' : 'false' ?>)) && matchesSearch('<?= addslashes($searchContent) ?>')"
                  style="border-bottom:1px solid var(--color-canvas-soft);<?= $isOverdue ? 'background:rgba(234,34,97,0.02);' : '' ?>"
                >
                  <!-- Asset Details -->
                  <td style="padding:.75rem;vertical-align:middle;">
                    <div style="font-weight:500;color:var(--color-ink);font-size:13px;"><?= $itemName ?></div>
                    <div style="display:flex;align-items:center;gap:.4rem;margin-top:2px;">
                      <span class="badge-barcode font-mono text-[11px]"><?= $barcode ?></span>
                      <?php if ($propertyNum): ?>
                        <span style="font-size:11px;color:var(--color-ink-mute);font-family:monospace;">#<?= $propertyNum ?></span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- Borrower Identity with Avatar Chip -->
                  <td style="padding:.75rem;vertical-align:middle;">
                    <div style="display:flex;align-items:center;gap:.6rem;">
                      <div class="borrower-avatar-chip"><?= $borrowerInitials ?: 'U' ?></div>
                      <div>
                        <div style="font-weight:500;color:var(--color-ink);font-size:13px;"><?= $borrowerName ?></div>
                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;">
                          <span><?= $borrowerIdNum ?></span>
                          <span>&bull;</span>
                          <span class="pill-tag-soft" style="font-size:10px;padding:1px 6px;"><?= $borrowerDept ?></span>
                        </div>
                      </div>
                    </div>
                    <?php if ($borrowerPhone || $borrowerEmail): ?>
                      <div style="margin-top:4px;margin-left:36px;font-size:11px;display:flex;gap:.6rem;align-items:center;">
                        <?php if ($borrowerPhone): ?>
                          <a href="tel:<?= $borrowerPhone ?>" title="Call / SMS borrower" style="color:var(--color-primary);text-decoration:none;display:inline-flex;align-items:center;gap:3px;font-weight:500;">
                            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path></svg>
                            <?= $borrowerPhone ?>
                          </a>
                        <?php endif; ?>
                        <?php if ($borrowerEmail): ?>
                          <a href="mailto:<?= $borrowerEmail ?>" title="Send email notice" style="color:var(--color-ink-mute);text-decoration:none;display:inline-flex;align-items:center;gap:3px;">
                            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            Email
                          </a>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <!-- Checkout Date -->
                  <td style="padding:.75rem;vertical-align:middle;font-size:12px;color:var(--color-ink-secondary);" class="num">
                    <?= $txDateFormatted ?>
                  </td>

                  <!-- Return Deadline -->
                  <td style="padding:.75rem;vertical-align:middle;">
                    <div style="font-size:12px;font-weight:500;color:<?= $isOverdue ? 'var(--color-ruby)' : 'var(--color-ink)' ?>;" class="num">
                      <?= $expectedFormatted ?>
                    </div>
                    <div style="font-size:11px;color:<?= $isOverdue ? 'var(--color-ruby)' : ($isDueToday ? 'var(--color-warning)' : 'var(--color-ink-mute)') ?>;margin-top:2px;font-weight:<?= $isOverdue ? '600' : '400' ?>;">
                      <?= $dueStatusLabel ?>
                    </div>
                  </td>

                  <!-- Status Badge -->
                  <td style="padding:.75rem;vertical-align:middle;">
                    <?php if ($isOverdue): ?>
                      <span class="badge badge-maintenance" style="background:rgba(234,34,97,0.1);color:var(--color-ruby);border:1px solid rgba(234,34,97,0.22);font-weight:600;">
                        <span style="width:6px;height:6px;border-radius:50%;background:var(--color-ruby);display:inline-block;margin-right:4px;"></span>
                        Overdue
                      </span>
                    <?php elseif ($isDueToday): ?>
                      <span class="badge badge-pending" style="background:rgba(155,104,41,0.1);color:var(--color-warning);border:1px solid rgba(155,104,41,0.2);font-weight:600;">
                        Due Today
                      </span>
                    <?php else: ?>
                      <span class="badge badge-borrowed" style="font-weight:500;">
                        Active Borrow
                      </span>
                    <?php endif; ?>
                  </td>

                  <!-- Action Button (Redirects to Scan & Transact) -->
                  <?php if ($role !== 'executive_admin'): ?>
                    <td style="padding:.75rem;vertical-align:middle;text-align:right;">
                      <a 
                        href="<?= url('/scan') ?>" 
                        class="btn btn-secondary btn-sm" 
                        style="font-size:12px;padding:6px 14px;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;border-radius:var(--radius-pill);"
                        title="Redirect to Scan &amp; Transact station to scan barcode or lookup asset"
                      >
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18M15 3v18"></path></svg>
                        <span>Scan &amp; Transact</span>
                      </a>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── TAB 2: Pending Pickup Requests ──────────────────────── -->
    <div x-show="tab === 'pending'" style="padding:var(--space-lg);" x-cloak>
      
      <div style="margin-bottom:var(--space-md);">
        <h3 style="font-size:15px;font-weight:600;color:var(--color-ink);">Self-Service Counter Holds</h3>
        <p style="font-size:13px;color:var(--color-ink-mute);margin-top:2px;">
          Online reservations submitted by students and faculty awaiting physical release at the Supply Office counter.
        </p>
      </div>

      <?php if (empty($pendingReservations)): ?>
        <div style="padding:3rem 1.5rem;text-align:center;background:var(--color-canvas-soft);border-radius:var(--radius-md);border:1px dashed var(--color-hairline);">
          <div style="font-size:14px;font-weight:500;color:var(--color-ink);">No Pending Pickup Holds</div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">All requested items have been processed or released.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table" style="min-width:700px;">
            <thead>
              <tr style="border-bottom:1px solid var(--color-hairline);text-align:left;font-size:12px;color:var(--color-ink-mute);">
                <th style="padding:.75rem;">Requested Asset</th>
                <th style="padding:.75rem;">Requester</th>
                <th style="padding:.75rem;">Purpose</th>
                <th style="padding:.75rem;">Reservation Expiry</th>
                <?php if ($role !== 'executive_admin'): ?>
                  <th style="padding:.75rem;text-align:right;">Action</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingReservations as $req): 
                $rItem = htmlspecialchars($req['item_name'] ?? 'Asset', ENT_QUOTES, 'UTF-8');
                $rCode = htmlspecialchars($req['barcode'] ?? '', ENT_QUOTES, 'UTF-8');
                $rUser = htmlspecialchars($req['requester_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
                $rId = htmlspecialchars($req['requester_id_number'] ?? '', ENT_QUOTES, 'UTF-8');
                $rDept = htmlspecialchars($req['requester_department'] ?? 'General', ENT_QUOTES, 'UTF-8');
                $rPurpose = htmlspecialchars($req['request_purpose'] ?? 'Academic / Coursework usage', ENT_QUOTES, 'UTF-8');
                $rTimeLabel = htmlspecialchars($req['time_remaining_label'] ?? 'Hold Active', ENT_QUOTES, 'UTF-8');
                $rExpired = !empty($req['is_expired']);
                $searchReq = strtolower("$rItem $rCode $rUser $rId $rDept $rPurpose");
              ?>
                <tr 
                  data-item-id="<?= (int)($req['item_id'] ?? 0) ?>"
                  x-show="matchesSearch('<?= addslashes($searchReq) ?>')"
                  style="border-bottom:1px solid var(--color-canvas-soft);"
                >
                  <td style="padding:.75rem;vertical-align:middle;">
                    <div style="font-weight:500;color:var(--color-ink);font-size:13px;"><?= $rItem ?></div>
                    <span class="badge-barcode font-mono text-[11px]" style="margin-top:2px;display:inline-block;"><?= $rCode ?></span>
                  </td>

                  <td style="padding:.75rem;vertical-align:middle;">
                    <div style="font-weight:500;color:var(--color-ink);font-size:13px;"><?= $rUser ?></div>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
                      <?= $rId ?> &bull; <span class="pill-tag-soft" style="font-size:10px;padding:1px 6px;"><?= $rDept ?></span>
                    </div>
                  </td>

                  <td style="padding:.75rem;vertical-align:middle;font-size:12px;color:var(--color-ink-secondary);max-width:240px;">
                    <?= $rPurpose ?>
                  </td>

                  <td style="padding:.75rem;vertical-align:middle;">
                    <span class="badge <?= $rExpired ? 'badge-maintenance' : 'badge-pending' ?>">
                      <?= $rTimeLabel ?>
                    </span>
                  </td>

                  <?php if ($role !== 'executive_admin'): ?>
                    <td style="padding:.75rem;vertical-align:middle;text-align:right;">
                      <a 
                        href="<?= url('/scan?barcode=' . urlencode($req['barcode'])) ?>" 
                        class="btn btn-primary btn-sm"
                        style="font-size:12px;padding:6px 14px;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;border-radius:var(--radius-pill);"
                        title="Route to Scan Station to release equipment to borrower"
                      >
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path></svg>
                        <span>Release Item</span>
                      </a>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── TAB 3: Completed Loan History & Returns ─────────────── -->
    <div x-show="tab === 'history'" style="padding:var(--space-lg);" x-cloak>
      
      <div style="margin-bottom:var(--space-md);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
          <h3 style="font-size:15px;font-weight:600;color:var(--color-ink);">Completed Returns &amp; Audit Log</h3>
          <p style="font-size:13px;color:var(--color-ink-mute);margin-top:2px;">
            Recent returned transactions with physical condition inspection ratings and custodian remarks.
          </p>
        </div>
        <span class="pill-tag-soft" style="font-size:11px;background:rgba(22, 101, 52,0.08);color:var(--color-primary);">Last 50 Records</span>
      </div>

      <?php if (empty($completedLoans)): ?>
        <div style="padding:3rem 1.5rem;text-align:center;background:var(--color-canvas-soft);border-radius:var(--radius-md);border:1px dashed var(--color-hairline);">
          <div style="font-size:14px;font-weight:500;color:var(--color-ink);">No Completed Transactions Yet</div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Returned equipment logs will appear here.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table" style="min-width:760px;">
            <thead>
              <tr style="border-bottom:1px solid var(--color-hairline);text-align:left;font-size:12px;color:var(--color-ink-mute);">
                <th style="padding:.75rem;">Return Timestamp</th>
                <th style="padding:.75rem;">Asset Details</th>
                <th style="padding:.75rem;">Borrower</th>
                <th style="padding:.75rem;">Condition Returned</th>
                <th style="padding:.75rem;">Inspector / Custodian</th>
                <th style="padding:.75rem;">Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($completedLoans as $hist): 
                $hItem = htmlspecialchars($hist['item_name'] ?? 'Asset', ENT_QUOTES, 'UTF-8');
                $hCode = htmlspecialchars($hist['barcode'] ?? '', ENT_QUOTES, 'UTF-8');
                $hUser = htmlspecialchars($hist['borrower_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
                $hDept = htmlspecialchars($hist['borrower_department'] ?? 'General', ENT_QUOTES, 'UTF-8');
                $hRetDate = !empty($hist['actual_return_date']) ? date('M j, Y • h:i A', strtotime($hist['actual_return_date'])) : '—';
                $hCondition = strtolower($hist['returned_condition'] ?? 'good');
                $hStaff = htmlspecialchars($hist['processed_by_name'] ?? 'Supply Staff', ENT_QUOTES, 'UTF-8');
                $hRemarks = htmlspecialchars($hist['remarks'] ?? 'Standard check-in', ENT_QUOTES, 'UTF-8');
                $searchHist = strtolower("$hItem $hCode $hUser $hDept $hCondition $hStaff $hRemarks");
                
                $isDamaged = in_array($hCondition, ['damaged', 'not_working']);
              ?>
                <tr 
                  x-show="matchesSearch('<?= addslashes($searchHist) ?>')"
                  style="border-bottom:1px solid var(--color-canvas-soft);"
                >
                  <td style="padding:.75rem;vertical-align:middle;font-size:12px;color:var(--color-ink-secondary);" class="num">
                    <?= $hRetDate ?>
                  </td>

                  <td style="padding:.75rem;vertical-align:middle;">
                    <div style="font-weight:500;color:var(--color-ink);font-size:13px;"><?= $hItem ?></div>
                    <span class="badge-barcode font-mono text-[11px]" style="margin-top:2px;display:inline-block;"><?= $hCode ?></span>
                  </td>

                  <td style="padding:.75rem;vertical-align:middle;">
                    <div style="font-weight:500;color:var(--color-ink);font-size:13px;"><?= $hUser ?></div>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;"><?= $hDept ?></div>
                  </td>

                  <td style="padding:.75rem;vertical-align:middle;">
                    <?php if ($isDamaged): ?>
                      <span class="badge badge-maintenance" style="background:rgba(234,34,97,0.12);color:var(--color-ruby);border:1px solid rgba(234,34,97,0.25);">
                        Damaged
                      </span>
                    <?php else: ?>
                      <span class="badge badge-approved">
                        <?= ucfirst($hCondition) ?>
                      </span>
                    <?php endif; ?>
                  </td>

                  <td style="padding:.75rem;vertical-align:middle;font-size:12px;color:var(--color-ink);">
                    <?= $hStaff ?>
                  </td>

                  <td style="padding:.75rem;vertical-align:middle;font-size:12px;color:var(--color-ink-mute);max-width:220px;">
                    <?= $hRemarks ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (!window.RealtimeHub) return;

  window.RealtimeHub.on('LOAN_RETURNED', function(event) {
    const p = event.payload || {};
    const rows = document.querySelectorAll(`tr[data-item-id="${p.item_id}"]`);
    rows.forEach(row => {
      // If this row is in the active table, remove with smooth animation
      if (row.closest('[x-show="tab === \'active\'"]')) {
        row.classList.add('row-fade-out');
        setTimeout(() => row.remove(), 400);
      }
    });

    // Update pill badge counts
    const activePillBadges = document.querySelectorAll('.active-all .pill-metric-badge');
    activePillBadges.forEach(badge => {
      let cur = parseInt(badge.textContent.trim(), 10) || 0;
      if (cur > 0) {
        badge.textContent = cur - 1;
        badge.classList.add('kpi-pulse');
        setTimeout(() => badge.classList.remove('kpi-pulse'), 500);
      }
    });

    if (window.Toast && typeof window.Toast.info === 'function') {
      window.Toast.info('Asset Checked In', `Asset #${p.item_id} was successfully returned.`);
    }
  });

  window.RealtimeHub.on('LOAN_APPROVED', function(event) {
    const p = event.payload || {};
    // Remove from pending holds table
    const pendingRows = document.querySelectorAll(`[x-show="tab === 'pending'"] tr[data-item-id="${p.item_id}"]`);
    pendingRows.forEach(row => {
      row.classList.add('row-fade-out');
      setTimeout(() => row.remove(), 400);
    });

    if (window.Toast && typeof window.Toast.info === 'function') {
      window.Toast.info('Loan Dispatched', `Asset #${p.item_id} was approved and issued.`);
    }
  });

  window.RealtimeHub.on('LOAN_REQUESTED', function(event) {
    const p = event.payload || {};
    if (window.Toast && typeof window.Toast.info === 'function') {
      window.Toast.info('New Pickup Request', `A reservation hold was submitted for asset #${p.item_id}.`);
    }
  });
});
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
