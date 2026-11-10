<?php
require_once 'config.php';
$config = require 'config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$conn->set_charset('utf8mb4');

echo "=== EXAM DENGAN KIMIA ===\n";
$result = $conn->query("SELECT id, name, title, class FROM exams WHERE name LIKE '%KIMIA%' OR title LIKE '%KIMIA%'");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Title: " . $row['title'] . " | Class: " . $row['class'] . "\n";
}

echo "\n=== EXAM DENGAN ARCHIMEDES ===\n";
$result = $conn->query("SELECT id, name, title, class FROM exams WHERE class LIKE '%ARCHIMEDES%'");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Title: " . $row['title'] . " | Class: " . $row['class'] . "\n";
}

echo "\n=== EXAM DENGAN KIMIA DAN ARCHIMEDES ===\n";
$result = $conn->query("SELECT id, name, title, class FROM exams WHERE (name LIKE '%KIMIA%' OR title LIKE '%KIMIA%') AND class LIKE '%ARCHIMEDES%'");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Title: " . $row['title'] . " | Class: " . $row['class'] . "\n";
}

$conn->close();
?>
