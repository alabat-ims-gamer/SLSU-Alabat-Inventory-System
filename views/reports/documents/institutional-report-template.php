<?php
/**
 * Official Institutional Property & Circulation Report Document Template
 * Southern Luzon State University – Alabat Campus
 * 1-to-1 Faithful Replication of Executive-Institutional-Property (1).pdf
 * Sized for Standard US Legal Paper (8.5in × 14in / 612pt × 1008pt)
 * Multi-Page: Page 1 (Sections 1–4) & Page 2 (Section 5 & Official Signatures).
 */

declare(strict_types=1);

$meta = $reportData['metadata'] ?? [];
$summaryRows = $reportData['summary_rows'] ?? [];
$summaryTotals = $reportData['summary_totals'] ?? [
    'total_items' => 0, 'available' => 0, 'borrowed' => 0,
    'under_maintenance' => 0, 'lost_and_missing' => 0, 'damaged' => 0
];
$borrowingHistory = $reportData['borrowing_history'] ?? [];
$damagedProperties = $reportData['damaged_properties'] ?? [];
$lostProperties = $reportData['lost_properties'] ?? [];
$propertyMovements = $reportData['property_movements'] ?? [];

// ── Base64 Encode Official Logos and Header Icons ────────────────────────────
$baseDir = dirname(__DIR__, 3);
$parentDir = dirname($baseDir);

// 1. SLSU Seal (Left)
$logo1Candidates = [
    $baseDir . '/public/images/slsu-header-seal.png',
    $baseDir . '/public/images/slsu-header-seal.jpg',
    $parentDir . '/cropped-SLSU_Logo-1.png',
    $baseDir . '/public/images/slsu-logo.png',
    $baseDir . '/public/images/slsu-main-logo.png',
];
$logo1Data = '';
foreach ($logo1Candidates as $path) {
    if (file_exists($path)) {
        $mime = str_ends_with($path, '.jpg') || str_ends_with($path, '.jpeg') ? 'image/jpeg' : 'image/png';
        $logo1Data = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        break;
    }
}

// 2. Bagong Pilipinas (Center/Right Pair)
$logo2Candidates = [
    $baseDir . '/public/images/bagong-pilipinas-header.png',
    $baseDir . '/public/images/bagong-pilipinas-header.jpg',
    $baseDir . '/public/images/bagong-pilipinas.png',
    $parentDir . '/Bagong_Pilipinas_Logo.svg.webp',
];
$logo2Data = '';
foreach ($logo2Candidates as $path) {
    if (file_exists($path)) {
        $mime = str_ends_with($path, '.jpg') || str_ends_with($path, '.jpeg') ? 'image/jpeg' : (str_ends_with($path, '.webp') ? 'image/webp' : 'image/png');
        $logo2Data = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        break;
    }
}

// 3. SLSU Alabat Campus Logo (Right)
$logo3Candidates = [
    $baseDir . '/public/images/slsu-alabat-header.png',
    $baseDir . '/public/images/slsu-alabat-header.jpg',
    $parentDir . '/SLSU_Alabat_Logo 1.png',
    $baseDir . '/public/images/slsu-main-logo.png',
    $baseDir . '/public/images/slsu-logo.png',
];
$logo3Data = '';
foreach ($logo3Candidates as $path) {
    if (file_exists($path)) {
        $mime = str_ends_with($path, '.jpg') || str_ends_with($path, '.jpeg') ? 'image/jpeg' : 'image/png';
        $logo3Data = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        break;
    }
}

// 4. Contact Icons (Location, Phone, Email, Social)
$iconLocationPath = $baseDir . '/public/images/icon-report-location.png';
$iconLocation = file_exists($iconLocationPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconLocationPath)) : '';

$iconPhonePath = $baseDir . '/public/images/icon-report-phone.png';
$iconPhone = file_exists($iconPhonePath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconPhonePath)) : '';

$iconEmailPath = $baseDir . '/public/images/icon-report-email.png';
$iconEmail = file_exists($iconEmailPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconEmailPath)) : '';

