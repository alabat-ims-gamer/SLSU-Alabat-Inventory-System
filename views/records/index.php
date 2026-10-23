<?php
/**
 * Borrower Transaction Ledger View
 * Comprehensive, searchable, and filtered audit log of laboratory borrows.
 * 100% database-backed, responsive, OWASP XSS protected.
 */
$title      = 'My Records';
$breadcrumb = 'My Records';

$stats       = $stats ?? ['total_borrowed' => 0, 'active_loans' => 0, 'returned_count' => 0, 'overdue_count' => 0];
$records     = $records ?? [];
$total       = (int)($total ?? 0);
$currentPage = (int)($currentPage ?? 1);
$totalPages  = (int)($totalPages ?? 1);
$pageSize    = (int)($pageSize ?? 10);
$status      = $filters['status'] ?? 'all';
$searchQuery = $filters['q'] ?? '';
$period      = $filters['period'] ?? 'all';

require __DIR__ . '/../layouts/shell.php';
?>

<!-- ── Page Header ────────────────────────────────────────── -->
<div class="section-header">
  <div>
    <h1 class="section-title">My Records</h1>
    <div class="section-subtitle">Comprehensive audit ledger of all your equipment borrows and returns</div>
  </div>
  <a href="<?= url('/scan') ?>" class="btn btn-primary btn-sm">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
    Scan Equipment
  </a>
</div>

<!-- ── Summary Metric Strip ───────────────────────────────── -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:var(--space-md);margin-bottom:var(--space-xl);">
  <?php
  $props = ['title' => 'Lifetime Borrows', 'value' => $stats['total_borrowed'], 'icon' => 'clipboard-list', 'color' => 'indigo', 'subtitle' => 'Total transactions recorded'];
  require __DIR__ . '/../components/stat-card.php';

  $props = ['title' => 'Active Borrows', 'value' => $stats['active_loans'], 'icon' => 'clock', 'color' => 'blue', 'subtitle' => 'Currently in possession'];
  require __DIR__ . '/../components/stat-card.php';

  $props = ['title' => 'Returned Items', 'value' => $stats['returned_count'], 'icon' => 'package', 'color' => 'green', 'subtitle' => 'Checked back into inventory'];
  require __DIR__ . '/../components/stat-card.php';

  $props = ['title' => 'Overdue Records', 'value' => $stats['overdue_count'], 'icon' => 'alert-triangle', 'color' => 'ruby', 'subtitle' => 'Requires immediate return'];
  require __DIR__ . '/../components/stat-card.php';
  ?>
</div>

