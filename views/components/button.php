<?php
/**
 * Button Component
 * Usage: component('button', ['variant'=>'primary','icon'=>'scan','text'=>'Scan','type'=>'button','attributes'=>'data-modal-open="..."'])
 */
$variant    = $props['variant']    ?? 'primary';   // primary | secondary | ghost | destructive | on-dark
$iconName   = $props['icon']       ?? null;
$text       = $props['text']       ?? 'Button';
$type       = $props['type']       ?? 'button';
$attributes = $props['attributes'] ?? '';
$size       = $props['size']       ?? '';           // sm | lg | icon | ''
$href       = $props['href']       ?? null;

$variantClass = match($variant) {
  'secondary'   => 'btn-secondary',
  'ghost'       => 'btn-ghost',
  'destructive' => 'btn-destructive',
  'on-dark'     => 'btn-on-dark',
  default       => 'btn-primary',
};

$sizeClass = match($size) {
  'sm'   => 'btn-sm',
  'lg'   => 'btn-lg',
  'icon' => 'btn-icon',
  default => '',
};

$iconPaths = [
  'scan'   => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18M15 3v18"></path>',
  'plus'   => '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>',
  'edit'   => '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>',
  'trash'  => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>',
  'print'  => '<polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect>',
  'export' => '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line>',
  'filter' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>',
  'download'=> '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>',
];

$iconSvg = '';
if ($iconName && isset($iconPaths[$iconName])) {
  $iconSvg = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;">' . $iconPaths[$iconName] . '</svg>';
}
?>

<?php if ($href): ?>
<a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $variantClass ?> <?= $sizeClass ?>" <?= $attributes ?>>
  <?= $iconSvg ?>
  <?php if ($size !== 'icon'): ?>
    <span class="btn-text"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></span>
  <?php endif; ?>
</a>
<?php else: ?>
<button type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $variantClass ?> <?= $sizeClass ?>" <?= $attributes ?>>
  <?= $iconSvg ?>
  <?php if ($size !== 'icon'): ?>
    <span class="btn-text"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></span>
  <?php endif; ?>
</button>
<?php endif; ?>
