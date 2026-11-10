<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal - CAT Enterprise</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-back:hover {
            color: var(--primary);
            transform: translateX(-3px);
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            color: white;
        }

        .table-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow-x: auto;
        }

        .table thead th {
            background: var(--light);
            color: #64748b;
            font-weight: 600;
            font-size: 0.875rem;
            border: none;
        }

        .table tbody tr:hover {
            background: var(--light);
        }

        .badge-subject {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-points {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-link {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.2s;
        }

        .badge-link:hover {
            background: #fecaca;
            color: #7f1d1d;
        }

        .btn-action-delete {
            background: #fee2e2;
            color: #991b1b;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: 0.2s;
        }

        .btn-action-delete:hover {
            background: #fecaca;
            color: #7f1d1d;
        }
        
        @media (max-width: 768px) {
            
            .btn-back {
                font-size: 0.85rem;
                margin-bottom: 0.75rem;
            }
        
            .page-header {
                padding: 0 1rem;
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 0.85rem;
            }
        
            .page-header h2 {
                font-size: 1.35rem !important;
                text-align: left;
            }
        
            .page-header > div {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
                gap: 0.6rem !important;
                width: 100%;
                box-sizing: border-box;
                align-items: stretch;
            }
        
            .page-header > div > button,
            .page-header > div > a {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box;
                margin: 0 !important;
                padding: 0.65rem 0.5rem !important;
                font-size: 0.78rem !important;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.4rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                border-radius: 0.5rem !important;
            }
        
            #btn-hapus-terpilih {
                order: -1;
            }
            
            .filter-toolbar {
                flex-direction: column !important;
                align-items: stretch !important;
                padding: 0.85rem !important;
                gap: 0.6rem !important;
            }
        
            .filter-toolbar > div[style*="position:relative"] {
                flex: 1 1 100% !important;
                min-width: 0 !important;
                width: 100%;
            }
            .filter-toolbar input#searchInput {
                font-size: 0.85rem;
                padding-top: 0.6rem !important;
                padding-bottom: 0.6rem !important;
            }
        
            .filter-toolbar select {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                width: auto !important;
                font-size: 0.8rem;
                padding: 0.5rem 0.4rem !important;
            }
        
            .filter-toolbar #filterStats {
                flex: 1 1 100% !important;
                width: 100%;
                box-sizing: border-box;
                text-align: center;
                order: 3;
            }
            
             .table thead tr {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 10px 12px;
                background: #f8fafc;
                border-radius: 10px;
                margin-bottom: 10px;
            }
            .table thead th {
                border: none !important;
                padding: 0 !important;
                width: auto !important;
            }
            .table thead th:nth-child(2),
            .table thead th:nth-child(3),
            .table thead th:nth-child(4) {
                display: none;
            }
            .table thead th:first-child::after {
                content: 'Pilih Semua';
                font-size: 0.8rem;
                font-weight: 600;
                color: #475569;
                margin-left: 6px;
            }
        
            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
            .table tr {
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                margin-bottom: 12px;
                padding: 14px;
                box-shadow: 0 1px 3px rgba(0,0,0,.06);
                position: relative;
            }
            .table td {
                padding: 0 !important;
                border: none !important;
            }
        
            .table td:nth-child(2),
            .table td:nth-child(3),
            .table td:nth-child(4) {
                width: 100% !important;
                max-width: 100% !important;
            }
        
            .table td:first-child {
                position: absolute;
                top: 12px;
                right: 12px;
                width: auto !important;
            }
        
            .table td:nth-child(2) {
                display: inline-flex;
                align-items: baseline;
                gap: 6px;
                padding-right: 40px !important;
                margin-bottom: 8px;
            }
            .table td:nth-child(2) span:first-child { font-size: 13px !important; }
            .table td:nth-child(2) span:last-child  { font-size: 10px !important; }
            .table td:nth-child(2) br { display: none; }
        
            .table td:nth-child(3) {
                clear: both;
                font-size: 0.85rem;
            }
            .table td:nth-child(3) div[style*="display:flex"] {
                flex-wrap: wrap;
            }
            .table td:nth-child(3) i.fa-file-pdf {
                font-size: 2rem !important;
            }
            .table td:nth-child(3) > div[style*="gap:15px"] {
                gap: 10px !important;
            }
        
            .table td:nth-child(4) {
                margin-top: 12px;
                padding-top: 12px !important;
                border-top: 1px solid #f1f5f9 !important;
            }
            .table td:nth-child(4) > div {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr);
                gap: 6px !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box;
            }
            .table td:nth-child(4) > div a,
            .table td:nth-child(4) > div button {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box;
                margin: 0 !important;
                padding: 8px 4px !important;
                font-size: 11px !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                text-align: center;
            }
        
            tr[data-type="pdf"] td:nth-child(4) > div {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 420px) {
            .page-header h2 {
                font-size: 1.15rem !important;
            }
            .page-header > div > button,
            .page-header > div > a {
                font-size: 0.72rem !important;
                padding: 0.6rem 0.4rem !important;
            }
            #btn-hapus-terpilih span#jumlah-terpilih {
                display: inline;
            }
            
            .filter-toolbar select {
                flex: 1 1 100% !important;
            }
            
            .table td:nth-child(3) > div[style*="gap:15px"] {
                flex-direction: column;
                align-items: flex-start !important;
            }
        }
    </style>
