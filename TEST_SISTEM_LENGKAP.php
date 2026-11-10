<?php
/**
 * TEST SISTEM LENGKAP - Sebelum Pelatihan Guru
 * 
 * Script ini akan mengecek semua fungsi penting:
 * 1. Database connection
 * 2. Format jawaban benar (A,B,C,D,E)
 * 3. Perhitungan nilai
 * 4. Form create/edit soal
 * 5. Submit ujian
 */

require_once 'config.php';

// Force output buffering off
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Sistem Lengkap</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 1000px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 10px; }
        .test-item { padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #ddd; }
        .success { background: #d5f4e6; border-left-color: #27ae60; }
        .error { background: #fadbd8; border-left-color: #e74c3c; }
        .warning { background: #fcf3cf; border-left-color: #f39c12; }
        .info { background: #d6eaf8; border-left-color: #3498db; }
        .icon { font-size: 20px; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ecf0f1; font-weight: bold; }
        .summary { background: #ecf0f1; padding: 20px; border-radius: 5px; margin-top: 30px; }
        .summary h2 { border: none; margin-top: 0; }
    </style>
</head>
<body>
<div class="container">
<h1>🔍 TEST SISTEM LENGKAP - Sebelum Pelatihan Guru</h1>
<p>Mengecek semua fungsi penting sistem CAT...</p>

<?php
$allPassed = true;
$errors = [];
$warnings = [];

try {
    $db = db();
    
    // TEST 1: Database Connection
    echo "<h2>1️⃣ Test Database Connection</h2>";
    try {
        $stmt = $db->query("SELECT 1");
        echo "<div class='test-item success'><span class='icon'>✅</span><strong>Database Connection:</strong> OK</div>";
    } catch (Exception $e) {
        echo "<div class='test-item error'><span class='icon'>❌</span><strong>Database Connection:</strong> FAILED - " . $e->getMessage() . "</div>";
        $allPassed = false;
        $errors[] = "Database connection failed";
    }
    
    // TEST 2: Format Jawaban Benar
    echo "<h2>2️⃣ Test Format Jawaban Benar</h2>";
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN correct_answer IN ('A','B','C','D','E') THEN 1 ELSE 0 END) as uppercase,
            SUM(CASE WHEN correct_answer IN ('a','b','c','d','e') THEN 1 ELSE 0 END) as lowercase
        FROM questions
    ");
    $result = $stmt->fetch();
    
    if ($result['lowercase'] > 0) {
        echo "<div class='test-item warning'><span class='icon'>⚠️</span><strong>Format Jawaban:</strong> Ada {$result['lowercase']} soal dengan huruf kecil (perlu diperbaiki)</div>";
        $warnings[] = "Ada soal dengan format huruf kecil";
    } else {
        echo "<div class='test-item success'><span class='icon'>✅</span><strong>Format Jawaban:</strong> Semua {$result['total']} soal menggunakan huruf besar (A,B,C,D,E)</div>";
    }
    
    // TEST 3: Perhitungan Nilai
    echo "<h2>3️⃣ Test Perhitungan Nilai</h2>";
    $stmt = $db->query("
        SELECT es.id, es.score, es.status,
               (SELECT COUNT(*) FROM answers WHERE session_id = es.id) as answer_count
        FROM exam_sessions es
        WHERE es.status = 'submitted'
        ORDER BY es.submitted_at DESC
        LIMIT 5
    ");
    $sessions = $stmt->fetchAll();
    
    if (count($sessions) > 0) {
        echo "<table>";
        echo "<tr><th>Session ID</th><th>Status</th><th>Nilai</th><th>Jumlah Jawaban</th><th>Status</th></tr>";
        
        $zeroScoreWithAnswers = 0;
        foreach ($sessions as $s) {
            $statusClass = '';
            $statusText = '';
            
            if ($s['score'] == 0 && $s['answer_count'] > 0) {
                $statusClass = 'error';
                $statusText = '❌ Nilai 0 padahal ada jawaban';
                $zeroScoreWithAnswers++;
            } elseif ($s['score'] > 0) {
                $statusClass = 'success';
                $statusText = '✅ OK';
            } else {
                $statusClass = 'info';
                $statusText = 'ℹ️ Tidak ada jawaban';
            }
            
            echo "<tr class='{$statusClass}'>";
            echo "<td>{$s['id']}</td>";
            echo "<td>{$s['status']}</td>";
            echo "<td><strong>{$s['score']}</strong></td>";
            echo "<td>{$s['answer_count']}</td>";
            echo "<td>{$statusText}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($zeroScoreWithAnswers > 0) {
            echo "<div class='test-item error'><span class='icon'>❌</span><strong>Perhitungan Nilai:</strong> Ada {$zeroScoreWithAnswers} session dengan nilai 0 padahal ada jawaban (perlu recalculate)</div>";
            $allPassed = false;
            $errors[] = "Ada session dengan nilai 0 yang perlu diperbaiki";
        } else {
            echo "<div class='test-item success'><span class='icon'>✅</span><strong>Perhitungan Nilai:</strong> Semua session memiliki nilai yang benar</div>";
        }
    } else {
        echo "<div class='test-item info'><span class='icon'>ℹ️</span><strong>Perhitungan Nilai:</strong> Belum ada session ujian yang di-submit</div>";
    }
    
    // TEST 4: Case-Insensitive Comparison
    echo "<h2>4️⃣ Test Case-Insensitive Comparison</h2>";
    
    // Check if code has strtolower
    $examControllerContent = file_get_contents('app/Controllers/ExamController.php');
    if (strpos($examControllerContent, 'strtolower($answer[\'answer\'])') !== false) {
        echo "<div class='test-item success'><span class='icon'>✅</span><strong>ExamController:</strong> Menggunakan case-insensitive comparison</div>";
    } else {
        echo "<div class='test-item error'><span class='icon'>❌</span><strong>ExamController:</strong> Tidak menggunakan case-insensitive comparison</div>";
        $allPassed = false;
        $errors[] = "ExamController tidak menggunakan case-insensitive comparison";
    }
    
    // TEST 5: Dashboard tidak menghitung ulang skor saat halaman dibuka
    echo "<h2>5️⃣ Test Beban Database Dashboard</h2>";
    $dashboardContent = file_get_contents('app/Controllers/DashboardController.php');
    $indexStart = strpos($dashboardContent, 'public function index()');
    $nextMethod = $indexStart === false
        ? false
        : strpos($dashboardContent, 'public function ', $indexStart + strlen('public function index()'));
    $dashboardIndex = $indexStart === false
        ? ''
        : substr($dashboardContent, $indexStart, $nextMethod === false ? null : $nextMethod - $indexStart);
    if ($indexStart !== false && stripos($dashboardIndex, 'UPDATE exam_sessions') === false) {
        echo "<div class='test-item success'><span class='icon'>✅</span><strong>Dashboard:</strong> Tidak menghitung ulang nilai pada setiap akses</div>";
    } else {
        echo "<div class='test-item error'><span class='icon'>❌</span><strong>Dashboard:</strong> Masih ada update sesi ujian saat halaman dibuka</div>";
        $allPassed = false;
        $errors[] = "Dashboard masih mengubah sesi ujian saat halaman dibuka";
    }
    
    // TEST 6: Form Create Question
    echo "<h2>6️⃣ Test Form Create Question</h2>";
    $createFormContent = file_get_contents('views/questions/create.php');
    if (strpos($createFormContent, 'value="A"') !== false) {
        echo "<div class='test-item success'><span class='icon'>✅</span><strong>Form Create:</strong> Menggunakan huruf besar (A,B,C,D,E)</div>";
    } else {
        echo "<div class='test-item error'><span class='icon'>❌</span><strong>Form Create:</strong> Masih menggunakan huruf kecil</div>";
        $allPassed = false;
        $errors[] = "Form create masih menggunakan huruf kecil";
    }
    
    // TEST 7: Form Edit Question
    echo "<h2>7️⃣ Test Form Edit Question</h2>";
    $editFormContent = file_get_contents('views/questions/edit.php');
    if (strpos($editFormContent, "['A', 'B', 'C', 'D', 'E']") !== false) {
        echo "<div class='test-item success'><span class='icon'>✅</span><strong>Form Edit:</strong> Menggunakan huruf besar (A,B,C,D,E)</div>";
    } else {
        echo "<div class='test-item error'><span class='icon'>❌</span><strong>Form Edit:</strong> Masih menggunakan huruf kecil</div>";
        $allPassed = false;
        $errors[] = "Form edit masih menggunakan huruf kecil";
    }
    
    // TEST 8: Statistics
    echo "<h2>8️⃣ Statistik Sistem</h2>";
    
    // Count questions
    $stmt = $db->query("SELECT COUNT(*) as total FROM questions");
    $totalQuestions = $stmt->fetch()['total'];
    
    // Count exams
    $stmt = $db->query("SELECT COUNT(*) as total FROM exams");
    $totalExams = $stmt->fetch()['total'];
    
    // Count users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'siswa'");
    $totalStudents = $stmt->fetch()['total'];
    
    // Count sessions
    $stmt = $db->query("SELECT COUNT(*) as total FROM exam_sessions WHERE status = 'submitted'");
    $totalSubmissions = $stmt->fetch()['total'];
    
    echo "<table>";
    echo "<tr><th>Item</th><th>Jumlah</th></tr>";
    echo "<tr><td>Total Soal</td><td><strong>{$totalQuestions}</strong></td></tr>";
    echo "<tr><td>Total Ujian</td><td><strong>{$totalExams}</strong></td></tr>";
    echo "<tr><td>Total Siswa</td><td><strong>{$totalStudents}</strong></td></tr>";
    echo "<tr><td>Total Submission</td><td><strong>{$totalSubmissions}</strong></td></tr>";
    echo "</table>";
    
    // SUMMARY
    echo "<div class='summary'>";
    echo "<h2>📊 SUMMARY</h2>";
    
    if ($allPassed && count($warnings) == 0) {
        echo "<div class='test-item success' style='font-size: 18px;'>";
        echo "<span class='icon'>🎉</span><strong>SISTEM SIAP UNTUK PELATIHAN!</strong><br>";
        echo "Semua test passed. Tidak ada error atau warning.";
        echo "</div>";
    } elseif ($allPassed && count($warnings) > 0) {
        echo "<div class='test-item warning' style='font-size: 18px;'>";
        echo "<span class='icon'>⚠️</span><strong>SISTEM HAMPIR SIAP</strong><br>";
        echo "Ada beberapa warning yang perlu diperhatikan:";
        echo "<ul>";
        foreach ($warnings as $w) {
            echo "<li>{$w}</li>";
        }
        echo "</ul>";
        echo "<p><strong>Action:</strong> Jalankan script perbaikan sebelum pelatihan.</p>";
        echo "</div>";
    } else {
        echo "<div class='test-item error' style='font-size: 18px;'>";
        echo "<span class='icon'>❌</span><strong>SISTEM BELUM SIAP</strong><br>";
        echo "Ada error yang harus diperbaiki:";
        echo "<ul>";
        foreach ($errors as $e) {
            echo "<li>{$e}</li>";
        }
        echo "</ul>";
        echo "<p><strong>Action:</strong> Perbaiki error di atas sebelum pelatihan.</p>";
        echo "</div>";
    }
    
    // Action Items
    echo "<h3>🔧 Action Items:</h3>";
    echo "<ol>";
    
    if (count($warnings) > 0 || count($errors) > 0) {
        echo "<li><strong>Jalankan Fix Scripts:</strong>";
        echo "<ul>";
        echo "<li><a href='FIX_CORRECT_ANSWER_CASE.php' target='_blank'>FIX_CORRECT_ANSWER_CASE.php</a> - Perbaiki format jawaban</li>";
        echo "<li><a href='RECALCULATE_SCORES_AFTER_FIX.php' target='_blank'>RECALCULATE_SCORES_AFTER_FIX.php</a> - Hitung ulang nilai</li>";
        echo "</ul>";
        echo "</li>";
    }
    
    echo "<li><strong>Test Manual:</strong>";
    echo "<ul>";
    echo "<li>Login sebagai guru</li>";
    echo "<li>Buat soal baru dengan jawaban benar</li>";
    echo "<li>Buat ujian dan assign soal</li>";
    echo "<li>Login sebagai siswa</li>";
    echo "<li>Kerjakan ujian dan submit</li>";
    echo "<li>Cek apakah nilai muncul dengan benar</li>";
    echo "</ul>";
    echo "</li>";
    
    echo "<li><strong>Backup Database:</strong> Backup database sebelum pelatihan untuk jaga-jaga</li>";
    
    echo "</ol>";
    
    echo "<hr>";
    echo "<p><a href='dashboard'>← Kembali ke Dashboard</a> | <a href='TEST_SISTEM_LENGKAP.php'>🔄 Refresh Test</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-item error'><span class='icon'>❌</span><strong>FATAL ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

</div>
</body>
</html>
