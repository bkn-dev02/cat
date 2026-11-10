<?php
/**
 * Admin Tool — Reset Submit Tidak Sengaja
 * Untuk siswa yang TIDAK SENGAJA mengklik tombol "Kumpulkan Ujian"
 * sebelum menyelesaikan semua soal.
 *
 * Yang dilakukan:
 *  - Status dikembalikan ke 'active'
 *  - submitted_at di-NULL-kan
 *  - score di-NULL-kan (akan dihitung ulang saat submit benar)
 *  - remaining_time dikembalikan: diambil dari waktu tersimpan sebelum submit,
 *    dikurangi selisih waktu sejak submitted_at, tidak melebihi sisa end_time
 *  - Hanya berlaku jika:
 *      a) submitted_at ada (sudah submit)
 *      b) status = 'completed' atau 'submitted' (submit normal, BUKAN karena pelanggaran)
 *      c) Waktu ujian (end_time) belum habis
 *      d) Belum terlalu lama (< MAX_RESET_MINUTES menit sejak submit)
 *
 * KEAMANAN:
 *  - Tidak bisa reset session yang submit karena pelanggaran / suspended
 *  - Tidak bisa reset jika waktu ujian sudah berakhir
 *  - Tidak bisa reset jika sudah > MAX_RESET_MINUTES menit sejak submit
 *  - Semua aksi dicatat di error_log
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ─── Konfigurasi ────────────────────────────────────────────────────────────
const MAX_RESET_MINUTES = 30; // Batas waktu maksimal reset setelah submit (menit)

// ── Koneksi DB ───────────────────────────────────────────────────────────────
try {
    $config = require __DIR__ . '/config.php';
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 10,
        ]
    );
} catch (Exception $e) {
    error_log('[reset_accidental] DB connect error: ' . $e->getMessage());
    http_response_code(503);
    die('<h2>Koneksi database gagal. Cek konfigurasi server.</h2>');
}

$user     = null;
$sessions = [];
$message  = null;
$email    = trim($_POST['email'] ?? $_GET['email'] ?? '');

// ── Fetch user ───────────────────────────────────────────────────────────────
if ($email) {
    try {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $sessions = fetchSessions($pdo, $user['id']);
        }
    } catch (Exception $e) {
        error_log('[reset_accidental] fetchUser error: ' . $e->getMessage());
        $message = ['type' => 'error', 'text' => '❌ Terjadi kesalahan database saat mencari user. Cek error log.'];
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  FUNGSI UTILITAS
// ════════════════════════════════════════════════════════════════════════════

function fetchSessions(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT
            es.id,
            e.id   AS exam_id,
            e.title,
            e.start_time,
            e.end_time,
            e.duration,
            es.status,
            es.submitted_at,
            es.score,
            es.remaining_time,
            es.violation_count,
            es.suspension_reason,
            es.suspended_at,
            (SELECT COUNT(*) FROM answers a       WHERE a.session_id  = es.id)      AS answered_count,
            (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id  = es.exam_id) AS total_questions
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.user_id = ?
        ORDER BY es.id DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Apakah session ini submit karena pelanggaran / auto-suspend?
 * Jika ya → TIDAK BOLEH direset oleh tool ini (gunakan reset_suspend.php)
 */
