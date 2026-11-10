<?php
require_once 'app/helpers.php';

$pdo = db();

echo "<h2>DIAGNOSA: KENAPA NILAI BANYAK 0%</h2>";

// 1. Cek session dengan score 0
echo "<h3>1. CEK SESSION DENGAN SCORE 0%:</h3>";
$stmt = $pdo->query("
    SELECT es.id, e.title, u.full_name, es.score
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    JOIN users u ON es.user_id = u.id
    WHERE es.status = 'submitted' AND es.score = 0
    LIMIT 5
");
$zero_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($zero_sessions as $session) {
    echo "<h4>Session {$session['id']} - {$session['full_name']} ({$session['title']}):</h4>";
    
    // Cek jawaban siswa
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.answer,
            q.correct_answer,
            q.question_text,
            q.points
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        WHERE a.session_id = ?
        LIMIT 5
    ");
    $stmt->execute([$session['id']]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Jawaban Siswa</th><th>Jawaban Benar</th><th>Cocok?</th><th>Poin</th></tr>";
    foreach ($answers as $answer) {
        $user_ans = strtoupper(trim($answer['answer']));
        $correct_ans = strtoupper(trim($answer['correct_answer']));
        $match = ($user_ans === $correct_ans) ? '✓ YA' : '✗ TIDAK';
        echo "<tr>";
        echo "<td>{$answer['answer']}</td>";
        echo "<td>{$answer['correct_answer']}</td>";
        echo "<td>{$match}</td>";
        echo "<td>{$answer['points']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";
}

// 2. Cek apakah ada correct_answer yang kosong
echo "<h3>2. CEK SOAL DENGAN CORRECT_ANSWER KOSONG:</h3>";
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM questions WHERE correct_answer IS NULL OR correct_answer = ''
");
$result = $stmt->fetch();
echo "<p>Total soal dengan correct_answer kosong: " . $result['total'] . "</p>";

// 3. Cek sample soal
echo "<h3>3. SAMPLE SOAL DENGAN CORRECT_ANSWER:</h3>";
$stmt = $pdo->query("
    SELECT id, question_text, correct_answer, points
    FROM questions
    WHERE correct_answer IS NOT NULL AND correct_answer != ''
    LIMIT 10
");
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Soal</th><th>Jawaban Benar</th><th>Poin</th></tr>";
foreach ($questions as $q) {
    $text = substr($q['question_text'], 0, 50) . '...';
    echo "<tr>";
    echo "<td>{$q['id']}</td>";
    echo "<td>{$text}</td>";
    echo "<td>{$q['correct_answer']}</td>";
    echo "<td>{$q['points']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Cek jawaban siswa vs correct answer
echo "<h3>4. ANALISIS DETAIL JAWABAN:</h3>";
$stmt = $pdo->query("
    SELECT 
        a.answer,
        q.correct_answer,
        COUNT(*) as total,
        SUM(CASE WHEN UPPER(TRIM(a.answer)) = UPPER(TRIM(q.correct_answer)) THEN 1 ELSE 0 END) as benar
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    GROUP BY a.answer, q.correct_answer
    LIMIT 20
");
$analysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Jawaban Siswa</th><th>Jawaban Benar</th><th>Total</th><th>Benar</th><th>Salah</th></tr>";
foreach ($analysis as $row) {
    $salah = $row['total'] - $row['benar'];
    echo "<tr>";
    echo "<td>{$row['answer']}</td>";
    echo "<td>{$row['correct_answer']}</td>";
    echo "<td>{$row['total']}</td>";
    echo "<td style='color: green;'>{$row['benar']}</td>";
    echo "<td style='color: red;'>{$salah}</td>";
    echo "</tr>";
}
echo "</table>";
