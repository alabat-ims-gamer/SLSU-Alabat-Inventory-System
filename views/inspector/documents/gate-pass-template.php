<?php
/**
 * Official Gate Pass Permit Document Template (1-to-1 Faithful Replication of New Institutional Layout)
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 * Sized strictly for US Legal Paper (8.5in × 14.0in / 215.9mm × 355.6mm / 612pt × 1008pt)
 * 100% Exact Vector & Typography Replication of Official Gate Pass Permit.
 * Compatible with both High-DPI Browser Printing and Server-Side Dompdf Compilation.
 */

declare(strict_types=1);

$passNo = htmlspecialchars($pass['gate_pass_no'] ?? 'GP-UNASSIGNED', ENT_QUOTES, 'UTF-8');
$passDate = !empty($pass['created_at']) ? date('F d, Y', strtotime($pass['created_at'])) : date('F d, Y');
$reqName = htmlspecialchars($pass['requisitioner_name'] ?? '', ENT_QUOTES, 'UTF-8');
$reqOffice = htmlspecialchars($pass['requisitioning_office'] ?? '', ENT_QUOTES, 'UTF-8');
$dept = htmlspecialchars($pass['department'] ?? $pass['office_dept'] ?? $reqOffice, ENT_QUOTES, 'UTF-8');
$purpose = htmlspecialchars($pass['purpose'] ?? '', ENT_QUOTES, 'UTF-8');
$items = $pass['items'] ?? [];

// Resolve logos to Base64 data URIs for 100% reliable rendering in both browser & Dompdf
$slsuMainLogoPath = dirname(__DIR__, 3) . '/public/images/slsu-main-logo.png';
if (!file_exists($slsuMainLogoPath)) {
    $slsuMainLogoPath = dirname(__DIR__, 4) . '/cropped-SLSU_Logo-1.png';
}
$slsuAlabatLogoPath = dirname(__DIR__, 3) . '/public/images/slsu-logo.png';
if (!file_exists($slsuAlabatLogoPath)) {
    $slsuAlabatLogoPath = dirname(__DIR__, 4) . '/SLSU_Alabat_Logo 1.png';
}

$slsuMainLogoData = file_exists($slsuMainLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($slsuMainLogoPath)) : '';
$slsuAlabatLogoData = file_exists($slsuAlabatLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($slsuAlabatLogoPath)) : '';

