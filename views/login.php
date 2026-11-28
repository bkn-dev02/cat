<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= asset_url('images/cat.png') ?>" type="image/x-icon">
    <title>Login Page | CAT SMA UNGGUL BINA KASIH NUSANTARA</title>
    <link rel="manifest" href="<?= app_path_url('manifest.json') ?>">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CAT SMA UNGGUL BINA KASIH NUSANTARA">
    <link rel="apple-touch-icon" href="<?= asset_url('cat.png') ?>">
    <!-- Aset CSS lokal — CSS fallback inline di bawah menjaga tampilan jika aset gagal dimuat -->
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <!-- Fallback CSS kritis — aktif di semua kondisi jaringan (termasuk Electron offline) -->
    <style>
        /* ── Fallback kritis agar form tetap tampil meski CDN gagal ── */
        * {
            box-sizing: border-box
        }

        .fas,
        .fa-sign-in-alt,
        .fa-eye,
        .fa-eye-slash,
        .fa-exclamation-circle,
        .fa-check,
        .fa-code {
            font-style: normal
        }

        .fas:empty::before {
            content: ''
        }

        /* Icon fallback sederhana */
        .fa-sign-in-alt::before {
            content: '→'
        }

        .fa-eye::before {
            content: '👁'
        }

        .fa-eye-slash::before {
            content: '🚫'
        }

        .fa-exclamation-circle::before {
            content: '⚠'
        }

        .fa-check::before {
            content: '✓'
        }

        .fa-code::before {
            content: '</>'
        }
    </style>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #2e4cd3 0%, #0a2bcc 0%, #050136 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            flex-direction: column;
            gap: 1.5rem;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: white;
            border-radius: 1.5rem;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            /* background: var(--primary); */
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 1rem;
        }

        .logo-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .logo-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s;
            background: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* ── Remember Me Row ── */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            cursor: pointer;
            user-select: none;
        }

        .remember-label input[type="checkbox"] {
            display: none;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .remember-label input[type="checkbox"]:checked+.custom-checkbox {
            background: var(--primary);
            border-color: var(--primary);
        }

        .remember-label input[type="checkbox"]:checked+.custom-checkbox i {
            opacity: 1;
        }

        .custom-checkbox i {
            font-size: 11px;
            color: white;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .remember-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
        }

        .remember-duration {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* ── Submit Button ── */
        .btn-login {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.875rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* ── Footer ── */
        .footer-text {
            text-align: center;
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .footer-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 24px;
            border-radius: 50px;
        }

        .footer-dot {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .footer-dot i {
            font-size: 11px;
            color: white;
        }

        /* ── Spinner Loading ── */
        .btn-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .btn-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.5px solid rgba(255, 255, 255, 0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: btn-spin 0.7s linear infinite;
            flex-shrink: 0;
        }

        .btn-login.loading {
            pointer-events: none;
            cursor: wait;
            opacity: 0.85;
            transform: none;
            box-shadow: none;
        }

        .btn-login.loading .btn-spinner {
            display: inline-block;
        }

        .btn-login.loading .btn-icon {
            display: none;
        }

        @keyframes btn-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-card">

            <!-- Logo -->
            <div class="logo-section">
                <div class="logo-icon">
                    <img src="<?= asset_url('images/cat.png') ?>" alt="CAT"
                        style="width:100%;height:100%;object-fit:contain;border-radius:1rem;">
                </div>
                <h1 class="logo-title">CAT LOCAL</h1>
                <p class="logo-subtitle">SMA UNGGUL BINA KASIH NUSANTARA</p>
            </div>

            <!-- Error Alert (dari PHP) -->
            <?php if (!empty($error)): ?>
                <div style="background:#fee2e2;color:#dc2626;padding:12px 16px;border-radius:0.75rem;
                            margin-bottom:1.25rem;font-size:0.875rem;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" id="loginForm">

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input
                        type="text"
                        name="username"
                        id="usernameInput"
                        class="form-control"
                        placeholder="Masukkan username"
                        required
                        autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position:relative">
                        <input
                            type="password"
                            name="password"
                            id="passwordInput"
                            class="form-control"
                            placeholder="Masukkan password"
                            style="padding-right:2.75rem"
                            required>
                        <button
                            type="button"
                            onclick="togglePassword()"
                            style="position:absolute;right:14px;top:50%;transform:translateY(-50%);
                                   background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;
                                   display:flex;align-items:center;justify-content:center;width:28px;height:28px;"
                            aria-label="Tampilkan/sembunyikan password"
                            id="togglePasswordBtn">
                            <!-- SVG eye icon — tidak perlu FontAwesome -->
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <!-- SVG eye-slash — ditampilkan saat password terlihat -->
                            <svg id="eyeSlashIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Ingat Saya -->
                <div class="remember-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember_me" id="rememberMe">
                        <div class="custom-checkbox">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="remember-text">Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="btn-spinner" aria-hidden="true"></span>
                    <i class="fas fa-sign-in-alt btn-icon"></i>
                    <span class="btn-label">Masuk</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-text">
        <div class="footer-pill">
            <div class="footer-dot">
                <i class="fas fa-code"></i>
            </div>
            <span style="font-size:0.8rem;color:rgba(255,255,255,0.9);letter-spacing:0.3px;">
                Created by <strong style="color:white;">Oktavianus Sihotang</strong> &mdash; Programmer BKN
            </span>
        </div>
    </div>

    <script>
        const REMEMBER_KEY = 'cat_remember_session';
        const SESSION_KEY = 'cat_temp_session';
        const DURATION_30D = 30 * 24 * 60 * 60 * 1000;
        const DURATION_8H = 8 * 60 * 60 * 1000;
        const LOGIN_DELAY_MS = 800;

        const form = document.getElementById('loginForm');
        const usernameInput = document.getElementById('usernameInput');
        const passwordInput = document.getElementById('passwordInput');
        const rememberCheck = document.getElementById('rememberMe');
        const btnLogin = document.getElementById('btnLogin');
        const btnLabel = btnLogin.querySelector('.btn-label');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const eyeOn = document.getElementById('eyeIcon');
        const eyeOff = document.getElementById('eyeSlashIcon');

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= app_path_url('sw.js') ?>').catch(() => {});
        }

        function togglePassword() {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            eyeOn.style.display = isHidden ? 'none' : 'block';
            eyeOff.style.display = isHidden ? 'block' : 'none';
            toggleBtn.style.color = isHidden ? '#6366f1' : '#94a3b8';
        }

        function saveSession(username, remember) {
            const payload = JSON.stringify({
                username,
                remember,
                expiry: Date.now() + (remember ? DURATION_30D : DURATION_8H),
                savedAt: new Date().toLocaleString('id-ID')
            });

            try {
                if (remember) {
                    localStorage.setItem(REMEMBER_KEY, payload);
                    sessionStorage.removeItem(SESSION_KEY);
                } else {
                    sessionStorage.setItem(SESSION_KEY, payload);
                    localStorage.removeItem(REMEMBER_KEY);
                }
            } catch (err) {
                // Storage diblokir (mode privat / kebijakan browser) — abaikan
            }
        }

        function clearSession() {
            try {
                localStorage.removeItem(REMEMBER_KEY);
                sessionStorage.removeItem(SESSION_KEY);
            } catch (err) {}
        }

        function loadSession() {
            let raw;
            try {
                raw = localStorage.getItem(REMEMBER_KEY) || sessionStorage.getItem(SESSION_KEY);
            } catch (err) {
                return;
            }
            if (!raw) return;

            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                return;
            }

            if (Date.now() > data.expiry) {
                clearSession();
                return;
            }

            usernameInput.value = data.username;
            rememberCheck.checked = !!data.remember;
        }

        function setLoading(isLoading) {
            btnLogin.classList.toggle('loading', isLoading);
            btnLabel.textContent = isLoading ? 'Memproses...' : 'Masuk';
        }

        form.addEventListener('submit', function(e) {
            const username = usernameInput.value.trim();
            if (!username) return;

            if (btnLogin.classList.contains('loading')) {
                e.preventDefault();
                return;
            }

            saveSession(username, rememberCheck.checked);
            setLoading(true);

            e.preventDefault();
            setTimeout(() => form.submit(), LOGIN_DELAY_MS);
        });

        window.addEventListener('pageshow', function(e) {
            if (e.persisted) setLoading(false);
        });

        loadSession();
    </script>
</body>

</html>