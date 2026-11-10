<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Ambil SEMUA soal dari ujian 440 untuk lihat kondisi sebenarnya
$soalSemua = $pdo->query("
    SELECT eq.order_num, q.id, 
           SUBSTRING(q.question_text, 1, 80) as q_text,
           q.option_a, q.option_b, q.correct_answer
    FROM exam_questions eq
    JOIN questions q ON q.id = eq.question_id
    WHERE eq.exam_id = 440
    ORDER BY eq.order_num ASC
")->fetchAll();

$soal3840 = $pdo->query("
    SELECT eq.order_num, q.id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.option_e, q.correct_answer
    FROM exam_questions eq
    JOIN questions q ON q.id = eq.question_id
    WHERE eq.exam_id = 440
    AND eq.order_num IN (38, 40)
    ORDER BY eq.order_num ASC
")->fetchAll();

$action = $_GET['action'] ?? 'preview';

if ($action === 'fix') {
    // Cari ID soal no.40 (yang duplikat soal 38)
    $soal40 = null;
    foreach ($soal3840 as $s) {
        if ($s['order_num'] == 40) { $soal40 = $s; break; }
    }
    if ($soal40) {
        $pdo->prepare("
            UPDATE questions SET
                question_text  = 'A: 那我点七杯卡布奇诺，给你钱！\nNà wǒ diǎn qī bēi kǎbùqínuò, gěi nǐ qián!\nB: 好的,你点七杯卡布奇诺对吗？\nHǎo de, nǐ diǎn qī bēi kǎbùqínuò duì ma?\nA: 对，谢谢。Duì, xièxie.\n一杯三万七千盾 × 7 = Berapa total belanjaan yang harus di bayarnya…?',
                option_a       = '两十万九千盾',
                option_b       = '十三万五千盾',
                option_c       = '二十五万九千盾',
                option_d       = '二五十万久千盾',
                option_e       = '三十五万七千盾',
                correct_answer = 'C'
            WHERE id = ?
        ")->execute([$soal40['id']]);
        $success = $soal40['id'];
    } else {
        $error = 'Soal no.40 tidak ditemukan!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Fix Soal 38 & 40</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#f1f5f9;padding:24px}
.wrap{max-width:800px;margin:0 auto}
.card{background:#fff;border-radius:10px;padding:22px 26px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.success-box{background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:16px 20px;color:#166534;margin-bottom:16px}
.error-box{background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:16px;color:#991b1b;margin-bottom:16px}
.info-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;color:#1e40af;font-size:.9rem;margin-bottom:14px}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{background:#f1f5f9;padding:8px 10px;text-align:left;color:#475569;border-bottom:2px solid #e2e8f0}
td{padding:8px 10px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:top;white-space:pre-wrap}
.btn{display:inline-block;padding:11px 26px;border-radius:8px;font-weight:600;font-size:.95rem;text-decoration:none;margin-top:14px;margin-right:8px}
.btn-red{background:#dc2626;color:white}
.btn-green{background:#16a34a;color:white}
.btn-gray{background:#e2e8f0;color:#475569}
</style>
</head>
<body>
<div class="wrap">
<?php if (isset($success)): ?>
<div class="success-box">✅ Soal No.40 (ID: <?= $success ?>) berhasil diperbaiki! Sekarang berbeda dari soal No.38.</div>
<a href="questionGroup/show/31" class="btn btn-green">→ Lihat Grup Soal</a>
<a href="exam/assignQuestions/440" class="btn btn-gray">→ Cek Ujian</a>
<?php elseif (isset($error)): ?>
<div class="error-box">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview'): ?>
<div class="card">
  <h2 style="margin-bottom:12px">🔧 Perbaiki Soal No.38 & 40 — SAT Mandarin XI Genap</h2>
  <div class="info-box">
    Soal No.38 dan No.40 saat ini isinya sama. Soal No.40 akan diganti dengan soal total belanjaan 7 gelas cappuccino (kunci C).
  </div>
  <table>
    <tr><th>No.</th><th>ID</th><th>Pertanyaan Sekarang</th><th>Jawaban</th></tr>
    <?php foreach ($soal3840 as $s): ?>
    <tr>
      <td><?= $s['order_num'] ?></td>
      <td><?= $s['id'] ?></td>
      <td><?= htmlspecialchars(mb_substr($s['question_text'], 0, 100)) ?>...</td>
      <td><b><?= $s['correct_answer'] ?></b></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <hr style="margin:16px 0;border-color:#e2e8f0">
  <p style="font-size:.85rem;color:#64748b;margin-bottom:8px"><b>Semua soal di ujian 440 (total: <?= count($soalSemua) ?>):</b></p>
  <div style="max-height:250px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px">
  <table style="font-size:.78rem">
    <tr><th>#</th><th>ID</th><th>Pertanyaan</th><th>Jwb</th></tr>
    <?php foreach ($soalSemua as $s): ?>
    <tr style="<?= in_array($s['order_num'],[38,39,40]) ? 'background:#fef9c3' : '' ?>">
      <td><?= $s['order_num'] ?></td>
      <td><?= $s['id'] ?></td>
      <td><?= htmlspecialchars(mb_substr($s['q_text'], 0, 70)) ?>...</td>
      <td><?= $s['correct_answer'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <p style="margin-top:14px;font-size:.9rem;color:#475569"><b>Soal No.40 akan diganti menjadi:</b><br>
  "A: 那我点七杯卡布奇诺，给你钱！... 一杯三万七千盾 × 7 = Berapa total belanjaan...?" — Kunci: C (二十五万九千盾)</p>
  <a href="?action=fix" class="btn btn-red"
     onclick="return confirm('Perbaiki soal No.40?')">
    🔧 PERBAIKI SEKARANG
  </a>
  <a href="exam" class="btn btn-gray">Batal</a>
</div>
<?php endif; ?>
</div>
</body>
</html>
