<?php
/**
 * Custodian Dashboard + Scan & Transact Station View
 * 100% database-backed with live catalog metrics, approval queues, transactions, and 7-day velocity chart.
 */
$title      = 'Custodian Dashboard';
$breadcrumb = 'Custodian Dashboard';

$liveItemStats        = $liveItemStats ?? ['total' => 0, 'healthy' => 0, 'maintenance' => 0, 'disposal' => 0, 'borrowed' => 0];
$pendingRequestsCount = (int)($pendingRequestsCount ?? 0);
$recentTransactions   = $recentTransactions ?? [];
$overdueCount         = (int)($overdueCount ?? 0);
$borrowingActivity    = $borrowingActivity ?? ['labels' => [], 'loans' => [], 'returns' => []];

require __DIR__ . '/../layouts/shell.php';
?>

<!-- ── Stat Cards ─────────────────────────────────────────── -->
<div class="section-header">
  <div>
    <h1 class="section-title">Operations Center</h1>
    <div class="section-subtitle">Property Custodian &amp; Supply Office</div>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="<?= url('/inventory') ?>" class="btn btn-primary btn-sm">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
      Register New Asset
    </a>
    <a href="<?= url('/scan') ?>" class="btn btn-ghost btn-sm">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18M15 3v18"></path></svg>
      Open Scan Station
    </a>
  </div>
</div>

<div class="grid-kpi-stats-6 stagger-children" style="margin-bottom:var(--space-xl);">
  <?php
  $custStats = [
    ['title' => 'Total Assets',          'value' => number_format((int)($liveItemStats['total'] ?? 0)),       'icon' => 'package',        'color' => 'indigo', 'trend' => 'Catalog SSOT',     'trend_dir' => 'up',   'link' => url('/inventory')],
    ['title' => 'Ready & Available',     'value' => number_format((int)($liveItemStats['healthy'] ?? 0)),     'icon' => 'clipboard-list', 'color' => 'emerald','trend' => 'Mint & Good',      'trend_dir' => 'up',   'link' => url('/inventory')],
    ['title' => 'Currently Borrowed',    'value' => number_format((int)($liveItemStats['borrowed'] ?? 0)),    'icon' => 'layers',         'color' => 'indigo', 'trend' => 'Active Borrows',   'trend_dir' => 'up',   'link' => url('/inventory?status=borrowed')],
    ['title' => 'Pending Borrow Requests', 'value' => number_format($pendingRequestsCount),                    'icon' => 'inbox',          'color' => ($pendingRequestsCount > 0 ? 'ruby' : 'indigo'), 'trend' => ($pendingRequestsCount > 0 ? 'Awaiting Approval' : 'Queue Clear'), 'trend_dir' => ($pendingRequestsCount > 0 ? 'up' : 'down'), 'link' => url('/scan')],
    ['title' => 'Overdue Items',         'value' => number_format($overdueCount),                              'icon' => 'alert-triangle', 'color' => ($overdueCount > 0 ? 'ruby' : 'emerald'), 'trend' => ($overdueCount > 0 ? 'Action Required' : 'All Clear'), 'trend_dir' => ($overdueCount > 0 ? 'down' : 'up'), 'link' => url('/scan')],
    ['title' => 'Under Maintenance',     'value' => number_format((int)($liveItemStats['maintenance'] ?? 0)), 'icon' => 'tool',           'color' => 'amber',  'trend' => 'Defect/Repair',     'trend_dir' => 'down', 'link' => url('/inventory?status=maintenance')],
  ];
  foreach ($custStats as $stat):
    $props = $stat;
    require __DIR__ . '/../components/stat-card.php';
  endforeach;
  ?>
</div>

