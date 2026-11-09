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
    <title>Nilai Siswa X-ARCHIMEDES</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .problem { background-color: #ffcccc; }
        .ok { background-color: #ccffcc; }
    </style>
</head>
<body>
<div class="container">
    <h1>NILAI SISWA X-ARCHIMEDES UTS KIMIA X</h1>
    
    <table>
        <tr>
            <th>Email</th>
            <th>Nama</th>
            <th>Nilai Terekam</th>
            <th>Jawaban Benar</th>
            <th>Total Jawaban</th>
            <th>Nilai Seharusnya</th>
            <th>Status</th>
        </tr>
        
        <?php
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
        
        $problemCount = 0;
        
        foreach ($students as $email => $fullName) {
            $userQuery = "SELECT id FROM users WHERE email = ?";
            $stmt = $conn->prepare($userQuery);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $userResult = $stmt->get_result();
            
            if ($userResult->num_rows === 0) continue;
            
            $user = $userResult->fetch_assoc();
            $userId = $user['id'];
            
            // Cari session terbaru untuk siswa ini
            $sessionQuery = "SELECT es.id, es.score FROM exam_sessions es 
                             WHERE es.user_id = ? 
                             ORDER BY es.submitted_at DESC LIMIT 1";
            $stmt = $conn->prepare($sessionQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $sessionResult = $stmt->get_result();
            
            if ($sessionResult->num_rows === 0) continue;
            
            $session = $sessionResult->fetch_assoc();
            $sessionId = $session['id'];
            $recordedScore = $session['score'];
            
            // Hitung jawaban benar
            $answersQuery = "SELECT COUNT(*) as total, 
                                    SUM(CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN 1 ELSE 0 END) as correct,
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
            
            $rowClass = 'ok';
            $status = '✓ OK';
            
            if ($recordedScore != $calculatedScore) {
                $rowClass = 'problem';
                $status = '✗ TIDAK COCOK';
                $problemCount++;
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
        ?>
    </table>
    
    <div style="padding: 15px; background: #fff3cd; border: 2px solid #ff9800; border-radius: 4px;">
        <h2>RINGKASAN</h2>
        <p><strong>Total Masalah Ditemukan: <?php echo $problemCount; ?> siswa</strong></p>
        <?php if ($problemCount > 0): ?>
            <p style="color: red;"><strong>⚠️ Ada nilai yang tidak sesuai dengan jawaban yang dijawab!</strong></p>
            <p>Hubungi developer untuk perbaikan nilai otomatis.</p>
        <?php else: ?>
            <p style="color: green;"><strong>✓ Semua nilai sudah sesuai!</strong></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

<?php
$conn->close();
?>
