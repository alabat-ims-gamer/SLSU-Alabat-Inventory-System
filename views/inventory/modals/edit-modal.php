<?php
/**
 * Edit Asset Modal — Metadata & Condition Updates with Optimistic Locking
 * Follows Stripe-inspired design tokens and locks immutable identifiers (ID & Barcode).
 */
?>

<!-- Edit Asset Modal Overlay -->
<div id="edit-asset-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="edit-asset-modal-title">
  <div class="modal-panel md" style="max-height:90vh;display:flex;flex-direction:column;">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="edit-asset-modal-title">Edit Asset Details</h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Update specifications, room location, or physical health condition
        </div>
      </div>
      <button class="modal-close" data-modal-close="edit-asset-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="modal-body" style="overflow-y:auto;padding:1.5rem;flex:1;">
      
      <!-- Error / Conflict Alert Banner -->
      <div id="edit-modal-alert" style="display:none;padding:.75rem 1rem;border-radius:var(--radius-md);margin-bottom:1.25rem;font-size:13px;"></div>

      <form id="edit-asset-form" method="POST" action="<?= url('/inventory/edit') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" id="edit-item-id" name="item_id" value="">
        <input type="hidden" id="edit-original-updated-at" name="original_updated_at" value="">

        <!-- Immutable Identifiers & Status Banner -->
        <div style="padding:.75rem 1rem;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-md);margin-bottom:1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
          <div>
            <div style="font-size:11px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:.5px;">
              System Barcode (Immutable)
            </div>
            <div id="edit-display-barcode" class="font-mono" style="font-size:13px;font-weight:600;color:var(--color-primary);margin-top:2px;">
              BC-000000
            </div>
          </div>
          <div>
            <div style="font-size:11px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:.5px;">
              Record ID
            </div>
            <div id="edit-display-id" class="font-mono" style="font-size:13px;font-weight:500;color:var(--color-ink);margin-top:2px;">
              #0
            </div>
          </div>
          <div>
            <div style="font-size:11px;font-weight:500;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:.5px;">
              Current Status
            </div>
            <div id="edit-display-status" style="margin-top:2px;">
              <span class="badge badge-approved">Available</span>
            </div>
          </div>
        </div>

        <!-- Active Loan Lockout Warning Banner -->
        <div id="edit-borrowed-lock-banner" style="display:none;background:rgba(217, 119, 6, 0.08);border:1px solid rgba(217, 119, 6, 0.25);border-radius:var(--radius-md);padding:.75rem 1rem;color:#92400e;font-size:13px;margin-bottom:1.25rem;">
          <div style="display:flex;align-items:center;gap:.5rem;font-weight:600;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Active Borrow Lockout
          </div>
          <div style="font-size:12px;margin-top:2px;color:#78350f;line-height:1.4;">
            This asset is currently borrowed. Modifying specifications, room location, or physical health condition is locked until the asset is returned and checked in at the Scanner Station.
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
          <!-- Item Name -->
          <div class="form-group md:col-span-2">
            <label class="form-label required" for="edit-item-name">
              Item / Equipment Name
            </label>
            <input 
              id="edit-item-name" 
              name="item_name" 
              class="form-input" 
              type="text" 
              required
              autocomplete="off"
            >
          </div>

          <!-- Category -->
          <div class="form-group md:col-span-2">
            <label class="form-label required" for="edit-category">
              Asset Category
            </label>
            <select id="edit-category" name="category" class="form-input" style="width:100%;" required>
              <option value="IT Equipment">IT Equipment</option>
              <option value="Laboratory Tools">Laboratory Tools</option>
              <option value="Aquaculture Equipment">Aquaculture Equipment</option>
              <option value="Instructional Materials">Instructional Materials</option>
              <option value="Office Equipment">Office Equipment</option>
              <option value="Furniture &amp; Fixtures">Furniture &amp; Fixtures</option>
              <option value="Audio/Visual Equipment">Audio/Visual Equipment</option>
              <option value="Other / General">Other / General</option>
            </select>
          </div>

          <!-- Storage Facility & Specific Location -->
          <div class="form-group md:col-span-2" style="background:var(--color-canvas-soft);padding:14px;border:1px solid var(--color-hairline);border-radius:var(--radius-md);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <label class="form-label required" for="edit-facility-select" style="margin-bottom:0;font-weight:600;">
                Storage Facility / Department
              </label>
              <span style="font-size:11px;color:var(--color-ink-mute);">Standardized Campus Unit</span>
            </div>
            <select id="edit-facility-select" class="form-input" required style="width:100%;margin-bottom:8px;">
              <option value="" disabled selected>Select facility or laboratory...</option>
              <option value="Science Laboratory">Science Laboratory</option>
              <option value="Farm Laboratory">Farm Laboratory</option>
              <option value="GIS &amp; Comp. Lab">GIS &amp; Comp. Lab</option>
              <option value="Fisheries Laboratory">Fisheries Laboratory</option>
              <option value="Clinic">Clinic</option>
              <option value="Other">Other / Administrative Office</option>
            </select>

            <!-- Custom Office/Department input (visible only when 'Other' is selected) -->
            <div id="edit-custom-facility-group" style="display:none;margin-bottom:8px;">
              <label class="form-label required" for="edit-custom-facility" style="font-size:11px;font-weight:500;">
                Specify Office / Department Name
              </label>
              <input 
                id="edit-custom-facility" 
                class="form-input" 
                type="text" 
                placeholder="e.g. Supply Office, Library, Faculty Room" 
                autocomplete="off"
                maxlength="50"
              >
            </div>

            <!-- Specific Location (Room / Rack / Shelf / Bench) -->
            <div>
              <label class="form-label" for="edit-specific-location" style="display:flex;justify-content:space-between;align-items:center;font-size:11px;font-weight:500;">
                <span>Specific Location / Room / Cabinet</span>
                <span style="color:var(--color-ink-mute);font-weight:400;">Optional</span>
              </label>
              <input 
                id="edit-specific-location" 
                class="form-input" 
                type="text" 
                placeholder="e.g. Cabinet 3, Shelf B, Bench 4" 
                autocomplete="off"
                maxlength="70"
              >
            </div>

            <!-- Hidden actual location input submitted to backend -->
            <input type="hidden" id="edit-location" name="location" value="" required>

            <!-- Transfer Reason (Revealed when location changes) -->
            <div id="edit-transfer-reason-group" style="display:none;margin-top:10px;padding-top:10px;border-top:1px dashed var(--color-hairline);">
              <label class="form-label" for="edit-transfer-reason" style="font-size:11px;font-weight:600;color:var(--color-primary);display:flex;align-items:center;gap:4px;">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                Reason for Relocation / Transfer
              </label>
              <input 
                id="edit-transfer-reason" 
                name="transfer_reason" 
                class="form-input" 
                type="text" 
                placeholder="e.g. Department reallocation, Lab reorganization, Custody transfer" 
                autocomplete="off"
                maxlength="255"
              >
              <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
                Recorded in Section 5 (Property Movement &amp; Location History) of the Institutional Audit Report.
              </div>
            </div>
          </div>

          <!-- Property Number -->
          <div class="form-group">
            <label class="form-label" for="edit-property-number">
              Property Number (Optional)
            </label>
            <input 
              id="edit-property-number" 
              name="property_number" 
              class="form-input font-mono" 
              type="text" 
              placeholder="e.g. PROP-2024-001"
              autocomplete="off"
            >
          </div>

          <!-- Physical Condition with Dynamic Helper Text -->
          <div class="form-group">
            <label class="form-label required" for="edit-condition">
              Physical Health Condition
            </label>
            <select id="edit-condition" name="item_condition" class="form-input" required>
              <option value="mint">Mint Condition (Brand New / Pristine)</option>
              <option value="good">Good Condition (Normal Working Order)</option>
              <option value="fair">Fair Condition (Minor Functional Wear)</option>
              <option value="damaged">Damaged (Requires Repair / Maintenance)</option>
              <option value="not_working">Not Working (Defective / Unserviceable)</option>
              <option value="for_disposal">Condemned (Unserviceable / For Disposal)</option>
            </select>
            <div id="edit-condition-help" style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;line-height:1.4;"></div>
          </div>

          <!-- Student Borrowable Toggle -->
          <div class="form-group md:col-span-2">
            <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer;user-select:none;margin-top:.25rem;">
              <input 
                type="checkbox" 
                id="edit-is-student-borrowable" 
                name="is_student_borrowable" 
                value="1" 
                style="width:18px;height:18px;accent-color:var(--color-primary);border-radius:4px;"
              >
              <span style="font-size:13px;color:var(--color-ink);">
                Allow Student Borrowing (BSIT / BSFAS / BIndTech)
              </span>
            </label>
            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
              Uncheck to restrict asset exclusively to Faculty.
            </div>
          </div>

          <!-- Detailed Description & Specs -->
          <div class="form-group md:col-span-2">
            <label class="form-label" for="edit-description">Specifications &amp; Remarks</label>
            <textarea 
              id="edit-description" 
              name="description" 
              class="form-input" 
              rows="3" 
              placeholder="Technical specifications, serial numbers, condition notes..."
            ></textarea>
          </div>

        </div>

        <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--color-hairline);display:flex;justify-content:flex-end;gap:.75rem;">
          <button type="button" class="btn btn-ghost btn-sm" data-modal-close="edit-asset-modal">Cancel</button>
          <button type="submit" id="edit-submit-btn" class="btn btn-primary btn-sm" style="border-radius:var(--radius-pill);">
            Save Changes
          </button>
        </div>
      </form>

    </div>

  </div>
