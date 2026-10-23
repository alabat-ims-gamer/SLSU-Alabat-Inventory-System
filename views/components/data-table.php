<?php
/**
 * Data Table Component
 * Usage: component('data-table', ['id'=>'assets-table','columns'=>[...],'searchPlaceholder'=>'Search...'])
 */
$id                = $props['id']                ?? 'data-table-' . rand(1000, 9999);
$columns           = $props['columns']           ?? [];
$searchPlaceholder = $props['searchPlaceholder'] ?? 'Search...';
$title             = $props['title']             ?? null;
$subtitle          = $props['subtitle']          ?? null;
$total             = $props['total']             ?? 0;
$currentPage       = $props['currentPage']       ?? 1;
$pageSize          = $props['pageSize']          ?? 10;
$totalPages        = $total > 0 ? ceil($total / $pageSize) : 1;
$filterSlot        = $props['filterSlot']        ?? null;
$actionSlot        = $props['actionSlot']        ?? null;
$rows              = $props['rows']              ?? [];
$emptyIcon         = $props['emptyIcon']         ?? 'package';

// Empty state icons
$emptyIcons = [
  'package' => '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>',
  'users'   => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle>',
  'search'  => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
  'clipboard-list' => '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect>',
];
$emptyIconPath = $emptyIcons[$emptyIcon] ?? $emptyIcons['package'];

$startEntry = $total > 0 ? (($currentPage - 1) * $pageSize) + 1 : 0;
$endEntry   = min($currentPage * $pageSize, $total);
?>

<div class="data-table-wrap" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" data-table-id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">

  <!-- Toolbar -->
  <div class="data-table-toolbar">

    <?php if ($title): ?>
    <div style="margin-right:auto;">
      <div style="font-size:14px;font-weight:400;color:var(--color-ink);"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
      <?php if ($subtitle): ?>
      <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="data-table-search" style="<?= $title ? 'max-width:240px;' : 'flex:1;' ?>">
      <span class="data-table-search-icon">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:block;">
          <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </span>
      <input
        type="text"
        class="form-input data-table-search-input"
        placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>"
        aria-label="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>"
      >
    </div>

    <!-- Filter slot -->
    <?php if ($filterSlot): ?>
      <?= $filterSlot ?>
    <?php endif; ?>

    <!-- Action slot -->
    <?php if ($actionSlot): ?>
      <?= $actionSlot ?>
    <?php endif; ?>

  </div>

  <!-- Table Body -->
  <div class="data-table-body">
    <?php if (empty($rows) && empty($props['bodyContent'])): ?>
    <!-- Empty State -->
    <div class="empty-state">
      <div class="empty-state-icon">
        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px;display:block;">
          <?= $emptyIconPath ?>
        </svg>
      </div>
      <div class="empty-state-title">No records found</div>
      <div class="empty-state-desc">
        <?= $total > 0 ? 'No results match your search query. Try adjusting your filters.' : 'There are no records to display yet. Add a new entry to get started.' ?>
      </div>
    </div>

    <?php else: ?>
    <table class="data-table" role="grid">
      <thead>
        <tr>
          <?php foreach ($columns as $col): ?>
            <th scope="col" data-sort="<?= htmlspecialchars($col['key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($col['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if ($props['bodyContent'] ?? null): ?>
          <?= $props['bodyContent'] ?>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <?php foreach ($columns as $col): ?>
                <td><?= $row[$col['key']] ?? '—' ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Pagination Footer -->
  <div class="data-table-footer">
    <span class="data-table-count text-caption">
      <?php if ($total > 0): ?>
        Showing <?= $startEntry ?>–<?= $endEntry ?> of <?= number_format($total) ?> entries
      <?php else: ?>
        0 entries
      <?php endif; ?>
    </span>

    <?php if ($totalPages > 1): ?>
    <div class="pagination" role="navigation" aria-label="Pagination">
      <!-- Prev -->
      <button class="pagination-btn" <?= $currentPage <= 1 ? 'disabled' : '' ?> aria-label="Previous page"
        onclick="window.location.href=updatePageParam(<?= max(1, $currentPage - 1) ?>)">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline-block;"><polyline points="15 18 9 12 15 6"></polyline></svg>
      </button>

      <?php
      $range = range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2));
      if (!in_array(1, $range)) { echo '<span class="pagination-btn" style="cursor:default;">1</span><span style="color:var(--color-ink-mute);font-size:12px;padding:0 4px;">…</span>'; }
      foreach ($range as $p):
      ?>
        <button class="pagination-btn <?= $p === $currentPage ? 'active' : '' ?>"
          onclick="window.location.href=updatePageParam(<?= $p ?>)"
          aria-label="Page <?= $p ?>" <?= $p === $currentPage ? 'aria-current="page"' : '' ?>>
          <?= $p ?>
        </button>
      <?php
      endforeach;
      if (!in_array($totalPages, $range) && $totalPages > 1) {
        echo '<span style="color:var(--color-ink-mute);font-size:12px;padding:0 4px;">…</span><span class="pagination-btn" onclick="window.location.href=updatePageParam(' . $totalPages . ')">' . $totalPages . '</span>';
      }
      ?>

      <!-- Next -->
      <button class="pagination-btn" <?= $currentPage >= $totalPages ? 'disabled' : '' ?> aria-label="Next page"
        onclick="window.location.href=updatePageParam(<?= min($totalPages, $currentPage + 1) ?>)">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline-block;"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </button>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
function updatePageParam(page) {
  const url = new URL(window.location.href);
  url.searchParams.set('page', page);
  return url.toString();
}
</script>
