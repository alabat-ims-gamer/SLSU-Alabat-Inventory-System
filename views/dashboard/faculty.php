<?php
/**
 * Faculty Dashboard
 * Faculty can borrow items, scan barcodes, and view their own borrowing records
 * 100% database-backed with live statistics and clean empty states.
 */
$title      = 'Faculty Portal';
$breadcrumb = 'Faculty Portal';

$activeLoans       = $activeLoans ?? [];
$loanHistory       = $loanHistory ?? [];
$loanCount         = count($activeLoans);
$maxItems          = (int)($maxItems ?? 10);
$isOverdue         = $isOverdue ?? false;
$returnedThisMonth = (int)($returnedThisMonth ?? 0);
$totalLoansYtd     = (int)($totalLoansYtd ?? 0);

require __DIR__ . '/../layouts/shell.php';
?>

<!-- ── Damage Clearance Hold Banner (shown when faculty has damage hold) ── -->
<?php if (!empty($damageHold)): ?>
<div class="overdue-banner" style="background:rgba(234,34,97,0.06);border-color:rgba(234,34,97,0.3);margin-bottom:var(--space-md);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div style="display:flex;align-items:flex-start;gap:12px;flex:1;min-width:280px;">
    <div class="overdue-banner-icon" style="background:var(--color-ruby);color:#ffffff;flex-shrink:0;">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;display:block;">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
      </svg>
    </div>
    <div>
      <div style="font-size:14px;font-weight:600;color:var(--color-ruby);display:flex;align-items:center;gap:8px;">
        Borrowing Suspended &mdash; Damage Clearance Required
        <?php if (!empty($damageHold['incident_status']) && $damageHold['incident_status'] === 'pending_verification'): ?>
          <span class="badge badge-pending" style="font-size:10px;padding:2px 8px;">Receipt Under Review</span>
        <?php elseif (!empty($damageHold['incident_status']) && $damageHold['incident_status'] === 'rejected'): ?>
          <span class="badge badge-rejected" style="font-size:10px;padding:2px 8px;">Receipt Rejected</span>
        <?php endif; ?>
      </div>
      <div style="font-size:13px;color:var(--color-ink-secondary);margin-top:3px;line-height:1.4;">
        Account hold active for: <strong><?= htmlspecialchars($damageHold['item_name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($damageHold['barcode'], ENT_QUOTES, 'UTF-8') ?>).
        <?php if ($damageHold['estimated_cost'] !== null && (float)$damageHold['estimated_cost'] > 0): ?>
          <span style="display:inline-block;margin-left:6px;background:rgba(220,38,38,0.1);color:var(--color-ruby);font-weight:600;padding:1px 6px;border-radius:4px;font-family:monospace;">
            Assessed Fee: ₱<?= number_format((float)$damageHold['estimated_cost'], 2) ?>
          </span>
        <?php endif; ?>
        <br><span style="color:var(--color-ink-mute);font-size:12px;">Inspection remarks: &ldquo;<?= htmlspecialchars($damageHold['damage_description'] ?? 'Damaged upon return', ENT_QUOTES, 'UTF-8') ?>&rdquo;</span>
      </div>
    </div>
  </div>
  <div>
    <a href="<?= url('/my-clearances') ?>" class="btn btn-sm" style="background:var(--color-ruby);color:#ffffff;border:none;border-radius:9999px;padding:6px 14px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;text-decoration:none;box-shadow:0 1px 2px rgba(220,38,38,0.2);">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
      Settle in My Clearances &rarr;
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ── Overdue Alert Banner ─────────────────────────────── -->
<?php if ($isOverdue): ?>
<div class="overdue-banner" style="margin-bottom:var(--space-md);">
  <div class="overdue-banner-icon">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;display:block;">
      <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
      <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
    </svg>
  </div>
  <div>
    <div style="font-size:14px;font-weight:400;color:var(--color-ruby);">Overdue Items — Borrowing Suspended</div>
    <div style="font-size:13px;color:var(--color-ink-mute);margin-top:2px;">Please return all overdue items to the Property Custodian to restore your borrowing access.</div>
  </div>
</div>
<?php endif; ?>

<!-- ── Page Header ────────────────────────────────────────── -->
<div class="section-header">
  <div>
    <h1 class="section-title">Faculty Portal</h1>
    <div class="section-subtitle">Manage your instructional equipment borrows and laboratory access</div>
  </div>
  <div style="display:flex;gap:.5rem;">
    <a href="<?= url('/scan') ?>" class="btn btn-primary btn-sm">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
      Scan &amp; Request Equipment
    </a>
  </div>
</div>

<!-- ── Summary Cards (Responsive Fluid Grid) ─────────── -->
<div class="grid-kpi-stats stagger-children">
  <?php
  $facultyStats = [
    ['title' => 'Currently Borrowed', 'value' => $loanCount,                     'icon' => 'clipboard-list', 'color' => 'indigo',  'subtitle' => "{$loanCount} of {$maxItems} quota used"],
    ['title' => 'Returned This Month','value' => $returnedThisMonth,             'icon' => 'clock',          'color' => 'emerald', 'subtitle' => 'Checked in successfully'],
    ['title' => 'Total Borrows (YTD)',  'value' => $totalLoansYtd,                 'icon' => 'bar-chart-2',    'color' => 'indigo',  'subtitle' => 'This academic year'],
    ['title' => 'Overdue',            'value' => $isOverdue ? '1' : '0',         'icon' => 'alert-triangle', 'color' => $isOverdue ? 'ruby' : 'emerald', 'subtitle' => $isOverdue ? 'Action required' : 'All on time'],
  ];
  foreach ($facultyStats as $stat):
    $props = $stat;
    require __DIR__ . '/../components/stat-card.php';
  endforeach;
  ?>
</div>

<!-- ── Quota Indicator ────────────────────────────────────── -->
<div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);padding:var(--space-lg) var(--space-xl);margin-bottom:var(--space-xl);box-shadow:var(--shadow-1);display:flex;align-items:center;gap:var(--space-xl);flex-wrap:wrap;" class="animate-fade-in-up">
  <div style="flex:1;min-width:240px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
      <span style="font-size:13px;color:var(--color-ink-mute);">Borrowing Quota</span>
      <span style="font-size:13px;font-weight:400;color:var(--color-ink);font-feature-settings:'tnum';"><?= $loanCount ?> / <?= $maxItems ?> items</span>
    </div>
    <div style="height:6px;background:var(--color-hairline);border-radius:var(--radius-pill);overflow:hidden;">
      <?php $pct = $maxItems > 0 ? ($loanCount / $maxItems) * 100 : 0; ?>
      <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct >= 100 ? 'var(--color-ruby)' : ($pct >= 80 ? 'var(--color-warning)' : 'var(--color-primary)') ?>;border-radius:var(--radius-pill);transition:width .5s cubic-bezier(.16,1,.3,1);"></div>
    </div>
    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:.25rem;">
      Faculty account &bull; up to <?= $maxItems ?> concurrent borrows, 7-day borrowal periods
    </div>
  </div>
  <div style="text-align:right;flex-shrink:0;">
    <div style="font-size:28px;font-weight:300;color:var(--color-primary);font-feature-settings:'tnum';letter-spacing:-.5px;"><?= max(0, $maxItems - $loanCount) ?></div>
    <div style="font-size:11px;color:var(--color-ink-mute);">slots available</div>
  </div>
</div>

<!-- ── Active Loans ───────────────────────────────────────── -->
<div class="section-header" id="active-loans" style="scroll-margin-top:2rem;">
  <div>
    <div class="section-title">Currently Borrowed Items</div>
    <div class="section-subtitle">Show barcodes to Property Custodian on return</div>
  </div>
</div>

<?php if (empty($activeLoans)): ?>
  <?php
  $props = [
    'icon'        => 'clipboard-list',
    'title'       => 'No active borrows',
    'description' => 'You have no equipment currently borrowed. Visit the Property Custodian or open the Scanner Station to request items.',
    'actionText'  => 'Open Scanner Station',
    'actionLink'  => url('/scan'),
  ];
  require __DIR__ . '/../components/empty-state.php';
  ?>
<?php else: ?>

<div class="grid-2col-responsive stagger-children" style="margin-bottom:var(--space-xl);">
  <?php foreach ($activeLoans as $i => $loan):
    $loanName = $loan['item_name'] ?? $loan['name'] ?? 'Equipment';
    $dueDate  = $loan['expected_return_date'] ?? $loan['due'] ?? date('Y-m-d H:i:s');
    $timeLeft  = strtotime($dueDate) - time();
    $daysLeft  = floor($timeLeft / 86400);
    $isUrgent  = $timeLeft > 0 && $timeLeft < 3600;
    $isWarning = $timeLeft > 0 && $timeLeft < 86400;
    $isOverdueItem = $timeLeft < 0;
    $borderColor = $isOverdueItem ? 'var(--color-error)' : ($isUrgent ? 'var(--color-ruby)' : ($isWarning ? 'var(--color-warning)' : 'var(--color-hairline)'));
  ?>
  <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3px solid <?= $borderColor ?>;border-radius:var(--radius-lg);padding:var(--space-lg) var(--space-xl);box-shadow:var(--shadow-1);display:flex;flex-direction:column;gap:var(--space-md);transition:box-shadow var(--transition-med),transform var(--transition-med);" onmouseover="this.style.boxShadow='var(--shadow-2)';this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='var(--shadow-1)';this.style.transform=''">

    <!-- Top Row: Category Pill + Status -->
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <span class="pill-tag" style="background:rgba(22, 101, 52,.08);color:var(--color-primary);"><?= htmlspecialchars($loan['category'] ?? 'Equipment', ENT_QUOTES, 'UTF-8') ?></span>
      <?php if ($isOverdueItem): ?>
        <span class="badge badge-overdue">Overdue</span>
      <?php else: ?>
        <span class="badge badge-borrowed">Active</span>
      <?php endif; ?>
    </div>

    <!-- Asset Name -->
    <div>
      <div style="font-size:14px;font-weight:400;color:var(--color-ink);line-height:1.3;"><?= htmlspecialchars($loanName, ENT_QUOTES, 'UTF-8') ?></div>
      <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;font-feature-settings:'tnum';"><?= htmlspecialchars($loan['barcode'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <!-- Barcode SVG -->
    <svg data-barcode="<?= htmlspecialchars($loan['barcode'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width:100%;height:44px;"></svg>

    <!-- Due + Countdown -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:var(--space-sm);border-top:1px solid var(--color-canvas-soft);">
      <div>
        <div style="font-size:11px;color:var(--color-ink-mute);">Return by</div>
        <div style="font-size:12px;color:var(--color-ink);font-feature-settings:'tnum';margin-top:1px;">
          <?= $daysLeft > 0 ? date('M j, h:i A', strtotime($dueDate)) : date('h:i A', strtotime($dueDate)) ?>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:11px;color:var(--color-ink-mute);">Remaining</div>
        <?php
        $props = ['targetDate' => $dueDate, 'warningThresholdMinutes' => 1440];
        require __DIR__ . '/../components/countdown-timer.php';
        ?>
      </div>
    </div>

  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Borrowing History ───────────────────────────────────── -->
<div class="section-header" style="margin-top:var(--space-xl);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-sm);">
  <div>
    <div class="section-title">Recent Borrow Activity</div>
    <div class="section-subtitle">Your personal faculty borrowing record preview</div>
  </div>
  <a href="<?= url('/records') ?>" class="btn btn-secondary btn-sm">
    View All Records
    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><polyline points="9 18 15 12 9 6"></polyline></svg>
  </a>
</div>

<?php
$historyRowsHtml = '';
if (!empty($loanHistory)) {
    foreach ($loanHistory as $tx) {
        $borrowDate = !empty($tx['transaction_date']) ? date('M j, Y', strtotime($tx['transaction_date'])) : '—';
        $item = htmlspecialchars($tx['item_name'] ?? 'Equipment', ENT_QUOTES, 'UTF-8');
        $barcode = htmlspecialchars($tx['barcode'] ?? '—', ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($tx['category'] ?? 'General', ENT_QUOTES, 'UTF-8');
        
        $isReturned = !empty($tx['actual_return_date']);
        $returnDate = $isReturned ? date('M j, h:i A', strtotime($tx['actual_return_date'])) : '<span style="color:var(--color-primary);font-weight:400;">In Use</span>';
        
        if ($isReturned) {
            $statusBadge = ($tx['returned_condition'] === 'damaged') 
                ? '<span class="badge badge-maintenance">Damaged</span>' 
                : '<span class="badge badge-approved">Returned</span>';
        } else {
            $isOverdueTx = !empty($tx['expected_return_date']) && strtotime($tx['expected_return_date']) < time();
            $statusBadge = $isOverdueTx 
                ? '<span class="badge badge-maintenance">Overdue</span>' 
                : '<span class="badge badge-borrowed">Active</span>';
        }

        $historyRowsHtml .= "
        <tr>
          <td class=\"num\" style=\"font-size:12px;color:var(--color-ink-mute);\">{$borrowDate}</td>
          <td style=\"font-weight:400;color:var(--color-ink);\">{$item}</td>
          <td class=\"num\" style=\"font-size:12px;\">{$barcode}</td>
          <td>{$category}</td>
          <td class=\"num\" style=\"font-size:12px;\">{$returnDate}</td>
          <td>{$statusBadge}</td>
        </tr>";
    }
} else {
    $historyRowsHtml = '<tr><td colspan="6" style="text-align:center;padding:2.5rem 1rem;color:var(--color-ink-mute);">No personal borrowing history recorded.</td></tr>';
}

$props = [
  'id'                => 'faculty-history-table',
  'searchPlaceholder' => 'Search recent history...',
  'total'             => count($loanHistory),
  'currentPage'       => 1,
  'pageSize'          => 5,
  'columns'           => [
    ['key' => 'date',      'label' => 'Borrowed'],
    ['key' => 'item',      'label' => 'Item'],
    ['key' => 'barcode',   'label' => 'Barcode'],
    ['key' => 'category',  'label' => 'Category'],
    ['key' => 'returned',  'label' => 'Returned'],
    ['key' => 'status',    'label' => 'Status'],
  ],
  'bodyContent' => $historyRowsHtml,
];
require __DIR__ . '/../components/data-table.php';
?>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
