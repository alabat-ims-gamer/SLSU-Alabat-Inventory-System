/**
 * SLSU-Alabat IMS — Global Application JavaScript
 * Toast, Modal, Sidebar, API Wrapper, Countdown, Tabs
 */

'use strict';

/* ════════════════════════════════════════════════════════════
   1. TOAST / SNACKBAR ENGINE
   ════════════════════════════════════════════════════════════ */
const Toast = (() => {
  const DURATION = 4000; // ms

  const icons = {
    success: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`,
    error:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`,
    warning: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`,
    info:    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`,
  };

  const defaultTitles = {
    success: 'Success',
    error:   'Error',
    warning: 'Warning',
    info:    'Notice',
  };

  function getContainer() {
    let c = document.getElementById('toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'toast-container';
      c.setAttribute('aria-live', 'polite');
      c.setAttribute('aria-atomic', 'false');
      document.body.appendChild(c);
    }
    return c;
  }

  function show({ type = 'info', title, message, actionText, actionCallback, duration = DURATION }) {
    const container = getContainer();
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;

    el.innerHTML = `
      <div class="toast-header">
        <div class="toast-icon">${icons[type] || icons.info}</div>
        <div class="toast-content">
          <div class="toast-title">${_esc(title || defaultTitles[type] || 'Notification')}</div>
          ${message ? `<div class="toast-message">${_esc(message)}</div>` : ''}
        </div>
        <button class="toast-close" aria-label="Close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
      ${actionText ? `<button class="toast-action">${_esc(actionText)}</button>` : ''}
      <div class="toast-progress">
        <div class="toast-progress-bar" style="animation-duration:${duration}ms"></div>
      </div>
    `;

    container.appendChild(el);

    // Trigger animation
    requestAnimationFrame(() => {
      requestAnimationFrame(() => el.classList.add('show'));
    });

    // Close button
    el.querySelector('.toast-close').addEventListener('click', () => dismiss(el));

    // Action callback
    if (actionText && actionCallback) {
      el.querySelector('.toast-action').addEventListener('click', () => {
        actionCallback();
        dismiss(el);
      });
    }

    // Auto dismiss
    const timer = setTimeout(() => dismiss(el), duration);
    el._timer = timer;

    return el;
  }

  function dismiss(el) {
    if (!el || el._dismissing) return;
    el._dismissing = true;
    clearTimeout(el._timer);
    el.classList.remove('show');
    el.classList.add('dismissing');
    setTimeout(() => el.remove(), 350);
  }

  function _dispatch(type, arg1, arg2, opts = {}) {
    if (typeof arg1 === 'object' && arg1 !== null) {
      return show({ type, ...arg1 });
    }
    let title = defaultTitles[type] || 'Notification';
    let message = '';
    let options = opts;

    if (arg2 === undefined || (typeof arg2 === 'object' && arg2 !== null && !Array.isArray(arg2))) {
      // Single argument provided (message only)
      message = String(arg1 || '');
      if (typeof arg2 === 'object' && arg2 !== null) {
        options = arg2;
      }
    } else {
      // Two arguments provided (title, message)
      title = String(arg1 || defaultTitles[type] || 'Notification');
      message = String(arg2 || '');
    }

    return show({ type, title, message, ...options });
  }

  function initFromUrl() {
    try {
      const url = new URL(window.location.href);
      const params = url.searchParams;
      let toastTriggered = false;

      // Notice messages mapping for system codes
      const noticeMap = {
        'session_expired': 'Your session has expired due to inactivity. Please sign in again.',
        'session_revoked': 'Your session was terminated or signed out from another device.',
        'pending': 'Your registration is currently pending Administrator approval.',
        'suspended': 'This account has been deactivated. Please contact the administrator.'
      };

      if (params.has('success')) {
        const msg = params.get('success');
        if (msg) {
          _dispatch('success', msg);
          toastTriggered = true;
        }
      }

      if (params.has('error')) {
        const msg = params.get('error');
        if (msg) {
          _dispatch('error', msg);
          toastTriggered = true;
        }
      }

      if (params.has('warning')) {
        const msg = params.get('warning');
        if (msg) {
          _dispatch('warning', msg);
          toastTriggered = true;
        }
      }

      if (params.has('notice')) {
        let msg = params.get('notice');
        if (msg) {
          msg = noticeMap[msg] || msg;
          _dispatch('info', 'Notice', msg);
          toastTriggered = true;
        }
      }

      if (params.has('info')) {
        const msg = params.get('info');
        if (msg) {
          _dispatch('info', msg);
          toastTriggered = true;
        }
      }

      if (params.has('msg') || params.has('message')) {
        const msg = params.get('msg') || params.get('message');
        if (msg) {
          _dispatch('info', msg);
          toastTriggered = true;
        }
      }

      // Clean query parameters from address bar without reloading
      if (toastTriggered) {
        const cleanParams = ['success', 'error', 'warning', 'notice', 'info', 'msg', 'message'];
        let modified = false;
        cleanParams.forEach(p => {
          if (params.has(p)) {
            params.delete(p);
            modified = true;
          }
        });
        if (modified) {
          const newUrl = url.pathname + (params.toString() ? '?' + params.toString() : '') + url.hash;
          window.history.replaceState({}, document.title, newUrl);
        }
      }
    } catch (e) {
      console.warn('[Toast] Error parsing URL flash notifications:', e);
    }
  }

  return {
    success: (arg1, arg2, opts) => _dispatch('success', arg1, arg2, opts),
    error:   (arg1, arg2, opts) => _dispatch('error',   arg1, arg2, opts),
    warning: (arg1, arg2, opts) => _dispatch('warning', arg1, arg2, opts),
    info:    (arg1, arg2, opts) => _dispatch('info',    arg1, arg2, opts),
    show,
    dismiss,
    initFromUrl,
  };
})();

