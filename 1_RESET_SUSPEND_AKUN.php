<?php
/**
 * Reset Suspend / Auto-Submit Ujian Siswa
 * - Jawaban siswa TETAP tersimpan
 * - Session TETAP sama (tidak dibuat ulang)
 * - Hanya status submitted/suspended yang direset → siswa lanjut dari soal yang sudah dijawab
 */

$config = require __DIR__ . '/config.php';
$pdo = new PDO(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
    $config['db_user'],
    $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$user     = null;
$sessions = [];
$message  = null;
$email    = trim($_POST['email'] ?? $_GET['email'] ?? '');

// ── Cari siswa ──────────────────────────────────────────────────────────────
if ($email) {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $sessions = fetchSessions($pdo, $user['id']);
    }
}

function fetchSessions(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT
            es.id,
            e.id   AS exam_id,
            e.title,
            e.start_time,
            e.end_time,
            es.status,
            es.submitted_at,
            es.score,
            es.remaining_time,
            es.violation_count,
            es.suspension_reason,
            es.suspended_at,
            (SELECT COUNT(*) FROM answers a    WHERE a.session_id  = es.id)        AS answered_count,
            (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = es.exam_id) AS total_questions
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.user_id = ?
        ORDER BY es.id DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Apakah session ini terkena auto-submit / suspend karena pelanggaran? ────
function isViolationSession(array $s): bool
{
    if ($s['status'] === 'suspended') {
        return true;
    }
    if (!empty($s['suspension_reason']) &&
        (stripos($s['suspension_reason'], 'pelanggaran') !== false ||
         stripos($s['suspension_reason'], 'auto')        !== false ||
         stripos($s['suspension_reason'], 'suspend')     !== false ||
         stripos($s['suspension_reason'], 'violation')   !== false)) {
        return true;
    }
    if ((int)$s['violation_count'] >= 1 &&
        !empty($s['submitted_at'])       &&
        $s['status'] !== 'completed') {
        return true;
    }
    return false;
}

// ── Hitung sisa waktu setelah reset ─────────────────────────────────────────
function calcFinalRemaining(array $s): int
{
    $now = time();
    $savedRemaining = (int)$s['remaining_time'];

    $referenceTime = null;
    if (!empty($s['suspended_at'])) {
        $referenceTime = strtotime($s['suspended_at']);
    } elseif (!empty($s['submitted_at']) && isViolationSession($s)) {
        $referenceTime = strtotime($s['submitted_at']);
    }

    if (!$referenceTime) {
        return max(60, $savedRemaining);
    }

    $elapsed      = max(0, $now - $referenceTime);
    $newRemaining = max(0, $savedRemaining - $elapsed);

    if ($newRemaining > $savedRemaining) {
        $newRemaining = $savedRemaining;
    }

    $examEnd      = strtotime($s['end_time']);
    $timeUntilEnd = max(0, $examEnd - $now);

    $finalRemaining = min($newRemaining, $timeUntilEnd, $savedRemaining);

    return max(60, $finalRemaining);
}

// ── Proses reset ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_session_id']) && $user) {
    $sid = (int) $_POST['reset_session_id'];

    $stmt = $pdo->prepare("
        SELECT es.*, e.title, e.start_time, e.end_time,
               (SELECT COUNT(*) FROM answers a WHERE a.session_id = es.id) AS answered_count
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.id = ? AND es.user_id = ?
    ");
    $stmt->execute([$sid, $user['id']]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        $message = ['type' => 'error', 'text' => '❌ Session tidak ditemukan.'];
    } elseif (!isViolationSession($target)) {
        $message = ['type' => 'error', 'text' => '❌ Session ini bukan auto-submit/suspend karena pelanggaran.'];
    } else {
        $timeUntilEnd = max(0, strtotime($target['end_time']) - time());

        if ($timeUntilEnd <= 0) {
            $message = ['type' => 'error', 'text' => '❌ Waktu ujian sudah berakhir. Tidak bisa dilanjutkan.'];
        } else {
            $finalRemaining = calcFinalRemaining($target);

            $pdo->prepare("
                UPDATE exam_sessions
                SET status            = 'active',
                    token_status      = 'active',
                    submitted_at      = NULL,
                    score             = NULL,
                    is_best_score     = 0,
                    violation_count   = 0,
                    suspension_reason = NULL,
                    suspended_at      = NULL,
                    remaining_time    = ?
                WHERE id = ? AND user_id = ?
            ")->execute([$finalRemaining, $sid, $user['id']]);

            $pdo->prepare("DELETE FROM exam_violations WHERE session_id = ?")
                ->execute([$sid]);

            $examTitle = htmlspecialchars($target['title'] ?? '');
            $menit     = floor($finalRemaining / 60);
            $detik     = $finalRemaining % 60;

            $message = [
                'type' => 'success',
                'text' => "✅ Session ujian <b>\"$examTitle\"</b> berhasil direset! "
                        . "Jawaban siswa tetap tersimpan. "
                        . "Sisa waktu disesuaikan: <b>{$menit} menit {$detik} detik</b>. "
                        . "Minta siswa kembali ke halaman Daftar Ujian dan klik <b>\"Mulai Ujian\"</b> untuk melanjutkan."
            ];

            $sessions = fetchSessions($pdo, $user['id']);
        }
    }
}

// ── Status label ─────────────────────────────────────────────────────────────
function statusLabel(array $s): array
{
    if (isViolationSession($s)) {
        return ['label' => '🚫 Auto-Submit (Pelanggaran)', 'class' => 'badge-suspended'];
    }
    if ($s['submitted_at']) {
        return ['label' => '✅ Submitted', 'class' => 'badge-submitted'];
    }
    return match ($s['status']) {
        'active', 'in_progress' => ['label' => '🟢 Aktif',    'class' => 'badge-active'],
        'completed'             => ['label' => '✅ Selesai',   'class' => 'badge-submitted'],
        'suspended'             => ['label' => '🚫 Suspended', 'class' => 'badge-suspended'],
        default                 => ['label' => ucfirst($s['status']), 'class' => 'badge-other'],
    };
}

// ── Siapkan data timer untuk JavaScript ──────────────────────────────────────
// Setiap session yang perlu countdown dikirim ke JS sebagai:
// { id, endTimestamp, referenceTimestamp, savedRemaining, type }
//   type: 'active'    → timer berjalan dari sekarang (siswa sedang ujian)
//   type: 'suspended' → timer berjalan dari referenceTime (waktu suspend/submit)
$timerData = [];
$serverNow = time(); // timestamp server saat halaman dimuat

foreach ($sessions as $s) {
    $canReset     = isViolationSession($s);
    $examEnd      = strtotime($s['end_time']);
    $savedRem     = (int)$s['remaining_time'];

    // Tentukan referenceTime (kapan timer "berhenti" di server)
    $referenceTime = null;
    if (!empty($s['suspended_at'])) {
        $referenceTime = strtotime($s['suspended_at']);
    } elseif (!empty($s['submitted_at']) && $canReset) {
        $referenceTime = strtotime($s['submitted_at']);
    }

    if ($canReset) {
        // Session suspended/auto-submit:
        // Sisa waktu = savedRemaining - (sekarang - referenceTime)
        // Timer terus berkurang di JS
        $timerData[$s['id']] = [
            'type'          => 'suspended',
            'savedRemaining'=> $savedRem,
            'referenceTs'   => $referenceTime,   // bisa null
            'endTs'         => $examEnd,
            'serverNow'     => $serverNow,
        ];
    } elseif (
        !$s['submitted_at'] &&
        $s['status'] !== 'completed' &&
        in_array($s['status'], ['active', 'in_progress', 'suspended'])
    ) {
        // Session aktif: timer berjalan normal dari remaining_time
        // remaining_time di DB berkurang di sisi siswa; di sini kita tampilkan
        // sisa waktu terhadap end_time sebagai proxy realtime
        $timerData[$s['id']] = [
            'type'          => 'active',
            'savedRemaining'=> $savedRem,
            'referenceTs'   => $serverNow, // mulai hitung dari sekarang
            'endTs'         => $examEnd,
            'serverNow'     => $serverNow,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Suspend / Auto-Submit Ujian</title>
<style>
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; padding: 16px; color: #1e293b; }
    .container { max-width: 960px; margin: 0 auto; padding-top: 24px; }

    .btn-back {
        display: inline-flex; align-items: center; gap: 6px;
        margin-bottom: 20px; padding: 8px 16px;
        background: white; color: #374151;
        border: 1px solid #e5e7eb; border-radius: 8px;
        font-size: .875rem; font-weight: 600; text-decoration: none;
        box-shadow: 0 1px 3px rgba(0,0,0,.07); transition: all .2s;
    }
    .btn-back:hover { background: #f8fafc; border-color: #2563eb; color: #2563eb; text-decoration: none; }

    .page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
    .page-header .icon {
        flex-shrink: 0; width: 44px; height: 44px;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px;
    }
    h2 { margin: 0; font-size: 1.25rem; line-height: 1.3; }
    .subtitle { color: #64748b; margin: 4px 0 24px; font-size: .85rem; padding-left: 56px; }

    .card { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 16px; }

    .form-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 200px; }
    label { display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: .875rem; }
    input[type=text] { width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 1rem; transition: border-color .2s; }
    input[type=text]:focus { outline: none; border-color: #6366f1; }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: .9rem; font-weight: 600; cursor: pointer; transition: opacity .2s; white-space: nowrap; }
    .btn:hover { opacity: .85; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-warning { background: #f59e0b; color: white; }
    .btn-sm { padding: 7px 14px; font-size: .82rem; }
    .btn-block { width: 100%; }

    .alert { padding: 13px 16px; border-radius: 10px; margin-bottom: 16px; font-weight: 500; font-size: .9rem; line-height: 1.5; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

    .student-info {
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
        padding: 11px 16px; margin-bottom: 14px; display: flex; align-items: center;
        gap: 8px; color: #1e40af; font-weight: 600; flex-wrap: wrap; font-size: .9rem;
    }

    .note { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 13px 16px; color: #1e40af; font-size: .85rem; margin-bottom: 16px; }
    .note strong { display: block; margin-bottom: 4px; color: #1e3a8a; }
    .note ul { margin: 4px 0 0 16px; padding: 0; }
    .note li { margin-bottom: 3px; }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
    .badge-active    { background: #d1fae5; color: #065f46; }
    .badge-submitted { background: #fef3c7; color: #92400e; }
    .badge-suspended { background: #fee2e2; color: #991b1b; }
    .badge-other     { background: #e2e8f0; color: #475569; }

    .violation-box {
        background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px;
        padding: 4px 8px; margin-top: 5px; font-size: .74rem; color: #92400e;
    }

    .progress-wrap { background: #e2e8f0; border-radius: 4px; height: 5px; width: 100%; max-width: 90px; overflow: hidden; margin-top: 4px; }
    .progress-fill { background: #6366f1; height: 100%; }

    /* ── Realtime timer ── */
    .timer-realtime {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        letter-spacing: .03em;
        transition: color .3s;
    }
    .timer-realtime.urgent   { color: #ef4444; animation: pulse-red 1s ease-in-out infinite; }
    .timer-realtime.warning  { color: #f59e0b; }
    .timer-realtime.ok       { color: #059669; }
    .timer-realtime.active   { color: #3b82f6; }
    .timer-realtime.expired  { color: #94a3b8; }

    @keyframes pulse-red {
        0%, 100% { opacity: 1; }
        50%       { opacity: .5; }
    }

    /* ══ DESKTOP TABLE ══ */
    .table-wrap { border-radius: 14px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    .desktop-table { width: 100%; border-collapse: collapse; background: white; }
    .desktop-table th, .desktop-table td { padding: 11px 13px; text-align: left; font-size: .85rem; }
    .desktop-table th { background: #f8fafc; font-weight: 700; color: #475569; border-bottom: 2px solid #e2e8f0; }
    .desktop-table tr:not(:last-child) td { border-bottom: 1px solid #f1f5f9; }
    .desktop-table tr:hover td { background: #fafafa; }

    /* ══ MOBILE CARDS ══ */
    .mobile-cards { display: none; }
    .session-card {
        background: white; border-radius: 14px; padding: 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 12px;
    }
    .session-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 12px; }
    .session-card-title { font-weight: 700; font-size: .95rem; color: #1e293b; flex: 1; }
    .session-card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
    .session-card-item label { font-size: .72rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px; }
    .session-card-item .val { font-size: .88rem; font-weight: 600; color: #1e293b; }
    .session-card-footer { border-top: 1px solid #f1f5f9; padding-top: 12px; }

    @media (max-width: 700px) {
        body { padding: 10px; }
        .container { padding-top: 12px; }
        h2 { font-size: 1.1rem; }
        .subtitle { padding-left: 0; margin-top: 8px; }
        .page-header { flex-wrap: wrap; }
        .form-row { flex-direction: column; }
        .form-row .btn { width: 100%; }
        .table-wrap { display: none; }
        .mobile-cards { display: block; }
    }
    @media (max-width: 480px) {
        .session-card-grid { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>
<div class="container">

    <a href="/exam" class="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Ujian
    </a>

    <div class="page-header">
        <div class="icon">🔓</div>
        <div>
            <h2>Reset Ujian — Auto-Submit &amp; Suspend</h2>
        </div>
    </div>
    <p class="subtitle">
        Untuk siswa yang ter-<em>auto submit</em> / ter-<em>suspend</em> karena pelanggaran.
        Jawaban tetap tersimpan, siswa melanjutkan dari soal yang belum dijawab.
    </p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['type'] ?>"><?= $message['text'] ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>📧 Email / Username Siswa</label>
                    <input type="text" name="email"
                           value="<?= htmlspecialchars($email) ?>"
                           placeholder="contoh: siswa@gmail.com" autofocus>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Cari Siswa</button>
            </div>
        </form>
    </div>

    <?php if ($email && !$user): ?>
        <div class="alert alert-error">❌ Email <b><?= htmlspecialchars($email) ?></b> tidak ditemukan.</div>
    <?php endif; ?>

    <?php if ($user): ?>

        <div class="student-info">
            👤 <span><?= htmlspecialchars($user['full_name']) ?></span>
            <span style="color:#93c5fd">|</span>
            <span style="font-weight:400; color:#3b82f6"><?= htmlspecialchars($email) ?></span>
        </div>

        <div class="note">
            <strong>ℹ️ Yang terjadi saat klik "Lanjutkan Ujian":</strong>
            <ul>
                <li>Status ujian dikembalikan ke <b>Aktif</b>.</li>
                <li><b>Jawaban yang sudah diisi tetap tersimpan</b> — siswa tidak mengulang dari awal.</li>
                <li>Hitungan pelanggaran direset ke 0.</li>
                <li><b>Sisa waktu dihitung ulang</b>: waktu tersimpan saat suspend dikurangi waktu yang berlalu, tidak boleh melebihi batas akhir ujian.</li>
                <li>Siswa cukup klik <b>"Mulai Ujian"</b> di halaman Daftar Ujian untuk melanjutkan.</li>
            </ul>
        </div>

        <?php if (empty($sessions)): ?>
            <div class="card"><p style="color:#64748b; text-align:center; padding:20px 0">Tidak ada session ujian ditemukan.</p></div>
        <?php else: ?>

        <!-- ══ DESKTOP TABLE ══ -->
        <div class="table-wrap">
            <table class="desktop-table">
                <thead>
                    <tr>
                        <th style="padding-left:20px">Ujian</th>
                        <th>Status</th>
                        <th>Jawaban Tersimpan</th>
                        <th>Sisa Waktu (Live)</th>
                        <th>Nilai</th>
                        <th style="padding-right:20px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sessions as $s):
                    $sl             = statusLabel($s);
                    $canReset       = isViolationSession($s);
                    $minutes        = floor($s['remaining_time'] / 60);
                    $seconds        = $s['remaining_time'] % 60;
                    $pct            = $s['total_questions'] > 0
                                        ? round(($s['answered_count'] / $s['total_questions']) * 100) : 0;
                    $timeUntilEnd   = max(0, strtotime($s['end_time']) - time());
                    $examExpired    = $timeUntilEnd <= 0;
                    $finalRemaining = calcFinalRemaining($s);
                    $resetMinutes   = floor($finalRemaining / 60);
                    $resetSeconds   = $finalRemaining % 60;
                ?>
                <tr>
                    <td style="padding-left:20px; max-width:200px">
                        <div style="font-weight:600"><?= htmlspecialchars($s['title']) ?></div>
                        <?php if ($s['violation_count']): ?>
                            <div style="font-size:.74rem; color:#ef4444; margin-top:3px">⚡ <?= (int)$s['violation_count'] ?> pelanggaran</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span>
                        <?php if (!empty($s['suspension_reason'])): ?>
                            <div class="violation-box">📋 <?= htmlspecialchars($s['suspension_reason']) ?>
                                <?php if ($s['submitted_at']): ?><br>🕐 <?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?><?php endif; ?>
                            </div>
                        <?php elseif ($s['submitted_at'] && $canReset): ?>
                            <div class="violation-box">🕐 Auto-submit: <?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size:.88rem; font-weight:600; color:#374151"><?= (int)$s['answered_count'] ?> / <?= (int)$s['total_questions'] ?></div>
                        <div class="progress-wrap"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                        <div style="font-size:.72rem; color:#94a3b8; margin-top:2px"><?= $pct ?>%</div>
                    </td>
                    <td>
                        <?php if ($canReset && !$examExpired): ?>
                            <!-- Timer suspended: terus berkurang realtime -->
                            <div style="font-size:.72rem; color:#94a3b8; text-decoration:line-through; margin-bottom:2px">
                                Tersimpan: <?= sprintf('%02d:%02d', $minutes, $seconds) ?>
                            </div>
                            <span class="timer-realtime" data-session="<?= $s['id'] ?>">
                                <?= sprintf('%02d:%02d', $resetMinutes, $resetSeconds) ?>
                            </span>
                            <div style="font-size:.7rem; color:#6b7280; margin-top:2px">sisa jika reset sekarang</div>
                        <?php elseif ($canReset && $examExpired): ?>
                            <span style="color:#ef4444; font-size:.82rem; font-weight:600">⛔ Berakhir</span>
                        <?php elseif (!$s['submitted_at'] && $s['status'] !== 'completed'): ?>
                            <!-- Timer aktif: tampilkan countdown realtime -->
                            <span class="timer-realtime" data-session="<?= $s['id'] ?>">
                                <?= sprintf('%02d:%02d', $minutes, $seconds) ?>
                            </span>
                            <div style="font-size:.7rem; color:#6b7280; margin-top:2px">⏱️ Live</div>
                        <?php else: ?>
                            <span style="color:#94a3b8">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['submitted_at'] && $canReset): ?>
                            <span style="font-size:.84rem; color:#94a3b8; text-decoration:line-through"><?= number_format((float)$s['score'], 0) ?></span>
                            <div style="font-size:.72rem; color:#94a3b8">akan direset</div>
                        <?php elseif ($s['submitted_at']): ?>
                            <span style="font-weight:700; color:#059669"><?= number_format((float)$s['score'], 0) ?></span>
                        <?php else: ?>
                            <span style="color:#94a3b8">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding-right:20px">
                        <?php if ($canReset && !$examExpired): ?>
                            <form method="POST" onsubmit="return confirmReset(this, '<?= addslashes(htmlspecialchars($s['title'])) ?>', <?= (int)$s['answered_count'] ?>, <?= $s['id'] ?>)">
                                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                                <input type="hidden" name="reset_session_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-warning btn-sm">▶️ Lanjutkan Ujian</button>
                            </form>
                        <?php elseif ($canReset && $examExpired): ?>
                            <span style="color:#ef4444; font-size:.82rem; font-weight:600">⛔ Waktu habis</span>
                        <?php elseif ($s['submitted_at']): ?>
                            <span style="color:#94a3b8; font-size:.82rem">✅ Submit normal</span>
                        <?php else: ?>
                            <span style="color:#10b981; font-size:.82rem">🟢 Sedang aktif</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- /table-wrap -->

        <!-- ══ MOBILE CARDS ══ -->
        <div class="mobile-cards">
        <?php foreach ($sessions as $s):
            $sl             = statusLabel($s);
            $canReset       = isViolationSession($s);
            $minutes        = floor($s['remaining_time'] / 60);
            $seconds        = $s['remaining_time'] % 60;
            $pct            = $s['total_questions'] > 0 ? round(($s['answered_count'] / $s['total_questions']) * 100) : 0;
            $timeUntilEnd   = max(0, strtotime($s['end_time']) - time());
            $expired        = $timeUntilEnd <= 0;
            $finalRemaining = calcFinalRemaining($s);
            $resetMin       = floor($finalRemaining / 60);
            $resetSec       = $finalRemaining % 60;
        ?>
        <div class="session-card">
            <div class="session-card-header">
                <div class="session-card-title"><?= htmlspecialchars($s['title']) ?></div>
                <span class="badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span>
            </div>
            <?php if (!empty($s['suspension_reason']) || ($s['submitted_at'] && $canReset)): ?>
            <div class="violation-box" style="margin-bottom:10px">
                <?php if (!empty($s['suspension_reason'])): ?>
                    📋 <?= htmlspecialchars($s['suspension_reason']) ?>
                    <?php if ($s['submitted_at']): ?><br>🕐 <?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?><?php endif; ?>
                <?php else: ?>
                    🕐 Auto-submit: <?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="session-card-grid">
                <div class="session-card-item">
                    <label>Jawaban</label>
                    <div class="val"><?= (int)$s['answered_count'] ?> / <?= (int)$s['total_questions'] ?> soal</div>
                    <div class="progress-wrap" style="max-width:100%"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:2px"><?= $pct ?>% terjawab</div>
                </div>
                <div class="session-card-item">
                    <label>Sisa Waktu (Live)</label>
                    <?php if ($canReset && !$expired): ?>
                        <div style="font-size:.72rem;color:#94a3b8;text-decoration:line-through"><?= sprintf('%02d:%02d', $minutes, $seconds) ?></div>
                        <span class="timer-realtime val" data-session="<?= $s['id'] ?>">
                            <?= sprintf('%02d:%02d', $resetMin, $resetSec) ?>
                        </span>
                        <div style="font-size:.7rem;color:#6b7280">sisa jika reset sekarang</div>
                    <?php elseif ($canReset && $expired): ?>
                        <div class="val" style="color:#ef4444">⛔ Berakhir</div>
                    <?php elseif (!$s['submitted_at'] && $s['status'] !== 'completed'): ?>
                        <span class="timer-realtime val" data-session="<?= $s['id'] ?>">
                            <?= sprintf('%02d:%02d', $minutes, $seconds) ?>
                        </span>
                        <div style="font-size:.7rem;color:#6b7280">⏱️ Live</div>
                    <?php else: ?>
                        <div class="val" style="color:#94a3b8">—</div>
                    <?php endif; ?>
                </div>
                <?php if ($s['violation_count']): ?>
                <div class="session-card-item">
                    <label>Pelanggaran</label>
                    <div class="val" style="color:#ef4444">⚡ <?= (int)$s['violation_count'] ?>x</div>
                </div>
                <?php endif; ?>
                <div class="session-card-item">
                    <label>Nilai</label>
                    <?php if ($s['submitted_at'] && $canReset): ?>
                        <div class="val" style="color:#94a3b8;text-decoration:line-through"><?= number_format((float)$s['score'], 0) ?></div>
                        <div style="font-size:.72rem;color:#94a3b8">akan direset</div>
                    <?php elseif ($s['submitted_at']): ?>
                        <div class="val" style="color:#059669"><?= number_format((float)$s['score'], 0) ?></div>
                    <?php else: ?>
                        <div class="val" style="color:#94a3b8">—</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="session-card-footer">
                <?php if ($canReset && !$expired): ?>
                    <form method="POST" onsubmit="return confirmReset(this, '<?= addslashes(htmlspecialchars($s['title'])) ?>', <?= (int)$s['answered_count'] ?>, <?= $s['id'] ?>)">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                        <input type="hidden" name="reset_session_id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-warning btn-block">▶️ Lanjutkan Ujian</button>
                    </form>
                <?php elseif ($canReset && $expired): ?>
                    <div style="text-align:center;color:#ef4444;font-weight:600;font-size:.88rem">⛔ Waktu ujian sudah habis</div>
                <?php elseif ($s['submitted_at']): ?>
                    <div style="text-align:center;color:#94a3b8;font-size:.85rem">✅ Submit normal</div>
                <?php else: ?>
                    <div style="text-align:center;color:#10b981;font-size:.85rem">🟢 Ujian sedang aktif</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div><!-- /mobile-cards -->

        <?php endif; ?>

    <?php endif; ?>

    <div style="margin-top:30px; text-align:center; color:#94a3b8; font-size:.8rem; padding-bottom:20px">
        Tool ini hanya untuk Administrator &bull; Jangan bagikan URL ini ke siswa
    </div>
</div>

<!-- ══ REALTIME TIMER SCRIPT ══ -->
<?php if (!empty($timerData)): ?>
<script>
/**
 * timerData: data dari PHP untuk setiap session yang perlu countdown.
 *
 * Untuk session 'suspended':
 *   remaining = savedRemaining - (now - referenceTs) - (pageLoadTime - serverNow)
 *   → timer terus berkurang meski halaman tidak di-reload
 *
 * Untuk session 'active':
 *   remaining = savedRemaining - (now - serverNow)
 *   → approx; actual remaining ada di sisi siswa, ini hanya tampilan admin
 */
const TIMER_DATA  = <?= json_encode($timerData) ?>;
const PAGE_LOAD_TS = Math.floor(Date.now() / 1000); // Unix timestamp saat halaman dimuat di browser

function fmt(secs) {
    if (secs <= 0) return '00:00';
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
}

function calcRemaining(data) {
    const nowBrowser = Math.floor(Date.now() / 1000);
    const saved      = data.savedRemaining;
    const serverNow  = data.serverNow;
    const endTs      = data.endTs;

    // Berapa lama browser sudah terbuka sejak halaman dimuat
    const browserElapsed = nowBrowser - PAGE_LOAD_TS;

    let remaining;

    if (data.type === 'suspended') {
        if (data.referenceTs) {
            // Waktu yang sudah berlalu sejak suspend/auto-submit (termasuk sebelum & setelah halaman dimuat)
            // = (serverNow - referenceTs) sudah berlalu sebelum halaman dimuat
            // + browserElapsed sudah berlalu sejak halaman dimuat
            const elapsedBeforeLoad = Math.max(0, serverNow - data.referenceTs);
            const totalElapsed      = elapsedBeforeLoad + browserElapsed;
            remaining = Math.max(0, saved - totalElapsed);
        } else {
            // Tidak ada referenceTs → kurangi hanya sejak halaman dimuat
            remaining = Math.max(0, saved - browserElapsed);
        }
        // Hard cap: tidak boleh melebihi sisa waktu ujian
        const timeUntilEnd = Math.max(0, endTs - nowBrowser);
        remaining = Math.min(remaining, timeUntilEnd, saved);
    } else {
        // Active session: kurangi dari sisa waktu tersimpan sejak server render
        remaining = Math.max(0, saved - browserElapsed);
        const timeUntilEnd = Math.max(0, endTs - nowBrowser);
        remaining = Math.min(remaining, timeUntilEnd);
    }

    return Math.max(0, remaining);
}

function colorClass(secs, type) {
    if (secs <= 0)    return 'expired';
    if (type === 'active') return 'active';   // biru untuk session aktif
    if (secs < 60)    return 'urgent';        // merah < 1 menit
    if (secs < 300)   return 'warning';       // kuning < 5 menit
    return 'ok';                              // hijau
}

function updateAllTimers() {
    const elements = document.querySelectorAll('.timer-realtime[data-session]');
    elements.forEach(el => {
        const sid  = el.dataset.session;
        const data = TIMER_DATA[sid];
        if (!data) return;

        const rem = calcRemaining(data);
        el.textContent = fmt(rem);

        // Update warna
        el.classList.remove('expired', 'urgent', 'warning', 'ok', 'active');
        el.classList.add(colorClass(rem, data.type));

        // Jika waktu habis → disable tombol reset terkait
        if (rem <= 0) {
            const form = document.querySelector(`form input[name="reset_session_id"][value="${sid}"]`);
            if (form) {
                const btn = form.closest('form').querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    btn.textContent = '⛔ Waktu habis';
                    btn.style.opacity = '0.5';
                    btn.style.cursor  = 'not-allowed';
                }
            }
        }
    });
}

// Jalankan langsung dan setiap detik
updateAllTimers();
setInterval(updateAllTimers, 1000);
</script>
<?php endif; ?>

</body>
</html>