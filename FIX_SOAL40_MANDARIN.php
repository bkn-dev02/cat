<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Cari soal No.40 yang rusak (berisi meta tag)
$rusak = $pdo->query("
    SELECT id, question_text, option_a, option_b, option_c, option_d, option_e, correct_answer
    FROM questions
    WHERE question_text LIKE '%meta%' OR question_text LIKE '%Content-Type%'
    ORDER BY id DESC
    LIMIT 5
")->fetchAll();

$action = $_GET['action'] ?? 'preview';

if ($action === 'fix') {
    // Cari ID soal yang rusak di bank soal Mandarin (subject_id=7, terbaru)
    $soalRusak = $pdo->query("
        SELECT id FROM questions
        WHERE subject_id = 7
          AND (question_text LIKE '%meta%' OR question_text LIKE '%Content-Type%')
        ORDER BY id DESC
        LIMIT 1
    ")->fetch();

    if ($soalRusak) {
        $pdo->prepare("
            UPDATE questions SET
                question_text  = 'Zhang Linghe membeli pensil dengan harga Rp.2500 dan membeli roti seharga Rp.5300. Total belanjaannya adalah…?',
                option_a       = '二千五百盾',
                option_b       = '五千三百盾',
                option_c       = '六千盾',
                option_d       = '七千八百盾',
                option_e       = '七万八千盾',
                correct_answer = 'D'
            WHERE id = ?
        ")->execute([$soalRusak['id']]);
        $fixedId = $soalRusak['id'];
        $success = true;
    } else {
        $error = 'Soal rusak tidak ditemukan!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Fix Soal 40 Mandarin</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 700px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 16px 20px; color: #166534; margin-bottom: 16px; }
.error-box   { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; color: #991b1b; margin-bottom: 16px; }
.warn-box    { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 12px 16px; color: #92400e; font-size:.9rem; margin-bottom:16px; }
table { width:100%; border-collapse:collapse; font-size:.85rem; }
th { background:#f1f5f9; padding:8px 10px; text-align:left; color:#475569; }
td { padding:7px 10px; border-bottom:1px solid #f1f5f9; color:#334155; word-break:break-all; }
.btn { display:inline-block; padding:10px 24px; border-radius:8px; font-weight:600; text-decoration:none; margin-top:12px; margin-right:8px; font-size:.95rem; }
.btn-red   { background:#dc2626; color:white; }
.btn-green { background:#16a34a; color:white; }
.btn-gray  { background:#e2e8f0; color:#475569; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  <b>✅ Soal No.40 berhasil diperbaiki! (ID: <?= $fixedId ?>)</b><br><br>
  Sekarang bunyinya:<br>
  <i>"Zhang Linghe membeli pensil dengan harga Rp.2500 dan membeli roti seharga Rp.5300. Total belanjaannya adalah…?"</i><br>
  Kunci jawaban: <b>D (七千八百盾)</b>
</div>
<a href="questionGroup" class="btn btn-green">→ Lihat Grup Soal</a>
<a href="exam" class="btn btn-gray">→ Daftar Ujian</a>

<?php elseif (isset($error)): ?>
<div class="error-box">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview' || isset($error)): ?>
<div class="card">
  <h2 style="margin-bottom:12px">🔧 Perbaiki Soal No.40 yang Rusak</h2>

  <?php if (!empty($rusak)): ?>
  <div class="warn-box">Soal berikut terdeteksi rusak (berisi kode HTML):</div>
  <table style="margin-bottom:16px">
    <tr><th>ID</th><th>Isi question_text (rusak)</th><th>Jwb</th></tr>
    <?php foreach ($rusak as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars(mb_substr($r['question_text'], 0, 120)) ?>...</td>
      <td><?= $r['correct_answer'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
  <div class="warn-box">Tidak ada soal rusak terdeteksi. Mungkin sudah diperbaiki sebelumnya.</div>
  <?php endif; ?>

  <p style="font-size:.9rem;color:#475569;margin-bottom:4px">Akan diganti dengan:</p>
  <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 16px;font-size:.9rem;color:#166534;margin-bottom:14px">
    <b>Pertanyaan:</b> Zhang Linghe membeli pensil dengan harga Rp.2500 dan membeli roti seharga Rp.5300. Total belanjaannya adalah…?<br>
    <b>A.</b> 二千五百盾 &nbsp; <b>B.</b> 五千三百盾 &nbsp; <b>C.</b> 六千盾 &nbsp; <b>D.</b> 七千八百盾 &nbsp; <b>E.</b> 七万八千盾<br>
    <b>Kunci: D</b>
  </div>

  <a href="?action=fix" class="btn btn-red"
     onclick="return confirm('Perbaiki soal No.40 yang rusak?')">
    🔧 PERBAIKI SEKARANG
  </a>
  <a href="exam" class="btn btn-gray">Batal</a>
</div>
<?php endif; ?>

</div>
</body>
</html>
