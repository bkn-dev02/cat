<?php
require_once 'app/helpers.php';

$pdo = db();

echo "<h2>CEK STRUKTUR TABEL ANSWERS</h2>";

// Cek struktur answers
echo "<h3>Struktur Tabel 'answers':</h3>";
$stmt = $pdo->query("DESCRIBE answers");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Sample data
echo "<h3>Sample Data Answers (5 baris):</h3>";
$stmt = $pdo->query("SELECT * FROM answers LIMIT 5");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($data);
echo "</pre>";

// Cek struktur exam_questions
echo "<h3>Struktur Tabel 'exam_questions':</h3>";
$stmt = $pdo->query("DESCRIBE exam_questions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Sample data
echo "<h3>Sample Data Exam Questions (5 baris):</h3>";
$stmt = $pdo->query("SELECT * FROM exam_questions LIMIT 5");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($data);
echo "</pre>";
