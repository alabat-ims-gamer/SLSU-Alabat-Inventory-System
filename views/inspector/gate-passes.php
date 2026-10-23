<?php
/**
 * Gate Pass Desk View — Local Inspector Hub
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 * Follows stripe-DESIGN.md design guidelines and reuses views/components/.
 */

require dirname(__DIR__) . '/layouts/shell.php';

// Prepare columns for the data table
$columns = [
    ['key' => 'gate_pass_no',          'label' => 'Pass Number',     'sortable' => false],
    ['key' => 'requisitioner_name',    'label' => 'Requisitioner',   'sortable' => false],
    ['key' => 'requisitioning_office',  'label' => 'Office / Dept',   'sortable' => false],
    ['key' => 'item_count',            'label' => 'Items',           'sortable' => false],
    ['key' => 'total_valuation',       'label' => 'Book Valuation',  'sortable' => false],
    ['key' => 'created_at',            'label' => 'Issued On',       'sortable' => false],
    ['key' => 'actions',               'label' => 'Permit Action',   'sortable' => false],
];

// Transform rows for data table
$tableRows = [];
$totalValuationSum = 0.0;
$totalItemsSum = 0;

foreach ($passes as $p) {
    $totalValuationSum += (float)($p['total_valuation'] ?? 0.0);
    $totalItemsSum += (int)($p['item_count'] ?? 0);
    $printUrl = url('/inspector/gate-passes/print?id=' . $p['id']);

    $tableRows[] = [
        'gate_pass_no' => '
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="badge" style="font-family:monospace;font-weight:600;background:var(--color-primary-bg-subdued-hover);color:var(--color-primary);letter-spacing:0.02em;">
                    ' . htmlspecialchars($p['gate_pass_no'], ENT_QUOTES, 'UTF-8') . '
                </span>
            </div>',
        'requisitioner_name' => '
            <div style="font-weight:500;color:var(--color-ink);">' . htmlspecialchars($p['requisitioner_name'], ENT_QUOTES, 'UTF-8') . '</div>
            <div style="font-size:11px;color:var(--color-ink-mute);">' . htmlspecialchars(mb_strimwidth($p['purpose'], 0, 40, '...'), ENT_QUOTES, 'UTF-8') . '</div>',
        'requisitioning_office' => '
            <span style="font-size:13px;color:var(--color-ink);">' . htmlspecialchars($p['requisitioning_office'], ENT_QUOTES, 'UTF-8') . '</span>',
        'item_count' => '
            <span class="num" style="font-weight:500;">' . (int)$p['item_count'] . ' item' . ((int)$p['item_count'] === 1 ? '' : 's') . '</span>',
        'total_valuation' => '
            <span class="num" style="font-feature-settings:\'tnum\';font-weight:600;color:var(--color-ink);">₱ ' . number_format((float)$p['total_valuation'], 2) . '</span>',
        'created_at' => '
            <span style="font-size:12px;color:var(--color-ink-mute);">' . date('M d, Y h:i A', strtotime($p['created_at'])) . '</span>',
        'actions' => '
            <a href="' . htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;text-decoration:none;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span>Print Official PDF</span>
            </a>'
    ];
}
?>

<div class="content-wrap" style="max-width:1400px;margin:0 auto;padding:1.5rem 1rem;">

    <!-- Flash Notifications -->
    <?php if (!empty($_GET['success'])): ?>
    <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:0.75rem 1rem;border-radius:var(--radius-md);margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span><?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:0.75rem 1rem;border-radius:var(--radius-md);margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>

    <!-- Section Header & Primary CTA -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:24px;font-weight:600;color:var(--color-ink);margin:0;letter-spacing:-0.02em;">Gate Pass Desk</h1>
            <p style="font-size:13px;color:var(--color-ink-mute);margin:4px 0 0 0;">Inspect borrowed equipment, verify serial tags, and generate pre-filled official campus gate permits.</p>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <button type="button" class="btn btn-primary" onclick="openNewPassModal()" data-modal-open="issue-gatepass-modal" style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;box-shadow:0 1px 3px rgba(22,101,52,0.2);">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Issue New Gate Pass</span>
            </button>
        </div>
    </div>

    <!-- Metric Overview Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem;margin-bottom:1.5rem;">
        <?php component('stat-card', [
            'title'    => 'Total Permits Issued',
            'value'    => $total,
            'icon'     => 'printer',
            'color'    => 'indigo',
            'subtitle' => 'Official Gate Passes on record'
        ]); ?>

        <?php component('stat-card', [
            'title'    => 'Total Items Bundled',
            'value'    => $totalItemsSum,
            'icon'     => 'package',
            'color'    => 'emerald',
            'subtitle' => 'Physical assets authorized off-campus'
        ]); ?>

        <?php component('stat-card', [
            'title'    => 'Certified Asset Valuation',
            'value'    => '₱ ' . number_format($totalValuationSum, 2),
            'icon'     => 'peso-sign',
            'color'    => 'gold',
            'subtitle' => 'Inspected cumulative property value'
        ]); ?>
    </div>

    <!-- Data Table Registry of Issued Passes -->
    <div class="card" style="padding:0;overflow:hidden;border:1px solid var(--color-hairline);">
        <?php component('data-table', [
            'id'                => 'gate-passes-table',
            'title'             => 'Issued Gate Pass Permits',
            'subtitle'          => 'Historical registry of approved off-campus movement permits',
            'columns'           => $columns,
            'rows'              => $tableRows,
            'total'             => $total,
            'currentPage'       => max(1, (int)($_GET['page'] ?? 1)),
            'pageSize'          => 15,
            'searchPlaceholder' => 'Search by pass number, borrower, or office...',
            'emptyIcon'         => 'clipboard-list',
        ]); ?>
    </div>

</div>

<!-- ── Modular Gate Pass Issuance Modal Component ── -->
<?php require __DIR__ . '/components/gate-pass-modal.php'; ?>

<script>
// Auto-open print tab if redirected with ?open_print=... or new pass modal if ?action=new or ?barcode=...
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const printId = params.get('open_print');
    if (printId) {
        window.open(window.appUrl('/inspector/gate-passes/print?id=' + encodeURIComponent(printId)), '_blank');
    }
    const prefilledBarcode = params.get('barcode');
    if (prefilledBarcode) {
        window.stageAndOpenGatePass(prefilledBarcode);
    } else if (params.get('action') === 'new') {
        openNewPassModal();
    }
});
</script>

<?php require dirname(__DIR__) . '/layouts/shell-footer.php'; ?>
