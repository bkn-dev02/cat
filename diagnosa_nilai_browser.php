<?php
/**
 * DIAGNOSA NILAI SISWA X-ARCHIMEDES UTS KIMIA X
 * Akses via browser: http://localhost/cat-v2/diagnosa_nilai_browser.php
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnosa Nilai Kimia X-ARCHIMEDES</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .ok { background-color: #e6ffe6; }
        .problem { background-color: #ffe6e6; }
        .summary { margin: 20px 0; padding: 10px; border: 2px solid #333; }
    </style>
</head>
<body>

<h1>DIAGNOSA NILAI SISWA X-ARCHIMEDES UTS KIMIA X</h1>

<?php

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

echo "<table>";
echo "<tr><th>Email</th><th>Nama</th><th>Nilai Terekam</th><th>Jawaban Benar</th><th>Total Jawaban</th><th>Nilai Seharusnya</th><th>Status</th></tr>";

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
        echo "<tr class='problem'><td>$email</td><td>$fullName</td><td colspan='5'>USER TIDAK DITEMUKAN</td></tr>";
        continue;
    }
    
    $user = $userResult->fetch_assoc();
    $userId = $user['id'];
    
    // Cari exam session
    $sessionQuery = "SELECT es.id, es.score FROM exam_sessions es 
                     JOIN exams e ON es.exam_id = e.id 
                     WHERE es.user_id = ? AND e.name LIKE '%KIMIA%' AND e.class LIKE '%ARCHIMEDES%'";
    $stmt = $conn->prepare($sessionQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $sessionResult = $stmt->get_result();
    
    if ($sessionResult->num_rows === 0) {
        echo "<tr class='problem'><td>$email</td><td>$fullName</td><td colspan='5'>TIDAK ADA SESSION UJIAN</td></tr>";
        continue;
    }
    
    $session = $sessionResult->fetch_assoc();
    $sessionId = $session['id'];
    $recordedScore = $session['score'];
    
    // Hitung jawaban benar
    $answersQuery = "SELECT COUNT(*) as total, SUM(CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN 1 ELSE 0 END) as correct,
                            SUM(CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN q.points ELSE 0 END) as points
                     FROM answers a 
                     JOIN questions q ON a.question_id = q.id 
                     WHERE a.session_id = ?";
    $stmt = $conn->prepare($answersQuery);
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $answersResult = $stmt->get_result();
    $answers = $answersResult->fetch_assoc();
    
    $totalAnswers = $answers['total'] ?? 0;
    $correctAnswers = $answers['correct'] ?? 0;
    $calculatedScore = $answers['points'] ?? 0;
    
    $rowClass = ($recordedScore == $calculatedScore) ? 'ok' : 'problem';
    $status = ($recordedScore == $calculatedScore) ? '✓ OK' : '✗ TIDAK COCOK';
    
    if ($recordedScore != $calculatedScore) {
        $problemCount++;
    } else {
        $okCount++;
    }
    
    echo "<tr class='$rowClass'>";
    echo "<td>$email</td>";
    echo "<td>$fullName</td>";
    echo "<td><strong>$recordedScore</strong></td>";
    echo "<td>$correctAnswers</td>";
    echo "<td>$totalAnswers</td>";
    echo "<td><strong>$calculatedScore</strong></td>";
    echo "<td><strong>$status</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<div class='summary'>";
echo "<h2>RINGKASAN</h2>";
echo "<p>Total Siswa OK: <strong style='color:green;'>$okCount</strong></p>";
echo "<p>Total Siswa dengan Masalah: <strong style='color:red;'>$problemCount</strong></p>";
echo "</div>";

$conn->close();
?>

</body>
</html>
