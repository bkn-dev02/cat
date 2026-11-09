<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Cek status ujian PPDB
$stmt = $pdo->query("SELECT id, title, status, category, start_time, end_time FROM exams WHERE category = 'ppdb' ORDER BY id");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Status Ujian PPDB</h3><table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Judul</th><th>Status</th><th>Mulai</th><th>Selesai</th><th>Peserta</th></tr>";
foreach ($exams as $e) {
    $stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT u.kelas) as jml_kelas, GROUP_CONCAT(DISTINCT u.kelas) as kelas FROM exam_participants ep JOIN users u ON ep.user_id = u.id WHERE ep.exam_id = ?");
    $stmt2->execute([$e['id']]);
    $p = $stmt2->fetch(PDO::FETCH_ASSOC);
    $peserta = $p['jml_kelas'] > 0 ? $p['kelas'] : '<span style="color:green">Semua Siswa</span>';
    $statusColor = $e['status'] === 'published' ? 'green' : 'red';
    echo "<tr>
        <td>{$e['id']}</td>
        <td>{$e['title']}</td>
        <td style='color:{$statusColor}'><b>{$e['status']}</b></td>
        <td>{$e['start_time']}</td>
        <td>{$e['end_time']}</td>
        <td>{$peserta}</td>
    </tr>";
}
echo "</table>";

// Cek jumlah siswa PPDB-REGULER
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE kelas = 'PPDB-REGULER' AND role = 'siswa'");
$total = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Total siswa kelas PPDB-REGULER: <b>{$total['total']}</b></p>";

// FIX DARURAT: Publish semua ujian PPDB dan set ke semua siswa
if (isset($_POST['fix'])) {
    $pdo->exec("UPDATE exams SET status = 'published' WHERE category = 'ppdb'");
    // Hapus pembatasan peserta (buka ke semua siswa PPDB-REGULER)
    $stmt = $pdo->query("SELECT id FROM exams WHERE category = 'ppdb'");
    $ppdbExams = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ppdbExams as $examId) {
        $pdo->prepare("DELETE FROM exam_participants WHERE exam_id = ?")->execute([$examId]);
        // Insert semua siswa PPDB-REGULER
        $stmt2 = $pdo->query("SELECT id FROM users WHERE kelas = 'PPDB-REGULER' AND role = 'siswa'");
        $students = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        $ins = $pdo->prepare("INSERT IGNORE INTO exam_participants (exam_id, user_id) VALUES (?, ?)");
        foreach ($students as $sid) { $ins->execute([$examId, $sid]); }
    }
    echo "<p style='color:green'><b>✅ Semua ujian PPDB sudah dipublish dan di-assign ke semua siswa PPDB-REGULER!</b></p>";
    echo "<script>location.reload();</script>";
}
?>
<br>
<form method="POST">
    <button name="fix" value="1" style="background:#e53e3e;color:white;padding:12px 24px;border:none;border-radius:6px;font-size:1rem;cursor:pointer">
        🚨 FIX DARURAT: Publish + Assign semua ujian PPDB ke PPDB-REGULER
    </button>
</form>
