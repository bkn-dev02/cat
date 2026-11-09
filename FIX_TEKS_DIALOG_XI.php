<?php
/**
 * FIX teks dialog soal 29, 31, 33, 36 di SAT Mandarin XI Genap
 * yang jadi satu baris setelah dibersihkan
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Ambil soal di grup 31 yang perlu diperbaiki — cari berdasarkan konten
$soalGrup = $pdo->query("
    SELECT id, question_text
    FROM questions
    WHERE group_id = 31
    ORDER BY id ASC
")->fetchAll();

// Teks yang benar untuk soal-soal dialog
$fixes = [
    // Soal 29: ini adalah 这是我的好朋友 (dari grup)
    '这是我的好' => [
        'text' => "这是我的好……\nZhè shì wǒ de hǎo péngyǒu.\nCarilah aksara dari pinyin yang bercetak tebal…?",
    ],
    // Soal 31: dialog kopi 四万六千
    '四万六千' => [
        'text' => "A: 你好，这个咖啡价格多少钱?\nNǐ hǎo, zhège kāfēi jiàgé duōshao qián?\nB: 你好，这一杯咖啡是四万六千盾。\nNǐ hǎo, zhè yì bēi kāfēi shì Sì wàn liù qiān dùn.\nBerapa harga 1 gelas kopi pada dialogue tersebut..?",
    ],
    // Soal 33: dialog 便宜一点
    '便宜一点' => [
        'text' => "A: 那有咖啡便宜一点吗？\nNà yǒu kāfēi piányi yìdiǎn ma?\nB: 有, 卡布奇诺更便宜, 一杯二万七千盾。\nYǒu, kǎbùqínuò gèng piányi, yì bēi èr wàn qī qiān dùn.\nArti dari kata \"便宜 piányi\" pada dialogue adalah…?",
    ],
    // Soal 36: dialog total 5 gelas cappuccino
    '五杯卡布奇诺' => [
        'text' => "A: 那有咖啡便宜一点吗？\nNà yǒu kāfēi piányi yìdiǎn ma?\nB: 有, 卡布奇诺更便宜, 一杯二万七千盾。\nYǒu, kǎbùqínuò gèng piányi, yì bēi èr wàn qī qiān dùn.\nA: 那我点五杯卡布奇诺，给你钱！\nNà wǒ diǎn wǔ bēi kǎbùqínuò, gěi nǐ qián!\nB: 好的,你点五杯卡布奇诺对吗？\nHǎo de, nǐ diǎn wǔ bēi kǎbùqínuò duì ma?\nA: 对，谢谢。Duì, xièxie.\nBerapa total belanjaan yang harus di bayarnya pada kalimat di atas…?",
    ],
];

$action = $_GET['action'] ?? 'preview';
$toFix = [];

// Cari soal yang perlu diperbaiki
foreach ($soalGrup as $q) {
    foreach ($fixes as $keyword => $fix) {
        if (mb_stripos($q['question_text'], $keyword) !== false) {
            // Cek apakah teks sudah rapi (ada newline) atau masih satu baris
            if (strpos($q['question_text'], "\n") === false && mb_strlen($q['question_text']) > 60) {
                $toFix[] = [
                    'id'       => $q['id'],
                    'old'      => $q['question_text'],
                    'new'      => $fix['text'],
                    'keyword'  => $keyword,
                ];
                break;
            }
        }
    }
}

if ($action === 'fix' && !empty($toFix)) {
    $upd = $pdo->prepare("UPDATE questions SET question_text = ? WHERE id = ?");
    foreach ($toFix as $f) {
        $upd->execute([$f['new'], $f['id']]);
    }
    $success = count($toFix);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Fix Teks Dialog XI</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 860px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; margin-bottom: 16px; color: #166534; font-size:1.05rem; }
.info-box    { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; color: #1e40af; font-size:.88rem; margin-bottom:14px; }
table { width: 100%; border-collapse: collapse; font-size: .83rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
.old { color: #dc2626; white-space: pre-wrap; }
.new { color: #16a34a; white-space: pre-wrap; }
.btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; font-size: .95rem; text-decoration: none; margin-top: 14px; margin-right: 8px; }
.btn-blue  { background: #2563eb; color: white; }
.btn-green { background: #16a34a; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  ✅ <b><?= $success ?> soal dialog berhasil diperbaiki!</b><br>
  Teks dialog sekarang sudah rapi dengan baris yang benar.
</div>
<a href="questionGroup/show/31" class="btn btn-green">→ Lihat Grup Soal</a>
<a href="exam/assignQuestions/440" class="btn btn-gray">→ Cek Ujian</a>

<?php elseif ($action === 'preview'): ?>
<div class="card">
  <h2 style="margin-bottom:12px">🔧 Perbaiki Teks Dialog — SAT Mandarin XI Genap</h2>

  <?php if (empty($toFix)): ?>
  <div class="success-box">✅ Semua teks dialog sudah rapi! Tidak ada yang perlu diperbaiki.</div>
  <a href="exam" class="btn btn-green" style="margin-top:8px">→ Kembali ke Ujian</a>
  <?php else: ?>
  <div class="info-box">
    <?= count($toFix) ?> soal dialog ditemukan masih dalam satu baris. Klik <b>Perbaiki Sekarang</b> untuk merapikan.
  </div>
  <div style="overflow-x:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:14px">
  <table>
    <tr><th>ID</th><th>Sebelum (satu baris)</th><th>Sesudah (rapi)</th></tr>
    <?php foreach ($toFix as $f): ?>
    <tr>
      <td><?= $f['id'] ?></td>
      <td class="old"><?= htmlspecialchars(mb_substr($f['old'], 0, 120)) ?>...</td>
      <td class="new"><?= htmlspecialchars($f['new']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <a href="?action=fix" class="btn btn-blue"
     onclick="return confirm('Perbaiki <?= count($toFix) ?> soal dialog sekarang?')">
    🔧 PERBAIKI SEKARANG
  </a>
  <a href="exam" class="btn btn-gray">Batal</a>
  <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
