<?php
$config = require __DIR__ . '/config.production.php';
$pdo = new PDO('mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$email = trim($_GET['email'] ?? $_POST['email'] ?? 'emmiyaginting775@gmail.com');

// Export Excel
if (isset($_GET['export']) && isset($_GET['sid'])) {
    $sid = (int)$_GET['sid'];
    $stmt = $pdo->prepare("SELECT u.full_name, u.username, e.title FROM exam_sessions es JOIN users u ON es.user_id=u.id JOIN exams e ON es.exam_id=e.id WHERE es.id=?");
    $stmt->execute([$sid]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT a.question_id, a.answer, q.correct_answer, q.points, q.question_text,
               CASE WHEN LOWER(a.answer)=LOWER(q.correct_answer) THEN 'BENAR' ELSE 'SALAH' END as hasil
        FROM answers a JOIN questions q ON a.question_id=q.id
        WHERE a.session_id=? ORDER BY a.id
    ");
    $stmt->execute([$sid]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $score = array_sum(array_map(fn($a) => $a['hasil']==='BENAR' ? $a['points'] : 0, $answers));

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="nilai_'.str_replace('@','_',$email).'_'.date('Ymd').'.xls"');
    echo "<table border='1'>";
    echo "<tr><th colspan='6'>Laporan Nilai Ujian</th></tr>";
    echo "<tr><td>Nama</td><td colspan='5'>{$info['full_name']}</td></tr>";
    echo "<tr><td>Email</td><td colspan='5'>{$info['username']}</td></tr>";
    echo "<tr><td>Ujian</td><td colspan='5'>{$info['title']}</td></tr>";
    echo "<tr><td>Total Nilai</td><td colspan='5'>$score</td></tr>";
    echo "<tr><th>No</th><th>Soal</th><th>Jawaban</th><th>Kunci</th><th>Poin</th><th>Hasil</th></tr>";
    foreach ($answers as $i => $a) {
        echo "<tr>
            <td>".($i+1)."</td>
            <td>".substr(strip_tags($a['question_text']),0,100)."</td>
            <td>{$a['answer']}</td>
            <td>{$a['correct_answer']}</td>
            <td>{$a['points']}</td>
            <td>{$a['hasil']}</td>
        </tr>";
    }
    echo "</table>";
    exit;
}

$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE username = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Force submit
if (isset($_GET['forcesubmit'])) {
    $sid = (int)$_GET['forcesubmit'];
    $stmt = $pdo->prepare("SELECT SUM(CASE WHEN LOWER(a.answer)=LOWER(q.correct_answer) THEN q.points ELSE 0 END) as total FROM answers a JOIN questions q ON a.question_id=q.id WHERE a.session_id=?");
    $stmt->execute([$sid]);
    $score = $stmt->fetchColumn() ?? 0;
    $pdo->prepare("UPDATE exam_sessions SET status='submitted', submitted_at=NOW(), score=?, is_best_score=1 WHERE id=?")->execute([$score, $sid]);
    echo "<script>alert('✅ Force submit berhasil! Nilai: $score'); window.location='?email=".urlencode($email)."';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Investigasi Jawaban Siswa</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#f8fafc;padding:20px}
.container{max-width:1000px;margin:0 auto}
h2{color:#1e293b}
table{border-collapse:collapse;width:100%;background:white;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1)}
td,th{border:1px solid #e2e8f0;padding:10px;font-size:13px}
th{background:#f1f5f9;font-weight:600}
.ok{color:#065f46;background:#d1fae5}
.err{color:#991b1b;background:#fee2e2}
.btn{padding:6px 14px;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;text-decoration:none;display:inline-block}
.btn-red{background:#ef4444;color:white}
.btn-green{background:#10b981;color:white}
.btn-blue{background:#6366f1;color:white}
.search-box{background:white;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);margin-bottom:20px}
input[type=text]{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:1rem;box-sizing:border-box;margin-bottom:10px}
</style>
</head>
<body>
<div class="container">
<h2>🔍 Investigasi & Recovery Nilai Siswa</h2>

<div class="search-box">
    <form method="GET">
        <label style="font-weight:600">Email Siswa:</label>
        <input type="text" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="email@gmail.com">
        <button type="submit" class="btn btn-blue">Cari</button>
    </form>
</div>

<?php if (!$user): ?>
    <p style="color:red">❌ Email tidak ditemukan: <?= htmlspecialchars($email) ?></p>
<?php else: ?>

<h3>Siswa: <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($email) ?>)</h3>

<?php
$stmt = $pdo->prepare("
    SELECT es.id, e.title, es.status, es.submitted_at, es.score, es.started_at,
           COUNT(a.id) as jumlah_jawaban
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    LEFT JOIN answers a ON a.session_id = es.id
    WHERE es.user_id = ?
    GROUP BY es.id
    ORDER BY es.id DESC
");
$stmt->execute([$user['id']]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table>
<tr><th>Session ID</th><th>Ujian</th><th>Status</th><th>Mulai</th><th>Submit</th><th>Score</th><th>Jml Jawaban</th><th>Aksi</th></tr>
<?php foreach ($sessions as $s): ?>
<tr style="background:<?= $s['jumlah_jawaban']>0 ? '#f0fdf4' : '#fff' ?>">
    <td><?= $s['id'] ?></td>
    <td><?= htmlspecialchars($s['title']) ?></td>
    <td><?= $s['status'] ?></td>
    <td><?= $s['started_at'] ?></td>
    <td><?= $s['submitted_at'] ?? '-' ?></td>
    <td><b><?= $s['score'] ?></b></td>
    <td><b><?= $s['jumlah_jawaban'] ?></b></td>
    <td style="white-space:nowrap">
        <a href="?email=<?= urlencode($email) ?>&detail=<?= $s['id'] ?>" class="btn btn-blue">Detail</a>
        <?php if ($s['status'] !== 'submitted' && $s['status'] !== 'completed'): ?>
        <a href="?email=<?= urlencode($email) ?>&forcesubmit=<?= $s['id'] ?>" class="btn btn-red" onclick="return confirm('Force submit session ini?')">Force Submit</a>
        <?php endif; ?>
        <?php if ($s['jumlah_jawaban'] > 0): ?>
        <a href="?email=<?= urlencode($email) ?>&export=1&sid=<?= $s['id'] ?>" class="btn btn-green">⬇ Excel</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php if (isset($_GET['detail'])): 
    $sid = (int)$_GET['detail'];
    $stmt = $pdo->prepare("
        SELECT a.question_id, a.answer, q.correct_answer, q.points, q.question_text,
               CASE WHEN LOWER(a.answer)=LOWER(q.correct_answer) THEN 'BENAR' ELSE 'SALAH' END as hasil
        FROM answers a JOIN questions q ON a.question_id=q.id
        WHERE a.session_id=? ORDER BY a.id
    ");
    $stmt->execute([$sid]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $score = array_sum(array_map(fn($a) => $a['hasil']==='BENAR' ? $a['points'] : 0, $answers));
?>
<h3 style="margin-top:24px">Detail Jawaban Session <?= $sid ?> — Total Nilai: <b><?= $score ?></b></h3>
<?php if (empty($answers)): ?>
    <p style="color:red">Tidak ada jawaban tersimpan.</p>
<?php else: ?>
<p><a href="?email=<?= urlencode($email) ?>&export=1&sid=<?= $sid ?>" class="btn btn-green">⬇ Download Excel</a></p>
<table>
<tr><th>#</th><th>Soal</th><th>Jawaban</th><th>Kunci</th><th>Poin</th><th>Hasil</th></tr>
<?php foreach ($answers as $i => $a): $benar = $a['hasil']==='BENAR'; ?>
<tr>
    <td><?= $i+1 ?></td>
    <td style="max-width:350px;font-size:12px"><?= substr(strip_tags($a['question_text']),0,100) ?>...</td>
    <td><b><?= $a['answer'] ?></b></td>
    <td><?= $a['correct_answer'] ?></td>
    <td><?= $a['points'] ?></td>
    <td class="<?= $benar?'ok':'err' ?>"><?= $a['hasil'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>

<?php endif; ?>
</div>
</body>
</html>