</div>

<script>
(function() {
  function initEditLocationSync() {
    const facilitySel = document.getElementById('edit-facility-select');
    const customGroup = document.getElementById('edit-custom-facility-group');
    const customInput = document.getElementById('edit-custom-facility');
    const specificInput = document.getElementById('edit-specific-location');
    const hiddenLoc = document.getElementById('edit-location');

    if (!facilitySel || !hiddenLoc) return;

    window.syncEditLocation = function() {
      const selVal = facilitySel.value;
      let base = '';
      if (selVal === 'Other') {
        customGroup.style.display = 'block';
        if (customInput) {
          customInput.required = true;
          base = customInput.value.trim();
        }
      } else {
        customGroup.style.display = 'none';
        if (customInput) {
          customInput.required = false;
        }
        base = selVal.trim();
      }

      const specific = specificInput ? specificInput.value.trim() : '';
      if (base && specific) {
        hiddenLoc.value = base + ' — ' + specific;
      } else if (base) {
        hiddenLoc.value = base;
      } else {
        hiddenLoc.value = '';
      }

      // Check against original location for transfer reason reveal
      const origLoc = (hiddenLoc.getAttribute('data-original-location') || '').trim();
      const currentLoc = hiddenLoc.value.trim();
      const reasonGroup = document.getElementById('edit-transfer-reason-group');
      if (reasonGroup) {
        if (origLoc !== '' && currentLoc !== '' && origLoc !== currentLoc) {
          reasonGroup.style.display = 'block';
        } else {
          reasonGroup.style.display = 'none';
        }
      }
    };

    facilitySel.addEventListener('change', window.syncEditLocation);
    if (customInput) customInput.addEventListener('input', window.syncEditLocation);
    if (specificInput) specificInput.addEventListener('input', window.syncEditLocation);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEditLocationSync);
  } else {
    initEditLocationSync();
  }
})();
</script>
