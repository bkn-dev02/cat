<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Nilai - <?= htmlspecialchars($exam['title']) ?></title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #000; background: #f5f5f5; }

  .toolbar {
    background: #1e293b; color: #fff; padding: 12px 20px;
    display: flex; align-items: center; gap: 12px;
    position: sticky; top: 0; z-index: 100;
  }
  .toolbar h2 { font-size: 14px; flex: 1; }
  .btn-print { background: #10b981; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: bold; }
  .btn-back  { background: #64748b; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }

  .page { background: #fff; width: 210mm; margin: 20px auto; padding: 15mm; box-shadow: 0 2px 8px rgba(0,0,0,.15); }

  .filter-bar {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 16px; margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
  }
  .filter-group { flex: 1; min-width: 180px; }
  .filter-group label { display: block; font-size: 11px; font-weight: bold; color: #475569; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
  .filter-group select {
    width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px;
    font-size: 12px; color: #1e293b; background: #fff; cursor: pointer;
  }
  .filter-group select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
  .btn-filter {
    background: #1e293b; color: #fff; border: none; padding: 9px 20px; border-radius: 6px;
    cursor: pointer; font-size: 12px; font-weight: bold; height: 38px; display: flex; align-items: center; gap: 6px;
  }
  .btn-filter:hover { background: #334155; }

  .filter-info {
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px;
    padding: 10px 14px; margin-bottom: 15px; font-size: 12px; color: #166534;
    display: flex; align-items: center; gap: 8px;
  }

  .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 14px; }
  .kop h1 { font-size: 15px; text-transform: uppercase; letter-spacing: 1px; }
  .kop h2 { font-size: 13px; font-weight: normal; }
  .kop p  { font-size: 11px; color: #444; }

  .info-ujian table { width: 100%; margin-bottom: 14px; }
  .info-ujian td { padding: 2px 4px; font-size: 12px; }
  .info-ujian td:first-child { width: 130px; font-weight: bold; }

  .tabel-ringkasan { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
  .tabel-ringkasan th { background: #1e293b; color: #fff; padding: 6px 8px; text-align: center; border: 1px solid #0f172a; }
  .tabel-ringkasan td { border: 1px solid #ccc; padding: 5px 8px; }
  .tabel-ringkasan tr:nth-child(even) td { background: #f8fafc; }
  .nilai-cell { text-align: center; font-weight: bold; }
  .lulus { color: #059669; }
  .tidak-lulus { color: #dc2626; }
  .belum { color: #f59e0b; }

  .siswa-block { margin-bottom: 24px; page-break-inside: avoid; }
  .siswa-header { background: #1e293b; color: #fff; padding: 7px 12px; border-radius: 4px 4px 0 0; display: flex; justify-content: space-between; align-items: center; }
  .siswa-header .nama { font-weight: bold; font-size: 12px; }
  .nilai-badge { background: #10b981; padding: 2px 10px; border-radius: 12px; font-weight: bold; font-size: 13px; }
  .nilai-badge.rendah { background: #ef4444; }
  .nilai-badge.belum-ujian { background: #f59e0b; }

  .tabel-jawaban { width: 100%; border-collapse: collapse; font-size: 11px; }
  .tabel-jawaban th { background: #e2e8f0; padding: 5px 8px; text-align: left; border: 1px solid #ccc; }
  .tabel-jawaban td { border: 1px solid #ddd; padding: 5px 8px; vertical-align: top; }
  .tabel-jawaban tr:nth-child(even) td { background: #fafafa; }
  .no { width: 35px; text-align: center; }
  .soal { width: 45%; }
  .jwb-siswa, .jwb-benar { width: 15%; text-align: center; }
  .status-col { width: 10%; text-align: center; font-weight: bold; }
  .benar { color: #059669; }
  .salah { color: #dc2626; }

  .section-title { font-size: 13px; font-weight: bold; margin: 20px 0 10px; padding-bottom: 4px; border-bottom: 2px solid #1e293b; text-transform: uppercase; }
  .section-title .subtitle { font-weight: normal; font-size: 11px; color: #64748b; margin-left: 8px; }

  .stat-row { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
  .stat-box { flex: 1; min-width: 90px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; text-align: center; }
  .stat-box .angka { font-size: 20px; font-weight: bold; color: #1e293b; }
  .stat-box .label { font-size: 10px; color: #64748b; }

  .belum-ujian-row td { background: #fffbeb !important; color: #92400e; font-style: italic; }
  .empty-state { text-align: center; padding: 30px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b; }

  /* Style khusus untuk kolom per mapel */
  .mapel-header { background: #334155 !important; font-size: 10px; }
  .mapel-sub { background: #475569 !important; font-size: 9px; padding: 4px 6px !important; }
  .benar-cell { color: #059669; font-weight: bold; text-align: center; }
  .salah-cell { color: #dc2626; font-weight: bold; text-align: center; }
  .skor-cell { color: #7c3aed; font-weight: bold; text-align: center; }

  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .filter-bar { display: none !important; }
    .filter-info { display: none !important; }
    .page { margin: 0; padding: 12mm; box-shadow: none; width: 100%; }
    .siswa-block { page-break-inside: avoid; }
    .new-page { page-break-before: always; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <a href="<?= config('app_url') ?>/dashboard" class="btn-back">← Kembali</a>
  <h2>Laporan: <?= htmlspecialchars($exam['title']) ?><?= $kelas ? ' — ' . htmlspecialchars($kelas) : '' ?></h2>
  <button class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
</div>

<div class="page">

  <?php if ($subjectId): ?>
  <div class="filter-info">
    <span>ℹ️</span>
    <span><strong>Filter Aktif:</strong> Menampilkan detail soal hanya untuk mata pelajaran <strong><?= htmlspecialchars($exam['subject_name'] ?? 'Terpilih') ?></strong>.</span>
  </div>
  <?php endif; ?>

  <div class="kop">
    <h1>SMA Unggul Binakasih Nusantara</h1>
    <h2>Laporan Hasil Ujian</h2>
    <p>Dicetak: <?= date('d/m/Y H:i') ?> WIB</p>
  </div>

  <div class="info-ujian">
    <table>
      <tr><td>Nama Ujian</td><td>: <?= htmlspecialchars($exam['title']) ?></td></tr>
      <tr><td>Mata Pelajaran</td><td>: <?= htmlspecialchars($exam['subject_name'] ?? '-') ?></td></tr>
      <tr><td>Kelas</td><td>: <?= $kelas ? htmlspecialchars($kelas) : 'Semua Kelas' ?></td></tr>
      <tr><td>Jumlah Soal (Ditampilkan)</td><td>: <?= count($questions) ?> soal</td></tr>
      <tr><td>Jumlah Siswa</td><td>: <?= count($sessions) ?> siswa</td></tr>
    </table>
  </div>

  <?php
    $sudahUjian = array_filter($sessions, fn($s) => $s['session_id'] !== null);
    $belumUjian = array_filter($sessions, fn($s) => $s['session_id'] === null);
    $nilaiArr   = array_map(fn($s) => (float)$s['nilai'], $sudahUjian);
    $rataRata   = count($nilaiArr) ? round(array_sum($nilaiArr) / count($nilaiArr), 1) : 0;
    $tertinggi  = count($nilaiArr) ? max($nilaiArr) : 0;
    $terendah   = count($nilaiArr) ? min($nilaiArr) : 0;
    $lulus      = count(array_filter($nilaiArr, fn($n) => $n >= 75));
    $totalObjektif = count(array_filter($questions, fn($q) => !in_array($q['type'] ?? 'multiple_choice', ['essay', 'tkp'])));
  ?>

  <div class="stat-row">
    <div class="stat-box"><div class="angka"><?= count($sessions) ?></div><div class="label">Total Siswa</div></div>
    <div class="stat-box"><div class="angka"><?= count($sudahUjian) ?></div><div class="label">Sudah Ujian</div></div>
    <div class="stat-box"><div class="angka" style="color:#f59e0b"><?= count($belumUjian) ?></div><div class="label">Belum Ujian</div></div>
    <div class="stat-box"><div class="angka"><?= $rataRata ?></div><div class="label">Rata-rata</div></div>
    <div class="stat-box"><div class="angka" style="color:#059669"><?= $tertinggi ?></div><div class="label">Tertinggi</div></div>
    <div class="stat-box"><div class="angka" style="color:#dc2626"><?= $terendah ?></div><div class="label">Terendah</div></div>
    <div class="stat-box"><div class="angka" style="color:#059669"><?= $lulus ?></div><div class="label">Lulus (≥75)</div></div>
    <div class="stat-box"><div class="angka" style="color:#dc2626"><?= count($sudahUjian) - $lulus ?></div><div class="label">Tidak Lulus</div></div>
  </div>

  <div class="section-title">Ringkasan Nilai</div>
  
  <?php if (!$subjectId && count($allSubjectsInExam) > 1): ?>
  <!-- TABEL DENGAN BREAKDOWN PER MAPEL -->
  <table class="tabel-ringkasan">
    <thead>
      <tr>
        <th rowspan="2">No</th>
        <th rowspan="2">Nama Siswa</th>
        <th rowspan="2">Kelas</th>
        <th rowspan="2">Nilai</th>
        <?php foreach ($allSubjectsInExam as $subj): 
          $isTkp = (stripos($subj['name'], 'tkp') !== false);
        ?>
          <?php if ($isTkp): ?>
            <th class="mapel-header" colspan="1"><?= htmlspecialchars($subj['name']) ?></th>
          <?php else: ?>
            <th class="mapel-header" colspan="2"><?= htmlspecialchars($subj['name']) ?></th>
          <?php endif; ?>
        <?php endforeach; ?>
        <th rowspan="2">Keterangan</th>
      </tr>
      <tr>
        <?php foreach ($allSubjectsInExam as $subj): 
          $isTkp = (stripos($subj['name'], 'tkp') !== false);
        ?>
          <?php if ($isTkp): ?>
            <th class="mapel-sub">Skor</th>
          <?php else: ?>
            <th class="mapel-sub">B</th>
            <th class="mapel-sub">S</th>
          <?php endif; ?>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sessions as $i => $s):
        $sudah = $s['session_id'] !== null;
        $lls = $sudah && (float)$s['nilai'] >= 75;
      ?>
      <tr class="<?= !$sudah ? 'belum-ujian-row' : '' ?>">
        <td style="text-align:center"><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($s['full_name']) ?></td>
        <td style="text-align:center"><?= htmlspecialchars($s['kelas']) ?></td>
        <td class="nilai-cell <?= $sudah ? ($lls ? 'lulus' : 'tidak-lulus') : 'belum' ?>">
          <?= $sudah ? number_format((float)$s['nilai'], 0) : '-' ?>
        </td>
            <?php foreach ($allSubjectsInExam as $subj):
              $sid = $subj['id'];
              $isTkp = (stripos($subj['name'], 'tkp') !== false);
              
              if ($isTkp) {
                // Untuk TKP: ambil skor dari controller (sudah dihitung)
                $skorTkp = $s['skor_tkp_per_mapel'][$sid] ?? 0;
                echo '<td class="' . ($sudah ? 'skor-cell' : '') . '">' . ($sudah ? $skorTkp : '-') . '</td>';
              } else {
                // Untuk mapel biasa: hitung Benar & Salah
                $benar = $s['benar_per_mapel'][$sid] ?? 0;
                $totalSoalMapel = count(array_filter($questions, fn($q) => $q['subject_id'] == $sid && !in_array($q['type'], ['essay', 'tkp'])));
                $salah = max(0, $totalSoalMapel - $benar);
                echo '<td class="' . ($sudah ? 'benar-cell' : '') . '">' . ($sudah ? $benar : '-') . '</td>';
                echo '<td class="' . ($sudah ? 'salah-cell' : '') . '">' . ($sudah ? $salah : '-') . '</td>';
              }
            ?>
            <?php endforeach; ?>
        <td style="text-align:center" class="<?= $sudah ? ($lls ? 'lulus' : 'tidak-lulus') : 'belum' ?>">
          <?= $sudah ? ($lls ? 'Lulus' : 'Tidak Lulus') : 'Belum Ujian' ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php else: ?>
  <!-- TABEL SEDERHANA (jika filter mapel aktif atau hanya 1 mapel) -->
  <table class="tabel-ringkasan">
    <thead>
      <tr>
        <th>No</th><th>Nama Siswa</th><th>Kelas</th>
        <th>Nilai</th><th>Benar</th><th>Salah</th><th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sessions as $i => $s):
        $sudah = $s['session_id'] !== null;
        $benar = 0;
        if ($sudah) {
          foreach ($questions as $q) {
                $jwb   = strtolower(trim((string)($s['answers'][$q['id']] ?? '')));
                $kunci = strtolower(trim((string)($q['correct_answer'] ?? '')));
                $type  = $q['type'] ?? 'multiple_choice';
                
                if ($jwb === '' || $type === 'essay' || $type === 'tkp') continue;
                
                if ($type === 'short_answer') {
                    if (is_numeric($jwb) && is_numeric($kunci)) {
                        if (abs((float)$jwb - (float)$kunci) < 0.0001) $benar++;
                    } elseif ($jwb === $kunci) {
                        $benar++;
                    }
                } elseif ($type === 'listening' || $type === 'multiple_choice' || $type === 'short_answer_text') {
                    if ($jwb === $kunci) $benar++;
                } else {
                    if ($jwb === $kunci) $benar++;
                }
            }
        }
        $lls = $sudah && (float)$s['nilai'] >= 75;
      ?>
      <tr class="<?= !$sudah ? 'belum-ujian-row' : '' ?>">
        <td style="text-align:center"><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($s['full_name']) ?></td>
        <td style="text-align:center"><?= htmlspecialchars($s['kelas']) ?></td>
        <td class="nilai-cell <?= $sudah ? ($lls ? 'lulus' : 'tidak-lulus') : 'belum' ?>">
          <?= $sudah ? number_format((float)$s['nilai'], 0) : '-' ?>
        </td>
        <td style="text-align:center;font-weight:bold;color:#059669"><?= $sudah ? $benar : '-' ?></td>
        <td style="text-align:center;font-weight:bold;color:#dc2626"><?= $sudah ? ($totalObjektif - $benar) : '-' ?></td>
        <td style="text-align:center" class="<?= $sudah ? ($lls ? 'lulus' : 'tidak-lulus') : 'belum' ?>">
          <?= $sudah ? ($lls ? 'Lulus' : 'Tidak Lulus') : 'Belum Ujian' ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- DETAIL JAWABAN PER SISWA (DIFILTER) -->
  <?php if (count($sudahUjian) > 0 && count($questions) > 0): ?>
  <div class="section-title new-page">
    Detail Jawaban Per Siswa
    <span class="subtitle">(Hanya menampilkan soal dari mapel yang dipilih)</span>
  </div>

  <?php foreach ($sudahUjian as $s):
    $nilaiInt = (int)$s['nilai'];
    $rendah   = $nilaiInt < 75;
  ?>
  <div class="siswa-block">
    <div class="siswa-header">
      <span class="nama"><?= htmlspecialchars($s['full_name']) ?> — <?= htmlspecialchars($s['kelas']) ?></span>
      <span class="nilai-badge <?= $rendah ? 'rendah' : '' ?>">Nilai: <?= $nilaiInt ?></span>
    </div>
    <table class="tabel-jawaban">
      <thead>
        <tr>
          <th class="no">No</th>
          <th class="soal">Soal</th>
          <th class="jwb-siswa">Jawaban Siswa</th>
          <th class="jwb-benar">Kunci</th>
          <th class="status-col">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($questions as $q):
          $jwbSiswa = $s['answers'][$q['id']] ?? '-';
          $kunci    = (string)($q['correct_answer'] ?? '');
          $type     = $q['type'] ?? 'multiple_choice';
          $jwbLow   = strtolower(trim($jwbSiswa));
          $kunciLow = strtolower(trim($kunci));
        
          if ($type === 'essay') { $isBenar = null; } 
          elseif ($type === 'tkp') { $isBenar = null; } 
          elseif ($type === 'short_answer' && is_numeric($jwbLow) && is_numeric($kunciLow)) {
              $isBenar = $jwbSiswa !== '-' && abs((float)$jwbLow - (float)$kunciLow) < 0.0001;
          } elseif ($type === 'listening' || $type === 'multiple_choice' || $type === 'short_answer_text') {
              $isBenar = $jwbSiswa !== '-' && $jwbLow === $kunciLow;
          } else {
              $isBenar = $jwbSiswa !== '-' && $jwbLow === $kunciLow;
          }
            
          $tkpSkor = null;
          $tkpKunciLetter = null;
          if ($type === 'tkp') {
              $scoresMap = json_decode($q['option_scores'] ?? '', true) ?: [];
              if (!empty($scoresMap)) {
                  arsort($scoresMap);
                  $tkpKunciLetter = strtoupper((string) array_key_first($scoresMap));
              }
              if ($jwbSiswa !== '-') {
                  $tkpSkor = $scoresMap[strtolower($jwbSiswa)] ?? null;
              }
              $isBenar = $jwbSiswa !== '-' && $tkpKunciLetter !== null && strtoupper($jwbSiswa) === $tkpKunciLetter;
          }
        ?>
        <tr>
          <td class="no"><?= $q['question_number'] ?></td>
          <td class="soal">
            <?php if ($q['type'] === 'listening'): ?>
                <span style="background:#dbeafe;color:#1e40af;padding:1px 6px;border-radius:4px;font-size:10px;margin-right:4px;">🎧 Listening</span>
            <?php elseif ($q['type'] === 'tkp'): ?>
                <span style="background:#fce7f3;color:#9d174d;padding:1px 6px;border-radius:4px;font-size:10px;margin-right:4px;">📊 TKP</span>
            <?php endif; ?>
            <?= htmlspecialchars(mb_strimwidth(strip_tags($q['question_text']), 0, 120, '...')) ?>
          </td>
          <?php if ($type === 'tkp'): ?>
          <td class="jwb-siswa <?= $isBenar ? 'benar' : 'salah' ?>">
              <?= htmlspecialchars(strtoupper((string)$jwbSiswa)) ?><?= $tkpSkor !== null ? ' (skor ' . (int)$tkpSkor . ')' : '' ?>
          </td>
          <td class="jwb-benar benar"><?= $tkpKunciLetter ?? '—' ?></td>
          <td class="status-col <?= $isBenar ? 'benar' : 'salah' ?>"><?= $isBenar ? '✓' : '✗' ?></td>
          <?php elseif ($isBenar === null): ?>
          <td class="jwb-siswa" style="color:#64748b;"><?= htmlspecialchars(strtoupper((string)$jwbSiswa)) ?></td>
          <td class="jwb-benar" style="color:#64748b;">—</td>
          <td class="status-col" style="color:#64748b;">—</td>
          <?php else: ?>
          <td class="jwb-siswa <?= $isBenar ? 'benar' : 'salah' ?>"><?= htmlspecialchars(strtoupper((string)$jwbSiswa)) ?></td>
          <td class="jwb-benar benar"><?= htmlspecialchars(strtoupper((string)$kunci)) ?></td>
          <td class="status-col <?= $isBenar ? 'benar' : 'salah' ?>"><?= $isBenar ? '✓' : '✗' ?></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endforeach; ?>

  <?php elseif (count($sudahUjian) > 0 && count($questions) === 0): ?>
    <div class="empty-state">
      <strong>Tidak ada soal untuk mata pelajaran yang dipilih.</strong><br>
      <small>Ujian ini mungkin tidak memiliki soal untuk mapel tersebut, atau silakan pilih mata pelajaran lain.</small>
    </div>
  <?php endif; ?>

  <?php if (count($belumUjian) > 0): ?>
  <div class="section-title">Siswa Belum Mengerjakan Ujian</div>
  <table class="tabel-ringkasan">
    <thead>
      <tr><th>No</th><th>Nama Siswa</th><th>Kelas</th><th>Keterangan</th></tr>
    </thead>
    <tbody>
      <?php foreach ($belumUjian as $i => $s): ?>
      <tr>
        <td style="text-align:center"><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($s['full_name']) ?></td>
        <td style="text-align:center"><?= htmlspecialchars($s['kelas']) ?></td>
        <td style="text-align:center;color:#f59e0b;font-weight:bold">Belum Mengerjakan</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

</div>
</body>
</html>