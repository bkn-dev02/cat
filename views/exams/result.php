<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">

    <script>
        if (!window.MathJax) {
            window.MathJax = {
                tex: {
                    inlineMath: [
                        ['$', '$'],
                        ['\\(', '\\)']
                    ],
                    displayMath: [
                        ['$$', '$$'],
                        ['\\[', '\\]']
                    ],
                    processEscapes: true
                },
                options: {
                    skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
                }
            };
        }
    </script>
    <script id="MathJax-script" async src="<?= asset_url('mathjax/tex-mml-chtml.js') ?>"></script>
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --light: #f1f5f9;
            --dark: #1e293b;
            --border: #e2e8f0;
            --shadow: 0 10px 30px -10px rgba(30, 41, 59, 0.15);
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--light);
        }

        .navbar {
            background: white;
            box-shadow: var(--shadow);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .main-content {
            padding: 3rem 0;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ── Force Submit Banner ─────────────────────────── */
        .force-submit-banner {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .force-submit-banner::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, .05);
            border-radius: 50%;
        }

        .force-submit-banner::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: -20px;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, .04);
            border-radius: 50%;
        }

        .fsb-top {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .fsb-icon {
            width: 36px;
            height: 36px;
            background: rgba(245, 158, 11, .25);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .fsb-badge {
            display: inline-block;
            background: rgba(245, 158, 11, .2);
            border: 1px solid rgba(245, 158, 11, .4);
            color: #fbbf24;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .fsb-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #f8fafc;
            margin: 0;
        }

        .fsb-body {
            font-size: .85rem;
            color: rgba(255, 255, 255, .7);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .fsb-steps {
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .fsb-step {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            font-size: .82rem;
            color: rgba(255, 255, 255, .65);
        }

        .fsb-step-num {
            width: 20px;
            height: 20px;
            background: rgba(99, 102, 241, .35);
            color: #a5b4fc;
            border-radius: 5px;
            font-size: .72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ── Result card (existing styles) ────────────────── */
        .result-card {
            background: white;
            border-radius: 1.5rem;
            padding: 3rem 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            text-align: center;
        }

        .result-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin-bottom: 1.5rem;
        }

        .result-icon.normal {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        }

        .result-icon.forced {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .result-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 2rem;
        }

        .result-score {
            font-size: 4rem;
            font-weight: 800;
            color: var(--success);
            margin-bottom: .5rem;
        }

        .result-label {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .result-details {
            text-align: left;
            background: var(--light);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .result-item {
            display: flex;
            justify-content: space-between;
            padding: .75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .result-item:last-child {
            border-bottom: none;
        }

        .result-item-label {
            color: #64748b;
            font-weight: 500;
        }

        .result-item-value {
            color: var(--dark);
            font-weight: 600;
        }

        .badge {
            padding: .5rem 1rem;
            border-radius: .5rem;
            font-weight: 600;
            font-size: .875rem;
        }

        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.forced-s {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            color: white;
            padding: .875rem 2rem;
            border-radius: .75rem;
            font-weight: 600;
            transition: all .2s;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            color: white;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 2rem 1rem;
            }

            .result-card {
                padding: 2rem 1.5rem;
            }

            .result-score {
                font-size: 3rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="<?= config('app_url') ?>/dashboard">
                <i class="fas fa-graduation-cap"></i> CAT SMA UNGGUL BINA KASIH NUSANTARA
            </a>
        </div>
    </nav>

    <div class="container main-content">

        <?php
        $isForceSubmitted = !empty($session['force_submitted']);
        $isAdmin = isset($user) && in_array($user['role'] ?? '', ['admin', 'guru']);

        // Cek kategori ujian
        $stmt = db()->prepare("SELECT category FROM exams WHERE id = ?");
        $stmt->execute([$session['exam_id']]);
        $examInfo = $stmt->fetch();
        $reguler    = ($examInfo['category'] ?? '') === 'reguler';
        $isPpdb    = ($examInfo['category'] ?? '') === 'ppdb';
        $hideScore = $isPpdb && !$isAdmin || $reguler;
        ?>

        <?php if ($isForceSubmitted): ?>
            <!-- ── FORCE SUBMIT BANNER ─────────────────────────── -->
            <div class="force-submit-banner">
                <div class="fsb-top">
                    <div class="fsb-icon">⚡</div>
                    <div>
                        <div class="fsb-badge">Disubmit oleh Administrator</div>
                        <p class="fsb-title" style="margin-top:.3rem">Ujian Kamu Telah Disubmit Paksa</p>
                    </div>
                </div>

                <p class="fsb-body">
                    Ujianmu di-submit paksa oleh administrator karena:
                    <strong style="color:#fbbf24">batas waktu ujian habis atau tindakan darurat admin.</strong><br>
                    Semua jawaban yang tersimpan di server tetap dihitung dan nilai sudah dihitung otomatis.
                </p>

                <div class="fsb-steps">
                    <div class="fsb-step">
                        <div class="fsb-step-num">1</div>
                        <span>Lihat nilai dan detail jawabanmu di halaman ini.</span>
                    </div>
                    <div class="fsb-step">
                        <div class="fsb-step-num">2</div>
                        <span>Jika ada keberatan, <strong style="color:rgba(255,255,255,.85)">hubungi pengawas</strong> dengan membawa bukti.</span>
                    </div>
                    <div class="fsb-step">
                        <div class="fsb-step-num">3</div>
                        <span>Ujian yang sudah disubmit <strong style="color:rgba(255,255,255,.85)">tidak bisa dibuka kembali.</strong></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── RESULT CARD ─────────────────────────────────── -->
        <div class="result-card">

            <div class="result-icon <?= $isForceSubmitted ? 'forced' : 'normal' ?>">
                <i class="fas <?= $isForceSubmitted ? 'fa-clipboard-check' : 'fa-check' ?>"></i>
            </div>

            <h1 class="result-title">
                <?= $isForceSubmitted ? 'Ujian Disubmit Paksa' : 'Ujian Selesai!' ?>
            </h1>

            <?php if ($hideScore): ?>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:1rem;padding:1.25rem 1.5rem;margin-bottom:2rem;text-align:left;display:flex;align-items:flex-start;gap:1rem;">
                    <div style="width:40px;height:40px;flex-shrink:0;background:#6366f1;border-radius:.75rem;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-info-circle" style="color:white;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <strong style="color:#3730a3;display:block;margin-bottom:.25rem;font-size:.95rem;">
                            Jawaban Anda telah tersimpan
                        </strong>
                        <p style="color:#4338ca;margin:0;font-size:.85rem;line-height:1.6;">
                            Terima kasih telah menyelesaikan ujian ini. Nilai akan diumumkan oleh pihak sekolah pada waktu yang telah ditentukan.
                        </p>
                    </div>
                </div>

            <?php else: ?>
                <div class="result-score"><?= number_format($totalScore ?? $session['score'] ?? 0, 0) ?></div>
                <p class="result-label">
                    <?= $session['status'] === 'needs_grading' ? 'Nilai Sementara' : 'Nilai Total' ?>
                </p>

                <?php if ($session['status'] === 'needs_grading'): ?>
                    <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:1rem 1.5rem;border-radius:.5rem;margin-bottom:2rem;text-align:left;">
                        <div style="display:flex;align-items:start;gap:.75rem;">
                            <i class="fas fa-clock" style="color:#f59e0b;font-size:1.25rem;margin-top:.125rem;"></i>
                            <div>
                                <strong style="color:#92400e;display:block;margin-bottom:.25rem;">Menunggu Koreksi Guru</strong>
                                <p style="color:#92400e;margin:0;font-size:.875rem;">
                                    Beberapa soal memerlukan koreksi manual oleh guru. Nilai di atas adalah nilai sementara. Nilai final akan diumumkan setelah guru selesai mengoreksi.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($session['exam_type']) && $session['exam_type'] === 'multi' && !empty($subjectScores)): ?>
                    <div style="background:#f8fafc;border-radius:1rem;padding:1.5rem;margin-bottom:2rem;">
                        <h5 style="color:#1e293b;font-weight:700;margin-bottom:1rem;font-size:1rem;">Nilai Per Mata Pelajaran:</h5>
                        <?php foreach ($subjectScores as $score): ?>
                            <div style="display:flex;justify-content:space-between;padding:.75rem 0;border-bottom:1px solid #e2e8f0;">
                                <span style="color:#64748b;font-weight:500;"><?= htmlspecialchars($score['subject_name']) ?></span>
                                <span style="color:#10b981;font-weight:700;font-size:1.25rem;"><?= number_format($score['score'], 0) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Detail rows -->
            <div class="result-details">
                <div class="result-item">
                    <span class="result-item-label">Ujian</span>
                    <span class="result-item-value"><?= htmlspecialchars($session['title']) ?></span>
                </div>
                <div class="result-item">
                    <span class="result-item-label">Peserta</span>
                    <span class="result-item-value"><?= htmlspecialchars($session['full_name'] ?? $session['username'] ?? 'Siswa') ?></span>
                </div>
                <?php if (!empty($session['kelas'])): ?>
                    <div class="result-item">
                        <span class="result-item-label">Kelas</span>
                        <span class="result-item-value"><?= htmlspecialchars($session['kelas']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="result-item">
                    <span class="result-item-label">Waktu Mulai</span>
                    <span class="result-item-value"><?= !empty($session['started_at']) ? date('d M Y, H:i', strtotime($session['started_at'])) : '-' ?></span>
                </div>
                <div class="result-item">
                    <span class="result-item-label">Waktu Selesai</span>
                    <span class="result-item-value"><?= !empty($session['submitted_at']) ? date('d M Y, H:i', strtotime($session['submitted_at'])) : '-' ?></span>
                </div>
                <?php if ($isForceSubmitted && !empty($session['force_submitted_at'])): ?>
                    <div class="result-item">
                        <span class="result-item-label">Di-submit Admin</span>
                        <span class="result-item-value" style="color:#d97706;">
                            <?= date('d M Y, H:i', strtotime($session['force_submitted_at'])) ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="result-item">
                    <span class="result-item-label">Status</span>
                    <?php if ($isForceSubmitted): ?>
                        <span class="badge forced-s">⚡ Force Submit</span>
                    <?php else: ?>
                        <span class="badge success">✅ <?= ucfirst($session['status']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <button onclick="goToExamList()" class="btn btn-primary" id="btnKembali">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Ujian
            </button>
        </div>
    </div>

    <script>
        // ── Navigasi aman untuk Electron/ExamBro ─────────────────────────────
        // Hindari history.pushState yang bisa menyebabkan white screen di Electron
        var APP_URL = "<?= config('app_url') ?>";

        function goToExamList() {
            var btn = document.getElementById('btnKembali');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
            }
            // Bersihkan history dulu, lalu navigate — cegah popstate loop
            try {
                // Replace state saat ini agar back tidak kembali ke result
                history.replaceState(null, '', APP_URL + '/exam/result/<?= (int)($session['id'] ?? 0) ?>');
            } catch (e) {}
            window.location.replace(APP_URL + '/exam');
        }

        // Cegah tombol Back browser kembali ke halaman ujian (take.php)
        // Gunakan replaceState, bukan pushState, agar tidak membuat entry baru
        try {
            history.replaceState(null, '', location.href);
        } catch (e) {}

        window.addEventListener('popstate', function() {
            window.location.replace(APP_URL + '/exam');
        });
    </script>
</body>

</html>