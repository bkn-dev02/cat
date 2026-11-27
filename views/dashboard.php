<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CAT Enterprise</title>
    <link rel="icon" type="image/png" href="<?= asset_url('cat.png') ?>">
    <link rel="manifest" href="<?= app_path_url('manifest.json') ?>">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="CAT Enterprise">
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('vendor/fontawesome/6.4.0/css/all.min.css') ?>">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        /* Navbar */
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand:hover {
            color: var(--primary-dark);
        }

        .nav-link {
            color: var(--dark);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            background: var(--light);
            color: var(--primary);
        }

        .nav-link.active {
            background: var(--primary);
            color: white;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--light);
            border-radius: 0.5rem;
            margin-right: 0.75rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--dark);
        }

        .btn-logout {
            background: white;
            border: 1px solid var(--border);
            color: var(--dark);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: var(--danger);
            border-color: var(--danger);
            color: white;
        }

        /* ── User Dropdown ── */
        .user-dropdown-wrap {
            position: relative;
        }

        .user-trigger {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: none;
            border: 1.5px solid var(--border);
            border-radius: 50px;
            padding: 0.3rem 0.9rem 0.3rem 0.3rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .user-trigger:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.04);
        }

        .user-trigger .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
            margin-right: 0;
        }

        .user-trigger .user-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.875rem;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-chevron {
            font-size: 0.65rem;
            color: #94a3b8;
            transition: transform 0.25s;
        }

        .user-chevron.open {
            transform: rotate(180deg);
        }

        .user-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: white;
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.13);
            min-width: 220px;
            padding: 0.5rem;
            z-index: 9999;
            animation: dropIn 0.18s ease;
        }

        .user-dropdown-menu.open {
            display: block;
        }

        @keyframes dropIn {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .udm-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.75rem 0.5rem;
        }

        .udm-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .udm-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.875rem;
        }

        .udm-role {
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 1px;
        }

        .udm-divider {
            height: 1px;
            background: var(--border);
            margin: 0.3rem 0;
        }

        .udm-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            text-decoration: none;
            transition: all 0.15s;
        }

        .udm-item:hover {
            background: rgba(99, 102, 241, 0.08);
            color: #6366f1;
            text-decoration: none;
        }

        .udm-item i {
            width: 16px;
            text-align: center;
            font-size: 0.8rem;
        }

        .udm-logout {
            color: #dc2626;
        }

        .udm-logout:hover {
            background: rgba(220, 38, 38, 0.08);
            color: #dc2626;
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 1rem;
        }

        /* Alert */
        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-icon.primary {
            background: #eef2ff;
            color: var(--primary);
        }

        .stat-icon.success {
            background: #d1fae5;
            color: var(--success);
        }

        .stat-icon.warning {
            background: #fef3c7;
            color: var(--warning);
        }

        .stat-icon.secondary {
            background: #f3e8ff;
            color: var(--secondary);
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.75rem;
        }

        .stat-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .stat-role {
            font-size: 0.875rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-stat {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-stat:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-1px);
        }

        /* Activity Card */
        .activity-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .activity-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .activity-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin: 0;
        }

        .table thead th {
            background: var(--light);
            color: #64748b;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background: var(--light);
        }

        /* Badge */
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.primary {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            background: var(--light);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-text {
            color: #64748b;
            font-size: 0.875rem;
        }

        .admin-area {
            display: flex;
            gap: 1rem;
        }

        .sync-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 2rem;
            width: 100%;
            padding: 1.5rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
            transition: all 0.25s ease;

        }

        .sync-card::after {
            content: "";
            position: absolute;
            top: -90px;
            right: -70px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.10), rgba(59, 130, 246, 0.03));
            pointer-events: none;

        }

        .sync-card:hover {
            transform: translateY(-3px);
            border-color: #c7d2fe;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.10);
        }

        .sync-card-icon {
            position: relative;
            z-index: 1;
            flex: 0 0 150px;
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: linear-gradient(145deg, #eff6ff, #eef2ff);
            color: #2563eb;
            overflow: hidden;
        }

        .sync-card-icon::before {
            content: "";
            position: absolute;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.08);
        }

        .sync-card-icon i {
            position: relative;
            z-index: 2;
            font-size: 4rem;

        }

        .sync-card-icon .sync-arrow {
            position: absolute;
            z-index: 3;
            right: 25px;
            bottom: 27px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #ffffff;
            color: #2563eb;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.20);
        }

        .sync-card-content {
            position: relative;
            z-index: 1;
            flex: 1;
            min-width: 0;
        }

        .sync-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.55rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .sync-card-title {
            margin: 0 0 0.45rem;
            color: #1e293b;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .sync-card-description {
            max-width: 650px;
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .sync-card-features {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem 1.5rem;
            margin-top: 1rem;
        }

        .sync-feature {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
        }

        .sync-feature i {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #ecfdf5;
            color: #10b981;
            font-size: 11px;

        }

        .sync-feature.refresh i {
            background: #eff6ff;
            color: #3b82f6;

        }

        .sync-feature+.sync-feature {
            padding-left: 1.5rem;
            border-left: 1px solid #e2e8f0;

        }

        .sync-card-action {
            position: relative;
            z-index: 2;
            flex: 0 0 auto;

        }

        .btn-sync {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            min-width: 175px;
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.20);
            transition: all 0.25s ease;

        }

        .btn-sync:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.28);

        }

        .btn-sync i {
            transition: transform 0.25s ease;

        }

        .btn-sync:hover i {
            transform: translateX(4px);

        }

        /* Style Mobile */
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem 1rem;
            }

            .page-title {
                display: none;
            }

            .page-subtitle {
                display: none;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .user-info {
                display: none;
            }

            .sync-card {
                flex-direction: column;
                align-items: stretch;
                gap: 1.25rem;
                padding: 1.25rem;

            }

            .sync-card-icon {
                flex: none;
                width: 100%;
                height: 120px;

            }

            .sync-card-icon i {
                font-size: 3.2rem;

            }

            .sync-card-action {
                width: 100%;

            }

            .btn-sync {
                width: 100%;

            }

            .sync-card-title {
                font-size: 1.25rem;

            }

            .sync-card-features {
                gap: 0.65rem;

            }

            .sync-feature+.sync-feature {
                padding-left: 0;
                border-left: none;

            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php require_once __DIR__ . '/partials/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Alert -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $_SESSION['success'];
                        unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">
                    <small id="current-time" style="color: #64748b; font-weight: 500;">
                        <?= formatWaktuIndonesia(waktuSekarang(), 'lengkap') ?>
                    </small>
                </p>
            </div>
            <?php if ($user['role'] !== 'siswa' && $user['role'] !== 'calon-siswa'): ?>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <button type="button" class="btn btn-stat" onclick="showExportModal()" style="background: var(--success);">
                        <i class="fas fa-download"></i> Export Nilai
                    </button>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="<?= config('app_url') ?>/dashboard/syncRecovery" class="btn btn-stat" style="background: #8b5cf6; text-decoration: none;">
                            <i class="fas fa-sync-alt"></i> Sync Recovery
                        </a>
                        <a href="<?= config('app_url') ?>/RECOVERY_SESI_BELUM_SUBMIT.php" class="btn btn-stat" style="background: #dc2626; text-decoration: none;">
                            <i class="fas fa-tools"></i> Recovery Sesi
                        </a>
                        <a href="<?= config('app_url') ?>/dashboard/auditAktivitas" class="btn btn-stat" style="background: #f59e0b; text-decoration: none;">
                            <i class="fas fa-history"></i> Audit Aktivitas
                        </a>
                        <a href="<?= config('app_url') ?>/admin/resetPassword" class="btn btn-stat" style="background: #f59e1f; color: #fff; text-decoration: none;">
                            <i class="fas fa-key"></i> Reset Password
                        </a>
                        <a href="<?= config('app_url') ?>/admin/resetSubmit" class="btn btn-stat" style="background: #f59e1f; color: #fff; text-decoration: none;">
                            <i class="fas fa-rotate-left"></i> Reset Submit
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Welcome Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="stat-name"><?= htmlspecialchars($user['full_name'] ?? $user['name'] ?? 'User') ?></div>
                <div class="stat-role">
                    <i class="fas fa-shield-alt"></i>
                    <span><?= ucfirst($user['role']) ?></span>
                </div>
                <?php if ($user['role'] === 'guru' && !empty($subjectName)): ?>
                    <div class="stat-role" style="margin-top:6px;color:#6366f1;">
                        <i class="fas fa-book"></i>
                        <span><?= htmlspecialchars($subjectName) ?></span>
                    </div>
                <?php elseif ($user['role'] === 'guru'): ?>
                    <div class="stat-role" style="margin-top:6px;color:#94a3b8;">
                        <i class="fas fa-book"></i>
                        <span>Mapel belum diassign</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($user['role'] === 'siswa' || $user['role'] === 'calon-siswa'): ?>
                <!-- Ujian Tersedia -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-label">Ujian Tersedia</div>
                    <div class="stat-value"><?= $availableExamCount ?></div>
                    <a href="<?= config('app_url') ?>/exam" class="btn-stat">
                        Lihat Ujian <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <!-- Ujian Selesai -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-label">Ujian Selesai</div>
                    <div class="stat-value"><?= $completedExamCount ?></div>
                </div>
            <?php elseif ($user['role'] === 'guru'): ?>
                <!-- Total Ujian -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-label">Total Ujian</div>
                    <div class="stat-value"><?= $totalUjian ?></div>
                    <a href="<?= config('app_url') ?>/exam" class="btn-stat">
                        Kelola Ujian <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <!-- Bank Soal -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon secondary">
                            <i class="fas fa-question-circle"></i>
                        </div>
                    </div>
                    <div class="stat-label">Bank Soal</div>
                    <div class="stat-value">
                        <?= $totalBankSoal ?>
                    </div>
                    <a href="<?= config('app_url') ?>/question" class="btn-stat">
                        Kelola Soal <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <!-- Total Ujian -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-label">Total Ujian</div>
                    <div class="stat-value"><?= $totalUjian ?></div>
                    <a href="<?= config('app_url') ?>/exam" class="btn-stat">
                        Kelola Ujian <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <!-- Bank Soal -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon secondary">
                            <i class="fas fa-question-circle"></i>
                        </div>
                    </div>
                    <div class="stat-label">Bank Soal</div>
                    <div class="stat-value"><?= $totalBankSoal ?></div>
                    <a href="<?= config('app_url') ?>/question" class="btn-stat">
                        Kelola Soal <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($user['role'] === 'admin'): ?>
            <div class="admin-area">
                <div class="sync-card">
                    <div class="sync-card-icon">
                        <i class="fas fa-cloud-arrow-down"></i>
                        <span class="sync-arrow"> <i class="fas fa-arrows-rotate"></i> </span>
                    </div>
                    <div class="sync-card-content">
                        <div class="sync-card-badge">
                            <i class="fas fa-shield-halved"></i> Admin Only
                        </div>
                        <h3 class="sync-card-title"> Sync Data E-recruitment </h3>
                        <p class="sync-card-description"> Sinkronisasi data dari sistem e-recruitment ke dalam sistem ujian.</p>
                    </div>
                    <div class="sync-card-action">
                        <a href="<?= config('app_url') ?>/sync/recruitment" class="btn-sync"> Sinkronisasi Data <i class="fas fa-arrow-right"></i> </a>
                    </div>
                </div>
                <div class="sync-card">
                    <div class="sync-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="sync-card-content">
                        <div class="sync-card-badge">
                            <i class="fas fa-shield-halved"></i> Admin Only
                        </div>
                        <h3 class="sync-card-title"> Buat Akun Baru </h3>
                    </div>
                    <div class="sync-card-action">
                        <button type="button" class="btn-sync" onclick="openCreateAccountModal()">
                            Tambah Akun <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- 1. MODAL TAMBAH AKUN BARU -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px; border:none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                    <form id="createAccountForm" autocomplete="off">
                        <div class="modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius:16px 16px 0 0;">
                            <h5 class="modal-title" id="createAccountModalLabel" style="font-weight:800; color:#1e293b; font-size: 1.1rem;">
                                <i class="fas fa-user-plus" style="color:#6366f1; margin-right: 8px;"></i> Tambah Akun Baru
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="padding: 1.5rem;">
                            <div id="createAccountAlert" style="display:none; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight: 500;"></div>
                            <div class="mb-3">
                                <label for="caFullName" class="form-label" style="font-weight:600; font-size:13px; color:#334155;">
                                    Nama Lengkap <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="text" class="form-control" id="caFullName" name="full_name" required placeholder="Contoh: Budi Setia Pribadi" style="border-radius: 8px;">
                            </div>
                            <div class="mb-3">
                                <label for="caRole" class="form-label" style="font-weight:600; font-size:13px; color:#334155;">
                                    Role <span style="color:#ef4444;">*</span>
                                </label>
                                <select class="form-select" id="caRole" name="role" required style="border-radius: 8px;">
                                    <option value="">-- Pilih Role --</option>
                                    <option value="admin">Admin</option>
                                    <option value="guru">Guru</option>
                                    <option value="siswa">Siswa</option>
                                    <option value="yayasan">Yayasan</option>
                                    <option value="staff">Staff</option>
                                    <option value="calon-siswa">Calon Siswa</option>
                                </select>
                            </div>
                            <div class="mb-3" id="caKelasGroup" style="display:none;">
                                <label for="caKelas" class="form-label" style="font-weight:600; font-size:13px; color:#334155;">
                                    Kelas <span style="color:#ef4444;">*</span>
                                </label>
                                <select class="form-select" id="caKelas" name="kelas" style="border-radius: 8px;">
                                    <option value="">-- Pilih Kelas --</option>
                                    <option value="XII-SOEKARNO">XII-SOEKARNO</option>
                                    <option value="XII-LINCOLN">XII-LINCOLN</option>
                                    <option value="XII-MANDELA">XII-MANDELA</option>
                                    <option value="XII-GANDHI">XII-GANDHI</option>
                                    <option value="XI-ADISOETJIPTO">XI-ADISOETJIPTO</option>
                                    <option value="XI-SOEDIRMAN">XI-SOEDIRMAN</option>
                                    <option value="XI-HATTA">XI-HATTA</option>
                                    <option value="XI-YOS-SUDARSO">XI-YOS-SUDARSO</option>
                                    <option value="XI-HOEGENG">XI-HOEGENG</option>
                                    <option value="X-ARCHIMEDES">X-ARCHIMEDES</option>
                                    <option value="X-VOLTA">X-VOLTA</option>
                                    <option value="X-NEWTON">X-NEWTON</option>
                                    <option value="X-PASCAL">X-PASCAL</option>
                                    <option value="X-EINSTEIN">X-EINSTEIN</option>
                                    <option value="X-TESLA">X-TESLA</option>
                                    <option value="PPDB-REGULER">PPDB-REGULER</option>
                                    <option value="CSR">CSR</option>
                                    <option value="CALON-GURU">CALON-GURU</option>
                                    <option value="XII-UNIVERSITAS">XII-UNIVERSITAS</option>
                                    <option value="XII-KEDINASAN">XII-KEDINASAN</option>
                                    <option value="XII-AKPOL">XII-AKPOL</option>
                                    <option value="XII-AKMIL">XII-AKMIL</option>
                                    <option value="XI-UNIVERSITAS-1">XI-UNIVERSITAS-1</option>
                                    <option value="XI-UNIVERSITAS-2">XI-UNIVERSITAS-2</option>
                                    <option value="XI-KEDINASAN-1">XI-KEDINASAN-1</option>
                                    <option value="XI-KEDINASAN-2">XI-KEDINASAN-2</option>
                                    <option value="XI-AKPOL">XI-AKPOL</option>
                                    <option value="XI-AKMIL">XI-AKMIL</option>
                                </select>
                            </div>
                            <div class="mb-3" id="caSubjectGroup" style="display:none;">
                                <label for="caSubjectId" class="form-label" style="font-weight:600; font-size:13px; color:#334155;">
                                    Mata Pelajaran <span style="color:#ef4444;">*</span>
                                </label>
                                <select class="form-select" id="caSubjectId" name="subject_id" style="border-radius: 8px;">
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                </select>
                            </div>
                            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 14px; font-size:13px; color:#1e40af; display: flex; align-items: start; gap: 10px;">
                                <i class="fas fa-info-circle" style="margin-top: 2px;"></i>
                                <span>Username &amp; password akan dibuat otomatis berdasarkan nama dan role setelah disimpan.</span>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid #e2e8f0; padding: 1rem 1.5rem;">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 600; color: #64748b;">Batal</button>
                            <button type="submit" class="btn btn-primary" id="caSubmitBtn" style="background:#6366f1; border:none; border-radius: 8px; font-weight: 600; padding: 0.5rem 1.5rem;">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- 2. MODAL PREVIEW AKUN (Muncul setelah berhasil simpan) -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <div class="modal fade" id="accountPreviewModal" tabindex="-1" aria-labelledby="accountPreviewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px; border:none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                    <div class="modal-header" style="background:#f0fdf4; border-bottom:1px solid #bbf7d0; border-radius:16px 16px 0 0;">
                        <h5 class="modal-title" id="accountPreviewModalLabel" style="font-weight:800; color:#166534; font-size: 1.1rem;">
                            <i class="fas fa-check-circle" style="color:#22c55e; margin-right: 8px;"></i> Akun Berhasil Dibuat!
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="padding: 1.5rem;">
                        <p class="text-muted" style="font-size: 14px; margin-bottom: 1rem;">Silakan salin dan simpan kredensial berikut untuk diberikan kepada pengguna:</p>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600; font-size:12px; color:#64748b; text-transform: uppercase; letter-spacing: 0.5px;">Nama Lengkap</label>
                            <div class="p-2 bg-light rounded" style="font-size: 15px; font-weight: 600; color: #1e293b;" id="pvFullName">-</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600; font-size:12px; color:#64748b; text-transform: uppercase; letter-spacing: 0.5px;">Role</label>
                            <div class="p-2 bg-light rounded" style="font-size: 15px; font-weight: 600; color: #1e293b;" id="pvRole">-</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600; font-size:12px; color:#64748b; text-transform: uppercase; letter-spacing: 0.5px;">Username</label>
                            <div class="d-flex align-items-center p-2 bg-light rounded" style="font-size: 15px; font-weight: 600; color: #1e293b; font-family: monospace;">
                                <span id="pvUsername" class="flex-grow-1">-</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600; font-size:12px; color:#64748b; text-transform: uppercase; letter-spacing: 0.5px;">Password Default</label>
                            <div class="d-flex align-items-center p-2 bg-light rounded" style="font-size: 15px; font-weight: 600; color: #dc2626; font-family: monospace;">
                                <span id="pvPassword" class="flex-grow-1">-</span>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center" style="font-size: 13px; border-radius: 8px; margin-bottom: 0; border-color: #fde68a; background-color: #fefce8; color: #854d0e;">
                            <i class="fas fa-exclamation-triangle me-2" style="margin-top: 2px;"></i>
                            <div>Pengguna <strong>wajib</strong> mengganti password ini saat pertama kali login.</div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #e2e8f0; padding: 1rem 1.5rem;">
                        <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" style="background:#6366f1; border:none; border-radius: 8px; font-weight: 600; padding: 0.6rem;">
                            <i class="fas fa-check"></i> Selesai
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Exams Section -->
        <?php if (!empty($exams)): ?>
            <div class="activity-card" style="margin-top: 2rem;">
                <div class="activity-header">
                    <i class="fas fa-clipboard-list" style="color: var(--primary);"></i>
                    <h2 class="activity-title">
                        <?= $user['role'] === 'siswa' ? 'Ujian Tersedia' : 'Ujian Saya' ?>
                    </h2>
                </div>

                <div class="exams-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                    <?php foreach ($exams as $exam): ?>
                        <?php
                        $kelasUpper  = strtoupper($user['kelas'] ?? '');
                        $isCsr       = $user['role'] === 'siswa' && $kelasUpper === 'CSR';
                        $isPpdbKelas = $user['role'] === 'siswa' && str_contains($kelasUpper, 'PPDB');
                        $forceTabPpdb = $isCsr || $isPpdbKelas;

                        $examCategory = $exam['category'] ?? '';

                        // Jika kelas CSR atau PPDB, hanya tampilkan ujian kategori ppdb
                        if ($forceTabPpdb && $examCategory !== 'ppdb') continue;
                        ?>
                        <div class="exam-card" style="background: white; border-radius: 12px; padding: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                                <h3 style="margin: 0; color: var(--dark); font-size: 1.1rem; font-weight: 600;">
                                    <?= htmlspecialchars($exam['title']) ?>
                                </h3>
                                <span class="badge <?= $exam['status'] === 'published' ? 'success' : 'warning' ?>" style="margin-left: auto;">
                                    <?= ucfirst($exam['status']) ?>
                                </span>
                            </div>

                            <div style="margin-bottom: 1rem; color: #64748b; font-size: 0.9rem;">
                                <?php if ($exam['subject_name']): ?>
                                    <div style="margin-bottom: 0.5rem;">
                                        <i class="fas fa-book"></i> <?= htmlspecialchars($exam['subject_name']) ?>
                                    </div>
                                <?php endif; ?>

                                <div style="margin-bottom: 0.5rem;">
                                    <i class="fas fa-clock"></i> <?= $exam['duration'] ?> menit
                                </div>

                                <?php if ($exam['start_time']): ?>
                                    <div style="margin-bottom: 0.5rem;">
                                        <i class="fas fa-calendar"></i> <?= formatWaktuIndonesia($exam['start_time'], 'd M Y, H:i') ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($user['role'] !== 'siswa'): ?>
                                    <div style="display: flex; gap: 1rem; font-size: 0.8rem; margin-top: 0.5rem;">
                                        <span><i class="fas fa-question"></i> <?= $exam['question_count'] ?? 0 ?> soal</span>
                                        <span><i class="fas fa-users"></i> <?= $exam['session_count'] ?? 0 ?> peserta</span>
                                        <?php if (($exam['active_sessions'] ?? 0) > 0): ?>
                                            <span style="color: var(--success);"><i class="fas fa-circle"></i> <?= $exam['active_sessions'] ?> aktif</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="display: flex; gap: 0.5rem;">
                                <?php if ($user['role'] === 'siswa'): ?>
                                    <?php if (isset($exam['can_access']) && $exam['can_access']): ?>
                                        <?php if (isset($exam['has_taken']) && $exam['has_taken']): ?>
                                            <a href="<?= config('app_url') ?>/exam/result/<?= $exam['session_id'] ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-chart-bar"></i> Lihat Hasil
                                            </a>
                                        <?php elseif (isset($exam['exam_status']) && $exam['exam_status'] === 'upcoming'): ?>
                                            <span class="btn btn-sm" style="background:#fef3c7;color:#92400e;border:none;cursor:not-allowed;opacity:0.9;">
                                                <i class="fas fa-clock"></i> Belum Dimulai
                                            </span>
                                        <?php elseif (isset($exam['exam_status']) && $exam['exam_status'] === 'active'): ?>
                                            <a href="<?= config('app_url') ?>/exam/start/<?= $exam['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-play"></i> Mulai Ujian
                                            </a>
                                        <?php else: ?>
                                            <span class="btn btn-sm" style="background:#f1f5f9;color:#475569;border:none;cursor:not-allowed;opacity:0.9;">
                                                <i class="fas fa-times-circle"></i> Berakhir
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="btn btn-secondary btn-sm" style="opacity: 0.6; cursor: not-allowed;">
                                            <i class="fas fa-lock"></i> Tidak Tersedia
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?= config('app_url') ?>/exam/edit/<?= $exam['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="<?= config('app_url') ?>/exam/assignQuestions/<?= $exam['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-cog"></i> Kelola Soal
                                    </a>
                                    <?php if (($exam['active_sessions'] ?? 0) > 0): ?>
                                        <a href="<?= config('app_url') ?>/exam/monitor/<?= $exam['id'] ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-desktop"></i> Monitor
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Activity Card -->
        <div class="activity-card">
            <div class="activity-header">
                <i class="fas fa-history" style="color: var(--primary);"></i>
                <h2 class="activity-title">Aktivitas Terbaru</h2>
            </div>

            <?php if ($user['role'] === 'siswa'): ?>
                <?php
                $stmt = db()->prepare("
                        SELECT es.*, e.title, e.duration
                        FROM exam_sessions es
                        JOIN exams e ON es.exam_id = e.id
                        WHERE es.user_id = ?
                        ORDER BY es.started_at DESC
                        LIMIT 5
                    ");
                $stmt->execute([$user['id']]);
                $sessions = $stmt->fetchAll();
                ?>
                <?php if (count($sessions) > 0): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ujian</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $s): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($s['title']) ?></strong></td>
                                        <td><?= formatWaktuIndonesia($s['started_at'], 'd M Y, H:i') ?></td>
                                        <td>
                                            <span class="badge <?= $s['status'] === 'graded' ? 'success' : ($s['status'] === 'submitted' ? 'warning' : 'primary') ?>">
                                                <?= ucfirst($s['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($s['status'] === 'graded'): ?>
                                                <strong style="color: var(--primary);"><?= number_format($s['score'], 0) ?></strong>
                                            <?php else: ?>
                                                <span style="color: #94a3b8;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <p class="empty-text">Belum ada aktivitas ujian</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: #64748b;">Gunakan menu navigasi di atas untuk mengelola ujian dan bank soal.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-alt"></i> Export / Laporan Nilai Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-book"></i> Pilih Ujian</label>
                        <div style="position: relative;">
                            <input
                                type="text"
                                id="examSearch"
                                class="form-control"
                                placeholder="🔍 Ketik judul ujian..."
                                autocomplete="off">
                            <input type="hidden" id="modalExamId">
                            <ul id="examSuggestions" style="
                                display: none;
                                position: absolute;
                                top: 100%; left: 0; right: 0;
                                background: #fff;
                                border: 1px solid #dee2e6;
                                border-radius: 0.375rem;
                                max-height: 220px;
                                overflow-y: auto;
                                z-index: 9999;
                                list-style: none;
                                margin: 2px 0 0;
                                padding: 0;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                            "></ul>
                        </div>
                        <small id="selectedExamLabel" class="text-success mt-1 d-block"></small>
                    </div>

                    <!-- Pilih Kelas: combobox, populate dari ujian yang dipilih -->
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-users"></i> Pilih Kelas (Opsional)</label>
                        <select id="modalKelas" class="form-select">
                            <option value="">Semua Kelas</option>
                        </select>
                    </div>

                    <!-- Pilih Mata Pelajaran: hanya menampilkan mapel yang benar-benar dimasukkan ke ujian yang dipilih -->
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-book"></i> Pilih Mata Pelajaran (Opsional)</label>
                        <select id="modalSubject" class="form-select" disabled>
                            <option value="">Pilih ujian dahulu</option>
                        </select>
                        <small class="text-muted">Daftar mapel di bawah ini otomatis menyesuaikan mapel yang ada di ujian yang dipilih.</small>
                    </div>

                    <div class="alert alert-info" style="font-size:0.875rem;">
                        <i class="fas fa-info-circle"></i>
                        Pilih format export yang diinginkan:
                        <ul class="mb-0 mt-1">
                            <li><strong>PDF</strong> — buka laporan di tab baru, lalu Ctrl+P → Save as PDF</li>
                            <li><strong>Excel (.xls)</strong> — download langsung, buka dengan Microsoft Excel</li>
                            <li><strong>CSV</strong> — format teks, kompatibel semua aplikasi spreadsheet</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer d-flex gap-2 flex-wrap justify-content-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" onclick="doExport('pdf')">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button type="button" class="btn btn-success" onclick="doExport('excel')">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="doExport('csv')">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        const APP_URL = '<?= config('app_url') ?>';
        // ── User dropdown ────────────────────────────────────────────
        function toggleUserMenu() {
            const menu = document.getElementById('userDropdownMenu');
            const chevron = document.getElementById('userChevron');
            const isOpen = menu.classList.contains('open');
            menu.classList.toggle('open', !isOpen);
            chevron.classList.toggle('open', !isOpen);
        }
        document.addEventListener('click', function(e) {
            const wrap = document.querySelector('.user-dropdown-wrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('userDropdownMenu')?.classList.remove('open');
                document.getElementById('userChevron')?.classList.remove('open');
            }
        });

        // ── Waktu real-time ──────────────────────────────────────────
        function updateCurrentTime() {
            const timeString = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }).format(new Date()) + ' WIB';

            const el = document.getElementById('current-time');
            if (el) el.textContent = timeString;
        }
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime();

        // ── Autocomplete ujian ───────────────────────────────────────
        const searchInput = document.getElementById('examSearch');
        const hiddenId = document.getElementById('modalExamId');
        const suggestions = document.getElementById('examSuggestions');
        const selectedLabel = document.getElementById('selectedExamLabel');
        const kelasSelect = document.getElementById('modalKelas');
        const subjectSelect = document.getElementById('modalSubject');

        // Catatan: daftar mapel TIDAK di-load sekaligus di sini lagi.
        // Mapel hanya dimuat setelah user memilih ujian (lihat loadSubjects() di bawah),
        // supaya hanya menampilkan mapel yang benar-benar dimasukkan ke ujian tersebut.

        function renderSuggestions(filtered) {
            suggestions.innerHTML = '';
            if (filtered.length === 0) {
                suggestions.innerHTML = '<li style="padding:10px 14px;color:#999;">Ujian tidak ditemukan</li>';
            } else {
                filtered.forEach(exam => {
                    const li = document.createElement('li');
                    li.textContent = exam.title;
                    li.style.cssText = 'padding:10px 14px;cursor:pointer;border-bottom:1px solid #f0f0f0;';
                    li.addEventListener('mouseenter', () => li.style.background = '#f5f5f5');
                    li.addEventListener('mouseleave', () => li.style.background = '');
                    li.addEventListener('mousedown', () => {
                        searchInput.value = exam.title;
                        hiddenId.value = exam.id;
                        selectedLabel.textContent = '✅ Ujian dipilih: ' + exam.title;
                        suggestions.style.display = 'none';
                        loadKelas(exam.id);
                        loadSubjects(exam.id);
                    });
                    suggestions.appendChild(li);
                });
            }
            suggestions.style.display = 'block';
        }

        let examSearchTimeout;
        let examSearchRequest = 0;
        searchInput?.addEventListener('input', function() {
            const keyword = this.value.trim().toLowerCase();
            hiddenId.value = '';
            selectedLabel.textContent = '';
            resetKelas();
            resetSubject();
            window.clearTimeout(examSearchTimeout);
            const requestId = ++examSearchRequest;
            if (!keyword) {
                suggestions.style.display = 'none';
                return;
            }

            examSearchTimeout = window.setTimeout(() => {
                fetch(`${APP_URL}/dashboard/searchExams?q=${encodeURIComponent(keyword)}`)
                    .then(response => {
                        if (!response.ok) throw new Error(`Exam search failed: ${response.status}`);
                        return response.json();
                    })
                    .then(exams => {
                        if (requestId === examSearchRequest) renderSuggestions(exams);
                    })
                    .catch(error => {
                        if (requestId === examSearchRequest) {
                            console.error('Error searching exams:', error);
                            suggestions.style.display = 'none';
                        }
                    });
            }, 250);
        });

        searchInput?.addEventListener('focus', function() {
            if (this.value.trim()) searchInput.dispatchEvent(new Event('input'));
        });

        searchInput?.addEventListener('blur', () => {
            setTimeout(() => suggestions.style.display = 'none', 150);
        });

        // ── Kelas combobox ───────────────────────────────────────────
        function resetKelas() {
            kelasSelect.innerHTML = '<option value="">Semua Kelas</option>';
        }

        function resetSubject() {
            subjectSelect.innerHTML = '<option value="">Semua Mata Pelajaran</option>';
            subjectSelect.disabled = true;
        }

        function loadKelas(examId) {
            resetKelas();
            if (!examId) return;
            fetch(`${APP_URL}/dashboard/getKelas?exam_id=${examId}`)
                .then(r => r.json())
                .then(data => {
                    data.forEach(kelas => {
                        const opt = document.createElement('option');
                        opt.value = opt.textContent = kelas;
                        kelasSelect.appendChild(opt);
                    });
                })
                .catch(err => console.error('Error loading kelas:', err));
        }

        // ── Mapel combobox (hanya mapel yang dimasukkan ke ujian terpilih) ──
        function loadSubjects(examId) {
            resetSubject();
            if (!examId) return;
            fetch(`${APP_URL}/dashboard/getSubjects?exam_id=${examId}`)
                .then(r => r.json())
                .then(data => {
                    subjectSelect.disabled = false;
                    if (!Array.isArray(data) || data.length === 0) {
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = 'Ujian ini belum punya mapel';
                        subjectSelect.appendChild(opt);
                        return;
                    }
                    data.forEach(subj => {
                        const opt = document.createElement('option');
                        opt.value = subj.id;
                        opt.textContent = subj.name;
                        subjectSelect.appendChild(opt);
                    });
                })
                .catch(err => console.error('Error loading subjects:', err));
        }

        let exportModal;

        function showExportModal() {
            // Reset state tiap buka modal
            if (searchInput) searchInput.value = '';
            if (hiddenId) hiddenId.value = '';
            if (selectedLabel) selectedLabel.textContent = '';
            if (suggestions) suggestions.style.display = 'none';
            resetKelas();
            resetSubject();

            exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
            exportModal.show();
        }

        function doExport(format) {
            const examId = document.getElementById('modalExamId').value;
            const kelas = document.getElementById('modalKelas').value;
            const subjectId = document.getElementById('modalSubject').value;

            if (!examId) {
                alert('Pilih ujian terlebih dahulu');
                return;
            }

            if (format === 'pdf') {
                // PERBAIKAN: Gunakan '&kelas=' agar sesuai dengan $_GET['kelas'] di ReportController.php
                let url = APP_URL + '/report/laporanNilai?exam_id=' + examId;
                if (kelas) url += '&kelas=' + encodeURIComponent(kelas);
                if (subjectId) url += '&subject_id=' + encodeURIComponent(subjectId);
                window.open(url, '_blank');
            } else {
                // Untuk export Excel/CSV, tetap gunakan '&namaKelas=' sesuai DashboardController::exportNilai
                let url = APP_URL + '/dashboard/exportNilai?exam_id=' + examId + '&format=' + format;
                if (kelas) url += '&namaKelas=' + encodeURIComponent(kelas);
                if (subjectId) url += '&subject_id=' + encodeURIComponent(subjectId);

                const a = document.createElement('a');
                a.href = url;
                a.download = '';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }

            exportModal.hide();
        }

        // ==============SCRIPT BUAT AKUN BARU=====================
        document.addEventListener('DOMContentLoaded', function() {
            const createAccountModalEl = document.getElementById('createAccountModal');
            const createAccountForm = document.getElementById('createAccountForm');
            const caRole = document.getElementById('caRole');
            const caFullName = document.getElementById('caFullName');
            const caKelasGroup = document.getElementById('caKelasGroup');
            const caKelas = document.getElementById('caKelas');
            const caSubjectGroup = document.getElementById('caSubjectGroup');
            const caSubjectId = document.getElementById('caSubjectId');
            const caSubmitBtn = document.getElementById('caSubmitBtn');
            const caAlert = document.getElementById('createAccountAlert');
            const createAccountModal = createAccountModalEl ? new bootstrap.Modal(createAccountModalEl) : null;
            const accountPreviewModalEl = document.getElementById('accountPreviewModal');
            const accountPreviewModal = accountPreviewModalEl ? new bootstrap.Modal(accountPreviewModalEl) : null;

            window.openCreateAccountModal = function() {
                if (createAccountModal) {
                    createAccountForm.reset();
                    caKelasGroup.style.display = 'none';
                    caSubjectGroup.style.display = 'none';
                    caKelas.removeAttribute('required');
                    caSubjectId.removeAttribute('required');
                    caAlert.style.display = 'none';
                    createAccountModal.show();
                }
            };

            if (caRole) {
                caRole.addEventListener('change', function() {
                    const role = this.value;
                    caKelasGroup.style.display = 'none';
                    caSubjectGroup.style.display = 'none';
                    caKelas.removeAttribute('required');
                    caSubjectId.removeAttribute('required');
                    if (role === 'siswa' || role === 'calon-siswa') {
                        caKelasGroup.style.display = 'block';
                        caKelas.setAttribute('required', 'required');
                    } else if (role === 'guru') {
                        caSubjectGroup.style.display = 'block';
                        caSubjectId.setAttribute('required', 'required');
                    }
                });
            }

            function loadSubjects() {
                if (!caSubjectId) return;

                caSubjectId.innerHTML = '<option value="">Sedang memuat data...</option>';
                caSubjectId.disabled = true;

                const fetchUrl = '<?= config("app_url") ?>/dashboard/getSubjects';

                fetch(fetchUrl)
                    .then(res => res.json())
                    .then(data => {
                        caSubjectId.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';

                        if (data.error) {
                            caSubjectId.innerHTML += `<option value="" disabled>${data.error}</option>`;
                        } else if (data && data.length > 0) {
                            data.forEach(subj => {
                                const option = document.createElement('option');
                                option.value = subj.id;
                                option.textContent = subj.name;
                                caSubjectId.appendChild(option);
                            });
                        } else {
                            caSubjectId.innerHTML += '<option value="" disabled>Tidak ada data mata pelajaran</option>';
                        }
                        caSubjectId.disabled = false;
                    })
                    .catch(err => {
                        console.error("Gagal memuat mata pelajaran:", err);
                        caSubjectId.innerHTML = '<option value="">Gagal memuat data</option>';
                        caSubjectId.disabled = false;
                    });
            }

            if (createAccountModalEl) {
                createAccountModalEl.addEventListener('shown.bs.modal', function() {
                    loadSubjects();
                });

                createAccountModalEl.addEventListener('hidden.bs.modal', function() {
                    createAccountForm.reset();
                    caKelasGroup.style.display = 'none';
                    caSubjectGroup.style.display = 'none';
                    caKelas.removeAttribute('required');
                    caSubjectId.removeAttribute('required');
                    caAlert.style.display = 'none';
                });
            }

            if (createAccountForm) {
                createAccountForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    caAlert.style.display = 'none';

                    const role = caRole.value;
                    const fullName = caFullName.value.trim();

                    const fd = new FormData();
                    fd.append('full_name', fullName);
                    fd.append('role', role);
                    fd.append('kelas', (role === 'siswa' || role === 'calon-siswa') ? caKelas.value.trim() : '');
                    fd.append('subject_id', (role === 'guru') ? caSubjectId.value : '');

                    const originalBtnText = caSubmitBtn.innerHTML;
                    caSubmitBtn.disabled = true;
                    caSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

                    const submitUrl = '<?= config("app_url") ?>/dashboard/createAccount';

                    fetch(submitUrl, {
                            method: 'POST',
                            body: fd
                        })
                        .then(res => res.json())
                        .then(json => {
                            if (json.success) {
                                createAccountModal.hide();

                                const roleLabels = {
                                    admin: 'Admin',
                                    guru: 'Guru',
                                    siswa: 'Siswa',
                                    yayasan: 'Yayasan',
                                    staff: 'Staff',
                                    'calon-siswa': 'Calon Siswa'
                                };

                                const pvFullName = document.getElementById('pvFullName');
                                if (pvFullName) {
                                    pvFullName.textContent = fullName;
                                    document.getElementById('pvRole').textContent = roleLabels[role] || role;
                                    document.getElementById('pvUsername').textContent = json.username;
                                    document.getElementById('pvPassword').textContent = json.password;
                                }

                                if (accountPreviewModal) {
                                    accountPreviewModal.show();
                                } else {
                                    alert(`Akun berhasil dibuat!\nUsername: ${json.username}\nPassword: ${json.password}`);
                                }
                            } else {
                                caAlert.style.display = 'block';
                                caAlert.style.background = '#fee2e2';
                                caAlert.style.color = '#991b1b';
                                caAlert.textContent = json.message || 'Gagal membuat akun.';
                            }
                        })
                        .catch(() => {
                            caAlert.style.display = 'block';
                            caAlert.style.background = '#fee2e2';
                            caAlert.style.color = '#991b1b';
                            caAlert.textContent = 'Terjadi kesalahan koneksi. Periksa console untuk detail.';
                        })
                        .finally(() => {
                            caSubmitBtn.disabled = false;
                            caSubmitBtn.innerHTML = originalBtnText;
                        });
                });
            }
        });
    </script>
</body>

</html>