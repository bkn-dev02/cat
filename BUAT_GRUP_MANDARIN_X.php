<?php
/**
 * BUAT GRUP SOAL — SAT Mandarin Kelas X Genap
 * Ambil soal dari ujian SAT MANDARIN KELAS X GENAP
 * lalu masukkan ke grup soal baru
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Cari ujian SAT Mandarin Kelas X Genap
$exam = $pdo->query("
    SELECT id, title FROM exams
    WHERE title LIKE '%Mandarin%' AND title LIKE '%X%'
    ORDER BY id DESC
    LIMIT 5
")->fetchAll();

// Cari guru
$guru = $pdo->query("SELECT id FROM users WHERE username LIKE '%wara%' LIMIT 1")->fetch();
if (!$guru) $guru = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
$guruId = $guru['id'];

// Cari subject Mandarin
$subj = $pdo->query("SELECT id FROM subjects WHERE name LIKE '%Mandarin%' LIMIT 1")->fetch();
$subjectId = $subj['id'];

$selectedExamId = $_GET['exam_id'] ?? null;
$action = $_GET['action'] ?? 'preview';

// Ambil soal dari ujian yang dipilih
$soal = [];
if ($selectedExamId) {
    $soal = $pdo->query("
        SELECT q.id, q.question_text, q.correct_answer, q.group_id
        FROM exam_questions eq
        JOIN questions q ON q.id = eq.question_id
        WHERE eq.exam_id = $selectedExamId
        ORDER BY eq.order_num ASC
    ")->fetchAll();
}

if ($action === 'buat' && $selectedExamId && !empty($soal)) {
    $pdo->beginTransaction();
    try {
        // Cek apakah grup sudah ada
        $cekGrup = $pdo->prepare("SELECT id FROM question_groups WHERE name = ? AND created_by = ?");
        $cekGrup->execute(['SAT Mandarin Kelas X Genap', $guruId]);
        $grupAda = $cekGrup->fetch();

        if ($grupAda) {
            $groupId = $grupAda['id'];
            $grupBaru = false;
        } else {
            $pdo->prepare("
                INSERT INTO question_groups (name, description, subject_id, type, sub_topic, default_points, created_by, created_at)
                VALUES (?, ?, ?, 'multiple_choice', 'SAT Semester Genap', 1, ?, NOW())
            ")->execute([
                'SAT Mandarin Kelas X Genap',
                'Kumpulan soal SAT Bahasa Mandarin Kelas X Semester Genap.',
                $subjectId,
                $guruId
            ]);
            $groupId = $pdo->lastInsertId();
            $grupBaru = true;
        }

        // Masukkan semua soal ke grup
        $upd = $pdo->prepare("UPDATE questions SET group_id = ? WHERE id = ?");
        $count = 0;
        foreach ($soal as $q) {
            $upd->execute([$groupId, $q['id']]);
            $count++;
        }

        $pdo->commit();
        $success = ['groupId' => $groupId, 'baru' => $grupBaru, 'total' => $count];

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Buat Grup Soal Mandarin X</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 820px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
h2 { color: #1e293b; margin-bottom: 12px; font-size: 1.1rem; }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; margin-bottom: 16px; color: #166534; line-height: 2; }
.error-box   { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; margin-bottom: 16px; color: #991b1b; }
.info-box    { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; color: #1e40af; font-size: .9rem; line-height: 1.8; }
.warn-box    { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 12px 16px; color: #92400e; font-size:.9rem; margin-bottom:14px; }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
tr:nth-child(even) { background: #f8fafc; }
.jwb { font-weight: 700; color: #16a34a; background: #dcfce7; border-radius: 4px; padding: 1px 7px; font-size: .8rem; }
.already { color: #f59e0b; font-size: .78rem; }
.btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; font-size: .95rem; text-decoration: none; margin-top: 14px; margin-right: 8px; }
.btn-blue  { background: #2563eb; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
.btn-green { background: #16a34a; color: white; }
.exam-btn  { display:inline-block; padding:10px 18px; border-radius:8px; background:#6366f1; color:white; font-weight:600; text-decoration:none; margin:4px 4px 4px 0; font-size:.9rem; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  <b style="font-size:1.15rem">✅ Grup Soal Berhasil Dibuat!</b><br>
  Nama Grup: <b>SAT Mandarin Kelas X Genap</b><br>
  ID Grup: <b><?= $success['groupId'] ?></b><br>
  Total soal: <b><?= $success['total'] ?> soal</b><br>
  Status: <?= $success['baru'] ? '🆕 Grup baru dibuat' : '✅ Ditambahkan ke grup yang sudah ada' ?>
</div>
<div class="info-box">
  <b>Cara menambah soal baru ke grup ini:</b><br>
  1. Buka menu <b>Grup Soal</b> di navbar<br>
  2. Klik grup <b>"SAT Mandarin Kelas X Genap"</b><br>
  3. Klik <b>"+ Tambah Soal"</b> — subject & tipe sudah otomatis terisi
</div>
<a href="questionGroup/show/<?= $success['groupId'] ?>" class="btn btn-green">→ Buka Grup Soal</a>
<a href="questionGroup" class="btn btn-gray">→ Daftar Grup Soal</a>

<?php elseif (isset($error)): ?>
<div class="error-box">❌ Error: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview' || isset($error)): ?>

<?php if (!$selectedExamId): ?>
<!-- STEP 1: Pilih ujian -->
<div class="card">
  <h2>📁 Buat Grup Soal — SAT Mandarin Kelas X Genap</h2>
  <p style="color:#475569;font-size:.9rem;margin-bottom:14px">Pilih ujian SAT Mandarin Kelas X yang soalnya ingin dimasukkan ke grup:</p>

  <?php if (empty($exam)): ?>
  <div class="warn-box">Tidak ada ujian SAT Mandarin Kelas X ditemukan di database!</div>
  <?php else: ?>
  <?php foreach ($exam as $e): ?>
  <a href="?exam_id=<?= $e['id'] ?>" class="exam-btn">
    📝 <?= htmlspecialchars($e['title']) ?> (ID: <?= $e['id'] ?>)
  </a>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- STEP 2: Konfirmasi soal -->
<div class="card">
  <h2>📁 Konfirmasi — Masukkan Soal ke Grup</h2>

  <?php
  $sudahAdaGrup = array_filter($soal, fn($q) => !empty($q['group_id']));
  if (!empty($sudahAdaGrup)):
  ?>
  <div class="warn-box">
    ⚠️ <?= count($sudahAdaGrup) ?> soal sudah ada di grup lain. Soal tersebut akan dipindahkan ke grup baru ini.
  </div>
  <?php endif; ?>

  <div class="info-box">
    <b><?= count($soal) ?> soal</b> dari ujian ini akan dimasukkan ke grup <b>"SAT Mandarin Kelas X Genap"</b>.
  </div>

  <div style="max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:4px">
  <table>
    <tr><th>#</th><th>ID</th><th>Pertanyaan</th><th>Jwb</th><th>Grup Saat Ini</th></tr>
    <?php foreach ($soal as $i => $q): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td style="color:#94a3b8"><?= $q['id'] ?></td>
      <td><?= htmlspecialchars(mb_substr(strip_tags($q['question_text']), 0, 75)) ?>...</td>
      <td><span class="jwb"><?= $q['correct_answer'] ?></span></td>
      <td><?= $q['group_id'] ? '<span class="already">Grup #'.$q['group_id'].'</span>' : '<span style="color:#94a3b8">-</span>' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>

  <a href="?exam_id=<?= $selectedExamId ?>&action=buat"
     class="btn btn-blue"
     onclick="return confirm('Buat grup dan masukkan <?= count($soal) ?> soal ke dalamnya?')">
    📁 BUAT GRUP SEKARANG
  </a>
  <a href="?" class="btn btn-gray">← Pilih Ujian Lain</a>
</div>
<?php endif; ?>

<?php endif; ?>

</div>
</body>
</html>
