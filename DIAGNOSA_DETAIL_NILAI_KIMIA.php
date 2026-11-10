<?php
/**
 * DIAGNOSA DETAIL NILAI SISWA X-ARCHIMEDES UTS KIMIA X
 * Melihat jawaban yang tersimpan dan membandingkan dengan nilai terekam
 */

// Koneksi database
$db_host = 'localhost';
$db_name = 'u245247469_catbkn';
$db_user = 'u245247469_enterprise';
$db_pass = '1.SMAUnggul234!!';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Daftar siswa
$students = [
    'agape.pratama@student.ubkn.sch.id' => 'AGAPE PRATAMA MARBUN',
    'brenda.christy@student.ubkn.sch.id' => 'BRENDA CHRISTY AGITHA BARUS',
    'brillianda.simangunsong@student.ubkn.sch.id' => 'BRILLIANDA J. SIMANGUNSONG',
    'chelin.juwita@student.ubkn.sch.id' => 'CHELIN JUWITA TASYA SIMANJUNTAK',
    'christian.darrel@student.ubkn.sch.id' => 'CHRISTIAN DARREL SITUMORANG',
    'cristian.doli@student.ubkn.sch.id' => 'CRISTIAN DOLI NAIBAHO',
    'david.hottua@student.ubkn.sch.id' => 'DAVID HOTTUA SIMBOLON',
    'eginta.octamira@student.ubkn.sch.id' => 'EGINTA OCTAMIRA BR. TARIGAN',
    'gading.sweka@student.ubkn.sch.id' => 'GADING SWEKA ARIHTA SEMBIRING',
    'gideon.maruli@student.ubkn.sch.id' => 'GIDEON MARULI TUA SINAGA',
    'hans.dianro@student.ubkn.sch.id' => 'HANS DIANRO TARIGAN',
    'harvat.marselo@student.ubkn.sch.id' => 'HARVAT MARSELO PERANGIN ANGIN',
    'immanuel.sijabat@student.ubkn.sch.id' => 'IMMANUEL SIJABAT',
    'inez.nathania@student.ubkn.sch.id' => 'INEZ NATHANIA SITEPU',
    'jasmine.olivia@student.ubkn.sch.id' => 'JASMINE OLIVIA SIREGAR',
    'john.moses@student.ubkn.sch.id' => 'JOHN MOSES HAZAKIM HUTABARAT',
    'kristian.stevanus@student.ubkn.sch.id' => 'KRISTIAN STEVANUS SEMBIRING',
    'martina.aurelia@student.ubkn.sch.id' => 'MARTINA AURELIA LUMBANTOBING',
    'nicolas.benyamin@student.ubkn.sch.id' => 'NICOLAS BENYAMIN SIAHAAN',
    'petuahta.sitepu@student.ubkn.sch.id' => 'PETUAHTA SITEPU',
    'ravael.timoti@student.ubkn.sch.id' => 'RAVAEL TIMOTI BHAYANGKARA BATUBARA',
    'rendy.vian@student.ubkn.sch.id' => 'RENDY VIAN WANDA SITANGGANG',
    'rony.kristian@student.ubkn.sch.id' => 'RONY KRISTIAN PURBA',
    'sean.efrata@student.ubkn.sch.id' => 'SEAN EFRATA MALAU',
    'stephany.yesyurun@student.ubkn.sch.id' => 'STEPHANY YESYURUN KABAN',
    'theodora.timothy@student.ubkn.sch.id' => 'THEODORA TIMOTHY PAULINE SILALAHI',
    'william.barclay@student.ubkn.sch.id' => 'WILLIAM BARCLAY TARIGAN'
];

echo "<h2>DIAGNOSA DETAIL NILAI SISWA X-ARCHIMEDES UTS KIMIA X</h2>";
echo "<p>Melihat jawaban yang tersimpan dan membandingkan dengan nilai terekam di database</p>";

$problemCount = 0;
$okCount = 0;

