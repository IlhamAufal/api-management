<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?= esc($title ?? 'Sign In | MD-Bridge') ?></title>
  <link rel="icon" href="<?= base_url('favicon.ico') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: #f8fafc; color: #101828; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .auth-shell { display: grid; min-height: 100vh; grid-template-columns: minmax(0, 1fr) minmax(440px, .9fr); }
    .auth-form-panel { display: flex; align-items: center; justify-content: center; padding: 48px 32px; background: #fff; }
    .auth-form-wrap { width: 100%; max-width: 430px; }
    .auth-logo { display: inline-flex; align-items: center; gap: 12px; color: #344054; font-weight: 700; text-decoration: none; }
    .auth-logo img { width: 42px; height: 42px; }
    .auth-eyebrow { margin: 48px 0 0; color: #465fff; font-size: 12px; font-weight: 700; letter-spacing: .14em; }
    .auth-title { margin: 10px 0 0; color: #101828; font-size: clamp(30px, 4vw, 42px); line-height: 1.15; letter-spacing: -.03em; }
    .auth-copy { margin: 14px 0 32px; color: #667085; font-size: 15px; line-height: 1.7; }
    .auth-alert { margin-bottom: 20px; border: 1px solid #fecdca; border-radius: 12px; padding: 12px 14px; background: #fef3f2; color: #b42318; font-size: 13px; line-height: 1.5; }
    .auth-alert-success { border-color: #abefc6; background: #ecfdf3; color: #067647; }
    .auth-field { margin-bottom: 20px; }
    .auth-field label { display: block; margin-bottom: 8px; color: #344054; font-size: 14px; font-weight: 600; }
    .auth-input-wrap { position: relative; }
    .auth-input { width: 100%; height: 50px; border: 1px solid #d0d5dd; border-radius: 12px; padding: 0 15px; background: #fff; color: #101828; font: inherit; outline: none; transition: border-color .18s ease, box-shadow .18s ease; }
    .auth-input-password { padding-right: 54px; }
    .auth-input:focus { border-color: #7592ff; box-shadow: 0 0 0 4px rgba(70, 95, 255, .12); }
    .auth-input[aria-invalid="true"] { border-color: #f04438; }
    .auth-error { display: block; margin-top: 7px; color: #d92d20; font-size: 12px; }
    .password-toggle { position: absolute; top: 50%; right: 8px; width: 38px; height: 38px; transform: translateY(-50%); border: 0; border-radius: 9px; background: transparent; color: #667085; cursor: pointer; }
    .password-toggle:hover { background: #f2f4f7; color: #344054; }
    .auth-submit { display: inline-flex; width: 100%; height: 50px; align-items: center; justify-content: center; border: 0; border-radius: 12px; background: #465fff; color: #fff; font-size: 15px; font-weight: 700; cursor: pointer; transition: background .18s ease, transform .18s ease; }
    .auth-submit:hover { background: #3641f5; transform: translateY(-1px); }
    .auth-note { margin: 24px 0 0; color: #98a2b3; font-size: 12px; text-align: center; }
    .auth-art-panel { position: relative; display: flex; min-height: 100vh; align-items: center; justify-content: center; overflow: hidden; padding: 56px; background: radial-gradient(circle at 20% 20%, rgba(117,146,255,.34), transparent 34%), radial-gradient(circle at 80% 80%, rgba(54,65,245,.32), transparent 34%), #101828; }
    .auth-art-grid { position: absolute; inset: 0; opacity: .13; background-image: linear-gradient(rgba(255,255,255,.18) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.18) 1px, transparent 1px); background-size: 48px 48px; mask-image: linear-gradient(to bottom, transparent, black 20%, black 80%, transparent); }
    .auth-visual { position: relative; z-index: 1; width: min(100%, 560px); }
    .auth-visual svg { display: block; width: 100%; height: auto; filter: drop-shadow(0 28px 70px rgba(0,0,0,.28)); }
    .auth-art-copy { position: relative; z-index: 1; max-width: 520px; margin: 36px auto 0; color: #d0d5dd; text-align: center; }
    .auth-art-copy strong { display: block; margin-bottom: 10px; color: #fff; font-size: 24px; }
    .auth-art-copy span { font-size: 14px; line-height: 1.7; }
    @media (max-width: 960px) { .auth-shell { grid-template-columns: 1fr; } .auth-form-panel { min-height: 100vh; } .auth-art-panel { display: none; } }
    @media (max-width: 540px) { .auth-form-panel { padding: 32px 22px; } .auth-eyebrow { margin-top: 38px; } }
  </style>
</head>
<body>
  <?php
  $errors = session()->getFlashdata('auth_errors') ?: [];
  $loginEmail = session()->getFlashdata('login_email') ?: '';
  ?>
  <main class="auth-shell">
    <section class="auth-form-panel" aria-labelledby="signin-title">
      <div class="auth-form-wrap">
        <a href="<?= base_url('login') ?>" class="auth-logo" aria-label="MD-Bridge">
          <img src="<?= base_url('assets/images/logo/logo-icon.svg') ?>" alt="">
          <span>MD-Bridge</span>
        </a>

        <p class="auth-eyebrow">Secure Workspace</p>
        <h1 id="signin-title" class="auth-title">Sign in to your account</h1>
        <p class="auth-copy">Masukkan email dan password untuk mengakses dashboard integrasi dan monitoring sinkronisasi.</p>

        <?php if ($message = session()->getFlashdata('auth_error')): ?>
          <div class="auth-alert" role="alert"><?= esc($message) ?></div>
        <?php endif; ?>


        <form method="post" action="<?= base_url('login') ?>" novalidate>
          <?= csrf_field() ?>
          <div class="auth-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" class="auth-input" value="<?= esc($loginEmail, 'attr') ?>" placeholder="name@company.com" autocomplete="username" maxlength="190" required autofocus aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>">
            <?php if (isset($errors['email'])): ?><span class="auth-error"><?= esc($errors['email']) ?></span><?php endif; ?>
          </div>

          <div class="auth-field">
            <label for="password">Password</label>
            <div class="auth-input-wrap">
              <input id="password" name="password" type="password" class="auth-input auth-input-password" placeholder="Enter your password" autocomplete="current-password" maxlength="255" required aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>">
              <button id="password-toggle" type="button" class="password-toggle" aria-label="Tampilkan password" aria-pressed="false">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/></svg>
              </button>
            </div>
            <?php if (isset($errors['password'])): ?><span class="auth-error"><?= esc($errors['password']) ?></span><?php endif; ?>
          </div>

          <button type="submit" class="auth-submit">Sign In</button>
        </form>
        <p class="auth-note">Akses hanya untuk pengguna yang telah terdaftar.</p>
      </div>
    </section>

    <aside class="auth-art-panel" aria-label="Ilustrasi integrasi data">
      <div class="auth-art-grid"></div>
      <div>
        <div class="auth-visual">
          <svg viewBox="0 0 680 460" role="img" aria-labelledby="auth-art-title auth-art-desc">
            <title id="auth-art-title">Ilustrasi pipeline data MD-Bridge</title>
            <desc id="auth-art-desc">Beberapa aplikasi dan database terhubung melalui pipeline monitoring.</desc>
            <defs>
              <linearGradient id="cardGradient" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#344054"/><stop offset="1" stop-color="#1d2939"/></linearGradient>
              <linearGradient id="flowGradient" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#7592ff"/><stop offset="1" stop-color="#9b8afb"/></linearGradient>
            </defs>
            <rect x="45" y="48" width="590" height="364" rx="34" fill="url(#cardGradient)" stroke="#475467" stroke-width="2"/>
            <rect x="82" y="86" width="516" height="54" rx="16" fill="#101828" opacity=".8"/>
            <circle cx="112" cy="113" r="8" fill="#f97066"/><circle cx="138" cy="113" r="8" fill="#fdb022"/><circle cx="164" cy="113" r="8" fill="#32d583"/>
            <rect x="485" y="104" width="78" height="18" rx="9" fill="#12b76a" opacity=".9"/>
            <g fill="#101828" stroke="#667085" stroke-width="2">
              <rect x="92" y="190" width="130" height="100" rx="18"/><rect x="275" y="190" width="130" height="100" rx="18"/><rect x="458" y="190" width="130" height="100" rx="18"/>
            </g>
            <g fill="none" stroke="url(#flowGradient)" stroke-width="6" stroke-linecap="round">
              <path d="M224 240h45"/><path d="M407 240h45"/>
            </g>
            <g fill="#7592ff"><path d="m264 230 14 10-14 10Z"/><path d="m447 230 14 10-14 10Z"/></g>
            <g fill="#fff" font-family="Inter, sans-serif" text-anchor="middle">
              <text x="157" y="230" font-size="16" font-weight="700">SAP Source</text><text x="157" y="255" font-size="12" fill="#98a2b3">Fetch</text>
              <text x="340" y="230" font-size="16" font-weight="700">MD-Bridge</text><text x="340" y="255" font-size="12" fill="#98a2b3">Monitor</text>
              <text x="523" y="230" font-size="16" font-weight="700">AWS RDS</text><text x="523" y="255" font-size="12" fill="#98a2b3">Persist</text>
            </g>
            <rect x="92" y="326" width="496" height="48" rx="14" fill="#101828" opacity=".82"/>
            <rect x="116" y="345" width="210" height="10" rx="5" fill="#475467"/><rect x="465" y="340" width="98" height="20" rx="10" fill="#465fff"/>
          </svg>
        </div>
        <p class="auth-art-copy"><strong>One bridge. Every integration.</strong><span>Pantau kesehatan pipeline, riwayat cronjob, dan aliran master data dalam satu command center.</span></p>
      </div>
    </aside>
  </main>

  <?= view('components/toast') ?>

  <script defer src="<?= base_url('assets/js/bundle.js') ?>"></script>
  <script>
  (() => {
    const input = document.getElementById('password');
    const button = document.getElementById('password-toggle');
    button.addEventListener('click', () => {
      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      button.setAttribute('aria-pressed', visible ? 'false' : 'true');
      button.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
      input.focus();
    });
  })();
  </script>
</body>
</html>
