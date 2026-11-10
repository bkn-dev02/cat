<?php
/**
 * DARURAT - Bersihkan soal rusak (HTML Word) di SAT Mandarin X Genap
 * Grup ID: 32, Ujian ID: 346
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function cleanHtml($html) {
    if (!$html) return '';
    // Hapus blok head, style, xml
    $html = preg_replace('/<head[^>]*>[\s\S]*?<\/head>/i', '', $html);
    $html = preg_replace('/<style[^>]*>[\s\S]*?<\/style>/i', '', $html);
    $html = preg_replace('/<xml[^>]*>[\s\S]*?<\/xml>/i', '', $html);
    // Hapus meta, link
    $html = preg_replace('/<(meta|link)[^>]*>/i', '', $html);
    // Hapus Office tags
    $html = preg_replace('/<\/?(o|w|m):[^>]*>/i', '', $html);
    // Hapus komentar kondisional Word
    $html = preg_replace('/<!--\[if[^\]]*\]>[\s\S]*?<!\[endif\]-->/i', '', $html);
    $html = preg_replace('/<!--StartFragment-->|<!--EndFragment-->/i', '', $html);
    $html = preg_replace('/<!--[\s\S]*?-->/', '', $html);
    // Ganti br, p, div, li dengan newline
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $html = preg_replace('/<\/p>/i', "\n", $html);
    $html = preg_replace('/<p[^>]*>/i', '', $html);
    $html = preg_replace('/<\/div>/i', "\n", $html);
    $html = preg_replace('/<div[^>]*>/i', '', $html);
    $html = preg_replace('/<li[^>]*>/i', "\n", $html);
    // Strip semua tag tersisa
    $text = strip_tags($html);
    // Decode HTML entities
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Bersihkan whitespace
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n[ \t]+/', "\n", $text);
    $text = preg_replace('/[ \t]+\n/', "\n", $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = trim($text);
    return $text;
}

// Ambil semua soal di grup 32 yang berisi HTML
$soalRusak = $pdo->query("
    SELECT id, question_text, option_a, option_b, option_c, option_d, option_e
    FROM questions
    WHERE group_id = 32
    AND (
        question_text LIKE '%<span%'
        OR question_text LIKE '%<div%'
        OR question_text LIKE '%<p %'
        OR question_text LIKE '%<p>%'
        OR question_text LIKE '%font-size%'
        OR question_text LIKE '%&nbsp;%'
        OR question_text LIKE '%MsoNormal%'
        OR option_a LIKE '%<span%'
        OR option_b LIKE '%<span%'
        OR option_c LIKE '%<span%'
    )
    ORDER BY id ASC
")->fetchAll();

$action = $_GET['action'] ?? 'preview';

if ($action === 'fix' && !empty($soalRusak)) {
    $fixed = 0;
    $upd = $pdo->prepare("UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=? WHERE id=?");
    foreach ($soalRusak as $q) {
        $upd->execute([
            cleanHtml($q['question_text']),
            cleanHtml($q['option_a'] ?? ''),
            cleanHtml($q['option_b'] ?? ''),
            cleanHtml($q['option_c'] ?? ''),
            cleanHtml($q['option_d'] ?? ''),
            cleanHtml($q['option_e'] ?? ''),
            $q['id']
        ]);
        $fixed++;
    }
    $success = $fixed;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>DARURAT - Fix Soal Rusak X</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 900px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; color: #166534; font-size:1.05rem; margin-bottom:16px; }
.warn-box    { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 12px 16px; color: #92400e; font-size:.9rem; margin-bottom:14px; }
table { width: 100%; border-collapse: collapse; font-size: .82rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; word-break: break-word; }
tr:nth-child(even) { background: #f8fafc; }
.old { color: #dc2626; }
.new { color: #16a34a; white-space: pre-wrap; }
.btn { display: inline-block; padding: 12px 28px; border-radius: 8px; font-weight: 700; font-size: 1rem; text-decoration: none; margin-top: 14px; margin-right: 8px; }
.btn-red   { background: #dc2626; color: white; }
.btn-green { background: #16a34a; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  ✅ <b><?= $success ?> soal berhasil dibersihkan!</b><br>
  Semua soal HTML Word di SAT Mandarin Kelas X Genap sudah bersih.
</div>
<a href="questionGroup/show/32" class="btn btn-green">→ Lihat Grup Soal X</a>
<a href="exam" class="btn btn-gray">→ Daftar Ujian</a>

<?php elseif ($action === 'preview'): ?>
<div class="card">
  <h2 style="color:#dc2626;margin-bottom:12px">🚨 <?= count($soalRusak) ?> Soal Rusak di SAT Mandarin Kelas X Genap</h2>

  <?php if (empty($soalRusak)): ?>
  <p style="color:#16a34a;font-weight:700">✅ Semua soal sudah bersih!</p>
  <a href="questionGroup/show/32" class="btn btn-green" style="margin-top:12px">→ Lihat Grup Soal</a>
  <?php else: ?>
  <div class="warn-box">
    Klik <b>"BERSIHKAN SEKARANG"</b> untuk memperbaiki semua <?= count($soalRusak) ?> soal.
  </div>
  <div style="max-height:380px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:14px">
  <table>
    <tr><th>ID</th><th>Isi Rusak</th><th>Setelah dibersihkan</th></tr>
    <?php foreach ($soalRusak as $q): ?>
    <tr>
      <td><?= $q['id'] ?></td>
      <td class="old"><?= htmlspecialchars(mb_substr($q['question_text'], 0, 80)) ?>...</td>
      <td class="new"><?= htmlspecialchars(mb_substr(cleanHtml($q['question_text']), 0, 100)) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <a href="?action=fix" class="btn btn-red"
     onclick="return confirm('Bersihkan <?= count($soalRusak) ?> soal rusak di SAT Mandarin Kelas X?')">
    🔧 BERSIHKAN SEKARANG (<?= count($soalRusak) ?> soal)
  </a>
  <a href="exam" class="btn btn-gray">Batal</a>
  <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
