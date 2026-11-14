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
    body { margin: 0; min-height: 100vh; background: #fafafa; color: #171717; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .auth-shell { display: grid; min-height: 100vh; grid-template-columns: minmax(0, 1fr) minmax(440px, .95fr); }
    .auth-form-panel { display: flex; align-items: center; justify-content: center; padding: 48px 32px; background: #fff; }
    .auth-form-wrap { width: 100%; max-width: 420px; }
    .auth-logo { display: inline-flex; align-items: center; gap: 10px; color: #171717; font-size: 15px; font-weight: 600; text-decoration: none; }
    .auth-logo img { width: 32px; height: 32px; }
    .auth-eyebrow { margin: 40px 0 0; color: #737373; font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
    .auth-title { margin: 8px 0 0; color: #171717; font-size: 28px; font-weight: 600; line-height: 1.2; letter-spacing: -.02em; }
    .auth-copy { margin: 10px 0 28px; color: #525252; font-size: 14px; line-height: 1.6; }
    .auth-alert { margin-bottom: 20px; border: 1px solid #fecdca; border-radius: 8px; padding: 12px 14px; background: #fef3f2; color: #b42318; font-size: 13px; line-height: 1.5; }
    .auth-alert-success { border-color: #abefc6; background: #ecfdf3; color: #067647; }
    .auth-field { margin-bottom: 20px; }
    .auth-field label { display: block; margin-bottom: 8px; color: #344054; font-size: 13px; font-weight: 500; }
    .auth-input-wrap { position: relative; }
    .auth-input { width: 100%; height: 44px; border: 1px solid #d0d5dd; border-radius: 8px; padding: 0 14px; background: #fff; color: #171717; font: inherit; font-size: 14px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .auth-input-password { padding-right: 46px; }
    .auth-input:focus { border-color: #465fff; box-shadow: 0 0 0 3px rgba(70, 95, 255, .15); }
    .auth-input[aria-invalid="true"] { border-color: #f04438; }
    .auth-error { display: block; margin-top: 6px; color: #d92d20; font-size: 12px; }
    .password-toggle { position: absolute; top: 50%; right: 6px; width: 32px; height: 32px; transform: translateY(-50%); border: 0; border-radius: 6px; background: transparent; color: #667085; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .password-toggle:hover { background: #f2f4f7; color: #344054; }
    .auth-submit { display: inline-flex; width: 100%; height: 44px; align-items: center; justify-content: center; border: 0; border-radius: 8px; background: #465fff; color: #fff; font-size: 14px; font-weight: 500; cursor: pointer; transition: background .15s ease; }
    .auth-submit:hover { background: #3641f5; }
    .auth-note { margin: 24px 0 0; color: #85858b; font-size: 12px; text-align: center; }
    .auth-art-panel { position: relative; display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 48px; background: #0c0e14; border-left: 1px solid #1c1f28; }
    .auth-visual { position: relative; width: min(100%, 520px); }
    .auth-visual svg { display: block; width: 100%; height: auto; }
    .auth-art-copy { max-width: 460px; margin: 28px auto 0; color: #94a3b8; text-align: center; }
    .auth-art-copy strong { display: block; margin-bottom: 6px; color: #f1f5f9; font-size: 16px; font-weight: 600; }
    .auth-art-copy span { font-size: 13px; line-height: 1.6; color: #64748b; }
    @media (max-width: 960px) { .auth-shell { grid-template-columns: 1fr; } .auth-form-panel { min-height: 100vh; } .auth-art-panel { display: none; } }
    @media (max-width: 540px) { .auth-form-panel { padding: 32px 20px; } .auth-eyebrow { margin-top: 24px; } }
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

    <aside class="auth-art-panel" aria-label="Skematik integrasi data">
      <div>
        <div class="auth-visual">
          <svg viewBox="0 0 540 340" role="img" aria-labelledby="auth-art-title auth-art-desc" fill="none" xmlns="http://www.w3.org/2000/svg">
            <title id="auth-art-title">Skematik pipeline data MD-Bridge</title>
            <desc id="auth-art-desc">Arsitektur telemetri data dari database source ke konsol monitoring.</desc>
            <rect x="1" y="1" width="538" height="338" rx="12" fill="#131620" stroke="#1f2432"/>
            
            <!-- Header bar -->
            <path d="M1 13C1 6.37 6.37 1 13 1H527C533.63 1 539 6.37 539 13V40H1V13Z" fill="#171b26"/>
            <line x1="1" y1="40" x2="539" y2="40" stroke="#1f2432"/>
            <circle cx="24" cy="20" r="4" fill="#22c55e"/>
            <text x="36" y="24" fill="#94a3b8" font-family="ui-monospace, monospace" font-size="11" font-weight="500">MD-BRIDGE TELEMETRY ENGINE</text>
            <text x="515" y="24" text-anchor="end" fill="#64748b" font-family="ui-monospace, monospace" font-size="10">v2.4.0</text>
            
            <!-- Node 1: Sources -->
            <rect x="28" y="72" width="136" height="180" rx="8" fill="#1a1e2c" stroke="#282f42"/>
            <text x="44" y="98" fill="#e2e8f0" font-family="Inter, sans-serif" font-size="13" font-weight="600">Sources</text>
            <text x="44" y="114" fill="#64748b" font-family="Inter, sans-serif" font-size="10">Active Databases</text>
            <rect x="40" y="132" width="112" height="36" rx="6" fill="#121520" stroke="#23293a"/>
            <text x="52" y="154" fill="#cbd5e1" font-family="ui-monospace, monospace" font-size="11">npd (rds)</text>
            <circle cx="138" cy="150" r="3" fill="#22c55e"/>
            <rect x="40" y="176" width="112" height="36" rx="6" fill="#121520" stroke="#23293a"/>
            <text x="52" y="198" fill="#cbd5e1" font-family="ui-monospace, monospace" font-size="11">sap (db2)</text>
            <circle cx="138" cy="194" r="3" fill="#22c55e"/>
            <text x="44" y="234" fill="#475569" font-family="ui-monospace, monospace" font-size="10">TCP ESTABLISHED</text>

            <!-- Connector 1 -->
            <line x1="164" y1="162" x2="202" y2="162" stroke="#465fff" stroke-width="2"/>
            <polygon points="202,158 208,162 202,166" fill="#465fff"/>

            <!-- Node 2: Checker -->
            <rect x="208" y="96" width="124" height="132" rx="8" fill="#1a1e2c" stroke="#3b82f6" stroke-opacity="0.4"/>
            <text x="224" y="122" fill="#e2e8f0" font-family="Inter, sans-serif" font-size="13" font-weight="600">Checker</text>
            <text x="224" y="138" fill="#64748b" font-family="Inter, sans-serif" font-size="10">Sync Evaluator</text>
            <rect x="220" y="152" width="100" height="28" rx="4" fill="#121520"/>
            <text x="232" y="170" fill="#93c5fd" font-family="ui-monospace, monospace" font-size="10">cron / manual</text>
            <text x="224" y="206" fill="#475569" font-family="ui-monospace, monospace" font-size="10">INTERVAL 60s</text>

            <!-- Connector 2 -->
            <line x1="332" y1="162" x2="370" y2="162" stroke="#465fff" stroke-width="2"/>
            <polygon points="370,158 376,162 370,166" fill="#465fff"/>

            <!-- Node 3: Matrix Console -->
            <rect x="376" y="72" width="136" height="180" rx="8" fill="#1a1e2c" stroke="#282f42"/>
            <text x="392" y="98" fill="#e2e8f0" font-family="Inter, sans-serif" font-size="13" font-weight="600">Console</text>
            <text x="392" y="114" fill="#64748b" font-family="Inter, sans-serif" font-size="10">Telemetry Matrix</text>
            <rect x="388" y="132" width="112" height="36" rx="6" fill="#121520" stroke="#23293a"/>
            <text x="400" y="154" fill="#cbd5e1" font-family="ui-monospace, monospace" font-size="11">fx_orders</text>
            <text x="488" y="154" text-anchor="end" fill="#22c55e" font-family="ui-monospace, monospace" font-size="10">OK</text>
            <rect x="388" y="176" width="112" height="36" rx="6" fill="#121520" stroke="#23293a"/>
            <text x="400" y="198" fill="#cbd5e1" font-family="ui-monospace, monospace" font-size="11">master_item</text>
            <text x="488" y="198" text-anchor="end" fill="#eab308" font-family="ui-monospace, monospace" font-size="10">STALE</text>
            <text x="392" y="234" fill="#475569" font-family="ui-monospace, monospace" font-size="10">SNAPSHOT SAVED</text>
            
            <!-- Bottom status bar -->
            <rect x="28" y="280" width="484" height="34" rx="6" fill="#121520" stroke="#1f2432"/>
            <circle cx="44" cy="297" r="3" fill="#22c55e"/>
            <text x="56" y="301" fill="#94a3b8" font-family="ui-monospace, monospace" font-size="11">LIVE TELEMETRY STREAM</text>
            <text x="496" y="301" text-anchor="end" fill="#64748b" font-family="ui-monospace, monospace" font-size="10">ALL SERVICES OPERATIONAL</text>
          </svg>
        </div>
        <p class="auth-art-copy"><strong>Telemetry integrasi terpusat</strong><span>Pantau kesehatan sinkronisasi basis data, ambang stale, dan status tabel dalam satu ruang kendali.</span></p>
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
