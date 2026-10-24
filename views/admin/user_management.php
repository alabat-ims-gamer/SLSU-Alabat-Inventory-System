<?php
/**
 * User Management Control Center (System Admin Only)
 * 3-Tab Interface: Pending, Directory, Audit Log
 */
$title      = 'User Management';
$breadcrumb = 'Admin / User Management';

// Variables provided by Controller:
// $pendingUsers (array)
// $allUsers (array)
// $auditLogs (array)

require __DIR__ . '/../layouts/shell.php';
?>

<!-- Include Alpine.js for simple tab switching -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<div class="section-header">
  <div>
    <h1 class="section-title">User Management Center</h1>
    <div class="section-subtitle">Manage access, roles, and review security logs</div>
  </div>
</div>

<div x-data="{ tab: 'pending' }" class="card" style="padding:0;overflow:hidden;">
  
  <!-- Tabs Navigation (Responsive Horizontal Scroll) -->
  <div class="tabs-header-scroll">
    <button @click="tab = 'pending'" :class="{'tab-active': tab === 'pending'}" class="tab-btn">
      <span>Pending Approvals</span>
      <?php if(count($pendingUsers) > 0): ?>
        <span class="badge badge-pending" style="font-size:10px;"><?= count($pendingUsers) ?></span>
      <?php endif; ?>
    </button>
    <button @click="tab = 'directory'" :class="{'tab-active': tab === 'directory'}" class="tab-btn">
      <span>User Directory</span>
    </button>
  </div>

  <div class="card-responsive-body">
    <!-- TAB 1: Pending Approvals -->
    <div x-show="tab === 'pending'">
      <div style="margin-bottom:1rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
        <div>
          <h3 style="font-size:16px;font-weight:600;color:var(--color-ink);">Awaiting Action</h3>
          <p style="font-size:13px;color:var(--color-ink-mute);">Users who have registered but not yet been assigned a role.</p>
        </div>
        <div class="table-scroll-hint">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
          Scroll horizontally to view
        </div>
      </div>
      
      <?php if(empty($pendingUsers)): ?>
        <div style="padding:2.5rem 1rem;text-align:center;color:var(--color-ink-mute);background:var(--color-canvas-soft);border-radius:var(--radius-md);border:1px dashed var(--color-hairline);">
          <div style="font-size:14px;font-weight:400;color:var(--color-ink);">No Pending Approvals</div>
          <div style="font-size:12px;margin-top:2px;">All user registrations have been reviewed.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table" style="min-width:600px;">
            <thead>
              <tr style="border-bottom:1px solid var(--color-hairline);text-align:left;font-size:12px;color:var(--color-ink-mute);">
                <th style="padding:.75rem;">Name / ID</th>
                <th style="padding:.75rem;">Email</th>
                <th style="padding:.75rem;">Department</th>
                <th style="padding:.75rem;">Requested On</th>
                <th style="padding:.75rem;text-align:right;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($pendingUsers as $u): 
                $uName = trim($u['full_name'] ?? 'User');
                $parts = explode(' ', $uName);
                $uInitials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? substr(end($parts), 0, 1) : ''));
              ?>
              <tr style="border-bottom:1px solid var(--color-canvas-soft);">
                <td style="padding:.75rem;">
                  <div style="display:flex;align-items:center;gap:0.75rem;">
                    <div style="width:36px;height:36px;border-radius:50%;overflow:hidden;background:linear-gradient(135deg, var(--color-primary) 0%, #6366f1 100%);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:12px;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.06);border:1px solid rgba(0,0,0,0.04);">
                      <?php if (!empty($u['avatar_path'])): ?>
                        <img src="<?= asset($u['avatar_path']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;display:block;">
                      <?php else: ?>
                        <?= htmlspecialchars($uInitials, ENT_QUOTES, 'UTF-8') ?>
                      <?php endif; ?>
                    </div>
                    <div>
                      <div style="font-weight:500;color:var(--color-ink);"><?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div style="font-size:11px;color:var(--color-ink-mute);"><?= htmlspecialchars($u['id_number'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </div>
                </td>
                <td style="padding:.75rem;font-size:13px;"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:.75rem;font-size:13px;"><?= htmlspecialchars($u['department_program'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:.75rem;font-size:13px;font-feature-settings:'tnum';"><?= date('M d, Y h:i A', strtotime($u['created_at'])) ?></td>
                <td style="padding:.75rem;text-align:right;">
                  <div style="display:inline-flex;gap:.5rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="openApproveModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)">Approve</button>
                    <button type="button" class="btn btn-sm btn-ghost" style="color:var(--color-error);border-color:rgba(220,38,38,0.2);" onclick="openRejectModal(<?= $u['user_id'] ?>)">Reject</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- TAB 2: User Directory -->
    <div x-show="tab === 'directory'" style="display:none;">
      <div style="margin-bottom:1rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
        <div>
          <h3 style="font-size:16px;font-weight:600;color:var(--color-ink);">All Users</h3>
          <p style="font-size:13px;color:var(--color-ink-mute);">Manage active, suspended, and approved users.</p>
        </div>
        <div class="table-scroll-hint">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
          Scroll horizontally to view
        </div>
      </div>

      <div class="table-responsive">
        <table class="table" style="min-width:620px;">
          <thead>
            <tr style="border-bottom:1px solid var(--color-hairline);text-align:left;font-size:12px;color:var(--color-ink-mute);">
              <th style="padding:.75rem;">Name / ID</th>
              <th style="padding:.75rem;">Role</th>
              <th style="padding:.75rem;">Status</th>
              <th style="padding:.75rem;">Max Borrows</th>
              <th style="padding:.75rem;text-align:right;">Manage</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($allUsers as $u): 
              $isCurrentUser = ($u['user_id'] == ($_SESSION['user_id'] ?? 0));
              $uName = trim($u['full_name'] ?? 'User');
              $parts = explode(' ', $uName);
              $uInitials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? substr(end($parts), 0, 1) : ''));
            ?>
            <tr style="border-bottom:1px solid var(--color-canvas-soft);">
              <td style="padding:.75rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                  <div style="width:36px;height:36px;border-radius:50%;overflow:hidden;background:linear-gradient(135deg, var(--color-primary) 0%, #6366f1 100%);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:12px;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.06);border:1px solid rgba(0,0,0,0.04);">
                    <?php if (!empty($u['avatar_path'])): ?>
                      <img src="<?= asset($u['avatar_path']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;display:block;">
                    <?php else: ?>
                      <?= htmlspecialchars($uInitials, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div style="font-weight:500;color:var(--color-ink);display:flex;align-items:center;gap:4px;">
                      <span><?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                      <?php if ($isCurrentUser): ?>
                        <span class="badge" style="font-size:10px;background:var(--color-canvas-soft);color:var(--color-ink-mute);font-weight:500;">(You)</span>
                      <?php endif; ?>
                    </div>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;"><?= htmlspecialchars($u['id_number'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
              </td>
              <td style="padding:.75rem;font-size:13px;text-transform:capitalize;">
                <?php if($u['role']): ?>
                  <span class="badge" style="background:var(--color-canvas-soft);color:var(--color-ink);"><?= htmlspecialchars(str_replace('_', ' ', $u['role']), ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                  <span style="color:var(--color-ink-mute);">-</span>
                <?php endif; ?>
              </td>
              <td style="padding:.75rem;font-size:13px;">
                <?php if($u['account_status'] === 'approved'): ?>
                  <span class="badge badge-available">Approved</span>
                <?php elseif($u['account_status'] === 'suspended'): ?>
                  <span class="badge badge-maintenance">Suspended</span>
                <?php elseif($u['account_status'] === 'pending'): ?>
                  <span class="badge badge-pending">Pending</span>
                <?php else: ?>
                  <span class="badge" style="background:var(--color-error-bg);color:var(--color-error);"><?= htmlspecialchars(ucfirst($u['account_status']), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
              </td>
              <td style="padding:.75rem;font-size:13px;font-feature-settings:'tnum';"><?= $u['max_concurrent_loans'] ?></td>
              <td style="padding:.75rem;text-align:right;">
                <?php if ($isCurrentUser): ?>
                  <button class="btn btn-ghost btn-icon-sm" disabled style="opacity:0.35;cursor:not-allowed;" title="You cannot edit your own account in the User Management Center">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </button>
                <?php else: ?>
                  <button class="btn btn-ghost btn-icon-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)" title="Edit User">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ─── Approve User Modal ────────────────────────────────────── -->
<div id="approve-user-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="approve-user-modal-title">
  <div class="modal-panel sm">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="approve-user-modal-title">Approve Registration</h2>
        <div class="modal-subtitle">Assign an institutional role to <strong id="approve_user_name" style="color:var(--color-ink);font-weight:500;"></strong></div>
      </div>
      <button class="modal-close" data-modal-close="approve-user-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    
    <form method="POST" action="<?= url('/admin/users/approve') ?>">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="user_id" id="approve_user_id">
        
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
          <div class="form-group">
            <label class="form-label" for="approve_role">Institutional Role</label>
            <select name="role" id="approve_role" class="form-input form-select" required>
              <option value="student">Student</option>
              <option value="faculty">Faculty</option>
              <option value="custodian">Property Custodian</option>
              <option value="executive_admin">Executive Admin</option>
              <option value="inspector">Local Inspector</option>
              <option value="admin">System Admin</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="approve_max_loans">Max Concurrent Borrows</label>
            <input type="number" name="max_concurrent_loans" id="approve_max_loans" class="form-input" value="3" min="1" required style="font-feature-settings:'tnum';">
            <span style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;display:block;">Maximum number of simultaneous active borrow transactions allowed.</span>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="approve-user-modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Approve User</button>
      </div>
    </form>
  </div>
</div>

<!-- ─── Reject / Purge User Modal ─────────────────────────────── -->
<div id="reject-user-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="reject-user-modal-title">
  <div class="modal-panel sm">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="reject-user-modal-title">Reject Registration</h2>
        <div class="modal-subtitle">Provide a rejection reason or purge request</div>
      </div>
      <button class="modal-close" data-modal-close="reject-user-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    
    <div class="modal-body">
      <form method="POST" action="<?= url('/admin/users/reject') ?>" style="margin-bottom:1.25rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="user_id" id="reject_user_id">
        
        <div class="form-group">
          <label class="form-label">Rejection Reason</label>
          <textarea name="rejection_reason" class="form-input" rows="3" placeholder="e.g. Invalid institutional ID number or unregistered student record." required></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:1rem;">
          <button type="submit" class="btn btn-sm" style="background:var(--color-ruby);color:white;border-radius:var(--radius-pill);padding:8px 16px;">Reject Request</button>
        </div>
      </form>
      
      <div style="border-top:1px solid var(--color-hairline);padding-top:1rem;">
        <form method="POST" action="<?= url('/admin/users/purge') ?>" data-confirm="Are you sure you want to permanently purge this pending registration record? This cannot be undone." data-confirm-title="Purge Registration Record" data-confirm-btn="Purge Record">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="user_id" id="purge_user_id">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:12px;color:var(--color-ink-mute);">Or remove request entirely:</span>
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-error);border-color:rgba(220,38,38,0.2);">Purge Record</button>
          </div>
        </form>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-ghost btn-sm" data-modal-close="reject-user-modal">Close</button>
    </div>
  </div>
</div>

<!-- ─── Edit User Profile Modal (Stripe-Inspired Design) ────── -->
<div id="edit-user-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="edit-user-modal-title">
  <div class="modal-panel md">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="edit-user-modal-title">Edit User Profile</h2>
        <div class="modal-subtitle">Update credentials, institutional role, and active permissions</div>
      </div>
      <button class="modal-close" data-modal-close="edit-user-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    
    <div class="modal-body">
      <!-- Main Profile Form -->
      <form id="edit-user-form" method="POST" action="<?= url('/admin/users/edit') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="user_id" id="edit_user_id">
        
        <div style="display:flex;flex-direction:column;gap:1rem;">
          <div class="form-grid-2col">
            <div class="form-group">
              <label class="form-label" for="edit_full_name">Full Name</label>
              <input type="text" name="full_name" id="edit_full_name" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="edit_email">Institutional Email</label>
              <input type="email" name="email" id="edit_email" class="form-input" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="edit_department">Department / Program</label>
            <input type="text" name="department_program" id="edit_department" class="form-input" placeholder="e.g. BSIT / BSFAS / BIndTech / Faculty / Campus Administration">
          </div>

          <div class="form-grid-2col">
            <div class="form-group">
              <label class="form-label" for="edit_role">Institutional Role</label>
              <select name="role" id="edit_role" class="form-input form-select" required>
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
                <option value="custodian">Property Custodian</option>
                <option value="executive_admin">Executive Admin</option>
                <option value="inspector">Local Inspector</option>
                <option value="admin">System Admin</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="edit_max_loans">Max Concurrent Borrows</label>
              <input type="number" name="max_concurrent_loans" id="edit_max_loans" class="form-input" min="1" required style="font-feature-settings:'tnum';">
            </div>
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;">
          <button type="button" class="btn btn-ghost btn-sm" data-modal-close="edit-user-modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
        </div>
      </form>
      
      <!-- Administrative Overrides Card -->
      <div style="margin-top:1.75rem;padding:1.25rem;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);">
        <div style="font-size:12px;font-weight:600;color:var(--color-ink);letter-spacing:0.04em;text-transform:uppercase;margin-bottom:0.875rem;">
          Administrative Overrides
        </div>

        <!-- Password Override -->
        <form method="POST" action="<?= url('/admin/users/password-override') ?>" data-confirm="Are you sure you want to forcibly override this user's password?" data-confirm-title="Override User Password" data-confirm-btn="Override Password" data-confirm-destructive="false" style="margin-bottom:1.25rem;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="user_id" id="pw_user_id">
          
          <div class="form-group" style="margin-bottom:0.75rem;">
            <label class="form-label" for="pw_override_input" style="font-size:11px;color:var(--color-ink-mute);margin-bottom:4px;display:block;">Forced Password Reset</label>
            <input type="password" name="new_password" id="pw_override_input" class="form-input" style="width:100%;" placeholder="New password (min 8 chars)" required minlength="8">
          </div>
          <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-secondary override-pw-btn" style="padding:8px 18px;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              <span>Override Password</span>
            </button>
          </div>
        </form>

        <div style="border-top:1px solid var(--color-hairline);padding-top:0.875rem;display:flex;align-items:center;justify-content:space-between;">
          <div style="font-size:12px;color:var(--color-ink-mute);">
            Permanent Deletion Guard
          </div>
          <form method="POST" action="<?= url('/admin/users/delete') ?>" data-confirm="Are you sure you want to permanently delete this user account? If they have transaction or audit records, deletion will be safely rejected." data-confirm-title="Delete User Account" data-confirm-btn="Delete Account" data-confirm-destructive="true">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="user_id" id="delete_user_id">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-error);border-color:rgba(220,38,38,0.2);">Delete Account</button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function openApproveModal(user) {
  document.getElementById('approve_user_id').value = user.user_id;
  document.getElementById('approve_user_name').textContent = user.full_name;
  
  if (user.department_program && user.department_program.toLowerCase().includes('faculty')) {
    document.getElementById('approve_role').value = 'faculty';
    document.getElementById('approve_max_loans').value = '5';
  } else {
    document.getElementById('approve_role').value = 'student';
    document.getElementById('approve_max_loans').value = '3';
  }
  
  Modal.open('approve-user-modal');
}

function openRejectModal(userId) {
  document.getElementById('reject_user_id').value = userId;
  document.getElementById('purge_user_id').value = userId;
  Modal.open('reject-user-modal');
}

function openEditModal(user) {
  if (user.user_id === <?= (int)($_SESSION['user_id'] ?? 0) ?>) {
    if (typeof Toast !== 'undefined') {
      Toast.error('Access Restricted', 'You cannot edit your own account from the User Management Center.');
    }
    return;
  }

  document.getElementById('edit_user_id').value = user.user_id;
  document.getElementById('pw_user_id').value = user.user_id;
  document.getElementById('delete_user_id').value = user.user_id;
  const pwInput = document.getElementById('pw_override_input');
  if (pwInput) pwInput.value = '';
  
  document.getElementById('edit_full_name').value = user.full_name;
  document.getElementById('edit_email').value = user.email;
  document.getElementById('edit_department').value = user.department_program || '';
  document.getElementById('edit_role').value = user.role || 'student';
  document.getElementById('edit_max_loans').value = user.max_concurrent_loans || 3;
  
  Modal.open('edit-user-modal');
}
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>


