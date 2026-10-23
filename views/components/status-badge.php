<?php
/**
 * Status Badge Component
 * Usage: component('status-badge', ['status' => 'available'])
 */
$status = strtolower($props['status'] ?? 'unknown');
$isCondition = !empty($props['is_condition']) || ((string)($props['type'] ?? '') === 'condition');

$labels = [
  'available'    => 'Available',
  'borrowed'     => 'Borrowed',
  'maintenance'  => 'Maintenance',
  'disposed'     => 'Disposed',
  'pending'      => 'Pending',
  'approved'     => 'Approved',
  'suspended'    => 'Suspended',
  'overdue'      => 'Overdue',
  'active'       => 'Active',
  'returned'     => 'Returned',
  'lost'         => 'Lost',
  'mint'         => 'Mint Condition',
  'good'         => 'Good Condition',
  'fair'         => 'Fair Condition',
  'damaged'      => 'Damaged',
  'not_working'  => 'Not Working',
  'for_disposal'         => $isCondition ? 'Condemned' : 'For Disposal',
  'condemned'            => 'Condemned',
  'awaiting_payment'     => 'Awaiting Payment',
  'pending_verification' => 'Pending Verification',
  'partially_paid'       => 'Partially Paid',
  'cleared'              => 'Cleared',
  'rejected'             => 'Receipt Rejected',
  'open_hold'            => 'Active Hold',
];

$badgeClasses = [
  'available'            => 'badge-approved',
  'borrowed'             => 'badge-borrowed',
  'maintenance'          => 'badge-maintenance',
  'disposed'             => 'badge-suspended',
  'pending'              => 'badge-pending',
  'approved'             => 'badge-approved',
  'suspended'            => 'badge-suspended',
  'overdue'              => 'badge-rejected',
  'mint'                 => 'badge-approved',
  'good'                 => 'badge-approved',
  'fair'                 => 'badge-pending',
  'damaged'              => 'badge-maintenance',
  'not_working'          => 'badge-rejected',
  'for_disposal'         => 'badge-suspended',
  'condemned'            => 'badge-suspended',
  'awaiting_payment'     => 'badge-rejected',
  'pending_verification' => 'badge-pending',
  'partially_paid'       => 'badge-pending',
  'cleared'              => 'badge-approved',
  'rejected'             => 'badge-rejected',
  'open_hold'            => 'badge-rejected',
];

$label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
$class = $badgeClasses[$status] ?? 'badge-' . $status;
?>
<span class="badge <?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