// Expose on window object
if (typeof window !== 'undefined') {
  window.Toast = Toast;
}


/* ════════════════════════════════════════════════════════════
/* ════════════════════════════════════════════════════════════
   2. MODAL & DIALOG ENGINE
   ════════════════════════════════════════════════════════════ */
const Modal = (() => {
  const stack = [];

  function ensureAttachedToBody(el) {
    if (el && el.parentElement !== document.body) {
      document.body.appendChild(el);
    }
  }

  function init() {
    // Relocate all modals to document.body so they are never trapped in transformed parent containers
    document.querySelectorAll('.modal-overlay, .modal-backdrop, .slideover-overlay, [role="dialog"]').forEach(el => {
      ensureAttachedToBody(el);
    });
  }

  function open(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return console.warn(`Modal "${id}" not found.`);
    
    // Ensure modal is attached directly to document.body
    ensureAttachedToBody(overlay);

    if (!stack.includes(id)) {
      stack.push(id);
    }
    overlay.style.zIndex = (1000 + (stack.length * 20)).toString();
    overlay.classList.add('open');
    overlay.style.display = 'flex';
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';

    // Focus first interactive element safely
    const firstInput = overlay.querySelector('input:not([type="hidden"]), select, textarea, button:not([data-modal-close]):not(.modal-close)');
    if (firstInput) {
      setTimeout(() => {
        try {
          firstInput.focus({ preventScroll: true });
        } catch (e) {
          firstInput.focus();
        }
      }, 120);
    }
  }

  function close(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.remove('open');
    overlay.style.display = '';
    overlay.style.zIndex = '';
    const idx = stack.indexOf(id);
    if (idx !== -1) stack.splice(idx, 1);
    if (stack.length === 0) {
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
    }
  }

  function closeTop() {
    if (stack.length > 0) close(stack[stack.length - 1]);
  }

  function confirmDialog(message, title = 'Confirm Action', confirmBtnText = 'Confirm', isDestructive = true) {
    return new Promise((resolve) => {
      const dialog = document.getElementById('confirm-dialog');
      if (!dialog) {
        resolve(false);
        return;
      }
      const titleEl = document.getElementById('confirm-dialog-title');
      const msgEl = document.getElementById('confirm-dialog-message');
      const confirmBtn = document.getElementById('confirm-dialog-confirm');
      const cancelBtns = dialog.querySelectorAll('[data-modal-close="confirm-dialog"], .modal-close');

      if (titleEl) titleEl.textContent = title;
      if (msgEl) msgEl.textContent = message;
      if (confirmBtn) {
        confirmBtn.textContent = confirmBtnText;
        if (isDestructive) {
          confirmBtn.className = 'btn btn-sm';
          confirmBtn.style.background = 'var(--color-ruby)';
          confirmBtn.style.color = '#ffffff';
          confirmBtn.style.borderColor = 'var(--color-ruby)';
          confirmBtn.style.borderRadius = 'var(--radius-pill)';
        } else {
          confirmBtn.className = 'btn btn-primary btn-sm';
          confirmBtn.style.background = 'var(--color-primary)';
          confirmBtn.style.color = '#ffffff';
          confirmBtn.style.borderColor = 'var(--color-primary)';
          confirmBtn.style.borderRadius = 'var(--radius-pill)';
        }
      }

      let resolved = false;

      function onConfirm(e) {
        e?.preventDefault();
        e?.stopPropagation();
        if (resolved) return;
        resolved = true;
        cleanup();
        close('confirm-dialog');
        resolve(true);
      }

      function onCancel(e) {
        e?.preventDefault();
        e?.stopPropagation();
        if (resolved) return;
        resolved = true;
        cleanup();
        close('confirm-dialog');
        resolve(false);
      }

      function onEsc(e) {
        if (e.key === 'Escape' && stack[stack.length - 1] === 'confirm-dialog') {
          onCancel(e);
        }
      }

      function onOverlayClick(e) {
        if (e.target === dialog) {
          onCancel(e);
        }
      }

      function cleanup() {
        confirmBtn?.removeEventListener('click', onConfirm);
        cancelBtns.forEach(btn => btn.removeEventListener('click', onCancel));
        document.removeEventListener('keydown', onEsc);
        dialog.removeEventListener('click', onOverlayClick);
      }

      confirmBtn?.addEventListener('click', onConfirm, { once: true });
      cancelBtns.forEach(btn => btn.addEventListener('click', onCancel, { once: true }));
      document.addEventListener('keydown', onEsc);
      dialog.addEventListener('click', onOverlayClick);

      open('confirm-dialog');
    });
  }

  function alertDialog(message, title = 'Notice', subtitle = '') {
    return new Promise((resolve) => {
      const dialog = document.getElementById('alert-dialog');
      if (!dialog) {
        if (window.Toast) {
          window.Toast.info(title, message);
        }
        resolve();
        return;
      }
      const titleEl = document.getElementById('alert-dialog-title');
      const subEl = document.getElementById('alert-dialog-subtitle');
      const msgEl = document.getElementById('alert-dialog-message');
      const okBtn = document.getElementById('alert-dialog-ok');
      const closeBtns = dialog.querySelectorAll('[data-modal-close="alert-dialog"], .modal-close');

      if (titleEl) titleEl.textContent = title;
      if (subEl) {
        subEl.textContent = subtitle;
        subEl.style.display = subtitle ? 'block' : 'none';
      }
      if (msgEl) msgEl.textContent = message;

      let resolved = false;

      function onDismiss(e) {
        e?.preventDefault();
        e?.stopPropagation();
        if (resolved) return;
        resolved = true;
        cleanup();
        close('alert-dialog');
        resolve();
      }

      function onEsc(e) {
        if (e.key === 'Escape' && stack[stack.length - 1] === 'alert-dialog') {
          onDismiss(e);
        }
      }

      function onOverlayClick(e) {
        if (e.target === dialog) {
          onDismiss(e);
        }
      }

      function cleanup() {
        okBtn?.removeEventListener('click', onDismiss);
        closeBtns.forEach(btn => btn.removeEventListener('click', onDismiss));
        document.removeEventListener('keydown', onEsc);
        dialog.removeEventListener('click', onOverlayClick);
      }

      okBtn?.addEventListener('click', onDismiss, { once: true });
      closeBtns.forEach(btn => btn.addEventListener('click', onDismiss, { once: true }));
      document.addEventListener('keydown', onEsc);
      dialog.addEventListener('click', onOverlayClick);

      open('alert-dialog');
    });
  }

  // Global ESC key handler
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeTop();
  });

  // Click-on-overlay & button open/close handlers
  document.addEventListener('click', e => {
    const overlay = e.target.closest('.modal-overlay, .modal-backdrop, .slideover-overlay');
    if (overlay && e.target === overlay) {
      close(overlay.id || undefined);
    }
    if (e.target.closest('[data-modal-close]')) {
      const id = e.target.closest('[data-modal-close]').dataset.modalClose;
      id ? close(id) : closeTop();
    }
    if (e.target.closest('[data-modal-open]')) {
      open(e.target.closest('[data-modal-open]').dataset.modalOpen);
    }
  });

  // Global form data-confirm listener (Capture phase to intercept before submit)
  document.addEventListener('submit', async function (e) {
    const form = e.target;
    if (!form || !form.getAttribute) return;
    const confirmMsg = form.getAttribute('data-confirm');
    if (confirmMsg && !form.dataset.confirmed) {
      e.preventDefault();
      e.stopImmediatePropagation();
      
      const title = form.getAttribute('data-confirm-title') || 'Confirm Action';
      const btnText = form.getAttribute('data-confirm-btn') || 'Confirm';
      const isDestructive = form.getAttribute('data-confirm-destructive') !== 'false';
      
      const ok = await confirmDialog(confirmMsg, title, btnText, isDestructive);
      if (ok) {
        form.dataset.confirmed = 'true';
        form.submit();
      }
    }
  }, true);

  return { init, open, close, closeTop, confirmDialog, alertDialog };
})();

