<?php
require_once 'config.php';
$config = require 'config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$conn->set_charset('utf8mb4');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recover Siswa 1730 Session 934</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .problem { background: #ffcccc; }
        .ok { background: #ccffcc; }
        .btn { padding: 10px 20px; margin: 10px 0; cursor: pointer; border: none; border-radius: 4px; background: #dc3545; color: white; }
    </style>
</head>
<body>

<h1>RECOVER SISWA 1730 - SESSION 934 (UTS FISIKA XI)</h1>

<div class="box">
    <h2>1. Detail Session 934</h2>
    <?php
    $sessionId = 934;
    $result = $conn->query("SELECT es.id, es.score, es.status, es.submitted_at, e.id as exam_id, e.name FROM exam_sessions es JOIN exams e ON es.exam_id = e.id WHERE es.id = $sessionId");
    $session = $result->fetch_assoc();
    
    if ($session) {
        echo "<p><strong>Session ID:</strong> " . $session['id'] . "</p>";
        echo "<p><strong>Exam:</strong> " . $session['name'] . "</p>";
        echo "<p><strong>Score:</strong> " . $session['score'] . "</p>";
        echo "<p><strong>Status:</strong> " . $session['status'] . "</p>";
        echo "<p><strong>Submitted At:</strong> " . $session['submitted_at'] . "</p>";
        
        $examId = $session['exam_id'];
    }
    ?>
</div>

<div class="box">
    <h2>2. Cek Answers untuk Session 934</h2>
    <?php
    $result = $conn->query("SELECT COUNT(*) as cnt FROM answers WHERE session_id = $sessionId");
    $row = $result->fetch_assoc();
    $answerCount = $row['cnt'];
    
    echo "<p><strong>Total Answers:</strong> " . $answerCount . "</p>";
    
    if ($answerCount === 0) {
        echo "<p style='color:red;'><strong>⚠️ TIDAK ADA ANSWERS! Jawaban tidak tersimpan saat submit.</strong></p>";
    }
    ?>
</div>

<div class="box">
    <h2>3. Cek Exam ID 32 (UTS FISIKA XI)</h2>
    <?php
    $result = $conn->query("SELECT id, name, title FROM exams WHERE id = 32");
    $exam = $result->fetch_assoc();
    
    if ($exam) {
        echo "<p><strong>Exam ID:</strong> " . $exam['id'] . "</p>";
        echo "<p><strong>Name:</strong> " . $exam['name'] . "</p>";
        echo "<p><strong>Title:</strong> " . $exam['title'] . "</p>";
        
        // Cek jumlah questions
        $result = $conn->query("SELECT COUNT(*) as cnt FROM questions WHERE subject_id = (SELECT subject_id FROM exams WHERE id = 32)");
        $q = $result->fetch_assoc();
        echo "<p><strong>Total Questions:</strong> " . $q['cnt'] . "</p>";
    }
    ?>
</div>

<div class="box">
    <h2>4. Cek Session Lain dari User 1730</h2>
    <?php
    $result = $conn->query("SELECT es.id, es.score, e.name FROM exam_sessions es JOIN exams e ON es.exam_id = e.id WHERE es.user_id = 1730 ORDER BY es.submitted_at DESC");
    
    echo "<table>";
    echo "<tr><th>Session ID</th><th>Exam</th><th>Score</th><th>Answers</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $sid = $row['id'];
        $ansResult = $conn->query("SELECT COUNT(*) as cnt FROM answers WHERE session_id = $sid");
        $ans = $ansResult->fetch_assoc();
        
        $rowClass = ($ans['cnt'] === 0) ? 'problem' : 'ok';
        echo "<tr class='$rowClass'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['score'] . "</td>";
        echo "<td>" . $ans['cnt'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    ?>
</div>

<div class="box">
    <h2>5. Solusi</h2>
    <p>Karena jawaban tidak tersimpan, ada beberapa kemungkinan:</p>
    <ol>
        <li><strong>Error saat submit:</strong> Aplikasi error sebelum menyimpan answers ke database</li>
        <li><strong>Timeout:</strong> Koneksi putus saat submit</li>
        <li><strong>Bug di aplikasi:</strong> Answers tidak di-insert ke tabel answers</li>
    </ol>
    
    <p><strong>Solusi:</strong></p>
    <ul>
        <li>Jika siswa masih ingat jawaban, bisa di-input manual</li>
        <li>Jika ada log/screenshot jawaban, bisa di-recover</li>
        <li>Atau bisa di-set nilai manual berdasarkan penilaian guru</li>
    </ul>
    
    <p style="color:red;"><strong>⚠️ Catatan:</strong> Ini adalah bug aplikasi yang perlu diperbaiki di kode untuk mencegah hal serupa di masa depan.</p>
</div>

</body>
</html>

<?php
$conn->close();
?>
