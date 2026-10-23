<?php
/**
 * Main Application Layout Shell
 * Usage: $title, $breadcrumb, $breadcrumbSub, $role are set by the calling view
 * Include via: require layout('shell')
 */

$appName = htmlspecialchars($config['name'] ?? 'SLSU-Alabat IMS', ENT_QUOTES, 'UTF-8');
$role    = $_SESSION['role'] ?? 'guest';
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$userInitials = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1) . (substr(strstr($_SESSION['user_name'] ?? '', ' '), 1, 1) ?? ''));
$pageTitle = htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8');
$breadcrumb = htmlspecialchars($breadcrumb ?? 'Dashboard', ENT_QUOTES, 'UTF-8');

// Pre-calculate live badges via centralized NotificationService
$topDb = \App\Config\Database::getConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);
$badges = \App\Services\NotificationService::getSidebarBadges($topDb, $userId, $role);
$overdueCount = $badges['overdue'] ?? 0;

// Role-based sidebar menus (dynamic and environment-agnostic)
$menus = [
  'admin' => [
    ['label' => 'Overview',          'href' => url('/dashboard'),   'icon' => 'grid',           'badge' => null],
    ['label' => 'User Management',   'href' => url('/admin/users'), 'icon' => 'users',          'badge' => !empty($badges['pending']) ? $badges['pending'] : null],
    ['label' => 'Audit Trail',       'href' => url('/admin/audit'), 'icon' => 'file-text',      'badge' => null],
    ['label' => 'Settings',          'href' => url('/settings'),    'icon' => 'settings',       'badge' => null],
  ],
  'custodian' => [
    ['label' => 'Dashboard',           'href' => url('/dashboard'),       'icon' => 'grid',           'badge' => null],
    ['label' => 'Scan & Transact',     'href' => url('/scan'),            'icon' => 'scan',           'badge' => !empty($badges['pending_borrows']) ? $badges['pending_borrows'] : null],
    ['label' => 'Asset Catalog',       'href' => url('/inventory'),       'icon' => 'package',        'badge' => !empty($badges['iar_incoming']) ? $badges['iar_incoming'] : null],
    ['label' => 'Borrow Logs',         'href' => url('/custodian/loans'), 'icon' => 'clipboard-list', 'badge' => !empty($badges['overdue']) ? $badges['overdue'] : (!empty($badges['pending_borrows']) ? $badges['pending_borrows'] : null)],
    ['label' => 'Damage Clearances',   'href' => url('/clearances'),      'icon' => 'shield',         'badge' => !empty($badges['holds']) ? $badges['holds'] : null],
    ['label' => 'Reports & Analytics', 'href' => url('/reports'),         'icon' => 'bar-chart-2',    'badge' => null],
    ['label' => 'Disposals',           'href' => url('/disposal'),        'icon' => 'trash-2',        'badge' => null],
    ['label' => 'Settings',            'href' => url('/settings'),        'icon' => 'settings',       'badge' => null],
  ],
  'executive_admin' => [
    ['label' => 'Executive Overview',  'href' => url('/dashboard'),       'icon' => 'grid',           'badge' => null],
    ['label' => 'Asset Catalog',       'href' => url('/inventory'),       'icon' => 'package',        'badge' => null],
    ['label' => 'Borrow Logs',         'href' => url('/custodian/loans'), 'icon' => 'clipboard-list', 'badge' => !empty($badges['overdue']) ? $badges['overdue'] : null],
    ['label' => 'Disposal Archive',    'href' => url('/disposal'),        'icon' => 'trash-2',        'badge' => null],
    ['label' => 'Damage Clearances',   'href' => url('/clearances'),      'icon' => 'shield',         'badge' => !empty($badges['holds']) ? $badges['holds'] : null],
    ['label' => 'Audit Logs',          'href' => url('/admin/audit'),     'icon' => 'file-text',      'badge' => null],
    ['label' => 'Settings',            'href' => url('/settings'),        'icon' => 'settings',       'badge' => null],
  ],
  'faculty' => [
    ['label' => 'My Portal',          'href' => url('/dashboard'),     'icon' => 'grid',           'badge' => null],
    ['label' => 'Scan & Verify',      'href' => url('/scan'),          'icon' => 'scan',           'badge' => null],
    ['label' => 'Asset Catalog',       'href' => url('/inventory'),     'icon' => 'package',        'badge' => null],
    ['label' => 'My Records',         'href' => url('/records'),       'icon' => 'clipboard-list', 'badge' => !empty($badges['overdue']) ? $badges['overdue'] : null],
    ['label' => 'My Clearances',      'href' => url('/my-clearances'), 'icon' => 'shield',         'badge' => !empty($badges['holds']) ? $badges['holds'] : null],
    ['label' => 'Settings',           'href' => url('/settings'),      'icon' => 'settings',       'badge' => null],
  ],
  'student' => [
    ['label' => 'Lab Portal',         'href' => url('/dashboard'),     'icon' => 'grid',           'badge' => null],
    ['label' => 'Scan Equipment',     'href' => url('/scan'),          'icon' => 'camera',         'badge' => null],
    ['label' => 'Asset Catalog',       'href' => url('/inventory'),     'icon' => 'package',        'badge' => null],
    ['label' => 'My Records',         'href' => url('/records'),       'icon' => 'clipboard-list', 'badge' => !empty($badges['overdue']) ? $badges['overdue'] : null],
    ['label' => 'My Clearances',      'href' => url('/my-clearances'), 'icon' => 'shield',         'badge' => !empty($badges['holds']) ? $badges['holds'] : null],
    ['label' => 'Settings',           'href' => url('/settings'),      'icon' => 'settings',       'badge' => null],
  ],
  'inspector' => [
    ['label' => 'Inspector Desk',     'href' => url('/dashboard'),             'icon' => 'grid',     'badge' => null],
    ['label' => 'Gate Pass Desk',     'href' => url('/inspector/gate-passes'), 'icon' => 'printer',  'badge' => null],
    ['label' => 'IAR Inspection',     'href' => url('/inspector/iar'),         'icon' => 'package',  'badge' => !empty($badges['iar_pending']) ? $badges['iar_pending'] : null],
    ['label' => 'Scan & Verify',      'href' => url('/scan'),                  'icon' => 'scan',     'badge' => null],
    ['label' => 'Settings',           'href' => url('/settings'),              'icon' => 'settings', 'badge' => null],
  ],
];

