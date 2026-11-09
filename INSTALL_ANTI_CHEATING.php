<?php
/**
 * INSTALL ANTI-CHEATING SYSTEM
 * 
 * Script ini akan menambahkan:
 * 1. Kolom violation_count, suspended_at, suspension_reason ke exam_sessions
 * 2. Tabel exam_violations untuk log pelanggaran
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install Anti-Cheating System</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #2c3e50; }
        .success { color: #27ae60; padding: 15px; background: #d5f4e6; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; padding: 15px; background: #fadbd8; border-radius: 5px; margin: 10px 0; }
        .step { padding: 10px; margin: 10px 0; border-left: 4px solid #3498db; }
        .feature { padding: 10px; margin: 5px 0; background: #ecf0f1; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
<h1>🔒 Install Anti-Cheating System</h1>

<?php
try {
    $db = db();
    
    echo "<div class='step'><strong>Step 1:</strong> Menambahkan kolom ke exam_sessions...</div>";
    
    // Add columns to exam_sessions
    $queries = [
        "ALTER TABLE exam_sessions ADD COLUMN IF NOT EXISTS violation_count INT DEFAULT 0",
        "ALTER TABLE exam_sessions ADD COLUMN IF NOT EXISTS suspended_at DATETIME NULL",
        "ALTER TABLE exam_sessions ADD COLUMN IF NOT EXISTS suspension_reason VARCHAR(255) NULL"
    ];
    
    foreach ($queries as $query) {
        try {
            $db->exec($query);
        } catch (Exception $e) {
            // Ignore if column already exists
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
    }
    
    echo "<div class='success'>✅ Kolom berhasil ditambahkan</div>";
    
    echo "<div class='step'><strong>Step 2:</strong> Membuat tabel exam_violations...</div>";
    
    // Create exam_violations table
    $db->exec("
        CREATE TABLE IF NOT EXISTS exam_violations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            user_id INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            violation_count INT NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_session (session_id),
            INDEX idx_user (user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "<div class='success'>✅ Tabel exam_violations berhasil dibuat</div>";
    
    echo "<div class='step'><strong>Step 3:</strong> Update existing sessions...</div>";
    
    // Update existing sessions
    $stmt = $db->exec("UPDATE exam_sessions SET violation_count = 0 WHERE violation_count IS NULL");
    
    echo "<div class='success'>✅ Existing sessions berhasil diupdate</div>";
    
    // Summary
    echo "<div class='success' style='margin-top: 30px; font-size: 18px;'>";
    echo "<h2 style='margin-top: 0;'>🎉 INSTALASI BERHASIL!</h2>";
    echo "<p><strong>Anti-Cheating System sudah aktif!</strong></p>";
    echo "<hr>";
    echo "<h3>Fitur yang Aktif:</h3>";
    echo "<div class='feature'>✅ Auto Fullscreen - Ujian otomatis fullscreen</div>";
    echo "<div class='feature'>✅ Deteksi Tab Switching - Deteksi jika siswa pindah tab</div>";
    echo "<div class='feature'>✅ Deteksi Window Blur - Deteksi jika siswa pindah aplikasi</div>";
    echo "<div class='feature'>✅ Sistem Pelanggaran - Max 3x pelanggaran</div>";
    echo "<div class='feature'>✅ Auto Suspend - Otomatis suspend jika 3x pelanggaran</div>";
    echo "<div class='feature'>✅ Log Pelanggaran - Semua pelanggaran tercatat di database</div>";
    echo "<hr>";
    echo "<p><strong>Cara Kerja:</strong></p>";
    echo "<ol>";
    echo "<li>Saat siswa mulai ujian, layar otomatis fullscreen</li>";
    echo "<li>Jika siswa keluar fullscreen, pindah tab, atau pindah aplikasi = PELANGGARAN</li>";
    echo "<li>Setiap pelanggaran tercatat dan siswa diberi warning</li>";
    echo "<li>Setelah 3x pelanggaran, ujian otomatis di-suspend</li>";
    echo "<li>Guru bisa lihat log pelanggaran di monitoring</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='dashboard'>← Kembali ke Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Jika error 'Duplicate column', abaikan saja. Artinya kolom sudah ada.</p>";
}
?>

</div>
</body>
</html>
