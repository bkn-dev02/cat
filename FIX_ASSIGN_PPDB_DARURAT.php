<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 1. Update semua siswa kelas X yang emailnya ada di PPDB ke PPDB-REGULER
$ppdb = new PDO('mysql:host='.$config['ppdb_db_host'].';dbname='.$config['ppdb_db_name'].';charset=utf8mb4',
    $config['ppdb_db_user'], $config['ppdb_db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$stmt = $ppdb->query("SELECT u.email FROM users u JOIN registrations r ON r.user_id = u.id WHERE r.jalur = 'reguler'");
$emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

$newPass = password_hash('Ppdb2026!', PASSWORD_DEFAULT);
$updated = 0;
foreach ($emails as $email) {
    $s = $pdo->prepare("UPDATE users SET kelas='PPDB-REGULER', password=? WHERE username=? AND role='siswa' AND kelas != 'PPDB-REGULER'");
    $s->execute([$newPass, $email]);
    $updated += $s->rowCount();
}

// 2. Assign semua siswa PPDB-REGULER ke semua ujian PPDB
$exams = $pdo->query("SELECT id FROM exams WHERE category='ppdb'")->fetchAll(PDO::FETCH_COLUMN);
$students = $pdo->query("SELECT id FROM users WHERE kelas='PPDB-REGULER' AND role='siswa'")->fetchAll(PDO::FETCH_COLUMN);

$assigned = 0;
$ins = $pdo->prepare("INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)");
foreach ($exams as $eid) {
    foreach ($students as $sid) {
        $ins->execute([$eid, $sid]);
        $assigned += $ins->rowCount();
    }
}

echo "<div style='font-family:sans-serif;max-width:500px;margin:40px auto;padding:20px;border:1px solid #e2e8f0;border-radius:8px'>";
echo "<h3 style='color:#10b981'>✅ Fix Darurat Selesai!</h3>";
echo "<p>$updated siswa diupdate ke kelas PPDB-REGULER</p>";
echo "<p>$assigned siswa di-assign ke ujian PPDB</p>";
echo "<p>Total siswa PPDB-REGULER: <b>".count($students)."</b></p>";
echo "<p>Total ujian PPDB: <b>".count($exams)."</b></p>";
echo "</div>";
