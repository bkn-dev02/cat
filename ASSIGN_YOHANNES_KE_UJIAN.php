<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Ambil user ID Yohannes
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'yohannes.felix@siswa.ubkn.sch.id'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { die("User tidak ditemukan"); }

// Ambil semua ujian yang terbatas untuk kelas X-TESLA
$stmt = $pdo->query("
    SELECT DISTINCT e.id, e.title 
    FROM exams e
    JOIN exam_participants ep ON ep.exam_id = e.id
    JOIN users u ON ep.user_id = u.id
    WHERE u.kelas = 'X-TESLA' AND e.status = 'published'
");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$added = 0;
$ins = $pdo->prepare("INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)");
foreach ($exams as $e) {
    $ins->execute([$e['id'], $user['id']]);
    $added += $ins->rowCount();
}

echo "<div style='font-family:sans-serif;padding:20px;max-width:500px;margin:40px auto;background:#d1fae5;border-radius:8px'>";
echo "<h3 style='color:#065f46'>✅ Selesai!</h3>";
echo "<p>Yohannes Felix Tamba ditambahkan ke <b>$added ujian</b> kelas X-TESLA.</p>";
echo "<p>Ujian yang tersedia:</p><ul>";
foreach ($exams as $e) echo "<li>{$e['title']}</li>";
echo "</ul></div>";