</head>

<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <div class="page-header">
            <a href="<?= config('app_url') ?>/dashboard" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h2 style="font-size: 2rem; font-weight: 800; color: var(--dark); margin: 0;">Bank Soal</h2>
            <div style="display:flex; gap:0.75rem; align-items:center">
                <button id="btn-hapus-terpilih" onclick="hapusTerpilih()" style="display:none; background:#ef4444; color:white; border:none; padding:0.6rem 1.2rem; border-radius:0.5rem; font-weight:600; cursor:pointer">
                    <i class="fas fa-trash"></i> Hapus Terpilih (<span id="jumlah-terpilih">0</span>)
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadPdfModal" style="background:#ef4444; border:none;">
                    <i class="fas fa-file-pdf"></i> Upload PDF
                </button>
                <a href="<?= config('app_url') ?>/question/create" class="btn btn-primary" style="background:#1e293b;">
                    <i class="fas fa-plus"></i> Tambah Soal
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; border: none; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'];
                                                    unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <!-- Filter Toolbar -->
            <div class="filter-toolbar" style="display:flex; flex-wrap:wrap; gap:0.625rem; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.875rem; padding:0.75rem 1rem; margin-bottom:1.25rem;">
                <div style="flex:1; min-width:180px; position:relative;">
                    <i class="fas fa-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:0.8rem;"></i>
                    <input type="text" id="searchInput" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari soal, topik, pembuat..."
                           style="width:100%; padding:0.5rem 0.75rem 0.5rem 2rem; border:1.5px solid #e2e8f0; border-radius:0.5rem; font-size:0.85rem; background:white;">
                </div>
        
                <select id="filterType" style="padding:0.5rem 0.75rem; border:1.5px solid #e2e8f0; border-radius:0.5rem; background:white; color:#1e293b;">
                    <option value="">Semua Tipe</option>
                    <option value="multiple_choice" <?= $type === 'multiple_choice' ? 'selected' : '' ?>>Pilihan Ganda</option>
                    <option value="essay" <?= $type === 'essay' ? 'selected' : '' ?>>Essay</option>
                    <option value="short_answer" <?= $type === 'short_answer' ? 'selected' : '' ?>>Isian Angka</option>
                    <option value="short_answer_text" <?= $type === 'short_answer_text' ? 'selected' : '' ?>>Isian Kalimat</option>
                    <option value="tkp" <?= $type === 'tkp' ? 'selected' : '' ?>>📊 TKP (Skala Likert)</option>
                    <option value="pdf" <?= $type === 'pdf' ? 'selected' : '' ?>>📄 File PDF</option>
                </select>
        
                <select id="filterSubject" style="padding:0.5rem 0.75rem; border:1.5px solid #e2e8f0; border-radius:0.5rem; background:white; color:#1e293b;">
                    <option value="">Semua Mapel</option>
                    <?php foreach ($subjectOptions as $sn): ?>
                        <option value="<?= htmlspecialchars(strtolower($sn)) ?>" <?= $subject === strtolower($sn) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sn) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
        
                <div id="filterStats" style="font-size:0.8rem; color:#64748b; padding:0.25rem 0.5rem; background:#eff6ff; border-radius:0.375rem; white-space:nowrap;">
                    <span id="loadingIndicator" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                    <span id="statsText">Tampil: <b id="visibleCount"><?= count($questions) ?></b> dari <b id="totalCount"><?= number_format($totalRows) ?></b></span>
                </div>
            </div>
        
            <div id="tableWrapper">
                <?php include __DIR__ . '/_table.php'; ?>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: UPLOAD PDF (STEP 1)               -->
    <!-- ========================================== -->
    <div class="modal fade" id="uploadPdfModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none; border-radius:1rem;">
                <div class="modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <h5 class="modal-title" style="font-weight:700; color:#1e293b;">
                        <i class="fas fa-file-pdf" style="color:#ef4444; margin-right:8px;"></i> Upload Dokumen PDF
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:2rem;">
                    <div style="border:2px dashed #cbd5e1; border-radius:0.75rem; padding:2.5rem 2rem; text-align:center; background:#f8fafc; cursor:pointer; transition:all 0.2s;"
                        id="dropZone"
                        ondragover="event.preventDefault(); this.style.borderColor='#6366f1'; this.style.background='#eef2ff'"
                        ondragleave="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc'"
                        ondrop="handleDrop(event)"
                        onclick="document.getElementById('pdfInput').click()">
                        <i class="fas fa-cloud-upload-alt" style="font-size:3rem; color:#94a3b8; margin-bottom:1rem;"></i>
                        <p style="color:#475569; font-weight:600;">Pilih File PDF</p>
                        <p style="color:#94a3b8; font-size:0.85rem; margin-bottom:1rem;">Drag & drop atau klik di sini</p>
                        <input type="file" id="pdfInput" accept=".pdf" style="display:none;" onchange="handleFileSelect(this)">
                    </div>
                    <div id="fileInfo" style="display:none; margin-top:1.5rem; padding:1rem; background:#eff6ff; border-radius:0.5rem; border:1px solid #bfdbfe;">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <i class="fas fa-file-pdf" style="font-size:1.5rem; color:#ef4444;"></i>
                            <div style="flex:1;">
                                <div id="fileName" style="font-weight:600; color:#1e293b;"></div>
                                <div id="fileSize" style="font-size:0.8rem; color:#64748b;"></div>
                            </div>
                            <button type="button" onclick="removeFile()" style="background:none; border:none; color:#94a3b8; cursor:pointer;"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div id="uploadMessage" style="display:none; margin-top:1rem;"></div>
                </div>
                <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:0.5rem;">Batal</button>
                    <button type="button" id="btnStep1" class="btn btn-primary" disabled onclick="uploadPdfStep1()" style="border-radius:0.5rem;">
                        <i class="fas fa-arrow-right"></i> Lanjut Konfigurasi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: KONFIGURASI & SIMPAN (STEP 2)     -->
    <!-- ========================================== -->
    <div class="modal fade" id="configPdfModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none; border-radius:1rem;">
                <div class="modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <h5 class="modal-title" style="font-weight:700; color:#1e293b;">
                        <i class="fas fa-cog" style="color:#6366f1; margin-right:8px;"></i> Konfigurasi PDF
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:2rem;">
                    <form id="configForm">
                        <input type="hidden" id="uploadedPdfPath">
                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;">Mata Pelajaran</label>
                            <select class="form-select" id="configSubject" required style="border-radius:0.5rem; border:1.5px solid #e2e8f0;">
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php
                                $subjects = db()->query("SELECT id, name FROM subjects ORDER BY name")->fetchAll();
                                foreach ($subjects as $subj):
                                ?>
                                    <option value="<?= $subj['id'] ?>"><?= htmlspecialchars($subj['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;">
                                <i class="fas fa-tag" style="color:#6366f1; margin-right:6px;"></i> Sub Topik
                            </label>
                            <input type="text" id="configSubTopic" class="form-control"
                                placeholder="Contoh: Aljabar, Trigonometri, dll (opsional)"
                                style="border-radius:0.5rem; border:1.5px solid #e2e8f0;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;">Tipe Soal</label>
                            <select class="form-select" id="configType" required style="border-radius:0.5rem; border:1.5px solid #e2e8f0;" onchange="toggleQuestionCountLabel()">
                                <option value="multiple_choice">Pilihan Ganda (A-E)</option>
                                <option value="short_answer">Isian Singkat (Angka)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;">Poin / Bobot</label>
                            <input type="number" id="configPoints" class="form-control" min="1" value="1" required style="border-radius:0.5rem; border:1.5px solid #e2e8f0;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;">
                                <i class="fas fa-hashtag" style="color:#10b981; margin-right:6px;"></i> Jumlah Soal di PDF
                            </label>
                            <input type="number" id="configQuestionCount" class="form-control"
                                placeholder="Contoh: 10" min="1" max="100" value="10" required
                                style="border-radius:0.5rem; border:1.5px solid #e2e8f0;">
                            <small class="text-muted" style="font-size:0.75rem;">
                                Berapa banyak soal dalam file PDF ini?
                            </small>
                        </div>
                        <div id="configMessage" style="display:none; margin-top:1rem;"></div>
                    </form>
                </div>
                <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                    <button type="button" class="btn btn-secondary" onclick="backToUpload()" style="border-radius:0.5rem;">Kembali</button>
                    <button type="button" onclick="savePdfDirectly()" class="btn btn-primary" style="background:#10b981; border:none; border-radius:0.5rem;">
                        <i class="fas fa-save"></i> Simpan ke Bank Soal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 3: PREVIEW SOAL BIASA                -->
    <!-- ========================================== -->
    <!-- Modal Preview Soal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="border:none; border-radius:1rem;">
                <div class="modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <h5 class="modal-title" style="font-weight:700; color:#1e293b;">
                        <i class="fas fa-eye" style="color:#6366f1; margin-right:8px;"></i> Preview Soal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;">
                    <div id="previewLoading" style="text-align:center; padding:2rem; color:#64748b;">
                        <i class="fas fa-spinner fa-spin" style="font-size:2rem;"></i><br>Memuat...
                    </div>
                    <div id="previewContent" style="display:none;">
                        <div id="previewMeta" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:1rem;"></div>
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.5rem; padding:1rem; margin-bottom:1rem;">
                            <div style="font-size:11px; color:#94a3b8; font-weight:600; margin-bottom:6px;">SOAL</div>
                            <div id="previewQuestionText" style="font-size:15px; line-height:1.7; color:#1e293b;"></div>
                        </div>
                        <div id="previewOptionsWrap" style="margin-bottom:1rem; display:none;">
                            <div style="font-size:11px; color:#94a3b8; font-weight:600; margin-bottom:8px;">PILIHAN JAWABAN</div>
                            <div id="previewOptions"></div>
                        </div>
                        <div id="previewAnswerWrap" style="background:#dcfce7; border:1px solid #bbf7d0; border-radius:0.5rem; padding:0.75rem 1rem; display:none;">
                            <span style="font-size:12px; font-weight:700; color:#166534;">✅ KUNCI JAWABAN: </span>
                            <span id="previewAnswer" style="font-size:14px; font-weight:700; color:#166534;"></span>
                        </div>
                        <div id="previewPdfLink" style="margin-top:1rem; padding:1rem; background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.5rem; display:none;">
                            <div style="font-size:12px; color:#64748b; font-weight:600; margin-bottom:6px;">FILE PDF:</div>
                            <a href="#" id="pdfFileLink" target="_blank" style="color:#6366f1; text-decoration:none; font-weight:600;">
                                <i class="fas fa-external-link-alt"></i> Buka File PDF
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                    <a id="previewEditLink" href="#" class="btn btn-sm" style="background:#fef3c7; color:#92400e; border:none; padding:6px 14px; border-radius:6px; font-weight:600; text-decoration:none;">
                        <i class="fas fa-edit"></i> Edit Soal
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="padding:6px 14px; border-radius:6px;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>

    <script>
        // ==========================================
        // 1. LOGIKA UPLOAD PDF (2 LANGKAH SEDERHANA)
        // ==========================================
        let selectedPdfFile = null;

        function handleDrop(event) {
            event.preventDefault();
            event.currentTarget.style.borderColor = '#cbd5e1';
            event.currentTarget.style.background = '#f8fafc';
            if (event.dataTransfer.files.length) processFile(event.dataTransfer.files[0]);
        }

        function handleFileSelect(input) {
            if (input.files.length) processFile(input.files[0]);
        }

        function processFile(file) {
            if (file.type !== 'application/pdf') {
                showMessage('uploadMessage', 'Hanya file PDF yang diperbolehkan!', 'danger');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                showMessage('uploadMessage', 'Ukuran file maksimal 10MB!', 'danger');
                return;
            }
            selectedPdfFile = file;
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            document.getElementById('fileInfo').style.display = 'block';
            document.getElementById('btnStep1').disabled = false;
            hideMessage('uploadMessage');
        }

        function removeFile() {
            selectedPdfFile = null;
            document.getElementById('pdfInput').value = '';
            document.getElementById('fileInfo').style.display = 'none';
            document.getElementById('btnStep1').disabled = true;
        }

        function showMessage(elementId, message, type) {
            const el = document.getElementById(elementId);
            el.style.display = 'block';
            el.className = type === 'success' ? 'alert alert-success' : 'alert alert-danger';
            el.style.background = type === 'success' ? '#d1fae5' : '#fee2e2';
            el.style.color = type === 'success' ? '#065f46' : '#991b1b';
            el.style.border = 'none';
            el.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
        }

        function hideMessage(elementId) {
            document.getElementById(elementId).style.display = 'none';
        }

        // STEP 1: Upload File ke Server
        function uploadPdfStep1() {
            if (!selectedPdfFile) return;
            const formData = new FormData();
            formData.append('pdf_file', selectedPdfFile);
            const btn = document.getElementById('btnStep1');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengupload...';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?= config("app_url") ?>/question/uploadPdfStep1');
            xhr.onload = () => {
                if (xhr.status === 200) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            document.getElementById('uploadedPdfPath').value = res.file_path;
                            bootstrap.Modal.getInstance(document.getElementById('uploadPdfModal')).hide();
                            setTimeout(() => new bootstrap.Modal(document.getElementById('configPdfModal')).show(), 300);
                            removeFile();
                            btn.innerHTML = '<i class="fas fa-arrow-right"></i> Lanjut Konfigurasi';
                        } else {
                            showMessage('uploadMessage', res.message, 'danger');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-arrow-right"></i> Lanjut Konfigurasi';
                        }
                    } catch (e) {
                        showMessage('uploadMessage', 'Respon server tidak valid', 'danger');
                        btn.disabled = false;
                    }
                }
            };
            xhr.send(formData);
        }

        function backToUpload() {
            bootstrap.Modal.getInstance(document.getElementById('configPdfModal')).hide();
            new bootstrap.Modal(document.getElementById('uploadPdfModal')).show();
        }

        function savePdfDirectly() {
            const subjectId = document.getElementById('configSubject').value;
            const subTopic = document.getElementById('configSubTopic').value.trim();
            const points = document.getElementById('configPoints').value;
            const questionCount = document.getElementById('configQuestionCount').value;
            const pdfPath = document.getElementById('uploadedPdfPath').value;
            const type = document.getElementById('configType').value; // BARU
        
            if (!subjectId || !points || !questionCount || !pdfPath) {
                showMessage('configMessage', 'Lengkapi semua data!', 'danger');
                return;
            }
        
            bootstrap.Modal.getInstance(document.getElementById('configPdfModal')).hide();
        
            showAnswerInputModal(subjectId, subTopic, points, questionCount, pdfPath, type);
        }
        
        function showAnswerInputModal(subjectId, subTopic, points, questionCount, pdfPath, type) {
            const isShortAnswer = type === 'short_answer';
            const modalTitle = isShortAnswer
                ? `<i class="fas fa-hashtag" style="color:#10b981; margin-right:8px;"></i> Isi Kunci Jawaban Angka (${questionCount} Soal)`
                : `<i class="fas fa-tasks" style="color:#6366f1; margin-right:8px;"></i> Pilih Jawaban Benar (${questionCount} Soal)`;
            const infoText = isShortAnswer
                ? 'Masukkan jawaban angka yang benar untuk setiap soal nomor 1-' + questionCount + '. Kosongkan jika belum tahu jawabannya.'
                : 'Pilih jawaban yang benar untuk setiap soal nomor 1-' + questionCount + '. Kosongkan jika soal essay atau tidak ada pilihan ganda.';
        
            const modalHtml = `
                <div class="modal fade" id="answerInputModal" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content" style="border:none; border-radius:1rem;">
                            <div class="modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                                <h5 class="modal-title" style="font-weight:700; color:#1e293b;">${modalTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" style="padding:1.5rem;">
                                <div class="alert alert-info" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.5rem; padding:1rem; margin-bottom:1.5rem;">
                                    <i class="fas fa-info-circle" style="color:#3b82f6;"></i>
                                    <strong>Petunjuk:</strong> ${infoText}
                                </div>
        
                                <div id="answersForm">
                                    ${generateAnswerInputs(questionCount, type)}
                                </div>
        
                                <div id="saveMessage" style="display:none; margin-top:1rem;"></div>
                            </div>
                            <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #e2e8f0;">
                                <button type="button" id="btnBackToConfig" class="btn btn-secondary" style="border-radius:0.5rem;">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </button>
                                <button type="button" id="btnSubmitAnswers"
                                    class="btn btn-primary" style="background:#10b981; border:none; border-radius:0.5rem;">
                                    <i class="fas fa-save"></i> Simpan Semua Soal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        
            document.getElementById('answerInputModal')?.remove();
        
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = modalHtml;
            document.body.appendChild(tempDiv.firstElementChild);
        
            document.getElementById('btnBackToConfig').addEventListener('click', backToConfig);
            document.getElementById('btnSubmitAnswers').addEventListener('click', function (e) {
                submitAllAnswers(subjectId, subTopic, points, pdfPath, type, e.currentTarget);
            });
        
            const modal = new bootstrap.Modal(document.getElementById('answerInputModal'));
            modal.show();
        }

        function generateAnswerInputs(count, type) {
            let html = '';
        
            if (type === 'short_answer') {
                // ISIAN SINGKAT ANGKA — input angka per soal
                for (let i = 1; i <= count; i++) {
                    html += `
                <div class="card mb-2" style="border:1px solid #e2e8f0; border-radius:0.5rem;">
                    <div class="card-body" style="padding:1rem;">
                        <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                            <div style="font-weight:700; color:#1e293b; min-width:100px;">
                                <i class="fas fa-question-circle" style="color:#10b981; margin-right:6px;"></i> Soal #${i}
                            </div>
                            <input type="number" step="any" name="answer_${i}"
                                placeholder="Jawaban angka"
                                style="flex:1; min-width:160px; padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:6px; font-size:0.9rem;">
                        </div>
                    </div>
                </div>
            `;
                }
                return html;
            }
        
            // PILIHAN GANDA — radio A-E (default, tidak berubah)
            for (let i = 1; i <= count; i++) {
                html += `
                <div class="card mb-2" style="border:1px solid #e2e8f0; border-radius:0.5rem;">
                    <div class="card-body" style="padding:1rem;">
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                            <div style="font-weight:700; color:#1e293b; min-width:100px;">
                                <i class="fas fa-question-circle" style="color:#6366f1; margin-right:6px;"></i> Soal #${i}
                            </div>
                            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                ${['A', 'B', 'C', 'D', 'E'].map(opt => `
                                    <label style="display:inline-flex; align-items:center; gap:4px; padding:6px 12px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:6px; cursor:pointer; transition:all 0.2s;"
                                        onmouseover="this.style.background='#eef2ff'; this.style.borderColor='#6366f1'"
                                        onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'">
                                        <input type="radio" name="answer_${i}" value="${opt}" style="cursor:pointer;">
                                        <span style="font-weight:600; color:#475569; font-size:0.9rem;">${opt}</span>
                                    </label>
                                `).join('')}
                                <label style="display:inline-flex; align-items:center; gap:4px; padding:6px 12px; background:#fef3c7; border:1.5px solid #fbbf24; border-radius:6px; cursor:pointer;">
                                    <input type="radio" name="answer_${i}" value="" checked style="cursor:pointer;">
                                    <span style="font-weight:600; color:#92400e; font-size:0.85rem;">Kosong</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }
            return html;
        }


        function submitAllAnswers(subjectId, subTopic, points, pdfPath, type, btn) {
            const questionCount = parseInt(document.getElementById('configQuestionCount').value);
            const answers = {};
        
            for (let i = 1; i <= questionCount; i++) {
                if (type === 'short_answer') {
                    const input = document.querySelector(`input[name="answer_${i}"]`);
                    const val = input ? input.value.trim() : '';
                    if (val !== '') answers[i] = val;
                } else {
                    const selected = document.querySelector(`input[name="answer_${i}"]:checked`);
                    if (selected && selected.value) answers[i] = selected.value;
                }
            }
        
            const formData = new FormData();
            formData.append('pdf_path', pdfPath);
            formData.append('subject_id', subjectId);
            formData.append('sub_topic', subTopic);
            formData.append('points', points);
            formData.append('question_count', questionCount);
            formData.append('type', type); // BARU
            formData.append('answers', JSON.stringify(answers));
        
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        
            fetch('<?= config("app_url") ?>/question/previewPdfQuestions', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('answerInputModal')).hide();
                        alert(data.message);
                        location.reload();
                    } else {
                        showMessage('saveMessage', data.message || 'Gagal menyimpan', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save"></i> Simpan Semua Soal';
                    }
                })
                .catch(err => {
                    showMessage('saveMessage', 'Error: ' + err.message, 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Simpan Semua Soal';
                });
        }

        function backToConfig() {
            bootstrap.Modal.getInstance(document.getElementById('answerInputModal')).hide();
            new bootstrap.Modal(document.getElementById('configPdfModal')).show();
        }

        // ==========================================
        // 2. LOGIKA TABEL (Filter, Hapus, Preview)
        // ==========================================
        const BASE_URL = '<?= config('app_url') ?>';

        function filterQuestions() {
            const search = document.getElementById('searchInput').value.toLowerCase().trim();
            const type = document.getElementById('filterType').value;
            const subject = document.getElementById('filterSubject').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr[data-type]');
            let visible = 0;

            rows.forEach(row => {
                const rType = row.dataset.type || '';
                const rSubject = row.dataset.subject || '';
                const rText = row.innerText.toLowerCase();

                let show = true;
                if (type && rType !== type) show = false;
                if (subject && !rSubject.includes(subject)) show = false;
                if (search && !rText.includes(search)) show = false;

                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            document.getElementById('visibleCount').textContent = visible;
            document.getElementById('filterEmpty').style.display = (visible === 0 && (search || type || subject)) ? 'block' : 'none';
        }

        function toggleAll(cb) {
            document.querySelectorAll('.soal-checkbox').forEach(c => c.checked = cb.checked);
            updateCount();
        }

        function updateCount() {
            const checked = document.querySelectorAll('.soal-checkbox:checked').length;
            document.getElementById('jumlah-terpilih').textContent = checked;
            document.getElementById('btn-hapus-terpilih').style.display = checked > 0 ? 'inline-block' : 'none';
            const all = document.querySelectorAll('.soal-checkbox').length;
            document.getElementById('select-all').indeterminate = checked > 0 && checked < all;
            document.getElementById('select-all').checked = checked === all && all > 0;
        }

        function hapusTerpilih() {
            const ids = [...document.querySelectorAll('.soal-checkbox:checked')].map(c => c.value);
            if (ids.length === 0) return;
            if (!confirm('Yakin ingin menghapus ' + ids.length + ' item yang dipilih?\n\nTindakan ini tidak dapat dibatalkan!')) return;

            let deleted = 0;
            const promises = ids.map(id =>
                fetch(BASE_URL + '/question/delete/' + id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'confirm_delete=1'
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        document.getElementById('row-' + id)?.remove();
                        deleted++;
                    }
                })
            );

            Promise.all(promises).then(() => {
                updateCount();
                if (deleted > 0) {
                    const msg = document.createElement('div');
                    msg.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:12px 20px;border-radius:8px;z-index:9999;font-weight:600';
                    msg.textContent = '✅ ' + deleted + ' item berhasil dihapus';
                    document.body.appendChild(msg);
                    setTimeout(() => msg.remove(), 3000);
                }
            });
        }

        function openPreview(id) {
            console.log('Opening preview for ID:', id);

            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            document.getElementById('previewLoading').style.display = 'block';
            document.getElementById('previewContent').style.display = 'none';
            modal.show();

            fetch(BASE_URL + '/question/previewData/' + id)
                .then(r => r.json())
                .then(data => {
                    console.log('Preview data:', data);

                    if (!data.success) {
                        document.getElementById('previewLoading').innerHTML = '<span style="color:#ef4444">Gagal memuat soal.</span>';
                        return;
                    }

                    renderPreview(data.question);
                    document.getElementById('previewLoading').style.display = 'none';
                    document.getElementById('previewContent').style.display = 'block';
                })
                .catch(err => {
                    console.error('Error:', err);
                    document.getElementById('previewLoading').innerHTML = '<span style="color:#ef4444">Error: ' + err.message + '</span>';
                });
        }

        function renderPreview(q) {
            console.log('Rendering preview:', q);
        
            document.getElementById('previewEditLink').href = BASE_URL + '/question/edit/' + q.id;
        
            let metaHtml = `<span style="background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600">📚 ${q.subject_name || '-'}</span>`;
        
            if (q.sub_topic) {
                metaHtml += `<span style="background:#ede9fe;color:#5b21b6;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600">🏷️ ${q.sub_topic}</span>`;
            }
        
            if (q.question_number) {
                metaHtml += `<span style="background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600">📝 Soal #${q.question_number}</span>`;
            }
            if (q.type === 'tkp') {
                metaHtml += `<span style="background:#fce7f3;color:#9d174d;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600">📊 TKP (Skala Likert)</span>`;
            }
            document.getElementById('previewMeta').innerHTML = metaHtml;
        
            document.getElementById('previewQuestionText').innerHTML = q.question_text || 'Tidak ada teks soal';
        
            // ==========================================
            // TAMPILKAN OPSI JAWABAN (A-E) + NILAI PER OPSI UNTUK TKP
            // ==========================================
            const optionsWrap = document.getElementById('previewOptionsWrap');
            const optionsContainer = document.getElementById('previewOptions');
        
            let scores = {};
            if (q.type === 'tkp') {
                try {
                    scores = q.option_scores ? JSON.parse(q.option_scores) : {};
                } catch (e) {
                    scores = {};
                }
            }
        
            const optionKeys = ['a', 'b', 'c', 'd', 'e'];
            const optionLabels = { a: 'A', b: 'B', c: 'C', d: 'D', e: 'E' };
            let optionsHtml = '';
            let hasAnyOption = false;
        
            optionKeys.forEach(key => {
                const optText = q['option_' + key];
                if (optText === null || optText === undefined || optText === '') return;
                hasAnyOption = true;
            
                const isCorrect = q.type !== 'tkp'
                    && q.correct_answer
                    && q.correct_answer.toString().trim().toLowerCase() === key;
            
                let scoreBadge = '';
                if (q.type === 'tkp') {
                    const val = scores[key] !== undefined ? scores[key] : '-';
                    scoreBadge = `<span style="margin-left:auto; background:#fce7f3; color:#9d174d; border:1px solid #f9a8d4; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:700; white-space:nowrap;">Nilai: ${val}</span>`;
                }
            
                optionsHtml += `
                    <div style="display:flex; align-items:center; gap:10px; padding:8px 12px; margin-bottom:6px; border-radius:8px; background:${isCorrect ? '#dcfce7' : '#f8fafc'}; border:1px solid ${isCorrect ? '#bbf7d0' : '#e2e8f0'};">
                        <span style="min-width:26px; height:26px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:${isCorrect ? '#16a34a' : '#e2e8f0'}; color:${isCorrect ? 'white' : '#475569'}; font-weight:700; font-size:12px;">
                            ${optionLabels[key]}
                        </span>
                        <span style="flex:1; font-size:14px; color:#1e293b;">${optText}</span>
                        ${isCorrect ? '<span style="color:#16a34a; font-size:12px; font-weight:700;">✅ Benar</span>' : ''}
                        ${scoreBadge}
                    </div>
                `;
            });
        
            if (hasAnyOption) {
                optionsContainer.innerHTML = optionsHtml;
                optionsWrap.style.display = 'block';
            } else {
                optionsContainer.innerHTML = '';
                optionsWrap.style.display = 'none';
            }
        
            // ==========================================
            // KUNCI JAWABAN / RINGKASAN SKOR (tetap dipertahankan sebagai ringkasan)
            // ==========================================
            const answerWrap = document.getElementById('previewAnswerWrap');
            const answerText = document.getElementById('previewAnswer');
        
            if (q.type === 'tkp') {
                answerWrap.style.display = 'block';
                answerWrap.style.background = '#fce7f3';
                answerWrap.style.borderColor = '#f9a8d4';
                let scoreHtml = '<span style="font-size:12px;font-weight:700;color:#9d174d;">📊 RINGKASAN SKOR TIAP OPSI: </span><br>';
                scoreHtml += optionKeys.map(k => {
                    const val = scores[k] !== undefined ? scores[k] : '-';
                    return `<span style="display:inline-block;margin:4px 6px 0 0;background:white;border:1px solid #f9a8d4;padding:3px 10px;border-radius:6px;font-size:13px;font-weight:600;color:#831843;">${k.toUpperCase()}: ${val}</span>`;
                }).join('');
                answerText.innerHTML = scoreHtml;
            } else if (q.correct_answer && q.correct_answer.toString().trim() !== '' && q.correct_answer.toString().trim() !== '0') {
                answerWrap.style.display = 'block';
                answerWrap.style.background = '#dcfce7';
                answerWrap.style.borderColor = '#bbf7d0';
                answerText.innerHTML = `<strong style="font-size:16px;color:#166534">${q.correct_answer.toString().toUpperCase()}</strong>`;
            } else {
                answerWrap.style.display = 'block';
                answerWrap.style.background = '#dcfce7';
                answerWrap.style.borderColor = '#bbf7d0';
                answerText.innerHTML = `<span style="color:#92400e">Tidak ada kunci jawaban</span>`;
            }
        
            if (q.pdf_file) {
                const pdfLink = document.getElementById('previewPdfLink');
                const pdfFileLink = document.getElementById('pdfFileLink');
        
                const fileName = (q.pdf_file || '').split('/').pop();
                const viewUrl = BASE_URL + '/question/viewPdf?file=' + encodeURIComponent(fileName);
        
                pdfLink.style.display = 'block';
                pdfFileLink.href = viewUrl;
                pdfFileLink.innerHTML = '<i class="fas fa-external-link-alt"></i> Lihat PDF';
            } else {
                document.getElementById('previewPdfLink').style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => filterQuestions());
        
        (function() {
            const searchInput   = document.getElementById('searchInput');
            const typeSelect    = document.getElementById('filterType');
            const subjectSelect = document.getElementById('filterSubject');
            const tableWrapper  = document.getElementById('tableWrapper');
            const loadingEl     = document.getElementById('loadingIndicator');
            const statsTextEl   = document.getElementById('statsText');
            const baseUrl       = '<?= config('app_url') ?>/question';
        
            let debounceTimer   = null;
            let currentRequest  = null;
        
            function loadData(page) {
                if (currentRequest) {
                    currentRequest.abort();
                }
                currentRequest = new AbortController();
        
                const params = new URLSearchParams({
                    ajax: 1,
                    search: searchInput.value.trim(),
                    type: typeSelect.value,
                    subject: subjectSelect.value,
                    page: page
                });
        
                loadingEl.style.display = 'inline';
                statsTextEl.style.opacity = '0.5';
        
                fetch(baseUrl + '?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: currentRequest.signal
                })
                .then(res => res.text())
                .then(html => {
                    tableWrapper.innerHTML = html;
                    loadingEl.style.display = 'none';
                    statsTextEl.style.opacity = '1';
                    history.replaceState(null, '', baseUrl + '?' + params.toString());
                    currentRequest = null;
                })
                .catch(err => {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    console.error('Gagal memuat data:', err);
                    loadingEl.style.display = 'none';
                    statsTextEl.style.opacity = '1';
                });
            }
        
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => loadData(1), 400);
            });
            typeSelect.addEventListener('change', () => loadData(1));
            subjectSelect.addEventListener('change', () => loadData(1));
        
            tableWrapper.addEventListener('click', (e) => {
                const link = e.target.closest('[data-page]');
                if (link) {
                    e.preventDefault();
                    loadData(parseInt(link.dataset.page, 10));
                }
            });
        })();
    </script>
</body>

</html>