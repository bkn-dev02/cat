<?php
/**
 * RECALCULATE ALL SCORES - PERBAIKI SEMUA NILAI OTOMATIS
 * Script ini akan menghitung ulang semua nilai dari answers dan update ke exam_sessions
 */

require_once 'config.php';
$config = require 'config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recalculate All Scores</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .fixed { background-color: #ccffcc; }
        .problem { background-color: #ffcccc; }
        .ok { background-color: #e6f3ff; }
        .btn { padding: 12px 24px; margin: 10px 0; cursor: pointer; border: none; border-radius: 4px; font-size: 16px; }
        .btn-fix { background-color: #dc3545; color: white; }
        .btn-fix:hover { background-color: #c82333; }
        .summary { padding: 15px; background: #fff3cd; border: 2px solid #ff9800; border-radius: 4px; margin: 20px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        h1 { color: #333; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 RECALCULATE ALL SCORES</h1>
    
    <?php
    $action = $_POST['action'] ?? 'diagnosa';
    $fixedCount = 0;
    $problemCount = 0;
    $totalProcessed = 0;
    $okCount = 0;
    
    // Ambil semua exam_sessions yang submitted
    $query = "SELECT id, score, user_id, exam_id FROM exam_sessions WHERE status = 'submitted' ORDER BY id DESC LIMIT 100";
    $result = $conn->query($query);
    
    if (!$result) {
        echo "<p style='color:red;'><strong>Error: " . $conn->error . "</strong></p>";
        exit;
    }
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    if (count($sessions) === 0) {
        echo "<p style='color:orange;'><strong>Tidak ada exam_sessions yang submitted</strong></p>";
        exit;
    }
    
    echo "<p>Memproses " . count($sessions) . " sessions...</p>";
    echo "<table>";
    echo "<tr>";
    echo "<th>Session ID</th>";
    echo "<th>User ID</th>";
    echo "<th>Exam ID</th>";
    echo "<th>Nilai Terekam</th>";
    echo "<th>Jawaban Benar</th>";
    echo "<th>Total Jawaban</th>";
    echo "<th>Nilai Seharusnya</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    foreach ($sessions as $session) {
        $sessionId = $session['id'];
        $scoreTerekam = $session['score'];
        $userId = $session['user_id'];
        $examId = $session['exam_id'];
        
        // Hitung score yang seharusnya
        $ansQuery = "
            SELECT 
                COUNT(*) as total_answers,
                SUM(CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN 1 ELSE 0 END) as correct_answers,
                SUM(CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN q.points ELSE 0 END) as calculated_score
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = $sessionId
        ";
        
        $ansResult = $conn->query($ansQuery);
        if (!$ansResult) {
            echo "<tr class='problem'><td colspan='8'>Error: " . $conn->error . "</td></tr>";
            continue;
        }
        
        $ansData = $ansResult->fetch_assoc();
        $totalAnswers = $ansData['total_answers'] ?? 0;
        $correctAnswers = $ansData['correct_answers'] ?? 0;
        $calculatedScore = $ansData['calculated_score'] ?? 0;
        
        $totalProcessed++;
        $rowClass = 'ok';
        $status = '✓ OK';
        
        if ($scoreTerekam != $calculatedScore) {
            $rowClass = 'problem';
            $status = '✗ TIDAK COCOK';
            $problemCount++;
            
            // Jika action adalah fix, perbaiki nilainya
            if ($action === 'fix') {
                $updateQuery = "UPDATE exam_sessions SET score = $calculatedScore WHERE id = $sessionId";
                if ($conn->query($updateQuery)) {
                    $rowClass = 'fixed';
                    $status = '✓ DIPERBAIKI';
                    $fixedCount++;
                } else {
                    $status = '✗ GAGAL: ' . $conn->error;
                }
            }
        } else {
            $okCount++;
        }
        
        echo "<tr class='$rowClass'>";
        echo "<td>" . $sessionId . "</td>";
        echo "<td>" . $userId . "</td>";
        echo "<td>" . $examId . "</td>";
        echo "<td><strong>" . $scoreTerekam . "</strong></td>";
        echo "<td>" . $correctAnswers . "</td>";
        echo "<td>" . $totalAnswers . "</td>";
        echo "<td><strong>" . $calculatedScore . "</strong></td>";
        echo "<td><strong>" . $status . "</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div class='summary'>";
    echo "<h2>RINGKASAN</h2>";
    echo "<p>Total Diproses: <strong>" . $totalProcessed . "</strong></p>";
    echo "<p>Nilai OK: <strong style='color:green;'>" . $okCount . "</strong></p>";
    
    if ($action === 'fix') {
        echo "<p class='success'>✓ Nilai yang diperbaiki: " . $fixedCount . "</p>";
        echo "<p>Total masalah yang ditemukan: " . ($problemCount + $fixedCount) . "</p>";
    } else {
        if ($problemCount > 0) {
            echo "<p class='error'>✗ Ditemukan masalah pada: " . $problemCount . " sessions</p>";
            echo "<p>Klik tombol di bawah untuk memperbaiki nilai secara otomatis</p>";
        } else {
            echo "<p class='success'>✓ Semua nilai sudah benar!</p>";
        }
    }
    echo "</div>";
    
    if ($action !== 'fix' && $problemCount > 0) {
        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='fix'>";
        echo "<button type='submit' class='btn btn-fix'>🔧 PERBAIKI SEMUA NILAI SEKARANG</button>";
        echo "</form>";
    } elseif ($action === 'fix') {
        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='diagnosa'>";
        echo "<button type='submit' class='btn btn-fix'>↻ KEMBALI KE DIAGNOSA</button>";
        echo "</form>";
    }
    
    $conn->close();
    ?>
</div>

</body>
</html>
