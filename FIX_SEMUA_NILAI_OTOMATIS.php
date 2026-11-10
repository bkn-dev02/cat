<?php
/**
 * FIX SEMUA NILAI - AMBIL TERTINGGI OTOMATIS
 * 
 * Script ini akan:
 * 1. Hitung ulang semua nilai berdasarkan jawaban
 * 2. Tandai submission mana yang nilai terbaik
 * 3. Tampilkan statistik hasil
 */

require_once __DIR__ . '/app/helpers.php';

// Set timeout lebih lama
set_time_limit(300); // 5 menit

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Semua Nilai - Ambil Tertinggi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-box h3 { margin: 0; font-size: 36px; }
        .stat-box p { margin: 5px 0 0 0; opacity: 0.9; }
        .progress { background: #e0e0e0; border-radius: 10px; height: 20px; margin: 10px 0; overflow: hidden; }
        .progress-bar { background: #4CAF50; height: 100%; transition: width 0.3s; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 Fix Semua Nilai - Ambil Tertinggi</h1>";
echo "<p><strong>Waktu:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $db = db();
    
    // STEP 1: Cek multiple submissions
    echo "<h2>📊 Step 1: Cek Multiple Submissions</h2>";
    $stmt = $db->query("
        SELECT COUNT(DISTINCT CONCAT(user_id, '-', exam_id)) as total
        FROM (
            SELECT user_id, exam_id, COUNT(*) as jumlah_submit
            FROM exam_sessions
            WHERE status IN ('submitted', 'graded', 'completed')
            GROUP BY user_id, exam_id
            HAVING COUNT(*) > 1
        ) as multiple_subs
    ");
    $multipleCount = $stmt->fetch()['total'];
    
    echo "<div class='info'>";
    echo "<strong>Siswa dengan multiple submissions:</strong> {$multipleCount} siswa";
    echo "</div>";
    
    // STEP 2: Hitung ulang semua nilai
    echo "<h2>🔄 Step 2: Hitung Ulang Semua Nilai</h2>";
    echo "<div class='info'>Sedang menghitung ulang nilai berdasarkan jawaban yang benar...</div>";
    
    $stmt = $db->prepare("
        UPDATE exam_sessions es
        SET score = (
            SELECT COALESCE(SUM(
                CASE WHEN LOWER(a.answer) = LOWER(q.correct_answer) THEN q.points ELSE 0 END
            ), 0)
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = es.id
        )
        WHERE es.status IN ('submitted', 'graded', 'completed')
        AND EXISTS (SELECT 1 FROM answers WHERE session_id = es.id)
    ");
    $stmt->execute();
    $recalculated = $stmt->rowCount();
    
    echo "<div class='success'>";
    echo "<strong>✓ Berhasil menghitung ulang:</strong> {$recalculated} submissions";
    echo "</div>";
    
    // STEP 3: Tambah kolom is_best_score jika belum ada
    echo "<h2>🏗️ Step 3: Setup Database</h2>";
    try {
        $db->exec("ALTER TABLE exam_sessions ADD COLUMN is_best_score TINYINT(1) DEFAULT 1");
        echo "<div class='success'>✓ Kolom is_best_score berhasil ditambahkan</div>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div class='info'>ℹ️ Kolom is_best_score sudah ada</div>";
        } else {
            throw $e;
        }
    }
    
    // STEP 4: Update flag is_best_score
    echo "<h2>🎯 Step 4: Tandai Nilai Terbaik</h2>";
    echo "<div class='info'>Sedang menandai submission dengan nilai tertinggi...</div>";
    
    // Get all user-exam combinations
    $stmt = $db->query("
        SELECT DISTINCT user_id, exam_id
        FROM exam_sessions
        WHERE status IN ('submitted', 'graded', 'completed')
    ");
    $combinations = $stmt->fetchAll();
    
    $updated = 0;
    foreach ($combinations as $combo) {
        // Find best session for this user-exam
        $stmt = $db->prepare("
            SELECT id
            FROM exam_sessions
            WHERE user_id = ? AND exam_id = ?
            AND status IN ('submitted', 'graded', 'completed')
            ORDER BY score DESC, submitted_at DESC
            LIMIT 1
        ");
        $stmt->execute([$combo['user_id'], $combo['exam_id']]);
        $bestSession = $stmt->fetch();
        
        if ($bestSession) {
            // Set this as best
            $stmt = $db->prepare("UPDATE exam_sessions SET is_best_score = 1 WHERE id = ?");
            $stmt->execute([$bestSession['id']]);
            
            // Set others as not best
            $stmt = $db->prepare("
                UPDATE exam_sessions 
                SET is_best_score = 0 
                WHERE user_id = ? AND exam_id = ? AND id != ?
                AND status IN ('submitted', 'graded', 'completed')
            ");
            $stmt->execute([$combo['user_id'], $combo['exam_id'], $bestSession['id']]);
            $updated++;
        }
    }
    
    echo "<div class='success'>";
    echo "<strong>✓ Berhasil memproses:</strong> {$updated} kombinasi siswa-ujian";
    echo "</div>";
    
    // STEP 5: Statistik
    echo "<h2>📈 Step 5: Statistik Hasil</h2>";
    
    $stmt = $db->query("
        SELECT 
            COUNT(CASE WHEN is_best_score = 1 THEN 1 END) as best_count,
            COUNT(CASE WHEN is_best_score = 0 THEN 1 END) as duplicate_count,
            COUNT(*) as total_count
        FROM exam_sessions
        WHERE status IN ('submitted', 'graded', 'completed')
    ");
    $stats = $stmt->fetch();
    
    echo "<div class='stats'>";
    echo "<div class='stat-box'>";
    echo "<h3>{$stats['best_count']}</h3>";
    echo "<p>Nilai Terbaik</p>";
    echo "</div>";
    echo "<div class='stat-box' style='background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);'>";
    echo "<h3>{$stats['duplicate_count']}</h3>";
    echo "<p>Submission Duplikat</p>";
    echo "</div>";
    echo "<div class='stat-box' style='background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);'>";
    echo "<h3>{$stats['total_count']}</h3>";
    echo "<p>Total Submissions</p>";
    echo "</div>";
    echo "</div>";
    
    // STEP 6: Detail per ujian
    echo "<h2>📋 Step 6: Detail Per Ujian</h2>";
    
    $stmt = $db->query("
        SELECT 
            e.title as ujian,
            COUNT(DISTINCT es.user_id) as total_siswa,
            COUNT(*) as total_submissions,
            COUNT(CASE WHEN es.is_best_score = 1 THEN 1 END) as submission_terbaik,
            COUNT(CASE WHEN es.is_best_score = 0 THEN 1 END) as submission_duplikat,
            ROUND(AVG(CASE WHEN es.is_best_score = 1 THEN es.score END), 2) as rata_rata_nilai,
            MIN(CASE WHEN es.is_best_score = 1 THEN es.score END) as nilai_terendah,
            MAX(CASE WHEN es.is_best_score = 1 THEN es.score END) as nilai_tertinggi
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.status IN ('submitted', 'graded', 'completed')
        GROUP BY e.id
        ORDER BY e.title
    ");
    $examStats = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr>
            <th>Ujian</th>
            <th>Total Siswa</th>
            <th>Submissions</th>
            <th>Terbaik</th>
            <th>Duplikat</th>
            <th>Rata-rata</th>
            <th>Min</th>
            <th>Max</th>
          </tr>";
    
    foreach ($examStats as $exam) {
        $duplicatePercent = $exam['total_submissions'] > 0 
            ? round(($exam['submission_duplikat'] / $exam['total_submissions']) * 100, 1) 
            : 0;
        
        echo "<tr>";
        echo "<td><strong>{$exam['ujian']}</strong></td>";
        echo "<td>{$exam['total_siswa']}</td>";
        echo "<td>{$exam['total_submissions']}</td>";
        echo "<td>{$exam['submission_terbaik']}</td>";
        echo "<td>{$exam['submission_duplikat']} ({$duplicatePercent}%)</td>";
        echo "<td>{$exam['rata_rata_nilai']}</td>";
        echo "<td>{$exam['nilai_terendah']}</td>";
        echo "<td>{$exam['nilai_tertinggi']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // STEP 7: Siswa dengan banyak duplikat
    echo "<h2>⚠️ Step 7: Siswa dengan Banyak Duplikat</h2>";
    
    $stmt = $db->query("
        SELECT 
            u.username,
            u.full_name,
            u.kelas,
            e.title as ujian,
            COUNT(*) as jumlah_submit,
            MIN(es.score) as nilai_terendah,
            MAX(es.score) as nilai_tertinggi,
            MAX(es.score) - MIN(es.score) as selisih
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        WHERE es.status IN ('submitted', 'graded', 'completed')
        GROUP BY u.id, e.id
        HAVING COUNT(*) > 2
        ORDER BY jumlah_submit DESC, selisih DESC
        LIMIT 20
    ");
    $topDuplicates = $stmt->fetchAll();
    
    if (count($topDuplicates) > 0) {
        echo "<div class='warning'>";
        echo "<strong>⚠️ Siswa dengan lebih dari 2 submissions:</strong>";
        echo "</div>";
        
        echo "<table>";
        echo "<tr>
                <th>Username</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Ujian</th>
                <th>Jumlah Submit</th>
                <th>Nilai Terendah</th>
                <th>Nilai Tertinggi</th>
                <th>Selisih</th>
              </tr>";
        
        foreach ($topDuplicates as $dup) {
            echo "<tr>";
            echo "<td>{$dup['username']}</td>";
            echo "<td>{$dup['full_name']}</td>";
            echo "<td>{$dup['kelas']}</td>";
            echo "<td>{$dup['ujian']}</td>";
            echo "<td><strong>{$dup['jumlah_submit']}</strong></td>";
            echo "<td>{$dup['nilai_terendah']}</td>";
            echo "<td><strong>{$dup['nilai_tertinggi']}</strong></td>";
            echo "<td>{$dup['selisih']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='success'>✓ Tidak ada siswa dengan lebih dari 2 submissions</div>";
    }
    
    // FINAL MESSAGE
    echo "<h2>✅ Selesai!</h2>";
    echo "<div class='success'>";
    echo "<h3>Perbaikan Berhasil!</h3>";
    echo "<p>✓ Semua nilai sudah dihitung ulang berdasarkan jawaban yang benar</p>";
    echo "<p>✓ Submission terbaik sudah ditandai (is_best_score = 1)</p>";
    echo "<p>✓ Export nilai akan otomatis mengambil nilai tertinggi</p>";
    echo "<p>✓ Submission duplikat tidak akan muncul di export</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>Langkah Selanjutnya:</strong><br>";
    echo "1. Coba export nilai dari dashboard<br>";
    echo "2. Verifikasi bahwa nilai sudah benar<br>";
    echo "3. Jika ada masalah, jalankan script ini lagi<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
    error_log("Fix nilai error: " . $e->getMessage());
}

echo "</div></body></html>";
?>
