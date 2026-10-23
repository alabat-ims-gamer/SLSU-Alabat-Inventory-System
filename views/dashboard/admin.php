<?php
/**
 * Admin Dashboard View
 * Completely database-backed with live statistics, charts, queues, and tables.
 */
$title      = 'Overview Dashboard';
$breadcrumb = 'Overview Dashboard';

$allUsers          = $allUsers ?? [];
$pendingUsersCount = (int)($pendingUsersCount ?? 0);
$activeUsersCount  = (int)($activeUsersCount ?? 0);
$overdueCount      = (int)($overdueCount ?? 0);
$liveItemStats     = $liveItemStats ?? ['total' => 0, 'healthy' => 0, 'maintenance' => 0, 'disposal' => 0, 'borrowed' => 0];

$stats = [
  ['title' => 'Total Campus Assets', 'value' => number_format((int)($liveItemStats['total'] ?? 0)),       'icon' => 'package',        'color' => 'indigo', 'trend' => 'Catalog Count', 'trend_dir' => 'up', 'link' => null],
  ['title' => 'Active Users',        'value' => number_format($activeUsersCount),                         'icon' => 'users',          'color' => 'emerald','trend' => 'Approved',      'trend_dir' => 'up', 'link' => url('/admin/users')],
  ['title' => 'Pending Approvals',   'value' => number_format($pendingUsersCount),                       'icon' => 'clock',          'color' => 'amber',  'trend' => ($pendingUsersCount > 0 ? 'Action required' : 'All clear'), 'trend_dir' => ($pendingUsersCount > 0 ? 'down' : 'up'), 'link' => url('/admin/users')],
  ['title' => 'Overdue Items',       'value' => number_format($overdueCount),                             'icon' => 'alert-triangle', 'color' => ($overdueCount > 0 ? 'ruby' : 'emerald'),  'trend' => ($overdueCount > 0 ? 'Action required' : 'All on time'), 'trend_dir' => ($overdueCount > 0 ? 'down' : 'up'), 'link' => url('/admin/audit')],
];

$pendingUsers    = $pendingUsers ?? [];
$recentAuditLogs = $recentAuditLogs ?? [];
$recentAssets    = $recentAssets ?? [];
$categoryDist    = $categoryDist ?? ['labels' => [], 'data' => []];
$monthlyActivity = $monthlyActivity ?? ['labels' => [], 'loans' => [], 'returns' => []];

require __DIR__ . '/../layouts/shell.php';
?>

<!-- ── Stat Grid ─────────────────────────────────────────── -->
<div class="section-header">
  <div>
    <h1 class="section-title">System Overview</h1>
    <div class="section-subtitle">Real-time metrics for SLSU-Alabat campus accounts, security, and properties</div>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="<?= url('/admin/users') ?>" class="btn btn-primary btn-sm">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      Manage Users
    </a>
    <a href="<?= url('/admin/audit') ?>" class="btn btn-ghost btn-sm">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
      Security Audit
    </a>
    <button class="btn btn-ghost btn-sm" onclick="window.location.reload()">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 102.13-9.36L1 10"></path></svg>
      Refresh
    </button>
  </div>
</div>

<!-- Stats (Responsive Fluid Grid) -->
<div class="grid-kpi-stats stagger-children">
  <?php foreach ($stats as $stat): ?>
    <?php
      $props = $stat;
      require __DIR__ . '/../components/stat-card.php';
    ?>
  <?php endforeach; ?>
</div>