// Expose on window object
if (typeof window !== 'undefined') {
  window.Modal = Modal;
  window.confirmAction = function(message, onConfirm, title = 'Confirm Action', btnText = 'Confirm', isDestructive = true) {
    Modal.confirmDialog(message, title, btnText, isDestructive).then(ok => {
      if (ok && typeof onConfirm === 'function') {
        onConfirm();
      }
    });
  };
  window.alertDialog = function(message, title = 'Notice', subtitle = '') {
    return Modal.alertDialog(message, title, subtitle);
  };
  window.alert = function(message) {
    if (typeof message === 'object') {
      try { message = JSON.stringify(message); } catch (e) { message = String(message); }
    }
    return Modal.alertDialog(message);
  };
}


/* ════════════════════════════════════════════════════════════
   3. SIDEBAR & MOBILE DRAWER CONTROLLER
   ════════════════════════════════════════════════════════════ */
const Sidebar = (() => {
  const STORAGE_KEY = 'ims_sidebar_collapsed';

  function init() {
    const sidebar = document.getElementById('app-sidebar');
    if (!sidebar) return;

    // Restore desktop collapsed state
    if (window.innerWidth > 768 && localStorage.getItem(STORAGE_KEY) === 'true') {
      sidebar.classList.add('collapsed');
    }

    // Desktop Collapse Toggle button
    const toggleBtn = document.getElementById('sidebar-toggle');
    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => toggle(sidebar));
    }

    // Mobile hamburger & backdrop overlay
    const mobileBtn  = document.getElementById('mobile-menu-btn');
    const overlay    = document.getElementById('mobile-nav-overlay');
    const drawerCloseBtn = document.getElementById('mobile-drawer-close-btn');

    if (mobileBtn) {
      mobileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleMobile(sidebar, overlay);
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {
        closeMobile(sidebar, overlay);
      });
    }

    if (drawerCloseBtn) {
      drawerCloseBtn.addEventListener('click', () => {
        closeMobile(sidebar, overlay);
      });
    }

    // Close mobile drawer when clicking any navigation link
    sidebar.querySelectorAll('.sidebar-nav-item').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          closeMobile(sidebar, overlay);
        }
      });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
        closeMobile(sidebar, overlay);
      }
    });

    // Clean up mobile drawer classes if window is resized to desktop
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768 && sidebar.classList.contains('mobile-open')) {
        closeMobile(sidebar, overlay);
      }
    });
  }

  function toggle(sidebar) {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem(STORAGE_KEY, sidebar.classList.contains('collapsed'));
  }

  function openMobile(sidebar, overlay) {
    sidebar.classList.add('mobile-open');
    if (overlay) overlay.classList.add('active');
    document.body.classList.add('sidebar-open');
  }

  function closeMobile(sidebar, overlay) {
    sidebar.classList.remove('mobile-open');
    if (overlay) overlay.classList.remove('active');
    document.body.classList.remove('sidebar-open');
  }

  function toggleMobile(sidebar, overlay) {
    if (sidebar.classList.contains('mobile-open')) {
      closeMobile(sidebar, overlay);
    } else {
      openMobile(sidebar, overlay);
    }
  }

  return { init, openMobile, closeMobile, toggleMobile };
})();


