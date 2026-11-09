<?php
require_once 'config.php';
$config = require 'config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$conn->set_charset('utf8mb4');

// Cek user agape
$email = 'agape.pratama@student.ubkn.sch.id';
$result = $conn->query("SELECT id, email, full_name, kelas FROM users WHERE email = '$email'");
$user = $result->fetch_assoc();

if (!$user) {
    echo "User tidak ditemukan";
    exit;
}

echo "User ID: " . $user['id'] . "<br>";
echo "Email: " . $user['email'] . "<br>";
echo "Nama: " . $user['full_name'] . "<br>";
echo "Kelas: " . $user['kelas'] . "<br><br>";

// Cek session untuk user ini
$userId = $user['id'];
$result = $conn->query("SELECT es.id, es.score, es.status, e.id as exam_id, e.name, e.title FROM exam_sessions es JOIN exams e ON es.exam_id = e.id WHERE es.user_id = $userId");

echo "Total Session: " . $result->num_rows . "<br><br>";

while ($row = $result->fetch_assoc()) {
    echo "Session ID: " . $row['id'] . "<br>";
    echo "Exam ID: " . $row['exam_id'] . "<br>";
    echo "Exam Name: " . $row['name'] . "<br>";
    echo "Exam Title: " . $row['title'] . "<br>";
    echo "Score: " . $row['score'] . "<br>";
    echo "Status: " . $row['status'] . "<br>";
    
    // Cek answers
    $sessionId = $row['id'];
    $ansResult = $conn->query("SELECT COUNT(*) as cnt FROM answers WHERE session_id = $sessionId");
    $ans = $ansResult->fetch_assoc();
    echo "Total Answers: " . $ans['cnt'] . "<br><br>";
}

$conn->close();
?>
