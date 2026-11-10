<?php
// =====================================================
// EMERGENCY FIX NILAI - PASTI JALAN
// =====================================================
// Akses: https://cat-enterprise.binakasihnusantara.sch.id/2_FIX_NILAI_EMERGENCY.php
// =====================================================

require_once 'config.php';

echo "<h1>EMERGENCY FIX NILAI</h1>";
echo "<p>Waktu: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

try {
    $db = db();
    
    // LANGKAH 1: CEK TABEL APA SAJA YANG ADA
    echo "<h2>1. Tabel yang Ada di Database</h2>";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    $hasExamSessions = in_array('exam_sessions', $tables);
    $hasExamParticipants = in_array('exam_participants', $tables);
    $hasAnswers = in_array('answers', $tables);
    
    echo "<p><strong>exam_sessions:</strong> " . ($hasExamSessions ? "✓ ADA" : "✗ TIDAK ADA") . "</p>";
    echo "<p><strong>exam_participants:</strong> " . ($hasExamParticipants ? "✓ ADA" : "✗ TIDAK ADA") . "</p>";
    echo "<p><strong>answers:</strong> " . ($hasAnswers ? "✓ ADA" : "✗ TIDAK ADA") . "</p>";
    
    echo "<hr>";
    
    // LANGKAH 2: BUAT TABEL EXAM_SESSIONS JIKA TIDAK ADA
    if (!$hasExamSessions) {
        echo "<h2>2. Membuat Tabel exam_sessions...</h2>";
        
        $db->exec("
            CREATE TABLE exam_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_token VARCHAR(64),
                token_status ENUM('active', 'suspended', 'revoked') DEFAULT 'active',
                suspension_reason TEXT,
                suspended_at DATETIME,
                exam_id INT NOT NULL,
                user_id INT NOT NULL,
                remaining_time INT NOT NULL,
                score DECIMAL(5,2) DEFAULT 0,
                status ENUM('active', 'in_progress', 'submitted', 'graded', 'needs_grading') DEFAULT 'active',
                started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                submitted_at DATETIME,
                INDEX idx_exam_user (exam_id, user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "<p style='color: green'>✓ Tabel exam_sessions berhasil dibuat!</p>";
        
        // Migrasi data dari exam_participants jika ada
        if ($hasExamParticipants) {
            echo "<p>Migrasi data dari exam_participants...</p>";
            $db->exec("
                INSERT INTO exam_sessions (exam_id, user_id, remaining_time, status, started_at)
                SELECT 
                    ep.exam_id,
                    ep.user_id,
                    60 * 60 as remaining_time,
                    'active' as status,
                    ep.assigned_at as started_at
                FROM exam_participants ep
                WHERE NOT EXISTS (
                    SELECT 1 FROM exam_sessions es 
                    WHERE es.exam_id = ep.exam_id AND es.user_id = ep.user_id
                )
            ");
            echo "<p style='color: green'>✓ Data berhasil dimigrasi!</p>";
        }
    } else {
        echo "<h2>2. Tabel exam_sessions sudah ada</h2>";
    }
    
    echo "<hr>";
    
    // LANGKAH 3: HITUNG ULANG SEMUA NILAI
    echo "<h2>3. Menghitung Ulang Nilai...</h2>";
    
    if (!$hasExamSessions || !$hasAnswers) {
        echo "<p style='color: red'>Tidak bisa menghitung nilai karena tabel tidak lengkap</p>";
    } else {
        // Ambil semua session yang submitted tapi nilai 0 atau NULL
        $stmt = $db->query("
            SELECT es.id, es.exam_id, es.user_id, u.username, e.title
            FROM exam_sessions es
            JOIN users u ON es.user_id = u.id
            JOIN exams e ON es.exam_id = e.id
            WHERE es.status IN ('submitted', 'graded')
            AND (es.score IS NULL OR es.score = 0)
            ORDER BY es.id DESC
            LIMIT 50
        ");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Ditemukan <strong>" . count($sessions) . "</strong> session dengan nilai kosong</p>";
        
        if (count($sessions) > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse'>";
            echo "<tr><th>Session ID</th><th>Username</th><th>Ujian</th><th>Nilai Lama</th><th>Nilai Baru</th><th>Status</th></tr>";
            
            $fixed = 0;
            foreach ($sessions as $s) {
                // Hitung nilai yang benar (CASE INSENSITIVE)
                $stmt = $db->prepare("
                    SELECT COALESCE(SUM(
                        CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN q.points ELSE 0 END
                    ), 0) as correct_score
                    FROM answers a
                    JOIN questions q ON a.question_id = q.id
                    WHERE a.session_id = ?
                ");
                $stmt->execute([$s['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $newScore = $result['correct_score'];
                
                // Update score
                $stmt = $db->prepare("UPDATE exam_sessions SET score = ? WHERE id = ?");
                $stmt->execute([$newScore, $s['id']]);
                
                echo "<tr>";
                echo "<td>{$s['id']}</td>";
                echo "<td>{$s['username']}</td>";
                echo "<td>{$s['title']}</td>";
                echo "<td>0</td>";
                echo "<td><strong style='color: green'>{$newScore}</strong></td>";
                echo "<td>✓ DIPERBAIKI</td>";
                echo "</tr>";
                
                $fixed++;
            }
            
            echo "</table>";
            echo "<br><p style='color: green; font-size: 18px'><strong>✓ SELESAI! {$fixed} nilai berhasil diperbaiki</strong></p>";
        } else {
            echo "<p style='color: green'>✓ Semua nilai sudah terisi dengan benar</p>";
        }
    }
    
    echo "<hr>";
    
    // LANGKAH 4: VERIFIKASI
    echo "<h2>4. Verifikasi - 10 Session Terakhir</h2>";
    
    if ($hasExamSessions) {
        $stmt = $db->query("
            SELECT es.id, u.username, e.title, es.score, es.status, es.submitted_at
            FROM exam_sessions es
            JOIN users u ON es.user_id = u.id
            JOIN exams e ON es.exam_id = e.id
            WHERE es.status IN ('submitted', 'graded')
            ORDER BY es.id DESC
            LIMIT 10
        ");
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse'>";
        echo "<tr><th>ID</th><th>Username</th><th>Ujian</th><th>Score</th><th>Status</th><th>Submit</th></tr>";
        
        foreach ($recent as $r) {
            $bgColor = ($r['score'] > 0) ? '#ccffcc' : '#ffcccc';
            echo "<tr style='background-color: {$bgColor}'>";
            echo "<td>{$r['id']}</td>";
            echo "<td>{$r['username']}</td>";
            echo "<td>{$r['title']}</td>";
            echo "<td><strong>{$r['score']}</strong></td>";
            echo "<td>{$r['status']}</td>";
            echo "<td>{$r['submitted_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h2>SELESAI!</h2>";
    echo "<p><a href='2_FIX_NILAI_EMERGENCY.php' style='padding: 10px 20px; background: blue; color: white; text-decoration: none'>Refresh Halaman</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffcccc; padding: 20px; border: 2px solid red'>";
    echo "<h2>ERROR</h2>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
