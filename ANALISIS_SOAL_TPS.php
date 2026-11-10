<?php
/**
 * ANALISIS SOAL TPS - Difficulty Index & Konversi Nilai 200-1000
 * Mapel: PU(32), PPU(33), PBM(34), PK(35), LBI(36), LBE(37), PM(38)
 */
require_once 'app/helpers.php';

$pdo = db();


$TPS_SUBJECTS = [
    32 => ['kode' => 'PU',  'nama' => 'Penalaran Umum'],
    33 => ['kode' => 'PPU', 'nama' => 'Pemahaman Bacaan dan Menulis'],
    34 => ['kode' => 'PBM', 'nama' => 'Pengetahuan dan Pemahaman Umum'],
    35 => ['kode' => 'PK',  'nama' => 'Pengetahuan Kuantitatif'],
    36 => ['kode' => 'LBI', 'nama' => 'Literasi Bahasa Indonesia'],
    37 => ['kode' => 'LBE', 'nama' => 'Literasi Bahasa Inggris'],
    38 => ['kode' => 'PM',  'nama' => 'Penalaran Matematika'],
];


function konversiNilai($benar, $total) {
    if ($total == 0) return 200;
    $ratio = $benar / $total;
    return round(200 + ($ratio * 800));
}


function kategoriKesulitan($pct) {
    if ($pct >= 70) return ['label' => 'Mudah',  'color' => '#059669', 'bg' => '#d1fae5'];
    if ($pct >= 40) return ['label' => 'Sedang', 'color' => '#d97706', 'bg' => '#fef3c7'];
    if ($pct >= 20) return ['label' => 'Sulit',  'color' => '#dc2626', 'bg' => '#fee2e2'];
    return             ['label' => 'HOTS',   'color' => '#7c3aed', 'bg' => '#ede9fe'];
}


$examId     = (int)($_GET['exam_id'] ?? 0);
$filterMode = $_GET['mode'] ?? 'analisis'; // analisis | nilai
$filterKelas = $_GET['kelas'] ?? '';


