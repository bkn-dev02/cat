<?php
/**
 * EMERGENCY: MANUAL SUBMIT UJIAN
 * Gunakan ini jika siswa stuck di loading "Mengumpulkan..."
 */

session_start();
require_once 'config.php';
require_once 'app/helpers.php';

// HANYA ADMIN/GURU YANG BISA AKSES
requireAuth();
requireRole(['admin', 'guru']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Submit - CAT Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        .container { max-width: 800px; background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .alert-danger { background: #fee; border-left: 4px solid #dc3545; }
        .alert-success { background: #d1fae5; border-left: 4px solid #10b981; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #dc3545;">🚨 EMERGENCY: Manual Submit Ujian</h1>
        <p><strong>Gunakan ini jika siswa stuck di loading "Mengumpulkan..."</strong></p>
        
        <div class="alert alert-warning">
            <strong>⚠️ PERHATIAN:</strong><br>
            - Tool ini untuk EMERGENCY saja<br>
            - Hanya admin/guru yang bisa akses<br>
            - Pastikan siswa sudah selesai mengerjakan
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sessionId = $_POST['session_id'] ?? '';
            
            if (empty($sessionId)) {
                echo '<div class="alert alert-danger">Session ID tidak boleh kosong!</div>';
            } else {
                try {
                    // Get session info
                    $stmt = db()->prepare("
                        SELECT es.*, e.title, u.full_name
                        FROM exam_sessions es
                        JOIN exams e ON es.exam_id = e.id
                        JOIN users u ON es.user_id = u.id
                        WHERE es.id = ?
                    ");
                    $stmt->execute([$sessionId]);
                    $session = $stmt->fetch();
                    
                    if (!$session) {
                        echo '<div class="alert alert-danger">Session tidak ditemukan!</div>';
                    } else {
                        // Force submit
                        $stmt = db()->prepare("
                            UPDATE exam_sessions 
                            SET status = 'submitted', submitted_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$sessionId]);
                        
                        echo '<div class="alert alert-success">';
                        echo '<h4>✅ BERHASIL!</h4>';
                        echo '<p><strong>Siswa:</strong> ' . htmlspecialchars($session['full_name']) . '</p>';
                        echo '<p><strong>Ujian:</strong> ' . htmlspecialchars($session['title']) . '</p>';
                        echo '<p><strong>Status:</strong> Submitted</p>';
                        echo '<p><strong>Waktu Submit:</strong> ' . date('Y-m-d H:i:s') . '</p>';
                        echo '<hr>';
                        echo '<p>Siswa sekarang bisa melihat hasil ujian di:</p>';
                        echo '<p><a href="' . config('app_url') . '/exam/result/' . $sessionId . '" class="btn btn-primary" target="_blank">Lihat Hasil Ujian</a></p>';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">';
                    echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
            }
        }
        ?>

        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label class="form-label"><strong>Session ID:</strong></label>
                <input type="number" name="session_id" class="form-control" placeholder="Contoh: 590" required>
                <small class="text-muted">
                    Session ID bisa dilihat di URL ujian siswa:<br>
                    <code>cat-enterprise.binakasihnusantara.sch.id/exam/take/<strong>590</strong></code>
                </small>
            </div>
            
            <button type="submit" class="btn btn-danger">
                🚨 Force Submit Ujian
            </button>
            
            <a href="<?= config('app_url') ?>/exam/monitor" class="btn btn-secondary">
                Kembali ke Monitoring
            </a>
        </form>

        <hr class="my-4">

        <h3>📋 Cara Menggunakan:</h3>
        <ol>
            <li>Tanyakan ke siswa: "Apa URL ujian kamu?"</li>
            <li>Contoh URL: <code>cat-enterprise.../exam/take/590</code></li>
            <li>Ambil angka terakhir (590) = Session ID</li>
            <li>Masukkan Session ID di form atas</li>
            <li>Klik "Force Submit Ujian"</li>
            <li>Ujian akan langsung ter-submit</li>
            <li>Siswa bisa lihat hasil ujian</li>
        </ol>

        <h3>🔍 Cek Session ID dari Monitoring:</h3>
        <p>Atau bisa cek di halaman <a href="<?= config('app_url') ?>/exam/monitor" target="_blank">Monitoring Ujian</a></p>
        <p>Lihat kolom "Session ID" untuk siswa yang bermasalah</p>
    </div>
</body>
</html>
