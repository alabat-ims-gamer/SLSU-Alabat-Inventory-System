<?php
/**
 * Shared Gate Pass Issuance Modal Component
 * Southern Luzon State University – Alabat Campus (SLSU-Alabat IMS)
 * Supports multi-item bundling, rapid consecutive barcode scanning, borrower validation, and audio feedback.
 */
?>
<div id="issue-gatepass-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="issue-gatepass-title">
    <div class="modal-panel lg" style="background:#ffffff;border-radius:var(--radius-lg,12px);width:100%;max-width:840px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 45px rgba(0,0,0,0.22);border:1px solid var(--color-hairline,rgba(22,101,52,0.12));overflow:hidden;">
        
        <!-- Modal Header -->
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:flex;align-items:flex-start;justify-content:space-between;background:var(--color-canvas-soft,#f8fafc);">
            <div>
                <h2 id="issue-gatepass-title" style="font-size:18px;font-weight:600;color:var(--color-ink,#0f172a);margin:0;">Issue Official Gate Pass Permit</h2>
                <div style="font-size:12px;color:var(--color-ink-mute,#64748b);margin-top:2px;">Scan equipment barcodes to bundle borrowed assets into this official university permit</div>
            </div>
            <button type="button" class="modal-close" onclick="closeNewPassModal()" data-modal-close="issue-gatepass-modal" aria-label="Close" style="background:none;border:none;cursor:pointer;color:var(--color-ink-mute,#64748b);padding:4px;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div style="padding:1.5rem;overflow-y:auto;flex:1;">
            
            <!-- Step 1: Barcode Scan Bar -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:var(--radius-md,8px);padding:1rem;margin-bottom:1.25rem;">
                <label style="font-size:11.5px;font-weight:600;color:var(--color-ink,#0f172a);text-transform:uppercase;letter-spacing:0.04em;display:block;margin-bottom:6px;">
                    Scan or Enter Item Barcode / Property Number
                </label>
                <div style="display:flex;gap:8px;">
                    <div style="position:relative;flex:1;">
                        <input type="text" id="scan_barcode_input" class="form-input" placeholder="e.g. BC-100293 or Property Tag (Press Enter to add)" style="width:100%;padding-left:36px;font-family:monospace;font-size:13.5px;" autofocus onkeydown="if(event.key==='Enter'){event.preventDefault();handleBarcodeLookup();}">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--color-ink-mute,#64748b);"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18M3 15h18M9 3v18M15 3v18"></path></svg>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="handleBarcodeLookup()" id="lookup_btn" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#166534;color:#ffffff;border-radius:8px;border:none;cursor:pointer;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span>Scan / Add</span>
                    </button>
                </div>

                <!-- Live Status Feedback Message -->
                <div id="lookup_feedback" style="display:none;font-size:12px;margin-top:8px;padding:6px 10px;border-radius:6px;"></div>
            </div>

            <!-- Step 2: Form Header Details (Borrower & Purpose) -->
            <form id="create_gatepass_form" method="POST" action="<?= url('/inspector/gate-passes/create') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="requisitioner_id" id="requisitioner_id" value="">
                <input type="hidden" name="items" id="staged_items_json" value="[]">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label class="form-label" for="requisitioner_name" style="font-size:12px;font-weight:500;color:var(--color-ink,#0f172a);margin-bottom:4px;display:block;">
                            Requisitioner Name <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" name="requisitioner_name" id="requisitioner_name" class="form-input" placeholder="e.g. Prof. Alan Santos" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
                    </div>
                    <div>
                        <label class="form-label" for="requisitioning_office" style="font-size:12px;font-weight:500;color:var(--color-ink,#0f172a);margin-bottom:4px;display:block;">
                            Requisitioning Office / Department <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" name="requisitioning_office" id="requisitioning_office" class="form-input" placeholder="e.g. BSIT Department" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
                    </div>
                </div>

                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="purpose" style="font-size:12px;font-weight:500;color:var(--color-ink,#0f172a);margin-bottom:4px;display:block;">
                        Purpose of Transport <span style="color:#ef4444;">*</span>
                    </label>
                    <textarea name="purpose" id="purpose" class="form-input" rows="2" placeholder="e.g. Off-campus instructional lab training & research field survey" required style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;"></textarea>
                </div>

                <!-- Step 3: Staged Items Table -->
                <div style="border:1px solid var(--color-hairline,rgba(22,101,52,0.12));border-radius:var(--radius-md,8px);overflow:hidden;margin-bottom:1rem;">
                    <div style="padding:0.75rem 1rem;background:var(--color-canvas-soft,#f8fafc);border-bottom:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:13px;font-weight:600;color:var(--color-ink,#0f172a);">Items Bundled into this Gate Pass (<span id="staged_count">0</span>)</span>
                        <span id="staged_valuation_badge" class="num" style="font-size:12px;font-weight:600;color:#166534;font-feature-settings:'tnum';">₱ 0.00 Total</span>
                    </div>

                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:12px;">
                            <thead>
                                <tr style="background:#f8fafc;border-bottom:1px solid var(--color-hairline,#cbd5e1);text-align:left;color:var(--color-ink-mute,#64748b);">
                                    <th style="padding:7px 10px;">Item Description</th>
                                    <th style="padding:7px 10px;">Model</th>
                                    <th style="padding:7px 10px;">Serial No.</th>
                                    <th style="padding:7px 10px;text-align:right;">Unit Valuation</th>
                                    <th style="padding:7px 10px;text-align:center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="staged_items_tbody">
                                <tr id="empty_staged_row">
                                    <td colspan="5" style="padding:2rem 1rem;text-align:center;color:var(--color-ink-mute,#64748b);">
                                        Scan an item barcode above to add borrowed assets to this permit.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Signatories Note -->
                <div style="background:#fcfdfe;border:1px dashed #cbd5e1;border-radius:6px;padding:0.75rem 1rem;font-size:11px;color:#64748b;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div><strong>Inspection Officer:</strong> MANUEL S. VALDERAMA, MIT</div>
                    <div><strong>Property Custodian:</strong> VERONICA C. OBIEN</div>
                    <div><strong>Carrier:</strong> Authorized Requisitioner</div>
                    <div><strong>Gate Verification:</strong> Campus Security Force</div>
                </div>

            </form>
        </div>

        <!-- Modal Footer -->
        <div style="padding:1rem 1.5rem;border-top:1px solid var(--color-hairline,rgba(22,101,52,0.12));display:flex;justify-content:flex-end;gap:8px;background:var(--color-canvas-soft,#f8fafc);">
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeNewPassModal()" data-modal-close="issue-gatepass-modal" style="padding:6px 12px;border:1px solid #cbd5e1;background:#ffffff;border-radius:6px;cursor:pointer;">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="submit_permit_btn" onclick="submitGatePass()" disabled style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#166534;color:#ffffff;border-radius:6px;border:none;cursor:pointer;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span>Generate &amp; Print Permit</span>
            </button>
        </div>

    </div>
</div>

<script>
let stagedItems = [];

// Sound Synthesizer on Scan (880Hz beep)
function playBeepSound() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.08);
    } catch(e) {}
}