$iconSocialPath = $baseDir . '/public/images/icon-report-social.png';
$iconSocial = file_exists($iconSocialPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconSocialPath)) : '';

// ── Reusable Institutional Header Function ──────────────────────────────────
$renderInstitutionalHeader = function() use ($logo1Data, $logo2Data, $logo3Data, $iconLocation, $iconPhone, $iconEmail, $iconSocial) {
?>
  <table class="hdr-tbl">
    <tr>
      <td class="hdr-left">
        <?php if ($logo1Data): ?>
          <img src="<?= $logo1Data ?>" class="hdr-seal" alt="SLSU Seal">
        <?php endif; ?>
        <?php if ($logo2Data): ?>
          <img src="<?= $logo2Data ?>" class="hdr-bagong" alt="Bagong Pilipinas">
        <?php endif; ?>
      </td>
      <td class="hdr-center">
        <div class="hdr-univ">SOUTHERN LUZON STATE UNIVERSITY</div>
        <div class="hdr-campus">Alabat Campus</div>
        <div class="hdr-contact-line">
          <?php if ($iconLocation): ?>
            <img src="<?= $iconLocation ?>" class="hdr-icon" alt="">
          <?php endif; ?>
          <span>Francia St. Brgy. 5 Poblacion, Alabat, Quezon PH 4333</span>
        </div>
        <div class="hdr-contact-line" style="margin-top:2pt;">
          <?php if ($iconPhone): ?>
            <img src="<?= $iconPhone ?>" class="hdr-icon" alt="">
          <?php endif; ?>
          <span>(+63)966-153-1466</span>
          <?php if ($iconEmail): ?>
            <img src="<?= $iconEmail ?>" class="hdr-icon" style="margin-left:8pt;" alt="">
          <?php endif; ?>
          <span>sscslsualabat@gmail.com</span>
          <?php if ($iconSocial): ?>
            <img src="<?= $iconSocial ?>" class="hdr-icon" style="margin-left:8pt;" alt="">
          <?php endif; ?>
          <span>@sscalabat</span>
        </div>
      </td>
      <td class="hdr-right">
        <?php if ($logo3Data): ?>
          <img src="<?= $logo3Data ?>" class="hdr-alabat" alt="SLSU Alabat Campus">
        <?php endif; ?>
      </td>
    </tr>
  </table>
<?php
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Executive Institutional Property &amp; Circulation Report</title>
  <style>
    @page {
      size: legal portrait;
      margin: 36pt 68pt 36pt 68pt;
    }

    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: "Aptos", "Segoe UI", Arial, Helvetica, sans-serif;
      font-size: 9.0pt;
      line-height: 1.2;
      color: #000000;
      background: #ffffff;
    }

    .report-sheet {
      width: 100%;
      max-width: 476pt;
      margin: 0 auto;
      background: #ffffff;
    }

    /* ── Header ─────────────────────────────────────────────────── */
    .hdr-tbl {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8pt;
    }

    .hdr-tbl td {
      vertical-align: middle;
      padding: 0;
    }

    .hdr-left {
      width: 122pt;
      white-space: nowrap;
      text-align: left;
    }

    .hdr-seal {
      width: 55pt;
      height: 55pt;
      vertical-align: middle;
      display: inline-block;
    }

    .hdr-bagong {
      width: 58pt;
      height: 55pt;
      vertical-align: middle;
      display: inline-block;
      margin-left: 4pt;
    }

    .hdr-center {
      text-align: left;
      padding: 0 4pt;
      vertical-align: middle;
    }

    .hdr-univ {
      font-family: Verdana, Geneva, sans-serif;
      font-weight: bold;
      font-size: 13.13pt;
      color: #19532b;
      text-transform: uppercase;
      letter-spacing: 0.01em;
      margin: 0 0 1pt 0;
      line-height: 1.1;
    }

    .hdr-campus {
      font-family: Verdana, Geneva, sans-serif;
      font-weight: normal;
      font-size: 10.99pt;
      color: #19532b;
      margin: 0 0 4pt 0;
      line-height: 1.1;
    }

    .hdr-contact-line {
      font-size: 6.81pt;
      color: #7f7f7f;
      line-height: 1.25;
      white-space: nowrap;
    }

    .hdr-icon {
      width: 8pt;
      height: 8pt;
      vertical-align: middle;
      display: inline-block;
      margin-right: 2pt;
    }

    .hdr-right {
      width: 58pt;
      text-align: right;
      vertical-align: middle;
    }

    .hdr-alabat {
      width: 55pt;
      height: 51pt;
      vertical-align: middle;
      display: inline-block;
    }

    /* ── Document Title ─────────────────────────────────────────── */
    .doc-title {
      font-family: "Times New Roman", Times, Georgia, serif;
      font-weight: bold;
      font-size: 14.04pt;
      color: #000000;
      text-align: center;
      margin: 14pt 0 14pt 0;
    }

    /* ── Metadata Ribbon (4 Columns) ────────────────────────────── */
    .meta-ribbon {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10pt;
      border-top: 0.75pt solid #000000;
    }

    .meta-ribbon td {
      width: 25%;
      padding: 4.5pt 2pt 4pt 2pt;
      text-align: center;
      vertical-align: top;
      border-right: 0.75pt solid #000000;
    }

    .meta-ribbon td:last-child {
      border-right: none;
    }

    .meta-label {
      font-size: 9.96pt;
      color: #808080;
      text-transform: uppercase;
      margin-bottom: 2pt;
      line-height: 1.1;
    }

    .meta-value {
      font-size: 9.96pt;
      font-weight: bold;
      color: #000000;
      line-height: 1.15;
    }

    /* ── Section Headings & Tables ──────────────────────────────── */
    .sec-heading {
      font-size: 11.04pt;
      font-weight: bold;
      color: #215f9a;
      text-transform: uppercase;
      margin: 12pt 0 4pt 0;
      letter-spacing: 0.01em;
      page-break-after: avoid;
      break-after: avoid;
    }

    .report-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 9.0pt;
      margin-bottom: 4pt;
    }

    .report-table tr {
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .report-table th {
      border-top: 0.75pt solid #7e7f7e;
      border-bottom: 0.75pt solid #7e7f7e;
      padding: 2.5pt 2pt;
      font-weight: bold;
      color: #000000;
      text-align: center;
      text-transform: uppercase;
      font-size: 9.0pt;
      line-height: 1.12;
      background: transparent;
    }

    .report-table td {
      border-bottom: 0.5pt solid #7e7f7e;
      padding: 2pt 2pt;
      color: #000000;
      text-align: center;
      font-size: 9.0pt;
      line-height: 1.15;
      vertical-align: middle;
    }

    .report-table tfoot td {
      border-top: 0.75pt solid #7e7f7e;
      border-bottom: 0.75pt solid #7e7f7e;
      font-weight: bold;
    }

    /* ── Text Color Utilities ───────────────────────────────────── */
    .txt-bold  { font-weight: bold; }
    .txt-green { color: #196b24; }
    .txt-gray  { color: #808080; }
    .stat-overdue  { font-weight: bold; color: #c00000; }
    .stat-returned { font-weight: bold; color: #196b24; }
    .stat-borrowed { font-weight: bold; color: #78206e; }

    /* ── Page Break Controller ──────────────────────────────────── */
    .page-break {
      page-break-after: always;
      break-after: page;
      height: 0;
      margin: 0;
      padding: 0;
    }

    /* ── Signatures ─────────────────────────────────────────────── */
    .sig-tbl {
      width: 100%;
      border-collapse: collapse;
      margin-top: 38pt;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .sig-left {
      width: 50%;
      vertical-align: top;
      padding-left: 4pt;
      text-align: left;
    }

    .sig-right {
      width: 50%;
      vertical-align: top;
      padding-left: 20pt;
      text-align: left;
    }

    .sig-label {
      font-size: 11.04pt;
      color: #000000;
      margin-bottom: 26pt;
      font-weight: normal;
    }

    .sig-name {
      font-size: 11.04pt;
      font-weight: bold;
      color: #000000;
      margin-bottom: 1.5pt;
    }

    .sig-title {
      font-size: 11.04pt;
      color: #000000;
      font-weight: normal;
    }
  </style>
</head>
<body>

<div class="report-sheet">

  <!-- ════════════════════════════════════════════════════════════ -->
  <!-- PAGE 1: SECTIONS 1 TO 4                                      -->
  <!-- ════════════════════════════════════════════════════════════ -->

  <!-- Top Official Institutional Letterhead -->
  <?php $renderInstitutionalHeader(); ?>

  <!-- Document Title -->
  <div class="doc-title">Executive Institutional Property &amp; Circulation Report</div>

  <!-- Metadata Ribbon (4 Columns) -->
  <table class="meta-ribbon">
    <tr>
      <td>
        <div class="meta-label">REPORTING PERIOD</div>
        <div class="meta-value"><?= htmlspecialchars((string)($meta['timeframe_label'] ?? 'Last 30 Days'), ENT_QUOTES, 'UTF-8') ?></div>
      </td>
      <td>
        <div class="meta-label">GENERATED ON</div>
        <div class="meta-value"><?= htmlspecialchars((string)($meta['generated_on'] ?? date('Y-m-d H:i:s')), ENT_QUOTES, 'UTF-8') ?></div>
      </td>
      <td>
        <div class="meta-label">TARGET SCOPE</div>
        <div class="meta-value"><?= htmlspecialchars((string)($meta['target_scope'] ?? 'All Categories'), ENT_QUOTES, 'UTF-8') ?></div>
      </td>
      <td>
        <div class="meta-label">LOCATION</div>
        <div class="meta-value"><?= htmlspecialchars((string)($meta['location'] ?? 'All Location'), ENT_QUOTES, 'UTF-8') ?></div>
      </td>
    </tr>
  </table>

  <!-- SECTION 1: INVENTORY SUMMARY/OVERVIEW REPORT -->
  <div class="sec-heading">1. INVENTORY SUMMARY/OVERVIEW REPORT</div>
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:15.0%;">PROPERTY<br>CATEGORY</th>
        <th style="width:12.7%;">TOTAL<br>ITEMS</th>
        <th style="width:14.6%;">AVAILABLE</th>
        <th style="width:13.0%;">BORROWED</th>
        <th style="width:16.9%;">UNDER<br>MAINTENANCE</th>
        <th style="width:13.5%;">LOST AND<br>MISSING</th>
        <th style="width:14.3%;">DAMAGED</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      // Standard canonical 8 categories from reference template
      $displayCategories = [
          'IT Equipment'                       => 'IT Equipment',
          'Aquaculture Equipment'              => 'Fisheries and Aquaculture Equipment',
          'Fisheries and Aquaculture Equipment'=> 'Fisheries and Aquaculture Equipment',
          'Laboratory Tools'                   => 'Laboratory Tools',
          'Instructional Materials'            => 'Instructional Materials',
          'Office Equipment'                   => 'Office Equipment',
          'Furniture & Fixtures'               => 'Furniture & Fixtures',
          'Audio/Visual Equipment'             => 'Audio/Visual Equipment',
          'Other / General'                    => 'Others',
          'Others'                             => 'Others',
      ];

      // Index summary rows by normalized label
      $indexedRows = [];
      foreach ($summaryRows as $sr) {
          $rawCat = $sr['category'] ?? '';
          $mappedLabel = $displayCategories[$rawCat] ?? $rawCat;
          $indexedRows[$mappedLabel] = $sr;
      }

      $orderedCategories = [
          'IT Equipment',
          'Fisheries and Aquaculture Equipment',
          'Laboratory Tools',
          'Instructional Materials',
          'Office Equipment',
          'Furniture & Fixtures',
          'Audio/Visual Equipment',
          'Others'
      ];

      foreach ($orderedCategories as $catName):
          $row = $indexedRows[$catName] ?? [
              'total_items' => 0, 'available' => 0, 'borrowed' => 0,
              'under_maintenance' => 0, 'lost_and_missing' => 0, 'damaged' => 0
          ];

          $fmtVal = function($val) {
              $n = (int)$val;
              return $n > 0 ? (string)$n : '-';
          };
      ?>
        <tr>
          <td><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= $fmtVal($row['total_items'] ?? 0) ?></td>
          <td><?= $fmtVal($row['available'] ?? 0) ?></td>
          <td><?= $fmtVal($row['borrowed'] ?? 0) ?></td>
          <td><?= $fmtVal($row['under_maintenance'] ?? 0) ?></td>
          <td><?= $fmtVal($row['lost_and_missing'] ?? 0) ?></td>
          <td><?= $fmtVal($row['damaged'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <?php
      $fmtTot = function($val) {
          $n = (int)$val;
          return $n > 0 ? (string)$n : '-';
      };
      ?>
      <tr>
        <td class="txt-bold">Total</td>
        <td><?= $fmtTot($summaryTotals['total_items'] ?? 0) ?></td>
        <td><?= $fmtTot($summaryTotals['available'] ?? 0) ?></td>
        <td><?= $fmtTot($summaryTotals['borrowed'] ?? 0) ?></td>
        <td><?= $fmtTot($summaryTotals['under_maintenance'] ?? 0) ?></td>
        <td><?= $fmtTot($summaryTotals['lost_and_missing'] ?? 0) ?></td>
        <td><?= $fmtTot($summaryTotals['damaged'] ?? 0) ?></td>
      </tr>
    </tfoot>
  </table>

  <!-- SECTION 2: BORROWING AND RETURN HISTORY -->
  <div class="sec-heading">2. BORROWING AND RETURN HISTORY</div>
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:16.8%;">PROPERTY/<br>BARCODE</th>
        <th style="width:17.8%;">BORROWER<br>IDENTITY</th>
        <th style="width:17.1%;">DATE<br>BORROWED</th>
        <th style="width:17.3%;">RETURN DATE</th>
        <th style="width:13.3%;">LOCATION</th>
        <th style="width:17.7%;">CONDITION<br>UPON RETURN</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($borrowingHistory)): ?>
        <tr>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
        </tr>
      <?php else: ?>
        <?php foreach ($borrowingHistory as $bh): 
          $propName = htmlspecialchars((string)($bh['item_name'] ?? 'Asset'), ENT_QUOTES, 'UTF-8');
          $barcode  = htmlspecialchars((string)($bh['barcode'] ?? ''), ENT_QUOTES, 'UTF-8');
          $bName    = htmlspecialchars((string)($bh['borrower_name'] ?? ''), ENT_QUOTES, 'UTF-8');
          $bId      = htmlspecialchars((string)($bh['borrower_id_number'] ?? ''), ENT_QUOTES, 'UTF-8');
          $dateBor  = !empty($bh['date_borrowed']) ? date('m-d-y', strtotime((string)$bh['date_borrowed'])) : '-';
          $retDate  = !empty($bh['return_date']) ? date('m-d-y', strtotime((string)$bh['return_date'])) : '-';
          $loc      = htmlspecialchars((string)($bh['location'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $cond     = htmlspecialchars((string)($bh['condition_upon_return'] ?? '-'), ENT_QUOTES, 'UTF-8');

          $status = strtolower(trim((string)($bh['status'] ?? 'borrowed')));
        ?>
          <tr>
            <td>
              <div class="txt-bold"><?= $propName ?></div>
              <div><?= $barcode ?></div>
            </td>
            <td>
              <?php if ($bName !== ''): ?>
                <div class="txt-green"><?= $bName ?></div>
                <div class="txt-gray"><?= $bId ?></div>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= $dateBor ?></td>
            <td>
              <?php if ($retDate !== '-'): ?>
                <div><?= $retDate ?></div>
              <?php endif; ?>
              <?php if ($status === 'overdue'): ?>
                <div class="stat-overdue">OVERDUE</div>
              <?php elseif ($status === 'returned'): ?>
                <div class="stat-returned">RETURNED</div>
              <?php else: ?>
                <div class="stat-borrowed">BORROWED</div>
              <?php endif; ?>
            </td>
            <td><?= $loc ?: '-' ?></td>
            <td><?= $cond ?: '-' ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- SECTION 3: DAMAGED PROPERTIES REPORT -->
  <div class="sec-heading">3. DAMAGED PROPERTIES REPORT</div>
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:12.5%;">PROPERTY/<br>BARCODE</th>
        <th style="width:8.8%;">DATE</th>
        <th style="width:13.8%;">DAMAGE<br>INSPECTION<br>REMARKS</th>
        <th style="width:14.2%;">BORROWER<br>&amp; ID</th>
        <th style="width:12.5%;">LOCATION</th>
        <th style="width:13.4%;">ASSIGNED<br>TECHNICIAN</th>
        <th style="width:12.0%;">REPAIR<br>COST</th>
        <th style="width:12.9%;">CURRENT<br>STATUS</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($damagedProperties)): ?>
        <tr>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
        </tr>
      <?php else: ?>
        <?php foreach ($damagedProperties as $dp): 
          $propName = htmlspecialchars((string)($dp['item_name'] ?? 'Asset'), ENT_QUOTES, 'UTF-8');
          $barcode  = htmlspecialchars((string)($dp['barcode'] ?? ''), ENT_QUOTES, 'UTF-8');
          $repDate  = !empty($dp['reported_date']) ? date('m-d-y', strtotime((string)$dp['reported_date'])) : '-';
          $remarks  = htmlspecialchars((string)($dp['damage_remarks'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $bName    = htmlspecialchars((string)($dp['borrower_name'] ?? ''), ENT_QUOTES, 'UTF-8');
          $bId      = htmlspecialchars((string)($dp['borrower_id_number'] ?? ''), ENT_QUOTES, 'UTF-8');
          $loc      = htmlspecialchars((string)($dp['location'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $tech     = htmlspecialchars((string)($dp['assigned_technician'] ?? ''), ENT_QUOTES, 'UTF-8');
          $techTel  = htmlspecialchars((string)($dp['technician_contact'] ?? ''), ENT_QUOTES, 'UTF-8');
          $costVal  = !empty($dp['repair_cost']) ? number_format((float)$dp['repair_cost'], 2) : '';
          $costType = strtoupper(trim((string)($dp['cost_type'] ?? 'ACTUAL')));
          $status   = htmlspecialchars((string)($dp['status'] ?? '-'), ENT_QUOTES, 'UTF-8');
        ?>
          <tr>
            <td>
              <div class="txt-bold"><?= $propName ?></div>
              <div><?= $barcode ?></div>
            </td>
            <td><?= $repDate ?></td>
            <td><?= $remarks ?: '-' ?></td>
            <td>
              <?php if ($bName !== ''): ?>
                <div class="txt-green"><?= $bName ?></div>
                <div class="txt-gray"><?= $bId ?></div>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= $loc ?: '-' ?></td>
            <td>
              <?php if ($tech !== ''): ?>
                <div class="txt-green"><?= $tech ?></div>
                <div class="txt-gray"><?= $techTel ?></div>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td>
              <?php if ($costVal !== ''): ?>
                <div><?= $costVal ?></div>
                <div class="txt-green txt-bold"><?= $costType ?></div>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= $status ?: '-' ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- SECTION 4: LOST AND MISSING PROPERTIES REPORT -->
  <div class="sec-heading">4. LOST AND MISSING PROPERTIES REPORT</div>
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:12.7%;">PROPERTY/<br>BARCODE</th>
        <th style="width:12.9%;">LAST<br>RECORDED<br>LOCATION</th>
        <th style="width:16.2%;">ACCOUNTABLE<br>PERSON</th>
        <th style="width:12.2%;">DATE<br>REPORTED</th>
        <th style="width:15.1%;">DESCRIPTION</th>
        <th style="width:16.6%;">INVESTIGATION<br>STATUS</th>
        <th style="width:14.3%;">RESOLUTION</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($lostProperties)): ?>
        <tr>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
        </tr>
      <?php else: ?>
        <?php foreach ($lostProperties as $lp): 
          $propName = htmlspecialchars((string)($lp['item_name'] ?? 'Asset'), ENT_QUOTES, 'UTF-8');
          $barcode  = htmlspecialchars((string)($lp['barcode'] ?? ''), ENT_QUOTES, 'UTF-8');
          $loc      = htmlspecialchars((string)($lp['location'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $person   = htmlspecialchars((string)($lp['accountable_person'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $repDate  = !empty($lp['reported_date']) ? date('m-d-y', strtotime((string)$lp['reported_date'])) : '-';
          $desc     = htmlspecialchars((string)($lp['incident_description'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $invStat  = htmlspecialchars((string)($lp['investigation_status'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $res      = htmlspecialchars((string)($lp['resolution'] ?? '-'), ENT_QUOTES, 'UTF-8');
        ?>
          <tr>
            <td>
              <div class="txt-bold"><?= $propName ?></div>
              <div><?= $barcode ?></div>
            </td>
            <td><?= $loc ?: '-' ?></td>
            <td><?= $person ?: '-' ?></td>
            <td><?= $repDate ?></td>
            <td><?= $desc ?: '-' ?></td>
            <td><?= $invStat ?: '-' ?></td>
            <td><?= $res ?: '-' ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- SECTION 5: PROPERTY MOVEMENT AND LOCATION HISTORY -->
  <div class="sec-heading">5. PROPERTY MOVEMENT AND LOCATION HISTORY</div>
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:16.7%;">PROPERTY/<br>BARCODE</th>
        <th style="width:14.3%;">PREVIOUS<br>LOCATION</th>
        <th style="width:16.0%;">NEW<br>LOCATION</th>
        <th style="width:17.7%;">DATE<br>TRANSFERRED</th>
        <th style="width:17.6%;">REASON</th>
        <th style="width:17.7%;">TRANSFERRED<br>BY</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($propertyMovements)): ?>
        <tr>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
          <td>-</td>
        </tr>
      <?php else: ?>
        <?php foreach ($propertyMovements as $pm): 
          $propName = htmlspecialchars((string)($pm['item_name'] ?? 'Asset'), ENT_QUOTES, 'UTF-8');
          $barcode  = htmlspecialchars((string)($pm['barcode'] ?? ''), ENT_QUOTES, 'UTF-8');
          $prevLoc  = htmlspecialchars((string)($pm['previous_location'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $newLoc   = htmlspecialchars((string)($pm['new_location'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $tDate    = !empty($pm['transfer_date']) ? date('m-d-y', strtotime((string)$pm['transfer_date'])) : '-';
          $reason   = htmlspecialchars((string)($pm['reason'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $by       = htmlspecialchars((string)($pm['transferred_by'] ?? '-'), ENT_QUOTES, 'UTF-8');
        ?>
          <tr>
            <td>
              <div class="txt-bold"><?= $propName ?></div>
              <div><?= $barcode ?></div>
            </td>
            <td><?= $prevLoc ?: '-' ?></td>
            <td><?= $newLoc ?: '-' ?></td>
            <td><?= $tDate ?></td>
            <td><?= $reason ?: '-' ?></td>
            <td><?= $by ?: '-' ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Certified Signatory Block -->
  <table class="sig-tbl">
    <tr>
      <td class="sig-left">
        <div class="sig-label">PREPARED BY:</div>
        <div class="sig-name">ARLIE MARIE F. BENIEGAS</div>
        <div class="sig-title">Over-all Property Custodian</div>
      </td>
      <td class="sig-right">
        <div class="sig-label">APPROVED BY:</div>
        <div class="sig-name">LYN VARGAS-TAPAN, PhD</div>
        <div class="sig-title">Campus Director</div>
      </td>
    </tr>
  </table>

</div>

</body>
</html>