$roleLabels = [
  'admin'           => 'System Administrator',
  'custodian'       => 'Property Custodian',
  'executive_admin' => 'Executive Admin',
  'inspector'       => 'Local Inspector',
  'faculty'         => 'Faculty',
  'student'         => 'Student',
];

$currentMenu = $menus[$role] ?? [];
$roleLabel   = $roleLabels[$role] ?? 'User';

// SVG icon helper with explicit sizing fallbacks
if (!function_exists('icon')) {
  function icon(string $name): string {
    $icons = [
      'grid'          => '<path d="M3 3h7v7H3z"></path><path d="M14 3h7v7h-7z"></path><path d="M3 14h7v7H3z"></path><path d="M14 14h7v7h-7z"></path>',
      'users'         => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
      'package'       => '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
      'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
      'settings'      => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
      'scan'          => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="M3 9h18"></path><path d="M3 15h18"></path><path d="M9 3v18"></path><path d="M15 3v18"></path>',
      'printer'       => '<polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect>',
      'user-check'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline>',
      'trash-2'       => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>',
      'bar-chart-2'   => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
      'trending-up'   => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline>',
      'layers'        => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
      'file-text'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
      'clipboard-list'=> '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><line x1="12" y1="11" x2="16" y2="11"></line><line x1="12" y1="16" x2="16" y2="16"></line><line x1="8" y1="11" x2="8.01" y2="11"></line><line x1="8" y1="16" x2="8.01" y2="16"></line>',
      'clock'         => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
      'camera'        => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle>',
      'bell'          => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
      'log-out'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
      'menu'          => '<line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line>',
      'chevron-right' => '<polyline points="9 18 15 12 9 6"></polyline>',
      'chevron-left'  => '<polyline points="15 18 9 12 15 6"></polyline>',
      'search'        => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
      'alert-triangle'=> '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
      'peso-sign'     => '<path d="M8 19V5h3.5a4.5 4.5 0 1 1 0 9H8"></path><line x1="6" y1="8" x2="18" y2="8"></line><line x1="6" y1="11" x2="18" y2="11"></line>',
      'peso'          => '<path d="M8 19V5h3.5a4.5 4.5 0 1 1 0 9H8"></path><line x1="6" y1="8" x2="18" y2="8"></line><line x1="6" y1="11" x2="18" y2="11"></line>',
      'dollar-sign'   => '<path d="M8 19V5h3.5a4.5 4.5 0 1 1 0 9H8"></path><line x1="6" y1="8" x2="18" y2="8"></line><line x1="6" y1="11" x2="18" y2="11"></line>', // Auto-localized to Philippine Peso (₱)
      'coins'         => '<ellipse cx="12" cy="6" rx="8" ry="3"></ellipse><path d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6"></path><path d="M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"></path>',
      'banknote'      => '<rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path>',
      'wallet'        => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>',
      'ims-logo'      => '<path d="M4 6h16M4 12h16M4 18h16" stroke-width="2" stroke-linecap="round"/>',
    ];

    $path = $icons[$name] ?? '<circle cx="12" cy="12" r="10"></circle>';
    return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="max-width:24px;max-height:24px;display:inline-block;">' . $path . '</svg>';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <?php $initialLastEventId = \App\Services\EventDispatcher::getLatestEventId($topDb); ?>
  <meta name="last-event-id" content="<?= $initialLastEventId ?>">
  <title><?= $pageTitle ?> — <?= $appName ?></title>
  <meta name="description" content="SLSU-Alabat Campus Inventory Management and Properties Tracking System">
  <meta name="robots" content="noindex, nofollow">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Design System (Dynamic Asset Resolver) -->
  <link rel="stylesheet" href="<?= asset('/css/custom.css') ?>">

  <!-- Client-Side JS API Bridge -->
  <script>
    window.APP_CONFIG = {
      baseUrl: "<?= rtrim(url(), '/') ?>",
      lastEventId: <?= $initialLastEventId ?>,
      userRole: "<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>",
      userId: <?= $userId ?>,
      isRemembered: <?= (!empty($_SESSION['is_remembered']) || !empty($_COOKIE[\App\Helpers\RememberMe::COOKIE_NAME] ?? null)) ? 'true' : 'false' ?>
    };
    window.appUrl = function(path) {
      const clean = path.startsWith('/') ? path : '/' + path;
      return window.APP_CONFIG.baseUrl + clean;
    };
  </script>

  <!-- Tailwind (CDN for rapid utility use) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: { DEFAULT: '#166534', deep: '#14532d', press: '#052e16', soft: '#22c55e', subdued: '#dcfce7' },
            ink: { DEFAULT: '#0f291e', secondary: '#244234', mute: '#5c7367' },
            brand: { dark: '#0a2318' },
            gold: { DEFAULT: '#d97706', light: '#fef3c7', deep: '#b45309' }
          }
        }
      }
    }
  </script>
