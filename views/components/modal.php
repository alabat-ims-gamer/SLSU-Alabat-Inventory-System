<?php
/**
 * Universal Modal Component
 * Usage: component('modal', ['id'=>'...','title'=>'...','size'=>'md','bodyContent'=>'...','actionSlot'=>'...'])
 */
$id          = $props['id']          ?? 'modal-' . rand(1000,9999);
$title       = $props['title']       ?? 'Dialog';
$size        = $props['size']        ?? 'md';   // sm | md | lg | xl
$bodyContent = $props['bodyContent'] ?? '';
$actionSlot  = $props['actionSlot']  ?? '';
$subtitle    = $props['subtitle']    ?? null;
$type        = $props['type']        ?? 'center'; // center | slideover
$isDestructive = $props['isDestructive'] ?? false;

if ($type === 'slideover'):
?>
<div id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="slideover-overlay" role="dialog" aria-modal="true" aria-labelledby="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>-title">
  <div class="slideover-panel">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if ($subtitle): ?><div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      </div>
      <button class="modal-close" data-modal-close="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body"><?= $bodyContent ?></div>
    <?php if ($actionSlot): ?>
    <div class="modal-footer"><?= $actionSlot ?></div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<div id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>-title">
  <div class="modal-panel <?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if ($subtitle): ?><div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      </div>
      <button class="modal-close" data-modal-close="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body"><?= $bodyContent ?></div>
    <?php if ($actionSlot): ?>
    <div class="modal-footer">
      <button class="btn btn-ghost btn-sm" data-modal-close="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">Cancel</button>
      <?= $actionSlot ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
