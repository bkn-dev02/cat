<?php
require_once 'config.php';

echo "<h2>DEBUG - CEK DATA UJIAN</h2>";

// 1. Cek apakah ada exams
echo "<h3>1. Total Exams:</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM exams");
$result = $stmt->fetch();
echo "Total: " . $result['total'] . "<br>";

// 2. Cek apakah ada exam_assignments
echo "<h3>2. Total Exam Assignments:</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM exam_assignments");
$result = $stmt->fetch();
echo "Total: " . $result['total'] . "<br>";

// 3. Cek exam dengan assignments
echo "<h3>3. Exams dengan Assignments:</h3>";
$stmt = $pdo->query("
    SELECT DISTINCT e.id, e.title, e.subject, COUNT(ea.id) as assignment_count
    FROM exams e
    LEFT JOIN exam_assignments ea ON e.id = ea.exam_id
    GROUP BY e.id
    ORDER BY e.id DESC
    LIMIT 20
");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Subject</th><th>Assignments</th></tr>";
foreach ($exams as $exam) {
    echo "<tr>";
    echo "<td>{$exam['id']}</td>";
    echo "<td>{$exam['title']}</td>";
    echo "<td>{$exam['subject']}</td>";
    echo "<td>{$exam['assignment_count']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Cek exam_sessions
echo "<h3>4. Total Exam Sessions:</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM exam_sessions");
$result = $stmt->fetch();
echo "Total: " . $result['total'] . "<br>";

// 5. Cek exam_sessions per exam
echo "<h3>5. Sessions per Exam (Top 10):</h3>";
$stmt = $pdo->query("
    SELECT e.id, e.title, COUNT(es.id) as session_count
    FROM exams e
    LEFT JOIN exam_sessions es ON e.id = es.exam_id
    GROUP BY e.id
    ORDER BY session_count DESC
    LIMIT 10
");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Exam ID</th><th>Title</th><th>Sessions</th></tr>";
foreach ($results as $row) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['title']}</td>";
    echo "<td>{$row['session_count']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='EXPORT_NILAI_LENGKAP_GURU.php'>← Kembali ke Export Nilai</a></p>";
