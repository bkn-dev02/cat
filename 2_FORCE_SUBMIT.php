<?php
/**
 * Admin Tool — Force Submit & Recalculate
 * Fix: wrapped semua DB calls dalam try/catch untuk mencegah HTTP 500
 *      gradeAndCalculateScore sekarang UPDATE score ke exam_sessions
 *      Recalculate sekarang hanya update score tanpa mengubah submitted_at
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ── Koneksi DB ───────────────────────────────────────────────────────────────
try {
    $config = require __DIR__ . '/config.production.php';
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
    error_log('[force_submit] DB connect error: ' . $e->getMessage());
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
        error_log('[force_submit] fetchUser error: ' . $e->getMessage());
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
            es.status,
            es.submitted_at,
            es.score,
            es.remaining_time,
            es.violation_count,
            es.suspension_reason,
            es.suspended_at,
            e.start_time,
            e.end_time,
            e.duration,
            (SELECT COUNT(*) FROM answers a       WHERE a.session_id  = es.id)        AS answered_count,
            (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id  = es.exam_id)   AS total_questions
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.user_id = ?
        ORDER BY es.id DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function isOverTime(array $s): bool
{
    if ($s['submitted_at'])            return false;
    if ($s['status'] === 'completed')  return false;
    if ((int)$s['remaining_time'] <= 0) return true;
    if ($s['end_time'] && strtotime($s['end_time']) < time()) return true;
    return false;
}

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
    if ((int)$s['violation_count'] >= 1 && !empty($s['submitted_at']) && $s['status'] !== 'completed') {
        return true;
    }
    return false;
}

function isZeroScoreWithAnswers(array $s): bool
{
    return (float)$s['score'] == 0
        && (int)$s['answered_count'] > 0
        && $s['submitted_at'] !== null;
}

/**
 * Cek kolom 'points' yang tersedia di exam_questions dan questions.
 * Kembalikan SQL expression yang aman untuk SELECT.
 */
function detectPointsColumn(PDO $pdo): string
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $eqCols = [];
    $qCols  = [];

    try {
        $r = $pdo->query("SHOW COLUMNS FROM exam_questions");
        foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $eqCols[] = strtolower($col['Field']);
        }
    } catch (Exception $e) {}

    try {
        $r = $pdo->query("SHOW COLUMNS FROM questions");
        foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $qCols[] = strtolower($col['Field']);
        }
    } catch (Exception $e) {}

    $hasEqPoints = in_array('points', $eqCols);
    $hasQPoints  = in_array('points', $qCols);

    if ($hasEqPoints && $hasQPoints) {
        $cache = 'COALESCE(eq.points, q.points, 1)';
    } elseif ($hasEqPoints) {
        $cache = 'COALESCE(eq.points, 1)';
    } elseif ($hasQPoints) {
        $cache = 'COALESCE(q.points, 1)';
    } else {
        $cache = '1'; // fallback: semua soal bernilai 1
    }

    return $cache;
}

/**
 * Grade semua jawaban, UPDATE is_correct & points_earned, kembalikan ringkasan skor.
 */
