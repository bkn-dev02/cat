<?php
require_once 'config.php';
$config = require 'config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$conn->set_charset('utf8mb4');

echo "=== SEMUA EXAM YANG ADA ===\n\n";
$result = $conn->query("SELECT id, name, title, class FROM exams ORDER BY id DESC");
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo $count . ". ID: " . $row['id'] . " | Name: " . $row['name'] . " | Title: " . $row['title'] . " | Class: " . $row['class'] . "\n";
}

echo "\n=== EXAM DENGAN SESSION DARI SISWA ARCHIMEDES ===\n\n";
$result = $conn->query("
    SELECT DISTINCT e.id, e.name, e.title, e.class, COUNT(es.id) as jumlah_session
    FROM exams e
    JOIN exam_sessions es ON e.id = es.exam_id
    JOIN users u ON es.user_id = u.id
    WHERE u.kelas LIKE '%ARCHIMEDES%'
    GROUP BY e.id
    ORDER BY e.id DESC
");
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo $count . ". ID: " . $row['id'] . " | Name: " . $row['name'] . " | Title: " . $row['title'] . " | Class: " . $row['class'] . " | Sessions: " . $row['jumlah_session'] . "\n";
}

$conn->close();
?>
