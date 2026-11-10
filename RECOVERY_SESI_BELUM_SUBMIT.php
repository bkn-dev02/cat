<?php
/**
 * Recovery Tool - Fix SEMUA Sesi Belum Submit
 * Diagnosa dan perbaiki semua sesi yang punya jawaban tapi tidak ter-submit
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/helpers.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'diagnosa';

// ── QUERY DIAGNOSA SEMUA ─────────────────────────────────────────────────────
function getDiagnosa($examId = null) {
    // total_soal: cek exam_questions dulu, kalau 0 fallback ke questions.exam_id
    $q = "
        SELECT
            es.id        AS session_id,
            es.exam_id,
            es.status,
            es.started_at,
            es.submitted_at,
            es.score,
            u.full_name,
            u.kelas,
            e.title      AS ujian,
            s.name       AS mapel,
            COUNT(a.id)  AS jumlah_jawaban,
            (SELECT COUNT(*) FROM exam_questions eq2 WHERE eq2.exam_id = es.exam_id) AS total_soal
        FROM exam_sessions es
        JOIN users u        ON u.id = es.user_id
        JOIN exams e        ON e.id = es.exam_id
        LEFT JOIN subjects s ON s.id = e.subject_id
        LEFT JOIN answers a  ON a.session_id = es.id
        WHERE es.status NOT IN ('submitted','graded','completed')
    ";
    $params = [];
    if ($examId) { $q .= " AND es.exam_id = ?"; $params[] = $examId; }
    $q .= "
        GROUP BY es.id, es.exam_id, es.status, es.started_at, es.submitted_at,
                 es.score, u.full_name, u.kelas, e.title, s.name
        HAVING jumlah_jawaban > 0
        ORDER BY e.title, u.kelas, u.full_name
    ";
    $stmt = db()->prepare($q);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── PROSES FIX ───────────────────────────────────────────────────────────────
$fixResult = [];
$fixSummary = ['fixed' => 0, 'skip' => 0, 'error' => 0];

if ($action === 'fix_all') {
    $examId = $_POST['exam_id_filter'] ?? null;
    $rows   = getDiagnosa($examId ?: null);

    foreach ($rows as $row) {
        $sid = $row['session_id'];
        try {
            // Hitung nilai dari jawaban tersimpan
            // Support dua struktur: via exam_questions ATAU langsung questions.exam_id
            $stmt = db()->prepare("
                SELECT
                    COUNT(a.id) AS total,
                    SUM(CASE WHEN LOWER(TRIM(a.answer)) = LOWER(TRIM(q.correct_answer)) THEN 1 ELSE 0 END) AS benar,
                    SUM(CASE WHEN LOWER(TRIM(a.answer)) = LOWER(TRIM(q.correct_answer)) THEN COALESCE(q.points, 1) ELSE 0 END) AS total_poin,
                    SUM(COALESCE(q.points, 1)) AS max_poin
                FROM answers a
                JOIN questions q ON q.id = a.question_id
                WHERE a.session_id = ?
                  AND q.id IN (SELECT question_id FROM exam_questions WHERE exam_id = ?)
            ");
            $stmt->execute([$sid, $row['exam_id']]);
            $h = $stmt->fetch();

            $total    = (int)$h['total'];
            $benar    = (int)$h['benar'];
            $maxPoin  = (float)$h['max_poin'];
            $totPoin  = (float)$h['total_poin'];
            // Gunakan SUM(points) langsung — sama persis dengan logika submit sistem
            $nilai = $totPoin;

            $upd = db()->prepare("
                UPDATE exam_sessions
                SET status       = 'submitted',
                    submitted_at = COALESCE(submitted_at, NOW()),
                    score        = ?
                WHERE id = ?
                  AND status NOT IN ('submitted','graded','completed')
            ");
            $upd->execute([$nilai, $sid]);

            if ($upd->rowCount() > 0) {
                $fixSummary['fixed']++;
                $fixResult[] = [
                    'ok'    => true,
                    'nama'  => $row['full_name'],
                    'kelas' => $row['kelas'],
                    'ujian' => $row['ujian'],
                    'mapel' => $row['mapel'],
                    'nilai' => $nilai,
                    'benar' => $benar,
                    'total' => $total,
                    'poin'  => $totPoin . '/' . $maxPoin,
                ];
            } else {
                $fixSummary['skip']++;
            }
        } catch (Exception $e) {
            $fixSummary['error']++;
            $fixResult[] = ['ok' => false, 'nama' => $row['full_name'], 'msg' => $e->getMessage()];
        }
    }
}

// ── AMBIL DATA DIAGNOSA UNTUK DITAMPILKAN ────────────────────────────────────
$examIdFilter = $_GET['exam_id'] ?? null;
$diagData     = getDiagnosa($examIdFilter ?: null);

// Kelompokkan per ujian
$grouped = [];
foreach ($diagData as $row) {
    $grouped[$row['ujian']][] = $row;
}

// Dropdown ujian
$allExams = db()->query("SELECT id, title FROM exams ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Recovery Sesi Belum Submit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f1f5f9; font-size:13px; }
  .card { border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
  .table th { background:#1e293b; color:#fff; font-size:12px; }
  .table td { font-size:12px; vertical-align:middle; }
  .ujian-header { background:#334155; color:#fff; padding:8px 14px; border-radius:6px 6px 0 0; font-weight:bold; }
  .summary-box { border-radius:10px; padding:16px 20px; text-align:center; }
  .summary-box .num { font-size:28px; font-weight:800; }
</style>
</head>
<body>
<div class="container py-4" style="max-width:1100px">

  <div class="d-flex align-items-center gap-3 mb-3">
    <a href="<?= config('app_url') ?>/dashboard" class="btn btn-secondary btn-sm">← Dashboard</a>
    <div>
      <h4 class="mb-0 text-danger fw-bold">🔧 Recovery Sesi Belum Submit</h4>
      <small class="text-muted">Perbaiki semua sesi yang punya jawaban tapi tidak ter-submit karena bug sistem</small>
    </div>
  </div>

  <?php if ($action === 'fix_all' && !empty($fixResult)): ?>
  <!-- Hasil Fix -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="summary-box bg-success text-white">
        <div class="num"><?= $fixSummary['fixed'] ?></div>
        <div>Sesi Berhasil Diperbaiki</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="summary-box bg-warning text-dark">
        <div class="num"><?= $fixSummary['skip'] ?></div>
        <div>Dilewati (sudah OK)</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="summary-box bg-danger text-white">
        <div class="num"><?= $fixSummary['error'] ?></div>
        <div>Error</div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header bg-success text-white fw-bold">✅ Detail Hasil Fix</div>
    <div class="card-body p-0" style="max-height:400px;overflow-y:auto">
      <table class="table table-sm mb-0">
        <thead><tr><th>Nama</th><th>Kelas</th><th>Ujian</th><th>Mapel</th><th>Nilai</th><th>Benar/Total</th></tr></thead>
        <tbody>
          <?php foreach ($fixResult as $r): ?>
          <tr class="<?= $r['ok'] ? '' : 'table-danger' ?>">
            <?php if ($r['ok']): ?>
              <td><?= htmlspecialchars($r['nama']) ?></td>
              <td><?= htmlspecialchars($r['kelas']) ?></td>
              <td><?= htmlspecialchars($r['ujian']) ?></td>
              <td><?= htmlspecialchars($r['mapel'] ?? '-') ?></td>
              <td><strong class="<?= $r['nilai'] >= 75 ? 'text-success' : 'text-danger' ?>"><?= $r['nilai'] ?></strong></td>
              <td><?= $r['benar'] ?>/<?= $r['total'] ?></td>
            <?php else: ?>
              <td colspan="6" class="text-danger">✗ <?= htmlspecialchars($r['nama']) ?>: <?= htmlspecialchars($r['msg']) ?></td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="alert alert-info">
    ✅ Proses selesai. Silakan buka laporan nilai untuk masing-masing ujian.
    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-primary btn-sm ms-2">🔍 Cek Ulang</a>
  </div>

  <?php elseif (empty($diagData)): ?>
  <div class="alert alert-success fs-6">
    ✅ <strong>Tidak ada sesi bermasalah!</strong> Semua data sudah bersih.
  </div>

  <?php else: ?>
  <!-- Diagnosa -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="summary-box bg-danger text-white">
        <div class="num"><?= count($diagData) ?></div>
        <div>Total Sesi Bermasalah</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="summary-box bg-warning text-dark">
        <div class="num"><?= count($grouped) ?></div>
        <div>Ujian Terdampak</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="summary-box bg-info text-white">
        <div class="num"><?= count(array_unique(array_column($diagData, 'kelas'))) ?></div>
        <div>Kelas Terdampak</div>
      </div>
    </div>
  </div>

  <!-- Filter per ujian (form terpisah, GET) -->
  <form method="GET" class="d-flex gap-2 align-items-center mb-3 flex-wrap">
    <select name="exam_id" class="form-select form-select-sm" style="width:300px">
      <option value="">Semua Ujian</option>
      <?php foreach ($allExams as $e): ?>
        <option value="<?= $e['id'] ?>" <?= $examIdFilter == $e['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($e['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
    <?php if ($examIdFilter): ?>
      <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <!-- Tombol Fix Semua (form terpisah, POST) -->
  <form method="POST" id="fixForm" class="mb-3">
    <input type="hidden" name="action" value="fix_all">
    <input type="hidden" name="exam_id_filter" value="<?= htmlspecialchars($examIdFilter ?? '') ?>">
    <button type="submit" class="btn btn-danger fw-bold px-4"
            onclick="return confirm('Fix SEMUA <?= count($diagData) ?> sesi bermasalah?\n\nNilai akan dihitung otomatis dari jawaban yang tersimpan.\n\nLanjutkan?')">
      🔧 Fix Semua <?= count($diagData) ?> Sesi Sekarang
    </button>
  </form>

  <!-- Tabel per Ujian -->
  <?php foreach ($grouped as $ujianNama => $rows): ?>
  <div class="card mb-3">
    <div class="ujian-header d-flex justify-content-between">
      <span>📋 <?= htmlspecialchars($ujianNama) ?></span>
      <span class="badge bg-danger"><?= count($rows) ?> sesi bermasalah</span>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Nama Siswa</th><th>Kelas</th><th>Mapel</th>
            <th>Status</th><th>Jawaban Tersimpan</th><th>Total Soal</th><th>Mulai Ujian</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($row['kelas']) ?></td>
            <td><?= htmlspecialchars($row['mapel'] ?? '-') ?></td>
            <td><span class="badge bg-warning text-dark"><?= $row['status'] ?></span></td>
            <td>
              <span class="badge bg-info text-dark"><?= $row['jumlah_jawaban'] ?> jawaban</span>
              <?php $pct = $row['total_soal'] > 0 ? round($row['jumlah_jawaban']/$row['total_soal']*100) : 0; ?>
              <small class="text-muted">(<?= $pct ?>%)</small>
            </td>
            <td><?= $row['total_soal'] ?></td>
            <td><small><?= $row['started_at'] ? date('d/m/Y H:i', strtotime($row['started_at'])) : '-' ?></small></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>

</div>
</body>
</html>
