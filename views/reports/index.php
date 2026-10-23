<?php
/**
 * Reports & Property Analytics Studio
 * Unified analytics cockpit for Property Custodians and Executive Administrators.
 * Features multi-timeframe scoping, Chart.js visualizations, CSV streaming, and print briefings.
 */
declare(strict_types=1);

$role = $_SESSION['role'] ?? 'custodian';
$isCustodian = ($role === 'custodian');

$title = $title ?? ($isCustodian ? 'Reports & Property Analytics' : 'Executive Asset Intelligence');
$breadcrumb = $breadcrumb ?? 'Reports';

$analyticsData  = $analyticsData ?? [];
if (empty($analyticsData)) {
    try {
        $db = \App\Config\Database::getConnection();
        $analyticsData = \App\Controllers\AnalyticsController::computeAnalytics($db, '30d');
    } catch (\Throwable $e) {
        $analyticsData = [];
    }
}
$kpi            = $analyticsData['kpi'] ?? [];
$categoriesList = $categoriesList ?? ($analyticsData['metadata']['categories'] ?? []);
$locationsList  = $locationsList ?? ($analyticsData['metadata']['locations'] ?? []);

require __DIR__ . '/../layouts/shell.php';
?>

<!-- ── Page Header ────────────────────────────────────────── -->
<div class="section-header">
  <div>
    <h1 class="section-title"><?= $isCustodian ? 'Reports &amp; Property Analytics Studio' : 'Executive Analytics &amp; Institutional Intelligence' ?></h1>
    <div class="section-subtitle"><?= $isCustodian ? 'Institutional asset health, circulation velocity, demand patterns, and official compliance exports' : 'Campus-wide property governance, circulation dynamics, and loss accountability cockpit' ?></div>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
    <span class="pill-tag" style="background:rgba(22, 101, 52,.08);color:var(--color-primary);font-weight:500;padding:6px 12px;border-radius:var(--radius-pill);">
      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
      <?= $isCustodian ? 'Property Custodian Intelligence' : 'Executive Governance View' ?>
    </span>
    <button class="btn btn-ghost btn-sm" id="btn-refresh-analytics" style="border-radius:var(--radius-pill);">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 102.13-9.36L1 10"></path></svg>
      Refresh
    </button>
  </div>
</div>

<!-- ── Global Scope & Reporting Toolbar ──────────────────── -->
<div class="analytics-toolbar animate-fade-in-up">
  <!-- Scope Filter Controls -->
  <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
    <span style="font-size:12px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Filter Scope:</span>
    <div>
      <select id="filter-category" class="form-input" style="height:34px;font-size:12px;padding:4px 10px;border-radius:var(--radius-pill);background:var(--color-canvas);">
        <option value="">All Asset Categories</option>
        <?php foreach ($categoriesList as $cat): ?>
          <option value="<?= htmlspecialchars((string)$cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$cat, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Action Buttons -->
  <div style="display:flex;align-items:center;gap:.5rem;">
    <button type="button" id="btn-open-report-configurator" data-modal-open="modal-report-configurator" class="btn btn-secondary btn-sm" style="border-radius:var(--radius-pill);font-size:12px;padding:6px 12px;" title="Configure and generate printable institutional report">
      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
      Print Briefing
    </button>

    <a id="btn-export-csv" href="<?= url('/api/analytics/export-csv?timeframe=30d') ?>" class="btn btn-primary btn-sm" style="border-radius:var(--radius-pill);font-size:12px;padding:6px 14px;" title="Download filtered CSV data spreadsheet">
      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
      Export CSV
    </a>
  </div>
</div>

<!-- ── Dynamic KPI Metric Ribbon ──────────────────────────── -->
<div class="grid-kpi-stats stagger-children" style="margin-bottom:var(--space-xl);">
  
  <!-- Total Institutional Assets -->
  <div class="stat-card">
    <div class="stat-card-header">
      <span class="stat-card-label">Total Campus Assets</span>
      <div class="stat-card-icon icon-indigo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
      </div>
    </div>
    <div class="stat-card-value" id="kpi-total-assets"><?= number_format((int)($kpi['total_assets'] ?? 0)) ?></div>
    <div class="stat-card-footer">
      <span class="stat-card-trend up">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
        <span id="kpi-serviceable-rate"><?= $kpi['serviceability_rate'] ?? 0 ?>%</span>&nbsp;Serviceable
      </span>
      <span class="stat-card-subtext">All institutional properties</span>
    </div>
  </div>

  <!-- Operational Availability -->
  <div class="stat-card">
    <div class="stat-card-header">
      <span class="stat-card-label">Available in Stock</span>
      <div class="stat-card-icon icon-emerald">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
      </div>
    </div>
    <div class="stat-card-value" id="kpi-available-assets"><?= number_format((int)($kpi['available_assets'] ?? 0)) ?></div>
    <div class="stat-card-footer">
      <span class="stat-card-trend up">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
        <span id="kpi-available-rate"><?= $kpi['availability_rate'] ?? 0 ?>%</span>&nbsp;Ready
      </span>
      <span class="stat-card-subtext">Mint &amp; Good condition</span>
    </div>
  </div>

  <!-- Active Circulation Volume -->
  <div class="stat-card">
    <div class="stat-card-header">
      <span class="stat-card-label">Active Borrowers</span>
      <div class="stat-card-icon icon-info">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      </div>
    </div>
    <div class="stat-card-value" id="kpi-active-borrowers"><?= number_format((int)($kpi['active_borrowers'] ?? 0)) ?></div>
    <div class="stat-card-footer">
      <span class="stat-card-trend up">
        <span id="kpi-borrowed-assets"><?= number_format((int)($kpi['borrowed_assets'] ?? 0)) ?></span>&nbsp;Units borrowed
      </span>
      <span class="stat-card-subtext">Currently in custody</span>
    </div>
  </div>

  <!-- Delinquency & Open Holds -->
  <div class="stat-card">
    <div class="stat-card-header">
      <span class="stat-card-label">Overdue / Holds</span>
      <div class="stat-card-icon icon-ruby">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
      </div>
    </div>
    <div class="stat-card-value" id="kpi-overdue-holds"><?= number_format((int)($kpi['overdue_count'] ?? 0)) ?></div>
    <div class="stat-card-footer">
      <span class="stat-card-trend <?= ((int)($kpi['open_damage_holds'] ?? 0) > 0 ? 'down' : 'up') ?>">
        <span id="kpi-damage-holds"><?= number_format((int)($kpi['open_damage_holds'] ?? 0)) ?></span>&nbsp;Damage Holds
      </span>
      <span class="stat-card-subtext"><?= ((int)($kpi['overdue_count'] ?? 0) > 0 ? 'Follow-up required' : 'Zero overdue borrows') ?></span>
    </div>
  </div>

