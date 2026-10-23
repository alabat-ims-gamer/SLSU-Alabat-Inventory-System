<?php
/**
 * Executive Institutional Briefing & Audit Report Layout
 * Formatted for A4 / Letter official institutional reporting with high-DPI printable vector charts,
 * structured tables with progress indicators, and dynamic section modularity.
 */
declare(strict_types=1);

$kpi = $data['kpi'] ?? [];
$assetDyn = $data['asset_dynamics'] ?? [];
$circ = $data['circulation'] ?? [];
$incidents = $data['incidents'] ?? [];
$meta = $data['metadata'] ?? [];

// Active sections map passed by AnalyticsController
$activeSections = $activeSections ?? [
    'kpi'         => true,
    'status'      => true,
    'condition'   => true,
    'velocity'    => true,
    'categories'  => true,
    'locations'   => true,
    'dow'         => true,
    'incidents'   => true,
    'signatories' => true,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SLSU-Alabat — Executive Institutional Briefing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="<?= asset('/js/chart.min.js') ?>"></script>
  <style>
    :root {
      --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      --color-ink: #0d253d;
      --color-ink-secondary: #273951;
      --color-ink-mute: #64748d;
      --color-primary: #166534;
      --color-success: #059669;
      --color-warning: #d97706;
      --color-danger: #dc2626;
      --color-ruby: #ea2261;
      --color-hairline: #e3e8ee;
      --color-canvas: #ffffff;
      --color-canvas-soft: #f8fafc;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: var(--font-family);
      font-size: 12px;
      line-height: 1.5;
      color: var(--color-ink);
      background: var(--color-canvas-soft);
      padding: 2rem 1rem;
    }
    .print-container {
      max-width: 960px;
      margin: 0 auto;
      background: var(--color-canvas);
      padding: 2.5rem 3rem;
      border-radius: 12px;
      box-shadow: 0 4px 24px rgba(0, 55, 112, 0.08);
      border: 1px solid var(--color-hairline);
    }
    .action-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--color-hairline);
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 18px;
      font-size: 13px;
      font-weight: 500;
      border-radius: 9999px;
      cursor: pointer;
      text-decoration: none;
      border: 1px solid transparent;
      transition: all 0.2s ease;
    }
    .btn-primary {
      background: var(--color-primary);
      color: #ffffff;
    }
    .btn-primary:hover { background: #14532d; }
    .btn-secondary {
      background: #ffffff;
      color: var(--color-ink);
      border-color: var(--color-hairline);
    }
    .btn-secondary:hover { background: var(--color-canvas-soft); }
    
    /* Institutional Header */
    .header-block {
      text-align: center;
      margin-bottom: 2rem;
      padding-bottom: 1.25rem;
      border-bottom: 2px solid var(--color-ink);
    }
    .inst-title {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--color-ink-mute);
      margin-bottom: 4px;
    }
    .doc-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--color-ink);
      letter-spacing: -0.5px;
      margin-bottom: 4px;
    }
    .doc-subtitle {
      font-size: 12px;
      color: var(--color-ink-secondary);
    }
    .meta-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.75rem;
      background: var(--color-canvas-soft);
      border: 1px solid var(--color-hairline);
      border-radius: 8px;
      padding: 0.85rem 1rem;
      margin-bottom: 1.75rem;
      font-size: 11.5px;
    }
    .meta-item-label { color: var(--color-ink-mute); margin-bottom: 2px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
    .meta-item-value { font-weight: 600; color: var(--color-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    /* KPI Summary Cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.85rem;
      margin-bottom: 1.75rem;
    }
    .kpi-card {
      border: 1px solid var(--color-hairline);
      border-radius: 8px;
      padding: 0.85rem 1rem;
      background: #ffffff;
      border-left: 3px solid var(--color-primary);
    }
    .kpi-card.kpi-success { border-left-color: var(--color-success); }
    .kpi-card.kpi-warning { border-left-color: var(--color-warning); }
    .kpi-card.kpi-danger  { border-left-color: var(--color-ruby); }
    
    .kpi-label {
      font-size: 10px;
      color: var(--color-ink-mute);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 2px;
    }
    .kpi-value {
      font-size: 20px;
      font-weight: 600;
      color: var(--color-ink);
      font-feature-settings: 'tnum';
      letter-spacing: -0.5px;
    }
    .kpi-sub {
      font-size: 10.5px;
      color: var(--color-ink-secondary);
      margin-top: 2px;
    }
    
    /* Section Headers */
    .report-section {
      margin-bottom: 1.75rem;
      page-break-inside: avoid;
    }
    .section-title {
      font-size: 13px;
      font-weight: 600;
      color: var(--color-ink);
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--color-hairline);
      padding-bottom: 4px;
    }
    
    /* Data Tables */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 11.5px;
    }
    .data-table th {
      text-align: left;
      padding: 6px 10px;
      background: var(--color-canvas-soft);
      border-bottom: 1px solid var(--color-hairline);
      color: var(--color-ink-mute);
      font-weight: 600;
      font-size: 10.5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .data-table td {
      padding: 6px 10px;
      border-bottom: 1px solid var(--color-hairline);
      color: var(--color-ink);
    }
    .data-table td.num {
      text-align: right;
      font-feature-settings: 'tnum';
    }
    .data-table th.num {
      text-align: right;
    }
    
    /* Progress Bar in Tables */
    .progress-bar-bg {
      background: var(--color-hairline);
      height: 6px;
      border-radius: 999px;
      overflow: hidden;
      width: 100%;
      margin-top: 4px;
    }
    .progress-bar-fill {
      height: 100%;
      border-radius: 999px;
    }
    
    /* Grids */
    .grid-2col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
    }
    
    .chart-box {
      border: 1px solid var(--color-hairline);
      border-radius: 8px;
      padding: 1rem;
      background: #ffffff;
      display: flex;
      flex-direction: column;
    }
    .chart-box-title {
      font-size: 11.5px;
      font-weight: 600;
      color: var(--color-ink);
      margin-bottom: 8px;
    }
    .chart-canvas-wrap {
      position: relative;
      width: 100%;
      height: 180px;
    }
    
    /* Signatory Block */
    .signatory-block {
      margin-top: 2.5rem;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
      page-break-inside: avoid;
    }
    .sign-line {
      border-top: 1px solid var(--color-ink);
      padding-top: 6px;
      font-size: 11.5px;
      text-align: center;
    }
    .sign-name { font-weight: 600; color: var(--color-ink); }
    .sign-role { font-size: 10.5px; color: var(--color-ink-mute); }

    /* Print Overrides */
    @media print {
      body {
        background: #ffffff;
        padding: 0;
        font-size: 11px;
      }
      .print-container {
        border: none;
        box-shadow: none;
        padding: 0;
        max-width: 100%;
      }
      .action-bar { display: none !important; }
      .report-section { page-break-inside: avoid; }
      .chart-canvas-wrap { height: 160px; }
      @page {
        size: A4 portrait;
        margin: 12mm 15mm;
      }
    }
  </style>
</head>
<body>

<div class="print-container">

  <!-- Action Bar (Hidden on Print) -->
  <div class="action-bar">
    <div>
      <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to Dashboard
      </a>
    </div>
    <div style="display:flex;gap:.5rem;">
      <button onclick="window.print()" class="btn btn-primary">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Print / Save as PDF
      </button>
    </div>
  </div>

  <!-- Institutional Header -->
  <div class="header-block">
    <div class="inst-title">Republic of the Philippines &bull; Southern Luzon State University</div>
    <div class="doc-title">Executive Institutional Property &amp; Circulation Report</div>
    <div class="doc-subtitle">Alabat Campus Inventory Governance, Circulation Velocity &amp; Accountability Ledger</div>
  </div>

  <!-- Meta Info -->
  <div class="meta-grid">
    <div>
      <div class="meta-item-label">Reporting Period</div>
      <div class="meta-item-value"><?= htmlspecialchars($meta['timeframe_label'] ?? 'Current', ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
      <div class="meta-item-label">Generated On</div>
      <div class="meta-item-value"><?= htmlspecialchars($meta['generated_at'] ?? date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
      <div class="meta-item-label">Target Scope</div>
      <div class="meta-item-value"><?= htmlspecialchars(!empty($_GET['category']) ? $_GET['category'] : 'All Categories', ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
      <div class="meta-item-label">Campus Location</div>
      <div class="meta-item-value"><?= htmlspecialchars(!empty($_GET['location']) ? $_GET['location'] : 'All Locations', ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>

  <!-- ── MODULE 1: EXECUTIVE KPI RIBBON ──────────────────────── -->
  <?php if (!empty($activeSections['kpi'])): ?>
  <div class="report-section">
    <div class="section-title">
      <span>1. Institutional KPI &amp; Operational Overview</span>
      <span style="font-size:10px;font-weight:400;color:var(--color-ink-mute);">Campus-wide asset state</span>
    </div>
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">Total Properties</div>
        <div class="kpi-value"><?= number_format((int)($kpi['total_assets'] ?? 0)) ?></div>
        <div class="kpi-sub"><?= $kpi['serviceability_rate'] ?? 0 ?>% Serviceable</div>
      </div>
      <div class="kpi-card kpi-success">
        <div class="kpi-label">Operational Availability</div>
        <div class="kpi-value"><?= $kpi['availability_rate'] ?? 0 ?>%</div>
        <div class="kpi-sub"><?= number_format((int)($kpi['available_assets'] ?? 0)) ?> Units in Stock</div>
      </div>
      <div class="kpi-card kpi-warning">
        <div class="kpi-label">Active Borrows</div>
        <div class="kpi-value"><?= number_format((int)($kpi['borrowed_assets'] ?? 0)) ?></div>
        <div class="kpi-sub"><?= number_format((int)($kpi['active_borrowers'] ?? 0)) ?> Borrowers</div>
      </div>
      <div class="kpi-card <?= ((int)($kpi['overdue_count'] ?? 0) > 0 ? 'kpi-danger' : 'kpi-success') ?>">
        <div class="kpi-label">Delinquent / Overdue</div>
        <div class="kpi-value"><?= number_format((int)($kpi['overdue_count'] ?? 0)) ?></div>
        <div class="kpi-sub"><?= ((int)($kpi['overdue_count'] ?? 0) === 0 ? 'Zero Overdue Borrows' : 'Action Required') ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── MODULE 2: ASSET STATUS & CONDITION SPECTRUM ─────────── -->
  <?php if (!empty($activeSections['status']) || !empty($activeSections['condition'])): ?>
  <div class="report-section">
    <div class="section-title">
      <span>2. Asset Inventory Dynamics &amp; Condition Spectrum</span>
      <span style="font-size:10px;font-weight:400;color:var(--color-ink-mute);">Lifecycle and condition breakdown</span>
    </div>
    
    <div class="grid-2col">
      <?php if (!empty($activeSections['status'])): ?>
      <div class="chart-box">
        <div class="chart-box-title">Operational Status Distribution</div>
        <div class="chart-canvas-wrap">
          <canvas id="rpt-status-chart"></canvas>
        </div>
        <table class="data-table" style="margin-top:10px;">
          <tbody>
            <tr>
              <td>Available in Stock</td>
              <td class="num"><?= number_format((int)($kpi['available_assets'] ?? 0)) ?></td>
              <td class="num"><?= $kpi['availability_rate'] ?? 0 ?>%</td>
            </tr>
            <tr>
              <td>Currently Borrowed</td>
              <td class="num"><?= number_format((int)($kpi['borrowed_assets'] ?? 0)) ?></td>
              <td class="num"><?= ((int)($kpi['total_assets'] ?? 0) > 0 ? round(((int)$kpi['borrowed_assets'] / (int)$kpi['total_assets']) * 100, 1) : 0) ?>%</td>
            </tr>
            <tr>
              <td>Under Maintenance</td>
              <td class="num"><?= number_format((int)($kpi['maintenance_assets'] ?? 0)) ?></td>
              <td class="num"><?= ((int)($kpi['total_assets'] ?? 0) > 0 ? round(((int)$kpi['maintenance_assets'] / (int)$kpi['total_assets']) * 100, 1) : 0) ?>%</td>
            </tr>
            <tr>
              <td>For Disposal / Condemned</td>
              <td class="num"><?= number_format((int)($kpi['for_disposal_assets'] ?? 0)) ?></td>
              <td class="num"><?= ((int)($kpi['total_assets'] ?? 0) > 0 ? round(((int)$kpi['for_disposal_assets'] / (int)$kpi['total_assets']) * 100, 1) : 0) ?>%</td>
            </tr>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <?php if (!empty($activeSections['condition'])): ?>
      <div class="chart-box">
        <div class="chart-box-title">Physical Condition Spectrum</div>
        <div class="chart-canvas-wrap">
          <canvas id="rpt-condition-chart"></canvas>
        </div>
        <?php
          $condLabels = $assetDyn['condition_distribution']['labels'] ?? ['Mint', 'Good', 'Fair', 'Damaged', 'Not Working', 'For Disposal'];
          $condData   = $assetDyn['condition_distribution']['data'] ?? [0,0,0,0,0,0];
          $totalCond  = array_sum($condData);
        ?>
        <table class="data-table" style="margin-top:10px;">
          <tbody>
            <?php foreach ($condLabels as $cIdx => $cLbl): 
              $cVal = $condData[$cIdx] ?? 0;
              $cPct = $totalCond > 0 ? round(($cVal / $totalCond) * 100, 1) : 0;
            ?>
            <tr>
              <td><?= htmlspecialchars((string)$cLbl, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="num"><?= number_format($cVal) ?></td>
              <td class="num"><?= $cPct ?>%</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── MODULE 3: CIRCULATION VELOCITY TIMELINE ─────────────── -->
  <?php if (!empty($activeSections['velocity'])): ?>
  <div class="report-section">
    <div class="section-title">
      <span>3. Circulation Velocity &amp; Lending Movement</span>
      <span style="font-size:10px;font-weight:400;color:var(--color-ink-mute);">Time-series borrow checkouts vs. completed returns</span>
    </div>
    <div class="chart-box" style="margin-bottom:1rem;">
      <div class="chart-box-title">Transaction Movement Timeline</div>
      <div class="chart-canvas-wrap" style="height:190px;">
        <canvas id="rpt-velocity-chart"></canvas>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:11px;color:var(--color-ink-mute);">
        <span>Total Period Borrows: <strong style="color:var(--color-primary);"><?= number_format((int)($kpi['period_loans'] ?? 0)) ?></strong></span>
        <span>Total Period Returns: <strong style="color:var(--color-success);"><?= number_format((int)($kpi['period_returns'] ?? 0)) ?></strong></span>
        <span>Net Flow Ratio: <strong><?= ((int)($kpi['period_loans'] ?? 0) > 0 ? round(((int)$kpi['period_returns'] / (int)$kpi['period_loans']) * 100, 1) : 0) ?>% returned</strong></span>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── MODULE 4: TOP CATEGORIES & LOCATIONS ────────────────── -->
  <?php if (!empty($activeSections['categories']) || !empty($activeSections['locations'])): ?>
  <div class="report-section">
    <div class="section-title">
      <span>4. Categorical Classifications &amp; Department Allocation</span>
      <span style="font-size:10px;font-weight:400;color:var(--color-ink-mute);">Distribution matrix across equipment categories and campus units</span>
    </div>
    
    <div class="grid-2col">
      <?php if (!empty($activeSections['categories'])): ?>
      <div>
        <div class="chart-box-title">Top Asset Classifications</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Category</th>
              <th class="num">Units</th>
              <th class="num">Share</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $catLabels = $assetDyn['top_categories']['labels'] ?? [];
            $catData = $assetDyn['top_categories']['data'] ?? [];
            $totalCat = array_sum($catData);
            if (!empty($catLabels)):
              foreach ($catLabels as $idx => $lbl):
                $cnt = $catData[$idx] ?? 0;
                $pct = $totalCat > 0 ? round(($cnt / $totalCat) * 100, 1) : 0;
            ?>
              <tr>
                <td>
                  <div><?= htmlspecialchars((string)$lbl, ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?= $pct ?>%;background:#059669;"></div></div>
                </td>
                <td class="num"><?= number_format($cnt) ?></td>
                <td class="num"><?= $pct ?>%</td>
              </tr>
            <?php
              endforeach;
            else:
            ?>
              <tr><td colspan="3" style="text-align:center;color:var(--color-ink-mute);">No categories recorded</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <?php if (!empty($activeSections['locations'])): ?>
      <div>
        <div class="chart-box-title">Campus Location Density</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Department / Room</th>
              <th class="num">Units</th>
              <th class="num">Share</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $locLabels = $assetDyn['top_locations']['labels'] ?? [];
            $locData = $assetDyn['top_locations']['data'] ?? [];
            $totalLoc = array_sum($locData);
            if (!empty($locLabels)):
              foreach ($locLabels as $idx => $lbl):
                $cnt = $locData[$idx] ?? 0;
                $pct = $totalLoc > 0 ? round(($cnt / $totalLoc) * 100, 1) : 0;
            ?>
              <tr>
                <td>
                  <div><?= htmlspecialchars((string)$lbl, ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?= $pct ?>%;background:#166534;"></div></div>
                </td>
                <td class="num"><?= number_format($cnt) ?></td>
                <td class="num"><?= $pct ?>%</td>
              </tr>
            <?php
              endforeach;
            else:
            ?>
              <tr><td colspan="3" style="text-align:center;color:var(--color-ink-mute);">No location data recorded</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── MODULE 5: WEEKLY DEMAND HEAT CURVE ──────────────────── -->
  <?php if (!empty($activeSections['dow'])): ?>
  <div class="report-section">
    <div class="section-title">
      <span>5. Weekly Demand Heat Curve</span>
      <span style="font-size:10px;font-weight:400;color:var(--color-ink-mute);">Borrowing density by day of the week</span>
    </div>
    <div class="chart-box">
      <div class="chart-canvas-wrap" style="height:140px;">
        <canvas id="rpt-dow-chart"></canvas>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── MODULE 6: INCIDENTS & DAMAGE ACCOUNTABILITY ─────────── -->
  <?php if (!empty($activeSections['incidents'])): ?>
  <div class="report-section">
    <div class="section-title">
      <span>6. Incident Accountability &amp; Financial Liability</span>
      <span style="font-size:10px;font-weight:400;color:var(--color-ink-mute);">Damage records, settlement methods, and recovery balance</span>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>Liability &amp; Accountability Metric</th>
          <th class="num">Value</th>
          <th>Administrative Context</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Open Damage Holds</td>
          <td class="num" style="color:var(--color-ruby);font-weight:600;"><?= number_format((int)($kpi['open_damage_holds'] ?? 0)) ?></td>
          <td style="color:var(--color-ink-mute);">Students/borrowers with active registration holds pending clearance</td>
        </tr>
        <tr>
          <td>Cleared Incident Settlements</td>
          <td class="num" style="color:var(--color-success);font-weight:600;"><?= number_format((int)($kpi['cleared_damage_holds'] ?? 0)) ?></td>
          <td style="color:var(--color-ink-mute);">Resolved via repair reimbursement, in-kind replacement, or administrative waiver</td>
        </tr>
        <tr>
          <td>Total Recorded Damage Liability</td>
          <td class="num" style="font-weight:600;">PHP <?= number_format((float)($kpi['total_damage_cost'] ?? 0), 2) ?></td>
          <td style="color:var(--color-ink-mute);">Estimated institutional assessment value for damaged properties</td>
        </tr>
        <tr>
          <td>Cleared Settlement Value</td>
          <td class="num" style="color:var(--color-success);font-weight:600;">PHP <?= number_format((float)($kpi['total_cleared_cost'] ?? 0), 2) ?></td>
          <td style="color:var(--color-ink-mute);">Recovered in-kind equipment or reimbursed repair capital</td>
        </tr>
        <tr>
          <td>Average Resolution Velocity</td>
          <td class="num" style="font-weight:600;"><?= $kpi['avg_resolution_days'] ?? 0 ?> Days</td>
          <td style="color:var(--color-ink-mute);">Average duration from incident reporting to issuance of clearance certificate</td>
        </tr>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- ── MODULE 7: INSTITUTIONAL SIGNATORY BLOCK ─────────────── -->
  <?php if (!empty($activeSections['signatories'])): ?>
  <div class="signatory-block">
    <div>
      <div class="sign-line">
        <div class="sign-name">Supply Office &amp; Custodian</div>
        <div class="sign-role">Certified Data Source &bull; SLSU-Alabat</div>
      </div>
    </div>
    <div>
      <div class="sign-line">
        <div class="sign-name">Internal Audit / Inventory Officer</div>
        <div class="sign-role">Verification &bull; SLSU-Alabat</div>
      </div>
    </div>
    <div>
      <div class="sign-line">
        <div class="sign-name">Campus Director / Executive Office</div>
        <div class="sign-role">Institutional Oversight &bull; SLSU-Alabat</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- ── High-DPI Print Chart Initialization ──────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const fontConfig = { family: "'Inter', sans-serif", size: 10 };
  
  // 1. Status Doughnut Chart
  <?php if (!empty($activeSections['status'])): ?>
  const statusEl = document.getElementById('rpt-status-chart');
  if (statusEl && typeof Chart !== 'undefined') {
    new Chart(statusEl, {
      type: 'doughnut',
      data: {
        labels: ['Available', 'Borrowed', 'Maintenance', 'For Disposal'],
        datasets: [{
          data: [
            <?= (int)($kpi['available_assets'] ?? 0) ?>,
            <?= (int)($kpi['borrowed_assets'] ?? 0) ?>,
            <?= (int)($kpi['maintenance_assets'] ?? 0) ?>,
            <?= (int)($kpi['for_disposal_assets'] ?? 0) ?>
          ],
          backgroundColor: ['#059669', '#166534', '#d97706', '#ea2261'],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          legend: {
            position: 'right',
            labels: { font: fontConfig, boxWidth: 8, usePointStyle: true }
          }
        }
      }
    });
  }
  <?php endif; ?>

  // 2. Condition Solid Bar Chart
  <?php if (!empty($activeSections['condition'])): ?>
  const condEl = document.getElementById('rpt-condition-chart');
  if (condEl && typeof Chart !== 'undefined') {
    new Chart(condEl, {
      type: 'bar',
      data: {
        labels: <?= json_encode($condLabels ?? []) ?>,
        datasets: [{
          data: <?= json_encode($condData ?? []) ?>,
          backgroundColor: ['#059669', '#10b981', '#d97706', '#ea2261', '#dc2626', '#64748d'],
          borderRadius: 4,
          maxBarThickness: 24,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: fontConfig } },
          y: { grid: { color: '#f1f5f9' }, ticks: { font: fontConfig, precision: 0 }, beginAtZero: true }
        }
      }
    });
  }
  <?php endif; ?>

  // 3. Velocity Line Area Chart
  <?php if (!empty($activeSections['velocity'])): ?>
  const vEl = document.getElementById('rpt-velocity-chart');
  if (vEl && typeof Chart !== 'undefined') {
    const vSeries = <?= json_encode($circ['velocity_series'] ?? []) ?>;
    new Chart(vEl, {
      type: 'line',
      data: {
        labels: vSeries.labels || [],
        datasets: [
          {
            label: 'Borrow Checkouts',
            data: vSeries.borrows || [],
            borderColor: '#166534',
            backgroundColor: 'rgba(22, 101, 52, 0.08)',
            fill: true,
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 2,
          },
          {
            label: 'Returns',
            data: vSeries.returns || [],
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.08)',
            fill: true,
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 2,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            labels: { font: fontConfig, boxWidth: 8, usePointStyle: true }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { font: fontConfig } },
          y: { grid: { color: '#f1f5f9' }, ticks: { font: fontConfig, precision: 0 }, beginAtZero: true }
        }
      }
    });
  }
  <?php endif; ?>

  // 4. Day of Week Demand Bar Chart
  <?php if (!empty($activeSections['dow'])): ?>
  const dowEl = document.getElementById('rpt-dow-chart');
  if (dowEl && typeof Chart !== 'undefined') {
    const dowData = <?= json_encode($circ['dow_demand'] ?? []) ?>;
    new Chart(dowEl, {
      type: 'bar',
      data: {
        labels: dowData.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
          data: dowData.data || [],
          backgroundColor: '#8b5cf6',
          borderRadius: 4,
          maxBarThickness: 32,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: fontConfig } },
          y: { grid: { color: '#f1f5f9' }, ticks: { font: fontConfig, precision: 0 }, beginAtZero: true }
        }
      }
    });
  }
  <?php endif; ?>
});
</script>

</body>
</html>
