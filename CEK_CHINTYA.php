<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$email = 'chintyameilanindruru@gmail.com';

$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { echo "❌ User tidak ditemukan"; exit; }

echo "<style>body{font-family:sans-serif;padding:20px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:8px}th{background:#f1f5f9}</style>";
echo "<h2>Cek Data: {$user['full_name']}</h2>";

// Semua session termasuk yang sudah submitted
$stmt = $pdo->prepare("
    SELECT es.id, e.title, es.status, es.submitted_at, es.score, es.started_at,
           COUNT(a.id) as jml_jawaban
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    LEFT JOIN answers a ON a.session_id = es.id
    WHERE es.user_id = ?
    GROUP BY es.id ORDER BY es.id DESC
");
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "<p style='color:red'>❌ Tidak ada session sama sekali — data kemungkinan terhapus saat reset darurat.</p>";
} else {
    echo "<table><tr><th>ID</th><th>Ujian</th><th>Status</th><th>Mulai</th><th>Submit</th><th>Score</th><th>Jml Jawaban</th><th>Aksi</th></tr>";
    foreach ($rows as $r) {
        $bg = $r['jml_jawaban'] > 0 ? '#d1fae5' : '#fee2e2';
        echo "<tr style='background:$bg'>
            <td>{$r['id']}</td>
            <td>{$r['title']}</td>
            <td>{$r['status']}</td>
            <td>{$r['started_at']}</td>
            <td>".($r['submitted_at']??'-')."</td>
            <td><b>{$r['score']}</b></td>
            <td><b>{$r['jml_jawaban']}</b></td>
            <td>
                <a href='CEK_JAWABAN_EMMI.php?email=".urlencode($email)."&detail={$r['id']}'>Detail</a>
                ".($r['status']!=='submitted'&&$r['status']!=='completed'?"| <a href='CEK_JAWABAN_EMMI.php?email=".urlencode($email)."&forcesubmit={$r['id']}' onclick='return confirm(\"Force submit?\")'>Force Submit</a>":'')."
            </td>
        </tr>";
    }
    echo "</table>";
}
