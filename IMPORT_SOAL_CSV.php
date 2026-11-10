<?php
/**
 * IMPORT SOAL CSV
 * Script mandiri untuk import soal dari file CSV ke Bank Soal
 * Format: subject_id,type,difficulty_level,sub_topic,question_text,option_a,option_b,option_c,option_d,option_e,correct_answer,points
 */

session_start();
require_once __DIR__ . '/app/helpers.php';

// Auth check
if (!isLoggedIn()) {
    header('Location: ' . config('app_url') . '/auth/login');
    exit;
}
$user = auth();
if (!in_array($user['role'], ['admin', 'guru'])) {
    die('Akses ditolak.');
}

$results  = [];
$imported = 0;
$failed   = 0;
$errors   = [];
$preview  = [];

// Handle download template
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_import_soal.csv"');
    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['subject_id','type','difficulty_level','sub_topic','question_text','option_a','option_b','option_c','option_d','option_e','correct_answer','points']);
    fputcsv($out, [1,'multiple_choice','mudah','Bab 1 - Pengenalan','Berapakah hasil dari 2 + 2?','1','2','3','4','5','D',1]);
    fputcsv($out, [1,'multiple_choice','sedang','Bab 2 - Aljabar','Jika x = 5, maka 2x + 1 = ?','9','10','11','12','13','C',2]);
    fputcsv($out, [1,'multiple_choice','sulit','Bab 3 - Kalkulus','Turunan dari f(x) = x^2 adalah...','x','2x','x^2','2x^2','x/2','B',3]);
    fputcsv($out, [1,'essay','hots','Bab 4 - Essay','Jelaskan konsep integral dalam matematika!','','','','','','',5]);
    fputcsv($out, [1,'short_answer','','Bab 5 - Angka','Berapa hasil 10 x 10?','','','','','','100',2]);
    fclose($out);
    exit;
}

// Ambil daftar mapel untuk form
$subjects = db()->query("SELECT id, name FROM subjects ORDER BY name")->fetchAll();

$message = '';
$msgType = '';

