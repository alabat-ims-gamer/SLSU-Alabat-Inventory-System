<?php
/**
 * Countdown Timer Component
 * Usage: component('countdown-timer', ['targetDate' => '2025-09-01 14:00:00', 'warningThresholdMinutes' => 30])
 */
$targetDate = $props['targetDate'] ?? date('Y-m-d H:i:s', time() + 3600);
$warning    = $props['warningThresholdMinutes'] ?? 30;
$id         = 'countdown-' . substr(md5($targetDate), 0, 6);
?>
<span
  id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
  class="countdown-timer"
  data-countdown="<?= htmlspecialchars($targetDate, ENT_QUOTES, 'UTF-8') ?>"
  data-countdown-warn="<?= (int)$warning ?>"
  aria-live="polite"
>
  Calculating...
</span>
