<?php
// =====================================================
// EXPORT NILAI LANGSUNG - TANPA LOGIN
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config
$config = require __DIR__ . '/config.production.php';

// Koneksi database langsung
try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Jika ada parameter download, langsung export
if (isset($_GET['download'])) {
    try {
        $examId = $_GET['exam_id'] ?? null;
        $kelas = $_GET['kelas'] ?? null;
        
        $query = "
            SELECT 
                u.username,
                u.full_name,
                u.kelas,
                e.title as ujian,
                s.name as mata_pelajaran,
                COALESCE(es.score, 0) as nilai,
                es.started_at as waktu_mulai,
                es.submitted_at as waktu_selesai,
                es.status
            FROM exam_sessions es
            JOIN users u ON es.user_id = u.id
            JOIN exams e ON es.exam_id = e.id
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE es.submitted_at IS NOT NULL
        ";
        
        $params = [];
        
        if ($examId) {
            $query .= " AND es.exam_id = ?";
            $params[] = $examId;
        }
        
        if ($kelas) {
            $query .= " AND u.kelas = ?";
            $params[] = $kelas;
        }
        
        $query .= " ORDER BY u.kelas, u.full_name, es.submitted_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = 'nilai_' . ($kelas ? 'kelas_' . $kelas . '_' : '') . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Username', 'Nama Lengkap', 'Kelas', 'Ujian', 'Mata Pelajaran', 'Nilai', 'Waktu Mulai', 'Waktu Selesai', 'Status']);
        
        foreach ($results as $row) {
            fputcsv($output, [
                $row['username'],
                $row['full_name'],
                $row['kelas'],
                $row['ujian'],
                $row['mata_pelajaran'],
                number_format($row['nilai'], 2),
                $row['waktu_mulai'],
                $row['waktu_selesai'],
                $row['status']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// Tampilkan form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Export Nilai</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        button { width: 100%; padding: 12px 20px; background: #10b981; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 4px; font-weight: bold; }
        button:hover { background: #059669; }
        .info { background: #e0f2fe; padding: 15px; border-left: 4px solid #0284c7; margin-bottom: 25px; border-radius: 4px; }
        .quick-links { margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; }
        .quick-links h3 { color: #555; font-size: 16px; }
        .quick-links ul { list-style: none; padding: 0; }
        .quick-links li { margin: 10px 0; }
        .quick-links a { color: #0284c7; text-decoration: none; padding: 8px 12px; display: inline-block; background: #f0f9ff; border-radius: 4px; }
        .quick-links a:hover { background: #e0f2fe; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Export Nilai</h1>
        
        <div class="info">
            <strong>Info:</strong> Halaman ini untuk export nilai semua siswa yang sudah submit ujian.
        </div>
        
        <form method="GET">
            <input type="hidden" name="download" value="1">
            
            <div class="form-group">
                <label>Pilih Ujian (Opsional)</label>
                <select name="exam_id">
                    <option value="">-- Semua Ujian --</option>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT id, title FROM exams ORDER BY created_at DESC LIMIT 50");
                        while ($exam = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$exam['id']}'>{$exam['title']}</option>";
                        }
                    } catch (Exception $e) {
                        echo "<option value=''>Error loading exams</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Pilih Kelas (Opsional)</label>
                <select name="kelas">
                    <option value="">-- Semua Kelas --</option>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT DISTINCT kelas FROM users WHERE kelas IS NOT NULL ORDER BY kelas");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$row['kelas']}'>{$row['kelas']}</option>";
                        }
                    } catch (Exception $e) {
                        echo "<option value=''>Error loading classes</option>";
                    }
                    ?>
                </select>
            </div>
            
            <button type="submit">📥 Download CSV</button>
        </form>
        
        <div class="quick-links">
            <h3>Quick Links:</h3>
            <ul>
                <li><a href="export_nilai_langsung.php?download=1">📥 Download Semua Nilai</a></li>
                <li><a href="dashboard">🏠 Kembali ke Dashboard</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
