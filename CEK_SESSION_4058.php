<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Cek session terbaru untuk ujian PPDB
$stmt = $pdo->query("
    SELECT es.id, es.user_id, es.exam_id, es.status, es.submitted_at, es.score, es.started_at,
           u.username, e.title
    FROM exam_sessions es
    JOIN users u ON es.user_id = u.id
    JOIN exams e ON es.exam_id = e.id
    WHERE e.category = 'ppdb'
    ORDER BY es.id DESC
    LIMIT 20
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<style>body{font-family:sans-serif;padding:20px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:8px;font-size:13px}</style>";
echo "<h3>Session PPDB Terbaru</h3>";
echo "<table><tr><th>ID</th><th>User</th><th>Ujian</th><th>Status</th><th>submitted_at</th><th>Score</th><th>Aksi</th></tr>";
foreach ($rows as $r) {
    $color = $r['submitted_at'] ? '#fee2e2' : '#d1fae5';
    echo "<tr style='background:$color'>
        <td>{$r['id']}</td>
        <td>{$r['username']}</td>
        <td>{$r['title']}</td>
        <td>{$r['status']}</td>
        <td>".($r['submitted_at'] ?? '-')."</td>
        <td>{$r['score']}</td>
        <td><a href='?hapus={$r['id']}' onclick='return confirm(\"Hapus?\")'>Hapus</a></td>
    </tr>";
}
echo "</table>";

// Hapus session jika diminta
if (isset($_GET['hapus'])) {
    $sid = (int)$_GET['hapus'];
    $pdo->prepare("DELETE FROM answers WHERE session_id = ?")->execute([$sid]);
    $pdo->prepare("DELETE FROM exam_sessions WHERE id = ?")->execute([$sid]);
    echo "<p style='color:green'>✅ Session $sid dihapus. <a href='CEK_SESSION_4058.php'>Refresh</a></p>";
}