<!-- ── Charts + Pending Approvals Row ────────────────────── -->
<div class="grid-dashboard-split">

  <!-- Asset Distribution Chart -->
  <div class="chart-container animate-fade-in-up" style="animation-delay:.1s;">
    <div class="chart-header">
      <div>
        <div class="chart-title">Asset Distribution</div>
        <div class="chart-subtitle">By category cataloged</div>
      </div>
    </div>
    <div class="chart-scalable-container">
      <?php if (empty($categoryDist['labels'])): ?>
        <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--color-ink-mute);font-size:13px;flex-direction:column;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:.4;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
          No asset categories registered yet
        </div>
      <?php else: ?>
        <canvas id="asset-distribution-chart"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pending Approvals Queue -->
  <div class="card animate-fade-in-up" style="animation-delay:.15s;padding:var(--space-xl);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-lg);">
      <div>
        <div style="font-size:14px;font-weight:400;color:var(--color-ink);">Pending Approvals</div>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Self-service registrations</div>
      </div>
      <span id="admin-pending-badge" class="badge badge-pending" style="font-size:10px;"><?= $pendingUsersCount ?> new</span>
    </div>

    <!-- Real pending users -->
    <div style="display:flex;flex-direction:column;gap:.5rem;max-height:280px;overflow-y:auto;">
      <?php if (empty($pendingUsers)): ?>
        <div style="padding:2.5rem 1rem;text-align:center;color:var(--color-ink-mute);background:var(--color-canvas-soft);border-radius:var(--radius-md);border:1px dashed var(--color-hairline);">
          <div style="font-size:13px;font-weight:500;color:var(--color-ink);">No Pending Approvals</div>
          <div style="font-size:12px;margin-top:2px;">All user registrations have been reviewed.</div>
        </div>
      <?php else: ?>
        <?php foreach ($pendingUsers as $u):
          $fullName = $u['full_name'] ?? 'User';
          $parts = explode(' ', trim($fullName));
          $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[count($parts)-1] ?? '', 0, 1));
          $timeAgo = !empty($u['created_at']) ? date('M j, h:i A', strtotime($u['created_at'])) : 'Recently';
        ?>
        <div data-user-id="<?= (int)($u['user_id'] ?? 0) ?>" style="display:flex;align-items:center;gap:.75rem;padding:.5rem .625rem;border-radius:var(--radius-md);background:var(--color-canvas-soft);transition:background var(--transition-fast);">
          <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary) 0%,var(--color-ruby) 100%);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:500;color:white;flex-shrink:0;"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:400;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div>
            <div style="font-size:11px;color:var(--color-ink-mute);"><?= htmlspecialchars($u['department_program'] ?? 'General', ENT_QUOTES, 'UTF-8') ?> &bull; <span style="font-feature-settings:'tnum';"><?= htmlspecialchars($timeAgo, ENT_QUOTES, 'UTF-8') ?></span></div>
          </div>
          <a href="<?= url('/admin/users') ?>" class="btn btn-primary btn-icon-sm" style="background:var(--color-success);border-radius:var(--radius-sm);text-decoration:none;" title="Review in User Center">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline-block;"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <a href="<?= url('/admin/users') ?>" class="btn btn-secondary w-full btn-sm" style="margin-top:var(--space-md);justify-content:center;text-decoration:none;">
      Open User Management
    </a>
  </div>

</div>

<!-- ── Monthly Activity + Audit Stream ───────────────────── -->
<div class="grid-2col-responsive">

  <!-- Monthly Borrowing Chart -->
  <div class="chart-container animate-fade-in-up" style="animation-delay:.2s;">
    <div class="chart-header">
      <div>
        <div class="chart-title">Borrowing Activity</div>
        <div class="chart-subtitle">Monthly borrow &amp; return frequency</div>
      </div>
      <div class="segmented-group segmented-group-sm" id="admin-borrowing-tf">
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
        <canvas id="borrowing-activity-chart"></canvas>
      </div>
    </div>
  </div>

  <!-- Recent Audit Stream -->
  <div class="card animate-fade-in-up" style="animation-delay:.25s;padding:var(--space-xl);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-lg);">
      <div>
        <div style="font-size:14px;font-weight:400;color:var(--color-ink);">Security Activity</div>
        <div style="font-size:12px;color:var(--color-ink-mute);">Live audit stream</div>
      </div>
      <a href="<?= url('/admin/audit') ?>" class="btn btn-ghost btn-sm" style="font-size:12px;text-decoration:none;">View Full Audit</a>
    </div>

    <div style="display:flex;flex-direction:column;gap:0;">
      <?php if (empty($recentAuditLogs)): ?>
        <div style="padding:2rem 1rem;text-align:center;color:var(--color-ink-mute);font-size:12px;">
          No security activity recorded yet.
        </div>
      <?php else: ?>
        <?php
        foreach ($recentAuditLogs as $log):
          $actionType = $log['action_type'] ?? 'EVENT';
          $timeStr = !empty($log['created_at']) ? date('h:i A', strtotime($log['created_at'])) : '';
          $userLabel = !empty($log['user_name']) ? $log['user_name'] : 'System';
          $isWarn = str_contains($actionType, 'DENIED') || str_contains($actionType, 'DELETE') || str_contains($actionType, 'FAILED') || str_contains($actionType, 'DAMAGE');
          $dotColor = $isWarn ? 'var(--color-error)' : 'var(--color-primary)';
        ?>
        <div style="display:flex;align-items:flex-start;gap:.75rem;padding:.625rem 0;border-bottom:1px solid var(--color-canvas-soft);">
          <div style="width:6px;height:6px;border-radius:50%;background:<?= $dotColor ?>;flex-shrink:0;margin-top:5px;"></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:400;color:var(--color-ink);"><?= htmlspecialchars(str_replace('_', ' ', $actionType), ENT_QUOTES, 'UTF-8') ?></div>
            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <span style="font-size:11px;color:var(--color-ink-mute);font-feature-settings:'tnum';flex-shrink:0;"><?= htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- ── Institutional User Directory ───────────────────────── -->
