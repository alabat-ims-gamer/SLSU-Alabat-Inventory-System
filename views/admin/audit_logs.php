<?php
/**
 * SLSU-Alabat IMS — Security & Audit Trail Control Center
 * Stripe-Inspired Precision Layout & Design System
 */
require_once __DIR__ . '/../layouts/shell.php';
?>

<!-- ─── Breadcrumb & Action Header ──────────────────────────── -->
<div class="section-header">
  <div>
    <h1 class="section-title">Security &amp; Audit Trail</h1>
    <div class="section-subtitle">Immutable forensic timeline recording administrative actions, role elevations, security events, and user activity</div>
  </div>
  
  <div style="display:flex;gap:0.5rem;align-items:center;">
    <a href="<?= url('/admin/audit') ?>" class="btn btn-secondary btn-sm" title="Refresh Log Stream">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
      Refresh Stream
    </a>
  </div>
</div>

<!-- ─── Metric KPI Grid ───────────────────────────────────────── -->
<div class="grid-kpi-stats stagger-children">
  <!-- Total Records -->
  <div class="card" style="padding:1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
      <span style="font-size:12px;font-weight:500;color:var(--color-ink-mute);">Total Audit Records</span>
      <div style="width:28px;height:28px;border-radius:var(--radius-md);background:rgba(22, 101, 52,0.1);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
      </div>
    </div>
    <div style="font-size:24px;font-weight:600;color:var(--color-ink);font-feature-settings:'tnum';"><?= number_format($stats['total']) ?></div>
    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Permanent forensic entries</div>
  </div>

  <!-- Past 24 Hours -->
  <div class="card" style="padding:1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
      <span style="font-size:12px;font-weight:500;color:var(--color-ink-mute);">Last 24 Hours</span>
      <div style="width:28px;height:28px;border-radius:var(--radius-md);background:rgba(0,187,249,0.1);display:flex;align-items:center;justify-content:center;color:#0077b6;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
      </div>
    </div>
    <div style="font-size:24px;font-weight:600;color:var(--color-ink);font-feature-settings:'tnum';"><?= number_format($stats['past_24h']) ?></div>
    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Recent system events</div>
  </div>

  <!-- Security & Auth -->
  <div class="card" style="padding:1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
      <span style="font-size:12px;font-weight:500;color:var(--color-ink-mute);">Auth &amp; Security Events</span>
      <div style="width:28px;height:28px;border-radius:var(--radius-md);background:rgba(16,185,129,0.1);display:flex;align-items:center;justify-content:center;color:var(--color-success);">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
      </div>
    </div>
    <div style="font-size:24px;font-weight:600;color:var(--color-ink);font-feature-settings:'tnum';"><?= number_format($stats['security_events']) ?></div>
    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Logins, logouts &amp; resets</div>
  </div>

  <!-- Admin Governance -->
  <div class="card" style="padding:1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
      <span style="font-size:12px;font-weight:500;color:var(--color-ink-mute);">Administrative Actions</span>
      <div style="width:28px;height:28px;border-radius:var(--radius-md);background:rgba(235,87,87,0.1);display:flex;align-items:center;justify-content:center;color:var(--color-ruby);">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
      </div>
    </div>
    <div style="font-size:24px;font-weight:600;color:var(--color-ink);font-feature-settings:'tnum';"><?= number_format($stats['admin_actions']) ?></div>
    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Approvals, edits &amp; deletions</div>
  </div>
</div>

