<?php
/**
 * Asset Maintenance & B.E.R. Lifecycle Modals
 * 1. Restore from Maintenance (Repair & Tested -> Available)
 * 2. Declare Beyond Economic Repair (B.E.R. -> For Disposal Queue)
 * Follows Stripe-inspired design tokens and reusable UI components.
 */
?>

<!-- ── Modal 1: Restore Asset to Service (Repaired & Tested) ── -->
<div id="modal-restore-asset" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="restore-modal-title">
  <div class="modal-panel sm" style="max-height:90vh;display:flex;flex-direction:column;">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="restore-modal-title" style="color:#059669;display:flex;align-items:center;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          Restore Asset to Service
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Mark maintenance complete &amp; return to active circulation
        </div>
      </div>
      <button type="button" class="modal-close" data-modal-close="modal-restore-asset" aria-label="Close" onclick="closeRestoreModal()">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Form -->
    <form id="form-restore-asset" onsubmit="handleRestoreSubmit(event)" style="display:flex;flex-direction:column;flex:1;overflow-y:auto;margin:0;">
      <input type="hidden" id="restore-item-id" name="item_id">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <div class="modal-body" style="padding:1.25rem 1.5rem;">
        <!-- Target Asset Info Banner -->
        <div class="card-soft" style="padding:0.75rem 1rem;margin-bottom:1.25rem;border-left:3px solid #059669;background:var(--color-surface);">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <span style="font-size:12px;color:var(--color-ink-mute);">Asset Name:</span>
            <strong style="color:var(--color-ink);font-size:13px;" id="restore-item-name">-</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;color:var(--color-ink-mute);">Barcode:</span>
            <span class="font-mono" style="font-size:12px;font-weight:600;color:var(--color-primary);" id="restore-item-barcode">-</span>
          </div>
        </div>

        <!-- Restored Condition Selector -->
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label required" for="restore-condition" style="font-weight:500;">
            Restored Operational Condition
          </label>
          <select id="restore-condition" name="condition" class="form-input" style="width:100%;font-size:13px;" required>
            <option value="good" selected>Good Condition (Normal Working Order)</option>
            <option value="fair">Fair Condition (Minor Functional Wear)</option>
            <option value="mint">Mint Condition (Like New / Pristine)</option>
          </select>
        </div>

        <!-- Maintenance / Repair Notes -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="restore-notes" style="font-weight:500;">
            Repair / Maintenance Notes
          </label>
          <textarea 
            id="restore-notes" 
            name="notes" 
            class="form-input" 
            rows="3" 
            placeholder="e.g. Screen replaced, calibrated, verified functional by IT technician."
            style="width:100%;font-size:13px;resize:vertical;min-height:75px;"
          ></textarea>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--color-hairline);background:var(--color-canvas);">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="modal-restore-asset" onclick="closeRestoreModal()">
          Cancel
        </button>
        <button type="submit" id="btn-submit-restore" class="btn btn-primary btn-sm" style="border-radius:var(--radius-pill);background:#059669;border-color:#059669;color:#ffffff;display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
          Restore to Available
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── Modal 2: Declare Beyond Economic Repair (B.E.R.) ───────── -->
<div id="modal-ber-asset" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="ber-modal-title">
  <div class="modal-panel sm" style="max-height:90vh;display:flex;flex-direction:column;">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="ber-modal-title" style="color:#d97706;display:flex;align-items:center;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          Declare Beyond Economic Repair (B.E.R.)
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Condemn equipment &amp; queue for institutional disposal
        </div>
      </div>
      <button type="button" class="modal-close" data-modal-close="modal-ber-asset" aria-label="Close" onclick="closeBerModal()">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Form -->
    <form id="form-ber-asset" onsubmit="handleBerSubmit(event)" style="display:flex;flex-direction:column;flex:1;overflow-y:auto;margin:0;">
      <input type="hidden" id="ber-item-id" name="item_id">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <div class="modal-body" style="padding:1.25rem 1.5rem;">
        <!-- Target Asset Info Banner -->
        <div class="card-soft" style="padding:0.75rem 1rem;margin-bottom:1.25rem;border-left:3px solid #d97706;background:var(--color-surface);">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <span style="font-size:12px;color:var(--color-ink-mute);">Asset Name:</span>
            <strong style="color:var(--color-ink);font-size:13px;" id="ber-item-name">-</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;color:var(--color-ink-mute);">Barcode:</span>
            <span class="font-mono" style="font-size:12px;font-weight:600;color:var(--color-primary);" id="ber-item-barcode">-</span>
          </div>
        </div>

        <!-- Safeguard Warning Notice -->
        <div style="font-size:12px;color:var(--color-ink-secondary);background:rgba(217,119,6,0.06);border:1px solid rgba(217,119,6,0.25);border-radius:var(--radius-md);padding:.75rem;margin-bottom:1.25rem;line-height:1.5;">
          <strong style="color:#b45309;display:block;margin-bottom:2px;">⚠️ Condemnation Lifecycle Rule:</strong>
          Declaring this unit B.E.R. transitions its status to <strong>For Disposal</strong>. It will be scheduled for the institutional Waste Materials Report (IIRUP) under the <strong>Disposal Management</strong> module.
        </div>

        <!-- Condemnation Justification -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label required" for="ber-reason" style="font-weight:500;">
            Condemnation Justification Reason
          </label>
          <textarea 
            id="ber-reason" 
            name="reason" 
            class="form-input" 
            rows="3" 
            placeholder="Detailed justification (e.g., Mainboard burned out, repair quote exceeds 80% of replacement value, parts obsolete)..."
            style="width:100%;font-size:13px;resize:vertical;min-height:75px;"
            required
          ></textarea>
          <span style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;display:block;">
            This justification will be permanently stamped into the government asset audit ledger.
          </span>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--color-hairline);background:var(--color-canvas);">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="modal-ber-asset" onclick="closeBerModal()">
          Cancel
        </button>
        <button type="submit" id="btn-submit-ber" class="btn btn-primary btn-sm" style="border-radius:var(--radius-pill);background:#d97706;border-color:#d97706;color:#ffffff;display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          Condemn to Disposal Queue
        </button>
      </div>
    </form>
  </div>
</div>
