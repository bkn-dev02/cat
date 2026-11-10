<?php
require_once 'app/helpers.php';

$pdo = db();

echo "<h2>CEK STRUKTUR DATABASE</h2>";

// 1. Cek tabel apa saja yang ada
echo "<h3>1. Daftar Tabel:</h3>";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($tables);
echo "</pre>";

// 2. Cek struktur users
echo "<h3>2. Struktur Tabel 'users':</h3>";
$stmt = $pdo->query("DESCRIBE users");
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

// 3. Cek struktur exam_sessions
echo "<h3>3. Struktur Tabel 'exam_sessions':</h3>";
$stmt = $pdo->query("DESCRIBE exam_sessions");
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

// 4. Cek struktur exams
echo "<h3>4. Struktur Tabel 'exams':</h3>";
$stmt = $pdo->query("DESCRIBE exams");
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

// 5. Sample data users
echo "<h3>5. Sample Data Users (5 baris pertama):</h3>";
$stmt = $pdo->query("SELECT * FROM users LIMIT 5");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($data);
echo "</pre>";

// 6. Sample data exam_sessions
echo "<h3>6. Sample Data Exam Sessions (5 baris pertama):</h3>";
$stmt = $pdo->query("SELECT * FROM exam_sessions LIMIT 5");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($data);
echo "</pre>";
