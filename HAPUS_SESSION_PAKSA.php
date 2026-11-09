<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$message = null;
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email) {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = ['type' => 'error', 'text' => "❌ Email <b>$email</b> tidak ditemukan."];
    } else {
        $stmt = $pdo->prepare("SELECT es.id FROM exam_sessions es JOIN exams e ON es.exam_id = e.id WHERE es.user_id = ? AND e.category = 'ppdb'");
        $stmt->execute([$user['id']]);
        $sessions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($sessions as $sid) {
            $pdo->prepare("DELETE FROM answers WHERE session_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM exam_sessions WHERE id = ?")->execute([$sid]);
        }

        $message = ['type' => 'success', 'text' => "✅ Berhasil! Semua session ujian PPDB untuk <b>{$user['full_name']}</b> dihapus (".count($sessions)." session).<br><br>Minta siswa: <b>refresh browser → Kembali ke Daftar Ujian → Mulai Ujian</b>"];
        $email = '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hapus Session Ujian PPDB</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 20px; }
    .container { max-width: 500px; margin: 60px auto; }
    h2 { color: #1e293b; }
    .subtitle { color: #64748b; margin-bottom: 24px; font-size: 0.95rem; }
    .card { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; }
    input[type=text] { width: 100%; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; box-sizing: border-box; margin-bottom: 16px; }
    button { width: 100%; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; }
    button:hover { background: #dc2626; }
    .alert { padding: 16px 18px; border-radius: 8px; margin-bottom: 20px; line-height: 1.6; }
    .alert-success { background: #d1fae5; color: #065f46; }
    .alert-error { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<div class="container">
    <h2>🗑️ Reset Session Ujian PPDB</h2>
    <p class="subtitle">Gunakan ini jika siswa tidak sengaja submit atau tidak bisa mulai ujian kembali.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['type'] ?>"><?= $message['text'] ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" onsubmit="return confirm('Hapus semua session ujian PPDB untuk siswa ini?')">
            <label>Email Siswa:</label>
            <input type="text" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="paste email siswa di sini..." autofocus required>
            <button type="submit">🔄 Reset Session Ujian</button>
        </form>
    </div>
</div>
</body>
</html>
