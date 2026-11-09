<?php
// =====================================================
// TEST EXPORT NILAI
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config.php';
} catch (Exception $e) {
    die("Error loading config: " . $e->getMessage());
}

echo "<h1>Test Export Nilai</h1>";
echo "<hr>";

try {
    // Test query export
    $query = "
        SELECT 
            u.username,
            u.full_name,
            u.kelas,
            e.title as ujian,
            s.name as mata_pelajaran,
            COALESCE(es.score, 0) as nilai,
            es.started_at as waktu_mulai,
            es.submitted_at as waktu_selesai,
            es.status,
            (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as jumlah_jawaban
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE es.submitted_at IS NOT NULL
        ORDER BY u.kelas, u.full_name, es.submitted_at DESC
        LIMIT 20
    ";
    
    $stmt = db()->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Hasil Query (20 data pertama):</h2>";
    echo "<p>Total data: <strong>" . count($results) . "</strong></p>";
    
    if (count($results) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse'>";
        echo "<tr>";
        echo "<th>Username</th>";
        echo "<th>Nama</th>";
        echo "<th>Kelas</th>";
        echo "<th>Ujian</th>";
        echo "<th>Nilai</th>";
        echo "<th>Status</th>";
        echo "<th>Submit</th>";
        echo "</tr>";
        
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['username']}</td>";
            echo "<td>{$row['full_name']}</td>";
            echo "<td>{$row['kelas']}</td>";
            echo "<td>{$row['ujian']}</td>";
            echo "<td><strong>{$row['nilai']}</strong></td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['waktu_selesai']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<br><hr>";
        echo "<h2>Test Download CSV</h2>";
        echo "<p><a href='test_export.php?download=1' style='padding: 10px 20px; background: green; color: white; text-decoration: none'>Download CSV</a></p>";
        
    } else {
        echo "<p style='color: red'>Tidak ada data untuk di-export!</p>";
        echo "<p>Kemungkinan:</p>";
        echo "<ul>";
        echo "<li>Belum ada siswa yang submit ujian</li>";
        echo "<li>Kolom submitted_at masih NULL</li>";
        echo "</ul>";
    }
    
    // Jika ada parameter download
    if (isset($_GET['download'])) {
        $stmt = db()->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = 'test_nilai_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Username', 'Nama Lengkap', 'Kelas', 'Ujian', 'Mata Pelajaran', 'Nilai', 'Waktu Mulai', 'Waktu Selesai', 'Status', 'Jumlah Jawaban']);
        
        foreach ($results as $row) {
            fputcsv($output, [
                $row['username'],
                $row['full_name'],
                $row['kelas'],
                $row['ujian'],
                $row['mata_pelajaran'],
                number_format($row['nilai'], 2),
                $row['waktu_mulai'],
                $row['waktu_selesai'],
                $row['status'],
                $row['jumlah_jawaban']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffcccc; padding: 20px; border: 2px solid red'>";
    echo "<h2>ERROR</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
