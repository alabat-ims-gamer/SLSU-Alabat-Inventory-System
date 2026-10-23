<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <title>Request Access — SLSU-Alabat IMS</title>
  <meta name="description" content="Request access to the SLSU-Alabat Campus Inventory Management System">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= asset('/css/custom.css') ?>">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: { DEFAULT: '#166534', deep: '#14532d', press: '#052e16', soft: '#22c55e', subdued: '#dcfce7' },
            ink: { DEFAULT: '#0f291e', secondary: '#244234', mute: '#5c7367' },
            brand: { dark: '#0a2318' },
            gold: { DEFAULT: '#d97706', light: '#fef3c7', deep: '#b45309' }
          }
        }
      }
    };
  </script>
</head>
<body style="background:var(--color-canvas-soft);">

<div class="mesh-auth-backdrop"></div>

<div class="auth-page" style="align-items:flex-start;padding-top:3rem;padding-bottom:3rem;">
  <div class="auth-card animate-fade-in-up" style="max-width:520px;">

    <!-- Logo -->
    <div class="auth-logo">
      <div class="auth-logo-mark">
        <img src="<?= asset('/images/slsu-logo.png') ?>" alt="SLSU-Alabat Logo">
      </div>
      <div class="auth-logo-text">
        <span class="auth-logo-name">SLSU-Alabat IMS</span>
        <span class="auth-logo-sub">Inventory &amp; Properties Tracking</span>
      </div>
    </div>

    <h1 class="auth-title">Request system access</h1>
    <p class="auth-subtitle" style="margin-bottom:1.5rem;">
      New accounts require approval from the Property Custodian or Administrator before activation.
    </p>

    <!-- Pending approval notice -->
    <div class="auth-alert auth-alert-info" style="align-items:flex-start;margin-bottom:1.5rem;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;">
        <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>
      </svg>
      <span>Once your account has been reviewed and approved by an Administrator, you will be able to sign in with your credentials.</span>
    </div>

    <?php 
      $old = $old ?? [];
      $valFirstName = htmlspecialchars($old['first_name'] ?? $_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8');
      $valLastName  = htmlspecialchars($old['last_name'] ?? $_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8');
      $valEmail     = htmlspecialchars($old['email'] ?? $_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
      $valIdNumber  = htmlspecialchars($old['id_number'] ?? $_POST['id_number'] ?? '', ENT_QUOTES, 'UTF-8');
      $valDept      = $old['department_program'] ?? $_POST['department_program'] ?? $_POST['department'] ?? '';
    ?>

    <form id="register-form" method="POST" action="<?= url('/register') ?>" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <div style="display:flex;flex-direction:column;gap:1rem;">

        <!-- Name row -->
        <div class="form-grid-2col">
          <div class="form-group">
            <label class="form-label" for="first_name">First Name</label>
            <input id="first_name" name="first_name" type="text" class="form-input"
              placeholder="Maria" required
              value="<?= $valFirstName ?>">
            <span class="form-error" id="first-name-error" style="display:none;"></span>
          </div>
          <div class="form-group">
            <label class="form-label" for="last_name">Last Name</label>
            <input id="last_name" name="last_name" type="text" class="form-input"
              placeholder="Reyes" required
              value="<?= $valLastName ?>">
            <span class="form-error" id="last-name-error" style="display:none;"></span>
          </div>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label class="form-label" for="reg-email">Institutional Email</label>
          <input id="reg-email" name="email" type="email" class="form-input"
            placeholder="m.reyes@slsu.edu.ph" required
            value="<?= $valEmail ?>">
          <span class="form-helper">Use your official SLSU email address.</span>
          <span class="form-error" id="reg-email-error" style="display:none;"></span>
        </div>

        <!-- Department -->
        <div class="form-group" id="department-group">
          <label class="form-label" for="department">Department / Academic Program</label>
          <select id="department" name="department_program" class="form-input form-select" required>
            <option value="" disabled <?= empty($valDept) ? 'selected' : '' ?>>Select department / program...</option>
            <optgroup label="Academic Programs (Students)">
              <?php foreach (\App\Models\User::ACADEMIC_PROGRAMS as $code => $label): ?>
                <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $valDept === $code ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Administrative & Faculty Units">
              <?php foreach (\App\Models\User::DEPARTMENTS as $code => $label): ?>
                <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= ($valDept === $code || ($code === 'Campus Administration' && $valDept === 'admin')) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          </select>
          <span class="form-error" id="dept-error" style="display:none;"></span>
        </div>

        <!-- Student/Employee ID -->
        <div class="form-group">
          <label class="form-label" for="id_number" id="id-label">Student / Employee ID Number</label>
          <input id="id_number" name="id_number" type="text" class="form-input"
            placeholder="e.g. 2021-00147" required
            value="<?= $valIdNumber ?>">
          <span class="form-error" id="id-error" style="display:none;"></span>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="reg-password">Password</label>
          <input id="reg-password" name="password" type="password" class="form-input"
            placeholder="Minimum 8 characters" required autocomplete="new-password">
          <span class="form-helper">At least 8 characters with a mix of letters and numbers.</span>
          <span class="form-error" id="reg-password-error" style="display:none;"></span>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label class="form-label" for="password_confirm">Confirm Password</label>
          <input id="password_confirm" name="password_confirm" type="password" class="form-input"
            placeholder="Repeat your password" required autocomplete="new-password">
          <span class="form-error" id="confirm-error" style="display:none;"></span>
        </div>

        <button type="submit" class="btn btn-primary w-full" id="register-btn" style="justify-content:center;margin-top:.5rem;">
          <span class="btn-text">Submit Access Request</span>
        </button>

      </div>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="<?= url('/login') ?>" class="auth-link">Sign in</a>
    </div>



  </div>
</div>

<div id="toast-container" aria-live="polite"></div>
<script src="<?= asset('/js/app.js') ?>"></script>
<script>
(function () {
  const form = document.getElementById('register-form');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    let valid = true;
    clearErrors();

    const fn = v('first_name');
    const ln = v('last_name');
    const em = v('reg-email');
    const dp = v('department');
    const id = v('id_number');
    const pw = v('reg-password');
    const pc = v('password_confirm');

    if (!fn.value.trim()) { showErr('first-name-error', 'First name is required.'); fn.classList.add('error'); valid = false; }
    if (!ln.value.trim()) { showErr('last-name-error', 'Last name is required.');  ln.classList.add('error'); valid = false; }
    if (!em.value.trim() || !em.validity.valid) { showErr('reg-email-error', 'Enter a valid institutional email address.'); em.classList.add('error'); valid = false; }
    if (!dp.value) { showErr('dept-error', 'Please select your department / academic program.'); dp.classList.add('error'); valid = false; }
    if (!id.value.trim()) { showErr('id-error', 'Student or Employee ID Number is required.'); id.classList.add('error'); valid = false; }
    if (!pw.value || pw.value.length < 8) { showErr('reg-password-error', 'Password must be at least 8 characters.'); pw.classList.add('error'); valid = false; }
    if (pw.value !== pc.value) { showErr('confirm-error', 'Passwords do not match.'); pc.classList.add('error'); valid = false; }

    if (!valid) { e.preventDefault(); return; }
    document.getElementById('register-btn').classList.add('loading');
  });

  function v(id) { return document.getElementById(id); }
  function showErr(id, msg) { const el = document.getElementById(id); if (el) { el.textContent = msg; el.style.display = 'block'; } }
  function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => { el.style.display = 'none'; el.textContent = ''; });
    document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
  }
})();
</script>
</body>
</html>
