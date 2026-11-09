<?php
require_once 'app/helpers.php';

$pdo = db();

echo "<h2>CEK SUBJECTS / MAPEL TPS</h2>";
echo "<style>body{font-family:Arial;padding:20px} table{border-collapse:collapse;width:100%} th,td{border:1px solid #ccc;padding:8px 12px} th{background:#1e293b;color:#fff} tr:nth-child(even){background:#f8fafc} .ok{color:green;font-weight:bold} .no{color:red;font-weight:bold}</style>";

// 1. Semua subjects yang ada
echo "<h3>1. Semua Subjects di Database:</h3>";
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY id");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($subjects)) {
    echo "<p class='no'>Tabel subjects KOSONG!</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Kolom lainnya</th></tr>";
    foreach ($subjects as $s) {
        echo "<tr>";
        echo "<td>{$s['id']}</td>";
        echo "<td><b>{$s['name']}</b></td>";
        // tampilkan kolom lain
        $extra = array_diff_key($s, ['id' => 1, 'name' => 1]);
        echo "<td><small>" . htmlspecialchars(json_encode($extra, JSON_UNESCAPED_UNICODE)) . "</small></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Cek apakah mapel TPS ada
echo "<h3>2. Cek Mapel TPS (PU, PK, PM, PPU, PBM, LBI, LBE):</h3>";
$tpsMapel = ['PU', 'PK', 'PM', 'PPU', 'PBM', 'LBI', 'LBE'];
$namaLengkap = [
    'PU'  => 'Penalaran Umum',
    'PK'  => 'Pengetahuan dan Pemahaman Umum',
    'PM'  => 'Pemahaman Bacaan dan Menulis',
    'PPU' => 'Pengetahuan Kuantitatif / PPU',
    'PBM' => 'Pemahaman Bacaan dan Menulis',
    'LBI' => 'Literasi Bahasa Indonesia',
    'LBE' => 'Literasi Bahasa Inggris',
];

echo "<table>";
echo "<tr><th>Kode</th><th>Nama Lengkap</th><th>Status di DB</th><th>ID (jika ada)</th></tr>";
foreach ($tpsMapel as $kode) {
    // Cari dengan LIKE untuk fleksibel
    $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE name LIKE ? OR name LIKE ? OR name = ?");
    $stmt->execute(["%$kode%", "%" . $namaLengkap[$kode] . "%", $kode]);
    $found = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $status = empty($found) ? "<span class='no'>❌ Tidak ada</span>" : "<span class='ok'>✅ Ada</span>";
    $idInfo = empty($found) ? '-' : implode(', ', array_map(fn($f) => "ID:{$f['id']} ({$f['name']})", $found));
    
    echo "<tr>";
    echo "<td><b>$kode</b></td>";
    echo "<td>{$namaLengkap[$kode]}</td>";
    echo "<td>$status</td>";
    echo "<td>$idInfo</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Cek struktur tabel subjects
echo "<h3>3. Struktur Tabel Subjects:</h3>";
$stmt = $pdo->query("DESCRIBE subjects");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($cols as $c) {
    echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Key']}</td><td>{$c['Default']}</td></tr>";
}
echo "</table>";

// 4. Cek apakah ada kolom kategori/kode di subjects
echo "<h3>4. Apakah ada kolom 'code' atau 'category' di subjects?</h3>";
$colNames = array_column($cols, 'Field');
$hasCode = in_array('code', $colNames);
$hasCategory = in_array('category', $colNames);
echo "<p>Kolom <b>code</b>: " . ($hasCode ? "<span class='ok'>Ada ✅</span>" : "<span class='no'>Tidak ada ❌</span>") . "</p>";
echo "<p>Kolom <b>category</b>: " . ($hasCategory ? "<span class='ok'>Ada ✅</span>" : "<span class='no'>Tidak ada ❌</span>") . "</p>";

// 5. Sample soal yang terhubung ke ujian TPS (jika ada exam dengan kata TPS)
echo "<h3>5. Cek Ujian yang Mengandung Kata 'TPS':</h3>";
$stmt = $pdo->query("SELECT e.id, e.title, s.name as subject_name, COUNT(eq.question_id) as jumlah_soal 
                     FROM exams e 
                     LEFT JOIN subjects s ON e.subject_id = s.id
                     LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                     WHERE e.title LIKE '%TPS%' OR e.title LIKE '%PU%' OR e.title LIKE '%UTBK%'
                     GROUP BY e.id, e.title, s.name
                     LIMIT 10");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($exams)) {
    echo "<p style='color:#888'>Tidak ditemukan ujian dengan kata TPS/PU/UTBK.</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Judul Ujian</th><th>Mapel</th><th>Jumlah Soal</th></tr>";
    foreach ($exams as $e) {
        echo "<tr><td>{$e['id']}</td><td>{$e['title']}</td><td>{$e['subject_name']}</td><td>{$e['jumlah_soal']}</td></tr>";
    }
    echo "</table>";
}

echo "<br><hr><p><b>Total subjects:</b> " . count($subjects) . "</p>";
echo "<p><a href='/cat-v2/dashboard'>← Kembali ke Dashboard</a></p>";
