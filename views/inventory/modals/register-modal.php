<?php
/**
 * Register Asset Modal — Single/Batch Form + CSV Bulk Import
 * Follows Stripe-inspired design tokens and reusable UI components.
 */
?>

<!-- Register Asset Modal Overlay -->
<div id="register-asset-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="register-asset-modal-title">
  <div class="modal-panel lg" style="max-height:90vh;display:flex;flex-direction:column;">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="register-asset-modal-title">Register Physical Assets</h2>
        <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
          Intake new laboratory equipment, office supplies, or bulk classroom tools
        </div>
      </div>
      <button class="modal-close" data-modal-close="register-asset-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;display:block;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Intake Mode Tabs -->
    <div style="padding:0 1.5rem;background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);display:flex;gap:1.5rem;">
      <button type="button" id="tab-btn-form" class="tab-btn active" style="padding:.75rem 0;border-bottom:2px solid var(--color-primary);font-size:13px;font-weight:500;color:var(--color-primary);background:none;border-top:none;border-left:none;border-right:none;cursor:pointer;">
        Single / Batch Intake Form
      </button>
      <button type="button" id="tab-btn-csv" class="tab-btn" style="padding:.75rem 0;border-bottom:2px solid transparent;font-size:13px;font-weight:500;color:var(--color-ink-mute);background:none;border-top:none;border-left:none;border-right:none;cursor:pointer;">
        Spreadsheet Bulk Import (.CSV)
      </button>
    </div>

    <!-- Modal Body (Scrollable) -->
    <div class="modal-body" style="overflow-y:auto;padding:1.5rem;flex:1;">
      
      <!-- Error & Warning Alert Banner -->
      <div id="register-modal-alert" style="display:none;padding:.75rem 1rem;border-radius:var(--radius-md);margin-bottom:1.25rem;font-size:13px;"></div>

      <!-- Tab 1: Form Multiplier View -->
      <form id="register-asset-form" method="POST" action="<?= url('/inventory/register') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
          <!-- Item Name -->
          <div class="form-group md:col-span-2">
            <label class="form-label" for="reg-item-name">
              Item / Equipment Name <span style="color:var(--color-ruby);">*</span>
            </label>
            <input 
              id="reg-item-name" 
              name="item_name" 
              class="form-input" 
              type="text" 
              placeholder="e.g. Digital Storage Oscilloscope 100MHz" 
              required
              autocomplete="off"
            >
          </div>

          <!-- Category -->
          <div class="form-group md:col-span-2">
            <label class="form-label" for="reg-category">
              Asset Category <span style="color:var(--color-ruby);">*</span>
            </label>
            <select id="reg-category" name="category" class="form-input" style="width:100%;" required>
              <option value="" disabled selected>Select category...</option>
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
              <label class="form-label" for="reg-facility-select" style="margin-bottom:0;font-weight:600;">
                Storage Facility / Department <span style="color:var(--color-ruby);">*</span>
              </label>
              <span style="font-size:11px;color:var(--color-ink-mute);">Standardized Campus Unit</span>
            </div>
            <select id="reg-facility-select" class="form-input" required style="width:100%;margin-bottom:8px;">
              <option value="" disabled selected>Select facility or laboratory...</option>
              <option value="Science Laboratory">Science Laboratory</option>
              <option value="Farm Laboratory">Farm Laboratory</option>
              <option value="GIS &amp; Comp. Lab">GIS &amp; Comp. Lab</option>
              <option value="Fisheries Laboratory">Fisheries Laboratory</option>
              <option value="Clinic">Clinic</option>
              <option value="Other">Other / Administrative Office</option>
            </select>

            <!-- Custom Office/Department input (visible only when 'Other' is selected) -->
            <div id="reg-custom-facility-group" style="display:none;margin-bottom:8px;">
              <label class="form-label" for="reg-custom-facility" style="font-size:11px;font-weight:500;">
                Specify Office / Department Name <span style="color:var(--color-ruby);">*</span>
              </label>
              <input 
                id="reg-custom-facility" 
                class="form-input" 
                type="text" 
                placeholder="e.g. Supply Office, Library, Faculty Room" 
                autocomplete="off"
                maxlength="50"
              >
            </div>

            <!-- Specific Location (Room / Rack / Shelf / Bench) -->
            <div>
              <label class="form-label" for="reg-specific-location" style="display:flex;justify-content:space-between;align-items:center;font-size:11px;font-weight:500;">
                <span>Specific Location / Room / Cabinet</span>
                <span style="color:var(--color-ink-mute);font-weight:400;">Optional</span>
              </label>
              <input 
                id="reg-specific-location" 
                class="form-input" 
                type="text" 
                placeholder="e.g. Cabinet 3, Shelf B, Bench 4" 
                autocomplete="off"
                maxlength="70"
              >
            </div>

            <!-- Hidden actual location input submitted to backend -->
            <input type="hidden" id="reg-location" name="location" value="" required>
          </div>

          <!-- Base Property Number (Optional) -->
          <div class="form-group">
            <label class="form-label" for="reg-property-number" style="display:flex;justify-content:space-between;align-items:center;">
              <span>Base Property Number</span>
              <span id="prop-check-indicator" style="font-size:11px;font-weight:400;color:var(--color-ink-mute);">Optional</span>
            </label>
            <input 
              id="reg-property-number" 
              name="property_number" 
              class="form-input font-mono" 
              type="text" 
              placeholder="e.g. SLSU-IT-2026-0042" 
              autocomplete="off"
            >
            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;">
              If quantity &gt; 1, units are auto-suffixed (-1, -2, etc.).
            </div>
          </div>

          <!-- Quantity Multiplier (1-100) -->
          <div class="form-group">
            <label class="form-label" for="reg-quantity">
              Quantity Multiplier <span style="color:var(--color-ruby);">*</span>
            </label>
            <input 
              id="reg-quantity" 
              name="quantity" 
              class="form-input font-mono" 
              type="number" 
              min="1" 
              max="100" 
              value="1" 
              required
            >
            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;">
              Generates distinct sequential barcodes for each unit.
            </div>
          </div>

          <!-- Physical Condition / Health -->
          <div class="form-group">
            <label class="form-label" for="reg-condition">
              Physical Health Condition <span style="color:var(--color-ruby);">*</span>
            </label>
            <select id="reg-condition" name="item_condition" class="form-input" required>
              <option value="mint" selected>Mint Condition (Brand New / Pristine)</option>
              <option value="good">Good Condition (Normal Working Order)</option>
              <option value="fair">Fair Condition (Minor Functional Wear)</option>
              <option value="damaged">Damaged (Requires Repair / Maintenance)</option>
            </select>
          </div>

          <!-- Student Borrowable Toggle -->
          <div class="form-group" style="display:flex;flex-direction:column;justify-content:center;">
            <label class="form-label" style="margin-bottom:.5rem;">Borrower Eligibility</label>
            <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer;user-select:none;">
              <input 
                type="checkbox" 
                name="is_student_borrowable" 
                value="1" 
                checked 
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
            <label class="form-label" for="reg-description">Specifications &amp; Remarks</label>
            <textarea 
              id="reg-description" 
              name="description" 
              class="form-input" 
              rows="3" 
              placeholder="Serial numbers, technical specifications, accessories included, or supplier details..."
            ></textarea>
          </div>

        </div>

        <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--color-hairline);display:flex;justify-content:flex-end;gap:.75rem;">
          <button type="button" class="btn btn-ghost btn-sm" data-modal-close="register-asset-modal">Cancel</button>
          <button type="submit" id="reg-submit-btn" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Save &amp; Generate Barcodes
          </button>
        </div>
      </form>

      <!-- Tab 2: CSV Bulk Import View (Initially Hidden) -->
      <div id="register-csv-view" style="display:none;">
        <form id="import-csv-form" method="POST" enctype="multipart/form-data" action="<?= url('/inventory/import-csv') ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          
          <div style="border:2px dashed var(--color-hairline-input);border-radius:var(--radius-lg);padding:2.5rem 1.5rem;text-align:center;background:var(--color-canvas-soft);margin-bottom:1.25rem;">
            <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="var(--color-primary)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 1rem;display:block;">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="12" y1="18" x2="12" y2="12"></line>
              <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
            
            <div style="font-size:14px;font-weight:500;color:var(--color-ink);margin-bottom:.25rem;">
              Select or Drag &amp; Drop CSV File
            </div>
            <div style="font-size:12px;color:var(--color-ink-mute);margin-bottom:1.25rem;">
              Standard comma-separated spreadsheet (.csv) up to 5MB
            </div>

            <input 
              type="file" 
              id="csv-file-input" 
              name="csv_file" 
              accept=".csv,text/csv" 
              style="display:none;" 
              required
            >
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('csv-file-input').click();">
              Browse Files...
            </button>
            <div id="csv-selected-name" style="font-size:12px;font-weight:500;color:var(--color-primary);margin-top:.75rem;display:none;"></div>
          </div>

          <!-- Template Download & Format Guide -->
          <div style="padding:1rem;background:rgba(22, 101, 52,.04);border:1px solid rgba(22, 101, 52,.12);border-radius:var(--radius-md);margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
              <span style="font-size:12px;font-weight:500;color:var(--color-primary);">Required CSV Column Headers</span>
              <button type="button" id="download-sample-csv-btn" class="btn btn-ghost btn-sm" style="font-size:11px;height:24px;padding:0 8px;">
                Download Sample CSV
              </button>
            </div>
            <div style="font-size:11px;color:var(--color-ink-mute);line-height:1.6;font-family:monospace;">
              item_name, category, location, property_number, description, condition
            </div>
            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:.5rem;">
              * Unique Code 128 barcodes will be generated automatically for every valid line.
            </div>
          </div>

          <div style="padding-top:1rem;border-top:1px solid var(--color-hairline);display:flex;justify-content:flex-end;gap:.75rem;">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close="register-asset-modal">Cancel</button>
            <button type="submit" id="csv-submit-btn" class="btn btn-primary btn-sm">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"></path></svg>
              Upload &amp; Import Spreadsheet
            </button>
          </div>
        </form>
      </div>

      <!-- Tab 3: Success Barcode Cards View (Rendered after registration) -->
      <div id="register-success-view" style="display:none;">
        <div style="text-align:center;padding:1rem 0 1.5rem;">
          <div style="width:48px;height:48px;border-radius:50%;background:rgba(5,150,105,.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <div style="font-size:16px;font-weight:500;color:var(--color-ink);" id="reg-success-title">Assets Successfully Registered!</div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;" id="reg-success-sub">
            The items have been recorded with immutable barcodes. You can now print their labels.
          </div>
        </div>

        <div id="generated-barcodes-container" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));gap:1rem;max-height:280px;overflow-y:auto;padding:.5rem;border:1px solid var(--color-hairline);border-radius:var(--radius-md);margin-bottom:1.5rem;background:var(--color-canvas-soft);">
          <!-- Dynamic Barcode Cards Injected Here via JsBarcode -->
        </div>

        <div style="padding-top:1rem;border-top:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;">
          <button type="button" class="btn btn-ghost btn-sm" id="btn-register-another">
            + Register Another Asset
          </button>
          <div style="display:flex;gap:.5rem;">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close="register-asset-modal">Done</button>
            <button type="button" class="btn btn-primary btn-sm" id="btn-print-registered-barcodes">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
              Print Barcode Labels
            </button>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

<script>
(function() {
  function initRegLocationSync() {
    const form = document.getElementById('register-asset-form');
    const facilitySel = document.getElementById('reg-facility-select');
    const customGroup = document.getElementById('reg-custom-facility-group');
    const customInput = document.getElementById('reg-custom-facility');
    const specificInput = document.getElementById('reg-specific-location');
    const hiddenLoc = document.getElementById('reg-location');

    if (!facilitySel || !hiddenLoc) return;

    function syncLocation() {
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
    }

    facilitySel.addEventListener('change', syncLocation);
    if (customInput) customInput.addEventListener('input', syncLocation);
    if (specificInput) specificInput.addEventListener('input', syncLocation);

    if (form) {
      form.addEventListener('reset', function() {
        setTimeout(function() {
          if (customGroup) customGroup.style.display = 'none';
          if (customInput) customInput.required = false;
          if (hiddenLoc) hiddenLoc.value = '';
        }, 10);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRegLocationSync);
  } else {
    initRegLocationSync();
  }
})();
</script>
