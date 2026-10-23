<?php
/**
 * Official Inspection and Acceptance Report (IAR Form AFA-SAF-1.016) Document Template
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 * Sized strictly for Standard A4 Paper (210mm × 297mm / 595.45pt × 841.70pt)
 * 100% Exact Vector & Typography Replication of Institutional INSPECTION-AND-ACCEPTANCE.pdf.
 * Compatible with both High-DPI Browser Printing and Server-Side Dompdf Compilation.
 */

declare(strict_types=1);

$iarNo = htmlspecialchars($report['iar_no'] ?? 'IAR-UNASSIGNED', ENT_QUOTES, 'UTF-8');
$entityName = htmlspecialchars($report['entity_name'] ?? 'SLSU-ALABAT', ENT_QUOTES, 'UTF-8');
$fundCluster = htmlspecialchars($report['fund_cluster'] ?? 'PRE-STF', ENT_QUOTES, 'UTF-8');
$supplier = htmlspecialchars($report['supplier_name'] ?? '', ENT_QUOTES, 'UTF-8');

$poNo = htmlspecialchars($report['po_no'] ?? '', ENT_QUOTES, 'UTF-8');
$poDateFormatted = !empty($report['po_date']) ? date('m/d/Y', strtotime($report['po_date'])) : '';
$poString = $poNo . ($poDateFormatted ? " / {$poDateFormatted}" : '');

$iarDateFormatted = !empty($report['created_at']) ? date('m/d/Y', strtotime($report['created_at'])) : date('m/d/Y');
$invoiceNo = htmlspecialchars($report['invoice_no'] ?? '', ENT_QUOTES, 'UTF-8');
$invoiceDateFormatted = !empty($report['invoice_date']) ? date('m/d/Y', strtotime($report['invoice_date'])) : '';

$officeDept = htmlspecialchars($report['office_dept'] ?? '', ENT_QUOTES, 'UTF-8');
$rcc = htmlspecialchars($report['rcc'] ?? '', ENT_QUOTES, 'UTF-8');

$dateInspected = !empty($report['date_inspected']) ? date('m/d/Y', strtotime($report['date_inspected'])) : '';
$dateReceived = !empty($report['date_received']) ? date('m/d/Y', strtotime($report['date_received'])) : date('m/d/Y');

$inspectionOfficer = htmlspecialchars($report['inspection_officer'] ?? 'MANUEL S. VALDERAMA, MIT', ENT_QUOTES, 'UTF-8');
$propertyOfficer = htmlspecialchars($report['property_officer'] ?? 'VERONICA C. OBIEN', ENT_QUOTES, 'UTF-8');

$isInspectedOrder = !empty($report['is_inspected_order']);
$acceptanceType = strtolower((string)($report['acceptance_type'] ?? 'complete'));
$partialRemarks = htmlspecialchars($report['partial_remarks'] ?? '', ENT_QUOTES, 'UTF-8');

$items = $report['items'] ?? [];

// Resolve logo to Base64 data URI
$slsuLogoPath = dirname(__DIR__, 3) . '/public/images/slsu-main-logo.png';
if (!file_exists($slsuLogoPath)) {
    $slsuLogoPath = dirname(__DIR__, 4) . '/cropped-SLSU_Logo-1.png';
}
$slsuLogoData = file_exists($slsuLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($slsuLogoPath)) : '';

