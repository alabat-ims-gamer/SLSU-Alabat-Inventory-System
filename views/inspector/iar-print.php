<?php
/**
 * Official Inspection and Acceptance Report (IAR Form AFA-SAF-1.016) Print & Export Engine
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 * Dual-Mode: Native High-Resolution Web Printing (window.print()) + Server-Side Dompdf Folio Compilation.
 */

declare(strict_types=1);

if (!isset($report) || empty($report)) {
    http_response_code(404);
    die("Inspection report record is missing.");
}

$iarNo = htmlspecialchars($report['iar_no'] ?? 'IAR-UNASSIGNED', ENT_QUOTES, 'UTF-8');
$supplier = htmlspecialchars($report['supplier_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$officeDept = htmlspecialchars($report['office_dept'] ?? 'N/A', ENT_QUOTES, 'UTF-8');

// ── Mode A: Server-Side Dompdf Binary Download (?format=pdf) ───────────────
if (isset($_GET['format']) && strtolower(trim((string)$_GET['format'])) === 'pdf') {
    if (!class_exists(\Dompdf\Dompdf::class)) {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    }

    ob_start();
    require __DIR__ . '/documents/iar-template.php';
    $documentHtml = ob_get_clean();

    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled'      => true,
        'isHtml5ParserEnabled' => true,
        'defaultFont'          => 'Helvetica',
    ]);

    // Standard A4 Paper: 210mm × 297mm = 595.45pt × 841.70pt (matching official template)
    $dompdf->setPaper('a4', 'portrait');
    $dompdf->loadHtml($documentHtml);
    $dompdf->render();

    $cleanFilename = 'IAR_' . preg_replace('/[^A-Za-z0-9_-]/', '', $iarNo) . '.pdf';

    // Clean any active output buffer to ensure clean binary stream
    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $cleanFilename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $dompdf->output();
    exit;
}

// ── Mode B: Interactive Web Print Preview ─────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Form AFA-SAF-1.016 — <?= $iarNo ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('/css/custom.css') ?>">

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #0f172a;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .print-toolbar {
            background: rgba(15, 23, 42, 0.96);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .toolbar-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .iar-badge {
            background: #044e29;
            color: #dcfce7;
            font-family: monospace;
            font-weight: 700;
            font-size: 13px;
            padding: 4px 10px;
            border-radius: var(--radius-pill, 9999px);
            border: 1px solid rgba(34, 197, 94, 0.3);
            letter-spacing: 0.04em;
        }

        .preview-viewport {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem 1rem 4rem 1rem;
            background: #1e293b;
            overflow-y: auto;
        }

        .folio-paper-canvas {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4), 0 1px 3px rgba(0, 0, 0, 0.2);
            border-radius: 2px;
            padding: 28pt 24.5pt 20pt 24.5pt;
            color: #0f172a;
        }

        @media print {
            .print-toolbar {
                display: none !important;
            }
            body, .preview-viewport {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                height: auto !important;
                overflow: visible !important;
            }
            .folio-paper-canvas {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-height: auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- Sticky Top Print Action Bar -->
    <header class="print-toolbar">
        <div class="toolbar-title-wrap">
            <span class="iar-badge"><?= $iarNo ?></span>
            <div>
                <div style="font-size:14px;font-weight:600;color:#ffffff;">Form AFA-SAF-1.016 (Inspection &amp; Acceptance)</div>
                <div style="font-size:12px;color:rgba(255,255,255,0.7);"><?= $supplier ?> · <?= $officeDept ?></div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:0.75rem;">
            <a href="<?= url('/inspector/iar') ?>" class="btn btn-ghost btn-sm" style="color:rgba(255,255,255,0.8);border-color:rgba(255,255,255,0.2);text-decoration:none;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><polyline points="15 18 9 12 15 6"></polyline></svg>
                <span>Back to IAR Desk</span>
            </a>

            <a href="<?= url('/inspector/iar/print?id=' . (int)$report['id'] . '&format=pdf') ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Download Folio PDF</span>
            </a>

            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()" style="display:inline-flex;align-items:center;gap:6px;background:#166534;border-color:#22c55e;box-shadow:0 0 15px rgba(34,197,94,0.35);">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span>Print Official Report</span>
            </button>
        </div>
    </header>

    <!-- Main Sheet Preview -->
    <main class="preview-viewport">
        <div class="folio-paper-canvas">
            <?php require __DIR__ . '/documents/iar-template.php'; ?>
        </div>
    </main>

</body>
</html>
