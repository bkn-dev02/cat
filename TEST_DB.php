<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Database Connection</h2>";

// Direct connection
$db_host = 'localhost';
$db_user = 'u245247469_enterprise';
$db_pass = '1.SMAUnggul234!!';
$db_name = 'u245247469_catbkn';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Test 1: Count exam_sessions
    $result = $conn->query("SELECT COUNT(*) as total FROM exam_sessions");
    $row = $result->fetch_assoc();
    echo "<p>Total exam_sessions: <strong>" . $row['total'] . "</strong></p>";
    
    // Test 2: Count submitted
    $result = $conn->query("SELECT COUNT(*) as total FROM exam_sessions WHERE status IN ('submitted', 'graded', 'completed', 'suspended') AND submitted_at IS NOT NULL");
    $row = $result->fetch_assoc();
    echo "<p>Total submitted: <strong>" . $row['total'] . "</strong></p>";
    
    // Test 3: Sample data
    echo "<h3>Sample Data (10 rows):</h3>";
    $result = $conn->query("
        SELECT 
            u.username,
            u.full_name,
            u.kelas,
            e.title as ujian,
            es.score,
            es.status
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        WHERE es.status IN ('submitted', 'graded', 'completed', 'suspended')
        AND es.submitted_at IS NOT NULL
        LIMIT 10
    ");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Username</th><th>Nama</th><th>Kelas</th><th>Ujian</th><th>Nilai</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ujian']) . "</td>";
            echo "<td>" . $row['score'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No data found</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
