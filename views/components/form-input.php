<?php
/**
 * Form Input Component
 * Usage: component('form-input', ['name'=>'...','label'=>'...','type'=>'text','placeholder'=>'...','required'=>true,'error'=>'...'])
 */
$name        = $props['name']        ?? 'field';
$label       = $props['label']       ?? '';
$type        = $props['type']        ?? 'text';
$placeholder = $props['placeholder'] ?? '';
$value       = $props['value']       ?? '';
$required    = $props['required']    ?? false;
$error       = $props['error']       ?? null;
$helper      = $props['helper']      ?? null;
$pattern     = $props['pattern']     ?? null;
$autocomplete= $props['autocomplete']?? null;
$id          = $props['id']          ?? $name;
$options     = $props['options']     ?? []; // For select type
$rows        = $props['rows']        ?? 3;  // For textarea

$errorClass = $error ? ' error' : '';
?>
<div class="form-group">
  <?php if ($label): ?>
    <label class="form-label" for="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
      <?php if ($required): ?><span style="color:var(--color-ruby);margin-left:2px;">*</span><?php endif; ?>
    </label>
  <?php endif; ?>

  <?php if ($type === 'select'): ?>
    <select
      id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
      name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
      class="form-input form-select<?= $errorClass ?>"
      <?= $required ? 'required' : '' ?>
    >
      <?php foreach ($options as $optVal => $optLabel): ?>
        <option value="<?= htmlspecialchars((string)$optVal, ENT_QUOTES, 'UTF-8') ?>"
          <?= $value == $optVal ? 'selected' : '' ?>>
          <?= htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>

  <?php elseif ($type === 'textarea'): ?>
    <textarea
      id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
      name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
      class="form-input<?= $errorClass ?>"
      placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>"
      rows="<?= (int)$rows ?>"
      <?= $required ? 'required' : '' ?>
    ><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></textarea>

  <?php else: ?>
    <input
      id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
      name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
      type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
      class="form-input<?= $errorClass ?>"
      placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>"
      value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
      <?= $required   ? 'required'                                                    : '' ?>
      <?= $pattern    ? 'pattern="' . htmlspecialchars($pattern, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
      <?= $autocomplete ? 'autocomplete="' . htmlspecialchars($autocomplete, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
    >
  <?php endif; ?>

  <?php if ($helper && !$error): ?>
    <span class="form-helper"><?= htmlspecialchars($helper, ENT_QUOTES, 'UTF-8') ?></span>
  <?php endif; ?>

  <?php if ($error): ?>
    <span class="form-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
  <?php endif; ?>
</div>
