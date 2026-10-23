<?php
/**
 * Executive Admin Dashboard — Analytics & Institutional Intelligence Cockpit
 * Delegates directly to the shared Reports & Property Analytics Studio view (DRY principle).
 */
declare(strict_types=1);

$title      = $title ?? 'Executive Intelligence';
$breadcrumb = $breadcrumb ?? 'Executive Overview';

require __DIR__ . '/../reports/index.php';
