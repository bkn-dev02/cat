<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download BKN CAT Browser</title>
    <link rel="icon" type="image/png" href="/assets/cat.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 50%, #1a2f4e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 1.5rem;
            padding: 2.5rem;
            max-width: 580px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }

        .app-icon {
            width: 80px;
            height: 80px;
            background: transparent;
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            overflow: hidden;
        }

        .app-name {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
            margin-bottom: 0.25rem;
        }

        .app-sub {
            color: #64748b;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
        }

        .feature-list {
            background: #f8fafc;
            border-radius: 0.875rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 0;
            font-size: 0.875rem;
            color: #374151;
            font-weight: 500;
        }

        .feature-item i { color: #10b981; width: 16px; }

        /* Download buttons */
        .download-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .btn-download {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 1rem 0.75rem;
            border-radius: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            text-align: center;
        }

        .btn-download:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            text-decoration: none;
        }

        .btn-windows {
            background: linear-gradient(135deg, #1e3a5f, #2d6a9f);
            color: white;
            box-shadow: 0 4px 12px rgba(30,58,95,0.3);
        }

        .btn-windows:hover {
            box-shadow: 0 8px 20px rgba(30,58,95,0.4);
            color: white;
        }

        .btn-mac {
            background: linear-gradient(135deg, #1c1c1e, #3a3a3c);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }

        .btn-mac:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.35);
            color: white;
        }

        .btn-mac.coming-soon {
            background: linear-gradient(135deg, #94a3b8, #64748b);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .btn-mac.coming-soon:hover {
            transform: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-download .os-icon {
            font-size: 1.5rem;
            line-height: 1;
        }

        .btn-download .os-name {
            font-size: 1rem;
            font-weight: 700;
        }

        .btn-download .os-detail {
            font-size: 0.72rem;
            opacity: 0.8;
            font-weight: 500;
        }

        .btn-download .os-badge {
            font-size: 0.65rem;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* Steps */
        .install-steps {
            margin-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            padding-top: 1.5rem;
        }

        .install-steps h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .steps-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .tab-btn {
            flex: 1;
            padding: 0.5rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.5rem;
            background: white;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .tab-btn.active {
            border-color: #1e3a5f;
            background: #1e3a5f;
            color: white;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .step {
            display: flex;
            gap: 0.875rem;
            margin-bottom: 0.875rem;
            align-items: flex-start;
        }

        .step-num {
            width: 26px;
            height: 26px;
            background: #1e3a5f;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .step-text {
            font-size: 0.85rem;
            color: #374151;
            line-height: 1.5;
        }

        .step-text strong { color: #1e293b; }

        .warning-box {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 0.625rem;
            padding: 0.875rem 1rem;
            margin-top: 1.25rem;
            font-size: 0.8rem;
            color: #92400e;
            display: flex;
            gap: 0.5rem;
        }

        .footer-note {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Logo & Title -->
        <div class="app-icon">
            <img src="/assets/logobkn.webp" alt="BKN CAT" style="width:100%;height:100%;object-fit:contain;border-radius:1rem;">
        </div>
        <div class="app-name">BKN CAT Browser</div>
        <div class="app-sub">Aplikasi Ujian Resmi — SMA Unggul Binakasih Nusantara</div>

        <!-- Peringatan HP -->
        <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:0.75rem;padding:0.875rem 1rem;margin-bottom:1.5rem;font-size:0.82rem;color:#92400e;display:flex;gap:0.6rem;align-items:flex-start;">
            <i class="fas fa-mobile-alt" style="margin-top:2px;flex-shrink:0;font-size:1rem;"></i>
            <span>
                <strong>Khusus Laptop/Komputer.</strong> Aplikasi ini hanya dapat dijalankan di laptop atau komputer berbasis <strong>Windows</strong> atau <strong>macOS</strong>.
                Tidak tersedia untuk HP/smartphone (Android maupun iPhone).
                Pastikan Anda menggunakan laptop saat hari ujian.
            </span>
        </div>

        <!-- Features -->
        <div class="feature-list">
            <div class="feature-item"><i class="fas fa-check-circle"></i> Mode ujian terkunci — tidak bisa pindah aplikasi</div>
            <div class="feature-item"><i class="fas fa-check-circle"></i> Blok Alt+Tab, Win key, dan shortcut OS</div>
            <div class="feature-item"><i class="fas fa-check-circle"></i> Blok copy-paste dan klik kanan</div>
            <div class="feature-item"><i class="fas fa-check-circle"></i> Hanya bisa akses server ujian sekolah</div>
            <div class="feature-item"><i class="fas fa-check-circle"></i> Tidak perlu install — langsung jalankan</div>
        </div>

        <!-- Download Buttons -->
        <div class="download-section">
            <!-- Windows -->
            <a href="https://drive.google.com/uc?export=download&id=1-Qgg0hbRilIi35xGVkdyGVMcfnqDtnEa"
               class="btn-download btn-windows">
                <span class="os-icon"><i class="fab fa-windows"></i></span>
                <span class="os-name">Windows</span>
                <span class="os-detail">Windows 10/11 64-bit</span>
                <span class="os-badge">~193 MB</span>
            </a>

            <!-- Mac -->
            <a href="/download/BKN-CAT-Mac.rar"
               download="BKN-CAT-Mac.rar"
               class="btn-download btn-mac"
               id="macBtn">
                <span class="os-icon"><i class="fab fa-apple"></i></span>
                <span class="os-name">macOS</span>
                <span class="os-detail">macOS 11+ (Intel x64)</span>
                <span class="os-badge">~98 MB</span>
            </a>
        </div>

        <!-- Petunjuk patch Mac -->
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:0.875rem;padding:1rem 1.25rem;margin-top:0.75rem;font-size:0.82rem;color:#92400e;">
            <strong><i class="fas fa-wrench"></i> Pengguna Mac — Baca instruksi di dalam RAR:</strong>
            <ol style="margin:0.5rem 0 0 1.25rem;line-height:2">
                <li>Extract RAR → dapatkan: <code>BKN CAT-1.0.0.dmg</code>, <code>app.asar</code>, <code>CARA_INSTALL_MAC.txt</code></li>
                <li>Install DMG → drag <strong>BKN CAT</strong> ke Applications</li>
                <li>Klik kanan <strong>BKN CAT.app</strong> → <strong>Show Package Contents</strong> → masuk ke <strong>Contents/Resources</strong></li>
                <li>Replace <code>app.asar</code> lama dengan file <code>app.asar</code> dari RAR</li>
                <li>Buka aplikasi → login seperti biasa</li>
            </ol>
        </div>

        <!-- Steps with tabs -->
        <div class="install-steps">
            <h3><i class="fas fa-list-ol" style="color:#1e3a5f"></i> Cara Menggunakan</h3>

            <div class="steps-tabs">
                <button class="tab-btn active" onclick="switchTab('windows', this)">
                    <i class="fab fa-windows"></i> Windows
                </button>
                <button class="tab-btn" onclick="switchTab('mac', this)">
                    <i class="fab fa-apple"></i> macOS
                </button>
            </div>

            <!-- Windows steps -->
            <div class="tab-content active" id="tab-windows">
                <div class="step">
                    <div class="step-num">1</div>
                    <div class="step-text">Klik tombol <strong>Windows</strong> di atas → simpan file <strong>BKN-CAT-Portable.rar</strong></div>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <div class="step-text">Klik kanan file RAR → pilih <strong>Extract All</strong> atau gunakan WinRAR/7-Zip → pilih lokasi (misal Desktop)</div>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <div class="step-text">Buka folder hasil extract → masuk ke <strong>BKN-CAT-Windows</strong> → klik dua kali <strong>BKN CAT.exe</strong></div>
                </div>
                <div class="step">
                    <div class="step-num">4</div>
                    <div class="step-text">Jika muncul peringatan Windows: klik <strong>"More info"</strong> → <strong>"Run anyway"</strong></div>
                </div>
                <div class="step">
                    <div class="step-num">5</div>
                    <div class="step-text">Aplikasi terbuka fullscreen → login dengan akun yang diberikan guru</div>
                </div>
            </div>

            <!-- Mac steps -->
            <div class="tab-content" id="tab-mac">
                <div class="step">
                    <div class="step-num">1</div>
                    <div class="step-text">Klik tombol <strong>macOS</strong> di atas → simpan file <strong>BKN-CAT.dmg</strong></div>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <div class="step-text">Buka file <strong>.dmg</strong> → drag ikon <strong>BKN CAT</strong> ke folder Applications</div>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <div class="step-text">Buka Launchpad → klik <strong>BKN CAT</strong></div>
                </div>
                <div class="step">
                    <div class="step-num">4</div>
                    <div class="step-text">Jika muncul peringatan: buka <strong>System Settings → Privacy & Security</strong> → klik <strong>"Open Anyway"</strong></div>
                </div>
                <div class="step">
                    <div class="step-num">5</div>
                    <div class="step-text">Aplikasi terbuka fullscreen → login dengan akun yang diberikan guru</div>
                </div>
            </div>
        </div>

        <div class="warning-box">
            <i class="fas fa-exclamation-triangle" style="margin-top:1px;flex-shrink:0"></i>
            <span>Gunakan aplikasi ini <strong>hanya saat ujian berlangsung</strong>. Jangan tutup aplikasi sebelum ujian selesai.</span>
        </div>

        <div class="footer-note">
            Hanya untuk <strong>Laptop Windows 10/11</strong> &amp; <strong>MacBook/Mac (macOS 11+)</strong> &nbsp;•&nbsp; ❌ Tidak tersedia untuk HP &nbsp;•&nbsp; v1.2.0
        </div>
    </div>

    <script>
        function switchTab(os, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + os).classList.add('active');
        }
    </script>
</body>
</html>
