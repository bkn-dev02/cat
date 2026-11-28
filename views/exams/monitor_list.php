<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= asset_url('images/cat.png') ?>" type="image/x-icon">
    <title>Monitor Ujian - CAT System</title>
    <link href="<?= $bootstrapCssUrl ?>" rel="stylesheet">
    <link href="<?= $fontAwesomeCssUrl ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --success: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
            --dark: #1f2937;
            --muted: #6b7280;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            padding: 2rem 0;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1rem;
            padding: .5rem 1rem;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            font-size: .875rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .btn-back:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .page-header {
            background: rgba(255, 255, 255, .95);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ===== Grid 3 kolom ===== */
        .exam-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.25rem;
        }

        @media (max-width: 991.98px) {
            .exam-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .exam-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== Card ===== */
        .exam-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1.1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
            transition: box-shadow .2s ease, transform .2s ease;
        }

        .exam-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .1);
        }

        .exam-head {
            flex: 1;
        }

        /* mendorong statistik + tombol ke bawah */

        .exam-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .5rem;
        }

        .exam-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.35;
            color: var(--dark);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .exam-duration {
            flex-shrink: 0;
            padding: .2rem .55rem;
            border-radius: 8px;
            background: rgba(37, 99, 235, .1);
            color: var(--primary);
            font-size: .75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .exam-time {
            margin: .5rem 0 0;
            font-size: .8125rem;
            color: var(--muted);
        }

        /* ===== Statistik 3 baris ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .5rem;
            margin: 1rem 0;
        }

        .stat-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
        }

        .stat-row:first-child {
            grid-template-columns: 1fr;
        }

        .stat-item {
            text-align: center;
            padding: .6rem .5rem;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 10px;
        }

        .stat-number {
            display: block;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-label {
            font-size: .75rem;
            color: var(--muted);
        }

        /* ===== Tombol selalu di bawah ===== */
        .btn-monitor {
            display: block;
            width: 100%;
            padding: .6rem 1rem;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: .875rem;
            transition: background .2s ease;
        }

        .btn-monitor:hover {
            background: #1d4ed8;
            color: #fff;
        }

        /* ===== Pagination ===== */
        .pager-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
            margin-top: 1.75rem;
        }

        .pager-info {
            font-size: .8125rem;
            color: var(--muted);
        }

        .pager {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .35rem;
        }

        .pager a,
        .pager span {
            min-width: 38px;
            padding: .4rem .7rem;
            text-align: center;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: var(--dark);
            font-size: .875rem;
            text-decoration: none;
        }

        .pager a:hover {
            background: #f3f4f6;
        }

        .pager .active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            font-weight: 600;
        }

        .pager .disabled {
            color: #9ca3af;
            background: #f9fafb;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
            background: #fff;
            border-radius: 14px;
        }

        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            opacity: .5;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }
    </style>
</head>

<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <a href="<?= $appUrl ?>/exam" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Ujian
        </a>

        <div class="page-header">
            <h1 style="margin:0; font-size:1.5rem; font-weight:800; color:var(--dark);">
                <i class="fas fa-desktop" style="color:var(--primary);"></i>
                Monitor Ujian Real-time
            </h1>

            <div style="padding:.75rem 1rem; background:rgba(40,167,69,.1); border:1px solid rgba(40,167,69,.2); border-radius:8px;">
                <div style="display:flex; align-items:center; gap:.5rem;">
                    <div style="width:8px; height:8px; background:#28a745; border-radius:50%; animation:pulse 2s infinite;"></div>
                    <span style="color:#28a745; font-weight:600; font-size:14px;">System Online &amp; Ready</span>
                    <span style="color:#6c757d; font-size:12px; margin-left:auto;">
                        Last checked: <span id="last-check"><?= $lastCheck ?></span>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!$hasExams): ?>
            <div class="empty-state">
                <i class="fas fa-desktop"></i>
                <h3>Belum ada ujian aktif</h3>
                <p style="margin:0;">Ujian akan tampil di sini ketika ada siswa yang mengikuti atau baru menyelesaikan ujian.</p>
            </div>
        <?php else: ?>

            <div class="exam-grid">
                <?php foreach ($exams as $exam): ?>
                    <div class="exam-card">
                        <div class="exam-head">
                            <div class="exam-title-row">
                                <h4 class="exam-title" title="<?= htmlspecialchars($exam['title']) ?>">
                                    <?= htmlspecialchars($exam['title']) ?>
                                </h4>
                                <span class="exam-duration">
                                    <i class="far fa-clock"></i> <?= $exam['duration'] ?> mnt
                                </span>
                            </div>
                            <p class="exam-time">
                                <i class="far fa-calendar"></i>
                                <?= $exam['formatted_start_time'] ?>
                            </p>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-row">
                                <div class="stat-item">
                                    <span class="stat-number" style="color:var(--dark);"><?= $exam['total_count'] ?></span>
                                    <span class="stat-label">Total peserta</span>
                                </div>
                            </div>
                            <div class="stat-row">
                                <div class="stat-item">
                                    <span class="stat-number" style="color:var(--success);"><?= $exam['active_count'] ?></span>
                                    <span class="stat-label">Sedang ujian</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number" style="color:var(--warning);"><?= $exam['not_started_count'] ?></span>
                                    <span class="stat-label">Belum ujian</span>
                                </div>
                            </div>
                            <div class="stat-row">
                                <div class="stat-item">
                                    <span class="stat-number" style="color:var(--primary);"><?= $exam['submitted_count'] ?></span>
                                    <span class="stat-label">Sudah submit</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number" style="color:var(--danger);"><?= $exam['suspended_count'] ?? 0 ?></span>
                                    <span class="stat-label">Suspend</span>
                                </div>
                            </div>
                        </div>

                        <a href="<?= $appUrl ?>/exam/monitor/<?= $exam['id'] ?>" class="btn-monitor">
                            <i class="fas fa-eye"></i> Lihat detail
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($showPagination): ?>
                <nav class="pager-wrap" aria-label="Navigasi halaman ujian">
                    <div class="pager">
                        <?php if ($hasPreviousPage): ?>
                            <a href="<?= $baseUrl ?>?page=<?= $previousPage ?>" aria-label="Sebelumnya">&lsaquo;</a>
                        <?php else: ?>
                            <span class="disabled">&lsaquo;</span>
                        <?php endif; ?>

                        <?php foreach ($pages as $p): ?>
                            <?php if ($p === '...'): ?>
                                <span class="disabled">&hellip;</span>
                            <?php elseif ($p === $page): ?>
                                <span class="active"><?= $p ?></span>
                            <?php else: ?>
                                <a href="<?= $baseUrl ?>?page=<?= $p ?>"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if ($hasNextPage): ?>
                            <a href="<?= $baseUrl ?>?page=<?= $nextPage ?>" aria-label="Berikutnya">&rsaquo;</a>
                        <?php else: ?>
                            <span class="disabled">&rsaquo;</span>
                        <?php endif; ?>
                    </div>
                    <div class="pager-info">
                        Menampilkan <?= $from ?>–<?= $to ?> dari <?= $totalExams ?> ujian
                    </div>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script src="<?= $bootstrapJsUrl ?>"></script>
</body>

</html>