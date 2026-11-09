<?php
/**
 * DEBUG EXPORT SIMPLE - CEK KENAPA EXPORT KOSONG
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config
$config = require __DIR__ . '/config.php';

echo "<h1>Debug Export Nilai</h1>";
echo "<pre>";

try {
    // Connect to database
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $db = new PDO($dsn, $config['db_user'], $config['db_pass']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "✅ Koneksi database OK\n\n";
    
    // Test 1: Total exam_sessions
    echo "=== TEST 1: Total Exam Sessions ===\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM exam_sessions");
    $total = $stmt->fetch()['total'];
    echo "Total exam_sessions: $total\n\n";
    
    // Test 2: Status breakdown
    echo "=== TEST 2: Status Breakdown ===\n";
    $stmt = $db->query("
        SELECT 
            status,
            COUNT(*) as jumlah,
            COUNT(CASE WHEN submitted_at IS NOT NULL THEN 1 END) as ada_submit
        FROM exam_sessions 
        GROUP BY status
    ");
    while ($row = $stmt->fetch()) {
        echo "Status: {$row['status']} | Jumlah: {$row['jumlah']} | Ada submitted_at: {$row['ada_submit']}\n";
    }
    echo "\n";
    
    // Test 3: Yang sudah submit
    echo "=== TEST 3: Yang Sudah Submit ===\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM exam_sessions WHERE submitted_at IS NOT NULL");
    $submitted = $stmt->fetch()['total'];
    echo "Total yang sudah submit: $submitted\n\n";
    
    // Test 4: Query export
    echo "=== TEST 4: Query Export (Yang Dipakai Controller) ===\n";
    $query = "
        SELECT 
            u.username,
            u.full_name,
            u.kelas,
            e.title as ujian,
            s.name as mata_pelajaran,
            COALESCE(es.score, 0) as nilai,
            es.started_at,
            es.submitted_at,
            es.status
        FROM exam_sessions es
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE es.submitted_at IS NOT NULL
        AND es.status IN ('submitted', 'completed', 'graded')
        LIMIT 5
    ";
    
    $stmt = $db->query($query);
    $results = $stmt->fetchAll();
    
    echo "Hasil query: " . count($results) . " baris\n";
    
    if (count($results) == 0) {
        echo "❌ TIDAK ADA DATA!\n\n";
        
        // Cek tanpa filter status
        echo "=== TEST 5: Cek Tanpa Filter Status ===\n";
        $altQuery = "
            SELECT 
                u.username,
                u.full_name,
                e.title as ujian,
                es.status,
                es.submitted_at
            FROM exam_sessions es
            JOIN users u ON es.user_id = u.id
            JOIN exams e ON es.exam_id = e.id
            WHERE es.submitted_at IS NOT NULL
            LIMIT 5
        ";
        
        $stmt = $db->query($altQuery);
        $altResults = $stmt->fetchAll();
        
        echo "Hasil tanpa filter status: " . count($altResults) . " baris\n";
        
        if (count($altResults) > 0) {
            echo "\n📋 Data yang ditemukan:\n";
            foreach ($altResults as $row) {
                echo "- {$row['username']} | {$row['full_name']} | {$row['ujian']} | Status: {$row['status']} | Submit: {$row['submitted_at']}\n";
            }
            echo "\n❌ MASALAH: Status bukan 'submitted', 'completed', atau 'graded'!\n";
        }
    } else {
        echo "✅ ADA DATA!\n\n";
        echo "📋 Sample data:\n";
        foreach ($results as $row) {
            echo "- {$row['username']} | {$row['full_name']} | {$row['ujian']} | Nilai: {$row['nilai']} | Status: {$row['status']}\n";
        }
    }
    
    // Test 6: Semua status yang ada
    echo "\n=== TEST 6: Semua Status di Database ===\n";
    $stmt = $db->query("SELECT DISTINCT status FROM exam_sessions ORDER BY status");
    $statuses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    echo "Status yang ada: " . implode(", ", $statuses) . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";
