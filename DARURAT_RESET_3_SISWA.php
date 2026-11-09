<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$emails = [
    'almandabarusnaeltra@gmail.com',
    'smjksamuel@gmail.com',
    'dummy.ppdb2@binakasihnusantara.sch.id',
];

foreach ($emails as $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { echo "❌ $email tidak ditemukan<br>"; continue; }

    $stmt = $pdo->prepare("SELECT id FROM exam_sessions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $sessions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($sessions as $sid) {
        $pdo->prepare("DELETE FROM answers WHERE session_id = ?")->execute([$sid]);
        $pdo->prepare("DELETE FROM exam_sessions WHERE id = ?")->execute([$sid]);
    }
    echo "✅ $email — ".count($sessions)." session dihapus<br>";
}
echo "<br><b>Selesai! Minta semua siswa refresh browser lalu klik Mulai Ujian.</b>";
