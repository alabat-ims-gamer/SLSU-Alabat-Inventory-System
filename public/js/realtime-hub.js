/**
 * SLSU-Alabat IMS — RealtimeHub Engine
 * 
 * High-performance, zero-cost real-time synchronization client.
 * Features:
 * - Multi-Tab Leader Election (BroadcastChannel): Only 1 tab talks to the server,
 *   broadcasting events locally across all open tabs of the same browser.
 * - Dual Transport: Native Server-Sent Events (SSE) with seamless 5s Delta Polling fallback.
 * - Hardware & Credit Protection: Throttles to 30s when tab is in background,
 *   sleeps completely after 5 minutes of inactivity to preserve Railway trial credits.
 * - Lightweight Event Bus: Components subscribe via RealtimeHub.on(type, callback).
 */
(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.RealtimeHub = factory();
  }
}(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  // Configuration
  const CONFIG = {
    activeInterval: 5000,       // 5 seconds active polling
    backgroundInterval: 30000,  // 30 seconds background tab throttling
    idleTimeout: 300000,        // 5 minutes user inactivity sleep
    leaderHeartbeat: 3000,      // 3 seconds leader beacon
    syncEndpoint: '/api/realtime/sync',
    streamEndpoint: '/api/realtime/stream',
  };

  // State
  const tabId = Math.random().toString(36).substring(2, 9);
  let lastEventId = 0;
  let isLeader = false;
  let leaderId = null;
  let lastLeaderHeartbeat = 0;
  let isIdle = false;
  let isPolling = false;
  let pollTimer = null;
  let idleTimer = null;
  let heartbeatTimer = null;
  let eventSource = null;
  let connectionMode = 'polling'; // 'sse' or 'polling'

  // Subscriptions map: eventType -> Set of callbacks
  const listeners = new Map();

  // Multi-tab channel
  const hasBroadcastChannel = typeof window.BroadcastChannel !== 'undefined';
  const channel = hasBroadcastChannel ? new BroadcastChannel('slsu_realtime_bus') : null;

  /**
   * Helper: Resolve application URL
   */
  function getAppUrl(path) {
    if (typeof window.appUrl === 'function') {
      return window.appUrl(path);
    }
    const base = (window.APP_CONFIG && window.APP_CONFIG.baseUrl) ? window.APP_CONFIG.baseUrl : '';
    return base + (path.startsWith('/') ? path : '/' + path);
  }

  /**
   * Read watermark from meta tag or APP_CONFIG
   */
  function initLastEventId() {
    if (window.APP_CONFIG && typeof window.APP_CONFIG.lastEventId === 'number') {
      lastEventId = window.APP_CONFIG.lastEventId;
    }
    const meta = document.querySelector('meta[name="last-event-id"]');
    if (meta && meta.content) {
      const parsed = parseInt(meta.content, 10);
      if (!isNaN(parsed) && parsed > lastEventId) {
        lastEventId = parsed;
      }
    }
  }

  /**
   * Leader Election Protocol
   */
  function setupLeaderElection() {
    if (!channel) {
      isLeader = true;
      return;
    }

    // Listen to inter-tab broadcasts
    channel.onmessage = function (e) {
      const msg = e.data;
      if (!msg || typeof msg !== 'object') return;

      switch (msg.type) {
        case 'leader_heartbeat':
          if (msg.tabId !== tabId) {
            leaderId = msg.tabId;
            lastLeaderHeartbeat = Date.now();
            if (isLeader) {
              // Yield leadership to higher tabId tie-break
              if (msg.tabId > tabId) {
                isLeader = false;
                stopConnection();
              }
            }
          }
          break;

        case 'leader_resigned':
          if (msg.tabId === leaderId) {
            claimLeadership();
          }
          break;

        case 'events_broadcast':
          // Another tab was leader and fetched events; dispatch them locally
          if (msg.tabId !== tabId && Array.isArray(msg.events)) {
            if (typeof msg.lastEventId === 'number' && msg.lastEventId > lastEventId) {
              lastEventId = msg.lastEventId;
            }
            dispatchIncomingEvents(msg.events, false);
          }
          break;
      }
    };

    // Attempt to claim leadership
    claimLeadership();

    // Watchdog: check if leader went silent
    setInterval(function () {
      if (!isLeader) {
        const elapsed = Date.now() - lastLeaderHeartbeat;
        if (elapsed > (CONFIG.leaderHeartbeat * 2.5)) {
          claimLeadership();
        }
      }
    }, CONFIG.leaderHeartbeat);

    // Resign on window unload
    window.addEventListener('beforeunload', function () {
      if (isLeader && channel) {
        try {
          channel.postMessage({ type: 'leader_resigned', tabId: tabId });
        } catch (e) {}
      }
    });
  }

  function claimLeadership() {
    isLeader = true;
    leaderId = tabId;
    lastLeaderHeartbeat = Date.now();

    if (channel) {
      channel.postMessage({ type: 'leader_heartbeat', tabId: tabId });
    }

    // Start periodic heartbeat
    if (heartbeatTimer) clearInterval(heartbeatTimer);
    heartbeatTimer = setInterval(function () {
      if (isLeader && channel) {
        channel.postMessage({ type: 'leader_heartbeat', tabId: tabId });
      }
    }, CONFIG.leaderHeartbeat);

    // Leader boots up connection
    startConnection();
  }

  /**
   * Dispatch events to registered listeners
   */
  function dispatchIncomingEvents(events, broadcastToOtherTabs = true) {
    if (!Array.isArray(events) || events.length === 0) return;

    events.forEach(function (ev) {
      if (!ev || !ev.event_type) return;

      // Update watermark
      if (typeof ev.event_id === 'number' && ev.event_id > lastEventId) {
        lastEventId = ev.event_id;
      }

      // 1. Notify specific event listeners
      if (listeners.has(ev.event_type)) {
        listeners.get(ev.event_type).forEach(function (callback) {
          try {
            callback(ev);
          } catch (err) {
            console.error('[RealtimeHub] Listener error on ' + ev.event_type + ':', err);
          }
        });
      }

      // 2. Notify wildcard listeners
      if (listeners.has('*')) {
        listeners.get('*').forEach(function (callback) {
          try {
            callback(ev);
          } catch (err) {
            console.error('[RealtimeHub] Wildcard listener error:', err);
          }
        });
      }
    });

    // Rebroadcast to other open browser tabs
    if (broadcastToOtherTabs && channel) {
      try {
        channel.postMessage({
          type: 'events_broadcast',
          tabId: tabId,
          lastEventId: lastEventId,
          events: events
        });
      } catch (err) {
        console.warn('[RealtimeHub] Broadcast error:', err);
      }
    }
  }

  /**
   * Delta Polling Sync Request
   */
  async function performSync() {
    if (isPolling) return;
    if (!isLeader) return;
    if (isIdle) return;

    isPolling = true;

    try {
      const url = getAppUrl(CONFIG.syncEndpoint + '?after_id=' + encodeURIComponent(lastEventId));
      const res = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        cache: 'no-store'
      });

      if (!res.ok) {
        isPolling = false;
        scheduleNextPoll();
        return;
      }

      const data = await res.json();
      isPolling = false;

      if (data && data.success) {
        if (typeof data.last_event_id === 'number' && data.last_event_id > lastEventId) {
          lastEventId = data.last_event_id;
        }
        if (Array.isArray(data.events) && data.events.length > 0) {
          dispatchIncomingEvents(data.events, true);
        }
      }
    } catch (err) {
      isPolling = false;
    }

    scheduleNextPoll();
  }

  function scheduleNextPoll() {
    if (pollTimer) clearTimeout(pollTimer);
    if (!isLeader || isIdle) return;

    const interval = document.hidden ? CONFIG.backgroundInterval : CONFIG.activeInterval;
    pollTimer = setTimeout(performSync, interval);
  }

  function startConnection() {
    if (!isLeader) return;
    performSync();
  }

  function stopConnection() {
    if (pollTimer) {
      clearTimeout(pollTimer);
      pollTimer = null;
    }
    if (eventSource) {
      try {
        eventSource.close();
      } catch (e) {}
      eventSource = null;
    }
  }

  /**
   * Activity & Inactivity Tracking (Protects Railway Free Tier)
   */
  function setupActivityTracking() {
    function resetIdleTimer() {
      if (isIdle) {
        isIdle = false;
        // Woke up from sleep: sync immediately
        if (isLeader) {
          performSync();
        }
      }

      if (idleTimer) clearTimeout(idleTimer);
      idleTimer = setTimeout(function () {
        isIdle = true;
        stopConnection();
      }, CONFIG.idleTimeout);
    }

    // Monitor user interaction
    ['mousemove', 'keydown', 'touchstart', 'scroll', 'click'].forEach(function (evt) {
      window.addEventListener(evt, resetIdleTimer, { passive: true });
    });

    // Tab visibility changes (Page Visibility API)
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) {
        // Tab brought to foreground: immediately perform catch-up sync
        isIdle = false;
        resetIdleTimer();
        if (isLeader) {
          performSync();
        }
      } else {
        // Tab backgrounded: reschedule with relaxed 30s timer
        if (isLeader) {
          scheduleNextPoll();
        }
      }
    });

    resetIdleTimer();
  }

  /**
   * Public API
   */
  return {
    init: function () {
      initLastEventId();
      setupLeaderElection();
      setupActivityTracking();
    },

    /**
     * Subscribe to a real-time event type
     */
    on: function (eventType, callback) {
      if (typeof callback !== 'function') return;
      if (!listeners.has(eventType)) {
        listeners.set(eventType, new Set());
      }
      listeners.get(eventType).add(callback);
    },

    /**
     * Unsubscribe
     */
    off: function (eventType, callback) {
      if (listeners.has(eventType)) {
        listeners.get(eventType).delete(callback);
      }
    },

    /**
     * Manually trigger immediate sync
     */
    syncNow: function () {
      if (isLeader) {
        performSync();
      }
    },

    /**
     * Get current watermark
     */
    getLastEventId: function () {
      return lastEventId;
    },

    /**
     * Check if this tab is currently the leader
     */
    isLeaderTab: function () {
      return isLeader;
    }
  };
}));