/* ════════════════════════════════════════════════════════════
   4. API WRAPPER (with CSRF injection)
   ════════════════════════════════════════════════════════════ */
const API = (() => {
  function getCSRF() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  async function request(url, options = {}) {
    const defaults = {
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCSRF(),
        'X-Requested-With': 'XMLHttpRequest',
      },
    };

    const config = {
      ...defaults,
      ...options,
      headers: { ...defaults.headers, ...(options.headers || {}) },
    };

    try {
      const res = await fetch(url, config);

      if (res.status === 419 || res.status === 401) {
        Toast.error('Session Expired', 'Your session has expired. Please log in again.', {
          actionText: 'Go to Login',
          actionCallback: () => { window.location.href = (window.appUrl ? window.appUrl('/login') : '/login'); },
        });
        return null;
      }

      if (res.status === 403) {
        Toast.error('Access Denied', 'You do not have permission for this action.');
        return null;
      }

      return await res.json();
    } catch (err) {
      Toast.error('Connection Error', 'Could not reach the server. Please check your connection.');
      console.error('API Error:', err);
      return null;
    }
  }

  return {
    get:    (url, opts = {})         => request(url, { method: 'GET',    ...opts }),
    post:   (url, body, opts = {})   => request(url, { method: 'POST',   body: JSON.stringify(body), ...opts }),
    put:    (url, body, opts = {})   => request(url, { method: 'PUT',    body: JSON.stringify(body), ...opts }),
    delete: (url, opts = {})         => request(url, { method: 'DELETE', ...opts }),
    patch:  (url, body, opts = {})   => request(url, { method: 'PATCH',  body: JSON.stringify(body), ...opts }),
  };
})();


