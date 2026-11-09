<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Soal - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">

    <!-- MathJax for Mathematical Formulas -->
    <script>
        if (!window.MathJax) {
            window.MathJax = {
                tex: {
                    inlineMath: [['$', '$'], ['\\(', '\\)']],
                    displayMath: [['$$', '$$'], ['\\[', '\\]']],
                    processEscapes: true
                },
                options: {
                    skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
                }
            };
        }
    </script>
    <!-- Script MathJax dimuat oleh navbar.php agar tidak double-load -->
   <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }

        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--light); }

        .navbar {
            background: white; box-shadow: var(--shadow);
            padding: 1rem 0; border-bottom: 1px solid var(--border);
        }
        .navbar-brand { font-weight: 800; font-size: 1.5rem; color: var(--primary); }

        .main-content { padding: 2rem 0; max-width: 900px; margin: 0 auto; }

        .btn-back {
            display: inline-flex; align-items: center; gap: 0.5rem;
            color: #64748b; text-decoration: none; margin-bottom: 1.5rem;
            font-weight: 600; transition: all 0.2s;
        }
        .btn-back:hover { color: var(--primary); transform: translateX(-3px); }

        .form-card {
            background: white; border-radius: 1rem;
            padding: 2rem; box-shadow: var(--shadow); border: 1px solid var(--border);
        }
        .form-header { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
        .form-title { font-size: 1.5rem; font-weight: 700; color: var(--dark); margin: 0; }

        .form-label { display: block; font-weight: 600; color: var(--dark); margin-bottom: 0.5rem; font-size: 0.875rem; }
        .form-control, .form-select {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border);
            border-radius: 0.5rem; font-size: 1rem; transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn { padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.2s; border: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-secondary { background: white; color: var(--dark); border: 1px solid var(--border); }

        #imageUploadArea:hover { border-color: var(--primary); background: #f1f5f9; }

        @media (max-width: 768px) {
            .main-content { padding: 1.5rem 1rem; }
            .form-card { padding: 1.5rem; }
        }
        .listening-section {
            background: #f0f7ff; border: 1.5px dashed #6ea8d8;
            border-radius: 10px; padding: 16px 20px; margin-bottom: 1rem;
        }

        /* =============================================
           TOOLBAR RICH TEXT EDITOR
        ============================================= */
        .rte-wrapper {
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: all 0.2s;
        }
        .rte-wrapper:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .rte-toolbar {
            display: flex; align-items: center; gap: 2px;
            padding: 6px 8px; background: #f8fafc;
            border-bottom: 1px solid var(--border); flex-wrap: wrap;
        }

        .rte-btn {
            width: 30px; height: 30px;
            border: 1px solid transparent; border-radius: 5px;
            background: transparent; color: #475569;
            cursor: pointer; display: inline-flex;
            align-items: center; justify-content: center;
            font-size: 13px; font-weight: 600;
            transition: all 0.15s; flex-shrink: 0; padding: 0;
        }
        .rte-btn:hover { background: white; border-color: var(--border); color: var(--primary); }
        .rte-btn.active { background: #ede9fe; border-color: #c4b5fd; color: var(--primary-dark); }
        .rte-btn i { pointer-events: none; }

        .rte-divider { width: 1px; height: 18px; background: var(--border); margin: 0 4px; flex-shrink: 0; }

        .rte-fs-group {
            display: flex; align-items: center; gap: 2px;
            background: white; border: 1px solid var(--border);
            border-radius: 5px; padding: 0 4px; height: 30px;
        }
        .rte-fs-group button {
            border: none; background: transparent; cursor: pointer;
            color: #64748b; padding: 0 3px; font-size: 14px;
            display: flex; align-items: center; transition: color 0.15s;
        }
        .rte-fs-group button:hover { color: var(--primary); }
        .rte-fs-display {
            font-size: 11px; font-weight: 700; color: #334155;
            min-width: 22px; text-align: center; user-select: none;
        }

        /* Hidden textarea yang dikirim ke server */
        #questionText { display: none !important; }

        /* contenteditable area */
        #questionEditor {
            width: 100%; min-height: 120px; padding: 0.75rem 1rem;
            font-size: 1rem; line-height: 1.7; color: #1e293b;
            background: white; outline: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            resize: vertical; overflow-y: auto;
        }
        #questionEditor:empty::before {
            content: attr(data-placeholder);
            color: #94a3b8; pointer-events: none;
        }

        .rte-feedback {
            font-size: 11px; color: #10b981; font-weight: 600;
            padding: 2px 8px; opacity: 0; transition: opacity 0.3s; white-space: nowrap;
        }
        .rte-feedback.show { opacity: 1; }
        .rte-feedback.error { color: #ef4444; }

        /* Paksa semua teks di preview (termasuk MathJax) jadi hitam */
        #questionPreview,
        #questionTextPreview,
        #optionsPreview,
        #optionAPreview, #optionBPreview, #optionCPreview,
        #optionDPreview, #optionEPreview {
            color: #1e293b !important;
        }
        #questionPreview mjx-container,
        #questionPreview mjx-container * {
            color: #1e293b !important;
            fill: #1e293b !important;
        }
        #questionPreview svg path,
        #questionPreview svg use {
            fill: #1e293b !important;
        }
        .opt-rte{border:1px solid var(--border);border-radius:.5rem;overflow:hidden;transition:border-color .2s;margin-bottom:1rem}
        .opt-rte:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.1)}
        .opt-toolbar{display:flex;align-items:center;gap:2px;padding:5px 8px;background:#f8fafc;border-bottom:1px solid var(--border);flex-wrap:wrap}
        .opt-rte-btn{width:28px;height:28px;border:1px solid transparent;border-radius:5px;background:transparent;color:#475569;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;transition:all .15s;padding:0}
        .opt-rte-btn:hover{background:white;border-color:var(--border);color:var(--primary)}
        .opt-rte-btn.active{background:#ede9fe;border-color:#c4b5fd;color:var(--primary-dark)}
        .opt-fs-group{display:flex;align-items:center;gap:2px;background:white;border:1px solid var(--border);border-radius:5px;padding:0 4px;height:28px}
        .opt-fs-group button{border:none;background:transparent;cursor:pointer;color:#64748b;padding:0 3px;font-size:11px;display:flex;align-items:center;transition:color .15s}
        .opt-fs-group button:hover{color:var(--primary)}
        .opt-fs-display{font-size:11px;font-weight:700;color:#334155;min-width:20px;text-align:center;user-select:none}
        .opt-editor{width:100%;min-height:44px;padding:8px 12px;font-size:16px;line-height:1.6;color:#1e293b;background:white;outline:none;font-family:'Plus Jakarta Sans',sans-serif}
        .opt-editor:empty::before{content:attr(data-ph);color:#94a3b8;pointer-events:none}
        .rte-opt-divider{width:1px;height:16px;background:var(--border);margin:0 3px;flex-shrink:0}
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <a href="<?= config('app_url') ?>/question" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Bank Soal
        </a>

        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">Edit Soal</h1>
            </div>

            <form method="POST" enctype="multipart/form-data" id="mainForm">
                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($question['question_image'] ?? '') ?>">
                <input type="hidden" name="existing_audio" value="<?= htmlspecialchars($question['audio_file'] ?? '') ?>">

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" style="background:#d1fae5;color:#065f46;border:none;border-radius:0.75rem;padding:1rem;margin-bottom:1.5rem;">
                        <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Mata Pelajaran -->
                <div class="mb-3">
                    <label class="form-label">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select" required>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subject['id'] == $question['subject_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tipe Soal -->
                <div class="mb-3">
                    <label class="form-label">Tipe Soal</label>
                    <select name="type" id="questionType" class="form-select" required>
                        <option value="multiple_choice" <?= $question['type'] === 'multiple_choice' ? 'selected' : '' ?>>Pilihan Ganda</option>
                        <option value="short_answer"    <?= $question['type'] === 'short_answer'    ? 'selected' : '' ?>>Isian Singkat (Angka)</option>
                        <option value="short_answer_text" <?= $question['type'] === 'short_answer_text' ? 'selected' : '' ?>>Isian Singkat (Kalimat)</option>
                        <option value="essay"           <?= $question['type'] === 'essay'           ? 'selected' : '' ?>>Essay</option>
                        <option value="listening"       <?= $question['type'] === 'listening'       ? 'selected' : '' ?>>Listening</option>
                        <option value="tkp"             <?= $question['type'] === 'tkp'             ? 'selected' : '' ?>>TKP (Skala Likert)</option>
                    </select>
                </div>

                <!-- Listening Section -->
                <div id="listeningSection" class="listening-section" style="display:<?= $question['type'] === 'listening' ? 'block' : 'none' ?>;">

                    <?php if (!empty($question['audio_file'])): ?>
                    <!-- Audio Existing -->
                    <div id="existingAudioContainer" class="mb-3">
                        <label class="form-label"><i class="fas fa-headphones"></i> Audio Saat Ini</label>
                        <audio controls style="width:100%;border-radius:8px;margin-bottom:8px;">
                            <source src="<?= config('app_url') ?>/uploads/audio/<?= htmlspecialchars($question['audio_file']) ?>">
                        </audio>
                        <button type="button" onclick="gantiAudio()"
                            style="background:#fef3c7;color:#92400e;border:none;padding:6px 14px;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px;">
                            <i class="fas fa-edit"></i> Ganti Audio
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Upload Audio Baru -->
                    <div id="audioUploadWrap" style="<?= !empty($question['audio_file']) ? 'display:none;' : '' ?>">
                        <label class="form-label"><i class="fas fa-headphones"></i> Upload Audio</label>
                        <input type="file" id="audioFile" name="audio_file" class="form-control" accept="audio/*">
                        <div class="form-text">Format: MP3, WAV, OGG, M4A (maks. 20MB)</div>
                    </div>

                    <!-- Preview Audio Baru -->
                    <div id="audioPreviewWrap" style="display:none;margin-top:12px;" class="mb-3">
                        <label class="form-label">Preview Audio Baru</label>
                        <audio id="audioPreview" controls style="width:100%;border-radius:8px;"></audio>
                    </div>

                    <input type="hidden" name="max_plays" value="2">
                </div>

                <!-- Sub Materi -->
                <div class="mb-3">
                    <label class="form-label">Sub Materi</label>
                    <input type="text" name="sub_topic" class="form-control"
                           value="<?= htmlspecialchars($question['sub_topic'] ?? '') ?>"
                           placeholder="Masukkan Sub Materi (Optional)">
                </div>

                <!-- Soal Text -->
                <div class="mb-3" id="questionTextSection">
                    <label class="form-label">Soal</label>

                    <div class="rte-wrapper">
                        <div class="rte-toolbar" id="rteToolbar">

                            <!-- Ukuran Teks -->
                            <div class="rte-fs-group" title="Ukuran teks">
                                <button type="button" id="rtefsDown" title="Kecilkan teks" onclick="rteChangeFontSize(-1)">
                                    <i class="fas fa-minus" style="font-size:9px;"></i>
                                </button>
                                <span class="rte-fs-display" id="rteFsDisplay">16</span>
                                <button type="button" id="rteFsUp" title="Besarkan teks" onclick="rteChangeFontSize(1)">
                                    <i class="fas fa-plus" style="font-size:9px;"></i>
                                </button>
                            </div>

                            <div class="rte-divider"></div>

                            <button type="button" class="rte-btn" id="rteBtnBold" title="Cetak Tebal (Ctrl+B)" onclick="rteFormat('bold')">
                                <i class="fas fa-bold"></i>
                            </button>
                            <button type="button" class="rte-btn" id="rteBtnItalic" title="Cetak Miring (Ctrl+I)" onclick="rteFormat('italic')">
                                <i class="fas fa-italic"></i>
                            </button>
                            <button type="button" class="rte-btn" id="rteBtnUnderline" title="Garis Bawah (Ctrl+U)" onclick="rteFormat('underline')">
                                <i class="fas fa-underline"></i>
                            </button>

                            <div class="rte-divider"></div>

                            <button type="button" class="rte-btn" title="Salin teks soal" onclick="rteCopy()">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button type="button" class="rte-btn" title="Tempel dari clipboard" onclick="rtePaste()">
                                <i class="fas fa-paste"></i>
                            </button>

                            <span class="rte-feedback" id="rteFeedback"></span>
                        </div>

                        <!-- Editable area — diisi dari PHP via JS setelah load -->
                        <div
                            id="questionEditor"
                            contenteditable="true"
                            data-placeholder="Ketik soal atau paste screenshot di bawah..."
                            spellcheck="false"
                        ></div>
                    </div>

                    <!-- Hidden textarea — nilai dikirim ke server -->
                    <textarea name="question_text" id="questionText" class="form-control" rows="4"><?= htmlspecialchars($question['question_text']) ?></textarea>

                    <small class="text-muted" style="display:block;margin-top:0.5rem;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Cara menulis rumus matematika:</strong><br>
                        • <strong>Copy-paste dari Word:</strong> f(x) = x² + 2x - 3 (otomatis dikonversi!)<br>
                        • <strong>Manual LaTeX:</strong> $f(x) = x^2 + 2x - 3$<br>
                        • <strong>Inline:</strong> $x^2 + y^2 = z^2$<br>
                        • Display: $$\frac{a}{b} = c$$<br>
                        • Contoh: $\sqrt{16} = 4$ atau $\int_0^1 x^2 dx$
                    </small>
                </div>

                <!-- Gambar Soal -->
                <div class="mb-3" id="imageSection" style="display:<?= $question['type'] === 'listening' ? 'none' : 'block' ?>;">
                    <label class="form-label">
                        <i class="fas fa-image"></i> Gambar Soal (Opsional)
                    </label>

                    <?php if (!empty($question['question_image'])): ?>
                    <div id="existingImageContainer" style="margin-bottom:1rem;">
                        <img src="<?= config('app_url') ?>/uploads/questions/<?= htmlspecialchars($question['question_image']) ?>"
                             alt="Current Image"
                             style="max-width:100%;border-radius:0.5rem;border:1px solid #e2e8f0;">
                        <div style="margin-top:0.5rem;">
                            <button type="button" id="changeImageBtn" class="btn btn-sm"
                                style="background:#fef3c7;color:#92400e;border:none;padding:5px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;">
                                <i class="fas fa-edit"></i> Ganti Gambar
                            </button>
                            <button type="button" id="deleteImageBtn" class="btn btn-sm"
                                style="background:#fee2e2;color:#991b1b;border:none;padding:5px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;margin-left:6px;">
                                <i class="fas fa-trash"></i> Hapus Gambar
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="delete_image" id="deleteImageFlag" value="0">
                    <?php endif; ?>

                    <div id="imageUploadArea" style="border:2px dashed #e2e8f0;border-radius:0.5rem;padding:2rem;text-align:center;background:#f8fafc;cursor:pointer;transition:all 0.2s;<?= !empty($question['question_image']) ? 'display:none;' : '' ?>">
                        <i class="fas fa-cloud-upload-alt" style="font-size:3rem;color:#94a3b8;margin-bottom:1rem;"></i>
                        <p style="color:#64748b;margin:0;">
                            <strong>Klik untuk upload</strong> atau <strong>Paste (Ctrl+V)</strong> screenshot<br>
                            <small>Format: JPG, PNG, GIF, WebP | Upload langsung tanpa kompresi</small>
                        </p>
                        <input type="file" name="question_image" id="questionImageInput" accept="image/*" style="display:none;">
                    </div>
                    <div id="imagePreviewContainer" style="margin-top:1rem;display:none;">
                        <img id="imagePreview" src="" alt="Preview" style="max-width:100%;border-radius:0.5rem;border:1px solid #e2e8f0;">
                        <button type="button" id="removeImageBtn" class="btn btn-sm btn-danger" style="margin-top:0.5rem;">
                            <i class="fas fa-times"></i> Hapus Gambar
                        </button>
                    </div>
                </div>

                <!-- Preview Soal & Opsi -->
                <div class="mb-3">
                    <label class="form-label">Preview Soal & Opsi</label>
                    <div id="questionPreview" style="padding:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;min-height:60px;">
                        <div id="questionTextPreview">
                            <?= nl2br(autoConvertMath($question['question_text'])) ?>
                        </div>
                        <div id="optionsPreview" style="margin-top:1rem;">
                            <?php if ($question['option_a'] !== null && $question['option_a'] !== ''): ?><div id="optionAPreview"><strong>A.</strong> <?= autoConvertMath($question['option_a']) ?></div><?php endif; ?>
                            <?php if ($question['option_b'] !== null && $question['option_b'] !== ''): ?><div id="optionBPreview"><strong>B.</strong> <?= autoConvertMath($question['option_b']) ?></div><?php endif; ?>
                            <?php if ($question['option_c'] !== null && $question['option_c'] !== ''): ?><div id="optionCPreview"><strong>C.</strong> <?= autoConvertMath($question['option_c']) ?></div><?php endif; ?>
                            <?php if ($question['option_d'] !== null && $question['option_d'] !== ''): ?><div id="optionDPreview"><strong>D.</strong> <?= autoConvertMath($question['option_d']) ?></div><?php endif; ?>
                            <?php if ($question['option_e'] !== null && $question['option_e'] !== ''): ?><div id="optionEPreview"><strong>E.</strong> <?= autoConvertMath($question['option_e']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Panduan Matematika (hanya untuk PG & listening) -->
                <div id="MatematikaSection" style="background:#dcfce7;border-left:4px solid #16a34a;padding:1rem;border-radius:0.5rem;margin-bottom:1rem;display:<?= in_array($question['type'], ['multiple_choice']) ? 'block' : 'none' ?>;">
                    <strong style="color:#15803d;">✅ SISTEM MATEMATIKA SEDERHANA & RELIABLE</strong>
                    <p style="margin:0.5rem 0 0 0;color:#15803d;font-size:0.875rem;">
                        <strong>Fitur yang PASTI BERFUNGSI:</strong><br>
                        🔢 <strong>Pecahan:</strong> Ketik 1/2 otomatis jadi $\frac{1}{2}$<br>
                        📐 <strong>Pangkat:</strong> Ketik x² otomatis jadi $x^2$<br>
                        📊 <strong>Persamaan:</strong> Ketik x = 5 otomatis jadi $x = 5$<br>
                        🎯 <strong>Fungsi:</strong> Ketik f(x) otomatis jadi $f(x)$<br>
                        Sistem fokus pada yang paling sering digunakan guru matematika!
                    </p>
                </div>

                <div id="PanduanMatematikaSection" style="background:#eff6ff;border-left:4px solid #3b82f6;padding:1rem;border-radius:0.5rem;margin-bottom:1.5rem;display:<?= in_array($question['type'], ['multiple_choice']) ? 'block' : 'none' ?>;">
                    <strong style="color:#1e40af;">📚 PANDUAN MATEMATIKA SEDERHANA - YANG PASTI BERFUNGSI</strong>
                    <div style="margin:0.5rem 0 0 0;color:#1e40af;font-size:0.875rem;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:0.5rem;">
                            <div>
                                <strong>🔢 PECAHAN (Paling Penting):</strong><br>
                                • Ketik: <code>1/2</code> → Hasil: $\frac{1}{2}$<br>
                                • Ketik: <code>3/4</code> → Hasil: $\frac{3}{4}$<br>
                                • Ketik: <code>5/6</code> → Hasil: $\frac{5}{6}$<br><br>
                                <strong>📐 PANGKAT:</strong><br>
                                • Ketik: <code>x²</code> → Hasil: $x^2$<br>
                                • Ketik: <code>y³</code> → Hasil: $y^3$<br><br>
                                
                                <strong>Cara menulis SUBSCRIPT (huruf/angka kecil di bawah):</strong><br>
                                • <strong>Format underscore:</strong> Ketik <code>H_2O</code> → tampil H₂O<br>
                                • <strong>Contoh kimia:</strong> <code>CO_2</code> → CO₂ &nbsp;|&nbsp; <code>H_2SO_4</code> → H₂SO₄<br>
                                • <strong>Contoh matematika:</strong> <code>x_1</code> → x₁ &nbsp;|&nbsp; <code>a_n</code> → aₙ<br>
                                • <strong>LaTeX manual:</strong> <code>$H_{2}O$</code> → H₂O (untuk yang kompleks)<br>
                                <br>
                                <strong>📊 PERSAMAAN:</strong><br>
                                • Ketik: <code>x = 5</code> → Hasil: $x = 5$<br>
                                • Ketik: <code>y = 10</code> → Hasil: $y = 10$<br>
                            </div>
                            <div>
                                <strong>🎯 FUNGSI:</strong><br>
                                • Ketik: <code>f(x)</code> → Hasil: $f(x)$<br>
                                • Ketik: <code>g(y)</code> → Hasil: $g(y)$<br><br>
                                <strong>✍️ MANUAL LaTeX:</strong><br>
                                • Ketik: <code>$x^2 + 1$</code> → Hasil: $x^2 + 1$<br>
                                • Ketik: <code>$\sqrt{16}$</code> → Hasil: $\sqrt{16}$<br><br>
                                <strong>💡 TIPS:</strong><br>
                                • Fokus pada pecahan dan pangkat<br>
                                • Gunakan preview untuk cek hasil<br>
                                • Jika tidak otomatis, tulis manual LaTeX<br>
                            </div>
                        </div>
                        <div style="margin-top:1rem;padding:0.5rem;background:#dbeafe;border-radius:0.5rem;">
                            <strong>🎯 FOKUS:</strong> Sistem ini fokus pada fitur yang PASTI BERFUNGSI untuk kebutuhan sehari-hari guru matematika!
                        </div>
                    </div>
                </div>

                <!-- Opsi A-E & Jawaban Benar -->
                <div id="optionsSection" style="display:<?= in_array($question['type'], ['multiple_choice','listening','tkp']) ? 'block' : 'none' ?>;">
                    <!-- INPUT CEPAT: Paste semua opsi sekaligus — otomatis terparsing -->
                    <div class="mb-3" id="bulkPasteBox" style="background: #f0fdf4; border: 2px dashed #16a34a; border-radius: 0.75rem; padding: 1rem; transition: border-color 0.2s;">
                        <label class="form-label" style="color: #15803d; font-weight: 700; margin-bottom: 0.4rem;">
                            <i class="fas fa-bolt"></i> Paste Semua Opsi Sekaligus (A–E)
                        </label>
                        <textarea id="bulkOptionsInput" class="form-control" rows="5"
                            placeholder="Paste di sini — otomatis terisi ke A, B, C, D, E&#10;&#10;Contoh:&#10;A. Jakarta&#10;B. Bandung&#10;C. Surabaya&#10;D. Medan&#10;E. Makassar"
                            style="font-size: 0.9rem; resize: vertical;"></textarea>
                        <div style="margin-top: 0.4rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                            <small style="color: #64748b;"><i class="fas fa-info-circle"></i> Format: <code>A. teks</code> / <code>A) teks</code> / atau satu baris per opsi — <strong>langsung otomatis</strong> saat paste</small>
                            <button type="button" id="clearBulkOptions" class="btn btn-sm btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
                                <i class="fas fa-times"></i> Bersihkan
                            </button>
                        </div>
                        <div id="bulkParseStatus" style="display:none; margin-top: 0.5rem; font-size: 0.85rem;"></div>
                    </div>

                    <?php foreach (['A','B','C','D','E'] as $letter):
                    $lc  = strtolower($letter);
                    $val = htmlspecialchars($question['option_' . $lc] ?? '');
                ?>
                <div class="mb-3">
                    <label class="form-label">Opsi <?= $letter ?></label>
                
                    <div class="opt-rte">
                        <div class="opt-toolbar">
                            <div class="opt-fs-group" title="Ukuran teks">
                                <button type="button" onclick="chFSOpt('<?= $lc ?>',-1)" title="Perkecil">−</button>
                                <span class="opt-fs-display" id="opt-fs-<?= $lc ?>">16</span>
                                <button type="button" onclick="chFSOpt('<?= $lc ?>',1)" title="Perbesar">+</button>
                            </div>
                            <div class="rte-opt-divider"></div>
                            <button type="button" class="opt-rte-btn" id="opt-bold-<?= $lc ?>"
                                title="Cetak Tebal (Ctrl+B)" onclick="fmtOpt('<?= $lc ?>','bold')">
                                <i class="fas fa-bold"></i>
                            </button>
                            <button type="button" class="opt-rte-btn" id="opt-italic-<?= $lc ?>"
                                title="Cetak Miring (Ctrl+I)" onclick="fmtOpt('<?= $lc ?>','italic')">
                                <i class="fas fa-italic"></i>
                            </button>
                            <button type="button" class="opt-rte-btn" id="opt-underline-<?= $lc ?>"
                                title="Garis Bawah (Ctrl+U)" onclick="fmtOpt('<?= $lc ?>','underline')">
                                <i class="fas fa-underline"></i>
                            </button>
                        </div>
                
                        <!-- Editor area — konten existing dimuat via JS -->
                        <div id="opt-ed-<?= $lc ?>"
                             class="opt-editor"
                             contenteditable="true"
                             data-ph="Ketik opsi <?= $letter ?>..."
                             data-existing="<?= $val ?>"
                             spellcheck="false"></div>
                    </div>
                
                    <!-- Nilai yang dikirim ke server -->
                    <input type="hidden" name="option_<?= $lc ?>" id="option_<?= $lc ?>" value="<?= $val ?>">
                </div>
                <?php endforeach; ?>
                    <div class="mb-3" id="correctAnswerSection" style="display:<?= $question['type'] === 'tkp' ? 'none' : 'block' ?>;">
                        <label class="form-label">Jawaban Benar</label>
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <select name="correct_answer" class="form-select" style="flex:1;">
                                <?php foreach (['A','B','C','D','E'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= strtoupper($question['correct_answer'] ?? '') === $opt ? 'selected' : '' ?>>
                                        <?= $opt ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label style="margin:0;display:flex;align-items:center;gap:0.5rem;cursor:pointer;white-space:nowrap;">
                                <input type="checkbox" name="hide_correct_answer" value="1"
                                       <?= !empty($question['hide_correct_answer']) ? 'checked' : '' ?>
                                       style="width:20px;height:20px;">
                                <span style="color:#64748b;font-weight:500;">
                                    <i class="fas fa-eye-slash"></i> Sembunyikan
                                </span>
                            </label>
                        </div>
                        <small style="color:#64748b;display:block;margin-top:0.5rem;">
                            <i class="fas fa-info-circle"></i> Centang "Sembunyikan" jika soal akan dikoreksi manual oleh guru
                        </small>
                    </div>
                </div>
                <!-- Section skor TKP -->
                <div id="tkpScoreSection" style="display:<?= $question['type'] === 'tkp' ? 'block' : 'none' ?>; background:#fefce8; border:1px solid #fde047; border-radius:0.75rem; padding:1.25rem; margin-bottom:1rem;">
                    <label class="form-label" style="color:#854d0e; font-weight:700;">
                        <i class="fas fa-sliders-h"></i> Skor Tiap Opsi (Skala Likert)
                    </label>
                    <p style="font-size:0.85rem; color:#a16207; margin-bottom:1rem;">
                        Isi skor untuk masing-masing opsi. Biasanya 1 (paling tidak sesuai) sampai 5 (paling sesuai).
                    </p>
                    <div id="tkpMaxPointsInfo" style="background:white; border:1px solid #fde047; border-radius:0.5rem; padding:0.6rem 1rem; margin-bottom:1rem; font-size:0.85rem; color:#854d0e; font-weight:700;">
                        <i class="fas fa-star"></i> Poin Maksimal Soal Ini (otomatis): <span id="tkpMaxPointsValue"><?= (int)$question['points'] ?></span>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(90px,1fr)); gap:0.75rem;">
                        <?php
                        $existingScores = [];
                        if (!empty($question['option_scores'])) {
                            $decoded = json_decode($question['option_scores'], true);
                            if (is_array($decoded)) $existingScores = $decoded;
                        }
                        foreach (['A','B','C','D','E'] as $letter):
                            $lc = strtolower($letter);
                            $scoreVal = $existingScores[$lc] ?? 0;
                        ?>
                        <div>
                            <label class="form-label" style="font-size:0.8rem;">Skor <?= $letter ?></label>
                            <input type="number" name="option_<?= $lc ?>_score" class="form-control tkp-score-input" min="0" max="10" value="<?= (int)$scoreVal ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Isian Singkat Angka -->
                <div id="shortAnswerSection" style="display:<?= $question['type'] === 'short_answer' ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label class="form-label">Jawaban Benar (Angka)</label>
                        <input type="number" name="correct_answer_number" step="any"
                               class="form-control"
                               value="<?= htmlspecialchars($question['type'] === 'short_answer' ? ($question['correct_answer'] ?? '') : '') ?>"
                               placeholder="Contoh: 42 atau 3.14">
                        <small style="color:#64748b;margin-top:4px;display:block;">
                            <i class="fas fa-info-circle"></i> Masukkan angka yang menjadi jawaban benar
                        </small>
                    </div>
                </div>

                <!-- Isian Singkat Kalimat -->
                <div id="shortAnswerTextSection" style="display:<?= $question['type'] === 'short_answer_text' ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label class="form-label">Jawaban Benar (Kalimat)</label>
                        <input type="text" name="correct_answer_text"
                               class="form-control"
                               value="<?= htmlspecialchars($question['type'] === 'short_answer_text' ? ($question['correct_answer'] ?? '') : '') ?>"
                               placeholder="Contoh: fotosintesis">
                        <small style="color:#64748b;margin-top:4px;display:block;">
                            <i class="fas fa-info-circle"></i> Jawaban dicocokkan tanpa memperhatikan huruf kapital
                        </small>
                    </div>
                </div>

                <!-- Poin -->
                <div class="mb-3" id="points">
                    <label class="form-label">Poin</label>
                    <input type="number" name="points" class="form-control" value="<?= $question['points'] ?>" min="1" required>
                </div>

                <!-- Tombol Aksi -->
                <div style="display:flex;gap:1rem;margin-top:2rem;flex-wrap:wrap;">
                    <button type="submit" name="action" value="update" class="btn btn-primary" id="updateBtn">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <button type="submit" name="action" value="update_and_add" class="btn btn-success" id="updateAddBtn">
                        <i class="fas fa-plus-circle"></i> Update & Tambah Soal Baru
                    </button>
                    <a href="<?= config('app_url') ?>/question" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;justify-content:center;align-items:center;">
        <div style="background:white;padding:2rem 3rem;border-radius:1rem;box-shadow:0 4px 20px rgba(0,0,0,0.3);text-align:center;min-width:300px;">
            <div style="position:relative;width:60px;height:60px;margin:0 auto 1rem;">
                <div style="position:absolute;width:100%;height:100%;border:4px solid #e2e8f0;border-radius:50%;"></div>
                <div style="position:absolute;width:100%;height:100%;border:4px solid #6366f1;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
            </div>
            <p style="margin:0;font-weight:700;color:#1e293b;font-size:1.1rem;">Menyimpan soal...</p>
            <p id="loadingProgress" style="margin:0.5rem 0 0 0;font-size:0.875rem;color:#10b981;font-weight:600;">
                <i class="fas fa-check-circle"></i> Proses cepat, tunggu sebentar
            </p>
        </div>
    </div>

   <style>
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>

    <script>
    // =============================================
    // RICH TEXT TOOLBAR — questionEditor
    // =============================================
    const editor     = document.getElementById('questionEditor');
    const hiddenTA   = document.getElementById('questionText');
    const feedbackEl = document.getElementById('rteFeedback');
    let rteFontSize  = 16;
    let feedbackTimer = null;
    const optFontSizes = { a:16, b:16, c:16, d:16, e:16 };
    
    function encodeBase64(str) {
    return btoa(unescape(encodeURIComponent(str || '')));
    }

    // =============================================
    // AUTO-HITUNG POIN MAKSIMAL UNTUK TKP
    // =============================================
    function recalcTkpMaxPoints() {
        const scoreInputs = document.querySelectorAll('.tkp-score-input');
        let maxScore = 0;
        scoreInputs.forEach(inp => {
            const val = parseInt(inp.value, 10) || 0;
            if (val > maxScore) maxScore = val;
        });
        if (maxScore < 1) maxScore = 1;

        const display = document.getElementById('tkpMaxPointsValue');
        if (display) display.textContent = maxScore;

        const pointsInput = document.querySelector('input[name="points"]');
        if (pointsInput) pointsInput.value = maxScore;
    }

    function applyTkpPointsLock() {
        const points = document.getElementById('points');
        if (!points) return;
        const pointsInput = points.querySelector('input[name="points"]');
        const ptsLabel = points.querySelector('.form-label');
        if (pointsInput) {
            pointsInput.setAttribute('readonly', 'readonly');
            pointsInput.style.background = '#f8fafc';
            pointsInput.style.cursor = 'not-allowed';
        }
        if (ptsLabel) ptsLabel.innerHTML = 'Poin (Otomatis dari Skor Opsi Tertinggi)';
    }

    function releaseTkpPointsLock() {
        const points = document.getElementById('points');
        if (!points) return;
        const pointsInput = points.querySelector('input[name="points"]');
        const ptsLabel = points.querySelector('.form-label');
        if (pointsInput) {
            pointsInput.removeAttribute('readonly');
            pointsInput.style.background = '';
            pointsInput.style.cursor = '';
        }
        if (ptsLabel) ptsLabel.innerHTML = 'Poin';
    }
    function fmtOpt(key, cmd) {
        const ed = document.getElementById('opt-ed-' + key);
        ed.focus();
        document.execCommand(cmd, false, null);
        updateOptBtns(key);
        syncOpt(key);
    }
    
    function updateOptBtns(key) {
        ['bold','italic','underline'].forEach(cmd => {
            const btn = document.getElementById('opt-' + cmd + '-' + key);
            if (btn) btn.classList.toggle('active', document.queryCommandState(cmd));
        });
    }
    
    function chFSOpt(key, delta) {
        optFontSizes[key] = Math.max(8, Math.min(48, optFontSizes[key] + delta));
        document.getElementById('opt-fs-' + key).textContent = optFontSizes[key];
        const ed  = document.getElementById('opt-ed-' + key);
        ed.focus();
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
            const range = sel.getRangeAt(0);
            const span  = document.createElement('span');
            span.style.fontSize = optFontSizes[key] + 'px';
            try { range.surroundContents(span); }
            catch {
                const frag = range.extractContents();
                span.appendChild(frag);
                range.insertNode(span);
            }
            const nr = document.createRange();
            nr.selectNodeContents(span);
            sel.removeAllRanges();
            sel.addRange(nr);
        } else {
            ed.style.fontSize = optFontSizes[key] + 'px';
        }
        syncOpt(key);
    }
    
    function syncOpt(key) {
        const ed     = document.getElementById('opt-ed-' + key);
        const hidden = document.getElementById('option_' + key);
        // Simpan innerHTML ke hidden — akan di-encode base64 saat submit
        if (hidden) hidden.value = ed.innerHTML;
     
        const prev    = document.getElementById('option' + key.toUpperCase() + 'Preview');
        const optsCtn = document.getElementById('optionsPreview');
        if (prev) {
            if (ed.innerText.trim().length > 0) {
                prev.innerHTML = '<strong>' + key.toUpperCase() + '.</strong> ' + ed.innerHTML;
                prev.style.display = 'block';
                if (optsCtn) optsCtn.style.display = 'block';
                clearTimeout(window.optionMathJaxTimeout);
                window.optionMathJaxTimeout = setTimeout(() => {
                    if (typeof MathJax !== 'undefined') MathJax.typesetPromise([prev]).catch(() => {});
                }, 300);
            } else {
                prev.style.display = 'none';
            }
        }
    }
    // *** KUNCI EDIT PAGE: Load teks existing ke editor saat halaman buka ***
    window.addEventListener('DOMContentLoaded', function() {
    // Load teks existing ke editor
        const existingText = hiddenTA.value.trim();
        if (existingText) {
            editor.innerHTML = existingText;
            triggerPreview(editor.innerHTML, editor.innerText);
        }
     
        // Render opsi existing ke preview
        ['A','B','C','D','E'].forEach(letter => {
            const input = document.getElementById('option_' + letter.toLowerCase());
            if (input && input.value.trim().length > 0) {
                const preview = document.getElementById('option' + letter + 'Preview');
                const optionsContainer = document.getElementById('optionsPreview');
                if (preview) {
                    let t = input.value.trim();
                    preview.innerHTML = '<strong>' + letter + '.</strong> ' + t;
                    preview.style.display = 'block';
                    if (optionsContainer) optionsContainer.style.display = 'block';
                }
            }
        });
     
        // MathJax render awal
        setTimeout(() => {
            if (typeof MathJax !== 'undefined') {
                const previewEl = document.getElementById('questionTextPreview');
                const optionsEl = document.getElementById('optionsPreview');
                MathJax.typesetPromise([previewEl, optionsEl]).then(() => {
                    forceBlackOnPreview();
                }).catch(() => {});
            }
        }, 800);
     
        // Setup setiap editor opsi
        ['a','b','c','d','e'].forEach(key => {
            const ed     = document.getElementById('opt-ed-' + key);
            const hidden = document.getElementById('option_' + key);
            if (!ed) return;
     
            // Load nilai existing ke editor
            if (hidden && hidden.value.trim().length > 0) {
                ed.innerHTML = hidden.value;
            }
     
            ed.addEventListener('input',   () => syncOpt(key));
            ed.addEventListener('keyup',   () => updateOptBtns(key));
            ed.addEventListener('mouseup', () => updateOptBtns(key));
            ed.addEventListener('keydown', e => {
                if (e.ctrlKey || e.metaKey) {
                    if (e.key === 'b') { e.preventDefault(); fmtOpt(key, 'bold'); }
                    if (e.key === 'i') { e.preventDefault(); fmtOpt(key, 'italic'); }
                    if (e.key === 'u') { e.preventDefault(); fmtOpt(key, 'underline'); }
                }
            });
        });

        // Setup skor TKP: listener + kunci poin jika tipe soal saat ini TKP
        document.querySelectorAll('.tkp-score-input').forEach(inp => {
            inp.addEventListener('input', recalcTkpMaxPoints);
        });
        const currentType = document.getElementById('questionType').value;
        if (currentType === 'tkp') {
            applyTkpPointsLock();
            recalcTkpMaxPoints();
        }
    });

    // Paksa warna hitam pada semua elemen SVG/MathJax di preview
    function forceBlackOnPreview() {
        const preview = document.getElementById('questionTextPreview');
        const optionsPreview = document.getElementById('optionsPreview');
        [preview, optionsPreview].forEach(el => {
            if (!el) return;
            el.querySelectorAll('svg path, svg use, mjx-container').forEach(node => {
                if (node.style) node.style.fill = '#1e293b';
                if (node.setAttribute) node.setAttribute('fill', '#1e293b');
            });
        });
    }
     
    function syncToTextarea() {
        // Tidak di-encode di sini — encoding hanya saat submit
        hiddenTA.value = editor.innerHTML;
        triggerPreview(editor.innerHTML, editor.innerText);
    }
    editor.addEventListener('input', syncToTextarea);
     
    function showFeedback(msg, isError = false) {
        clearTimeout(feedbackTimer);
        feedbackEl.textContent = msg;
        feedbackEl.className = 'rte-feedback show' + (isError ? ' error' : '');
        feedbackTimer = setTimeout(() => { feedbackEl.className = 'rte-feedback'; }, 2200);
    }
     
    function rteFormat(cmd) {
        editor.focus();
        document.execCommand(cmd, false, null);
        updateToolbarState();
        triggerPreview(editor.innerHTML, editor.innerText);
        hiddenTA.value = editor.innerHTML;
    }
     
    function updateToolbarState() {
        ['bold', 'italic', 'underline'].forEach(cmd => {
            const btn = document.getElementById('rteBtn' + cmd.charAt(0).toUpperCase() + cmd.slice(1));
            if (btn) btn.classList.toggle('active', document.queryCommandState(cmd));
        });
    }
    editor.addEventListener('keyup',   updateToolbarState);
    editor.addEventListener('mouseup', updateToolbarState);
     
    editor.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            if (e.key === 'b') { e.preventDefault(); rteFormat('bold'); }
            if (e.key === 'i') { e.preventDefault(); rteFormat('italic'); }
            if (e.key === 'u') { e.preventDefault(); rteFormat('underline'); }
        }
    });
     
    function rteChangeFontSize(delta) {
        rteFontSize = Math.max(8, Math.min(48, rteFontSize + delta));
        document.getElementById('rteFsDisplay').textContent = rteFontSize;
        editor.focus();
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
            const range = sel.getRangeAt(0);
            const span  = document.createElement('span');
            span.style.fontSize = rteFontSize + 'px';
            try { range.surroundContents(span); }
            catch (err) {
                const frag = range.extractContents();
                span.appendChild(frag);
                range.insertNode(span);
            }
            const nr = document.createRange();
            nr.selectNodeContents(span);
            sel.removeAllRanges();
            sel.addRange(nr);
        } else {
            editor.style.fontSize = rteFontSize + 'px';
        }
        triggerPreview(editor.innerHTML, editor.innerText);
        hiddenTA.value = editor.innerHTML;
    }
     
    async function rteCopy() {
        editor.focus();
        const sel  = window.getSelection();
        const text = (sel && !sel.isCollapsed) ? sel.toString() : editor.innerText;
        try {
            await navigator.clipboard.writeText(text);
            showFeedback('✓ Tersalin ke clipboard!');
        } catch {
            document.execCommand('copy');
            showFeedback('✓ Tersalin!');
        }
    }
     
    async function rtePaste() {
        editor.focus();
        try {
            const text = await navigator.clipboard.readText();
            document.execCommand('insertText', false, text);
            syncToTextarea();
            showFeedback('✓ Tempel berhasil!');
        } catch {
            showFeedback('Tekan Ctrl+V untuk tempel', true);
        }
    }
     
    document.addEventListener('paste', function(e) {
        const items = e.clipboardData.items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                e.preventDefault();
                handleImageFile(items[i].getAsFile());
                return;
            }
        }
        if (document.activeElement === editor || editor.contains(document.activeElement)) {
            const html = e.clipboardData.getData('text/html');
            if (html) {
                e.preventDefault();
                document.execCommand('insertHTML', false, cleanWordHtml(html));
                syncToTextarea();
                return;
            }
        }
    });

    // =============================================
    // FILTER WORD HTML — hapus semua tag & metadata dari Microsoft Word/Excel
    // =============================================
    function cleanWordHtml(html) {
        if (!html) return '';
        html = html.replace(/<head[^>]*>[\s\S]*?<\/head>/gi, '');
        html = html.replace(/<meta[^>]*>/gi, '');
        html = html.replace(/<link[^>]*>/gi, '');
        html = html.replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '');
        html = html.replace(/<xml[^>]*>[\s\S]*?<\/xml>/gi, '');
        html = html.replace(/<\/?[owm]:[^>]*>/gi, '');
        html = html.replace(/<!--\[if[^\]]*\]>[\s\S]*?<!\[endif\]-->/gi, '');
        html = html.replace(/<!--[\s\S]*?-->/g, '');
        html = html.replace(/\s*class="Mso[^"]*"/gi, '');
        html = html.replace(/\s*class='Mso[^']*'/gi, '');
        html = html.replace(/\s*lang="[^"]*"/gi, '');
        html = html.replace(/\s*xmlns[^=]*="[^"]*"/gi, '');
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        tmp.querySelectorAll('span, p, div').forEach(el => {
            if (!el.innerHTML.trim() && !el.querySelector('img')) el.remove();
        });
        tmp.querySelectorAll('[style]').forEach(el => {
            const s = el.style;
            const c = s.color.toLowerCase().replace(/\s/g, '');
            if (['black','#000000','#000','rgb(0,0,0)','windowtext'].includes(c)) s.removeProperty('color');
            const bg = s.backgroundColor.toLowerCase().replace(/\s/g, '');
            if (['white','#ffffff','rgb(255,255,255)'].includes(bg)) s.removeProperty('background-color');
            let styleVal = el.getAttribute('style') || '';
            styleVal = styleVal.replace(/mso-[^;]+;?/gi, '').trim();
            if (styleVal) el.setAttribute('style', styleVal);
            else el.removeAttribute('style');
        });
        return tmp.innerHTML;
    }
     
    function triggerPreview(richHtml, plainText) {
        const preview = document.getElementById('questionTextPreview');
        if (!plainText || !plainText.trim()) {
            preview.innerHTML = '<em style="color:#94a3b8;">Preview soal akan muncul di sini...</em>';
            return;
        }
        function applyMath(str) {
            str = str.replace(/(\d+)\/(\d+)/g, '$\\frac{$1}{$2}$');
            str = str.replace(/([a-zA-Z])²/g, '$$1^2$');
            str = str.replace(/([a-zA-Z])³/g, '$$1^3$');
            ['₀','₁','₂','₃','₄','₅','₆','₇','₈','₉'].forEach((ch, idx) => {
                const re = new RegExp('([a-zA-Z0-9])' + ch.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
                str = str.replace(re, '$$1_' + idx + '$');
            });
            str = str.replace(/([a-zA-Z0-9])_(\d+)/g, '$$1_{$2}$');
            str = str.replace(/([a-zA-Z])\s*=\s*(\d+)/g, '$$1 = $2$');
            str = str.replace(/([fgh])\s*\(\s*([xy])\s*\)/g, '$$1($2)$');
            return str;
        }
        let processed = richHtml.replace(/>([^<]+)</g, (match, textContent) => {
            return '>' + applyMath(textContent) + '<';
        });
        if (!processed.startsWith('<')) processed = applyMath(processed);
        preview.innerHTML = processed;
        clearTimeout(window.mathJaxTimeout);
        window.mathJaxTimeout = setTimeout(() => {
            if (typeof MathJax !== 'undefined') {
                MathJax.typesetPromise([preview]).then(() => {
                    preview.querySelectorAll('svg path, svg use, mjx-container').forEach(el => {
                        if (el.style) el.style.fill = '#1e293b';
                        if (el.setAttribute) el.setAttribute('fill', '#1e293b');
                    });
                }).catch(() => {});
            }
        }, 500);
    }

    // =============================================
    // QUESTION TYPE TOGGLE
    // =============================================
    document.getElementById('questionType').addEventListener('change', function() {
        const optionsSection         = document.getElementById('optionsSection');
        const shortAnswerSection     = document.getElementById('shortAnswerSection');
        const shortAnswerTextSection = document.getElementById('shortAnswerTextSection');
        const MatematikaSection      = document.getElementById('MatematikaSection');
        const PanduanSection         = document.getElementById('PanduanMatematikaSection');
        const questionTextSection    = document.getElementById('questionTextSection');
        const listeningSection       = document.getElementById('listeningSection');
        const audioInput             = document.getElementById('audioFile');
        const imageSection           = document.getElementById('imageSection');
        const tkpScoreSection        = document.getElementById('tkpScoreSection');
        const correctAnswerSection   = document.getElementById('correctAnswerSection');
    
        listeningSection.style.display = 'none';
        imageSection.style.display = 'block';
        tkpScoreSection.style.display = 'none';
        correctAnswerSection.style.display = 'block';
        if (audioInput) audioInput.removeAttribute('required');
    
        if (this.value === 'essay') {
            optionsSection.style.display = 'none';
            shortAnswerSection.style.display = 'none';
            shortAnswerTextSection.style.display = 'none';
            MatematikaSection.style.display = 'none';
            PanduanSection.style.display = 'none';
            questionTextSection.style.display = 'block';
        } else if (this.value === 'short_answer') {
            optionsSection.style.display = 'none';
            shortAnswerSection.style.display = 'block';
            shortAnswerTextSection.style.display = 'none';
            MatematikaSection.style.display = 'none';
            PanduanSection.style.display = 'none';
            questionTextSection.style.display = 'block';
        } else if (this.value === 'short_answer_text') {
            optionsSection.style.display = 'none';
            shortAnswerSection.style.display = 'none';
            shortAnswerTextSection.style.display = 'block';
            MatematikaSection.style.display = 'none';
            PanduanSection.style.display = 'none';
            questionTextSection.style.display = 'block';
        } else if (this.value === 'listening') {
            optionsSection.style.display = 'block';
            shortAnswerSection.style.display = 'none';
            shortAnswerTextSection.style.display = 'none';
            MatematikaSection.style.display = 'none';
            PanduanSection.style.display = 'none';
            questionTextSection.style.display = 'block';
            listeningSection.style.display = 'block';
            if (audioInput) audioInput.setAttribute('required', 'required');
            imageSection.style.display = 'none';
        } else if (this.value === 'tkp') {
            optionsSection.style.display = 'block';
            shortAnswerSection.style.display = 'none';
            shortAnswerTextSection.style.display = 'none';
            MatematikaSection.style.display = 'none';
            PanduanSection.style.display = 'none';
            questionTextSection.style.display = 'block';
            tkpScoreSection.style.display = 'block';
            correctAnswerSection.style.display = 'none';
            applyTkpPointsLock();
            recalcTkpMaxPoints();
        } else {
            optionsSection.style.display = 'block';
            shortAnswerSection.style.display = 'none';
            shortAnswerTextSection.style.display = 'none';
            MatematikaSection.style.display = 'block';
            PanduanSection.style.display = 'block';
            questionTextSection.style.display = 'block';
            releaseTkpPointsLock();
        }
    });
    // Audio preview
    const audioFileEl = document.getElementById('audioFile');
    if (audioFileEl) {
        audioFileEl.addEventListener('change', function() {
            const previewWrap = document.getElementById('audioPreviewWrap');
            const preview     = document.getElementById('audioPreview');
            if (this.files && this.files[0]) {
                if (this.files[0].size > 20 * 1024 * 1024) {
                    alert('Ukuran file audio maksimal 20MB!');
                    this.value = '';
                    previewWrap.style.display = 'none';
                    return;
                }
                preview.src = URL.createObjectURL(this.files[0]);
                previewWrap.style.display = 'block';
            } else {
                preview.src = '';
                previewWrap.style.display = 'none';
            }
        });
    }

    // Ganti audio
    function gantiAudio() {
        document.getElementById('existingAudioContainer').style.display = 'none';
        document.getElementById('audioUploadWrap').style.display = 'block';
    }

    // Option previews
    function updateOptionPreview(optionLetter) {
        const input   = document.querySelector(`input[name="option_${optionLetter.toLowerCase()}"]`);
        const preview = document.getElementById(`option${optionLetter}Preview`);
        const optionsContainer = document.getElementById('optionsPreview');
        if (input && preview) {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    let t = this.value.trim().replace(/^[A-E]\.?\s*/, '');
                    t = t.replace(/(\d+)\/(\d+)/g, '$\\frac{$1}{$2}$');
                    t = t.replace(/([a-zA-Z])²/g, '$$1^2$');
                    t = t.replace(/([a-zA-Z])³/g, '$$1^3$');
                    t = t.replace(/([a-zA-Z0-9])_(\d)/g, '$$1_{$2}$');
                    t = t.replace(/([a-zA-Z])\s*=\s*(\d+)/g, '$$1 = $2$');
                    preview.innerHTML = `<strong>${optionLetter}.</strong> ${t}`;
                    preview.style.display = 'block';
                    if (optionsContainer) optionsContainer.style.display = 'block';
                    clearTimeout(window.optionMathJaxTimeout);
                    window.optionMathJaxTimeout = setTimeout(() => {
                        if (typeof MathJax !== 'undefined') {
                            MathJax.typesetPromise([preview]).then(() => {
                                preview.querySelectorAll('svg path, svg use, mjx-container').forEach(el => {
                                    if (el.style) el.style.fill = '#1e293b';
                                    if (el.setAttribute) el.setAttribute('fill', '#1e293b');
                                });
                            }).catch(() => {});
                        }
                    }, 300);
                } else {
                    preview.style.display = 'none';
                }
            });
        }
    }
    ['A','B','C','D','E'].forEach(updateOptionPreview);

    // Image upload
    const imageUploadArea       = document.getElementById('imageUploadArea');
    const imageInput            = document.getElementById('questionImageInput');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreviewEl        = document.getElementById('imagePreview');
    const removeImageBtn        = document.getElementById('removeImageBtn');

    imageUploadArea.addEventListener('click', () => imageInput.click());
    imageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) handleImageFile(this.files[0]);
    });
    imageUploadArea.addEventListener('dragover', e => { e.preventDefault(); imageUploadArea.style.borderColor = '#6366f1'; });
    imageUploadArea.addEventListener('dragleave', () => { imageUploadArea.style.borderColor = '#e2e8f0'; });
    imageUploadArea.addEventListener('drop', e => {
        e.preventDefault();
        imageUploadArea.style.borderColor = '#e2e8f0';
        if (e.dataTransfer.files && e.dataTransfer.files[0]) handleImageFile(e.dataTransfer.files[0]);
    });

    function handleImageFile(file) {
        if (!file.type.match('image.*')) { alert('File harus berupa gambar!'); return; }
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        imageInput.files = dataTransfer.files;
        const reader = new FileReader();
        reader.onload = e => {
            imagePreviewEl.src = e.target.result;
            imagePreviewContainer.style.display = 'block';
            imageUploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', () => {
            imageInput.value = '';
            imagePreviewEl.src = '';
            imagePreviewContainer.style.display = 'none';
            imageUploadArea.style.display = 'block';
        });
    }

    // Ganti / Hapus gambar existing
    const changeImageBtn = document.getElementById('changeImageBtn');
    const deleteImageBtn = document.getElementById('deleteImageBtn');
    if (changeImageBtn) {
        changeImageBtn.addEventListener('click', () => {
            document.getElementById('existingImageContainer').style.display = 'none';
            imageUploadArea.style.display = 'block';
        });
    }
    if (deleteImageBtn) {
        deleteImageBtn.addEventListener('click', () => {
            if (confirm('Hapus gambar ini?')) {
                document.getElementById('existingImageContainer').style.display = 'none';
                document.getElementById('deleteImageFlag').value = '1';
            }
        });
    }

    // Sync editor sebelum submit
    document.getElementById('mainForm').addEventListener('submit', function() {
        // Sync + encode question_text
        hiddenTA.value = encodeBase64(editor.innerHTML);
     
        // Sync + encode semua opsi
        ['a','b','c','d','e'].forEach(key => {
            const ed     = document.getElementById('opt-ed-' + key);
            const hidden = document.getElementById('option_' + key);
            if (ed && hidden) {
                hidden.value = encodeBase64(ed.innerHTML);
            }
        });
     
        // Loading indicator
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(btn => { btn.disabled = true; btn.style.opacity = '0.6'; });
        const loadingIndicator = document.getElementById('loadingIndicator');
        const loadingProgress  = document.getElementById('loadingProgress');
        loadingIndicator.style.display = 'flex';
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += 20;
            if (progress <= 60) {
                loadingProgress.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses data...';
            } else {
                loadingProgress.innerHTML = '<i class="fas fa-check-circle"></i> Hampir selesai...';
            }
            if (progress >= 100) clearInterval(progressInterval);
        }, 200);
        setTimeout(() => {
            clearInterval(progressInterval);
            loadingIndicator.style.display = 'none';
            submitButtons.forEach(btn => { btn.disabled = false; btn.style.opacity = '1'; });
        }, 5000);
    });

    // Bulk options parser
    function parseBulkOptions(text) {
        const results = { a:'', b:'', c:'', d:'', e:'' };
        if (!text.trim()) return results;
        const keys = ['a','b','c','d','e'];
        const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        const prefixPattern = /^[\(\[]?([AaBbCcDdEe])[\)\]\.)\s]+\s*(.+)/;
        let matched = 0;
        const tempResults = { a:'', b:'', c:'', d:'', e:'' };
        lines.forEach(line => {
            const m = line.match(prefixPattern);
            if (m) {
                const key = m[1].toLowerCase();
                if (keys.includes(key) && !tempResults[key]) { tempResults[key] = m[2].trim(); matched++; }
            }
        });
        if (matched >= 2) return tempResults;
        lines.forEach((line, idx) => { if (idx < 5) results[keys[idx]] = line; });
        return results;
    }

    function applyBulkOptions(text) {
        const parsed   = parseBulkOptions(text);
        const statusEl = document.getElementById('bulkParseStatus');
        const box      = document.getElementById('bulkPasteBox');
        let filled = 0;
    
        ['a','b','c','d','e'].forEach(k => {
            // ← izinkan nilai "0", cek null/undefined/string kosong saja
            if (parsed[k] === '' || parsed[k] === undefined || parsed[k] === null) return;
            const ed = document.getElementById('opt-ed-' + k);
            if (ed) {
                ed.innerText = parsed[k];
                ed.style.borderColor = '#16a34a';
                ed.style.background  = '#f0fdf4';
                filled++;
                setTimeout(() => { ed.style.borderColor = ''; ed.style.background = ''; }, 1500);
                syncOpt(k);
            }
        });
    
        statusEl.style.display = 'block';
        if (filled > 0) {
            box.style.borderColor = '#16a34a';
            statusEl.innerHTML = '<span style="color:#15803d;font-weight:600;"><i class="fas fa-check-circle"></i> ' + filled + ' opsi berhasil diisi otomatis!</span>';
        } else {
            box.style.borderColor = '#f59e0b';
            statusEl.innerHTML = '<span style="color:#b45309;font-weight:600;"><i class="fas fa-exclamation-triangle"></i> Format tidak dikenali.</span>';
        }
    }

    document.getElementById('bulkOptionsInput').addEventListener('paste', function() {
        setTimeout(() => applyBulkOptions(this.value), 50);
    });
    let bulkTypingTimer;
    document.getElementById('bulkOptionsInput').addEventListener('input', function() {
        clearTimeout(bulkTypingTimer);
        const val = this.value;
        bulkTypingTimer = setTimeout(() => { if (val.trim()) applyBulkOptions(val); }, 400);
    });
    document.getElementById('clearBulkOptions').addEventListener('click', function() {
    document.getElementById('bulkOptionsInput').value = '';
    document.getElementById('bulkParseStatus').style.display = 'none';
    document.getElementById('bulkPasteBox').style.borderColor = '#16a34a';
    ['a','b','c','d','e'].forEach(k => {
        const ed     = document.getElementById('opt-ed-' + k);
        const hidden = document.getElementById('option_' + k);
        if (ed)     ed.innerHTML = '';
        if (hidden) hidden.value = '';
        const prev = document.getElementById('option' + k.toUpperCase() + 'Preview');
        if (prev) prev.style.display = 'none';
    });
    document.getElementById('optionsPreview').style.display = 'none';
    });
    </script>
</body>
</html>