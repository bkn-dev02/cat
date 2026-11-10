<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - CAT Enterprise</title>
    <link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
    <link rel="manifest" href="<?= app_path_url('manifest.json') ?>">
    <meta name="theme-color" content="#6366f1">
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
        }

        * {
            margin: 0; padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }

        .page-wrapper {
            max-width: 520px;
            margin: 2.5rem auto;
            padding: 0 1rem 3rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: opacity 0.2s;
        }
        .back-link:hover { opacity: 0.75; color: var(--primary); }

        .card {
            background: white;
            border-radius: 1.25rem;
            padding: 2.25rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            border: 1px solid var(--border);
        }

        .card-header-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.75rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .card-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.15rem;
        }

        .card-subtitle {
            font-size: 0.8rem;
            color: #64748b;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            margin-bottom: 0.4rem;
        }

        .input-wrap {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 2.75rem 0.75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 0.625rem;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s;
            background: white;
            color: var(--dark);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        .form-control.is-invalid {
            border-color: #ef4444;
        }

        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: var(--primary); }

        .strength-wrap {
            margin-top: 6px;
        }

        .strength-bar-bg {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: all 0.35s ease;
        }

        .strength-label {
            font-size: 0.72rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .match-hint {
            font-size: 0.75rem;
            margin-top: 5px;
            font-weight: 600;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            border-radius: 0.625rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 0.625rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 0.875rem;
            border-radius: 0.625rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .tips-box {
            background: #f0f4ff;
            border: 1px solid #c7d2fe;
            border-radius: 0.625rem;
            padding: 0.875rem 1rem;
            margin-top: 1.25rem;
        }

        .tips-box p {
            font-size: 0.78rem;
            color: #4338ca;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }

        .tips-box ul {
            margin: 0;
            padding-left: 1.1rem;
        }

        .tips-box li {
            font-size: 0.75rem;
            color: #4338ca;
            margin-bottom: 0.15rem;
        }

        @media (max-width: 480px) {
            .card { padding: 1.5rem 1.25rem; }
        }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="page-wrapper">
        <a href="<?= config('app_url') ?>/dashboard" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="card">
            <div class="card-header-section">
                <div class="card-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div>
                    <div class="card-title">Ubah Password</div>
                    <div class="card-subtitle">Perbarui password akun Anda</div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle" style="margin-top:1px;flex-shrink:0"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle" style="margin-top:1px;flex-shrink:0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="changePasswordForm" novalidate>
                <!-- Password Saat Ini -->
                <div class="mb-3">
                    <label class="form-label">Password Saat Ini</label>
                    <div class="input-wrap">
                        <input type="password" name="current_password" id="pw-current"
                               class="form-control" placeholder="Masukkan password saat ini" required autocomplete="current-password">
                        <button type="button" class="toggle-pw" onclick="togglePw('pw-current','eye-current')" tabindex="-1">
                            <i class="fas fa-eye" id="eye-current"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Baru -->
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <div class="input-wrap">
                        <input type="password" name="new_password" id="pw-new"
                               class="form-control" placeholder="Minimal 8 karakter" required autocomplete="new-password"
                               oninput="checkStrength(this.value); checkMatch()">
                        <button type="button" class="toggle-pw" onclick="togglePw('pw-new','eye-new')" tabindex="-1">
                            <i class="fas fa-eye" id="eye-new"></i>
                        </button>
                    </div>
                    <div class="strength-wrap">
                        <div class="strength-bar-bg">
                            <div class="strength-bar-fill" id="strength-bar"></div>
                        </div>
                        <div class="strength-label" id="strength-text" style="color:#94a3b8"></div>
                    </div>
                </div>

                <!-- Konfirmasi Password -->
                <div class="mb-4">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <div class="input-wrap">
                        <input type="password" name="confirm_password" id="pw-confirm"
                               class="form-control" placeholder="Ulangi password baru" required autocomplete="new-password"
                               oninput="checkMatch()">
                        <button type="button" class="toggle-pw" onclick="togglePw('pw-confirm','eye-confirm')" tabindex="-1">
                            <i class="fas fa-eye" id="eye-confirm"></i>
                        </button>
                    </div>
                    <div class="match-hint" id="match-hint"></div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-save"></i> Simpan Password Baru
                </button>
            </form>

            <div class="tips-box">
                <p><i class="fas fa-shield-alt"></i> Tips password yang kuat:</p>
                <ul>
                    <li>Minimal 8 karakter</li>
                    <li>Kombinasi huruf besar dan kecil</li>
                    <li>Tambahkan angka dan simbol (!, @, #, dll)</li>
                    <li>Jangan gunakan informasi pribadi</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        function togglePw(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function checkStrength(val) {
            const bar  = document.getElementById('strength-bar');
            const text = document.getElementById('strength-text');
            let score = 0;
            if (val.length >= 8)           score++;
            if (/[A-Z]/.test(val))         score++;
            if (/[0-9]/.test(val))         score++;
            if (/[^A-Za-z0-9]/.test(val))  score++;

            const levels = [
                { w: '0%',   c: '#e2e8f0', t: '' },
                { w: '25%',  c: '#ef4444', t: 'Lemah' },
                { w: '50%',  c: '#f59e0b', t: 'Cukup' },
                { w: '75%',  c: '#3b82f6', t: 'Kuat' },
                { w: '100%', c: '#10b981', t: 'Sangat Kuat' },
            ];

            bar.style.width      = levels[score].w;
            bar.style.background = levels[score].c;
            text.textContent     = levels[score].t;
            text.style.color     = levels[score].c;
        }

        function checkMatch() {
            const pw1  = document.getElementById('pw-new').value;
            const pw2  = document.getElementById('pw-confirm').value;
            const hint = document.getElementById('match-hint');
            const confirmInput = document.getElementById('pw-confirm');

            if (pw2.length === 0) {
                hint.textContent = '';
                confirmInput.classList.remove('is-invalid');
                return;
            }

            if (pw1 === pw2) {
                hint.textContent = '✓ Password cocok';
                hint.style.color = '#10b981';
                confirmInput.classList.remove('is-invalid');
            } else {
                hint.textContent = '✗ Password tidak cocok';
                hint.style.color = '#ef4444';
                confirmInput.classList.add('is-invalid');
            }
        }

        // Prevent submit if passwords don't match
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const pw1 = document.getElementById('pw-new').value;
            const pw2 = document.getElementById('pw-confirm').value;
            if (pw1 !== pw2) {
                e.preventDefault();
                document.getElementById('pw-confirm').focus();
                checkMatch();
            }
        });
    </script>
</body>
</html>