/* ════════════════════════════════════════════════════════════
   5. TAB CONTROLLER
   ════════════════════════════════════════════════════════════ */
const Tabs = (() => {
  function init(containerSelector = '[data-tabs]') {
    document.querySelectorAll(containerSelector).forEach(container => {
      const buttons = container.querySelectorAll('.tab-btn');
      const panels  = container.querySelectorAll('.tab-panel');

      buttons.forEach((btn, i) => {
        btn.addEventListener('click', () => {
          buttons.forEach(b => b.classList.remove('active'));
          panels.forEach(p => p.classList.remove('active'));
          btn.classList.add('active');
          if (panels[i]) panels[i].classList.add('active');
        });
      });

      // Activate first by default
      if (buttons[0]) buttons[0].classList.add('active');
      if (panels[0])  panels[0].classList.add('active');
    });
  }

  return { init };
})();


/* ════════════════════════════════════════════════════════════
   6. COUNTDOWN TIMER
   ════════════════════════════════════════════════════════════ */
const Countdown = (() => {
  const timers = new Map();

  function start(el, targetDate, { warningThreshold = 30, onExpire = null } = {}) {
    const target = new Date(targetDate).getTime();
    const id = setInterval(() => tick(el, target, warningThreshold, onExpire, id), 1000);
    timers.set(el, id);
    tick(el, target, warningThreshold, onExpire, id);
  }

  function tick(el, target, warnMins, onExpire, id) {
    const now  = Date.now();
    const diff = target - now;

    if (diff <= 0) {
      el.textContent = 'Session Expired';
      el.className = 'countdown-timer expired';
      clearInterval(id);
      if (onExpire) onExpire();
      return;
    }

    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000)   / 1000);

    el.textContent = h > 0
      ? `${h}h ${String(m).padStart(2,'0')}m`
      : `${String(m).padStart(2,'0')}m ${String(s).padStart(2,'0')}s`;

    const totalMins = diff / 60000;
    if (totalMins < 5) {
      el.className = 'countdown-timer urgent';
    } else if (totalMins < warnMins) {
      el.className = 'countdown-timer warning';
    } else {
      el.className = 'countdown-timer';
    }
  }

  function stop(el) {
    if (timers.has(el)) {
      clearInterval(timers.get(el));
      timers.delete(el);
    }
  }

  // Auto-init from DOM
  function initAll() {
    document.querySelectorAll('[data-countdown]').forEach(el => {
      const targetDate = el.dataset.countdown;
      const warnMins   = parseInt(el.dataset.countdownWarn || '30', 10);
      if (targetDate) start(el, targetDate, { warningThreshold: warnMins });
    });
  }

  return { start, stop, initAll };
})();


/* ════════════════════════════════════════════════════════════
   7. DROPDOWN CONTROLLER
   ════════════════════════════════════════════════════════════ */
const Dropdown = (() => {
  function init() {
    document.addEventListener('click', e => {
      const trigger = e.target.closest('[data-dropdown]');
      if (trigger) {
        e.stopPropagation();
        const targetId = trigger.dataset.dropdown;
        const dropdown = targetId
          ? document.getElementById(targetId)
          : trigger.closest('.dropdown');

        if (!dropdown) return;
        const wasOpen = dropdown.classList.contains('open');
        closeAll();
        if (!wasOpen) dropdown.classList.add('open');
      } else {
        closeAll();
      }
    });
  }

  function closeAll() {
    document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
  }

  return { init };
})();


/* ════════════════════════════════════════════════════════════
   8. TABLE HELPERS
   ════════════════════════════════════════════════════════════ */
const TableHelper = (() => {
  function initSearch(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const searchInput = table.querySelector('.data-table-search-input');
    const tbody       = table.querySelector('.data-table tbody');
    if (!searchInput || !tbody) return;

    searchInput.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase().trim();
      tbody.querySelectorAll('tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
      updateCount(table);
    });
  }

  function updateCount(table) {
    const countEl = table.querySelector('.data-table-count');
    if (!countEl) return;
    const visible = table.querySelectorAll('.data-table tbody tr:not([style*="display: none"])').length;
    const total   = table.querySelectorAll('.data-table tbody tr').length;
    countEl.textContent = `Showing ${visible} of ${total} entries`;
  }

  return { initSearch, updateCount };
})();


