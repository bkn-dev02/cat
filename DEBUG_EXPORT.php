<?php
// Debug script untuk test export query
require_once 'config.php';

echo "<h2>Debug Export Query</h2>";

try {
    // Test 1: Count exam_sessions
    $stmt = db()->query("SELECT COUNT(*) as total FROM exam_sessions");
    $result = $stmt->fetch();
    echo "<p>Total exam_sessions: " . $result['total'] . "</p>";
    
    // Test 2: Count submitted sessions
    $stmt = db()->query("SELECT COUNT(*) as total FROM exam_sessions WHERE status IN ('submitted', 'graded', 'completed', 'suspended') AND submitted_at IS NOT NULL");
    $result = $stmt->fetch();
    echo "<p>Total submitted sessions: " . $result['total'] . "</p>";
    
    // Test 3: Sample data
    echo "<h3>Sample Data:</h3>";
    $stmt = db()->query("
        SELECT 
            u.username,
            u.full_name,
            u.kelas,
            e.title as ujian,
            es.score,
            es.status,
            es.submitted_at
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        WHERE es.status IN ('submitted', 'graded', 'completed', 'suspended')
        AND es.submitted_at IS NOT NULL
        LIMIT 10
    ");
    $results = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Nama</th><th>Kelas</th><th>Ujian</th><th>Nilai</th><th>Status</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['kelas'] . "</td>";
        echo "<td>" . $row['ujian'] . "</td>";
        echo "<td>" . $row['score'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 4: Check exams
    echo "<h3>Daftar Ujian:</h3>";
    $stmt = db()->query("SELECT id, title FROM exams LIMIT 10");
    $exams = $stmt->fetchAll();
    echo "<ul>";
    foreach ($exams as $exam) {
        echo "<li>" . $exam['id'] . " - " . $exam['title'] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