function openNewPassModal() {
    if (window.Modal && typeof window.Modal.open === 'function') {
        window.Modal.open('issue-gatepass-modal');
    } else {
        const modal = document.getElementById('issue-gatepass-modal');
        if (modal) {
            if (modal.parentElement !== document.body) document.body.appendChild(modal);
            modal.classList.add('open');
            document.body.classList.add('modal-open');
        }
    }
    setTimeout(() => {
        const input = document.getElementById('scan_barcode_input');
        if (input) input.focus();
    }, 150);
}

function closeNewPassModal() {
    if (window.Modal && typeof window.Modal.close === 'function') {
        window.Modal.close('issue-gatepass-modal');
    } else {
        const modal = document.getElementById('issue-gatepass-modal');
        if (modal) {
            modal.classList.remove('open');
            document.body.classList.remove('modal-open');
        }
    }
    hideFeedback();
}

function resetPassModalState() {
    stagedItems = [];
    renderStagedItems();
    const barcodeInput = document.getElementById('scan_barcode_input');
    if (barcodeInput) barcodeInput.value = '';
    const reqName = document.getElementById('requisitioner_name');
    if (reqName) reqName.value = '';
    const reqOffice = document.getElementById('requisitioning_office');
    if (reqOffice) reqOffice.value = '';
    const purpose = document.getElementById('purpose');
    if (purpose) purpose.value = '';
    const reqId = document.getElementById('requisitioner_id');
    if (reqId) reqId.value = '';
    hideFeedback();
}