$tpsIds = implode(',', array_keys($TPS_SUBJECTS));
$stmtExams = $pdo->query("
    SELECT e.id, e.title, s.name as subject_name, e.subject_id,
           COUNT(DISTINCT eq.question_id) as jumlah_soal
    FROM exams e
    JOIN subjects s ON e.subject_id = s.id
    LEFT JOIN exam_questions eq ON e.id = eq.exam_id
    WHERE e.subject_id IN ($tpsIds)
    GROUP BY e.id, e.title, s.name, e.subject_id
    ORDER BY s.name ASC, e.created_at DESC
    LIMIT 200
");
$semuaUjian = $stmtExams->fetchAll(PDO::FETCH_ASSOC);

$exam = null;
$questions = [];
$difficultyData = [];
$siswaNilai = [];
$siswaBelumSubmit = [];
$allKelas = [];
$examError = null;

if ($examId > 0) { 
    
    try {
    $stmt = $pdo->prepare("SELECT e.*, s.name as subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id = s.id WHERE e.id = ?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch();

    if ($exam) {
        
        $stmt = $pdo->prepare("
            SELECT q.id, q.question_text, q.correct_answer, q.type, q.subject_id,
                   s.name as subject_name,
                   eq.order_num as nomor
            FROM exam_questions eq
            JOIN questions q ON eq.question_id = q.id
            LEFT JOIN subjects s ON q.subject_id = s.id
            WHERE eq.exam_id = ?
            ORDER BY eq.order_num ASC
        ");
        $stmt->execute([$examId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

       
        $stmt = $pdo->prepare("
            SELECT es.id as session_id, es.user_id, u.full_name, u.kelas,
                   es.status, es.submitted_at
            FROM exam_sessions es
            JOIN users u ON es.user_id = u.id
            WHERE es.exam_id = ?
              AND (
                  es.submitted_at IS NOT NULL
                  OR es.status IN ('submitted','graded','completed','suspended')
              )
            ORDER BY u.kelas, u.full_name
        ");
        $stmt->execute([$examId]);
        
        $sesiByUser = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uid = $row['user_id'];
            if (!isset($sesiByUser[$uid]) || $row['submitted_at'] > ($sesiByUser[$uid]['submitted_at'] ?? '')) {
                $sesiByUser[$uid] = $row;
            }
        }
        $sesiList = array_values($sesiByUser);

        
        $allKelas = array_unique(array_column($sesiList, 'kelas'));
        sort($allKelas);

        
        if ($filterKelas !== '') {
            $sesiList = array_filter($sesiList, fn($s) => $s['kelas'] === $filterKelas);
            $sesiList = array_values($sesiList);
        }

        
        $stmtBelum = $pdo->prepare("
            SELECT DISTINCT u.id as user_id, u.full_name, u.kelas, es.status
            FROM exam_sessions es
            JOIN users u ON es.user_id = u.id
            WHERE es.exam_id = ?
              AND es.submitted_at IS NULL
              AND es.status NOT IN ('submitted','graded','completed','suspended')
              AND u.id NOT IN (
                  SELECT DISTINCT user_id FROM exam_sessions
                  WHERE exam_id = ? AND (submitted_at IS NOT NULL OR status IN ('submitted','graded','completed','suspended'))
              )
            ORDER BY u.kelas, u.full_name
        ");
        $stmtBelum->execute([$examId, $examId]);
        $siswaBelumSubmit = $stmtBelum->fetchAll(PDO::FETCH_ASSOC);
        if ($filterKelas !== '') {
            $siswaBelumSubmit = array_filter($siswaBelumSubmit, fn($s) => $s['kelas'] === $filterKelas);
            $siswaBelumSubmit = array_values($siswaBelumSubmit);
        }

        $totalSiswa = count($sesiList);

       
        if ($totalSiswa > 0) {
            $sessionIds = array_column($sesiList, 'session_id');
            $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
            $stmt = $pdo->prepare("
                SELECT a.session_id, a.question_id, a.answer
                FROM answers a
                WHERE a.session_id IN ($placeholders)
            ");
            $stmt->execute($sessionIds);
            $allAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);

          
            $answerIndex = [];
            foreach ($allAnswers as $a) {
                $answerIndex[$a['session_id']][$a['question_id']] = $a['answer'];
            }

          
            foreach ($questions as &$q) {
                $benarCount = 0;
                foreach ($sesiList as $sesi) {
                    $jwb   = strtolower(trim($answerIndex[$sesi['session_id']][$q['id']] ?? ''));
                    $kunci = strtolower(trim($q['correct_answer'] ?? ''));
                    if ($jwb !== '' && $jwb === $kunci) $benarCount++;
                }
                $pct = $totalSiswa > 0 ? round(($benarCount / $totalSiswa) * 100, 1) : 0;
                $q['benar_count'] = $benarCount;
                $q['difficulty_pct'] = $pct;
                $q['difficulty'] = kategoriKesulitan($pct);
            }
            unset($q);

            
            $questionIds = array_column($questions, 'id');
            $totalSoal = count($questions);

            foreach ($sesiList as $sesi) {
                $benar = 0;
                $detail = []; // [question_id => 1 atau 0]
                foreach ($questions as $q) {
                    $jwb   = strtolower(trim($answerIndex[$sesi['session_id']][$q['id']] ?? ''));
                    $kunci = strtolower(trim($q['correct_answer'] ?? ''));
                    $isBenar = ($jwb !== '' && $jwb === $kunci) ? 1 : 0;
                    $detail[$q['id']] = $isBenar;
                    $benar += $isBenar;
                }
                $nilaiSkala = konversiNilai($benar, $totalSoal);
                $siswaNilai[] = [
                    'user_id'    => $sesi['user_id'],
                    'full_name'  => $sesi['full_name'],
                    'kelas'      => $sesi['kelas'],
                    'benar'      => $benar,
                    'salah'      => $totalSoal - $benar,
                    'total_soal' => $totalSoal,
                    'nilai_skala'=> $nilaiSkala,
                    'detail'     => $detail,
                ];
            }
            // Urutkan dari nilai tertinggi
            usort($siswaNilai, fn($a, $b) => $b['nilai_skala'] - $a['nilai_skala']);
        }
    }
    } catch (\Exception $e) {
        $exam = null;
        $examError = $e->getMessage();
    }
}


// Statistik ringkasan
$statNilai = [];
if (!empty($siswaNilai)) {
    $arr = array_column($siswaNilai, 'nilai_skala');
    $statNilai = [
        'rata'      => round(array_sum($arr) / count($arr)),
        'tertinggi' => max($arr),
        'terendah'  => min($arr),
        'total'     => count($arr),
    ];
}

// Statistik difficulty
$diffStat = ['Mudah' => 0, 'Sedang' => 0, 'Sulit' => 0, 'HOTS' => 0];
foreach ($questions as $q) {
    $label = $q['difficulty']['label'] ?? 'Sedang';
    $diffStat[$label]++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analisis Soal TPS</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; background:#f1f5f9; color:#1e293b; }
.topbar { background:linear-gradient(135deg,#1e293b,#0f172a); color:#fff; padding:16px 24px;
          display:flex; align-items:center; gap:16px; }
.topbar h1 { font-size:18px; flex:1; }
.topbar a { color:#94a3b8; text-decoration:none; font-size:13px; }
.topbar a:hover { color:#fff; }
.container { max-width:1400px; margin:0 auto; padding:24px; }

/* Form pilih ujian */
.card { background:#fff; border-radius:12px; padding:20px 24px; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:20px; }
.card h3 { font-size:15px; font-weight:700; color:#1e293b; margin-bottom:12px; padding-bottom:8px; border-bottom:2px solid #e2e8f0; }
select, .btn { padding:10px 16px; border-radius:8px; font-size:14px; border:1px solid #e2e8f0; }
select { width:100%; max-width:600px; background:#fff; color:#1e293b; cursor:pointer; }
.btn { background:#3b82f6; color:#fff; border:none; cursor:pointer; font-weight:600; margin-left:8px; }
.btn:hover { background:#2563eb; }
.btn-green { background:#10b981; }
.btn-green:hover { background:#059669; }
.btn-purple { background:#7c3aed; }
.btn-purple:hover { background:#6d28d9; }

/* Tab */
.tabs { display:flex; gap:4px; margin-bottom:20px; border-bottom:2px solid #e2e8f0; }
.tab { padding:10px 20px; font-size:14px; font-weight:600; cursor:pointer; border-radius:8px 8px 0 0;
       color:#64748b; border:none; background:none; transition:all .2s; }
.tab.active { background:#3b82f6; color:#fff; }
.tab:not(.active):hover { background:#f1f5f9; color:#1e293b; }

/* Stat boxes */
.stat-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.stat-box { flex:1; min-width:120px; background:#fff; border-radius:10px; padding:14px 18px;
            text-align:center; box-shadow:0 2px 6px rgba(0,0,0,.07); border-top:4px solid #3b82f6; }
.stat-box.green  { border-top-color:#10b981; }
.stat-box.red    { border-top-color:#ef4444; }
.stat-box.yellow { border-top-color:#f59e0b; }
.stat-box.purple { border-top-color:#7c3aed; }
.stat-val { font-size:26px; font-weight:800; color:#1e293b; }
.stat-lbl { font-size:11px; color:#64748b; margin-top:2px; }

/* Tabel */
.tbl-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { background:#1e293b; color:#fff; padding:10px 12px; text-align:left; font-weight:600; }
td { padding:9px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tr:hover td { background:#f8fafc; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.no-data { text-align:center; padding:40px; color:#94a3b8; font-size:14px; }
.rank-1 td { background:#fffbeb !important; }
.rank-2 td { background:#f0fdf4 !important; }
.rank-3 td { background:#eff6ff !important; }

/* Bar difficulty */
.diff-bar { width:100%; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin-top:4px; }
.diff-fill { height:100%; border-radius:4px; transition:width .3s; }

/* Print */
@media print {
    .topbar .btn, .card:first-child, .tabs { display:none; }
    body { background:#fff; }
    .container { padding:0; }
}
</style>
</head>
<body>
<div class="topbar">
    <div>
        <div style="font-size:11px;color:#64748b;margin-bottom:2px">SMA Unggul Binakasih Nusantara</div>
        <h1>📊 Analisis Soal TPS — Difficulty Index & Nilai 200–1000</h1>
    </div>
    <a href="<?= config('app_url') ?>/dashboard">← Dashboard</a>
    <?php if ($exam): ?>
    <button class="btn btn-green" onclick="window.print()">🖨️ Cetak</button>
    <?php endif; ?>
</div>
<div class="container">

<!-- FORM PILIH UJIAN -->
<div class="card">
    <h3>🔍 Pilih Ujian untuk Dianalisis</h3>
    <form method="GET">
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
            <select name="exam_id" onchange="this.form.submit()">
                <option value="">-- Pilih Ujian TPS --</option>
                <?php
                // Kelompokkan per mapel untuk optgroup
                $grouped = [];
                foreach ($semuaUjian as $e) {
                    $grouped[$e['subject_name']][] = $e;
                }
                // Urutan tampil sesuai urutan mapel TPS
                $urutanMapel = ['PU','PPU','PBM','PK','LBI','LBE','PM'];
                uksort($grouped, function($a, $b) use ($urutanMapel) {
                    $ia = array_search($a, $urutanMapel);
                    $ib = array_search($b, $urutanMapel);
                    $ia = $ia === false ? 99 : $ia;
                    $ib = $ib === false ? 99 : $ib;
                    return $ia - $ib;
                });
                foreach ($grouped as $mapelNama => $ujianList):
                ?>
                <optgroup label="<?= htmlspecialchars($mapelNama) ?>">
                    <?php foreach ($ujianList as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $examId == $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['title']) ?> (<?= $e['jumlah_soal'] ?> soal)
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="mode" value="<?= htmlspecialchars($filterMode) ?>">
            <?php if ($examId && !empty($allKelas ?? [])): ?>
            <select name="kelas" onchange="this.form.submit()" style="max-width:200px;">
                <option value="">-- Semua Kelas --</option>
                <?php foreach (($allKelas ?? []) as $k): ?>
                <option value="<?= htmlspecialchars($k) ?>" <?= $filterKelas === $k ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
    </form>
    <?php if (empty($semuaUjian)): ?>
    <p style="color:#f59e0b;margin-top:12px;font-size:13px;">⚠️ Belum ada ujian dengan mapel TPS (PU/PK/PM/PPU/PBM/LBI/LBE).</p>
    <?php endif; ?>
</div>

<?php if ($examError): ?>
<div class="card" style="border-top:4px solid #ef4444;">
    <h3 style="color:#dc2626;">⚠️ Terjadi Error — Mohon laporkan ke admin</h3>
    <p style="margin-top:8px;font-size:13px;font-family:monospace;background:#fef2f2;padding:12px;border-radius:8px;"><?= htmlspecialchars($examError) ?></p>
</div>
<?php elseif ($exam): ?>
<!-- INFO UJIAN -->
<div class="card" style="padding:16px 24px;">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;">
            <div style="font-size:18px;font-weight:800;color:#1e293b"><?= htmlspecialchars($exam['title']) ?></div>
            <div style="color:#64748b;font-size:13px;margin-top:4px;">
                Mapel: <b><?= htmlspecialchars($exam['subject_name'] ?? '-') ?></b> &nbsp;|&nbsp;
                Soal: <b><?= count($questions) ?></b> &nbsp;|&nbsp;
                Peserta Submit: <b><?= count($siswaNilai) ?></b>
                <?= $filterKelas ? " &nbsp;|&nbsp; Kelas: <b>" . htmlspecialchars($filterKelas) . "</b>" : '' ?>
                <?php if (!empty($siswaBelumSubmit)): ?>
                &nbsp;|&nbsp; <span style="color:#f59e0b;font-weight:700">⚠️ <?= count($siswaBelumSubmit) ?> belum submit</span>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="?exam_id=<?= $examId ?>&mode=analisis" class="btn <?= $filterMode=='analisis'?'':'btn-green' ?>" style="text-decoration:none;display:inline-block;">📈 Analisis Soal</a>
            <a href="?exam_id=<?= $examId ?>&mode=nilai" class="btn <?= $filterMode=='nilai'?'':'btn-green' ?>" style="text-decoration:none;display:inline-block;">🏆 Nilai Siswa</a>
        </div>
    </div>
</div>

<?php if (!empty($siswaNilai)): ?>
<!-- STAT BOXES -->
<div class="stat-row">
    <div class="stat-box"><div class="stat-val"><?= $statNilai['total'] ?></div><div class="stat-lbl">Peserta</div></div>
    <div class="stat-box green"><div class="stat-val"><?= $statNilai['rata'] ?></div><div class="stat-lbl">Rata-rata (skala)</div></div>
    <div class="stat-box green"><div class="stat-val"><?= $statNilai['tertinggi'] ?></div><div class="stat-lbl">Tertinggi</div></div>
    <div class="stat-box red"><div class="stat-val"><?= $statNilai['terendah'] ?></div><div class="stat-lbl">Terendah</div></div>
    <div class="stat-box"><div class="stat-val"><?= $diffStat['Mudah'] ?></div><div class="stat-lbl">Soal Mudah</div></div>
    <div class="stat-box yellow"><div class="stat-val"><?= $diffStat['Sedang'] ?></div><div class="stat-lbl">Soal Sedang</div></div>
    <div class="stat-box red"><div class="stat-val"><?= $diffStat['Sulit'] ?></div><div class="stat-lbl">Soal Sulit</div></div>
    <div class="stat-box purple"><div class="stat-val"><?= $diffStat['HOTS'] ?></div><div class="stat-lbl">Soal HOTS</div></div>
</div>
<?php endif; ?>

<?php if ($filterMode === 'analisis'): ?>
<!-- ========== TAB ANALISIS SOAL ========== -->
<div class="card">
    <h3>📈 Analisis Tingkat Kesulitan Per Soal
        <span style="font-weight:400;font-size:12px;color:#64748b;margin-left:8px;">
            (Difficulty Index = % siswa yang menjawab benar)
        </span>
    </h3>
    <div style="margin-bottom:12px;font-size:12px;color:#64748b;display:flex;gap:16px;flex-wrap:wrap;">
        <span><b style="color:#059669">■</b> Mudah ≥ 70%</span>
        <span><b style="color:#d97706">■</b> Sedang 40–69%</span>
        <span><b style="color:#dc2626">■</b> Sulit 20–39%</span>
        <span><b style="color:#7c3aed">■</b> HOTS &lt; 20%</span>
    </div>
    <div class="tbl-wrap">
    <?php if (empty($questions)): ?>
        <div class="no-data">Tidak ada soal ditemukan.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:45px">No</th>
                <th>Teks Soal</th>
                <th style="width:100px">Mapel</th>
                <th style="width:80px">Kunci</th>
                <th style="width:100px">Benar</th>
                <th style="width:160px">Difficulty Index</th>
                <th style="width:90px">Kategori</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($questions as $i => $q):
            $diff = $q['difficulty'];
            $pct  = $q['difficulty_pct'] ?? 0;
            $benarCount = $q['benar_count'] ?? 0;
        ?>
        <tr>
            <td style="text-align:center;font-weight:700"><?= $q['nomor'] ?? ($i+1) ?></td>
            <td style="max-width:400px">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:380px;" title="<?= htmlspecialchars(strip_tags($q['question_text'])) ?>">
                    <?= htmlspecialchars(mb_strimwidth(strip_tags($q['question_text']), 0, 100, '...')) ?>
                </div>
            </td>
            <td>
                <span class="badge" style="background:#e2e8f0;color:#1e293b;">
                    <?= htmlspecialchars($q['subject_name'] ?? '-') ?>
                </span>
            </td>
            <td style="text-align:center;font-weight:800;font-size:16px;color:#3b82f6">
                <?= strtoupper(htmlspecialchars($q['correct_answer'] ?? '-')) ?>
            </td>
            <td style="text-align:center;">
                <?= $benarCount ?> / <?= count($siswaNilai) ?> siswa
            </td>
            <td>
                <div style="font-size:13px;font-weight:700;color:<?= $diff['color'] ?>"><?= $pct ?>%</div>
                <div class="diff-bar">
                    <div class="diff-fill" style="width:<?= $pct ?>%;background:<?= $diff['color'] ?>"></div>
                </div>
            </td>
            <td>
                <span class="badge" style="background:<?= $diff['bg'] ?>;color:<?= $diff['color'] ?>">
                    <?= $diff['label'] ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>
</div>

<?php elseif ($filterMode === 'nilai'): ?>
<!-- ========== TAB NILAI SISWA ========== -->
<div class="card">
    <h3>🏆 Nilai Siswa — Skala 200–1000
        <span style="font-weight:400;font-size:12px;color:#64748b;margin-left:8px;">
            (Rumus: 200 + (benar/total × 800))
        </span>
    </h3>
    <div class="tbl-wrap">
    <?php if (empty($siswaNilai)): ?>
        <div class="no-data">Belum ada siswa yang mengumpulkan ujian ini.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:45px">Rank</th>
                <th>Nama Siswa</th>
                <th style="width:80px">Kelas</th>
                <th style="width:70px">Benar</th>
                <th style="width:70px">Salah</th>
                <th style="width:80px">Total</th>
                <th style="width:80px">% Benar</th>
                <th style="width:110px">Nilai (200–1000)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($siswaNilai as $rank => $s):
            $pctBenar = $s['total_soal'] > 0 ? round(($s['benar'] / $s['total_soal']) * 100, 1) : 0;
            $rowClass = $rank === 0 ? 'rank-1' : ($rank === 1 ? 'rank-2' : ($rank === 2 ? 'rank-3' : ''));
            $medal = $rank === 0 ? '🥇' : ($rank === 1 ? '🥈' : ($rank === 2 ? '🥉' : ''));
        ?>
        <tr class="<?= $rowClass ?>">
            <td style="text-align:center;font-weight:700"><?= $medal ?: ($rank+1) ?></td>
            <td><b><?= htmlspecialchars($s['full_name']) ?></b></td>
            <td style="text-align:center"><?= htmlspecialchars($s['kelas']) ?></td>
            <td style="text-align:center;color:#059669;font-weight:700"><?= $s['benar'] ?></td>
            <td style="text-align:center;color:#dc2626;font-weight:700"><?= $s['salah'] ?></td>
            <td style="text-align:center"><?= $s['total_soal'] ?></td>
            <td style="text-align:center"><?= $pctBenar ?>%</td>
            <td style="text-align:center;">
                <?php
                $nilaiVal = $s['nilai_skala'];
                $nilaiColor = $nilaiVal >= 700 ? '#059669' : ($nilaiVal >= 500 ? '#d97706' : '#dc2626');
                ?>
                <span style="font-size:18px;font-weight:800;color:<?= $nilaiColor ?>"><?= $nilaiVal ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php else: // exam belum dipilih ?>
<div class="card">
    <div class="no-data" style="padding:60px;">
        <div style="font-size:48px;margin-bottom:16px;">📊</div>
        <div style="font-size:16px;font-weight:700;color:#1e293b;margin-bottom:8px;">Pilih ujian di atas untuk mulai analisis</div>
        <div style="font-size:13px;color:#94a3b8;">Tersedia untuk ujian dengan mapel: PU · PPU · PBM · PK · LBI · LBE · PM</div>
        <div style="margin-top:20px;font-size:12px;color:#cbd5e1;">
            <b>Keterangan skala nilai:</b><br>
            Nilai minimum = <b>200</b> (semua salah) &nbsp;|&nbsp; Nilai maksimum = <b>1000</b> (semua benar)<br>
            <b>Difficulty Index:</b> &lt;20% = HOTS · 20–39% = Sulit · 40–69% = Sedang · ≥70% = Mudah
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($exam && !empty($siswaBelumSubmit)): ?>
<!-- TABEL SISWA BELUM SUBMIT -->
<div class="card" style="border-top:4px solid #f59e0b;">
    <h3 style="color:#d97706;">⚠️ Siswa Belum Mengumpulkan (<?= count($siswaBelumSubmit) ?> siswa)</h3>
    <div class="tbl-wrap" style="margin-top:12px;">
    <table>
        <thead>
            <tr>
                <th style="width:45px">No</th>
                <th>Nama Siswa</th>
                <th style="width:150px">Kelas</th>
                <th style="width:120px">Status Sesi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($siswaBelumSubmit as $i => $sb): ?>
        <tr>
            <td style="text-align:center"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($sb['full_name']) ?></td>
            <td style="text-align:center"><?= htmlspecialchars($sb['kelas']) ?></td>
            <td style="text-align:center">
                <span class="badge" style="background:#fef3c7;color:#d97706;">
                    <?= htmlspecialchars($sb['status'] ?? 'active') ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

</div><!-- /container -->
</body>
</html>