function isViolationSession(array $s): bool
{
    if ($s['status'] === 'suspended') return true;
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

/**
 * Berapa menit yang sudah berlalu sejak submit?
 */
function minutesSinceSubmit(array $s): ?float
{
    if (empty($s['submitted_at'])) return null;
    return (time() - strtotime($s['submitted_at'])) / 60;
}

/**
 * Apakah session ini bisa di-reset?
 * Return array ['can' => bool, 'reason' => string]
 */
function canResetSession(array $s): array
{
    // Belum pernah submit
    if (empty($s['submitted_at'])) {
        return ['can' => false, 'reason' => 'Ujian belum disubmit.'];
    }

    // Submit karena pelanggaran → gunakan tool lain
    if (isViolationSession($s)) {
        return ['can' => false, 'reason' => 'Submit karena pelanggaran. Gunakan tool Reset Suspend.'];
    }

    // Waktu ujian sudah berakhir
    if ($s['end_time'] && strtotime($s['end_time']) < time()) {
        return ['can' => false, 'reason' => 'Waktu ujian sudah berakhir.'];
    }

    // Sudah terlalu lama sejak submit
    $minsAgo = minutesSinceSubmit($s);
    if ($minsAgo !== null && $minsAgo > MAX_RESET_MINUTES) {
        $minsAgoRound = round($minsAgo);
        return ['can' => false, 'reason' => "Sudah {$minsAgoRound} menit sejak submit (batas: " . MAX_RESET_MINUTES . " menit)."];
    }

    return ['can' => true, 'reason' => ''];
}

/**
 * Hitung sisa waktu setelah reset:
 * Ambil remaining_time tersimpan, kurangi waktu yang berlalu sejak submitted_at,
 * tidak melebihi sisa end_time.
 */
function calcRestoredRemaining(array $s): int
{
    $savedRemaining = (int)$s['remaining_time'];
    $now            = time();

    // Berapa lama waktu berjalan sejak siswa menekan submit
    $elapsedSinceSubmit = 0;
    if (!empty($s['submitted_at'])) {
        $elapsedSinceSubmit = max(0, $now - strtotime($s['submitted_at']));
    }

    $restoredRemaining = max(0, $savedRemaining - $elapsedSinceSubmit);

    // Hard cap: tidak boleh melebihi sisa end_time
    if (!empty($s['end_time'])) {
        $timeUntilEnd      = max(0, strtotime($s['end_time']) - $now);
        $restoredRemaining = min($restoredRemaining, $timeUntilEnd);
    }

    // Minimal 60 detik agar tidak langsung timeout saat dibuka
    return max(60, $restoredRemaining);
}

// ════════════════════════════════════════════════════════════════════════════
//  PROSES POST
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_session_id']) && $user) {

    $sid = (int) $_POST['reset_session_id'];

    try {
        $stmt = $pdo->prepare("
            SELECT es.*, e.title, e.start_time, e.end_time, e.duration,
                   (SELECT COUNT(*) FROM answers a WHERE a.session_id = es.id) AS answered_count,
                   (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = es.exam_id) AS total_questions
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.id = ? AND es.user_id = ?
        ");
        $stmt->execute([$sid, $user['id']]);
        $target = $stmt->fetch();

        if (!$target) {
            $message = ['type' => 'error', 'text' => '❌ Session tidak ditemukan.'];
        } else {
            $check = canResetSession($target);

            if (!$check['can']) {
                $message = ['type' => 'error', 'text' => '❌ Tidak dapat direset: ' . $check['reason']];
            } else {
                $restoredRemaining = calcRestoredRemaining($target);
                $minsAgo           = round(minutesSinceSubmit($target));

                // Catat sebelum reset (untuk audit log)
                error_log(sprintf(
                    '[reset_accidental] RESET: user=%s session=%d exam="%s" submitted_at=%s score=%s minsAgo=%d restoredRemaining=%d',
                    $email,
                    $sid,
                    $target['title'],
                    $target['submitted_at'],
                    $target['score'],
                    $minsAgo,
                    $restoredRemaining
                ));

                // Lakukan reset
                $pdo->prepare("
                    UPDATE exam_sessions
                    SET status          = 'active',
                        token_status    = 'active',
                        submitted_at    = NULL,
                        score           = NULL,
                        is_best_score   = 0,
                        remaining_time  = ?
                    WHERE id = ? AND user_id = ?
                ")->execute([$restoredRemaining, $sid, $user['id']]);

                $examTitle = htmlspecialchars($target['title'] ?? '');
                $menit     = floor($restoredRemaining / 60);
                $detik     = $restoredRemaining % 60;

                $message = [
                    'type' => 'success',
                    'text' => "✅ Ujian <b>\"$examTitle\"</b> berhasil direset! "
                            . "Submit {$minsAgo} menit lalu berhasil dibatalkan. "
                            . "Jawaban yang sudah diisi <b>tetap tersimpan</b>. "
                            . "Sisa waktu: <b>{$menit} menit {$detik} detik</b>. "
                            . "Minta siswa buka halaman Daftar Ujian dan klik <b>\"Lanjutkan Ujian\"</b>."
                ];

                $sessions = fetchSessions($pdo, $user['id']);
            }
        }

    } catch (Exception $e) {
        error_log('[reset_accidental] POST error: ' . $e->getMessage() . ' | session=' . $sid);
        $message = [
            'type' => 'error',
            'text' => '❌ Terjadi error: <code>' . htmlspecialchars($e->getMessage()) . '</code>'
        ];
    }
}

// ── Status label ─────────────────────────────────────────────────────────────
function statusLabel(array $s): array
{
    if (isViolationSession($s)) return ['label' => '🚫 Auto-Submit (Pelanggaran)', 'class' => 'badge-suspended'];
    if (!empty($s['submitted_at'])) {
        $check = canResetSession($s);
        return $check['can']
            ? ['label' => '📤 Submitted (Bisa Reset)', 'class' => 'badge-submitted-reset']
            : ['label' => '✅ Submitted',              'class' => 'badge-submitted'];
    }
    return match ($s['status']) {
        'active', 'in_progress' => ['label' => '🟢 Aktif',    'class' => 'badge-active'],
        'completed'             => ['label' => '✅ Selesai',   'class' => 'badge-submitted'],
        'suspended'             => ['label' => '🚫 Suspended', 'class' => 'badge-suspended'],
        default                 => ['label' => ucfirst($s['status']), 'class' => 'badge-other'],
    };
}

// ── Data timer untuk JS ───────────────────────────────────────────────────────
$timerData = [];
$serverNow = time();
foreach ($sessions as $s) {
    $check = canResetSession($s);
    if ($check['can']) {
        $timerData[$s['id']] = [
            'submittedTs'    => strtotime($s['submitted_at']),
            'savedRemaining' => (int)$s['remaining_time'],
            'endTs'          => strtotime($s['end_time']),
            'serverNow'      => $serverNow,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Reset Submit Tidak Sengaja</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── Reset & base ─────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:        #f8f5f0;
    --surface:   #ffffff;
    --surface-2: #fdf8f2;
    --border:    #e8e0d5;
    --border-2:  #d4c9b8;

    --ink:       #1a1208;
    --ink-2:     #4a3f30;
    --ink-3:     #7a6d5e;
    --ink-4:     #a89e92;

    --amber:     #d97706;
    --amber-bg:  #fef3c7;
    --amber-bdr: #fcd34d;

    --red:       #dc2626;
    --red-bg:    #fef2f2;
    --red-bdr:   #fca5a5;

    --green:     #059669;
    --green-bg:  #ecfdf5;
    --green-bdr: #6ee7b7;

    --blue:      #2563eb;
    --blue-bg:   #eff6ff;
    --blue-bdr:  #bfdbfe;

    --accent:    #b45309;   /* burnt amber */
    --radius:    12px;
    --radius-sm: 8px;
    --shadow:    0 2px 8px rgba(26,18,8,.08), 0 0 0 1px rgba(26,18,8,.04);
    --shadow-lg: 0 8px 32px rgba(26,18,8,.12), 0 0 0 1px rgba(26,18,8,.04);
}

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    padding: 0 0 60px;
    line-height: 1.6;
}

/* ── Header strip ─────────────────────────────────────────────────────── */
.top-strip {
    background: var(--ink);
    color: #e8e0d5;
    padding: 10px 24px;
    font-size: .78rem;
    font-weight: 500;
    letter-spacing: .04em;
    display: flex;
    align-items: center;
    gap: 8px;
}
.top-strip .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--amber); display: inline-block; }

/* ── Container ────────────────────────────────────────────────────────── */
.container { max-width: 1040px; margin: 0 auto; padding: 36px 24px 0; }

/* ── Page title ───────────────────────────────────────────────────────── */
.page-title-block { margin-bottom: 32px; }
.page-eyebrow {
    font-size: .75rem; font-weight: 600; letter-spacing: .1em;
    text-transform: uppercase; color: var(--accent); margin-bottom: 6px;
}
.page-title {
    font-family: 'DM Serif Display', serif;
    font-size: 2rem; line-height: 1.2; color: var(--ink);
    margin-bottom: 8px;
}
.page-subtitle {
    font-size: .88rem; color: var(--ink-3); max-width: 560px; line-height: 1.6;
}

/* ── Cards ────────────────────────────────────────────────────────────── */
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: var(--shadow);
    margin-bottom: 20px;
}

