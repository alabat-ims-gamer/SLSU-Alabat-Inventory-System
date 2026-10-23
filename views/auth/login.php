<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <title>Sign In — SLSU-Alabat IMS</title>
  <meta name="description" content="Sign in to the SLSU-Alabat Campus Inventory Management System">
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

<!-- Animated mesh backdrop -->
<div class="mesh-auth-backdrop"></div>

<!-- Auth Page -->
<div class="auth-page">
  <div class="auth-card animate-fade-in-up">

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

    <!-- Heading -->
    <h1 class="auth-title">Sign in to your account</h1>
    <p class="auth-subtitle">Enter your institutional credentials to continue.</p>

    <?php if (!empty($notice)): ?>
      <div class="auth-alert auth-alert-info">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
          <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <span><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="auth-alert auth-alert-danger">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
          <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form id="login-form" method="POST" action="<?= url('/login') ?>" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <div style="display:flex;flex-direction:column;gap:1rem;">

        <div class="form-group">
          <label class="form-label" for="email">Institutional Email</label>
          <input
            id="email"
            name="email"
            type="email"
            class="form-input"
            placeholder="you@slsu.edu.ph"
            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="email"
            required
          >
          <span class="form-error" id="email-error" style="display:none;"></span>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div style="position:relative;">
            <input
              id="password"
              name="password"
              type="password"
              class="form-input"
              placeholder="Your password"
              value=""
              autocomplete="current-password"
              required
              style="padding-right:2.5rem;"
            >
            <button type="button" id="toggle-password"
              style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-ink-mute);"
              aria-label="Toggle password visibility">
              <svg id="eye-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
          <span class="form-error" id="password-error" style="display:none;"></span>
        </div>

        <div style="display:flex;align-items:center;gap:.5rem;">
          <input id="remember" name="remember" type="checkbox" value="1" checked
            style="width:15px;height:15px;border:1px solid var(--color-hairline-input);border-radius:3px;accent-color:var(--color-primary);cursor:pointer;">
          <label for="remember" style="font-size:13px;color:var(--color-ink-mute);cursor:pointer;">Remember this device for 30 days</label>
        </div>

        <button type="submit" class="btn btn-primary w-full" id="login-btn" style="justify-content:center;margin-top:.5rem;">
          <span class="btn-text">Sign In</span>
        </button>

      </div>
    </form>

    <div class="auth-divider">
      <div class="auth-divider-line"></div>
      <span class="auth-divider-text">or</span>
      <div class="auth-divider-line"></div>
    </div>

    <div class="auth-footer">
      Don't have an account? <a href="<?= url('/register') ?>" class="auth-link">Request access</a>
    </div>



  </div>
</div>

<!-- Toast Container -->
<div id="toast-container" aria-live="polite"></div>

<script src="<?= asset('/js/app.js') ?>"></script>
<script>
(function () {
  // Password toggle
  const toggleBtn = document.getElementById('toggle-password');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eye-icon');
  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', function () {
      const isText = passwordInput.type === 'text';
      passwordInput.type = isText ? 'password' : 'text';
      eyeIcon.innerHTML = isText
        ? '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>'
        : '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><line x1="1" y1="23" x2="23" y2="1"></line>';
    });
  }

  // Form validation
  const form = document.getElementById('login-form');
  const submitBtn = document.getElementById('login-btn');
  if (form) {
    form.addEventListener('submit', function (e) {
      let valid = true;
      const email = document.getElementById('email');
      const pass  = document.getElementById('password');

      document.querySelectorAll('.form-error').forEach(el => { el.style.display = 'none'; el.textContent = ''; });
      document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));

      if (!email.value.trim() || !email.validity.valid) {
        showError('email-error', 'Please enter a valid email address.');
        email.classList.add('error');
        valid = false;
      }

      if (!pass.value) {
        showError('password-error', 'Password is required.');
        pass.classList.add('error');
        valid = false;
      }

      if (!valid) {
        e.preventDefault();
        return;
      }

      submitBtn.classList.add('loading');
    });
  }

  function showError(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.style.display = 'block'; }
  }
})();
</script>
</body>
</html>
