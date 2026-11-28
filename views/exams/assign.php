<?php
if (!function_exists('urutkanSoal')) {
    function nomorSoal(array $q)
    {
        // Utamakan nomor dari teks "Soal PDF #N"
        $teks = html_entity_decode(strip_tags((string)($q['question_text'] ?? '')), ENT_QUOTES, 'UTF-8');
        if (preg_match('/#\s*(\d+)/u', $teks, $m)) {
            return (int)$m[1];
        }
        if (isset($q['question_number']) && $q['question_number'] !== '' && $q['question_number'] !== null) {
            return (int)$q['question_number'];
        }
        return 1000000;
    }

    function urutkanSoal(array $questions): array
    {
        // Beri indeks asli supaya urutan stabil
        foreach ($questions as $i => &$q) {
            $q['_idx'] = $i;
        }
        unset($q);

        // id terbesar per PDF -> menentukan PDF mana yang tampil lebih dulu
        $grpMax = [];
        foreach ($questions as $q) {
            $pdf = (string)($q['pdf_file'] ?? '');
            if ($pdf !== '') {
                $grpMax[$pdf] = max($grpMax[$pdf] ?? 0, (int)$q['id']);
            }
        }

        usort($questions, function ($a, $b) use ($grpMax) {
            $c = strcasecmp((string)($a['subject_name'] ?? ''), (string)($b['subject_name'] ?? ''));
            if ($c !== 0) return $c;

            $pa = (string)($a['pdf_file'] ?? '');
            $pb = (string)($b['pdf_file'] ?? '');
            $ga = $pa !== '' ? $grpMax[$pa] : (int)$a['id'];
            $gb = $pb !== '' ? $grpMax[$pb] : (int)$b['id'];
            if ($ga !== $gb) return $gb <=> $ga;          // PDF terbaru di atas

            if ($pa !== $pb) return strcmp($pa, $pb);     // jaga satu PDF tetap satu blok

            $na = nomorSoal($a);
            $nb = nomorSoal($b);
            if ($na !== $nb) return $na <=> $nb;          // 1, 2, 3, ... naik

            return $a['_idx'] <=> $b['_idx'];
        });

        return $questions;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Soal - CAT Enterprise</title>
    <link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
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
            --primary-dark: #4f46e5;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
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
            padding: 2rem 0;
            max-width: 900px;
            margin: 0 auto;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-back:hover {
            color: var(--primary);
            transform: translateX(-3px);
        }

        .form-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        /* ── Filter & Search Bar ── */
        .filter-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.625rem;
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
        }

        .filter-search {
            flex: 1;
            min-width: 180px;
            position: relative;
        }

        .filter-search i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .filter-search input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2rem;
            border: 1.5px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-family: inherit;
            background: white;
            transition: border-color 0.2s;
        }

        .filter-search input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-select {
            padding: 0.5rem 0.75rem;
            border: 1.5px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-family: inherit;
            background: white;
            color: var(--dark);
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-date-group {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            background: white;
            border: 1.5px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.35rem 0.75rem;
        }

        .filter-date-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            margin: 0;
        }

        .filter-date-group input[type="date"] {
            border: none;
            outline: none;
            font-size: 0.8rem;
            font-family: inherit;
            color: var(--dark);
            background: transparent;
            padding: 0;
            cursor: pointer;
        }

        .filter-sep {
            color: #cbd5e1;
            font-size: 0.75rem;
        }

        .btn-reset-filter {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.35rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            transition: all 0.15s;
            display: none;
        }

        .btn-reset-filter:hover {
            color: #ef4444;
            background: #fef2f2;
        }

        /* Stats bar */
        .filter-stats {
            font-size: 0.8rem;
            color: #64748b;
            padding: 0.25rem 0.5rem;
            background: #eff6ff;
            border-radius: 0.375rem;
            white-space: nowrap;
        }

        .filter-stats span {
            font-weight: 700;
            color: var(--primary);
        }

        /* Question items */
        .question-item {
            background: var(--light);
            border: 2px solid var(--border);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }

        .question-item:hover {
            border-color: var(--primary);
            background: #eef2ff;
        }

        .question-item.q-hidden {
            display: none;
        }

        .question-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .question-item label {
            cursor: pointer;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 1px solid var(--border);
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .subject-section {
            margin-bottom: 2.5rem;
            cursor: move;
            transition: all 0.2s;
        }

        .subject-section.dragging {
            opacity: 0.5;
        }

        .subject-section.drag-over {
            border: 2px dashed var(--primary);
            padding: 0.5rem;
            border-radius: 1rem;
        }

        .drag-handle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: move;
            padding: 0.25rem 0.75rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-left: 1rem;
        }

        .drag-handle:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Empty filter result */
        .filter-empty {
            display: none;
            text-align: center;
            padding: 2.5rem 1rem;
            color: #64748b;
            background: #f8fafc;
            border-radius: 0.75rem;
            border: 2px dashed #e2e8f0;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem 1rem;
            }

            .form-card {
                padding: 1.5rem;
            }

            .filter-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-date-group {
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="<?= config('app_url') ?>/dashboard">
                <i class="fas fa-graduation-cap"></i> CAT Enterprise
            </a>
        </div>
    </nav>

    <div class="container main-content">
        <a href="<?= config('app_url') ?>/exam" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Ujian
        </a>

        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">Kelola Soal: <?= htmlspecialchars($exam['title']) ?></h1>

                <?php if (!$hasQuestions): ?>
                    <div style="text-align:center; padding:60px 20px; background:#f8fafc; border-radius:12px; border:2px dashed #cbd5e1;">
                        <div style="font-size:4rem; color:#94a3b8; margin-bottom:20px;">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3 style="color:#475569; margin-bottom:15px;">Ujian Baru - Belum Ada Soal</h3>
                        <p style="color:#64748b; margin-bottom:30px; max-width:500px; margin-left:auto; margin-right:auto;">
                            Ujian ini baru dibuat dan belum memiliki soal. Pilih salah satu opsi di bawah.
                        </p>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr)); gap:20px; max-width:600px; margin:0 auto;">
                            <a href="<?= config('app_url') ?>/question/create"
                                style="background:#6366f1; color:white; padding:20px; border-radius:10px; text-decoration:none; display:block;">
                                <div style="font-size:2rem; margin-bottom:10px;"><i class="fas fa-plus-circle"></i></div>
                                <h4 style="margin:0 0 8px 0;">Buat Soal Baru</h4>
                                <p style="margin:0; font-size:0.9rem; opacity:0.9;">Buat soal khusus untuk ujian ini</p>
                            </a>
                            <button onclick="showExistingQuestions()"
                                style="background:#10b981; color:white; padding:20px; border-radius:10px; border:none; cursor:pointer;">
                                <div style="font-size:2rem; margin-bottom:10px;"><i class="fas fa-search"></i></div>
                                <h4 style="margin:0 0 8px 0;">Pilih dari Soal Lama</h4>
                                <p style="margin:0; font-size:0.9rem; opacity:0.9;">Gunakan soal yang sudah pernah dibuat</p>
                            </button>
                        </div>
                        <div style="margin-top:30px; padding:15px; background:#fef3c7; border-radius:8px; border:1px solid #fbbf24;">
                            <p style="color:#92400e; margin:0; font-size:0.9rem;">
                                <i class="fas fa-lightbulb"></i>
                                <strong>Tips:</strong> Untuk ujian baru, disarankan buat soal baru agar lebih sesuai materi.
                            </p>
                        </div>
                    </div>

                    <div id="existingQuestionsSection" style="display:none; margin-top:30px;">
                        <div style="background:#fff7ed; border:1px solid #fb923c; border-radius:8px; padding:15px; margin-bottom:20px;">
                            <h5 style="color:#ea580c; margin:0 0 8px 0;"><i class="fas fa-exclamation-triangle"></i> Menggunakan Soal yang Sudah Ada</h5>
                            <p style="color:#ea580c; margin:0; font-size:0.9rem;">Pilih dengan hati-hati dan pastikan sesuai dengan ujian ini.</p>
                        </div>
                        <button onclick="loadExistingQuestions()" class="btn btn-primary" style="margin-bottom:20px;">
                            <i class="fas fa-download"></i> Muat Soal yang Tersedia
                        </button>
                        <div id="questionsContainer"></div>
                    </div>
                <?php else: ?>
                    <div style="background:#d1fae5; border:1px solid #10b981; border-radius:8px; padding:15px; margin:15px 0;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-check-circle" style="color:#059669;"></i>
                            <span style="color:#059669; font-weight:600;">Ujian ini sudah memiliki soal. Anda dapat menambah, mengurangi, atau mengubah soal.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($exam['exam_type'] === 'multi'): ?>
                    <p style="color:#64748b; margin-top:0.5rem;">Ujian Multi Mata Pelajaran (Tryout Paket)</p>
                <?php endif; ?>
            </div>

            <!-- PDF Upload Modal -->
            <div id="pdfUploadModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
                <div style="background:white; padding:2rem; border-radius:1rem; max-width:500px; width:90%;">
                    <h3 style="margin:0 0 1rem 0;">Upload PDF Soal</h3>
                    <form id="pdfUploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                        <div style="margin-bottom:1rem;">
                            <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Pilih File PDF:</label>
                            <input type="file" name="exam_pdf" accept=".pdf" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:0.5rem;">
                            <small style="color:#64748b;">Max 10MB</small>
                        </div>
                        <div style="display:flex; gap:1rem;">
                            <button type="submit" class="btn" style="background:#10b981; color:white; flex:1;"><i class="fas fa-upload"></i> Upload</button>
                            <button type="button" class="btn" style="background:#64748b; color:white; flex:1;" onclick="document.getElementById('pdfUploadModal').style.display='none'">Batal</button>
                        </div>
                    </form>
                    <div id="uploadProgress" style="display:none; margin-top:1rem;">
                        <div style="background:#e2e8f0; height:20px; border-radius:10px; overflow:hidden;">
                            <div id="progressBar" style="background:#10b981; height:100%; width:0%; transition:width 0.3s;"></div>
                        </div>
                        <p id="uploadStatus" style="margin-top:0.5rem; text-align:center; color:#64748b;"></p>
                    </div>
                </div>
            </div>

            <script>
                document.getElementById('pdfUploadForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const progressDiv = document.getElementById('uploadProgress');
                    const progressBar = document.getElementById('progressBar');
                    const statusText = document.getElementById('uploadStatus');
                    progressDiv.style.display = 'block';
                    statusText.textContent = 'Uploading...';
                    fetch('<?= config('app_url') ?>/exam/uploadPdf', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                progressBar.style.width = '100%';
                                statusText.textContent = 'Upload berhasil!';
                                statusText.style.color = '#10b981';
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                statusText.textContent = 'Error: ' + data.message;
                                statusText.style.color = '#ef4444';
                            }
                        })
                        .catch(error => {
                            statusText.textContent = 'Error: ' + error.message;
                            statusText.style.color = '#ef4444';
                        });
                });
            </script>

            <form method="POST" id="assignForm" onsubmit="return validateAssignForm()">
                <div style="margin-top:2rem; margin-bottom:2rem; display:flex; gap:1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Soal
                    </button>
                    <a href="<?= config('app_url') ?>/exam" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>

                <?php if ($exam['exam_type'] === 'multi'): ?>
                    <!-- ══ MULTI SUBJECT ══ -->
                    <div id="subjectSections">
                        <?php foreach ($examSubjects as $index => $examSubject):
                            $subjectId = $examSubject['subject_id'];
                            $questions = urutkanSoal($questionsBySubject[$subjectId] ?? []);
                        ?>
                            <div class="subject-section"
                                draggable="true"
                                data-subject-id="<?= $subjectId ?>"
                                data-exam-subject-id="<?= $examSubject['id'] ?>">

                                <div style="background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:white; padding:1rem 1.5rem; border-radius:0.75rem; margin-bottom:1rem;">
                                    <h4 style="margin:0; font-weight:700; display:flex; align-items:center; justify-content:space-between;">
                                        <span>BAGIAN <?= $index + 1 ?>: <?= strtoupper(htmlspecialchars($examSubject['subject_name'])) ?></span>
                                        <span class="drag-handle"><i class="fas fa-grip-vertical"></i> Geser</span>
                                    </h4>
                                    <p style="margin:0.5rem 0 0 0; font-size:0.875rem; opacity:0.9;">Target: <?= $examSubject['question_count'] ?> soal</p>
                                </div>

                                <?php if (count($questions) > 0): ?>
                                    <!-- Filter toolbar per subject -->
                                    <div class="filter-toolbar" data-target="subject-<?= $subjectId ?>">
                                        <div class="filter-search">
                                            <i class="fas fa-search"></i>
                                            <input type="text" placeholder="Cari soal..." oninput="filterQuestions('subject-<?= $subjectId ?>')">
                                        </div>
                                        <select class="filter-select" onchange="filterQuestions('subject-<?= $subjectId ?>')">
                                            <option value="">Semua Tipe</option>
                                            <option value="multiple_choice">Pilihan Ganda</option>
                                            <option value="essay">Essay</option>
                                            <option value="short_answer">Isian Angka</option>
                                            <option value="short_answer_text">Isian Kalimat</option>
                                            <option value="listening">Listening</option>
                                            <option value="tkp">📊 TKP</option>
                                        </select>
                                        </select>
                                        <div class="filter-date-group">
                                            <i class="fas fa-calendar-alt" style="color:var(--primary); font-size:0.8rem;"></i>
                                            <label>Dari</label>
                                            <input type="date" onchange="filterQuestions('subject-<?= $subjectId ?>')">
                                            <span class="filter-sep">—</span>
                                            <label>Sampai</label>
                                            <input type="date" onchange="filterQuestions('subject-<?= $subjectId ?>')">
                                            <button class="btn-reset-filter" onclick="resetFilter('subject-<?= $subjectId ?>', this)" title="Reset filter">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="filter-stats">Tampil: <span class="count">?</span></div>
                                    </div>
                                    <div class="filter-empty" id="empty-subject-<?= $subjectId ?>">
                                        <i class="fas fa-search" style="font-size:1.75rem; opacity:0.4; display:block; margin-bottom:0.5rem;"></i>
                                        Tidak ada soal yang cocok dengan filter
                                    </div>

                                    <div class="question-list" data-group="subject-<?= $subjectId ?>">
                                        <?php foreach ($questions as $q):
                                            if ($q['type'] === 'multiple_choice') {
                                                $tBg = '#e0e7ff';
                                                $tC = '#3730a3';
                                                $tL = 'PG';
                                            } elseif ($q['type'] === 'short_answer') {
                                                $tBg = '#dcfce7';
                                                $tC = '#166534';
                                                $tL = 'Isian Singkat (Angka)';
                                            } elseif ($q['type'] === 'short_answer_text') {
                                                $tBg = '#fef9c3';
                                                $tC = '#854d0e';
                                                $tL = 'Isian Singkat (Kalimat)';
                                            } elseif ($q['type'] === 'listening') {
                                                $tBg = '#e0f2fe';
                                                $tC = '#0369a1';
                                                $tL = '🎧 Listening';
                                            } elseif ($q['type'] === 'tkp') {
                                                $tBg = '#fce7f3';
                                                $tC = '#9d174d';
                                                $tL = '📊 TKP';
                                            } else {
                                                $tBg = '#f3e8ff';
                                                $tC = '#6b21a8';
                                                $tL = 'Essay';
                                            }
                                        ?>
                                            <div class="question-item"
                                                data-type="<?= $q['type'] ?>"
                                                data-date="<?= date('Y-m-d', strtotime($q['created_at'])) ?>"
                                                data-text="<?= htmlspecialchars(strtolower(strip_tags($q['question_text'] ?? ''))) ?>">
                                                <label>
                                                    <input type="checkbox"
                                                        name="questions_<?= $subjectId ?>[]"
                                                        value="<?= $q['id'] ?>"
                                                        <?= ($q['is_selected'] ?? 0) > 0 ? 'checked' : '' ?>>
                                                    <div style="flex:1;">
                                                        <strong style="background:<?= $tBg ?>;color:<?= $tC ?>;padding:2px 8px;border-radius:4px;font-size:11px;">
                                                            <?= $tL ?>
                                                        </strong>
                                                        <?= truncateHtml(renderQuestionText($q['question_text']), 150) ?>
                                                        <span class="badge" style="background:#dbeafe;color:#1e40af;"><?= $q['points'] ?> poin</span>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        Belum ada soal untuk mata pelajaran <?= htmlspecialchars($examSubject['subject_name']) ?>.
                                        <a href="<?= config('app_url') ?>/question/create?subject_id=<?= $subjectId ?>&exam_id=<?= $exam['id'] ?>" style="color:#92400e; font-weight:600;">
                                            <i class="fas fa-plus-circle"></i> Tambah soal baru
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <!-- ══ SINGLE SUBJECT ══ -->
                    <?php if (isset($allQuestions) && count($allQuestions) > 0): ?>

                        <!-- Filter toolbar -->
                        <div class="filter-toolbar" data-target="single">
                            <div class="filter-search">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Cari soal, topik, pembuat..." oninput="filterQuestions('single')">
                            </div>
                            <select class="filter-select" onchange="filterQuestions('single')">
                                <option value="">Semua Tipe</option>
                                <option value="multiple_choice">Pilihan Ganda</option>
                                <option value="essay">Essay</option>
                                <option value="short_answer">Isian Angka</option>
                                <option value="short_answer_text">Isian Kalimat</option>
                                <option value="listening">Listening</option>
                                <option value="tkp">📊 TKP</option>
                            </select>
                            <select class="filter-select" id="filterSubjectSingle" onchange="filterQuestions('single')">
                                <option value="">Semua Mapel</option>
                                <?php
                                $uniqueSubjects = [];
                                foreach ($allQuestions as $q) {
                                    $sn = $q['subject_name'] ?? '-';
                                    if (!in_array($sn, $uniqueSubjects)) $uniqueSubjects[] = $sn;
                                }
                                sort($uniqueSubjects);
                                foreach ($uniqueSubjects as $sn):
                                ?>
                                    <option value="<?= htmlspecialchars(strtolower($sn)) ?>"><?= htmlspecialchars($sn) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="filter-date-group">
                                <i class="fas fa-calendar-alt" style="color:var(--primary); font-size:0.8rem;"></i>
                                <label>Dari</label>
                                <input type="date" onchange="filterQuestions('single')">
                                <span class="filter-sep">—</span>
                                <label>Sampai</label>
                                <input type="date" onchange="filterQuestions('single')">
                                <button class="btn-reset-filter" onclick="resetFilter('single', this)" title="Reset filter">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="filter-stats">Tampil: <span class="count">?</span></div>
                        </div>

                        <div class="filter-empty" id="empty-single">
                            <i class="fas fa-search" style="font-size:1.75rem; opacity:0.4; display:block; margin-bottom:0.5rem;"></i>
                            Tidak ada soal yang cocok dengan filter
                        </div>

                        <div class="question-list" data-group="single">
                            <?php foreach (urutkanSoal($allQuestions) as $q):
                                // Tambahkan kondisi khusus untuk soal PDF
                                if ($q['type'] === 'pdf') {
                                    $tBg = '#f0f7ff';
                                    $tC = '#3b82f6';
                                    $tL = '📄 PDF Soal';
                                    $pdfInfo = ' (' . (isset($q['question_number']) ? $q['question_number'] : '1') . ' soal)';
                                    $questionText = 'Dokumen PDF: ' . basename($q['pdf_file']);
                                    $isPdf = true;
                                }
                                // Jika ini adalah soal biasa
                                else {
                                    if ($q['type'] === 'multiple_choice') {
                                        $tBg = '#e0e7ff';
                                        $tC = '#3730a3';
                                        $tL = 'PG';
                                    } elseif ($q['type'] === 'short_answer') {
                                        $tBg = '#dcfce7';
                                        $tC = '#166534';
                                        $tL = 'Isian Singkat (Angka)';
                                    } elseif ($q['type'] === 'short_answer_text') {
                                        $tBg = '#fef9c3';
                                        $tC = '#854d0e';
                                        $tL = 'Isian Singkat (Kalimat)';
                                    } elseif ($q['type'] === 'listening') {
                                        $tBg = '#e0f2fe';
                                        $tC = '#0369a1';
                                        $tL = '🎧 Listening';
                                    } elseif ($q['type'] === 'tkp') {
                                        $tBg = '#fce7f3';
                                        $tC = '#9d174d';
                                        $tL = '📊 TKP';
                                    } else {
                                        $tBg = '#f3e8ff';
                                        $tC = '#6b21a8';
                                        $tL = 'Essay';
                                    }
                                    $pdfInfo = '';
                                    $questionText = $q['question_text'];
                                    $isPdf = false;
                                }
                            ?>
                                <div class="question-item"
                                    data-type="<?= $q['type'] ?>"
                                    data-date="<?= date('Y-m-d', strtotime($q['created_at'])) ?>"
                                    data-subject="<?= htmlspecialchars(strtolower($q['subject_name'] ?? '')) ?>"
                                    data-text="<?= htmlspecialchars(strtolower(strip_tags($questionText) . ' ' . ($q['sub_topic'] ?? '') . ' ' . ($q['creator_name'] ?? ''))) ?>">
                                    <label>
                                        <input type="checkbox"
                                            name="questions[]"
                                            value="<?= $q['id'] ?>"
                                            <?= ($q['is_selected'] ?? 0) > 0 ? 'checked' : '' ?>>
                                        <div style="flex:1;">
                                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                                                <strong style="background:<?= $tBg ?>;color:<?= $tC ?>;padding:2px 8px;border-radius:4px;font-size:12px;"><?= $tL ?><?= $pdfInfo ?></strong>
                                                <span style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:4px;font-size:12px;">
                                                    <i class="fas fa-book" style="font-size:10px;"></i> <?= htmlspecialchars($q['subject_name'] ?? '-') ?>
                                                </span>
                                                <span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:4px;font-size:12px;"><?= $q['points'] ?> poin</span>
                                                <span style="color:#94a3b8;font-size:11px;">
                                                    <i class="fas fa-user" style="font-size:10px;"></i> <?= htmlspecialchars($q['creator_name'] ?? '-') ?>
                                                </span>
                                                <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-size:11px;">
                                                    <i class="fas fa-tag" style="font-size:9px;"></i> <?= !empty($q['sub_topic']) ? htmlspecialchars($q['sub_topic']) : '-' ?>
                                                </span>
                                                <?php if (!empty($q['audio_file'])): ?>
                                                    <span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:4px;font-size:11px;">
                                                        <i class="fas fa-headphones" style="font-size:9px;"></i> Ada Audio
                                                    </span>
                                                <?php endif; ?>
                                                <span style="color:#94a3b8;font-size:11px;">
                                                    <i class="fas fa-calendar" style="font-size:10px;"></i> <?= date('d M Y', strtotime($q['created_at'])) ?>
                                                </span>
                                            </div>
                                            <div style="color:#374151;font-size:13px;"><?= truncateHtml($questionText, 120) ?></div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-warning">
                            Belum ada soal untuk mata pelajaran ini.
                            <a href="<?= config('app_url') ?>/question/create" style="color:#92400e; font-weight:600;">Tambah soal baru</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ══ FILTER LOGIC ══ -->
    <script>
        function filterQuestions(group) {
            const toolbar = document.querySelector(`.filter-toolbar[data-target="${group}"]`);
            const list = document.querySelector(`.question-list[data-group="${group}"]`);
            const emptyMsg = document.getElementById(`empty-${group}`);
            if (!toolbar || !list) return;

            const searchVal = (toolbar.querySelector('input[type="text"]')?.value || '').toLowerCase().trim();
            const typeVal = toolbar.querySelector('select')?.value || '';
            const subjectSel = toolbar.querySelector('#filterSubjectSingle');
            const subjectVal = subjectSel ? subjectSel.value : '';
            const dateInputs = toolbar.querySelectorAll('input[type="date"]');
            const dateFrom = dateInputs[0]?.value || '';
            const dateTo = dateInputs[1]?.value || '';
            const resetBtn = toolbar.querySelector('.btn-reset-filter');

            const hasFilter = searchVal || typeVal || subjectVal || dateFrom || dateTo;
            if (resetBtn) resetBtn.style.display = hasFilter ? 'inline-flex' : 'none';

            const items = list.querySelectorAll('.question-item');
            let visible = 0;

            items.forEach(item => {
                const itemType = item.dataset.type || '';
                const itemDate = item.dataset.date || '';
                const itemText = item.dataset.text || '';
                const itemSubject = item.dataset.subject || '';

                let show = true;
                if (typeVal && itemType !== typeVal) show = false;
                if (subjectVal && itemSubject !== subjectVal) show = false;
                if (searchVal && !itemText.includes(searchVal)) show = false;
                if (dateFrom && itemDate && itemDate < dateFrom) show = false;
                if (dateTo && itemDate && itemDate > dateTo) show = false;

                item.classList.toggle('q-hidden', !show);
                if (show) visible++;
            });

            // Update stats
            const statsSpan = toolbar.querySelector('.count');
            if (statsSpan) statsSpan.textContent = visible + ' / ' + items.length;

            // Show/hide empty message
            if (emptyMsg) emptyMsg.style.display = (visible === 0 && hasFilter) ? 'block' : 'none';
        }

        function resetFilter(group, btn) {
            const toolbar = document.querySelector(`.filter-toolbar[data-target="${group}"]`);
            if (!toolbar) return;
            toolbar.querySelectorAll('input[type="text"]').forEach(i => i.value = '');
            toolbar.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
            toolbar.querySelectorAll('input[type="date"]').forEach(i => i.value = '');
            filterQuestions(group);
        }

        // Init counts on load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.filter-toolbar').forEach(tb => {
                const group = tb.dataset.target;
                if (group) filterQuestions(group);
            });
        });
    </script>

    <!-- ══ DRAG & DROP (multi subject) ══ -->
    <?php if ($exam['exam_type'] === 'multi'): ?>
        <script>
            const subjectSections = document.querySelectorAll('.subject-section');
            let draggedElement = null;

            subjectSections.forEach(section => {
                section.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });
                section.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                    subjectSections.forEach(s => s.classList.remove('drag-over'));
                    updateOrderNumbers();
                    saveOrder();
                });
                section.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    if (this !== draggedElement) this.classList.add('drag-over');
                });
                section.addEventListener('dragleave', function(e) {
                    this.classList.remove('drag-over');
                });
                section.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');
                    if (this !== draggedElement) {
                        const container = document.getElementById('subjectSections');
                        const all = [...container.querySelectorAll('.subject-section')];
                        const di = all.indexOf(draggedElement),
                            ti = all.indexOf(this);
                        di < ti ? this.parentNode.insertBefore(draggedElement, this.nextSibling) : this.parentNode.insertBefore(draggedElement, this);
                    }
                });
            });

            function updateOrderNumbers() {
                document.querySelectorAll('.subject-section').forEach((s, i) => {
                    const h = s.querySelector('h4 span:first-child');
                    h.textContent = h.textContent.replace(/BAGIAN \d+:/, `BAGIAN ${i+1}:`);
                });
            }

            function saveOrder() {
                const order = [];
                document.querySelectorAll('.subject-section').forEach((s, i) => {
                    order.push({
                        exam_subject_id: s.dataset.examSubjectId,
                        order_num: i + 1
                    });
                });
                fetch('<?= config('app_url') ?>/exam/updateSubjectOrder', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            exam_id: <?= $exam['id'] ?>,
                            order
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const msg = document.createElement('div');
                            msg.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:1rem 1.5rem;border-radius:0.5rem;z-index:9999;';
                            msg.innerHTML = '<i class="fas fa-check-circle"></i> Urutan berhasil disimpan!';
                            document.body.appendChild(msg);
                            setTimeout(() => msg.remove(), 2000);
                        }
                    })
                    .catch(() => alert('Gagal menyimpan urutan.'));
            }
        </script>
    <?php endif; ?>

    <script>
        function showExistingQuestions() {
            const el = document.getElementById('existingQuestionsSection');
            el.style.display = 'block';
            el.scrollIntoView({
                behavior: 'smooth'
            });
        }

        function loadExistingQuestions() {
            const container = document.getElementById('questionsContainer');
            const button = event.target;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
            button.disabled = true;
            fetch('<?= config('app_url') ?>/exam/getAvailableQuestions/<?= $exam['id'] ?>')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        container.innerHTML = data.html;
                        button.style.display = 'none';
                    } else {
                        container.innerHTML = '<div style="background:#fef2f2;color:#dc2626;padding:15px;border-radius:8px;">Gagal: ' + data.message + '</div>';
                    }
                })
                .catch(err => {
                    container.innerHTML = '<div style="background:#fef2f2;color:#dc2626;padding:15px;border-radius:8px;">Error: ' + err.message + '</div>';
                })
                .finally(() => {
                    button.innerHTML = '<i class="fas fa-download"></i> Muat Soal yang Tersedia';
                    button.disabled = false;
                });
        }

        window.addEventListener('load', function() {
            if (typeof MathJax !== 'undefined') MathJax.typesetPromise().catch(e => console.log(e));
        });

        function validateAssignForm() {
            const checkboxes = document.querySelectorAll('#assignForm input[type="checkbox"]:checked');
            const seen = {};
            const dupes = [];
            for (const cb of checkboxes) {
                const val = cb.value;
                if (seen[val]) {
                    if (!dupes.includes(val)) dupes.push(val);
                } else {
                    seen[val] = true;
                }
            }
            if (dupes.length > 0) {
                alert('Soal duplikat tidak diperbolehkan dalam satu ujian: ID ' + dupes.join(', ') + ' dipilih lebih dari sekali.');
                return false;
            }
            return true;
        }
    </script>
</body>

</html>