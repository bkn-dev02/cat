<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>EXPORT NILAI SISWA - LENGKAP DENGAN STATUS</h1>";
echo "<p>Pilih ujian untuk melihat nilai semua siswa (termasuk yang gagal submit)</p>";

// 1. Ambil daftar ujian
try {
    $stmt = $pdo->query("
        SELECT DISTINCT e.id, e.title, e.subject
        FROM exams e
        JOIN exam_assignments ea ON e.id = ea.exam_id
        ORDER BY e.id DESC
    ");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    $exams = [];
}

echo "<form method='GET'>";
echo "<label>Pilih Ujian: </label>";
echo "<select name='exam_id' required>";
echo "<option value=''>-- Pilih Ujian --</option>";
foreach ($exams as $exam) {
    $selected = (isset($_GET['exam_id']) && $_GET['exam_id'] == $exam['id']) ? 'selected' : '';
    echo "<option value='{$exam['id']}' {$selected}>{$exam['title']} ({$exam['subject']})</option>";
}
echo "</select>";
echo " <button type='submit'>Tampilkan Nilai</button>";
echo " <button type='button' onclick='exportToCSV()'>Export CSV</button>";
echo "</form>";

if (isset($_GET['exam_id']) && !empty($_GET['exam_id'])) {
    $exam_id = $_GET['exam_id'];
    
    // Cek exam info
    $stmt = $pdo->prepare("SELECT id, title, subject FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam) {
        echo "<p style='color: red;'>Ujian tidak ditemukan</p>";
        exit;
    }
    
    echo "<h2>Nilai Ujian: {$exam['title']} ({$exam['subject']})</h2>";
    
    // Query untuk mendapatkan nilai siswa
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.name,
            u.class,
            c.name as class_name,
            es.id as session_id,
            es.status,
            es.score,
            es.submitted_at,
            es.start_time,
            es.end_time,
            CASE 
                WHEN es.status = 'completed' THEN 'Selesai & Submit'
                WHEN es.status = 'submitted' THEN 'Selesai & Submit'
                WHEN es.status = 'in_progress' THEN 'Masih Mengerjakan'
                WHEN es.status = 'suspended' THEN 'Suspended'
                WHEN es.status = 'failed_submit' THEN 'Gagal Submit (Sudah Mengerjakan)'
                WHEN es.status = 'incomplete' THEN 'Tidak Selesai'
                ELSE es.status
            END as status_label,
            (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count,
            (SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?) as total_questions
        FROM users u
        LEFT JOIN classes c ON u.class_id = c.id
        LEFT JOIN exam_sessions es ON u.id = es.user_id AND es.exam_id = ?
        WHERE u.class_id IN (
            SELECT DISTINCT class_id FROM exam_assignments WHERE exam_id = ?
        )
        ORDER BY u.class_id, u.name
    ");
    $stmt->execute([$exam_id, $exam_id, $exam_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        echo "<table border='1' cellpadding='8' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background-color: #4CAF50; color: white;'>";
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
        $submitted_count = 0;
        $failed_submit_count = 0;
        $no_session_count = 0;
        
        foreach ($students as $student) {
            $status = $student['status'];
            $status_label = $student['status_label'];
            $score = $student['score'];
            $answer_count = $student['answer_count'];
            $total_questions = $student['total_questions'];
            
            // Tentukan warna baris
            $row_color = '';
            $keterangan = '';
            
            if ($status === null) {
                $row_color = '#FFF3CD'; // Kuning - belum ujian
                $keterangan = 'Belum Ujian';
                $no_session_count++;
            } elseif ($status === 'completed' || $status === 'submitted') {
                $row_color = '#D4EDDA'; // Hijau - selesai submit
                $keterangan = 'Nilai Tercatat';
                $submitted_count++;
            } elseif ($status === 'failed_submit' && $answer_count > 0) {
                $row_color = '#F8D7DA'; // Merah muda - gagal submit tapi ada jawaban
                $keterangan = "Gagal Submit (Sudah Jawab {$answer_count}/{$total_questions})";
                $failed_submit_count++;
            } elseif ($status === 'in_progress') {
                $row_color = '#E2E3E5'; // Abu - masih mengerjakan
                $keterangan = 'Masih Mengerjakan';
            } else {
                $row_color = '#F8D7DA'; // Merah muda - status lain
                $keterangan = $status_label;
            }
            
            echo "<tr style='background-color: {$row_color};'>";
            echo "<td>{$no}</td>";
            echo "<td>{$student['username']}</td>";
            echo "<td>{$student['name']}</td>";
            echo "<td><strong>{$status_label}</strong></td>";
            echo "<td style='text-align: center; font-weight: bold;'>";
            if ($score !== null) {
                echo number_format($score, 2);
            } else {
                echo "-";
            }
            echo "</td>";
            echo "<td style='text-align: center;'>";
            if ($answer_count > 0) {
                echo "{$answer_count}/{$total_questions}";
            } else {
                echo "-";
            }
            echo "</td>";
            echo "<td>" . ($student['start_time'] ? date('d/m/Y H:i', strtotime($student['start_time'])) : '-') . "</td>";
            echo "<td>" . ($student['submitted_at'] ? date('d/m/Y H:i', strtotime($student['submitted_at'])) : '-') . "</td>";
            echo "<td>{$keterangan}</td>";
            echo "</tr>";
            
            $no++;
        }
        
        echo "</table>";
        echo "<p style='margin-top: 10px;'>";
        echo "Total Siswa: " . count($students) . " | ";
        echo "<span style='color: green;'>✓ Selesai Submit: {$submitted_count}</span> | ";
        echo "<span style='color: red;'>✗ Gagal Submit (Ada Jawaban): {$failed_submit_count}</span> | ";
        echo "<span style='color: orange;'>⚠ Belum Ujian: {$no_session_count}</span>";
        echo "</p>";
        echo "<hr>";
    }
    
    // Tombol export
    echo "<h3>Export Data</h3>";
    echo "<form method='POST' action='EXPORT_NILAI_CSV.php'>";
    echo "<input type='hidden' name='exam_id' value='{$exam_id}'>";
    echo "<button type='submit' name='format' value='csv' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer;'>📥 Download CSV</button>";
    echo "<button type='submit' name='format' value='excel' style='padding: 10px 20px; background-color: #2196F3; color: white; border: none; cursor: pointer;'>📊 Download Excel</button>";
    echo "</form>";
}

echo "<hr>";
echo "<p style='color: #666; font-size: 12px;'>";
echo "Keterangan Warna:<br>";
echo "<span style='background-color: #D4EDDA; padding: 5px;'>Hijau = Selesai & Submit (Nilai Tercatat)</span><br>";
echo "<span style='background-color: #F8D7DA; padding: 5px;'>Merah Muda = Gagal Submit (Tapi Sudah Mengerjakan - Perlu Diinput Manual)</span><br>";
echo "<span style='background-color: #FFF3CD; padding: 5px;'>Kuning = Belum Ujian</span><br>";
echo "<span style='background-color: #E2E3E5; padding: 5px;'>Abu = Masih Mengerjakan</span>";
echo "</p>";
