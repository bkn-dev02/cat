<?php
/**
 * DARURAT - Bersihkan soal rusak (HTML Word) di SAT Mandarin XI Genap
 * Ujian ID: 440, Grup ID: 31
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Fungsi bersihkan HTML Word dari teks
function cleanWordHtmlServer($html) {
    if (!$html) return '';
    // Hapus tag <head>
    $html = preg_replace('/<head[^>]*>[\s\S]*?<\/head>/i', '', $html);
    // Hapus meta, link, style, xml
    $html = preg_replace('/<(meta|link)[^>]*>/i', '', $html);
    $html = preg_replace('/<style[^>]*>[\s\S]*?<\/style>/i', '', $html);
    $html = preg_replace('/<xml[^>]*>[\s\S]*?<\/xml>/i', '', $html);
    // Hapus Office namespace tags
    $html = preg_replace('/<\/?(o|w|m):[^>]*>/i', '', $html);
    // Hapus conditional comments
    $html = preg_replace('/<!--\[if[^\]]*\]>[\s\S]*?<!\[endif\]-->/i', '', $html);
    $html = preg_replace('/<!--[\s\S]*?-->/', '', $html);
    // Hapus class Mso*
    $html = preg_replace('/\s*class="Mso[^"]*"/i', '', $html);
    $html = preg_replace('/\s*class=\'Mso[^\']*\'/i', '', $html);
    // Hapus style mso-*
    $html = preg_replace('/\s*mso-[^;]+;?/i', '', $html);

    // Ganti <br>, <p>, </p>, </div>, <li> dengan newline SEBELUM strip_tags
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $html = preg_replace('/<\/p>/i', "\n", $html);
    $html = preg_replace('/<p[^>]*>/i', '', $html);
    $html = preg_replace('/<\/div>/i', "\n", $html);
    $html = preg_replace('/<li[^>]*>/i', "\n", $html);

    // Strip semua tag HTML yang tersisa
    $text = strip_tags($html);

    // Decode HTML entities (&nbsp; dll)
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Bersihkan whitespace berlebih tapi pertahankan newline
    $text = preg_replace('/[ \t]+/', ' ', $text);         // spasi berlebih
    $text = preg_replace('/\n[ \t]+/', "\n", $text);      // spasi setelah newline
    $text = preg_replace('/[ \t]+\n/', "\n", $text);      // spasi sebelum newline
    $text = preg_replace('/\n{3,}/', "\n\n", $text);      // max 2 newline berturut
    $text = trim($text);

    return $text;
}

// Ambil semua soal di grup 31 yang isinya HTML Word
$soalRusak = $pdo->query("
    SELECT id, question_text, option_a, option_b, option_c, option_d, option_e
    FROM questions
    WHERE group_id = 31
    AND (
        question_text LIKE '%MsoNormal%'
        OR question_text LIKE '%margin-top%'
        OR question_text LIKE '%mso-%'
        OR question_text LIKE '%<p %'
        OR question_text LIKE '%<p>%'
        OR question_text LIKE '%<div%'
        OR question_text LIKE '%<span%'
        OR question_text LIKE '%Content-Type%'
        OR question_text LIKE '%&nbsp;%'
        OR option_a LIKE '%<div%'
        OR option_a LIKE '%<span%'
        OR option_b LIKE '%<div%'
        OR option_b LIKE '%<span%'
    )
    ORDER BY id ASC
")->fetchAll();

$action = $_GET['action'] ?? 'preview';

if ($action === 'fix' && !empty($soalRusak)) {
    $fixed = 0;
    $upd = $pdo->prepare("UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=? WHERE id=?");
    foreach ($soalRusak as $q) {
        $newText = cleanWordHtmlServer($q['question_text']);
        $newA    = cleanWordHtmlServer($q['option_a'] ?? '');
        $newB    = cleanWordHtmlServer($q['option_b'] ?? '');
        $newC    = cleanWordHtmlServer($q['option_c'] ?? '');
        $newD    = cleanWordHtmlServer($q['option_d'] ?? '');
        $newE    = cleanWordHtmlServer($q['option_e'] ?? '');
        $upd->execute([$newText, $newA, $newB, $newC, $newD, $newE, $q['id']]);
        $fixed++;
    }
    $success = $fixed;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>DARURAT - Fix Soal Rusak XI</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 900px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; margin-bottom: 16px; color: #166534; font-size:1.05rem; }
.warn-box    { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 12px 16px; color: #92400e; font-size:.9rem; margin-bottom:14px; }
table { width: 100%; border-collapse: collapse; font-size: .82rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; word-break: break-word; }
tr:nth-child(even) { background: #f8fafc; }
.btn { display: inline-block; padding: 12px 28px; border-radius: 8px; font-weight: 700; font-size: 1rem; text-decoration: none; margin-top: 14px; margin-right: 8px; }
.btn-red   { background: #dc2626; color: white; }
.btn-green { background: #16a34a; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
.empty { color: #16a34a; font-weight: 700; font-size: 1.1rem; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  ✅ <b><?= $success ?> soal berhasil dibersihkan dari HTML Word!</b><br>
  Soal sekarang sudah bersih dan siap untuk ujian.
</div>
<a href="exam/assignQuestions/440" class="btn btn-green">→ Cek Ujian SAT Mandarin XI Genap</a>
<a href="questionGroup/show/31" class="btn btn-gray">→ Lihat Grup Soal</a>

<?php elseif ($action === 'preview'): ?>
<div class="card">
  <h2 style="color:#dc2626;margin-bottom:12px">🚨 DARURAT — <?= count($soalRusak) ?> Soal Rusak Ditemukan</h2>

  <?php if (empty($soalRusak)): ?>
  <p class="empty">✅ Tidak ada soal rusak! Semua soal sudah bersih.</p>
  <a href="exam" class="btn btn-green" style="margin-top:12px">→ Kembali ke Ujian</a>
  <?php else: ?>
  <div class="warn-box">
    Soal-soal berikut berisi kode HTML dari Microsoft Word. Klik <b>"BERSIHKAN SEKARANG"</b> untuk memperbaiki.
  </div>
  <div style="max-height:350px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:14px">
  <table>
    <tr><th>ID</th><th>Isi Rusak (sebelum)</th><th>Setelah dibersihkan</th></tr>
    <?php foreach ($soalRusak as $q): ?>
    <tr>
      <td><?= $q['id'] ?></td>
      <td style="color:#dc2626;"><?= htmlspecialchars(mb_substr($q['question_text'], 0, 80)) ?>...</td>
      <td style="color:#16a34a;"><?= htmlspecialchars(mb_substr(cleanWordHtmlServer($q['question_text']), 0, 80)) ?>...</td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>

  <a href="?action=fix" class="btn btn-red"
     onclick="return confirm('Bersihkan <?= count($soalRusak) ?> soal rusak sekarang?')">
    🔧 BERSIHKAN SEKARANG (<?= count($soalRusak) ?> soal)
  </a>
  <a href="exam" class="btn btn-gray">Batal</a>
  <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