</div>

<!-- ── Analytical Perspective Tab Navigation ──────────────── -->
<div class="analytics-tab-bar">
  <button type="button" class="analytics-tab-btn active" data-target="tab-summary">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
    Summary Dashboard
  </button>
  <button type="button" class="analytics-tab-btn" data-target="tab-assets">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
    Asset Inventory Dynamics
  </button>
  <button type="button" class="analytics-tab-btn" data-target="tab-velocity">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
    Circulation &amp; Velocity
  </button>
  <button type="button" class="analytics-tab-btn" data-target="tab-incidents">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
    Incidents &amp; Accountability
  </button>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- TAB 1: SUMMARY COCKPIT                                     -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="analytics-tab-pane active" id="tab-summary">
  
  <!-- Row 1: Asset Health Doughnut + Circulation Velocity Area -->
  <div class="grid-analytics-2col">
    
    <!-- Asset Health & Condition -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Operational Status Distribution</div>
          <div class="analytics-chart-subtitle">Real-time status breakdown across tracked assets</div>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="summary-health-chart"></canvas>
      </div>
    </div>

    <!-- Circulation Velocity Timeline -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Circulation Velocity Curve</div>
          <div class="analytics-chart-subtitle">Time-series borrow issuances vs. completed returns</div>
        </div>
        <div class="segmented-group segmented-group-sm" data-chart-target="summary-velocity-chart">
          <button type="button" class="segmented-btn" data-tf="7d">7D</button>
          <button type="button" class="segmented-btn active" data-tf="30d">30D</button>
          <button type="button" class="segmented-btn" data-tf="90d">90D</button>
          <button type="button" class="segmented-btn" data-tf="ytd">YTD</button>
          <button type="button" class="segmented-btn" data-tf="all">All</button>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="summary-velocity-chart"></canvas>
      </div>
    </div>

  </div>

  <!-- Row 2: Campus Location Allocation + Top Asset Categories -->
  <div class="grid-analytics-2col">
    
    <!-- Location Density -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Campus Location Density</div>
          <div class="analytics-chart-subtitle">Asset deployment count across departments and labs</div>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="summary-location-chart"></canvas>
      </div>
    </div>

    <!-- Top Categories -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Top Asset Classifications</div>
          <div class="analytics-chart-subtitle">Highest-volume equipment and property categories</div>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="summary-category-chart"></canvas>
      </div>
    </div>

  </div>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- TAB 2: ASSET INVENTORY DYNAMICS                            -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="analytics-tab-pane" id="tab-assets">
  
  <div class="grid-analytics-2col">
    
    <!-- Condition Degradation Matrix -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Physical Condition Spectrum</div>
          <div class="analytics-chart-subtitle">Quality classification (Mint, Good, Fair, Damaged, Disposal)</div>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="dynamics-condition-chart"></canvas>
      </div>
    </div>

    <!-- Asset Lifecycle Distribution -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Asset Lifecycle &amp; Disposition</div>
          <div class="analytics-chart-subtitle">Serviceable vs. maintenance vs. decommissioning</div>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="dynamics-status-chart"></canvas>
      </div>
    </div>

  </div>

  <!-- Full Width: Department Matrix -->
  <div class="analytics-chart-card">
    <div class="analytics-chart-header">
      <div>
        <div class="analytics-chart-title">Institutional Department Allocation</div>
        <div class="analytics-chart-subtitle">Comprehensive property allocation across academic and administrative units</div>
      </div>
    </div>
    <div class="analytics-canvas-wrapper" style="height:320px;">
      <canvas id="dynamics-location-matrix"></canvas>
    </div>
  </div>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- TAB 3: CIRCULATION & LENDING VELOCITY                      -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="analytics-tab-pane" id="tab-velocity">
  
  <!-- Full Width Time-Series Velocity -->
  <div class="analytics-chart-card" style="margin-bottom:var(--space-xl);">
    <div class="analytics-chart-header">
      <div>
        <div class="analytics-chart-title">High-Resolution Lending &amp; Return Flow</div>
        <div class="analytics-chart-subtitle">Daily and weekly transaction movement throughout the selected timeframe</div>
      </div>
      <div class="segmented-group segmented-group-sm" data-chart-target="velocity-detailed-chart">
        <button type="button" class="segmented-btn" data-tf="7d">7D</button>
        <button type="button" class="segmented-btn active" data-tf="30d">30D</button>
        <button type="button" class="segmented-btn" data-tf="90d">90D</button>
        <button type="button" class="segmented-btn" data-tf="ytd">YTD</button>
        <button type="button" class="segmented-btn" data-tf="all">All</button>
      </div>
    </div>
    <div class="analytics-canvas-wrapper" style="height:300px;">
      <canvas id="velocity-detailed-chart"></canvas>
    </div>
  </div>

  <div class="grid-analytics-2col">
    
    <!-- Borrower Demographics -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Borrower Demographics Split</div>
          <div class="analytics-chart-subtitle">Faculty vs. Student borrowing volume ratio</div>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="velocity-demographics-chart"></canvas>
      </div>
    </div>

    <!-- Day of Week Demand -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Weekly Demand Heat Curve</div>
          <div class="analytics-chart-subtitle">Borrow transaction density by day of the week</div>
        </div>
        <div class="segmented-group segmented-group-sm" data-chart-target="velocity-dow-chart">
          <button type="button" class="segmented-btn active" data-tf="30d">30D</button>
          <button type="button" class="segmented-btn" data-tf="90d">90D</button>
          <button type="button" class="segmented-btn" data-tf="ytd">YTD</button>
          <button type="button" class="segmented-btn" data-tf="all">All</button>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="velocity-dow-chart"></canvas>
      </div>
    </div>

  </div>

