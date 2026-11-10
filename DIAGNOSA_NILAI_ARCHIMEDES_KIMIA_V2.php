<?php
// Direct database connection
$db_host = 'localhost';
$db_name = 'u245247469_catbkn';
$db_user = 'u245247469_enterprise';
$db_pass = '1.SMAUnggul234!!';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Daftar siswa dari X-ARCHIMEDES UTS KIMIA X
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

echo "<h2>DIAGNOSA NILAI SISWA X-ARCHIMEDES UTS KIMIA X</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
echo "<tr style='background-color:#f0f0f0;'><th>Email</th><th>Nama</th><th>Nilai Terekam</th><th>Jumlah Jawaban</th><th>Jawaban Benar</th><th>Nilai Seharusnya</th><th>Status</th></tr>";

foreach ($students as $email => $fullName) {
    // Cari user
    $userQuery = "SELECT id, name FROM users WHERE email = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if ($userResult->num_rows === 0) {
        echo "<tr><td>$email</td><td>$fullName</td><td colspan='5' style='color:red;'>USER TIDAK DITEMUKAN</td></tr>";
        continue;
    }
    
    $user = $userResult->fetch_assoc();
    $userId = $user['id'];
    
    // Cari exam session untuk UTS KIMIA X
    $sessionQuery = "SELECT es.id, es.score FROM exam_sessions es 
                     JOIN exams e ON es.exam_id = e.id 
                     WHERE es.user_id = ? AND e.name LIKE '%KIMIA%' AND e.class LIKE '%ARCHIMEDES%'";
    $stmt = $conn->prepare($sessionQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $sessionResult = $stmt->get_result();
    
    if ($sessionResult->num_rows === 0) {
        echo "<tr><td>$email</td><td>$fullName</td><td colspan='5' style='color:orange;'>TIDAK ADA SESSION UJIAN</td></tr>";
        continue;
    }
    
    $session = $sessionResult->fetch_assoc();
    $sessionId = $session['id'];
    $recordedScore = $session['score'];
    
    // Hitung jawaban benar dari answers table
    $answersQuery = "SELECT COUNT(*) as total_answers, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers FROM answers WHERE session_id = ?";
    $stmt = $conn->prepare($answersQuery);
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $answersResult = $stmt->get_result();
    $answers = $answersResult->fetch_assoc();
    
    $totalAnswers = $answers['total_answers'];
    $correctAnswers = $answers['correct_answers'] ?? 0;
    
    // Hitung nilai seharusnya (100 poin / total soal)
    $calculatedScore = ($totalAnswers > 0) ? round(($correctAnswers / $totalAnswers) * 100) : 0;
    
    $status = ($recordedScore == $calculatedScore) ? "✓ OK" : "✗ TIDAK COCOK";
    $statusColor = ($recordedScore == $calculatedScore) ? "green" : "red";
    
    echo "<tr>";
    echo "<td>$email</td>";
    echo "<td>$fullName</td>";
    echo "<td><strong>$recordedScore</strong></td>";
    echo "<td>$totalAnswers</td>";
    echo "<td>$correctAnswers</td>";
    echo "<td>$calculatedScore</td>";
    echo "<td style='color:$statusColor;'><strong>$status</strong></td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();
?>
