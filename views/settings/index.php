<?php
/**
 * User Settings & Profile Center (Universal for All Authenticated Roles)
 * Designed following Stripe-Inspired Design System (stripe-DESIGN.md)
 */

$title      = 'User Settings';
$breadcrumb = 'Settings & Profile';

require __DIR__ . '/../layouts/shell.php';
?>

<!-- Alpine.js for smooth reactive tab switching and form interactions -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
  [x-cloak] { display: none !important; }
  .settings-save-btn {
    transition: opacity 0.2s ease, transform 0.1s ease;
  }
  .settings-save-btn:disabled {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
    pointer-events: none;
  }
</style>

<div class="section-header">
  <div>
    <h1 class="section-title">Account Settings</h1>
    <div class="section-subtitle">Manage personal profile, identity credentials, security settings, and device sessions</div>
  </div>
  <div style="display:flex;align-items:center;gap:0.75rem;">
    <span class="badge" style="background:var(--color-canvas-soft);color:var(--color-ink);border:1px solid var(--color-hairline);padding:6px 12px;font-size:12px;">
      Role: <strong style="font-weight:600;text-transform:capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $user['role']), ENT_QUOTES, 'UTF-8') ?></strong>
    </span>
  </div>
</div>

<div x-data="{ tab: '<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>' }" class="card" style="padding:0;overflow:hidden;border:1px solid var(--color-hairline);border-radius:var(--radius-lg);box-shadow:0 1px 3px rgba(0,55,112,0.06);">

  <!-- Tab Navigation Bar (Responsive Horizontal Scroll) -->
  <div class="tabs-header-scroll" style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);display:flex;gap:0.25rem;padding:0.5rem 1rem;overflow-x:auto;">
    
    <button @click="tab = 'profile'" :class="{'tab-active': tab === 'profile'}" class="tab-btn" style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;font-size:13px;font-weight:500;border:none;background:none;border-radius:var(--radius-md);cursor:pointer;color:var(--color-ink-mute);transition:all 0.15s ease;">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <span>Profile & Avatar</span>
    </button>

    <button @click="tab = 'security'" :class="{'tab-active': tab === 'security'}" class="tab-btn" style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;font-size:13px;font-weight:500;border:none;background:none;border-radius:var(--radius-md);cursor:pointer;color:var(--color-ink-mute);transition:all 0.15s ease;">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
      <span>Security & Credentials</span>
    </button>

    <button @click="tab = 'sessions'" :class="{'tab-active': tab === 'sessions'}" class="tab-btn" style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;font-size:13px;font-weight:500;border:none;background:none;border-radius:var(--radius-md);cursor:pointer;color:var(--color-ink-mute);transition:all 0.15s ease;">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
      <span>Active Sessions</span>
    </button>

    <button @click="tab = 'notifications'" :class="{'tab-active': tab === 'notifications'}" class="tab-btn" style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;font-size:13px;font-weight:500;border:none;background:none;border-radius:var(--radius-md);cursor:pointer;color:var(--color-ink-mute);transition:all 0.15s ease;">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
      <span>Alert Preferences</span>
    </button>

    <button @click="tab = 'activity'" :class="{'tab-active': tab === 'activity'}" class="tab-btn" style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;font-size:13px;font-weight:500;border:none;background:none;border-radius:var(--radius-md);cursor:pointer;color:var(--color-ink-mute);transition:all 0.15s ease;">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
      <span>Personal Activity & Safety</span>
    </button>

  </div>

  <div class="card-responsive-body" style="padding:1.75rem;">

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- TAB 1: Profile & Identity Customization                         -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div x-show="tab === 'profile'" x-cloak>
      <div style="margin-bottom:1.5rem;">
        <h3 style="font-size:17px;font-weight:600;color:var(--color-ink);letter-spacing:-0.2px;">Public Profile & Identity</h3>
        <p style="font-size:13px;color:var(--color-ink-mute);margin-top:2px;">Customize your display name, department designation, and profile avatar.</p>
      </div>

      <!-- Avatar Management Section -->
      <div style="padding:1.5rem;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);margin-bottom:2rem;display:flex;gap:1.5rem;align-items:center;flex-wrap:wrap;">
        
        <!-- Live Avatar Preview Box -->
        <div style="position:relative;width:80px;height:80px;border-radius:var(--radius-pill);overflow:hidden;background:linear-gradient(135deg, var(--color-primary) 0%, var(--color-ruby) 100%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,55,112,0.12);flex-shrink:0;">
          <?php if (!empty($user['avatar_path'])): ?>
            <img id="avatar-preview-img" src="<?= asset($user['avatar_path']) ?>" alt="Profile Avatar" style="width:100%;height:100%;object-fit:cover;display:block;">
          <?php else: ?>
            <img id="avatar-preview-img" src="" alt="Profile Avatar" style="width:100%;height:100%;object-fit:cover;display:none;">
            <div id="avatar-fallback-initials" style="font-size:26px;font-weight:500;color:white;letter-spacing:0.5px;">
              <?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>
        </div>

        <div style="flex:1;min-width:240px;">
          <div style="font-size:14px;font-weight:600;color:var(--color-ink);">Profile Picture</div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;margin-bottom:0.75rem;">
            Upload JPEG, PNG, or WEBP (Max 2MB). Images are securely sanitized and re-encoded.
          </div>

          <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
            <!-- Upload Form -->
            <form id="avatar-upload-form" method="POST" action="<?= url('/settings/avatar') ?>" enctype="multipart/form-data" style="display:inline-flex;align-items:center;gap:0.5rem;">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="file" name="avatar" id="avatar-file-input" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewAndSubmitAvatar(this)">
              <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('avatar-file-input').click()" style="border-radius:var(--radius-pill);padding:6px 14px;display:inline-flex;align-items:center;gap:6px;">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Upload Photo
              </button>
            </form>

            <?php if (!empty($user['avatar_path'])): ?>
            <form method="POST" action="<?= url('/settings/avatar/remove') ?>" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-error);border-color:rgba(220,38,38,0.2);border-radius:var(--radius-pill);padding:6px 14px;">
                Remove Photo
              </button>
            </form>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Profile Details Form -->
      <form method="POST" action="<?= url('/settings/profile') ?>" x-data="{
        initFullName: <?= htmlspecialchars(json_encode((string)($user['full_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,
        initDepartment: <?= htmlspecialchars(json_encode((string)($user['department_program'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,
        initPhone: <?= htmlspecialchars(json_encode((string)($user['phone_number'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,
        fullName: <?= htmlspecialchars(json_encode((string)($user['full_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,
        department: <?= htmlspecialchars(json_encode((string)($user['department_program'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,
        phone: <?= htmlspecialchars(json_encode((string)($user['phone_number'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,
        get isDirty() {
          return String(this.fullName || '').trim() !== String(this.initFullName || '').trim() ||
                 String(this.department || '').trim() !== String(this.initDepartment || '').trim() ||
                 String(this.phone || '').trim() !== String(this.initPhone || '').trim();
        }
      }">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div style="display:flex;flex-direction:column;gap:1.25rem;">
          
          <div class="form-grid-2col">
            <div class="form-group">
              <label class="form-label" for="profile_full_name">Full Name</label>
              <input type="text" name="full_name" id="profile_full_name" x-model="fullName" class="form-input" required maxlength="120">
              <span style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;display:block;">Your primary name displayed across all inventory and borrowing records.</span>
            </div>

            <div class="form-group">
              <label class="form-label" for="profile_id_number">Institutional ID Number</label>
              <input type="text" id="profile_id_number" class="form-input" value="<?= htmlspecialchars($user['id_number'], ENT_QUOTES, 'UTF-8') ?>" disabled style="background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink-mute);font-feature-settings:'tnum';">
              <span style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;display:block;">Assigned student/employee ID (locked for institutional compliance).</span>
            </div>
          </div>

          <div class="form-grid-2col">
            <div class="form-group">
              <label class="form-label" for="profile_department">Department / Academic Program</label>
              <input type="text" name="department_program" id="profile_department" x-model="department" class="form-input" required placeholder="e.g. BSIT / BSFAS / BIndTech / Faculty / Campus Administration" maxlength="100">
            </div>

            <div class="form-group">
              <label class="form-label" for="profile_phone">Contact Phone Number</label>
              <input type="text" name="phone_number" id="profile_phone" x-model="phone" class="form-input" placeholder="e.g. +63 912 345 6789" maxlength="25" style="font-feature-settings:'tnum';">
              <span style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;display:block;">Optional contact number for equipment borrow and overdue notifications.</span>
            </div>
          </div>

        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid var(--color-hairline);">
          <button type="submit" class="btn btn-primary settings-save-btn" :disabled="!isDirty" style="border-radius:var(--radius-pill);padding:8px 20px;">
            Save Profile Changes
          </button>
        </div>
      </form>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- TAB 2: Security & Credentials Management                       -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div x-show="tab === 'security'" x-cloak>
      <div style="margin-bottom:1.5rem;">
        <h3 style="font-size:17px;font-weight:600;color:var(--color-ink);letter-spacing:-0.2px;">Security & Account Credentials</h3>
        <p style="font-size:13px;color:var(--color-ink-mute);margin-top:2px;">Update your email address or change your login password. Sensitive updates require verifying your current password.</p>
      </div>

      <form method="POST" action="<?= url('/settings/credentials') ?>" x-data="{ 
        initEmail: <?= htmlspecialchars(json_encode((string)($user['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,
        email: <?= htmlspecialchars(json_encode((string)($user['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,
        newPw: '', 
        confirmPw: '',
        currentPw: '',
        showPw: false,
        get strength() {
          if (!this.newPw) return 0;
          let score = 0;
          if (this.newPw.length >= 8) score += 25;
          if (this.newPw.length >= 12) score += 25;
          if (/[A-Z]/.test(this.newPw) && /[a-z]/.test(this.newPw)) score += 25;
          if (/[0-9]/.test(this.newPw) || /[^A-Za-z0-9]/.test(this.newPw)) score += 25;
          return score;
        },
        get strengthColor() {
          if (this.strength < 50) return 'var(--color-ruby)';
          if (this.strength < 75) return '#d97706';
          return '#059669';
        },
        get strengthLabel() {
          if (!this.newPw) return '';
          if (this.strength < 50) return 'Weak (min 8 chars)';
          if (this.strength < 75) return 'Medium strength';
          return 'Strong password';
        },
        get isDirty() {
          const emailChanged = String(this.email || '').trim().toLowerCase() !== String(this.initEmail || '').trim().toLowerCase();
          const passwordTyped = String(this.newPw || '').length > 0;
          return emailChanged || passwordTyped;
        }
      }">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <!-- Email Section -->
        <div style="margin-bottom:2rem;">
          <h4 style="font-size:14px;font-weight:600;color:var(--color-ink);margin-bottom:0.75rem;">Email Address</h4>
          <div class="form-group" style="max-width:540px;">
            <label class="form-label" for="cred_email">Registered Email Address</label>
            <input type="email" name="email" id="cred_email" x-model="email" class="form-input" required placeholder="e.g. yourname@gmail.com or user@slsu.edu.ph">
            <span style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;display:block;">You can use any valid standard personal or institutional email address.</span>
          </div>
        </div>

        <div style="border-top:1px solid var(--color-hairline);margin-bottom:1.75rem;"></div>

        <!-- Password Change Section with Live Strength Meter -->
        <div>
          <h4 style="font-size:14px;font-weight:600;color:var(--color-ink);margin-bottom:0.25rem;">Change Password</h4>
          <p style="font-size:12px;color:var(--color-ink-mute);margin-bottom:1rem;">Leave new password fields blank if you only wish to update your email address.</p>

          <div class="form-grid-2col" style="margin-bottom:1.25rem;">
            <div class="form-group">
              <label class="form-label" for="new_password">New Password</label>
              <div style="position:relative;">
                <input :type="showPw ? 'text' : 'password'" name="new_password" id="new_password" x-model="newPw" class="form-input" minlength="8" placeholder="At least 8 characters">
                <button type="button" @click="showPw = !showPw" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:4px;" aria-label="Toggle password visibility">
                  <svg x-show="!showPw" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  <svg x-show="showPw" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                </button>
              </div>

              <!-- Password Strength Meter -->
              <div x-show="newPw.length > 0" style="margin-top:6px;display:none;">
                <div style="height:4px;width:100%;background:var(--color-canvas-soft);border-radius:var(--radius-pill);overflow:hidden;border:1px solid var(--color-hairline);">
                  <div :style="`width:${strength}%; background:${strengthColor}; height:100%; transition:all 0.3s ease;`"></div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
                  <span style="font-size:11px;color:var(--color-ink-mute);">Password strength:</span>
                  <span :style="`font-size:11px;font-weight:600;color:${strengthColor};`" x-text="strengthLabel"></span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="confirm_password">Confirm New Password</label>
              <input :type="showPw ? 'text' : 'password'" name="confirm_password" id="confirm_password" x-model="confirmPw" class="form-input" placeholder="Re-enter new password">
            </div>
          </div>
        </div>

        <!-- Mandatory Current Password Re-Auth Lock -->
        <div style="padding:1.25rem;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);margin-top:1.5rem;">
          <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            <span style="font-size:13px;font-weight:600;color:var(--color-ink);">Re-Authentication Required</span>
          </div>
          
          <div class="form-group" style="max-width:440px;">
            <label class="form-label" for="current_password">Current Password <span style="color:var(--color-ruby);">*</span></label>
            <input type="password" name="current_password" id="current_password" x-model="currentPw" class="form-input" required placeholder="Enter current password to authorize changes">
            <span style="font-size:11px;color:var(--color-ink-mute);margin-top:4px;display:block;">Required to protect your account against unauthorized modifications.</span>
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid var(--color-hairline);">
          <button type="submit" class="btn btn-primary settings-save-btn" :disabled="!isDirty" style="border-radius:var(--radius-pill);padding:8px 20px;">
            Save Security Credentials
          </button>
        </div>
      </form>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- TAB 3: Active Sessions & Connected Devices                     -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div x-show="tab === 'sessions'" x-cloak>
      <div style="margin-bottom:1.5rem;">
        <h3 style="font-size:17px;font-weight:600;color:var(--color-ink);letter-spacing:-0.2px;">Active Device & Session Security</h3>
        <p style="font-size:13px;color:var(--color-ink-mute);margin-top:2px;">View active device sessions and sign out of other browsers or unattended terminals.</p>
      </div>

      <!-- Current Session Card -->
      <div style="padding:1.5rem;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);box-shadow:0 1px 3px rgba(0,55,112,0.04);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:flex-start;gap:1rem;">
          <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--color-canvas-soft);border:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:center;color:var(--color-primary);flex-shrink:0;">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
          </div>
          <div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
              <div style="font-size:14px;font-weight:600;color:var(--color-ink);">Current Active Session</div>
              <span class="badge badge-available" style="font-size:11px;padding:2px 8px;display:inline-flex;align-items:center;gap:4px;">
                <span style="width:6px;height:6px;border-radius:50%;background:#059669;display:inline-block;"></span> Active Now
              </span>
            </div>
            <div style="font-size:12px;color:var(--color-ink-mute);margin-top:4px;word-break:break-all;">
              <?= htmlspecialchars($currentAgent, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;font-feature-settings:'tnum';">
              IP: <strong><?= htmlspecialchars($currentIp, ENT_QUOTES, 'UTF-8') ?></strong> · Logged in: <?= date('M d, Y h:i A', $loginTimestamp) ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Revoke Other Sessions Card -->
      <div style="padding:1.5rem;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div>
          <div style="font-size:14px;font-weight:600;color:var(--color-ink);">Sign Out of All Other Devices</div>
          <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;max-width:540px;">
            If you logged in on a public campus computer or shared laboratory terminal, click below to invalidate all other active sessions immediately.
          </div>
        </div>

        <form method="POST" action="<?= url('/settings/sessions/revoke-others') ?>" data-confirm="Are you sure you want to sign out of all other active devices and sessions?" data-confirm-title="Revoke Connected Sessions" data-confirm-btn="Sign Out Other Devices" data-confirm-destructive="false">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="btn btn-secondary btn-sm" style="border-radius:var(--radius-pill);padding:8px 18px;white-space:nowrap;">
            Sign Out Other Devices
          </button>
        </form>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- TAB 4: Notification & Alert Preferences                        -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div x-show="tab === 'notifications'" x-cloak>
      <div style="margin-bottom:1.5rem;">
        <h3 style="font-size:17px;font-weight:600;color:var(--color-ink);letter-spacing:-0.2px;">In-App Notification & Alert Preferences</h3>
        <p style="font-size:13px;color:var(--color-ink-mute);margin-top:2px;">Customize system alerts and transaction notices delivered to your Notification Hub.</p>
      </div>

      <form method="POST" action="<?= url('/settings/notifications') ?>" x-data="{
        initDue: <?= (!empty($preferences['alert_due_reminders']) || !empty($preferences['email_due_reminders'])) ? 'true' : 'false' ?>,
        initOverdue: <?= (!empty($preferences['alert_overdue_alerts']) || !empty($preferences['email_overdue_alerts'])) ? 'true' : 'false' ?>,
        initSecurity: <?= (!empty($preferences['alert_security_logins']) || !empty($preferences['email_security_logins'])) ? 'true' : 'false' ?>,
        due: <?= (!empty($preferences['alert_due_reminders']) || !empty($preferences['email_due_reminders'])) ? 'true' : 'false' ?>,
        overdue: <?= (!empty($preferences['alert_overdue_alerts']) || !empty($preferences['email_overdue_alerts'])) ? 'true' : 'false' ?>,
        security: <?= (!empty($preferences['alert_security_logins']) || !empty($preferences['email_security_logins'])) ? 'true' : 'false' ?>,
        get isDirty() {
          return this.due !== this.initDue ||
                 this.overdue !== this.initOverdue ||
                 this.security !== this.initSecurity;
        }
      }">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div style="display:flex;flex-direction:column;gap:1rem;">
          
          <!-- Preference 1 -->
          <label style="display:flex;align-items:flex-start;gap:1rem;padding:1.25rem;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);cursor:pointer;">
            <input type="checkbox" name="alert_due_reminders" value="1" x-model="due" style="margin-top:3px;accent-color:var(--color-primary);width:16px;height:16px;">
            <div>
              <div style="font-size:14px;font-weight:600;color:var(--color-ink);">24-Hour Return Reminders</div>
              <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Receive in-app alerts 24 hours prior to the scheduled return deadline of borrowed equipment.</div>
            </div>
          </label>

          <!-- Preference 2 -->
          <label style="display:flex;align-items:flex-start;gap:1rem;padding:1.25rem;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);cursor:pointer;">
            <input type="checkbox" name="alert_overdue_alerts" value="1" x-model="overdue" style="margin-top:3px;accent-color:var(--color-primary);width:16px;height:16px;">
            <div>
              <div style="font-size:14px;font-weight:600;color:var(--color-ink);">Overdue Borrow Alerts</div>
              <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Receive high-priority alerts in your topbar feed when an item in your custody passes its scheduled cutoff.</div>
            </div>
          </label>

          <!-- Preference 3 -->
          <label style="display:flex;align-items:flex-start;gap:1rem;padding:1.25rem;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--radius-lg);cursor:pointer;">
            <input type="checkbox" name="alert_security_logins" value="1" x-model="security" style="margin-top:3px;accent-color:var(--color-primary);width:16px;height:16px;">
            <div>
              <div style="font-size:14px;font-weight:600;color:var(--color-ink);">Security & Credential Change Notices</div>
              <div style="font-size:12px;color:var(--color-ink-mute);margin-top:2px;">Get security alerts in your topbar feed whenever your password or email address is modified.</div>
            </div>
          </label>

        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid var(--color-hairline);">
          <button type="submit" class="btn btn-primary settings-save-btn" :disabled="!isDirty" style="border-radius:var(--radius-pill);padding:8px 20px;">
            Save Preferences
          </button>
        </div>
      </form>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- TAB 5: Personal Activity & Danger Zone                         -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div x-show="tab === 'activity'" x-cloak>
      <div style="margin-bottom:1.5rem;">
        <h3 style="font-size:17px;font-weight:600;color:var(--color-ink);letter-spacing:-0.2px;">Personal Security & Account Governance</h3>
        <p style="font-size:13px;color:var(--color-ink-mute);margin-top:2px;">Review your personal audit log history and manage account lifecycle.</p>
      </div>

      <!-- Recent Personal Activity Table -->
      <div style="margin-bottom:2.5rem;">
        <h4 style="font-size:14px;font-weight:600;color:var(--color-ink);margin-bottom:0.75rem;">Recent Security Activity</h4>
        
        <?php if (empty($personalLogs)): ?>
          <div style="padding:2rem 1rem;text-align:center;color:var(--color-ink-mute);background:var(--color-canvas-soft);border-radius:var(--radius-md);border:1px dashed var(--color-hairline);font-size:13px;">
            No recent activity recorded.
          </div>
        <?php else: ?>
          <div class="table-responsive" style="border:1px solid var(--color-hairline);border-radius:var(--radius-md);overflow:hidden;">
            <table class="table" style="min-width:600px;margin-bottom:0;">
              <thead>
                <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);text-align:left;font-size:12px;color:var(--color-ink-mute);">
                  <th style="padding:.75rem;">Event Type</th>
                  <th style="padding:.75rem;">Description</th>
                  <th style="padding:.75rem;">IP Address</th>
                  <th style="padding:.75rem;text-align:right;">Date & Time</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($personalLogs as $log): ?>
                <tr style="border-bottom:1px solid var(--color-canvas-soft);font-size:13px;">
                  <td style="padding:.75rem;">
                    <span class="badge" style="background:var(--color-canvas-soft);color:var(--color-ink);font-size:11px;font-weight:500;">
                      <?= htmlspecialchars($log['action_type'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td style="padding:.75rem;color:var(--color-ink);"><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:.75rem;font-size:12px;color:var(--color-ink-mute);font-feature-settings:'tnum';"><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:.75rem;text-align:right;font-size:12px;color:var(--color-ink-mute);font-feature-settings:'tnum';">
                    <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Danger Zone: Account Termination -->
      <div style="padding:1.5rem;background:#fef2f2;border:1px solid rgba(220,38,38,0.25);border-radius:var(--radius-lg);">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--color-ruby)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          <span style="font-size:15px;font-weight:600;color:var(--color-ruby);">Danger Zone: Account Termination</span>
        </div>

        <p style="font-size:13px;color:#991b1b;margin-bottom:1rem;line-height:1.5;">
          <?php if ($hasHistory): ?>
            <strong>Notice:</strong> Your account has active or historical borrowing/inventory transaction records. In compliance with institutional audit policy, your account will be <strong>deactivated and suspended</strong> rather than hard deleted so historical audit trails remain preserved.
          <?php else: ?>
            <strong>Notice:</strong> Your account has no borrowing or transaction records. Requesting termination will <strong>permanently delete</strong> your profile and credentials from the system.
          <?php endif; ?>
        </p>

        <button type="button" class="btn btn-sm" onclick="Modal.open('self-delete-modal')" style="background:var(--color-ruby);color:white;border-radius:var(--radius-pill);padding:8px 18px;font-weight:500;">
          <?= $hasHistory ? 'Deactivate My Account' : 'Permanently Delete Account' ?>
        </button>
      </div>

    </div>

  </div>
</div>

<!-- ─── Account Termination Confirmation Modal ─────────────────── -->
<div id="self-delete-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="self-delete-modal-title">
  <div class="modal-panel sm">
    <div class="modal-header">
      <div>
        <h2 class="modal-title" id="self-delete-modal-title" style="color:var(--color-ruby);">
          <?= $hasHistory ? 'Deactivate Account' : 'Delete Account Permanently' ?>
        </h2>
        <div class="modal-subtitle">Confirm authorization to terminate this account</div>
      </div>
      <button class="modal-close" data-modal-close="self-delete-modal" aria-label="Close">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <form method="POST" action="<?= url('/settings/account/delete') ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="modal-body">
        <div style="font-size:13px;color:var(--color-ink);margin-bottom:1rem;line-height:1.5;">
          <?php if ($hasHistory): ?>
            You will be immediately logged out, and your access will be suspended. All past equipment borrows and transactions will remain archived for institutional auditing.
          <?php else: ?>
            This action cannot be undone. All your profile information and credentials will be completely erased.
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="del_confirm_pw">Verify Current Password <span style="color:var(--color-ruby);">*</span></label>
          <input type="password" name="current_password" id="del_confirm_pw" class="form-input" required placeholder="Enter password to confirm termination">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost btn-sm" data-modal-close="self-delete-modal">Cancel</button>
        <button type="submit" class="btn btn-sm" style="background:var(--color-ruby);color:white;border-radius:var(--radius-pill);padding:8px 18px;">
          Confirm <?= $hasHistory ? 'Deactivation' : 'Permanent Deletion' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function previewAndSubmitAvatar(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    
    // Check file size limit (2MB) on client side
    if (file.size > 2 * 1024 * 1024) {
      if (typeof Toast !== 'undefined') {
        Toast.error('File Too Large', 'Please select an image smaller than 2MB.');
      }
      input.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      const previewImg = document.getElementById('avatar-preview-img');
      const fallbackInitials = document.getElementById('avatar-fallback-initials');
      if (previewImg) {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
      }
      if (fallbackInitials) {
        fallbackInitials.style.display = 'none';
      }
      
      // Submit upload form automatically
      document.getElementById('avatar-upload-form').submit();
    };
    reader.readAsDataURL(file);
  }
}
</script>

<?php require __DIR__ . '/../layouts/shell-footer.php'; ?>