/* ── Search form ──────────────────────────────────────────────────────── */
.form-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; }
.field-label {
    display: block;
    font-size: .8rem; font-weight: 600; letter-spacing: .03em;
    color: var(--ink-2); margin-bottom: 7px;
}
.field-input {
    width: 100%; padding: 11px 16px;
    border: 1.5px solid var(--border-2);
    border-radius: var(--radius-sm);
    font-family: 'DM Sans', sans-serif;
    font-size: .95rem; color: var(--ink);
    background: var(--surface-2);
    transition: border-color .2s, box-shadow .2s;
}
.field-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(180,83,9,.1);
}

/* ── Buttons ──────────────────────────────────────────────────────────── */
.btn {
    padding: 11px 22px; border: none; border-radius: var(--radius-sm);
    font-family: 'DM Sans', sans-serif;
    font-size: .88rem; font-weight: 700; cursor: pointer;
    letter-spacing: .02em;
    transition: opacity .15s, transform .1s, box-shadow .15s;
    white-space: nowrap;
}
.btn:hover  { opacity: .88; }
.btn:active { transform: scale(.98); }

.btn-primary {
    background: var(--ink); color: #f8f5f0;
    box-shadow: 0 2px 8px rgba(26,18,8,.18);
}
.btn-reset {
    background: var(--amber); color: white;
    box-shadow: 0 2px 8px rgba(217,119,6,.25);
}
.btn-reset:hover { box-shadow: 0 4px 16px rgba(217,119,6,.35); }
.btn-sm { padding: 8px 16px; font-size: .82rem; }
.btn-disabled {
    background: var(--border); color: var(--ink-4);
    cursor: not-allowed; box-shadow: none;
}
.btn-disabled:hover { opacity: 1; }

