<?php
/**
 * HITUNG ULANG SEMUA NILAI UJIAN
 * Buka di browser untuk jalankan
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 menit

// Load config
$config = require __DIR__ . '/config.php';

echo "<h1>🔄 Hitung Ulang Nilai Ujian</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .success { background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid #4CAF50; }
    .info { background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid #2196F3; }
    .error { background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid #f44336; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
</style>";

try {
    // Connect to database
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $db = new PDO($dsn, $config['db_user'], $config['db_pass']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "<div class='success'>✅ Koneksi database berhasil</div>";
    
    // Cek berapa yang perlu dihitung
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM exam_sessions 
        WHERE submitted_at IS NOT NULL 
        AND (score IS NULL OR score = 0)
    ");
    $needCalculation = $stmt->fetch()['total'];
    
    echo "<div class='info'>📊 Ditemukan <strong>$needCalculation</strong> ujian yang perlu dihitung nilainya</div>";
    
    if ($needCalculation == 0) {
        echo "<div class='success'>✅ Semua nilai sudah dihitung!</div>";
        exit;
    }
    
    echo "<div class='info'>⏳ Memulai perhitungan...</div>";
    echo "<pre>";
    
    // Get all sessions that need calculation
    $stmt = $db->query("
        SELECT 
            es.id,
            es.exam_id,
            u.full_name,
            e.title as exam_title
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        WHERE es.submitted_at IS NOT NULL 
        AND (es.score IS NULL OR es.score = 0)
        ORDER BY es.id
    ");
    $sessions = $stmt->fetchAll();
    
    $success = 0;
    $failed = 0;
    
    foreach ($sessions as $session) {
        // Calculate score - case insensitive comparison
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_questions,
                SUM(CASE WHEN UPPER(a.answer) = UPPER(q.correct_answer) THEN 1 ELSE 0 END) as correct_answers
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = ?
        ");
        $stmt->execute([$session['id']]);
        $result = $stmt->fetch();
        
        if ($result['total_questions'] > 0) {
            $score = ($result['correct_answers'] / $result['total_questions']) * 100;
            
            // Update score
            $updateStmt = $db->prepare("
                UPDATE exam_sessions 
                SET score = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$score, $session['id']]);
            
            echo "✅ {$session['full_name']} - {$session['exam_title']}: {$result['correct_answers']}/{$result['total_questions']} = " . number_format($score, 2) . "\n";
            $success++;
        } else {
            echo "⚠️  {$session['full_name']} - {$session['exam_title']}: Tidak ada jawaban\n";
            $failed++;
        }
        
        // Flush output agar terlihat progress
        flush();
        ob_flush();
    }
    
    echo "</pre>";
    
    echo "<div class='success'>";
    echo "<h2>✅ Selesai!</h2>";
    echo "<p>Berhasil: <strong>$success</strong> ujian</p>";
    if ($failed > 0) {
        echo "<p>Gagal/Tidak ada jawaban: <strong>$failed</strong> ujian</p>";
    }
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📋 Langkah Selanjutnya:</h3>";
    echo "<ol>";
    echo "<li>Kembali ke dashboard</li>";
    echo "<li>Klik tombol 'Export Nilai'</li>";
    echo "<li>Pilih ujian atau kelas</li>";
    echo "<li>Download CSV</li>";
    echo "</ol>";
    echo "<p><a href='/dashboard' style='display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Kembali ke Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "</div>";
}