</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- TAB 4: INCIDENTS & ACCOUNTABILITY                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="analytics-tab-pane" id="tab-incidents">
  
  <!-- Financial Liability & Velocity Summary Ribbon -->
  <div class="grid-analytics-3col" style="margin-bottom:var(--space-xl);">
    
    <div class="card" style="padding:var(--space-lg);border-left:3px solid var(--color-ruby);background:var(--color-canvas);">
      <div style="font-size:11px;font-weight:600;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Total Recorded Damage Cost</div>
      <div style="font-size:24px;font-weight:300;color:var(--color-ruby);margin-top:4px;font-feature-settings:'tnum';letter-spacing:-0.5px;" id="inc-total-cost">
        PHP <?= number_format((float)($kpi['total_damage_cost'] ?? 0), 2) ?>
      </div>
      <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Campus equipment incident liability</div>
    </div>

    <div class="card" style="padding:var(--space-lg);border-left:3px solid var(--color-success);background:var(--color-canvas);">
      <div style="font-size:11px;font-weight:600;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Cleared Settlement Value</div>
      <div style="font-size:24px;font-weight:300;color:var(--color-success);margin-top:4px;font-feature-settings:'tnum';letter-spacing:-0.5px;" id="inc-cleared-cost">
        PHP <?= number_format((float)($kpi['total_cleared_cost'] ?? 0), 2) ?>
      </div>
      <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Recovered in-kind or repaired value</div>
    </div>

    <div class="card" style="padding:var(--space-lg);border-left:3px solid var(--color-info);background:var(--color-canvas);">
      <div style="font-size:11px;font-weight:600;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Avg Clearance Turnaround</div>
      <div style="font-size:24px;font-weight:300;color:var(--color-info);margin-top:4px;font-feature-settings:'tnum';letter-spacing:-0.5px;" id="inc-resolution-days">
        <?= $kpi['avg_resolution_days'] ?? 0 ?> Days
      </div>
      <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Incident reporting to clearance velocity</div>
    </div>

  </div>

  <div class="grid-analytics-2col">
    
    <!-- Damage Incidents by Severity -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Damage Severity Classification</div>
          <div class="analytics-chart-subtitle">Repairable damage vs. non-functional breakdown</div>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="incident-severity-chart"></canvas>
      </div>
    </div>

    <!-- Settlement Methods Distribution -->
    <div class="analytics-chart-card">
      <div class="analytics-chart-header">
        <div>
          <div class="analytics-chart-title">Settlement Resolution Methods</div>
          <div class="analytics-chart-subtitle">In-kind replacements, repair reimbursements, and waivers</div>
        </div>
      </div>
      <div class="analytics-canvas-wrapper">
        <canvas id="incident-settlement-chart"></canvas>
      </div>
    </div>

  </div>

</div>

