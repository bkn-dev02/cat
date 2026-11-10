<?php
/**
 * DEBUG - Lihat struktur exam dan session yang ada
 */

require_once 'config.php';

$config = require 'config.php';
$conn = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name']
);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Exam Struktur</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>

<h1>DEBUG - Struktur Exam dan Session</h1>

<h2>1. Semua Exam yang Ada</h2>
<?php
$query = "SELECT id, name, title, class, subject_id FROM exams ORDER BY name";
$result = $conn->query($query);
echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Title</th><th>Class</th><th>Subject ID</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['class']) . "</td>";
    echo "<td>" . $row['subject_id'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>

<h2>2. Exam yang Mengandung "KIMIA"</h2>
<?php
$query = "SELECT id, name, title, class FROM exams WHERE name LIKE '%KIMIA%' OR title LIKE '%KIMIA%'";
$result = $conn->query($query);
echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Title</th><th>Class</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['class']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>

<h2>3. Exam yang Mengandung "ARCHIMEDES"</h2>
<?php
$query = "SELECT id, name, title, class FROM exams WHERE class LIKE '%ARCHIMEDES%'";
$result = $conn->query($query);
echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Title</th><th>Class</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['class']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>

<h2>4. Session untuk Email agape.pratama@student.ubkn.sch.id</h2>
<?php
$email = 'agape.pratama@student.ubkn.sch.id';
$query = "SELECT u.id, u.email, u.full_name FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    
    echo "<p>User ID: " . $userId . " | Email: " . $email . " | Nama: " . htmlspecialchars($user['full_name']) . "</p>";
    
    $query = "SELECT es.id, es.score, es.status, e.id as exam_id, e.name, e.title, e.class 
              FROM exam_sessions es 
              JOIN exams e ON es.exam_id = e.id 
              WHERE es.user_id = ?
              ORDER BY es.submitted_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<table>";
    echo "<tr><th>Session ID</th><th>Exam ID</th><th>Exam Name</th><th>Exam Title</th><th>Class</th><th>Score</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['exam_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['class']) . "</td>";
        echo "<td>" . $row['score'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>User tidak ditemukan</p>";
}
?>

</body>
</html>

<?php
$conn->close();
?>
