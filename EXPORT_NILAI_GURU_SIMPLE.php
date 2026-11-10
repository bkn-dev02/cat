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
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        form { margin: 20px 0; }
        select { padding: 8px; font-size: 14px; }
        button { padding: 8px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; margin-right: 10px; }
        button:hover { background-color: #45a049; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .green { background-color: #D4EDDA; }
        .red { background-color: #F8D7DA; }
        .yellow { background-color: #FFF3CD; }
        .gray { background-color: #E2E3E5; }
        .summary { margin-top: 15px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; }
    </style>
</head>
<body>

<h1>📊 EXPORT NILAI SISWA - LENGKAP DENGAN STATUS</h1>
<p>Pilih ujian untuk melihat nilai semua siswa (termasuk yang gagal submit)</p>

<form method="GET">
    <label>Pilih Ujian: </label>
    <select name="exam_id" required>
        <option value="">-- Pilih Ujian --</option>
        <?php
        try {
            $stmt = $pdo->query("
                SELECT DISTINCT e.id, e.title
                FROM exams e
                ORDER BY e.id DESC
            ");
            $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($exams as $exam) {
                $selected = (isset($_GET['exam_id']) && $_GET['exam_id'] == $exam['id']) ? 'selected' : '';
                echo "<option value='{$exam['id']}' {$selected}>{$exam['title']}</option>";
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
        $stmt = $pdo->prepare("SELECT id, title FROM exams WHERE id = ?");
        $stmt->execute([$exam_id]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exam) {
            echo "<p style='color: red;'>Ujian tidak ditemukan</p>";
        } else {
            echo "<h2>Nilai Ujian: {$exam['title']}</h2>";
            
            // Query untuk mendapatkan nilai siswa
            $stmt = $pdo->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.name,
                    c.name as class_name,
                    es.id as session_id,
                    es.status,
                    es.score,
                    es.submitted_at,
                    es.start_time,
                    (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count,
                    (SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?) as total_questions
                FROM users u
                LEFT JOIN classes c ON u.class_id = c.id
                LEFT JOIN exam_sessions es ON u.id = es.user_id AND es.exam_id = ?
                WHERE u.class_id IN (
                    SELECT DISTINCT class_id FROM exam_assignments WHERE exam_id = ?
                )
                ORDER BY c.name, u.name
            ");
            $stmt->execute([$exam_id, $exam_id, $exam_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) == 0) {
                echo "<p style='color: orange;'>Tidak ada siswa yang terdaftar untuk ujian ini</p>";
            } else {
                // Kelompokkan per kelas
                $by_class = [];
                foreach ($results as $row) {
                    $class = $row['class_name'] ?: 'Tidak Ada Kelas';
                    if (!isset($by_class[$class])) {
                        $by_class[$class] = [];
                    }
                    $by_class[$class][] = $row;
                }
                
                // Tampilkan per kelas
                foreach ($by_class as $class_name => $students) {
                    echo "<h3>Kelas: {$class_name}</h3>";
                    echo "<table>";
                    echo "<tr>";
                    echo "<th>No</th>";
                    echo "<th>Username</th>";
                    echo "<th>Nama Siswa</th>";
                    echo "<th>Status</th>";
                    echo "<th>Nilai</th>";
                    echo "<th>Jawaban</th>";
                    echo "<th>Waktu Mulai</th>";
                    echo "<th>Waktu Submit</th>";
                    echo "<th>Keterangan</th>";
                    echo "</tr>";
                    
                    $no = 1;
                    $submitted = 0;
                    $failed_submit = 0;
                    $no_session = 0;
                    
                    foreach ($students as $student) {
                        $status = $student['status'];
                        $score = $student['score'];
                        $answer_count = $student['answer_count'];
                        $total_questions = $student['total_questions'];
                        
                        $row_class = '';
                        $keterangan = '';
                        
                        if ($status === null) {
                            $row_class = 'yellow';
                            $keterangan = 'Belum Ujian';
                            $no_session++;
                        } elseif ($status === 'completed' || $status === 'submitted') {
                            $row_class = 'green';
                            $keterangan = 'Nilai Tercatat ✓';
                            $submitted++;
                        } elseif ($status === 'failed_submit' && $answer_count > 0) {
                            $row_class = 'red';
                            $keterangan = "Gagal Submit (Jawab {$answer_count}/{$total_questions})";
                            $failed_submit++;
                        } else {
                            $row_class = 'gray';
                            $keterangan = $status;
                        }
                        
                        echo "<tr class='{$row_class}'>";
                        echo "<td>{$no}</td>";
                        echo "<td>{$student['username']}</td>";
                        echo "<td>{$student['name']}</td>";
                        echo "<td><strong>" . ucfirst($status ?? 'Belum Ujian') . "</strong></td>";
                        echo "<td style='text-align: center; font-weight: bold;'>";
                        echo ($score !== null) ? number_format($score, 2) : '-';
                        echo "</td>";
                        echo "<td style='text-align: center;'>";
                        echo ($answer_count > 0) ? "{$answer_count}/{$total_questions}" : '-';
                        echo "</td>";
                        echo "<td>" . ($student['start_time'] ? date('d/m/Y H:i', strtotime($student['start_time'])) : '-') . "</td>";
                        echo "<td>" . ($student['submitted_at'] ? date('d/m/Y H:i', strtotime($student['submitted_at'])) : '-') . "</td>";
                        echo "<td>{$keterangan}</td>";
                        echo "</tr>";
                        
                        $no++;
                    }
                    
                    echo "</table>";
                    echo "<div class='summary'>";
                    echo "Total: " . count($students) . " | ";
                    echo "<span style='color: green;'>✓ Selesai Submit: {$submitted}</span> | ";
                    echo "<span style='color: red;'>✗ Gagal Submit (Ada Jawaban): {$failed_submit}</span> | ";
                    echo "<span style='color: orange;'>⚠ Belum Ujian: {$no_session}</span>";
                    echo "</div>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<hr>
<p style='color: #666; font-size: 12px;'>
    <strong>Keterangan Warna:</strong><br>
    <span class='green' style='padding: 5px;'>Hijau = Selesai & Submit (Nilai Tercatat)</span><br>
    <span class='red' style='padding: 5px;'>Merah = Gagal Submit (Tapi Sudah Mengerjakan - Perlu Input Manual)</span><br>
    <span class='yellow' style='padding: 5px;'>Kuning = Belum Ujian</span><br>
    <span class='gray' style='padding: 5px;'>Abu = Status Lain</span>
</p>

</body>
</html>
