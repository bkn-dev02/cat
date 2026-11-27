<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <!-- Header Top -->
            <div style=" display:flex; justify-content:space-between; align-items:flex-start; gap:1.5rem; flex-wrap:wrap; margin-bottom:1.5rem; padding-bottom:1.25rem; border-bottom:1px solid #e2e8f0; ">
                <!-- Informasi Ujian -->
                <div style="min-width:280px;">
                    <!-- Back -->
                    <a href="<?= config('app_url') ?>/exam/monitor" style=" display:inline-flex; align-items:center; gap:0.4rem; color:#6366f1; text-decoration:none; font-size:13px; font-weight:600; margin-bottom:0.6rem; ">
                        <i class="fas fa-arrow-left"></i> <span>Kembali ke Monitor List</span> </a> <!-- Judul -->
                    <h2 style=" display:flex; align-items:center; gap:0.6rem; font-size:1.45rem; font-weight:800; color:#1e293b; margin:0 0 0.4rem; line-height:1.3; "> <i class="fas fa-desktop" style="color:#6366f1;font-size:1.25rem;"></i> <?= htmlspecialchars($exam['title']) ?> </h2>
                    <!-- Meta Informasi -->
                    <div style=" display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem 0.9rem; color:#64748b; font-size:13px; "> <span> <i class="fas fa-calendar" style="margin-right:0.3rem;"></i> <?= formatWaktuIndonesia($exam['start_time'], 'l, d F Y H:i') ?> WIB </span> <span style="color:#cbd5e1;">|</span> <span> <i class="fas fa-clock" style="margin-right:0.3rem;"></i> Durasi <?= $exam['duration'] ?> menit </span> </div>
                </div> <!-- Status & Refresh -->
                <div style=" display:flex; align-items:center; justify-content:flex-end; gap:1rem; flex-wrap:wrap; ">
                    <!-- Status Ujian -->
                    <span id="exam-status-badge" class="exam-status-badge <?= htmlspecialchars($examStatusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($examStatusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <!-- Auto Refresh -->
                    <div class="refresh-indicator" style=" display:flex; align-items:center; gap:0.45rem; color:#64748b; font-size:12px; white-space:nowrap; "> <i class="fas fa-sync-alt" id="refresh-icon"></i> <span>Update otomatis setiap 15 detik</span> </div>
                </div>
            </div>
            <!-- Statistik Peserta -->
            <div id="stats-grid" class="row g-3">
                <!-- Total Peserta -->
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#1e293b;"> - </span>
                        <div class="stat-label"> Total Peserta </div>
                    </div>
                </div>
                <!-- Sedang Ujian -->
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#10b981;"> - </span>
                        <div class="stat-label"> Sedang Ujian </div>
                    </div>
                </div>
                <!-- Belum Ujian -->
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#f59e0b;"> - </span>
                        <div class="stat-label"> Belum Ujian </div>
                    </div>
                </div>
                <!-- Sudah Submit -->
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#6366f1;"> - </span>
                        <div class="stat-label"> Sudah Submit </div>
                    </div>
                </div> <!-- Suspend -->
                <div class="col-6 col-md">
                    <div class="stat-card"> <span class="stat-number" style="color:#ef4444;"> - </span>
                        <div class="stat-label"> Ter-suspend </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Peserta -->
        <div class="card-glass">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                <h5 style="font-weight:700;color:#1e293b;margin:0"><i class="fas fa-users" style="color:#6366f1"></i> Daftar Peserta</h5>
                <span class="refresh-indicator"><i class="fas fa-sync-alt pulse"></i> Update otomatis setiap 15 detik</span>
            </div>
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

        function actionButtonsHtml(s, mobile) {
            const isRunning = s.status === 'active' || s.status === 'in_progress';
            const isSuspended = s.status === 'suspended';

            if (!isRunning && !isSuspended) {
                return mobile ? '' : '<span style="color:#94a3b8;font-size:11px">-</span>';
            }

            const size = mobile ? 'padding:8px 16px;font-size:12px;border-radius:6px' :
                'padding:4px 8px;font-size:11px;border-radius:4px';
            const base = `border:0;font-weight:600;${size}`;

            const stateBtn = isSuspended ?
                `<button type="button" class="resume-btn" data-session-id="${Number(s.id)}" style="${base};background:#10b981;color:white;cursor:pointer"><i class="fas fa-play"></i> Lanjutkan</button>` :
                `<button type="button" disabled style="${base};background:#e2e8f0;color:#64748b;cursor:not-allowed">Active</button>`;

            const addBtn = `<button type="button" onclick="promptAddDuration(${Number(s.id)})" style="${base};background:#6366f1;color:white;cursor:pointer">+ Waktu</button>`;

            const wrapStyle = mobile ?
                'display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:8px' :
                'display:flex;gap:6px;flex-wrap:wrap';

            return `<div style="${wrapStyle}">${stateBtn}${addBtn}</div>`;
        }

        async function resumeSession(sessionId, btn) {
            const s = monitorSessions.get(String(sessionId));
            if (!s) {
                alert('Data siswa tidak ditemukan. Muat ulang halaman.');
                return;
            }
            const name = s.full_name || s.username || 'siswa ini';

            if (!confirm(
                    'Lanjutkan ujian untuk ' + name + '?\n\n' +
                    'Status akan diubah menjadi aktif dan jawaban/histori pelanggaran tetap tersimpan.\n' +
                    'Waktu hanya akan berjalan setelah siswa mulai ujian kembali.\n\n' +
                    'Lanjutkan?'
                )) return;

            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Memproses...';
            }

            try {
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
                const r = await fetch(`${baseUrl}/exam/monitorData/${examId}`, {
                    credentials: 'same-origin'
                });
                const result = await r.json();
                if (!result.success) throw new Error(result.message || 'Gagal memuat data');

                updateStats(result.summary);
                updateStudentsList(result.data);

                const after = result.data.find(x => String(x.id) === String(sessionId));
                if (after && after.status !== 'suspended') {
                    showToast('Ujian ' + name + ' dilanjutkan', 'success');
                } else {
                    alert('Sesi belum berhasil dilanjutkan. Coba lewat halaman Suspend Akun untuk melihat alasannya.');
                }
            } catch (err) {
                console.error('Resume error:', err);
                alert('Gagal terhubung ke server.');
                fetchData();
            }
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
        }

        function updateStudentsList(sessions) {
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

            const isMobile = window.innerWidth < 768;

            if (isMobile) {
                // Mobile: card layout
                let cards = '';
                sessions.forEach(s => {
                    const pct = s.total_questions > 0 ? Math.round(s.answered_count / s.total_questions * 100) : 0;
                    let statusColor = s.status === 'active' || s.status === 'in_progress' ? '#10b981' :
                        s.status === 'submitted' || s.status === 'completed' ? '#6366f1' :
                        s.status === 'suspended' ? '#ef4444' : '#64748b';
                    let statusText = s.status === 'active' || s.status === 'in_progress' ? 'Sedang Ujian' :
                        s.status === 'submitted' || s.status === 'completed' ? 'Selesai' :
                        s.status === 'suspended' ? 'Ter-suspend' : s.status;

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
                            <div class="progress-wrap" style="flex:1"><div class="progress-fill" style="width:${pct}%"></div></div>
                            <span style="font-size:12px;font-weight:600;color:#374151;white-space:nowrap">${s.answered_count}/${s.total_questions} (${pct}%)</span>
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
                // Desktop: table layout
                let rows = '';
                sessions.forEach(s => {
                    const pct = s.total_questions > 0 ? Math.round(s.answered_count / s.total_questions * 100) : 0;
                    let badge = '';
                    if (s.status === 'active' || s.status === 'in_progress') badge = `<span class="badge-active"><i class="fas fa-circle" style="font-size:8px"></i> Sedang Ujian</span>`;
                    else if (s.status === 'submitted' || s.status === 'completed' || s.status === 'graded') badge = `<span class="badge-submitted"><i class="fas fa-check-circle" style="font-size:10px"></i> Selesai</span>`;
                    else if (s.status === 'suspended') badge = `<span class="badge-suspended"><i class="fas fa-ban" style="font-size:10px"></i> Ter-suspend</span>`;
                    else badge = `<span class="badge-pending">${s.status}</span>`;

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

                    rows += `<tr>
                        <td>
                            <div style="display:flex;align-items:center">
                                <div class="avatar">${getInitials(s.full_name)}</div>
                                <div>
                                    <div style="font-weight:600;color:#1e293b;font-size:14px">${s.full_name || '-'}</div>
                                    <div style="font-size:12px;color:#64748b">${s.username || ''} ${s.kelas ? '· ' + s.kelas : ''}</div>
                                </div>
                            </div>
                        </td>
                        <td>${badge}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="progress-wrap"><div class="progress-fill" style="width:${pct}%"></div></div>
                                <span style="font-size:13px;font-weight:600;color:#374151">${s.answered_count}/${s.total_questions}</span>
                                <span style="font-size:12px;color:#64748b">(${pct}%)</span>
                            </div>
                        </td>
                        <td style="font-size:13px;color:#374151">${(s.status === 'active' || s.status === 'in_progress') ? formatTime(s.remaining_seconds) : '-'}</td>
                        <td style="font-size:13px;color:#374151">${submitTime}</td>
                        <td>${violationInfo || '<span style="color:#94a3b8;font-size:12px">-</span>'}</td>
                        <td>${actionButtonsHtml(s, false)}</td>
                    </tr>`;
                });

                document.getElementById('students-container').innerHTML = `
                    <div class="table-responsive" id="table-scroll-wrap" style="overflow-x:auto">
                        <table class="table table-hover" style="min-width:700px">
                            <thead><tr>
                                <th style="min-width:200px">Siswa</th>
                                <th style="min-width:110px">Status</th>
                                <th style="min-width:180px">Progress</th>
                                <th style="min-width:110px">Waktu Tersisa</th>
                                <th style="min-width:100px">Waktu Submit</th>
                                <th style="min-width:140px">Pelanggaran</th>
                                <th style="min-width:170px">Aksi</th>
                            </tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
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

            fetch(`${baseUrl}/exam/monitorData/${examId}`)
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(result => {
                    if (result.success) {
                        updateStats(result.summary);
                        updateStudentsList(result.data);
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
            const t = document.createElement('div');
            t.style.cssText = `position:fixed;top:20px;right:20px;background:${type==='success'?'#10b981':'#6366f1'};color:white;padding:12px 20px;border-radius:8px;z-index:9999;font-weight:600;box-shadow:0 4px 12px rgba(0,0,0,0.2)`;
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

        function promptAddDuration(sessionId) {
            const minutes = prompt('Masukkan tambahan durasi (menit):');
            if (minutes === null) return;
            const m = parseInt(minutes, 10);
            if (!m || m <= 0) {
                alert('Masukkan angka menit yang valid.');
                return;
            }
            fetch(`${baseUrl}/exam/addDuration/${sessionId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'additional_minutes=' + encodeURIComponent(m)
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message);
                        fetchData();
                    } else {
                        alert('Gagal: ' + (result.message || 'Terjadi kesalahan'));
                    }
                })
                .catch(() => {
                    alert('Gagal terhubung ke server.');
                });
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
</body>

</html>