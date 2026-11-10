<?php
/**
 * QUICK FIX - Update semua sekaligus tanpa loop
 * Lebih cepat untuk data banyak
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
set_time_limit(300);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Fix Nilai</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #2c3e50; }
        .success { color: #27ae60; padding: 15px; background: #d5f4e6; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; padding: 15px; background: #d6eaf8; border-radius: 5px; margin: 10px 0; }
        .step { padding: 10px; margin: 10px 0; border-left: 4px solid #3498db; }
    </style>
</head>
<body>
<div class="container">
<h1>⚡ QUICK FIX NILAI - Super Cepat</h1>

<?php
try {
    $db = db();
    
    // STEP 1: Fix format jawaban benar (huruf kecil → huruf besar)
    echo "<div class='step'><strong>Step 1:</strong> Memperbaiki format jawaban benar...</div>";
    
    $stmt = $db->prepare("
        UPDATE questions 
        SET correct_answer = UPPER(correct_answer)
        WHERE correct_answer IN ('a', 'b', 'c', 'd', 'e')
    ");
    $stmt->execute();
    $fixed = $stmt->rowCount();
    
    echo "<div class='success'>✅ Berhasil memperbaiki <strong>{$fixed}</strong> soal</div>";
    
    // STEP 2: Recalculate semua nilai sekaligus
    echo "<div class='step'><strong>Step 2:</strong> Menghitung ulang semua nilai...</div>";
    
    $stmt = $db->prepare("
        UPDATE exam_sessions es
        SET es.score = (
            SELECT COALESCE(SUM(
                CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN q.points ELSE 0 END
            ), 0)
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = es.id
        )
        WHERE es.status = 'submitted'
        AND EXISTS (SELECT 1 FROM answers WHERE session_id = es.id)
    ");
    $stmt->execute();
    $recalculated = $stmt->rowCount();
    
    echo "<div class='success'>✅ Berhasil menghitung ulang <strong>{$recalculated}</strong> session</div>";
    
    // STEP 3: Statistik
    echo "<div class='step'><strong>Step 3:</strong> Statistik hasil...</div>";
    
    // Cek berapa session yang masih nilai 0
    $stmt = $db->query("
        SELECT COUNT(*) as total
        FROM exam_sessions es
        WHERE es.status = 'submitted'
        AND es.score = 0
        AND EXISTS (SELECT 1 FROM answers WHERE session_id = es.id)
    ");
    $zeroScore = $stmt->fetch()['total'];
    
    if ($zeroScore > 0) {
        echo "<div class='info'>⚠️ Masih ada <strong>{$zeroScore}</strong> session dengan nilai 0 (mungkin semua jawaban salah)</div>";
    } else {
        echo "<div class='success'>✅ Tidak ada session dengan nilai 0 yang bermasalah</div>";
    }
    
    // Summary
    echo "<div class='success' style='margin-top: 30px; font-size: 18px;'>";
    echo "<h2 style='margin-top: 0;'>🎉 SELESAI!</h2>";
    echo "<p><strong>Total soal diperbaiki:</strong> {$fixed}</p>";
    echo "<p><strong>Total session dihitung ulang:</strong> {$recalculated}</p>";
    echo "<hr>";
    echo "<p><strong>Sistem sudah siap digunakan!</strong></p>";
    echo "<p>Guru bisa membuat soal baru dan nilai akan muncul dengan benar setelah siswa submit.</p>";
    echo "</div>";
    
    echo "<p><a href='TEST_SISTEM_LENGKAP.php'>🔍 Test Sistem Lengkap</a> | <a href='dashboard'>← Kembali ke Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 15px; background: #fadbd8; border-radius: 5px;'>";
    echo "<strong>ERROR:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

</div>
</body>
</html>
