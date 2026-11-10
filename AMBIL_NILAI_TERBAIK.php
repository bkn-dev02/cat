<?php
require_once 'app/helpers.php';

$pdo = db();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ambil Nilai Terbaik Per Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .controls { margin: 20px 0; }
        select { padding: 10px; font-size: 14px; width: 400px; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; margin-right: 10px; font-size: 14px; }
        button:hover { background-color: #45a049; }
        button.danger { background-color: #dc3545; }
        button.danger:hover { background-color: #c82333; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .best { background-color: #D4EDDA; font-weight: bold; }
        .multiple { background-color: #FFF3CD; }
        .info { background-color: #e7f3fe; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .warning { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
        .summary { margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-left: 4px solid #4CAF50; }
    </style>
</head>
<body>

<div class="container">
<h1>🏆 AMBIL NILAI TERBAIK PER SISWA</h1>

<div class="info">
    <strong>ℹ️ Cara Kerja:</strong> Tool ini akan:
    <ol>
        <li>Cari siswa yang submit lebih dari 1x atau ada error</li>
        <li>Lihat semua jawaban yang tersimpan di database</li>
        <li>Hitung nilai untuk setiap attempt</li>
        <li>Ambil nilai tertinggi untuk setiap siswa</li>
        <li>Update database dengan nilai terbaik</li>
    </ol>
</div>

<div class="controls">
    <form method="GET">
        <label><strong>Pilih Mata Pelajaran:</strong></label><br><br>
        <select name="subject_id" required onchange="this.form.submit()">
            <option value="">-- Pilih Mata Pelajaran --</option>
            <?php
            try {
                $stmt = $pdo->query("
                    SELECT DISTINCT s.id, s.name
                    FROM subjects s
                    JOIN exams e ON s.id = e.subject_id
                    ORDER BY s.name
                ");
                $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($subjects as $subject) {
                    $selected = (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : '';
                    echo "<option value='{$subject['id']}' {$selected}>{$subject['name']}</option>";
                }
            } catch (Exception $e) {
                echo "<option disabled>Error: " . $e->getMessage() . "</option>";
            }
            ?>
        </select>
        <button type="submit">Analisis</button>
    </form>
</div>

<?php
if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];
    
    try {
        // Cek subject
        $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$subject) {
            echo "<p style='color: red;'>❌ Mata pelajaran tidak ditemukan</p>";
        } else {
            echo "<h2>Analisis: {$subject['name']}</h2>";
            
            // Ambil semua exam di subject ini
            $stmt = $pdo->prepare("SELECT id FROM exams WHERE subject_id = ?");
            $stmt->execute([$subject_id]);
            $exams = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($exams) == 0) {
                echo "<p style='color: orange;'>ℹ️ Tidak ada ujian untuk mata pelajaran ini</p>";
            } else {
                $placeholders = implode(',', array_fill(0, count($exams), '?'));
                
                // Cari siswa dengan multiple attempts atau error
                $query = "
                    SELECT 
                        u.id,
                        u.full_name,
                        u.kelas,
                        COUNT(DISTINCT es.id) as total_attempts,
                        GROUP_CONCAT(DISTINCT es.status) as statuses
                    FROM users u
                    JOIN exam_sessions es ON u.id = es.user_id
                    WHERE es.exam_id IN ({$placeholders}) AND u.role = 'siswa'
                    GROUP BY u.id
                    HAVING total_attempts > 1 OR statuses LIKE '%suspended%' OR statuses LIKE '%active%'
                    ORDER BY u.kelas, u.full_name
                ";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($exams);
                $problem_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($problem_students) == 0) {
                    echo "<p style='color: green;'>✓ Tidak ada siswa dengan multiple attempts atau error</p>";
                } else {
                    echo "<h3>📊 Siswa dengan Multiple Attempts atau Error: " . count($problem_students) . "</h3>";
                    
                    echo "<table>";
                    echo "<tr>";
                    echo "<th>No</th>";
                    echo "<th>Nama Siswa</th>";
                    echo "<th>Kelas</th>";
                    echo "<th>Attempts</th>";
                    echo "<th>Status</th>";
                    echo "<th>Detail Nilai</th>";
                    echo "<th>Nilai Terbaik</th>";
                    echo "</tr>";
                    
                    $no = 1;
                    $total_updated = 0;
                    
                    foreach ($problem_students as $student) {
                        // Ambil semua session siswa
                        $stmt = $pdo->prepare("
                            SELECT 
                                es.id as session_id,
                                es.status,
                                es.score,
                                es.submitted_at,
                                (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count
                            FROM exam_sessions es
                            WHERE es.user_id = ? AND es.exam_id IN ({$placeholders})
                            ORDER BY es.submitted_at DESC
                        ");
                        $stmt->execute(array_merge([$student['id']], $exams));
                        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Hitung nilai untuk setiap session
                        $best_score = 0;
                        $best_session_id = null;
                        $detail_nilai = [];
                        
                        foreach ($sessions as $session) {
                            // Ambil jawaban siswa
                            $stmt = $pdo->prepare("
                                SELECT 
                                    a.question_id,
                                    a.answer,
                                    q.correct_answer,
                                    q.points
                                FROM answers a
                                JOIN questions q ON a.question_id = q.id
                                WHERE a.session_id = ?
                            ");
                            $stmt->execute([$session['session_id']]);
                            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Hitung score
                            $correct = 0;
                            $total_points = 0;
                            $earned_points = 0;
                            
                            foreach ($answers as $answer) {
                                $total_points += $answer['points'];
                                $user_ans = strtoupper(trim($answer['answer']));
                                $correct_ans = strtoupper(trim($answer['correct_answer']));
                                
                                if ($user_ans === $correct_ans) {
                                    $correct++;
                                    $earned_points += $answer['points'];
                                }
                            }
                            
                            $score = ($total_points > 0) ? ($earned_points / $total_points) * 100 : 0;
                            
                            $detail_nilai[] = [
                                'session_id' => $session['session_id'],
                                'status' => $session['status'],
                                'score' => $score,
                                'submitted_at' => $session['submitted_at'],
                                'answer_count' => $session['answer_count']
                            ];
                            
                            if ($score > $best_score) {
                                $best_score = $score;
                                $best_session_id = $session['session_id'];
                            }
                        }
                        
                        // Tampilkan detail
                        echo "<tr class='multiple'>";
                        echo "<td>{$no}</td>";
                        echo "<td>{$student['full_name']}</td>";
                        echo "<td>{$student['kelas']}</td>";
                        echo "<td>{$student['total_attempts']}</td>";
                        echo "<td>{$student['statuses']}</td>";
                        echo "<td>";
                        foreach ($detail_nilai as $detail) {
                            echo "Session {$detail['session_id']}: " . number_format($detail['score'], 2) . "% ({$detail['answer_count']} jawab) - {$detail['status']}<br>";
                        }
                        echo "</td>";
                        echo "<td class='best'>" . number_format($best_score, 2) . "%</td>";
                        echo "</tr>";
                        
                        $no++;
                    }
                    
                    echo "</table>";
                    
                    // Tombol untuk update nilai
                    echo "<div style='margin-top: 20px;'>";
                    echo "<form method='POST'>";
                    echo "<input type='hidden' name='subject_id' value='{$subject_id}'>";
                    echo "<button type='submit' name='action' value='update_best_scores' class='danger'>🔄 UPDATE SEMUA NILAI TERBAIK KE DATABASE</button>";
                    echo "</form>";
                    echo "</div>";
                    
                    // Proses update jika diminta
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_best_scores') {
                        echo "<h3>⏳ Memproses Update...</h3>";
                        
                        $updated = 0;
                        $errors = 0;
                        
                        foreach ($problem_students as $student) {
                            try {
                                // Ambil semua session siswa
                                $stmt = $pdo->prepare("
                                    SELECT 
                                        es.id as session_id,
                                        es.status,
                                        (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count
                                    FROM exam_sessions es
                                    WHERE es.user_id = ? AND es.exam_id IN ({$placeholders})
                                    ORDER BY es.submitted_at DESC
                                ");
                                $stmt->execute(array_merge([$student['id']], $exams));
                                $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                $best_score = 0;
                                $best_session_id = null;
                                
                                foreach ($sessions as $session) {
                                    // Hitung score
                                    $stmt = $pdo->prepare("
                                        SELECT 
                                            a.answer,
                                            q.correct_answer,
                                            q.points
                                        FROM answers a
                                        JOIN questions q ON a.question_id = q.id
                                        WHERE a.session_id = ?
                                    ");
                                    $stmt->execute([$session['session_id']]);
                                    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    $total_points = 0;
                                    $earned_points = 0;
                                    
                                    foreach ($answers as $answer) {
                                        $total_points += $answer['points'];
                                        $user_ans = strtoupper(trim($answer['answer']));
                                        $correct_ans = strtoupper(trim($answer['correct_answer']));
                                        
                                        if ($user_ans === $correct_ans) {
                                            $earned_points += $answer['points'];
                                        }
                                    }
                                    
                                    $score = ($total_points > 0) ? ($earned_points / $total_points) * 100 : 0;
                                    
                                    if ($score > $best_score) {
                                        $best_score = $score;
                                        $best_session_id = $session['session_id'];
                                    }
                                }
                                
                                // Update nilai terbaik
                                if ($best_session_id) {
                                    $stmt = $pdo->prepare("UPDATE exam_sessions SET score = ?, is_best_score = 1 WHERE id = ?");
                                    $stmt->execute([$best_score, $best_session_id]);
                                    
                                    // Set is_best_score = 0 untuk session lain
                                    $stmt = $pdo->prepare("UPDATE exam_sessions SET is_best_score = 0 WHERE user_id = ? AND exam_id IN ({$placeholders}) AND id != ?");
                                    $stmt->execute(array_merge([$student['id']], $exams, [$best_session_id]));
                                    
                                    $updated++;
                                    echo "<p>✓ {$student['full_name']}: Nilai terbaik " . number_format($best_score, 2) . "% disimpan</p>";
                                }
                            } catch (Exception $e) {
                                $errors++;
                                echo "<p style='color: red;'>✗ {$student['full_name']}: " . $e->getMessage() . "</p>";
                            }
                        }
                        
                        echo "<div class='summary'>";
                        echo "<h3>✅ Selesai!</h3>";
                        echo "<p>Total diupdate: {$updated}</p>";
                        echo "<p>Total error: {$errors}</p>";
                        echo "</div>";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}
?>

</div>

</body>
</html>