/* ════════════════════════════════════════════════════════════
   9. SESSION TIMEOUT WATCHER
   ════════════════════════════════════════════════════════════ */
const SessionWatcher = (() => {
  const IDLE_LIMIT     = 15 * 60 * 1000; // 15 min
  const WARNING_BEFORE = 60 * 1000;       // warn 1 min before
  let idleTimer, warningTimer, countdownInterval;

  function reset() {
    clearTimeout(idleTimer);
    clearTimeout(warningTimer);
    clearInterval(countdownInterval);

    const modal = document.getElementById('session-timeout-modal');
    if (modal && modal.classList.contains('active')) {
      Modal.close('session-timeout-modal');
    }

    warningTimer = setTimeout(() => {
      Modal.open('session-timeout-modal');
      let secondsLeft = Math.round(WARNING_BEFORE / 1000);
      const cdEl = document.getElementById('session-countdown');
      if (cdEl) cdEl.textContent = secondsLeft;

      countdownInterval = setInterval(() => {
        secondsLeft--;
        if (cdEl) cdEl.textContent = Math.max(0, secondsLeft);
        if (secondsLeft <= 0) {
          clearInterval(countdownInterval);
        }
      }, 1000);
    }, IDLE_LIMIT - WARNING_BEFORE);

    idleTimer = setTimeout(() => {
      window.location.href = (window.appUrl ? window.appUrl('/logout?reason=idle') : '/logout?reason=idle');
    }, IDLE_LIMIT);
  }

  function init() {
    if (!document.getElementById('session-timeout-modal')) return;
    if (window.APP_CONFIG && window.APP_CONFIG.isRemembered) return;
    ['click', 'keydown', 'mousemove', 'touchstart'].forEach(e => {
      document.addEventListener(e, reset, { passive: true });
    });
    document.addEventListener('session-renewed', reset);
    reset();
  }

  return { init, reset };
})();


/* ════════════════════════════════════════════════════════════
   10. SCAN STATION CONTROLLER
   ════════════════════════════════════════════════════════════ */
const ScanStation = (() => {
  let currentMode = 'borrow';

  function setMode(mode) {
    if (!mode) return;
    currentMode = mode;
    document.querySelectorAll('[data-scan-mode]').forEach(el => {
      const isMatch = (el.dataset.scanMode || el.getAttribute('data-scan-mode')) === mode;
      el.classList.toggle('active', isMatch);
      if (isMatch) {
        el.classList.remove('btn-ghost');
        el.classList.add('btn-primary');
      } else {
        el.classList.remove('btn-primary');
        el.classList.add('btn-ghost');
      }
    });
    document.querySelectorAll('[data-mode-panel]').forEach(panel => {
      const isMatch = (panel.dataset.modePanel || panel.getAttribute('data-mode-panel')) === mode;
      if (isMatch) {
        panel.classList.remove('hidden');
        panel.style.display = 'block';
      } else {
        panel.classList.add('hidden');
        panel.style.display = 'none';
      }
    });
  }

  function init() {
    document.addEventListener('click', e => {
      const btn = e.target.closest('[data-scan-mode]');
      if (btn) {
        e.preventDefault();
        const mode = btn.dataset.scanMode || btn.getAttribute('data-scan-mode');
        setMode(mode);
      }
    });
  }

  return { init, setMode };
})();


/* ════════════════════════════════════════════════════════════
   11. NOTIFICATION HUB (Stripe-Inspired Feed & Auto-Pruning UI)
   ════════════════════════════════════════════════════════════ */