<div style="margin-top:var(--space-xl);">
  <div class="section-header">
    <div>
      <div class="section-title">Institutional User Directory</div>
      <div class="section-subtitle">Active accounts and access privileges across campus departments</div>
    </div>
    <a href="<?= url('/admin/users') ?>" class="btn btn-primary btn-sm" style="text-decoration:none;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      Open User Management
    </a>
  </div>

  <?php
  $tableRowsHtml = '';
  if (!empty($allUsers)) {
      foreach (array_slice($allUsers, 0, 10) as $usr) {
          $idNum = htmlspecialchars($usr['id_number'] ?? '—', ENT_QUOTES, 'UTF-8');
          $name = htmlspecialchars($usr['full_name'] ?? '—', ENT_QUOTES, 'UTF-8');
          $email = htmlspecialchars($usr['email'] ?? '—', ENT_QUOTES, 'UTF-8');
          $usrRole = htmlspecialchars(ucwords(str_replace('_', ' ', $usr['role'] ?? 'user')), ENT_QUOTES, 'UTF-8');
          $dept = htmlspecialchars($usr['department_program'] ?? '—', ENT_QUOTES, 'UTF-8');
          $status = htmlspecialchars($usr['account_status'] ?? 'pending', ENT_QUOTES, 'UTF-8');

          $statusBadge = $status === 'approved' 
              ? '<span class="badge badge-available">Active</span>' 
              : ($status === 'pending' ? '<span class="badge badge-borrowed">Pending</span>' : '<span class="badge badge-maintenance">Suspended</span>');

          $roleBadge = match($usr['role'] ?? '') {
              'admin' => 'badge-indigo',
              'custodian' => 'badge-available',
              'executive_admin' => 'badge-borrowed',
              default => 'badge-pending'
          };

          $tableRowsHtml .= "
          <tr data-user-id=\"{$usr['user_id']}\">
            <td class=\"num\" style=\"color:var(--color-ink-mute);font-size:12px;\">{$idNum}</td>
            <td style=\"font-weight:500;color:var(--color-ink);\">{$name}</td>
            <td style=\"font-size:12px;color:var(--color-ink-mute);\">{$email}</td>
            <td><span class=\"badge {$roleBadge}\" style=\"font-size:11px;\">{$usrRole}</span></td>
            <td>{$dept}</td>
            <td>{$statusBadge}</td>
            <td style=\"text-align:right;\">
              <a href=\"" . url('/admin/users') . "\" class=\"btn btn-ghost btn-icon-sm\" title=\"Manage User\"><svg viewBox=\"0 0 24 24\" width=\"14\" height=\"14\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7\"></path><path d=\"M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z\"></path></svg></a>
            </td>
          </tr>";
      }
  } else {
      $tableRowsHtml = '<tr><td colspan="7" style="text-align:center;padding:2.5rem 1rem;color:var(--color-ink-mute);">No users registered in the database yet.</td></tr>';
  }

  $props = [
    'id'                => 'admin-users-table',
    'title'             => null,
    'searchPlaceholder' => 'Search accounts...',
    'total'             => count($allUsers),
    'currentPage'       => 1,
    'pageSize'          => 10,
    'columns'           => [
      ['key' => 'id_number',   'label' => 'ID Number'],
      ['key' => 'name',        'label' => 'Full Name'],
      ['key' => 'email',       'label' => 'Institutional Email'],
      ['key' => 'role',        'label' => 'Assigned Role'],
      ['key' => 'dept',        'label' => 'Department / Program'],
      ['key' => 'status',      'label' => 'Status'],
      ['key' => 'actions',     'label' => ''],
    ],
    'bodyContent' => $tableRowsHtml,
  ];
  require __DIR__ . '/../components/data-table.php';
  ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const catLabels = <?= json_encode($categoryDist['labels'] ?? []) ?>;
  const catData   = <?= json_encode($categoryDist['data'] ?? []) ?>;
  const monthLabels = <?= json_encode($monthlyActivity['labels'] ?? []) ?>;
  const loanData    = <?= json_encode($monthlyActivity['loans'] ?? []) ?>;
  const returnData  = <?= json_encode($monthlyActivity['returns'] ?? []) ?>;

  if (typeof ChartFactory !== 'undefined') {
    // Asset Distribution Doughnut
    if (catLabels.length > 0) {
      ChartFactory.createDoughnut('asset-distribution-chart', {
        labels: catLabels,
        data: catData,
        colors: ['#166534', '#059669', '#d97706', '#0284c7', '#64748d', '#ea2261', '#8b5cf6'],
      });
    }

    // Function to render solid monthly activity bar chart
    function renderBorrowingChart(labels, loans, returns) {
      ChartFactory.createBar('borrowing-activity-chart', {
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
    renderBorrowingChart(monthLabels, loanData, returnData);

    // Timeframe toggle event listeners
    const tfBtns = document.querySelectorAll('#admin-borrowing-tf .segmented-btn');
    tfBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        tfBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const sliceVal = this.getAttribute('data-slice');

        if (sliceVal === '3') {
          renderBorrowingChart(monthLabels.slice(-3), loanData.slice(-3), returnData.slice(-3));
        } else if (sliceVal === '6') {
          renderBorrowingChart(monthLabels.slice(-6), loanData.slice(-6), returnData.slice(-6));
        } else {
          renderBorrowingChart(monthLabels, loanData, returnData);
        }
      });
    });
  }

  // Realtime live integration for Admin Overview
  if (window.RealtimeHub) {
    window.RealtimeHub.on('USER_REGISTERED', function(event) {
      const p = event.payload || {};
      const badge = document.getElementById('admin-pending-badge');
      if (badge) {
        let cur = parseInt(badge.textContent, 10) || 0;
        badge.textContent = (cur + 1) + ' new';
        badge.classList.add('kpi-pulse');
        setTimeout(() => badge.classList.remove('kpi-pulse'), 500);
      }
      if (window.Toast && typeof window.Toast.info === 'function') {
        window.Toast.info('New Registration', `${p.name || 'A new user'} registered and requires approval.`);
      }
    });

    window.RealtimeHub.on('USER_STATUS_CHANGED', function(event) {
      const p = event.payload || {};
      const pendingItem = document.querySelector(`.card div[data-user-id="${p.user_id}"]`);
      if (pendingItem) {
        pendingItem.classList.add('row-fade-out');
        setTimeout(() => pendingItem.remove(), 400);
      }
      const badge = document.getElementById('admin-pending-badge');
      if (badge) {
        let cur = parseInt(badge.textContent, 10) || 0;
        if (cur > 0) badge.textContent = (cur - 1) + ' new';
      }
      const tableRow = document.querySelector(`tr[data-user-id="${p.user_id}"]`);
      if (tableRow && tableRow.children[5]) {
        tableRow.children[5].innerHTML = (p.status === 'approved')
          ? '<span class="badge badge-available">Active</span>'
          : '<span class="badge badge-maintenance">Suspended</span>';
        tableRow.classList.remove('row-pulse-highlight');
        void tableRow.offsetWidth;
        tableRow.classList.add('row-pulse-highlight');
      }
    });

    window.RealtimeHub.on('LOAN_APPROVED', function(event) {
      if (typeof ChartFactory !== 'undefined') {
        ChartFactory.updateDataset('borrowing-activity-chart', function(chart) {
          if (chart.data.datasets && chart.data.datasets[0] && chart.data.datasets[0].data.length > 0) {
            const lastIdx = chart.data.datasets[0].data.length - 1;
            chart.data.datasets[0].data[lastIdx] = (chart.data.datasets[0].data[lastIdx] || 0) + 1;
          }
        });
      }
    });

    window.RealtimeHub.on('LOAN_RETURNED', function(event) {
      if (typeof ChartFactory !== 'undefined') {
        ChartFactory.updateDataset('borrowing-activity-chart', function(chart) {
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
