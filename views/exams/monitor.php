<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= asset_url('images/cat.png') ?>" type="image/x-icon">
    <title>Monitor: <?= htmlspecialchars($exam['title']) ?> - CAT Enterprise</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/fontawesome/6.0.0/css/all.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 800;
            color: #6366f1 !important;
            font-size: 1.4rem;
        }

        .main-content {
            padding: 1.5rem 0;
        }

        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .stat-card {
            text-align: center;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            display: block;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table th {
            background: #f8fafc;
            font-weight: 700;
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 0.75rem 1rem;
        }

        .table td {
            padding: 0.875rem 1rem;
            vertical-align: middle;
            border-color: #f1f5f9;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-submitted {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-suspended {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending {
            background: #f1f5f9;
            color: #64748b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .progress-wrap {
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            width: 100px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            transition: width 0.5s;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            margin-right: 10px;
        }

        .refresh-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #64748b;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0.5
            }
        }

        .exam-status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-active-exam {
            background: #d1fae5;
            color: #065f46;
        }

        .status-draft-exam {
            background: #f1f5f9;
            color: #475569;
        }

        .status-ended-exam {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>
    <?php include VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container main-content">
        <!-- Header -->
        <div class="card-glass">
            <div style=" display:flex; justify-content:space-between; align-items:flex-start; gap:1.5rem; flex-wrap:nowrap; margin-bottom:1.5rem; padding-bottom:1.25rem; border-bottom:1px solid #e2e8f0; ">
                <div style="min-width:280px;">
                    <a href="<?= config('app_url') ?>/exam/monitor" style=" display:inline-flex; align-items:center; gap:0.4rem; color:#6366f1; text-decoration:none; font-size:13px; font-weight:600; margin-bottom:0.6rem; ">
                        <i class="fas fa-arrow-left"></i> <span>Kembali ke Monitor List</span> </a> <!-- Judul -->
                    <h2 style=" display:flex; align-items:center; gap:0.6rem; font-size:1.45rem; font-weight:800; color:#1e293b; margin:0 0 0.4rem; line-height:1.3; "> <i class="fas fa-desktop" style="color:#6366f1;font-size:1.25rem;"></i> <?= htmlspecialchars($exam['title']) ?> </h2>
                    <div style=" display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem 0.9rem; color:#64748b; font-size:13px; "> <span> <i class="fas fa-calendar" style="margin-right:0.3rem;"></i> <?= formatWaktuIndonesia($exam['start_time'], 'l, d F Y H:i') ?> WIB </span> <span style="color:#cbd5e1;">|</span> <span> <i class="fas fa-clock" style="margin-right:0.3rem;"></i> Durasi <?= $exam['duration'] ?> menit </span> </div>
                </div>
                <div style=" display:flex; align-items:center; justify-content:flex-end; gap:1rem; flex-wrap:nowrap; ">
                    <span id="exam-status-badge" class="exam-status-badge <?= htmlspecialchars($examStatusClass, ENT_QUOTES, 'UTF-8') ?>" style="white-space:nowrap;flex-shrink:0;"><?= htmlspecialchars($examStatusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="refresh-indicator" style=" display:flex; align-items:center; gap:0.45rem; color:#64748b; font-size:12px; white-space:nowrap; "> <i class="fas fa-sync-alt" id="refresh-icon"></i> <span>Update otomatis setiap 15 detik</span> </div>
                </div>
            </div>
            <!-- Statistik Peserta -->
            <div id="stats-grid" class="row g-3">
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#1e293b;"> - </span>
                        <div class="stat-label"> Total Peserta </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#10b981;"> - </span>
                        <div class="stat-label"> Sedang Ujian </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#f59e0b;"> - </span>
                        <div class="stat-label"> Belum Ujian </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#6366f1;"> - </span>
                        <div class="stat-label"> Sudah Submit </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#ef4444;"> - </span>
                        <div class="stat-label"> Ter-suspend </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Peserta -->
        <div class="card-glass">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;gap:1rem;flex-wrap:wrap;">
                <div style="width:100%; display:flex; justify-content:space-between; align-items:flex-start;gap:0.5rem;flex-wrap:wrap;">
                    <div id="statusTabs" style="display:flex;gap:0;overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;">
                        <button type="button" class="status-tab active" data-filter="all" style="flex:1;min-width:150px;padding:10px 16px;border:1px solid #e2e8f0;border-top-left-radius:12px;border-top-right-radius:12px;background:#fff;color:#6366f1;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;text-align:center;position:relative;">All <span class="tab-badge" style="position:absolute;top:6px;right:8px;background:#6366f1;color:#fff;width:20px;height:20px;line-height:20px;border-radius:10px;text-align:center;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;">-</span></button>
                        <button type="button" class="status-tab" data-filter="active" style="flex:1;min-width:150px;padding:10px 16px;border:1px solid #e2e8f0;border-top-left-radius:12px;border-top-right-radius:12px;background:#f8fafc;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;text-align:center;position:relative;">Sedang Ujian <span class="tab-badge" style="position:absolute;top:6px;right:8px;background:#6366f1;color:#fff;width:20px;height:20px;line-height:20px;border-radius:10px;text-align:center;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;">-</span></button>
                        <button type="button" class="status-tab" data-filter="pending" style="flex:1;min-width:150px;padding:10px 16px;border:1px solid #e2e8f0;border-top-left-radius:12px;border-top-right-radius:12px;background:#f8fafc;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;text-align:center;position:relative;">Belum Ujian <span class="tab-badge" style="position:absolute;top:6px;right:8px;background:#6366f1;color:#fff;width:20px;height:20px;line-height:20px;border-radius:10px;text-align:center;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;">-</span></button>
                        <button type="button" class="status-tab" data-filter="submitted" style="flex:1;min-width:150px;padding:10px 16px;border:1px solid #e2e8f0;border-top-left-radius:12px;border-top-right-radius:12px;background:#f8fafc;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;text-align:center;position:relative;">Selesai <span class="tab-badge" style="position:absolute;top:6px;right:8px;background:#6366f1;color:#fff;width:20px;height:20px;line-height:20px;border-radius:10px;text-align:center;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;">-</span></button>
                        <button type="button" class="status-tab" data-filter="suspended" style="flex:1;min-width:150px;padding:10px 16px;border:1px solid #e2e8f0;border-top-left-radius:12px;border-top-right-radius:12px;background:#f8fafc;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;text-align:center;position:relative;">Ter-suspend <span class="tab-badge" style="position:absolute;top:6px;right:8px;background:#6366f1;color:#fff;width:20px;height:20px;line-height:20px;border-radius:10px;text-align:center;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;">-</span></button>
                    </div>
                    <input type="text" id="nameSearch" placeholder="Cari nama, email, atau kelas..." style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:13px;color:#374151;width:220px;min-width:150px;">
                </div>
            </div>
            <div style="border-top:1px solid #e2e8f0;margin-top:-1px;"></div>
            <div id="students-container">
                <div style="text-align:center;padding:3rem;color:#64748b">
                    <i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:1rem"></i>
                    <p>Memuat data peserta...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const examId = <?= (int)$exam['id'] ?>;
        const baseUrl = '<?= config('app_url') ?>';
        let lastSubmittedCount = 0;
        let lastActiveCount = 0;
        let monitorSessions = new Map();
        let currentStatusFilter = 'all';
        let currentSearch = '';
        let searchDebounceTimer = null;
        let currentSummary = null;
        let currentPage = 1;

        function normalizeStatus(s) {
            if (s.status === 'active' || s.status === 'in_progress') return 'active';
            if (s.status === 'submitted' || s.status === 'completed' || s.status === 'graded') return 'submitted';
            if (s.status === 'suspended') return 'suspended';
            return 'pending';
        }

        function actionButtonsHtml(s, mobile) {
            const isRunning = s.status === 'active' || s.status === 'in_progress';
            const isSuspended = s.status === 'suspended';

            if (!isRunning && !isSuspended) {
                return mobile ? '' : '<span style="color:#94a3b8;font-size:11px">-</span>';
            }

            const size = mobile ? 'padding:8px 16px;font-size:12px;border-radius:6px;width:100px;text-align:center' :
                'padding:4px 8px;font-size:11px;border-radius:4px;width:80px;text-align:center';
            const base = `border:0;font-weight:600;${size}`;

            const stateBtn = isSuspended ?
                `<button type="button" class="resume-btn" data-session-id="${Number(s.id)}" style="${base};background:#10b981;color:white;cursor:pointer"><i class="fas fa-play"></i> Lanjutkan</button>` :
                `<button type="button" disabled style="${base};background:#e2e8f0;color:#64748b;cursor:not-allowed">Active</button>`;

            const addBtn = `<button type="button" onclick="promptAddDuration(${Number(s.id)})" style="${base};background:#6366f1;color:white;cursor:pointer">+ Waktu</button>`;

            const wrapStyle = mobile ?
                'display:flex;gap:6px;flex-wrap:nowrap;justify-content:center;margin-top:8px' :
                'display:flex;gap:6px;flex-wrap:nowrap';

            return `<div style="${wrapStyle}">${stateBtn}${addBtn}</div>`;
        }

        async function resumeSession(sessionId, btn) {
            openResumeModal(sessionId, btn);
        }

        function formatTime(seconds) {
            if (!seconds || seconds < 0) return '00:00';
            // Cap at 99:59
            seconds = Math.min(seconds, 5999);
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        function getInitials(name) {
            if (!name) return '?';
            return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
        }

        function updateStats(summary) {
            currentSummary = summary;
            const total = summary.total_participants ?? 0;
            const active = summary.active_count ?? 0;
            const notStarted = summary.not_started_count ?? 0;
            const submitted = summary.submitted_count ?? 0;
            const suspended = summary.suspended_count ?? 0;

            if (lastSubmittedCount > 0 && submitted > lastSubmittedCount) {
                showToast((submitted - lastSubmittedCount) + ' siswa baru mengumpulkan ujian!', 'success');
            }
            lastSubmittedCount = submitted;
            lastActiveCount = active;

            document.getElementById('stats-grid').innerHTML = `
                <div class="col-6 col-md"><div class="stat-card"><span class="stat-number" style="color:#1e293b">${total}</span><div class="stat-label">Total Peserta</div></div></div>
                <div class="col-6 col-md"><div class="stat-card"><span class="stat-number" style="color:#10b981">${active}</span><div class="stat-label">Sedang Ujian</div></div></div>
                <div class="col-6 col-md"><div class="stat-card"><span class="stat-number" style="color:#f59e0b">${notStarted}</span><div class="stat-label">Belum Ujian</div></div></div>
                <div class="col-6 col-md"><div class="stat-card"><span class="stat-number" style="color:#6366f1">${submitted}</span><div class="stat-label">Sudah Submit</div></div></div>
                <div class="col-6 col-md"><div class="stat-card"><span class="stat-number" style="color:#ef4444">${suspended}</span><div class="stat-label">Ter-suspend</div></div></div>
            `;

            updateTabBadges();
        }

        function updateTabBadges() {
            if (!currentSummary) return;
            const counts = {
                all: currentSummary.total_participants ?? 0,
                active: currentSummary.active_count ?? 0,
                pending: currentSummary.not_started_count ?? 0,
                submitted: currentSummary.submitted_count ?? 0,
                suspended: currentSummary.suspended_count ?? 0
            };
            document.querySelectorAll('.status-tab').forEach(btn => {
                const filter = btn.dataset.filter;
                const badge = btn.querySelector('.tab-badge');
                if (badge) badge.textContent = counts[filter] ?? 0;
            });
        }

        function updateStudentsList(sessions, pagination) {
            monitorSessions = new Map(sessions.map(x => [String(x.id), x]));
            if (sessions.length === 0) {
                document.getElementById('students-container').innerHTML = `
                    <div style="text-align:center;padding:3rem;color:#64748b">
                        <i class="fas fa-users" style="font-size:3rem;margin-bottom:1rem;opacity:0.4"></i>
                        <h5>Belum Ada Peserta</h5>
                        <p>Belum ada siswa yang memulai ujian ini.</p>
                    </div>`;
                return;
            }

            // Filter berdasarkan status yang dipilih
            const filtered = currentStatusFilter === 'all' ?
                sessions :
                sessions.filter(s => normalizeStatus(s) === currentStatusFilter);

            // Live search berdasarkan nama, email (username), atau kelas
            const search = currentSearch.toLowerCase();
            const searched = search === '' ?
                filtered :
                filtered.filter(s => {
                    const nama = (s.full_name || '').toLowerCase();
                    const email = (s.username || '').toLowerCase();
                    const kelas = (s.kelas || '').toLowerCase();
                    return nama.includes(search) || email.includes(search) || kelas.includes(search);
                });

            if (searched.length === 0) {
                document.getElementById('students-container').innerHTML = `
                    <div style="text-align:center;padding:3rem;color:#64748b">
                        <i class="fas fa-search" style="font-size:3rem;margin-bottom:1rem;opacity:0.4"></i>
                        <h5>Tidak Ada Peserta</h5>
                        <p>Tidak ada peserta yang cocok dengan pencarian.</p>
                    </div>`;
                return;
            }

            const isMobile = window.innerWidth < 768;

            if (isMobile) {
                // Mobile: card layout
                let cards = '';
                searched.forEach((s, idx) => {
                    const pct = s.total_questions > 0 ? Math.round(s.answered_count / s.total_questions * 100) : 0;
                    let statusColor = s.status === 'active' || s.status === 'in_progress' ? '#10b981' :
                        s.status === 'submitted' || s.status === 'completed' ? '#6366f1' :
                        s.status === 'suspended' ? '#ef4444' : '#64748b';
                    let statusText = s.status === 'active' || s.status === 'in_progress' ? 'Sedang Ujian' :
                        s.status === 'submitted' || s.status === 'completed' ? 'Selesai' :
                        s.status === 'suspended' ? 'Ter-suspend' :
                        s.status === 'pending' ? 'Belum Ujian' : s.status;

                    const reason = s.suspension_reason === 'tab_switch' ? 'Pindah Tab' :
                        s.suspension_reason === 'fullscreen_exit' ? 'Keluar Fullscreen' :
                        s.suspension_reason || '';
                    const violationHtml = s.violation_count ?
                        `<button type="button" class="violation-log-trigger" data-session-id="${Number(s.id)}" style="margin-top:6px;background:#fee2e2;color:#991b1b;padding:4px 10px;border:0;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer">
                            <i class="fas fa-exclamation-triangle"></i> ${s.violation_count}x pelanggaran ${reason ? '· ' + reason : ''}
                        </button>` :
                        `<div style="margin-top:6px;background:#f1f5f9;color:#64748b;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600">
                            <i class="fas fa-exclamation-triangle"></i> 0x pelanggaran
                        </div>`;

                    cards += `<div style="background:white;border-radius:10px;padding:14px;margin-bottom:10px;border:1px solid #e2e8f0;border-left:4px solid ${statusColor}">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="avatar" style="width:32px;height:32px;font-size:12px">${getInitials(s.full_name)}</div>
                                <div>
                                    <div style="font-weight:700;color:#1e293b;font-size:14px">${s.full_name || '-'}</div>
                                    <div style="font-size:11px;color:#64748b">${s.kelas || ''}</div>
                                </div>
                            </div>
                            <span style="background:${statusColor}20;color:${statusColor};padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600">${statusText}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                            <div class="progress-wrap" style="width:100px"><div class="progress-fill" style="width:${pct}%"></div></div>
                            <span style="font-size:12px;font-weight:600;color:#374151;white-space:nowrap">${s.answered_count}/${s.total_questions}</span>
                        </div>
                        <div style="font-size:11px;color:#64748b;display:flex;gap:12px">
                            <span><i class="fas fa-clock"></i> ${(s.status === 'active' || s.status === 'in_progress') ? formatTime(s.remaining_seconds) : '-'}</span>
                            ${s.submitted_at ? `<span><i class="fas fa-check"></i> ${new Date(s.submitted_at).toLocaleTimeString('id-ID')}</span>` : ''}
                        </div>
                        ${violationHtml}
                        ${actionButtonsHtml(s, true)}
                    </div>`;
                });
                document.getElementById('students-container').innerHTML = cards;
            } else {
                // Desktop: div-based layout (teks bisa melebar, tidak kena batasan kolom tabel)
                let rows = '';
                searched.forEach((s, idx) => {
                    const pct = s.total_questions > 0 ? Math.round(s.answered_count / s.total_questions * 100) : 0;
                    let badge = '';
                    if (s.status === 'active' || s.status === 'in_progress') badge = `<span class="badge-active"><i class="fas fa-circle" style="font-size:8px"></i> Sedang Ujian</span>`;
                    else if (s.status === 'submitted' || s.status === 'completed' || s.status === 'graded') badge = `<span class="badge-submitted"><i class="fas fa-check-circle" style="font-size:10px"></i> Selesai</span>`;
                    else if (s.status === 'suspended') badge = `<span class="badge-suspended"><i class="fas fa-ban" style="font-size:10px"></i> Ter-suspend</span>`;
                    else badge = `<span class="badge-pending">Belum Ujian</span>`;

                    const submitTime = s.submitted_at ? new Date(s.submitted_at).toLocaleTimeString('id-ID') : '-';

                    let violationInfo = `<span style="background:${s.violation_count ? '#fee2e2' : '#f1f5f9'};color:${s.violation_count ? '#991b1b' : '#64748b'};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600">
                        ${s.violation_count}x pelanggaran
                    </span>`;
                    if (s.violation_count > 0) {
                        violationInfo = `<button type="button" class="violation-log-trigger" data-session-id="${Number(s.id)}"
                            style="background:#fee2e2;color:#991b1b;padding:2px 8px;border:0;border-radius:4px;font-size:11px;font-weight:600;cursor:pointer;text-decoration:underline dotted">
                            <i class="fas fa-exclamation-triangle"></i> ${s.violation_count}x pelanggaran
                        </button>`;
                    }
                    if (s.status === 'suspended' && s.suspension_reason) {
                        const reason = s.suspension_reason === 'tab_switch' ? 'Pindah Tab' :
                            s.suspension_reason === 'window_blur' ? 'Keluar Window' :
                            s.suspension_reason === 'fullscreen_exit' ? 'Keluar Fullscreen' : s.suspension_reason;
                        violationInfo += `<br><span style="color:#dc2626;font-size:11px"><i class="fas fa-ban"></i> ${reason}</span>`;
                    }

                    rows += `<div style="display:flex;align-items:center;gap:16px;padding:10px 0;border-bottom:1px solid #f1f5f9;flex-wrap:nowrap;">
                        <div style="flex:0 0 auto;width:40px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#64748b;font-size:13px;white-space:nowrap;">${idx + 1}</div>
                        <div style="flex:0 0 auto;width:280px;display:flex;align-items:center;">
                            <div>
                                <div style="font-weight:600;color:#1e293b;font-size:14px;white-space:nowrap;">${s.full_name || '-'}</div>
                                <div style="font-size:12px;color:#64748b;white-space:nowrap;">${s.username || ''}</div>
                                <div style="font-size:12px;color:#64748b;white-space:nowrap;">${s.kelas || '-'}</div>
                            </div>
                        </div>
                        <div style="flex:0 0 auto;width:110px;white-space:nowrap;">${badge}</div>
                        <div style="flex:0 0 auto;width:180px;display:flex;align-items:center;gap:8px;">
                            <div class="progress-wrap" style="width:100px"><div class="progress-fill" style="width:${pct}%"></div></div>
                            <span style="font-size:13px;font-weight:600;color:#374151;white-space:nowrap">${s.answered_count}/${s.total_questions}</span>
                        </div>
                        <div style="flex:0 0 auto;width:110px;font-size:13px;color:#374151;white-space:nowrap">${(s.status === 'active' || s.status === 'in_progress') ? formatTime(s.remaining_seconds) : '-'}</div>
                        <div style="flex:0 0 auto;width:100px;font-size:13px;color:#374151;white-space:nowrap">${submitTime}</div>
                        <div style="flex:0 0 auto;width:140px;">${violationInfo || '<span style="color:#94a3b8;font-size:12px">-</span>'}</div>
                        <div style="flex:0 0 auto;width:170px;">${actionButtonsHtml(s, false)}</div>
                    </div>`;
                });

                document.getElementById('students-container').innerHTML = `
                    <div id="table-scroll-wrap" style="overflow-x:auto">
                        <div style="display:flex;align-items:center;gap:16px;padding:10px 0;border-bottom:2px solid #e2e8f0;background:#f8fafc;flex-wrap:nowrap;">
                            <div style="flex:0 0 auto;width:40px;font-weight:700;font-size:0.8rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;text-align:center;">No</div>
                            <div style="flex:0 0 auto;width:280px;font-weight:700;font-size:0.8rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;">Siswa</div>
                            <div style="flex:0 0 auto;width:110px;font-weight:700;font-size:0.8rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;">Status</div>
                            <div style="flex:0 0 auto;width:180px;font-weight:700;font-size:0.8rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;">Progress</div>
                            <div style="flex:0 0 auto;width:110px;font-weight:700;font-size:0.8rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;">Waktu Tersisa</div>
                            <div style="flex:0 0 auto;width:100px;font-weight:700;font-size:0.8rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;">Waktu Submit</div>
                            <div style="flex:0 0 auto;width:140px;font-weight:700;font-size:0.8rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;">Pelanggaran</div>
                            <div style="flex:0 0 auto;width:170px;font-weight:700;font-size:0.8rem;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;">Aksi</div>
                        </div>
                        <div style="display:flex;flex-direction:column;">${rows}</div>
                    </div>`;
            }

            const sessionsById = new Map(sessions.map(session => [String(session.id), session]));
            document.querySelectorAll('.violation-log-trigger').forEach(button => {
                button.addEventListener('click', () => {
                    const session = sessionsById.get(button.dataset.sessionId);
                    if (session) showViolations(session.full_name || '-', session.id);
                });
            });

            document.querySelectorAll('.resume-btn').forEach(button => {
                button.addEventListener('click', () => resumeSession(button.dataset.sessionId, button));
            });

            document.querySelectorAll('.status-tab').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.status-tab').forEach(b => {
                        b.classList.remove('active');
                        b.style.background = '#f8fafc';
                        b.style.color = '#64748b';
                    });
                    btn.classList.add('active');
                    btn.style.background = '#fff';
                    btn.style.color = '#6366f1';
                    currentStatusFilter = btn.dataset.filter;
                    currentPage = 1;
                    fetchData();
                });
            });

            document.getElementById('nameSearch').addEventListener('input', (e) => {
                currentSearch = e.target.value;
                currentPage = 1;
                if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    const all = Array.from(monitorSessions.values());
                    updateStudentsList(all, null);
                }, 300);
            });

            // Render pagination controls
            if (pagination && pagination.total > pagination.per_page) {
                const lastPage = pagination.last_page;
                const current = pagination.current_page;
                let phtml = '<div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:12px;flex-wrap:wrap;">';

                // Prev
                phtml += `<button type="button" onclick="goToPage(${current - 1})" ${current <= 1 ? 'disabled' : ''} style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;background:${current <= 1 ? '#f1f5f9' : '#fff'};color:${current <= 1 ? '#94a3b8' : '#374151'};font-size:12px;cursor:${current <= 1 ? 'not-allowed' : 'pointer'}">Sebelumnya</button>`;

                // Page numbers
                let start = Math.max(1, current - 2);
                let end = Math.min(lastPage, current + 2);
                if (start > 1) {
                    phtml += `<button type="button" onclick="goToPage(1)" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#374151;font-size:12px;cursor:pointer">1</button>`;
                    if (start > 2) phtml += `<span style="font-size:12px;color:#64748b">...</span>`;
                }
                for (let i = start; i <= end; i++) {
                    phtml += `<button type="button" onclick="goToPage(${i})" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;background:${i === current ? '#6366f1' : '#fff'};color:${i === current ? '#fff' : '#374151'};font-size:12px;font-weight:${i === current ? '700' : '600'};cursor:pointer">${i}</button>`;
                }
                if (end < lastPage) {
                    if (end < lastPage - 1) phtml += `<span style="font-size:12px;color:#64748b">...</span>`;
                    phtml += `<button type="button" onclick="goToPage(${lastPage})" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#374151;font-size:12px;cursor:pointer">${lastPage}</button>`;
                }

                // Next
                phtml += `<button type="button" onclick="goToPage(${current + 1})" ${current >= lastPage ? 'disabled' : ''} style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;background:${current >= lastPage ? '#f1f5f9' : '#fff'};color:${current >= lastPage ? '#94a3b8' : '#374151'};font-size:12px;cursor:${current >= lastPage ? 'not-allowed' : 'pointer'}">Selanjutnya</button>`;

                // Info
                phtml += `<span style="font-size:12px;color:#64748b;margin-left:8px">${pagination.from} - ${pagination.to} dari ${pagination.total}</span>`;

                phtml += '</div>';

                // Insert pagination after students-container
                let container = document.getElementById('students-container');
                let existing = document.getElementById('pagination-controls');
                if (existing) existing.remove();
                const pdiv = document.createElement('div');
                pdiv.id = 'pagination-controls';
                pdiv.innerHTML = phtml;
                container.parentNode.insertBefore(pdiv, container.nextSibling);
            } else {
                let existing = document.getElementById('pagination-controls');
                if (existing) existing.remove();
            }
        }

        function goToPage(page) {
            currentPage = page;
            fetchData();
        }

        let isFetching = false;

        function fetchData() {
            if (isFetching || document.hidden) return;
            isFetching = true;

            const icon = document.getElementById('refresh-icon');
            if (icon) icon.style.transform = 'rotate(360deg)';

            // Simpan posisi scroll sebelum update
            const wrap = document.getElementById('table-scroll-wrap');
            const scrollLeft = wrap ? wrap.scrollLeft : 0;

            fetch(`${baseUrl}/exam/monitorData/${examId}?status=${encodeURIComponent(currentStatusFilter)}&page=${currentPage}&limit=50`)
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(result => {
                    if (result.success) {
                        updateStats(result.summary);
                        updateStudentsList(result.data, result.pagination);
                        // Restore posisi scroll setelah render
                        const newWrap = document.getElementById('table-scroll-wrap');
                        if (newWrap && scrollLeft > 0) newWrap.scrollLeft = scrollLeft;
                    } else {
                        console.error('Monitor error:', result.message);
                    }
                    if (icon) setTimeout(() => icon.style.transform = 'rotate(0deg)', 500);
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    if (icon) setTimeout(() => icon.style.transform = 'rotate(0deg)', 500);
                })
                .finally(() => {
                    isFetching = false;
                });
        }

        function showToast(msg, type) {
            const bg = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6366f1';
            const t = document.createElement('div');
            t.style.cssText = `position:fixed;top:20px;right:20px;background:${bg};color:white;padding:12px 20px;border-radius:8px;z-index:9999;font-weight:600;box-shadow:0 4px 12px rgba(0,0,0,0.2)`;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 4000);
        }

        let refreshTimer = null;

        function updateRefreshSchedule() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
                refreshTimer = null;
            }
            if (!document.hidden) {
                fetchData();
                refreshTimer = setInterval(fetchData, 15000);
            }
        }
        document.addEventListener('visibilitychange', updateRefreshSchedule);
        updateRefreshSchedule();

        let pendingSessionId = null;

        function openAddDurationModal(sessionId) {
            pendingSessionId = sessionId;
            const s = monitorSessions.get(String(sessionId));
            if (s) document.getElementById('addDuration-student').textContent = 'Siswa: ' + (s.full_name || s.username || '-');
            const input = document.getElementById('addDuration-minutes');
            input.value = '';
            input.focus();
            document.getElementById('addDuration-modal').style.display = 'flex';
        }

        function closeAddDurationModal() {
            document.getElementById('addDuration-modal').style.display = 'none';
            pendingSessionId = null;
        }

        function submitAddDuration() {
            const input = document.getElementById('addDuration-minutes');
            const minutes = input.value;
            const m = parseInt(minutes, 10);
            if (!m || m <= 0) {
                showToast('Masukkan angka menit yang valid.', 'error');
                return;
            }
            closeAddDurationModal();
            fetch(`${baseUrl}/exam/addDuration/${pendingSessionId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'additional_minutes=' + encodeURIComponent(m)
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        showToast(result.message, 'success');
                        fetchData();
                    } else {
                        showToast('Gagal: ' + (result.message || 'Terjadi kesalahan'), 'error');
                    }
                })
                .catch(() => {
                    showToast('Gagal terhubung ke server.', 'error');
                });
        }

        function promptAddDuration(sessionId) {
            openAddDurationModal(sessionId);
        }

        let pendingResumeSession = null;
        let pendingResumeBtn = null;

        function openResumeModal(sessionId, btn) {
            const s = monitorSessions.get(String(sessionId));
            if (!s) {
                showToast('Data siswa tidak ditemukan. Muat ulang halaman.', 'error');
                return;
            }
            document.getElementById('resume-student-name').textContent = s.full_name || s.username || 'siswa ini';
            pendingResumeSession = sessionId;
            pendingResumeBtn = btn;
            document.getElementById('resume-modal').style.display = 'flex';
        }

        function closeResumeModal() {
            document.getElementById('resume-modal').style.display = 'none';
            pendingResumeSession = null;
            pendingResumeBtn = null;
        }

        async function confirmResumeSession() {
            const sessionId = pendingResumeSession;
            const btn = pendingResumeBtn;
            closeResumeModal();

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Memproses...';
            }

            try {
                const s = monitorSessions.get(String(sessionId));
                // Request yang sama dengan form "Lanjutkan Ujian" di halaman Suspend Akun
                await fetch(`${baseUrl}/admin/suspendAkun?user_id=${encodeURIComponent(s.user_id)}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'reset_session_id=' + encodeURIComponent(sessionId),
                    credentials: 'same-origin'
                });

                // Hasil dinilai dari status terbaru di database
                const r = await fetch(`${baseUrl}/exam/monitorData/${examId}?status=${encodeURIComponent(currentStatusFilter)}&page=${currentPage}&limit=50`, {
                    credentials: 'same-origin'
                });
                const result = await r.json();
                if (!result.success) throw new Error(result.message || 'Gagal memuat data');

                updateStats(result.summary);
                updateStudentsList(result.data, result.pagination);

                const after = result.data.find(x => String(x.id) === String(sessionId));
                if (after && after.status !== 'suspended') {
                    showToast('Ujian ' + (s.full_name || s.username || 'siswa ini') + ' dilanjutkan', 'success');
                } else {
                    showToast('Sesi belum berhasil dilanjutkan. Coba lewat halaman Suspend Akun.', 'error');
                }
            } catch (err) {
                console.error('Resume error:', err);
                showToast('Gagal terhubung ke server.', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Lanjutkan';
                }
                fetchData();
            }
        }

        async function resumeSession(sessionId, btn) {
            openResumeModal(sessionId, btn);
        }
    </script>

    <!-- Modal Pelanggaran -->
    <div id="violation-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
        <div style="background:white;border-radius:12px;padding:24px;max-width:480px;width:90%;max-height:80vh;overflow-y:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h5 style="margin:0;font-weight:700;color:#1e293b"><i class="fas fa-exclamation-triangle" style="color:#ef4444"></i> Log Pelanggaran</h5>
                <button onclick="document.getElementById('violation-modal').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#64748b">&times;</button>
            </div>
            <p id="violation-student" style="color:#64748b;font-size:13px;margin-bottom:12px"></p>
            <div id="violation-logs"></div>
        </div>
    </div>

    <script>
        async function showViolations(name, sessionId) {
            document.getElementById('violation-student').textContent = 'Siswa: ' + name;
            const logContainer = document.getElementById('violation-logs');
            logContainer.textContent = 'Memuat log pelanggaran...';
            document.getElementById('violation-modal').style.display = 'flex';

            try {
                const response = await fetch(`${baseUrl}/exam/monitorViolations/${encodeURIComponent(sessionId)}`);
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || `HTTP ${response.status}`);
                }

                const logs = result.data;
                if (!logs || logs.length === 0) {
                    logContainer.textContent = 'Tidak ada log detail pelanggaran.';
                } else {
                    let html = '';
                    logs.forEach((l, i) => {
                        const reason = String(l.reason || '').startsWith('Auto-suspend event:') ? '⛶ Keluar fullscreen / kehilangan fokus' :
                            l.reason === 'tab_switch' ? '🔀 Pindah Tab' :
                            l.reason === 'window_blur' ? '🪟 Keluar Window' :
                            l.reason === 'fullscreen_exit' ? '⛶ Keluar Fullscreen' : l.reason;
                        const time = new Date(l.time).toLocaleString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });
                        const safeReason = String(reason).replace(/[&<>"']/g, char => ({
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        })[char]);
                        html += `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #fee2e2">
                            <span style="font-weight:600;color:#991b1b">${i + 1}. ${safeReason}</span>
                            <span style="color:#94a3b8;font-size:12px">${time}</span>
                        </div>`;
                    });
                    logContainer.innerHTML = html;
                }
            } catch (error) {
                console.error('Violation logs error:', error);
                logContainer.textContent = 'Gagal memuat log pelanggaran.';
            }
        }
        document.getElementById('violation-modal').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>

    <!-- Modal Tambah Durasi -->
    <div id="addDuration-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:12px;padding:24px;max-width:400px;width:90%;max-height:80vh;overflow-y:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h5 style="margin:0;font-weight:700;color:#1e293b"><i class="fas fa-clock" style="color:#6366f1"></i> Tambah Durasi Ujian</h5>
                <button onclick="closeAddDurationModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#64748b">&times;</button>
            </div>
            <p id="addDuration-student" style="color:#64748b;font-size:13px;margin-bottom:16px"></p>
            <div style="margin-bottom:16px">
                <label style="display:block;font-size:12px;color:#64748b;font-weight:600;margin-bottom:6px">Tambahan durasi (menit)</label>
                <input type="number" id="addDuration-minutes" placeholder="0" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:14px" min="1">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" onclick="closeAddDurationModal()" style="padding:8px 16px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#64748b;font-size:13px;cursor:pointer">Batal</button>
                <button type="button" onclick="submitAddDuration()" style="padding:8px 16px;border:0;border-radius:6px;background:#6366f1;color:white;font-size:13px;cursor:pointer">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Lanjutkan Ujian -->
    <div id="resume-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:12px;padding:24px;max-width:400px;width:90%;max-height:80vh;overflow-y:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h5 style="margin:0;font-weight:700;color:#1e293b"><i class="fas fa-play" style="color:#10b981"></i> Lanjutkan Ujian</h5>
                <button onclick="closeResumeModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#64748b">&times;</button>
            </div>
            <p id="resume-student-name" style="color:#64748b;font-size:13px;margin-bottom:16px"></p>
            <p style="color:#64748b;font-size:13px;margin-bottom:20px">Status akan diubah menjadi aktif dan jawaban/histori pelanggaran tetap tersimpan. Waktu hanya akan berjalan setelah siswa mulai ujian kembali.</p>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" onclick="closeResumeModal()" style="padding:8px 16px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#64748b;font-size:13px;cursor:pointer">Batal</button>
                <button type="button" onclick="confirmResumeSession()" style="padding:8px 16px;border:0;border-radius:6px;background:#10b981;color:white;font-size:13px;cursor:pointer">Lanjutkan</button>
            </div>
        </div>
    </div>

</body>

</html>