const NotificationHub = (() => {
  let isFetching = false;
  let hasLoaded = false;

  const categoryIcons = {
    LOAN_DUE:      `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`,
    LOAN_OVERDUE:  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`,
    LOAN_CHECKOUT: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>`,
    LOAN_DENIED:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`,
    LOAN_RETURN:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`,
    DAMAGE_HOLD:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`,
    CLEARANCE:     `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>`,
    ACCOUNT:       `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>`,
    SECURITY:      `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`,
    default:       `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
  };

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function init() {
    const bellBtn = document.getElementById('notif-bell-btn');
    if (!bellBtn) return;

    bellBtn.addEventListener('click', () => {
      // Refresh feed whenever user opens the dropdown
      fetchNotifications();
    });

    // Initial background pre-fetch
    setTimeout(() => {
      fetchNotifications(true);
    }, 400);

    // Real-Time Hub Subscription: Listen for live notification dispatches
    if (window.RealtimeHub && typeof window.RealtimeHub.on === 'function') {
      window.RealtimeHub.on('NOTIFICATION_DISPATCHED', (ev) => {
        if (ev && ev.payload) {
          handleIncomingNotification(ev.payload);
        }
      });
    }
  }

  function handleIncomingNotification(item) {
    if (!item) return;

    // 1. Increment and bounce unread badge
    const badge = document.getElementById('notif-unread-badge');
    let currentCount = 0;
    if (badge && badge.style.display !== 'none') {
      currentCount = parseInt(badge.textContent, 10) || 0;
    }
    const newCount = currentCount + 1;
    updateBadge(newCount);

    if (badge) {
      badge.classList.remove('animate-badge-pop');
      void badge.offsetWidth; // force reflow
      badge.classList.add('animate-badge-pop');
    }

    // 2. Prepend item to dropdown feed container
    const listEl = document.getElementById('notif-list-container');
    if (listEl) {
      const emptyState = listEl.querySelector('.notif-empty');
      if (emptyState) {
        listEl.innerHTML = '';
      }

      const iconType = item.type || 'info';
      const catIcon = categoryIcons[item.category] || categoryIcons.default;
      const targetUrl = item.action_url ? _esc(item.action_url) : '';
      const notifId = item.notification_id || Date.now();

      const itemHtml = `
        <div class="notif-item is-unread animate-fade-in" data-notif-id="${notifId}" data-url="${targetUrl}" onclick="NotificationHub.handleItemClick(this, ${notifId}, '${targetUrl}')" style="background:rgba(83, 58, 253, 0.05);">
          <div class="notif-icon-box ${iconType}">
            ${catIcon}
          </div>
          <div class="notif-content">
            <div class="notif-top-row">
              <span class="notif-item-title">${_esc(item.title)}</span>
              <span class="notif-item-time">Just now</span>
            </div>
            <div class="notif-item-desc">${_esc(item.message)}</div>
          </div>
        </div>`;
      
      listEl.insertAdjacentHTML('afterbegin', itemHtml);
    }

    // 3. Fire non-intrusive floating toast
    if (typeof Toast !== 'undefined' && typeof Toast.show === 'function') {
      const toastType = item.type === 'danger' ? 'error' : (item.type || 'info');
      Toast.show({
        type: toastType,
        title: item.title || 'New Notification',
        message: item.message,
        duration: 5000,
        actionText: item.action_url ? 'View' : null,
        actionCallback: item.action_url ? () => { window.location.href = item.action_url; } : null
      });
    }
  }

  function fetchNotifications(silent = false) {
    if (isFetching) return;
    isFetching = true;

    const listEl = document.getElementById('notif-list-container');
    if (!hasLoaded && listEl && !silent) {
      listEl.innerHTML = `
        <div class="notif-loading-state">
          <div class="notif-skeleton-row"></div>
          <div class="notif-skeleton-row"></div>
        </div>`;
    }

    const notifEndpoint = window.appUrl ? window.appUrl('/api/notifications') : '/api/notifications';
    fetch(notifEndpoint, {
      headers: { 'Accept': 'application/json' }
    })
      .then(res => res.json())
      .then(data => {
        isFetching = false;
        hasLoaded = true;
        if (data && data.success) {
          updateBadge(data.unread_count);
          renderList(data.notifications, data.unread_count);
        }
      })
      .catch(err => {
        isFetching = false;
        if (listEl && !hasLoaded) {
          listEl.innerHTML = `<div class="notif-empty"><span class="notif-empty-title">Could not load alerts</span><span class="notif-empty-sub">Please verify network connection.</span></div>`;
        }
      });
  }

  function updateBadge(count) {
    const badge = document.getElementById('notif-unread-badge');
    const headerCount = document.getElementById('notif-header-count');
    
    if (badge) {
      if (count > 0) {
        badge.textContent = count > 9 ? '9+' : String(count);
        badge.style.display = 'block';
      } else {
        badge.style.display = 'none';
      }
    }

    if (headerCount) {
      headerCount.textContent = count > 0 ? `${count} unread` : 'All caught up';
      headerCount.style.color = count > 0 ? 'var(--color-primary)' : 'var(--color-ink-mute)';
      headerCount.style.background = count > 0 ? 'rgba(83, 58, 253, 0.08)' : 'var(--color-canvas-soft)';
    }
  }

  function renderList(notifications, unreadCount) {
    const listEl = document.getElementById('notif-list-container');
    if (!listEl) return;

    if (!notifications || notifications.length === 0) {
      listEl.innerHTML = `
        <div class="notif-empty">
          <div class="notif-empty-icon">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
          </div>
          <div class="notif-empty-title">No notifications</div>
          <div class="notif-empty-sub">You have no recent notices or alerts.</div>
        </div>`;
      return;
    }

    let html = '';
    notifications.forEach(item => {
      const isUnread = !item.is_read;
      const unreadClass = isUnread ? ' is-unread' : '';
      const iconType = item.type || 'info';
      const catIcon = categoryIcons[item.category] || categoryIcons.default;
      const targetUrl = item.action_url ? _esc(item.action_url) : '';

      html += `
        <div class="notif-item${unreadClass}" data-notif-id="${item.notification_id}" data-url="${targetUrl}" onclick="NotificationHub.handleItemClick(this, ${item.notification_id}, '${targetUrl}')">
          <div class="notif-icon-box ${iconType}">
            ${catIcon}
          </div>
          <div class="notif-content">
            <div class="notif-top-row">
              <span class="notif-item-title">${_esc(item.title)}</span>
              <span class="notif-item-time">${_esc(item.time_ago)}</span>
            </div>
            <div class="notif-item-desc">${_esc(item.message)}</div>
          </div>
        </div>`;
    });

    listEl.innerHTML = html;
  }

  function markAllRead(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }

    const csrfToken = getCsrfToken();
    
    // Optimistic UI update
    document.querySelectorAll('.notif-item.is-unread').forEach(el => el.classList.remove('is-unread'));
    updateBadge(0);

    const readEndpoint = window.appUrl ? window.appUrl('/api/notifications/read') : '/api/notifications/read';
    fetch(readEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ csrf_token: csrfToken })
    })
      .then(res => res.json())
      .then(data => {
        if (data && data.success) {
          updateBadge(data.unread_count ?? 0);
        }
      })
      .catch(() => {
        // Silently recover on next fetch
      });
  }

  function handleItemClick(el, notifId, actionUrl) {
    if (el && el.classList.contains('is-unread')) {
      el.classList.remove('is-unread');
      const badge = document.getElementById('notif-unread-badge');
      if (badge && badge.textContent) {
        let count = parseInt(badge.textContent, 10);
        if (!isNaN(count) && count > 0) {
          count--;
          updateBadge(count);
        }
      }

      const csrfToken = getCsrfToken();
      const readEndpoint = window.appUrl ? window.appUrl('/api/notifications/read') : '/api/notifications/read';
      fetch(readEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ csrf_token: csrfToken, notification_id: notifId })
      }).catch(() => {});
    }

    if (actionUrl && actionUrl.trim() !== '') {
      window.location.href = actionUrl;
    }
  }

  return {
    init,
    fetchNotifications,
    markAllRead,
    handleItemClick,
  };
})();


