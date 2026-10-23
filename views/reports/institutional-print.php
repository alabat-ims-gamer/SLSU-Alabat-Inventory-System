<?php
/**
 * Official Institutional Property Report Print & Export Engine
 * Southern Luzon State University – Alabat Campus
 * Dual-Mode: Native High-Resolution Web Printing (window.print()) + Server-Side Dompdf Compilation.
 */

declare(strict_types=1);

if (!isset($reportData) || empty($reportData)) {
    http_response_code(404);
    die("Institutional report record is missing or failed to generate.");
}

$meta = $reportData['metadata'] ?? [];
$scopeLabel = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($meta['target_scope'] ?? 'All_Categories'));
$pdfFilename = 'SLSU_Executive_Institutional_Property_Report_' . $scopeLabel . '_' . date('Ymd_His') . '.pdf';

// ── Mode A: Server-Side Dompdf Binary Download (?format=pdf) ───────────────
if (isset($_GET['format']) && strtolower(trim((string)$_GET['format'])) === 'pdf') {
    if (!class_exists(\Dompdf\Dompdf::class)) {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    }

    ob_start();
    require __DIR__ . '/documents/institutional-report-template.php';
    $documentHtml = ob_get_clean();

    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled'      => true,
        'isHtml5ParserEnabled' => true,
        'defaultFont'          => 'Helvetica',
    ]);

    // Standard US Legal Paper: 8.5in × 14in (612pt × 1008pt Portrait)
    $dompdf->setPaper('legal', 'portrait');
    $dompdf->loadHtml($documentHtml);
    $dompdf->render();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $pdfFilename . '"');
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
    <title>Executive Institutional Property Report — SLSU Alabat Campus</title>

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
            gap: 0.75rem;
        }

        .toolbar-badge {
            background: rgba(22, 101, 52, 0.25);
            color: #86efac;
            border: 1px solid rgba(134, 239, 172, 0.35);
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .preview-viewport {
            flex: 1;
            padding: 2rem 1rem;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            background: #0f172a;
        }

        .preview-paper {
            background: #ffffff;
            color: #000000;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
            width: 8.5in;
            min-height: 14in;
            padding: 36pt 68pt;
            box-sizing: border-box;
            border-radius: 4px;
        }

        @page {
            size: legal portrait;
            margin: 36pt 68pt;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .print-toolbar {
                display: none !important;
            }
            .preview-viewport {
                padding: 0 !important;
                background: #ffffff !important;
            }
            .preview-paper {
                box-shadow: none !important;
                width: 100% !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body>

    <div class="print-toolbar">
        <div class="toolbar-title-wrap">
            <span class="toolbar-badge">1-to-1 Official Audit Report</span>
            <span style="font-size:13px;font-weight:600;color:#f8fafc;">
                Scope: <?= htmlspecialchars((string)($meta['target_scope'] ?? 'All Categories'), ENT_QUOTES, 'UTF-8') ?>
                (<?= htmlspecialchars((string)($meta['timeframe_label'] ?? 'Last 30 Days'), ENT_QUOTES, 'UTF-8') ?>)
            </span>
        </div>

        <div class="toolbar-actions">
            <?php 
            $currentQuery = $_GET;
            $currentQuery['format'] = 'pdf';
            $pdfDownloadUrl = '?' . http_build_query($currentQuery);
            ?>
            <a href="<?= htmlspecialchars($pdfDownloadUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm" style="border-radius:var(--radius-pill);color:#fff;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download PDF
            </a>

            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm" style="border-radius:var(--radius-pill);background:#166534;border-color:#166534;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print / Save as PDF
            </button>

            <button type="button" onclick="window.close()" class="btn btn-ghost btn-sm" style="color:#94a3b8;">
                Close
            </button>
        </div>
    </div>

    <div class="preview-viewport">
        <div class="preview-paper">
            <?php require __DIR__ . '/documents/institutional-report-template.php'; ?>
        </div>
    </div>

</body>
</html>
