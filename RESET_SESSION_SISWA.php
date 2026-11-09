<?php
/**
 * Reset Session Ujian Siswa PPDB
 * Cukup masukkan email siswa, pilih ujian, klik Reset
 */
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$user = null;
$sessions = [];
$message = null;
$email = trim($_POST['email'] ?? $_GET['email'] ?? '');

// Cari siswa
if ($email) {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stmt = $pdo->prepare("
            SELECT es.id, e.title, es.status, es.submitted_at, es.score, es.remaining_time
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.user_id = ?
            ORDER BY es.id DESC
        ");
        $stmt->execute([$user['id']]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Proses reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_session_id']) && $user) {
    $sid = (int)$_POST['reset_session_id'];
    // HAPUS session sepenuhnya agar sistem buat session baru saat siswa klik Mulai Ujian
    $pdo->prepare("DELETE FROM answers WHERE session_id=?")->execute([$sid]);
    $pdo->prepare("DELETE FROM exam_sessions WHERE id=? AND user_id=?")->execute([$sid, $user['id']]);
    $message = ['type' => 'success', 'text' => "✅ Session dihapus! Minta siswa klik 'Kembali ke Daftar Ujian' lalu klik 'Mulai Ujian' lagi."];
    
    // Refresh sessions
    $stmt = $pdo->prepare("SELECT es.id, e.title, es.status, es.submitted_at, es.score, es.remaining_time FROM exam_sessions es JOIN exams e ON es.exam_id = e.id WHERE es.user_id = ? ORDER BY es.id DESC");
    $stmt->execute([$user['id']]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Session Ujian</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 20px; }
    .container { max-width: 700px; margin: 40px auto; }
    h2 { color: #1e293b; margin-bottom: 8px; }
    .subtitle { color: #64748b; margin-bottom: 24px; }
    .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-weight: 600; color: #374151; margin-bottom: 6px; }
    input[type=text], input[type=email] { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
    .btn { padding: 10px 24px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-sm { padding: 6px 14px; font-size: 0.875rem; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
    th { background: #f8fafc; font-weight: 600; color: #374151; }
    .badge { padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .badge-active { background: #d1fae5; color: #065f46; }
    .badge-submitted { background: #fef3c7; color: #92400e; }
    .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
    .alert-success { background: #d1fae5; color: #065f46; }
    .alert-error { background: #fee2e2; color: #991b1b; }
    .student-info { background: #eff6ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: #1e40af; font-weight: 600; }
</style>
</head>
<body>
<div class="container">
    <h2>🔄 Reset Session Ujian Siswa</h2>
    <p class="subtitle">Masukkan email siswa untuk melihat dan mereset session ujiannya.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['type'] ?>"><?= $message['text'] ?></div>
    <?php endif; ?>

    <!-- Form cari siswa -->
    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label>Email Siswa:</label>
                <input type="text" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="contoh: siswa@gmail.com" autofocus>
            </div>
            <button type="submit" class="btn btn-primary">🔍 Cari Siswa</button>
        </form>
    </div>

    <?php if ($email && !$user): ?>
        <div class="alert alert-error">❌ Email <b><?= htmlspecialchars($email) ?></b> tidak ditemukan di database CAT.</div>
    <?php endif; ?>

    <?php if ($user): ?>
        <div class="student-info">👤 <?= htmlspecialchars($user['full_name']) ?> &nbsp;|&nbsp; <?= htmlspecialchars($email) ?></div>

        <?php if (empty($sessions)): ?>
            <div class="card"><p style="color:#64748b">Tidak ada session ujian ditemukan untuk siswa ini.</p></div>
        <?php else: ?>
        <div class="card">
            <table>
                <tr>
                    <th>Ujian</th>
                    <th>Status</th>
                    <th>Waktu Submit</th>
                    <th>Nilai</th>
                    <th>Aksi</th>
                </tr>
                <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['title']) ?></td>
                    <td>
                        <span class="badge <?= $s['submitted_at'] ? 'badge-submitted' : 'badge-active' ?>">
                            <?= $s['submitted_at'] ? 'Submitted' : ucfirst($s['status']) ?>
                        </span>
                    </td>
                    <td><?= $s['submitted_at'] ? date('d M, H:i', strtotime($s['submitted_at'])) : '-' ?></td>
                    <td><?= number_format($s['score'], 0) ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Reset ujian ini? Semua jawaban akan dihapus dan siswa mulai dari awal.')">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                            <input type="hidden" name="reset_session_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Reset</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