/* ── Alert ────────────────────────────────────────────────────────────── */
.alert {
    padding: 14px 18px; border-radius: var(--radius-sm);
    font-size: .88rem; font-weight: 500; line-height: 1.6;
    margin-bottom: 20px; border: 1px solid;
}
.alert-success { background: var(--green-bg); color: #064e3b; border-color: var(--green-bdr); }
.alert-error   { background: var(--red-bg);   color: #7f1d1d; border-color: var(--red-bdr);   }
.alert-warning { background: var(--amber-bg); color: #78350f; border-color: var(--amber-bdr); }

/* ── Info notice ──────────────────────────────────────────────────────── */
.info-box {
    background: var(--blue-bg);
    border: 1px solid var(--blue-bdr);
    border-radius: var(--radius-sm);
    padding: 14px 18px;
    font-size: .84rem; color: #1e3a8a; line-height: 1.65;
    margin-bottom: 20px;
}
.info-box strong { display: block; margin-bottom: 6px; font-size: .82rem; letter-spacing: .04em; text-transform: uppercase; }
.info-box ul { margin-left: 18px; }
.info-box li { margin-bottom: 3px; }

/* ── Student bar ──────────────────────────────────────────────────────── */
.student-bar {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 12px 18px; margin-bottom: 20px;
    font-size: .88rem;
}
.student-name { font-weight: 700; color: var(--ink); }
.student-email { color: var(--blue); font-family: 'DM Mono', monospace; font-size: .82rem; }
.student-divider { color: var(--border-2); }

/* ── Stats ────────────────────────────────────────────────────────────── */
.stats-row { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.stat-box {
    flex: 1; min-width: 100px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 14px 18px; text-align: center;
    box-shadow: var(--shadow);
}
.stat-num { font-family: 'DM Serif Display', serif; font-size: 2rem; line-height: 1; }
.stat-num.amber  { color: var(--amber); }
.stat-num.red    { color: var(--red); }
.stat-num.green  { color: var(--green); }
.stat-num.ink    { color: var(--ink); }
.stat-label { font-size: .73rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--ink-4); margin-top: 5px; }

/* ── Badges ───────────────────────────────────────────────────────────── */
.badge {
    display: inline-block;
    padding: 3px 11px; border-radius: 20px;
    font-size: .72rem; font-weight: 700; letter-spacing: .03em;
    white-space: nowrap;
}
.badge-active          { background: var(--green-bg); color: #065f46; border: 1px solid var(--green-bdr); }
.badge-submitted       { background: #f0fdf4;         color: #065f46; border: 1px solid #bbf7d0; }
.badge-submitted-reset { background: var(--amber-bg); color: #78350f; border: 1px solid var(--amber-bdr); }
.badge-suspended       { background: var(--red-bg);   color: #991b1b; border: 1px solid var(--red-bdr); }
.badge-other           { background: #f1f5f9;         color: #475569; border: 1px solid #e2e8f0; }

/* ── Progress ─────────────────────────────────────────────────────────── */
.progress-wrap { background: var(--border); border-radius: 4px; height: 5px; width: 80px; overflow: hidden; margin-top: 4px; }
.progress-fill { background: var(--accent); height: 100%; border-radius: 4px; transition: width .4s; }

/* ── Table ────────────────────────────────────────────────────────────── */
.table-wrap {
    border-radius: var(--radius);
    overflow: hidden;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
}
table { width: 100%; border-collapse: collapse; background: var(--surface); }
th, td { padding: 12px 14px; text-align: left; font-size: .84rem; }
th { background: var(--surface-2); font-weight: 700; font-size: .75rem; letter-spacing: .05em; text-transform: uppercase; color: var(--ink-3); border-bottom: 1.5px solid var(--border); }
tr:not(:last-child) td { border-bottom: 1px solid var(--border); }
tr:hover td { background: var(--surface-2); }
td.pl, th.pl { padding-left: 22px; }

/* ── Realtime countdown ───────────────────────────────────────────────── */
.countdown {
    font-family: 'DM Mono', monospace;
    font-weight: 500; font-size: .95rem;
    letter-spacing: .04em;
}
.countdown.fresh   { color: var(--green); }
.countdown.caution { color: var(--amber); }
.countdown.urgent  { color: var(--red); animation: blink 1s ease-in-out infinite; }
.countdown.expired { color: var(--ink-4); }

@keyframes blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: .4; }
}

/* ── Reason tag ───────────────────────────────────────────────────────── */
.reason-tag {
    display: inline-block;
    background: var(--red-bg);
    border: 1px solid var(--red-bdr);
    border-radius: 6px;
    padding: 3px 9px;
    font-size: .73rem; color: #991b1b;
    margin-top: 4px;
}

/* ── Submit time hint ─────────────────────────────────────────────────── */
.time-hint { font-size: .73rem; color: var(--ink-4); margin-top: 2px; }
.time-ago  { font-size: .73rem; color: var(--amber); font-weight: 600; margin-top: 2px; }

/* ── Empty ────────────────────────────────────────────────────────────── */
.empty-state { text-align: center; padding: 40px; color: var(--ink-3); font-size: .9rem; }

/* ── Footer ───────────────────────────────────────────────────────────── */
.page-footer { margin-top: 40px; text-align: center; color: var(--ink-4); font-size: .77rem; letter-spacing: .03em; }

/* ── Mobile ───────────────────────────────────────────────────────────── */
@media (max-width: 680px) {
    body { padding-bottom: 40px; }
    .container { padding: 20px 14px 0; }
    .page-title { font-size: 1.5rem; }
    table, thead, tbody, th, td, tr { display: block; }
    thead { display: none; }
    td { padding: 6px 14px; }
    td::before { content: attr(data-label); font-weight: 700; font-size: .73rem; color: var(--ink-3); text-transform: uppercase; letter-spacing: .04em; display: block; margin-bottom: 2px; }
    tr { border-bottom: 1.5px solid var(--border); padding: 12px 0; }
    .stats-row { gap: 8px; }
    .stat-num { font-size: 1.6rem; }
}
</style>
</head>
<body>

<div class="top-strip">
    <span class="dot"></span>
    ADMIN TOOL &nbsp;·&nbsp; HANYA UNTUK ADMINISTRATOR &nbsp;·&nbsp; JANGAN BAGIKAN URL INI
</div>

<div class="container">

    <div class="page-title-block">
        <div class="page-eyebrow">🔄 Admin Tool</div>
        <h1 class="page-title">Reset Submit Tidak Sengaja</h1>
        <p class="page-subtitle">
            Untuk siswa yang <em>tidak sengaja</em> mengklik "Kumpulkan Ujian" sebelum menyelesaikan semua soal.
            Jawaban yang sudah diisi <strong>tidak akan hilang</strong>.
        </p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['type'] ?>"><?= $message['text'] ?></div>
    <?php endif; ?>

    <!-- Search form -->
    <div class="card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="field-label">📧 Email / Username Siswa</label>
                    <input class="field-input" type="text" name="email"
                           value="<?= htmlspecialchars($email) ?>"
                           placeholder="contoh: siswa@sekolah.sch.id" autofocus>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Cari Siswa</button>
            </div>
        </form>
    </div>

    <?php if ($email && !$user && !$message): ?>
        <div class="alert alert-error">❌ Email <b><?= htmlspecialchars($email) ?></b> tidak ditemukan di database.</div>
    <?php endif; ?>

    <?php if ($user):
        $resetableSessions = array_filter($sessions, fn($s) => canResetSession($s)['can']);
        $doneSessions      = array_filter($sessions, fn($s) =>
            !empty($s['submitted_at']) && !canResetSession($s)['can'] && !isViolationSession($s));
        $activeSessions    = array_filter($sessions, fn($s) => empty($s['submitted_at']) && $s['status'] !== 'completed');
    ?>

    <div class="student-bar">
        👤 <span class="student-name"><?= htmlspecialchars($user['full_name']) ?></span>
        <span class="student-divider">|</span>
        <span class="student-email"><?= htmlspecialchars($email) ?></span>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-num ink"><?= count($sessions) ?></div>
            <div class="stat-label">Total Sesi</div>
        </div>
        <div class="stat-box">
            <div class="stat-num amber"><?= count($resetableSessions) ?></div>
            <div class="stat-label">Bisa Direset</div>
        </div>
        <div class="stat-box">
            <div class="stat-num green"><?= count($activeSessions) ?></div>
            <div class="stat-label">Sedang Aktif</div>
        </div>
        <div class="stat-box">
            <div class="stat-num ink"><?= count($doneSessions) ?></div>
            <div class="stat-label">Selesai Normal</div>
        </div>
    </div>

    <?php if (count($resetableSessions) > 0): ?>
        <div class="alert alert-warning">
            ⚠️ Ditemukan <b><?= count($resetableSessions) ?> sesi</b> yang bisa direset (submit normal dalam <?= MAX_RESET_MINUTES ?> menit terakhir, waktu ujian masih berlangsung).
        </div>
    <?php endif; ?>

    <div class="info-box">
        <strong>ℹ️ Syarat & apa yang terjadi saat reset:</strong>
        <ul>
            <li>Hanya ujian yang submit <b>secara normal</b> (bukan karena pelanggaran/suspended) yang bisa direset di sini.</li>
            <li>Reset hanya tersedia dalam <b><?= MAX_RESET_MINUTES ?> menit</b> sejak waktu submit & selama <b>waktu ujian belum berakhir</b>.</li>
            <li>Status ujian dikembalikan ke <b>Aktif</b> — semua jawaban yang sudah diisi <b>tetap ada</b>.</li>
            <li>Sisa waktu dihitung ulang: waktu tersimpan dikurangi waktu yang sudah berlalu sejak submit.</li>
            <li>Siswa cukup buka halaman Daftar Ujian dan klik <b>"Lanjutkan Ujian"</b>.</li>
            <li>Untuk submit karena <em>pelanggaran/suspended</em>, gunakan tool <b>Reset Suspend</b>.</li>
        </ul>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="card"><div class="empty-state">Tidak ada sesi ujian ditemukan untuk siswa ini.</div></div>
    <?php else: ?>

    <div class="table-wrap">
        <table>
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
                $sl         = statusLabel($s);
                $check      = canResetSession($s);
                $pct        = $s['total_questions'] > 0
                                ? round(($s['answered_count'] / $s['total_questions']) * 100) : 0;
                $minsAgo    = !empty($s['submitted_at'])
                                ? round((time() - strtotime($s['submitted_at'])) / 60) : null;
                $isViolation = isViolationSession($s);

                // Restored remaining for preview
                $restoredRem = $check['can'] ? calcRestoredRemaining($s) : 0;
                $resMin = floor($restoredRem / 60);
                $resSec = $restoredRem % 60;
            ?>
            <tr>
                <!-- Ujian -->
                <td class="pl" data-label="Ujian" style="max-width:200px">
                    <div style="font-weight:700"><?= htmlspecialchars($s['title']) ?></div>
                    <?php if ($s['end_time']): ?>
                        <div class="time-hint">Berakhir: <?= date('d M, H:i', strtotime($s['end_time'])) ?></div>
                    <?php endif; ?>
                </td>

                <!-- Status -->
                <td data-label="Status">
                    <span class="badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span>
                    <?php if (!$check['can'] && !empty($s['submitted_at']) && !$isViolation): ?>
                        <div class="reason-tag">🚫 <?= htmlspecialchars($check['reason']) ?></div>
                    <?php elseif ($isViolation): ?>
                        <div class="reason-tag">⚡ Gunakan Reset Suspend</div>
                    <?php endif; ?>
                </td>

                <!-- Jawaban -->
                <td data-label="Jawaban">
                    <div style="font-weight:600"><?= (int)$s['answered_count'] ?> / <?= (int)$s['total_questions'] ?></div>
                    <div class="progress-wrap"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                    <div class="time-hint"><?= $pct ?>% terjawab</div>
                </td>

                <!-- Waktu submit -->
                <td data-label="Waktu Submit">
                    <?php if ($s['submitted_at']): ?>
                        <div style="font-size:.84rem;font-weight:600"><?= date('H:i:s', strtotime($s['submitted_at'])) ?></div>
                        <div class="time-hint"><?= date('d M Y', strtotime($s['submitted_at'])) ?></div>
                        <?php if ($minsAgo !== null): ?>
                            <div class="time-ago"><?= $minsAgo ?> menit lalu</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:var(--ink-4);font-size:.84rem">—</span>
                    <?php endif; ?>
                </td>

                <!-- Sisa waktu (live countdown jika bisa reset) -->
                <td data-label="Sisa Waktu">
                    <?php if ($check['can']): ?>
                        <div class="countdown" data-sid="<?= $s['id'] ?>">
                            <?= sprintf('%02d:%02d', $resMin, $resSec) ?>
                        </div>
                        <div class="time-hint">sisa jika reset sekarang</div>
                    <?php elseif (empty($s['submitted_at']) && $s['status'] !== 'completed'): ?>
                        <?php
                        $rem  = (int)$s['remaining_time'];
                        $rMin = floor($rem / 60); $rSec = $rem % 60;
                        ?>
                        <span style="font-weight:600;color:var(--green)"><?= sprintf('%02d:%02d', $rMin, $rSec) ?></span>
                        <div class="time-hint">⏱️ Aktif</div>
                    <?php else: ?>
                        <span style="color:var(--ink-4)">—</span>
                    <?php endif; ?>
                </td>

                <!-- Nilai -->
                <td data-label="Nilai">
                    <?php if ($s['submitted_at'] && $check['can']): ?>
                        <span style="font-size:1rem;font-weight:700;color:var(--amber)"><?= number_format((float)$s['score'], 0) ?></span>
                        <div class="time-hint" style="color:var(--amber)">akan dihapus</div>
                    <?php elseif ($s['submitted_at']): ?>
                        <span style="font-size:1rem;font-weight:700;color:var(--green)"><?= number_format((float)$s['score'], 0) ?></span>
                    <?php else: ?>
                        <span style="color:var(--ink-4)">—</span>
                    <?php endif; ?>
                </td>

                <!-- Aksi -->
                <td data-label="Aksi">
                    <?php if ($check['can']): ?>
                        <form method="POST" onsubmit="return confirmReset(
                            '<?= addslashes(htmlspecialchars($s['title'])) ?>',
                            <?= (int)$s['answered_count'] ?>,
                            <?= (int)$s['total_questions'] ?>,
                            <?= $minsAgo ?? 0 ?>
                        )">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                            <input type="hidden" name="reset_session_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-reset btn-sm" data-reset-btn="<?= $s['id'] ?>">
                                🔄 Batalkan Submit
                            </button>
                        </form>
                    <?php elseif ($isViolation): ?>
                        <a href="reset_suspend.php?email=<?= urlencode($email) ?>" class="btn btn-sm" style="background:var(--red-bg);color:var(--red);border:1px solid var(--red-bdr);text-decoration:none">
                            ↗ Buka Reset Suspend
                        </a>
                    <?php elseif (!empty($s['submitted_at'])): ?>
                        <span style="color:var(--ink-4);font-size:.82rem">✓ Tidak bisa direset</span>
                    <?php elseif (empty($s['submitted_at'])): ?>
                        <span style="color:var(--green);font-size:.82rem">🟢 Masih aktif</span>
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
        🛡️ Tool ini hanya untuk Administrator &bull; Data siswa bersifat rahasia &bull; Jangan bagikan URL ini
    </div>
</div>

<!-- Countdown JS -->
<?php if (!empty($timerData)): ?>
<script>
const TIMER_DATA   = <?= json_encode($timerData) ?>;
const PAGE_LOAD_TS = Math.floor(Date.now() / 1000);

function fmtTime(secs) {
    if (secs <= 0) return '00:00';
    return String(Math.floor(secs / 60)).padStart(2,'0') + ':' + String(secs % 60).padStart(2,'0');
}

function calcRemaining(d) {
    const now           = Math.floor(Date.now() / 1000);
    const browserElap   = now - PAGE_LOAD_TS;
    const elapsedBefore = Math.max(0, d.serverNow - d.submittedTs);
    const totalElapsed  = elapsedBefore + browserElap;
    let rem             = Math.max(0, d.savedRemaining - totalElapsed);
    const timeUntilEnd  = Math.max(0, d.endTs - now);
    rem = Math.min(rem, timeUntilEnd, d.savedRemaining);
    return Math.max(0, rem);
}

function colorClass(secs) {
    if (secs <= 0)    return 'expired';
    if (secs < 120)   return 'urgent';
    if (secs < 600)   return 'caution';
    return 'fresh';
}

function tick() {
    document.querySelectorAll('.countdown[data-sid]').forEach(el => {
        const sid  = el.dataset.sid;
        const data = TIMER_DATA[sid];
        if (!data) return;
        const rem = calcRemaining(data);
        el.textContent = fmtTime(rem);
        el.className = 'countdown ' + colorClass(rem);
        // Disable tombol jika habis
        if (rem <= 0) {
            const btn = document.querySelector(`[data-reset-btn="${sid}"]`);
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = '⛔ Waktu habis';
                btn.className = 'btn btn-disabled btn-sm';
            }
        }
    });
}

tick();
setInterval(tick, 1000);
</script>
<?php endif; ?>

</body>
</html>