<?php
/**
 * FIX NILAI SISWA X-ARCHIMEDES UTS KIMIA X
 * Script untuk diagnosa dan perbaikan nilai otomatis
 * 
 * Cara pakai:
 * 1. Upload ke Hostinger
 * 2. Akses via browser: https://cat-enterprise.binakasihnusantara.sch.id/FIX_NILAI_KIMIA_ARCHIMEDES.php
 * 3. Klik tombol "PERBAIKI NILAI" untuk fix otomatis
 */

require_once 'config.php';

$config = require 'config.php';
$conn = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name']
);

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

$action = $_POST['action'] ?? 'diagnosa';
$fixedCount = 0;
$problemCount = 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Nilai Kimia X-ARCHIMEDES</title>
    <style>
        body { font-family: Arial; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .ok { background-color: #d4edda; }
        .problem { background-color: #f8d7da; }
        .fixed { background-color: #cfe2ff; }
        .btn { padding: 10px 20px; margin: 10px 0; cursor: pointer; border: none; border-radius: 4px; }
        .btn-fix { background-color: #dc3545; color: white; }
        .btn-fix:hover { background-color: #c82333; }
        .summary { margin: 20px 0; padding: 15px; border: 2px solid #333; border-radius: 4px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h1>FIX NILAI SISWA X-ARCHIMEDES UTS KIMIA X</h1>
    
    <?php if ($action === 'fix'): ?>
        <div class="summary" style="background-color: #fff3cd; border-color: #ff9800;">
            <h2>PROSES PERBAIKAN NILAI...</h2>
        </div>
    <?php endif; ?>
    
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
            
            // Cari exam session - lebih fleksibel
            $sessionQuery = "SELECT es.id, es.score FROM exam_sessions es 
                             JOIN exams e ON es.exam_id = e.id 
                             WHERE es.user_id = ? AND (e.name LIKE '%KIMIA%' OR e.title LIKE '%KIMIA%') 
                             AND (e.class LIKE '%ARCHIMEDES%' OR e.class LIKE '%X%')
                             ORDER BY es.submitted_at DESC LIMIT 1";
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
                
                // Jika action adalah fix, perbaiki nilainya
                if ($action === 'fix') {
                    $updateQuery = "UPDATE exam_sessions SET score = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("di", $calculatedScore, $sessionId);
                    if ($stmt->execute()) {
                        $rowClass = 'fixed';
                        $status = '✓ DIPERBAIKI';
                        $fixedCount++;
                    } else {
                        $status = '✗ GAGAL DIPERBAIKI';
                    }
                }
            } else {
                $problemCount--;
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
    
    <div class="summary">
        <h2>RINGKASAN</h2>
        <?php if ($action === 'fix'): ?>
            <p class="success">✓ Nilai yang diperbaiki: <?php echo $fixedCount; ?></p>
            <p>Total masalah yang ditemukan: <?php echo $problemCount + $fixedCount; ?></p>
        <?php else: ?>
            <p class="error">✗ Ditemukan masalah pada: <?php echo $problemCount; ?> siswa</p>
            <p>Klik tombol di bawah untuk memperbaiki nilai secara otomatis</p>
        <?php endif; ?>
    </div>
    
    <?php if ($action !== 'fix'): ?>
        <form method="POST">
            <input type="hidden" name="action" value="fix">
            <button type="submit" class="btn btn-fix">🔧 PERBAIKI NILAI SEKARANG</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="action" value="diagnosa">
            <button type="submit" class="btn btn-fix">↻ KEMBALI KE DIAGNOSA</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>

<?php
$conn->close();
?>
