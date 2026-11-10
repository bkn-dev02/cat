<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$emails = [
    'almandabarusnaeltra@gmail.com',
    'smjksamuel@gmail.com',
    'dummy.ppdb2@binakasihnusantara.sch.id',
    'mercycans0.1@gmail.com',
    'geovanylumbangaol9@gmail.com',
    'edreytarigan@gmail.com',
    'chintyameilanindruru@gmail.com',
    'josehartono11@gmail.com',
];

echo "<style>body{font-family:sans-serif;padding:20px;max-width:600px;margin:auto}</style>";
echo "<h2>🔧 Fix Darurat Session</h2>";

foreach ($emails as $email) {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { echo "<p style='color:red'>❌ $email tidak ditemukan</p>"; continue; }

    // Hapus SEMUA session user ini
    $stmt = $pdo->prepare("SELECT id FROM exam_sessions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $sids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($sids as $sid) {
        $pdo->prepare("DELETE FROM answers WHERE session_id = ?")->execute([$sid]);
    }
    $pdo->prepare("DELETE FROM exam_sessions WHERE user_id = ?")->execute([$user['id']]);
    
    echo "<p style='color:green'>✅ <b>{$user['full_name']}</b> ($email) — ".count($sids)." session dihapus</p>";
}

echo "<hr>";
echo "<h3>Langkah untuk siswa:</h3>";
echo "<ol>";
echo "<li>Buka browser baru / tab baru</li>";
echo "<li>Ketik URL ini: <b>cat-enterprise.binakasihnusantara.sch.id/exam/start/80</b></li>";
echo "<li>Ujian akan langsung dimulai</li>";
echo "</ol>";
echo "<p style='background:#fef3c7;padding:12px;border-radius:6px'>⚠️ Jangan pakai tombol back atau refresh — harus ketik URL baru</p>";
