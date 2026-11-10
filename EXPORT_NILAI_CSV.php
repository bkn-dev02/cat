<?php
require_once 'config.php';

if (!isset($_POST['exam_id']) || !isset($_POST['format'])) {
    die('Invalid request');
}

$exam_id = $_POST['exam_id'];
$format = $_POST['format'];

// Cek exam
$stmt = $pdo->prepare("SELECT id, title, subject FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die('Ujian tidak ditemukan');
}

// Query data
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

// Prepare data
$data = [];
$data[] = ['LAPORAN NILAI UJIAN', $exam['title'], $exam['subject']];
$data[] = ['Tanggal Export', date('d/m/Y H:i:s')];
$data[] = [];
$data[] = ['No', 'Username', 'Nama Siswa', 'Kelas', 'Status', 'Nilai', 'Jawaban Terisi', 'Waktu Mulai', 'Waktu Submit', 'Keterangan'];

$no = 1;
foreach ($results as $row) {
    $status = $row['status'];
    $status_label = $row['status_label'];
    $score = $row['score'];
    $answer_count = $row['answer_count'];
    $total_questions = $row['total_questions'];
    
    $keterangan = '';
    if ($status === null) {
        $keterangan = 'Belum Ujian';
    } elseif ($status === 'completed' || $status === 'submitted') {
        $keterangan = 'Nilai Tercatat';
    } elseif ($status === 'failed_submit' && $answer_count > 0) {
        $keterangan = "Gagal Submit (Sudah Jawab {$answer_count}/{$total_questions})";
    } elseif ($status === 'in_progress') {
        $keterangan = 'Masih Mengerjakan';
    } else {
        $keterangan = $status_label;
    }
    
    $data[] = [
        $no,
        $row['username'],
        $row['name'],
        $row['class_name'] ?: '-',
        $status_label,
        $score !== null ? number_format($score, 2) : '-',
        $answer_count > 0 ? "{$answer_count}/{$total_questions}" : '-',
        $row['start_time'] ? date('d/m/Y H:i', strtotime($row['start_time'])) : '-',
        $row['submitted_at'] ? date('d/m/Y H:i', strtotime($row['submitted_at'])) : '-',
        $keterangan
    ];
    
    $no++;
}

if ($format === 'csv') {
    // Export CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Nilai_' . str_replace(' ', '_', $exam['title']) . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM untuk UTF-8
    
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
    
} elseif ($format === 'excel') {
    // Export Excel (simple format)
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Nilai_' . str_replace(' ', '_', $exam['title']) . '_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    exit;
}
