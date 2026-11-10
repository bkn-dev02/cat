<?php
/**
 * FIX: Replace soal di ujian SAT MANDARIN XI GENAP (ID: 338)
 * dengan 40 soal baru yang kita masukkan (ID 5312-5351)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$examId = 338;

// Ambil semua soal yang sekarang ter-assign ke ujian 338
$existing = $pdo->prepare("
    SELECT eq.question_id, eq.order_num, q.question_text, q.created_at
    FROM exam_questions eq
    JOIN questions q ON q.id = eq.question_id
    WHERE eq.exam_id = ?
    ORDER BY eq.order_num ASC
");
$existing->execute([$examId]);
$currentSoal = $existing->fetchAll();

// Ambil 40 soal terbaru Bahasa Mandarin (yang baru kita insert: ID 5312-5351)
$newSoal = $pdo->prepare("
    SELECT id, question_text, correct_answer, created_at
    FROM questions
    WHERE subject_id = 7
    ORDER BY id DESC
    LIMIT 40
");
$newSoal->execute();
$soalBaru = array_reverse($newSoal->fetchAll()); // urutkan dari terkecil ke terbesar

$action = $_GET['action'] ?? 'preview';

if ($action === 'fix') {
    $pdo->beginTransaction();
    try {
        // Hapus semua assign soal lama
        $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?")->execute([$examId]);

        // Insert 40 soal baru
        $ins = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, order_num) VALUES (?, ?, ?)");
        foreach ($soalBaru as $i => $q) {
            $ins->execute([$examId, $q['id'], $i + 1]);
        }

        $pdo->commit();
        $success = true;
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
<title>Fix Assign Soal Mandarin XI</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 900px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
h2 { color: #1e293b; margin-bottom: 12px; font-size: 1.1rem; }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
tr:nth-child(even) { background: #f8fafc; }
.ok  { color: #16a34a; font-weight: 700; }
.bad { color: #dc2626; font-weight: 700; }
.old { color: #dc2626; }
.new { color: #16a34a; }
.warn-box { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; font-size: .9rem; color: #92400e; }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; font-size: .9rem; color: #166534; }
.error-box { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; font-size: .9rem; color: #991b1b; }
.btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; font-size: .95rem; text-decoration: none; margin-top: 12px; margin-right: 8px; }
.btn-red   { background: #dc2626; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
.btn-green { background: #16a34a; color: white; }
.cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  ✅ <b>Berhasil!</b> 40 soal baru sudah di-assign ke ujian SAT MANDARIN KELAS XI GENAP (ID: 338).
  Soal lama sudah dihapus dari ujian ini (soal lama tetap ada di bank soal, tidak dihapus).
  <br><br>
  <a href="exam/assignQuestions/338" class="btn btn-green">→ Lihat Kelola Soal Ujian 338</a>
  <a href="exam" class="btn btn-gray">→ Kembali ke Daftar Ujian</a>
</div>
<?php elseif (isset($error)): ?>
<div class="error-box">❌ Error: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview'): ?>

<div class="card">
  <h2>⚠️ Kondisi Saat Ini — Ujian SAT MANDARIN XI GENAP (ID: 338)</h2>
  <div class="warn-box">
    Ujian ini sekarang punya <b><?= count($currentSoal) ?> soal ter-assign</b>, 
    tapi bukan semua soal Anda. Klik <b>"GANTI SEKARANG"</b> untuk mengganti semua soal 
    dengan 40 soal baru yang baru diimport.
    <br><small style="opacity:.8">Catatan: soal lama tidak dihapus dari bank soal, hanya dilepas dari ujian ini.</small>
  </div>

  <div class="cols">
    <div>
      <div class="sec" style="font-weight:700;color:#dc2626;margin-bottom:8px">
        ❌ Soal LAMA (sekarang ter-assign) — <?= count($currentSoal) ?> soal
      </div>
      <table>
        <tr><th>#</th><th>Q ID</th><th>Pertanyaan</th><th>Dibuat</th></tr>
        <?php foreach ($currentSoal as $q): ?>
        <tr>
          <td><?= $q['order_num'] ?></td>
          <td class="old"><?= $q['question_id'] ?></td>
          <td><?= htmlspecialchars(mb_substr(strip_tags($q['question_text']), 0, 60)) ?>...</td>
          <td style="font-size:.78rem;color:#94a3b8"><?= substr($q['created_at'], 0, 10) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <div>
      <div class="sec" style="font-weight:700;color:#16a34a;margin-bottom:8px">
        ✅ Soal BARU (akan di-assign) — <?= count($soalBaru) ?> soal
      </div>
      <table>
        <tr><th>#</th><th>Q ID</th><th>Pertanyaan</th><th>Jwb</th></tr>
        <?php foreach ($soalBaru as $i => $q): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td class="new"><?= $q['id'] ?></td>
          <td><?= htmlspecialchars(mb_substr(strip_tags($q['question_text']), 0, 55)) ?>...</td>
          <td><b><?= $q['correct_answer'] ?></b></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div style="margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9">
    <a href="?action=fix" class="btn btn-red" 
       onclick="return confirm('Yakin ingin mengganti soal ujian 338 dengan 40 soal baru?')">
      🔄 GANTI SEKARANG — Replace dengan 40 Soal Baru
    </a>
    <a href="exam" class="btn btn-gray">Batal</a>
  </div>
</div>

<?php endif; ?>

</div>
</body>
</html>
