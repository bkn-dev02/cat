<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($group['name']) ?> - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
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
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 1rem 0; border-bottom: 1px solid #e2e8f0; }
        .navbar-brand { font-weight: 800; font-size: 1.5rem; color: #6366f1; }
        .main-content { padding: 2rem 0; max-width: 1000px; margin: 0 auto; }

        /* Group Header */
        .group-header { background: white; border-radius: 1rem; padding: 1.5rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .group-title { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem 0; }
        .group-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; font-size: 0.82rem; color: #64748b; }
        .meta-badge { display: inline-flex; align-items: center; gap: 0.3rem; background: #f1f5f9; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .meta-badge.purple { background: #ede9fe; color: #6d28d9; }
        .meta-badge.green { background: #d1fae5; color: #065f46; }
        .meta-badge.blue { background: #dbeafe; color: #1e40af; }

        /* Add Question Form */
        .add-form-card { background: #f0fdf4; border: 2px dashed #16a34a; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        .add-form-title { font-size: 1rem; font-weight: 700; color: #15803d; margin: 0 0 1rem 0; }
        .form-label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem; font-size: 0.875rem; }
        .form-control, .form-select { width: 100%; padding: 0.65rem 0.9rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.95rem; transition: all 0.2s; background: white; }
        .form-control:focus, .form-select:focus { outline: none; border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }

        /* Question List */
        .question-card { background: white; border-radius: 0.75rem; border: 1px solid #e2e8f0; padding: 1rem 1.25rem; margin-bottom: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .question-card:hover { border-color: #c7d2fe; }
        .q-number { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #6366f1; color: white; border-radius: 50%; font-size: 0.8rem; font-weight: 700; flex-shrink: 0; }
        .q-text { color: #1e293b; font-size: 0.95rem; flex: 1; }
        .q-answer { font-size: 0.8rem; color: #16a34a; font-weight: 600; }
        .q-actions { display: flex; gap: 0.35rem; flex-wrap: wrap; }
        .btn-xs { padding: 0.25rem 0.6rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; }
        .btn-edit-q { background: #fef3c7; color: #92400e; }
        .btn-edit-q:hover { background: #fde68a; color: #92400e; }
        .btn-copy-q { background: #dbeafe; color: #1e40af; }
        .btn-copy-q:hover { background: #bfdbfe; color: #1e40af; }
        .btn-move-q { background: #ede9fe; color: #6d28d9; }
        .btn-move-q:hover { background: #ddd6fe; color: #6d28d9; }
        .btn-remove-q { background: #fee2e2; color: #991b1b; }
        .btn-remove-q:hover { background: #fecaca; color: #991b1b; }

        /* Buttons */
        .btn { padding: 0.65rem 1.2rem; border-radius: 0.5rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; color: white; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; color: white; }
        .btn-secondary { background: white; color: #1e293b; border: 1px solid #e2e8f0; }
        .btn-back { display: inline-flex; align-items: center; gap: 0.5rem; color: #64748b; text-decoration: none; margin-bottom: 1.5rem; font-weight: 600; }
        .btn-back:hover { color: #6366f1; }

        .alert-success { background: #d1fae5; color: #065f46; border: none; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: none; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; }
        .empty-q { text-align: center; padding: 2.5rem; color: #94a3b8; background: white; border-radius: 0.75rem; border: 1px dashed #e2e8f0; }

        /* Bulk paste */
        .bulk-box { background: #f0fdf4; border: 2px dashed #16a34a; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem; }

        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
    </style>
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <a href="<?= config('app_url') ?>/questionGroup" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Grup
        </a>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Group Header -->
        <div class="group-header">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h1 class="group-title"><i class="fas fa-layer-group" style="color:#6366f1;"></i> <?= htmlspecialchars($group['name']) ?></h1>
                    <?php if ($group['description']): ?>
                        <p style="color:#64748b; margin:0 0 0.75rem 0; font-size:0.875rem;"><?= htmlspecialchars($group['description']) ?></p>
                    <?php endif; ?>
                    <div class="group-meta">
                        <span class="meta-badge purple"><i class="fas fa-book"></i> <?= htmlspecialchars($group['subject_name']) ?></span>
                        <?php
                        $typeLabels = [
                            'multiple_choice'   => 'Pilihan Ganda',
                            'short_answer'      => 'Isian Angka',
                            'short_answer_text' => 'Isian Kalimat',
                            'essay'             => 'Essay',
                            'listening'         => 'Listening',
                            'tkp'               => '📊 TKP (Skala Likert)',
                        ];
                        ?>
                        <span class="meta-badge blue"><i class="fas fa-list"></i> <?= $typeLabels[$group['type']] ?? $group['type'] ?></span>
                        <?php if ($group['sub_topic']): ?>
                            <span class="meta-badge"><i class="fas fa-tag"></i> <?= htmlspecialchars($group['sub_topic']) ?></span>
                        <?php endif; ?>
                        <span class="meta-badge green"><i class="fas fa-star"></i> <?= $group['default_points'] ?> poin/soal</span>
                        <span class="meta-badge"><i class="fas fa-list-ol"></i> <?= count($questions) ?> soal</span>
                    </div>
                </div>
                <a href="<?= config('app_url') ?>/questionGroup/edit/<?= $group['id'] ?>" class="btn btn-secondary" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                    <i class="fas fa-edit"></i> Edit Grup
                </a>
            </div>
        </div>

        <!-- ADD QUESTION FORM -->
        <div class="add-form-card">
            <p class="add-form-title"><i class="fas fa-plus-circle"></i> Tambah Soal ke Grup Ini</p>
            <p style="font-size:0.82rem; color:#15803d; margin:-0.5rem 0 1rem 0;">
                Identitas sudah terisi otomatis dari grup: <strong><?= htmlspecialchars($group['subject_name']) ?></strong> · <strong><?= $typeLabels[$group['type']] ?? $group['type'] ?></strong>
                <?php if ($group['sub_topic']): ?> · <strong><?= htmlspecialchars($group['sub_topic']) ?></strong><?php endif; ?>
            </p>

            <form method="POST" action="<?= config('app_url') ?>/question/createInGroup/<?= $group['id'] ?>" enctype="multipart/form-data">
                <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                <input type="hidden" name="subject_id" value="<?= $group['subject_id'] ?>">
                <input type="hidden" name="type" value="<?= $group['type'] ?>">
                <input type="hidden" name="sub_topic" value="<?= htmlspecialchars($group['sub_topic'] ?? '') ?>">
                <input type="hidden" name="points" value="<?= $group['default_points'] ?>">

                <!-- Soal Text -->
                <?php if ($group['type'] !== 'listening'): ?>
                <div class="mb-3">
                    <label class="form-label">Teks Soal <span style="color:#dc2626;">*</span></label>
                    <textarea name="question_text" class="form-control" rows="3" required
                              placeholder="Ketik soal di sini..."></textarea>
                </div>
                <?php endif; ?>

                <!-- Listening Audio -->
                <?php if ($group['type'] === 'listening'): ?>
                <div class="mb-3" style="background:#f0f7ff; border:1.5px dashed #6ea8d8; border-radius:8px; padding:12px 16px;">
                    <label class="form-label"><i class="fas fa-headphones"></i> Upload Audio</label>
                    <input type="file" name="audio_file" class="form-control" accept="audio/*" required>
                    <input type="hidden" name="max_plays" value="2">
                    <small style="color:#64748b;">Format: MP3, WAV, OGG, M4A (maks. 20MB)</small>
                </div>
                <?php endif; ?>

                <!-- Gambar Soal -->
                <?php if ($group['type'] !== 'listening'): ?>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-image"></i> Gambar Soal (Opsional)</label>
                    <input type="file" name="question_image" class="form-control" accept="image/*">
                </div>
                <?php endif; ?>
                
                <?php if (in_array($group['type'], ['multiple_choice', 'listening', 'tkp'])): ?>
                <div class="mb-3">
                    <!-- Bulk Paste -->
                    <div class="bulk-box">
                        <label class="form-label" style="color:#15803d; margin-bottom:0.3rem;">
                            <i class="fas fa-bolt"></i> Paste Semua Opsi Sekaligus (A–E)
                        </label>
                        <textarea id="bulkOptionsInput" class="form-control" rows="5"
                            placeholder="Paste di sini — otomatis terisi ke A, B, C, D, E&#10;&#10;Contoh:&#10;A. Jakarta&#10;B. Bandung&#10;C. Surabaya&#10;D. Medan&#10;E. Makassar"
                            style="font-size:0.875rem;"></textarea>
                        <small style="color:#64748b; margin-top:0.3rem; display:block;">
                            <i class="fas fa-info-circle"></i> Format: <code>A. teks</code> / <code>A) teks</code> / satu baris per opsi — <strong>otomatis</strong> saat paste
                        </small>
                        <div id="bulkParseStatus" style="display:none; margin-top:0.4rem; font-size:0.82rem;"></div>
                    </div>
                
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div>
                            <label class="form-label">Opsi A</label>
                            <input type="text" name="option_a" id="option_a" class="form-control" placeholder="Opsi A">
                        </div>
                        <div>
                            <label class="form-label">Opsi B</label>
                            <input type="text" name="option_b" id="option_b" class="form-control" placeholder="Opsi B">
                        </div>
                        <div>
                            <label class="form-label">Opsi C</label>
                            <input type="text" name="option_c" id="option_c" class="form-control" placeholder="Opsi C">
                        </div>
                        <div>
                            <label class="form-label">Opsi D</label>
                            <input type="text" name="option_d" id="option_d" class="form-control" placeholder="Opsi D">
                        </div>
                        <div>
                            <label class="form-label">Opsi E (Opsional)</label>
                            <input type="text" name="option_e" id="option_e" class="form-control" placeholder="Opsi E">
                        </div>
                        <?php if ($group['type'] !== 'tkp'): ?>
                        <div>
                            <label class="form-label">Jawaban Benar</label>
                            <select name="correct_answer" class="form-select">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                
                    <?php if ($group['type'] === 'tkp'): ?>
                    <div style="margin-top:1rem; background:#fefce8; border:1px solid #fde047; border-radius:0.75rem; padding:1rem;">
                        <label class="form-label" style="color:#854d0e;">
                            <i class="fas fa-sliders-h"></i> Skor Tiap Opsi (Skala Likert)
                        </label>
                        <p style="font-size:0.8rem; color:#a16207; margin-bottom:0.75rem;">Biasanya 1 (paling tidak sesuai) sampai 5 (paling sesuai). Poin soal otomatis mengikuti skor tertinggi.</p>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(80px,1fr)); gap:0.6rem;">
                            <?php foreach (['A','B','C','D','E'] as $letter): $lc = strtolower($letter); ?>
                            <div>
                                <label class="form-label" style="font-size:0.78rem;">Skor <?= $letter ?></label>
                                <input type="number" name="option_<?= $lc ?>_score" class="form-control tkp-score-input" min="0" max="10" value="0">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Isian Singkat Angka -->
                <?php if ($group['type'] === 'short_answer'): ?>
                <div class="mb-3">
                    <label class="form-label">Kunci Jawaban (Angka) <span style="color:#dc2626;">*</span></label>
                    <input type="number" name="short_answer_correct" class="form-control" step="any" required placeholder="Contoh: 42 atau 3.14">
                </div>
                <?php endif; ?>

                <!-- Isian Singkat Kalimat -->
                <?php if ($group['type'] === 'short_answer_text'): ?>
                <div class="mb-3">
                    <label class="form-label">Kunci Jawaban (Kalimat) <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="correct_answer_text" class="form-control" required placeholder="Jawaban yang benar...">
                </div>
                <?php endif; ?>

                <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-top:0.5rem;">
                    <button type="submit" name="action" value="save_and_add" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Simpan & Tambah Lagi
                    </button>
                    <button type="submit" name="action" value="save" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Saja
                    </button>
                </div>
            </form>
        </div>

        <!-- QUESTION LIST -->
        <div>
            <h2 style="font-size:1.1rem; font-weight:700; color:#1e293b; margin-bottom:1rem;">
                <i class="fas fa-list-ol" style="color:#6366f1;"></i> Soal dalam Grup
                <span style="background:#6366f1; color:white; padding:2px 10px; border-radius:20px; font-size:0.8rem; margin-left:0.5rem;"><?= count($questions) ?></span>
            </h2>

            <?php if (empty($questions)): ?>
                <div class="empty-q">
                    <i class="fas fa-inbox" style="font-size:2.5rem; margin-bottom:0.75rem; display:block;"></i>
                    <p style="font-weight:600; color:#475569;">Belum ada soal di grup ini</p>
                    <p style="font-size:0.875rem;">Gunakan form di atas untuk menambahkan soal pertama.</p>
                </div>
            <?php else: ?>
                <?php foreach ($questions as $i => $q): ?>
                    <div class="question-card">
                        <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                            <span class="q-number"><?= $i + 1 ?></span>
                            <div style="flex:1; min-width:0;">
                                <div class="q-text" style="margin-bottom:0.3rem;">
                                    <?php if ($q['type'] === 'listening'): ?>
                                        <em style="color:#6ea8d8;"><i class="fas fa-headphones"></i> Soal Listening</em>
                                    <?php else: ?>
                                        <?= nl2br(htmlspecialchars(mb_substr($q['question_text'], 0, 150))) ?><?= mb_strlen($q['question_text']) > 150 ? '...' : '' ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (in_array($q['type'], ['multiple_choice', 'listening', 'tkp']) && $q['option_a']): ?>
                                    <div style="font-size:0.78rem; color:#64748b; margin-bottom:0.3rem;">
                                        A. <?= htmlspecialchars(mb_substr($q['option_a'], 0, 40)) ?>
                                        <?php if ($q['option_b']): ?> &nbsp;|&nbsp; B. <?= htmlspecialchars(mb_substr($q['option_b'], 0, 40)) ?><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($q['type'] === 'tkp' && !empty($q['option_scores'])):
                                    $sc = json_decode($q['option_scores'], true) ?: [];
                                ?>
                                    <span class="q-answer" style="color:#9d174d;">
                                        <i class="fas fa-chart-bar"></i> Skor:
                                        <?php foreach (['a','b','c','d','e'] as $k): ?>
                                            <?= strtoupper($k) ?>:<?= (int)($sc[$k] ?? 0) ?> &nbsp;
                                        <?php endforeach; ?>
                                    </span>
                                <?php elseif ($q['correct_answer'] && empty($q['hide_correct_answer'])): ?>
                                    <span class="q-answer"><i class="fas fa-check-circle"></i> Jawaban: <?= htmlspecialchars($q['correct_answer']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="q-actions">
                                <a href="<?= config('app_url') ?>/question/edit/<?= $q['id'] ?>" class="btn-xs btn-edit-q" title="Edit soal">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= config('app_url') ?>/questionGroup/copyQuestion/<?= $q['id'] ?>?from=<?= $group['id'] ?>" class="btn-xs btn-copy-q" title="Copy ke grup lain">
                                    <i class="fas fa-copy"></i>
                                </a>
                                <a href="<?= config('app_url') ?>/questionGroup/moveQuestion/<?= $q['id'] ?>?from=<?= $group['id'] ?>" class="btn-xs btn-move-q" title="Pindah ke grup lain">
                                    <i class="fas fa-exchange-alt"></i>
                                </a>
                                <button onclick="confirmRemove(<?= $q['id'] ?>, <?= $group['id'] ?>)" class="btn-xs btn-remove-q" title="Lepas dari grup">
                                    <i class="fas fa-unlink"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $q['id'] ?>, <?= $group['id'] ?>)" class="btn-xs" style="background:#dc2626;color:white;" title="Hapus permanen dari bank soal">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ============================================
        // BULK OPTIONS PARSER — FULLY AUTOMATIC
        // ============================================
        function parseBulkOptions(text) {
            const results = { a:'', b:'', c:'', d:'', e:'' };
            if (!text.trim()) return results;
            const keys = ['a','b','c','d','e'];
            const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);
            const prefixPattern = /^[\(\[]?([AaBbCcDdEe])[\)\]\.)\s]+\s*(.+)/;
            let matched = 0;
            const temp = { a:'', b:'', c:'', d:'', e:'' };
            lines.forEach(line => {
                const m = line.match(prefixPattern);
                if (m) {
                    const key = m[1].toLowerCase();
                    if (keys.includes(key) && !temp[key]) { temp[key] = m[2].trim(); matched++; }
                }
            });
            if (matched >= 2) return temp;
            lines.forEach((line, idx) => { if (idx < 5) results[keys[idx]] = line; });
            return results;
        }

        function applyBulkOptions(text) {
            const parsed = parseBulkOptions(text);
            const statusEl = document.getElementById('bulkParseStatus');
            let filled = 0;
            ['a','b','c','d','e'].forEach(k => {
                const el = document.getElementById('option_' + k);
                if (el && parsed[k]) {
                    el.value = parsed[k];
                    el.style.borderColor = '#16a34a';
                    el.style.background = '#f0fdf4';
                    filled++;
                    setTimeout(() => { el.style.borderColor = ''; el.style.background = ''; }, 1500);
                }
            });
            if (statusEl) {
                statusEl.style.display = 'block';
                statusEl.innerHTML = filled > 0
                    ? '<span style="color:#15803d;font-weight:600;"><i class="fas fa-check-circle"></i> ' + filled + ' opsi berhasil diisi!</span>'
                    : '<span style="color:#b45309;font-weight:600;"><i class="fas fa-exclamation-triangle"></i> Format tidak dikenali. Coba: A. teks</span>';
            }
        }

        const bulkInput = document.getElementById('bulkOptionsInput');
        if (bulkInput) {
            bulkInput.addEventListener('paste', function() {
                setTimeout(() => applyBulkOptions(this.value), 50);
            });
            let timer;
            bulkInput.addEventListener('input', function() {
                clearTimeout(timer);
                const val = this.value;
                timer = setTimeout(() => { if (val.trim()) applyBulkOptions(val); }, 400);
            });
        }

        function confirmRemove(questionId, groupId) {
            if (confirm('Lepas soal ini dari grup?\n\nSoal tidak dihapus, hanya dikembalikan ke Bank Soal.')) {
                window.location.href = '<?= config('app_url') ?>/questionGroup/removeQuestion/' + groupId + '/' + questionId;
            }
        }

        function confirmDelete(questionId, groupId) {
            if (confirm('HAPUS PERMANEN soal ini?\n\n⚠️ Soal akan dihapus dari bank soal dan semua ujian.\nTindakan ini tidak bisa dibatalkan!')) {
                window.location.href = '<?= config('app_url') ?>/questionGroup/deleteQuestion/' + groupId + '/' + questionId;
            }
        }
    </script>
</body>
</html>
