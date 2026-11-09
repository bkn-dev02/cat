<?php
/**
 * HAPUS SOAL DUPLIKAT MANDARIN XI
 * Hapus soal ID 5312-5351 (duplikat yang baru diimport 2026-06-07)
 * Soal asli ID 4084-4158 (2026-05-19) tetap aman & ter-assign ke ujian 338
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ID duplikat yang akan dihapus
$dupIds = range(5312, 5351); // 40 soal duplikat

$action = $_GET['action'] ?? 'preview';

if ($action === 'hapus') {
    $pdo->beginTransaction();
    try {
        $placeholders = implode(',', $dupIds);

        // Pastikan tidak ada yang ter-assign ke ujian manapun
        $cekAssign = $pdo->query("SELECT COUNT(*) as c FROM exam_questions WHERE question_id IN ($placeholders)")->fetch();

        if ($cekAssign['c'] > 0) {
            // Lepas dari semua ujian dulu
            $pdo->exec("DELETE FROM exam_questions WHERE question_id IN ($placeholders)");
        }

        // Hapus dari answers jika ada
        $pdo->exec("DELETE FROM answers WHERE question_id IN ($placeholders)");

        // Hapus soalnya
        $deleted = $pdo->exec("DELETE FROM questions WHERE id IN ($placeholders)");

        $pdo->commit();
        $success = true;
        $deletedCount = $deleted;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Preview: ambil data soal duplikat
$placeholders = implode(',', $dupIds);
$dupSoal = $pdo->query("
    SELECT id, question_text, correct_answer, created_at
    FROM questions
    WHERE id IN ($placeholders)
    ORDER BY id ASC
")->fetchAll();

$sisaCount = count($dupSoal);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Hapus Duplikat Soal Mandarin XI</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 800px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
h2 { color: #1e293b; margin-bottom: 12px; font-size: 1.1rem; }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
tr:nth-child(even) { background: #f8fafc; }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; color: #166534; }
.error-box   { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; color: #991b1b; }
.warn-box    { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; color: #92400e; font-size: .9rem; }
.btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; font-size: .95rem; text-decoration: none; margin-top: 12px; margin-right: 8px; }
.btn-red   { background: #dc2626; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
.btn-green { background: #16a34a; color: white; }
.done { color: #16a34a; font-weight: 700; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  <b style="font-size:1.1rem">✅ Berhasil! <?= $deletedCount ?> soal duplikat dihapus.</b><br><br>
  Soal ID 5312–5351 sudah dihapus dari bank soal.<br>
  Ujian <b>SAT MANDARIN KELAS XI GENAP (ID: 338)</b> tetap aman dengan 40 soal asli (ID 4084–4158).<br><br>
  <a href="exam" class="btn btn-green">→ Kembali ke Daftar Ujian</a>
  <a href="question" class="btn btn-gray">→ Lihat Bank Soal</a>
</div>
<?php elseif (isset($error)): ?>
<div class="error-box">❌ Error: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview' || isset($error)): ?>
<div class="card">
  <h2>🗑️ Konfirmasi Hapus Soal Duplikat (ID 5312–5351)</h2>

  <?php if ($sisaCount === 0): ?>
  <div class="success-box">✅ Soal duplikat sudah tidak ada. Bank soal sudah bersih!</div>
  <a href="exam" class="btn btn-green">→ Kembali ke Daftar Ujian</a>
  <?php else: ?>
  <div class="warn-box">
    Berikut <b><?= $sisaCount ?> soal duplikat</b> yang akan dihapus permanen dari bank soal.<br>
    Soal asli (ID 4084–4158) yang ter-assign ke ujian <b>tidak terpengaruh</b>.
  </div>

  <table>
    <tr><th>ID</th><th>Pertanyaan</th><th>Jwb</th><th>Dibuat</th></tr>
    <?php foreach ($dupSoal as $q): ?>
    <tr>
      <td style="color:#dc2626;font-weight:700"><?= $q['id'] ?></td>
      <td><?= htmlspecialchars(mb_substr(strip_tags($q['question_text']), 0, 75)) ?>...</td>
      <td><b><?= $q['correct_answer'] ?></b></td>
      <td style="font-size:.78rem;color:#94a3b8"><?= $q['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9">
    <a href="?action=hapus" class="btn btn-red"
       onclick="return confirm('Yakin hapus permanen 40 soal duplikat ID 5312-5351?')">
      🗑️ HAPUS PERMANEN 40 Soal Duplikat
    </a>
    <a href="exam" class="btn btn-gray">Batal</a>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
