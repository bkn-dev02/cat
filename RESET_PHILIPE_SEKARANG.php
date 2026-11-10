<?php
require_once 'config.php';

echo "<h2>RESET PHILIPE CORNELLIUS - URGENT FIX</h2>";

$user_id = 1692;

try {
    // 1. Cek user dulu
    echo "<h3>1. Data User:</h3>";
    $stmt = $pdo->prepare("SELECT id, username, name, class FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // 2. Cek semua sessions sebelum dihapus
    echo "<h3>2. Sessions Sebelum Dihapus:</h3>";
    $stmt = $pdo->prepare("
        SELECT es.id, e.title, es.status, es.start_time
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        WHERE es.user_id = ?
        ORDER BY es.id DESC
    ");
    $stmt->execute([$user_id]);
    $sessions_before = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Session ID</th><th>Exam</th><th>Status</th><th>Start Time</th></tr>";
    foreach ($sessions_before as $s) {
        echo "<tr><td>{$s['id']}</td><td>{$s['title']}</td><td>{$s['status']}</td><td>{$s['start_time']}</td></tr>";
    }
    echo "</table>";
    echo "<p>Total: " . count($sessions_before) . " sessions</p>";
    
    // 3. HAPUS SEMUA SESSIONS
    echo "<h3>3. Menghapus Semua Sessions...</h3>";
    $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $deleted = $stmt->rowCount();
    echo "<p style='color: green; font-weight: bold;'>✓ Berhasil menghapus {$deleted} sessions</p>";
    
    // 4. Verifikasi sessions sudah kosong
    echo "<h3>4. Verifikasi Sessions Setelah Dihapus:</h3>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM exam_sessions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $remaining = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($remaining['total'] == 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ BERHASIL! Tidak ada session tersisa</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ MASIH ADA {$remaining['total']} session!</p>";
    }
    
    // 5. Cek exam yang tersedia
    echo "<h3>5. Exam yang Tersedia untuk User:</h3>";
    $stmt = $pdo->prepare("
        SELECT DISTINCT e.id, e.title, e.status, e.start_time, e.end_time
        FROM exams e
        JOIN exam_assignments ea ON e.id = ea.exam_id
        JOIN users u ON ea.class_id = u.class_id
        WHERE u.id = ?
        ORDER BY e.id DESC
    ");
    $stmt->execute([$user_id]);
    $available = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Exam ID</th><th>Title</th><th>Status</th><th>Start</th><th>End</th></tr>";
    foreach ($available as $exam) {
        echo "<tr><td>{$exam['id']}</td><td>{$exam['title']}</td><td>{$exam['status']}</td><td>{$exam['start_time']}</td><td>{$exam['end_time']}</td></tr>";
    }
    echo "</table>";
    echo "<p>Total: " . count($available) . " exam tersedia</p>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ SELESAI!</h2>";
    echo "<p><strong>PHILIPE CORNELLIUS sekarang bisa ujian kembali.</strong></p>";
    echo "<p>Semua session lama sudah dihapus, user bisa mulai ujian fresh.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>ERROR: " . $e->getMessage() . "</p>";
}
