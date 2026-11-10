<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/helpers.php';

$sid = (int)($_GET['sid'] ?? 934);
$uid = (int)($_GET['uid'] ?? 1730);

// 1. Info sesi
$sesi = db()->prepare("SELECT es.*, e.title AS ujian, e.id AS exam_id FROM exam_sessions es JOIN exams e ON e.id = es.exam_id WHERE es.id = ?");
$sesi->execute([$sid]);
$sesi = $sesi->fetch();

// 2. Semua jawaban di sesi ini (tanpa filter exam)
$jawaban = db()->prepare("SELECT a.*, q.correct_answer, q.points, q.question_text FROM answers a LEFT JOIN questions q ON q.id = a.question_id WHERE a.session_id = ?");
$jawaban->execute([$sid]);
$jawaban = $jawaban->fetchAll();

// 3. Cek apakah ada jawaban user ini di exam yang sama tapi sesi berbeda
$jawaban_lain = db()->prepare("
    SELECT a.session_id, COUNT(*) AS jml, es.status, es.score
    FROM answers a
    JOIN exam_sessions es ON es.id = a.session_id
    WHERE es.user_id = ? AND es.exam_id = ?
    GROUP BY a.session_id
");
$jawaban_lain->execute([$uid, $sesi['exam_id'] ?? 0]);
$jawaban_lain = $jawaban_lain->fetchAll();

// 4. Cek soal ujian ini
$soal = db()->prepare("SELECT COUNT(*) AS jml FROM exam_questions WHERE exam_id = ?");
$soal->execute([$sesi['exam_id'] ?? 0]);
$jml_soal = $soal->fetchColumn();

// 5. Cek semua sesi user ini untuk ujian fisika
$semua_sesi = db()->prepare("
    SELECT es.id, es.status, es.score,
           (SELECT COUNT(*) FROM answers a WHERE a.session_id = es.id) AS jml_jawaban
    FROM exam_sessions es
    WHERE es.user_id = ? AND es.exam_id = ?
    ORDER BY es.id DESC
");
$semua_sesi->execute([$uid, $sesi['exam_id'] ?? 0]);
$semua_sesi = $semua_sesi->fetchAll();

// 6. Handle reset sesi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'reset_sesi') {
    db()->prepare("UPDATE exam_sessions SET status='active', score=0, submitted_at=NULL WHERE id=?")->execute([$sid]);
    echo '<script>alert("Sesi direset ke active. Siswa bisa ujian ulang."); window.location.href=window.location.href;</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Investigasi Sesi <?= $sid ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f1f5f9;font-size:13px;} .table th{background:#1e293b;color:#fff;}</style>
</head>
<body>
<div class="container py-4" style="max-width:1000px">
  <h4 class="fw-bold mb-4">🔬 Investigasi Mendalam — Sesi #<?= $sid ?></h4>

  <!-- Info Sesi -->
  <div class="card mb-3">
    <div class="card-header bg-dark text-white fw-bold">1. Info Sesi</div>
    <div class="card-body">
      <?php if (!$sesi): ?>
        <div class="alert alert-danger">Sesi #<?= $sid ?> tidak ditemukan!</div>
      <?php else: ?>
      <table class="table table-sm mb-0">
        <tr><th>Sesi ID</th><td><?= $sesi['id'] ?></td></tr>
        <tr><th>Ujian</th><td><?= htmlspecialchars((string)$sesi['ujian']) ?></td></tr>
        <tr><th>Exam ID</th><td><?= $sesi['exam_id'] ?></td></tr>
        <tr><th>User ID</th><td><?= $sesi['user_id'] ?></td></tr>
        <tr><th>Status</th><td><span class="badge bg-<?= $sesi['status']==='submitted'?'success':'warning' ?>"><?= $sesi['status'] ?></span></td></tr>
        <tr><th>Score</th><td><?= $sesi['score'] ?></td></tr>
        <tr><th>Submitted At</th><td><?= $sesi['submitted_at'] ?? '(null)' ?></td></tr>
        <tr><th>Jumlah Soal Ujian</th><td><?= $jml_soal ?></td></tr>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Jawaban di sesi ini -->
  <div class="card mb-3">
    <div class="card-header bg-<?= count($jawaban)>0?'success':'danger' ?> text-white fw-bold">
      2. Jawaban di Sesi #<?= $sid ?> — <?= count($jawaban) ?> jawaban ditemukan
    </div>
    <div class="card-body p-0">
      <?php if (empty($jawaban)): ?>
        <div class="p-3 text-danger fw-bold">⚠ Tidak ada jawaban sama sekali di tabel answers untuk sesi ini.</div>
      <?php else: ?>
      <table class="table table-sm mb-0">
        <thead><tr><th>Answer ID</th><th>Question ID</th><th>Jawaban Siswa</th><th>Kunci</th><th>Benar?</th><th>Poin</th></tr></thead>
        <tbody>
        <?php foreach ($jawaban as $j): 
          $benar = strtolower(trim($j['answer'])) === strtolower(trim($j['correct_answer']));
        ?>
        <tr class="<?= $benar?'table-success':'' ?>">
          <td><?= $j['id'] ?></td>
          <td><?= $j['question_id'] ?></td>
          <td><?= htmlspecialchars((string)$j['answer']) ?></td>
          <td><?= htmlspecialchars((string)$j['correct_answer']) ?></td>
          <td><?= $benar ? '✓' : '✗' ?></td>
          <td><?= $j['points'] ?? 1 ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Semua sesi user untuk ujian ini -->
  <div class="card mb-3">
    <div class="card-header bg-info text-white fw-bold">3. Semua Sesi User ID <?= $uid ?> untuk Ujian Ini</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>Sesi ID</th><th>Status</th><th>Score</th><th>Jml Jawaban</th><th>Keterangan</th></tr></thead>
        <tbody>
        <?php foreach ($semua_sesi as $s): ?>
        <tr class="<?= $s['id']==$sid?'table-warning':'' ?>">
          <td><?= $s['id'] ?> <?= $s['id']==$sid?'← INI':'' ?></td>
          <td><span class="badge bg-<?= $s['status']==='submitted'?'success':($s['status']==='active'?'warning':'danger') ?>"><?= $s['status'] ?></span></td>
          <td><?= $s['score'] ?></td>
          <td><?= $s['jml_jawaban'] ?></td>
          <td><?= $s['jml_jawaban']>0?'Ada jawaban':'Kosong' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Jawaban di sesi lain untuk ujian ini -->
  <?php if (!empty($jawaban_lain)): ?>
  <div class="card mb-3">
    <div class="card-header bg-warning fw-bold">4. Distribusi Jawaban per Sesi (Ujian yang Sama)</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>Sesi ID</th><th>Jml Jawaban</th><th>Status</th><th>Score</th></tr></thead>
        <tbody>
        <?php foreach ($jawaban_lain as $j): ?>
        <tr class="<?= $j['session_id']==$sid?'table-warning':'' ?>">
          <td><?= $j['session_id'] ?> <?= $j['session_id']==$sid?'← sesi bermasalah':'' ?></td>
          <td><?= $j['jml'] ?></td>
          <td><?= $j['status'] ?></td>
          <td><?= $j['score'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Kesimpulan & Aksi -->
  <div class="card mb-3">
    <div class="card-header bg-primary text-white fw-bold">5. Kesimpulan & Aksi</div>
    <div class="card-body">
      <?php if (empty($jawaban)): ?>
        <div class="alert alert-danger">
          <strong>Tidak ada jawaban tersimpan di sesi #<?= $sid ?>.</strong><br>
          Kemungkinan penyebab:
          <ul>
            <li>Siswa membuka ujian tapi tidak menjawab satu soal pun</li>
            <li>Koneksi internet putus saat mengerjakan sehingga jawaban tidak tersimpan</li>
            <li>Siswa langsung klik Submit tanpa menjawab</li>
          </ul>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <form method="POST">
            <input type="hidden" name="action" value="reset_sesi">
            <button type="submit" class="btn btn-warning"
              onclick="return confirm('Reset sesi #<?= $sid ?> ke active?\nSiswa bisa mengerjakan ulang UTS Fisika XI.')">
              🔄 Reset Sesi — Beri Kesempatan Ujian Ulang
            </button>
          </form>
          <a href="CEK_SISWA_NILAI_0.php?nama=STAFINDO+TUAH+PURBA" class="btn btn-secondary">← Kembali</a>
        </div>
      <?php else: ?>
        <div class="alert alert-success">Ada <?= count($jawaban) ?> jawaban ditemukan. Cek tabel di atas.</div>
      <?php endif; ?>
    </div>
  </div>

</div>
</body>
</html>
