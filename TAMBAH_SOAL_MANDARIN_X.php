<?php
/**
 * TAMBAH 10 SOAL BARU — SAT Mandarin Kelas X Genap
 * Soal 51-60, langsung masuk ke grup & ujian
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Cari grup SAT Mandarin Kelas X Genap
$grup = $pdo->query("SELECT id, name FROM question_groups WHERE name LIKE '%Mandarin%X%' OR name LIKE '%Mandarin%10%' ORDER BY id DESC LIMIT 5")->fetchAll();

// Cari ujian SAT Mandarin Kelas X Genap
$ujian = $pdo->query("SELECT id, title FROM exams WHERE title LIKE '%Mandarin%X%' OR title LIKE '%MANDARIN%X%' ORDER BY id DESC LIMIT 5")->fetchAll();

// Cari subject Mandarin
$subj = $pdo->query("SELECT id FROM subjects WHERE name LIKE '%Mandarin%' LIMIT 1")->fetch();
$subjectId = $subj['id'];

// Cari guru
$guru = $pdo->query("SELECT id FROM users WHERE username LIKE '%wara%' LIMIT 1")->fetch();
if (!$guru) $guru = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
$guruId = $guru['id'];

$selectedGroupId = $_GET['group_id'] ?? null;
$selectedExamId  = $_GET['exam_id']  ?? null;
$action = $_GET['action'] ?? 'preview';

// ============================================
// 10 SOAL BARU (51-60)
// ============================================
$soalBaru = [
[51,
 'Find the Pinyin of "Dream"….?',
 'Mèng xiāng','Méng xiàng','Men xiǎng','Meng xiang','Mèng xiǎng','E'],

[52,
 'What the meaning from this pinyin "还有"…?',
 'Already','Do not have','Have / Also','Done','Good','C'],

[53,
 'The meaning of this word 文具盒 is….?',
 'School bag','Pencil','Book','Ruler','Pencil case','E'],

[54,
 'Find the hanzi from this sentence "Where are you from?"',
 '你从哪里来？','你去哪里来？','你从哪儿吗？','我从那里来？','你怎么来了？','A'],

[55,
 'Arti dari kata "在" adalah…?',
 'Pergi','Tinggal','Datang','Di / pada / sedang','Mendengar','D'],

[56,
 '我是马来人 find the meaning =…?',
 'I am a Malaysian','I am a Indonesian','You are Malay.','You from Malaysia','You from Indonesia','A'],

[57,
 'Jackson Wang: 你好, 你从哪里来 Nǐ hǎo, nǐ cóng nǎlǐ lái?
Zhao Lusi: 你好，我从中国来。你呢? Nǐ hǎo, wǒ cóng zhōngguó lái. Nǐ ne?
Jackson Wang: 我从美国我是印尼人。Wǒ cóng měiguó wǒ shì yìnní rén.
Where is Jackson Wang from, and what is his nationality?',
 'From America, Chinese person.','From China, American person','From America, Indonesian person','From Indonesia, Chinese person','Indonesian person','C'],

[58,
 'Wang: 你要点什么？Nǐ yào diǎn shénme?
Cheng: 我要点咖啡，你呢？Wǒ yào diǎn kāfēi, nǐ ne?
Wang: 我也要点咖啡。Wǒ yě yào diǎn kāfēi.
Cheng: 那我们点五杯咖啡 Nà wǒmen diǎn Wǔ bēi kāfēi.
How many glasses they order?',
 '一杯咖啡','两杯咖啡','三杯咖啡','四杯咖啡','五杯咖啡','E'],

[59,
 '"我不喜欢辣 Wǒ bù xǐhuān là" The meaning is..?',
 'I like sweet.','I don\'t like spicy.','I like spicy food','I want spicy food','I don\'t like salty food','B'],

[60,
 'Wang: 你要点什么？Nǐ yào diǎn shénme?
Cheng: 我要点牛奶，你呢？Wǒ yào diǎn Niúnǎi, nǐ ne?
Wang: 我也要点牛奶。Wǒ yě yào diǎn Niúnǎi.
Cheng: 那我们点二杯牛奶 Nà wǒmen diǎn èr bēi Niúnǎi.
What they want to order in that dialogues?',
 'Juice','Milk','Coffee','Beer','Cola','B'],
];

if ($action === 'tambah' && $selectedGroupId && $selectedExamId) {
    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare("
            INSERT INTO questions (
                group_id, subject_id, type, question_text,
                option_a, option_b, option_c, option_d, option_e,
                correct_answer, points, created_by, created_at
            ) VALUES (?, ?, 'multiple_choice', ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ");

        // Cek order_num terakhir di ujian
        $lastOrder = $pdo->query("SELECT MAX(order_num) as mo FROM exam_questions WHERE exam_id = $selectedExamId")->fetch()['mo'] ?? 0;

        $insEQ = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, order_num) VALUES (?, ?, ?)");
        $newIds = [];
        foreach ($soalBaru as $i => $s) {
            $ins->execute([$selectedGroupId, $subjectId, $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $guruId]);
            $qId = $pdo->lastInsertId();
            $newIds[] = $qId;
            $insEQ->execute([$selectedExamId, $qId, $lastOrder + $i + 1]);
        }

        $pdo->commit();
        $success = ['total' => count($newIds), 'groupId' => $selectedGroupId, 'examId' => $selectedExamId];
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
<title>Tambah Soal Mandarin X</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 820px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
h2 { color: #1e293b; margin-bottom: 12px; font-size: 1.1rem; }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; margin-bottom: 16px; color: #166534; line-height: 2; }
.error-box   { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; margin-bottom: 16px; color: #991b1b; }
.info-box    { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; color: #1e40af; font-size: .9rem; }
table { width: 100%; border-collapse: collapse; font-size: .84rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
tr:nth-child(even) { background: #f8fafc; }
.jwb { font-weight: 700; color: #16a34a; background: #dcfce7; border-radius: 4px; padding: 1px 7px; font-size: .8rem; }
.btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; font-size: .95rem; text-decoration: none; margin-top: 14px; margin-right: 8px; }
.btn-blue  { background: #2563eb; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
.btn-green { background: #16a34a; color: white; }
.sel-btn { display:inline-block; padding:10px 18px; border-radius:8px; background:#6366f1; color:white; font-weight:600; text-decoration:none; margin:4px 4px 4px 0; font-size:.9rem; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  <b style="font-size:1.15rem">✅ <?= $success['total'] ?> Soal Baru Berhasil Ditambahkan!</b><br>
  Grup ID: <b><?= $success['groupId'] ?></b><br>
  Ujian ID: <b><?= $success['examId'] ?></b><br>
  Soal No.51–60 sudah masuk ke grup & ujian SAT Mandarin Kelas X Genap.
</div>
<a href="questionGroup/show/<?= $success['groupId'] ?>" class="btn btn-green">→ Lihat Grup Soal</a>
<a href="exam" class="btn btn-gray">→ Daftar Ujian</a>

<?php elseif (isset($error)): ?>
<div class="error-box">❌ Error: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview' || isset($error)): ?>

<?php if (!$selectedGroupId || !$selectedExamId): ?>
<div class="card">
  <h2>➕ Tambah Soal No.51–60 — SAT Mandarin Kelas X Genap</h2>
  <p style="color:#475569;font-size:.9rem;margin-bottom:14px">Pilih grup dan ujian yang sesuai:</p>

  <div style="margin-bottom:16px">
    <b style="font-size:.9rem;color:#475569;display:block;margin-bottom:8px">Pilih Grup Soal:</b>
    <?php foreach ($grup as $g): ?>
    <a href="?group_id=<?= $g['id'] ?><?= $selectedExamId ? '&exam_id='.$selectedExamId : '' ?>" class="sel-btn">
      📁 <?= htmlspecialchars($g['name']) ?> (ID: <?= $g['id'] ?>)
    </a>
    <?php endforeach; ?>
    <?php if (empty($grup)): ?>
    <p style="color:#dc2626">Grup Mandarin X tidak ditemukan!</p>
    <?php endif; ?>
  </div>

  <div>
    <b style="font-size:.9rem;color:#475569;display:block;margin-bottom:8px">Pilih Ujian:</b>
    <?php foreach ($ujian as $u): ?>
    <a href="?exam_id=<?= $u['id'] ?><?= $selectedGroupId ? '&group_id='.$selectedGroupId : '' ?>" class="sel-btn" style="background:#059669">
      📝 <?= htmlspecialchars($u['title']) ?> (ID: <?= $u['id'] ?>)
    </a>
    <?php endforeach; ?>
    <?php if (empty($ujian)): ?>
    <p style="color:#dc2626">Ujian SAT Mandarin X tidak ditemukan!</p>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<div class="card">
  <h2>➕ Konfirmasi Tambah 10 Soal Baru (No.51–60)</h2>
  <div class="info-box">
    Grup ID: <b><?= $selectedGroupId ?></b> &nbsp;|&nbsp; Ujian ID: <b><?= $selectedExamId ?></b><br>
    10 soal berikut akan ditambahkan ke grup & assign ke ujian.
  </div>

  <div style="max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:14px">
  <table>
    <tr><th>#</th><th>Pertanyaan</th><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th><th>Jwb</th></tr>
    <?php foreach ($soalBaru as $s): ?>
    <tr>
      <td><?= $s[0] ?></td>
      <td><?= htmlspecialchars(mb_substr($s[1], 0, 55)) ?>...</td>
      <td><?= htmlspecialchars(mb_substr($s[2], 0, 15)) ?></td>
      <td><?= htmlspecialchars(mb_substr($s[3], 0, 15)) ?></td>
      <td><?= htmlspecialchars(mb_substr($s[4], 0, 15)) ?></td>
      <td><?= htmlspecialchars(mb_substr($s[5], 0, 15)) ?></td>
      <td><?= htmlspecialchars(mb_substr($s[6], 0, 15)) ?></td>
      <td><span class="jwb"><?= $s[7] ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>

  <a href="?group_id=<?= $selectedGroupId ?>&exam_id=<?= $selectedExamId ?>&action=tambah"
     class="btn btn-blue"
     onclick="return confirm('Tambah 10 soal baru ke grup dan ujian SAT Mandarin X?')">
    ➕ TAMBAH SEKARANG (10 Soal)
  </a>
  <a href="?" class="btn btn-gray">← Pilih Ulang</a>
</div>
<?php endif; ?>

<?php endif; ?>
</div>
</body>
</html>
