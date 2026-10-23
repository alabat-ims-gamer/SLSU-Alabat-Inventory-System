<?php
/**
 * Delete / Decommission Asset Modal
 * Provides destructive confirmation with mandatory justification reason and hybrid safeguard indicators.
 * Follows Stripe-inspired design tokens and reusable UI components.
 */
?>

<!-- Delete Asset Modal Overlay -->
<div id="delete-asset-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="delete-asset-modal-title">
  <div class="modal-panel sm" style="max-height:90vh;display:flex;flex-direction:column;">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="delete-asset-modal-title" style="color:var(--color-ruby);display:flex;align-items:center;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
          Delete or Archive Asset
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Institutional audit accountability & safeguard verification
        </div>
      </div>
      <button class="modal-close" data-modal-close="delete-asset-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Modal Body / Form -->
    <form id="delete-asset-form" style="display:flex;flex-direction:column;flex:1;overflow-y:auto;">
      <input type="hidden" id="delete-item-id" name="item_id" value="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <div class="modal-body" style="padding:1.25rem 1.5rem;">
        
        <!-- Error / Warning Alert Box -->
        <div id="delete-modal-alert" style="display:none;margin-bottom:1rem;padding:.75rem 1rem;border-radius:var(--radius-md);background:var(--color-ruby-subtle);border:1px solid rgba(223, 27, 65, 0.2);color:var(--color-ruby);font-size:13px;line-height:1.4;">
        </div>

        <!-- Asset Target Summary Card -->
        <div class="card-soft" style="padding:1rem;margin-bottom:1.25rem;border-left:3px solid var(--color-ruby);background:var(--color-surface);">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">
            <span id="delete-display-barcode" class="font-mono" style="font-weight:600;font-size:13px;color:var(--color-primary);">BC-000000</span>
            <span id="delete-display-cond" class="badge badge-maintenance">Condition</span>
          </div>
          <div id="delete-display-name" style="font-size:14px;font-weight:500;color:var(--color-ink);margin-bottom:.25rem;">Asset Name</div>
          <div id="delete-display-room" style="font-size:12px;color:var(--color-ink-mute);">Location</div>
        </div>

        <!-- Safeguard Contextual Note -->
        <div style="font-size:12px;color:var(--color-ink-secondary);background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--radius-md);padding:.75rem;margin-bottom:1.25rem;line-height:1.5;">
          <strong style="color:var(--color-ink);display:block;margin-bottom:2px;">⚠️ Institutional Safeguard Rule:</strong>
          If this item has zero past borrow history, it will be permanently purged. If it has past borrow/return records, it will safely be queued into the <strong>Disposal Staging Queue</strong> (as Condemned / For Disposal) to protect government auditing compliance and await official decommissioning.
        </div>

        <!-- Mandatory Justification Reason -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label required" for="delete-reason" style="font-weight:500;">
            Reason for Deletion / Disposal
          </label>
          <textarea 
            id="delete-reason" 
            name="reason" 
            class="form-input" 
            rows="3" 
            required 
            placeholder="Please state why this asset is being deleted or disposed (e.g., Registered by mistake, Broken beyond repair, Broken in lab, Duplicate entry)..."
            style="resize:vertical;font-size:13px;line-height:1.5;min-height:75px;"
          ></textarea>
          <span style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;display:block;">
            This justification will be permanently stamped into the immutable audit ledger.
          </span>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--color-hairline);background:var(--color-canvas);">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="delete-asset-modal">
          Cancel
        </button>
        <button type="submit" id="btn-confirm-delete" class="btn btn-primary btn-sm" style="border-radius:var(--radius-pill);background:var(--color-ruby);border-color:var(--color-ruby);color:#fff;">
          Confirm Delete / Archive
        </button>
      </div>

    </form>
  </div>
</div>