// Handle POST upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Gagal upload file. Error code: ' . $file['error'];
        $msgType = 'error';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'])) {
            $message = 'Format file harus CSV (.csv atau .txt)';
            $msgType = 'error';
        } else {
            $tmpPath = $file['tmp_name'];
            $handle  = fopen($tmpPath, 'r');

            if (!$handle) {
                $message = 'Gagal membaca file.';
                $msgType = 'error';
            } else {
                // Detect & strip BOM
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }

                $rowNum  = 0;
                $header  = null;
                $validTypes = ['multiple_choice','essay','short_answer','short_answer_text','listening'];
                $validDiff  = ['mudah','sedang','sulit','hots',''];

                $pdo = db();

                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $rowNum++;

                    // Skip header row
                    if ($rowNum === 1) {
                        $header = array_map('trim', $row);
                        // Validate header
                        $expected = ['subject_id','type','difficulty_level','sub_topic','question_text','option_a','option_b','option_c','option_d','option_e','correct_answer','points'];
                        if (array_slice($header, 0, 12) !== $expected) {
                            // Lenient: try to parse anyway if count matches
                            if (count($header) < 12) {
                                $message = 'Header CSV tidak sesuai format. Download template untuk format yang benar.';
                                $msgType = 'error';
                                break;
                            }
                        }
                        continue;
                    }

                    // Skip empty rows
                    if (empty(array_filter($row))) continue;

                    // Pad row to 12 columns
                    while (count($row) < 12) $row[] = '';

                    $rowData = [
                        'subject_id'      => trim($row[0]),
                        'type'            => trim($row[1]),
                        'difficulty_level'=> trim($row[2]),
                        'sub_topic'       => trim($row[3]),
                        'question_text'   => trim($row[4]),
                        'option_a'        => trim($row[5]),
                        'option_b'        => trim($row[6]),
                        'option_c'        => trim($row[7]),
                        'option_d'        => trim($row[8]),
                        'option_e'        => trim($row[9]),
                        'correct_answer'  => trim($row[10]),
                        'points'          => trim($row[11]),
                    ];

                    // Validate
                    $rowErrors = [];

                    if (empty($rowData['subject_id']) || !is_numeric($rowData['subject_id'])) {
                        $rowErrors[] = 'subject_id tidak valid';
                    }
                    if (!in_array($rowData['type'], $validTypes)) {
                        $rowErrors[] = 'type tidak valid (harus: ' . implode(', ', $validTypes) . ')';
                    }
                    if ($rowData['difficulty_level'] !== '' && !in_array($rowData['difficulty_level'], ['mudah','sedang','sulit','hots'])) {
                        $rowErrors[] = 'difficulty_level tidak valid';
                    }
                    if (empty($rowData['question_text'])) {
                        $rowErrors[] = 'question_text kosong';
                    }
                    if (empty($rowData['points']) || !is_numeric($rowData['points'])) {
                        $rowData['points'] = 1;
                    }

                    // Check subject exists
                    if (empty($rowErrors)) {
                        $subStmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ?");
                        $subStmt->execute([$rowData['subject_id']]);
                        if (!$subStmt->fetch()) {
                            $rowErrors[] = 'subject_id ' . $rowData['subject_id'] . ' tidak ditemukan';
                        }
                    }

                    if (!empty($rowErrors)) {
                        $failed++;
                        $errors[] = "Baris $rowNum: " . implode(', ', $rowErrors);
                        $preview[] = array_merge($rowData, ['_row' => $rowNum, '_status' => 'GAGAL', '_reason' => implode(', ', $rowErrors)]);
                        continue;
                    }

                    // Insert
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO questions (
                                subject_id, type, difficulty_level, sub_topic, question_text,
                                option_a, option_b, option_c, option_d, option_e,
                                correct_answer, points, created_by, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            (int)$rowData['subject_id'],
                            $rowData['type'],
                            $rowData['difficulty_level'] !== '' ? $rowData['difficulty_level'] : null,
                            $rowData['sub_topic'] !== '' ? $rowData['sub_topic'] : null,
                            $rowData['question_text'],
                            $rowData['option_a'] !== '' ? $rowData['option_a'] : null,
                            $rowData['option_b'] !== '' ? $rowData['option_b'] : null,
                            $rowData['option_c'] !== '' ? $rowData['option_c'] : null,
                            $rowData['option_d'] !== '' ? $rowData['option_d'] : null,
                            $rowData['option_e'] !== '' ? $rowData['option_e'] : null,
                            $rowData['correct_answer'] !== '' ? $rowData['correct_answer'] : null,
                            (int)$rowData['points'],
                            $user['id'],
                        ]);
                        $imported++;
                        $preview[] = array_merge($rowData, ['_row' => $rowNum, '_status' => 'OK', '_reason' => '']);
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Baris $rowNum: DB error - " . $e->getMessage();
                        $preview[] = array_merge($rowData, ['_row' => $rowNum, '_status' => 'GAGAL', '_reason' => $e->getMessage()]);
                    }
                }

                fclose($handle);

                if ($msgType !== 'error') {
                    $message = "Import selesai: <strong>$imported soal berhasil</strong>" . ($failed > 0 ? ", <strong>$failed soal gagal</strong>" : '') . '.';
                    $msgType = $imported > 0 ? 'success' : 'warning';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Soal CSV - CAT Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; }
        .main-content { max-width: 960px; margin: 0 auto; padding: 2rem 1rem; }
        .card { background: white; border-radius: 1rem; padding: 1.5rem 2rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgb(0 0 0 / .08); margin-bottom: 1.5rem; }
        .btn-primary { background: #6366f1; border: none; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { background: #4f46e5; color: white; }
        .btn-success { background: #10b981; border: none; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-success:hover { background: #059669; color: white; }
        .btn-secondary { background: white; border: 1px solid #e2e8f0; color: #1e293b; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary:hover { background: #f8fafc; color: #1e293b; }
        .alert-success { background: #d1fae5; color: #065f46; border: none; border-radius: 0.75rem; padding: 1rem 1.25rem; }
        .alert-warning { background: #fef3c7; color: #92400e; border: none; border-radius: 0.75rem; padding: 1rem 1.25rem; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: none; border-radius: 0.75rem; padding: 1rem 1.25rem; }
        .table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table th { background: #f8fafc; color: #64748b; font-weight: 600; padding: 8px 10px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .badge-ok { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .badge-fail { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
        .upload-area { border: 2px dashed #c7d2fe; border-radius: 0.75rem; padding: 2rem; text-align: center; cursor: pointer; background: #f8fafc; transition: all 0.2s; }
        .upload-area:hover, .upload-area.dragover { border-color: #6366f1; background: #eff6ff; }
        .format-col { background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.85rem; color: #1e40af; }
    </style>
</head>
<body>

<div class="main-content">
    <a href="<?= config('app_url') ?>/question" style="display:inline-flex;align-items:center;gap:0.5rem;color:#64748b;text-decoration:none;margin-bottom:1.5rem;font-weight:600;">
        <i class="fas fa-arrow-left"></i> Kembali ke Bank Soal
    </a>

    <h2 style="font-size:1.75rem;font-weight:800;color:#1e293b;margin-bottom:1.5rem;">📥 Import Soal dari CSV</h2>

    <?php if ($message): ?>
    <div class="alert-<?= $msgType ?>" style="margin-bottom:1.5rem;padding:1rem 1.25rem;border-radius:0.75rem;">
        <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : ($msgType === 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
        <?= $message ?>
    </div>
    <?php endif; ?>

    <!-- Upload form -->
    <div class="card">
        <h5 style="font-weight:700;margin-bottom:1rem;color:#1e293b;"><i class="fas fa-upload" style="color:#6366f1;"></i> Upload File CSV</h5>
        
        <div class="format-col">
            <strong>Format kolom CSV (wajib urut):</strong><br>
            <code>subject_id, type, difficulty_level, sub_topic, question_text, option_a, option_b, option_c, option_d, option_e, correct_answer, points</code>
            <hr style="border-color:#bfdbfe;margin:8px 0;">
            <strong>Nilai valid untuk kolom:</strong><br>
            • <strong>type:</strong> <code>multiple_choice</code>, <code>essay</code>, <code>short_answer</code>, <code>short_answer_text</code>, <code>listening</code><br>
            • <strong>difficulty_level:</strong> <code>mudah</code>, <code>sedang</code>, <code>sulit</code>, <code>hots</code> (bisa kosong)<br>
            • <strong>correct_answer:</strong> huruf kapital opsi (A/B/C/D/E), angka, atau teks kalimat<br>
            • <strong>subject_id:</strong> ID mapel dari tabel subjects
        </div>

        <div style="margin-bottom:1rem;">
            <strong>Daftar ID Mata Pelajaran:</strong>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
                <?php foreach ($subjects as $s): ?>
                    <span style="background:#eff6ff;color:#1d4ed8;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:600;">#<?= $s['id'] ?> - <?= htmlspecialchars($s['name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area" id="uploadArea" onclick="document.getElementById('csvFile').click()">
                <i class="fas fa-file-csv" style="font-size:3rem;color:#94a3b8;margin-bottom:0.75rem;display:block;"></i>
                <p style="color:#64748b;margin:0;"><strong>Klik untuk pilih file CSV</strong> atau seret ke sini</p>
                <p style="color:#94a3b8;font-size:0.85rem;margin-top:4px;">Format: .csv — Encoding: UTF-8</p>
                <input type="file" name="csv_file" id="csvFile" accept=".csv,.txt" style="display:none;" onchange="showFileName(this)">
            </div>
            <div id="selectedFile" style="display:none;margin-top:8px;font-size:13px;color:#475569;"></div>

            <div style="margin-top:1.25rem;display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-file-import"></i> Proses Import
                </button>
                <a href="?download_template=1" class="btn-secondary">
                    <i class="fas fa-download"></i> Download Template CSV
                </a>
            </div>
        </form>
    </div>

    <?php if (!empty($preview)): ?>
    <!-- Hasil import -->
    <div class="card">
        <h5 style="font-weight:700;margin-bottom:1rem;color:#1e293b;">📊 Hasil Import</h5>
        <div style="display:flex;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap;">
            <div style="background:#dcfce7;color:#166534;border-radius:0.75rem;padding:0.75rem 1.25rem;font-weight:700;">
                ✅ Berhasil: <?= $imported ?>
            </div>
            <?php if ($failed > 0): ?>
            <div style="background:#fee2e2;color:#991b1b;border-radius:0.75rem;padding:0.75rem 1.25rem;font-weight:700;">
                ❌ Gagal: <?= $failed ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors)): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:0.5rem;padding:1rem;margin-bottom:1rem;">
            <strong style="color:#dc2626;">Detail Error:</strong>
            <ul style="margin:0.5rem 0 0 0;padding-left:1.25rem;font-size:13px;color:#991b1b;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Mapel ID</th>
                        <th>Tipe</th>
                        <th>Kesulitan</th>
                        <th>Sub Materi</th>
                        <th>Soal (preview)</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview as $p): ?>
                    <tr>
                        <td><?= $p['_row'] ?></td>
                        <td><span class="badge-<?= $p['_status'] === 'OK' ? 'ok' : 'fail' ?>"><?= $p['_status'] ?></span></td>
                        <td><?= htmlspecialchars($p['subject_id']) ?></td>
                        <td><?= htmlspecialchars($p['type']) ?></td>
                        <td><?= htmlspecialchars($p['difficulty_level']) ?></td>
                        <td><?= htmlspecialchars($p['sub_topic']) ?></td>
                        <td><?= htmlspecialchars(mb_substr(strip_tags($p['question_text']), 0, 60)) . (mb_strlen($p['question_text']) > 60 ? '...' : '') ?></td>
                        <td style="color:<?= $p['_status'] === 'OK' ? '#166534' : '#dc2626' ?>;"><?= htmlspecialchars($p['_reason']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($imported > 0): ?>
        <div style="margin-top:1rem;">
            <a href="<?= config('app_url') ?>/question" class="btn-success">
                <i class="fas fa-book"></i> Lihat Bank Soal (<?= $imported ?> soal baru)
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
    function showFileName(input) {
        if (input.files.length > 0) {
            document.getElementById('selectedFile').style.display = 'block';
            document.getElementById('selectedFile').innerHTML = '<i class="fas fa-file"></i> File dipilih: <strong>' + input.files[0].name + '</strong> (' + Math.round(input.files[0].size / 1024) + ' KB)';
        }
    }

    // Drag & drop
    const area = document.getElementById('uploadArea');
    area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', () => area.classList.remove('dragover'));
    area.addEventListener('drop', e => {
        e.preventDefault();
        area.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length) {
            document.getElementById('csvFile').files = files;
            showFileName(document.getElementById('csvFile'));
        }
    });
</script>
</body>
</html>