function gradeAndCalculateScore(PDO $pdo, int $sessionId, int $examId): array
{
    // ── Jawaban siswa ────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT a.id AS answer_id, a.question_id, a.answer,
               a.is_correct, a.points_earned
        FROM answers a
        WHERE a.session_id = :sid
    ");
    $stmt->execute([':sid' => $sessionId]);
    $studentAnswers = $stmt->fetchAll();

    if (empty($studentAnswers)) {
        return [
            'score' => 0, 'correct' => 0, 'total_answered' => 0,
            'total_questions' => 0, 'points_earned' => 0, 'max_points' => 0,
            'strategy' => 'none', 'graded_now' => false,
            'note' => 'Tidak ada jawaban tersimpan.',
        ];
    }

    // ── Kunci jawaban & bobot ────────────────────────────────────────────────
    // Deteksi kolom 'points' yang tersedia di exam_questions / questions
    $pointsExpr = detectPointsColumn($pdo);
    $stmt = $pdo->prepare("
        SELECT q.id AS question_id, q.correct_answer, q.type,
               {$pointsExpr} AS points
        FROM exam_questions eq
        JOIN questions q ON q.id = eq.question_id
        WHERE eq.exam_id = :eid
    ");
    $stmt->execute([':eid' => $examId]);
    $questionMap = [];
    $maxPoints   = 0;
    foreach ($stmt->fetchAll() as $row) {
        $questionMap[$row['question_id']] = $row;
        $maxPoints += (float)$row['points'];
    }

    $totalQuestions = count($questionMap);
    $totalAnswered  = count($studentAnswers);
    $correctCount   = 0;
    $pointsEarned   = 0;

    // Selalu re-grade semua jawaban (force recalculate)
    $updateStmt = $pdo->prepare("
        UPDATE answers
        SET is_correct    = :is_correct,
            points_earned = :pts
        WHERE id = :aid
    ");

    foreach ($studentAnswers as $ans) {
        $qInfo     = $questionMap[$ans['question_id']] ?? null;
        $isCorrect = 0;
        $pts       = 0.0;

        if ($qInfo) {
            $correctAnswer = trim($qInfo['correct_answer'] ?? '');
            $studentAnswer = trim($ans['answer'] ?? '');
            $qType         = $qInfo['type'] ?? 'multiple_choice';

            if ($qType === 'essay') {
                // Essay tidak bisa auto-grade → set NULL agar tidak di-overwrite
                $isCorrect = null;
                $pts       = null;
            } elseif ($qType === 'short_answer') {
                // Toleransi numerik 0.001
                $isCorrect = (strlen($studentAnswer) > 0 && abs((float)$studentAnswer - (float)$correctAnswer) < 0.001) ? 1 : 0;
                $pts       = $isCorrect ? (float)$qInfo['points'] : 0.0;
            } else {
                // multiple_choice, listening, short_answer_text — exact match (case-insensitive)
                $isCorrect = (strcasecmp($studentAnswer, $correctAnswer) === 0) ? 1 : 0;
                $pts       = $isCorrect ? (float)$qInfo['points'] : 0.0;
            }
        }

        $updateStmt->execute([
            ':is_correct' => $isCorrect,
            ':pts'        => $pts,
            ':aid'        => $ans['answer_id'],
        ]);

        if ($isCorrect === 1) {
            $correctCount++;
            $pointsEarned += $pts;
        }
    }

    // ── Hitung skor final ────────────────────────────────────────────────────
    if ($maxPoints > 0) {
        $score    = round(($pointsEarned / $maxPoints) * 100, 2);
        $strategy = 'points';
    } elseif ($totalQuestions > 0) {
        $score    = round(($correctCount / $totalQuestions) * 100, 2);
        $strategy = 'correct_ratio';
    } else {
        $score    = 0;
        $strategy = 'none';
    }

    return [
        'score'           => $score,
        'correct'         => $correctCount,
        'total_answered'  => $totalAnswered,
        'total_questions' => $totalQuestions,
        'points_earned'   => $pointsEarned,
        'max_points'      => $maxPoints,
        'strategy'        => $strategy,
        'graded_now'      => true,
    ];
}

// ════════════════════════════════════════════════════════════════════════════
//  PROSES POST
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_submit_session_id']) && $user) {

    $sid    = (int) $_POST['force_submit_session_id'];
    $action = $_POST['action'] ?? 'force_submit'; // 'force_submit' | 'recalculate'

    try {
        // ── Ambil session ────────────────────────────────────────────────────
        $stmt = $pdo->prepare("
            SELECT es.*, e.title, e.start_time, e.end_time, e.duration,
                   (SELECT COUNT(*) FROM answers a WHERE a.session_id = es.id) AS answered_count
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.id = ? AND es.user_id = ?
        ");
        $stmt->execute([$sid, $user['id']]);
        $target = $stmt->fetch();

        if (!$target) {
            $message = ['type' => 'error', 'text' => '❌ Session tidak ditemukan.'];

        } elseif (
            $action === 'force_submit'
            && $target['submitted_at']
            && $target['status'] === 'completed'
            && !isViolationSession($target)
            && !isZeroScoreWithAnswers($target)
        ) {
            $message = ['type' => 'error', 'text' => '❌ Ujian ini sudah disubmit secara normal dan nilainya sudah terhitung. Tidak dapat disubmit ulang.'];

        } else {
            // ── Grade & hitung skor ──────────────────────────────────────────
            $scoreData = gradeAndCalculateScore($pdo, $sid, (int)$target['exam_id']);

            if ($action === 'recalculate') {
                // ── RECALCULATE: hanya update score, jangan ubah submitted_at ──
                $pdo->prepare("
                    UPDATE exam_sessions
                    SET score         = ?,
                        is_best_score = 1
                    WHERE id = ? AND user_id = ?
                ")->execute([$scoreData['score'], $sid, $user['id']]);

                $examTitle    = htmlspecialchars($target['title'] ?? '');
                $strategyNote = buildStrategyNote($scoreData);

                $message = [
                    'type' => 'success',
                    'text' => "🔢 Recalculate selesai untuk ujian <b>\"$examTitle\"</b>. "
                            . "Jawaban diperiksa ulang — {$scoreData['correct']} benar dari {$scoreData['total_answered']} jawaban tersimpan. "
                            . "Skor dihitung $strategyNote. "
                            . "Nilai akhir: <b>{$scoreData['score']}</b>."
                ];

            } else {
                // ── FORCE SUBMIT: keep violation history and sync its summary ─
                $pdo->prepare("
                                UPDATE exam_sessions
                                SET status              = 'completed',
                                    token_status        = 'expired',
                                    submitted_at        = COALESCE(submitted_at, NOW()),
                                    score               = ?,
                                    is_best_score       = 1,
                                    remaining_time      = 0,
                                    violation_count     = (SELECT COUNT(*) FROM exam_violations WHERE session_id = ?),
                                    suspension_reason   = NULL,
                                    suspended_at        = NULL
                                    -- force_submitted dan force_submitted_at dihapus karena kolom belum ada di DB
                                WHERE id = ? AND user_id = ?
                            ")->execute([$scoreData['score'], $sid, $sid, $user['id']]);

                $examTitle    = htmlspecialchars($target['title'] ?? '');
                $strategyNote = buildStrategyNote($scoreData);

                $message = [
                    'type' => 'success',
                    'text' => "⏹ Ujian <b>\"$examTitle\"</b> berhasil di-force submit. "
                            . "Jawaban tersimpan: <b>{$scoreData['total_answered']}</b> dari <b>{$scoreData['total_questions']}</b> soal. "
                            . "Skor dihitung $strategyNote. "
                            . "Nilai akhir: <b>{$scoreData['score']}</b>."
                ];
            }

            $sessions = fetchSessions($pdo, $user['id']);
        }

    } catch (Exception $e) {
        error_log('[force_submit] POST error: ' . $e->getMessage() . ' | session=' . $sid);
        $message = [
            'type' => 'error',
            'text' => '❌ Terjadi error saat memproses: <code>' . htmlspecialchars($e->getMessage()) . '</code>'
        ];
    }
}

function buildStrategyNote(array $d): string
{
    return $d['strategy'] === 'points'
        ? "berbasis poin ({$d['points_earned']} / {$d['max_points']} poin)"
        : "berbasis rasio ({$d['correct']} benar / {$d['total_questions']} soal)";
}

// ── Status label ─────────────────────────────────────────────────────────────
function statusLabel(array $s): array
{
    if (isViolationSession($s))  return ['label' => '🚫 Auto-Submit (Pelanggaran)', 'class' => 'badge-suspended'];
    if (isOverTime($s))          return ['label' => '⏰ Lewat Waktu',              'class' => 'badge-overtime'];
    if ($s['submitted_at'])      return ['label' => '✅ Submitted',                'class' => 'badge-submitted'];
    return match ($s['status']) {
        'active', 'in_progress' => ['label' => '🟢 Aktif',    'class' => 'badge-active'],
        'completed'             => ['label' => '✅ Selesai',   'class' => 'badge-submitted'],
        'suspended'             => ['label' => '🚫 Suspended', 'class' => 'badge-suspended'],
        default                 => ['label' => ucfirst($s['status']), 'class' => 'badge-other'],
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Tool — Force Submit Ujian</title>
<style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', system-ui, sans-serif;
        background: #f1f5f9;
        margin: 0; padding: 20px;
        color: #1e293b;
    }
    .container { max-width: 1020px; margin: 32px auto; }

    .page-header { display: flex; align-items: center; gap: 14px; margin-bottom: 4px; }
    .page-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; flex-shrink: 0;
        background: linear-gradient(135deg, #6366f1, #4338ca);
    }
    h1 { margin: 0; font-size: 1.4rem; font-weight: 700; }
    .subtitle { color: #64748b; margin: 0 0 24px 62px; font-size: .88rem; }

    .card {
        background: white; border-radius: 14px; padding: 22px;
        box-shadow: 0 1px 4px rgba(0,0,0,.07); margin-bottom: 18px;
    }

    .form-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 200px; }
    label.field-label { display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: .87rem; }
    input[type=text] {
        width: 100%; padding: 10px 14px;
        border: 1.5px solid #d1d5db; border-radius: 8px;
        font-size: .95rem; transition: border-color .2s; color: #1e293b;
    }
    input[type=text]:focus { outline: none; border-color: #6366f1; }

    .btn {
        padding: 10px 20px; border: none; border-radius: 8px;
        font-size: .88rem; font-weight: 600; cursor: pointer;
        transition: opacity .15s, transform .1s; white-space: nowrap;
    }
    .btn:hover   { opacity: .87; }
    .btn:active  { transform: scale(.98); }
    .btn-primary { background: #6366f1; color: white; }
    .btn-danger  { background: #ef4444; color: white; }
    .btn-warning { background: #f59e0b; color: white; }
    .btn-sm      { padding: 6px 14px; font-size: .8rem; }

    .alert { padding: 13px 17px; border-radius: 10px; margin-bottom: 18px; font-size: .88rem; font-weight: 500; line-height: 1.55; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .alert-warning { background: #fff7ed; color: #92400e; border: 1px solid #fed7aa; }

    .student-bar {
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
        padding: 11px 17px; margin-bottom: 16px;
        display: flex; align-items: center; gap: 10px;
        font-weight: 600; color: #1e40af; font-size: .88rem; flex-wrap: wrap;
    }

    .note-warn {
        background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px;
        padding: 13px 17px; color: #92400e; font-size: .85rem; margin-bottom: 18px;
    }
    .note-warn strong { display: block; margin-bottom: 4px; color: #7c2d12; }
    .note-warn ul { margin: 5px 0 0 16px; padding: 0; }

    .table-wrap { border-radius: 14px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
    table { width: 100%; border-collapse: collapse; background: white; }
    th, td { padding: 11px 13px; text-align: left; font-size: .85rem; }
    th { background: #f8fafc; font-weight: 700; color: #475569; border-bottom: 2px solid #e2e8f0; }
    tr:not(:last-child) td { border-bottom: 1px solid #f1f5f9; }
    tr:hover td { background: #fafafa; }
    td.pl, th.pl { padding-left: 18px; }

    .badge {
        display: inline-block; padding: 3px 11px;
        border-radius: 20px; font-size: .75rem; font-weight: 700;
    }
    .badge-active    { background: #d1fae5; color: #065f46; }
    .badge-submitted { background: #fef3c7; color: #92400e; }
    .badge-suspended { background: #fee2e2; color: #991b1b; }
    .badge-overtime  { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .badge-other     { background: #e2e8f0; color: #475569; }
    .badge-zero      { background: #fee2e2; color: #991b1b; border: 1px dashed #fca5a5; }

    .progress-wrap { background: #e2e8f0; border-radius: 4px; height: 5px; width: 76px; overflow: hidden; margin-top: 3px; }
    .progress-fill { background: #6366f1; height: 100%; }

    .time-over { color: #dc2626; font-weight: 700; }
    .time-warn { color: #d97706; font-weight: 700; }
    .time-ok   { color: #1e293b; font-weight: 600; }
    .time-hint { font-size: .72rem; color: #94a3b8; margin-top: 2px; }

    .stats-row { display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
    .stat-box {
        flex: 1; min-width: 110px; background: white; border-radius: 10px;
        padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,.07); text-align: center;
    }
    .stat-box .stat-num { font-size: 1.6rem; font-weight: 700; line-height: 1; }
    .stat-box .stat-label { font-size: .78rem; color: #64748b; margin-top: 4px; }
    .stat-num.danger  { color: #dc2626; }
    .stat-num.warning { color: #d97706; }
    .stat-num.success { color: #059669; }
    .stat-num.primary { color: #6366f1; }

    /* Tombol aksi dalam satu kolom */
    .action-col { display: flex; flex-direction: column; gap: 6px; }

    /* Score preview chip */
    .score-chip {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: .78rem; padding: 2px 8px; border-radius: 20px;
        background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;
        font-weight: 600; margin-top: 4px;
    }
    .score-chip.zero { background: #fff1f2; color: #be123c; border-color: #fecdd3; }

    .page-footer { margin-top: 32px; text-align: center; color: #94a3b8; font-size: .78rem; }
    .empty-state { text-align: center; padding: 32px; color: #94a3b8; font-size: .88rem; }

    @media (max-width: 640px) {
        body { padding: 10px; }
        .container { margin: 16px auto; }
        table { font-size: .8rem; }
        th, td { padding: 8px 9px; }
    }
</style>
</head>
<body>
<div class="container">

    <div class="page-header">
        <div class="page-icon">🛡️</div>
        <h1>Admin Tool — Force Submit Ujian</h1>
    </div>
    <p class="subtitle">Submit paksa ujian siswa yang melewati waktu atau kondisi darurat. Jawaban diperiksa &amp; dinilai otomatis.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['type'] ?>"><?= $message['text'] ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="field-label">📧 Email / Username Siswa</label>
                    <input type="text" name="email"
                           value="<?= htmlspecialchars($email) ?>"
                           placeholder="contoh: siswa@gmail.com" autofocus>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Cari Siswa</button>
            </div>
        </form>
    </div>

    <?php if ($email && !$user && !$message): ?>
        <div class="alert alert-error">❌ Email <b><?= htmlspecialchars($email) ?></b> tidak ditemukan.</div>
    <?php endif; ?>

    <?php if ($user):
        $overtimeSessions  = array_filter($sessions, fn($s) => isOverTime($s));
        $zeroScoreSessions = array_filter($sessions, fn($s) => isZeroScoreWithAnswers($s));
        $totalSess         = count($sessions);
        $overtimeCount     = count($overtimeSessions);
        $zeroScoreCount    = count($zeroScoreSessions);
        $activeCount       = count(array_filter($sessions, fn($s) => !$s['submitted_at'] && $s['status'] !== 'completed'));
        $doneCount         = count(array_filter($sessions, fn($s) =>
            $s['submitted_at'] && $s['status'] === 'completed'
            && !isViolationSession($s) && !isZeroScoreWithAnswers($s)));
    ?>

    <div class="student-bar">
        👤 <span><?= htmlspecialchars($user['full_name']) ?></span>
        &nbsp;|&nbsp;
        <span style="font-weight:400;color:#3b82f6"><?= htmlspecialchars($email) ?></span>
    </div>

    <div class="stats-row">
        <div class="stat-box"><div class="stat-num primary"><?= $totalSess ?></div><div class="stat-label">Total Sesi</div></div>
        <div class="stat-box"><div class="stat-num danger"><?= $overtimeCount ?></div><div class="stat-label">Lewat Waktu</div></div>
        <div class="stat-box"><div class="stat-num warning"><?= $activeCount ?></div><div class="stat-label">Aktif</div></div>
        <div class="stat-box"><div class="stat-num success"><?= $doneCount ?></div><div class="stat-label">Selesai Normal</div></div>
        <?php if ($zeroScoreCount > 0): ?>
        <div class="stat-box"><div class="stat-num danger"><?= $zeroScoreCount ?></div><div class="stat-label">Nilai 0 ⚠️</div></div>
        <?php endif; ?>
    </div>

    <?php if ($overtimeCount > 0): ?>
        <div class="alert alert-warning">
            ⚠️ Ditemukan <b><?= $overtimeCount ?> sesi</b> yang sudah melewati batas waktu dan belum disubmit.
        </div>
    <?php endif; ?>
    <?php if ($zeroScoreCount > 0): ?>
        <div class="alert alert-warning">
            🔢 Ditemukan <b><?= $zeroScoreCount ?> sesi</b> yang sudah submit tapi nilainya <b>0</b> padahal ada jawaban tersimpan. Gunakan <b>Recalculate</b>.
        </div>
    <?php endif; ?>

    <div class="note-warn">
        <strong>⚠️ Perhatian sebelum Force Submit / Recalculate:</strong>
        <ul>
            <li><b>Force Submit</b>: submit paksa + hitung nilai dari jawaban tersimpan. Untuk ujian yang <em>belum</em> selesai.</li>
            <li><b>Recalculate</b>: hitung ulang nilai saja. Waktu submit &amp; data lain tidak berubah. Untuk yang sudah submit tapi nilai 0.</li>
            <li>Kedua operasi menjalankan pengecekan benar/salah otomatis per soal (pilihan ganda, isian singkat).</li>
            <li>Setelah Force Submit, siswa yang membuka halaman ujian akan <b>otomatis diarahkan ke halaman hasil</b>.</li>
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
                    <th>Waktu</th>
                    <th>Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $s):
                $sl        = statusLabel($s);
                $overtime  = isOverTime($s);
                $zeroScore = isZeroScoreWithAnswers($s);
                $pct       = $s['total_questions'] > 0
                                ? round(($s['answered_count'] / $s['total_questions']) * 100) : 0;
                $remAbs    = abs((int)$s['remaining_time']);
                $remMins   = floor($remAbs / 60);
                $remSecs   = $remAbs % 60;

                $canForce  = !$s['submitted_at'] || isViolationSession($s);
                $canRecalc = $zeroScore && !$canForce;
                $isDone    = !$canForce && !$canRecalc;
            ?>
            <tr <?= $zeroScore ? 'style="background:#fff8f8"' : '' ?>>

                <!-- Nama Ujian -->
                <td class="pl" style="max-width:210px">
                    <div style="font-weight:600"><?= htmlspecialchars($s['title']) ?></div>
                    <?php if ($overtime): ?>
                        <div style="font-size:.73rem;color:#dc2626;margin-top:2px">
                            ⏰ Lewat <?= sprintf('%02d:%02d', $remMins, $remSecs) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($s['end_time']): ?>
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:1px">
                            Berakhir: <?= date('d M, H:i', strtotime($s['end_time'])) ?>
                        </div>
                    <?php endif; ?>
                </td>

                <!-- Status -->
                <td>
                    <span class="badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span>
                    <?php if ($zeroScore): ?>
                        <div style="margin-top:4px"><span class="badge badge-zero">⚠️ Nilai 0</span></div>
                    <?php endif; ?>
                    <?php if ($s['submitted_at']): ?>
                        <div class="time-hint"><?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?></div>
                    <?php endif; ?>
                </td>

                <!-- Jawaban -->
                <td>
                    <div style="font-weight:600"><?= (int)$s['answered_count'] ?> / <?= (int)$s['total_questions'] ?></div>
                    <div class="progress-wrap">
                        <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="time-hint"><?= $pct ?>% terjawab</div>
                </td>

                <!-- Sisa Waktu -->
                <td>
                    <?php if ($s['submitted_at'] && !isViolationSession($s)): ?>
                        <span style="color:#94a3b8;font-size:.84rem">—</span>
                    <?php elseif ($overtime): ?>
                        <span class="time-over">+<?= sprintf('%02d:%02d', $remMins, $remSecs) ?></span>
                        <div class="time-hint">melebihi batas</div>
                    <?php else: ?>
                        <?php
                        $rem   = (int)$s['remaining_time'];
                        $rMin  = floor(abs($rem) / 60);
                        $rSec  = abs($rem) % 60;
                        $tCls  = $rem < 300 ? 'time-warn' : 'time-ok';
                        ?>
                        <span class="<?= $tCls ?>"><?= sprintf('%02d:%02d', $rMin, $rSec) ?></span>
                        <div class="time-hint">sisa waktu</div>
                    <?php endif; ?>
                </td>

                <!-- Nilai -->
                <td>
                    <?php if ($s['submitted_at'] && !$zeroScore): ?>
                        <span style="font-weight:700;color:#059669;font-size:1rem">
                            <?= number_format((float)$s['score'], 0) ?>
                        </span>
                    <?php elseif ($zeroScore): ?>
                        <span style="font-weight:700;color:#dc2626;font-size:1rem">0</span>
                        <div class="time-hint" style="color:#dc2626">perlu recalculate</div>
                    <?php else: ?>
                        <span style="color:#94a3b8;font-size:.82rem">Belum</span>
                    <?php endif; ?>
                </td>

                <!-- Aksi -->
                <td>
                    <div class="action-col">
                    <?php if ($canForce): ?>
                        <form method="POST"
                              onsubmit="return confirmForce(
                                  '<?= addslashes(htmlspecialchars($s['title'])) ?>',
                                  <?= (int)$s['answered_count'] ?>,
                                  <?= (int)$s['total_questions'] ?>,
                                  <?= $overtime ? 'true' : 'false' ?>
                              )">
                            <input type="hidden" name="email"  value="<?= htmlspecialchars($email) ?>">
                            <input type="hidden" name="action" value="force_submit">
                            <input type="hidden" name="force_submit_session_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">⏹ Force Submit</button>
                        </form>

                    <?php elseif ($canRecalc): ?>
                        <form method="POST"
                              onsubmit="return confirmRecalc(
                                  '<?= addslashes(htmlspecialchars($s['title'])) ?>',
                                  <?= (int)$s['answered_count'] ?>,
                                  <?= (int)$s['total_questions'] ?>
                              )">
                            <input type="hidden" name="email"  value="<?= htmlspecialchars($email) ?>">
                            <input type="hidden" name="action" value="recalculate">
                            <input type="hidden" name="force_submit_session_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-warning btn-sm">🔢 Recalculate</button>
                        </form>

                    <?php else: ?>
                        <span style="color:#94a3b8;font-size:.8rem">✓ Sudah selesai</span>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="page-footer">
        🛡️ Tool ini hanya untuk Administrator &bull; Jangan bagikan URL ini kepada siswa
    </div>
</div>

<script>
function confirmForce(title, answered, total, isOver) {
    const pct    = total > 0 ? Math.round((answered / total) * 100) : 0;
    const status = isOver
        ? '⏰ Waktu sudah habis'
        : '⚠️ Waktu BELUM habis (override admin)';
    return confirm(
        'Force Submit Ujian?\n\n'
        + 'Ujian    : ' + title + '\n'
        + 'Jawaban  : ' + answered + ' / ' + total + ' soal (' + pct + '%)\n'
        + 'Status   : ' + status + '\n\n'
        + 'Jawaban akan diperiksa benar/salah otomatis & nilai dihitung.\n'
        + 'Siswa langsung diarahkan ke halaman hasil.\n'
        + 'Tindakan ini TIDAK DAPAT dibatalkan.\n\nLanjutkan?'
    );
}

function confirmRecalc(title, answered, total) {
    const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
    return confirm(
        'Recalculate Nilai?\n\n'
        + 'Ujian   : ' + title + '\n'
        + 'Jawaban : ' + answered + ' / ' + total + ' soal (' + pct + '%)\n\n'
        + 'Jawaban diperiksa ulang dari awal (is_correct & points_earned di-reset).\n'
        + 'Nilai diperbaiki. Waktu submit tidak berubah.\n\nLanjutkan?'
    );
}
</script>
</body>
</html>