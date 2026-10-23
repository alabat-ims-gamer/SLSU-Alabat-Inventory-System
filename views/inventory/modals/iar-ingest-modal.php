<?php
/**
 * Ingest IAR Modal — Custodian Digital Handover & Asset Tagging
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 */
?>

<!-- Ingest IAR Modal Overlay -->
<div id="ingest-iar-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="ingest-iar-modal-title">
  <div class="modal-panel lg" style="max-width:980px;width:95%;max-height:90vh;display:flex;flex-direction:column;border-radius:16px;">
    
    <!-- Modal Header -->
    <div class="modal-header" style="background:var(--color-canvas-soft);padding:18px 24px;border-bottom:1px solid var(--color-hairline);">
      <div>
        <div style="font-size:11px;font-weight:600;letter-spacing:0.5px;color:var(--color-primary);text-transform:uppercase;margin-bottom:2px;">
          FORM AFA-SAF-1.016 DIGITAL HANDOVER
        </div>
        <h2 class="modal-title" id="ingest-iar-modal-title" style="margin:0;font-size:18px;font-weight:400;color:var(--color-ink);">
          Tag &amp; Ingest Certified Deliveries
        </h2>
      </div>
      <button class="modal-close" data-modal-close="ingest-iar-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Modal Body (Scrollable) -->
    <div class="modal-body" style="overflow-y:auto;padding:1.5rem;flex:1;">
      
      <!-- Error & Warning Alert Banner -->
      <div id="ingest-modal-alert" style="display:none;padding:.75rem 1rem;border-radius:var(--radius-md);margin-bottom:1.25rem;font-size:13px;"></div>

      <!-- IAR Selection & Details -->
      <div style="background:var(--color-canvas-soft);padding:14px 18px;border-radius:8px;border:1px solid var(--color-hairline);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div>
          <div style="font-size:11px;color:var(--color-ink-mute);text-transform:uppercase;font-weight:600;">Certified Report</div>
          <div id="ingest-iar-summary-title" style="font-size:15px;font-weight:500;color:var(--color-primary);">Select an IAR to ingest</div>
          <div id="ingest-iar-summary-sub" style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;"></div>
        </div>
        <div id="ingest-iar-scan-preview-btn-wrap" style="display:none;">
          <a id="ingest-iar-scan-link" href="#" target="_blank" class="btn btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:6px 14px;background:#ffffff;border:1px solid var(--color-hairline);color:var(--color-ocean);text-decoration:none;font-size:12px;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            View Wet-Signed Scan
          </a>
        </div>
      </div>

      <!-- Line Items Staging Form -->
      <form id="ingest-iar-form">
        <input type="hidden" id="ingest-iar-id" name="iar_id" value="">
        
        <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;">
          <div style="font-size:13px;font-weight:500;color:var(--color-ink);">
            Physical Items from IAR Certification
          </div>
          <div style="font-size:12px;color:var(--color-ink-mute);">
            Verify hardware serials &amp; affix barcode tags
          </div>
        </div>

        <div style="overflow-x:auto;border:1px solid var(--color-hairline);border-radius:8px;margin-bottom:1rem;">
          <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
              <tr style="background:var(--color-canvas-soft);color:var(--color-ink-secondary);border-bottom:1px solid var(--color-hairline);text-align:left;">
                <th style="padding:8px 10px;width:30px;">#</th>
                <th style="padding:8px 10px;">Item Name &amp; Description</th>
                <th style="padding:8px 10px;width:130px;">Barcode Tag</th>
                <th style="padding:8px 10px;width:130px;">Property Number</th>
                <th style="padding:8px 10px;width:130px;">Category</th>
                <th style="padding:8px 10px;width:130px;">Storage Location</th>
                <th style="padding:8px 10px;width:90px;text-align:right;">Valuation (₱)</th>
                <th style="padding:8px 10px;width:60px;text-align:center;">Borrow</th>
              </tr>
            </thead>
            <tbody id="ingest-iar-items-tbody">
              <!-- Injected dynamically via JS based on selected IAR -->
            </tbody>
          </table>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:12px;border-top:1px solid var(--color-hairline);">
          <button type="button" class="btn btn-ghost btn-sm" data-modal-close="ingest-iar-modal" style="border-radius:9999px;padding:8px 16px;">
            Cancel
          </button>
          <button type="submit" id="btn-submit-ingest-iar" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:8px 20px;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            Commit to Master Inventory
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
