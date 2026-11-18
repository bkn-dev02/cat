<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Suspend / Auto-Submit Ujian</title>
    <link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('vendor/fontawesome/6.0.0/css/all.min.css') ?>" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .07);
            transition: all .2s;
        }

        .btn-back:hover {
            background: #f8fafc;
            border-color: #2563eb;
            color: #2563eb;
            text-decoration: none;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 4px;
        }

        .page-header .icon {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        h2 {
            margin: 0;
            font-size: 1.25rem;
            line-height: 1.3;
        }

        .subtitle {
            color: #64748b;
            margin: 4px 0 24px;
            font-size: .85rem;
            padding-left: 56px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
            margin-bottom: 16px;
        }

        .search-form .form-group {
            min-width: 0;
        }

        .form-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            font-size: .875rem;
        }

        input[type=text] {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color .2s;
        }

        input[type=text]:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .14);
        }

        .search-input,
        .search-button {
            min-height: 48px;
        }

        .search-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s;
            white-space: nowrap;
        }

        .btn:hover {
            opacity: .85;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-sm {
            padding: 7px 14px;
            font-size: .82rem;
        }

        .btn-block {
            width: 100%;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 500;
            font-size: .9rem;
            line-height: 1.5;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .student-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 11px 16px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1e40af;
            font-weight: 600;
            flex-wrap: wrap;
            font-size: .9rem;
        }

        /* Kartu hasil pencarian */
        .result-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .result-card .rc-name {
            font-weight: 700;
            color: #1e293b;
        }

        .result-card .rc-meta {
            color: #64748b;
            font-size: .82rem;
            margin-top: 2px;
        }

        .btn-lihat {
            padding: 7px 16px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-lihat:hover {
            opacity: .85;
            color: white;
            text-decoration: none;
        }

        .note {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 13px 16px;
            color: #1e40af;
            font-size: .85rem;
            margin-bottom: 16px;
        }

        .note strong {
            display: block;
            margin-bottom: 4px;
            color: #1e3a8a;
        }

        .note ul {
            margin: 4px 0 0 16px;
            padding: 0;
        }

        .note li {
            margin-bottom: 3px;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 700;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-submitted {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-suspended {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-other {
            background: #e2e8f0;
            color: #475569;
        }

        .violation-box {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 4px 8px;
            margin-top: 5px;
            font-size: .74rem;
            color: #92400e;
        }

        .progress-wrap {
            background: #e2e8f0;
            border-radius: 4px;
            height: 5px;
            width: 100%;
            max-width: 90px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-fill {
            background: #6366f1;
            height: 100%;
        }

        .timer-realtime {
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            letter-spacing: .03em;
            transition: color .3s;
        }

        .timer-realtime.urgent {
            color: #ef4444;
            animation: pulse-red 1s ease-in-out infinite;
        }

        .timer-realtime.warning {
            color: #f59e0b;
        }

        .timer-realtime.ok {
            color: #059669;
        }

        .timer-realtime.active {
            color: #3b82f6;
        }

        .timer-realtime.expired {
            color: #94a3b8;
        }

        @keyframes pulse-red {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }

        .table-wrap {
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
        }

        .desktop-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .desktop-table th,
        .desktop-table td {
            padding: 11px 13px;
            text-align: left;
            font-size: .85rem;
        }

        .desktop-table th {
            background: #f8fafc;
            font-weight: 700;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }

        .desktop-table tr:not(:last-child) td {
            border-bottom: 1px solid #f1f5f9;
        }

        .desktop-table tr:hover td {
            background: #fafafa;
        }

        .mobile-cards {
            display: none;
        }

        .session-card {
            background: white;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
            margin-bottom: 12px;
        }

        .session-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 12px;
        }

        .session-card-title {
            font-weight: 700;
            font-size: .95rem;
            color: #1e293b;
            flex: 1;
        }

        .session-card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }

        .session-card-item label {
            font-size: .72rem;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 3px;
        }

        .session-card-item .val {
            font-size: .88rem;
            font-weight: 600;
            color: #1e293b;
        }

        .session-card-footer {
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
        }

        @media (max-width: 700px) {
            body {
                padding: 10px;
            }

            .container {
                padding-top: 12px;
            }

            h2 {
                font-size: 1.1rem;
            }

            .subtitle {
                padding-left: 0;
                margin-top: 8px;
            }

            .page-header {
                flex-wrap: wrap;
            }

            .form-row {
                flex-direction: column;
            }

            .form-row .btn {
                width: 100%;
            }

            .search-card {
                padding: 14px;
            }

            .search-form-row {
                align-items: stretch;
                gap: 12px;
            }

            .search-input,
            .search-button {
                min-height: 50px;
            }

            .search-input {
                padding: 12px;
                font-size: 16px;
            }

            .result-card {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .result-card .rc-meta {
                overflow-wrap: anywhere;
            }

            .btn-lihat {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
            }

            .table-wrap {
                display: none;
            }

            .mobile-cards {
                display: block;
            }
        }

        @media (max-width: 480px) {
            .session-card-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php require_once VIEW_PATH . '/partials/navbar.php'; ?>

    <div class="container">

        <div class="page-header">
            <div>
                <h2>Reset Ujian — Auto-Submit &amp; Suspend</h2>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message['type'] ?>"><?= $message['text'] ?></div>
        <?php endif; ?>

        <div class="card search-card">
            <form method="GET" action="<?= config('app_url') ?>/admin/suspendAkun" class="search-form" role="search">
                <div class="form-row search-form-row">
                    <div class="form-group">
                        <label for="student-search"><i class="fas fa-search"></i> Nama / Username Siswa</label>
                        <input id="student-search" class="search-input" type="text" name="q"
                            value="<?= htmlspecialchars($keyword) ?>"
                            placeholder="oktavianus" enterkeyhint="search" autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary search-button">🔍 Cari Siswa</button>
                </div>
            </form>
        </div>

        <?php if ($keyword !== '' && empty($results) && !$selectedUser): ?>
            <div class="alert alert-error">❌ Siswa dengan nama/username "<b><?= htmlspecialchars($keyword) ?></b>" tidak ditemukan.</div>

        <?php elseif (!empty($results) && !$selectedUser): ?>
            <div style="color:#64748b; font-size:.85rem; margin-bottom:10px"><?= count($results) ?> siswa ditemukan</div>
            <?php foreach ($results as $r): ?>
                <div class="result-card">
                    <div>
                        <div class="rc-name"><?= htmlspecialchars($r['full_name'] ?: $r['username']) ?></div>
                        <div class="rc-meta">
                            @<?= htmlspecialchars($r['username']) ?>
                            <?php if (!empty($r['kelas'])): ?>&nbsp;·&nbsp;<?= htmlspecialchars($r['kelas']) ?><?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= config('app_url') ?>/admin/suspendAkun?q=<?= urlencode($keyword) ?>&user_id=<?= $r['id'] ?>" class="btn-lihat">
                        <i class="fas fa-eye"></i> Lihat Sesi
                    </a>
                </div>
            <?php endforeach; ?>

        <?php elseif ($selectedUser): ?>

            <div class="student-info">
                <i class="fas fa-user"></i> <span><?= htmlspecialchars($selectedUser['full_name']) ?></span>
                <span style="color:#93c5fd">|</span>
                <a href="<?= config('app_url') ?>/admin/suspendAkun?q=<?= urlencode($keyword) ?>"
                    style="margin-left:auto; font-size:.82rem; color:#3b82f6; font-weight:400; text-decoration:none">
                    ← Kembali ke hasil
                </a>
            </div>

            <?php if (empty($sessions)): ?>
                <div class="card">
                    <p style="color:#64748b; text-align:center; padding:20px 0">Tidak ada session ujian ditemukan.</p>
                </div>
            <?php else: ?>

                <!-- Desktop Table -->
                <div class="table-wrap">
                    <table class="desktop-table">
                        <thead>
                            <tr>
                                <th style="padding-left:20px">Ujian</th>
                                <th>Status</th>
                                <th>Sisa Waktu (Live)</th>
                                <th style="padding-right:20px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td style="padding-left:20px; max-width:200px">
                                        <div style="font-weight:600"><?= htmlspecialchars($s['title']) ?></div>
                                        <?php if ($s['violation_count']): ?>
                                            <div style="font-size:.74rem; color:#ef4444; margin-top:3px"><i class="fas fa-bolt"></i> <?= (int)$s['violation_count'] ?> pelanggaran</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $s['status_label']['class'] ?>"><?= $s['status_label']['label'] ?></span>
                                        <?php
                                        $violationDetail = !empty($s['last_violation_reason'])
                                            ? $s['last_violation_reason']
                                            : (!empty($s['suspension_reason']) ? $s['suspension_reason'] : '');
                                        ?>
                                        <?php if ($s['status'] === 'suspended'): ?>
                                            <div class="violation-box"><i class="fas fa-clipboard-list"></i> Melakukan Pelanggaran: <?= htmlspecialchars($violationDetail !== '' ? $violationDetail : '-') ?></div>
                                        <?php elseif (in_array($s['status'], ['submitted', 'completed'], true) && !empty($s['submitted_at'])): ?>
                                            <div class="violation-box"><i class="fas fa-clock"></i> Submitted: <?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?></div>
                                        <?php elseif ($violationDetail !== ''): ?>
                                            <div class="violation-box"><i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($violationDetail) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($s['waiting_for_student']) && !$s['exam_expired']): ?>
                                            <span class="timer-realtime" data-session="<?= $s['id'] ?>">
                                                <?= sprintf('%02d:%02d', $s['remaining_minutes'], $s['remaining_seconds']) ?>
                                            </span>
                                            <div style="font-size:.7rem; color:#6b7280; margin-top:2px">Menunggu siswa mulai</div>
                                        <?php elseif (!empty($s['waiting_for_student']) && $s['exam_expired']): ?>
                                            <span style="color:#ef4444; font-size:.82rem; font-weight:600"><i class="fas fa-ban"></i> Berakhir</span>
                                            <div style="font-size:.7rem; color:#6b7280; margin-top:2px">Siswa tidak bisa mulai</div>
                                        <?php elseif (!empty($s['timer_running'])): ?>
                                            <span class="timer-realtime" data-session="<?= $s['id'] ?>">
                                                <?= sprintf('%02d:%02d', $s['remaining_minutes'], $s['remaining_seconds']) ?>
                                            </span>
                                            <div style="font-size:.7rem; color:#6b7280; margin-top:2px">Live (sedang dikerjakan)</div>
                                        <?php elseif ($s['can_reset'] && !$s['exam_expired']): ?>
                                            <span class="timer-realtime" data-session="<?= $s['id'] ?>">
                                                <?= sprintf('%02d:%02d', $s['remaining_minutes'], $s['remaining_seconds']) ?>
                                            </span>
                                        <?php elseif ($s['can_reset'] && $s['exam_expired']): ?>
                                            <span style="color:#ef4444; font-size:.82rem; font-weight:600"><i class="fas fa-ban"></i> Berakhir</span>
                                        <?php elseif (!$s['submitted_at'] && $s['status'] !== 'completed' && isset($timerData[$s['id']])): ?>
                                            <span class="timer-realtime" data-session="<?= $s['id'] ?>">
                                                <?= sprintf('%02d:%02d', $s['remaining_minutes'], $s['remaining_seconds']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding-right:20px">
                                        <?php if (!empty($s['waiting_for_student'])): ?>
                                            <?php if (!empty($s['can_change_duration'])): ?>
                                                <button type="button" class="btn btn-info btn-sm"
                                                    onclick="openRetakeModal(<?= (int)$s['id'] ?>, <?= (int)min(60, max(1, (int)ceil($s['effective_remaining'] / 60))) ?>, 'edit')">
                                                    <i class="fas fa-clock"></i> Ubah durasi
                                                </button>
                                            <?php else: ?>
                                                <span style="color:#2563eb; font-weight:600; font-size:.82rem"><i class="fas fa-hourglass-half"></i> Menunggu Siswa</span>
                                            <?php endif; ?>
                                        <?php elseif (!empty($s['timer_running'])): ?>
                                            <span style="color:#10b981; font-weight:600; font-size:.82rem"><i class="fas fa-circle"></i> Sedang Dikerjakan</span>
                                        <?php elseif ($s['already_reset']): ?>
                                            <span style="color:#10b981; font-weight:600; font-size:.82rem"><i class="fas fa-play"></i> Sedang Aktif</span>
                                        <?php elseif (!empty($s['can_retake'])): ?>
                                            <button type="button" class="btn btn-info btn-sm"
                                                onclick="openRetakeModal(<?= (int)$s['id'] ?>, <?= (int)min(60, max(1, (int)($s['duration'] ?? 60))) ?>, 'retake')">
                                                <i class="fas fa-redo"></i> Retake
                                            </button>
                                        <?php elseif ($s['status'] === 'suspended'): ?>
                                            <form method="POST"
                                                action="<?= config('app_url') ?>/admin/suspendAkun?q=<?= urlencode($keyword) ?>&user_id=<?= (int)$userId ?>"
                                                onsubmit="return confirmReset(this, '<?= addslashes(htmlspecialchars($s['title'])) ?>')">
                                                <input type="hidden" name="reset_session_id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-play"></i> Lanjutkan Ujian</button>
                                            </form>
                                        <?php elseif ($s['status'] === 'active'): ?>
                                            <span style="color:#10b981; font-weight:600; font-size:.82rem"><i class="fas fa-circle"></i> Active</span>
                                        <?php else: ?>
                                            <span style="color:#64748b; font-size:.82rem"><?= htmlspecialchars($s['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="mobile-cards">
                    <?php foreach ($sessions as $s): ?>
                        <div class="session-card">
                            <div class="session-card-header">
                                <div class="session-card-title"><?= htmlspecialchars($s['title']) ?></div>
                                <span class="badge <?= $s['status_label']['class'] ?>"><?= $s['status_label']['label'] ?></span>
                            </div>
                            <?php
                            $violationDetail = !empty($s['last_violation_reason'])
                                ? $s['last_violation_reason']
                                : (!empty($s['suspension_reason']) ? $s['suspension_reason'] : '');
                            ?>
                            <?php if ($s['status'] === 'suspended'): ?>
                                <div class="violation-box" style="margin-bottom:10px">
                                    <i class="fas fa-clipboard-list"></i> Melakukan Pelanggaran: <?= htmlspecialchars($violationDetail !== '' ? $violationDetail : '-') ?>
                                </div>
                            <?php elseif (in_array($s['status'], ['submitted', 'completed'], true) && !empty($s['submitted_at'])): ?>
                                <div class="violation-box" style="margin-bottom:10px">
                                    <i class="fas fa-clock"></i> Submitted: <?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?>
                                </div>
                            <?php elseif ($violationDetail !== ''): ?>
                                <div class="violation-box" style="margin-bottom:10px">
                                    <i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($violationDetail) ?>
                                </div>
                            <?php endif; ?>
                            <div class="session-card-grid">
                                <div class="session-card-item">
                                    <label>Sisa Waktu (Live)</label>
                                    <?php if (!empty($s['waiting_for_student']) && !$s['exam_expired']): ?>
                                        <span class="timer-realtime val" data-session="<?= $s['id'] ?>">
                                            <?= sprintf('%02d:%02d', $s['remaining_minutes'], $s['remaining_seconds']) ?>
                                        </span>
                                        <div style="font-size:.7rem;color:#6b7280">Menunggu siswa mulai</div>
                                    <?php elseif (!empty($s['waiting_for_student']) && $s['exam_expired']): ?>
                                        <div class="val" style="color:#ef4444">Berakhir</div>
                                        <div style="font-size:.7rem;color:#6b7280">Siswa tidak bisa mulai</div>
                                    <?php elseif (!empty($s['timer_running'])): ?>
                                        <span class="timer-realtime val" data-session="<?= $s['id'] ?>">
                                            <?= sprintf('%02d:%02d', $s['remaining_minutes'], $s['remaining_seconds']) ?>
                                        </span>
                                        <div style="font-size:.7rem;color:#6b7280">Live (sedang dikerjakan)</div>
                                    <?php elseif ($s['can_reset'] && !$s['exam_expired']): ?>
                                        <span class="timer-realtime val" data-session="<?= $s['id'] ?>">
                                            <?= sprintf('%02d:%02d', $s['remaining_minutes'], $s['remaining_seconds']) ?>
                                        </span>
                                    <?php elseif ($s['can_reset'] && $s['exam_expired']): ?>
                                        <div class="val" style="color:#ef4444">Berakhir</div>
                                    <?php elseif (!$s['submitted_at'] && $s['status'] !== 'completed' && isset($timerData[$s['id']])): ?>
                                        <span class="timer-realtime val" data-session="<?= $s['id'] ?>">
                                            <?= sprintf('%02d:%02d', $s['remaining_minutes'], $s['remaining_seconds']) ?>
                                        </span>
                                    <?php else: ?>
                                        <div class="val" style="color:#94a3b8">—</div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($s['violation_count']): ?>
                                    <div class="session-card-item">
                                        <label>Pelanggaran</label>
                                        <div class="val" style="color:#ef4444"><i class="fas fa-bolt"></i> <?= (int)$s['violation_count'] ?>x</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="session-card-footer">
                                <?php if (!empty($s['waiting_for_student'])): ?>
                                    <?php if (!empty($s['can_change_duration'])): ?>
                                        <button type="button" class="btn btn-info btn-block"
                                            onclick="openRetakeModal(<?= (int)$s['id'] ?>, <?= (int)min(60, max(1, (int)ceil($s['effective_remaining'] / 60))) ?>, 'edit')">
                                            <i class="fas fa-clock"></i> Ubah durasi
                                        </button>
                                    <?php else: ?>
                                        <div style="text-align:center;color:#2563eb;font-weight:600;font-size:.85rem"><i class="fas fa-hourglass-half"></i> Siap dilanjutkan, menunggu siswa mulai</div>
                                    <?php endif; ?>
                                <?php elseif (!empty($s['timer_running'])): ?>
                                    <div style="text-align:center;color:#10b981;font-weight:600;font-size:.85rem"><i class="fas fa-circle"></i> Sedang dikerjakan siswa</div>
                                <?php elseif ($s['already_reset']): ?>
                                    <div style="text-align:center;color:#10b981;font-weight:600;font-size:.85rem"><i class="fas fa-play"></i> Sedang Aktif</div>
                                <?php elseif (!empty($s['can_retake'])): ?>
                                    <button type="button" class="btn btn-info btn-block"
                                        onclick="openRetakeModal(<?= (int)$s['id'] ?>, <?= (int)min(60, max(1, (int)($s['duration'] ?? 60))) ?>, 'retake')">
                                        <i class="fas fa-redo"></i> Retake
                                    </button>
                                <?php elseif ($s['status'] === 'suspended'): ?>
                                    <form method="POST"
                                        action="<?= config('app_url') ?>/admin/suspendAkun?q=<?= urlencode($keyword) ?>&user_id=<?= (int)$userId ?>"
                                        onsubmit="return confirmReset(this, '<?= addslashes(htmlspecialchars($s['title'])) ?>')">
                                        <input type="hidden" name="reset_session_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-warning btn-block"><i class="fas fa-play"></i> Lanjutkan Ujian</button>
                                    </form>
                                <?php elseif ($s['status'] === 'active'): ?>
                                    <div style="text-align:center;color:#10b981;font-weight:600;font-size:.85rem"><i class="fas fa-circle"></i> Active</div>
                                <?php else: ?>
                                    <div style="text-align:center;color:#64748b;font-size:.85rem"><?= htmlspecialchars($s['status']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- Modal Retake Durasi -->
    <div id="retakeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10000; align-items:center; justify-content:center;">
        <div style="background:white; max-width:450px; width:90%; border-radius:1rem; padding:1.5rem; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h3 id="retakeModalTitle" style="margin:0; font-size:1.1rem; font-weight:700;"><i class="fas fa-redo"></i> Atur Durasi Retake</h3>
                <button type="button" onclick="closeRetakeModal()" style="background:none; border:none; color:#64748b; font-size:1.25rem; cursor:pointer;">&times;</button>
            </div>
            <form id="retakeForm" method="POST"
                action="<?= config('app_url') ?>/admin/suspendAkun?q=<?= urlencode($keyword) ?>&user_id=<?= (int)$userId ?>"
                onsubmit="document.getElementById('retakeSubmitBtn').disabled = true;">
                <input type="hidden" name="retake_session_id" id="retakeSessionId">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; font-size:.875rem; font-weight:600; color:#374151; margin-bottom:.375rem;">Durasi (menit)</label>
                    <input type="number" name="retake_duration_minutes" id="retakeDurationMinutes" min="1" max="60" step="1" value="60"
                        style="width:100%; padding:.625rem; border:1px solid #e2e8f0; border-radius:.5rem; font-size:1rem;" required>
                    <p id="retakeModalHint" style="font-size:.75rem; color:#64748b; margin-top:.25rem;">Maksimal 60 menit. Waktu baru berjalan setelah siswa menekan Mulai.</p>
                </div>
                <div style="display:flex; gap:.75rem; justify-content:flex-end;">
                    <button type="button" onclick="closeRetakeModal()" style="padding:.625rem 1.25rem; border:1px solid #e2e8f0; border-radius:.5rem; background:#f8fafc; color:#475569; font-weight:600; cursor:pointer;">Batal</button>
                    <button type="submit" id="retakeSubmitBtn" style="padding:.625rem 1.25rem; border:none; border-radius:.5rem; background:#6366f1; color:white; font-weight:600; cursor:pointer;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const TIMER_DATA = <?= json_encode($timerData ?? [], JSON_UNESCAPED_UNICODE) ?>;
        const PAGE_LOAD_TS = Math.floor(Date.now() / 1000);
        const MAX_RETAKE_MINUTES = 60;

        function fmt(secs) {
            if (secs <= 0) return '00:00';
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        // suspended / waiting: beku. running: jalan dari started_at (jam server).
        function calcRemaining(data) {
            const browserElapsed = Math.floor(Date.now() / 1000) - PAGE_LOAD_TS;
            const nowServer = data.serverNow + browserElapsed;
            let remaining = data.savedRemaining;

            if (data.type === 'running' && data.startedAtTs) {
                remaining = data.savedRemaining - Math.max(0, nowServer - data.startedAtTs);
            }
            if (data.endTs) {
                remaining = Math.min(remaining, data.endTs - nowServer);
            }
            return Math.max(0, Math.floor(remaining));
        }

        function colorClass(secs, type) {
            if (secs <= 0) return 'expired';
            if (type === 'running') return 'active';
            if (secs < 60) return 'urgent';
            if (secs < 300) return 'warning';
            return 'ok';
        }

        function updateAllTimers() {
            document.querySelectorAll('.timer-realtime[data-session]').forEach(el => {
                const sid = el.dataset.session;
                const data = TIMER_DATA[sid];
                if (!data) return;

                const rem = calcRemaining(data);
                el.textContent = fmt(rem);

                el.classList.remove('expired', 'urgent', 'warning', 'ok', 'active');
                el.classList.add(colorClass(rem, data.type));

                if (rem <= 0) {
                    const input = document.querySelector(`form input[name="reset_session_id"][value="${sid}"]`);
                    if (input) {
                        const btn = input.closest('form').querySelector('button[type="submit"]');
                        if (btn && !btn.disabled) {
                            btn.disabled = true;
                            btn.textContent = '⛔ Waktu habis';
                            btn.style.opacity = '0.5';
                            btn.style.cursor = 'not-allowed';
                        }
                    }
                }
            });
        }

        updateAllTimers();
        setInterval(updateAllTimers, 1000);

        function openRetakeModal(sessionId, defaultMinutes, mode) {
            const minutes = Math.min(MAX_RETAKE_MINUTES, Math.max(1, parseInt(defaultMinutes, 10) || MAX_RETAKE_MINUTES));
            document.getElementById('retakeSessionId').value = sessionId;
            document.getElementById('retakeDurationMinutes').value = minutes;
            document.getElementById('retakeSubmitBtn').disabled = false;
            document.getElementById('retakeModalTitle').innerHTML = mode === 'edit' ?
                '<i class="fas fa-clock"></i> Ubah Durasi' :
                '<i class="fas fa-redo"></i> Atur Durasi Retake';
            document.getElementById('retakeModalHint').textContent = mode === 'edit' ?
                'Durasi baru menggantikan durasi sebelumnya. Maksimal 60 menit. Waktu baru berjalan setelah siswa menekan Mulai.' :
                'Maksimal 60 menit. Waktu baru berjalan setelah siswa menekan Mulai.';
            document.getElementById('retakeModal').style.display = 'flex';
            document.getElementById('retakeDurationMinutes').focus();
        }

        function closeRetakeModal() {
            document.getElementById('retakeModal').style.display = 'none';
        }

        document.getElementById('retakeModal').addEventListener('click', function(e) {
            if (e.target === this) closeRetakeModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeRetakeModal();
        });

        function confirmReset(form, title) {
            return confirm(
                'Lanjutkan ujian "' + title + '"?\n\n' +
                'Status akan diubah menjadi aktif dan jawaban/histori pelanggaran tetap tersimpan.\n' +
                'Waktu hanya akan berjalan setelah siswa mulai ujian kembali.\n\n' +
                'Lanjutkan?'
            );
        }
    </script>
    <script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>

</html>