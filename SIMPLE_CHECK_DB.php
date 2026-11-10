<?php
require_once 'config.php';
$config = require 'config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$conn->set_charset('utf8mb4');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Check Database</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>

<h1>CHECK DATABASE</h1>

<div class="box">
    <h2>1. Jumlah Data di Setiap Tabel</h2>
    <?php
    $tables = ['users', 'exams', 'exam_sessions', 'answers', 'questions'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as cnt FROM $table");
        $row = $result->fetch_assoc();
        echo "<p><strong>$table:</strong> " . $row['cnt'] . " records</p>";
    }
    ?>
</div>

<div class="box">
    <h2>2. Sample Data dari exam_sessions</h2>
    <?php
    $result = $conn->query("SELECT * FROM exam_sessions LIMIT 5");
    if ($result->num_rows === 0) {
        echo "<p style='color:red;'>Tidak ada data di exam_sessions!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Exam ID</th><th>Score</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['exam_id'] . "</td>";
            echo "<td>" . $row['score'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
</div>

<div class="box">
    <h2>3. Sample Data dari answers</h2>
    <?php
    $result = $conn->query("SELECT * FROM answers LIMIT 5");
    if ($result->num_rows === 0) {
        echo "<p style='color:red;'>Tidak ada data di answers!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Session ID</th><th>Question ID</th><th>Answer</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['session_id'] . "</td>";
            echo "<td>" . $row['question_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['answer']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
</div>

<div class="box">
    <h2>4. Cek User ID 1730</h2>
    <?php
    $result = $conn->query("SELECT * FROM users WHERE id = 1730");
    if ($result->num_rows === 0) {
        echo "<p style='color:red;'>User ID 1730 tidak ditemukan!</p>";
    } else {
        $user = $result->fetch_assoc();
        echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
        echo "<p><strong>Nama:</strong> " . $user['full_name'] . "</p>";
        echo "<p><strong>Kelas:</strong> " . $user['kelas'] . "</p>";
        
        // Cek session untuk user ini
        $result = $conn->query("SELECT * FROM exam_sessions WHERE user_id = 1730");
        echo "<p><strong>Total Session:</strong> " . $result->num_rows . "</p>";
        
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Session ID</th><th>Exam ID</th><th>Score</th><th>Status</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['exam_id'] . "</td>";
                echo "<td>" . $row['score'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    ?>
</div>

</body>
</html>

<?php
$conn->close();
?>
