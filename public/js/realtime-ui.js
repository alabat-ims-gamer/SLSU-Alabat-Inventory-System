/**
 * Realtime UI Synchronizer Engine
 * Connects RealtimeHub events to DOM elements:
 * - Micro-animated KPI counters (pulse, locale formatting)
 * - Dynamic Action Locking on concurrent borrows
 * - Non-destructive Table / Queue DOM patching
 */
(function(window) {
  'use strict';

  const RealtimeUI = {
    // KPI Alias Dictionary
    kpiAliases: {
      'ready_available': ['ready_available', 'available_assets', 'healthy_items'],
      'currently_borrowed': ['currently_borrowed', 'active_loans', 'borrowed_assets'],
      'under_maintenance': ['under_maintenance', 'maintenance_items'],
      'pending_disposal': ['pending_disposal', 'disposal_items'],
      'total_assets': ['total_assets', 'total_campus_assets', 'total_registered_assets', 'all_items'],
      'pending_approvals': ['pending_approvals'],
      'active_users': ['active_users'],
      'overdue_items': ['overdue_items', 'overdue_loans'],
      'pending_borrow_requests': ['pending_borrow_requests', 'pending_pickups', 'pending_holds']
    },

    init: function() {
      if (!window.RealtimeHub) {
        console.warn('[RealtimeUI] RealtimeHub not found on window; skipping.');
        return;
      }

      this.bindEvents();
    },

    /**
     * Increment or decrement a KPI counter in the DOM with a 60fps micro-pulse
     * @param {string} key - Logical key of KPI
     * @param {number} delta - Positive or negative integer
     */
    updateKpi: function(key, delta) {
      if (!delta || delta === 0) return;

      const keysToSearch = [key];
      // Resolve aliases
      for (const [canonical, aliases] of Object.entries(this.kpiAliases)) {
        if (aliases.includes(key) || canonical === key) {
          aliases.forEach(a => { if (!keysToSearch.includes(a)) keysToSearch.push(a); });
          break;
        }
      }

      keysToSearch.forEach(k => {
        const elements = document.querySelectorAll(`[data-kpi="${k}"] [data-kpi-value], [data-kpi-key="${k}"] [data-kpi-value]`);
        elements.forEach(el => {
          const rawText = el.textContent.replace(/[^0-9.-]/g, '');
          const currentVal = parseInt(rawText, 10);
          if (!isNaN(currentVal)) {
            const newVal = Math.max(0, currentVal + delta);
            el.textContent = newVal.toLocaleString();
            el.classList.remove('kpi-pulse');
            void el.offsetWidth; // trigger reflow
            el.classList.add('kpi-pulse');
            setTimeout(() => el.classList.remove('kpi-pulse'), 500);
          }
        });
      });
    },

    /**
     * Lock or unlock action buttons across the page for a specific asset
     * @param {number|string} itemId
     * @param {boolean} lock
     * @param {string} reason
     */
    setItemActionLock: function(itemId, lock, reason = 'Asset is currently in use') {
      const buttons = document.querySelectorAll(`[data-action-item="${itemId}"], [data-item-id="${itemId}"]`);
      buttons.forEach(btn => {
        if (btn.tagName === 'BUTTON' || btn.tagName === 'A') {
          if (btn.classList.contains('btn-edit-asset') || btn.classList.contains('btn-delete-asset')) {
            if (lock) {
              btn.classList.add('btn-locked-realtime');
              btn.setAttribute('aria-disabled', 'true');
              btn.setAttribute('title', reason);
            } else {
              btn.classList.remove('btn-locked-realtime');
              btn.removeAttribute('aria-disabled');
              btn.setAttribute('title', 'Action Available');
            }
          }
        }
      });
    },

    bindEvents: function() {
      const hub = window.RealtimeHub;

      // ── ITEM_STATUS_CHANGED ──
      hub.on('ITEM_STATUS_CHANGED', (event) => {
        const p = event.payload || {};
        if (p.status === 'borrowed') {
          this.updateKpi('currently_borrowed', +1);
          this.updateKpi('ready_available', -1);
          this.setItemActionLock(p.item_id, true, 'Asset checked out by another transaction');
        } else if (p.status === 'available') {
          this.updateKpi('ready_available', +1);
          this.updateKpi('currently_borrowed', -1);
          this.setItemActionLock(p.item_id, false);
        } else if (p.status === 'maintenance') {
          this.updateKpi('under_maintenance', +1);
          this.updateKpi('ready_available', -1);
        } else if (p.status === 'disposed' || p.status === 'for_disposal') {
          this.updateKpi('pending_disposal', +1);
          this.updateKpi('ready_available', -1);
        }
      });

      // ── ITEM_CREATED ──
      hub.on('ITEM_CREATED', (event) => {
        this.updateKpi('total_assets', +1);
        this.updateKpi('ready_available', +1);
      });

      // ── ITEM_DELETED ──
      hub.on('ITEM_DELETED', (event) => {
        this.updateKpi('total_assets', -1);
        this.updateKpi('ready_available', -1);
      });

      // ── USER_REGISTERED ──
      hub.on('USER_REGISTERED', (event) => {
        this.updateKpi('pending_approvals', +1);
      });

      // ── USER_STATUS_CHANGED ──
      hub.on('USER_STATUS_CHANGED', (event) => {
        const p = event.payload || {};
        this.updateKpi('pending_approvals', -1);
        if (p.status === 'approved') {
          this.updateKpi('active_users', +1);
        }
      });

      // ── LOAN_APPROVED ──
      hub.on('LOAN_APPROVED', (event) => {
        this.updateKpi('pending_borrow_requests', -1);
        this.updateKpi('currently_borrowed', +1);
      });

      // ── LOAN_RETURNED ──
      hub.on('LOAN_RETURNED', (event) => {
        const p = event.payload || {};
        this.updateKpi('currently_borrowed', -1);
        if (p.is_damaged) {
          this.updateKpi('under_maintenance', +1);
        } else {
          this.updateKpi('ready_available', +1);
        }
      });

      // ── CLEARANCE_RESOLVED ──
      hub.on('CLEARANCE_RESOLVED', (event) => {
        const p = event.payload || {};
        if (window.Toast && typeof window.Toast.success === 'function') {
          window.Toast.success('Clearance Updated', 'A clearance hold was marked settled and cleared.');
        }
      });
    }
  };

  // Expose globally
  window.RealtimeUI = RealtimeUI;

  // Auto-init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => RealtimeUI.init());
  } else {
    RealtimeUI.init();
  }

})(window);
