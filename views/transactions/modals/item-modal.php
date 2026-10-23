<?php
/**
 * Dual-Tab Role-Adaptive Barcode Item Modal
 * Universal standard modal dialog, supporting full 8px backdrop blur and role-adaptive operations.
 */
?>
<div id="barcode-item-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-item-name">
  <div class="modal-panel lg" style="max-height:88vh;display:flex;flex-direction:column;">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap mb-1">
          <span id="modal-category-badge" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">—</span>
          <span id="modal-status-badge" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">—</span>
          <span id="modal-condition-badge" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">—</span>
        </div>
        <h2 id="modal-item-name" class="modal-title truncate">Loading Asset...</h2>
        <div class="flex items-center gap-3 text-xs text-slate-500 mt-0.5 font-mono">
          <span id="modal-barcode">—</span>
          <span>&bull;</span>
          <span id="modal-property-number">—</span>
        </div>
      </div>

      <button type="button" id="modal-close-btn" class="modal-close" data-modal-close="barcode-item-modal" aria-label="Close modal">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Tab Navigation -->
    <div class="flex border-b border-slate-200 bg-white px-6">
      <button type="button" id="tab-btn-overview" class="py-3 px-4 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 flex items-center gap-2 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        Item Overview &amp; Actions
      </button>
      <button type="button" id="tab-btn-history" class="py-3 px-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        Borrowing History (<span id="modal-history-count">0</span>)
      </button>
    </div>

    <!-- Modal Body (Scrollable) -->
    <div class="modal-body" style="padding:1.5rem;overflow-y:auto;flex:1;">

      <!-- ── TAB 1: Item Overview & Actions ──────────────────────── -->
      <div id="tab-content-overview" class="space-y-6">
        
        <!-- Key Asset Metadata Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs">
          <div>
            <span class="text-slate-500 block mb-0.5 font-medium">Storage Location</span>
            <span id="modal-location" class="font-semibold text-slate-900">—</span>
          </div>
          <div>
            <span class="text-slate-500 block mb-0.5 font-medium">Student Borrowable</span>
            <span id="modal-student-borrowable" class="font-semibold text-slate-900">—</span>
          </div>
          <div class="col-span-2 sm:col-span-1">
            <span class="text-slate-500 block mb-0.5 font-medium">Physical Condition</span>
            <span id="modal-condition-text" class="font-semibold text-slate-900 capitalize">—</span>
          </div>
          <div class="col-span-2 sm:col-span-3 pt-2 border-t border-slate-200">
            <span class="text-slate-500 block mb-0.5 font-medium">Description &amp; Specs</span>
            <span id="modal-description" class="text-slate-700 italic">No additional specifications logged.</span>
          </div>
        </div>

        <!-- Dynamic Action Panel (Injected based on user role and asset status) -->
        <div id="modal-action-panel" class="p-4 rounded-xl border transition-all">
          <div class="flex items-center justify-center py-6 text-slate-500 font-medium">
            <svg class="animate-spin w-6 h-6 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            Loading asset status...
          </div>
        </div>

      </div>

      <!-- ── TAB 2: Borrowing History Timeline ───────────────────── -->
      <div id="tab-content-history" class="hidden space-y-4">
        <div id="modal-history-container" class="space-y-4">
          <!-- Dynamically populated timeline or empty state -->
        </div>
      </div>

    </div>

  </div>
</div>