function showFeedback(type, message) {
    const el = document.getElementById('lookup_feedback');
    if (!el) return;
    el.style.display = 'block';
    if (type === 'success') {
        el.style.background = '#ecfdf5';
        el.style.color = '#065f46';
        el.style.border = '1px solid #a7f3d0';
    } else {
        el.style.background = '#fef2f2';
        el.style.color = '#991b1b';
        el.style.border = '1px solid #fecaca';
    }
    el.textContent = message;
}

function hideFeedback() {
    const el = document.getElementById('lookup_feedback');
    if (el) el.style.display = 'none';
}

/**
 * Global API helper: stages an item and opens the modal automatically
 */
window.stageAndOpenGatePass = function(barcode) {
    if (!barcode) return;
    openNewPassModal();
    const input = document.getElementById('scan_barcode_input');
    if (input) input.value = barcode;
    handleBarcodeLookup(barcode);
};

async function handleBarcodeLookup(targetBarcode) {
    const input = document.getElementById('scan_barcode_input');
    const barcode = (targetBarcode || (input ? input.value : '')).trim().toUpperCase();
    if (!barcode) return;

    // Check duplicate in staged list
    if (stagedItems.some(i => i.barcode === barcode || i.property_no === barcode)) {
        showFeedback('error', 'Item (' + barcode + ') is already added to this Gate Pass.');
        if (input) { input.select(); input.focus(); }
        return;
    }

    const btn = document.getElementById('lookup_btn');
    if (btn) btn.disabled = true;
    hideFeedback();

    try {
        const lookupUrl = (window.appUrl ? window.appUrl('/api/gate-pass/lookup') : '/api/gate-pass/lookup') + '?barcode=' + encodeURIComponent(barcode);
        const resp = await fetch(lookupUrl, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();

        if (resp.ok && data.status === 'success') {
            playBeepSound();

            // Borrower Validation: ensure subsequent items belong to the same borrower
            const currentReqId = document.getElementById('requisitioner_id').value;
            const newBorrowerId = data.borrower ? String(data.borrower.user_id || '') : '';
            const newBorrowerName = data.borrower ? (data.borrower.name || 'Borrower') : '';

            if (stagedItems.length > 0 && currentReqId && newBorrowerId && currentReqId !== newBorrowerId) {
                showFeedback('error', `Borrower Mismatch: Scanned asset is checked out to ${newBorrowerName}. A Gate Pass can only bundle items for the same borrower.`);
                if (input) { input.select(); input.focus(); }
                return;
            }

            // Auto-populate borrower details if first item
            if (stagedItems.length === 0 && data.borrower) {
                document.getElementById('requisitioner_name').value = data.borrower.name || '';
                document.getElementById('requisitioning_office').value = data.borrower.department || '';
                document.getElementById('requisitioner_id').value = data.borrower.user_id || '';
                if (data.loan && data.loan.purpose) {
                    document.getElementById('purpose').value = data.loan.purpose;
                }
            }

            // Append item to staged list
            stagedItems.push({
                item_id: data.item.item_id,
                transaction_id: data.loan ? data.loan.transaction_id : null,
                barcode: data.item.barcode,
                property_no: data.item.property_no,
                item_description: data.item.description || data.item.item_name,
                model: data.item.model || 'N/A',
                serial_no: data.item.serial_no || 'N/A',
                date_acquired: data.item.date_acquired || '',
                unit_price: parseFloat(data.item.unit_price || 0),
                total_amount: parseFloat(data.item.total_amount || data.item.unit_price || 0),
                remarks: 'Good condition'
            });

            renderStagedItems();
            showFeedback('success', 'Added: ' + data.item.item_name + ' (' + barcode + ')');
            if (input) {
                input.value = '';
                input.focus();
            }

        } else {
            showFeedback('error', data.message || 'Asset not found or not currently borrowed.');
            if (input) { input.select(); input.focus(); }
        }
    } catch (e) {
        showFeedback('error', 'Network error querying barcode: ' + e.message);
    } finally {
        if (btn) btn.disabled = false;
    }
}

function removeStagedItem(idx) {
    stagedItems.splice(idx, 1);
    renderStagedItems();
    const input = document.getElementById('scan_barcode_input');
    if (input) input.focus();
}

function renderStagedItems() {
    const tbody = document.getElementById('staged_items_tbody');
    const countEl = document.getElementById('staged_count');
    const badgeEl = document.getElementById('staged_valuation_badge');
    const submitBtn = document.getElementById('submit_permit_btn');
    if (!tbody || !countEl || !badgeEl || !submitBtn) return;

    countEl.textContent = stagedItems.length;

    if (stagedItems.length === 0) {
        tbody.innerHTML = '<tr id="empty_staged_row"><td colspan="5" style="padding:2rem 1rem;text-align:center;color:var(--color-ink-mute,#64748b);">Scan an item barcode above to add borrowed assets to this permit.</td></tr>';
        badgeEl.textContent = '₱ 0.00 Total';
        submitBtn.disabled = true;
        document.getElementById('staged_items_json').value = '[]';
        return;
    }

    submitBtn.disabled = false;
    let totalVal = 0.0;
    let html = '';

    stagedItems.forEach((item, idx) => {
        totalVal += (item.total_amount || 0.0);
        html += `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px 10px;">
                    <div style="font-weight:600;color:#0f172a;">${escapeHtml(item.item_description)}</div>
                    <div style="font-size:10px;font-family:monospace;color:#166534;">${escapeHtml(item.barcode)}</div>
                </td>
                <td style="padding:8px 10px;color:#334155;">${escapeHtml(item.model || 'N/A')}</td>
                <td style="padding:8px 10px;font-family:monospace;font-size:11px;color:#334155;">${escapeHtml(item.serial_no || 'N/A')}</td>
                <td style="padding:8px 10px;text-align:right;font-feature-settings:'tnum';font-weight:600;color:#0f172a;">₱ ${formatNumber(item.total_amount)}</td>
                <td style="padding:8px 10px;text-align:center;">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="removeStagedItem(${idx})" style="color:#ef4444;background:none;border:none;cursor:pointer;padding:2px 6px;" title="Remove Item">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    badgeEl.textContent = '₱ ' + formatNumber(totalVal) + ' Total';
    document.getElementById('staged_items_json').value = JSON.stringify(stagedItems);
}

async function submitGatePass() {
    const form = document.getElementById('create_gatepass_form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    if (stagedItems.length === 0) {
        showFeedback('error', 'Please scan and add at least one item.');
        return;
    }

    const btn = document.getElementById('submit_permit_btn');
    btn.disabled = true;
    btn.innerHTML = '<span>Issuing Permit...</span>';

    const formData = new FormData(form);

    try {
        const resp = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        const result = await resp.json();

        if (resp.ok && result.status === 'success') {
            closeNewPassModal();
            // Redirect to gate-passes with open_print param to trigger direct PDF download
            const passId = result.gate_pass_id || '';
            const printParam = passId ? ('&open_print=' + encodeURIComponent(passId)) : '';
            const targetUrl = (window.appUrl ? window.appUrl('/inspector/gate-passes') : '/inspector/gate-passes') + '?success=' + encodeURIComponent(result.message) + printParam;
            window.location.href = targetUrl;
        } else {
            showFeedback('error', result.message || 'Failed to issue Gate Pass.');
            btn.disabled = false;
            btn.innerHTML = '<span>Generate &amp; Print Permit</span>';
        }
    } catch (e) {
        showFeedback('error', 'Server error issuing permit: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<span>Generate &amp; Print Permit</span>';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

function formatNumber(num) {
    return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
