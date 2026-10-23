<?php
/**
 * Local Inspector Dashboard Overview
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 */

$title = 'Local Inspector Dashboard';
$breadcrumb = 'Inspector / Overview';

require dirname(__DIR__) . '/layouts/shell.php';
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="section-header" style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 class="section-title" style="font-size:24px;font-weight:400;color:var(--color-ink,#0f291e);letter-spacing:-0.4px;margin:0 0 4px 0;">
      Local Inspector Command Center
    </h1>
    <div class="section-subtitle" style="font-size:14px;color:var(--color-ink-mute,#5c7367);font-weight:300;">
      Official oversight for campus equipment gate clearances and Form AFA-SAF-1.016 delivery certifications
    </div>
  </div>

  <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
    <a href="<?= url('/inspector/gate-passes?action=new') ?>" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 16px;background:var(--color-primary,#166534);color:#ffffff;font-size:14px;text-decoration:none;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Issue Gate Pass
    </a>
    <a href="<?= url('/inspector/iar?action=new') ?>" class="btn" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 16px;background:#ffffff;border:1px solid var(--color-primary,#166534);color:var(--color-primary,#166534);font-size:14px;text-decoration:none;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>
      </svg>
      Encode New IAR
    </a>
    <a href="<?= url('/scan') ?>" class="btn" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 16px;background:var(--color-canvas-soft,#f2f7f4);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));color:var(--color-ink,#0f291e);font-size:14px;text-decoration:none;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="M3 9h18"></path><path d="M3 15h18"></path><path d="M9 3v18"></path><path d="M15 3v18"></path>
      </svg>
      Scan Barcode
    </a>
  </div>
</div>

<!-- ── KPI Summary Cards ───────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem;margin-bottom:1.5rem;">
  <?php
  $statCards = [
    [
      'title'     => 'Gate Passes Issued Today',
      'value'     => number_format((int)($metrics['active_passes_today'] ?? 0)),
      'icon'      => 'printer',
      'color'     => 'emerald',
      'trend'     => 'Active clearances',
      'trend_dir' => 'up'
    ],
    [
      'title'     => 'IAR Pending Signatures',
      'value'     => number_format((int)($metrics['pending_signatures'] ?? 0)),
      'icon'      => 'file-text',
      'color'     => 'amber',
      'trend'     => 'Requires wet-signed scan',
      'trend_dir' => 'down'
    ],
    [
      'title'     => 'Transmitted to Custodian',
      'value'     => number_format((int)($metrics['transmitted_count'] ?? 0)),
      'icon'      => 'package',
      'color'     => 'indigo',
      'trend'     => 'Awaiting asset tagging',
      'trend_dir' => 'up'
    ],
    [
      'title'     => 'Physical Items Inspected (YTD)',
      'value'     => number_format((int)($metrics['items_inspected_ytd'] ?? 0)),
      'icon'      => 'shield',
      'color'     => 'emerald',
      'trend'     => 'Verified deliveries',
      'trend_dir' => 'up'
    ],
  ];

  foreach ($statCards as $stat):
    $props = $stat;
    require dirname(__DIR__) . '/components/stat-card.php';
  endforeach;
  ?>
</div>

<!-- ── Dual Operational Stream (Recent Gate Passes & Recent IARs) ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:stretch;">

  <!-- Column A: Recent Gate Passes -->
  <div class="glass-card" style="background:var(--color-canvas-glass-heavy,rgba(255,255,255,0.95));border:1px solid var(--color-hairline,rgba(22,101,52,0.12));border-radius:12px;overflow:hidden;box-shadow:var(--shadow-1);display:flex;flex-direction:column;height:100%;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:flex;justify-content:space-between;align-items:center;min-height:74px;box-sizing:border-box;">
      <div>
        <h2 style="font-size:16px;font-weight:600;color:var(--color-ink,#0f291e);margin:0;line-height:1.3;">Recent Gate Pass Permits</h2>
        <div style="font-size:12px;color:var(--color-ink-mute,#5c7367);font-weight:400;margin-top:2px;">Authorized off-campus item movement</div>
      </div>
      <a href="<?= url('/inspector/gate-passes') ?>" style="font-size:13px;color:var(--color-primary,#166534);text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:4px;flex-shrink:0;">
        View All
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </a>
    </div>

    <div style="overflow-x:auto;flex:1;display:flex;flex-direction:column;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;font-feature-settings:'tnum';table-layout:fixed;">
        <colgroup>
          <col style="width:25%;">
          <col style="width:40%;">
          <col style="width:20%;">
          <col style="width:15%;">
        </colgroup>
        <thead>
          <tr style="background:var(--color-canvas-soft,#f2f7f4);color:var(--color-ink-secondary,#244234);border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));text-align:left;height:42px;">
            <th style="padding:10px 16px;font-weight:500;font-size:12px;letter-spacing:0.02em;">Pass No.</th>
            <th style="padding:10px 16px;font-weight:500;font-size:12px;letter-spacing:0.02em;">Requisitioner</th>
            <th style="padding:10px 16px;font-weight:500;font-size:12px;letter-spacing:0.02em;">Items</th>
            <th style="padding:10px 16px;font-weight:500;font-size:12px;letter-spacing:0.02em;text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentPasses)): ?>
            <tr>
              <td colspan="4" style="padding:48px 16px;text-align:center;color:var(--color-ink-mute,#5c7367);height:310px;box-sizing:border-box;">
                <div style="margin-bottom:8px;">
                  <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--color-ink-mute,#5c7367)" stroke-width="1.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                </div>
                No gate passes issued yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentPasses as $gp): ?>
              <tr class="unified-table-row" style="border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.08));height:62px;box-sizing:border-box;">
                <td style="padding:10px 16px;vertical-align:middle;">
                  <div style="font-weight:600;color:var(--color-primary,#166534);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($gp['gate_pass_no'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);font-weight:400;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                    <?= date('M d, Y', strtotime($gp['created_at'])) ?>
                  </div>
                </td>
                <td style="padding:10px 16px;vertical-align:middle;">
                  <div style="color:var(--color-ink,#0f291e);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($gp['requisitioner_name'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($gp['requisitioner_name'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;" title="<?= htmlspecialchars($gp['requisitioning_office'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($gp['requisitioning_office'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </td>
                <td style="padding:10px 16px;vertical-align:middle;color:var(--color-ink-secondary,#244234);">
                  <div style="font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= (int)$gp['item_count'] ?> item<?= (int)$gp['item_count'] === 1 ? '' : 's' ?>
                  </div>
                  <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                    ₱ <?= number_format((float)$gp['total_valuation'], 2) ?>
                  </div>
                </td>
                <td style="padding:10px 16px;vertical-align:middle;text-align:right;">
                  <a href="<?= url('/inspector/gate-passes/print?id=' . $gp['id']) ?>" target="_blank" class="btn btn-sm" style="display:inline-flex;align-items:center;gap:4px;border-radius:9999px;padding:4px 10px;background:var(--color-canvas-soft,#f2f7f4);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));color:var(--color-primary,#166534);text-decoration:none;font-size:12px;font-weight:500;">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Print
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php 
            $passCount = count($recentPasses);
            if ($passCount < 5):
              for ($i = $passCount; $i < 5; $i++):
            ?>
              <tr style="border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.06));height:62px;box-sizing:border-box;">
                <td colspan="4" style="padding:10px 16px;text-align:center;color:var(--color-ink-mute,#5c7367);font-size:12px;opacity:0.35;">
                  —
                </td>
              </tr>
            <?php 
              endfor;
            endif; 
            ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Column B: Recent Inspection & Acceptance Reports -->
  <div class="glass-card" style="background:var(--color-canvas-glass-heavy,rgba(255,255,255,0.95));border:1px solid var(--color-hairline,rgba(22,101,52,0.12));border-radius:12px;overflow:hidden;box-shadow:var(--shadow-1);display:flex;flex-direction:column;height:100%;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:flex;justify-content:space-between;align-items:center;min-height:74px;box-sizing:border-box;">
      <div>
        <h2 style="font-size:16px;font-weight:600;color:var(--color-ink,#0f291e);margin:0;line-height:1.3;">Recent IAR Reports (Form AFA-SAF-1.016)</h2>
        <div style="font-size:12px;color:var(--color-ink-mute,#5c7367);font-weight:400;margin-top:2px;">Incoming shipments and delivery intake certifications</div>
      </div>
      <a href="<?= url('/inspector/iar') ?>" style="font-size:13px;color:var(--color-primary,#166534);text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:4px;flex-shrink:0;">
        View All
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </a>
    </div>

    <div style="overflow-x:auto;flex:1;display:flex;flex-direction:column;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;font-feature-settings:'tnum';table-layout:fixed;">
        <colgroup>
          <col style="width:25%;">
          <col style="width:40%;">
          <col style="width:20%;">
          <col style="width:15%;">
        </colgroup>
        <thead>
          <tr style="background:var(--color-canvas-soft,#f2f7f4);color:var(--color-ink-secondary,#244234);border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));text-align:left;height:42px;">
            <th style="padding:10px 16px;font-weight:500;font-size:12px;letter-spacing:0.02em;">IAR No.</th>
            <th style="padding:10px 16px;font-weight:500;font-size:12px;letter-spacing:0.02em;">Supplier</th>
            <th style="padding:10px 16px;font-weight:500;font-size:12px;letter-spacing:0.02em;">Status</th>
            <th style="padding:10px 16px;font-weight:500;font-size:12px;letter-spacing:0.02em;text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentIars)): ?>
            <tr>
              <td colspan="4" style="padding:48px 16px;text-align:center;color:var(--color-ink-mute,#5c7367);height:310px;box-sizing:border-box;">
                <div style="margin-bottom:8px;">
                  <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--color-ink-mute,#5c7367)" stroke-width="1.5"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
                No Inspection Reports encoded yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentIars as $iar): ?>
              <?php
              $statusBadge = match($iar['status']) {
                'PENDING_SIGNATURES' => ['bg' => 'var(--color-gold-light,#fef3c7)', 'fg' => 'var(--color-gold-deep,#b45309)', 'label' => 'Pending Signatures'],
                'TRANSMITTED'        => ['bg' => 'var(--color-ocean-light,#e0f2fe)', 'fg' => 'var(--color-ocean,#0284c7)', 'label' => 'Transmitted'],
                'REGISTERED'         => ['bg' => 'var(--color-primary-bg-subdued-hover,#dcfce7)', 'fg' => 'var(--color-primary-deep,#14532d)', 'label' => 'Registered'],
                default              => ['bg' => 'var(--color-canvas-soft,#f2f7f4)', 'fg' => 'var(--color-ink-mute,#5c7367)', 'label' => $iar['status']],
              };
              ?>
              <tr class="unified-table-row" style="border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.08));height:62px;box-sizing:border-box;">
                <td style="padding:10px 16px;vertical-align:middle;">
                  <div style="font-weight:600;color:var(--color-primary,#166534);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($iar['iar_no'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);font-weight:400;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                    PO: <?= htmlspecialchars($iar['po_no'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </td>
                <td style="padding:10px 16px;vertical-align:middle;">
                  <div style="color:var(--color-ink,#0f291e);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($iar['supplier_name'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($iar['supplier_name'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                  <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;" title="<?= htmlspecialchars($iar['office_dept'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($iar['office_dept'], ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </td>
                <td style="padding:10px 16px;vertical-align:middle;">
                  <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:500;background:<?= $statusBadge['bg'] ?>;color:<?= $statusBadge['fg'] ?>;white-space:nowrap;">
                    <?= $statusBadge['label'] ?>
                  </span>
                  <div style="font-size:11px;color:var(--color-ink-mute,#5c7367);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                    <?= (int)($iar['item_count'] ?? 1) ?> item<?= (int)($iar['item_count'] ?? 1) === 1 ? '' : 's' ?>
                  </div>
                </td>
                <td style="padding:10px 16px;vertical-align:middle;text-align:right;">
                  <a href="<?= url('/inspector/iar/print?id=' . $iar['id']) ?>" target="_blank" class="btn btn-sm" style="display:inline-flex;align-items:center;gap:4px;border-radius:9999px;padding:4px 10px;background:var(--color-canvas-soft,#f2f7f4);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));color:var(--color-primary,#166534);text-decoration:none;font-size:12px;font-weight:500;">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Print
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php 
            $iarCount = count($recentIars);
            if ($iarCount < 5):
              for ($i = $iarCount; $i < 5; $i++):
            ?>
              <tr style="border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.06));height:62px;box-sizing:border-box;">
                <td colspan="4" style="padding:10px 16px;text-align:center;color:var(--color-ink-mute,#5c7367);font-size:12px;opacity:0.35;">
                  —
                </td>
              </tr>
            <?php 
              endfor;
            endif; 
            ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Responsive breakdown style for mobile -->
<style>
.unified-table-row:hover {
  background: var(--color-canvas-soft, rgba(242, 247, 244, 0.6));
  transition: background 0.15s ease-in-out;
}
@media (max-width: 860px) {
  div[style*="grid-template-columns:1fr 1fr"] {
    grid-template-columns: 1fr !important;
  }
}
</style>

<!-- ── Shared Gate Pass Issuance Modal ──────────────────────── -->
<?php require __DIR__ . '/components/gate-pass-modal.php'; ?>

<?php require dirname(__DIR__) . '/layouts/shell-footer.php'; ?>