// Process equipment items
$displayRows = [];
$totalValuation = 0.0;
foreach ($items as $it) {
    $qty = htmlspecialchars((string)($it['qty'] ?? $it['quantity'] ?? '1'), ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars((string)($it['item_description'] ?? $it['description'] ?? $it['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $model = htmlspecialchars((string)($it['model'] ?? ''), ENT_QUOTES, 'UTF-8');
    $serial = htmlspecialchars((string)($it['serial_no'] ?? $it['serial_number'] ?? ''), ENT_QUOTES, 'UTF-8');
    $dateAcq = !empty($it['date_acquired']) ? date('m/d/Y', strtotime($it['date_acquired'])) : '';
    
    $rawUnit = str_replace(',', '', (string)($it['unit_price'] ?? $it['unit_cost'] ?? 0));
    $rawTotal = str_replace(',', '', (string)($it['total_amount'] ?? $it['total_cost'] ?? $rawUnit));
    $unitPrice = number_format((float)$rawUnit, 2);
    $totalAmount = number_format((float)$rawTotal, 2);
    $totalValuation += (float)$rawTotal;

    $remarks = htmlspecialchars((string)($it['remarks'] ?? ''), ENT_QUOTES, 'UTF-8');

    $displayRows[] = [
        'qty' => $qty,
        'desc' => $desc,
        'model' => $model,
        'serial' => $serial,
        'date_acq' => $dateAcq,
        'unit_price' => $unitPrice,
        'total_amount' => $totalAmount,
        'remarks' => $remarks
    ];
}

// Exactly 6 item rows in the official template layout
$totalFormRows = 6;
$emptyRowCount = max(0, $totalFormRows - count($displayRows));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gate Pass Permit — <?= $passNo ?></title>
  <style>
    @page {
      size: 8.5in 14in; /* US Legal portrait: 612pt × 1008pt / 215.9mm × 355.6mm */
      margin: 34pt 36pt 25pt 36pt;
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
      max-width: 540pt;
      margin: 0 auto;
      background: #ffffff;
      position: relative;
    }

    /* ── Header Section with University Seals ── */
    .header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 0;
    }

    .header-table td {
      vertical-align: middle;
      padding: 0;
    }

    .logo-left-cell {
      width: 70pt;
      text-align: left;
    }

    .logo-right-cell {
      width: 70pt;
      text-align: right;
    }

    .header-logo-left {
      width: 65pt;
      height: 65pt;
      display: inline-block;
    }

    .header-logo-right {
      width: 69pt;
      height: 69pt;
      display: inline-block;
    }

    .header-center {
      text-align: center;
      padding: 0 4pt;
    }

    .hdr-republic {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 10pt;
      font-weight: normal;
      color: #000000;
      letter-spacing: 0.02em;
      margin: 0 0 5pt 0;
      line-height: 1.0;
    }

    .hdr-university {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 17pt;
      font-weight: bold;
      color: #006747;
      letter-spacing: 0.01em;
      margin: 0 0 6pt 0;
      line-height: 1.0;
    }

    .hdr-former {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 9pt;
      font-weight: normal;
      color: #000000;
      margin: 0 0 4pt 0;
      line-height: 1.0;
    }

    .hdr-campus {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 11pt;
      font-weight: bold;
      color: #006747;
      letter-spacing: 0.02em;
      margin: 0 0 4pt 0;
      line-height: 1.0;
    }

    .hdr-address {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 9pt;
      font-weight: normal;
      color: #000000;
      margin: 0;
      line-height: 1.0;
    }

    /* SLSU Green Divider Bar */
    .header-divider {
      width: 100%;
      height: 2.25pt;
      background-color: #006747;
      margin: 18pt 0 7pt 0;
    }

    /* ── Title Block ── */
    .title-block {
      text-align: center;
      margin-bottom: 7pt;
    }

    .title-main {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 18pt;
      font-weight: bold;
      color: #006747;
      letter-spacing: 0.03em;
      margin: 0 0 5pt 0;
      line-height: 1.0;
    }

    .title-sub-1 {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 9pt;
      font-weight: normal;
      color: #000000;
      margin: 0 0 2pt 0;
      line-height: 1.0;
    }

    .title-sub-2 {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8pt;
      font-weight: normal;
      color: #000000;
      margin: 0 0 2pt 0;
      line-height: 1.0;
    }

    .title-sub-3 {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8pt;
      font-weight: normal;
      color: #000000;
      margin: 0;
      line-height: 1.0;
    }

    /* ── Metadata Grid ── */
    .meta-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #000000;
      margin-bottom: 12.5pt;
    }

    .meta-table td {
      border: 1px solid #000000;
      font-size: 8pt;
      padding: 0 5pt;
      vertical-align: middle;
      line-height: 1.0;
    }

    .meta-row-1 {
      height: 16.3pt;
    }

    .meta-row-2 {
      height: 24.4pt;
    }

    .meta-row-3 {
      height: 16.3pt;
    }

    .meta-lbl {
      background-color: #eaf4ef;
      font-weight: bold;
      color: #000000;
      width: 90pt;
      line-height: 1.15;
    }

    .meta-val {
      background-color: #ffffff;
      color: #000000;
      width: 180pt;
    }

    .meta-val-purpose {
      background-color: #f5f8f6;
      color: #000000;
    }

    /* ── Items Table ── */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #000000;
      margin-bottom: 16.4pt;
    }

    .items-table th {
      background-color: #006747;
      color: #ffffff;
      border: 1px solid #000000;
      font-size: 8pt;
      font-weight: bold;
      text-align: center;
      vertical-align: middle;
      padding: 0 2pt;
      height: 33.7pt;
      line-height: 1.15;
    }

    .items-table td {
      border: 1px solid #000000;
      font-size: 8pt;
      padding: 0 4pt;
      height: 19.2pt;
      vertical-align: middle;
      line-height: 1.0;
    }

    .row-white {
      background-color: #ffffff;
    }

    .row-zebra {
      background-color: #f5f8f6;
    }

    .col-qty { width: 12.5%; text-align: center; }
    .col-desc { width: 12.5%; text-align: left; }
    .col-model { width: 12.5%; text-align: center; }
    .col-serial { width: 12.5%; text-align: center; }
    .col-date { width: 12.5%; text-align: center; }
    .col-unit { width: 12.5%; text-align: right; }
    .col-total { width: 12.5%; text-align: right; }
    .col-remarks { width: 12.5%; text-align: left; }

    /* Summary Row (Total Asset Valuation) */
    .summary-row td {
      height: 16.4pt;
      padding: 0 4pt;
      line-height: 1.0;
    }

    .summary-label-cell {
      border: 1px solid #000000;
      background-color: #ffffff;
      text-align: right;
      font-size: 8pt;
      font-weight: bold;
      color: #006747;
      padding-right: 6pt !important;
    }

    .summary-val-cell {
      border: 1px solid #000000;
      background-color: #ffffff;
      text-align: center;
      font-size: 8pt;
      font-weight: bold;
      color: #006747;
    }

    .summary-blank-cell {
      border: 1px solid #000000;
      background-color: #ffffff;
    }

    /* ── Note Box ── */
    .note-box {
      width: 100%;
      border: 1px solid #000000;
      background-color: #eaf4ef;
      padding: 4pt 7pt 4pt 7pt;
      margin-bottom: 14.4pt;
      height: 67.6pt;
    }

    .note-title {
      font-size: 11pt;
      font-weight: bold;
      color: #006747;
      margin-bottom: 2pt;
      line-height: 1.0;
    }

    .note-list-table {
      width: 100%;
      border-collapse: collapse;
    }

    .note-list-table td {
      padding: 0.5pt 0;
      vertical-align: top;
      font-size: 8pt;
      line-height: 1.15;
      color: #000000;
    }

    .note-check-cell {
      width: 14pt;
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 8pt;
      color: #000000;
    }

    /* ── Signatory Grid (2x2) ── */
    .signatory-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #000000;
      margin-bottom: 0;
    }

    .signatory-table td {
      width: 50%;
      border: 1px solid #000000;
      vertical-align: top;
      padding: 6pt 8pt 4pt 8pt;
      height: 86.8pt;
    }

    .sig-header {
      font-size: 8pt;
      font-weight: bold;
      color: #000000;
      margin-bottom: 27pt;
      line-height: 1.0;
    }

    .sig-content {
      text-align: center;
    }

    .sig-name {
      font-size: 8pt;
      font-weight: bold;
      color: #000000;
      margin-bottom: 2pt;
      line-height: 1.0;
    }

    .sig-line {
      border-top: 1px solid #000000;
      width: 75%;
      margin: 0 auto 3pt auto;
    }

    .sig-title {
      font-size: 9pt;
      color: #000000;
      line-height: 1.0;
    }

    /* ── Security Inspection Log Bar ── */
    .security-bar-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #000000;
      border-top: none;
      background-color: #eaf4ef;
      height: 24.7pt;
    }

    .security-bar-table td {
      border-right: 1px solid #000000;
      vertical-align: top;
      padding: 2.5pt 5pt;
      font-size: 7pt;
      color: #000000;
    }

    .security-bar-table td:last-child {
      border-right: none;
    }

    .sec-departed-cell {
      width: 24%;
      line-height: 1.25;
    }

    .sec-returned-cell {
      width: 25%;
      vertical-align: middle !important;
    }

    .sec-guard-cell {
      width: 23%;
      line-height: 1.25;
    }

    .sec-condition-cell {
      width: 28%;
      padding: 1.5pt 4pt !important;
    }

    .sec-cond-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 7pt;
    }

    .sec-cond-table td {
      border: none !important;
      padding: 0 !important;
    }

    .sym-box {
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 7pt;
      margin-right: 2pt;
    }

    /* ── Official Template Footer ── */
    .doc-footer {
      text-align: center;
      font-size: 7pt;
      color: #006747;
      letter-spacing: 0.05em;
      position: absolute;
      top: 935pt;
      left: 0;
      right: 0;
    }
  </style>
