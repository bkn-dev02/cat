<?php
/**
 * Recalculate nilai semua sesi submitted menggunakan SUM(points) per soal
 * — sama persis dengan logika submit sistem asli
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/helpers.php';

$action    = $_POST['action'] ?? 'preview';
$examFilter = $_GET['exam_id'] ?? '';

// Ambil semua sesi submitted yang punya jawaban
$baseQuery = "
    SELECT es.id AS session_id, es.exam_id, es.score AS score_lama,
           u.full_name, u.kelas, e.title AS ujian,
           COALESCE(s.name, '(Tanpa Mapel)') AS mapel
    FROM exam_sessions es
    JOIN users u ON u.id = es.user_id
    JOIN exams e ON e.id = es.exam_id
    LEFT JOIN subjects s ON s.id = e.subject_id
    WHERE es.status IN ('submitted','graded','completed')
";
$baseParams = [];
if ($examFilter) {
    $baseQuery .= " AND es.exam_id = ?";
    $baseParams[] = $examFilter;
}
$baseQuery .= " ORDER BY s.name, u.kelas, u.full_name";
$rows = db()->prepare($baseQuery);
$rows->execute($baseParams);
$rows = $rows->fetchAll();

// Dropdown ujian
$allExams = db()->query("SELECT id, title FROM exams ORDER BY created_at DESC")->fetchAll();

// Hitung ulang nilai pakai SUM(points) — sama dengan logika submit
$recalcData = [];
foreach ($rows as $r) {
    $sid = $r['session_id'];
    $eid = $r['exam_id'];

    $stmt = db()->prepare("
        SELECT
            COUNT(a.id) AS total,
            SUM(CASE WHEN LOWER(TRIM(a.answer)) = LOWER(TRIM(q.correct_answer)) THEN 1 ELSE 0 END) AS benar,
            SUM(CASE WHEN LOWER(TRIM(a.answer)) = LOWER(TRIM(q.correct_answer)) THEN COALESCE(q.points, 1) ELSE 0 END) AS poin_benar,
            SUM(COALESCE(q.points, 1)) AS poin_max
        FROM answers a
        JOIN questions q ON q.id = a.question_id
        WHERE a.session_id = ?
          AND q.id IN (SELECT question_id FROM exam_questions WHERE exam_id = ?)
    ");
    $stmt->execute([$sid, $eid]);
    $h = $stmt->fetch();

    $nilaiBaru = (float)($h['poin_benar'] ?? 0);
    $selisih   = abs($nilaiBaru - (float)$r['score_lama']);

    $recalcData[] = [
        'session_id' => $sid,
        'exam_id'    => $eid,
        'full_name'  => $r['full_name'],
        'kelas'      => $r['kelas'],
        'ujian'      => $r['ujian'],
        'mapel'      => $r['mapel'],
        'score_lama' => $r['score_lama'],
        'score_baru' => $nilaiBaru,
        'benar'      => (int)($h['benar'] ?? 0),
        'total'      => (int)($h['total'] ?? 0),
        'poin_max'   => (float)($h['poin_max'] ?? 0),
        'selisih'    => $selisih,
        'perlu_fix'  => $selisih > 0.5,
    ];
}

$perluFix  = array_filter($recalcData, function($r) { return $r['perlu_fix']; });
$sudahBenar = array_filter($recalcData, function($r) { return !$r['perlu_fix']; });

// Proses update
$fixResult = ['updated' => 0, 'error' => 0];
if ($action === 'fix') {
    // Fix hanya yang perlu (selisih > 0.5)
    foreach ($perluFix as $r) {
        try {
            $stmt = db()->prepare("UPDATE exam_sessions SET score = ? WHERE id = ?");
            $stmt->execute([$r['score_baru'], $r['session_id']]);
            $fixResult['updated']++;
        } catch (Exception $e) {
            $fixResult['error']++;
        }
    }
    $qs = $examFilter ? '?exam_id='.$examFilter.'&done=1' : '?done=1';
    header('Location: ' . $_SERVER['PHP_SELF'] . $qs);
    exit;
} elseif ($action === 'fix_all') {
    // Recalculate SEMUA — termasuk yang poinnya baru diubah
    foreach ($recalcData as $r) {
        try {
            $stmt = db()->prepare("UPDATE exam_sessions SET score = ? WHERE id = ?");
            $stmt->execute([$r['score_baru'], $r['session_id']]);
            $fixResult['updated']++;
        } catch (Exception $e) {
            $fixResult['error']++;
        }
    }
    $qs = $examFilter ? '?exam_id='.$examFilter.'&done=1&all=1' : '?done=1&all=1';
    header('Location: ' . $_SERVER['PHP_SELF'] . $qs);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Recalculate Nilai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f1f5f9; font-size:13px; }
  .table th { background:#1e293b; color:#fff; font-size:12px; }
  .card { border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,.08); }
</style>
</head>
<body>
<div class="container py-4" style="max-width:1100px">

  <div class="d-flex align-items-center gap-3 mb-3">
    <a href="<?= config('app_url') ?>/dashboard" class="btn btn-secondary btn-sm">← Dashboard</a>
    <div>
      <h4 class="mb-0 fw-bold">🔄 Recalculate Nilai (Pakai Points Per Soal)</h4>
      <small class="text-muted">Hitung ulang nilai semua sesi menggunakan SUM(points) — sama dengan logika submit sistem</small>
    </div>
  </div>

  <?php if (isset($_GET['done'])): ?>
  <div class="alert alert-success">
    ✅ <?= isset($_GET['all']) ? 'Semua nilai berhasil dihitung ulang.' : 'Nilai yang tidak sesuai berhasil diperbarui.' ?>
    Halaman ini menampilkan kondisi terkini.
  </div>
  <?php endif; ?>

  <!-- Filter ujian -->
  <form method="GET" class="card p-3 mb-4 d-flex flex-row gap-2 align-items-end flex-wrap">
    <div>
      <label class="form-label fw-bold mb-1">Filter Ujian</label>
      <select name="exam_id" class="form-select form-select-sm" style="width:320px">
        <option value="">Semua Ujian</option>
        <?php foreach ($allExams as $e): ?>
          <option value="<?= $e['id'] ?>" <?= $examFilter == $e['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($e['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
    <?php if ($examFilter): ?>
      <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <!-- Ringkasan -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card text-center p-3">
        <div style="font-size:26px;font-weight:800"><?= count($recalcData) ?></div>
        <div class="text-muted">Total Sesi</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center p-3 border-danger">
        <div style="font-size:26px;font-weight:800;color:#dc2626"><?= count($perluFix) ?></div>
        <div class="text-muted">Nilai Perlu Dikoreksi</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center p-3 border-success">
        <div style="font-size:26px;font-weight:800;color:#16a34a"><?= count($sudahBenar) ?></div>
        <div class="text-muted">Nilai Sudah Benar</div>
      </div>
    </div>
  </div>

  <?php if (count($perluFix) > 0): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span>⚠ Ada <strong><?= count($perluFix) ?></strong> sesi dengan nilai yang tidak sesuai poin per soal.</span>
    <div class="d-flex gap-2">
      <form method="POST">
        <input type="hidden" name="action" value="fix">
        <?php if ($examFilter): ?><input type="hidden" name="exam_id" value="<?= $examFilter ?>"> <?php endif; ?>
        <button type="submit" class="btn btn-warning btn-sm fw-bold"
                onclick="return confirm('Update <?= count($perluFix) ?> nilai yang tidak sesuai?')">
          🔧 Fix Yang Tidak Sesuai (<?= count($perluFix) ?>)
        </button>
      </form>
      <form method="POST">
        <input type="hidden" name="action" value="fix_all">
        <?php if ($examFilter): ?><input type="hidden" name="exam_id" value="<?= $examFilter ?>"> <?php endif; ?>
        <button type="submit" class="btn btn-danger btn-sm fw-bold"
                onclick="return confirm('Hitung ulang SEMUA <?= count($recalcData) ?> nilai berdasarkan poin soal terkini?\n\nGunakan ini setelah mengubah poin soal.')">
          🔄 Recalculate Semua (<?= count($recalcData) ?>)
        </button>
      </form>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span>✅ Semua nilai sudah sesuai dengan poin per soal.</span>
    <form method="POST">
      <input type="hidden" name="action" value="fix_all">
      <?php if ($examFilter): ?><input type="hidden" name="exam_id" value="<?= $examFilter ?>"> <?php endif; ?>
      <button type="submit" class="btn btn-outline-primary btn-sm"
              onclick="return confirm('Hitung ulang SEMUA <?= count($recalcData) ?> nilai berdasarkan poin soal terkini?\n\nGunakan ini setelah mengubah poin soal.')">
        🔄 Recalculate Ulang (poin soal berubah?)
      </button>
    </form>
  </div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header bg-danger text-white fw-bold">⚠ Nilai Yang Perlu Dikoreksi</div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>Nama</th><th>Kelas</th><th>Mapel</th><th>Ujian</th>
              <th>Nilai Lama</th><th>Nilai Baru (Benar)</th><th>Benar/Total</th><th>Selisih</th></tr>
        </thead>
        <tbody>
          <?php $no=1; foreach ($perluFix as $r): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($r['kelas']) ?></td>
            <td><?= htmlspecialchars($r['mapel']) ?></td>
            <td><small><?= htmlspecialchars($r['ujian']) ?></small></td>
            <td><span class="badge bg-secondary"><?= $r['score_lama'] ?></span></td>
            <td><span class="badge <?= $r['score_baru'] >= 75 ? 'bg-success' : 'bg-danger' ?>"><?= $r['score_baru'] ?></span></td>
            <td><?= $r['benar'] ?>/<?= $r['total'] ?> (max <?= $r['poin_max'] ?> poin)</td>
            <td><span class="text-danger fw-bold"><?= round($r['selisih'], 1) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (count($sudahBenar) > 0): ?>
  <details>
    <summary class="btn btn-outline-secondary btn-sm mb-2">Lihat <?= count($sudahBenar) ?> nilai yang sudah benar</summary>
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>#</th><th>Nama</th><th>Kelas</th><th>Mapel</th><th>Nilai</th><th>Benar/Total</th></tr></thead>
          <tbody>
            <?php $no=1; foreach ($sudahBenar as $r): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($r['full_name']) ?></td>
              <td><?= htmlspecialchars($r['kelas']) ?></td>
              <td><?= htmlspecialchars($r['mapel']) ?></td>
              <td><span class="badge <?= $r['score_baru'] >= 75 ? 'bg-success' : 'bg-danger' ?>"><?= $r['score_baru'] ?></span></td>
              <td><?= $r['benar'] ?>/<?= $r['total'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </details>
  <?php endif; ?>

</div>
</body>
</html>
