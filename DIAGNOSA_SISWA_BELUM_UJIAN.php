<?php
require_once 'app/helpers.php';

$pdo = db();

echo "<h2>DIAGNOSA: SISWA BELUM UJIAN PADAHAL UJIAN SUDAH BERAKHIR</h2>";

// 1. Cek exam yang sudah berakhir
echo "<h3>1. EXAM YANG SUDAH BERAKHIR:</h3>";
$stmt = $pdo->query("
    SELECT id, title, start_time, end_time, status
    FROM exams
    WHERE end_time < NOW()
    ORDER BY end_time DESC
    LIMIT 10
");
$ended_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Start</th><th>End</th><th>Status</th></tr>";
foreach ($ended_exams as $exam) {
    echo "<tr>";
    echo "<td>{$exam['id']}</td>";
    echo "<td>{$exam['title']}</td>";
    echo "<td>" . date('d/m/Y H:i', strtotime($exam['start_time'])) . "</td>";
    echo "<td>" . date('d/m/Y H:i', strtotime($exam['end_time'])) . "</td>";
    echo "<td>{$exam['status']}</td>";
    echo "</tr>";
}
echo "</table>";

if (count($ended_exams) > 0) {
    $exam_id = $ended_exams[0]['id'];
    
    echo "<h3>2. ANALISIS EXAM ID {$exam_id}: {$ended_exams[0]['title']}</h3>";
    
    // Cek exam_participants
    echo "<h4>a) Siswa di exam_participants:</h4>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM exam_participants WHERE exam_id = ?
    ");
    $stmt->execute([$exam_id]);
    $result = $stmt->fetch();
    echo "Total: " . $result['total'] . " siswa<br>";
    
    // Cek exam_sessions
    echo "<h4>b) Siswa yang sudah ujian (exam_sessions):</h4>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM exam_sessions WHERE exam_id = ?
    ");
    $stmt->execute([$exam_id]);
    $result = $stmt->fetch();
    echo "Total: " . $result['total'] . " siswa<br>";
    
    // Cek siswa yang terdaftar tapi belum ujian
    echo "<h4>c) Siswa yang TERDAFTAR tapi BELUM UJIAN:</h4>";
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.kelas
        FROM exam_participants ep
        JOIN users u ON ep.user_id = u.id
        LEFT JOIN exam_sessions es ON u.id = es.user_id AND es.exam_id = ?
        WHERE ep.exam_id = ? AND es.id IS NULL
        ORDER BY u.kelas, u.full_name
        LIMIT 20
    ");
    $stmt->execute([$exam_id, $exam_id]);
    $not_taken = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Nama</th><th>Kelas</th></tr>";
    foreach ($not_taken as $student) {
        echo "<tr>";
        echo "<td>{$student['id']}</td>";
        echo "<td>{$student['username']}</td>";
        echo "<td>{$student['full_name']}</td>";
        echo "<td>{$student['kelas']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: red; font-weight: bold;'>⚠️ TOTAL: " . count($not_taken) . " siswa terdaftar tapi belum ujian!</p>";
    
    // Cek siswa yang TIDAK terdaftar di exam_participants
    echo "<h4>d) Siswa yang TIDAK TERDAFTAR di exam_participants:</h4>";
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.kelas
        FROM users u
        LEFT JOIN exam_participants ep ON u.id = ep.user_id AND ep.exam_id = ?
        WHERE u.role = 'siswa' AND ep.id IS NULL
        ORDER BY u.kelas, u.full_name
        LIMIT 20
    ");
    $stmt->execute([$exam_id]);
    $not_registered = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Nama</th><th>Kelas</th></tr>";
    foreach ($not_registered as $student) {
        echo "<tr>";
        echo "<td>{$student['id']}</td>";
        echo "<td>{$student['username']}</td>";
        echo "<td>{$student['full_name']}</td>";
        echo "<td>{$student['kelas']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: red; font-weight: bold;'>⚠️ TOTAL: " . count($not_registered) . " siswa TIDAK terdaftar!</p>";
    
    // Solusi
    echo "<h3>3. SOLUSI:</h3>";
    echo "<p style='background-color: #fff3cd; padding: 10px; border: 1px solid #ffc107;'>";
    echo "<strong>Masalah:</strong> Siswa tidak terdaftar di exam_participants, jadi tidak bisa akses ujian.<br>";
    echo "<strong>Solusi:</strong> Daftarkan siswa ke exam_participants sebelum ujian dimulai.<br>";
    echo "</p>";
    
    // Cek struktur exam_participants
    echo "<h3>4. STRUKTUR TABEL exam_participants:</h3>";
    $stmt = $pdo->query("DESCRIBE exam_participants");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color: orange;'>Tidak ada exam yang sudah berakhir</p>";
}
