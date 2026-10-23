<?php
/**
 * Report Lost or Missing Asset Modal
 * Allows custodians to record missing equipment, accountable staff, and initial investigation status.
 */
?>
<!-- Report Lost / Missing Asset Modal Overlay -->
<div id="modal-report-lost" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="report-lost-modal-title">
  <div class="modal-panel sm" style="max-height:90vh;display:flex;flex-direction:column;border-radius:var(--radius-lg);">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="report-lost-modal-title" style="color:var(--color-ruby);display:flex;align-items:center;gap:.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          Report Asset as Lost / Missing
        </h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Log incident to the Institutional Lost &amp; Missing Ledger under formal investigation.
        </div>
      </div>
      <button class="modal-close" data-modal-close="modal-report-lost" aria-label="Close" onclick="closeLostModal()">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="modal-body" style="overflow-y:auto;padding:1.25rem 1.5rem;flex:1;">
      
      <div id="lost-modal-alert" style="display:none;padding:.75rem 1rem;border-radius:var(--radius-md);margin-bottom:1rem;font-size:12.5px;"></div>

      <!-- Asset Summary Banner -->
      <div style="padding:.75rem 1rem;background:rgba(234, 34, 97, 0.05);border:1px solid rgba(234, 34, 97, 0.2);border-radius:var(--radius-md);margin-bottom:1.25rem;">
        <div style="font-size:11px;font-weight:600;color:var(--color-ruby);text-transform:uppercase;letter-spacing:0.5px;">
          Target Asset
        </div>
        <div id="lost-item-name" style="font-size:14px;font-weight:600;color:var(--color-ink);margin-top:2px;">
          Asset Name
        </div>
        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:flex;gap:1rem;">
          <span>Barcode: <strong id="lost-item-barcode" class="font-mono">BC-000000</strong></span>
          <span>Last Location: <strong id="lost-item-location">—</strong></span>
        </div>
      </div>

      <form id="form-report-lost">
        <input type="hidden" id="lost-item-id" name="item_id" value="">
        
        <!-- Accountable Person -->
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label required" for="lost-accountable-person" style="font-size:12px;font-weight:600;">
            Accountable Person / Last In-Charge
          </label>
          <input 
            type="text" 
            id="lost-accountable-person" 
            name="accountable_person" 
            class="form-input" 
            style="width:100%;height:36px;font-size:12.5px;"
            placeholder="e.g. Laboratory In-Charge / Staff / Borrower Name" 
            required
            autocomplete="off"
          >
          <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
            Person officially accountable for custody of the property during physical audit.
          </div>
        </div>

        <!-- Date Reported -->
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label required" for="lost-date-reported" style="font-size:12px;font-weight:600;">
            Date Reported
          </label>
          <input 
            type="date" 
            id="lost-date-reported" 
            name="date_reported" 
            class="form-input" 
            style="width:100%;height:36px;font-size:12.5px;"
            value="<?= date('Y-m-d') ?>"
            required
          >
        </div>

        <!-- Investigation Status -->
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label required" for="lost-investigation-status" style="font-size:12px;font-weight:600;">
            Investigation Status
          </label>
          <select id="lost-investigation-status" name="investigation_status" class="form-input" style="width:100%;height:36px;font-size:12.5px;" required>
            <option value="under_investigation" selected>Under Investigation (Pending Audit / Verification)</option>
            <option value="confirmed_lost">Confirmed Lost (Unrecoverable / Officially Missing)</option>
          </select>
        </div>

        <!-- Incident Description / Remarks -->
        <div class="form-group" style="margin-bottom:1rem;">
          <label class="form-label required" for="lost-description" style="font-size:12px;font-weight:600;">
            Description &amp; Circumstances
          </label>
          <textarea 
            id="lost-description" 
            name="description" 
            class="form-input" 
            rows="3" 
            style="width:100%;font-size:12.5px;"
            placeholder="e.g. Item not found during annual inventory audit in Science Lab shelf 2."
            required
          ></textarea>
        </div>

        <!-- Warning Callout -->
        <div style="padding:.75rem;background:rgba(217, 119, 6, 0.08);border:1px solid rgba(217, 119, 6, 0.2);border-radius:var(--radius-sm);font-size:11.5px;color:#92400e;line-height:1.4;">
          ⚠️ <strong>Catalog Update:</strong> This item will be flagged with <strong>Status: Lost</strong> and appear in Section 4 of the official Executive Institutional Property Report.
        </div>
      </form>

    </div>

    <!-- Modal Footer -->
    <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 1.5rem;border-top:1px solid var(--color-hairline);background:var(--color-canvas);">
      <button type="button" class="btn btn-ghost btn-sm" data-modal-close="modal-report-lost" onclick="closeLostModal()">
        Cancel
      </button>
      <button type="button" id="btn-submit-report-lost" class="btn btn-sm" style="background:var(--color-ruby);color:#fff;border-radius:var(--radius-pill);padding:6px 16px;">
        Submit Lost Report
      </button>
    </div>

  </div>
</div>