<!-- ── Report Configurator Modal ──────────────────────────── -->
<div id="modal-report-configurator" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
  <div class="modal-panel lg" style="max-width:680px;">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="report-modal-title" style="display:flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-primary);"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
          Configure Institutional Report (Print / PDF)
        </h2>
        <div class="modal-subtitle">Select date bounds, scope filters, and modular report sections to include.</div>
      </div>
      <button type="button" class="modal-close" data-modal-close="modal-report-configurator" id="btn-close-report-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="modal-body" style="max-height:calc(85vh - 160px);overflow-y:auto;padding-right:4px;">
      
      <!-- Report Format Selection (Official Audit vs Visual Briefing) -->
      <div style="margin-bottom:var(--space-md);padding:12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-md);">
        <div style="font-size:11px;font-weight:600;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
          Select Export Format &amp; Report Type:
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <label class="radio-card active" id="label-format-official" style="display:flex;gap:10px;padding:10px;border:1.5px solid var(--color-primary);border-radius:var(--radius-md);cursor:pointer;background:rgba(22, 101, 52, 0.04);transition:all .15s ease;">
            <input type="radio" name="rpt_format_type" value="official" checked style="margin-top:2px;accent-color:var(--color-primary);">
            <div>
              <div style="font-size:12.5px;font-weight:600;color:var(--color-ink);">Official Institutional Audit Report</div>
              <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">1-to-1 physical PDF layout with 5 ledger tables, institutional letterhead, and certified signatories.</div>
            </div>
          </label>
          <label class="radio-card" id="label-format-visual" style="display:flex;gap:10px;padding:10px;border:1px solid var(--color-hairline);border-radius:var(--radius-md);cursor:pointer;background:var(--color-canvas);transition:all .15s ease;">
            <input type="radio" name="rpt_format_type" value="visual" style="margin-top:2px;accent-color:var(--color-primary);">
            <div>
              <div style="font-size:12.5px;font-weight:600;color:var(--color-ink);">Visual Analytics Presentation Briefing</div>
              <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Dynamic presentation with Chart.js velocity graphs, KPI cards, and trend breakdowns.</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Timeframe & Scope Grid -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md);margin-bottom:var(--space-md);">
        <div>
          <label style="font-size:11px;font-weight:600;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:4px;">Timeframe:</label>
          <select id="rpt-timeframe" class="form-input w-full" style="height:36px;font-size:12px;width:100%;">
            <option value="7d">Last 7 Days (7D)</option>
            <option value="30d" selected>Last 30 Days (30D)</option>
            <option value="90d">Last 90 Days (90D)</option>
            <option value="ytd">Year-to-Date (YTD)</option>
            <option value="all">All Time Recorded</option>
            <option value="custom">Custom Date Range...</option>
          </select>
        </div>

        <div>
          <label style="font-size:11px;font-weight:600;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:4px;">Category Filter:</label>
          <select id="rpt-category" class="form-input w-full" style="height:36px;font-size:12px;width:100%;">
            <option value="">All Categories</option>
            <?php foreach ($categoriesList as $cat): ?>
              <option value="<?= htmlspecialchars((string)$cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$cat, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Custom Date Inputs (Conditional) -->
      <div id="rpt-custom-date-wrap" class="date-range-container" style="display:none;margin-bottom:var(--space-md);">
        <div style="flex:1;min-width:140px;">
          <label style="font-size:10.5px;font-weight:500;color:var(--color-ink-mute);display:block;margin-bottom:2px;">Start Date:</label>
          <input type="date" id="rpt-start-date" class="form-input w-full" style="height:32px;font-size:11.5px;" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
        </div>
        <div style="flex:1;min-width:140px;">
          <label style="font-size:10.5px;font-weight:500;color:var(--color-ink-mute);display:block;margin-bottom:2px;">End Date:</label>
          <input type="date" id="rpt-end-date" class="form-input w-full" style="height:32px;font-size:11.5px;" value="<?= date('Y-m-d') ?>">
        </div>
      </div>

      <!-- Visual Options Wrap (Visible only when Visual Analytics Briefing is chosen) -->
      <div id="rpt-visual-options-wrap" style="display:none;margin-bottom:var(--space-md);padding-top:10px;border-top:1px dashed var(--color-hairline);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <div style="font-size:11px;font-weight:600;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Include Sections &amp; Visualizations:</div>
          <div style="display:flex;gap:8px;">
            <button type="button" id="btn-select-all-sections" style="background:none;border:none;color:var(--color-primary);font-size:11px;font-weight:500;cursor:pointer;">Select All</button>
            <span style="color:var(--color-hairline);">|</span>
            <button type="button" id="btn-clear-all-sections" style="background:none;border:none;color:var(--color-ink-mute);font-size:11px;font-weight:500;cursor:pointer;">Clear All</button>
          </div>
        </div>

        <div class="checkbox-card-grid" id="rpt-sections-grid">
          
          <label class="checkbox-card">
            <input type="checkbox" name="rpt_section" value="kpi" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Institutional KPI Metrics</div>
              <div class="checkbox-card-desc">Total assets, availability %, serviceability %, active borrowers, overdue counts</div>
            </div>
          </label>

          <label class="checkbox-card">
            <input type="checkbox" name="rpt_section" value="status" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Operational Status Breakdown</div>
              <div class="checkbox-card-desc">Doughnut chart &amp; table (Available, Borrowed, Maintenance, Disposal)</div>
            </div>
          </label>

          <label class="checkbox-card">
            <input type="checkbox" name="rpt_section" value="condition" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Physical Condition Spectrum</div>
              <div class="checkbox-card-desc">Solid bar chart (Mint, Good, Fair, Damaged, Not Working, Condemned)</div>
            </div>
          </label>

          <label class="checkbox-card">
            <input type="checkbox" name="rpt_section" value="velocity" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Circulation Velocity Curve</div>
              <div class="checkbox-card-desc">Lending issuances vs. completed return movement line graph</div>
            </div>
          </label>

          <label class="checkbox-card">
            <input type="checkbox" name="rpt_section" value="categories" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Top Asset Classifications</div>
              <div class="checkbox-card-desc">Highest-volume equipment categories with distribution progress bars</div>
            </div>
          </label>

          <label class="checkbox-card">
            <input type="checkbox" name="rpt_section" value="locations" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Campus Department Allocation</div>
              <div class="checkbox-card-desc">Property deployment density across academic rooms and laboratories</div>
            </div>
          </label>

          <label class="checkbox-card">
            <input type="checkbox" name="rpt_section" value="dow" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Weekly Demand Heat Curve</div>
              <div class="checkbox-card-desc">Day-of-week borrow frequency bar graph</div>
            </div>
          </label>

          <label class="checkbox-card">
            <input type="checkbox" name="rpt_section" value="incidents" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Damage &amp; Incident Liability</div>
              <div class="checkbox-card-desc">Damage holds, settlement recovery methods, and financial valuation</div>
            </div>
          </label>

          <label class="checkbox-card" style="grid-column:1/-1;">
            <input type="checkbox" name="rpt_section" value="signatories" checked>
            <div class="checkbox-card-body">
              <div class="checkbox-card-title">Institutional Signatory Blocks</div>
              <div class="checkbox-card-desc">Official certification blocks (Property Custodian, Internal Audit, Campus Director)</div>
            </div>
          </label>

        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-modal-close="modal-report-configurator" id="btn-cancel-report-modal">Cancel</button>
      <button type="button" class="btn btn-primary btn-sm" id="btn-submit-report-modal">
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Generate &amp; Open Report
      </button>
    </div>

  </div>
