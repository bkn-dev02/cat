<?php
require_once 'app/helpers.php';

$pdo = db();

echo "<h2>HITUNG ULANG NILAI SEMUA SISWA - VERSI BENAR</h2>";

// 1. Cek session yang belum dihitung
echo "<h3>1. CEK SESSION YANG BELUM DIHITUNG:</h3>";
$stmt = $pdo->query("
    SELECT es.id, es.exam_id, es.user_id, e.title, u.full_name, es.score, es.status,
           (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    JOIN users u ON es.user_id = u.id
    WHERE es.status = 'submitted' AND es.score = 0
    ORDER BY es.id DESC
    LIMIT 50
");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total session yang belum dihitung: " . count($sessions) . "</p>";

if (count($sessions) > 0) {
    echo "<h3>2. MULAI HITUNG NILAI...</h3>";
    
    $total_calculated = 0;
    $total_error = 0;
    
    foreach ($sessions as $session) {
        $session_id = $session['id'];
        $exam_id = $session['exam_id'];
        $user_id = $session['user_id'];
        
        try {
            // Ambil semua jawaban siswa dengan correct answer dari questions
            $stmt = $pdo->prepare("
                SELECT 
                    a.id as answer_id,
                    a.question_id,
                    a.answer,
                    q.correct_answer,
                    q.points
                FROM answers a
                JOIN questions q ON a.question_id = q.id
                WHERE a.session_id = ?
            ");
            $stmt->execute([$session_id]);
            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Hitung score
            $correct = 0;
            $total = 0;
            $total_points = 0;
            $earned_points = 0;
            
            foreach ($answers as $answer) {
                $total++;
                $points = $answer['points'] ?? 0;
                $total_points += $points;
                
                // Normalisasi jawaban untuk perbandingan
                $user_answer = strtoupper(trim($answer['answer']));
                $correct_answer = strtoupper(trim($answer['correct_answer']));
                
                if ($user_answer === $correct_answer) {
                    $correct++;
                    $earned_points += $points;
                }
            }
            
            // Hitung persentase (0-100)
            $score = ($total > 0) ? ($earned_points / $total_points) * 100 : 0;
            
            // Update score di exam_sessions
            $stmt = $pdo->prepare("UPDATE exam_sessions SET score = ? WHERE id = ?");
            $stmt->execute([$score, $session_id]);
            
            echo "<p>✓ Session {$session_id} ({$session['full_name']}): {$correct}/{$total} benar = " . number_format($score, 2) . "% (Poin: {$earned_points}/{$total_points})</p>";
            $total_calculated++;
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error session {$session_id}: " . $e->getMessage() . "</p>";
            $total_error++;
        }
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ SELESAI!</h3>";
    echo "<p><strong>Total dihitung:</strong> {$total_calculated}</p>";
    echo "<p><strong>Total error:</strong> {$total_error}</p>";
    
    // Verifikasi
    echo "<h3>3. VERIFIKASI HASIL:</h3>";
    $stmt = $pdo->query("
        SELECT es.id, e.title, u.full_name, es.score, es.status
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        JOIN users u ON es.user_id = u.id
        WHERE es.status = 'submitted' AND es.score > 0
        ORDER BY es.score DESC
        LIMIT 20
    ");
    $verified = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Session ID</th><th>Exam</th><th>Siswa</th><th>Score</th><th>Status</th></tr>";
    foreach ($verified as $v) {
        echo "<tr>";
        echo "<td>{$v['id']}</td>";
        echo "<td>{$v['title']}</td>";
        echo "<td>{$v['full_name']}</td>";
        echo "<td style='font-weight: bold;'>" . number_format($v['score'], 2) . "%</td>";
        echo "<td>{$v['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color: green;'>✓ Semua nilai sudah dihitung!</p>";
    
    // Tampilkan statistik
    echo "<h3>STATISTIK NILAI:</h3>";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_submitted,
            AVG(score) as avg_score,
            MIN(score) as min_score,
            MAX(score) as max_score
        FROM exam_sessions
        WHERE status = 'submitted'
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Total Submitted</th><th>Rata-rata</th><th>Minimum</th><th>Maximum</th></tr>";
    echo "<tr>";
    echo "<td>{$stats['total_submitted']}</td>";
    echo "<td>" . number_format($stats['avg_score'], 2) . "%</td>";
    echo "<td>" . number_format($stats['min_score'], 2) . "%</td>";
    echo "<td>" . number_format($stats['max_score'], 2) . "%</td>";
    echo "</tr>";
    echo "</table>";
}