</head>
<body>

<div class="document-sheet">

  <!-- ── Header Section with University Seals ── -->
  <table class="header-table">
    <tr>
      <td class="logo-left-cell">
        <?php if (!empty($slsuMainLogoData)): ?>
          <img src="<?= $slsuMainLogoData ?>" alt="SLSU Main Logo" class="header-logo-left">
        <?php endif; ?>
      </td>
      <td class="header-center">
        <div class="hdr-republic">REPUBLIC OF THE PHILIPPINES</div>
        <div class="hdr-university">SOUTHERN LUZON STATE UNIVERSITY</div>
        <div class="hdr-former">(Formerly Southern Luzon Polytechnic College)</div>
        <div class="hdr-campus">ALABAT CAMPUS</div>
        <div class="hdr-address">Alabat, Quezon</div>
      </td>
      <td class="logo-right-cell">
        <?php if (!empty($slsuAlabatLogoData)): ?>
          <img src="<?= $slsuAlabatLogoData ?>" alt="SLSU Alabat Logo" class="header-logo-right">
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <!-- Green Divider Bar -->
  <div class="header-divider"></div>

  <!-- ── Title Block ── -->
  <div class="title-block">
    <div class="title-main">OFFICIAL GATE PASS PERMIT</div>
    <div class="title-sub-1">(FOR BORROWED ITEMS AND OTHERS)</div>
    <div class="title-sub-2">(ITEMS FOR TRANSPORT FROM SLSU–ALABAT TO MAIN CAMPUS / SATELLITE CAMPUSES)</div>
    <div class="title-sub-3">(ITEMS FOR REPAIR)</div>
  </div>

  <!-- ── Metadata Grid ── -->
  <table class="meta-table">
    <tr class="meta-row-1">
      <td class="meta-lbl">PASS NUMBER:</td>
      <td class="meta-val"><?= $passNo ?></td>
      <td class="meta-lbl">DATE ISSUED:</td>
      <td class="meta-val"><?= $passDate ?></td>
    </tr>
    <tr class="meta-row-2">
      <td class="meta-lbl">REQUISITIONING<br>OFFICE:</td>
      <td class="meta-val"><?= $reqName ?></td>
      <td class="meta-lbl">OFFICE / DEPT.:</td>
      <td class="meta-val"><?= $reqOffice ?></td>
    </tr>
    <tr class="meta-row-3">
      <td class="meta-lbl">PURPOSE:</td>
      <td colspan="3" class="meta-val-purpose"><?= $purpose ?></td>
    </tr>
  </table>

  <!-- ── Items Table (6 Body Rows + 1 Valuation Summary Row) ── -->
  <table class="items-table">
    <thead>
      <tr>
        <th class="col-qty">QTY</th>
        <th class="col-desc">ITEM<br>DESCRIPTION /<br>SPEC.</th>
        <th class="col-model">MODEL</th>
        <th class="col-serial">SERIAL NO.</th>
        <th class="col-date">DATE ACQ.</th>
        <th class="col-unit">UNIT PRICE</th>
        <th class="col-total">TOTAL<br>AMOUNT</th>
        <th class="col-remarks">REMARKS</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      $rowIndex = 0;
      foreach ($displayRows as $row): 
        $rowBgClass = ($rowIndex % 2 === 0) ? 'row-white' : 'row-zebra';
        $rowIndex++;
      ?>
        <tr class="<?= $rowBgClass ?>">
          <td class="col-qty"><?= $row['qty'] ?></td>
          <td class="col-desc"><?= $row['desc'] ?></td>
          <td class="col-model"><?= $row['model'] ?></td>
          <td class="col-serial"><?= $row['serial'] ?></td>
          <td class="col-date"><?= $row['date_acq'] ?></td>
          <td class="col-unit"><?= $row['unit_price'] ?></td>
          <td class="col-total"><?= $row['total_amount'] ?></td>
          <td class="col-remarks"><?= $row['remarks'] ?></td>
        </tr>
      <?php endforeach; ?>

      <?php for ($i = 0; $i < $emptyRowCount; $i++): 
        $rowBgClass = ($rowIndex % 2 === 0) ? 'row-white' : 'row-zebra';
        $rowIndex++;
      ?>
        <tr class="<?= $rowBgClass ?>">
          <td class="col-qty">&nbsp;</td>
          <td class="col-desc">&nbsp;</td>
          <td class="col-model">&nbsp;</td>
          <td class="col-serial">&nbsp;</td>
          <td class="col-date">&nbsp;</td>
          <td class="col-unit">&nbsp;</td>
          <td class="col-total">&nbsp;</td>
          <td class="col-remarks">&nbsp;</td>
        </tr>
      <?php endfor; ?>

      <!-- Row 7: Total Asset Valuation Summary -->
      <tr class="summary-row">
        <td colspan="6" class="summary-label-cell">TOTAL ASSET VALUATION (PHP):</td>
        <td class="summary-val-cell">₱ <?= number_format($totalValuation, 2) ?></td>
        <td class="summary-blank-cell">&nbsp;</td>
      </tr>
    </tbody>
  </table>

  <!-- ── Note Box ── -->
  <div class="note-box">
    <div class="note-title">NOTE:</div>
    <table class="note-list-table">
      <tr>
        <td class="note-check-cell">&#10004;</td>
        <td>FOR BORROWED ITEMS PLEASE ATTTACH THE APPROVED BORROWER’S SLIP</td>
      </tr>
      <tr>
        <td class="note-check-cell">&#10004;</td>
        <td>ITEMS FOR REPAIR PLEASE ATTACH THE MONITORING FORM (job order for repair of equipment)</td>
      </tr>
      <tr>
        <td class="note-check-cell">&#10004;</td>
        <td>ITEMS FOR TRANSPORT FROM SLSU–ALABAT TO MAIN CAMPUS / SATELLITE CAMPUSES</td>
      </tr>
      <tr>
        <td class="note-check-cell">&#10004;</td>
        <td>PLEASE ATTACH THE APPROVED PURCHASE REQUEST AND / OR ACKNOWLEDGEMENT RECEIPT FOR EQUIPMENT (ARE), INVENTORY CUSTODIAN SLIP (ICS), AND REQUISITION AND ISSUE SLIP (RIS).</td>
      </tr>
    </table>
  </div>

  <!-- ── 4-Box Signatory Grid (2x2) ── -->
  <table class="signatory-table">
    <tr>
      <!-- Box 1: Requisitioner -->
      <td>
        <div class="sig-header">1. REQUISITIONER</div>
        <div class="sig-content">
          <div class="sig-name"><?= $reqName ?: 'SAMPLE STUDENT' ?></div>
          <div class="sig-line"></div>
          <div class="sig-title" style="font-size: 7pt;">Printed Name &amp; Signature / Date</div>
        </div>
      </td>
      <!-- Box 2: Recommending Approval -->
      <td>
        <div class="sig-header">2. RECOMMENDING APPROVAL</div>
        <div class="sig-content">
          <div class="sig-name">VERONICA C. OBIEN</div>
          <div class="sig-line"></div>
          <div class="sig-title">Property and Supply Coordinator</div>
        </div>
      </td>
    </tr>
    <tr>
      <!-- Box 3: Approved -->
      <td>
        <div class="sig-header">3. APPROVED</div>
        <div class="sig-content">
          <div class="sig-name">LYN VARGAS-TAPAN, PhD</div>
          <div class="sig-line"></div>
          <div class="sig-title">Campus Director</div>
        </div>
      </td>
      <!-- Box 4: Inspected By -->
      <td>
        <div class="sig-header">4. INSPECTED BY:</div>
        <div class="sig-content">
          <div class="sig-name">MANUEL S. VALDERAMA, MIT</div>
          <div class="sig-line"></div>
          <div class="sig-title">Inspection Officer</div>
        </div>
      </td>
    </tr>
  </table>

  <!-- ── Security Inspection Log Bar ── -->
  <table class="security-bar-table">
    <tr>
      <td class="sec-departed-cell">
        <div>TIME DEPARTED:</div>
        <div>__________________</div>
      </td>
      <td class="sec-returned-cell">
        TIME RETURNED: __________________
      </td>
      <td class="sec-guard-cell">
        <div>SECURITY GUARD ON DUTY:</div>
        <div>__________________________</div>
      </td>
      <td class="sec-condition-cell">
        <table class="sec-cond-table">
          <tr>
            <td style="vertical-align: middle; width: 62%;">CONDITION UPON RETURN:</td>
            <td style="vertical-align: middle; width: 38%; padding-left: 2pt;">
              <div><span class="sym-box">&#x2610;</span> OK</div>
              <div><span class="sym-box">&#x2610;</span> DEFECT</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- ── Official Template Footer ── -->
  <div class="doc-footer">
    SLSU – ALABAT CAMPUS | EDITABLE TEMPLATE | PAGE 1
  </div>

</div>

</body>
</html>
