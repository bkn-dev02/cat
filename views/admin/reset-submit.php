<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Submit Tidak Sengaja</title>
<link href="<?= asset_url('vendor/bootstrap/5.3.0/dist/css/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= asset_url('vendor/fontawesome/6.0.0/css/all.min.css') ?>" rel="stylesheet">
<link href="<?= asset_url('vendor/fonts/plus-jakarta-sans.css') ?>" rel="stylesheet">
<style>
    * { font-family: 'Plus Jakarta Sans', sans-serif; }
    body { background: var(--light, #f8fafc); }

    .page-header { box-shadow: 0 4px 12px rgba(0,0,0,.08); padding: 1rem 0; margin: 1rem auto; text-align: center; border-radius: 1rem; }
    .page-header h1 { font-size: 1.2rem; font-weight: 700; color: #1e293b; margin: 0; }
    .page-header p  { color: #64748b; margin: .25rem 0 0; font-size: .9rem; }
    .card { border: none; border-radius: 12px; box-shadow: 0 1px 8px rgba(0,0,0,.07); }

    .rs-page {
        --border: #e8e0d5; --border-2: #d4c9b8;
        --ink: #1a1208; --ink-3: #7a6d5e; --ink-4: #a89e92;
        --amber: #d97706; --amber-bg: #fef3c7; --amber-bdr: #fcd34d;
        --red: #dc2626; --red-bg: #fef2f2; --red-bdr: #fca5a5;
        --green: #059669; --green-bg: #ecfdf5; --green-bdr: #6ee7b7;
        --blue: #2563eb; --blue-bg: #eff6ff; --blue-bdr: #bfdbfe;
        --accent: #b45309;
    }

    .rs-page .info-box { background: var(--blue-bg); border: 1px solid var(--blue-bdr); border-radius: 8px; padding: 14px 18px; font-size: .84rem; color: #1e3a8a; line-height: 1.65; margin-bottom: 20px; }
    .rs-page .info-box strong { display: block; margin-bottom: 6px; font-size: .82rem; letter-spacing: .04em; text-transform: uppercase; }
    .rs-page .info-box ul { margin: 0 0 0 18px; padding: 0; }
    .rs-page .info-box li { margin-bottom: 3px; }

    .rs-page .student-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: #fdf8f2; border: 1px solid var(--border); border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; font-size: .88rem; }
    .rs-page .student-name  { font-weight: 700; }
    .rs-page .student-email { color: var(--blue); font-family: monospace; font-size: .82rem; }
    .rs-page .student-divider { color: var(--border-2); }

    .rs-page .stats-row { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
    .rs-page .stat-box { flex: 1; min-width: 100px; background: #fff; border: 1px solid var(--border); border-radius: 8px; padding: 14px 18px; text-align: center; box-shadow: 0 1px 8px rgba(0,0,0,.05); }
    .rs-page .stat-num { font-size: 1.8rem; font-weight: 700; line-height: 1; }
    .rs-page .stat-num.amber { color: var(--amber); }
    .rs-page .stat-num.green { color: var(--green); }
    .rs-page .stat-num.ink   { color: var(--ink); }
    .rs-page .stat-label { font-size: .73rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--ink-4); margin-top: 5px; }

    .rs-badge { display: inline-block; padding: 3px 11px; border-radius: 20px; font-size: .72rem; font-weight: 700; white-space: nowrap; border: 1px solid; }
    .rs-badge-active    { background: var(--green-bg); color: #065f46; border-color: var(--green-bdr); }
    .rs-badge-done      { background: #f0fdf4; color: #065f46; border-color: #bbf7d0; }
    .rs-badge-reset     { background: var(--amber-bg); color: #78350f; border-color: var(--amber-bdr); }
    .rs-badge-suspended { background: var(--red-bg); color: #991b1b; border-color: var(--red-bdr); }
    .rs-badge-other     { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

    .rs-page .progress-wrap { background: var(--border); border-radius: 4px; height: 5px; width: 80px; overflow: hidden; margin-top: 4px; }
    .rs-page .progress-fill { background: var(--accent); height: 100%; }

    .rs-table-wrap { border-radius: 12px; overflow: hidden; border: 1px solid var(--border); box-shadow: 0 1px 8px rgba(0,0,0,.06); margin-bottom: 20px; }
    .rs-table { width: 100%; border-collapse: collapse; background: #fff; margin: 0; }
    .rs-table th, .rs-table td { padding: 12px 14px; text-align: left; font-size: .84rem; vertical-align: top; }
    .rs-table th { background: #fdf8f2; font-weight: 700; font-size: .75rem; letter-spacing: .05em; text-transform: uppercase; color: var(--ink-3); border-bottom: 1.5px solid var(--border); }
    .rs-table tr:not(:last-child) td { border-bottom: 1px solid var(--border); }
    .rs-table tr:hover td { background: #fdf8f2; }
    .rs-table .pl { padding-left: 22px; }

    .rs-countdown { font-family: monospace; font-weight: 600; font-size: .95rem; letter-spacing: .04em; }
    .rs-countdown.fresh   { color: var(--green); }
    .rs-countdown.caution { color: var(--amber); }
    .rs-countdown.urgent  { color: var(--red); animation: rs-blink 1s ease-in-out infinite; }
    .rs-countdown.expired { color: var(--ink-4); }
    @keyframes rs-blink { 0%,100% { opacity: 1; } 50% { opacity: .4; } }

    .rs-page .reason-tag { display: inline-block; background: var(--red-bg); border: 1px solid var(--red-bdr); border-radius: 6px; padding: 3px 9px; font-size: .73rem; color: #991b1b; margin-top: 4px; }
    .rs-page .time-hint { font-size: .73rem; color: var(--ink-4); margin-top: 2px; }
    .rs-page .time-ago  { font-size: .73rem; color: var(--amber); font-weight: 600; margin-top: 2px; }
    .rs-page .page-footer { margin-top: 30px; text-align: center; color: var(--ink-4); font-size: .77rem; padding-bottom: 20px; }

    @media (max-width: 680px) {
        .rs-table, .rs-table thead, .rs-table tbody, .rs-table th, .rs-table td, .rs-table tr { display: block; }
        .rs-table thead { display: none; }
        .rs-table td { padding: 6px 14px; }
        .rs-table td::before { content: attr(data-label); font-weight: 700; font-size: .73rem; color: var(--ink-3); text-transform: uppercase; letter-spacing: .04em; display: block; margin-bottom: 2px; }
        .rs-table tr { border-bottom: 1.5px solid var(--border); padding: 12px 0; }
        .rs-page .stat-num { font-size: 1.5rem; }
    }
</style>
</head>
<body>

<?php require_once VIEW_PATH . '/partials/navbar.php'; ?>

<div class="container rs-page" style="max-width: 1040px">

    <div class="page-header">
        <div class="px-3">
            <h1><i class="fas fa-rotate-left text-warning me-2"></i> Reset Submit Tidak Sengaja</h1>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= $message['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Form cari -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="POST" action="<?= config('app_url') ?>/admin/resetSubmit" class="d-flex gap-2">
                <input type="text" name="email" class="form-control"
                       value="<?= htmlspecialchars($email) ?>"
                       placeholder="Email / username siswa" autofocus>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <?php if ($email !== '' && !$user && !$message): ?>
        <div class="alert alert-danger">❌ Email <b><?= htmlspecialchars($email) ?></b> tidak ditemukan.</div>
    <?php endif; ?>

    <?php if ($user):
        $resetable = array_filter($sessions, fn($s) => $s['can']);
        $active    = array_filter($sessions, fn($s) => empty($s['submitted_at']) && $s['status'] !== 'completed');
        $done      = array_filter($sessions, fn($s) => !empty($s['submitted_at']) && !$s['can'] && !$s['violation']);
    ?>

    <div class="student-bar">
        <span class="student-name"><?= htmlspecialchars($user['full_name']) ?></span>
        <span class="student-divider">|</span>
        <span class="student-email"><?= htmlspecialchars($email) ?></span>
    </div>

    <div class="stats-row">
        <div class="stat-box"><div class="stat-num ink"><?= count($sessions) ?></div><div class="stat-label">Total Sesi</div></div>
        <div class="stat-box"><div class="stat-num amber"><?= count($resetable) ?></div><div class="stat-label">Bisa Direset</div></div>
        <div class="stat-box"><div class="stat-num green"><?= count($active) ?></div><div class="stat-label">Sedang Aktif</div></div>
        <div class="stat-box"><div class="stat-num ink"><?= count($done) ?></div><div class="stat-label">Selesai Normal</div></div>
    </div>

    <?php if (count($resetable) > 0): ?>
        <div class="alert alert-warning">
            ⚠️ Ditemukan <b><?= count($resetable) ?> sesi</b> yang bisa direset (submit normal dalam <?= (int)$maxMinutes ?> menit terakhir, waktu ujian masih berlangsung).
        </div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
        <div class="card"><div class="card-body text-center text-muted py-4">Tidak ada sesi ujian ditemukan untuk siswa ini.</div></div>
    <?php else: ?>

    <div class="rs-table-wrap">
        <table class="rs-table">
            <thead>
                <tr>
                    <th class="pl">Ujian</th>
                    <th>Status</th>
                    <th>Jawaban</th>
                    <th>Waktu Submit</th>
                    <th>Sisa Waktu</th>
                    <th>Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $s):
                $sl  = $s['sl'];
                $pct = $s['total_questions'] > 0 ? round(($s['answered_count'] / $s['total_questions']) * 100) : 0;
            ?>
            <tr>
                <td class="pl" data-label="Ujian" style="max-width:200px">
                    <div class="fw-bold"><?= htmlspecialchars($s['title']) ?></div>
                    <?php if (!empty($s['end_time'])): ?>
                        <div class="time-hint">Berakhir: <?= date('d M, H:i', strtotime($s['end_time'])) ?></div>
                    <?php endif; ?>
                </td>

                <td data-label="Status">
                    <span class="rs-badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span>
                    <?php if (!$s['can'] && !empty($s['submitted_at']) && !$s['violation']): ?>
                        <div class="reason-tag">🚫 <?= htmlspecialchars($s['reason']) ?></div>
                    <?php elseif ($s['violation']): ?>
                        <div class="reason-tag">⚡ Gunakan Reset Suspend</div>
                    <?php endif; ?>
                </td>

                <td data-label="Jawaban">
                    <div class="fw-semibold"><?= (int)$s['answered_count'] ?> / <?= (int)$s['total_questions'] ?></div>
                    <div class="progress-wrap"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                    <div class="time-hint"><?= $pct ?>% terjawab</div>
                </td>

                <td data-label="Waktu Submit">
                    <?php if (!empty($s['submitted_at'])): ?>
                        <div class="fw-semibold"><?= date('H:i:s', strtotime($s['submitted_at'])) ?></div>
                        <div class="time-hint"><?= date('d M Y', strtotime($s['submitted_at'])) ?></div>
                        <div class="time-ago"><?= (int)$s['mins_ago'] ?> menit lalu</div>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>

                <td data-label="Sisa Waktu">
                    <?php if ($s['can']): ?>
                        <div class="rs-countdown" data-sid="<?= (int)$s['id'] ?>"><?= sprintf('%02d:%02d', floor($s['restored'] / 60), $s['restored'] % 60) ?></div>
                        <div class="time-hint">sisa jika reset sekarang</div>
                    <?php elseif (empty($s['submitted_at']) && $s['status'] !== 'completed'): ?>
                        <?php $rem = (int)$s['remaining_time']; ?>
                        <span class="fw-semibold" style="color:var(--green)"><?= sprintf('%02d:%02d', floor($rem / 60), $rem % 60) ?></span>
                        <div class="time-hint">⏱️ Aktif</div>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>

                <td data-label="Nilai">
                    <?php if (!empty($s['submitted_at']) && $s['can']): ?>
                        <span class="fw-bold" style="color:var(--amber)"><?= number_format((float)$s['score'], 0) ?></span>
                        <div class="time-hint" style="color:var(--amber)">akan dihapus</div>
                    <?php elseif (!empty($s['submitted_at'])): ?>
                        <span class="fw-bold" style="color:var(--green)"><?= number_format((float)$s['score'], 0) ?></span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>

                <td data-label="Aksi">
                    <?php if ($s['can']): ?>
                        <form method="POST" action="<?= config('app_url') ?>/admin/resetSubmit"
                              onsubmit="return confirmReset('<?= addslashes(htmlspecialchars($s['title'])) ?>', <?= (int)$s['answered_count'] ?>, <?= (int)$s['total_questions'] ?>, <?= (int)($s['mins_ago'] ?? 0) ?>)">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                            <input type="hidden" name="reset_session_id" value="<?= (int)$s['id'] ?>">
                            <button type="submit" class="btn btn-warning btn-sm text-white" data-reset-btn="<?= (int)$s['id'] ?>">
                                🔄 Batalkan Submit
                            </button>
                        </form>
                    <?php elseif ($s['violation']): ?>
                        <a href="<?= config('app_url') ?>/admin/suspendAkun?q=<?= urlencode($user['full_name']) ?>&user_id=<?= (int)$user['id'] ?>"
                           class="btn btn-outline-danger btn-sm">↗ Buka Reset Suspend</a>
                    <?php elseif (!empty($s['submitted_at'])): ?>
                        <span class="text-muted small">✓ Tidak bisa direset</span>
                    <?php else: ?>
                        <span class="small" style="color:var(--green)">🟢 Masih aktif</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
    <?php endif; ?>

    <div class="page-footer">
        🛡️ Tool ini hanya untuk Administrator &bull; Data siswa bersifat rahasia
    </div>
</div>

<?php if (!empty($timerData)): ?>
<script>
const TIMER_DATA   = <?= json_encode($timerData) ?>;
const PAGE_LOAD_TS = Math.floor(Date.now() / 1000);

function fmtTime(secs) {
    if (secs <= 0) return '00:00';
    return String(Math.floor(secs / 60)).padStart(2, '0') + ':' + String(secs % 60).padStart(2, '0');
}

function calcRemaining(d) {
    const now          = Math.floor(Date.now() / 1000);
    const totalElapsed = Math.max(0, d.serverNow - d.submittedTs) + (now - PAGE_LOAD_TS);
    let rem = Math.max(0, d.savedRemaining - totalElapsed);
    rem = Math.min(rem, Math.max(0, d.endTs - now), d.savedRemaining);
    return Math.max(0, rem);
}

function colorClass(secs) {
    if (secs <= 0)  return 'expired';
    if (secs < 120) return 'urgent';
    if (secs < 600) return 'caution';
    return 'fresh';
}

function tick() {
    document.querySelectorAll('.rs-countdown[data-sid]').forEach(el => {
        const data = TIMER_DATA[el.dataset.sid];
        if (!data) return;
        const rem = calcRemaining(data);
        el.textContent = fmtTime(rem);
        el.className = 'rs-countdown ' + colorClass(rem);
        if (rem <= 0) {
            const btn = document.querySelector(`[data-reset-btn="${el.dataset.sid}"]`);
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = '⛔ Waktu habis';
                btn.className = 'btn btn-secondary btn-sm';
            }
        }
    });
}
tick();
setInterval(tick, 1000);
</script>
<?php endif; ?>

<script>
function confirmReset(title, answered, total, minsAgo) {
    return confirm(
        `Batalkan submit ujian "${title}"?\n\n` +
        `📤 Disubmit ${minsAgo} menit lalu.\n` +
        `✅ ${answered}/${total} jawaban tetap tersimpan.\n` +
        `🗑️ Nilai akan dihapus dan dihitung ulang saat submit berikutnya.\n\n` +
        `Lanjutkan?`
    );
}
</script>

<script src="<?= asset_url('vendor/bootstrap/5.3.0/dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>