</head>
<body>

<!-- ─── Mobile Nav Overlay ─────────────────────────────────── -->
<div id="mobile-nav-overlay" class="mobile-nav-overlay"></div>

<!-- ─── Sidebar ────────────────────────────────────────────── -->
<aside id="app-sidebar" class="sidebar" role="navigation" aria-label="Main navigation">

  <!-- Brand -->
  <div class="sidebar-brand" style="justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:var(--space-md);overflow:hidden;">
      <div class="sidebar-brand-logo" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:transparent;border-radius:var(--radius-pill);flex-shrink:0;overflow:hidden;">
        <img src="<?= asset('/images/slsu-logo.png') ?>" alt="SLSU-Alabat Logo" style="width:100%;height:100%;object-fit:contain;display:block;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.25));">
      </div>
      <div class="sidebar-brand-text">
        <div class="sidebar-brand-name">SLSU-Alabat IMS</div>
        <div class="sidebar-brand-sub">Inventory System</div>
      </div>
    </div>
    <button id="mobile-drawer-close-btn" class="topbar-icon-btn" style="color:rgba(255,255,255,0.7);width:32px;height:32px;flex-shrink:0;" aria-label="Close navigation">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
  </div>

  <!-- Collapse toggle (desktop) -->
  <button id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle sidebar" title="Toggle sidebar">
    <?= icon('chevron-left') ?>
  </button>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Navigation</div>

    <?php 
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    $curPath = parse_url($currentUri, PHP_URL_PATH) ?? '';
    foreach ($currentMenu as $item): 
      $isDisabled = !empty($item['disabled']);
      $isActive = false;
      if (!$isDisabled && $item['href'] !== '#') {
        $itemPath = parse_url($item['href'], PHP_URL_PATH) ?? '';
        if ($itemPath === url('/dashboard') || $itemPath === url('/inspector')) {
          $isActive = in_array($curPath, [url('/dashboard'), url('/inspector'), url('/'), url('')], true);
        } else {
          $isActive = ($curPath === $itemPath) || (str_starts_with($curPath, $itemPath . '/'));
        }
      }
      $badge = $item['badge'] ?? null;
      $isSoon = ($badge === 'Soon');
    ?>
      <a href="<?= $isDisabled ? 'javascript:void(0);' : htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
         class="sidebar-nav-item<?= $isActive ? ' active' : '' ?><?= $isDisabled ? ' disabled' : '' ?>"
         title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>"
         <?= $isDisabled ? 'style="opacity:0.55;cursor:not-allowed;pointer-events:none;" tabindex="-1" aria-disabled="true"' : '' ?>>
        <span class="sidebar-nav-icon"><?= icon($item['icon']) ?></span>
        <span class="sidebar-nav-label"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($badge !== null && $badge !== ''): ?>
          <?php if ($isSoon): ?>
            <span class="sidebar-badge" style="background:rgba(255,255,255,0.12);color:rgba(255,255,255,0.85);font-size:9px;font-weight:600;letter-spacing:0.5px;padding:2px 6px;text-transform:uppercase;border:1px solid rgba(255,255,255,0.15);">Soon</span>
          <?php else: ?>
            <span class="sidebar-badge"><?= htmlspecialchars((string)$badge, ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- User Footer -->
  <div class="sidebar-footer">
    <div class="dropdown">
      <div class="sidebar-user" data-dropdown>
        <div class="sidebar-avatar">
          <?php if (!empty($_SESSION['avatar_path'])): ?>
            <img src="<?= asset($_SESSION['avatar_path']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-pill);display:block;">
          <?php else: ?>
            <?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?>
          <?php endif; ?>
        </div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name"><?= $userName ?></div>
          <div class="sidebar-user-role"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </div>
      <div class="dropdown-menu" style="bottom:100%;top:auto;right:0;left:0;transform-origin:bottom right;">
        <a href="<?= url('/settings') ?>" class="dropdown-item">
          <?= icon('settings') ?> Profile Settings
        </a>
        <div class="dropdown-divider"></div>
        <a href="<?= url('/logout') ?>" class="dropdown-item danger">
          <?= icon('log-out') ?> Sign Out
        </a>
      </div>
    </div>
  </div>
</aside>

<!-- ─── Main Wrapper ────────────────────────────────────────── -->
<div class="main-wrapper">

  <!-- Topbar -->
  <header class="topbar" role="banner">

    <!-- Mobile hamburger -->
    <button id="mobile-menu-btn" class="topbar-icon-btn" aria-label="Open navigation">
      <?= icon('menu') ?>
    </button>

    <!-- Breadcrumb -->
    <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
      <a href="<?= url('/dashboard') ?>" class="topbar-breadcrumb-item" style="text-decoration:none;color:inherit;"><?= $appName ?></a>
      <span class="topbar-breadcrumb-sep">/</span>
      <span class="topbar-breadcrumb-item active"><?= $breadcrumb ?></span>
    </nav>

    <!-- Topbar Actions -->
    <div class="topbar-actions">
      <?php
      if (!isset($overdueCount)) {
          try {
              $topDb = \App\Config\Database::getConnection();
              if ($role === 'student' || $role === 'faculty') {
                  $overdueCount = \App\Models\Transaction::hasOverdueLoans($topDb, (int)($_SESSION['user_id'] ?? 0)) ? 1 : 0;
              } else {
                  $overdueCount = \App\Models\Transaction::getOverdueCount($topDb);
              }
          } catch (\Throwable $e) {
              $overdueCount = 0;
          }
      }
      ?>
      <?php if (!empty($overdueCount) && (int)$overdueCount > 0): ?>
        <a href="<?= $role === 'custodian' ? url('/custodian/loans') : ($role === 'admin' ? url('/admin/audit') : url('/dashboard')) ?>" class="overdue-pill" style="text-decoration:none;">
          <span class="overdue-pill-dot"></span>
          <?= (int)$overdueCount ?> Overdue
        </a>
      <?php endif; ?>

      <!-- Notifications Hub Dropdown -->
      <div class="dropdown notif-hub-dropdown" id="notif-dropdown">
        <button class="topbar-icon-btn notif-bell-btn" id="notif-bell-btn" aria-label="Notifications" title="System Notifications" data-dropdown>
          <?= icon('bell') ?>
          <span class="notif-badge-pill" id="notif-unread-badge" style="<?= ($badges['unread_notifs'] ?? 0) > 0 ? '' : 'display:none;' ?>">
            <?= htmlspecialchars((string)($badges['unread_notifs'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
          </span>
        </button>

        <div class="dropdown-menu notif-menu-panel" id="notif-menu-panel" role="region" aria-label="Notifications List">
          <div class="notif-header">
            <div class="notif-header-left">
              <span class="notif-header-title">Notifications</span>
              <span class="notif-header-count" id="notif-header-count"><?= ($badges['unread_notifs'] ?? 0) > 0 ? ($badges['unread_notifs'] . ' unread') : 'All caught up' ?></span>
            </div>
            <button type="button" class="notif-mark-read-btn" id="notif-mark-all-btn" onclick="NotificationHub.markAllRead(event)">
              Mark all read
            </button>
          </div>

          <div class="notif-list" id="notif-list-container" tabindex="0">
            <div class="notif-loading-state">
              <div class="notif-skeleton-row"></div>
              <div class="notif-skeleton-row"></div>
            </div>
          </div>

          <div class="notif-footer">
            <span class="notif-footer-note">Auto-pruned to last 10 items</span>
            <a href="<?= url('/settings') ?>" class="notif-footer-link">Preferences &rarr;</a>
          </div>
        </div>
      </div>

      <!-- Quick Search -->
      <button class="topbar-icon-btn" aria-label="Search" title="Search assets" data-modal-open="item-lookup-modal">
        <?= icon('search') ?>
      </button>
    </div>
  </header>

  <!-- Main Content -->
  <main class="main-content animate-fade-in-up" id="main-content" role="main">
