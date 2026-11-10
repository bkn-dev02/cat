<?php
/**
 * RECALCULATE SCORES AFTER FIX
 * 
 * Setelah memperbaiki correct_answer case, script ini akan
 * menghitung ulang semua nilai siswa yang sudah submit
 */

require_once 'config.php';

// Force output buffering off for real-time display
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/html; charset=utf-8');

// Increase execution time
set_time_limit(300); // 5 minutes
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recalculate Scores</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 900px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .success { color: #27ae60; }
        .info { color: #3498db; }
        .warning { color: #f39c12; }
        .unchanged { color: #7f8c8d; }
        .session { padding: 10px; margin: 5px 0; border-left: 4px solid #3498db; background: #ecf0f1; }
        .session.changed { border-left-color: #27ae60; background: #d5f4e6; }
        .summary { background: #ecf0f1; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .score-change { font-weight: bold; }
        .score-up { color: #27ae60; }
    </style>
</head>
<body>
<div class="container">
<h1>🔄 RECALCULATE SCORES AFTER FIX</h1>
<?php

echo "<p class='info'>Mencari session yang perlu dihitung ulang...</p>\n";
flush(); // Force output

try {
    $db = db();
    
    // Get all submitted sessions
    $stmt = $db->query("
        SELECT es.id, es.user_id, es.exam_id, es.score as old_score,
               u.full_name as student_name, e.title as exam_title
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        WHERE es.status = 'submitted'
        ORDER BY es.submitted_at DESC
    ");
    $sessions = $stmt->fetchAll();
    
    echo "<p class='info'><strong>Ditemukan " . count($sessions) . " session yang perlu dihitung ulang</strong></p>\n";
    flush();
    
    if (count($sessions) === 0) {
        echo "<p class='warning'>✓ Tidak ada session yang perlu dihitung ulang!</p>\n";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p>Memproses perhitungan ulang...</p>\n";
    flush();
    
    $recalculated = 0;
    $totalScoreChange = 0;
    $processed = 0;
    
    foreach ($sessions as $session) {
        $processed++;
        
        // Show progress every 10 items
        if ($processed % 10 == 0) {
            echo "<p>Progress: {$processed}/" . count($sessions) . " session...</p>\n";
            flush();
        }
        // Get all answers with correct answers and points (CASE INSENSITIVE)
        $stmt = $db->prepare("
            SELECT a.*, q.correct_answer, q.points
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = ?
        ");
        $stmt->execute([$session['id']]);
        $answers = $stmt->fetchAll();
        
        // Calculate new score
        $newScore = 0;
        $correctCount = 0;
        foreach ($answers as $answer) {
            // CASE INSENSITIVE comparison
            if (strtolower($answer['answer']) === strtolower($answer['correct_answer'])) {
                $newScore += $answer['points'];
                $correctCount++;
            }
        }
        
        // Update score if changed
        if ($newScore != $session['old_score']) {
            $stmt = $db->prepare("
                UPDATE exam_sessions 
                SET score = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newScore, $session['id']]);
            
            $scoreChange = $newScore - $session['old_score'];
            $totalScoreChange += $scoreChange;
            $recalculated++;
        }
    }
    
    echo "<p class='success'>Selesai memproses semua session!</p>\n";
    flush();
    
    echo "<div class='summary'>";
    echo "<h2 class='success'>✅ SELESAI!</h2>";
    echo "<p><strong>Total session dihitung ulang:</strong> {$recalculated} dari " . count($sessions) . " session</p>";
    echo "<p><strong>Total perubahan nilai:</strong> <span class='score-up'>+" . $totalScoreChange . "</span></p>";
    echo "<hr>";
    echo "<p class='info'><strong>Perbaikan selesai!</strong> Sekarang siswa bisa mengerjakan ujian dan nilai akan muncul dengan benar.</p>";
    echo "<p><a href='dashboard'>← Kembali ke Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    exit(1);
}
?>
</div>
</body>
</html>