</div>

<!-- ── Chart.js and Reactive Client-Side Analytics Engine ── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= asset('/js/charts.js') ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Pre-hydrated initial server data for instant rendering
  let currentData = <?= json_encode($analyticsData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  
  let currentFilter = {
    timeframe: '30d',
    category: ''
  };

  // 1. Tab Switching Controller
  const tabBtns = document.querySelectorAll('.analytics-tab-btn');
  const tabPanes = document.querySelectorAll('.analytics-tab-pane');

  tabBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanes.forEach(p => p.classList.remove('active'));

      this.classList.add('active');
      const targetPane = document.getElementById(targetId);
      if (targetPane) {
        targetPane.classList.add('active');
      }

      // Re-render charts for newly active tab if needed
      renderChartsForActiveTab(targetId, currentData);
    });
  });

  // 2. Local Per-Widget Timeframe Toggles
  document.querySelectorAll('.segmented-group-sm[data-chart-target]').forEach(function (group) {
    const targetChartId = group.getAttribute('data-chart-target');
    const localBtns = group.querySelectorAll('.segmented-btn');

    localBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        localBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const chosenTf = this.getAttribute('data-tf');
        fetchSingleChartData(targetChartId, chosenTf);
      });
    });
  });

  function fetchSingleChartData(chartId, tf) {
    const params = new URLSearchParams({
      timeframe: tf,
      category: currentFilter.category
    });

    const endpoint = (window.appUrl ? window.appUrl('/api/analytics/executive') : '/api/analytics/executive') + '?' + params.toString();
    fetch(endpoint, {
      headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(res => {
      if (res.success && res.data) {
        if (chartId === 'summary-velocity-chart' || chartId === 'velocity-detailed-chart') {
          const vSeries = res.data.circulation?.velocity_series || {};
          ChartFactory.createLineArea(chartId, {
            labels: vSeries.labels || [],
            datasets: [
              { label: 'Borrow Issuances', data: vSeries.borrows || [], borderColor: '#166534', backgroundColor: 'rgba(22, 101, 52, 0.08)' },
              { label: 'Completed Returns', data: vSeries.returns || [], borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.08)' }
            ]
          });
        } else if (chartId === 'velocity-dow-chart') {
          const dowDemand = res.data.circulation?.dow_demand || {};
          ChartFactory.createBar('velocity-dow-chart', {
            labels: dowDemand.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            data: dowDemand.data || [],
            backgroundColor: '#8b5cf6',
            hoverBackgroundColor: '#7c3aed',
          });
        }
      }
    })
    .catch(err => console.error('Failed to fetch local chart data:', err));
  }

  // 4. Category Select
  const catSelect = document.getElementById('filter-category');

  if (catSelect) {
    catSelect.addEventListener('change', function () {
      currentFilter.category = this.value;
      fetchAnalyticsData();
    });
  }

  // 5. Refresh Button
  const refreshBtn = document.getElementById('btn-refresh-analytics');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      fetchAnalyticsData();
    });
  }

  // 6. AJAX Data Fetcher
  function fetchAnalyticsData() {
    updateExportLinks();

    const params = new URLSearchParams({
      timeframe: currentFilter.timeframe,
      category: currentFilter.category
    });

    const endpoint = (window.appUrl ? window.appUrl('/api/analytics/executive') : '/api/analytics/executive') + '?' + params.toString();
    fetch(endpoint, {
      headers: { 'Accept': 'application/json' }
    })
    .then(function (res) {
      if (!res.ok) throw new Error('HTTP error ' + res.status);
      return res.json();
    })
    .then(function (res) {
      if (res.success && res.data) {
        currentData = res.data;
        updateKpiRibbon(currentData.kpi || {});
        renderAllCharts(currentData);
      }
    })
    .catch(function (err) {
      console.error('Failed to fetch analytics:', err);
      if (typeof Toast !== 'undefined') {
        Toast.show('Unable to refresh live analytics. Please check connection.', 'error');
      }
    });
  }

  // 7. Update Export Links to match active filters
  function updateExportLinks() {
    const params = new URLSearchParams({
      timeframe: currentFilter.timeframe,
      category: currentFilter.category
    }).toString();

    const csvBtn = document.getElementById('btn-export-csv');
    if (csvBtn) {
      csvBtn.href = (window.appUrl ? window.appUrl('/api/analytics/export-csv') : '/api/analytics/export-csv') + '?' + params;
    }
  }

  // ── 8. Report Configurator Modal Engine ───────────────────────
  const reportModal = document.getElementById('modal-report-configurator');
  const btnOpenReportModal = document.getElementById('btn-open-report-configurator');
  const btnCloseReportModal = document.getElementById('btn-close-report-modal');
  const btnCancelReportModal = document.getElementById('btn-cancel-report-modal');
  const btnSubmitReportModal = document.getElementById('btn-submit-report-modal');
  const rptTimeframeSelect = document.getElementById('rpt-timeframe');
  const rptCustomDateWrap = document.getElementById('rpt-custom-date-wrap');
  const rptCategorySelect = document.getElementById('rpt-category');
  const btnSelectAllSections = document.getElementById('btn-select-all-sections');
  const btnClearAllSections = document.getElementById('btn-clear-all-sections');
  const presetBtns = document.querySelectorAll('.report-preset-btn');

  function openReportModal() {
    if (rptCategorySelect && catSelect) {
      rptCategorySelect.value = catSelect.value;
    }
    if (window.Modal && typeof window.Modal.open === 'function') {
      window.Modal.open('modal-report-configurator');
    } else if (reportModal) {
      reportModal.classList.add('open');
      document.body.classList.add('modal-open');
    }
  }

  function closeReportModal() {
    if (window.Modal && typeof window.Modal.close === 'function') {
      window.Modal.close('modal-report-configurator');
    } else if (reportModal) {
      reportModal.classList.remove('open');
      document.body.classList.remove('modal-open');
    }
  }

  if (btnOpenReportModal) btnOpenReportModal.addEventListener('click', openReportModal);
  if (btnCloseReportModal) btnCloseReportModal.addEventListener('click', closeReportModal);
  if (btnCancelReportModal) btnCancelReportModal.addEventListener('click', closeReportModal);

  // Timeframe custom date visibility toggle
  if (rptTimeframeSelect) {
    rptTimeframeSelect.addEventListener('change', function () {
      if (rptCustomDateWrap) {
        rptCustomDateWrap.style.display = this.value === 'custom' ? 'flex' : 'none';
      }
    });
  }

  // Preset templates mapping
  const PRESET_MAP = {
    full: ['kpi', 'status', 'condition', 'velocity', 'categories', 'locations', 'dow', 'incidents', 'signatories'],
    summary: ['kpi', 'status', 'velocity'],
    inventory: ['status', 'condition', 'categories', 'locations'],
    circulation: ['velocity', 'dow'],
    incidents: ['kpi', 'incidents', 'signatories']
  };

  presetBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      presetBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const presetKey = this.getAttribute('data-preset');
      const activeList = PRESET_MAP[presetKey] || PRESET_MAP.full;

      document.querySelectorAll('input[name="rpt_section"]').forEach(cb => {
        cb.checked = activeList.includes(cb.value);
      });
    });
  });

  // Select all / Clear all section checkboxes
  if (btnSelectAllSections) {
    btnSelectAllSections.addEventListener('click', function () {
      document.querySelectorAll('input[name="rpt_section"]').forEach(cb => cb.checked = true);
      presetBtns.forEach(b => b.classList.remove('active'));
      const fullBtn = document.querySelector('.report-preset-btn[data-preset="full"]');
      if (fullBtn) fullBtn.classList.add('active');
    });
  }

  if (btnClearAllSections) {
    btnClearAllSections.addEventListener('click', function () {
      document.querySelectorAll('input[name="rpt_section"]').forEach(cb => cb.checked = false);
      presetBtns.forEach(b => b.classList.remove('active'));
    });
  }

  // Toggle format between Official Audit Report and Visual Presentation Briefing
  const formatRadios = document.querySelectorAll('input[name="rpt_format_type"]');
  const visualOptionsWrap = document.getElementById('rpt-visual-options-wrap');
  const labelOfficial = document.getElementById('label-format-official');
  const labelVisual = document.getElementById('label-format-visual');

  function updateFormatTypeUi() {
    const selectedFormat = document.querySelector('input[name="rpt_format_type"]:checked')?.value || 'official';
    if (selectedFormat === 'official') {
      if (visualOptionsWrap) visualOptionsWrap.style.display = 'none';
      if (labelOfficial) {
        labelOfficial.style.border = '1.5pt solid var(--color-primary)';
        labelOfficial.style.background = 'rgba(22, 101, 52, 0.04)';
      }
      if (labelVisual) {
        labelVisual.style.border = '1px solid var(--color-hairline)';
        labelVisual.style.background = 'var(--color-canvas)';
      }
      if (btnSubmitReportModal) {
        btnSubmitReportModal.innerHTML = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Generate Official Audit Report';
      }
    } else {
      if (visualOptionsWrap) visualOptionsWrap.style.display = 'block';
      if (labelVisual) {
        labelVisual.style.border = '1.5pt solid var(--color-primary)';
        labelVisual.style.background = 'rgba(22, 101, 52, 0.04)';
      }
      if (labelOfficial) {
        labelOfficial.style.border = '1px solid var(--color-hairline)';
        labelOfficial.style.background = 'var(--color-canvas)';
      }
      if (btnSubmitReportModal) {
        btnSubmitReportModal.innerHTML = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Generate Visual Briefing';
      }
    }
  }

  formatRadios.forEach(radio => {
    radio.addEventListener('change', updateFormatTypeUi);
  });
  updateFormatTypeUi();

  // Submit and open report in new tab
  if (btnSubmitReportModal) {
    btnSubmitReportModal.addEventListener('click', function () {
      const selectedFormat = document.querySelector('input[name="rpt_format_type"]:checked')?.value || 'official';
      const tf = rptTimeframeSelect ? rptTimeframeSelect.value : '30d';
      const cat = rptCategorySelect ? rptCategorySelect.value : '';
      const startDate = document.getElementById('rpt-start-date') ? document.getElementById('rpt-start-date').value : '';
      const endDate = document.getElementById('rpt-end-date') ? document.getElementById('rpt-end-date').value : '';

      // Mode 1: Official Institutional Audit Report (1-to-1 PDF/Print)
      if (selectedFormat === 'official') {
        const query = new URLSearchParams({
          timeframe: tf,
          category: cat,
        });

        if (tf === 'custom') {
          if (startDate) query.set('start_date', startDate);
          if (endDate) query.set('end_date', endDate);
        }

        const targetUrl = (window.appUrl ? window.appUrl('/api/analytics/institutional-report') : '/api/analytics/institutional-report') + '?' + query.toString();
        window.open(targetUrl, '_blank');
        closeReportModal();
        return;
      }

      // Mode 2: Visual Analytics Presentation Briefing
      const checkedBoxes = Array.from(document.querySelectorAll('input[name="rpt_section"]:checked'));
      if (checkedBoxes.length === 0) {
        if (typeof Toast !== 'undefined' && typeof Toast.error === 'function') {
          Toast.error('Validation Error', 'Please select at least one section to include in the visual briefing.');
        } else if (typeof Toast !== 'undefined' && typeof Toast.show === 'function') {
          Toast.show('Validation Error', 'Please select at least one section to include in the visual briefing.', { type: 'error' });
        }
        return;
      }

      const selectedSections = checkedBoxes.map(cb => cb.value).join(',');
      const query = new URLSearchParams({
        timeframe: tf,
        category: cat,
        sections: selectedSections,
      });

      if (tf === 'custom') {
        if (startDate) query.set('start_date', startDate);
        if (endDate) query.set('end_date', endDate);
      }

      const targetUrl = (window.appUrl ? window.appUrl('/api/analytics/print-briefing') : '/api/analytics/print-briefing') + '?' + query.toString();
      window.open(targetUrl, '_blank');
      closeReportModal();
    });
  }

  // 8. Update KPI Numbers in DOM
  function updateKpiRibbon(kpi) {
    const totalEl = document.getElementById('kpi-total-assets');
    const availEl = document.getElementById('kpi-available-assets');
    const availRateEl = document.getElementById('kpi-available-rate');
    const servRateEl = document.getElementById('kpi-serviceable-rate');
    const activeBorrowEl = document.getElementById('kpi-active-borrowers');
    const borrowedEl = document.getElementById('kpi-borrowed-assets');
    const overdueEl = document.getElementById('kpi-overdue-holds');
    const damageHoldsEl = document.getElementById('kpi-damage-holds');

    if (totalEl) totalEl.textContent = Number(kpi.total_assets || 0).toLocaleString();
    if (availEl) availEl.textContent = Number(kpi.available_assets || 0).toLocaleString();
    if (availRateEl) availRateEl.textContent = (kpi.availability_rate || 0) + '%';
    if (servRateEl) servRateEl.textContent = (kpi.serviceability_rate || 0) + '%';
    if (activeBorrowEl) activeBorrowEl.textContent = Number(kpi.active_borrowers || 0).toLocaleString();
    if (borrowedEl) borrowedEl.textContent = Number(kpi.borrowed_assets || 0).toLocaleString();
    if (overdueEl) overdueEl.textContent = Number(kpi.overdue_count || 0).toLocaleString();
    if (damageHoldsEl) damageHoldsEl.textContent = Number(kpi.open_damage_holds || 0).toLocaleString();

    // Financial KPI Elements
    const incTotalCost = document.getElementById('inc-total-cost');
    const incClearedCost = document.getElementById('inc-cleared-cost');
    const incResDays = document.getElementById('inc-resolution-days');

    if (incTotalCost) incTotalCost.textContent = 'PHP ' + Number(kpi.total_damage_cost || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (incClearedCost) incClearedCost.textContent = 'PHP ' + Number(kpi.total_cleared_cost || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (incResDays) incResDays.textContent = (kpi.avg_resolution_days || 0) + ' Days';
  }

  // 9. Render All Perspective Charts
  function renderAllCharts(data) {
    if (!data || typeof ChartFactory === 'undefined') return;

    // Tab 1: Summary
    const statusDist = data.asset_dynamics?.status_distribution || {};
    ChartFactory.createDoughnut('summary-health-chart', {
      labels: statusDist.labels || ['Available', 'Borrowed', 'Maintenance', 'For Disposal', 'Disposed'],
      data: statusDist.data || [0, 0, 0, 0, 0],
      colors: ['#059669', '#166534', '#d97706', '#ea2261', '#64748d'],
    });

    const vSeries = data.circulation?.velocity_series || {};
    ChartFactory.createLineArea('summary-velocity-chart', {
      labels: vSeries.labels || [],
      datasets: [
        { label: 'Borrow Issuances', data: vSeries.borrows || [], borderColor: '#166534', backgroundColor: 'rgba(22, 101, 52, 0.08)' },
        { label: 'Completed Returns', data: vSeries.returns || [], borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.08)' }
      ]
    });

    const topLoc = data.asset_dynamics?.top_locations || {};
    ChartFactory.createHorizontalBar('summary-location-chart', {
      labels: topLoc.labels || [],
      data: topLoc.data || [],
      color: '#166534',
      hoverBg: '#14532d'
    });

    const topCat = data.asset_dynamics?.top_categories || {};
    ChartFactory.createBar('summary-category-chart', {
      labels: topCat.labels || [],
      data: topCat.data || [],
      backgroundColor: '#059669',
      hoverBackgroundColor: '#047857',
    });

    // Tab 2: Asset Dynamics
    const condDist = data.asset_dynamics?.condition_distribution || {};
    ChartFactory.createBar('dynamics-condition-chart', {
      labels: condDist.labels || ['Mint', 'Good', 'Fair', 'Damaged', 'Not Working', 'For Disposal'],
      data: condDist.data || [],
      backgroundColor: ['#059669', '#10b981', '#d97706', '#ea2261', '#dc2626', '#64748d'],
      hoverBackgroundColor: ['#047857', '#059669', '#b45309', '#be123c', '#b91c1c', '#475569'],
    });

    ChartFactory.createDoughnut('dynamics-status-chart', {
      labels: statusDist.labels || [],
      data: statusDist.data || [],
      colors: ['#059669', '#166534', '#d97706', '#ea2261', '#64748d'],
    });

    ChartFactory.createHorizontalBar('dynamics-location-matrix', {
      labels: topLoc.labels || [],
      data: topLoc.data || [],
      color: '#166534',
      hoverBg: '#14532d'
    });

    // Tab 3: Circulation Velocity
    ChartFactory.createLineArea('velocity-detailed-chart', {
      labels: vSeries.labels || [],
      datasets: [
        { label: 'Borrow Transactions', data: vSeries.borrows || [], borderColor: '#166534', backgroundColor: 'rgba(22, 101, 52, 0.12)' },
        { label: 'Return Transactions', data: vSeries.returns || [], borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.12)' }
      ]
    });

    const demoDist = data.circulation?.borrower_demographics || {};
    ChartFactory.createDoughnut('velocity-demographics-chart', {
      labels: demoDist.labels || ['Faculty Borrows', 'Student Borrows'],
      data: demoDist.data || [0, 0],
      colors: ['#166534', '#0284c7'],
    });

    const dowDemand = data.circulation?.dow_demand || {};
    ChartFactory.createBar('velocity-dow-chart', {
      labels: dowDemand.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      data: dowDemand.data || [],
      backgroundColor: '#8b5cf6',
      hoverBackgroundColor: '#7c3aed',
    });

    // Tab 4: Incidents & Accountability
    const sevDist = data.incidents?.severity_distribution || {};
    ChartFactory.createDoughnut('incident-severity-chart', {
      labels: sevDist.labels || ['Damaged (Repairable)', 'Not Working (Heavy)'],
      data: sevDist.data || [0, 0],
      colors: ['#d97706', '#dc2626'],
    });

    const settleDist = data.incidents?.settlement_distribution || {};
    ChartFactory.createDoughnut('incident-settlement-chart', {
      labels: settleDist.labels || ['In-Kind Replacement', 'Repair Cost Settled', 'Admin Waiver'],
      data: settleDist.data || [0, 0, 0],
      colors: ['#059669', '#166534', '#64748d'],
    });
  }

  function renderChartsForActiveTab(tabId, data) {
    renderAllCharts(data);
  }

  // Initial Chart Render
  renderAllCharts(currentData);
});
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
