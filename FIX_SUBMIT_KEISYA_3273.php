<?php
// EMERGENCY FIX: Submit session 3273 untuk KEISYA ANGGITA
// Session sudah ada, jawaban sudah ada, tinggal hitung dan submit

require_once 'config.php';

echo "<h2>EMERGENCY SUBMIT - KEISYA ANGGITA</h2>";
echo "<p>Session ID: 3273</p>";
echo "<p>Exam: UTS-TIK-XI</p>";
echo "<hr>";

$sessionId = 3273;

try {
    // 1. Get all answers with correct answers
    echo "<h3>1. Mengambil jawaban...</h3>";
    $stmt = db()->prepare("
        SELECT a.*, q.correct_answer, q.points
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        WHERE a.session_id = ?
    ");
    $stmt->execute([$sessionId]);
    $answers = $stmt->fetchAll();
    
    echo "<p>Total jawaban: " . count($answers) . "</p>";
    
    // 2. Calculate score
    echo "<h3>2. Menghitung nilai...</h3>";
    $totalScore = 0;
    $correctCount = 0;
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>No</th><th>Question ID</th><th>Jawaban Siswa</th><th>Jawaban Benar</th><th>Points</th><th>Status</th></tr>";
    
    foreach ($answers as $idx => $answer) {
        $isCorrect = (strtolower($answer['answer']) === strtolower($answer['correct_answer']));
        $points = $isCorrect ? $answer['points'] : 0;
        
        if ($isCorrect) {
            $totalScore += $answer['points'];
            $correctCount++;
        }
        
        echo "<tr>";
        echo "<td>" . ($idx + 1) . "</td>";
        echo "<td>" . $answer['question_id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($answer['answer']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($answer['correct_answer']) . "</td>";
        echo "<td>" . $answer['points'] . "</td>";
        echo "<td style='color: " . ($isCorrect ? 'green' : 'red') . ";'>" . ($isCorrect ? '✓ BENAR' : '✗ SALAH') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3 style='color: blue;'>Benar: {$correctCount} dari " . count($answers) . "</h3>";
    echo "<h3 style='color: green;'>NILAI TOTAL: {$totalScore}</h3>";
    
    // 3. Update session to submitted
    echo "<h3>3. Menyimpan hasil...</h3>";
    $stmt = db()->prepare("
        UPDATE exam_sessions 
        SET status = 'submitted', 
            submitted_at = NOW(), 
            score = ?,
            is_best_score = 1
        WHERE id = ?
    ");
    $result = $stmt->execute([$totalScore, $sessionId]);
    
    if ($result) {
        echo "<div style='background: #d4edda; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0;'>";
        echo "<h3 style='color: #155724;'>✓ BERHASIL!</h3>";
        echo "<p><strong>Session berhasil disubmit!</strong></p>";
        echo "<p>Nilai: <strong>{$totalScore}</strong></p>";
        echo "<p>Status: <strong>submitted</strong></p>";
        echo "<p>Waktu submit: <strong>" . date('Y-m-d H:i:s') . "</strong></p>";
        echo "</div>";
        
        echo "<h3>4. Link untuk siswa:</h3>";
        echo "<p><a href='" . config('app_url') . "/exam/result/{$sessionId}' target='_blank'>";
        echo config('app_url') . "/exam/result/{$sessionId}";
        echo "</a></p>";
    } else {
        throw new Exception("Gagal update session");
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-left: 4px solid #dc3545;'>";
    echo "<h3 style='color: #721c24;'>ERROR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
