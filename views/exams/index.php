<?php
$kelasUpper    = strtoupper($user['kelas'] ?? '');
$isCsr         = $user['role'] === 'siswa' && $kelasUpper === 'CSR';
$isPpdb        = $user['role'] === 'siswa' && str_contains($kelasUpper, 'PPDB');
$forceTabPpdb  = $isCsr || $isPpdb;

if ($forceTabPpdb && $activeTab !== 'ppdb') {
    if (!headers_sent()) {
        header('Location: ' . config('app_url') . '/exam?tab=ppdb');
        exit;
    } else {
        // fallback aman kalau header sudah terkirim duluan
        echo "<script>window.location.href='" . config('app_url') . "/exam?tab=ppdb';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Ujian - CAT Enterprise</title>
    <link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }
        
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--light); }
        
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
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }
        
        .btn{
            font-weight: bold;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
            align-items: stretch;
        }
        
        .exam-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .exam-card:hover {
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            transform: translateY(-2px);
        }
        .exam-card.hidden-by-filter { display: none; }
        
        .exam-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
            min-height: 3.15rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.26;
        }
        
        .exam-info { margin-bottom: 1rem; flex: 1 1 auto;}
        
        .exam-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: auto;
        }
        
        .exam-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        .exam-info-item i { color: var(--primary); width: 16px; }
        
        .exam-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        
        .btn-success {
            background: var(--success);
            border: none;
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .btn-success:hover { background: #059669; color: white; }
        
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
        
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .badge.success { background: #d1fae5; color: #065f46; }
        .badge.secondary { background: #f1f5f9; color: #475569; }

        /* ── Filter Tanggal ── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 0.375rem 0.875rem;
            box-shadow: var(--shadow);
        }
        .filter-bar label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            margin: 0;
        }
        .filter-bar input[type="date"] {
            border: none;
            outline: none;
            font-size: 0.825rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
            background: transparent;
            padding: 0.25rem 0;
            cursor: pointer;
        }
        .filter-sep { color: #cbd5e1; font-size: 0.75rem; }
        .btn-reset-filter {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.2rem 0.4rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            transition: all 0.15s;
            display: none;
            line-height: 1;
        }
        .btn-reset-filter:hover { color: #ef4444; background: #fef2f2; }
        
        .group-action-btn {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            align-items: stretch;
        }
        
        .group-action-btn > a,
        .group-action-btn > button {
            flex: 1 1 0;
            min-width: 0;
        
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .group-action-btn > a i,
        .group-action-btn > button i {
            font-size: 1rem;
        }
        
        .group-action-btn > a span,
        .group-action-btn > button span {
            font-size: 0.72rem;
        }

        /* ===== TABLET (769px - 1024px) ===== */
        @media (max-width: 1024px) and (min-width: 769px) {
            .exam-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 1rem;
            }
            .exam-card { padding: 1.25rem !important; }
            .filter-bar { flex-wrap: wrap; }
            .group-action-btn {
                flex-wrap: wrap;
                row-gap: 0.5rem;
            }
            .exam-actions .group-action-btn .btn {
                flex: 1 1 auto;
                min-width: 100px;
            }
        }
        
        /* ===== MOBILE (<= 768px) ===== */
        @media (max-width: 768px) {
            .main-content { padding: 1.5rem 1rem; }
            .page-title { font-size: 1rem; }
        
            .btn-success { font-size: 0.8rem; }
            .btn-primary { font-size: 0.8rem; }
        
            .exam-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .user-info { display: none; }
            .filter-bar { width: 100%; }
        
            .group-action {
                display: flex;
                gap: 5px;
                justify-content: space-between;
                width: 100%;
            }
            .group-action .btn {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        
            .exam-tab {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
        
            .exam-title {
                font-size: 1rem;
                word-break: break-word;
                overflow-wrap: break-word;
            }
        
            .page-header > div {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
                min-width: 0;
            }
        
            .filter-bar {
                width: 100%;
                min-width: 0;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem 0.6rem;
                box-sizing: border-box;
            }
            .filter-bar label {
                font-size: 0.8rem;
                flex: 0 0 auto;
            }
            .filter-bar input[type="date"] {
                flex: 1 1 110px;
                min-width: 0;
                max-width: 100%;
                width: auto;
                font-size: 0.8rem;
                padding: 0.4rem 0.5rem;
                box-sizing: border-box;
            }
            .filter-bar .filter-sep {
                display: none;
            }
            
            .filter-bar label[for="filterDateFrom"] {
                flex: 0 0 50px;
            }
            .filter-bar input#filterDateFrom {
                flex: 1 1 auto;
                min-width: 0;
                max-width: calc(100% - 90px); 
                box-sizing: border-box;
                font-size: 0.8rem;
                padding: 0.4rem 0.5rem;
            }
            
            .filter-bar label[for="filterDateTo"] {
                flex: 0 0 55px;
                margin-left: 1.6rem;
            }
            .filter-bar input#filterDateTo {
                flex: 1 1 auto;
                min-width: 0;
                max-width: calc(100% - 90px);
                box-sizing: border-box;
                font-size: 0.8rem;
                padding: 0.4rem 0.5rem;
            }
        
            .filter-bar .btn-reset-filter {
                flex: 0 0 auto;
                margin-left: auto;
            }
        
            .exam-card {
                padding: 1rem !important;
                overflow: hidden;
            }
            .exam-info-item {
                font-size: 0.85rem;
                word-break: break-word;
                overflow-wrap: break-word;
            }
        
            .exam-actions > div:first-child {
                flex-wrap: wrap;
                gap: 8px !important;
            }
        
            .group-action-btn {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.6rem !important;
                margin: 1rem 0 0 0 !important;
                width: 100%;
                box-sizing: border-box;
            }
        
            .exam-actions .group-action-btn .btn {
                width: 100% !important;
                max-width: none !important;
                min-width: 0;
                margin: 0 !important;
                box-sizing: border-box;
                font-size: 0.78rem;
                padding: 0.6rem 0.5rem !important;
                border-radius: 10px !important;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.35rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }
        
        /* ===== EXTRA SMALL PHONE (<= 420px) ===== */
        @media (max-width: 420px) {
            .exam-actions .group-action-btn .btn {
                flex: 1 1 100%;
            }
            .exam-info-item span {
                font-size: 0.8rem;
            }
        }
    </style>
    <!-- MathJax config harus ada di head, sebelum navbar yang mungkin load script-nya -->
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
</head>
<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1 class="page-title">Daftar Ujian</h1>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">

                <!-- Filter Tanggal -->
                <?php if ($user['role'] !== 'siswa'): ?>
                <div class="filter-bar">
                    <i class="fas fa-calendar-alt" style="color:var(--primary); font-size:0.875rem;"></i>
                    <label for="filterDateFrom">Dari</label>
                    <input type="date" id="filterDateFrom" onchange="applyDateFilter()">
                    <span class="filter-sep">—</span>
                    <label for="filterDateTo">Sampai</label>
                    <input type="date" id="filterDateTo" onchange="applyDateFilter()">
                    <button class="btn-reset-filter" id="btnResetFilter" onclick="resetDateFilter()" title="Reset filter">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="group-action">
                    <a href="<?= config('app_url') ?>/exam/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Ujian Baru
                    </a>
                    <a href="<?= config('app_url') ?>/exam/monitor" class="btn btn-success">
                        <i class="fas fa-desktop"></i> Monitor Ujian
                    </a>
                </div>

                <?php endif; ?>
            </div>
        </div>
        
        <div class="exam-tab">
            <?php if (!$forceTabPpdb): ?>
                <a href="<?= config('app_url') ?>/exam?tab=reguler"
                   style="padding:0.75rem 1.5rem; font-weight:600; font-size:0.95rem; text-decoration:none; border-bottom:3px solid <?= $activeTab==='reguler' ? '#6366f1' : 'transparent' ?>; color:<?= $activeTab==='reguler' ? '#6366f1' : '#64748b' ?>; margin-bottom:-2px;">
                    <i class="fas fa-school me-1"></i> Ujian Sekolah
                </a>
            <?php endif; ?>
            
            <a href="<?= config('app_url') ?>/exam?tab=ppdb"
               style="padding:0.75rem 1.5rem; font-weight:600; font-size:0.95rem; text-decoration:none; border-bottom:3px solid <?= $activeTab==='ppdb' ? '#10b981' : 'transparent' ?>; color:<?= $activeTab==='ppdb' ? '#10b981' : '#64748b' ?>; margin-bottom:-2px;">
                <i class="fas fa-user-graduate me-1"></i> Ujian Saringan Masuk (PPDB)
            </a>
        </div>
        <br>
        <div class="exam-grid" id="examGrid">
            <?php foreach ($exams as $exam): ?>
                <div class="exam-card"
                     data-exam-id="<?= $exam['id'] ?>"
                     data-exam-status="<?= htmlspecialchars($exam['exam_status'] ?? '') ?>"
                     data-published="<?= $exam['status'] === 'published' ? '1' : '0' ?>"
                     data-pdf="<?= htmlspecialchars($exam['exam_pdf'] ?? '') ?>"
                     data-start="<?= date('Y-m-d', strtotime($exam['start_time'])) ?>">

                    <h3 class="exam-title"><?= htmlspecialchars($exam['title'] ?? 'Ujian') ?></h3>
                    
                    <div class="exam-info">
                        <div class="exam-info-item">
                            <i class="fas fa-book"></i>
                            <span><?= htmlspecialchars($exam['subject_name'] ?? 'Mata Pelajaran') ?></span>
                            <?php if (($exam['exam_type'] ?? 'single') === 'multi'): ?>
                                <span class="badge" style="background:#fef3c7; color:#92400e; margin-left:0.5rem;">
                                    <i class="fas fa-layer-group"></i> Multi
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="exam-info-item">
                            <i class="fas fa-clock"></i>
                            <span><?= $exam['duration'] ?> menit</span>
                        </div>
                        <div class="exam-info-item">
                            <i class="fas fa-calendar"></i>
                            <span><?= formatWaktuIndonesia($exam['start_time'], 'd M Y, H:i') ?></span> - <span><?= formatWaktuIndonesia($exam['end_time'], 'd M Y, H:i') ?></span>
                        </div>
                        
                        <?php if ($user['role'] !== 'siswa'): ?>
                            <div class="exam-info-item" style="color:<?= $exam['participant_class_count'] > 0 ? '#dc2626' : '#16a34a' ?>;">
                                <i class="fas fa-<?= $exam['participant_class_count'] > 0 ? 'lock' : 'globe' ?>"></i>
                                <span>
                                    <?php if ($exam['participant_class_count'] > 0): ?>
                                        Terbatas: <?= htmlspecialchars($exam['participant_classes']) ?>
                                    <?php else: ?>
                                        Semua Siswa (Publik)
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($user['role'] === 'admin' && !empty($exam['creator_name'])): ?>
                            <div class="exam-info-item" style="color:#7c3aed;">
                                <i class="fas fa-user-edit"></i>
                                <span>Dibuat oleh: <?= htmlspecialchars($exam['creator_name']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (($exam['randomize_questions'] ?? 0) == 1): ?>
                            <div class="exam-info-item" style="color:#6366f1;">
                                <i class="fas fa-random"></i>
                                <span>Soal Diacak</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="exam-actions">
                        <?php if ($user['role'] === 'siswa'): ?>
                            <?php 
                            $now = date('Y-m-d H:i:s');
                            $is_upcoming = isset($exam['exam_status']) && $exam['exam_status'] === 'upcoming';
                            $is_active   = isset($exam['exam_status']) && $exam['exam_status'] === 'active';
                            $can_access  = isset($exam['can_access']) && $exam['can_access'];
                            ?>
                            <?php if (!$can_access): ?>
                                <button class="btn" style="background:#fef2f2; color:#dc2626; border:none; padding:0.625rem 1.25rem; border-radius:0.5rem; font-weight:600; cursor:not-allowed;" disabled>
                                    <i class="fas fa-lock"></i> Tidak Tersedia
                                </button>
                                <small style="color:#64748b; display:block; margin-top:0.5rem;">Ujian terbatas untuk kelas tertentu</small>
                            <?php elseif ($is_upcoming): ?>
                                <button class="btn exam-status-btn" style="background:#fef3c7; color:#92400e; border:none; padding:0.625rem 1.25rem; border-radius:0.5rem; font-weight:600; cursor:not-allowed;" disabled>
                                    <i class="fas fa-clock"></i> Belum Dimulai
                                </button>
                                <small style="color:#64748b; display:block; margin-top:0.5rem;">
                                    Ujian dimulai: <?= formatWaktuIndonesia($exam['start_time'], 'd M Y, H:i') ?>
                                </small>
                            <?php elseif (!$is_active): ?>
                                <button class="btn exam-status-btn" style="background:#f1f5f9; color:#475569; border:none; padding:0.625rem 1.25rem; border-radius:0.5rem; font-weight:600; cursor:not-allowed;" disabled>
                                    <i class="fas fa-times-circle"></i> Ujian Berakhir
                                </button>
                            <?php else: ?>
                                <?php if (!empty($exam['already_submitted'])): ?>
                                    <button class="btn exam-status-btn" style="background:#e0e7ff; color:#3730a3; border:none; padding:0.625rem 1.25rem; border-radius:0.5rem; font-weight:600; cursor:not-allowed;" disabled>
                                        <i class="fas fa-check-circle"></i> Sudah Dikerjakan
                                    </button>
                                <?php else: ?>
                                    <a href="<?= config('app_url') ?>/exam/start/<?= $exam['id'] ?>" class="btn btn-success exam-start-btn">
                                        <i class="fas fa-play"></i> Mulai Ujian
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:1rem;">
                                <span class="badge <?= $exam['status'] === 'published' ? 'success' : ($exam['status'] === 'draft' ? 'secondary' : 'warning') ?>"
                                      style="font-size:0.9rem; padding:6px 12px;">
                                    <?php if ($exam['status'] === 'published'): ?>
                                        <i class="fas fa-check-circle"></i> Published
                                    <?php elseif ($exam['status'] === 'draft'): ?>
                                        <i class="fas fa-edit"></i> Draft
                                    <?php else: ?>
                                        <i class="fas fa-question-circle"></i> <?= ucfirst($exam['status']) ?>
                                    <?php endif; ?>
                                </span>
                                <?php if ($exam['status'] !== 'published'): ?>
                                    <button onclick="quickPublishExam(<?= $exam['id'] ?>)"
                                            class="btn btn-success btn-sm"
                                            style="font-size:0.8rem; padding:4px 8px;">
                                        <i class="fas fa-rocket"></i> Publish
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="group-action-btn" style="display:flex; gap:0.5rem; margin-top:1rem;">
                                <a href="<?= config('app_url') ?>/exam/edit/<?= $exam['id'] ?>" class="btn btn-warning btn-sm" style="background:#fef3c7; color:#92400e; border:none;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="<?= config('app_url') ?>/exam/assignQuestions/<?= $exam['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-cog"></i> Kelola Soal
                                </a>
                                <?php if (!empty($exam['exam_pdf'])): ?>
                                    <a href="<?= config('app_url') ?>/uploads/exams/<?= htmlspecialchars($exam['exam_pdf']) ?>"
                                       target="_blank" rel="noopener"
                                       class="btn btn-sm" style="background:#dbeafe; color:#1d4ed8; border:none;">
                                        <i class="fas fa-file-pdf"></i> Buka PDF
                                    </a>
                                <?php endif; ?>
                                <button onclick="previewExamQuestions(<?= $exam['id'] ?>, '<?= htmlspecialchars($exam['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($exam['exam_pdf'] ?? '', ENT_QUOTES) ?>')"
                                        class="btn btn-sm" style="background:#ede9fe; color:#5b21b6; border:none;">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="deleteExam(<?= $exam['id'] ?>, '<?= htmlspecialchars($exam['title'], ENT_QUOTES) ?>')"
                                        class="btn btn-danger btn-sm" style="background:#fecaca; color:#dc2626; border:none;">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav style="margin-top:2rem; display:flex; justify-content:center; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <div style="font-size:0.875rem; color:#64748b; white-space:nowrap;">
                Halaman <strong><?= htmlspecialchars($page) ?></strong> dari <strong><?= htmlspecialchars($totalPages) ?></strong> (<?= htmlspecialchars($totalExams) ?> ujian)
            </div>

            <?php if ($page > 1): ?>
                <a href="<?= config('app_url') ?>/exam?tab=<?= htmlspecialchars($activeTab) ?>&amp;page=<?= $page - 1 ?>" class="btn" style="padding:0.5rem 1rem; background:var(--primary); color:white; border:none; border-radius:0.5rem; font-weight:600; text-decoration:none;">&larr; Sebelumnya</a>
            <?php else: ?>
                <span style="padding:0.5rem 1rem; color:#cbd5e1; border-radius:0.5rem;">&larr; Sebelumnya</span>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1): ?>
                <a href="<?= config('app_url') ?>/exam?tab=<?= htmlspecialchars($activeTab) ?>&amp;page=1" class="btn" style="padding:0.5rem 1rem; background:var(--primary); color:white; border:none; border-radius:0.5rem; font-weight:600; text-decoration:none;">1</a>
                <?php if ($start > 2): ?><span style="color:#94a3b8;">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                    <span style="padding:0.5rem 1rem; background:#f1f5f9; color:#1e293b; border:1px solid var(--primary); border-radius:0.5rem; font-weight:700;"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= config('app_url') ?>/exam?tab=<?= htmlspecialchars($activeTab) ?>&amp;page=<?= $i ?>" class="btn" style="padding:0.5rem 1rem; background:#f8fafc; color:#475569; border:1px solid var(--border); border-radius:0.5rem; font-weight:600; text-decoration:none;"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span style="color:#94a3b8;">…</span><?php endif; ?>
                <a href="<?= config('app_url') ?>/exam?tab=<?= htmlspecialchars($activeTab) ?>&amp;page=<?= $totalPages ?>" class="btn" style="padding:0.5rem 1rem; background:var(--primary); color:white; border:none; border-radius:0.5rem; font-weight:600; text-decoration:none;"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= config('app_url') ?>/exam?tab=<?= htmlspecialchars($activeTab) ?>&amp;page=<?= $page + 1 ?>" class="btn" style="padding:0.5rem 1rem; background:var(--primary); color:white; border:none; border-radius:0.5rem; font-weight:600; text-decoration:none;">Selanjutnya &rarr;</a>
            <?php else: ?>
                <span style="padding:0.5rem 1rem; color:#cbd5e1; border-radius:0.5rem;">Selanjutnya &rarr;</span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <!-- Modal Preview Soal -->
        <div id="previewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
            <div style="background:white; max-width:800px; margin:2rem auto; border-radius:1rem; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="background:#6366f1; padding:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h3 style="color:white; margin:0; font-size:1.25rem; font-weight:700;">
                            <i class="fas fa-eye"></i> Preview Soal & Kunci Jawaban
                        </h3>
                        <p id="previewExamTitle" style="color:rgba(255,255,255,0.8); margin:0.25rem 0 0; font-size:0.875rem;"></p>
                    </div>
                    <button onclick="closePreviewModal()" style="background:rgba(255,255,255,0.2); border:none; color:white; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:1.1rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="previewStats" style="display:none; background:#f8fafc; padding:1rem 1.5rem; border-bottom:1px solid #e2e8f0; display:flex; gap:2rem; flex-wrap:wrap;">
                    <div style="text-align:center;"><div id="statTotal" style="font-size:1.5rem; font-weight:800; color:#6366f1;">0</div><div style="font-size:0.75rem; color:#64748b;">Total Soal</div></div>
                    <div style="text-align:center;"><div id="statMC" style="font-size:1.5rem; font-weight:800; color:#10b981;">0</div><div style="font-size:0.75rem; color:#64748b;">Pilihan Ganda</div></div>
                    <div style="text-align:center;"><div id="statEssay" style="font-size:1.5rem; font-weight:800; color:#f59e0b;">0</div><div style="font-size:0.75rem; color:#64748b;">Essay/Isian</div></div>
                    <div style="text-align:center;"><div id="statTkp" style="font-size:1.5rem; font-weight:800; color:#db2777;">0</div><div style="font-size:0.75rem; color:#64748b;">TKP</div></div>
                    <div style="text-align:center;"><div id="statPoints" style="font-size:1.5rem; font-weight:800; color:#3b82f6;">0</div><div style="font-size:0.75rem; color:#64748b;">Total Poin</div></div>
                </div>
                <div id="previewBody" style="padding:1.5rem; max-height:70vh; overflow-y:auto;"></div>
                <div style="padding:1rem 1.5rem; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:0.75rem;">
                    <button onclick="closePreviewModal()" style="background:white; border:1px solid #e2e8f0; color:#64748b; padding:0.625rem 1.25rem; border-radius:0.5rem; font-weight:600; cursor:pointer;">Tutup</button>
                    <a id="manageQuestionsLink" href="#" style="background:#6366f1; color:white; padding:0.625rem 1.25rem; border-radius:0.5rem; font-weight:600; text-decoration:none;">
                        <i class="fas fa-cog"></i> Kelola Soal
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>

    <script>
        function applyDateFilter() {
            const from     = document.getElementById('filterDateFrom').value;
            const to       = document.getElementById('filterDateTo').value;
            const resetBtn = document.getElementById('btnResetFilter');
        
            resetBtn.style.display = (from || to) ? 'inline-flex' : 'none';
        
            let visibleCount = 0;
            document.querySelectorAll('.exam-card').forEach(card => {
                const examDate = card.dataset.start;
                let show = true;
                if (from && examDate && examDate < from) show = false;
                if (to   && examDate && examDate > to)   show = false;
                card.classList.toggle('hidden-by-filter', !show);
                if (show) visibleCount++;
            });
        
            let emptyMsg = document.getElementById('filterEmptyMsg');
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.id = 'filterEmptyMsg';
                emptyMsg.style.cssText = 'grid-column:1/-1; text-align:center; padding:3rem; color:#64748b;';
                emptyMsg.innerHTML = '<i class="fas fa-search" style="font-size:2rem; opacity:0.4; display:block; margin-bottom:0.75rem;"></i>'
                                   + '<p style="font-weight:600; margin:0;">Tidak ada ujian pada rentang tanggal ini</p>';
                document.getElementById('examGrid').appendChild(emptyMsg);
            }
            emptyMsg.style.display = (visibleCount === 0 && (from || to)) ? 'block' : 'none';
        }
        
        function resetDateFilter() {
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value   = '';
            document.getElementById('btnResetFilter').style.display = 'none';
            document.querySelectorAll('.exam-card').forEach(c => c.classList.remove('hidden-by-filter'));
            const msg = document.getElementById('filterEmptyMsg');
            if (msg) msg.style.display = 'none';
        }
    </script>

    <script>
    // ═══════════════════════════════════════════════
    // BASE URL
    // ═══════════════════════════════════════════════
    const baseUrl = '<?= config('app_url') ?>';

    <?php if ($user['role'] === 'siswa'): ?>
    // ── Real-time polling untuk siswa ──────────────────────────────────
    (function() {
        const POLL_INTERVAL = 15000;
        let pollTimer = null;

        function getExamIds() {
            return Array.from(document.querySelectorAll('.exam-card[data-exam-id]'))
                        .map(el => el.dataset.examId).filter(Boolean);
        }

        function updateCard(card, data) {
            const actions = card.querySelector('.exam-actions');
            if (!actions) return;
            const examId = card.dataset.examId;

            if (data.status !== 'published' || !data.can_access) {
                card.style.transition = 'opacity 0.5s';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 500);
                showToast('Ujian telah ditarik oleh pengawas.', 'warning');
                return;
            }

            if (data.already_submitted) {
                const startBtn = actions.querySelector('.exam-start-btn');
                if (startBtn) {
                    startBtn.outerHTML = '<button class="btn exam-status-btn" style="background:#e0e7ff;color:#3730a3;border:none;padding:0.625rem 1.25rem;border-radius:0.5rem;font-weight:600;cursor:not-allowed;" disabled><i class="fas fa-check-circle"></i> Sudah Dikerjakan</button>';
                }
                return;
            }

            const prevStatus    = card.dataset.examStatus;
            const prevPublished = card.dataset.published;

            if (data.exam_status !== prevStatus || data.status !== (prevPublished === '1' ? 'published' : 'draft')) {
                card.dataset.examStatus = data.exam_status;
                card.dataset.published  = data.status === 'published' ? '1' : '0';

                const statusBtn = actions.querySelector('.exam-status-btn');
                const startBtn  = actions.querySelector('.exam-start-btn');

                if (data.exam_status === 'upcoming') {
                    if (startBtn) startBtn.outerHTML = '<button class="btn exam-status-btn" style="background:#fef3c7;color:#92400e;border:none;padding:0.625rem 1.25rem;border-radius:0.5rem;font-weight:600;cursor:not-allowed;" disabled><i class="fas fa-clock"></i> Belum Dimulai</button>';
                } else if (data.exam_status === 'active') {
                    if (statusBtn && !startBtn) {
                        statusBtn.outerHTML = '<a href="' + baseUrl + '/exam/start/' + examId + '" class="btn btn-success exam-start-btn"><i class="fas fa-play"></i> Mulai Ujian</a>';
                        showToast('Ujian sudah bisa dimulai!', 'success');
                    }
                } else if (data.exam_status === 'ended') {
                    if (startBtn) startBtn.outerHTML = '<button class="btn exam-status-btn" style="background:#f1f5f9;color:#475569;border:none;padding:0.625rem 1.25rem;border-radius:0.5rem;font-weight:600;cursor:not-allowed;" disabled><i class="fas fa-times-circle"></i> Ujian Berakhir</button>';
                }
            }
        }

        function poll() {
            const ids = getExamIds();
            if (ids.length === 0) return;
            fetch(baseUrl + '/exam/statusCheck?ids=' + ids.join(','), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                Object.entries(data.exams).forEach(([id, info]) => {
                    const card = document.querySelector('.exam-card[data-exam-id="' + id + '"]');
                    if (card) updateCard(card, info);
                });
            })
            .catch(() => {});
        }

        function showToast(msg, type) {
            const colors = { success: '#10b981', warning: '#f59e0b', info: '#6366f1' };
            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;top:20px;right:20px;background:' + (colors[type]||'#6366f1') + ';color:white;padding:0.875rem 1.25rem;border-radius:0.75rem;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);font-weight:600;font-size:0.875rem;max-width:320px;';
            toast.innerHTML = '<i class="fas fa-bell"></i> ' + msg;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity='0'; toast.style.transition='opacity 0.5s'; setTimeout(()=>toast.remove(),500); }, 5000);
        }

        poll();
        pollTimer = setInterval(poll, POLL_INTERVAL);
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) { clearInterval(pollTimer); }
            else { poll(); pollTimer = setInterval(poll, POLL_INTERVAL); }
        });
    })();
    <?php endif; ?>
    
        function buildPdfViewUrl(pdfPath) {
            const filename = pdfPath.split('/').pop();
            return `${baseUrl}/question/viewPdf?file=${encodeURIComponent(filename)}`;
        }
        
        function renderPdfFile(q) {
            if (!q.pdf_file) return '';
            const pdfUrl = buildPdfViewUrl(q.pdf_file);
            return `
            <div style="margin-bottom:0.75rem;">
                <a href="${pdfUrl}" target="_blank" rel="noopener"
                   style="display:inline-flex; align-items:center; gap:0.5rem; background:#fee2e2; color:#b91c1c; padding:0.5rem 0.9rem; border-radius:0.5rem; font-size:0.825rem; font-weight:600; text-decoration:none;">
                    <i class="fas fa-file-pdf"></i> Buka File PDF Soal
                </a>
            </div>`;
        }

    // ── Preview Soal ──────────────────────────────────────────────────
        function previewExamQuestions(examId, examTitle, examPdf) {
        document.getElementById('previewModal').style.display = 'block';
        document.getElementById('previewExamTitle').textContent = examTitle;
        document.getElementById('manageQuestionsLink').href = baseUrl + '/exam/assignQuestions/' + examId;
        document.getElementById('previewStats').style.display = 'none';
    
        // ── Jika ujian berbasis PDF (exams.exam_pdf), tampilkan viewer PDF langsung ──
        if (examPdf) {
            document.getElementById('previewBody').innerHTML = `
                <div style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-weight:600; color:#1e293b;"><i class="fas fa-file-pdf" style="color:#dc2626;"></i> File Soal (PDF)</span>
                    <a href="${baseUrl}/uploads/exams/${examPdf}" target="_blank" rel="noopener"
                       style="background:#6366f1; color:white; padding:0.4rem 0.9rem; border-radius:0.5rem; font-size:0.8rem; font-weight:600; text-decoration:none;">
                        <i class="fas fa-external-link-alt"></i> Buka di Tab Baru
                    </a>
                </div>
                <iframe src="${baseUrl}/uploads/exams/${examPdf}"
                        style="width:100%; height:70vh; border:1px solid #e2e8f0; border-radius:0.5rem;"></iframe>
            `;
            return;
        }
    
        document.getElementById('previewBody').innerHTML = `
            <div style="text-align:center; padding:3rem; color:#64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:#6366f1;"></i>
                <p style="margin-top:1rem; font-weight:600;">Memuat soal...</p>
            </div>`;
    
        fetch(`${baseUrl}/exam/previewQuestions/${examId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('previewBody').innerHTML = `<div style="text-align:center; padding:3rem; color:#dc2626;"><i class="fas fa-exclamation-circle" style="font-size:2rem;"></i><p style="margin-top:1rem;">${data.message || 'Gagal memuat soal'}</p></div>`;
                return;
            }

            const questions = data.questions;
            let mc = 0, essay = 0, tkp = 0, totalPoints = 0;
            questions.forEach(q => {
                if (q.type === 'multiple_choice' || q.type === 'listening') mc++;
                else if (q.type === 'tkp') tkp++;
                else essay++;
                totalPoints += parseInt(q.points || 1);
            });

            document.getElementById('statTotal').textContent  = questions.length;
            document.getElementById('statMC').textContent     = mc;
            document.getElementById('statEssay').textContent  = essay;
            document.getElementById('statTkp').textContent    = tkp;
            document.getElementById('statPoints').textContent = totalPoints;
            document.getElementById('previewStats').style.display = 'flex';

            if (questions.length === 0) {
                document.getElementById('previewBody').innerHTML = `<div style="text-align:center; padding:3rem; color:#64748b;"><i class="fas fa-inbox" style="font-size:2rem;"></i><p style="margin-top:1rem; font-weight:600;">Belum ada soal dalam ujian ini</p></div>`;
                return;
            }

            const typeLabel = {
                multiple_choice:   { label:'Pilihan Ganda', color:'#6366f1', bg:'#ede9fe' },
                essay:             { label:'Essay',          color:'#d97706', bg:'#fef3c7' },
                short_answer:      { label:'Isian Angka',    color:'#0369a1', bg:'#e0f2fe' },
                short_answer_text: { label:'Isian Kalimat',  color:'#0369a1', bg:'#e0f2fe' },
                listening:         { label:'Listening',      color:'#059669', bg:'#d1fae5' },
                tkp:               { label:'📊 TKP',         color:'#db2777', bg:'#fce7f3' },
            };
            const optColors = { A:'#6366f1', B:'#10b981', C:'#f59e0b', D:'#ef4444', E:'#8b5cf6' };
            
            const uniquePdfs = [...new Set(questions.map(q => q.pdf_file).filter(Boolean))];

            let pdfBannerHtml = '';
            if (uniquePdfs.length > 0) {
                pdfBannerHtml = `
                <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:0.75rem; padding:1rem 1.25rem; margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
                    <div style="display:flex; align-items:center; gap:0.6rem;">
                        <i class="fas fa-file-pdf" style="color:#dc2626; font-size:1.25rem;"></i>
                        <div>
                            <div style="font-weight:700; color:#991b1b; font-size:0.9rem;">Ujian ini berbasis file PDF</div>
                            <div style="font-size:0.75rem; color:#7f1d1d;">${uniquePdfs.length} file terdeteksi dari soal-soal di ujian ini</div>
                        </div>
                    </div>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                        ${uniquePdfs.map((p, i) => `
                            <a href="${buildPdfViewUrl(p)}" target="_blank" rel="noopener"
                               style="background:#dc2626; color:white; padding:0.5rem 1rem; border-radius:0.5rem; font-size:0.8rem; font-weight:600; text-decoration:none;">
                                <i class="fas fa-external-link-alt"></i> Buka File ${uniquePdfs.length > 1 ? (i+1) : ''}
                            </a>`).join('')}
                    </div>
                </div>`;
            }
            let html = '';
            questions.forEach((q, idx) => {
                const t = typeLabel[q.type] || { label:q.type, color:'#64748b', bg:'#f1f5f9' };
                html += `
                <div style="border:1px solid #e2e8f0; border-radius:0.75rem; padding:1.25rem; margin-bottom:1rem; background:white;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span style="background:#f1f5f9; color:#475569; font-weight:700; font-size:0.75rem; padding:0.25rem 0.625rem; border-radius:0.375rem;">No. ${idx+1}</span>
                            <span style="background:${t.bg}; color:${t.color}; font-weight:600; font-size:0.75rem; padding:0.25rem 0.625rem; border-radius:0.375rem;">${t.label}</span>
                            ${q.sub_topic ? `<span style="background:#f0fdf4; color:#15803d; font-size:0.75rem; padding:0.25rem 0.625rem; border-radius:0.375rem; font-weight:600;">${q.sub_topic}</span>` : ''}
                        </div>
                        <span style="background:#eff6ff; color:#1d4ed8; font-weight:700; font-size:0.75rem; padding:0.25rem 0.625rem; border-radius:0.375rem;">${q.points} poin</span>
                    </div>
                    ${q.question_text ? `<div style="font-size:0.9375rem; line-height:1.7; color:#1e293b; margin-bottom:0.75rem; white-space:pre-line;">${q.question_text}</div>` : ''}
                    ${q.question_image ? `<div style="margin-bottom:0.75rem;"><img src="${baseUrl}/uploads/questions/${q.question_image}" style="max-width:100%; border-radius:0.5rem; border:1px solid #e2e8f0; cursor:pointer;" onclick="toggleZoom(this)" title="Klik untuk zoom"></div>` : ''}
                    ${renderPdfFile(q)}
                    ${renderOptions(q, optColors)}
                    ${renderAnswer(q)}
                </div>`;
            });

            document.getElementById('previewBody').innerHTML = html;
            setTimeout(() => {
                if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                    MathJax.typesetPromise([document.getElementById('previewBody')]).catch(err => console.log(err));
                }
            }, 300);
        })
        .catch(() => {
            document.getElementById('previewBody').innerHTML = `<div style="text-align:center; padding:3rem; color:#dc2626;"><i class="fas fa-exclamation-circle" style="font-size:2rem;"></i><p style="margin-top:1rem;">Gagal memuat soal. Coba lagi.</p></div>`;
        });
    }
    function renderTkpOptions(q) {
        let scores = {};
        try { scores = q.option_scores ? JSON.parse(q.option_scores) : {}; } catch (e) { scores = {}; }

        const opts = ['a', 'b', 'c', 'd', 'e'];
        let html = '<div style="margin-top:0.5rem;">';
        opts.forEach(k => {
            if (!q['option_' + k]) return;
            const letter = k.toUpperCase();
            const val = scores[k] !== undefined ? scores[k] : '-';
            html += `<div style="display:flex; align-items:center; gap:0.625rem; padding:0.5rem 0.75rem; margin-bottom:0.375rem; border-radius:0.5rem; background:#f8fafc; border:1px solid #e2e8f0;">
                <span style="font-weight:700; color:#db2777; min-width:1.25rem;">${letter}.</span>
                <span style="font-size:0.875rem; color:#374151; flex:1;">${q['option_' + k]}</span>
                <span style="background:#fce7f3; color:#9d174d; font-weight:700; font-size:0.75rem; padding:0.2rem 0.6rem; border-radius:0.375rem; white-space:nowrap;">Nilai: ${val}</span>
            </div>`;
        });
        html += '</div>';
        return html;
    }

    function renderOptions(q, optColors) {
        if (q.type === 'tkp') return renderTkpOptions(q);
        if (!['multiple_choice', 'listening'].includes(q.type)) return '';
        let html = '';
        if (q.type === 'listening' && q.audio_file) {
            html += `<div style="background:#eff6ff; border:1.5px solid #93c5fd; border-radius:0.75rem; padding:1rem; margin-bottom:0.75rem;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                    <i class="fas fa-headphones" style="color:#3b82f6; font-size:1.1rem;"></i>
                    <span style="font-weight:700; color:#1d4ed8; font-size:0.875rem;">Audio Listening</span>
                    <span style="background:#dbeafe; color:#1d4ed8; font-size:0.7rem; padding:0.2rem 0.5rem; border-radius:0.25rem; font-weight:600;">Maks. ${q.max_plays || 2}x putar</span>
                </div>
                <audio controls style="width:100%; border-radius:0.5rem;">
                    <source src="${baseUrl}/uploads/audio/${q.audio_file}">Browser tidak mendukung audio.
                </audio></div>`;
        }
        const opts = ['a','b','c','d','e'];
        const correct = (q.correct_answer || '').toUpperCase();
        html += '<div style="margin-top:0.5rem;">';
        opts.forEach(k => {
            if (!q['option_' + k]) return;
            const letter = k.toUpperCase();
            const isCorrect = letter === correct && !q.hide_correct_answer;
            html += `<div style="display:flex; align-items:flex-start; gap:0.625rem; padding:0.5rem 0.75rem; margin-bottom:0.375rem; border-radius:0.5rem; background:${isCorrect ? '#d1fae5' : '#f8fafc'}; border:1px solid ${isCorrect ? '#10b981' : '#e2e8f0'};">
                <span style="font-weight:700; color:${isCorrect ? '#059669' : optColors[letter]}; min-width:1.25rem;">${letter}.</span>
                <span style="font-size:0.875rem; color:${isCorrect ? '#065f46' : '#374151'}; flex:1;">${q['option_' + k]}</span>
                ${isCorrect ? '<i class="fas fa-check-circle" style="color:#10b981; margin-left:auto;"></i>' : ''}
            </div>`;
        });
        html += '</div>';
        return html;
    }

    function renderAnswer(q) {
        if (q.type === 'tkp') {
            let scores = {};
            try { scores = q.option_scores ? JSON.parse(q.option_scores) : {}; } catch (e) { scores = {}; }
            const scoreHtml = ['a','b','c','d','e'].map(k => {
                const val = scores[k] !== undefined ? scores[k] : '-';
                return `<span style="display:inline-block;margin:0 6px 4px 0;background:white;border:1px solid #f9a8d4;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;color:#831843;">${k.toUpperCase()}: ${val}</span>`;
            }).join('');
            return `<div style="margin-top:0.75rem; padding:0.625rem 0.875rem; background:#fce7f3; border-radius:0.5rem; border:1px solid #f9a8d4;">
                <div style="color:#9d174d; font-weight:700; font-size:0.8rem; margin-bottom:6px;"><i class="fas fa-chart-bar"></i> Skala Likert — skor tiap opsi:</div>
                ${scoreHtml}
            </div>`;
        }
        if (q.type === 'multiple_choice' || q.type === 'listening') {
            if (q.hide_correct_answer) return `<div style="margin-top:0.75rem; padding:0.625rem 0.875rem; background:#fef3c7; border-radius:0.5rem; border:1px solid #f59e0b;"><i class="fas fa-eye-slash" style="color:#d97706;"></i><span style="color:#92400e; font-weight:600; font-size:0.875rem; margin-left:0.25rem;">Kunci jawaban disembunyikan (koreksi manual)</span></div>`;
            return `<div style="margin-top:0.75rem; padding:0.625rem 0.875rem; background:#d1fae5; border-radius:0.5rem; border:1px solid #10b981; display:inline-flex; align-items:center; gap:0.5rem;"><i class="fas fa-key" style="color:#059669;"></i><span style="color:#065f46; font-weight:700; font-size:0.875rem;">Kunci: ${q.correct_answer}</span></div>`;
        }
        if (q.type === 'short_answer' || q.type === 'short_answer_text') {
            return `<div style="margin-top:0.75rem; padding:0.625rem 0.875rem; background:#e0f2fe; border-radius:0.5rem; border:1px solid #38bdf8; display:inline-flex; align-items:center; gap:0.5rem;"><i class="fas fa-key" style="color:#0369a1;"></i><span style="color:#0c4a6e; font-weight:700; font-size:0.875rem;">Kunci: ${q.correct_answer || '-'}</span></div>`;
        }
        if (q.type === 'essay') {
            return `<div style="margin-top:0.75rem; padding:0.625rem 0.875rem; background:#fef3c7; border-radius:0.5rem; border:1px solid #f59e0b;"><i class="fas fa-pen" style="color:#d97706;"></i><span style="color:#92400e; font-weight:600; font-size:0.875rem; margin-left:0.25rem;">Essay — dikoreksi manual oleh guru</span></div>`;
        }
        return '';
    }

    function closePreviewModal() { document.getElementById('previewModal').style.display = 'none'; }
    function toggleZoom(img) { img.style.maxWidth = img.style.maxWidth === '100%' ? '150%' : '100%'; }

    document.getElementById('previewModal').addEventListener('click', function(e) {
        if (e.target === this) closePreviewModal();
    });

    function deleteExam(examId, examTitle) {
        if (confirm(`Yakin ingin menghapus ujian "${examTitle}"?\n\nPerhatian: Semua data terkait ujian ini akan terhapus permanen, termasuk:\n- Soal-soal ujian\n- Jawaban siswa\n- Nilai dan riwayat ujian\n\nTindakan ini tidak dapat dibatalkan!`)) {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
            btn.disabled = true;
            fetch(`${baseUrl}/exam/delete/${examId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'confirm_delete=1'
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) return response.json();
                return response.text().then(text => { throw new Error('Server returned non-JSON: ' + text); });
            })
            .then(data => {
                if (data.success) { alert('✅ ' + data.message); window.location.reload(); }
                else { alert('❌ ' + (data.message || 'Gagal menghapus ujian')); btn.innerHTML = originalText; btn.disabled = false; }
            })
            .catch(error => { console.error('Error:', error); alert('❌ Terjadi kesalahan: ' + error.message); btn.innerHTML = originalText; btn.disabled = false; });
        }
    }

    function quickPublishExam(examId) {
        if (confirm('Publish ujian ini sekarang?\n\nUjian akan langsung tersedia untuk siswa.\nPastikan soal sudah ditambahkan.')) {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            btn.disabled = true;
            fetch(`${baseUrl}/exam/quickPublish/${examId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=publish'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const msg = document.createElement('div');
                    msg.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:1rem 1.5rem;border-radius:0.5rem;z-index:9999;';
                    msg.innerHTML = '<i class="fas fa-check-circle"></i> Ujian berhasil dipublish!';
                    document.body.appendChild(msg);
                    setTimeout(() => { msg.remove(); location.reload(); }, 2000);
                } else {
                    const errMsg = document.createElement('div');
                    errMsg.style.cssText = 'position:fixed;top:20px;right:20px;background:#ef4444;color:white;padding:1rem 1.5rem;border-radius:0.5rem;z-index:9999;';
                    errMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + data.message;
                    document.body.appendChild(errMsg);
                    setTimeout(() => errMsg.remove(), 5000);
                    btn.innerHTML = originalText; btn.disabled = false;
                }
            })
            .catch(error => { alert('Error: ' + error.message); btn.innerHTML = originalText; btn.disabled = false; });
        }
    }
    </script>
    <script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>
</html>