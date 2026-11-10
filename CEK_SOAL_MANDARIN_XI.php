<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Cari subject Bahasa Mandarin
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE name LIKE '%Mandarin%' OR name LIKE '%mandarin%'")->fetchAll();

// Cari ujian SAT Mandarin XI
$exams = $pdo->query("SELECT id, title, status FROM exams WHERE title LIKE '%Mandarin%' AND title LIKE '%XI%'")->fetchAll();

// Hitung soal mandarin yang ada
$soalInfo = [];
foreach ($subjects as $s) {
    $cnt = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE subject_id = ?");
    $cnt->execute([$s['id']]);
    $soalInfo[] = ['subject' => $s['name'], 'id' => $s['id'], 'total' => $cnt->fetch()['total']];
}

// Cek soal yang sudah di-assign ke ujian XI
$assignedInfo = [];
foreach ($exams as $e) {
    $cnt2 = $pdo->prepare("SELECT COUNT(*) as total FROM exam_questions WHERE exam_id = ?");
    $cnt2->execute([$e['id']]);
    $assignedInfo[] = ['exam' => $e['title'], 'exam_id' => $e['id'], 'status' => $e['status'], 'assigned' => $cnt2->fetch()['total']];
}

// Ambil 5 soal terbaru di bank soal Mandarin
$latestSoal = [];
foreach ($subjects as $s) {
    $q = $pdo->prepare("SELECT id, question_text, correct_answer, created_at FROM questions WHERE subject_id = ? ORDER BY id DESC LIMIT 5");
    $q->execute([$s['id']]);
    $latestSoal = array_merge($latestSoal, $q->fetchAll());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cek Soal Mandarin XI</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.card { background: #fff; border-radius: 10px; padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
h2 { color: #1e293b; margin: 0 0 12px; font-size: 1.1rem; }
table { width: 100%; border-collapse: collapse; font-size: .88rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.ok  { color: #16a34a; font-weight: 700; }
.bad { color: #dc2626; font-weight: 700; }
</style>
</head>
<body>

<div class="card">
  <h2>📚 Subject Bahasa Mandarin di Database</h2>
  <table>
    <tr><th>ID</th><th>Nama Subject</th><th>Total Soal</th></tr>
    <?php foreach ($soalInfo as $s): ?>
    <tr>
      <td><?= $s['id'] ?></td>
      <td><?= htmlspecialchars($s['subject']) ?></td>
      <td class="<?= $s['total'] >= 40 ? 'ok' : 'bad' ?>"><?= $s['total'] ?> soal</td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($soalInfo)): ?>
    <tr><td colspan="3" class="bad">Tidak ada subject Mandarin ditemukan!</td></tr>
    <?php endif; ?>
  </table>
</div>

<div class="card">
  <h2>📝 Ujian SAT Mandarin XI di Database</h2>
  <table>
    <tr><th>ID</th><th>Judul Ujian</th><th>Status</th><th>Soal Ter-assign</th></tr>
    <?php foreach ($assignedInfo as $e): ?>
    <tr>
      <td><?= $e['exam_id'] ?></td>
      <td><?= htmlspecialchars($e['exam']) ?></td>
      <td><?= $e['status'] ?></td>
      <td class="<?= $e['assigned'] >= 40 ? 'ok' : 'bad' ?>"><?= $e['assigned'] ?> soal</td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($assignedInfo)): ?>
    <tr><td colspan="4" class="bad">Tidak ada ujian SAT Mandarin XI ditemukan!</td></tr>
    <?php endif; ?>
  </table>
</div>

<div class="card">
  <h2>🔍 5 Soal Terbaru di Bank Soal Mandarin</h2>
  <table>
    <tr><th>ID</th><th>Pertanyaan (potong 80 char)</th><th>Jwb</th><th>Dibuat</th></tr>
    <?php foreach ($latestSoal as $q): ?>
    <tr>
      <td><?= $q['id'] ?></td>
      <td><?= htmlspecialchars(mb_substr(strip_tags($q['question_text']), 0, 80)) ?>...</td>
      <td><b><?= $q['correct_answer'] ?></b></td>
      <td><?= $q['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($latestSoal)): ?>
    <tr><td colspan="4" class="bad">Belum ada soal Mandarin di bank soal!</td></tr>
    <?php endif; ?>
  </table>
</div>

<?php foreach ($exams as $e): ?>
<div class="card">
  <h2>🔗 Link Assign Soal — <?= htmlspecialchars($e['title']) ?> (ID: <?= $e['id'] ?>)</h2>
  <p style="font-size:.9rem;color:#475569">Klik link ini untuk assign soal ke ujian:</p>
  <a href="exam/assignQuestions/<?= $e['id'] ?>" 
     style="display:inline-block;background:#2563eb;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;margin-top:6px">
    → Kelola Soal Ujian ID <?= $e['id'] ?>
  </a>
</div>
<?php endforeach; ?>

</body>
</html>
