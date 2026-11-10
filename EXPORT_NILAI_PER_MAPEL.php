<?php
require_once 'app/helpers.php';

$pdo = db();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Nilai Per Mata Pelajaran</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .controls { margin: 20px 0; }
        select { padding: 10px; font-size: 14px; width: 400px; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; margin-right: 10px; font-size: 14px; }
        button:hover { background-color: #45a049; }
        button.export { background-color: #2196F3; }
        button.export:hover { background-color: #0b7dda; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-submitted { background-color: #D4EDDA; }
        .status-active { background-color: #FFF3CD; }
        .status-suspended { background-color: #F8D7DA; }
        .multiple-attempts { background-color: #FFE5E5; font-weight: bold; }
        .summary { margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-left: 4px solid #4CAF50; }
        .info { background-color: #e7f3fe; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .warning { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
        .error-badge { background-color: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        .attempt-badge { background-color: #cfe2ff; color: #084298; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
    </style>
</head>
<body>

<div class="container">
<h1>📊 EXPORT NILAI PER MATA PELAJARAN</h1>

<div class="info">
    <strong>ℹ️ Informasi:</strong> Menampilkan semua siswa per mata pelajaran dengan detail submit berulang dan error.
</div>

<div class="controls">
    <form method="GET">
        <label><strong>Pilih Mata Pelajaran:</strong></label><br><br>
        <select name="subject_id" required onchange="this.form.submit()">
            <option value="">-- Pilih Mata Pelajaran --</option>
            <?php
            try {
                $stmt = $pdo->query("
                    SELECT DISTINCT s.id, s.name
                    FROM subjects s
                    JOIN exams e ON s.id = e.subject_id
                    ORDER BY s.name
                ");
                $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($subjects as $subject) {
                    $selected = (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : '';
                    echo "<option value='{$subject['id']}' {$selected}>{$subject['name']}</option>";
                }
            } catch (Exception $e) {
                echo "<option disabled>Error: " . $e->getMessage() . "</option>";
            }
            ?>
        </select>
        <button type="submit">Tampilkan</button>
        <?php if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])): ?>
            <button type="button" class="export" onclick="exportToCSV()">📥 Download CSV</button>
            <button type="button" class="export" onclick="window.print()">🖨️ Print</button>
        <?php endif; ?>
    </form>
</div>

<?php
if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];
    
    try {
        // Cek subject
        $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$subject) {
            echo "<p style='color: red;'>❌ Mata pelajaran tidak ditemukan</p>";
        } else {
            echo "<h2>Nilai Mata Pelajaran: {$subject['name']}</h2>";
            
            // Query untuk mendapatkan semua ujian di subject ini
            $stmt = $pdo->prepare("
                SELECT id, title FROM exams WHERE subject_id = ? ORDER BY id DESC
            ");
            $stmt->execute([$subject_id]);
            $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($exams) == 0) {
                echo "<p style='color: orange;'>ℹ️ Tidak ada ujian untuk mata pelajaran ini</p>";
            } else {
                echo "<p>Ujian: " . implode(", ", array_column($exams, 'title')) . "</p>";
                
                // Query untuk mendapatkan semua siswa dengan sessions di subject ini
                $exam_ids = array_column($exams, 'id');
                $placeholders = implode(',', array_fill(0, count($exam_ids), '?'));
                
                $query = "
                    SELECT 
                        u.id,
                        u.username,
                        u.full_name,
                        u.kelas,
                        e.id as exam_id,
                        e.title as exam_title,
                        es.id as session_id,
                        es.status,
                        es.score,
                        es.started_at,
                        es.submitted_at,
                        es.violation_count,
                        es.token_status,
                        es.suspension_reason,
                        es.is_best_score,
                        (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count,
                        (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as total_questions,
                        (SELECT COUNT(*) FROM exam_sessions WHERE user_id = u.id AND exam_id = e.id) as total_attempts
                    FROM users u
                    CROSS JOIN exams e
                    LEFT JOIN exam_sessions es ON u.id = es.user_id AND es.exam_id = e.id
                    WHERE e.id IN ({$placeholders}) AND u.role = 'siswa'
                    ORDER BY u.kelas, u.full_name, e.id, es.submitted_at DESC
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($exam_ids);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Kelompokkan per kelas
                $by_class = [];
                foreach ($results as $row) {
                    $class = $row['kelas'] ?: 'Tidak Ada Kelas';
                    if (!isset($by_class[$class])) {
                        $by_class[$class] = [];
                    }
                    $by_class[$class][] = $row;
                }
                
                // Tampilkan per kelas
                foreach ($by_class as $class_name => $students) {
                    echo "<h3>📚 Kelas: {$class_name}</h3>";
                    echo "<table id='dataTable'>";
                    echo "<tr>";
                    echo "<th>No</th>";
                    echo "<th>Username</th>";
                    echo "<th>Nama Siswa</th>";
                    echo "<th>Ujian</th>";
                    echo "<th>Status</th>";
                    echo "<th>Nilai</th>";
                    echo "<th>Best?</th>";
                    echo "<th>Jawaban</th>";
                    echo "<th>Attempts</th>";
                    echo "<th>Waktu Mulai</th>";
                    echo "<th>Waktu Submit</th>";
                    echo "<th>Pelanggaran</th>";
                    echo "<th>Keterangan</th>";
                    echo "</tr>";
                    
                    $no = 1;
                    $submitted_count = 0;
                    $error_count = 0;
                    $multiple_attempts = 0;
                    
                    // Group by student
                    $by_student = [];
                    foreach ($students as $row) {
                        $student_key = $row['id'];
                        if (!isset($by_student[$student_key])) {
                            $by_student[$student_key] = [
                                'username' => $row['username'],
                                'full_name' => $row['full_name'],
                                'sessions' => []
                            ];
                        }
                        if ($row['session_id']) {
                            $by_student[$student_key]['sessions'][] = $row;
                        } else {
                            $by_student[$student_key]['no_session'] = true;
                        }
                    }
                    
                    foreach ($by_student as $student) {
                        if (!isset($student['sessions']) || count($student['sessions']) == 0) {
                            // Belum ujian
                            echo "<tr>";
                            echo "<td>{$no}</td>";
                            echo "<td>{$student['username']}</td>";
                            echo "<td>{$student['full_name']}</td>";
                            echo "<td colspan='10' style='color: gray;'>Belum mengerjakan ujian</td>";
                            echo "</tr>";
                        } else {
                            // Tampilkan semua session
                            $first = true;
                            foreach ($student['sessions'] as $session) {
                                $status = $session['status'];
                                $row_class = '';
                                $keterangan = '';
                                
                                if ($status === 'submitted') {
                                    $row_class = 'status-submitted';
                                    $keterangan = '✓ Selesai Submit';
                                    $submitted_count++;
                                } elseif ($status === 'active') {
                                    $row_class = 'status-active';
                                    $keterangan = '⚠️ Masih Aktif/Error';
                                    $error_count++;
                                } elseif ($status === 'suspended') {
                                    $row_class = 'status-suspended';
                                    $keterangan = '❌ Suspended';
                                    $error_count++;
                                }
                                
                                if ($session['total_attempts'] > 1) {
                                    $row_class .= ' multiple-attempts';
                                    $keterangan .= " <span class='attempt-badge'>SUBMIT {$session['total_attempts']}x</span>";
                                    $multiple_attempts++;
                                }
                                
                                echo "<tr class='{$row_class}'>";
                                echo "<td>" . ($first ? $no : '') . "</td>";
                                echo "<td>" . ($first ? $student['username'] : '') . "</td>";
                                echo "<td>" . ($first ? $student['full_name'] : '') . "</td>";
                                echo "<td>{$session['exam_title']}</td>";
                                echo "<td><strong>" . ucfirst($status) . "</strong></td>";
                                echo "<td style='text-align: center; font-weight: bold;'>";
                                echo ($session['score'] !== null && $session['score'] > 0) ? number_format($session['score'], 2) . "%" : '-';
                                echo "</td>";
                                echo "<td style='text-align: center;'>";
                                echo ($session['is_best_score'] == 1) ? "✓" : "-";
                                echo "</td>";
                                echo "<td style='text-align: center;'>";
                                echo ($session['answer_count'] > 0) ? "{$session['answer_count']}/{$session['total_questions']}" : '-';
                                echo "</td>";
                                echo "<td style='text-align: center;'>";
                                echo ($session['total_attempts'] > 1) ? "<strong>{$session['total_attempts']}x</strong>" : "1";
                                echo "</td>";
                                echo "<td>" . ($session['started_at'] ? date('d/m H:i', strtotime($session['started_at'])) : '-') . "</td>";
                                echo "<td>" . ($session['submitted_at'] ? date('d/m H:i', strtotime($session['submitted_at'])) : '-') . "</td>";
                                echo "<td style='text-align: center;'>" . ($session['violation_count'] ?? '0') . "</td>";
                                echo "<td>{$keterangan}</td>";
                                echo "</tr>";
                                
                                $first = false;
                            }
                        }
                        $no++;
                    }
                    
                    echo "</table>";
                    
                    echo "<div class='summary'>";
                    echo "<h4>📊 Ringkasan Kelas {$class_name}:</h4>";
                    echo "<p><strong>Total Siswa:</strong> " . count($by_student) . "</p>";
                    echo "<p><span style='color: green;'>✓ Selesai Submit:</span> {$submitted_count}</p>";
                    echo "<p><span style='color: red;'>❌ Error/Suspended:</span> {$error_count}</p>";
                    echo "<p><span style='color: orange;'>⚠️ Submit Berulang (>1x):</span> {$multiple_attempts}</p>";
                    echo "</div>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}
?>

</div>

<script>
function exportToCSV() {
    let table = document.getElementById('dataTable');
    if (!table) {
        alert('Tidak ada data untuk di-export');
        return;
    }
    
    let csv = [];
    
    // Header
    let headers = [];
    for (let i = 0; i < table.rows[0].cells.length; i++) {
        headers.push(table.rows[0].cells[i].textContent);
    }
    csv.push(headers.join(';'));
    
    // Data
    for (let i = 1; i < table.rows.length; i++) {
        let row = [];
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            row.push('"' + table.rows[i].cells[j].textContent.replace(/"/g, '""') + '"');
        }
        csv.push(row.join(';'));
    }
    
    // Download
    let csvContent = csv.join('\n');
    let blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement('a');
    let url = URL.createObjectURL(blob);
    
    let subject = '<?php echo isset($_GET['subject_id']) ? $_GET['subject_id'] : 'semua'; ?>';
    link.setAttribute('href', url);
    link.setAttribute('download', 'Nilai_Mapel_' + subject + '_' + new Date().toISOString().split('T')[0] + '.csv');
    link.click();
}
</script>

</body>
</html>