// Prepare exactly 27 rows matching the official form
$displayRows = [];
foreach ($items as $idx => $it) {
    $itemNo = htmlspecialchars((string)($it['item_no'] ?? ($idx + 1)), ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars((string)($it['item_description'] ?? $it['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $model = !empty($it['model']) && $it['model'] !== 'N/A' ? ' (Model: ' . htmlspecialchars((string)$it['model'], ENT_QUOTES, 'UTF-8') . ')' : '';
    $serial = !empty($it['serial_no']) && $it['serial_no'] !== 'N/A' ? ' [SN: ' . htmlspecialchars((string)$it['serial_no'], ENT_QUOTES, 'UTF-8') . ']' : '';
    $fullDesc = $desc . $model . $serial;

    $unit = htmlspecialchars((string)($it['unit'] ?? 'unit'), ENT_QUOTES, 'UTF-8');
    $qty = htmlspecialchars((string)($it['quantity'] ?? '1'), ENT_QUOTES, 'UTF-8');

    $displayRows[] = [
        'item_no' => $itemNo,
        'desc' => $fullDesc,
        'unit' => $unit,
        'qty' => $qty
    ];
}

$totalFormRows = 27;
$emptyRowCount = max(0, $totalFormRows - count($displayRows));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Inspection and Acceptance Report — <?= $iarNo ?></title>
  <style>
    @page {
      size: A4 portrait; /* 210mm × 297mm / 595.45pt × 841.70pt */
      margin: 24pt 24.5pt 16pt 24.5pt;
    }

    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8pt;
      line-height: 1.15;
      color: #000000;
      background: #ffffff;
    }

    .document-sheet {
      width: 100%;
      max-width: 546.7pt;
      margin: 0 auto;
      background: #ffffff;
    }

    /* ── Header with Centered Institutional Title & Logo ── */
    .header-table {
      margin: 0 auto 8pt auto;
      border-collapse: collapse;
    }

    .header-table td {
      vertical-align: middle;
      padding: 0;
    }

    .header-table td.header-logo-cell {
      padding-right: 18pt;
      text-align: right;
    }

    .header-logo {
      width: 50pt;
      height: 50pt;
      display: block;
    }

    .header-text-cell {
      text-align: center;
    }

    .hdr-iar-title {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 11pt;
      font-weight: bold;
      text-transform: uppercase;
      color: #000000;
      margin: 0 0 2pt 0;
      letter-spacing: 0.02em;
    }

    .hdr-university {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 10.5pt;
      font-weight: bold;
      text-transform: uppercase;
      color: #000000;
      margin: 0 0 2pt 0;
    }

    .hdr-location {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 10pt;
      font-weight: bold;
      text-transform: uppercase;
      color: #000000;
      margin: 0;
    }

    /* ── Metadata Grid (5 rows × 4 columns) ── */
    .meta-grid {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #000000;
      margin-bottom: 6pt;
      table-layout: fixed;
    }

    .meta-grid td {
      border: 1px solid #000000;
      padding: 1.8pt 4pt;
      font-size: 8pt;
      vertical-align: middle;
      overflow: hidden;
    }

    .meta-col-lbl-1 { width: 80pt; font-weight: bold; }
    .meta-col-val-1 { width: 255pt; }
    .meta-col-lbl-2 { width: 70pt; font-weight: bold; }
    .meta-col-val-2 { width: 141.7pt; }

    .meta-center-bold {
      text-align: center;
      font-weight: bold;
    }

    /* ── Line Items Table (4 Columns, 27 Rows) ── */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #000000;
      margin-bottom: 6pt;
      table-layout: fixed;
    }

    .items-table th,
    .items-table td {
      border: 1px solid #000000;
      padding: 1pt 3pt;
      overflow: hidden;
      word-wrap: break-word;
    }

    .items-table th {
      background: #ffffff;
      color: #000000;
      text-align: center;
      vertical-align: middle;
      height: 22pt;
    }

    .th-stock {
      width: 60pt;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8pt;
      font-weight: bold;
      font-style: italic;
      line-height: 1.05;
    }

    .th-desc {
      width: 340pt;
      font-family: 'Times New Roman', Times, serif;
      font-size: 8.5pt;
      font-weight: bold;
      font-style: italic;
      letter-spacing: 0.03em;
    }

    .th-unit {
      width: 60pt;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8pt;
      font-weight: bold;
      font-style: italic;
    }

    .th-qty {
      width: 86.7pt;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8pt;
      font-weight: bold;
    }

    .items-table td {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 7.5pt;
      color: #000000;
      height: 15.2pt;
      vertical-align: middle;
      line-height: 1.1;
    }

    .td-stock { text-align: center; }
    .td-desc  { text-align: left; }
    .td-unit  { text-align: center; }
    .td-qty   { text-align: center; }

    /* ── Bottom Inspection & Acceptance Section ── */
    .footer-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #000000;
      margin-bottom: 2pt;
      table-layout: fixed;
      page-break-inside: avoid;
    }

    .footer-table td {
      border: 1px solid #000000;
      padding: 1.5pt 4pt;
      vertical-align: top;
      font-family: Arial, Helvetica, sans-serif;
    }

    .footer-col-left  { width: 50%; }
    .footer-col-right { width: 50%; }

    .footer-section-title {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8.5pt;
      font-weight: bold;
      font-style: italic;
      text-align: center;
      padding: 2pt 0;
      letter-spacing: 0.05em;
    }

    .footer-date-row {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8pt;
      font-weight: bold;
      padding: 2pt 4pt;
    }

    .footer-clause-box {
      padding: 3pt 4pt 4pt 4pt;
      font-size: 7.5pt;
      line-height: 1.3;
      height: 32pt;
    }

    .box-square {
      display: inline-block;
      width: 7.5pt;
      height: 7.5pt;
      border: 1px solid #000000;
      vertical-align: baseline;
      margin-right: 3pt;
      background: #ffffff;
    }

    .box-square-checked {
      display: inline-block;
      width: 7.5pt;
      height: 7.5pt;
      border: 1px solid #000000;
      vertical-align: baseline;
      margin-right: 3pt;
      background: #000000;
    }

    .footer-officer-name {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8pt;
      font-weight: bold;
      text-align: center;
      padding-top: 5pt;
      padding-bottom: 1pt;
    }

    .footer-officer-title {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 7pt;
      text-align: center;
      padding-bottom: 3pt;
    }

    .form-code {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 6.5pt;
      color: #000000;
      margin-top: 1.5pt;
      text-align: left;
    }
  </style>
</head>
<body>
<div class="document-sheet">

  <!-- 1. Header with Circular University Seal & Centered Text -->
  <table class="header-table">
    <tr>
      <?php if (!empty($slsuLogoData)): ?>
        <td class="header-logo-cell">
          <img src="<?= $slsuLogoData ?>" alt="SLSU Seal" class="header-logo">
        </td>
      <?php endif; ?>
      <td class="header-text-cell">
        <div class="hdr-iar-title">INSPECTION AND ACCEPTANCE REPORT</div>
        <div class="hdr-university">SOUTHERN LUZON STATE UNIVERSITY</div>
        <div class="hdr-location">LUCBAN, QUEZON</div>
      </td>
    </tr>
  </table>

  <!-- 2. Metadata Grid (5 rows × 4 columns) -->
  <table class="meta-grid">
    <tr>
      <td class="meta-col-lbl-1">Entity Name:</td>
      <td class="meta-col-val-1 meta-center-bold"><?= $entityName ?></td>
      <td class="meta-col-lbl-2">Fund Cluster</td>
      <td class="meta-col-val-2 meta-center-bold"><?= $fundCluster ?></td>
    </tr>
    <tr>
      <td class="meta-col-lbl-1">Supplier:</td>
      <td class="meta-col-val-1"><?= $supplier ?></td>
      <td class="meta-col-lbl-2">IAR No.:</td>
      <td class="meta-col-val-2"><?= $iarNo ?></td>
    </tr>
    <tr>
      <td class="meta-col-lbl-1">PO No./Date:</td>
      <td class="meta-col-val-1"><?= $poString ?></td>
      <td class="meta-col-lbl-2">Date:</td>
      <td class="meta-col-val-2"><?= $iarDateFormatted ?></td>
    </tr>
    <tr>
      <td class="meta-col-lbl-1">Office/Dept.:</td>
      <td class="meta-col-val-1"><?= $officeDept ?></td>
      <td class="meta-col-lbl-2">Invoice No.:</td>
      <td class="meta-col-val-2"><?= $invoiceNo ?></td>
    </tr>
    <tr>
      <td class="meta-col-lbl-1">RCC:</td>
      <td class="meta-col-val-1"><?= $rcc ?></td>
      <td class="meta-col-lbl-2">Date:</td>
      <td class="meta-col-val-2"><?= $invoiceDateFormatted ?></td>
    </tr>
  </table>

  <!-- 3. Line Items Table (4 Columns, exactly 27 Rows) -->
  <table class="items-table">
    <thead>
      <tr>
        <th class="th-stock">Stock/<br>item no.</th>
        <th class="th-desc">ITEM DESCRIPTION</th>
        <th class="th-unit">Unit</th>
        <th class="th-qty">Quantity</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($displayRows as $row): ?>
        <tr>
          <td class="td-stock"><?= $row['item_no'] ?></td>
          <td class="td-desc"><?= $row['desc'] ?></td>
          <td class="td-unit"><?= $row['unit'] ?></td>
          <td class="td-qty"><?= $row['qty'] ?></td>
        </tr>
      <?php endforeach; ?>

      <?php for ($i = 0; $i < $emptyRowCount; $i++): ?>
        <tr>
          <td class="td-stock">&nbsp;</td>
          <td class="td-desc">&nbsp;</td>
          <td class="td-unit">&nbsp;</td>
          <td class="td-qty">&nbsp;</td>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <!-- 4. Bottom Inspection & Acceptance Section -->
  <table class="footer-table">
    <!-- Row 1: Section Titles -->
    <tr>
      <td class="footer-col-left footer-section-title">INSPECTION</td>
      <td class="footer-col-right footer-section-title">ACCEPTANCE</td>
    </tr>

    <!-- Row 2: Date Header -->
    <tr>
      <td class="footer-date-row">Date Inspected: <?= $dateInspected ?></td>
      <td class="footer-date-row">Date Received: <?= $dateReceived ?></td>
    </tr>

    <!-- Row 3: Checkboxes & Verification Clauses -->
    <tr>
      <td class="footer-clause-box">
        <span class="<?= $isInspectedOrder ? 'box-square-checked' : 'box-square' ?>"></span>
        Inspected, verified and found in order as to<br>
        &nbsp;&nbsp;&nbsp;&nbsp;quantity and specifications
      </td>
      <td class="footer-clause-box">
        <div>
          <span class="<?= $acceptanceType === 'complete' ? 'box-square-checked' : 'box-square' ?>"></span> Complete
        </div>
        <div style="margin-top: 1.5pt;">
          <span class="<?= $acceptanceType === 'partial' ? 'box-square-checked' : 'box-square' ?>"></span> Partial (pls. specify quantity)
        </div>
        <?php if (!empty($partialRemarks)): ?>
          <div style="font-size:7pt;margin-left:14pt;font-style:italic;"><?= $partialRemarks ?></div>
        <?php endif; ?>
      </td>
    </tr>

    <!-- Row 4: Signatory Names -->
    <tr>
      <td class="footer-officer-name"><?= $inspectionOfficer ?></td>
      <td class="footer-officer-name"><?= $propertyOfficer ?></td>
    </tr>

    <!-- Row 5: Signatory Titles -->
    <tr>
      <td class="footer-officer-title">Inspection Officer/Inspection Committee</td>
      <td class="footer-officer-title">OIC, Supply and Property Officer</td>
    </tr>
  </table>

  <!-- 5. Form Code at Very Bottom Left -->
  <div class="form-code">AFA-SAF-1.016, Inspection and Acceptance Report</div>

</div>
</body>
</html>