foreach ($students as $email => $fullName) {
    // Cari user
    $userQuery = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if ($userResult->num_rows === 0) {
        continue;
    }
    
    $user = $userResult->fetch_assoc();
    $userId = $user['id'];
    
    // Cari exam session untuk UTS KIMIA X
    $sessionQuery = "SELECT es.id, es.score, es.status, es.submitted_at FROM exam_sessions es 
                     JOIN exams e ON es.exam_id = e.id 
                     WHERE es.user_id = ? AND e.name LIKE '%KIMIA%' AND e.class LIKE '%ARCHIMEDES%'";
    $stmt = $conn->prepare($sessionQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $sessionResult = $stmt->get_result();
    
    if ($sessionResult->num_rows === 0) {
        continue;
    }
    
    $session = $sessionResult->fetch_assoc();
    $sessionId = $session['id'];
    $recordedScore = $session['score'];
    
    // Ambil semua jawaban dan hitung score yang seharusnya
    $answersQuery = "SELECT a.id, a.answer, q.correct_answer, q.points 
                     FROM answers a 
                     JOIN questions q ON a.question_id = q.id 
                     WHERE a.session_id = ?
                     ORDER BY a.id";
    $stmt = $conn->prepare($answersQuery);
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $answersResult = $stmt->get_result();
    
    $totalAnswers = 0;
    $correctAnswers = 0;
    $calculatedScore = 0;
    $answerDetails = [];
    
    while ($answer = $answersResult->fetch_assoc()) {
        $totalAnswers++;
        $isCorrect = (strtolower($answer['answer']) === strtolower($answer['correct_answer']));
        
        if ($isCorrect) {
            $correctAnswers++;
            $calculatedScore += $answer['points'];
        }
        
        $answerDetails[] = [
            'answer' => $answer['answer'],
            'correct' => $answer['correct_answer'],
            'isCorrect' => $isCorrect,
            'points' => $answer['points']
        ];
    }
    
    $status = ($recordedScore == $calculatedScore) ? "OK" : "TIDAK COCOK";
    $statusColor = ($recordedScore == $calculatedScore) ? "green" : "red";
    
    if ($recordedScore != $calculatedScore) {
        $problemCount++;
        echo "<div style='border:2px solid red; padding:10px; margin:10px 0; background-color:#ffe6e6;'>";
    } else {
        $okCount++;
        echo "<div style='border:2px solid green; padding:10px; margin:10px 0; background-color:#e6ffe6;'>";
    }
    
    echo "<h3>$fullName ($email)</h3>";
    echo "<p><strong>Status:</strong> <span style='color:$statusColor;'>$status</span></p>";
    echo "<p><strong>Nilai Terekam:</strong> $recordedScore | <strong>Nilai Seharusnya:</strong> $calculatedScore</p>";
    echo "<p><strong>Jawaban Benar:</strong> $correctAnswers / $totalAnswers | <strong>Points:</strong> $calculatedScore</p>";
    
    if ($recordedScore != $calculatedScore) {
        echo "<p style='color:red;'><strong>PERBEDAAN: " . ($calculatedScore - $recordedScore) . " poin</strong></p>";
        echo "<details>";
        echo "<summary>Lihat Detail Jawaban</summary>";
        echo "<table border='1' cellpadding='5' style='margin-top:10px;'>";
        echo "<tr><th>No</th><th>Jawaban Siswa</th><th>Jawaban Benar</th><th>Points</th><th>Status</th></tr>";
        foreach ($answerDetails as $idx => $detail) {
            $detailStatus = $detail['isCorrect'] ? '✓ BENAR' : '✗ SALAH';
            $detailColor = $detail['isCorrect'] ? 'green' : 'red';
            echo "<tr>";
            echo "<td>" . ($idx + 1) . "</td>";
            echo "<td>" . htmlspecialchars($detail['answer']) . "</td>";
            echo "<td>" . htmlspecialchars($detail['correct']) . "</td>";
            echo "<td>" . $detail['points'] . "</td>";
            echo "<td style='color:$detailColor;'><strong>$detailStatus</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
    }
    
    echo "</div>";
}

echo "<h2>RINGKASAN</h2>";
echo "<p>Total Siswa OK: <strong style='color:green;'>$okCount</strong></p>";
echo "<p>Total Siswa dengan Masalah: <strong style='color:red;'>$problemCount</strong></p>";

$conn->close();
?>
