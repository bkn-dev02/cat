<?php
// Enable error reporting untuk debugging (hapus di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan variabel yang diperlukan ada
if (!isset($session) || !isset($questions)) {
    die('Error: Data ujian tidak tersedia. Silakan hubungi administrator.');
}

// Jika sesi suspended (setelah admin reset), tampilkan tombol Resume, jangan jalankan timer
$isSuspended = ($session['status'] ?? '') === 'suspended';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= htmlspecialchars($session['title'] ?? 'Ujian') ?> - CAT System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="<?= function_exists('config') ? config('app_url') : '' ?>/assets/pdfjs/pdf.min.js?v=3.11.174"></script>
    <!-- MathJax for Mathematical Formulas -->
    <script>
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
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre'],
                processHtmlClass: 'math-content',
                ignoreHtmlClass: 'no-math'
            },
            startup: {
                ready() {
                    // Guard: only call defaultReady if startup is fully initialized
                    if (MathJax.startup && typeof MathJax.startup.defaultReady === 'function') {
                        MathJax.startup.defaultReady();
                        MathJax.startup.promise.then(() => {
                            MathJax.typesetPromise().catch(err => console.log(err));
                        });
                    }
                }
            }
        };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

    <style>
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #mobile-warning {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ff6b6b;
            color: white;
            padding: 8px;
            text-align: center;
            z-index: 10000;
            font-size: 12px;
            font-weight: bold;
            display: none;
        }

        #violation-counter {
            position: fixed;
            top: 50px;
            right: 10px;
            background: rgba(255, 107, 107, 0.9);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            z-index: 9999;
            font-weight: bold;
            display: none;
        }

        .main-container {
            display: flex;
            height: 100vh;
            padding-top: 60px;
        }

        /* === TAMBAHAN: PDF Viewer Container === */
        .pdf-viewer-container {
            flex: 2.2;
            padding: 20px;
            background: #f8fafc;
            overflow-y: auto;
            border-right: 2px solid #e2e8f0;
            min-width: 0;
            transition: flex 0.3s ease;
        }

        .pdf-frame {
            width: 100%;
            height: calc(100vh - 100px);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .exam-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            margin-right: 300px;
            min-width: 280px;
            transition: flex 0.3s ease, opacity 0.2s ease;
        }

        .pdf-viewer-container.expanded {
            flex: 5;
        }

        .pdf-viewer-container.expanded~.exam-content {
            flex: 0.001;
            opacity: 0;
            pointer-events: none;
            padding: 0;
            overflow: hidden;
        }

        #pdf-expand-btn.active {
            background: #dbeafe;
            color: #1d4ed8;
            border-color: #93c5fd;
        }

        /* === TAMBAHAN: mode full-width saat soal aktif tidak punya PDF ===
           Catatan: .exam-content sudah flex:1, jadi begitu panel PDF disembunyikan
           (display:none), ia otomatis melebar mengisi sisa ruang di .main-container.
           Class ini sengaja dibiarkan tanpa override lebar supaya tidak "menjepit"
           kontennya ke kanan seperti sebelumnya (bug: max-width + margin-left:auto). */
        .exam-content.full-width {
            margin-right: 300px;
        }

        .navigation-panel {
            position: fixed;
            top: 60px;
            right: 0;
            width: 280px;
            height: calc(100vh - 60px);
            background: #f8f9fa;
            border-left: 2px solid #dee2e6;
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
        }

        .timer {
            position: fixed;
            top: 10px;
            left: 20px;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            z-index: 9998;
        }

        .exam-info {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            z-index: 9998;
        }

        .question-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: absolute;
            visibility: hidden;
            pointer-events: none;
            width: calc(100% - 40px);
        }

        .question-card.active {
            position: relative;
            visibility: visible;
            pointer-events: auto;
        }

        .question-card:first-of-type {
            display: block;
        }

        .option-label {
            display: block;
            padding: 15px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: #fff;
        }

        .option-label:hover {
            background: #f0f8ff;
            border-color: #007bff;
        }

        .option-label input[type="radio"]:checked+span {
            font-weight: bold;
            color: #007bff;
        }

        .option-label input[type="radio"]:checked {
            accent-color: #007bff;
        }

        .option-label.selected {
            background: #e3f2fd;
            border-color: #007bff;
        }

        .navigation-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }

        .nav-number {
            width: 40px;
            height: 40px;
            border: 2px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            background: white;
        }

        .nav-number.answered {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .nav-number.doubt {
            background: #ffc107;
            color: #212529;
            border-color: #ffc107;
        }

        .nav-number.unanswered {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .nav-number.current {
            border-width: 3px;
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        }

        .nav-number:hover {
            transform: scale(1.1);
        }

        .legend {
            margin-bottom: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 10px;
        }

        .navigation-controls {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 10px;
        }

        .nav-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .prev-btn {
            background: #6c757d;
            color: white;
        }

        .prev-btn:hover:not(:disabled) {
            background: #5a6268;
        }

        .next-btn {
            background: #007bff;
            color: white;
        }

        .next-btn:hover:not(:disabled) {
            background: #0056b3;
        }

        .doubt-btn {
            background: #ffc107;
            color: #212529;
            margin-top: 15px;
            width: 100%;
        }

        .doubt-btn:hover {
            background: #e0a800;
        }

        .doubt-btn.active {
            background: #ff8c00;
        }

        .submit-btn {
            position: fixed;
            bottom: 20px;
            right: 300px;
            padding: 15px 30px;
            font-size: 18px;
            z-index: 9997;
            background: #28a745;
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .progress-info {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .progress-info h6 {
            margin: 0 0 10px 0;
            color: #495057;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-weight: bold;
            font-size: 16px;
            display: block;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            .pdf-viewer-container {
                height: 50vh;
                border-right: none;
                border-bottom: 2px solid #e2e8f0;
            }

            .pdf-frame {
                height: 100%;
            }

            .exam-content,
            .exam-content.full-width {
                margin-right: 0;
                margin-left: 0;
                max-width: none;
                margin-bottom: 200px;
            }

            .navigation-panel {
                position: fixed;
                bottom: 0;
                top: auto;
                left: 0;
                right: 0;
                width: 100%;
                height: 180px;
                padding: 10px;
                border-left: none;
                border-top: 2px solid #dee2e6;
            }

            .navigation-grid {
                grid-template-columns: repeat(10, 1fr);
                gap: 5px;
            }

            .nav-number {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }

            .submit-btn {
                bottom: 190px;
                right: 20px;
            }

            .progress-info,
            .legend {
                display: none;
            }

            .navigation-controls {
                margin-top: 10px;
            }

            .doubt-btn {
                margin-top: 5px;
                padding: 8px;
                font-size: 14px;
            }
        }

        .pdf-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px 8px 0 0;
            padding: 8px 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .pdf-nav-btn {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 12px;
            cursor: pointer;
            color: #334155;
            transition: all 0.2s;
        }

        .pdf-nav-btn:hover {
            background: #e2e8f0;
        }

        .pdf-nav-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pdf-page-info {
            font-size: 14px;
            color: #475569;
            font-weight: 600;
            min-width: 90px;
            text-align: center;
        }

        .pdf-toolbar-sep {
            width: 1px;
            height: 20px;
            background: #e2e8f0;
            margin: 0 4px;
        }

        .pdf-canvas-wrapper {
            background: #525659;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            height: calc(100vh - 145px);
            overflow: auto;
            padding: 16px;
        }

        #pdf-canvas {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            background: white;
            display: block;
            margin: 0 auto;
            max-width: none;
        }
        /* Page loader / skeleton */
        #page-loader{position:fixed;inset:0;z-index:9999998;background:#f8fafc;display:flex;align-items:center;justify-content:center;padding:20px}
        #page-loader .pl-card{width:min(900px,100%);background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
        .skel{background:linear-gradient(90deg,#e5e7eb 25%,#f3f4f6 37%,#e5e7eb 63%);background-size:400% 100%;animation:skel 1.2s ease-in-out infinite;border-radius:8px}
        @keyframes skel{0%{background-position:100% 0}100%{background-position:0 0}}
        .skel-line{height:14px;margin:10px 0}.skel-title{height:22px;width:55%;margin-bottom:16px}.skel-opt{height:44px;margin:10px 0}
        #page-error{position:fixed;inset:0;z-index:10000060;background:rgba(15,23,42,.92);display:none;align-items:center;justify-content:center;padding:20px;color:#fff;text-align:center}
        #page-error .pe-card{background:#111827;border:1px solid #334155;border-radius:12px;padding:28px;max-width:520px;width:100%}
        #page-error .btn-r{background:#2563eb;color:#fff;border:0;padding:12px 18px;border-radius:8px;font-weight:600;cursor:pointer;margin:6px}
        #page-error .btn-b{background:#334155;color:#fff;border:0;padding:12px 18px;border-radius:8px;font-weight:600;cursor:pointer;margin:6px;text-decoration:none;display:inline-block}
        img[loading="lazy"], audio[preload="none"]{content-visibility:auto}
    </style>
</head>

<body>
    <div id="page-loader" style="<?= $isSuspended ? 'display:none' : '' ?>">
        <div class="pl-card">
            <div class="skel skel-title"></div>
            <div class="skel skel-line" style="width:90%"></div>
            <div class="skel skel-line" style="width:80%"></div>
            <div class="skel skel-line" style="width:70%"></div>
            <div style="height:12px"></div>
            <div class="skel skel-opt"></div>
            <div class="skel skel-opt"></div>
            <div class="skel skel-opt"></div>
            <div class="skel skel-opt"></div>
            <div style="margin-top:14px;color:#64748b;font-weight:600">Memuat ujian, mohon tunggu...</div>
        </div>
    </div>
    <div id="page-error">
        <div class="pe-card">
            <h3 style="margin-bottom:8px">Gagal memuat ujian</h3>
            <p id="page-error-msg" style="color:#cbd5e1;margin-bottom:16px">Koneksi terputus atau terjadi kendala saat memuat. Silakan muat ulang.</p>
            <button class="btn-r" onclick="location.reload()">Muat Ulang</button>
            <a class="btn-b" href="<?= function_exists('config') ? config('app_url') : '' ?>/exam">Kembali ke Daftar Ujian</a>
        </div>
    </div>    <?php if ($isSuspended): ?>
        <div style="position:fixed; inset:0; z-index:9999999; background:rgba(15,23,42,.96); align-items:center; justify-content:center; flex-direction:column;">
            <div style="background: white; padding: 40px; border-radius: 15px; text-align: center; max-width: 600px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <div style="font-size: 80px; margin-bottom: 20px;">⏸️</div>
                <h2 style="color: #2c3e50; margin-bottom: 20px; font-size: 28px;">Ujian Diteduhkan (Suspended)</h2>
                <p style="margin-bottom: 30px; font-size: 18px; color: #7f8c8d; line-height: 1.6;">
                    Sisa durasi ujian Anda telah disimpan. Countdown tidak berjalan sampai Anda menekan tombol di bawah untuk melanjutkan.
                </p>
                <a href="<?= function_exists('config') ? config('app_url') : '' ?>/exam/start/<?= $session['exam_id'] ?>"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 20px 60px; font-size: 20px; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); text-decoration: none; display: inline-block;">
                    Mulai Ujian / Resume
                </a>
                <p style="margin-top: 20px; font-size: 14px; color: #95a5a6;">
                    Waktu yang sudah digunakan tidak akan dikurangi selama menunggu resume.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div id="mobile-warning">
        ⚠️ JANGAN KELUAR DARI BROWSER! Pelanggaran: <span id="warning-count">0</span>/3
    </div>

    <!-- [FIX-FS] Layar penghalang fullscreen -->
    <div id="fullscreen-prompt" style="display:<?= $isSuspended ? 'none' : 'flex' ?>; position:fixed; inset:0; z-index:9999999; background:rgba(15,23,42,.96); align-items:center; justify-content:center;">
        <div style="background: white; padding: 50px; border-radius: 15px; text-align: center; max-width: 600px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <div style="font-size: 80px; margin-bottom: 20px;">🖥️</div>
            <h2 style="color: #2c3e50; margin-bottom: 20px; font-size: 28px;">Mode Fullscreen Diperlukan</h2>
            <p style="margin-bottom: 30px; font-size: 18px; color: #7f8c8d; line-height: 1.6;">
                Untuk memulai ujian, Anda harus mengaktifkan mode fullscreen.<br>
                Klik tombol di bawah untuk melanjutkan.
            </p>
            <button id="start-fullscreen-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 20px 60px; font-size: 20px; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); transition: all 0.3s;">
                Mulai Ujian (Fullscreen)
            </button>
            <p style="margin-top: 20px; font-size: 14px; color: #95a5a6;">
                ⚠️ Jangan keluar dari fullscreen selama ujian berlangsung
            </p>
        </div>
    </div>

    <div id="violation-counter">
        🚨 Pelanggaran: <span id="violation-count">0</span>/1
    </div>

    <div class="timer" id="timer" style="display:none;">
        ⏱️ <span id="time-display">--:--</span> <small style="font-size: 0.8em; opacity: 0.8;">WIB</small>
    </div>

    <div class="exam-info" style="display:none;">
        📝 <?= htmlspecialchars($session['title'] ?? 'Ujian') ?>
    </div>

    <div class="main-container" style="display:none;">
        <?php
        // === TAMBAHAN: Bangun peta PDF per nomor soal (bukan cuma 1 PDF untuk semua soal) ===
        $questionPdfMap = [];      // [nomor_soal => url_pdf]
        $examHasPdfQuestions = false;
        if (isset($questions) && is_array($questions)) {
            $qNo = 1;
            foreach ($questions as $q) {
                if (!empty($q['pdf_file'])) {
                    $examHasPdfQuestions = true;
                    $fileName = basename($q['pdf_file']);
                    $questionPdfMap[$qNo] = (function_exists('config') ? config('app_url') : '') . '/question/viewPdf?file=' . urlencode($fileName);
                }
                $qNo++;
            }
        }

        if ($examHasPdfQuestions):
        ?>
            <!-- PDF Viewer Area (Kiri) -->
            <div class="pdf-viewer-container" id="pdf-viewer-container" <?= empty($questionPdfMap[1]) ? 'style="display:none;"' : '' ?>>
                <div class="pdf-toolbar">
                    <button onclick="pdfPrevPage()" class="pdf-nav-btn"><i class="fas fa-chevron-left"></i></button>
                    <span class="pdf-page-info">Hal. <span id="pdf-page-num">1</span> / <span id="pdf-page-count">-</span></span>
                    <button onclick="pdfNextPage()" class="pdf-nav-btn"><i class="fas fa-chevron-right"></i></button>
                    <span class="pdf-toolbar-sep"></span>
                    <button onclick="pdfZoomOut()" class="pdf-nav-btn" id="pdf-zoom-out-btn"><i class="fas fa-search-minus"></i></button>
                    <span class="pdf-page-info" id="pdf-zoom-level" style="min-width:50px;">130%</span>
                    <button onclick="pdfZoomIn()" class="pdf-nav-btn" id="pdf-zoom-in-btn"><i class="fas fa-search-plus"></i></button>
                    <span class="pdf-toolbar-sep"></span>
                    <button onclick="pdfToggleExpand()" class="pdf-nav-btn" id="pdf-expand-btn" title="Perbesar panel PDF">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                </div>
                <div id="pdf-canvas-wrapper" class="pdf-canvas-wrapper">
                    <div id="pdf-loading" style="text-align:center; padding:40px; color:#64748b;">
                        <i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i>
                        <p style="margin-top:12px;">Memuat PDF...</p>
                    </div>
                    <canvas id="pdf-canvas" style="display:none;"></canvas>
                </div>
            </div>
        <?php endif; ?>

        <!-- Exam Content Area (Tengah) -->
        <div class="exam-content<?= empty($questionPdfMap[1]) ? ' full-width' : '' ?>">
            <form id="exam-form">
                <?php if (isset($questions) && is_array($questions) && count($questions) > 0): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($questions as $q): ?>
                        <div class="question-card" id="question-<?= $no ?>" <?= $no === 1 ? 'style="display: block;"' : '' ?>>
                            <div class="question-header">
                                <h4 style="color: #2c3e50; font-weight: 600; margin-bottom: 25px;">
                                    <i class="fas fa-question-circle" style="color: #3498db;"></i>
                                    Soal <?= $no ?> dari <?= count($questions) ?>
                                </h4>
                            </div>

                            <?php if (empty($q['pdf_file']) && !empty(trim($q['question_text'] ?? ''))): ?>
                                <div class="question-text" style="margin-bottom: 20px; line-height: 1.6; font-size: 16px;">
                                    <?php
                                    if (function_exists('renderQuestionText')) {
                                        echo renderQuestionText($q['question_text']);
                                    } else {
                                        echo htmlspecialchars($q['question_text']);
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>

                            <!-- Question Image -->
                            <?php if (!empty($q['question_image'])): ?>
                                <div class="question-image" style="margin: 20px 0; text-align: center; background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #e9ecef;">
                                    <?php
                                    $imagePath = (function_exists('config') ? config('app_url') : '') . '/uploads/questions/' . $q['question_image'];
                                    ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" loading="lazy" decoding="async"
                                        alt="Gambar Soal <?= $no ?>"
                                        style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.3s ease; border: 1px solid #dee2e6;"
                                        onclick="toggleImageZoom(this)"
                                        title="Klik untuk memperbesar/mengecilkan gambar"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div style="display: none; background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; color: #721c24;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Gambar tidak dapat dimuat</strong><br>
                                        <small>Silakan hubungi pengawas ujian</small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Warning if no content -->
                            <?php if (empty(trim($q['question_text'] ?? '')) && empty($q['question_image'])): ?>
                                <div class="alert alert-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; color: #856404;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Perhatian:</strong> Konten soal tidak tersedia. Silakan hubungi pengawas ujian.
                                </div>
                            <?php endif; ?>

                            <?php if (($q['type'] ?? '') === 'tkp'): ?>
                                <div style="background:#fce7f3; border:1px solid #f9a8d4; border-radius:8px; padding:10px 14px; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                                    <i class="fas fa-info-circle" style="color:#9d174d;"></i>
                                    <span style="color:#9d174d; font-size:14px; font-weight:600;">
                                        Tes Karakteristik Kepribadian (TKP).
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (in_array(($q['type'] ?? ''), ['multiple_choice', 'tkp'])): ?>
                                <?php if (!empty($q['pdf_file'])): ?>
                                    <!-- Soal dari PDF: pilihan huruf saja, tanpa teks opsi -->
                                    <div class="options" style="display:flex; gap:12px; flex-wrap:wrap;">
                                        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $opt): ?>
                                            <label class="option-label" style="flex:0 0 auto; min-width:70px; text-align:center;"
                                                onclick="selectOption(this, <?= $q['id'] ?? 0 ?>, '<?= $opt ?>', <?= $no ?>)">
                                                <input type="radio"
                                                    name="answer_<?= $q['id'] ?? 0 ?>"
                                                    value="<?= $opt ?>"
                                                    <?= (isset($answers[$q['id']]) && $answers[$q['id']] === $opt) ? 'checked' : '' ?>
                                                    onchange="selectOption(this.parentNode, <?= $q['id'] ?? 0 ?>, '<?= $opt ?>', <?= $no ?>)">
                                                <span style="font-weight:700;"><?= $opt ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="options">
                                        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $opt): ?>
                                            <?php
                                            $optKey = 'option_' . strtolower($opt);
                                            if (isset($q[$optKey]) && $q[$optKey] !== '' && $q[$optKey] !== null):
                                            ?>
                                                <label class="option-label" onclick="selectOption(this, <?= $q['id'] ?? 0 ?>, '<?= $opt ?>', <?= $no ?>)">
                                                    <input type="radio"
                                                        name="answer_<?= $q['id'] ?? 0 ?>"
                                                        value="<?= $opt ?>"
                                                        <?= (isset($answers[$q['id']]) && $answers[$q['id']] === $opt) ? 'checked' : '' ?>
                                                        onchange="selectOption(this.parentNode, <?= $q['id'] ?? 0 ?>, '<?= $opt ?>', <?= $no ?>)">
                                                    <?php
                                                    $optText = $q[$optKey] ?? '';
                                                    $optDecoded = html_entity_decode($optText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                                    $isHtml  = $optDecoded !== strip_tags($optDecoded);
                                                    $hasMath = preg_match('/\$|\\\\frac|\\\\sqrt|\\\\times|[²³¹⁰⁴⁵⁶⁷⁸⁹₀₁₂₃₄₅₆₇₈₉]/u', $optDecoded);
                                                    $spanClass = ($hasMath || $isHtml) ? 'math-content' : 'no-math';
                                                    echo '<span class="' . $spanClass . '">' . $opt . '. ';
                                                    if ($isHtml) {
                                                        // Teks mengandung HTML tag — echo langsung (sudah di-decode)
                                                        echo $optDecoded;
                                                    } elseif ($hasMath && function_exists('autoConvertMath')) {
                                                        echo autoConvertMath($optDecoded);
                                                    } else {
                                                        // Teks biasa — escape HTML untuk keamanan
                                                        echo htmlspecialchars($optDecoded, ENT_QUOTES, 'UTF-8');
                                                    }
                                                    echo '</span>';
                                                    ?>
                                                </label>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php elseif (($q['type'] ?? '') === 'essay'): ?>
                                <div class="essay-answer" style="margin-top:16px">
                                    <label style="font-weight:600;color:#374151;margin-bottom:8px;display:block">Jawaban Anda:</label>
                                    <textarea
                                        id="essay_<?= $q['id'] ?? 0 ?>"
                                        name="essay_<?= $q['id'] ?? 0 ?>"
                                        rows="6"
                                        placeholder="Tulis jawaban Anda di sini..."
                                        style="width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;box-sizing:border-box;transition:border-color 0.2s"
                                        onfocus="this.style.borderColor='#6366f1'"
                                        onblur="this.style.borderColor='#e2e8f0'"
                                        oninput="markEssayAnswered(<?= $q['id'] ?? 0 ?>, this.value, <?= $no ?>)"><?= htmlspecialchars($answers[$q['id']] ?? '') ?></textarea>
                                    <small style="color:#64748b">Jawaban akan disimpan saat Anda klik Kumpulkan Ujian.</small>
                                </div>
                            <?php elseif (($q['type'] ?? '') === 'short_answer'): ?>
                                <div class="short-answer" style="margin-top:16px">
                                    <label style="font-weight:600;color:#374151;margin-bottom:8px;display:block">
                                        <i class="fas fa-pencil-alt" style="color:#6366f1"></i> Jawaban Anda:
                                    </label>
                                    <input
                                        type="number"
                                        id="short_<?= $q['id'] ?? 0 ?>"
                                        name="short_<?= $q['id'] ?? 0 ?>"
                                        step="any"
                                        inputmode="decimal"
                                        placeholder="Contoh: 42 atau 3.14"
                                        value="<?= htmlspecialchars($answers[$q['id']] ?? '') ?>"
                                        style="width:100%;max-width:300px;padding:12px 16px;border:2px solid #e2e8f0;border-radius:8px;font-size:18px;font-weight:600;color:#1e293b;transition:border-color 0.2s;box-sizing:border-box"
                                        onfocus="this.style.borderColor='#6366f1'"
                                        onblur="this.style.borderColor='#e2e8f0'"
                                        oninput="saveShortAnswer(<?= $q['id'] ?? 0 ?>, this.value, <?= $no ?>)">
                                    <div style="margin-top:8px;color:#64748b;font-size:13px">
                                        <i class="fas fa-info-circle"></i> Ketik angka, jawaban tersimpan otomatis
                                    </div>
                                </div>
                            <?php elseif (($q['type'] ?? '') === 'short_answer_text'): ?>
                                <div class="short-answer-text" style="margin-top:16px">
                                    <label style="font-weight:600;color:#374151;margin-bottom:8px;display:block">
                                        <i class="fas fa-pencil-alt" style="color:#6366f1"></i> Jawaban Anda:
                                    </label>
                                    <input
                                        type="text"
                                        id="short_text_<?= $q['id'] ?? 0 ?>"
                                        name="short_text_<?= $q['id'] ?? 0 ?>"
                                        placeholder="Ketik jawaban Anda..."
                                        value="<?= htmlspecialchars($answers[$q['id']] ?? '') ?>"
                                        style="width:100%;max-width:500px;padding:12px 16px;border:2px solid #e2e8f0;border-radius:8px;font-size:16px;color:#1e293b;transition:border-color 0.2s;box-sizing:border-box"
                                        onfocus="this.style.borderColor='#6366f1'"
                                        onblur="this.style.borderColor='#e2e8f0'; saveShortAnswerText(<?= $q['id'] ?? 0 ?>, this.value, <?= $no ?>)"
                                        oninput="saveShortAnswerText(<?= $q['id'] ?? 0 ?>, this.value, <?= $no ?>)">
                                    <div style="margin-top:8px;color:#64748b;font-size:13px">
                                        <i class="fas fa-info-circle"></i> Ketik jawaban kalimat, tersimpan otomatis
                                    </div>
                                </div>
                            <?php elseif (($q['type'] ?? '') === 'listening'): ?>
                                <?php if (!empty($q['audio_file'])): ?>
                                    <div class="listening-section" style="margin-bottom: 20px; background: #f0f7ff; border: 2px solid #3b82f6; border-radius: 12px; padding: 20px;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                            <i class="fas fa-headphones" style="font-size: 24px; color: #3b82f6;"></i>
                                            <div>
                                                <strong style="color: #1e40af; font-size: 16px;">Soal Listening</strong><br>
                                                <small style="color: #64748b;" id="play-info-<?= $q['id'] ?? 0 ?>">
                                                    Sisa pemutaran: <strong id="play-remaining-<?= $q['id'] ?? 0 ?>">2</strong>x
                                                </small>
                                            </div>
                                        </div>

                                        <audio id="audio-<?= $q['id'] ?? 0 ?>"
                                            src="<?= (function_exists('config') ? config('app_url') : '') ?>/uploads/audio/<?= htmlspecialchars($q['audio_file']) ?>"
                                            preload="auto"
                                            style="display:none;"
                                            onended="onAudioEnded(<?= $q['id'] ?? 0 ?>)">
                                        </audio>

                                        <button id="play-btn-<?= $q['id'] ?? 0 ?>"
                                            onclick="playAudio(<?= $q['id'] ?? 0 ?>, <?= (int)($q['max_plays'] ?? 2) ?>)"
                                            style="background: #3b82f6; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                                            <i class="fas fa-play" id="play-icon-<?= $q['id'] ?? 0 ?>"></i> Putar Audio
                                        </button>

                                        <div id="play-progress-<?= $q['id'] ?? 0 ?>" style="display:none; margin-top: 12px;">
                                            <div style="background: #dbeafe; border-radius: 20px; height: 8px; overflow: hidden;">
                                                <div id="progress-bar-<?= $q['id'] ?? 0 ?>"
                                                    style="background: #3b82f6; height: 100%; width: 0%; transition: width 0.5s;"></div>
                                            </div>
                                            <small style="color: #64748b; margin-top: 4px; display: block;">
                                                <span id="current-time-<?= $q['id'] ?? 0 ?>">0:00</span> /
                                                <span id="duration-<?= $q['id'] ?? 0 ?>">--:--</span>
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Pilihan Jawaban (sama seperti multiple_choice) -->
                                <div class="options">
                                    <?php foreach (['A', 'B', 'C', 'D', 'E'] as $opt): ?>
                                        <?php
                                        $optKey = 'option_' . strtolower($opt);
                                        if (isset($q[$optKey]) && $q[$optKey] !== '' && $q[$optKey] !== null):
                                        ?>
                                            <label class="option-label" onclick="selectOption(this, <?= $q['id'] ?? 0 ?>, '<?= $opt ?>', <?= $no ?>)">
                                                <input type="radio"
                                                    name="answer_<?= $q['id'] ?? 0 ?>"
                                                    value="<?= $opt ?>"
                                                    <?= (isset($answers[$q['id']]) && $answers[$q['id']] === $opt) ? 'checked' : '' ?>
                                                    onchange="selectOption(this.parentNode, <?= $q['id'] ?? 0 ?>, '<?= $opt ?>', <?= $no ?>)">
                                                <?php
                                                $optText = $q[$optKey] ?? '';
                                                $optDecoded = html_entity_decode($optText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                                $isHtml  = $optDecoded !== strip_tags($optDecoded);
                                                $hasMath = preg_match('/\$|\\\\frac|\\\\sqrt|\\\\times|[²³¹⁰⁴⁵⁶⁷⁸⁹₀₁₂₃₄₅₆₇₈₉]/u', $optDecoded);
                                                $spanClass = ($hasMath || $isHtml) ? 'math-content' : 'no-math';
                                                echo '<span class="' . $spanClass . '">' . $opt . '. ';
                                                if ($isHtml) {
                                                    // Teks mengandung HTML tag — echo langsung (sudah di-decode)
                                                    echo $optDecoded;
                                                } elseif ($hasMath && function_exists('autoConvertMath')) {
                                                    echo autoConvertMath($optDecoded);
                                                } else {
                                                    // Teks biasa — escape HTML untuk keamanan
                                                    echo htmlspecialchars($optDecoded, ENT_QUOTES, 'UTF-8');
                                                }
                                                echo '</span>';
                                                ?>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php $no++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h4><i class="fas fa-exclamation-circle"></i> Tidak Ada Soal</h4>
                        <p>Maaf, tidak ada soal yang tersedia untuk ujian ini. Silakan hubungi administrator atau pengawas ujian.</p>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Navigation Panel (Kanan) -->
        <div class="navigation-panel">
            <div class="progress-info">
                <h6>📊 Progress Ujian</h6>
                <div class="progress-stats">
                    <div class="stat-item">
                        <span class="stat-number" id="answered-count" style="color: #28a745;">0</span>
                        <small>Dijawab</small>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="doubt-count" style="color: #ffc107;">0</span>
                        <small>Ragu</small>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="unanswered-count" style="color: #dc3545;"><?= count($questions ?? []) ?></span>
                        <small>Belum</small>
                    </div>
                </div>
            </div>

            <div class="legend">
                <h6>🎯 Keterangan Status</h6>
                <div class="legend-item">
                    <div class="legend-color" style="background: #28a745;"></div>
                    <span>Sudah dijawab</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ffc107;"></div>
                    <span>Masih ragu-ragu</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #dc3545;"></div>
                    <span>Belum dijawab</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: white; border: 3px solid #007bff;"></div>
                    <span>Soal aktif</span>
                </div>
            </div>

            <h6> Navigasi Soal</h6>
            <div class="navigation-grid">
                <?php for ($i = 1; $i <= (count($questions ?? [])); $i++): ?>
                    <div class="nav-number unanswered <?= $i === 1 ? 'current' : '' ?>"
                        id="nav-<?= $i ?>"
                        onclick="goToQuestion(<?= $i ?>)"
                        title="Soal <?= $i ?>">
                        <?= $i ?>
                    </div>
                <?php endfor; ?>
            </div>

            <button class="doubt-btn" id="doubt-btn" onclick="toggleDoubt()">
                <i class="fas fa-question-circle"></i> Tandai Ragu-ragu
            </button>

            <div class="navigation-controls">
                <button class="nav-btn prev-btn" id="prev-btn" onclick="previousQuestion()" disabled>
                    <i class="fas fa-chevron-left"></i> Sebelumnya
                </button>
                <button class="nav-btn next-btn" id="next-btn" onclick="nextQuestion()">
                    Selanjutnya <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <button class="btn btn-success submit-btn" onclick="submitExam()" style="display:none;">
        <i class="fas fa-paper-plane"></i> Kumpulkan Ujian
    </button>

    <script>
        // Safe variable initialization
        const sessionId = <?= isset($session['id']) ? (int)$session['id'] : 0 ?>;
        let remainingTime = <?= isset($session['remaining_time']) ? max(1, (int)$session['remaining_time']) : 0 ?>;
        let currentQuestion = 1;
        const totalQuestions = <?= count($questions ?? []) ?>;
        let questionStatus = {};
        let questionAnswers = {};

        const questionIdMap = {
            <?php if (isset($questions) && is_array($questions)): ?>
                <?php $no = 1;
                foreach ($questions as $q): ?>
                    <?= $no ?>: <?= $q['id'] ?? 0 ?>,
                <?php $no++;
                endforeach; ?>
            <?php endif; ?>
        };
        console.log('Question ID Map:', questionIdMap);

        // === TAMBAHAN: peta PDF per nomor soal (nomor_soal -> url PDF) ===
        const questionPdfMap = {
            <?php foreach ($questionPdfMap as $qNo => $url): ?>
                <?= $qNo ?>: <?= json_encode($url) ?>,
            <?php endforeach; ?>
        };
        const examHasPdfQuestions = <?= $examHasPdfQuestions ? 'true' : 'false' ?>;
        console.log('Question PDF Map:', questionPdfMap);

        let violationCount = 0;
        const maxViolations = 1;
        let isFullscreen = false;
        let isSubmitting = false;

        for (let i = 1; i <= totalQuestions; i++) {
            questionStatus[i] = 'unanswered';
        }

        <?php if (!empty($answers)): ?>
            <?php
            if (isset($questions) && is_array($questions)) {
                $questionIds = array_column($questions, 'id');
                foreach ($answers as $qId => $answer):
                    $questionIndex = array_search($qId, $questionIds);
                    if ($questionIndex !== false):
                        $questionNum = $questionIndex + 1;
            ?>
                        questionAnswers[<?= $questionNum ?>] = '<?= $answer ?>';
                        questionStatus[<?= $questionNum ?>] = 'answered';
            <?php
                    endif;
                endforeach;
            }
            ?>

            setTimeout(function() {
                <?php if (isset($questions) && is_array($questions)): ?>
                    <?php
                    $questionIds = array_column($questions, 'id');
                    foreach ($answers as $qId => $answer):
                        $questionIndex = array_search($qId, $questionIds);
                        if ($questionIndex !== false):
                    ?>
                            const radio<?= $qId ?> = document.querySelector(`input[name="answer_<?= $qId ?>"][value="<?= $answer ?>"]`);
                            if (radio<?= $qId ?>) {
                                radio<?= $qId ?>.checked = true;
                                radio<?= $qId ?>.closest('.option-label').classList.add('selected');
                            }
                    <?php
                        endif;
                    endforeach;
                    ?>
                    <?php foreach ($questions as $idx => $q): ?>
                        <?php if (($q['type'] ?? '') === 'short_answer' && isset($answers[$q['id']])): ?>
                            const shortInput<?= $q['id'] ?> = document.getElementById('short_<?= $q['id'] ?>');
                            if (shortInput<?= $q['id'] ?>) {
                                shortInput<?= $q['id'] ?>.value = '<?= htmlspecialchars($answers[$q['id']]) ?>';
                            }
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                updateNavigationDisplay();
            }, 100);
        <?php endif; ?>

        document.addEventListener('copy', function(e) {
            e.preventDefault();
        });
        document.addEventListener('cut', function(e) {
            e.preventDefault();
        });
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && ['c', 'x', 'a', 'p', 'u', 's'].includes(e.key.toLowerCase())) {
                e.preventDefault();
            }
            if (e.key === 'F12' || e.key === 'PrintScreen') {
                e.preventDefault();
            }
        });

        const style = document.createElement('style');
        style.textContent = '.question-text, .option-label span { user-select: none; -webkit-user-select: none; }';
        document.head.appendChild(style);

        let antiCheatReady = false;

        function isInFullscreen() {
            return !!(document.fullscreenElement || document.webkitFullscreenElement ||
                document.mozFullScreenElement || document.msFullscreenElement);
        }

        function fullscreenSupported() {
            const el = document.documentElement;
            return !!(el.requestFullscreen || el.webkitRequestFullscreen ||
                el.mozRequestFullScreen || el.msRequestFullscreen);
        }

        function requestFullscreen() {
            const el = document.documentElement;
            let p;
            try {
                if (el.requestFullscreen) p = el.requestFullscreen();
                else if (el.webkitRequestFullscreen) p = el.webkitRequestFullscreen();
                else if (el.mozRequestFullScreen) p = el.mozRequestFullScreen();
                else if (el.msRequestFullscreen) p = el.msRequestFullscreen();
            } catch (e) {}
            return Promise.resolve(p).then(lockEscapeKey).catch(function() {});
        }

        function lockEscapeKey() {
            try {
                if (navigator.keyboard && navigator.keyboard.lock) navigator.keyboard.lock(['Escape']);
            } catch (e) {}
        }

        function exitFullscreenSafe() {
            try {
                if (navigator.keyboard && navigator.keyboard.unlock) navigator.keyboard.unlock();
            } catch (e) {}
            if (!isInFullscreen()) return Promise.resolve();
            const fn = document.exitFullscreen || document.webkitExitFullscreen ||
                document.mozCancelFullScreen || document.msExitFullscreen;
            return fn ? Promise.resolve(fn.call(document)).catch(function() {}) : Promise.resolve();
        }

        function showFullscreenGate() {
            const g = document.getElementById('fullscreen-prompt');
            if (g) g.style.display = 'flex';
        }

        function hideFullscreenGate() {
            const g = document.getElementById('fullscreen-prompt');
            if (g) g.style.display = 'none';
        }

        // Coba fullscreen langsung saat masuk ujian. Jika browser menolak (butuh klik),
        function ensureFullscreenOnEntry() {
            if (!fullscreenSupported()) return; // mis. iPhone: jangan mengunci peserta
            requestFullscreen();
            setTimeout(function() {
                if (!isInFullscreen() && !isSubmitting) showFullscreenGate();
            }, 700);
        }

        // Lepas fullscreen HANYA setelah ujian selesai, baru pindah ke halaman hasil.
        function finishExam(url) {
            window.onpopstate = null;
            exitFullscreenSafe().then(function() {
                window.location.replace(url);
            });
        }

        // Notifikasi kecil di dalam halaman (pengganti alert() bawaan browser).
        function showFsToast(msg) {
            let t = document.getElementById('fs-toast');
            if (!t) {
                t = document.createElement('div');
                t.id = 'fs-toast';
                t.style.cssText = 'position:fixed;top:70px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:12px 22px;border-radius:10px;font-weight:600;z-index:99999999;box-shadow:0 8px 30px rgba(0,0,0,.35);';
                document.body.appendChild(t);
            }
            t.textContent = msg;
            t.style.display = 'block';
            clearTimeout(t._h);
            t._h = setTimeout(function() {
                t.style.display = 'none';
            }, 3000);
        }

        const BACKUP_KEY = 'examBackup_' + sessionId;

        function loadBackup() {
            try {
                return JSON.parse(localStorage.getItem(BACKUP_KEY) || '{}');
            } catch (e) {
                return {};
            }
        }

        function writeBackup(questionId, answer, synced) {
            const backup = loadBackup();
            backup[questionId] = {
                answer,
                synced,
                ts: Date.now()
            };
            try {
                localStorage.setItem(BACKUP_KEY, JSON.stringify(backup));
            } catch (e) {
                console.warn('LocalStorage gagal:', e);
            }
        }

        function markSynced(questionId) {
            const backup = loadBackup();
            if (backup[questionId]) {
                backup[questionId].synced = true;
                localStorage.setItem(BACKUP_KEY, JSON.stringify(backup));
            }
            updateSyncIndicator();
        }

        function clearBackup() {
            localStorage.removeItem(BACKUP_KEY);
        }

        function updateSyncIndicator() {
            const backup = loadBackup();
            const pending = Object.values(backup).filter(a => !a.synced).length;
            let el = document.getElementById('sync-indicator');
            if (!el) {
                el = document.createElement('div');
                el.id = 'sync-indicator';
                el.style.cssText = 'position:fixed; bottom:15px; left:15px; padding:8px 14px; border-radius:8px; font-size:13px; font-weight:600; z-index:9998;';
                document.body.appendChild(el);
            }
            if (pending > 0) {
                el.style.cssText += 'background:#fef3c7; color:#92400e; display:block;';
                el.innerHTML = '<i class="fas fa-sync fa-spin"></i> ' + pending + ' jawaban belum tersinkron...';
            } else {
                el.style.cssText += 'background:#d1fae5; color:#065f46; display:block;';
                el.innerHTML = '<i class="fas fa-check-circle"></i> Semua jawaban tersimpan';
                setTimeout(() => {
                    if (el) el.style.display = 'none';
                }, 2000);
            }
        }

        // Fungsi utama pengganti — dipakai semua tipe soal
        function saveAnswerResilient(questionId, answer) {
            writeBackup(questionId, answer, false);
            updateSyncIndicator();
            attemptSync(questionId, answer, 0);
        }

        function attemptSync(questionId, answer, retryCount) {
            const baseUrl = '<?= function_exists('config') ? config('app_url') : '' ?>';
            const maxRetries = 5;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch(baseUrl + '/exam/saveAnswer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'session_id=' + sessionId + '&question_id=' + questionId + '&answer=' + encodeURIComponent(answer),
                    signal: controller.signal
                })
                .then(r => {
                    clearTimeout(timeoutId);
                    return r.json();
                })
                .then(data => data.success ? markSynced(questionId) : scheduleRetry(questionId, answer, retryCount))
                .catch(() => {
                    clearTimeout(timeoutId);
                    scheduleRetry(questionId, answer, retryCount);
                });
        }

        function scheduleRetry(questionId, answer, retryCount) {
            if (retryCount >= 5) {
                updateSyncIndicator();
                return;
            } // nanti dicoba lagi oleh interval/online listener
            const delay = Math.min(1000 * Math.pow(2, retryCount), 15000);
            setTimeout(() => attemptSync(questionId, answer, retryCount + 1), delay);
        }

        function flushAllPending() {
            const backup = loadBackup();
            Object.keys(backup).forEach(qId => {
                if (!backup[qId].synced) attemptSync(qId, backup[qId].answer, 0);
            });
        }

        window.addEventListener('online', function() {
            showNotification('Koneksi kembali, menyinkronkan jawaban...', 'info');
            flushAllPending();
        });

        setInterval(flushAllPending, 10000); // safety net tiap 10 detik

        // Restore jawaban dari backup lokal ke form (jaga-jaga kalau halaman reload)
        document.addEventListener('DOMContentLoaded', function() {
            const backup = loadBackup();
            Object.keys(backup).forEach(qId => {
                const val = backup[qId].answer;
                const radio = document.querySelector(`input[name="answer_${qId}"][value="${val}"]`);
                if (radio) {
                    radio.checked = true;
                    radio.closest('.option-label')?.classList.add('selected');
                }
                const essay = document.getElementById('essay_' + qId);
                if (essay && !essay.value) essay.value = val;
                const shortNum = document.getElementById('short_' + qId);
                if (shortNum && !shortNum.value) shortNum.value = val;
                const shortText = document.getElementById('short_text_' + qId);
                if (shortText && !shortText.value) shortText.value = val;
            });
            updateSyncIndicator();
            if (Object.keys(backup).length > 0) flushAllPending();
        });

        // Last resort saat tab ditutup mendadak
        window.addEventListener('beforeunload', function() {
            const backup = loadBackup();
            const pending = Object.entries(backup).filter(([_, v]) => !v.synced);
            if (pending.length > 0 && navigator.sendBeacon) {
                const baseUrl = '<?= function_exists('config') ? config('app_url') : '' ?>';
                pending.forEach(([qId, v]) => {
                    const data = new Blob(
                        ['session_id=' + sessionId + '&question_id=' + qId + '&answer=' + encodeURIComponent(v.answer)], {
                            type: 'application/x-www-form-urlencoded'
                        }
                    );
                    navigator.sendBeacon(baseUrl + '/exam/saveAnswer', data);
                });
            }
        });

        // [FIX-FS] Masuk ujian langsung coba fullscreen; bila ditolak browser -> layar penghalang
        document.addEventListener('DOMContentLoaded', function() {
            antiCheatReady = false;
            if (isInFullscreen()) {
                unlockExam();
                hideFullscreenGate();
            }
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        document.addEventListener('visibilitychange', function() {
            if (!antiCheatReady || isSubmitting) return;
            if (document.hidden) {
                recordViolation('Berpindah tab/aplikasi');
            }
        });

        window.addEventListener('blur', function() {
            if (!antiCheatReady || isSubmitting) return;
            recordViolation('Keluar window/fokus');
        });

        function markEssayAnswered(questionId, value, questionNum) {
            questionAnswers[questionNum] = value;
            questionStatus[questionNum] = value.trim() ? 'answered' : 'unanswered';
            updateNavigationDisplay();
        }

        async function flushEssayAnswers() {
            const textareas = document.querySelectorAll('textarea[id^="essay_"]');
            const promises = [];
            textareas.forEach(function(ta) {
                const qId = ta.id.replace('essay_', '');
                const val = ta.value;
                promises.push(
                    fetch('<?= function_exists('config') ? config('app_url') : '' ?>/exam/saveAnswer', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'session_id=' + sessionId + '&question_id=' + qId + '&answer=' + encodeURIComponent(val)
                    }).catch(() => {})
                );
            });

            document.querySelectorAll('input[id^="short_"]').forEach(function(input) {
                if (input.id.startsWith('short_text_')) return;
                const qId = input.id.replace('short_', '');
                const val = input.value.trim();
                if (val !== '' && !isNaN(parseFloat(val))) {
                    promises.push(
                        fetch('<?= function_exists('config') ? config('app_url') : '' ?>/exam/saveAnswer', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'session_id=' + sessionId + '&question_id=' + qId + '&answer=' + encodeURIComponent(val)
                        }).catch(() => {})
                    );
                }
            });

            document.querySelectorAll('input[id^="short_text_"]').forEach(function(input) {
                const qId = input.id.replace('short_text_', '');
                const val = input.value.trim();
                if (val !== '') {
                    promises.push(
                        fetch('<?= function_exists('config') ? config('app_url') : '' ?>/exam/saveAnswer', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'session_id=' + sessionId + '&question_id=' + qId + '&answer=' + encodeURIComponent(val)
                        }).catch(() => {})
                    );
                }
            });

            await Promise.all(promises);
        }

        let essayTimers = {};

        function saveEssayAnswer(questionId, value, questionNum) {
            questionAnswers[questionNum] = value;
            questionStatus[questionNum] = value.trim() ? 'answered' : 'unanswered';
            updateNavigationDisplay();

            clearTimeout(essayTimers[questionId]);
            essayTimers[questionId] = setTimeout(function() {
                saveAnswerResilient(questionId, value);
            }, 1000);
        }

        let shortAnswerTimers = {};

        function saveShortAnswer(questionId, value, questionNum) {
            const trimmed = value.toString().trim();
            const numValue = trimmed !== '' ? parseFloat(trimmed) : '';

            questionAnswers[questionNum] = numValue;
            questionStatus[questionNum] = trimmed !== '' && !isNaN(numValue) ? 'answered' : 'unanswered';
            updateNavigationDisplay();

            clearTimeout(shortAnswerTimers[questionId]);
            shortAnswerTimers[questionId] = setTimeout(function() {
                if (trimmed === '' || isNaN(numValue)) return;
                saveAnswerResilient(questionId, numValue);
            }, 800);
        }

        let shortAnswerTextTimers = {};

        function saveShortAnswerText(questionId, value, questionNum) {
            questionAnswers[questionNum] = value;
            questionStatus[questionNum] = value.trim() ? 'answered' : 'unanswered';
            updateNavigationDisplay();

            clearTimeout(shortAnswerTextTimers[questionId]);
            shortAnswerTextTimers[questionId] = setTimeout(function() {
                if (value.trim() === '') return;
                saveAnswerResilient(questionId, value.trim());
            }, 800);
        }

        let timerInterval;
        let syncTimerInterval;
        let lastSavedTime = remainingTime;

        function updateTimer() {
            if (remainingTime <= 0) {
                clearInterval(timerInterval);
                timerExpired();
                return;
            }

            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            document.getElementById('time-display').textContent =
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            remainingTime--;

            if (lastSavedTime - remainingTime >= 30) {
                saveRemainingTime();
                lastSavedTime = remainingTime;
            }
        }

        function saveRemainingTime() {
            fetch('<?= function_exists('config') ? config('app_url') : '' ?>/exam/saveTime', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    remaining_time: remainingTime
                })
            }).catch(err => {
                console.error('Failed to save time:', err);
            });
        }

        function syncTimerFromServer() {
            const baseUrl = '<?= function_exists('config') ? config('app_url') : '' ?>';
            fetch(`${baseUrl}/exam/getRemainingTime/${sessionId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.remaining_time !== undefined) {
                        const serverTime = parseInt(data.remaining_time, 10);
                        if (!isNaN(serverTime) && serverTime >= 0) {
                            remainingTime = serverTime;
                            lastSavedTime = remainingTime;
                            document.getElementById('time-display').textContent =
                                String(Math.floor(remainingTime / 60)).padStart(2, '0') + ':' +
                                String(remainingTime % 60).padStart(2, '0');
                        }
                    }
                })
                .catch(() => {});
        }

        window.addEventListener('beforeunload', function(e) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?= function_exists('config') ? config('app_url') : '' ?>/exam/saveTime', false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify({
                session_id: sessionId,
                remaining_time: remainingTime
            }));
        });

        function goToQuestion(questionNum) {
            if (questionNum < 1 || questionNum > totalQuestions) return;

            const prevEl = document.getElementById(`question-${currentQuestion}`);
            if (prevEl) {
                prevEl.classList.remove('active');
                prevEl.style.display = 'none';
            }
            document.getElementById(`nav-${currentQuestion}`).classList.remove('current');

            currentQuestion = questionNum;
            const currEl = document.getElementById(`question-${currentQuestion}`);
            if (currEl) {
                currEl.style.display = 'block';
                currEl.classList.add('active');
            }
            document.getElementById(`nav-${currentQuestion}`).classList.add('current');

            document.getElementById('prev-btn').disabled = (currentQuestion === 1);
            document.getElementById('next-btn').disabled = (currentQuestion === totalQuestions);

            updateDoubtButton();

            if (typeof showPdfForQuestion === 'function') {
                showPdfForQuestion(currentQuestion);
            }

            setTimeout(function() {
                if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                    MathJax.typesetClear([currEl]);
                    MathJax.typesetPromise([currEl]).catch(err => console.log(err));
                }
            }, 150);
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions) {
                goToQuestion(currentQuestion + 1);
            }
        }

        function previousQuestion() {
            if (currentQuestion > 1) {
                goToQuestion(currentQuestion - 1);
            }
        }

        function selectOption(label, questionId, answer, questionNum) {
            const allLabels = label.parentNode.querySelectorAll('.option-label');
            allLabels.forEach(l => l.classList.remove('selected'));
            label.classList.add('selected');

            const radio = label.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }

            questionAnswers[questionNum] = answer;
            questionStatus[questionNum] = 'answered';

            updateNavigationDisplay();
            saveAnswerResilient(questionId, answer);
        }

        function toggleDoubt() {
            const currentStatus = questionStatus[currentQuestion];
            if (currentStatus === 'doubt') {
                questionStatus[currentQuestion] = questionAnswers[currentQuestion] ? 'answered' : 'unanswered';
            } else {
                questionStatus[currentQuestion] = 'doubt';
            }
            updateNavigationDisplay();
            updateDoubtButton();
        }

        function updateDoubtButton() {
            const btn = document.getElementById('doubt-btn');
            if (questionStatus[currentQuestion] === 'doubt') {
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Hapus Tanda Ragu';
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-question-circle"></i> Tandai Ragu-ragu';
            }
        }

        function updateNavigationDisplay() {
            let answeredCount = 0,
                doubtCount = 0,
                unansweredCount = 0;

            for (let i = 1; i <= totalQuestions; i++) {
                const navElement = document.getElementById(`nav-${i}`);
                const status = questionStatus[i];

                navElement.classList.remove('answered', 'doubt', 'unanswered');
                navElement.classList.add(status);

                if (status === 'answered') answeredCount++;
                else if (status === 'doubt') doubtCount++;
                else unansweredCount++;
            }

            document.getElementById('answered-count').textContent = answeredCount;
            document.getElementById('doubt-count').textContent = doubtCount;
            document.getElementById('unanswered-count').textContent = unansweredCount;
        }

        function saveAnswer(questionId, answer, retryCount = 0) {
            const baseUrl = '<?= function_exists('config') ? config('app_url') : '' ?>';
            const maxRetries = 3;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch(baseUrl + '/exam/saveAnswer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `session_id=${sessionId}&question_id=${questionId}&answer=${answer}`,
                    signal: controller.signal
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        if (retryCount < maxRetries) {
                            setTimeout(() => saveAnswer(questionId, answer, retryCount + 1), 1000);
                        } else {
                            showNotification('Gagal menyimpan jawaban: ' + data.message, 'error');
                        }
                    }
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    if (retryCount < maxRetries) {
                        setTimeout(() => saveAnswer(questionId, answer, retryCount + 1), 1000);
                    } else {
                        showNotification('Koneksi bermasalah, jawaban mungkin tidak tersimpan.', 'warning');
                    }
                });
        }

        function showNotification(message, type = 'info') {
            let notification = document.getElementById('notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'notification';
                notification.style.cssText = `position: fixed; top: 20px; right: 20px; padding: 10px 20px; border-radius: 5px; color: white; font-weight: bold; z-index: 9999; display: none;`;
                document.body.appendChild(notification);
            }
            const colors = {
                'success': '#28a745',
                'error': '#dc3545',
                'warning': '#ffc107',
                'info': '#17a2b8'
            };
            notification.style.backgroundColor = colors[type] || colors.info;
            notification.textContent = message;
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        const audioPlayCount = {};

        function playAudio(questionId, maxPlays) {
            const audio = document.getElementById('audio-' + questionId);
            const btn = document.getElementById('play-btn-' + questionId);
            const remaining = document.getElementById('play-remaining-' + questionId);
            const progress = document.getElementById('play-progress-' + questionId);

            if (!audioPlayCount[questionId]) audioPlayCount[questionId] = 0;

            if (audioPlayCount[questionId] >= maxPlays) {
                btn.disabled = true;
                btn.style.background = '#94a3b8';
                btn.style.cursor = 'not-allowed';
                btn.innerHTML = '<i class="fas fa-ban"></i> Batas putar tercapai';
                return;
            }

            audioPlayCount[questionId]++;
            const sisa = maxPlays - audioPlayCount[questionId];
            remaining.textContent = sisa;

            progress.style.display = 'block';
            btn.disabled = true;
            btn.style.background = '#1d4ed8';
            btn.style.cursor = 'not-allowed';
            btn.innerHTML = '<i class="fas fa-volume-up"></i> Sedang diputar...';

            audio.currentTime = 0;
            audio.play().catch(() => {
                showNotification('Gagal memutar audio. Coba lagi.', 'error');
                audioPlayCount[questionId]--;
                remaining.textContent = maxPlays - audioPlayCount[questionId];
                btn.disabled = false;
                btn.style.background = '#3b82f6';
                btn.style.cursor = 'pointer';
                btn.innerHTML = '<i class="fas fa-play"></i> Putar Audio';
            });

            audio.ontimeupdate = function() {
                const pct = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
                document.getElementById('progress-bar-' + questionId).style.width = pct + '%';
                document.getElementById('current-time-' + questionId).textContent = formatTime(audio.currentTime);
                if (audio.duration) {
                    document.getElementById('duration-' + questionId).textContent = formatTime(audio.duration);
                }
            };
        }

        function onAudioEnded(questionId) {
            const btn = document.getElementById('play-btn-' + questionId);
            const remaining = parseInt(document.getElementById('play-remaining-' + questionId).textContent);

            if (remaining <= 0) {
                btn.disabled = true;
                btn.style.background = '#94a3b8';
                btn.style.cursor = 'not-allowed';
                btn.innerHTML = '<i class="fas fa-ban"></i> Batas putar tercapai';
            } else {
                btn.disabled = false;
                btn.style.background = '#3b82f6';
                btn.style.cursor = 'pointer';
                btn.innerHTML = '<i class="fas fa-play"></i> Putar Audio';
            }
        }

        function formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return m + ':' + String(s).padStart(2, '0');
        }

        function submitExam(autoSubmit = false) {
            if (isSubmitting) return;
            // [FIX-FS] pastikan fullscreen tetap aktif saat popup konfirmasi muncul (klik = user gesture)
            if (!autoSubmit && !isInFullscreen()) requestFullscreen();
            const unansweredCount = Object.values(questionStatus).filter(status => status === 'unanswered').length;
            if (!autoSubmit) {
                showSubmitModal(unansweredCount);
                return;
            }
            doSubmit();
        }

        function showSubmitModal(unansweredCount) {
            let modal = document.getElementById('submit-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'submit-modal';
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                modal.setAttribute('aria-labelledby', 'submit-modal-title');
                modal.style.cssText = `position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 99999; display: flex; align-items: center; justify-content: center;`;
                modal.innerHTML = `
                    <div style="background: white; border-radius: 16px; padding: 40px; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                        <div style="font-size: 60px; margin-bottom: 16px;">📋</div>
                        <h3 id="submit-modal-title" style="color: #1e293b; margin-bottom: 12px; font-size: 22px;">Kumpulkan Ujian?</h3>
                        <p id="submit-modal-msg" style="color: #64748b; margin-bottom: 28px; line-height: 1.6; font-size: 15px;"></p>
                        <div style="display: flex; gap: 12px; justify-content: center;">
                            <button onclick="document.getElementById('submit-modal').style.display='none'" style="padding: 12px 28px; border: 2px solid #e2e8f0; border-radius: 8px; background: white; color: #64748b; font-size: 15px; font-weight: 600; cursor: pointer;">Kembali</button>
                            <button onclick="document.getElementById('submit-modal').style.display='none'; doSubmit();" style="padding: 12px 28px; border: none; border-radius: 8px; background: #16a34a; color: white; font-size: 15px; font-weight: 600; cursor: pointer;">Ya, Kumpulkan</button>
                        </div>
                    </div>
                `;
                (document.fullscreenElement || document.documentElement).appendChild(modal);
            }
            const msg = unansweredCount > 0 ? `Masih ada <strong style="color:#dc3545">${unansweredCount} soal</strong> yang belum dijawab.<br>Yakin ingin mengumpulkan ujian sekarang?` : 'Semua soal sudah dijawab. Yakin ingin mengumpulkan ujian?';
            document.getElementById('submit-modal-msg').innerHTML = msg;
            modal.style.display = 'flex';
        }

        function doSubmit() {
            if (isSubmitting) return;
            isSubmitting = true;
            if (timerInterval) clearInterval(timerInterval);
            saveRemainingTime();

            document.getElementById('prev-btn').disabled = true;
            document.getElementById('next-btn').disabled = true;
            document.getElementById('doubt-btn').disabled = true;
            const submitBtn = document.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengumpulkan...';
            }

            showLoadingOverlay('Menyimpan jawaban dan mengumpulkan ujian...');
            flushEssayAnswers().then(function() {
                doActualSubmit(0);
            }).catch(function() {
                doActualSubmit(0);
            });
        }

        function doActualSubmit(retryCount) {
            const maxRetries = 3;
            const baseUrl = '<?= function_exists('config') ? config('app_url') : '' ?>';

            fetch(baseUrl + '/exam/submit/' + sessionId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'session_id=' + sessionId,
                    credentials: 'same-origin',
                    signal: AbortSignal.timeout(20000)
                })
                .then(function(response) {
                    if (response.ok || response.redirected) {
                        updateLoadingOverlay('Ujian berhasil dikumpulkan! Menampilkan hasil...');
                        clearBackup();
                        setTimeout(function() {
                            // [FIX-FS] lepas fullscreen hanya setelah ujian benar-benar selesai
                            finishExam(baseUrl + '/exam/result/' + sessionId);
                        }, 1000);
                    } else {
                        throw new Error('HTTP ' + response.status);
                    }
                })
                .catch(function(error) {
                    console.error('Submit error:', error);
                    if (retryCount < maxRetries) {
                        updateLoadingOverlay('Mencoba ulang pengumpulan... (' + (retryCount + 1) + '/' + maxRetries + ')');
                        setTimeout(function() {
                            doActualSubmit(retryCount + 1);
                        }, 2000);
                    } else {
                        updateLoadingOverlay('Mengalihkan ke halaman hasil...');
                        setTimeout(function() {
                            finishExam(baseUrl + '/exam/result/' + sessionId);
                        }, 1500);
                    }
                });
        }

        function showLoadingOverlay(message) {
            let overlay = document.getElementById('submit-loading-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'submit-loading-overlay';
                overlay.style.cssText = `position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 999999; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; text-align: center;`;
                overlay.innerHTML = `<div style="font-size: 60px; margin-bottom: 20px; animation: spin 1s linear infinite;">⏳</div><h3 id="loading-overlay-msg" style="font-size: 20px; margin-bottom: 10px;"></h3><p style="color: rgba(255,255,255,0.7); font-size: 14px;">Mohon tunggu, jangan tutup aplikasi</p><style>@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }</style>`;
                document.body.appendChild(overlay);
            }
            document.getElementById('loading-overlay-msg').textContent = message;
            overlay.style.display = 'flex';
        }

        function updateLoadingOverlay(message) {
            const el = document.getElementById('loading-overlay-msg');
            if (el) el.textContent = message;
        }

        function timerExpired() {
            showLoadingOverlay('Waktu ujian telah habis. Mengumpulkan otomatis...');
            setTimeout(function() {
                doSubmit();
            }, 1500);
        }

        document.addEventListener('DOMContentLoaded', function() {

            setTimeout(function() {
                function tryRender(attempt) {
                    if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                        document.querySelectorAll('.question-card').forEach(el => {
                            el.style.visibility = 'visible';
                        });
                        MathJax.typesetPromise().then(function() {
                            document.querySelectorAll('.question-card').forEach(el => {
                                if (!el.classList.contains('active')) {
                                    el.style.display = 'none';
                                }
                            });
                        }).catch(err => console.log(err));
                    } else if (attempt < 20) {
                        setTimeout(() => tryRender(attempt + 1), 300);
                    }
                }
                tryRender(0);
            }, 500);

            // [FIX-FS] tombol layar penghalang fullscreen
            hidePageLoader();
            const fullscreenBtn = document.getElementById('start-fullscreen-btn');
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', function() {
                    requestFullscreen().then(function() {
                        hideFullscreenGate();
                        unlockExam();
                    }).catch(function() {
                        console.log('Fullscreen request denied by browser');
                    });
                });
            }

            function lockExam() {
                var elTimer = document.getElementById('timer');
                var elInfo = document.querySelector('.exam-info');
                var elMain = document.querySelector('.main-container');
                var elSubmit = document.querySelector('.submit-btn');
                if (elTimer) elTimer.style.display = 'none';
                if (elInfo) elInfo.style.display = 'none';
                if (elMain) elMain.style.display = 'none';
                if (elSubmit) elSubmit.style.display = 'none';
                var gate = document.getElementById('fullscreen-prompt');
                if (gate) gate.style.display = 'flex';
                antiCheatReady = false;
                if (timerInterval) clearInterval(timerInterval);
                if (syncTimerInterval) clearInterval(syncTimerInterval);
            }

        function hidePageLoader(){ var l=document.getElementById('page-loader'); if(l) l.style.display='none'; }
        function showPageError(msg){ hidePageLoader(); var e=document.getElementById('page-error'); if(!e) return; var m=document.getElementById('page-error-msg'); if(m && msg) m.textContent=msg; e.style.display='flex'; }
        window.addEventListener('error', function(ev){
            try {
                var t = ev && ev.target;
                if (t && (t.tagName === 'IMG' || t.tagName === 'AUDIO' || t.tagName === 'SCRIPT' || t.tagName === 'LINK' || t.tagName === 'IFRAME')) return;
            } catch(e){}
        });
        window.addEventListener('unhandledrejection', function(){ /* handled by in-app retries */ });

        function unlockExam() {
                hidePageLoader();
                var elTimer = document.getElementById('timer');
                var elInfo = document.querySelector('.exam-info');
                var elMain = document.querySelector('.main-container');
                var elSubmit = document.querySelector('.submit-btn');
                if (elTimer) elTimer.style.display = '';
                if (elInfo) elInfo.style.display = '';
                if (elMain) elMain.style.display = '';
                if (elSubmit) elSubmit.style.display = '';
                antiCheatReady = true;
                timerInterval = setInterval(updateTimer, 1000);
                syncTimerInterval = setInterval(syncTimerFromServer, 5000);
                syncTimerFromServer();
                updateTimer();
                updateNavigationDisplay();
                updateDoubtButton();
                goToQuestion(1);
            }

            document.addEventListener('fullscreenchange', handleFullscreenChange);
            document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
            document.addEventListener('mozfullscreenchange', handleFullscreenChange);
            document.addEventListener('MSFullscreenChange', handleFullscreenChange);

            setInterval(function() {
                if (!isSubmitting && !isFullscreen) {
                    if (!isInFullscreen()) {
                        requestFullscreen();
                    }
                }
            }, 500);

            window.addEventListener('beforeunload', function(e) {
                if (isSubmitting) return undefined;
                e.preventDefault();
                e.returnValue = 'Anda yakin ingin meninggalkan halaman ujian? Progress ujian akan hilang!';
                return 'Anda yakin ingin meninggalkan halaman ujian? Progress ujian akan hilang!';
            });

            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !isSubmitting) {
                    e.preventDefault();
                    e.stopPropagation();
                    // [FIX-FS] notifikasi di halaman, bukan alert() bawaan browser
                    showFsToast('⚠️ Tetap dalam mode fullscreen sampai ujian selesai.');
                    return false;
                }
                if (e.key === 'F12') {
                    e.preventDefault();
                    return false;
                }
                if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                    e.preventDefault();
                    return false;
                }
                if (e.ctrlKey && e.key === 'u') {
                    e.preventDefault();
                    return false;
                }
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    return false;
                }

                if (e.key === 'Meta' || e.key === 'OS') {
                    e.preventDefault();
                    e.stopPropagation();
                    recordViolation('Tekan tombol Windows');
                    return false;
                }

                if (e.key === 'ArrowLeft' && currentQuestion > 1) previousQuestion();
                else if (e.key === 'ArrowRight' && currentQuestion < totalQuestions) nextQuestion();
            });
        });

        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            if (!isSubmitting) {
                history.go(1);
            }
        };

        function handleFullscreenChange() {
            const isCurrentlyFullscreen = isInFullscreen();
            if (isCurrentlyFullscreen) {
                isFullscreen = true;
                hideViolationWarning();
                hideFullscreenGate();
                unlockExam();
            } else {
                isFullscreen = false;
                if (!isSubmitting) {
                    recordViolation('Keluar dari fullscreen');
                    lockExam();
                    requestFullscreen();
                    setTimeout(() => {
                        if (!isSubmitting && !isFullscreen) requestFullscreen();
                    }, 100);
                }
            }
        }

        function showBigWarning() {
            const warningDiv = document.createElement('div');
            warningDiv.style.cssText = `position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(231, 76, 60, 0.95); z-index: 999999; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s;`;
            warningDiv.innerHTML = `
                <div style="background: white; padding: 50px; border-radius: 20px; text-align: center; max-width: 600px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                    <div style="font-size: 100px; margin-bottom: 20px;">⚠️</div>
                    <h1 style="color: #e74c3c; margin-bottom: 20px; font-size: 36px;">PELANGGARAN TERDETEKSI!</h1>
                    <p style="font-size: 24px; margin-bottom: 20px; color: #2c3e50;">Anda berpindah tab/window!</p>
                    <p style="font-size: 20px; margin-bottom: 30px; color: #7f8c8d;">Jangan buka Google atau aplikasi lain!</p>
                    <div style="background: #fadbd8; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                        <p style="font-size: 28px; font-weight: bold; color: #c0392b; margin: 0;">Pelanggaran: ${violationCount}/1</p>
                        <p style="font-size: 18px; color: #e74c3c; margin: 10px 0 0 0;">Ujian akan dikumpulkan otomatis!</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: #27ae60; color: white; border: none; padding: 20px 50px; font-size: 20px; border-radius: 50px; cursor: pointer; font-weight: bold;">Saya Mengerti</button>
                </div>
            `;
            document.body.appendChild(warningDiv);
            setTimeout(() => {
                if (warningDiv.parentElement) warningDiv.remove();
            }, 5000);
        }

        function createViolationEventUuid() {
            const bytes = new Uint8Array(16);
            if (window.crypto && window.crypto.getRandomValues) {
                window.crypto.getRandomValues(bytes);
            } else {
                for (let i = 0; i < bytes.length; i++) {
                    bytes[i] = Math.floor(Math.random() * 256);
                }
            }
            bytes[6] = (bytes[6] & 0x0f) | 0x40;
            bytes[8] = (bytes[8] & 0x3f) | 0x80;
            const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
            return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
        }

        function recordViolation(reason) {
            if (isSubmitting || violationCount >= maxViolations) return;

            violationCount++;
            const counterEl = document.getElementById('violation-count');
            const warningEl = document.getElementById('warning-count');
            if (counterEl) counterEl.textContent = violationCount;
            if (warningEl) warningEl.textContent = violationCount;

            const violationCounterEl = document.getElementById('violation-counter');
            const mobileWarningEl = document.getElementById('mobile-warning');
            if (violationCounterEl) violationCounterEl.style.display = 'block';
            if (mobileWarningEl) mobileWarningEl.style.display = 'block';

            if (violationCount >= maxViolations) {
                isSubmitting = true;
                suspendAndSubmitExam(createViolationEventUuid(), reason);
            }
        }

        function suspendAndSubmitExam(eventUuid, reason) {
            if (timerInterval) clearInterval(timerInterval);

            const suspendDiv = document.createElement('div');
            suspendDiv.id = 'suspend-overlay';
            suspendDiv.style.cssText = `position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(192, 57, 43, 0.98); z-index: 9999999; display: flex; align-items: center; justify-content: center;`;
            suspendDiv.innerHTML = `
                <div style="background:white;padding:60px;border-radius:20px;text-align:center;max-width:700px;box-shadow:0 20px 60px rgba(0,0,0,0.7);">
                    <div style="font-size:120px;margin-bottom:20px;">❌</div>
                    <h1 style="color:#c0392b;margin-bottom:20px;font-size:42px;">UJIAN DI-SUSPEND!</h1>
                    <p style="font-size:24px;margin-bottom:20px;color:#2c3e50;font-weight:bold;">Anda telah melakukan pelanggaran</p>
                    <p style="font-size:20px;margin-bottom:30px;color:#7f8c8d;">Ujian Anda akan otomatis dikumpulkan dan di-suspend</p>
                    <div style="background:#fadbd8;padding:25px;border-radius:10px;margin-bottom:30px;">
                        <p style="font-size:22px;color:#c0392b;margin:0;">Hubungi pengawas ujian untuk informasi lebih lanjut</p>
                    </div>
                    <p id="submit-status" style="font-size:18px;color:#95a5a6;">⏳ Menyimpan sisa waktu...</p>
                </div>
            `;
            document.body.appendChild(suspendDiv);

            const baseUrl = '<?= function_exists('config') ? config('app_url') : '' ?>';
            const statusEl = () => document.getElementById('submit-status');
            const timePayload = JSON.stringify({
                session_id: sessionId,
                remaining_time: remainingTime
            });

            if (navigator.sendBeacon) {
                const blob = new Blob([timePayload], {
                    type: 'application/json'
                });
                navigator.sendBeacon(baseUrl + '/exam/saveTime', blob);
            }

            try {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', baseUrl + '/exam/saveTime', false);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.send(timePayload);
            } catch (e) {
                console.error('XHR saveTime gagal:', e);
            }

            function reportSuspension() {
                const retryButton = document.getElementById('retry-suspension');
                if (retryButton) retryButton.disabled = true;
                statusEl().textContent = 'Menyimpan status pelanggaran...';
                fetch(baseUrl + '/exam/suspendSession', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body: new URLSearchParams({
                            session_id: String(sessionId),
                            event_uuid: eventUuid,
                            reason: reason,
                            remaining_time: String(Math.max(0, Math.floor(remainingTime)))
                        }).toString()
                    })
                    .then(async r => {
                        const data = await r.json().catch(() => ({}));
                        if (!r.ok || !data.success) {
                            throw new Error(data.message || `Pencatatan pelanggaran gagal (HTTP ${r.status})`);
                        }
                        statusEl().innerHTML = '<span style="color:#27ae60;font-weight:bold;">Sesi ujian berhasil ditangguhkan.</span>';
                        clearBackup();
                        setTimeout(() => {
                            finishExam(baseUrl + '/exam');
                        }, 3000);
                    })
                    .catch(err => {
                        statusEl().textContent = 'Gagal mencatat pelanggaran: ' + err.message;
                        let button = document.getElementById('retry-suspension');
                        if (!button) {
                            button = document.createElement('button');
                            button.id = 'retry-suspension';
                            button.textContent = 'Coba lagi';
                            button.style.cssText = 'margin-top:16px;padding:12px 24px;border:0;border-radius:8px;background:#c0392b;color:white;font-size:16px;cursor:pointer';
                            suspendDiv.querySelector('div').appendChild(button);
                        }
                        button.onclick = reportSuspension;
                        button.disabled = false;
                    });
            }

            setTimeout(reportSuspension, 1000);
        }

        function showViolationWarning(message) {
            const warning = document.getElementById('mobile-warning');
            warning.textContent = message;
            warning.style.display = 'block';
        }

        function hideViolationWarning() {
            const warning = document.getElementById('mobile-warning');
            warning.style.display = 'none';
        }
    </script>

    <script>
        window.addEventListener('load', function() {
            function tryRender(attempt) {
                if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                    MathJax.typesetPromise().catch(err => console.log(err));
                } else if (attempt < 20) {
                    setTimeout(() => tryRender(attempt + 1), 300);
                }
            }
            setTimeout(() => tryRender(0), 300);
        });
    </script>

    <script>
        function toggleImageZoom(img) {
            if (img.style.transform && img.style.transform !== "none") {
                img.style.transform = "none";
                img.style.position = "relative";
                img.style.zIndex = "auto";
            } else {
                img.style.transform = "scale(1.5)";
                img.style.position = "relative";
                img.style.zIndex = "1000";
            }
        }

        document.addEventListener("click", function(e) {
            if (!e.target.matches(".question-image img")) {
                document.querySelectorAll(".question-image img").forEach(img => {
                    img.style.transform = "none";
                    img.style.position = "relative";
                    img.style.zIndex = "auto";
                });
            }
        });
    </script>
    <script>
        (function() {
            const panel = document.getElementById('pdf-viewer-container');
            const examContentEl = document.querySelector('.exam-content');

            if (!examHasPdfQuestions || !panel) {
                if (examContentEl) examContentEl.classList.add('full-width');
                return;
            }

            const canvas = document.getElementById('pdf-canvas');
            const ctx = canvas.getContext('2d');
            const loadingEl = document.getElementById('pdf-loading');

            // Cache dokumen PDF yang sudah pernah dimuat, key = URL PDF, agar soal
            // dengan file PDF sama (atau balik ke soal yang sama) tidak load ulang.
            const pdfCache = {}; // { url: { doc, numPages } }

            let activeUrl = null; // URL PDF yang sedang aktif di panel
            let currentPage = 1;
            const MIN_SCALE = 0.5;
            const MAX_SCALE = 3;
            const ZOOM_STEP = 0.2;
            let currentScale = 1.5;
            let loadToken = 0; // dipakai untuk membatalkan hasil load yang sudah usang
            let currentRenderTask = null; // render task pdf.js yang sedang berjalan, dibatalkan kalau ada render baru

            const zoomLevelEl = document.getElementById('pdf-zoom-level');
            const zoomInBtn = document.getElementById('pdf-zoom-in-btn');
            const zoomOutBtn = document.getElementById('pdf-zoom-out-btn');

            function updateZoomControls() {
                if (zoomLevelEl) zoomLevelEl.textContent = Math.round(currentScale * 100) + '%';
                if (zoomInBtn) zoomInBtn.disabled = currentScale >= MAX_SCALE;
                if (zoomOutBtn) zoomOutBtn.disabled = currentScale <= MIN_SCALE;
            }
            updateZoomControls();

            function setPanelState(state) {
                // state: 'loading' | 'ready'
                if (state === 'loading') {
                    canvas.style.display = 'none';
                    loadingEl.style.display = 'block';
                    loadingEl.innerHTML =
                        '<i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i>' +
                        '<p style="margin-top:12px;">Memuat PDF...</p>';
                } else if (state === 'ready') {
                    loadingEl.style.display = 'none';
                    canvas.style.display = 'block';
                }
            }

            function showPanelError(message) {
                canvas.style.display = 'none';
                loadingEl.style.display = 'block';
                loadingEl.innerHTML =
                    '<i class="fas fa-exclamation-triangle" style="color:#dc3545; font-size:2rem;"></i>' +
                    '<p style="margin-top:12px; color:#dc3545;">' + message + '</p>';
            }

            if (typeof pdfjsLib === 'undefined' || pdfjsLib.version !== '3.11.174') {
                showPanelError('Library PDF lokal gagal dimuat. Muat ulang halaman atau hubungi pengawas ujian.');
                console.error('PDF.js gagal dimuat atau versinya tidak sesuai.');
                return;
            }

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                '<?= function_exists('config') ? config('app_url') : '' ?>/assets/pdfjs/pdf.worker.min.js?v=3.11.174';

            function renderPage(num) {
                const cached = pdfCache[activeUrl];
                if (!cached || !cached.doc) return;

                // Batalkan render yang sedang berjalan (kalau ada) sebelum mulai render baru.
                // Tanpa ini, klik zoom yang cepat bisa membuat pdf.js gagal render secara
                // diam-diam karena canvas yang sama dipakai dua render bersamaan.
                const myRenderToken = ++loadToken;
                if (currentRenderTask) {
                    try {
                        currentRenderTask.cancel();
                    } catch (e) {}
                    currentRenderTask = null;
                }

                cached.doc.getPage(num).then(function(page) {
                    if (myRenderToken !== loadToken) return; // sudah ada render/soal lain yang lebih baru

                    const viewport = page.getViewport({
                        scale: currentScale
                    });
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    const renderContext = {
                        canvasContext: ctx,
                        viewport: viewport
                    };
                    const task = page.render(renderContext);
                    currentRenderTask = task;

                    task.promise.then(function() {
                        if (currentRenderTask === task) currentRenderTask = null;
                    }).catch(function(err) {
                        if (currentRenderTask === task) currentRenderTask = null;
                        // RenderingCancelledException terjadi normal saat kita membatalkan
                        // render lama demi render baru (mis. klik zoom cepat) — bukan error.
                        if (err && err.name === 'RenderingCancelledException') return;
                        console.error('Gagal render halaman PDF:', err);
                        if (myRenderToken === loadToken) {
                            showPanelError('Gagal menampilkan halaman PDF. Hubungi pengawas ujian.');
                        }
                    });

                    document.getElementById('pdf-page-num').textContent = num;
                }).catch(function(err) {
                    if (myRenderToken !== loadToken) return;
                    console.error('Gagal render halaman PDF:', err);
                    showPanelError('Gagal menampilkan halaman PDF. Hubungi pengawas ujian.');
                });
            }

            window.pdfPrevPage = function() {
                if (currentPage <= 1) return;
                currentPage--;
                renderPage(currentPage);
            };

            window.pdfNextPage = function() {
                const cached = pdfCache[activeUrl];
                if (!cached || !cached.doc || currentPage >= cached.numPages) return;
                currentPage++;
                renderPage(currentPage);
            };

            window.pdfZoomIn = function() {
                if (currentScale >= MAX_SCALE) return;
                currentScale = Math.min(currentScale + ZOOM_STEP, MAX_SCALE);
                updateZoomControls();
                renderPage(currentPage);
            };
            window.pdfToggleExpand = function() {
                panel.classList.toggle('expanded');
                const btn = document.getElementById('pdf-expand-btn');
                const isExpanded = panel.classList.contains('expanded');
                btn.classList.toggle('active', isExpanded);
                btn.innerHTML = isExpanded ?
                    '<i class="fas fa-compress-alt"></i>' :
                    '<i class="fas fa-expand-alt"></i>';
                btn.title = isExpanded ? 'Kecilkan panel PDF' : 'Perbesar panel PDF';

                // Setelah transisi lebar selesai, render ulang di scale yang pas
                setTimeout(() => {
                    fitToWidth();
                }, 320);
            };

            function fitToWidth() {
                const cached = pdfCache[activeUrl];
                if (!cached || !cached.doc) return;
                const wrapper = document.getElementById('pdf-canvas-wrapper');
                const availableWidth = wrapper.clientWidth - 32; // dikurangi padding

                cached.doc.getPage(currentPage).then(function(page) {
                    const baseViewport = page.getViewport({
                        scale: 1
                    });
                    let newScale = availableWidth / baseViewport.width;
                    newScale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, newScale));
                    currentScale = newScale;
                    updateZoomControls();
                    renderPage(currentPage);
                });
            }

            window.pdfZoomOut = function() {
                if (currentScale <= MIN_SCALE) return;
                currentScale = Math.max(currentScale - ZOOM_STEP, MIN_SCALE);
                updateZoomControls();
                renderPage(currentPage);
            };

            // ==== KONFIGURASI LAZY LOAD ====
            const MAX_CACHE_ENTRIES = 6;
            const MAX_LOAD_RETRIES = 2;
            const RETRY_DELAY_MS = 1500;

            const pdfLoadOrder = []; // urutan pemakaian cache, untuk LRU eviction

            function touchCacheEntry(url) {
                const idx = pdfLoadOrder.indexOf(url);
                if (idx !== -1) pdfLoadOrder.splice(idx, 1);
                pdfLoadOrder.push(url);
            }

            function evictOldCacheIfNeeded() {
                while (pdfLoadOrder.length > MAX_CACHE_ENTRIES) {
                    const oldestUrl = pdfLoadOrder.shift();
                    if (oldestUrl === activeUrl) continue; // jangan buang yang sedang aktif ditampilkan
                    const entry = pdfCache[oldestUrl];
                    if (entry && entry.doc && typeof entry.doc.destroy === 'function') {
                        entry.doc.destroy().catch(() => {});
                    }
                    delete pdfCache[oldestUrl];
                }
            }

            function setLoadProgress(pct) {
                if (!loadingEl) return;
                const pctText = pct === null ? '' : `<p style="margin-top:4px;font-size:13px;color:#94a3b8;">${pct}%</p>`;
                loadingEl.innerHTML =
                    '<i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i>' +
                    '<p style="margin-top:12px;">Memuat PDF...</p>' + pctText;
            }

            /**
             * Lazy load satu PDF dengan progress + retry.
             * `isPrefetch = true` artinya load ini berjalan diam-diam di background
             * (tidak boleh mengubah tampilan panel / loading indicator).
             */
            async function loadPdf(url, isPrefetch = false) {
                // Cegah load ganda untuk url yang sama saat masih dalam proses.
                if (pdfCache[url] && pdfCache[url].loading) {
                    return pdfCache[url].loadingPromise;
                }

                const myToken = isPrefetch ? loadToken : ++loadToken;
                if (!isPrefetch) setPanelState('loading');

                const loadingPromise = (async () => {
                    let lastError = null;

                    for (let attempt = 0; attempt <= MAX_LOAD_RETRIES; attempt++) {
                        try {
                            const loadingTask = pdfjsLib.getDocument({
                                url: url,
                                withCredentials: true
                            });

                            if (!isPrefetch) {
                                loadingTask.onProgress = function(progressData) {
                                    if (myToken !== loadToken) return;
                                    if (progressData.total) {
                                        const pct = Math.min(100, Math.round((progressData.loaded / progressData.total) * 100));
                                        setLoadProgress(pct);
                                    } else {
                                        setLoadProgress(null);
                                    }
                                };
                            }

                            const pdf = await loadingTask.promise;

                            pdfCache[url] = {
                                doc: pdf,
                                numPages: pdf.numPages,
                                loading: false
                            };
                            touchCacheEntry(url);
                            evictOldCacheIfNeeded();

                            if (!isPrefetch && myToken === loadToken) {
                                document.getElementById('pdf-page-count').textContent = pdf.numPages;
                                currentPage = 1;
                                setPanelState('ready');
                                fitToWidth(); // ← ganti dari renderPage(currentPage) langsung ke fitToWidth
                            }
                            return pdf;

                        } catch (err) {
                            lastError = err;
                            console.error(`Gagal load PDF (percobaan ${attempt + 1}/${MAX_LOAD_RETRIES + 1}):`, url, err);
                            if (attempt < MAX_LOAD_RETRIES) {
                                if (!isPrefetch) setLoadProgress(null); // reset tampilan sebelum retry
                                await new Promise(r => setTimeout(r, RETRY_DELAY_MS));
                            }
                        }
                    }

                    // Semua percobaan gagal
                    pdfCache[url] = {
                        doc: null,
                        numPages: 0,
                        failed: true,
                        loading: false
                    };
                    if (!isPrefetch && myToken === loadToken) {
                        showPanelError('Gagal memuat PDF. Hubungi pengawas ujian.');
                    }
                    throw lastError;
                })();

                pdfCache[url] = {
                    loading: true,
                    loadingPromise
                };
                return loadingPromise;
            }

            /**
             * Prefetch PDF untuk soal berikutnya secara diam-diam saat browser idle,
             * supaya saat user klik "Selanjutnya" PDF sudah siap tanpa loading.
             */
            function prefetchNextPdf(fromQuestionNum) {
                const nextUrl = questionPdfMap[fromQuestionNum + 1];
                if (!nextUrl || pdfCache[nextUrl]) return;

                const doPrefetch = () => loadPdf(nextUrl, true).catch(() => {}); // tambahkan .catch

                if ('requestIdleCallback' in window) {
                    requestIdleCallback(doPrefetch, {
                        timeout: 4000
                    });
                } else {
                    setTimeout(doPrefetch, 800);
                }
            }

            // Dipanggil setiap kali navigasi soal (dari goToQuestion).
            window.showPdfForQuestion = function(questionNum) {
                const url = questionPdfMap[questionNum];

                if (!url) {
                    activeUrl = null;
                    panel.style.display = 'none';
                    if (examContentEl) examContentEl.classList.add('full-width');
                    return;
                }

                panel.style.display = '';
                if (examContentEl) examContentEl.classList.remove('full-width');

                if (url === activeUrl) return;
                activeUrl = url;

                const cached = pdfCache[url];
                if (cached && cached.doc) {
                    touchCacheEntry(url);
                    document.getElementById('pdf-page-count').textContent = cached.numPages;
                    currentPage = 1;
                    setPanelState('ready');
                    fitToWidth();
                } else if (cached && cached.failed) {
                    showPanelError('Gagal memuat PDF. Hubungi pengawas ujian.');
                } else if (cached && cached.loading) {
                    setPanelState('loading');
                    cached.loadingPromise.then(() => {
                        if (activeUrl !== url) return;
                        const c = pdfCache[url];
                        if (c && c.doc) {
                            document.getElementById('pdf-page-count').textContent = c.numPages;
                            currentPage = 1;
                            setPanelState('ready');
                            renderPage(currentPage);
                        } else {
                            showPanelError('Gagal memuat PDF. Hubungi pengawas ujian.');
                        }
                    }).catch(() => {
                        if (activeUrl === url) showPanelError('Gagal memuat PDF. Hubungi pengawas ujian.');
                    });
                } else {
                    loadPdf(url).catch(() => {}); // sudah ditangani internal, cegah unhandled rejection
                }

                // Setelah soal ini ditangani, siapkan PDF soal berikutnya di background.
                prefetchNextPdf(questionNum);
            };

            // Panggil sekali di awal untuk soal pertama.
            showPdfForQuestion(currentQuestion || 1);
        })();
    </script>
</body>

</html>