<!-- ── Ledger Table & Control Panel ───────────────────────── -->
<div class="data-table-wrap" id="records-ledger-wrap">

  <!-- Toolbar & Filters -->
  <div class="data-table-toolbar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);padding:var(--space-md) var(--space-lg);border-bottom:1px solid var(--color-hairline);">
    
    <!-- Status Filter Tabs (Pills) -->
    <div style="display:flex;align-items:center;gap:var(--space-xs);flex-wrap:wrap;">
      <?php
      $tabDefs = [
        'all'      => ['label' => 'All Records', 'count' => $stats['total_borrowed']],
        'active'   => ['label' => 'Active',      'count' => $stats['active_loans']],
        'returned' => ['label' => 'Returned',    'count' => $stats['returned_count']],
        'overdue'  => ['label' => 'Overdue',     'count' => $stats['overdue_count']],
      ];

      foreach ($tabDefs as $tabKey => $tab):
        $isActiveTab = ($status === $tabKey);
        $tabUrl = url('/records') . '?' . http_build_query([
          'status' => $tabKey,
          'q'      => $searchQuery,
          'period' => $period,
          'page'   => 1
        ]);
      ?>
        <a href="<?= htmlspecialchars($tabUrl, ENT_QUOTES, 'UTF-8') ?>" 
           class="badge <?= $isActiveTab ? 'badge-approved' : 'badge-suspended' ?>" 
           style="text-decoration:none;padding:6px 12px;font-size:12px;cursor:pointer;transition:all var(--transition-fast);border:1px solid <?= $isActiveTab ? 'var(--color-primary)' : 'var(--color-hairline)' ?>;background:<?= $isActiveTab ? 'var(--color-primary)' : 'var(--color-canvas)' ?>;color:<?= $isActiveTab ? '#ffffff' : 'var(--color-ink-secondary)' ?>;">
          <?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
          <span style="margin-left:4px;opacity:0.85;font-feature-settings:'tnum';font-size:11px;">(<?= $tab['count'] ?>)</span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Search & Period Filter Form -->
    <form method="GET" action="<?= url('/records') ?>" style="display:flex;align-items:center;gap:var(--space-sm);flex-wrap:wrap;margin:0;">
      <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
      
      <!-- Period Selector -->
      <select name="period" class="form-input" style="width:auto;font-size:13px;padding:6px 10px;height:36px;border-radius:var(--radius-md);" onchange="this.form.submit()">
        <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>All Time</option>
        <option value="30days" <?= $period === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
        <option value="semester" <?= $period === 'semester' ? 'selected' : '' ?>>This Semester (120 Days)</option>
        <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Past Year</option>
      </select>

      <!-- Search Box -->
      <div style="position:relative;display:flex;align-items:center;">
        <input 
          type="text" 
          name="q" 
          class="form-input" 
          placeholder="Search by item, barcode..." 
          value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>"
          style="padding-left:32px;font-size:13px;height:36px;min-width:200px;border-radius:var(--radius-md);"
        >
        <span style="position:absolute;left:10px;color:var(--color-ink-mute);pointer-events:none;display:flex;align-items:center;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </span>
      </div>

      <button type="submit" class="btn btn-secondary btn-sm" style="height:36px;padding:0 12px;">Filter</button>
      
      <?php if ($searchQuery !== '' || $period !== 'all' || $status !== 'all'): ?>
        <a href="<?= url('/records') ?>" class="btn btn-ghost btn-sm" style="height:36px;padding:0 8px;font-size:12px;color:var(--color-ink-mute);" title="Reset all filters">Reset</a>
      <?php endif; ?>
    </form>

  </div>

  <!-- Table Body -->
  <div class="data-table-body" style="overflow-x:auto;">
    <?php if (empty($records)): ?>
      <!-- Empty State -->
      <div class="empty-state" style="padding:var(--space-xxl) var(--space-lg);text-align:center;">
        <div class="empty-state-icon" style="margin-bottom:var(--space-md);">
          <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:40px;height:40px;display:block;margin:0 auto;color:var(--color-ink-mute);">
            <path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"></path>
            <rect x="8" y="2" width="8" height="4" rx="1"></rect>
          </svg>
        </div>
        <div class="empty-state-title" style="font-size:16px;font-weight:400;color:var(--color-ink);">No transaction records found</div>
        <div class="empty-state-desc" style="font-size:13px;color:var(--color-ink-mute);max-width:420px;margin:var(--space-xs) auto var(--space-lg);">
          <?= ($searchQuery !== '' || $status !== 'all' || $period !== 'all') 
              ? 'No transactions matched your active search and filter criteria. Try clearing the filters.' 
              : 'You have no recorded laboratory transactions. Scan an equipment barcode or check out an item with the Property Custodian to begin.' ?>
        </div>
        <?php if ($searchQuery !== '' || $status !== 'all' || $period !== 'all'): ?>
          <a href="<?= url('/records') ?>" class="btn btn-secondary btn-sm">Clear Filters</a>
        <?php else: ?>
          <a href="<?= url('/scan') ?>" class="btn btn-primary btn-sm">Scan Equipment</a>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <table class="data-table" role="grid" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
            <th scope="col" style="padding:12px 16px;text-align:left;font-size:12px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Asset Details</th>
            <th scope="col" style="padding:12px 16px;text-align:left;font-size:12px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Borrowed On</th>
            <th scope="col" style="padding:12px 16px;text-align:left;font-size:12px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Due Date</th>
            <th scope="col" style="padding:12px 16px;text-align:left;font-size:12px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Returned On</th>
            <th scope="col" style="padding:12px 16px;text-align:left;font-size:12px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Return Condition</th>
            <th scope="col" style="padding:12px 16px;text-align:left;font-size:12px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.5px;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $row): 
            $itemName     = htmlspecialchars($row['item_name'] ?? 'Equipment', ENT_QUOTES, 'UTF-8');
            $barcode      = htmlspecialchars($row['barcode'] ?? '—', ENT_QUOTES, 'UTF-8');
            $propertyNo   = htmlspecialchars($row['property_number'] ?? '', ENT_QUOTES, 'UTF-8');
            $category     = htmlspecialchars($row['category'] ?? 'General', ENT_QUOTES, 'UTF-8');
            $borrowDate   = !empty($row['transaction_date']) ? date('M j, Y — h:i A', strtotime($row['transaction_date'])) : '—';
            $dueDate      = !empty($row['expected_return_date']) ? date('M j, Y — h:i A', strtotime($row['expected_return_date'])) : '—';
            
            $isReturned   = !empty($row['actual_return_date']);
            $returnedDate = $isReturned ? date('M j, Y — h:i A', strtotime($row['actual_return_date'])) : null;
            $condition    = $row['returned_condition'] ?? null;
            $remarks      = htmlspecialchars($row['remarks'] ?? '', ENT_QUOTES, 'UTF-8');
            
            $isOverdueRow = !$isReturned && !empty($row['expected_return_date']) && (strtotime($row['expected_return_date']) < time());
            
            // Status determination
            if ($isReturned) {
              if ($condition === 'damaged' || $condition === 'not_working') {
                $statusHtml = '<span class="badge badge-maintenance">Damaged</span>';
              } else {
                $statusHtml = '<span class="badge badge-approved">Returned</span>';
              }
            } elseif ($isOverdueRow) {
              $statusHtml = '<span class="badge badge-rejected">Overdue</span>';
            } else {
              $statusHtml = '<span class="badge badge-borrowed">Active Borrow</span>';
            }
          ?>
            <tr style="border-bottom:1px solid var(--color-hairline);transition:background var(--transition-fast);">
              <!-- Asset Details -->
              <td style="padding:14px 16px;">
                <div style="font-weight:400;color:var(--color-ink);font-size:14px;"><?= $itemName ?></div>
                <div style="display:flex;align-items:center;gap:var(--space-xs);margin-top:3px;font-size:12px;color:var(--color-ink-mute);flex-wrap:wrap;">
                  <span class="num" style="font-size:11px;background:var(--color-canvas-soft);padding:1px 6px;border-radius:var(--radius-xs);border:1px solid var(--color-hairline);"><?= $barcode ?></span>
                  <?php if ($propertyNo !== ''): ?>
                    <span>• Prop: <span class="num"><?= $propertyNo ?></span></span>
                  <?php endif; ?>
                  <span>• <?= $category ?></span>
                </div>
              </td>

              <!-- Borrowed Date -->
              <td style="padding:14px 16px;font-size:13px;color:var(--color-ink-secondary);" class="num">
                <?= $borrowDate ?>
              </td>

              <!-- Due Date -->
              <td style="padding:14px 16px;font-size:13px;color:<?= $isOverdueRow ? 'var(--color-ruby)' : 'var(--color-ink-secondary)' ?>;" class="num">
                <?= $dueDate ?>
                <?php if ($isOverdueRow): ?>
                  <div style="font-size:11px;color:var(--color-ruby);font-weight:500;">Late / Overdue</div>
                <?php endif; ?>
              </td>

              <!-- Returned Date -->
              <td style="padding:14px 16px;font-size:13px;" class="num">
                <?php if ($isReturned): ?>
                  <span style="color:var(--color-ink);"><?= $returnedDate ?></span>
                <?php else: ?>
                  <span style="color:var(--color-primary);font-weight:400;">Currently Held</span>
                <?php endif; ?>
              </td>

              <!-- Return Condition -->
              <td style="padding:14px 16px;font-size:13px;">
                <?php if ($condition): ?>
                  <?php 
                  $props = ['status' => $condition, 'is_condition' => true]; 
                  require __DIR__ . '/../components/status-badge.php';
                  ?>
                  <?php if ($remarks !== ''): ?>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;" title="<?= $remarks ?>">
                      <?= mb_strimwidth($remarks, 0, 35, '...') ?>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color:var(--color-ink-mute);font-size:12px;">Pending Return</span>
                <?php endif; ?>
              </td>

              <!-- Status -->
              <td style="padding:14px 16px;">
                <?= $statusHtml ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Pagination Footer -->
  <div class="data-table-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);padding:var(--space-md) var(--space-lg);border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
    <span class="data-table-count text-caption" style="font-size:13px;color:var(--color-ink-mute);">
      <?php if ($total > 0): 
        $startEntry = (($currentPage - 1) * $pageSize) + 1;
        $endEntry   = min($currentPage * $pageSize, $total);
      ?>
        Showing <span class="num"><?= $startEntry ?></span>–<span class="num"><?= $endEntry ?></span> of <span class="num"><?= number_format($total) ?></span> records
      <?php else: ?>
        0 records
      <?php endif; ?>
    </span>

    <?php if ($totalPages > 1): ?>
      <div class="pagination" role="navigation" aria-label="Pagination" style="display:flex;align-items:center;gap:var(--space-xxs);">
        
        <!-- Prev Button -->
        <?php
        $prevUrl = url('/records') . '?' . http_build_query([
          'status' => $status,
          'q'      => $searchQuery,
          'period' => $period,
          'page'   => max(1, $currentPage - 1)
        ]);
        ?>
        <a href="<?= $currentPage > 1 ? htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') : 'javascript:void(0);' ?>" 
           class="pagination-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" 
           style="<?= $currentPage <= 1 ? 'opacity:0.4;pointer-events:none;' : '' ?>"
           aria-label="Previous page">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </a>

        <!-- Page Numbers -->
        <?php
        $range = range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2));
        if (!in_array(1, $range, true)) {
          $firstUrl = url('/records') . '?' . http_build_query(['status' => $status, 'q' => $searchQuery, 'period' => $period, 'page' => 1]);
          echo '<a href="' . htmlspecialchars($firstUrl, ENT_QUOTES, 'UTF-8') . '" class="pagination-btn">1</a>';
          if (!in_array(2, $range, true)) {
            echo '<span style="color:var(--color-ink-mute);padding:0 4px;font-size:12px;">…</span>';
          }
        }

        foreach ($range as $p):
          $pUrl = url('/records') . '?' . http_build_query([
            'status' => $status,
            'q'      => $searchQuery,
            'period' => $period,
            'page'   => $p
          ]);
        ?>
          <a href="<?= htmlspecialchars($pUrl, ENT_QUOTES, 'UTF-8') ?>" 
             class="pagination-btn <?= $p === $currentPage ? 'active' : '' ?>"
             <?= $p === $currentPage ? 'aria-current="page"' : '' ?>>
            <?= $p ?>
          </a>
        <?php endforeach; ?>

        <?php
        if (!in_array($totalPages, $range, true)) {
          if (!in_array($totalPages - 1, $range, true)) {
            echo '<span style="color:var(--color-ink-mute);padding:0 4px;font-size:12px;">…</span>';
          }
          $lastUrl = url('/records') . '?' . http_build_query(['status' => $status, 'q' => $searchQuery, 'period' => $period, 'page' => $totalPages]);
          echo '<a href="' . htmlspecialchars($lastUrl, ENT_QUOTES, 'UTF-8') . '" class="pagination-btn">' . $totalPages . '</a>';
        }
        ?>

        <!-- Next Button -->
        <?php
        $nextUrl = url('/records') . '?' . http_build_query([
          'status' => $status,
          'q'      => $searchQuery,
          'period' => $period,
          'page'   => min($totalPages, $currentPage + 1)
        ]);
        ?>
        <a href="<?= $currentPage < $totalPages ? htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') : 'javascript:void(0);' ?>" 
           class="pagination-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" 
           style="<?= $currentPage >= $totalPages ? 'opacity:0.4;pointer-events:none;' : '' ?>"
           aria-label="Next page">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>

      </div>
    <?php endif; ?>
  </div>

</div>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
