<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/helpers.php';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Cek Struktur Sesi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body><div class="container py-4">
<h4>🔍 Diagnosa Struktur Database Sesi</h4>

<?php
// 1. Semua sesi yang belum submitted, berapa yang punya jawaban
$stmt = db()->query("
    SELECT 
        es.status,
        COUNT(DISTINCT es.id) as jumlah_sesi,
        COUNT(a.id) as total_jawaban
    FROM exam_sessions es
    LEFT JOIN answers a ON a.session_id = es.id
    GROUP BY es.status
    ORDER BY jumlah_sesi DESC
");
echo "<h6>1. Status semua sesi:</h6>";
echo "<table class='table table-sm table-bordered'><tr><th>Status</th><th>Jumlah Sesi</th><th>Total Jawaban</th></tr>";
foreach ($stmt->fetchAll() as $r) {
    echo "<tr><td>{$r['status']}</td><td>{$r['jumlah_sesi']}</td><td>{$r['total_jawaban']}</td></tr>";
}
echo "</table>";

// 2. Sesi belum submitted yang punya jawaban, per ujian
$stmt = db()->query("
    SELECT 
        e.title,
        e.id as exam_id,
        es.status,
        COUNT(DISTINCT es.id) as sesi_bermasalah,
        COUNT(a.id) as total_jawaban,
        -- cek apakah pakai exam_questions atau langsung questions.exam_id
        (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as pakai_exam_questions
    FROM exam_sessions es
    JOIN exams e ON e.id = es.exam_id
    LEFT JOIN answers a ON a.session_id = es.id
    WHERE es.status NOT IN ('submitted','graded','completed')
    GROUP BY e.id, e.title, es.status
    HAVING total_jawaban > 0
    ORDER BY e.title
");
$rows = $stmt->fetchAll();
echo "<h6>2. Sesi bermasalah per ujian (punya jawaban tapi belum submit):</h6>";
if (empty($rows)) {
    echo "<div class='alert alert-success'>Tidak ada sesi bermasalah!</div>";
} else {
    echo "<table class='table table-sm table-bordered'>";
    echo "<tr><th>Ujian</th><th>Status</th><th>Sesi Bermasalah</th><th>Total Jawaban</th><th>exam_questions count</th></tr>";
    foreach ($rows as $r) {
        echo "<tr>
            <td>{$r['title']}</td>
            <td><span class='badge bg-warning text-dark'>{$r['status']}</span></td>
            <td>{$r['sesi_bermasalah']}</td>
            <td>{$r['total_jawaban']}</td>
            <td class='".($r['pakai_exam_questions']>0?'table-success':'table-danger')."'>{$r['pakai_exam_questions']}</td>
        </tr>";
    }
    echo "</table>";
}

// 2b. Cek semua sesi belum submit TANPA filter jawaban
$stmt2 = db()->query("
    SELECT es.status, COUNT(*) as jumlah
    FROM exam_sessions es
    WHERE es.status NOT IN ('submitted','graded','completed')
    GROUP BY es.status
");
echo "<h6>2b. Semua sesi belum submit (tanpa filter jawaban):</h6>";
echo "<table class='table table-sm table-bordered'><tr><th>Status</th><th>Jumlah</th></tr>";
foreach ($stmt2->fetchAll() as $r) {
    echo "<tr><td>{$r['status']}</td><td>{$r['jumlah']}</td></tr>";
}
echo "</table>";

// 2c. Cek tabel answers - berapa yang session_id-nya ada di sesi belum submit
$stmt3 = db()->query("
    SELECT COUNT(DISTINCT a.session_id) as sesi_punya_jawaban, COUNT(a.id) as total_jawaban
    FROM answers a
    JOIN exam_sessions es ON es.id = a.session_id
    WHERE es.status NOT IN ('submitted','graded','completed')
");
$r3 = $stmt3->fetch();
echo "<h6>2c. Jawaban di sesi belum submit:</h6>";
echo "<p>Sesi yang punya jawaban: <strong>{$r3['sesi_punya_jawaban']}</strong> | Total jawaban: <strong>{$r3['total_jawaban']}</strong></p>";

// 3. Cek kolom di tabel questions
$cols = db()->query("DESCRIBE questions")->fetchAll();
echo "<h6>3. Kolom tabel questions:</h6>";
echo "<div class='d-flex flex-wrap gap-2'>";
foreach ($cols as $c) {
    echo "<span class='badge bg-secondary'>{$c['Field']} ({$c['Type']})</span>";
}
echo "</div><br>";

// 4. Sample sesi bermasalah
$stmt = db()->query("
    SELECT es.id, es.exam_id, es.status, u.full_name, u.kelas, e.title,
           COUNT(a.id) as jawaban
    FROM exam_sessions es
    JOIN users u ON u.id = es.user_id
    JOIN exams e ON e.id = es.exam_id
    LEFT JOIN answers a ON a.session_id = es.id
    WHERE es.status NOT IN ('submitted','graded','completed')
    GROUP BY es.id, es.exam_id, es.status, u.full_name, u.kelas, e.title
    HAVING jawaban > 0
    LIMIT 10
");
echo "<h6>4. Sample 10 sesi bermasalah:</h6>";
echo "<table class='table table-sm table-bordered'><tr><th>Session ID</th><th>Nama</th><th>Kelas</th><th>Ujian</th><th>Status</th><th>Jawaban</th></tr>";
foreach ($stmt->fetchAll() as $r) {
    echo "<tr><td>{$r['id']}</td><td>{$r['full_name']}</td><td>{$r['kelas']}</td><td>{$r['title']}</td><td>{$r['status']}</td><td>{$r['jawaban']}</td></tr>";
}
echo "</table>";
?>
</div></body></html>
