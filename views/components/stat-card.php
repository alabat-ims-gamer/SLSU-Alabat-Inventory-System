<?php
/**
 * Stat Card Component
 * Usage: component('stat-card', ['title'=>'...','value'=>'...','icon'=>'...','trend'=>'+12%','trend_dir'=>'up','color'=>'indigo','link'=>'/...'])
 */
$title     = $props['title']     ?? 'Metric';
$value     = $props['value']     ?? '0';
$icon      = $props['icon']      ?? 'package';
$trend     = $props['trend']     ?? null;
$trend_dir = $props['trend_dir'] ?? 'up';
$color     = $props['color']     ?? 'indigo';
$link      = $props['link']      ?? null;
$subtitle  = $props['subtitle']  ?? null;

$trendIcon = $trend_dir === 'up'
  ? '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline-block;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>'
  : '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:inline-block;"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>';

$iconSvgs = [
  'package'       => '<path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
  'users'         => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"></path>',
  'clipboard-list'=> '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect><line x1="12" y1="11" x2="16" y2="11"></line><line x1="12" y1="16" x2="16" y2="16"></line><line x1="8" y1="11" x2="8.01" y2="11"></line><line x1="8" y1="16" x2="8.01" y2="16"></line>',
  'alert-triangle'=> '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
  'trending-up'   => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline>',
  'bar-chart-2'   => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
  'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
  'tool'          => '<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"></path>',
  'clock'         => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
  'peso-sign'     => '<path d="M8 19V5h3.5a4.5 4.5 0 1 1 0 9H8"></path><line x1="6" y1="8" x2="18" y2="8"></line><line x1="6" y1="11" x2="18" y2="11"></line>',
  'peso'          => '<path d="M8 19V5h3.5a4.5 4.5 0 1 1 0 9H8"></path><line x1="6" y1="8" x2="18" y2="8"></line><line x1="6" y1="11" x2="18" y2="11"></line>',
  'dollar-sign'   => '<path d="M8 19V5h3.5a4.5 4.5 0 1 1 0 9H8"></path><line x1="6" y1="8" x2="18" y2="8"></line><line x1="6" y1="11" x2="18" y2="11"></line>', // Auto-localized to Philippine Peso (₱)
  'dollar'        => '<path d="M8 19V5h3.5a4.5 4.5 0 1 1 0 9H8"></path><line x1="6" y1="8" x2="18" y2="8"></line><line x1="6" y1="11" x2="18" y2="11"></line>',
  'coins'         => '<ellipse cx="12" cy="6" rx="8" ry="3"></ellipse><path d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6"></path><path d="M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"></path>',
  'banknote'      => '<rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path>',
  'wallet'        => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>',
  'layers'        => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
  'inbox'         => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>',
  'printer'       => '<polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect>',
  'file-text'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
];

$svgPath = $iconSvgs[$icon] ?? $iconSvgs['package'];
$kpiKey = $props['kpi_id'] ?? strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $title), '_'));
?>

<?php if ($link): ?>
<a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" class="stat-card cursor-pointer" style="text-decoration:none;" data-kpi="<?= htmlspecialchars($kpiKey, ENT_QUOTES, 'UTF-8') ?>">
<?php else: ?>
<div class="stat-card" data-kpi="<?= htmlspecialchars($kpiKey, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

  <div class="stat-card-header">
    <span class="stat-card-label"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
    <div class="stat-card-icon icon-<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;display:block;">
        <?= $svgPath ?>
      </svg>
    </div>
  </div>

  <div class="stat-card-value num" data-kpi-value="true"><?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?></div>

  <?php if ($trend || $subtitle): ?>
  <div class="stat-card-footer">
    <?php if ($trend): ?>
    <span class="stat-card-trend <?= htmlspecialchars($trend_dir, ENT_QUOTES, 'UTF-8') ?>">
      <?= $trendIcon ?>
      <span><?= htmlspecialchars($trend, ENT_QUOTES, 'UTF-8') ?></span>
      <?php if (!empty($props['compare'])): ?>
      <span class="stat-card-compare"><?= htmlspecialchars($props['compare'], ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
    </span>
    <?php endif; ?>
    <?php if ($subtitle): ?>
    <span class="stat-card-subtext" title="<?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?>
    </span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

<?php if ($link): ?>
</a>
<?php else: ?>
</div>
<?php endif; ?>
