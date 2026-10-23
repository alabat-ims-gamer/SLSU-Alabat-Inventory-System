<?php
/**
 * Empty State Component
 * Usage: component('empty-state', ['icon'=>'package','title'=>'No assets','description'=>'...','actionText'=>'Add Asset','actionLink'=>'/...'])
 */
$icon        = $props['icon']        ?? 'package';
$title       = $props['title']       ?? 'Nothing here yet';
$description = $props['description'] ?? 'No records to display.';
$actionText  = $props['actionText']  ?? null;
$actionLink  = $props['actionLink']  ?? null;
$actionModal = $props['actionModal'] ?? null;

$icons = [
  'package'       => '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>',
  'users'         => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle>',
  'clipboard-list'=> '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect>',
  'search'        => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
  'filter'        => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>',
  'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
];
$iconPath = $icons[$icon] ?? $icons['package'];
?>
<div class="empty-state">
  <div class="empty-state-icon">
    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px;display:block;">
      <?= $iconPath ?>
    </svg>
  </div>
  <div class="empty-state-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
  <div class="empty-state-desc"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></div>
  <?php if ($actionText): ?>
    <?php if ($actionLink): ?>
      <a href="<?= htmlspecialchars($actionLink, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm" style="margin-top:.25rem;"><?= htmlspecialchars($actionText, ENT_QUOTES, 'UTF-8') ?></a>
    <?php elseif ($actionModal): ?>
      <button class="btn btn-primary btn-sm" data-modal-open="<?= htmlspecialchars($actionModal, ENT_QUOTES, 'UTF-8') ?>" style="margin-top:.25rem;"><?= htmlspecialchars($actionText, ENT_QUOTES, 'UTF-8') ?></button>
    <?php endif; ?>
  <?php endif; ?>
</div>
