<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Tampilkan semua subjects dulu
if (!isset($_POST['confirm'])) {
    $stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<style>body{font-family:sans-serif;padding:20px;max-width:800px;margin:auto}
    table{border-collapse:collapse;width:100%}td,th{border:1px solid #e2e8f0;padding:8px;font-size:13px}
    th{background:#f1f5f9}.dup{background:#fee2e2}.keep{background:#d1fae5}
    button{background:#ef4444;color:white;padding:10px 20px;border:none;border-radius:6px;cursor:pointer;margin-top:16px}</style>";
    
    echo "<h2>Daftar Subjects (Total: ".count($subjects).")</h2>";
    echo "<p>Subjects yang akan <b>DIHAPUS</b> (merah) — duplikat/tidak standar. Yang <b>DIPERTAHANKAN</b> (hijau).</p>";
    
    // Kelompokkan berdasarkan nama yang mirip
    $keepIds = [
        // ID yang akan dipertahankan (nama paling bersih)
        // Akan ditentukan otomatis: ambil yang punya description atau yang paling lama
    ];
    
    // Ambil ID yang akan dipertahankan per nama unik (ambil yang terlama/terkecil ID)
    $stmt = $pdo->query("
        SELECT MIN(id) as keep_id, LOWER(TRIM(name)) as norm_name
        FROM subjects 
        GROUP BY LOWER(TRIM(name))
    ");
    $keepMap = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $keepMap[$row['keep_id']] = $row['norm_name'];
    }
    $keepIds = array_keys($keepMap);
    
    echo "<table><tr><th>ID</th><th>Nama</th><th>Status</th></tr>";
    foreach ($subjects as $s) {
        $isKeep = in_array($s['id'], $keepIds);
        $class = $isKeep ? 'keep' : 'dup';
        $status = $isKeep ? '✅ Pertahankan' : '❌ Hapus (duplikat)';
        echo "<tr class='$class'><td>{$s['id']}</td><td>{$s['name']}</td><td>$status</td></tr>";
    }
    echo "</table>";
    
    $dupCount = count($subjects) - count($keepIds);
    echo "<br><p>Akan menghapus <b>$dupCount duplikat</b> dan mempertahankan <b>".count($keepIds)." subjects</b>.</p>";
    echo "<form method='POST'><button type='submit' name='confirm' value='1'>🗑️ Hapus Duplikat Sekarang</button></form>";
    exit;
}

// Proses hapus duplikat
$stmt = $pdo->query("
    SELECT MIN(id) as keep_id, LOWER(TRIM(name)) as norm_name
    FROM subjects 
    GROUP BY LOWER(TRIM(name))
");
$keepIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Update questions yang pakai subject duplikat ke subject yang dipertahankan
$stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY id");
$allSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$deleted = 0;
foreach ($allSubjects as $s) {
    if (!in_array($s['id'], $keepIds)) {
        // Cari keep_id untuk nama yang sama
        $stmt2 = $pdo->prepare("SELECT MIN(id) FROM subjects WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))");
        $stmt2->execute([$s['name']]);
        $keepId = $stmt2->fetchColumn();
        
        if ($keepId && $keepId != $s['id']) {
            // Update referensi
            $pdo->prepare("UPDATE questions SET subject_id = ? WHERE subject_id = ?")->execute([$keepId, $s['id']]);
            $pdo->prepare("UPDATE users SET subject_id = ? WHERE subject_id = ?")->execute([$keepId, $s['id']]);
            $pdo->prepare("UPDATE exams SET subject_id = ? WHERE subject_id = ?")->execute([$keepId, $s['id']]);
            $pdo->prepare("UPDATE exam_subjects SET subject_id = ? WHERE subject_id = ?")->execute([$keepId, $s['id']]);
            // Hapus duplikat
            $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([$s['id']]);
            $deleted++;
        }
    }
}

echo "<div style='font-family:sans-serif;padding:30px;max-width:500px;margin:40px auto;background:#d1fae5;border-radius:8px'>";
echo "<h3 style='color:#065f46'>✅ Selesai!</h3>";
echo "<p>$deleted subjects duplikat berhasil dihapus.</p>";
echo "<p><a href='admin/guruMapel'>Kembali ke Assign Mapel Guru</a></p>";
echo "</div>";
