<?php
require_once 'config.php';
$config = require 'config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$conn->set_charset('utf8mb4');

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
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Nilai</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .btn { padding: 10px 20px; margin: 10px 0; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; display: inline-block; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>

<h1>Export Nilai Siswa</h1>

<a href="?format=csv" class="btn">Download CSV</a>

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
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['kelas'] . "</td>";
        echo "<td>" . $row['exam_name'] . "</td>";
        echo "<td>" . $row['score'] . "</td>";
        echo "<td>" . $row['submitted_at'] . "</td>";
        echo "</tr>";
    }
    ?>
</table>

</body>
</html>

<?php
$conn->close();
?>