<!-- ── Activity Stream & Counter Control Row ──────────────── -->
<div class="grid-2col-responsive" style="margin-bottom:var(--space-xl);">

  <!-- 7-Day / Multi-Period Borrowing & Return Velocity Chart -->
  <div class="chart-container animate-fade-in-up" style="animation-delay:.15s;">
    <div class="chart-header">
      <div>
        <div class="chart-title">Supply Activity</div>
        <div class="chart-subtitle">Equipment borrows vs. returned items velocity</div>
      </div>
      <div class="segmented-group segmented-group-sm" id="custodian-activity-tf">
        <button type="button" class="segmented-btn" data-slice="3">3M</button>
        <button type="button" class="segmented-btn active" data-slice="6">6M</button>
        <button type="button" class="segmented-btn" data-slice="all">All</button>
      </div>
    </div>
    <div class="scroll-hint-bar">
      <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
      Swipe horizontally to explore
    </div>
    <div class="chart-scroll-container">
      <div class="chart-scroll-inner">
        <?php if (empty($borrowingActivity['labels'])): ?>
          <div style="display:flex;align-items:center;justify-content:center;height:100%;min-height:220px;color:var(--color-ink-mute);font-size:13px;flex-direction:column;gap:.5rem;">
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:.4;"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            No transaction activity logged in the past 7 days
          </div>
        <?php else: ?>
          <canvas id="custodian-activity-chart"></canvas>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Operational Quick Station / Priority Action Card -->
  <div class="card animate-fade-in-up" style="animation-delay:.2s;padding:var(--space-xl);display:flex;flex-direction:column;justify-content:space-between;">
    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-md);">
        <div>
          <div style="font-size:14px;font-weight:400;color:var(--color-ink);">Supply Office Counter Control</div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Fast-lane actions for campus property management</div>
        </div>
        <span class="badge <?= $pendingRequestsCount > 0 ? 'badge-pending' : 'badge-approved' ?>">
          <?= $pendingRequestsCount > 0 ? $pendingRequestsCount . ' Pending' : 'Counter Clear' ?>
        </span>
      </div>

      <div style="display:flex;flex-direction:column;gap:.75rem;margin-top:var(--space-lg);">
        <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-md);">
          <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:rgba(22, 101, 52,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18M15 3v18"></path></svg>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:500;color:var(--color-ink);">Barcode Scan Station</div>
            <div style="font-size:11px;color:var(--color-ink-mute);">Process borrows, verify barcodes, inspect returned items</div>
          </div>
          <a href="<?= url('/scan') ?>" class="btn btn-primary btn-sm" style="font-size:11px;padding:4px 12px;text-decoration:none;">Open</a>
        </div>

        <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-md);">
          <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:rgba(5,150,105,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:500;color:var(--color-ink);">Asset Registry &amp; Intake</div>
            <div style="font-size:11px;color:var(--color-ink-mute);">Single/batch registration, CSV import, condition updates</div>
          </div>
          <a href="<?= url('/inventory') ?>" class="btn btn-ghost btn-sm" style="font-size:11px;padding:4px 12px;text-decoration:none;">Catalog</a>
        </div>
      </div>
    </div>

    <div style="margin-top:var(--space-lg);padding-top:var(--space-md);border-top:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;">
      <span style="font-size:11px;color:var(--color-ink-mute);"><span style="font-weight:500;color:var(--color-ink);"><?= count($recentTransactions) ?></span> recent transactions</span>
      <a href="<?= url('/scan') ?>" class="btn btn-ghost btn-sm" style="font-size:11px;padding:4px 8px;text-decoration:none;">Inspection Station &rarr;</a>
    </div>
  </div>

</div>

<!-- ── Recent Transactions Table ─────────────────────────── -->
<div class="section-header">
  <div>
    <div class="section-title">Recent Transactions</div>
    <div class="section-subtitle">Live ledger of borrows, checkouts, and returns</div>
  </div>
</div>

<?php
$tableRowsHtml = '';
if (!empty($recentTransactions)) {
    foreach ($recentTransactions as $tx) {
        $time = !empty($tx['transaction_date']) ? date('M j, h:i A', strtotime($tx['transaction_date'])) : '—';
        $isReturned = !empty($tx['actual_return_date']);
        $typeTag = $isReturned 
            ? '<span class="pill-tag" style="background:rgba(5,150,105,.1);color:var(--color-success);">Returned</span>'
            : '<span class="pill-tag" style="background:rgba(22, 101, 52,.1);color:var(--color-primary);">Checkout</span>';
        $borrower = htmlspecialchars($tx['borrower_name'] ?? 'Borrower', ENT_QUOTES, 'UTF-8');
        $asset = htmlspecialchars($tx['item_name'] ?? 'Item', ENT_QUOTES, 'UTF-8') . ' <span style="font-size:11px;color:var(--color-ink-mute);font-family:monospace;">(' . htmlspecialchars($tx['barcode'] ?? '', ENT_QUOTES, 'UTF-8') . ')</span>';
        
        if ($isReturned) {
            $dueReturn = date('M j, h:i A', strtotime($tx['actual_return_date']));
            $statusBadge = ($tx['returned_condition'] === 'damaged')
                ? '<span class="badge badge-maintenance">Damaged</span>'
                : '<span class="badge badge-approved">Returned</span>';
        } else {
            $dueReturn = !empty($tx['expected_return_date']) ? date('M j, h:i A', strtotime($tx['expected_return_date'])) : '—';
            $isOverdue = !empty($tx['expected_return_date']) && strtotime($tx['expected_return_date']) < time();
            $statusBadge = $isOverdue 
                ? '<span class="badge badge-maintenance">Overdue</span>'
                : '<span class="badge badge-borrowed">Active Borrow</span>';
        }

        $tableRowsHtml .= '
        <tr>
          <td class="num" style="color:var(--color-ink-mute);font-size:12px;">' . $time . '</td>
          <td>' . $typeTag . '</td>
          <td style="font-weight:400;color:var(--color-ink);">' . $borrower . '</td>
          <td>' . $asset . '</td>
          <td class="num" style="font-size:12px;">' . $dueReturn . '</td>
          <td>' . $statusBadge . '</td>
        </tr>';
    }
} else {
    $tableRowsHtml = '<tr><td colspan="6" style="text-align:center;padding:2.5rem 1rem;color:var(--color-ink-mute);">No transaction records found in the database.</td></tr>';
}

$props = [
  'id'                => 'custodian-transactions-table',
  'searchPlaceholder' => 'Search transactions...',
  'total'             => count($recentTransactions),
  'currentPage'       => 1,
  'columns'           => [
    ['key' => 'time',     'label' => 'Time'],
    ['key' => 'type',     'label' => 'Type'],
    ['key' => 'borrower', 'label' => 'Borrower'],
    ['key' => 'asset',    'label' => 'Asset'],
    ['key' => 'due',      'label' => 'Due / Returned'],
    ['key' => 'status',   'label' => 'Status'],
  ],
  'bodyContent' => $tableRowsHtml,
];
require __DIR__ . '/../components/data-table.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const monthLabels = <?= json_encode($borrowingActivity['labels'] ?? []) ?>;
  const loanData    = <?= json_encode($borrowingActivity['loans'] ?? []) ?>;
  const returnData  = <?= json_encode($borrowingActivity['returns'] ?? []) ?>;

  if (typeof ChartFactory !== 'undefined' && monthLabels.length > 0) {
    function renderCustodianChart(labels, loans, returns) {
      ChartFactory.createBar('custodian-activity-chart', {
        labels: labels,
        datasets: [
          {
            label: 'Borrows',
            data: loans,
            backgroundColor: '#166534',
            hoverBackgroundColor: '#14532d',
            borderRadius: 6,
            maxBarThickness: 32,
          },
          {
            label: 'Returns',
            data: returns,
            backgroundColor: '#059669',
            hoverBackgroundColor: '#047857',
            borderRadius: 6,
            maxBarThickness: 32,
          }
        ]
      });
    }

    // Initial Solid Render
    renderCustodianChart(monthLabels, loanData, returnData);

    // Timeframe toggle event listeners
    const tfBtns = document.querySelectorAll('#custodian-activity-tf .segmented-btn');
    tfBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        tfBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const sliceVal = this.getAttribute('data-slice');

        if (sliceVal === '3') {
          renderCustodianChart(monthLabels.slice(-3), loanData.slice(-3), returnData.slice(-3));
        } else if (sliceVal === '6') {
          renderCustodianChart(monthLabels.slice(-6), loanData.slice(-6), returnData.slice(-6));
        } else {
          renderCustodianChart(monthLabels, loanData, returnData);
        }
      });
    });
  }

  // Realtime live integration for Custodian Operations
  if (window.RealtimeHub) {
    window.RealtimeHub.on('LOAN_APPROVED', function(event) {
      if (typeof ChartFactory !== 'undefined') {
        ChartFactory.updateDataset('custodian-activity-chart', function(chart) {
          if (chart.data.datasets && chart.data.datasets[0] && chart.data.datasets[0].data.length > 0) {
            const lastIdx = chart.data.datasets[0].data.length - 1;
            chart.data.datasets[0].data[lastIdx] = (chart.data.datasets[0].data[lastIdx] || 0) + 1;
          }
        });
      }
    });

    window.RealtimeHub.on('LOAN_RETURNED', function(event) {
      if (typeof ChartFactory !== 'undefined') {
        ChartFactory.updateDataset('custodian-activity-chart', function(chart) {
          if (chart.data.datasets && chart.data.datasets[1] && chart.data.datasets[1].data.length > 0) {
            const lastIdx = chart.data.datasets[1].data.length - 1;
            chart.data.datasets[1].data[lastIdx] = (chart.data.datasets[1].data[lastIdx] || 0) + 1;
          }
        });
      }
    });
  }
});
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
