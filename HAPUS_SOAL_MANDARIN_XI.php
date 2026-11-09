<?php
/**
 * HAPUS SEMUA SOAL BAHASA MANDARIN dari bank soal
 * yang dibuat oleh guru wara (subject_id = 7, Bahasa Mandarin)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Cari guru wara
$guru = $pdo->query("SELECT id, full_name FROM users WHERE username LIKE '%wara%' LIMIT 1")->fetch();
if (!$guru) $guru = $pdo->query("SELECT id, full_name FROM users WHERE role='admin' LIMIT 1")->fetch();
$guruId = $guru['id'];

// Hitung soal Mandarin milik guru ini yang TIDAK ter-assign ke ujian Kelas X (346)
$total = $pdo->query("
    SELECT COUNT(*) FROM questions 
    WHERE subject_id = 7 
    AND created_by = $guruId
    AND id NOT IN (SELECT question_id FROM exam_questions WHERE exam_id = 346)
")->fetchColumn();

$soalIds = array_column(
    $pdo->query("
        SELECT id FROM questions 
        WHERE subject_id = 7 
        AND created_by = $guruId
        AND id NOT IN (SELECT question_id FROM exam_questions WHERE exam_id = 346)
    ")->fetchAll(),
    'id'
);

$action = $_GET['action'] ?? 'preview';

if ($action === 'hapus' && !empty($soalIds)) {
    $ids = implode(',', $soalIds);
    $pdo->exec("DELETE FROM exam_questions WHERE question_id IN ($ids)");
    $pdo->exec("DELETE FROM answers WHERE question_id IN ($ids)");
    $pdo->exec("DELETE FROM questions WHERE id IN ($ids)");

    // Hapus juga grup soal SAT Mandarin XI (group_id = 31)
    $pdo->exec("DELETE FROM question_groups WHERE id = 31");

    $success = count($soalIds);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Hapus Soal Mandarin XI</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#f1f5f9;padding:24px}
.wrap{max-width:600px;margin:0 auto}
.card{background:#fff;border-radius:10px;padding:22px 26px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.success-box{background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:16px 20px;color:#166534;margin-bottom:16px;font-size:1.05rem}
.warn-box{background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px 16px;color:#92400e;font-size:.9rem;margin-bottom:14px}
.btn{display:inline-block;padding:11px 26px;border-radius:8px;font-weight:600;font-size:.95rem;text-decoration:none;margin-top:14px;margin-right:8px}
.btn-red{background:#dc2626;color:white}
.btn-green{background:#16a34a;color:white}
.btn-gray{background:#e2e8f0;color:#475569}
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  ✅ <b><?= $success ?> soal Bahasa Mandarin berhasil dihapus dari bank soal.</b><br>
  Grup soal SAT Mandarin XI Genap (ID 31) juga sudah dihapus.<br>
  Bank soal sekarang bersih — siap untuk input soal baru.
</div>
<a href="question" class="btn btn-green">→ Lihat Bank Soal</a>
<a href="exam" class="btn btn-gray">→ Buat Ujian Baru</a>

<?php else: ?>
<div class="card">
  <h2 style="margin-bottom:12px;color:#dc2626">🗑️ Hapus Semua Soal Bahasa Mandarin</h2>
  <div class="warn-box">
    Akan menghapus <b><?= $total ?> soal</b> Bahasa Mandarin yang <b>bukan soal Kelas X</b> dari bank soal milik <b><?= htmlspecialchars($guru['full_name']) ?></b>.<br>
    Soal Kelas X (yang ter-assign di ujian 346) <b>AMAN, tidak akan terhapus</b>.
  </div>
  <a href="?action=hapus" class="btn btn-red"
     onclick="return confirm('Hapus semua <?= $total ?> soal Mandarin? Tidak bisa dibatalkan!')">
    🗑️ HAPUS SEMUA (<?= $total ?> soal)
  </a>
  <a href="question" class="btn btn-gray">Batal</a>
</div>
<?php endif; ?>

</div>
</body>
</html>
