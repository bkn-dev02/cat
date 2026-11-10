<?php
require_once 'app/helpers.php';

$pdo = db();

echo "<h2>HITUNG ULANG NILAI SEMUA SISWA</h2>";

// 1. Cek exam_sessions yang sudah submitted tapi score masih 0
echo "<h3>1. CEK SESSION YANG BELUM DIHITUNG:</h3>";
$stmt = $pdo->query("
    SELECT es.id, es.exam_id, es.user_id, e.title, u.full_name, es.score, es.status,
           (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    JOIN users u ON es.user_id = u.id
    WHERE es.status = 'submitted' AND es.score = 0
    ORDER BY es.id DESC
    LIMIT 20
");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Session ID</th><th>Exam</th><th>Siswa</th><th>Score</th><th>Jawaban</th><th>Status</th></tr>";
foreach ($sessions as $session) {
    echo "<tr>";
    echo "<td>{$session['id']}</td>";
    echo "<td>{$session['title']}</td>";
    echo "<td>{$session['full_name']}</td>";
    echo "<td>{$session['score']}</td>";
    echo "<td>{$session['answer_count']}</td>";
    echo "<td>{$session['status']}</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p>Total: " . count($sessions) . " session yang belum dihitung</p>";

if (count($sessions) > 0) {
    echo "<h3>2. MULAI HITUNG NILAI...</h3>";
    
    $total_calculated = 0;
    $total_error = 0;
    
    foreach ($sessions as $session) {
        $session_id = $session['id'];
        $exam_id = $session['exam_id'];
        
        try {
            // Ambil semua jawaban siswa
            $stmt = $pdo->prepare("
                SELECT a.question_id, a.answer_text, eq.correct_answer
                FROM answers a
                JOIN exam_questions eq ON a.question_id = eq.id
                WHERE a.session_id = ?
            ");
            $stmt->execute([$session_id]);
            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Hitung score
            $correct = 0;
            $total = 0;
            
            foreach ($answers as $answer) {
                $total++;
                // Normalisasi jawaban untuk perbandingan
                $user_answer = strtolower(trim($answer['answer_text']));
                $correct_answer = strtolower(trim($answer['correct_answer']));
                
                if ($user_answer === $correct_answer) {
                    $correct++;
                }
            }
            
            // Hitung persentase
            $score = ($total > 0) ? ($correct / $total) * 100 : 0;
            
            // Update score di exam_sessions
            $stmt = $pdo->prepare("UPDATE exam_sessions SET score = ? WHERE id = ?");
            $stmt->execute([$score, $session_id]);
            
            echo "<p>✓ Session {$session_id} ({$session['full_name']}): {$correct}/{$total} = " . number_format($score, 2) . "%</p>";
            $total_calculated++;
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error session {$session_id}: " . $e->getMessage() . "</p>";
            $total_error++;
        }
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ SELESAI!</h3>";
    echo "<p>Total dihitung: {$total_calculated}</p>";
    echo "<p>Total error: {$total_error}</p>";
    
    // Verifikasi
    echo "<h3>3. VERIFIKASI HASIL:</h3>";
    $stmt = $pdo->query("
        SELECT es.id, e.title, u.full_name, es.score, es.status
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        JOIN users u ON es.user_id = u.id
        WHERE es.status = 'submitted'
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
}
