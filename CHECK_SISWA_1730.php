<?php
require_once 'config.php';
$config = require 'config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$conn->set_charset('utf8mb4');

$userId = 1730;

echo "<h2>CHECK SISWA ID 1730 - STAFINDO TUAH PURBA</h2>";

// Cek user
$result = $conn->query("SELECT id, email, full_name, kelas FROM users WHERE id = $userId");
$user = $result->fetch_assoc();

if (!$user) {
    echo "User tidak ditemukan";
    exit;
}

echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
echo "<p><strong>Nama:</strong> " . $user['full_name'] . "</p>";
echo "<p><strong>Kelas:</strong> " . $user['kelas'] . "</p>";

// Cek semua session
echo "<h3>Semua Session untuk Siswa Ini:</h3>";
$result = $conn->query("SELECT es.id, es.score, es.status, es.submitted_at, e.id as exam_id, e.name, e.title FROM exam_sessions es JOIN exams e ON es.exam_id = e.id WHERE es.user_id = $userId ORDER BY es.submitted_at DESC");

if ($result->num_rows === 0) {
    echo "<p style='color:red;'>Tidak ada session untuk siswa ini!</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Session ID</th><th>Exam Name</th><th>Score</th><th>Status</th><th>Submitted</th><th>Answers</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $sessionId = $row['id'];
        
        // Cek jumlah answers
        $ansResult = $conn->query("SELECT COUNT(*) as cnt FROM answers WHERE session_id = $sessionId");
        $ans = $ansResult->fetch_assoc();
        $answerCount = $ans['cnt'];
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['score'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['submitted_at'] . "</td>";
        echo "<td>" . $answerCount . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Cek session UTS FISIKA XI
echo "<h3>Detail Session UTS FISIKA XI:</h3>";
$result = $conn->query("SELECT es.id, es.score FROM exam_sessions es JOIN exams e ON es.exam_id = e.id WHERE es.user_id = $userId AND e.name LIKE '%FISIKA%'");

if ($result->num_rows === 0) {
    echo "<p style='color:red;'>Tidak ada session UTS FISIKA XI</p>";
} else {
    $session = $result->fetch_assoc();
    $sessionId = $session['id'];
    
    echo "<p><strong>Session ID:</strong> " . $sessionId . "</p>";
    echo "<p><strong>Score Terekam:</strong> " . $session['score'] . "</p>";
    
    // Cek answers
    $result = $conn->query("SELECT a.id, a.answer, q.correct_answer, q.points FROM answers a JOIN questions q ON a.question_id = q.id WHERE a.session_id = $sessionId");
    
    echo "<p><strong>Total Answers:</strong> " . $result->num_rows . "</p>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Answer ID</th><th>Jawaban Siswa</th><th>Jawaban Benar</th><th>Points</th><th>Benar?</th></tr>";
        
        $totalPoints = 0;
        $correctCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $isCorrect = (strtolower($row['answer']) === strtolower($row['correct_answer']));
            if ($isCorrect) {
                $totalPoints += $row['points'];
                $correctCount++;
            }
            
            $status = $isCorrect ? '✓ BENAR' : '✗ SALAH';
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['answer']) . "</td>";
            echo "<td>" . htmlspecialchars($row['correct_answer']) . "</td>";
            echo "<td>" . $row['points'] . "</td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Jawaban Benar:</strong> " . $correctCount . "</p>";
        echo "<p><strong>Total Points Seharusnya:</strong> " . $totalPoints . "</p>";
        echo "<p><strong>Score Terekam:</strong> " . $session['score'] . "</p>";
        
        if ($session['score'] != $totalPoints) {
            echo "<p style='color:red;'><strong>⚠️ TIDAK COCOK! Perbedaan: " . ($totalPoints - $session['score']) . " poin</strong></p>";
        } else {
            echo "<p style='color:green;'><strong>✓ Score sudah benar</strong></p>";
        }
    } else {
        echo "<p style='color:red;'>Tidak ada answers untuk session ini!</p>";
    }
}

$conn->close();
?>