/* ════════════════════════════════════════════════════════════
   UTILITY: Safe DOM text escaping
   ════════════════════════════════════════════════════════════ */
function _esc(str) {
  if (str == null) return '';
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(String(str)));
  return div.innerHTML;
}


/* ════════════════════════════════════════════════════════════
   BOOTSTRAP — Initialize on DOM ready
   ════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  Toast.initFromUrl();
  Modal.init();
  Sidebar.init();
  Tabs.init();
  Dropdown.init();
  Countdown.initAll();
  SessionWatcher.init();
  ScanStation.init();
  NotificationHub.init();

  // Table search on any data-table
  document.querySelectorAll('[data-table-id]').forEach(table => {
    TableHelper.initSearch(table.id);
  });

  // Barcode card rendering
  document.querySelectorAll('[data-barcode]').forEach(el => {
    if (typeof JsBarcode !== 'undefined') {
      JsBarcode(el, el.dataset.barcode, {
        format: 'CODE128',
        width: 2, height: 50,
        displayValue: false,
        lineColor: '#0d253d',
      });
    }
  });

  console.log('[IMS] Application initialized — SLSU-Alabat IMS');
});

// Global window assignments for script compatibility
if (typeof window !== 'undefined') {
  window.Toast           = Toast;
  window.Modal           = Modal;
  window.Sidebar         = Sidebar;
  window.API             = API;
  window.Tabs            = Tabs;
  window.Countdown       = Countdown;
  window.Dropdown        = Dropdown;
  window.TableHelper     = TableHelper;
  window.SessionWatcher  = SessionWatcher;
  window.ScanStation     = ScanStation;
  window.NotificationHub = NotificationHub;
}

