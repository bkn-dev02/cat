<?php
/**
 * BUAT GRUP SOAL — SAT Mandarin Kelas XI Genap
 * Masukkan 40 soal terbaru ke dalam 1 grup soal
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.production.php';
$pdo = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Cari subject Mandarin
$subj = $pdo->query("SELECT id FROM subjects WHERE name LIKE '%Mandarin%' LIMIT 1")->fetch();
$subjectId = $subj['id']; // 7

// Cari guru
$guru = $pdo->query("SELECT id FROM users WHERE username LIKE '%wara%' LIMIT 1")->fetch();
if (!$guru) $guru = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
$guruId = $guru['id'];

// Ambil 40 soal terbaru Mandarin (yang baru di-rebuild)
$soal = $pdo->query("
    SELECT id, question_text, correct_answer
    FROM questions
    WHERE subject_id = $subjectId
      AND group_id IS NULL
    ORDER BY id DESC
    LIMIT 40
")->fetchAll();
$soal = array_reverse($soal); // urutkan dari terkecil

$action = $_GET['action'] ?? 'preview';

if ($action === 'buat' && !empty($soal)) {
    $pdo->beginTransaction();
    try {
        // Cek apakah grup sudah ada
        $cekGrup = $pdo->prepare("SELECT id FROM question_groups WHERE name = ? AND created_by = ?");
        $cekGrup->execute(['SAT Mandarin Kelas XI Genap', $guruId]);
        $grupAda = $cekGrup->fetch();

        if ($grupAda) {
            $groupId = $grupAda['id'];
            $grupBaru = false;
        } else {
            // Buat grup baru
            $pdo->prepare("
                INSERT INTO question_groups (name, description, subject_id, type, sub_topic, default_points, created_by, created_at)
                VALUES (?, ?, ?, 'multiple_choice', 'SAT Semester Genap', 1, ?, NOW())
            ")->execute([
                'SAT Mandarin Kelas XI Genap',
                'Kumpulan soal SAT Bahasa Mandarin Kelas XI Semester Genap. Tambah soal baru langsung di sini.',
                $subjectId,
                $guruId
            ]);
            $groupId = $pdo->lastInsertId();
            $grupBaru = true;
        }

        // Masukkan semua soal ke grup
        $upd = $pdo->prepare("UPDATE questions SET group_id = ? WHERE id = ?");
        foreach ($soal as $q) {
            $upd->execute([$groupId, $q['id']]);
        }

        $pdo->commit();
        $success = ['groupId' => $groupId, 'baru' => $grupBaru, 'total' => count($soal)];

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
<title>Buat Grup Soal Mandarin XI</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 24px; }
.wrap { max-width: 780px; margin: 0 auto; }
.card { background: #fff; border-radius: 10px; padding: 22px 26px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
h2 { color: #1e293b; margin-bottom: 12px; font-size: 1.1rem; }
.success-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; margin-bottom: 16px; color: #166534; line-height: 2; }
.error-box   { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; margin-bottom: 16px; color: #991b1b; }
.info-box    { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; color: #1e40af; font-size: .9rem; line-height: 1.8; }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
th { background: #f1f5f9; padding: 8px 10px; text-align: left; color: #475569; border-bottom: 2px solid #e2e8f0; }
td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
tr:nth-child(even) { background: #f8fafc; }
.jwb { font-weight: 700; color: #16a34a; background: #dcfce7; border-radius: 4px; padding: 1px 7px; font-size: .8rem; }
.btn { display: inline-block; padding: 11px 26px; border-radius: 8px; font-weight: 600; font-size: .95rem; text-decoration: none; margin-top: 14px; margin-right: 8px; }
.btn-blue  { background: #2563eb; color: white; }
.btn-gray  { background: #e2e8f0; color: #475569; }
.btn-green { background: #16a34a; color: white; }
</style>
</head>
<body>
<div class="wrap">

<?php if (isset($success)): ?>
<div class="success-box">
  <b style="font-size:1.15rem">✅ Grup Soal Berhasil Dibuat!</b><br>
  Nama Grup: <b>SAT Mandarin Kelas XI Genap</b><br>
  ID Grup: <b><?= $success['groupId'] ?></b><br>
  Total soal dalam grup: <b><?= $success['total'] ?> soal</b><br>
  Status: <?= $success['baru'] ? '🆕 Grup baru dibuat' : '✅ Soal ditambahkan ke grup yang sudah ada' ?>
</div>

<div class="info-box">
  <b>Cara menambah soal baru ke grup ini:</b><br>
  1. Buka menu <b>Grup Soal</b> di navbar<br>
  2. Klik grup <b>"SAT Mandarin Kelas XI Genap"</b><br>
  3. Klik <b>"+ Tambah Soal"</b> — subject & tipe sudah otomatis terisi<br>
  4. Langsung isi pertanyaan & pilihan jawaban saja
</div>

<a href="questionGroup/show/<?= $success['groupId'] ?>" class="btn btn-green">→ Buka Grup Soal Sekarang</a>
<a href="questionGroup" class="btn btn-gray">→ Daftar Grup Soal</a>

<?php elseif (isset($error)): ?>
<div class="error-box">❌ Error: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'preview' || isset($error)): ?>
<div class="card">
  <h2>📁 Buat Grup Soal — SAT Mandarin Kelas XI Genap</h2>

  <div class="info-box">
    Script ini akan membuat <b>1 Grup Soal</b> bernama <b>"SAT Mandarin Kelas XI Genap"</b>
    dan memasukkan <b><?= count($soal) ?> soal</b> berikut ke dalamnya.<br>
    Setelah ini, tambah soal baru cukup lewat menu <b>Grup Soal</b> — tidak perlu isi ulang subject/tipe.
  </div>

  <div style="max-height:300px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:4px">
  <table>
    <tr><th>#</th><th>ID</th><th>Pertanyaan</th><th>Jwb</th></tr>
    <?php foreach ($soal as $i => $q): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td style="color:#94a3b8"><?= $q['id'] ?></td>
      <td><?= htmlspecialchars(mb_substr(strip_tags($q['question_text']), 0, 80)) ?>...</td>
      <td><span class="jwb"><?= $q['correct_answer'] ?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($soal)): ?>
    <tr><td colspan="4" style="color:#dc2626;padding:12px">Tidak ada soal ditemukan!</td></tr>
    <?php endif; ?>
  </table>
  </div>

  <?php if (!empty($soal)): ?>
  <a href="?action=buat" class="btn btn-blue"
     onclick="return confirm('Buat grup soal dan masukkan <?= count($soal) ?> soal ke dalamnya?')">
    📁 BUAT GRUP SEKARANG
  </a>
  <?php endif; ?>
  <a href="questionGroup" class="btn btn-gray">Batal</a>
</div>
<?php endif; ?>

</div>
</body>
</html>
