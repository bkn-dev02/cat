<?php
/**
 * EMERGENCY FIX: Update correct_answer dari huruf kecil ke huruf besar
 * 
 * Masalah: Jawaban benar disimpan dengan huruf kecil (a,b,c,d,e)
 * tapi siswa mengirim jawaban dengan huruf besar (A,B,C,D,E)
 * sehingga perbandingan gagal dan nilai selalu 0
 * 
 * Solusi: Update semua correct_answer ke huruf besar
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
    <title>Fix Correct Answer Case</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .success { color: #27ae60; }
        .info { color: #3498db; }
        .warning { color: #f39c12; }
        .item { padding: 5px 0; }
        .summary { background: #ecf0f1; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
<h1>🔧 FIX CORRECT ANSWER CASE</h1>
<?php

echo "<p class='info'>Memeriksa database...</p>\n";
flush(); // Force output

try {
    $db = db();
    
    // Get all questions with lowercase correct_answer
    $stmt = $db->query("
        SELECT id, correct_answer, question_text
        FROM questions 
        WHERE correct_answer IN ('a', 'b', 'c', 'd', 'e')
    ");
    $questions = $stmt->fetchAll();
    
    echo "<p class='info'><strong>Ditemukan " . count($questions) . " soal dengan jawaban huruf kecil</strong></p>\n";
    flush();
    
    if (count($questions) === 0) {
        echo "<p class='success'>✓ Tidak ada yang perlu diperbaiki! Semua jawaban sudah menggunakan huruf besar.</p>\n";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p>Memproses perbaikan...</p>\n";
    flush();
    
    // Update each question
    $updateStmt = $db->prepare("
        UPDATE questions 
        SET correct_answer = ? 
        WHERE id = ?
    ");
    
    $fixed = 0;
    foreach ($questions as $q) {
        $newAnswer = strtoupper($q['correct_answer']);
        $updateStmt->execute([$newAnswer, $q['id']]);
        $fixed++;
        
        // Show progress every 10 items
        if ($fixed % 10 == 0) {
            echo "<p>Progress: {$fixed}/" . count($questions) . " soal...</p>\n";
            flush();
        }
    }
    
    echo "<div class='summary'>";
    echo "<h2 class='success'>✅ SELESAI!</h2>";
    echo "<p><strong>Total diperbaiki:</strong> {$fixed} soal</p>";
    echo "<p>Semua jawaban benar sudah diubah ke huruf besar (A, B, C, D, E)</p>";
    echo "<p class='info'><strong>Langkah selanjutnya:</strong> Jalankan script <a href='RECALCULATE_SCORES_AFTER_FIX.php'>RECALCULATE_SCORES_AFTER_FIX.php</a> untuk menghitung ulang nilai siswa.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    exit(1);
}
?>
</div>
</body>
</html>
