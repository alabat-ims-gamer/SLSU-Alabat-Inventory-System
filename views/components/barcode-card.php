<?php
/**
 * Barcode Card Component — Printable Asset Label
 * Usage: component('barcode-card', ['barcode'=>'BC-100293','propertyName'=>'...','propertyNumber'=>'...','category'=>'...','room'=>'...'])
 */
$barcode        = $props['barcode']        ?? 'BC-000000';
$propertyName   = $props['propertyName']   ?? 'Asset Name';
$propertyNumber = $props['propertyNumber'] ?? 'SLSU-000-000';
$category       = $props['category']       ?? 'General';
$room           = $props['room']           ?? 'Storage';
$id             = 'barcode-card-' . substr(md5($barcode), 0, 6);
?>
<div class="barcode-card" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
  <!-- Institution Header -->
  <div style="text-align:center;border-bottom:1px solid var(--color-hairline);padding-bottom:.5rem;margin-bottom:.5rem;">
    <div style="font-size:10px;font-weight:500;color:var(--color-ink);letter-spacing:.2px;">SLSU - Alabat Campus</div>
    <div style="font-size:9px;color:var(--color-ink-mute);">Property &amp; Inventory System</div>
  </div>

  <!-- Barcode SVG rendered by JsBarcode -->
  <svg data-barcode="<?= htmlspecialchars($barcode, ENT_QUOTES, 'UTF-8') ?>"
       style="width:100%;height:48px;"
       aria-label="Barcode for <?= htmlspecialchars($propertyName, ENT_QUOTES, 'UTF-8') ?>"></svg>

  <!-- Asset Info -->
  <div class="barcode-card-name"><?= htmlspecialchars($propertyName, ENT_QUOTES, 'UTF-8') ?></div>
  <div class="barcode-card-sub"><?= htmlspecialchars($propertyNumber, ENT_QUOTES, 'UTF-8') ?></div>
  <div class="barcode-card-sub"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($room, ENT_QUOTES, 'UTF-8') ?></div>
</div>
