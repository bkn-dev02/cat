<?php
// Direct connection tanpa require config
$db_host = 'localhost';
$db_user = 'u245247469_enterprise';
$db_pass = '1.SMAUnggul234!!';
$db_name = 'u245247469_catbkn';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$format = $_GET['format'] ?? 'html';

$query = "SELECT u.email, u.full_name, u.kelas, e.name as exam_name, es.score, es.submitted_at FROM exam_sessions es JOIN exams e ON es.exam_id = e.id JOIN users u ON es.user_id = u.id WHERE es.status = 'submitted' ORDER BY u.full_name LIMIT 500";

$result = $conn->query($query);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="nilai_' . date('Y-m-d') . '.csv"');
    
    echo "Email;Nama;Kelas;Ujian;Nilai;Waktu Submit\n";
    
    while ($row = $result->fetch_assoc()) {
        echo $row['email'] . ";" . $row['full_name'] . ";" . $row['kelas'] . ";" . $row['exam_name'] . ";" . $row['score'] . ";" . $row['submitted_at'] . "\n";
    }
    $conn->close();
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Download Nilai</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .btn { padding: 10px 20px; margin: 10px 0; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; display: inline-block; font-size: 16px; }
        .btn:hover { background: #45a049; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        h1 { color: #333; }
    </style>
</head>
<body>

<div class="container">
    <h1>📊 Download Nilai Siswa</h1>
    
    <a href="?format=csv" class="btn">📥 Download CSV</a>
    
    <h2>Preview Data</h2>
    <table>
        <tr>
            <th>Email</th>
            <th>Nama</th>
            <th>Kelas</th>
            <th>Ujian</th>
            <th>Nilai</th>
            <th>Waktu Submit</th>
        </tr>
        <?php
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['kelas']) . "</td>";
            echo "<td>" . htmlspecialchars($row['exam_name']) . "</td>";
            echo "<td><strong>" . $row['score'] . "</strong></td>";
            echo "<td>" . $row['submitted_at'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
