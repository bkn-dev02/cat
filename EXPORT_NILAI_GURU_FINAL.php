<?php
require_once 'app/helpers.php';

$pdo = db();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Nilai Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        form { margin: 20px 0; }
        select { padding: 10px; font-size: 14px; width: 300px; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; margin-right: 10px; font-size: 14px; }
        button:hover { background-color: #45a049; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .green { background-color: #D4EDDA; }
        .red { background-color: #F8D7DA; }
        .yellow { background-color: #FFF3CD; }
        .gray { background-color: #E2E3E5; }
        .summary { margin-top: 15px; padding: 15px; background-color: #f0f0f0; border-left: 4px solid #4CAF50; }
        .error { color: red; padding: 10px; background-color: #ffe6e6; border: 1px solid red; border-radius: 3px; }
        .info { color: #0066cc; padding: 10px; background-color: #e6f2ff; border: 1px solid #0066cc; border-radius: 3px; }
        .legend { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; }
        .legend-item { margin: 5px 0; padding: 5px; }
    </style>
</head>
<body>

<div class="container">
<h1>📊 EXPORT NILAI SISWA - LENGKAP DENGAN STATUS</h1>
<p>Pilih ujian untuk melihat nilai semua siswa (termasuk yang gagal submit)</p>

<form method="GET">
    <label><strong>Pilih Ujian:</strong></label><br><br>
    <select name="exam_id" required>
        <option value="">-- Pilih Ujian --</option>
        <?php
        try {
            $stmt = $pdo->query("
                SELECT e.id, e.title, s.name as subject_name
                FROM exams e
                LEFT JOIN subjects s ON e.subject_id = s.id
                ORDER BY e.id DESC
            ");
            $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($exams as $exam) {
                $selected = (isset($_GET['exam_id']) && $_GET['exam_id'] == $exam['id']) ? 'selected' : '';
                $subject = $exam['subject_name'] ? " ({$exam['subject_name']})" : '';
                echo "<option value='{$exam['id']}' {$selected}>{$exam['title']}{$subject}</option>";
            }
        } catch (Exception $e) {
            echo "<option disabled>Error: " . $e->getMessage() . "</option>";
        }
        ?>
    </select>
    <button type="submit">Tampilkan Nilai</button>
</form>

<?php
if (isset($_GET['exam_id']) && !empty($_GET['exam_id'])) {
    $exam_id = $_GET['exam_id'];
    
    try {
        // Cek exam
        $stmt = $pdo->prepare("
            SELECT e.id, e.title, s.name as subject_name
            FROM exams e
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE e.id = ?
        ");
        $stmt->execute([$exam_id]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exam) {
            echo "<div class='error'>❌ Ujian tidak ditemukan</div>";
        } else {
            echo "<h2>Nilai Ujian: {$exam['title']}" . ($exam['subject_name'] ? " ({$exam['subject_name']})" : "") . "</h2>";
            
            // Query untuk mendapatkan nilai siswa
            $stmt = $pdo->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.full_name,
                    u.kelas,
                    es.id as session_id,
                    es.status,
                    es.score,
                    es.submitted_at,
                    es.started_at,
                    (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count,
                    (SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?) as total_questions
                FROM users u
                LEFT JOIN exam_sessions es ON u.id = es.user_id AND es.exam_id = ?
                WHERE u.role = 'siswa'
                ORDER BY u.kelas, u.full_name
            ");
            $stmt->execute([$exam_id, $exam_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) == 0) {
                echo "<div class='info'>ℹ️ Tidak ada siswa ditemukan</div>";
            } else {
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
                    echo "<table>";
                    echo "<tr>";
                    echo "<th>No</th>";
                    echo "<th>Username</th>";
                    echo "<th>Nama Siswa</th>";
                    echo "<th>Status</th>";
                    echo "<th>Nilai</th>";
                    echo "<th>Jawaban Terisi</th>";
                    echo "<th>Waktu Mulai</th>";
                    echo "<th>Waktu Submit</th>";
                    echo "<th>Keterangan</th>";
                    echo "</tr>";
                    
                    $no = 1;
                    $submitted = 0;
                    $failed_submit = 0;
                    $no_session = 0;
                    $active = 0;
                    
                    foreach ($students as $student) {
                        $status = $student['status'];
                        $score = $student['score'];
                        $answer_count = $student['answer_count'];
                        $total_questions = $student['total_questions'];
                        
                        $row_class = '';
                        $keterangan = '';
                        $status_display = '';
                        
                        if ($status === null) {
                            $row_class = 'yellow';
                            $keterangan = 'Belum Ujian';
                            $status_display = 'Belum Ujian';
                            $no_session++;
                        } elseif ($status === 'submitted') {
                            $row_class = 'green';
                            $keterangan = '✓ Nilai Tercatat';
                            $status_display = 'Selesai & Submit';
                            $submitted++;
                        } elseif ($status === 'active' && $answer_count > 0) {
                            $row_class = 'red';
                            $keterangan = "⚠️ Masih Mengerjakan (Jawab {$answer_count}/{$total_questions})";
                            $status_display = 'Masih Mengerjakan';
                            $active++;
                        } elseif ($status === 'suspended') {
                            $row_class = 'red';
                            $keterangan = "❌ Suspended";
                            $status_display = 'Suspended';
                            $failed_submit++;
                        } else {
                            $row_class = 'gray';
                            $keterangan = $status;
                            $status_display = $status;
                        }
                        
                        echo "<tr class='{$row_class}'>";
                        echo "<td>{$no}</td>";
                        echo "<td>{$student['username']}</td>";
                        echo "<td>{$student['full_name']}</td>";
                        echo "<td><strong>{$status_display}</strong></td>";
                        echo "<td style='text-align: center; font-weight: bold;'>";
                        echo ($score !== null && $score > 0) ? number_format($score, 2) : '-';
                        echo "</td>";
                        echo "<td style='text-align: center;'>";
                        echo ($answer_count > 0) ? "{$answer_count}/{$total_questions}" : '-';
                        echo "</td>";
                        echo "<td>" . ($student['started_at'] ? date('d/m/Y H:i', strtotime($student['started_at'])) : '-') . "</td>";
                        echo "<td>" . ($student['submitted_at'] ? date('d/m/Y H:i', strtotime($student['submitted_at'])) : '-') . "</td>";
                        echo "<td>{$keterangan}</td>";
                        echo "</tr>";
                        
                        $no++;
                    }
                    
                    echo "</table>";
                    echo "<div class='summary'>";
                    echo "📊 <strong>Total: " . count($students) . " siswa</strong> | ";
                    echo "<span style='color: green;'>✓ Selesai Submit: {$submitted}</span> | ";
                    echo "<span style='color: orange;'>⚠️ Masih Mengerjakan: {$active}</span> | ";
                    echo "<span style='color: red;'>❌ Suspended/Gagal: {$failed_submit}</span> | ";
                    echo "<span style='color: gray;'>⭕ Belum Ujian: {$no_session}</span>";
                    echo "</div>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="legend">
    <h3>📋 Keterangan Warna & Status:</h3>
    <div class="legend-item"><span class="green" style='padding: 5px; display: inline-block; width: 20px;'></span> <strong>Hijau</strong> = Selesai & Submit (Nilai Tercatat)</div>
    <div class="legend-item"><span class="red" style='padding: 5px; display: inline-block; width: 20px;'></span> <strong>Merah</strong> = Masih Mengerjakan / Suspended</div>
    <div class="legend-item"><span class="yellow" style='padding: 5px; display: inline-block; width: 20px;'></span> <strong>Kuning</strong> = Belum Ujian</div>
    <div class="legend-item"><span class="gray" style='padding: 5px; display: inline-block; width: 20px;'></span> <strong>Abu</strong> = Status Lain</div>
</div>

</div>

</body>
</html>