<!-- ─── Filter & Search Toolbar (Stripe-Grade Layout) ───────────── -->
<div class="card" style="margin-bottom:1.5rem;padding:1.25rem;">
  <form method="GET" action="<?= url('/admin/audit') ?>" id="audit-filter-form">
    <div style="display:flex;flex-direction:column;gap:1rem;">
      
      <!-- Top Row: Primary Search & Event Selector -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1rem;">
        
        <!-- Keyword Search -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="search" style="font-size:12px;font-weight:500;color:var(--color-ink);margin-bottom:6px;display:block;">
            Search Audit Records
          </label>
          <div style="position:relative;">
            <input type="text" name="search" id="search" class="form-input" placeholder="Search by user name, email, IP address, or description..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" style="padding-left:2.35rem;height:38px;font-size:13px;">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--color-ink-mute);pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </div>
        </div>

        <!-- Action Type Selector -->
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="action_type" style="font-size:12px;font-weight:500;color:var(--color-ink);margin-bottom:6px;display:block;">
            Event Category
          </label>
          <select name="action_type" id="action_type" class="form-input form-select" style="height:38px;font-size:13px;">
            <option value="">All Action Types</option>
            <?php foreach ($actionTypes as $type): ?>
              <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['action_type'] === $type ? 'selected' : '' ?>>
                <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

      </div>

      <!-- Bottom Row: Date Range & Action Buttons -->
      <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;flex-wrap:wrap;border-top:1px solid var(--color-hairline);padding-top:1rem;">
        
        <!-- Date Pickers Container -->
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
          <div style="display:flex;flex-direction:column;gap:4px;">
            <label class="form-label" for="date_from" style="font-size:11px;font-weight:500;color:var(--color-ink-mute);margin:0;">Date From</label>
            <input type="date" name="date_from" id="date_from" class="form-input" value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>" style="height:36px;font-size:12.5px;padding:4px 10px;min-width:145px;">
          </div>
          
          <span style="color:var(--color-ink-mute);font-size:12px;margin-top:18px;">&rarr;</span>

          <div style="display:flex;flex-direction:column;gap:4px;">
            <label class="form-label" for="date_to" style="font-size:11px;font-weight:500;color:var(--color-ink-mute);margin:0;">Date To</label>
            <input type="date" name="date_to" id="date_to" class="form-input" value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>" style="height:36px;font-size:12.5px;padding:4px 10px;min-width:145px;">
          </div>
        </div>

        <!-- Buttons Container (Cleanly Right-Aligned) -->
        <div style="display:flex;gap:0.5rem;align-items:center;margin-left:auto;">
          <?php if (!empty($filters['search']) || !empty($filters['action_type']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
            <a href="<?= url('/admin/audit') ?>" class="btn btn-ghost btn-sm" style="height:36px;display:inline-flex;align-items:center;padding:0 14px;color:var(--color-ink-mute);" title="Clear all filters">
              <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              Reset Filters
            </a>
          <?php endif; ?>

          <button type="submit" class="btn btn-primary btn-sm" style="height:36px;display:inline-flex;align-items:center;padding:0 18px;font-weight:500;box-shadow:0 1px 2px rgba(22, 101, 52,0.15);">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            Apply Filters
          </button>
        </div>

      </div>

    </div>
  </form>
</div>

<!-- ─── Audit Records Ledger Table ───────────────────────────── -->
<div class="card" style="padding:0;overflow:hidden;">
  <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
    <div>
      <h2 style="font-size:15px;font-weight:600;color:var(--color-ink);margin:0;">Audit Event Stream</h2>
      <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">
        Showing <?= min($totalLogs, $offset + 1) ?> to <?= min($totalLogs, $offset + $limit) ?> of <?= number_format($totalLogs) ?> total events
      </div>
    </div>
    
    <div class="table-scroll-hint">
      <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
      Scroll horizontally to view
    </div>
  </div>

  <div class="card-responsive-body">
    <div class="table-responsive">
      <table class="table" style="min-width:760px;">
        <thead>
          <tr style="border-bottom:1px solid var(--color-hairline);text-align:left;font-size:12px;color:var(--color-ink-mute);">
            <th style="padding:.75rem 1rem;width:150px;">Timestamp</th>
            <th style="padding:.75rem 1rem;width:180px;">Actor / User</th>
            <th style="padding:.75rem 1rem;width:160px;">Action Event</th>
            <th style="padding:.75rem 1rem;">Description &amp; Context</th>
            <th style="padding:.75rem 1rem;width:140px;">Network / IP</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="5" style="padding:3rem 1rem;text-align:center;">
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
                  <div style="width:48px;height:48px;border-radius:50%;background:var(--color-canvas-soft);display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;color:var(--color-ink-mute);">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                  </div>
                  <div style="font-size:14px;font-weight:600;color:var(--color-ink);margin-bottom:2px;">No matching audit records found</div>
                  <div style="font-size:12px;color:var(--color-ink-mute);max-width:320px;">Try adjusting your keyword search, action type, or date range filters.</div>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $log): ?>
              <?php
                // Dynamic Badge Color Mapping
                $badgeStyle = 'background:rgba(22, 101, 52,0.08);color:var(--color-primary);border:1px solid rgba(22, 101, 52,0.2);';
                $action = $log['action_type'];
                
                if (str_contains($action, 'FAIL') || str_contains($action, 'REJECT') || str_contains($action, 'PURGE') || str_contains($action, 'DELETE') || str_contains($action, 'LIMITED')) {
                  $badgeStyle = 'background:rgba(235,87,87,0.1);color:var(--color-ruby);border:1px solid rgba(235,87,87,0.25);';
                } elseif (str_contains($action, 'SUCCESS') || str_contains($action, 'APPROVE')) {
                  $badgeStyle = 'background:rgba(16,185,129,0.1);color:var(--color-success);border:1px solid rgba(16,185,129,0.25);';
                } elseif (str_contains($action, 'LOGOUT')) {
                  $badgeStyle = 'background:rgba(100,116,139,0.1);color:var(--color-ink-mute);border:1px solid rgba(100,116,139,0.2);';
                }
              ?>
              <tr style="border-bottom:1px solid var(--color-canvas-soft);">
                <!-- Timestamp -->
                <td style="padding:.75rem 1rem;font-size:12px;font-feature-settings:'tnum';white-space:nowrap;color:var(--color-ink-mute);">
                  <div style="font-weight:500;color:var(--color-ink);"><?= date('M d, Y', strtotime($log['created_at'])) ?></div>
                  <div style="font-size:11px;"><?= date('h:i:s A', strtotime($log['created_at'])) ?></div>
                </td>

                <!-- Actor / User -->
                <td style="padding:.75rem 1rem;font-size:13px;">
                  <?php if ($log['user_name']): ?>
                    <?php
                      $logUName = trim($log['user_name']);
                      $parts = explode(' ', $logUName);
                      $logInitials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? substr(end($parts), 0, 1) : ''));
                    ?>
                    <div style="display:flex;align-items:center;gap:0.65rem;">
                      <div style="width:30px;height:30px;border-radius:50%;overflow:hidden;background:linear-gradient(135deg, var(--color-primary) 0%, #6366f1 100%);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:11px;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.06);">
                        <?php if (!empty($log['user_avatar'])): ?>
                          <img src="<?= asset($log['user_avatar']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;display:block;">
                        <?php else: ?>
                          <?= htmlspecialchars($logInitials, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div style="font-weight:500;color:var(--color-ink);"><?= htmlspecialchars($log['user_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:var(--color-ink-mute);"><?= htmlspecialchars($log['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                    </div>
                  <?php else: ?>
                    <div style="display:flex;align-items:center;gap:0.65rem;">
                      <div style="width:30px;height:30px;border-radius:50%;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:center;color:var(--color-ink-mute);flex-shrink:0;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                      </div>
                      <div style="color:var(--color-ink-mute);font-weight:400;font-style:italic;">System / Guest</div>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Action Badge -->
                <td style="padding:.75rem 1rem;font-size:11px;font-weight:600;">
                  <span style="display:inline-block;padding:3px 8px;border-radius:var(--radius-pill);letter-spacing:0.03em;<?= $badgeStyle ?>">
                    <?= htmlspecialchars($log['action_type'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>

                <!-- Description -->
                <td style="padding:.75rem 1rem;font-size:12.5px;color:var(--color-ink-secondary);line-height:1.4;">
                  <div><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if (!empty($log['user_agent'])): ?>
                    <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:3px;max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($log['user_agent'], ENT_QUOTES, 'UTF-8') ?>">
                      UA: <?= htmlspecialchars($log['user_agent'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- IP Address -->
                <td style="padding:.75rem 1rem;font-size:12px;color:var(--color-ink-mute);font-feature-settings:'tnum';white-space:nowrap;">
                  <code><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?></code>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ─── Pagination Bar ─────────────────────────────────────── -->
  <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.25rem;border-top:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
      <div style="font-size:12px;color:var(--color-ink-mute);">
        Page <strong style="color:var(--color-ink);"><?= $page ?></strong> of <strong style="color:var(--color-ink);"><?= $totalPages ?></strong>
      </div>

      <div style="display:flex;gap:0.25rem;align-items:center;">
        <?php
          // Helper to build pagination query string preserving filters
          function paginationUrl($p, $filters) {
            $params = array_filter($filters);
            $params['page'] = $p;
            return url('/admin/audit') . '?' . http_build_query($params);
          }
        ?>

        <!-- Previous Page -->
        <?php if ($page > 1): ?>
          <a href="<?= paginationUrl($page - 1, $filters) ?>" class="btn btn-secondary btn-sm" style="padding:4px 10px;">&laquo; Prev</a>
        <?php else: ?>
          <span class="btn btn-secondary btn-sm" style="opacity:0.5;pointer-events:none;padding:4px 10px;">&laquo; Prev</span>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);
          for ($i = $startPage; $i <= $endPage; $i++):
        ?>
          <a href="<?= paginationUrl($i, $filters) ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>" style="padding:4px 10px;min-width:32px;text-align:center;">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <!-- Next Page -->
        <?php if ($page < $totalPages): ?>
          <a href="<?= paginationUrl($page + 1, $filters) ?>" class="btn btn-secondary btn-sm" style="padding:4px 10px;">Next &raquo;</a>
        <?php else: ?>
          <span class="btn btn-secondary btn-sm" style="opacity:0.5;pointer-events:none;padding:4px 10px;">Next &raquo;</span>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/shell-footer.php'; ?>
