<?php
require_once 'app/helpers.php';

$pdo = db();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Rekam Jejak Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .controls { margin: 20px 0; }
        select { padding: 10px; font-size: 14px; width: 300px; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; margin-right: 10px; font-size: 14px; }
        button:hover { background-color: #45a049; }
        button.export { background-color: #2196F3; }
        button.export:hover { background-color: #0b7dda; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-submitted { background-color: #D4EDDA; }
        .status-active { background-color: #FFF3CD; }
        .status-suspended { background-color: #F8D7DA; }
        .summary { margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-left: 4px solid #4CAF50; }
        .info { background-color: #e7f3fe; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
    </style>
</head>
<body>

<div class="container">
<h1>📋 EXPORT REKAM JEJAK SISWA PER KELAS</h1>

<div class="info">
    <strong>ℹ️ Informasi:</strong> Menampilkan semua rekam jejak siswa yang mengerjakan ujian, termasuk yang error/tidak submit.
</div>

<div class="controls">
    <form method="GET">
        <label><strong>Pilih Kelas:</strong></label><br><br>
        <select name="kelas" required onchange="this.form.submit()">
            <option value="">-- Pilih Kelas --</option>
            <?php
            try {
                $stmt = $pdo->query("
                    SELECT DISTINCT kelas FROM users WHERE role = 'siswa' AND kelas IS NOT NULL AND kelas != ''
                    ORDER BY kelas
                ");
                $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($classes as $class) {
                    $selected = (isset($_GET['kelas']) && $_GET['kelas'] == $class) ? 'selected' : '';
                    echo "<option value='{$class}' {$selected}>{$class}</option>";
                }
            } catch (Exception $e) {
                echo "<option disabled>Error: " . $e->getMessage() . "</option>";
            }
            ?>
        </select>
        <button type="submit">Tampilkan</button>
        <?php if (isset($_GET['kelas']) && !empty($_GET['kelas'])): ?>
            <button type="button" class="export" onclick="exportToCSV()">📥 Download CSV</button>
            <button type="button" class="export" onclick="window.print()">🖨️ Print</button>
        <?php endif; ?>
    </form>
</div>

<?php
if (isset($_GET['kelas']) && !empty($_GET['kelas'])) {
    $kelas = $_GET['kelas'];
    
    try {
        echo "<h2>Rekam Jejak Siswa Kelas: {$kelas}</h2>";
        
        // Query untuk mendapatkan semua rekam jejak siswa (termasuk multiple attempts)
        $stmt = $pdo->prepare("
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
                es.remaining_time,
                es.violation_count,
                es.token_status,
                es.suspension_reason,
                es.is_best_score,
                (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count,
                (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as total_questions,
                (SELECT COUNT(*) FROM exam_sessions WHERE user_id = u.id AND exam_id = e.id) as total_attempts
            FROM users u
            LEFT JOIN exam_sessions es ON u.id = es.user_id
            LEFT JOIN exams e ON es.exam_id = e.id
            WHERE u.kelas = ? AND u.role = 'siswa'
            ORDER BY u.full_name, e.id DESC, es.submitted_at DESC
        ");
        $stmt->execute([$kelas]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) == 0) {
            echo "<p style='color: orange;'>ℹ️ Tidak ada siswa di kelas ini</p>";
        } else {
            // Kelompokkan per siswa
            $by_student = [];
            foreach ($results as $row) {
                $student_id = $row['id'];
                if (!isset($by_student[$student_id])) {
                    $by_student[$student_id] = [
                        'username' => $row['username'],
                        'full_name' => $row['full_name'],
                        'kelas' => $row['kelas'],
                        'sessions' => []
                    ];
                }
                if ($row['session_id']) {
                    $by_student[$student_id]['sessions'][] = $row;
                }
            }
            
            echo "<table id='dataTable'>";
            echo "<tr>";
            echo "<th>No</th>";
            echo "<th>Username</th>";
            echo "<th>Nama Siswa</th>";
            echo "<th>Ujian</th>";
            echo "<th>Attempt</th>";
            echo "<th>Status</th>";
            echo "<th>Nilai</th>";
            echo "<th>Best Score?</th>";
            echo "<th>Jawaban Terisi</th>";
            echo "<th>Waktu Mulai</th>";
            echo "<th>Waktu Submit</th>";
            echo "<th>Sisa Waktu (detik)</th>";
            echo "<th>Pelanggaran</th>";
            echo "<th>Token Status</th>";
            echo "<th>Alasan Suspend</th>";
            echo "</tr>";
            
            $no = 1;
            $total_students = 0;
            $total_sessions = 0;
            $submitted = 0;
            $active = 0;
            $suspended = 0;
            
            foreach ($by_student as $student_id => $student) {
                $total_students++;
                
                if (count($student['sessions']) == 0) {
                    // Siswa tidak ada session
                    echo "<tr>";
                    echo "<td>{$no}</td>";
                    echo "<td>{$student['username']}</td>";
                    echo "<td>{$student['full_name']}</td>";
                    echo "<td colspan='12' style='color: gray;'>Belum mengerjakan ujian</td>";
                    echo "</tr>";
                } else {
                    // Tampilkan semua session siswa
                    foreach ($student['sessions'] as $session) {
                        $total_sessions++;
                        $status = $session['status'];
                        $row_class = '';
                        
                        if ($status === 'submitted') {
                            $row_class = 'status-submitted';
                            $submitted++;
                        } elseif ($status === 'active') {
                            $row_class = 'status-active';
                            $active++;
                        } elseif ($status === 'suspended') {
                            $row_class = 'status-suspended';
                            $suspended++;
                        }
                        
                        echo "<tr class='{$row_class}'>";
                        echo "<td>{$no}</td>";
                        echo "<td>{$student['username']}</td>";
                        echo "<td>{$student['full_name']}</td>";
                        echo "<td>{$session['exam_title']}</td>";
                        echo "<td style='text-align: center;'>";
                        echo ($session['total_attempts'] > 1) ? "<strong>{$session['total_attempts']} kali</strong>" : "1";
                        echo "</td>";
                        echo "<td><strong>" . ucfirst($status) . "</strong></td>";
                        echo "<td style='text-align: center; font-weight: bold;'>";
                        echo ($session['score'] !== null && $session['score'] > 0) ? number_format($session['score'], 2) . "%" : '-';
                        echo "</td>";
                        echo "<td style='text-align: center;'>";
                        echo ($session['is_best_score'] == 1) ? "✓ YA" : "-";
                        echo "</td>";
                        echo "<td style='text-align: center;'>";
                        echo ($session['answer_count'] > 0) ? "{$session['answer_count']}/{$session['total_questions']}" : '-';
                        echo "</td>";
                        echo "<td>" . ($session['started_at'] ? date('d/m/Y H:i:s', strtotime($session['started_at'])) : '-') . "</td>";
                        echo "<td>" . ($session['submitted_at'] ? date('d/m/Y H:i:s', strtotime($session['submitted_at'])) : '-') . "</td>";
                        echo "<td style='text-align: center;'>" . ($session['remaining_time'] ?? '-') . "</td>";
                        echo "<td style='text-align: center;'>" . ($session['violation_count'] ?? '0') . "</td>";
                        echo "<td>" . ($session['token_status'] ?? '-') . "</td>";
                        echo "<td>" . ($session['suspension_reason'] ?? '-') . "</td>";
                        echo "</tr>";
                        
                        $no++;
                    }
                }
            }
            
            echo "</table>";
            
            echo "<div class='summary'>";
            echo "<h3>📊 RINGKASAN KELAS {$kelas}:</h3>";
            echo "<p><strong>Total Siswa:</strong> {$total_students}</p>";
            echo "<p><strong>Total Session:</strong> {$total_sessions}</p>";
            echo "<p><span style='color: green;'>✓ Selesai Submit:</span> {$submitted}</p>";
            echo "<p><span style='color: orange;'>⚠️ Masih Aktif:</span> {$active}</p>";
            echo "<p><span style='color: red;'>❌ Suspended:</span> {$suspended}</p>";
            echo "</div>";
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
    
    let kelas = '<?php echo isset($_GET['kelas']) ? $_GET['kelas'] : 'semua'; ?>';
    link.setAttribute('href', url);
    link.setAttribute('download', 'Rekam_Jejak_' + kelas + '_' + new Date().toISOString().split('T')[0] + '.csv');
    link.click();
}
</script>

</body>
</html>
