<?php
/**
 * 🔍 CEK STATUS SISWA SPESIFIK
 * Tool untuk cek apakah siswa benar-benar sudah submit atau belum
 */

session_start();
require_once 'config.php';
require_once 'app/helpers.php';

// HANYA ADMIN/GURU YANG BISA AKSES
requireAuth();
requireRole(['admin', 'guru']);

// Daftar siswa yang dilaporkan
$studentsToCheck = [
    'christian.jhesenta@student.ubkn.sch.id',
    'cristofel.septa@student.ubkn.sch.id',
    'hary.wahyu@student.ubkn.sch.id',
    'yogi.erdisyah@student.ubkn.sch.id'
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Cek Status Siswa - CAT Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        .container { max-width: 1200px; background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-submitted { background: #d1fae5; border-left: 4px solid #10b981; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem; }
        .status-active { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem; }
        .status-not-found { background: #fee2e2; border-left: 4px solid #ef4444; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Cek Status Siswa Spesifik</h1>
        <p>Mengecek status 4 siswa yang dilaporkan sudah submit tapi tidak terlihat di monitoring</p>
        
        <hr>
        
        <?php
        foreach ($studentsToCheck as $email) {
            echo '<h4>📧 ' . htmlspecialchars($email) . '</h4>';
            
            try {
                // Cari user berdasarkan username (bagian sebelum @)
                $username = explode('@', $email)[0];
                $username = str_replace('.', '_', $username); // Ganti titik dengan underscore
                
                // Coba beberapa variasi username
                $possibleUsernames = [
                    $username,
                    str_replace('_', '.', $username),
                    explode('@', $email)[0] // Original
                ];
                
                $user = null;
                foreach ($possibleUsernames as $tryUsername) {
                    $stmt = db()->prepare("SELECT * FROM users WHERE username LIKE ? LIMIT 1");
                    $stmt->execute(['%' . $tryUsername . '%']);
                    $user = $stmt->fetch();
                    if ($user) break;
                }
                
                if (!$user) {
                    echo '<div class="status-not-found">';
                    echo '❌ <strong>User tidak ditemukan di database</strong><br>';
                    echo 'Email/username: ' . htmlspecialchars($email) . '<br>';
                    echo 'Kemungkinan: username di database berbeda dengan email yang dilaporkan';
                    echo '</div>';
                    continue;
                }
                
                echo '<div class="alert alert-info">';
                echo '✅ <strong>User ditemukan:</strong><br>';
                echo '- ID: ' . $user['id'] . '<br>';
                echo '- Username: ' . htmlspecialchars($user['username']) . '<br>';
                echo '- Nama: ' . htmlspecialchars($user['full_name']) . '<br>';
                echo '- Kelas: ' . htmlspecialchars($user['kelas'] ?? '-') . '<br>';
                echo '</div>';
                
                // Cek semua exam sessions untuk user ini
                $stmt = db()->prepare("
                    SELECT es.*, e.title, e.start_time, e.end_time
                    FROM exam_sessions es
                    JOIN exams e ON es.exam_id = e.id
                    WHERE es.user_id = ?
                    ORDER BY es.started_at DESC
                ");
                $stmt->execute([$user['id']]);
                $sessions = $stmt->fetchAll();
                
                if (empty($sessions)) {
                    echo '<div class="status-not-found">';
                    echo '⚠️ <strong>Tidak ada exam session</strong><br>';
                    echo 'Siswa ini belum pernah memulai ujian apapun';
                    echo '</div>';
                } else {
                    echo '<h5>📋 Exam Sessions (' . count($sessions) . ' ujian):</h5>';
                    
                    foreach ($sessions as $session) {
                        $statusClass = 'status-active';
                        $statusIcon = '⏳';
                        $statusText = 'Belum Submit';
                        
                        if ($session['status'] === 'submitted' || $session['status'] === 'completed') {
                            $statusClass = 'status-submitted';
                            $statusIcon = '✅';
                            $statusText = 'SUDAH SUBMIT';
                        }
                        
                        echo '<div class="' . $statusClass . '">';
                        echo $statusIcon . ' <strong>' . $statusText . '</strong><br>';
                        echo '- Ujian: ' . htmlspecialchars($session['title']) . '<br>';
                        echo '- Session ID: ' . $session['id'] . '<br>';
                        echo '- Status: <strong>' . htmlspecialchars($session['status']) . '</strong><br>';
                        echo '- Mulai: ' . $session['started_at'] . '<br>';
                        echo '- Submit: ' . ($session['submitted_at'] ?? 'Belum submit') . '<br>';
                        
                        // Cek jumlah jawaban
                        $stmtAnswers = db()->prepare("SELECT COUNT(*) as count FROM answers WHERE session_id = ?");
                        $stmtAnswers->execute([$session['id']]);
                        $answerCount = $stmtAnswers->fetch()['count'];
                        
                        echo '- Jumlah jawaban tersimpan: ' . $answerCount . '<br>';
                        
                        // Jika belum submit, tampilkan tombol force submit
                        if ($session['status'] !== 'submitted' && $session['status'] !== 'completed') {
                            echo '<br>';
                            echo '<form method="POST" style="display: inline;">';
                            echo '<input type="hidden" name="force_submit_session" value="' . $session['id'] . '">';
                            echo '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Force submit session ini?\')">🚨 Force Submit Sekarang</button>';
                            echo '</form>';
                        }
                        
                        echo '</div>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<div class="status-not-found">';
                echo '❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            
            echo '<hr>';
        }
        
        // Handle force submit
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_submit_session'])) {
            $sessionId = $_POST['force_submit_session'];
            
            try {
                $stmt = db()->prepare("
                    UPDATE exam_sessions 
                    SET status = 'submitted', submitted_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$sessionId]);
                
                echo '<div class="alert alert-success">';
                echo '✅ <strong>BERHASIL!</strong> Session ID ' . $sessionId . ' sudah di-submit.<br>';
                echo '<a href="" class="btn btn-primary">Refresh Halaman</a>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">';
                echo '❌ <strong>GAGAL!</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
        ?>
        
        <hr>
        
        <h3>💡 Kesimpulan:</h3>
        <div class="alert alert-info">
            <strong>Jika siswa bilang sudah submit tapi tidak terlihat:</strong><br>
            1. Cek status di atas - apakah benar-benar sudah "submitted"?<br>
            2. Jika masih "active" - gunakan tombol "Force Submit" di atas<br>
            3. Jika sudah "submitted" tapi tidak muncul di monitoring - refresh monitoring dengan Ctrl+F5<br>
            4. Kemungkinan lain: siswa salah lihat, atau monitoring cache belum update
        </div>
        
        <a href="<?= config('app_url') ?>/exam/monitor" class="btn btn-secondary">Kembali ke Monitoring</a>
        <a href="EMERGENCY_SUBMIT_BATCH.php" class="btn btn-warning">Ke Emergency Batch Submit</a>
    </div>
</body>
</html>
